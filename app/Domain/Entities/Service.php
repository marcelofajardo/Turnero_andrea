<?php

declare(strict_types=1);

namespace App\Domain\Entities;

/**
 * Service Entity — a bookable service offered by the business.
 */
final class Service
{
    public function __construct(
        private readonly ?int $id,
        private string $name,
        private string $slug,
        private ?string $description,
        private float $price,
        private int $durationMinutes,
        private string $color,
        private bool $isActive,
        private int $sortOrder,
        private ?string $mpAccessToken = null,
        private ?string $mpPublicKey = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2, ',', '.');
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }
}