<div style="font-family:sans-serif;text-align:center;padding:60px">
    <h3>404 — Página no encontrada</h3>
    <?php if (($_ENV['APP_DEBUG'] ?? 'false') === 'true' && isset($debugUri)): ?>
    <p style="color:#666;font-size:0.9rem">Ruta intentada: <code><?= htmlspecialchars($debugUri)?></code></p>
    <?php
endif; ?>
    <p><a href="/">Volver al inicio</a></p>
</div>