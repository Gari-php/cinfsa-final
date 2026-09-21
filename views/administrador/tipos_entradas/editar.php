<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Sala</h2>

    <form method="POST"
          action="/administrador/tipos_entradas/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/tipos_entradas/listado">

        <input type="hidden" name="id_tipo_entrada" value="<?= $tipos_entradas->id_tipo_entrada ?>">

        <div class="campo">
            <label for="tipo_entrada_desc">Descripcion</label>
            <input type="text" name="tipo_entrada_desc" id="tipo_entrada_desc" value="<?= $tipos_entradas->tipo_entrada_desc ?>">
        </div>

        <div class="campo">
            <label for="precio_entrada">Precio</label>
            <input type="number" name="precio_entrada" id="precio_entrada" value="<?= $tipos_entradas->precio_entrada ?>">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $tipos_entradas->estado == 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= $tipos_entradas->estado == 0 ? 'selected' : '' ?>>Baja</option>
            </select>
        </div>
        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar tipo entrada">
        
        

        <div class="acciones">
            <a href="/administrador/tipos_entradas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
