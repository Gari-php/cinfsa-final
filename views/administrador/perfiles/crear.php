<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Perfil</h2>
    <form method="POST"
          action="/administrador/perfiles/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/perfiles/listado">

        <div class="campo">
            <label for="nombre_perfil">Nombre del Perfil</label>
            <input type="text" name="nombre_perfil" id="nombre_perfil" placeholder="Ej: Administrador">
        </div>

        <div class="campo">
            <label for="permiso_perfil">Permiso</label>
            <input type="text" name="permiso_perfil" id="permiso_perfil" placeholder="Ej: Total">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/perfiles/listado">Volver</a>
        </div>
    </form>
</div>
<script type="module" src="/assets/js/formularios.js"></script>
