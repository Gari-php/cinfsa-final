<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Ficha</h2>
    <form method="POST"
          action="/administrador/fichas/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/fichas/listado">

        <input type="hidden" name="id_fichas" value="<?= $ficha->id_fichas ?>">

        <div class="campo">
            <label for="precio_ficha">Precio</label>
            <input type="number" step="0.01" name="precio_ficha" id="precio_ficha" value="<?= $ficha->precio_ficha ?>">
        </div>

        <div class="campo">
            <label for="cantidad_ficha">Cantidad</label>
            <input type="number" name="cantidad_ficha" id="cantidad_ficha" value="<?= $ficha->cantidad_ficha ?>">
        </div>

        <input type="submit" class="boton" value="Actualizar">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/fichas/listado">Volver</a>
        </div>
    </form>
</div>
<script type="module" src="/assets/js/formularios.js"></script>
