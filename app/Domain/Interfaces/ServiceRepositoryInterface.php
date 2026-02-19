<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

use App\Domain\Entities\Service;

/**
 * Contract for service data access.
 */
interface ServiceRepositoryInterface
{
    public function findAll(bool $onlyActive = true): array;

    public function findById(int $id): ?Service;

    public function findBySlug(string $slug): ?Service;

    public function save(Service $service): Service;

    public function update(Service $service): void;

    public function delete(int $id): void;
}