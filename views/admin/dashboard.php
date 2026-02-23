<?php $pageTitle = 'Dashboard';
if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_header.php'; ?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>Operación realizada.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php
endif; ?>

<!-- Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-primary-soft"><i class="bi bi-calendar-check text-primary"></i></div>
            <div class="ms-3">
                <div class="metric-label">Turnos hoy</div>
                <div class="metric-value">
                    <?=(int)$todayCount?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-success-soft"><i class="bi bi-currency-dollar text-success"></i></div>
            <div class="ms-3">
                <div class="metric-label">Ingresos este mes</div>
                <div class="metric-value">$
                    <?= number_format((float)$monthRevenue, 0, ',', '.')?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-info-soft"><i class="bi bi-grid-1x2 text-info"></i></div>
            <div class="ms-3">
                <div class="metric-label">Servicios activos</div>
                <div class="metric-value">
                    <?= count(array_filter($allServices, fn($s) => $s->isActive()))?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <a href="<?= $base_path?>/admin/export-csv" class="metric-icon bg-warning-soft text-decoration-none">
                <i class="bi bi-file-earmark-spreadsheet text-warning"></i>
            </a>
            <div class="ms-3">
                <div class="metric-label">Exportar CSV</div>
                <div class="metric-value"><a href="<?= $base_path?>/admin/export-csv"
                        class="btn btn-sm btn-outline-secondary">Descargar</a></div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Appointments -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <i class="bi bi-calendar3 me-2"></i>Próximos turnos (pagados)
        <a href="<?= $base_path?>/admin/appointments" class="btn btn-sm btn-outline-primary ms-auto">Ver todos</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($upcomingAppts)): ?>
                <?php foreach ($upcomingAppts as $a): ?>
                <tr>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($a['appointment_datetime']))?>
                    </td>
                    <td>
                        <?= htmlspecialchars($a['customer_name'])?>
                    </td>
                    <td>
                        <?= htmlspecialchars($a['customer_phone'])?>
                    </td>
                    <td><span class="badge-status <?= $a['status']?>">
                            <?= $a['status']?>
                        </span></td>
                </tr>
                <?php
    endforeach; ?>
                <?php
else: ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No hay turnos próximos.</td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_footer.php'; ?>