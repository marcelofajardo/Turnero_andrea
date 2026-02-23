</main>

<footer class="footer-main mt-auto py-4">
    <div class="container text-center">
        <small class="text-muted">
            &copy;
            <?= date('Y')?>
            <?= htmlspecialchars($businessName ?? 'Turnero')?> &mdash;
            Sistema de Gestión de Turnos
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="<?= $base_path ?? ''?>/assets/js/app.js"></script>
</body>

</html>