<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_header.php'; ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 text-center">
                <div class="status-icon success mb-4">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 class="fw-bold text-success">¡Turno confirmado!</h2>
                <?php if ($appointment): ?>
                <p class="text-muted lead">
                    Gracias <strong>
                        <?= htmlspecialchars($appointment->getCustomerName())?>
                    </strong>.
                    Tu turno quedó reservado para el
                    <strong>
                        <?= $appointment->getAppointmentDatetime()->format('d/m/Y')?>
                    </strong>
                    a las <strong>
                        <?= $appointment->getAppointmentDatetime()->format('H:i')?>
                    </strong>.
                </p>
                <?php if ($appointment->getCustomerPhone()): ?>
                <a href="https://wa.me/<?= preg_replace('/[^\d]/', '', $appointment->getCustomerPhone())?>?text=<?= rawurlencode('Hola! Quería confirmar mi turno del ' . $appointment->getAppointmentDatetime()->format('d/m/Y H:i'))?>"
                    class="btn btn-success mt-2" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-2"></i> Contactar por WhatsApp
                </a>
                <?php
    endif; ?>
                <?php
else: ?>
                <p class="text-muted">Tu pago fue procesado con éxito.</p>
                <?php
endif; ?>
                <div class="mt-4">
                    <a href="/" class="btn btn-primary">Reservar otro turno</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_footer.php'; ?>