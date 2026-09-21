<div class="editar-funcion-container">

    <!-- Header con poster -->
    <div class="editar-header">
        <div class="editar-poster-wrapper" id="poster-wrapper">
            <?php
            // Buscar imagen de la película actual
            $db = \Models\ActiveRecord::getDB();
            $infoPelicula = $db->query("SELECT imagen_pelicula, titulo_pelicula FROM peliculas WHERE id_pelicula = {$funcion->rela_peliculas}")->fetch_assoc();
            ?>
            <?php if (!empty($infoPelicula['imagen_pelicula'])): ?>
                <img id="poster-img"
                    src="/assets/img/peliculas/<?php echo $infoPelicula['imagen_pelicula']; ?>"
                    alt="<?php echo htmlspecialchars($infoPelicula['titulo_pelicula']); ?>">
            <?php else: ?>
                <div class="sin-poster" id="poster-img">
                    <i class="fa-solid fa-film"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="editar-titulo">
            <h1><i class="fa-solid fa-pen" style="color:#ed850f;"></i> Editar Función</h1>
            <p style="color:#a0aec0; margin:4px 0 0 0;">ID Función: #<?php echo $funcion->id_funcion; ?></p>
        </div>

        <a href="/administrador/funciones/listado" class="btn-volver-editar">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Formulario -->
    <form method="POST"
        action="/administrador/funciones/actualizar"
        data-fetch="true"
        data-alerta=".contenedor-alertas"
        data-redirigir="/administrador/funciones/listado"
        data-sin-confirmacion="true">

        <div class="contenedor-alertas" style="margin-top: 1rem;"></div>

        <input type="hidden" name="id_funcion" value="<?php echo $funcion->id_funcion; ?>">

        <div class="editar-grid">

            <!-- Columna izquierda -->
            <div class="editar-col">
                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-film"></i> Película</h3>
                    <select name="rela_peliculas" id="rela_peliculas" class="editar-select">
                        <?php foreach ($peliculas as $pelicula): ?>
                            <option value="<?php echo $pelicula['id_pelicula']; ?>"
                                <?php echo $funcion->rela_peliculas == $pelicula['id_pelicula'] ? 'selected' : ''; ?>
                                data-imagen="<?php echo $pelicula['imagen_pelicula']; ?>">
                                <?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-calendar"></i> Fechas</h3>
                    <div class="fechas-grid">
                        <div>
                            <label class="editar-label">Fecha Inicio</label>
                            <input type="date" name="fecha_hora" class="editar-input"
                                value="<?php echo $funcion->fecha_hora; ?>">
                        </div>
                        <div>
                            <label class="editar-label">Fecha Fin</label>
                            <input type="date" name="fecha_finalizacion" class="editar-input"
                                value="<?php echo $funcion->fecha_finalizacion; ?>">
                        </div>
                    </div>
                </div>

                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-clock"></i> Turno / Horario</h3>
                    <select name="rela_turnos" class="editar-select">
                        <?php foreach ($turnos as $turno): ?>
                            <option value="<?php echo $turno['id_turnos']; ?>"
                                <?php echo $funcion->rela_turnos == $turno['id_turnos'] ? 'selected' : ''; ?>>
                                <?php echo $turno['turno_horario']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Columna derecha -->
            <div class="editar-col">
                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-couch"></i> Sala</h3>
                    <select name="rela_salas" class="editar-select">
                        <?php foreach ($salas as $sala): ?>
                            <option value="<?php echo $sala['id_sala']; ?>"
                                <?php echo $funcion->rela_salas == $sala['id_sala'] ? 'selected' : ''; ?>>
                                Sala <?php echo $sala['id_sala']; ?> — Cap: <?php echo $sala['capacidad_sala']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-language"></i> Idioma</h3>
                    <select name="rela_idioma" class="editar-select">
                        <option value="">-- Sin idioma --</option>
                        <?php foreach ($idiomas as $idioma): ?>
                            <option value="<?php echo $idioma['id_idioma_pelicula']; ?>"
                                <?php echo isset($funcion->rela_idioma) && $funcion->rela_idioma == $idioma['id_idioma_pelicula'] ? 'selected' : ''; ?>>
                                <?php echo $idioma['nombre_idioma_pelicula']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-ticket"></i> Tipo de Entrada</h3>
                    <select name="rela_tipo_entrada" class="editar-select">
                        <?php foreach ($tipos_entrada as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo_entrada']; ?>"
                                <?php echo $funcion->rela_tipo_entrada == $tipo['id_tipo_entrada'] ? 'selected' : ''; ?>>
                                <?php echo $tipo['tipo_entrada_desc']; ?> — $<?php echo number_format($tipo['precio_entrada'], 0, ',', '.'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="editar-seccion">
                    <h3><i class="fa-solid fa-circle-dot"></i> Estado</h3>
                    <div class="estado-opciones">
                        <label class="estado-opcion <?php echo $funcion->estado == 1 ? 'activa' : ''; ?>">
                            <input type="radio" name="estado" value="1"
                                <?php echo $funcion->estado == 1 ? 'checked' : ''; ?>>
                            <i class="fa-solid fa-circle-check"></i> Activa
                        </label>
                        <label class="estado-opcion <?php echo $funcion->estado == 0 ? 'baja' : ''; ?>">
                            <input type="radio" name="estado" value="0"
                                <?php echo $funcion->estado == 0 ? 'checked' : ''; ?>>
                            <i class="fa-solid fa-circle-xmark"></i> Baja
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="contenedor-alertas"></div>

        <div class="editar-acciones">
            <a href="/administrador/funciones/listado" class="btn-cancelar-editar">
                <i class="fa-solid fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn-guardar-editar">
                <i class="fa-solid fa-check"></i> Actualizar Función
            </button>
        </div>
    </form>
</div>

<style>
    .editar-funcion-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
    }

    .editar-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background: #2d3748;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #4a5568;
        border-bottom: 2px solid #ed850f;
    }

    .editar-poster-wrapper {
        flex-shrink: 0;
    }

    #poster-img {
        width: 70px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #ed850f;
        display: block;
    }

    .sin-poster {
        width: 70px;
        height: 100px;
        background: #1a202c;
        border-radius: 8px;
        border: 2px solid #4a5568;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        font-size: 1.5rem !important;
    }

    .editar-titulo {
        flex: 1;
    }

    .editar-titulo h1 {
        color: #fff;
        margin: 0;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-volver-editar {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #1a202c;
        color: #a0aec0;
        border: 1px solid #4a5568;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px !important;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .btn-volver-editar:hover {
        border-color: #ed850f;
        color: #ed850f;
    }

    /* GRID */
    .editar-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .editar-col {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .editar-seccion {
        background: #2d3748;
        border-radius: 12px;
        padding: 1.25rem;
        border: 1px solid #4a5568;
    }

    .editar-seccion h3 {
        color: #ed850f;
        font-size: 0.9rem;
        margin: 0 0 0.75rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #4a5568;
    }

    .editar-label {
        display: block;
        color: #a0aec0;
        font-size: 12px !important;
        margin-bottom: 4px;
        font-weight: 600;
    }

    .editar-input,
    .editar-select {
        width: 100%;
        padding: 10px 12px;
        background: #1a202c;
        border: 1px solid #4a5568;
        border-radius: 8px;
        color: #fff;
        font-size: 14px !important;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .editar-input:focus,
    .editar-select:focus {
        outline: none;
        border-color: #ed850f;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.15);
    }

    .editar-input::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }

    .editar-select option {
        background: #1a202c;
        color: #fff;
    }

    .fechas-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    /* ESTADO */
    .estado-opciones {
        display: flex;
        gap: 0.75rem;
    }

    .estado-opcion {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px;
        border-radius: 8px;
        border: 2px solid #4a5568;
        cursor: pointer;
        font-weight: 600;
        color: #a0aec0;
        transition: all 0.2s;
        font-size: 13px !important;
    }

    .estado-opcion input[type="radio"] {
        display: none;
    }

    .estado-opcion.activa,
    .estado-opcion:has(input:checked) {
        border-color: #22c55e;
        background: rgba(34, 197, 94, 0.15);
        color: #86efac;
    }

    .estado-opcion.baja {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
    }

    /* ACCIONES */
    .editar-acciones {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1.5rem;
        border-top: 1px solid #4a5568;
    }

    .btn-cancelar-editar {
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

    .btn-cancelar-editar:hover {
        border-color: #ef4444;
        color: #fca5a5;
    }

    .btn-guardar-editar {
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

    .btn-guardar-editar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 133, 15, 0.5);
    }

    /* Inputs con error - mantener paleta oscura */
    .editar-input.error,
    .editar-select.error {
        border-color: #ef4444 !important;
        background: #1a202c !important;
        color: #fff !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
    }

    /* Forzar fondo oscuro en inputs de fecha en todos los estados */
    input[type="date"].editar-input {
        color-scheme: dark;
        background: #1a202c !important;
        color: #fff !important;
    }

    input[type="date"].editar-input::-webkit-datetime-edit {
        color: #fff;
    }

    input[type="date"].editar-input::-webkit-datetime-edit-fields-wrapper {
        background: transparent;
    }

    input[type="date"].editar-input:invalid {
        background: #1a202c !important;
        color: #fff !important;
    }

    @media (max-width: 768px) {
        .editar-funcion-container {
            padding: 1rem;
        }

        .editar-grid {
            grid-template-columns: 1fr;
        }

        .editar-header {
            flex-direction: column;
            text-align: center;
        }

        .fechas-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script type="module" src="/assets/js/formularios.js"></script>
<script>
    // Actualizar poster cuando cambia la película
    document.getElementById('rela_peliculas').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const imagen = option.dataset.imagen;
        const posterImg = document.getElementById('poster-img');

        if (imagen) {
            posterImg.src = '/assets/img/peliculas/' + imagen;
            posterImg.style.display = 'block';
        } else {
            posterImg.style.display = 'none';
        }
    });

    // Actualizar visualmente el estado al clickear
    document.querySelectorAll('.estado-opcion').forEach(opcion => {
        opcion.addEventListener('click', function() {
            document.querySelectorAll('.estado-opcion').forEach(o => {
                o.classList.remove('activa', 'baja');
            });
            const valor = this.querySelector('input').value;
            this.classList.add(valor == 1 ? 'activa' : 'baja');
        });
    });
</script>