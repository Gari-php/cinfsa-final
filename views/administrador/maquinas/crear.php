
<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nueva Máquina</h2>

    <form method="POST"
          id="form-container"
          action="/administrador/maquinas/guardar"
          enctype="multipart/form-data"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/maquinas/listado">

        <div class="campo">
            <label for="maquinas_nombre">Nombre de la Máquina</label>
            <input type="text" name="maquinas_nombre" id="maquinas_nombre" placeholder="Nombre de la máquina">
        </div>

        <div class="campo">
            <label for="maquina_descripcion">Descripción</label>
            <textarea name="maquina_descripcion" id="maquina_descripcion" placeholder="Descripción de la máquina" rows="4"></textarea>
        </div>

        <div class="campo">
            <label for="imagen_maquina">Imagen de la Máquina</label>
            <input type="file" name="imagen_maquina" id="imagen_maquina" accept="image/*">
        </div>

        <!-- NUEVO CAMPO: Selector de fichas -->
        <div class="campo">
            <label for="rela_fichas">Tipo de Ficha</label>
            <select name="rela_fichas" id="rela_fichas">
                <?php foreach($fichas as $ficha): ?>
                    <option value="<?php echo $ficha->id_fichas; ?>" 
                            <?php echo $ficha->id_fichas == 1 ? 'selected' : ''; ?>>
                        Ficha Universal - $<?php echo number_format($ficha->precio_ficha, 0, ',', '.'); ?> 
                        (Stock: <?php echo $ficha->cantidad_ficha; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" selected>Activa</option>
                <option value="0">Baja</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear Máquina">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/maquinas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>


