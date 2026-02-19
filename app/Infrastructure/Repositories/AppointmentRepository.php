<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Appointment;
use App\Domain\Interfaces\AppointmentRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseConnection;
use DateTimeImmutable;
use PDO;

/**
 * PDO-based implementation of AppointmentRepositoryInterface.
 * All SQL lives here — never in controllers or services.
 */
final class AppointmentRepository implements AppointmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    public function findById(int $id): ?Appointment
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM appointments WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCancellationToken(string $token): ?Appointment
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM appointments WHERE cancellation_token = :token LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByMercadoPagoPaymentId(string $paymentId): ?Appointment
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM appointments WHERE mercadopago_payment_id = :pid LIMIT 1'
        );
        $stmt->execute([':pid' => $paymentId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /** @return Appointment[] */
    public function findByDateAndService(int $serviceId, string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointments
             WHERE service_id = :sid
               AND DATE(appointment_datetime) = :date
               AND status NOT IN ('cancelled')
             ORDER BY appointment_datetime ASC"
        );
        $stmt->execute([':sid' => $serviceId, ':date' => $date]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /** @return Appointment[] */
    public function findDueReminders(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointments
             WHERE appointment_datetime BETWEEN :from AND :to
               AND status IN ('pending', 'paid')
               AND reminder_sent = 0
             ORDER BY appointment_datetime ASC"
        );
        $stmt->execute([
            ':from' => $from->format('Y-m-d H:i:s'),
            ':to'   => $to->format('Y-m-d H:i:s'),
        ]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /** @return Appointment[] */
    public function findPaginated(int $page, int $perPage, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT a.*, s.name AS service_name
                FROM appointments a
                JOIN services s ON s.id = a.service_id
                {$where}
                ORDER BY a.appointment_datetime DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countByFilters(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql  = "SELECT COUNT(*) FROM appointments a JOIN services s ON s.id = a.service_id {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function save(Appointment $appointment): Appointment
    {
        $sql = "INSERT INTO appointments
                    (service_id, customer_name, customer_phone, customer_email,
                     appointment_datetime, end_datetime, status, notes,
                     cancellation_token, reminder_sent, mercadopago_payment_id)
                VALUES
                    (:sid, :name, :phone, :email,
                     :appt_dt, :end_dt, :status, :notes,
                     :token, :reminder_sent, :mp_pid)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':sid'          => $appointment->getServiceId(),
            ':name'         => $appointment->getCustomerName(),
            ':phone'        => $appointment->getCustomerPhone(),
            ':email'        => $appointment->getCustomerEmail(),
            ':appt_dt'      => $appointment->getAppointmentDatetime()->format('Y-m-d H:i:s'),
            ':end_dt'       => $appointment->getEndDatetime()->format('Y-m-d H:i:s'),
            ':status'       => $appointment->getStatus(),
            ':notes'        => $appointment->getNotes(),
            ':token'        => $appointment->getCancellationToken(),
            ':reminder_sent'=> $appointment->isReminderSent() ? 1 : 0,
            ':mp_pid'       => $appointment->getMercadopagoPaymentId(),
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(Appointment $appointment): void
    {
        $sql = "UPDATE appointments SET
                    status                   = :status,
                    mercadopago_payment_id   = :mp_pid,
                    reminder_sent            = :reminder_sent,
                    updated_at               = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':status'       => $appointment->getStatus(),
            ':mp_pid'       => $appointment->getMercadopagoPaymentId(),
            ':reminder_sent'=> $appointment->isReminderSent() ? 1 : 0,
            ':id'           => $appointment->getId(),
        ]);
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function hydrate(array $row): Appointment
    {
        return new Appointment(
            id:                     (int) $row['id'],
            serviceId:              (int) $row['service_id'],
            customerName:           $row['customer_name'],
            customerPhone:          $row['customer_phone'],
            customerEmail:          $row['customer_email'] ?? null,
            appointmentDatetime:    new DateTimeImmutable($row['appointment_datetime']),
            endDatetime:            new DateTimeImmutable($row['end_datetime']),
            status:                 $row['status'],
            notes:                  $row['notes'] ?? null,
            cancellationToken:      $row['cancellation_token'] ?? null,
            reminderSent:           (bool) $row['reminder_sent'],
            mercadopagoPaymentId:   $row['mercadopago_payment_id'] ?? null,
            createdAt:              new DateTimeImmutable($row['created_at']),
        );
    }

    /** @return array{string, array} */
    private function buildFilters(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'a.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['service_id'])) {
            $conditions[] = 'a.service_id = :service_id';
            $params[':service_id'] = (int) $filters['service_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(a.appointment_datetime) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(a.appointment_datetime) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['customer_search'])) {
            $conditions[] = '(a.customer_name LIKE :search OR a.customer_phone LIKE :search)';
            $params[':search'] = '%' . $filters['customer_search'] . '%';
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }
}