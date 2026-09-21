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
                    <span class="icono" onclick="togglePassword('password', this)">👁‍🗨</span>
                </div>    
            </div>

            <div class="campo">
                <label for="confirm_password">Confirmar contraseña</label>
                <div class="input-con-icono">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña">
                    <span class="icono" onclick="togglePassword('confirm_password', this)">👁‍🗨</span>
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
    <script>
    function togglePassword(id, icono) {
        const input = document.getElementById(id);
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        icono.textContent = isPassword ? "🚫" : "👁‍🗨";
    }
    </script>
<?php endif; ?>

