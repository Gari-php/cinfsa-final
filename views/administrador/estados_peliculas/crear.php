<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nuevo Estado de Película</h2>

    <form method="POST"
          action="/administrador/estados_peliculas/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/estados_peliculas/listado">

        <div class="campo">
            <label for="nombre_estado_pelicula">Nombre del Estado</label>
            <input type="text" name="nombre_estado_pelicula" id="nombre_estado_pelicula" placeholder="Nombre del estado de película" maxlength="45">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" selected>Activo</option>
                <option value="0">Baja</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear Estado de Película">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/estados_peliculas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>