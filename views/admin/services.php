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

<!-- Create Form -->
<div class="admin-card mb-4">
    <div class="admin-card-header"><i class="bi bi-plus-circle me-2"></i>Nuevo servicio</div>
    <div class="admin-card-body">
        <form method="POST" action="/admin/services/store" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
            <div class="col-12 col-md-4">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Precio ($)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="0">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Duración (min)</label>
                <input type="number" name="duration_minutes" class="form-control" min="5" value="30">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label">Color</label>
                <input type="color" name="color" class="form-control form-control-color w-100" value="#5AA9E6">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Descripción</label>
                <input type="text" name="description" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" name="sort_order" class="form-control" value="0" min="0">
            </div>
            <div class="col-6 col-md-2 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                    <label for="is_active" class="form-check-label">Activo</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Crear servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- Services list -->
<div class="admin-card">
    <div class="admin-card-header"><i class="bi bi-grid-1x2 me-2"></i>Servicios (
        <?= count($services)?>)
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
                        <span class="service-dot-sm"
                            style="background:<?= htmlspecialchars($svc->getColor())?>"></span>
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
                            data-active="<?= $svc->isActive() ? '1' : '0'?>" data-sort="<?= $svc->getSortOrder()?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="/admin/services/delete" style="display:inline"
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
                    <td colspan="5" class="text-center py-4 text-muted">No hay servicios.</td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar servicio</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/admin/services/update">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name"
                            id="edit_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><input type="text" name="description"
                            id="edit_desc" class="form-control"></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Precio</label><input type="number" name="price"
                                id="edit_price" class="form-control" step="0.01"></div>
                        <div class="col-6"><label class="form-label">Duración (min)</label><input type="number"
                                name="duration_minutes" id="edit_dur" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Color</label><input type="color" name="color"
                                id="edit_color" class="form-control form-control-color"></div>
                        <div class="col-6"><label class="form-label">Orden</label><input type="number" name="sort_order"
                                id="edit_sort" class="form-control"></div>
                    </div>
                    <div class="form-check mt-2"><input type="checkbox" name="is_active" id="edit_active"
                            class="form-check-input"><label for="edit_active" class="form-check-label">Activo</label>
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