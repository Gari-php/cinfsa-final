<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Ficha</h2>
    <form method="POST"
          action="/administrador/fichas/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/fichas/listado">

        <div class="campo">
            <label for="precio_ficha">Precio</label>
            <input type="number" step="0.01" name="precio_ficha" id="precio_ficha" placeholder="Precio de la ficha">
        </div>

        <div class="campo">
            <label for="cantidad_ficha">Cantidad</label>
            <input type="number" name="cantidad_ficha" id="cantidad_ficha" placeholder="Cantidad de fichas">
        </div>

        <input type="submit" class="boton" value="Crear">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/fichas/listado">Volver</a>
        </div>
    </form>
</div>
<script type="module" src="/assets/js/formularios.js"></script>
