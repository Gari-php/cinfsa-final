<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Butaca</h2>

    <form method="POST"
          action="/administrador/butacas/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/butacas/listado">

        <input type="hidden" name="id_butaca" value="<?= $butaca->id_butaca ?>">

        <div class="campo">
            <label for="rela_salas">Sala</label>
            <select name="rela_salas" id="rela_salas">
                <option value="" disabled>-- Seleccione --</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?php echo $sala['id_sala']; ?>"
                            <?php echo $butaca->rela_salas == $sala['id_sala'] ? 'selected' : ''; ?>>
                        Sala <?php echo $sala['id_sala']; ?> - <?php echo $sala['capacidad_sala']; ?> personas
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="fila_butaca">Fila</label>
            <input type="number" name="fila_butaca" id="fila_butaca" 
                   value="<?= $butaca->fila_butaca ?>" min="1" max="30">
        </div>

        <div class="campo">
            <label for="numero_butaca">Número de Butaca</label>
            <input type="number" name="numero_butaca" id="numero_butaca" 
                   value="<?= $butaca->numero_butaca ?>" min="1" max="25">
        </div>

        <div class="campo">
            <label for="rela_estado_butaca">Estado</label>
            <select name="rela_estado_butaca" id="rela_estado_butaca">
                <?php foreach ($estados as $estado): ?>
                    <option value="<?php echo $estado['id_estado_butaca']; ?>"
                            <?php echo $butaca->rela_estado_butaca == $estado['id_estado_butaca'] ? 'selected' : ''; ?>>
                        <?php echo $estado['nombre_estado_butaca']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Butaca">

        <div class="acciones">
            <a href="/administrador/butacas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>