<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Infrastructure\Repositories\SettingsRepository;

/**
 * HomeController — public booking page.
 * Only responsibility: load services and render the view.
 */
final class HomeController extends BaseController
{
    public function index(): void
    {
        $serviceRepo = new ServiceRepository();
        $services = $serviceRepo->findAll(true);
        $settings = new SettingsRepository();
        $businessName = $settings->get('business_name', 'Turnero');

        $this->view('public.home', compact('services', 'businessName'));
    }

    public function success(): void
    {
        $appointmentId = (int)($_GET['appt'] ?? 0);
        $repo = new AppointmentRepository();
        $appointment = $appointmentId ? $repo->findById($appointmentId) : null;

        $this->view('public.success', compact('appointment'));
    }

    public function failure(): void
    {
        $this->view('public.failure');
    }

    public function pending(): void
    {
        $this->view('public.pending');
    }

    public function cancel(): void
    {
        $token = $_GET['token'] ?? '';
        $repo = new AppointmentRepository();
        $appointment = $token ? $repo->findByCancellationToken($token) : null;

        if ($appointment === null) {
            $this->view('public.cancel', ['error' => 'Turno no encontrado.', 'appointment' => null]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $service = new \App\Application\Services\AppointmentService(
                new AppointmentRepository(),
                new ServiceRepository(),
                new SettingsRepository(),
                new \App\Application\Services\AppointmentAvailabilityService(
                new AppointmentRepository(),
                new SettingsRepository(),
                ),
                );
            $service->cancelByToken($token);
            $this->view('public.cancel', ['cancelled' => true, 'appointment' => $appointment]);
            return;
        }

        $settingsRepo = new SettingsRepository();
        $serviceRepo = new ServiceRepository();
        $serviceEntity = $serviceRepo->findById($appointment->getServiceId());

        $this->view('public.cancel', [
            'appointment' => $appointment,
            'serviceEntity' => $serviceEntity,
            'cancelled' => false,
            'error' => null,
        ]);
    }
}