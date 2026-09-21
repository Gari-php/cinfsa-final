<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Stock</h2>

    <form method="POST"
          action="/administrador/stock/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/stock/listado">

        <input type="hidden" name="id_stock_cantina" value="<?= $stock->id_stock_cantina ?>">

        <div class="campo">
            <label for="rela_producto_cantina">Producto</label>
            <select name="rela_producto_cantina" id="rela_producto_cantina">
                <option value="">Seleccione un producto</option>
                <?php foreach ($productos as $producto) { ?>
                    <option value="<?php echo $producto['id_producto_cantina']; ?>"
                            <?= $stock->rela_producto_cantina == $producto['id_producto_cantina'] ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($producto['nombre_producto_cantina']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="campo">
            <label for="rela_cantina">Cantina</label>
            <select name="rela_cantina" id="rela_cantina">
                <option value="">Seleccione una cantina</option>
                <?php foreach ($cantinas as $cantina) { ?>
                    <option value="<?php echo $cantina['id_cantina']; ?>"
                            <?= $stock->rela_cantina == $cantina['id_cantina'] ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($cantina['nombre_cantina']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="campo">
            <label for="stock_cantina">Cantidad en Stock</label>
            <input type="number" 
                   name="stock_cantina" 
                   id="stock_cantina" 
                   value="<?= $stock->stock_cantina ?>"
                   min="0"
                   step="1"
                   >
            <small>Stock actual: <strong><?= $stock->stock_cantina ?></strong></small>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Stock">
        
        <div class="acciones">
            <a href="/administrador/stock/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>

