<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nueva Entrada</h2>

    <form method="POST"
          action="/administrador/tipos_entradas/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/tipos_entradas/listado">

        <div class="campo">
            <label for="tipo_entrada_desc">Descripcion</label>
            <input type="text" name="tipo_entrada_desc" id="tipo_entrada_desc" placeholder="Descripcion de la Entrada">
        </div>

        <div class="campo">
            <label for="precio_entrada">Precio</label>
            <input type="number" name="precio_entrada" id="precio_entrada" placeholder="Precio de la entrada">
        </div>

       
        <input type="submit" class="boton" value="Crear tipo entrada">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/tipos_entradas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>