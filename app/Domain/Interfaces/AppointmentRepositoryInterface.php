<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

use App\Domain\Entities\Appointment;
use DateTimeImmutable;

/**
 * Contract for appointment data access.
 * Implementations live in the Infrastructure layer.
 */
interface AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment;

    public function findByCancellationToken(string $token): ?Appointment;

    public function findByMercadoPagoPaymentId(string $paymentId): ?Appointment;

    /** @return Appointment[] */
    public function findByDateAndService(int $serviceId, string $date): array;

    /** @return Appointment[] */
    public function findDueReminders(DateTimeImmutable $from, DateTimeImmutable $to): array;

    /** @return Appointment[] Paginated list for admin */
    public function findPaginated(int $page, int $perPage, array $filters = []): array;

    public function countByFilters(array $filters = []): int;

    public function save(Appointment $appointment): Appointment;

    public function update(Appointment $appointment): void;

    public function delete(int $id): void;
}