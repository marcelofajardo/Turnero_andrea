<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Infrastructure\Repositories\SettingsRepository;
use App\Shared\Helpers\CsrfHelper;
use App\Domain\Entities\Service;
use PDO;

/**
 * AdminController — all admin panel pages.
 * Every method calls AuthMiddleware first.
 */
final class AdminController extends BaseController
{
    private AppointmentRepository $apptRepo;
    private ServiceRepository     $serviceRepo;
    private SettingsRepository    $settingsRepo;
    private PDO $pdo;

    private function boot(): void
    {
        (new AuthMiddleware())->handle();
        $this->apptRepo    = new AppointmentRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->settingsRepo = new SettingsRepository();
        $this->pdo = DatabaseConnection::getInstance();
    }

    // -------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------

    public function dashboard(): void
    {
        $this->boot();

        // Monthly metrics
        $pdo = $this->pdo;

        $todayCount  = $pdo->query(
            "SELECT COUNT(*) FROM appointments WHERE DATE(appointment_datetime) = CURDATE() AND status != 'cancelled'"
        )->fetchColumn();

        $monthRevenue = $pdo->query(
            "SELECT COALESCE(SUM(p.amount),0) FROM payments p
             JOIN appointments a ON a.id = p.appointment_id
             WHERE MONTH(p.created_at)=MONTH(NOW()) AND p.mp_status='approved'"
        )->fetchColumn();

        $upcomingAppts = $this->apptRepo->findPaginated(1, 5, ['status' => 'paid']);
        $allServices   = $this->serviceRepo->findAll(false);
        $settings      = $this->settingsRepo;

        $this->view('admin.dashboard', compact(
            'todayCount', 'monthRevenue', 'upcomingAppts', 'allServices', 'settings'
        ));
    }

    // -------------------------------------------------------
    // Appointments
    // -------------------------------------------------------

    public function appointments(): void
    {
        $this->boot();
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = array_filter([
            'status'         => $_GET['status']   ?? '',
            'service_id'     => $_GET['service']  ?? '',
            'date_from'      => $_GET['from']      ?? '',
            'date_to'        => $_GET['to']        ?? '',
            'customer_search'=> $_GET['q']         ?? '',
        ]);

        $appointments = $this->apptRepo->findPaginated($page, $perPage, $filters);
        $total        = $this->apptRepo->countByFilters($filters);
        $totalPages   = (int) ceil($total / $perPage);
        $services     = $this->serviceRepo->findAll(false);
        $csrf         = CsrfHelper::getToken();

        $this->view('admin.appointments', compact(
            'appointments', 'page', 'totalPages', 'filters', 'services', 'csrf', 'total'
        ));
    }

    // -------------------------------------------------------
    // Services CRUD
    // -------------------------------------------------------

    public function services(): void
    {
        $this->boot();
        $services = $this->serviceRepo->findAll(false);
        $csrf = CsrfHelper::getToken();
        $this->view('admin.services', compact('services', 'csrf'));
    }

    public function storeService(): void
    {
        $this->boot();
        (new \App\Http\Middleware\CsrfMiddleware())->handle();

        $slug = $this->makeSlug($_POST['name'] ?? '');

        $service = new Service(
            id:              null,
            name:            trim($_POST['name']   ?? ''),
            slug:            $slug,
            description:     trim($_POST['description'] ?? '') ?: null,
            price:           (float) ($_POST['price'] ?? 0),
            durationMinutes: (int) ($_POST['duration_minutes'] ?? 30),
            color:           $_POST['color'] ?? '#5AA9E6',
            isActive:        isset($_POST['is_active']),
            sortOrder:       (int) ($_POST['sort_order'] ?? 0),
        );

        $this->serviceRepo->save($service);
        $this->redirect('/admin/services?msg=created');
    }

    public function updateService(): void
    {
        $this->boot();
        (new \App\Http\Middleware\CsrfMiddleware())->handle();

        $id = (int) ($_POST['id'] ?? 0);
        $service = $this->serviceRepo->findById($id);

        if ($service === null) {
            $this->redirect('/admin/services?error=notfound');
        }

        $updated = new Service(
            id:              $id,
            name:            trim($_POST['name'] ?? ''),
            slug:            $this->makeSlug($_POST['name'] ?? ''),
            description:     trim($_POST['description'] ?? '') ?: null,
            price:           (float) ($_POST['price'] ?? 0),
            durationMinutes: (int) ($_POST['duration_minutes'] ?? 30),
            color:           $_POST['color'] ?? '#5AA9E6',
            isActive:        isset($_POST['is_active']),
            sortOrder:       (int) ($_POST['sort_order'] ?? 0),
        );

        $this->serviceRepo->update($updated);
        $this->redirect('/admin/services?msg=updated');
    }

    public function deleteService(): void
    {
        $this->boot();
        (new \App\Http\Middleware\CsrfMiddleware())->handle();
        $id = (int) ($_POST['id'] ?? 0);
        $this->serviceRepo->delete($id);
        $this->redirect('/admin/services?msg=deleted');
    }

    // -------------------------------------------------------
    // Business Hours
    // -------------------------------------------------------

    public function hours(): void
    {
        $this->boot();
        $serviceId = !empty($_GET['service_id']) ? (int) $_GET['service_id'] : null;
        $hours    = $this->settingsRepo->getBusinessHours($serviceId);
        $services = $this->serviceRepo->findAll(false);
        $csrf     = CsrfHelper::getToken();
        $this->view('admin.hours', compact('hours', 'services', 'csrf', 'serviceId'));
    }

    public function saveHours(): void
    {
        $this->boot();
        (new \App\Http\Middleware\CsrfMiddleware())->handle();

        $serviceId = !empty($_POST['service_id']) ? (int) $_POST['service_id'] : null;
        $days   = $_POST['day']   ?? [];
        $starts = $_POST['start'] ?? [];
        $ends   = $_POST['end']   ?? [];

        try {
            $this->pdo->beginTransaction();
            
            \App\Shared\Logging\AppLogger::info("Saving business hours (start)", [
                'service_id' => $serviceId, 
                'rows_count' => count($days)
            ]);

            $this->settingsRepo->deleteBusinessHoursByServiceId($serviceId);

            $count = 0;
            foreach ($days as $i => $day) {
                if (!empty($starts[$i]) && !empty($ends[$i])) {
                    $this->settingsRepo->saveBusinessHour(
                        (int) $day,
                        $starts[$i],
                        $ends[$i],
                        $serviceId
                    );
                    $count++;
                }
            }
            
            $this->pdo->commit();
            \App\Shared\Logging\AppLogger::info("Saved business hours (success)", [
                'service_id' => $serviceId, 
                'inserted' => $count
            ]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            \App\Shared\Logging\AppLogger::error("Failed to save business hours", [
                'service_id' => $serviceId,
                'error' => $e->getMessage()
            ]);
            $this->redirect('/admin/hours?error=db' . ($serviceId ? "&service_id={$serviceId}" : ""));
        }

        $this->redirect('/admin/hours?msg=saved' . ($serviceId ? "&service_id={$serviceId}" : ""));
    }

    // -------------------------------------------------------
    // Settings
    // -------------------------------------------------------

    public function settings(): void
    {
        $this->boot();
        $settings = $this->settingsRepo->getAll();
        $csrf     = CsrfHelper::getToken();
        $this->view('admin.settings', compact('settings', 'csrf'));
    }

    public function saveSettings(): void
    {
        $this->boot();
        (new \App\Http\Middleware\CsrfMiddleware())->handle();

        $allowed = [
            'business_name', 'business_address', 'appointment_duration_minutes',
            'timezone', 'reminder_hours_before', 'cancellation_url_base',
            'whatsapp_template_confirmation', 'whatsapp_template_reminder',
            'mp_sandbox',
        ];

        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $this->settingsRepo->set($key, (string) $_POST[$key]);
            }
        }

        $this->redirect('/admin/settings?msg=saved');
    }

    // -------------------------------------------------------
    // CSV Export
    // -------------------------------------------------------

    public function exportCsv(): void
    {
        $this->boot();

        $filters  = [
            'date_from' => $_GET['from'] ?? '',
            'date_to'   => $_GET['to']   ?? '',
            'status'    => $_GET['status'] ?? '',
        ];

        $appointments = $this->apptRepo->findPaginated(1, 5000, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="turnos-' . date('Y-m-d') . '.csv"');

        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID', 'Servicio', 'Cliente', 'Telefono', 'Email', 'Fecha', 'Hora', 'Estado']);

        foreach ($appointments as $row) {
            fputcsv($fp, [
                $row['id'],
                $row['service_name'] ?? '',
                $row['customer_name'],
                $row['customer_phone'],
                $row['customer_email'] ?? '',
                date('d/m/Y', strtotime($row['appointment_datetime'])),
                date('H:i',   strtotime($row['appointment_datetime'])),
                $row['status'],
            ]);
        }

        fclose($fp);
        exit;
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[áàäâã]/u', 'a', $slug);
        $slug = preg_replace('/[éèëê]/u', 'e', $slug);
        $slug = preg_replace('/[íìïî]/u', 'i', $slug);
        $slug = preg_replace('/[óòöôõ]/u', 'o', $slug);
        $slug = preg_replace('/[úùüû]/u', 'u', $slug);
        $slug = preg_replace('/[ñ]/u', 'n', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        return trim($slug, '-');
    }
}