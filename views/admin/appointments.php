<?php $pageTitle = 'Turnos';
if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_header.php'; ?>

<!-- Filters -->
<div class="admin-card mb-3">
    <form method="GET" action="<?= $base_path?>/admin/appointments" class="row g-2 align-items-end">
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small">Estado</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (['pending', 'paid', 'cancelled', 'completed'] as $s): ?>
                <option value="<?= $s?>" <?=($filters['status'] ?? '' )===$s ? 'selected' : '' ?>>
                    <?= ucfirst($s)?>
                </option>
                <?php
endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small">Servicio</label>
            <select name="service" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($services as $svc): ?>
                <option value="<?= $svc->getId()?>" <?=($filters['service_id'] ?? '' )==$svc->getId() ? 'selected' :
                    ''?>>
                    <?= htmlspecialchars($svc->getName())?>
                </option>
                <?php
endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small">Desde</label>
            <input type="date" name="from" class="form-control form-control-sm"
                value="<?= htmlspecialchars($filters['date_from'] ?? '')?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small">Hasta</label>
            <input type="date" name="to" class="form-control form-control-sm"
                value="<?= htmlspecialchars($filters['date_to'] ?? '')?>">
        </div>
        <div class="col-12 col-lg-3">
            <label class="form-label small">Buscar</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Nombre o teléfono"
                value="<?= htmlspecialchars($filters['customer_search'] ?? '')?>">
        </div>
        <div class="col-12 col-lg-1">
            <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <i class="bi bi-list-ul me-2"></i>
        <?= number_format($total)?> turno(s)
        <a href="<?= $base_path?>/admin/export-csv<?=!empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''?>"
            class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-download me-1"></i>CSV
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha/Hora</th>
                    <th>Servicio</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($appointments)): ?>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="text-muted small">
                        <?= $a['id']?>
                    </td>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($a['appointment_datetime']))?>
                    </td>
                    <td>
                        <?= htmlspecialchars($a['service_name'] ?? '')?>
                    </td>
                    <td>
                        <?= htmlspecialchars($a['customer_name'])?>
                    </td>
                    <td>
                        <a href="https://wa.me/<?= preg_replace('/[^\d]/', '', $a['customer_phone'])?>" target="_blank"
                            rel="noopener" class="text-decoration-none">
                            <i class="bi bi-whatsapp text-success me-1"></i>
                            <?= htmlspecialchars($a['customer_phone'])?>
                        </a>
                    </td>
                    <td><span class="badge-status <?= $a['status']?>">
                            <?= $a['status']?>
                        </span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-edit-appointment" data-id="<?= $a['id']?>"
                            data-name="<?= htmlspecialchars($a['customer_name'])?>"
                            data-phone="<?= htmlspecialchars($a['customer_phone'])?>"
                            data-email="<?= htmlspecialchars($a['customer_email'] ?? '')?>"
                            data-status="<?= $a['status']?>" data-notes="<?= htmlspecialchars($a['notes'] ?? '')?>"
                            data-mp-id="<?= htmlspecialchars($a['mercadopago_payment_id'] ?? '')?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= $base_path?>/admin/appointments/delete" style="display:inline"
                            class="delete-appointment-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                            <input type="hidden" name="id" value="<?= $a['id']?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php
    endforeach; ?>
                <?php
else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No hay turnos.</td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center py-3">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : ''?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p]))?>">
                        <?= $p?>
                    </a>
                </li>
                <?php
    endfor; ?>
            </ul>
        </nav>
    </div>
    <?php
endif; ?>
</div>

<!-- Edit Appointment Modal -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Turno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $base_path?>/admin/appointments/update">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                <input type="hidden" name="id" id="edit_appt_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="customer_name" id="edit_appt_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="customer_phone" id="edit_appt_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" id="edit_appt_email" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Estado</label>
                            <select name="status" id="edit_appt_status" class="form-select">
                                <option value="pending">Pendiente</option>
                                <option value="paid">Pagado</option>
                                <option value="cancelled">Cancelado</option>
                                <option value="completed">Completado</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ID Pago MercadoPago</label>
                            <input type="text" name="mp_payment_id" id="edit_appt_mp_id" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notes" id="edit_appt_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_footer.php'; ?>