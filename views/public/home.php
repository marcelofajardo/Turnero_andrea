<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_header.php'; ?>

<!-- Hero -->
<section class="hero-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="hero-title">Reservá tu turno online</h1>
            <p class="hero-subtitle">Rápido, fácil y sin esperas. Elegí el servicio y el horario que mejor te quede.</p>
        </div>

        <!-- Booking Card -->
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xl-8">
                <div class="booking-card">

                    <!-- Step 1: Service -->
                    <div class="booking-step" id="step-service">
                        <div class="step-header">
                            <div class="step-badge">1</div>
                            <div>
                                <h4 class="mb-0">Elegí el servicio</h4>
                                <small class="text-muted">Seleccioná qué tipo de turno necesitás</small>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <?php foreach ($services as $srv): ?>
                            <div class="col-12 col-sm-6">
                                <div class="service-card" data-service-id="<?= $srv->getId()?>"
                                    data-duration="<?= $srv->getDurationMinutes()?>"
                                    data-price="<?= $srv->getPrice()?>">
                                    <div class="service-dot"
                                        style="background:<?= htmlspecialchars($srv->getColor())?>"></div>
                                    <div class="flex-grow-1">
                                        <strong>
                                            <?= htmlspecialchars($srv->getName())?>
                                        </strong>
                                        <?php if ($srv->getDescription()): ?>
                                        <p class="small text-muted mb-1">
                                            <?= htmlspecialchars($srv->getDescription())?>
                                        </p>
                                        <?php
    endif; ?>
                                        <span class="badge badge-price">
                                            <?= $srv->getFormattedPrice()?>
                                        </span>
                                        <span class="badge badge-duration ms-1"><i class="bi bi-clock"></i>
                                            <?= $srv->getDurationMinutes()?> min
                                        </span>
                                    </div>
                                    <div class="service-check"><i class="bi bi-check-circle-fill"></i></div>
                                </div>
                            </div>
                            <?php
endforeach; ?>
                            <?php if (empty($services)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">No hay servicios disponibles en este momento.</div>
                            </div>
                            <?php
endif; ?>
                        </div>
                    </div>

                    <!-- Step 2: Date -->
                    <div class="booking-step mt-4 d-none" id="step-date">
                        <div class="step-header">
                            <div class="step-badge">2</div>
                            <div>
                                <h4 class="mb-0">Elegí la fecha</h4>
                                <small class="text-muted">Seleccioná el día de tu turno</small>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-center">
                            <div class="calendar-container">
                                <input type="hidden" id="dateValue" name="date_val">
                                <div id="calendarInline"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Time Slot -->
                    <div class="booking-step mt-4 d-none" id="step-slot">
                        <div class="step-header">
                            <div class="step-badge">3</div>
                            <div>
                                <h4 class="mb-0">Elegí el horario</h4>
                                <small class="text-muted">Horarios disponibles para esa fecha</small>
                            </div>
                        </div>
                        <div id="slotsContainer" class="slots-grid mt-3">
                            <div class="text-center py-3 text-muted" id="slotsLoading">
                                <div class="spinner-border spinner-border-sm me-2"></div> Cargando horarios...
                            </div>
                        </div>
                        <div class="alert alert-warning d-none" id="noSlotsMsg">
                            <i class="bi bi-exclamation-circle me-2"></i>No hay horarios disponibles para este día.
                        </div>
                    </div>

                    <!-- Step 4: Customer Info -->
                    <div class="booking-step mt-4 d-none" id="step-form">
                        <div class="step-header">
                            <div class="step-badge">4</div>
                            <div>
                                <h4 class="mb-0">Tus datos</h4>
                                <small class="text-muted">Completá la información del turno</small>
                            </div>
                        </div>

                        <!-- Resumen de selección -->
                        <div class="selection-summary mt-3" id="selectionSummary"></div>

                        <form id="bookingForm" class="mt-3" novalidate>
                            <input type="hidden" id="f_service_id" name="service_id">
                            <input type="hidden" id="f_date" name="date">
                            <input type="hidden" id="f_time" name="time">

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-500">Nombre completo <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" id="f_name" class="form-control"
                                        placeholder="Ej: María González" required>
                                    <div class="invalid-feedback" id="err_name"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-500">Teléfono <span class="text-danger">*</span></label>
                                    <input type="tel" name="customer_phone" id="f_phone" class="form-control"
                                        placeholder="Ej: +54 11 1234-5678" required>
                                    <div class="invalid-feedback" id="err_phone"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-500">Email (opcional)</label>
                                    <input type="email" name="customer_email" id="f_email" class="form-control"
                                        placeholder="tucorreo@ejemplo.com">
                                    <div class="invalid-feedback" id="err_email"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-500">Notas (opcional)</label>
                                    <textarea name="notes" id="f_notes" class="form-control" rows="2"
                                        placeholder="¿Alguna indicación especial?"></textarea>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                    <i class="bi bi-lock-fill me-2"></i>Confirmar y Pagar
                                </button>
                                <p class="text-center text-muted small mt-2">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Tu turno se confirma inmediatamente al completar el pago.
                                </p>
                            </div>

                            <div class="alert alert-danger d-none mt-3" id="formError"></div>
                        </form>
                    </div>

                </div><!-- .booking-card -->
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_footer.php'; ?>