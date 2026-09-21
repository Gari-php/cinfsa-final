<div class="form-container">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Crear Nueva Butaca</h2>
    
    <form method="POST"
          id="form-container"
          action="/administrador/butacas/guardar"
          data-fetch="true"
          data-alerta=".contenedor-alertas"
          data-redirigir="/administrador/butacas/listado">
        
        <div class="campo">
            <label for="rela_salas">Sala</label>
            <select name="rela_salas" id="rela_salas">
                <option value="" disabled selected>-- Seleccione --</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?php echo $sala['id_sala']; ?>">
                        Sala <?php echo $sala['id_sala']; ?> - <?php echo $sala['capacidad_sala']; ?> personas
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="campo">
            <label for="fila_butaca">Fila</label>
            <input type="number" name="fila_butaca" id="fila_butaca" placeholder="Número de fila" min="1" max="30">
        </div>
        
        <div class="campo">
            <label for="numero_butaca">Número de Butaca</label>
            <input type="number" name="numero_butaca" id="numero_butaca" placeholder="Número de butaca" min="1" max="25">
        </div>
        
        <div class="campo">
            <label for="rela_estado_butaca">Estado</label>
            <select name="rela_estado_butaca" id="rela_estado_butaca">
                <?php foreach ($estados as $estado): ?>
                    <option value="<?php echo $estado['id_estado_butaca']; ?>" 
                            <?php echo $estado['id_estado_butaca'] == 1 ? 'selected' : ''; ?>>
                        <?php echo $estado['nombre_estado_butaca']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <input type="submit" class="boton" value="Crear Butaca">
        <div class="contenedor-alertas"></div>
        
        <div class="acciones">
            <a href="/administrador/butacas/listado">Volver</a>
            <button type="button" class="boton" id="btn-generar-masivo" style="background: #28a745;">
                Generar Todas las Butacas
            </button>
        </div>
    </form>
</div>

<script type="module" src="/assets/js/formularios.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnGenerar = document.getElementById('btn-generar-masivo');
    const selectSala = document.getElementById('rela_salas');
    
    btnGenerar.addEventListener('click', function() {
        const idSala = selectSala.value;
        
        if (!idSala) {
            alert('Debe seleccionar una sala primero');
            return;
        }
        
        if (!confirm('¿Está seguro de generar todas las butacas para esta sala? Esta acción no se puede deshacer.')) {
            return;
        }
        
        // Deshabilitar botón
        btnGenerar.disabled = true;
        btnGenerar.textContent = 'Generando...';
        
        fetch('/administrador/butacas/generar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id_sala: idSala })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                alert(data.mensaje);
                window.location.href = '/administrador/butacas/listado';
            } else {
                alert('Error: ' + data.mensaje);
            }
        })
        .catch(error => {
            alert('Error al generar butacas');
        })
        .finally(() => {
            btnGenerar.disabled = false;
            btnGenerar.textContent = 'Generar Todas las Butacas';
        });
    });
});
</script>