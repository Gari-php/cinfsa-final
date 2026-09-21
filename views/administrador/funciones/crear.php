<div class="funcion-wizard">

    <!-- PASO 1: Elegir película -->
    <div class="wizard-step" id="step-pelicula">
    <div class="step-header">
        <span class="step-numero">1</span>
        <h2>Elegir Película</h2>
        <a href="/administrador/funciones/listado" 
           style="margin-left: auto; display: inline-flex; align-items: center; gap: 6px; 
                  padding: 8px 16px; background: #1a202c; color: #a0aec0; 
                  border: 1px solid #4a5568; border-radius: 8px; text-decoration: none; 
                  font-weight: 600; font-size: 13px !important; transition: all 0.2s ease;">
            <i class="fa-solid fa-arrow-left" style="color: #ed850f;"></i> Volver
        </a>
    </div>
    <div class="peliculas-grid" id="peliculas-grid">
            <?php foreach ($peliculas as $pelicula): ?>
                <div class="pelicula-card" data-id="<?php echo $pelicula['id_pelicula']; ?>"
                    data-titulo="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>"
                    data-imagen="<?php echo $pelicula['imagen_pelicula']; ?>"
                    onclick="seleccionarPelicula(this)">
                    <div class="pelicula-poster">
                        <?php if ($pelicula['imagen_pelicula']): ?>
                            <img src="/assets/img/peliculas/<?php echo $pelicula['imagen_pelicula']; ?>"
                                alt="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>">
                        <?php else: ?>
                            <div class="sin-poster"><i class="fa-solid fa-film"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="pelicula-info">
                        <span class="pelicula-titulo"><?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PASO 2: Configurar funciones (aparece al elegir película) -->
    <div class="wizard-step" id="step-config" style="display:none;">

        <!-- Header con poster y título -->
        <div class="config-header" id="config-header">
            <img id="poster-seleccionado" src="" alt="">
            <div>
                <h2 id="titulo-seleccionado"></h2>
                <button type="button" class="btn-cambiar-pelicula" onclick="cambiarPelicula()">
                    <i class="fa-solid fa-arrow-left"></i> Cambiar película
                </button>
            </div>
        </div>

        <!-- Fechas -->
        <div class="config-seccion">
            <h3><i class="fa-solid fa-calendar"></i> Fechas</h3>
            <div class="fechas-grid">
                <div class="fecha-campo">
                    <label>Fecha Desde</label>
                    <input type="date" id="fecha_desde" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="fecha-campo">
                    <label>Fecha Hasta</label>
                    <input type="date" id="fecha_hasta" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        </div>

        <!-- Días de la semana -->
        <div class="config-seccion">
            <h3><i class="fa-solid fa-calendar-week"></i> Días de la Semana</h3>
            <div class="dias-grid">
                <button type="button" class="dia-btn" data-dia="1">Lun</button>
                <button type="button" class="dia-btn" data-dia="2">Mar</button>
                <button type="button" class="dia-btn" data-dia="3">Mié</button>
                <button type="button" class="dia-btn" data-dia="4">Jue</button>
                <button type="button" class="dia-btn" data-dia="5">Vie</button>
                <button type="button" class="dia-btn" data-dia="6">Sáb</button>
                <button type="button" class="dia-btn" data-dia="7">Dom</button>
            </div>
        </div>

        <!-- Horarios -->
        <div class="config-seccion">
            <h3><i class="fa-solid fa-clock"></i> Horarios</h3>
            <div id="lista-horarios"></div>
            <button type="button" class="btn-agregar-horario" onclick="agregarHorario()">
                <i class="fa-solid fa-plus"></i> Agregar Horario
            </button>
        </div>

        <!-- Acciones -->
        <div class="config-acciones">
            <a href="/administrador/funciones/listado" class="btn-cancelar">
                <i class="fa-solid fa-times"></i> Cancelar
            </a>
            <button type="button" class="btn-guardar" onclick="guardarFunciones()">
                <i class="fa-solid fa-check"></i> Crear Funciones
            </button>
        </div>

        <div id="alertas-wizard"></div>
    </div>
</div>

<style>
    .funcion-wizard {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* PASO 1 */
    .wizard-step {
        background: #2d3748;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        border: 1px solid #4a5568;
    }

    .step-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        color: #fff;
    }

    .step-numero {
        background: #ed850f;
        color: #fff;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem !important;
        flex-shrink: 0;
    }

    .step-header h2 {
        margin: 0;
        font-size: 1.3rem;
        color: #fff;
    }

    /* GRID DE PELÍCULAS */
    .peliculas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 1rem;
        max-height: 400px;
        overflow-y: auto;
        padding-right: 5px;
    }

    .peliculas-grid::-webkit-scrollbar {
        width: 6px;
    }

    .peliculas-grid::-webkit-scrollbar-track {
        background: #1a202c;
    }

    .peliculas-grid::-webkit-scrollbar-thumb {
        background: #ed850f;
        border-radius: 3px;
    }

    .pelicula-card {
        background: #1a202c;
        border-radius: 10px;
        border: 2px solid #4a5568;
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
    }

    .pelicula-card:hover {
        border-color: #ed850f;
        transform: translateY(-3px);
        box-shadow: 0 0 12px rgba(237, 133, 15, 0.3);
    }

    .pelicula-card.seleccionada {
        border-color: #ed850f;
        box-shadow: 0 0 15px rgba(237, 133, 15, 0.5);
    }

    .pelicula-poster img {
        width: 100%;
        aspect-ratio: 2/3;
        object-fit: cover;
        display: block;
    }

    .sin-poster {
        width: 100%;
        aspect-ratio: 2/3;
        background: #2d3748;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        font-size: 2rem !important;
    }

    .pelicula-info {
        padding: 8px;
    }

    .pelicula-titulo {
        color: #fff;
        font-size: 11px !important;
        font-weight: 600;
        display: block;
        text-align: center;
        line-height: 1.3;
    }

    /* PASO 2 - CONFIG */
    .config-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #4a5568;
    }

    .config-header img {
        width: 80px;
        border-radius: 8px;
        object-fit: cover;
        aspect-ratio: 2/3;
    }

    .config-header h2 {
        color: #ed850f;
        margin: 0 0 0.5rem 0;
        font-size: 1.4rem;
    }

    .btn-cambiar-pelicula {
        background: transparent;
        border: 1px solid #4a5568;
        color: #a0aec0;
        padding: 6px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px !important;
        transition: all 0.2s ease;
    }

    .btn-cambiar-pelicula:hover {
        border-color: #ed850f;
        color: #ed850f;
    }

    .config-seccion {
        margin-bottom: 2rem;
    }

    .config-seccion h3 {
        color: #ed850f;
        font-size: 1rem;
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #4a5568;
    }

    /* FECHAS */
    .fechas-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .fecha-campo label {
        display: block;
        color: #a0aec0;
        font-size: 13px !important;
        margin-bottom: 0.4rem;
        font-weight: 600;
    }

    .fecha-campo input {
        width: 100%;
        padding: 10px;
        background: #1a202c;
        border: 1px solid #4a5568;
        border-radius: 8px;
        color: #fff;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }

    .fecha-campo input:focus {
        outline: none;
        border-color: #ed850f;
    }

    .fecha-campo input::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }

    /* DÍAS */
    .dias-grid {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .dia-btn {
        width: 55px;
        height: 55px;
        border-radius: 10px;
        border: 2px solid #4a5568;
        background: #1a202c;
        color: #a0aec0;
        font-weight: bold;
        font-size: 13px !important;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dia-btn:hover {
        border-color: #ed850f;
        color: #ed850f;
    }

    .dia-btn.activo {
        background: #ed850f;
        border-color: #ed850f;
        color: #fff;
        box-shadow: 0 0 10px rgba(237, 133, 15, 0.4);
    }

    /* HORARIOS */
    .horario-item {
        background: #1a202c;
        border: 1px solid #4a5568;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr auto;
        gap: 0.75rem;
        align-items: end;
    }

    .horario-campo label {
        display: block;
        color: #a0aec0;
        font-size: 11px !important;
        margin-bottom: 4px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .horario-campo select {
        width: 100%;
        padding: 8px 10px;
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 6px;
        color: #fff;
        font-size: 13px !important;
        transition: border-color 0.2s;
    }

    .horario-campo select:focus {
        outline: none;
        border-color: #ed850f;
    }

    .horario-campo select option {
        background: #2d3748;
        color: #fff;
    }

    .btn-eliminar-horario {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid #ef4444;
        color: #fca5a5;
        width: 36px;
        height: 36px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-eliminar-horario:hover {
        background: #ef4444;
        color: #fff;
    }

    .btn-agregar-horario {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: transparent;
        border: 2px dashed #4a5568;
        border-radius: 8px;
        color: #a0aec0;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px !important;
        transition: all 0.2s;
        margin-top: 0.5rem;
    }

    .btn-agregar-horario:hover {
        border-color: #ed850f;
        color: #ed850f;
    }

    /* ACCIONES */
    .config-acciones {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1.5rem;
        border-top: 1px solid #4a5568;
    }

    .btn-cancelar {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 10px;
        color: #fff;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-cancelar:hover {
        border-color: #ef4444;
        color: #fca5a5;
    }

    .btn-guardar {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        background: linear-gradient(135deg, #ed850f, #d97706);
        border: none;
        border-radius: 10px;
        color: #fff;
        font-weight: bold;
        font-size: 15px !important;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(237, 133, 15, 0.4);
    }

    .btn-guardar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 133, 15, 0.5);
    }

    .btn-guardar:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 768px) {
        .funcion-wizard {
            padding: 1rem;
        }

        .fechas-grid {
            grid-template-columns: 1fr;
        }

        .horario-item {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<script>
    // Datos del PHP
    const TURNOS = <?php echo json_encode($turnos); ?>;
    const IDIOMAS = <?php echo json_encode($idiomas); ?>;
    const SALAS = <?php echo json_encode($salas); ?>;
    const TIPOS_ENTRADA = <?php echo json_encode($tipos_entrada); ?>;

    let peliculaSeleccionada = null;
    let contadorHorarios = 0;

    // ===== PASO 1: SELECCIONAR PELÍCULA =====
    function seleccionarPelicula(card) {
        document.querySelectorAll('.pelicula-card').forEach(c => c.classList.remove('seleccionada'));
        card.classList.add('seleccionada');

        peliculaSeleccionada = {
            id: card.dataset.id,
            titulo: card.dataset.titulo,
            imagen: card.dataset.imagen
        };

        // Mostrar paso 2
        const poster = document.getElementById('poster-seleccionado');
        poster.src = peliculaSeleccionada.imagen ?
            '/assets/img/peliculas/' + peliculaSeleccionada.imagen :
            '';
        poster.style.display = peliculaSeleccionada.imagen ? 'block' : 'none';

        document.getElementById('titulo-seleccionado').textContent = peliculaSeleccionada.titulo;
        document.getElementById('step-config').style.display = 'block';
        document.getElementById('step-config').scrollIntoView({
            behavior: 'smooth'
        });

        // Agregar un horario por defecto
        if (contadorHorarios === 0) agregarHorario();
    }

    function cambiarPelicula() {
        document.getElementById('step-config').style.display = 'none';
        peliculaSeleccionada = null;
        document.querySelectorAll('.pelicula-card').forEach(c => c.classList.remove('seleccionada'));
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // ===== DÍAS DE LA SEMANA =====
    document.querySelectorAll('.dia-btn').forEach(btn => {
        btn.addEventListener('click', () => btn.classList.toggle('activo'));
    });

    // ===== HORARIOS =====
    function agregarHorario() {
        contadorHorarios++;
        const id = `horario_${contadorHorarios}`;

        const opcionesTurnos = TURNOS.map(t =>
            `<option value="${t.id_turnos}">${t.turno_horario}</option>`
        ).join('');

        const opcionesIdiomas = IDIOMAS.map(i =>
            `<option value="${i.id_idioma_pelicula}">${i.nombre_idioma_pelicula}</option>`
        ).join('');

        const opcionesSalas = SALAS.map(s =>
            `<option value="${s.id_sala}">Sala ${s.id_sala} (Cap: ${s.capacidad_sala})</option>`
        ).join('');

        const opcionesTipos = TIPOS_ENTRADA.map(t =>
            `<option value="${t.id_tipo_entrada}">${t.tipo_entrada_desc} - $${Number(t.precio_entrada).toLocaleString('es-AR')}</option>`
        ).join('');

        const html = `
        <div class="horario-item" id="${id}">
            <div class="horario-campo">
                <label>Turno / Hora</label>
                <select name="turno_${id}">
                    <option value="">-- Turno --</option>
                    ${opcionesTurnos}
                </select>
            </div>
            <div class="horario-campo">
                <label>Idioma</label>
                <select name="idioma_${id}">
                    <option value="">-- Idioma --</option>
                    ${opcionesIdiomas}
                </select>
            </div>
            <div class="horario-campo">
                <label>Sala</label>
                <select name="sala_${id}">
                    <option value="">-- Sala --</option>
                    ${opcionesSalas}
                </select>
            </div>
            <div class="horario-campo">
                <label>Tipo Entrada</label>
                <select name="tipo_${id}">
                    <option value="">-- Tipo --</option>
                    ${opcionesTipos}
                </select>
            </div>
            <button type="button" class="btn-eliminar-horario" onclick="eliminarHorario('${id}')" title="Eliminar">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;

        document.getElementById('lista-horarios').insertAdjacentHTML('beforeend', html);
    }

    function eliminarHorario(id) {
        document.getElementById(id)?.remove();
    }

    // ===== GUARDAR =====
    async function guardarFunciones() {
        if (!peliculaSeleccionada) {
            mostrarAlertaWizard('Seleccioná una película', 'error');
            return;
        }

        const fechaDesde = document.getElementById('fecha_desde').value;
        const fechaHasta = document.getElementById('fecha_hasta').value;

        if (!fechaDesde || !fechaHasta) {
            mostrarAlertaWizard('Completá las fechas', 'error');
            return;
        }

        if (new Date(fechaDesde) > new Date(fechaHasta)) {
            mostrarAlertaWizard('La fecha desde no puede ser mayor que la fecha hasta', 'error');
            return;
        }

        const diasSeleccionados = Array.from(document.querySelectorAll('.dia-btn.activo'))
            .map(btn => parseInt(btn.dataset.dia));

        if (diasSeleccionados.length === 0) {
            mostrarAlertaWizard('Seleccioná al menos un día de la semana', 'error');
            return;
        }

        const horarios = [];
        let hayErrorHorario = false;

        document.querySelectorAll('.horario-item').forEach(item => {
            const turno = item.querySelector('select[name^="turno_"]').value;
            const idioma = item.querySelector('select[name^="idioma_"]').value;
            const sala = item.querySelector('select[name^="sala_"]').value;
            const tipo = item.querySelector('select[name^="tipo_"]').value;

            if (!turno || !idioma || !sala || !tipo) {
                hayErrorHorario = true;
                return;
            }

            horarios.push({
                turno,
                idioma,
                sala,
                tipo_entrada: tipo
            });
        });

        if (hayErrorHorario) {
            mostrarAlertaWizard('Completá todos los campos de cada horario', 'error');
            return;
        }

        if (horarios.length === 0) {
            mostrarAlertaWizard('Agregá al menos un horario', 'error');
            return;
        }

        const btn = document.querySelector('.btn-guardar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creando funciones...';

        try {
            const response = await fetch('/administrador/funciones/guardar-masivo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_pelicula: peliculaSeleccionada.id,
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta,
                    dias_semana: diasSeleccionados,
                    horarios: horarios,
                    estado: 1
                })
            });

            const data = await response.json();

            if (data.ok) {
                mostrarAlertaWizard('✅ ' + data.mensaje, 'exito');
                setTimeout(() => window.location.href = '/administrador/funciones/listado', 1500);
            } else {
                mostrarAlertaWizard('❌ ' + data.mensaje, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Crear Funciones';
            }
        } catch (error) {
            mostrarAlertaWizard('❌ Error de conexión', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Crear Funciones';
        }
    }

    function mostrarAlertaWizard(mensaje, tipo) {
        const colores = {
            exito: '#22c55e',
            error: '#ef4444',
            info: '#3b82f6'
        };
        const div = document.getElementById('alertas-wizard');
        div.innerHTML = `
        <div style="margin-top:1rem; padding:12px 20px; background:${colores[tipo] || colores.info}; 
             color:#fff; border-radius:8px; font-weight:600;">
            ${mensaje}
        </div>
    `;
        div.scrollIntoView({
            behavior: 'smooth'
        });
        setTimeout(() => div.innerHTML = '', 4000);
    }
</script>