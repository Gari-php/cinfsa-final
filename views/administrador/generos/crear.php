<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nuevo Género</h2>

    <form method="POST"
          id="form-container"
          action="/administrador/generos/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/generos/listado">

        <div class="campo">
            <label for="genero_pelicula">Género de Película</label>
            <input type="text" name="genero_pelicula" id="genero_pelicula" placeholder="Nombre del género">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" selected>Activo</option>
                <option value="0">Baja</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear Género">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/generos/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>