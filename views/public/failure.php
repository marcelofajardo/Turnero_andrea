<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_header.php'; ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 text-center">
                <div class="status-icon failure mb-4"><i class="bi bi-x-circle-fill"></i></div>
                <h2 class="fw-bold text-danger">Pago no procesado</h2>
                <p class="text-muted">Hubo un problema con tu pago. No se realizó ningún cargo.</p>
                <a href="/" class="btn btn-primary mt-3">Intentar de nuevo</a>
            </div>
        </div>
    </div>
</section>
<?php require_once dirname(__DIR__, 2) . '/views/layouts/public_footer.php'; ?>