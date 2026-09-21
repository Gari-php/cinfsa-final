<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nuevo Turno</h2>

    <form method="POST"
          action="/administrador/turnos/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/turnos/listado">

        <div class="campo">
            <label for="turno_horario">Horario</label>
            <input type="time" name="turno_horario" id="turno_horario" placeholder="Horario del Turno">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" selected>Activa</option>
                <option value="0">Baja</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear turno">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/turnos/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>
