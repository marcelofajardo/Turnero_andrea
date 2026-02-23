<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_header.php'; ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <?php if ($cancelled ?? false): ?>
                <div class="text-center">
                    <div class="status-icon mb-4" style="color:#dc3545"><i class="bi bi-calendar-x-fill"></i></div>
                    <h2 class="fw-bold">Turno cancelado</h2>
                    <p class="text-muted">Tu turno fue cancelado correctamente.</p>
                    <a href="/" class="btn btn-primary mt-3">Reservar nuevo turno</a>
                </div>
                <?php elseif (isset($error) && $error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php elseif ($appointment): ?>
                <div class="booking-card">
                    <h4 class="mb-3"><i class="bi bi-calendar-x me-2 text-danger"></i>Cancelar turno</h4>
                    <p><strong>Turno:</strong>
                        <?= $appointment->getAppointmentDatetime()->format('d/m/Y H:i') ?>
                    </p>
                    <p><strong>Servicio:</strong>
                        <?= htmlspecialchars($serviceEntity?->getName() ?? '') ?>
                    </p>
                    <p><strong>Cliente:</strong>
                        <?= htmlspecialchars($appointment->getCustomerName()) ?>
                    </p>
                    <?php if ($appointment->isCancelled()): ?>
                    <div class="alert alert-warning">Este turno ya fue cancelado.</div>
                    <?php else: ?>
                    <form method="POST"
                        action="/cancelar?token=<?= htmlspecialchars($appointment->getCancellationToken() ?? '') ?>">
                        <input type="hidden" name="_csrf" value="<?= \App\Shared\Helpers\CsrfHelper::getToken() ?>">
                        <p class="text-muted small">Al confirmar, el turno será cancelado definitivamente.</p>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-2"></i>Confirmar cancelación
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_footer.php'; ?>