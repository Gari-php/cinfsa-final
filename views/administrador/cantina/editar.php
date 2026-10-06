<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Cantina</h2>

    <form method="POST"
          action="/administrador/cantina/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/cantina/listado">

        <input type="hidden" name="id_cantina" value="<?= $cantina->id_cantina ?>">

        <div class="campo">
            <label for="nombre_cantina">Nombre de la Cantina</label>
            <input type="text" name="nombre_cantina" id="nombre_cantina" value="<?= s($cantina->nombre_cantina) ?>">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $cantina->estado == 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= $cantina->estado == 0 ? 'selected' : '' ?>>Inactiva</option>
            </select>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Cantina">
        
        <div class="acciones">
            <a href="/administrador/cantina/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>