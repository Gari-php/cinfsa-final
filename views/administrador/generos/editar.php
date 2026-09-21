<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Género</h2>

    <form method="POST"
          action="/administrador/generos/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/generos/listado">

        <input type="hidden" name="id_genero_pelicula" value="<?= $genero->id_genero_pelicula ?>">

        <div class="campo">
            <label for="genero_pelicula">Género de Película</label>
            <input type="text" name="genero_pelicula" id="genero_pelicula" value="<?= $genero->genero_pelicula ?>">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $genero->estado == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $genero->estado == 0 ? 'selected' : '' ?>>Baja</option>
            </select>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Género">
        
        <div class="acciones">
            <a href="/administrador/generos/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>