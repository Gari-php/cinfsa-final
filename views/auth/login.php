

<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Iniciar Sesión</h2>
    <div class="contenedor-alertas"></div>

    <form method="POST" action="/" data-fetch="true" data-alerta=".contenedor-alertas" data-redirigir="/menu">

        <div class="campo">
            <label for="nombre_usuario">Usuario o Gmail:</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario" >
        </div>

        <div class="campo">
            <label for="clave_usuario">Contraseña:</label>
            <div class="input-con-icono">
                <input type="password" name="clave_usuario" id="clave_usuario" >
                <span class="icono" data-mostrar-password="clave_usuario" role="button" tabindex="0" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
            </div>    
        </div>

        <input type="submit" class="boton" value="Iniciar Sesión">
    </form>
    

    <div class="acciones">
        <a href="crear-cuenta">¿No Tienes una Cuenta? Crear Una</a>
        <a href="/olvide">¿Olvidaste tu Password?</a>
    </div>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
<script src="/assets/js/mostrar-password.js"></script>