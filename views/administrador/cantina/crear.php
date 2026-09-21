<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nueva Cantina</h2>

    <form method="POST"
          id="form-container"
          action="/administrador/cantina/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/cantina/listado">

        <div class="campo">
            <label for="nombre_cantina">Nombre de la Cantina</label>
            <input type="text" name="nombre_cantina" id="nombre_cantina" placeholder="Nombre de la cantina">
        </div>

        <input type="submit" class="boton" value="Crear Cantina">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/cantina/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>