<!-- views/cliente/funciones.php -->
<div class="cartelera-container">

    <div class="cartelera-header">
        <h1><i class="fa-solid fa-film"></i> Cartelera</h1>
        <p>Seleccioná una película y elegí tu función</p>
    </div>

    <!-- FILTRO POR DÍA -->
    <div class="filtros-dias">
        <button class="btn-dia activo" onclick="filtrarDia('todos', this)">
            <span>TODOS</span>
            <small id="count-todos"><?php echo array_sum(array_map(fn($p) => count($p['funciones']), $peliculas)); ?></small>
        </button>
        <?php
        $dias = [
            'lunes' => 'LUNES',
            'martes' => 'MARTES',
            'miercoles' => 'MIÉRCOLES',
            'jueves' => 'JUEVES',
            'viernes' => 'VIERNES',
            'sabado' => 'SÁBADO',
            'domingo' => 'DOMINGO'
        ];
        foreach ($dias as $clave => $nombre):
            $total = count($funcionesPorDia[$clave] ?? []);
        ?>
            <button class="btn-dia <?php echo $total === 0 ? 'deshabilitado' : ''; ?>"
                onclick="filtrarDia('<?php echo $clave; ?>', this)"
                <?php echo $total === 0 ? 'disabled' : ''; ?>>
                <span><?php echo $nombre; ?></span>
                <small><?php echo $total; ?></small>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- TARJETAS DE PELÍCULAS -->
    <?php if (empty($peliculas)): ?>
        <div class="sin-funciones">
            <i class="fa-solid fa-ticket"></i>
            <h2>No hay funciones disponibles</h2>
            <p>Próximamente nuevas películas</p>
        </div>
    <?php else: ?>
        <div class="peliculas-cartelera" id="peliculas-cartelera">
            <?php foreach ($peliculas as $pelicula): ?>
                <div class="pelicula-cartelera-card"
                    data-funciones='<?php echo json_encode(array_map(fn($f) => $f["dia_semana"], $pelicula["funciones"])); ?>'>

                    <!-- Poster clickeable -->
                    <div class="pelicula-cartelera-poster" onclick="toggleFuncionesCliente(this)">
                        <?php if ($pelicula['imagen_pelicula']): ?>
                            <img src="/assets/img/peliculas/<?php echo $pelicula['imagen_pelicula']; ?>"
                                alt="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>">
                        <?php else: ?>
                            <div class="poster-placeholder"><i class="fa-solid fa-film"></i></div>
                        <?php endif; ?>
                        <span class="badge-estado <?php echo $pelicula['nombre_estado_pelicula'] === 'Emision' ? 'emision' : 'proximamente'; ?>">
                            <?php echo $pelicula['nombre_estado_pelicula'] === 'Emision' ? '▶ En cartelera' : '⏳ Próximamente'; ?>
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="pelicula-cartelera-info">
                        <div class="pelicula-cartelera-header"
                            onclick="toggleFuncionesCliente(this.closest('.pelicula-cartelera-card').querySelector('.pelicula-cartelera-poster'))"
                            style="cursor:pointer;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <h2><?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?></h2>
                                <span class="toggle-icon-cliente">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </span>
                            </div>
                            <div class="pelicula-meta">
                                <?php if ($pelicula['nombre_tipo_clasificacion']): ?>
                                    <span class="meta-badge clasificacion"><?php echo $pelicula['nombre_tipo_clasificacion']; ?></span>
                                <?php endif; ?>
                                <?php if ($pelicula['duracion_pelicula']): ?>
                                    <span class="meta-badge"><i class="fa-solid fa-clock"></i> <?php echo $pelicula['duracion_pelicula']; ?> min</span>
                                <?php endif; ?>
                                <span class="meta-badge">
                                    <i class="fa-solid fa-ticket"></i> <?php echo count($pelicula['funciones']); ?> función(es)
                                </span>
                            </div>
                            <?php if ($pelicula['sinopsis_pelicula']):
                                $sinopsisCompleta = trim($pelicula['sinopsis_pelicula']);
                                $esLarga = mb_strlen($sinopsisCompleta) > 140;
                            ?>
                                <p class="sinopsis"><?php echo s($sinopsisCompleta); ?></p>
                                <?php if ($esLarga): ?>
                                    <button type="button" class="btn-ver-sinopsis" onclick="toggleSinopsisTexto(event, this)">Ver más</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Funciones COLAPSADAS por defecto -->
                        <div class="funciones-desplegables" style="display:none;">
                            <div class="funciones-disponibles">
                                <h4><i class="fa-solid fa-calendar-days"></i> Funciones disponibles</h4>
                                <div class="funciones-lista">
                                    <?php foreach ($pelicula['funciones'] as $funcion): ?>
                                        <div class="funcion-item"
                                            data-dia="<?php echo $funcion['dia_semana']; ?>"
                                            onclick="verButacasFuncion(<?php echo $funcion['id_funcion']; ?>, '<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>')">
                                            <div class="funcion-item-fecha">
                                                <span class="funcion-fecha-dia"><?php echo date('d', strtotime($funcion['fecha_hora'])); ?></span>
                                                <span class="funcion-fecha-mes"><?php echo mesCorto(strtotime($funcion['fecha_hora'])); ?></span>
                                            </div>
                                            <div class="funcion-item-info">
                                                <span class="funcion-hora">
                                                    <i class="fa-solid fa-clock"></i> <?php echo $funcion['turno_horario']; ?>
                                                </span>
                                                <span class="funcion-sala">
                                                    <i class="fa-solid fa-couch"></i> Sala <?php echo $funcion['id_sala']; ?>
                                                </span>
                                                <?php if ($funcion['idioma']): ?>
                                                    <span class="funcion-idioma-badge"><?php echo $funcion['idioma']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($funcion['tipo_entrada_desc']): ?>
                                                    <span class="funcion-tipo-badge"><?php echo $funcion['tipo_entrada_desc']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="funcion-item-precio">
                                                $<?php echo number_format($funcion['precio_entrada'] ?? 0, 0, ',', '.'); ?>
                                            </div>
                                            <div class="funcion-item-accion">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($pelicula['trailer_url']): ?>
                                <button class="btn-trailer"
                                    onclick="verTrailer('<?php echo htmlspecialchars($pelicula['trailer_url']); ?>', '<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>')">
                                    <i class="fa-solid fa-play"></i> Ver Tráiler
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .cartelera-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem;
    }

    .cartelera-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .cartelera-header h1 {
        font-size: 2rem;
        margin: 0 0 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        color: #ed850f;
    }

    .cartelera-header p {
        color: rgba(255, 255, 255, 0.6);
        margin: 0;
    }

    /* ===== FILTROS DÍA ===== */
    .filtros-dias {
        display: flex;
        gap: 8px;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-dia {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 18px;
        background: #2d3748;
        border: 2px solid #4a5568;
        border-radius: 10px;
        color: #a0aec0;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 80px;
    }

    .btn-dia span {
        font-weight: bold;
        font-size: 12px !important;
    }

    .btn-dia small {
        font-size: 18px !important;
        font-weight: bold;
        color: #ed850f;
    }

    .btn-dia:hover:not(:disabled) {
        border-color: #ed850f;
        color: #fff;
    }

    .btn-dia.activo {
        background: #ed850f;
        border-color: #ed850f;
        color: #fff;
    }

    .btn-dia.activo small {
        color: #fff;
    }

    .btn-dia.deshabilitado {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* ===== TARJETAS ===== */
    .peliculas-cartelera {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .pelicula-cartelera-card {
        display: grid;
        grid-template-columns: 180px 1fr;
        background: #2d3748;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #4a5568;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .pelicula-cartelera-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        border-color: #ed850f;
    }

    /* ===== POSTER ===== */
    .pelicula-cartelera-poster {
        position: relative;
        overflow: hidden;
        cursor: pointer;
        min-height: 200px;
    }

    .pelicula-cartelera-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.3s ease;
    }

    .pelicula-cartelera-poster:hover img {
        transform: scale(1.05);
    }

    .poster-placeholder {
        width: 100%;
        height: 100%;
        min-height: 200px;
        background: #1a202c;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        font-size: 3rem !important;
    }

    .badge-estado {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px !important;
        font-weight: bold;
    }

    .badge-estado.emision {
        background: rgba(34, 197, 94, 0.9);
        color: #fff;
    }

    .badge-estado.proximamente {
        background: rgba(245, 158, 11, 0.9);
        color: #fff;
    }

    /* ===== INFO ===== */
    .pelicula-cartelera-info {
        padding: 1.25rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .pelicula-cartelera-header h2 {
        color: #fff;
        margin: 0 0 6px;
        font-size: 1.25rem;
    }

    .toggle-icon-cliente {
        color: #ed850f;
        font-size: 1rem !important;
        transition: transform 0.3s ease;
        flex-shrink: 0;
        margin-left: 10px;
    }

    .toggle-icon-cliente.abierto {
        transform: rotate(180deg);
    }

    .pelicula-meta {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }

    .meta-badge {
        background: #1a202c;
        color: #a0aec0;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 11px !important;
        border: 1px solid #4a5568;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .meta-badge.clasificacion {
        border-color: #ed850f;
        color: #ed850f;
    }

    .sinopsis {
        color: rgba(255, 255, 255, 0.55);
        font-size: 12px !important;
        line-height: 1.5;
        margin: 0;
    }

    .sinopsis.clamped {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .btn-ver-sinopsis {
        display: none;
        background: none;
        border: none;
        color: #ed850f;
        font-size: 11px !important;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        margin-top: 2px;
    }

    .btn-ver-sinopsis:hover {
        text-decoration: underline;
    }

    /* ===== FUNCIONES DESPLEGABLES ===== */
    .funciones-desplegables {
        border-top: 1px solid #4a5568;
        padding-top: 1rem;
        margin-top: 0.25rem;
        animation: slideDown 0.25s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .funciones-disponibles h4 {
        color: #ed850f;
        margin: 0 0 10px;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 6px;
        padding-bottom: 8px;
        border-bottom: 1px solid #4a5568;
    }

    .funciones-lista {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 1rem;
    }

    /* ===== FUNCION ITEM ===== */
    .funcion-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        background: #1a202c;
        border-radius: 10px;
        border: 1px solid #4a5568;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .funcion-item:hover {
        border-color: #ed850f;
        background: #1e2a3a;
        box-shadow: 0 0 10px rgba(237, 133, 15, 0.2);
    }

    .funcion-item-fecha {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 38px;
        background: #ed850f;
        border-radius: 8px;
        padding: 5px 7px;
        flex-shrink: 0;
    }

    .funcion-fecha-dia {
        font-size: 1.1rem !important;
        font-weight: bold;
        color: #fff;
        line-height: 1;
    }

    .funcion-fecha-mes {
        font-size: 9px !important;
        color: rgba(255, 255, 255, 0.85);
        text-transform: uppercase;
        font-weight: bold;
    }

    .funcion-item-info {
        flex: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .funcion-hora,
    .funcion-sala {
        color: #cbd5e0;
        font-size: 12px !important;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .funcion-hora i,
    .funcion-sala i {
        color: #ed850f;
    }

    .funcion-idioma-badge,
    .funcion-tipo-badge {
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 10px !important;
        font-weight: bold;
    }

    .funcion-idioma-badge {
        background: rgba(59, 130, 246, 0.2);
        color: #93c5fd;
        border: 1px solid #3b82f6;
    }

    .funcion-tipo-badge {
        background: rgba(237, 133, 15, 0.2);
        color: #ed850f;
        border: 1px solid #ed850f;
    }

    .funcion-item-precio {
        font-weight: bold;
        color: #22c55e;
        font-size: 0.95rem;
        white-space: nowrap;
    }

    .funcion-item-accion {
        color: #4a5568;
        transition: all 0.2s;
    }

    .funcion-item:hover .funcion-item-accion {
        color: #ed850f;
    }

    /* ===== TRAILER ===== */
    .btn-trailer {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        background: transparent;
        border: 2px solid #ed850f;
        border-radius: 8px;
        color: #ed850f;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px !important;
        transition: all 0.2s;
        margin-top: 0.5rem;
    }

    .btn-trailer:hover {
        background: #ed850f;
        color: #fff;
    }

    /* ===== SIN FUNCIONES ===== */
    .sin-funciones {
        text-align: center;
        padding: 60px 20px;
        color: rgba(255, 255, 255, 0.5);
    }

    .sin-funciones i {
        font-size: 3rem !important;
        margin-bottom: 1rem;
        display: block;
        color: #4a5568;
    }

    .sin-funciones h2 {
        color: #fff;
        margin-bottom: 0.5rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .cartelera-container {
            padding: 1rem;
        }

        .pelicula-cartelera-card {
            grid-template-columns: 1fr;
        }

        .pelicula-cartelera-poster {
            max-height: 220px;
        }

        .pelicula-cartelera-poster img {
            height: 220px;
        }

        .funcion-item {
            flex-wrap: wrap;
        }

        .filtros-dias {
            gap: 5px;
        }

        .btn-dia {
            min-width: 65px;
            padding: 8px 10px;
        }
    }
</style>

<script>
    const MAPA_DIAS = {
        'lunes': 2,
        'martes': 3,
        'miercoles': 4,
        'jueves': 5,
        'viernes': 6,
        'sabado': 7,
        'domingo': 1
    };

    // ===== TOGGLE ACORDEÓN =====
    function toggleFuncionesCliente(posterEl) {
        const card = posterEl.closest('.pelicula-cartelera-card');
        const desplegable = card.querySelector('.funciones-desplegables');
        const toggleIcon = card.querySelector('.toggle-icon-cliente');
        const sinopsis = card.querySelector('.sinopsis');
        const btnVerSinopsis = card.querySelector('.btn-ver-sinopsis');

        const estaAbierto = desplegable.style.display !== 'none';
        desplegable.style.display = estaAbierto ? 'none' : 'block';
        toggleIcon.classList.toggle('abierto', !estaAbierto);

        // Al mostrar los horarios se prioriza el espacio para la lista: se acorta la sinopsis.
        // Al ocultarlos, la sinopsis vuelve a verse completa.
        if (sinopsis) {
            sinopsis.classList.toggle('clamped', !estaAbierto);

            if (btnVerSinopsis) {
                btnVerSinopsis.style.display = estaAbierto ? 'none' : 'inline-block';
                btnVerSinopsis.textContent = 'Ver más';
            }
        }
    }

    // ===== VER MÁS / VER MENOS (SINOPSIS) =====
    function toggleSinopsisTexto(event, btn) {
        event.stopPropagation();
        const p = btn.previousElementSibling;
        const clamped = p.classList.toggle('clamped');
        btn.textContent = clamped ? 'Ver más' : 'Ver menos';
    }

    // ===== FILTRAR POR DÍA =====
    function filtrarDia(dia, btn) {
        document.querySelectorAll('.btn-dia').forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');

        const tarjetas = document.querySelectorAll('.pelicula-cartelera-card');

        tarjetas.forEach(tarjeta => {
            const items = tarjeta.querySelectorAll('.funcion-item');
            let algunVisible = false;

            items.forEach(item => {
                if (dia === 'todos') {
                    item.style.display = 'flex';
                    algunVisible = true;
                } else {
                    const diaNumero = MAPA_DIAS[dia];
                    const itemDia = parseInt(item.dataset.dia);
                    if (itemDia === diaNumero) {
                        item.style.display = 'flex';
                        algunVisible = true;
                    } else {
                        item.style.display = 'none';
                    }
                }
            });

            tarjeta.style.display = algunVisible ? 'grid' : 'none';

            // Si hay filtro activo y la tarjeta es visible, abrirla automáticamente
            if (dia !== 'todos' && algunVisible) {
                const desplegable = tarjeta.querySelector('.funciones-desplegables');
                const toggleIcon = tarjeta.querySelector('.toggle-icon-cliente');
                desplegable.style.display = 'block';
                toggleIcon.classList.add('abierto');
            }
        });
    }

    // ===== NAVEGAR A BUTACAS =====
    function verButacasFuncion(idFuncion, titulo) {
        window.location.href = `/butacas?id_funcion=${idFuncion}`;
    }

    // ===== VER TRÁILER =====
    function verTrailer(url, titulo) {
        const modal = document.getElementById('trailerModal');
        if (modal) {
            const iframe = document.getElementById('trailerFrame');
            if (iframe) iframe.src = url;
            modal.style.display = 'flex';
        } else {
            window.open(url, '_blank');
        }
    }
</script>