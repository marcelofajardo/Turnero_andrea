<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\MercadoPagoService;
use App\Application\Services\AppointmentService;
use App\Application\Services\AppointmentAvailabilityService;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Infrastructure\Repositories\SettingsRepository;
use App\Shared\Logging\AppLogger;

/**
 * WebhookController — handles MercadoPago IPN/webhook notifications.
 * This endpoint is public but validates the payload against MP API.
 */
final class WebhookController extends BaseController
{
    public function mercadoPago(): void
    {
        // Capture raw body to log exactly what MP sent
        $rawBody = file_get_contents('php://input');
        AppLogger::info('MP Webhook received', ['body' => $rawBody]);

        $payload = array_merge($_GET, (array)json_decode($rawBody, true));

        try {
            $apptRepo = new AppointmentRepository();
            $serviceRepo = new ServiceRepository();
            $settingsRepo = new SettingsRepository();

            $apptService = new AppointmentService(
                $apptRepo,
                $serviceRepo,
                $settingsRepo,
                new AppointmentAvailabilityService($apptRepo, $settingsRepo),
                );

            $mpService = new MercadoPagoService($apptRepo, $apptService);
            $mpService->processWebhook($payload);

            http_response_code(200);
            echo json_encode(['success' => true]);
        }
        catch (\Throwable $e) {
            AppLogger::error('Webhook processing failed', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}