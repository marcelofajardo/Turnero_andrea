<?php

declare(strict_types=1);

namespace App\Application\DTOs;

/**
 * Data Transfer Object for creating an appointment.
 * Immutable — no setters, only constructor.
 */
final class CreateAppointmentDTO
{
    public function __construct(
        public readonly int     $serviceId,
        public readonly string  $customerName,
        public readonly string  $customerPhone,
        public readonly ?string $customerEmail,
        public readonly string  $date,
        public readonly string  $time,
        public readonly ?string $notes = null,
    ) {}

    /** Factory: build from raw POST data with strict type coercion */
    public static function fromArray(array $data): self
    {
        return new self(
            serviceId:     (int)   ($data['service_id']    ?? 0),
            customerName:  (string)($data['customer_name'] ?? ''),
            customerPhone: (string)($data['customer_phone']?? ''),
            customerEmail: !empty($data['customer_email']) ? (string) $data['customer_email'] : null,
            date:          (string)($data['date']          ?? ''),
            time:          (string)($data['time']          ?? ''),
            notes:         !empty($data['notes'])          ? (string) $data['notes']  : null,
        );
    }
}