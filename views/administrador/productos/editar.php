<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Producto</h2>

    <form method="POST"
          id="form-container"
          action="/administrador/productos/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/productos/listado"
          enctype="multipart/form-data">

        <input type="hidden" name="id_producto_cantina" value="<?= $producto->id_producto_cantina ?>">

        <div class="campo">
            <label for="nombre_producto_cantina">Nombre</label>
            <input type="text" name="nombre_producto_cantina" id="nombre_producto_cantina" 
                   placeholder="Nombre del producto" value="<?= htmlspecialchars($producto->nombre_producto_cantina) ?>">
        </div>

        <div class="campo">
            <label for="precio_producto">Precio</label>
            <input type="number" name="precio_producto" id="precio_producto" 
                   placeholder="Precio del producto" step="0.01" min="0" value="<?= $producto->precio_producto ?>">
        </div>

        <div class="campo">
            <label for="rela_estado_producto">Estado</label>
            <select name="rela_estado_producto" id="rela_estado_producto">
                <option value="1" <?= $producto->rela_estado_producto == 1 ? 'selected' : '' ?>>Disponible</option>
                <option value="2" <?= $producto->rela_estado_producto == 2 ? 'selected' : '' ?>>Agotado</option>
                <option value="3" <?= $producto->rela_estado_producto == 3 ? 'selected' : '' ?>>Suspendido</option>
            </select>
        </div>

        <!-- Imagen actual -->
        <?php if ($producto->imagen_producto): ?>
        <div class="imagen-actual">
            <label>Imagen Actual</label>
            <div class="contenedor-imagen-actual">
                <img src="/assets/img/productos/<?= htmlspecialchars($producto->imagen_producto) ?>" 
                     alt="<?= htmlspecialchars($producto->nombre_producto_cantina) ?>"
                     style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            </div>
        </div>
        <?php endif; ?>

        <div class="campo">
            <label for="imagen_producto">Nueva Imagen (opcional)</label>
            <input type="file" name="imagen_producto" id="imagen_producto" 
                   accept="image/jpeg,image/jpg,image/png,image/webp">
            <small class="ayuda">Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamaño máximo: 5MB. Dejar vacío para mantener la imagen actual.</small>
        </div>

        <div class="preview-imagen" id="preview-container" style="display: none;">
            <label>Vista Previa de Nueva Imagen</label>
            <img id="imagen-preview" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px;">
        </div>

        <input type="submit" class="boton" value="Actualizar Producto">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/productos/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>



