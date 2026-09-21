<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Máquina</h2>

    <form method="POST"
          action="/administrador/maquinas/actualizar"
          enctype="multipart/form-data"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/maquinas/listado">

        <input type="hidden" name="id_maquinas" value="<?= $maquina->id_maquinas ?>">

        <div class="campo">
            <label for="maquinas_nombre">Nombre de la Máquina</label>
            <input type="text" name="maquinas_nombre" id="maquinas_nombre" value="<?= $maquina->maquinas_nombre ?>">
        </div>

        <div class="campo">
            <label for="maquina_descripcion">Descripción</label>
            <textarea name="maquina_descripcion" id="maquina_descripcion" rows="4"><?= $maquina->maquina_descripcion ?></textarea>
        </div>

        <div class="campo">
            <label for="imagen_maquina">Imagen de la Máquina</label>
            <input type="file" name="imagen_maquina" id="imagen_maquina" accept="image/*">
            <?php if($maquina->imagen_maquina): ?>
                <div style="margin-top: 10px;">
                    <p>Imagen actual:</p>
                    <img src="/assets/img/cantina/<?= $maquina->imagen_maquina ?>" alt="Imagen actual" style="max-width: 200px; height: auto;">
                </div>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $maquina->estado == 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= $maquina->estado == 0 ? 'selected' : '' ?>>Baja</option>
            </select>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Máquina">
        
        <div class="acciones">
            <a href="/administrador/maquinas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>