

<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Registrarse</h2>

    <form method="POST" action="/crear-cuenta" data-fetch="true" data-alerta=".contenedor-alertas" data-redirigir="/">
        <div class="campo">  
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" placeholder="Tu Nombre" >
        </div> 
        <div class="campo"> 
            <label for="apellido">Apellido</label>
            <input type="text" name="apellido" id="apellido" placeholder="Tu Apellido" >
        </div> 
        <div class="campo"> 
            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" >
        </div> 
        <div class="campo"> 
            <label for="sexo">Sexo</label>
            <select name="sexo" id="sexo" >
                <option value="" disabled selected>-- Seleccione --</option>
                <option value="1">Masculino</option>
                <option value="2">Femenino</option>
                <option value="3">Binario</option>
            </select>
            
        </div> 
        <div class="campo"> 
            <label for="email">E-mail</label>
            <input type="text" name="email" id="email" placeholder="Tu Email">
        </div> 
        <div class="campo">     
            <label for="nombre_usuario">Nombre de Usuario</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario" placeholder="Tu Usuario" >
        </div> 
        <div class="campo">      
            <label for="password">Contraseña</label>
            <div class="input-con-icono">
                <input type="password" name="password" id="password" placeholder="Contraseña">
                <span class="icono" data-mostrar-password="password" role="button" tabindex="0" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
            </div>
        </div>
        <div class="campo">     
            <label for="confirm_password">Repetir Contraseña</label>
            <div class="input-con-icono">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Repite la contraseña">
                <span class="icono" data-mostrar-password="confirm_password" role="button" tabindex="0" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
            </div>
        </div>

        <input type="submit" class="boton" value="Registrar">
        <div class="acciones">
            <a href="/">Volver</a>
        </div>
        <div class="contenedor-alertas"></div>

    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
<script src="/assets/js/mostrar-password.js"></script>