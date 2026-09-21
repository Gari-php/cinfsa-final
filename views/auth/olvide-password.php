<h2 class="nombre-pagina">Restablecer Contraseña</h2>
<p class="descripcion-pagina">
    Desde esta página podrás restablecer tu contraseña. Ingresa tu E-mail para enviarte las instrucciones.
</p>

<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">

    <form method="POST" action="/olvide" data-fetch="true" data-alerta=".contenedor-alertas">
        <div class="campo">
            <label for="email">E-mail</label>
            <input type="text" id="email" name="email" placeholder="Escriba su E-mail" > 
        </div>

        <input type="submit" class="boton" value="Enviar Instrucciones">
        <div class="contenedor-alertas"></div>
    </form>

    <div class="acciones">
        <a href="/">¿Ya Tienes una Cuenta? Inicia Sesión</a>
        <a href="crear-cuenta">¿No Tienes una Cuenta? Crear Una</a>
    </div>
</div>
<script type="module" src="/assets/js/formularios.js"></script>