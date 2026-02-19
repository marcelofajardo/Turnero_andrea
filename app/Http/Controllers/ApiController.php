<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\DTOs\CreateAppointmentDTO;
use App\Application\Services\AppointmentAvailabilityService;
use App\Application\Services\AppointmentService;
use App\Application\Services\MercadoPagoService;
use App\Domain\Exceptions\SlotNotAvailableException;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Exceptions\PaymentException;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Infrastructure\Repositories\SettingsRepository;

/**
 * ApiController — handles all internal AJAX/REST calls.
 * Returns structured JSON. No HTML output.
 */
final class ApiController extends BaseController
{
    /**
     * GET /api/available-slots?date=YYYY-MM-DD&service_id=X
     */
    public function availableSlots(): void
    {
        $date      = $_GET['date']       ?? '';
        $serviceId = (int) ($_GET['service_id'] ?? 0);

        if (empty($date) || $serviceId === 0) {
            $this->json(false, 'Parámetros requeridos: date, service_id', null, 400);
        }

        $settingsRepo = new SettingsRepository();
        $serviceRepo  = new ServiceRepository();
        $apptRepo     = new AppointmentRepository();

        $service = $serviceRepo->findById($serviceId);
        if ($service === null || !$service->isActive()) {
            $this->json(false, 'Servicio no disponible.', null, 404);
        }

        $duration = $service->getDurationMinutes();

        $availabilityService = new AppointmentAvailabilityService($apptRepo, $settingsRepo);
        $slots = $availabilityService->getAvailableSlots($serviceId, $date, $duration);

        $this->json(true, 'OK', [
            'date'       => $date,
            'service_id' => $serviceId,
            'duration'   => $duration,
            'price'      => $service->getPrice(),
            'slots'      => $slots,
        ]);
    }

    /**
     * POST /api/appointments
     * Body: { service_id, customer_name, customer_phone, customer_email?, date, time, notes? }
     */
    public function createAppointment(): void
    {
        $body = array_merge($_POST, $this->getJsonBody());

        try {
            $dto = CreateAppointmentDTO::fromArray($body);

            $apptService = new AppointmentService(
                new AppointmentRepository(),
                new ServiceRepository(),
                new SettingsRepository(),
                new AppointmentAvailabilityService(new AppointmentRepository(), new SettingsRepository()),
            );

            $appointment = $apptService->create($dto);

            // Create MP Preference
            $serviceRepo  = new ServiceRepository();
            $service      = $serviceRepo->findById($dto->serviceId);

            $mpService = new MercadoPagoService(
                new AppointmentRepository(),
                $apptService,
            );

            $preference = $mpService->createPreference(
                $appointment->getId(),
                $service?->getName() ?? 'Turno',
                (float) ($service?->getPrice() ?? 0),
            );

            $this->json(true, 'Turno creado. Redirigiendo al pago.', [
                'appointment_id' => $appointment->getId(),
                'init_point'     => $preference['init_point'],
                'preference_id'  => $preference['preference_id'],
                'cancel_token'   => $appointment->getCancellationToken(),
            ]);
        } catch (ValidationException $e) {
            $this->json(false, 'Error de validación.', ['errors' => $e->getErrors()], 422);
        } catch (SlotNotAvailableException $e) {
            $this->json(false, $e->getMessage(), null, 409);
        } catch (PaymentException $e) {
            $this->json(false, $e->getMessage(), null, 402);
        } catch (\Throwable $e) {
            $this->json(false, 'Error interno. Intente nuevamente.', null, 500);
        }
    }

    /**
     * POST /api/cancel  { token: string }
     */
    public function cancelAppointment(): void
    {
        $body  = array_merge($_POST, $this->getJsonBody());
        $token = $body['token'] ?? '';

        if (empty($token)) {
            $this->json(false, 'Token requerido.', null, 400);
        }

        try {
            $apptService = new AppointmentService(
                new AppointmentRepository(),
                new ServiceRepository(),
                new SettingsRepository(),
                new AppointmentAvailabilityService(new AppointmentRepository(), new SettingsRepository()),
            );
            $apptService->cancelByToken($token);
            $this->json(true, 'Turno cancelado correctamente.');
        } catch (\RuntimeException $e) {
            $this->json(false, $e->getMessage(), null, $e->getCode() ?: 400);
        }
    }
}