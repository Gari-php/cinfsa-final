<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Estado de Película</h2>

    <form method="POST"
          action="/administrador/estados_peliculas/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/estados_peliculas/listado">

        <input type="hidden" name="id_estado_pelicula" value="<?= $estado_pelicula->id_estado_pelicula ?>">

        <div class="campo">
            <label for="nombre_estado_pelicula">Nombre del Estado</label>
            <input type="text" name="nombre_estado_pelicula" id="nombre_estado_pelicula" 
                   value="<?= htmlspecialchars($estado_pelicula->nombre_estado_pelicula) ?>" maxlength="45">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $estado_pelicula->estado == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $estado_pelicula->estado == 0 ? 'selected' : '' ?>>Baja</option>
            </select>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Estado de Película">
        
        <div class="acciones">
            <a href="/administrador/estados_peliculas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>