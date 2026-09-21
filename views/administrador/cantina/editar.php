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
            <input type="text" name="nombre_cantina" id="nombre_cantina" value="<?= $cantina->nombre_cantina ?>">
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