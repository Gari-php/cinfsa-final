<h2 class="nombre-pagina">Restablecer Contraseña</h2>

<?php if (!$tokenValido): ?>
    <p class="descripcion-pagina error"><?php echo $mensaje; ?></p>
<?php else: ?>
    <p class="descripcion-pagina">Colocá tu nueva contraseña</p>

    <div class="form-container">
        <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">

        <form method="POST"
              action="/restablecer"
              data-fetch="true"
              data-alerta=".contenedor-alertas"
              data-redirigir="/">
            
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="campo">
                <label for="password">Nueva contraseña</label>
                <div class="input-con-icono">
                    <input type="password" id="password" name="password" placeholder="Nueva contraseña">
                    <span class="icono" data-mostrar-password="password" role="button" tabindex="0" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
                </div>    
            </div>

            <div class="campo">
                <label for="confirm_password">Confirmar contraseña</label>
                <div class="input-con-icono">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña">
                    <span class="icono" data-mostrar-password="confirm_password" role="button" tabindex="0" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
                </div>
            </div>

            <input type="submit" class="boton" value="Guardar nueva contraseña">
            <div class="contenedor-alertas"></div>
        </form>

        <div class="acciones">
            <a href="/">¿Ya tenés una cuenta? Iniciar sesión</a>
        </div>
    </div>

    <script type="module" src="/assets/js/formularios.js"></script>
    <script src="/assets/js/mostrar-password.js"></script>
<?php endif; ?>

