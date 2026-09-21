<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">

    <?php if (isset($mensaje)): ?>
        <div class="alerta <?php echo htmlspecialchars($tipo ?? 'neutral'); ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <div class="acciones">
        <?php if (($tipo ?? '') === 'exito'): ?>
            <a href="/">Iniciar Sesión</a>
        <?php else: ?>
            <a href="/crear-cuenta">Crear Otra Cuenta</a>
        <?php endif; ?>
    </div>
</div>
