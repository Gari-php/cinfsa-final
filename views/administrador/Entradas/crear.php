<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nueva Entrada</h2>

    <form method="POST"
          action="/administrador/entradas/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/entradas/listado">

        <div class="campo">
            <label for="numero_ticket_entrada">Número de Ticket</label>
            <input type="number" name="numero_ticket_entrada" id="numero_ticket_entrada" placeholder="Número de Ticket (opcional - se genera automáticamente si se deja vacío)">
        </div>

        <div class="campo">
            <label for="rela_funcion">Función</label>
            <select name="rela_funcion" id="rela_funcion">
                <option value="">Seleccione una función</option>
                <?php foreach($funciones as $funcion): ?>
                    <option value="<?php echo $funcion['id_funcion']; ?>">
                        Función <?php echo $funcion['id_funcion']; ?> - <?php echo $funcion['titulo_pelicula']; ?> - Sala <?php echo $funcion['id_sala']; ?> - <?php echo date('d/m/Y H:i', strtotime($funcion['fecha_hora'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="rela_tipo_entrada">Tipo de Entrada</label>
            <select name="rela_tipo_entrada" id="rela_tipo_entrada">
                <option value="">Seleccione un tipo de entrada</option>
                <?php foreach($tipos_entradas as $tipo): ?>
                    <option value="<?php echo $tipo['id_tipo_entrada']; ?>">
                        <?php echo $tipo['tipo_entrada_desc']; ?> - $<?php echo number_format($tipo['precio_entrada'], 2); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="submit" class="boton" value="Crear Entrada">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/entradas/listado">Volver</a>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>