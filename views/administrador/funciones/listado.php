<div class="listado">
    <nav class="nav-listado">
        <ul>
            <li><a href="/administrador/funciones/listado"><i class="fa-solid fa-arrow-left"></i> Volver</a></li>
            <li><a href="/administrador/funciones/crear"><i class="fa-solid fa-plus"></i> Agregar Funciones</a></li>
            <li><a href="/administrador/funciones/reportes"><i class="fa-solid fa-chart-line"></i> Reportes</a></li>
            <li class="filtros-exportacion">
                <div class="grupo-filtros-fecha">
                    <span><i class="fa-solid fa-calendar"></i> Filtrar por Fecha:</span>
                    <input type="date" id="fecha-desde-export"
                        value="<?php echo htmlspecialchars($fecha_desde ?? ''); ?>"
                        style="padding:6px 10px; border:1px solid #4a5568; border-radius:4px; background:#1a202c; color:#fff;">
                    <span style="color:#a0aec0;">hasta</span>
                    <input type="date" id="fecha-hasta-export"
                        value="<?php echo htmlspecialchars($fecha_hasta ?? ''); ?>"
                        style="padding:6px 10px; border:1px solid #4a5568; border-radius:4px; background:#1a202c; color:#fff;">
                    <button id="btn-buscar-fecha" type="button"
                        style="background:#ed850f; color:#fff; padding:6px 12px; border:none; border-radius:4px; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                    <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                        <a href="/administrador/funciones/listado"
                            style="background:#6c757d; color:#fff; padding:6px 12px; border-radius:4px; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                            <i class="fa-solid fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </li>
            <li class="buscador">
                <input type="text" id="buscador-peliculas" class="barra_buscador"
                    placeholder="Buscar película..."
                    oninput="filtrarPeliculas(this.value)">
                <button><i class="fa-solid fa-magnifying-glass"></i></button>
            </li>
        </ul>
    </nav>

    <?php if (empty($peliculasConFunciones)): ?>
        <div style="text-align:center; padding:60px; color:#a0aec0;">
            <i class="fa-solid fa-film" style="font-size:3rem; margin-bottom:1rem; display:block; color:#4a5568;"></i>
            <p>No hay funciones para mostrar</p>
        </div>
    <?php else: ?>
        <div class="funciones-grid">
            <?php foreach ($peliculasConFunciones as $pelicula): ?>
                <div class="pelicula-funcion-card">
                    <!-- Header de la tarjeta -->
                    <div class="card-pelicula-header" onclick="toggleFunciones(this)" style="cursor:pointer;">
                        <?php if ($pelicula['imagen_pelicula']): ?>
                            <img src="/assets/img/peliculas/<?php echo $pelicula['imagen_pelicula']; ?>"
                                alt="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>"
                                class="card-poster">
                        <?php else: ?>
                            <div class="card-poster sin-poster"><i class="fa-solid fa-film"></i></div>
                        <?php endif; ?>
                        <div class="card-pelicula-info">
                            <h3><?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?></h3>
                            <span class="badge-total">
                                <?php echo count($pelicula['funciones']); ?> función(es)
                            </span>
                        </div>
                        <div class="card-toggle-icon" style="margin-left:auto; color:#ed850f; font-size:1.2rem !important;">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>

                    <!-- Lista de funciones -->
                    <div class="card-funciones-lista" style="display:none;">
                        <?php foreach ($pelicula['funciones'] as $funcion): ?>
                            <div class="funcion-row">
                                <div class="funcion-datos">
                                    <span class="funcion-fecha">
                                        <i class="fa-solid fa-calendar-day"></i>
                                        <?php echo date('d/m/Y', strtotime($funcion['fecha_hora'])); ?>
                                    </span>
                                    <span class="funcion-hora">
                                        <i class="fa-solid fa-clock"></i>
                                        <?php echo $funcion['turno_horario']; ?>
                                    </span>
                                    <span class="funcion-sala">
                                        <i class="fa-solid fa-couch"></i>
                                        Sala <?php echo $funcion['id_sala']; ?>
                                    </span>
                                    <?php if ($funcion['idioma']): ?>
                                        <span class="funcion-idioma">
                                            <i class="fa-solid fa-language"></i>
                                            <?php echo $funcion['idioma']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="funcion-tipo">
                                        <?php echo $funcion['tipo_entrada_desc']; ?>
                                    </span>
                                    <span class="funcion-precio">
                                        $<?php echo number_format($funcion['precio_entrada'], 0, ',', '.'); ?>
                                    </span>
                                    <span class="funcion-estado estado-<?php echo $funcion['estado'] == 1 ? 'activa' : 'baja'; ?>">
                                        <?php echo $funcion['estado'] == 1 ? '✓ ACTIVA' : '✗ BAJA'; ?>
                                    </span>
                                </div>
                                <div class="funcion-acciones">
                                    <a href="/administrador/funciones/editar?id=<?php echo $funcion['id_funcion']; ?>"
                                        class="btn-funcion btn-editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <button class="btn-funcion btn-eliminar eliminar-funcion"
                                        data-id="<?php echo $funcion['id_funcion']; ?>"
                                        data-nombre="Función del <?php echo date('d/m/Y', strtotime($funcion['fecha_hora'])); ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .funciones-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        padding: 1rem 0;
    }

    .pelicula-funcion-card {
        background: #2d3748;
        border-radius: 16px;
        border: 1px solid #4a5568;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .card-pelicula-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        background: #1a202c;
        border-bottom: 2px solid #ed850f;
    }

    .card-poster {
        width: 55px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
    }

    .sin-poster {
        width: 55px;
        height: 80px;
        background: #2d3748;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        font-size: 1.5rem !important;
    }

    .card-pelicula-info h3 {
        color: #fff;
        margin: 0 0 6px 0;
        font-size: 1.1rem;
    }

    .badge-total {
        background: rgba(237, 133, 15, 0.2);
        color: #ed850f;
        border: 1px solid #ed850f;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px !important;
        font-weight: 600;
    }

    .card-funciones-lista {
        padding: 0.5rem 0;
    }

    .funcion-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid #3a4556;
        transition: background 0.15s ease;
    }

    .funcion-row:last-child {
        border-bottom: none;
    }

    .funcion-row:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .funcion-datos {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
        color: #cbd5e0;
        font-size: 13px !important;
    }

    .funcion-datos span {
        display: flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }

    .funcion-datos i {
        color: #ed850f;
    }

    .funcion-fecha {
        font-weight: 600;
        color: #fff;
    }

    .funcion-hora {
        color: #a0aec0;
    }

    .funcion-sala {
        color: #a0aec0;
    }

    .funcion-idioma {
        color: #a0aec0;
    }

    .funcion-tipo {
        background: #1a202c;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px !important;
        font-weight: bold;
        color: #ed850f;
        border: 1px solid #4a5568;
    }

    .funcion-precio {
        font-weight: bold;
        color: #22c55e;
    }

    .funcion-estado {
        font-size: 11px !important;
        font-weight: bold;
        padding: 3px 10px;
        border-radius: 20px;
    }

    .estado-activa {
        background: rgba(34, 197, 94, 0.15);
        color: #86efac;
        border: 1px solid #22c55e;
    }

    .estado-baja {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 1px solid #ef4444;
    }

    .funcion-acciones {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }

    .btn-funcion {
        width: 34px;
        height: 34px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 13px !important;
        border: none;
    }

    .btn-editar {
        background: rgba(237, 133, 15, 0.15);
        color: #ed850f;
        border: 1px solid #ed850f;
    }

    .btn-editar:hover {
        background: #ed850f;
        color: #fff;
    }

    .btn-eliminar {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 1px solid #ef4444;
    }

    .btn-eliminar:hover {
        background: #ef4444;
        color: #fff;
    }

    @media (max-width: 768px) {
        .funcion-datos {
            gap: 0.75rem;
        }

        .funcion-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
    }
</style>

<script>
    document.getElementById('btn-buscar-fecha')?.addEventListener('click', () => {
        const desde = document.getElementById('fecha-desde-export').value;
        const hasta = document.getElementById('fecha-hasta-export').value;
        const params = new URLSearchParams();
        if (desde) params.set('fecha_desde', desde);
        if (hasta) params.set('fecha_hasta', hasta);
        window.location.href = '/administrador/funciones/listado?' + params.toString();
    });

    function toggleFunciones(header) {
        const lista = header.nextElementSibling;
        const icono = header.querySelector('.fa-chevron-down, .fa-chevron-up');
        const estaAbierto = lista.style.display !== 'none';

        lista.style.display = estaAbierto ? 'none' : 'block';
        icono.className = estaAbierto ?
            'fa-solid fa-chevron-down' :
            'fa-solid fa-chevron-up';
    }
    
    // Función para filtrar películas por título                                    
    function filtrarPeliculas(termino) {
        const terminoLower = termino.toLowerCase().trim();
        const tarjetas = document.querySelectorAll('.pelicula-funcion-card');

        tarjetas.forEach(tarjeta => {
            const titulo = tarjeta.querySelector('.card-pelicula-info h3')
                ?.textContent.toLowerCase() ?? '';
            tarjeta.style.display = titulo.includes(terminoLower) ? 'block' : 'none';
        });
    }

    // La baja de una función (.eliminar-funcion) la maneja formularios.js con el modal de
    // confirmación. No agregar otro listener acá: mandaba la baja sin confirmar y, al
    // confirmar en el modal, se enviaba una segunda vez (quedaba duplicada en la auditoría).
</script>