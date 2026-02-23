<?php $pageTitle = 'Horarios de Atención';
if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_header.php'; ?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>Horarios guardados.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php
endif; ?>

<div class="admin-card">
    <div class="admin-card-header"><i class="bi bi-clock me-2"></i>Configurar horarios de atención</div>
    <div class="admin-card-body">
        <p class="text-muted small">Podés agregar múltiples rangos por día (ej: 09:00-13:00 y 15:00-19:00). Cuando no
            indicás un servicio, el horario aplica a todos.</p>

        <div class="mb-3">
            <label class="form-label fw-500">Servicio (opcional)</label>
            <select id="serviceSelector" class="form-select form-select-sm w-auto">
                <option value="">Global (todos los servicios)</option>
                <?php foreach ($services as $svc): ?>
                <option value="<?= $svc->getId()?>" <?=($serviceId ?? null)==$svc->getId() ? 'selected' : ''?>>
                    <?= htmlspecialchars($svc->getName())?>
                </option>
                <?php
endforeach; ?>
            </select>
        </div>

        <?php if ($serviceId && empty($hours)): ?>
        <div class="alert alert-info py-2 small">
            <i class="bi bi-info-circle me-2"></i>
            Este servicio no tiene horarios específicos y está usando los <strong>horarios Globales</strong>.
            Al guardar cambios aquí, se crearán horarios exclusivos para este servicio.
        </div>
        <?php
endif; ?>

        <?php $base_path = $_ENV['APP_BASE_PATH'] ?? ''; ?>
        <form method="POST" action="<?= $base_path?>/admin/hours/save" id="hoursForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
            <input type="hidden" name="service_id" id="fServiceId" value="<?= $serviceId ?? ''?>">

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:120px">Día</th>
                            <th>Rangos horarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$hoursByDay = [];
foreach ($hours as $h) {
    $hoursByDay[$h['day_of_week']][] = $h;
}
?>
                        <?php foreach ($dayNames as $dayNum => $dayName): ?>
                        <tr>
                            <td class="fw-500">
                                <?= $dayName?>
                            </td>
                            <td>
                                <div class="hour-ranges" id="ranges-<?= $dayNum?>">
                                    <?php $dayHours = $hoursByDay[$dayNum] ?? []; ?>
                                    <?php foreach ($dayHours as $h): ?>
                                    <div class="d-flex gap-2 align-items-center mb-1 hour-range-row">
                                        <input type="hidden" name="day[]" value="<?= $dayNum?>">
                                        <input type="time" name="start[]" class="form-control form-control-sm"
                                            style="width:130px" value="<?= substr($h['start_time'], 0, 5)?>">
                                        <span>–</span>
                                        <input type="time" name="end[]" class="form-control form-control-sm"
                                            style="width:130px" value="<?= substr($h['end_time'], 0, 5)?>">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-rm-range">
                                            <i class="bi bi-dash-circle"></i>
                                        </button>
                                    </div>
                                    <?php
    endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-1 btn-add-range"
                                    data-day="<?= $dayNum?>">
                                    <i class="bi bi-plus-circle me-1"></i>Agregar rango
                                </button>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Guardar horarios
            </button>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_footer.php'; ?>