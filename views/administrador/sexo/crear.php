<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo" class="form-logo">
    <h2>Crear Sexo</h2>
    <form method="POST"
          action="/administrador/sexo/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/sexo/listado">

        <div class="campo">
            <label for="nombre_sexo">Nombre</label>
            <input type="text" name="nombre_sexo" id="nombre_sexo" placeholder="Ej: MASCULINO">
        </div>

        <input type="submit" class="boton" value="Crear">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/sexo/listado">Volver</a>
        </div>
    </form>
</div>
<script type="module" src="/assets/js/formularios.js"></script>
