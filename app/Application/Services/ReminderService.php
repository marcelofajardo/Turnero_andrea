<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Entities\Appointment;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\SettingsRepository;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Shared\Logging\AppLogger;
use DateTimeImmutable;
use DateTimeZone;

/**
 * ReminderService — sends appointment reminders.
 *
 * Called by the cron script (bin/send_reminders.php).
 * Supports WhatsApp link generation and optional email placeholder.
 * Architecture is extensible to a real WhatsApp API with minimal changes.
 */
final class ReminderService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepo,
        private readonly ServiceRepository     $serviceRepo,
        private readonly SettingsRepository    $settingsRepo,
    ) {}

    /**
     * Process all appointments due for a reminder in the next X hours.
     * Returns count of reminders sent.
     */
    public function sendPendingReminders(): int
    {
        $hoursAhead = (int) $this->settingsRepo->get('reminder_hours_before', '24');
        $timezone   = new DateTimeZone($this->settingsRepo->get('timezone', 'UTC'));

        $now  = new DateTimeImmutable('now', $timezone);
        $from = $now->modify("+{$hoursAhead} hours");
        $to   = $from->modify('+1 hour');   // 1-hour window to avoid flooding

        $appointments = $this->appointmentRepo->findDueReminders($from, $to);

        $sent = 0;

        foreach ($appointments as $appointment) {
            try {
                $service = $this->serviceRepo->findById($appointment->getServiceId());
                $this->sendReminder($appointment, $service?->getName() ?? 'Turno');

                $appointment->markReminderSent();
                $this->appointmentRepo->update($appointment);
                $sent++;
            } catch (\Throwable $e) {
                AppLogger::error("Reminder failed for appointment #{$appointment->getId()}", [
                    'error' => $e->getMessage(),
                ]);
                $this->logFailedJob($appointment, $e->getMessage());
            }
        }

        AppLogger::info("Reminder cron complete", ['sent' => $sent]);

        return $sent;
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function sendReminder(Appointment $appointment, string $serviceName): void
    {
        $template = $this->settingsRepo->get(
            'whatsapp_template_reminder',
            'Recordatorio: Mañana {date} a las {time} tenés turno para {service}.'
        );

        $appUrl      = rtrim($_ENV['APP_URL'] ?? '', '/');
        $cancelUrl   = $appUrl . '/cancelar/' . $appointment->getCancellationToken();

        $message = strtr($template, [
            '{name}'       => $appointment->getCustomerName(),
            '{service}'    => $serviceName,
            '{date}'       => $appointment->getAppointmentDatetime()->format('d/m/Y'),
            '{time}'       => $appointment->getAppointmentDatetime()->format('H:i'),
            '{cancel_url}' => $cancelUrl,
        ]);

        $phone      = preg_replace('/[^\d]/', '', $appointment->getCustomerPhone());
        $waLink     = "https://wa.me/{$phone}?text=" . rawurlencode($message);

        // Log the WhatsApp link (production: replace with actual API call)
        AppLogger::info("WhatsApp reminder queued", [
            'appointment_id' => $appointment->getId(),
            'phone'          => $phone,
            'wa_link'        => $waLink,
        ]);

        // Future hook: inject WhatsApp API client and call it here
        // $this->whatsappApiClient->send($phone, $message);
    }

    private function logFailedJob(Appointment $appointment, string $error): void
    {
        try {
            $pdo = \App\Infrastructure\Persistence\DatabaseConnection::getInstance();
            $stmt = $pdo->prepare(
                "INSERT INTO failed_jobs (type, payload, last_error, retry_after)
                 VALUES ('reminder', :payload, :error, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
            );
            $stmt->execute([
                ':payload' => json_encode(['appointment_id' => $appointment->getId()]),
                ':error'   => $error,
            ]);
        } catch (\Throwable) {
            // Silently ignore — logger already captured the error
        }
    }
}