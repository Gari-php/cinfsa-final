<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nuevo Producto</h2>

    <form method="POST"
          id="form-container"
          action="/administrador/productos/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/productos/listado"
          enctype="multipart/form-data">

        <div class="campo">
            <label for="nombre_producto_cantina">Nombre</label>
            <input type="text" name="nombre_producto_cantina" id="nombre_producto_cantina" 
                   placeholder="Nombre del producto">
        </div>

        <div class="campo">
            <label for="precio_producto">Precio</label>
            <input type="number" name="precio_producto" id="precio_producto" 
                   placeholder="Precio del producto" step="0.01" min="0">
        </div>

        <div class="campo">
            <label for="rela_estado_producto">Estado</label>
            <select name="rela_estado_producto" id="rela_estado_producto">
                <option value="1" selected>Disponible</option>
                <option value="2">Agotado</option>
                <option value="3">Suspendido</option>
            </select>
        </div>

        <div class="campo">
            <label for="imagen_producto">Imagen del Producto</label>
            <input type="file" name="imagen_producto" id="imagen_producto" 
                   accept="image/jpeg,image/jpg,image/png,image/webp" >
            <small class="ayuda">Formatos permitidos: JPG, JPEG, PNG, WEBP. Tamaño máximo: 5MB</small>
        </div>

        <div class="preview-imagen" id="preview-container" style="display: none;">
            <img id="imagen-preview" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px;">
        </div>

        <input type="submit" class="boton" value="Crear Producto">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/productos/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>


