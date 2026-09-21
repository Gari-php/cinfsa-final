<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Sala</h2>

    <form method="POST"
          action="/administrador/salas/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/salas/listado">

        <input type="hidden" name="id_sala" value="<?= $sala->id_sala ?>">

        <div class="campo">
            <label for="capacidad_sala">Capacidad</label>
            <input type="number" name="capacidad_sala" id="capacidad_sala" value="<?= $sala->capacidad_sala ?>">
        </div>

        <div class="campo">
            <label for="filas_sala">Cantidad de Filas</label>
            <input type="number" name="filas_sala" id="filas_sala" value="<?= $sala->filas_sala ?>">
        </div>

        <div class="campo">
            <label for="columnas_sala">Cantidad de Columnas</label>
            <input type="number" name="columnas_sala" id="columnas_sala" value="<?= $sala->columnas_sala ?>">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $sala->estado == 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= $sala->estado == 0 ? 'selected' : '' ?>>Baja</option>
            </select>
        </div>
        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar sala">
        
        

        <div class="acciones">
            <a href="/administrador/salas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
