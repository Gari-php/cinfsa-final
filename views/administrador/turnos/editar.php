<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Turnos</h2>

    <form method="POST"
          action="/administrador/turnos/actualizar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/turnos/listado">

        <input type="hidden" name="id_turnos" value="<?= $turno->id_turnos ?>">

        <div class="campo">
            <label for="turno_horario">Capacidad</label>
            <input type="time" name="turno_horario" id="turno_horario" value="<?= $turno->turno_horario ?>">
        </div>


        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $turno->estado == 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= $turno->estado == 0 ? 'selected' : '' ?>>Baja</option>
            </select>
        </div>
        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar turno">
        
        

        <div class="acciones">
            <a href="/administrador/turnos/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>