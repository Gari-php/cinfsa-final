<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo" class="form-logo">
    <h2>Editar Sexo</h2>
    <form method="POST"
          action="/administrador/sexo/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/sexo/listado">

        <input type="hidden" name="id_sexo" value="<?= $sexo->id_sexo ?>">

        <div class="campo">
            <label for="nombre_sexo">Nombre</label>
            <input type="text" name="nombre_sexo" id="nombre_sexo" value="<?= htmlspecialchars($sexo->nombre_sexo) ?>">
        </div>

        <input type="submit" class="boton" value="Actualizar">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/sexo/listado">Volver</a>
        </div>
    </form>
</div>
<script type="module" src="/assets/js/formularios.js"></script>
