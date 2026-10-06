<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Cantina</h2>

    <form method="POST"
          action="/administrador/cantina/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/cantina/listado">

        <input type="hidden" name="id_cantina" value="<?= $cantina->id_cantina ?>">

        <div class="campo">
            <label for="nombre_cantina">Nombre de la Cantina</label>
            <input type="text" name="nombre_cantina" id="nombre_cantina" value="<?= s($cantina->nombre_cantina) ?>">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $cantina->estado == 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= $cantina->estado == 0 ? 'selected' : '' ?>>Inactiva</option>
            </select>
        </div>

        <div class="campo">
            <label>Cajas de esta cantina</label>
            <input type="hidden" name="cajas_enviadas" value="1">
            <div class="cajas-cantina">
                <?php foreach ($cajas as $caja): ?>
                    <?php $esDeEsta = (int) $caja['rela_cantina'] === (int) $cantina->id_cantina; ?>
                    <label class="caja-cantina">
                        <input type="checkbox" name="caja_<?= $caja['id_caja'] ?>" value="1"
                               <?= $esDeEsta ? 'checked' : '' ?>
                               <?= $caja['abierta'] ? 'disabled' : '' ?>>
                        <?php if ($caja['abierta'] && $esDeEsta): ?>
                            <?php // Un checkbox deshabilitado no se envía: se manda igual para no desasignarla ?>
                            <input type="hidden" name="caja_<?= $caja['id_caja'] ?>" value="1">
                        <?php endif; ?>
                        <span>
                            <?= s($caja['codigo_caja'] . ' - ' . $caja['nombre_caja']) ?>
                            <small>
                                <?php if ($caja['abierta']): ?>
                                    Abierta ahora: no se puede cambiar de cantina.
                                <?php elseif (!$caja['rela_cantina']): ?>
                                    Sin cantina asignada.
                                <?php elseif (!$esDeEsta): ?>
                                    Hoy está en <?= s($caja['nombre_cantina']) ?>; si la marcás, pasa a esta.
                                <?php endif; ?>
                            </small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small>El vendedor que abra una de estas cajas solo ve y vende el stock de esta cantina.</small>
        </div>

        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Cantina">
        
        <div class="acciones">
            <a href="/administrador/cantina/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
<style>
.form-container .cajas-cantina {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-container .campo label.caja-cantina {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 0;
    padding: 10px 12px;
    border: 2px solid #4a5568;
    border-radius: 10px;
    background: #2d3748;
    color: #e2e8f0;
    font-weight: 400;
    cursor: pointer;
}

.form-container .campo label.caja-cantina input {
    width: auto;
    margin-top: 3px;
    accent-color: #ed850f;
}

.form-container .caja-cantina small {
    display: block;
    color: #a0aec0;
}
</style>
