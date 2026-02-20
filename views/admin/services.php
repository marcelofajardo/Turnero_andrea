<?php $pageTitle = 'Servicios';
if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_header.php'; ?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>
    <?= $_GET['msg'] === 'created' ? 'Servicio creado.' : ($_GET['msg'] === 'updated' ? 'Servicio actualizado.' : 'Servicio eliminado.')?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php
endif; ?>

<!-- Services list -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-grid-1x2 me-2"></i>Servicios (
            <?= count($services)?>)
        </span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createServiceModal">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Servicio
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $svc): ?>
                <tr>
                    <td>
                        <span class="service-dot-sm" style="background:<?= htmlspecialchars($svc->getColor())?>"></span>
                        <?= htmlspecialchars($svc->getName())?>
                    </td>
                    <td>
                        <?= $svc->getFormattedPrice()?>
                    </td>
                    <td>
                        <?= $svc->getDurationMinutes()?> min
                    </td>
                    <td>
                        <span class="badge <?= $svc->isActive() ? 'bg-success' : 'bg-secondary'?>">
                            <?= $svc->isActive() ? 'Activo' : 'Inactivo'?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-service"
                            data-id="<?= $svc->getId()?>" data-name="<?= htmlspecialchars($svc->getName())?>"
                            data-price="<?= $svc->getPrice()?>" data-dur="<?= $svc->getDurationMinutes()?>"
                            data-color="<?= htmlspecialchars($svc->getColor())?>"
                            data-desc="<?= htmlspecialchars($svc->getDescription() ?? '')?>"
                            data-active="<?= $svc->isActive() ? '1' : '0'?>" data-sort="<?= $svc->getSortOrder()?>"
                            data-mp-token="<?= htmlspecialchars($svc->getMpAccessToken() ?? '')?>"
                            data-mp-key="<?= htmlspecialchars($svc->getMpPublicKey() ?? '')?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= $base_path?>/admin/services/delete" style="display:inline"
                            onsubmit="return confirm('¿Eliminar este servicio?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                            <input type="hidden" name="id" value="<?= $svc->getId()?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                    class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php
endforeach; ?>
                <?php if (empty($services)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No hay servicios registrados.</td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $base_path?>/admin/services/store">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Ej: Reiki">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio ($)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duración (min)</label>
                            <input type="number" name="duration_minutes" class="form-control" min="5" value="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color w-100"
                                value="#5AA9E6">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2"
                                placeholder="Breve descripción del servicio..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                                <label for="is_active" class="form-check-label">Servicio Activo</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr>
                        </div>
                        <div class="col-12 text-muted small mb-2"><i class="bi bi-shield-lock me-1"></i>Configuración
                            MercadoPago (Opcional)</div>

                        <div class="col-md-6">
                            <label class="form-label text-primary">Access Token</label>
                            <input type="password" name="mp_access_token" class="form-control"
                                placeholder="APP_USR-...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-primary">Public Key</label>
                            <input type="text" name="mp_public_key" class="form-control" placeholder="APP_USR-...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $base_path?>/admin/services/update">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio ($)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duración (min)</label>
                            <input type="number" name="duration_minutes" id="edit_dur" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" id="edit_color"
                                class="form-control form-control-color w-100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number" name="sort_order" id="edit_sort" class="form-control">
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_active" id="edit_active" class="form-check-input">
                                <label for="edit_active" class="form-check-label">Activo</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr>
                        </div>
                        <div class="col-12 text-muted small mb-2"><i class="bi bi-shield-lock me-1"></i>Configuración
                            MercadoPago (Opcional)</div>

                        <div class="col-md-6">
                            <label class="form-label text-primary">Access Token</label>
                            <input type="password" name="mp_access_token" id="edit_mp_token" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-primary">Public Key</label>
                            <input type="text" name="mp_public_key" id="edit_mp_key" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/admin_footer.php'; ?>