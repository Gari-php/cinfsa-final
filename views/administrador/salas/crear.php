<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nueva Sala</h2>

    <form method="POST"
          id="form-container"
          action="/administrador/salas/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/salas/listado">

        <div class="campo">
            <label for="capacidad_sala">Capacidad</label>
            <input type="number" name="capacidad_sala" id="capacidad_sala" placeholder="Capacidad de la sala">
        </div>

        <div class="campo">
            <label for="filas_sala">Filas</label>
            <input type="number" name="filas_sala" id="filas_sala" placeholder="Cantidad de filas">
        </div>

        <div class="campo">
            <label for="columnas_sala">Columnas</label>
            <input type="number" name="columnas_sala" id="columnas_sala" placeholder="Cantidad de columnas">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" selected>Activa</option>
                <option value="0">Baja</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear Sala">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/salas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
