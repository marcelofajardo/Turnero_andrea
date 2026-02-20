<?php $pageTitle = 'Configuración';
if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_header.php'; ?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>Configuración
    guardada. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php
endif; ?>

<?php
$s = [];
foreach ($settings as $row) {
    $s[$row['key_name']] = $row['value'];
}
?>

<div class="admin-card">
    <div class="admin-card-header"><i class="bi bi-gear me-2"></i>Configuración general</div>
    <div class="admin-card-body">
        <form method="POST" action="<?= $base_path?>/admin/settings/save" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">

            <div class="col-12">
                <h6 class="text-muted fw-600 text-uppercase small">Negocio</h6>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Nombre del negocio</label>
                <input type="text" name="business_name" class="form-control"
                    value="<?= htmlspecialchars($s['business_name'] ?? '')?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Dirección</label>
                <input type="text" name="business_address" class="form-control"
                    value="<?= htmlspecialchars($s['business_address'] ?? '')?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">URL base para cancelación</label>
                <input type="url" name="cancellation_url_base" class="form-control"
                    value="<?= htmlspecialchars($s['cancellation_url_base'] ?? $_ENV['APP_URL'] ?? '')?>">
            </div>

            <div class="col-12 mt-2">
                <h6 class="text-muted fw-600 text-uppercase small">Turnos</h6>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Duración global (min)</label>
                <input type="number" name="appointment_duration_minutes" class="form-control" min="5"
                    value="<?= htmlspecialchars($s['appointment_duration_minutes'] ?? '30')?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Recordatorio (horas antes)</label>
                <input type="number" name="reminder_hours_before" class="form-control" min="1"
                    value="<?= htmlspecialchars($s['reminder_hours_before'] ?? '24')?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Zona horaria</label>
                <select name="timezone" class="form-select">
                    <?php foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA) as $tz): ?>
                    <option value="<?= $tz?>" <?=($s['timezone'] ?? '' )===$tz ? 'selected' : '' ?>>
                        <?= $tz?>
                    </option>
                    <?php
endforeach; ?>
                </select>
            </div>

            <div class="col-12 mt-2">
                <h6 class="text-muted fw-600 text-uppercase small">MercadoPago</h6>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Modo sandbox</label>
                <select name="mp_sandbox" class="form-select">
                    <option value="true" <?=($s['mp_sandbox'] ?? 'true' )==='true' ? 'selected' : '' ?>>Sí (pruebas)
                    </option>
                    <option value="false" <?=($s['mp_sandbox'] ?? 'true' )==='false' ? 'selected' : '' ?>>No
                        (producción)
                    </option>
                </select>
            </div>

            <div class="col-12 mt-2">
                <h6 class="text-muted fw-600 text-uppercase small">WhatsApp — Plantillas</h6>
            </div>
            <div class="col-12">
                <label class="form-label">Confirmación <small class="text-muted">(vars: {name}, {service}, {date},
                        {time}, {cancel_url})</small></label>
                <textarea name="whatsapp_template_confirmation" class="form-control"
                    rows="2"><?= htmlspecialchars($s['whatsapp_template_confirmation'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Recordatorio</label>
                <textarea name="whatsapp_template_reminder" class="form-control"
                    rows="2"><?= htmlspecialchars($s['whatsapp_template_reminder'] ?? '')?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Guardar
                    configuración</button>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_footer.php'; ?>