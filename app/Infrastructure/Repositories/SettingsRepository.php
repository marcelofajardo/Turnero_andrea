<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Persistence\DatabaseConnection;
use PDO;

/**
 * Repository for business_hours and settings tables.
 */
final class SettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    // -------------------------------------------------------
    // Settings helpers
    // -------------------------------------------------------

    public function get(string $key, mixed $default = null): mixed
    {
        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE key_name = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();

        return $row ? $row['value'] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $pdo = \App\Infrastructure\Persistence\DatabaseConnection::getInstance();
        $sql = "INSERT INTO settings (key_name, value) VALUES (:key, :val)
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':key' => $key, ':val' => $value]);
    }

    /** @return array<string, string> */
    public function getGroup(string $group): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT key_name, value FROM settings WHERE `group` = :group'
        );
        $stmt->execute([':group' => $group]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['key_name']] = $row['value'];
        }

        return $result;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM settings ORDER BY `group`, key_name');
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    // Business hours helpers
    // -------------------------------------------------------

    /** Return working hours (global optionally overridden per service) */
    public function getBusinessHours(?int $serviceId = null): array
    {
        if ($serviceId !== null) {
            // First try service-specific, fallback to global if none found
            $stmt = $this->pdo->prepare(
                'SELECT * FROM business_hours
                 WHERE service_id = :sid AND is_active = 1
                 ORDER BY day_of_week, start_time'
            );
            $stmt->execute([':sid' => $serviceId]);
            $rows = $stmt->fetchAll();

            if (!empty($rows))
                return $rows;
        }

        $stmt = $this->pdo->query(
            'SELECT * FROM business_hours
             WHERE service_id IS NULL AND is_active = 1
             ORDER BY day_of_week, start_time'
        );

        return $stmt->fetchAll();
    }

    public function saveBusinessHour(
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $serviceId = null
        ): void
    {
        $sql = "INSERT INTO business_hours (service_id, day_of_week, start_time, end_time, is_active)
                VALUES (:sid, :dow, :start, :end, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':sid' => $serviceId,
            ':dow' => $dayOfWeek,
            ':start' => $startTime,
            ':end' => $endTime,
        ]);
    }

    public function deleteBusinessHoursByServiceId(?int $serviceId): void
    {
        if ($serviceId === null) {
            $stmt = $this->pdo->prepare('DELETE FROM business_hours WHERE service_id IS NULL');
            $stmt->execute();
        }
        else {
            $stmt = $this->pdo->prepare('DELETE FROM business_hours WHERE service_id = :sid');
            $stmt->execute([':sid' => $serviceId]);
        }
    }
}