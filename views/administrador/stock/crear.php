<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nuevo Stock</h2>

    <form method="POST"
          action="/administrador/stock/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/stock/listado">

        <div class="campo">
            <label for="rela_cantina">Cantina</label>
            <select name="rela_cantina" id="rela_cantina">
                <option value="">Seleccione una cantina</option>
                <?php foreach ($cantinas as $cantina) { ?>
                    <option value="<?php echo $cantina['id_cantina']; ?>">
                        <?php echo htmlspecialchars($cantina['nombre_cantina']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="campo">
            <label for="rela_producto_cantina">Producto</label>
            <select name="rela_producto_cantina" id="rela_producto_cantina" disabled>
                <option value="">Primero seleccione una cantina</option>
            </select>
        </div>

        <div class="campo">
            <label for="stock_cantina">Cantidad en Stock</label>
            <input type="number" 
                   name="stock_cantina" 
                   id="stock_cantina" 
                   placeholder="Cantidad inicial de stock"
                   step="1">
        </div>

        <input type="submit" class="boton" value="Crear Stock">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/stock/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectCantina = document.getElementById('rela_cantina');
    const selectProducto = document.getElementById('rela_producto_cantina');

    selectCantina.addEventListener('change', async () => {
        const cantinaId = selectCantina.value;

        if (!cantinaId) {
            selectProducto.innerHTML = '<option value="">Primero seleccione una cantina</option>';
            selectProducto.disabled = true;
            return;
        }

        selectProducto.innerHTML = '<option value="">Cargando productos...</option>';
        selectProducto.disabled = true;

        try {
            const response = await fetch(`/administrador/stock/productos-por-cantina?cantina_id=${cantinaId}`);
            const data = await response.json();

            if (data.ok && data.productos.length > 0) {
                selectProducto.innerHTML = '<option value="">Seleccione un producto</option>' +
                    data.productos.map(p =>
                        `<option value="${p.id_producto_cantina}">${p.nombre_producto_cantina}</option>`
                    ).join('');
                selectProducto.disabled = false;
            } else {
                selectProducto.innerHTML = '<option value="">Todos los productos ya tienen stock en esta cantina</option>';
                selectProducto.disabled = true;
            }
        } catch (error) {
            console.error('Error al cargar productos:', error);
            selectProducto.innerHTML = '<option value="">Error al cargar productos</option>';
            selectProducto.disabled = true;
        }
    });
});
</script>