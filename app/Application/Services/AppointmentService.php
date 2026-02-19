<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\CreateAppointmentDTO;
use App\Domain\Entities\Appointment;
use App\Domain\Exceptions\SlotNotAvailableException;
use App\Domain\Exceptions\ValidationException;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Infrastructure\Repositories\SettingsRepository;
use App\Shared\Logging\AppLogger;
use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use PDO;

/**
 * AppointmentService — orchestrates the full booking lifecycle.
 *
 * Responsibilities:
 *  - Validate input data
 *  - Check availability (via AvailabilityService)
 *  - Create appointment record using a DB transaction to prevent race conditions
 *  - Cancel appointments
 *  - Mark appointments as paid (triggered by webhook)
 */
final class AppointmentService
{
    private PDO $pdo;

    public function __construct(
        private readonly AppointmentRepository         $appointmentRepo,
        private readonly ServiceRepository              $serviceRepo,
        private readonly SettingsRepository             $settingsRepo,
        private readonly AppointmentAvailabilityService $availabilityService,
    ) {
        $this->pdo = DatabaseConnection::getInstance();
    }

    /**
     * Create a new appointment in 'pending' status.
     * Uses SELECT FOR UPDATE inside a transaction to prevent double booking.
     *
     * @throws ValidationException
     * @throws SlotNotAvailableException
     */
    public function create(CreateAppointmentDTO $dto): Appointment
    {
        $this->validate($dto);

        $service = $this->serviceRepo->findById($dto->serviceId);
        if ($service === null || !$service->isActive()) {
            throw new ValidationException(['service' => 'El servicio no está disponible.']);
        }

        $timezone = new DateTimeZone(
            $this->settingsRepo->get('timezone', 'America/Argentina/Buenos_Aires')
        );
        $appointmentDt = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            "{$dto->date} {$dto->time}",
            $timezone
        );

        if ($appointmentDt === false) {
            throw new ValidationException(['datetime' => 'Fecha u hora inválida.']);
        }

        $endDt = $appointmentDt->add(new DateInterval("PT{$service->getDurationMinutes()}M"));

        // --- Begin atomic transaction to prevent race conditions ---
        $this->pdo->beginTransaction();

        try {
            // Lock the row for the specific slot to prevent concurrent inserts
            $lockStmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM appointments
                 WHERE service_id = :sid
                   AND appointment_datetime = :dt
                   AND status NOT IN ('cancelled')
                 FOR UPDATE"
            );
            $lockStmt->execute([
                ':sid' => $dto->serviceId,
                ':dt'  => $appointmentDt->format('Y-m-d H:i:s'),
            ]);

            if ((int) $lockStmt->fetchColumn() > 0) {
                $this->pdo->rollBack();
                throw new SlotNotAvailableException();
            }

            $cancellationToken = bin2hex(random_bytes(32));

            $appointment = new Appointment(
                id:                   null,
                serviceId:            $dto->serviceId,
                customerName:         $dto->customerName,
                customerPhone:        $dto->customerPhone,
                customerEmail:        $dto->customerEmail,
                appointmentDatetime:  $appointmentDt,
                endDatetime:          $endDt,
                status:               'pending',
                notes:                $dto->notes,
                cancellationToken:    $cancellationToken,
                reminderSent:         false,
                mercadopagoPaymentId: null,
                createdAt:            new DateTimeImmutable('now'),
            );

            $saved = $this->appointmentRepo->save($appointment);

            $this->pdo->commit();

            AppLogger::info("Appointment created", [
                'appointment_id' => $saved->getId(),
                'service_id'     => $dto->serviceId,
                'datetime'       => $appointmentDt->format('Y-m-d H:i'),
            ]);

            return $saved;
        } catch (SlotNotAvailableException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            AppLogger::error("Failed to create appointment", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cancel an appointment by its cancellation token.
     *
     * @throws \RuntimeException
     */
    public function cancelByToken(string $token): Appointment
    {
        $appointment = $this->appointmentRepo->findByCancellationToken($token);

        if ($appointment === null) {
            throw new \RuntimeException('Turno no encontrado.', 404);
        }

        $appointment->cancel();
        $this->appointmentRepo->update($appointment);

        AppLogger::info("Appointment cancelled", ['appointment_id' => $appointment->getId()]);

        return $appointment;
    }

    /**
     * Mark appointment as paid — called by MercadoPago webhook.
     * Performs atomic update inside transaction.
     */
    public function markAsPaid(int $appointmentId, string $mpPaymentId): Appointment
    {
        $this->pdo->beginTransaction();

        try {
            $appointment = $this->appointmentRepo->findById($appointmentId);

            if ($appointment === null) {
                throw new \RuntimeException("Appointment #{$appointmentId} not found.", 404);
            }

            $appointment->markAsPaid($mpPaymentId);
            $this->appointmentRepo->update($appointment);

            $this->pdo->commit();

            AppLogger::info("Appointment marked as paid", [
                'appointment_id' => $appointmentId,
                'mp_payment_id'  => $mpPaymentId,
            ]);

            return $appointment;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            AppLogger::error("Failed to mark appointment as paid", [
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // -------------------------------------------------------
    // Private validation
    // -------------------------------------------------------

    private function validate(CreateAppointmentDTO $dto): void
    {
        $errors = [];

        if (empty(trim($dto->customerName))) {
            $errors['customer_name'] = 'El nombre es obligatorio.';
        }

        if (!preg_match('/^\+?[0-9\s\-]{7,20}$/', $dto->customerPhone)) {
            $errors['customer_phone'] = 'Teléfono inválido.';
        }

        if ($dto->customerEmail !== null && !filter_var($dto->customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'Email inválido.';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dto->date)) {
            $errors['date'] = 'Fecha inválida.';
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $dto->time)) {
            $errors['time'] = 'Hora inválida.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}