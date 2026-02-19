<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Service;
use App\Domain\Interfaces\ServiceRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseConnection;
use PDO;

/**
 * PDO-based implementation of ServiceRepositoryInterface.
 */
final class ServiceRepository implements ServiceRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    /** @return Service[] */
    public function findAll(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM services' . ($onlyActive ? ' WHERE is_active = 1' : '')
             . ' ORDER BY sort_order ASC, name ASC';

        $stmt = $this->pdo->query($sql);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Service
    {
        $stmt = $this->pdo->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?Service
    {
        $stmt = $this->pdo->prepare('SELECT * FROM services WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(Service $service): Service
    {
        $sql = "INSERT INTO services
                    (name, slug, description, price, duration_minutes, color, is_active, sort_order)
                VALUES
                    (:name, :slug, :desc, :price, :dur, :color, :active, :sort)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name'   => $service->getName(),
            ':slug'   => $service->getSlug(),
            ':desc'   => $service->getDescription(),
            ':price'  => $service->getPrice(),
            ':dur'    => $service->getDurationMinutes(),
            ':color'  => $service->getColor(),
            ':active' => $service->isActive() ? 1 : 0,
            ':sort'   => $service->getSortOrder(),
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(Service $service): void
    {
        $sql = "UPDATE services SET
                    name             = :name,
                    slug             = :slug,
                    description      = :desc,
                    price            = :price,
                    duration_minutes = :dur,
                    color            = :color,
                    is_active        = :active,
                    sort_order       = :sort,
                    updated_at       = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name'   => $service->getName(),
            ':slug'   => $service->getSlug(),
            ':desc'   => $service->getDescription(),
            ':price'  => $service->getPrice(),
            ':dur'    => $service->getDurationMinutes(),
            ':color'  => $service->getColor(),
            ':active' => $service->isActive() ? 1 : 0,
            ':sort'   => $service->getSortOrder(),
            ':id'     => $service->getId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM services WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function hydrate(array $row): Service
    {
        return new Service(
            id:              (int) $row['id'],
            name:            $row['name'],
            slug:            $row['slug'],
            description:     $row['description'] ?? null,
            price:           (float) $row['price'],
            durationMinutes: (int) $row['duration_minutes'],
            color:           $row['color'],
            isActive:        (bool) $row['is_active'],
            sortOrder:       (int) $row['sort_order'],
        );
    }
}