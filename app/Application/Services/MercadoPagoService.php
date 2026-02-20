<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Exceptions\PaymentException;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Shared\Logging\AppLogger;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Preference;
use PDO;

/**
 * MercadoPagoService — handles all MP SDK interactions.
 *
 * Responsibilities:
 *  - SDK initialization
 *  - Preference creation
 *  - Webhook payload validation and idempotent payment processing
 *  - Logging all transactions to the payments table
 */
final class MercadoPagoService
{
    private PDO $pdo;

    public function __construct(
        private readonly AppointmentRepository $appointmentRepo,
        private readonly AppointmentService    $appointmentService,
    ) {
        $this->pdo = DatabaseConnection::getInstance();
        $this->initSdk();
    }

    private function initSdk(): void
    {
        $accessToken = $_ENV['MP_ACCESS_TOKEN'] ?? '';

        if (empty($accessToken)) {
            AppLogger::warning('MercadoPago access token not configured.');
            return;
        }

        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
    }

    /**
     * Create a MercadoPago Preference for an appointment.
     *
     * @return array{'init_point': string, 'preference_id': string}
     * @throws PaymentException
     */
    public function createPreference(int $appointmentId, string $serviceTitle, float $amount): array
    {
        try {
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
            $basePath = $_ENV['APP_BASE_PATH'] ?? '';
            $fullUrl = $appUrl . $basePath;

            $client = new PreferenceClient();

            $body = [
                'items' => [[
                    'title'       => "Turno: {$serviceTitle}",
                    'quantity'    => 1,
                    'unit_price'  => $amount,
                    'currency_id' => 'ARS',
                ]],
                'back_urls' => [
                    'success' => "{$fullUrl}/pago/exito?appt={$appointmentId}",
                    'failure' => "{$fullUrl}/pago/fallo?appt={$appointmentId}",
                    'pending' => "{$fullUrl}/pago/pendiente?appt={$appointmentId}",
                ],
                'auto_return'         => 'approved',
                'notification_url'    => "{$fullUrl}/webhook/mercadopago",
                'external_reference'  => (string) $appointmentId,
                'expires'             => true,
                'expiration_date_to'  => date('Y-m-d\TH:i:s.000P', strtotime('+30 minutes')),
            ];

            $preference = $client->create($body);

            $initPoint = ($_ENV['MP_SANDBOX'] === 'true')
                ? $preference->sandbox_init_point
                : $preference->init_point;

            AppLogger::info("MP Preference created", [
                'preference_id'  => $preference->id,
                'appointment_id' => $appointmentId,
            ]);

            return [
                'init_point'    => $initPoint,
                'preference_id' => $preference->id,
            ];
        } catch (\Throwable $e) {
            AppLogger::error("Failed to create MP preference", ['error' => $e->getMessage()]);
            throw new PaymentException('No se pudo iniciar el proceso de pago. Intente nuevamente.');
        }
    }

    /**
     * Process an incoming webhook notification from MercadoPago.
     * Implements idempotency: skip if payment already recorded.
     *
     * @param array $payload Raw POST/GET data from MP webhook
     * @throws PaymentException
     */
    public function processWebhook(array $payload): void
    {
        $type      = $payload['type']    ?? $payload['topic'] ?? '';
        $paymentId = $payload['data']['id'] ?? $payload['data_id'] ?? null;

        if ($type !== 'payment' || empty($paymentId)) {
            AppLogger::info('MP webhook received but not a payment event.', ['payload' => $payload]);
            return;
        }

        $this->logWebhook($paymentId, $payload);

        // Idempotency check — skip if this payment ID was already processed
        if ($this->isPaymentAlreadyProcessed((string) $paymentId)) {
            AppLogger::info("MP payment already processed, skipping.", ['mp_payment_id' => $paymentId]);
            return;
        }

        try {
            $client  = new PaymentClient();
            $payment = $client->get((int) $paymentId);

            AppLogger::info("MP payment fetched", [
                'mp_payment_id' => $paymentId,
                'status'        => $payment->status,
                'external_ref'  => $payment->external_reference,
            ]);

            // Find the appointment by external_reference
            $appointmentId = (int) ($payment->external_reference ?? 0);
            if ($appointmentId === 0) {
                throw new PaymentException("Missing external_reference in payment #{$paymentId}");
            }

            // Record payment regardless of status (full audit trail)
            $this->recordPayment(
                appointmentId: $appointmentId,
                mpPaymentId:   (string) $paymentId,
                mpStatus:      $payment->status ?? 'unknown',
                mpStatusDetail: $payment->status_detail ?? '',
                amount:        (float) ($payment->transaction_amount ?? 0),
                rawResponse:   json_encode($payment),
            );

            if ($payment->status === 'approved') {
                $this->appointmentService->markAsPaid($appointmentId, (string) $paymentId);
            } elseif (in_array($payment->status, ['rejected', 'cancelled'], true)) {
                AppLogger::warning("MP payment rejected/cancelled", [
                    'mp_payment_id' => $paymentId,
                    'status_detail' => $payment->status_detail,
                ]);
            }
        } catch (\Throwable $e) {
            AppLogger::error("Error processing MP webhook", [
                'mp_payment_id' => $paymentId,
                'error'         => $e->getMessage(),
            ]);
            throw new PaymentException("Webhook processing error: " . $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function isPaymentAlreadyProcessed(string $mpPaymentId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM payments WHERE mp_payment_id = :pid'
        );
        $stmt->execute([':pid' => $mpPaymentId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function recordPayment(
        int    $appointmentId,
        string $mpPaymentId,
        string $mpStatus,
        string $mpStatusDetail,
        float  $amount,
        string $rawResponse,
    ): void {
        $sql = "INSERT IGNORE INTO payments
                    (appointment_id, mp_payment_id, mp_status, mp_status_detail, amount, raw_response, processed_at)
                VALUES
                    (:appt_id, :mp_pid, :status, :status_detail, :amount, :raw, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':appt_id'      => $appointmentId,
            ':mp_pid'       => $mpPaymentId,
            ':status'       => $mpStatus,
            ':status_detail'=> $mpStatusDetail,
            ':amount'       => $amount,
            ':raw'          => $rawResponse,
        ]);
    }

    private function logWebhook(string $paymentId, array $payload): void
    {
        $logPath = $_ENV['LOG_PATH'] ?? 'storage/logs/app.log';
        $webhookLog = str_replace('app.log', 'webhooks.log', $logPath);
        $entry = date('Y-m-d H:i:s') . " [WEBHOOK] payment_id={$paymentId} " . json_encode($payload) . "\n";
        @file_put_contents($webhookLog, $entry, FILE_APPEND);
    }
}