<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use DateTimeImmutable;

/**
 * Appointment Entity — core domain object.
 * Encapsulates all business rules related to an appointment.
 */
final class Appointment
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $serviceId,
        private readonly string $customerName,
        private readonly string $customerPhone,
        private readonly ?string $customerEmail,
        private readonly DateTimeImmutable $appointmentDatetime,
        private readonly DateTimeImmutable $endDatetime,
        private string $status,
        private readonly ?string $notes,
        private ?string $cancellationToken,
        private bool $reminderSent,
        private ?string $mercadopagoPaymentId,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerPhone(): string
    {
        return $this->customerPhone;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getAppointmentDatetime(): DateTimeImmutable
    {
        return $this->appointmentDatetime;
    }

    public function getEndDatetime(): DateTimeImmutable
    {
        return $this->endDatetime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCancellationToken(): ?string
    {
        return $this->cancellationToken;
    }

    public function isReminderSent(): bool
    {
        return $this->reminderSent;
    }

    public function getMercadopagoPaymentId(): ?string
    {
        return $this->mercadopagoPaymentId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** Business rule: only pending appointments can transition to paid */
    public function markAsPaid(string $paymentId): void
    {
        if ($this->status !== 'pending') {
            throw new \LogicException("Cannot mark a '{$this->status}' appointment as paid.");
        }

        $this->status = 'paid';
        $this->mercadopagoPaymentId = $paymentId;
    }

    /** Business rule: only pending/paid appointments can be cancelled */
    public function cancel(): void
    {
        if (!in_array($this->status, ['pending', 'paid'], true)) {
            throw new \LogicException("Cannot cancel a '{$this->status}' appointment.");
        }

        $this->status = 'cancelled';
    }

    public function complete(): void
    {
        $this->status = 'completed';
    }

    public function markReminderSent(): void
    {
        $this->reminderSent = true;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}