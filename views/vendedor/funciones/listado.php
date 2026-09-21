<!-- views/vendedor/funciones/listado.php -->
<div class="vendedor-funciones-container">
    <!-- Header del Vendedor -->
    <div class="vendedor-header">
        <div class="header-info">
            <a href="/vendedor/caja/estado" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Caja
            </a>
            <h1>VENTA DE ENTRADAS</h1>
            <p>Selecciona una función para vender entradas</p>
        </div>

        <div class="buscador-funcion">
            <input
                type="text"
                id="buscar-funcion"
                placeholder="Buscar funcion por el titulo de la pelicula"
                class="input-buscar">
            <button onclick="buscarFuncion()" class="btn-buscar">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <!-- FILTROS POR DÍA (igual que cliente) -->
    <div class="filtros-dias">
        <button class="btn-dia activo" onclick="filtrarPorDia('todos')">
            <span>TODOS</span>
            <small><?php echo count($funciones); ?></small>
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

        // Contar funciones por día
        $funcionesPorDia = [];
        foreach ($dias as $diaKey => $nombre) {
            $count = 0;
            foreach ($funciones as $f) {
                $diaSemana = strtolower(date('l', strtotime($f->fecha_hora)));
                $diasTraduccion = [
                    'monday' => 'lunes',
                    'tuesday' => 'martes',
                    'wednesday' => 'miercoles',
                    'thursday' => 'jueves',
                    'friday' => 'viernes',
                    'saturday' => 'sabado',
                    'sunday' => 'domingo'
                ];
                if (($diasTraduccion[$diaSemana] ?? '') === $diaKey) {
                    $count++;
                }
            }
            $funcionesPorDia[$diaKey] = $count;
        }

        foreach ($dias as $dia => $nombre):
            $totalDia = $funcionesPorDia[$dia] ?? 0;
        ?>
            <button class="btn-dia"
                onclick="filtrarPorDia('<?php echo $dia; ?>')"
                <?php echo $totalDia === 0 ? 'disabled' : ''; ?>>
                <span><?php echo $nombre; ?></span>
                <small><?php echo $totalDia; ?></small>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- LOADING -->
    <div id="loading" class="loading" style="display: none;">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Cargando funciones...</p>
    </div>

    <!-- LOADING -->
    <div id="loading" class="loading" style="display: none;">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Cargando funciones...</p>
    </div>

    <!-- GRID DE FUNCIONES (agrupadas por película) -->
    <div id="contenedor-funciones" class="funciones-grid">
        <?php if (empty($funciones)): ?>
            <div class="no-funciones">
                <i class="fas fa-film"></i>
                <h2>No hay funciones disponibles</h2>
                <p>Próximamente nuevas películas</p>
            </div>
        <?php else: ?>
            <?php
            $peliculasAgrupadas = [];
            foreach ($funciones as $funcion) {
                $key = $funcion->rela_peliculas ?? $funcion->titulo_pelicula;
                if (!isset($peliculasAgrupadas[$key])) {
                    $peliculasAgrupadas[$key] = [
                        'titulo_pelicula' => $funcion->titulo_pelicula,
                        'imagen_pelicula' => $funcion->imagen_pelicula,
                        'funciones' => []
                    ];
                }
                $peliculasAgrupadas[$key]['funciones'][] = $funcion;
            }
            ?>
            <?php foreach ($peliculasAgrupadas as $pelicula): ?>
                <div class="pelicula-card-vendedor">
                    <div class="pelicula-header-vendedor" onclick="toggleFuncionesVendedor(this)">
                        <?php if (!empty($pelicula['imagen_pelicula'])): ?>
                            <img src="/assets/img/peliculas/<?php echo $pelicula['imagen_pelicula']; ?>"
                                alt="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>"
                                class="poster-vendedor">
                        <?php else: ?>
                            <div class="poster-vendedor sin-poster-vendedor"><i class="fas fa-film"></i></div>
                        <?php endif; ?>

                        <div class="pelicula-info-vendedor">
                            <h3><?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?></h3>
                            <span class="badge-total-vendedor">
                                <?php echo count($pelicula['funciones']); ?> función(es)
                            </span>
                        </div>

                        <div class="toggle-icon-vendedor">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>

                    <div class="funciones-lista-vendedor" style="display:none;">
                        <?php foreach ($pelicula['funciones'] as $funcion): ?>
                            <div class="funcion-row-vendedor">
                                <div class="funcion-datos-vendedor">
                                    <span class="dato-badge-id">ID: <?php echo $funcion->id_funcion; ?></span>
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($funcion->fecha_hora)); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        <?php echo $funcion->turno_horario; ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-couch"></i>
                                        <?php echo $funcion->nombre_sala ?? 'Sala ' . $funcion->rela_salas; ?>
                                    </span>
                                    <?php if (!empty($funcion->tipo_entrada_desc)): ?>
                                        <span class="tipo-entrada-badge-vendedor">
                                            <?php echo $funcion->tipo_entrada_desc; ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="precio-vendedor-badge">
                                        $<?php echo number_format($funcion->precio_entrada ?? 2500, 0, ',', '.'); ?>
                                    </span>
                                </div>
                                <button class="btn-vender-entrada-row"
                                    onclick="seleccionarButacas(<?php echo $funcion->id_funcion; ?>, '<?php echo htmlspecialchars($funcion->titulo_pelicula, ENT_QUOTES); ?>')">
                                    <i class="fas fa-couch"></i> Vender
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .vendedor-funciones-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
        padding: 2rem;
    }

    .vendedor-header {
        background: linear-gradient(135deg, #ed850f 0%, #d67607 100%);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .header-info {
        text-align: center;
        color: #fff;
        position: relative;
    }

    .btn-volver {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-volver:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .header-info h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }

    .header-info p {
        margin: 0;
        opacity: 0.9;
    }

    .buscador-funcion {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .input-buscar {
        flex: 1;
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        background: rgba(255, 255, 255, 0.9);
    }

    .btn-buscar {
        padding: 1rem 1.5rem;
        background: #2d3748;
        color: #fff;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-buscar:hover {
        background: #1a202c;
    }

    .filtros-dias {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .btn-dia {
        background: #4a5568;
        color: #fff;
        border: none;
        padding: 0.8rem 1rem;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 80px;
    }

    .btn-dia:hover:not(:disabled) {
        background: #ed850f;
        transform: translateY(-2px);
    }

    .btn-dia.activo {
        background: #ed850f;
        transform: translateY(-2px);
    }

    .btn-dia:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .btn-dia span {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .btn-dia small {
        font-size: 0.7rem;
        opacity: 0.8;
        margin-top: 0.2rem;
    }

    .loading {
        text-align: center;
        padding: 3rem;
        color: #ed850f;
    }

    .loading i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    /* Contenedor: pila vertical de una sola columna (no grid) */
    .funciones-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .no-funciones {
        text-align: center;
        padding: 4rem 2rem;
        color: #a0aec0;
    }

    .no-funciones i {
        font-size: 5rem;
        color: #4a5568;
        margin-bottom: 1rem;
    }

    .no-funciones h2 {
        color: #ed850f;
        margin: 1rem 0 0.5rem 0;
    }

    /* Tarjetas de película agrupadas */
    .pelicula-card-vendedor {
        background: #2d3748;
        border-radius: 16px;
        border: 1px solid #4a5568;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
        transition: box-shadow 0.2s ease;
    }

    .pelicula-card-vendedor:hover {
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.45);
    }

    .pelicula-header-vendedor {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.5rem 1.75rem;
        background: #1a202c;
        border-bottom: 2px solid #ed850f;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .pelicula-header-vendedor:hover {
        background: #212936;
    }

    .poster-vendedor {
        width: 70px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
    }

    .sin-poster-vendedor {
        width: 70px;
        height: 100px;
        background: #2d3748;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a5568;
        font-size: 1.8rem;
    }

    .pelicula-info-vendedor {
        flex: 1;
    }

    .pelicula-info-vendedor h3 {
        color: #fff;
        margin: 0 0 8px 0;
        font-size: 1.3rem;
    }

    .badge-total-vendedor {
        background: rgba(237, 133, 15, 0.2);
        color: #ed850f;
        border: 1px solid #ed850f;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .toggle-icon-vendedor {
        color: #ed850f;
        font-size: 1.4rem;
        transition: transform 0.25s ease;
    }

    .funciones-lista-vendedor {
        padding: 0.5rem 0;
    }

    .funcion-row-vendedor {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.75rem;
        border-bottom: 1px solid #3a4556;
        flex-wrap: wrap;
        gap: 1rem;
        transition: background 0.15s ease;
    }

    .funcion-row-vendedor:last-child {
        border-bottom: none;
    }

    .funcion-row-vendedor:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .funcion-datos-vendedor {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
        color: #cbd5e0;
        font-size: 14px;
    }

    .funcion-datos-vendedor span {
        display: flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }

    .funcion-datos-vendedor i {
        color: #ed850f;
    }

    .dato-badge-id {
        background: rgba(0, 0, 0, 0.3);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        color: #a0aec0;
    }

    .tipo-entrada-badge-vendedor {
        background: #1a202c;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        color: #ed850f;
        border: 1px solid #4a5568;
    }

    .precio-vendedor-badge {
        font-weight: bold;
        color: #22c55e;
    }

    .btn-vender-entrada-row {
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: #fff;
        border: none;
        padding: 0.7rem 1.4rem;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .btn-vender-entrada-row:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.4);
    }

    @media (max-width: 768px) {
        .vendedor-funciones-container {
            padding: 1rem;
        }

        .vendedor-header {
            padding: 1.5rem;
        }

        .btn-volver {
            position: static;
            transform: none;
            margin-bottom: 1rem;
        }

        .header-info h1 {
            font-size: 1.5rem;
        }

        .buscador-funcion {
            flex-direction: column;
        }

        .filtros-dias {
            gap: 0.3rem;
        }

        .btn-dia {
            min-width: 70px;
            padding: 0.6rem 0.8rem;
        }

        .pelicula-header-vendedor {
            padding: 1.25rem;
            gap: 1rem;
        }

        .poster-vendedor,
        .sin-poster-vendedor {
            width: 55px;
            height: 80px;
        }

        .funcion-row-vendedor {
            flex-direction: column;
            align-items: flex-start;
            padding: 1rem 1.25rem;
        }

        .btn-vender-entrada-row {
            width: 100%;
            justify-content: center;
        }

        .funcion-datos-vendedor {
            gap: 0.75rem;
        }
    }
</style>

<script>
    let diaActual = 'todos';

    // Función para seleccionar butacas de una función
    function seleccionarButacas(idFuncion, titulo) {
        window.location.href = `/vendedor/funciones/butacas?id_funcion=${idFuncion}`;
    }

    // Filtrar por día (reutiliza lógica del cliente)
    async function filtrarPorDia(dia) {
        if (dia === diaActual) return;

        const btnDia = event.target.closest('.btn-dia');
        if (btnDia.disabled) return;

        // Mostrar loading
        document.getElementById('loading').style.display = 'block';
        document.getElementById('contenedor-funciones').style.opacity = '0.5';

        // Actualizar botones
        document.querySelectorAll('.btn-dia').forEach(btn => {
            btn.classList.remove('activo');
        });
        btnDia.classList.add('activo');

        try {
            if (dia === 'todos') {
                window.location.href = '/vendedor/funciones/listado';
                return;
            }

            // Hacer petición AJAX (reutiliza endpoint del cliente)
            const response = await fetch(`/api/funciones/por-dia?dia=${dia}`);
            const data = await response.json();

            if (data.ok) {
                actualizarFunciones(data.funciones, dia);
                diaActual = dia;
            } else {
                mostrarAlerta(data.mensaje, 'error');
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error al cargar funciones', 'error');
        } finally {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('contenedor-funciones').style.opacity = '1';
        }
    }

    function toggleFuncionesVendedor(header) {
        const lista = header.nextElementSibling;
        const icono = header.querySelector('.toggle-icon-vendedor');
        const estaAbierto = lista.style.display !== 'none';

        lista.style.display = estaAbierto ? 'none' : 'block';
        icono.style.transform = estaAbierto ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    function agruparPorPelicula(funciones) {
        const grupos = {};
        funciones.forEach(f => {
            const key = f.rela_peliculas ?? f.titulo_pelicula;
            if (!grupos[key]) {
                grupos[key] = {
                    titulo_pelicula: f.titulo_pelicula,
                    imagen_pelicula: f.imagen_pelicula,
                    funciones: []
                };
            }
            grupos[key].funciones.push(f);
        });
        return Object.values(grupos);
    }

    function crearTarjetaPelicula(pelicula) {
        const imagen = pelicula.imagen_pelicula ?
            `<img src="/assets/img/peliculas/${pelicula.imagen_pelicula}" alt="${pelicula.titulo_pelicula}" class="poster-vendedor">` :
            '<div class="poster-vendedor sin-poster-vendedor"><i class="fas fa-film"></i></div>';

        const filas = pelicula.funciones.map(funcion => {
            const tipoEntrada = funcion.tipo_entrada_desc ?
                `<span class="tipo-entrada-badge-vendedor">${funcion.tipo_entrada_desc}</span>` : '';
            const fecha = funcion.fecha_formato || new Date(funcion.fecha_hora).toLocaleDateString('es-AR');

            return `
            <div class="funcion-row-vendedor">
                <div class="funcion-datos-vendedor">
                    <span class="dato-badge-id">ID: ${funcion.id_funcion}</span>
                    <span><i class="fas fa-calendar"></i> ${fecha}</span>
                    <span><i class="fas fa-clock"></i> ${funcion.turno_horario}</span>
                    <span><i class="fas fa-couch"></i> ${funcion.nombre_sala || 'Sala ' + funcion.rela_salas}</span>
                    ${tipoEntrada}
                    <span class="precio-vendedor-badge">$${new Intl.NumberFormat('es-AR').format(funcion.precio_entrada || 2500)}</span>
                </div>
                <button class="btn-vender-entrada-row"
                        onclick="seleccionarButacas(${funcion.id_funcion}, '${funcion.titulo_pelicula.replace(/'/g, "\\'")}')">
                    <i class="fas fa-couch"></i> Vender
                </button>
            </div>
        `;
        }).join('');

        return `
        <div class="pelicula-card-vendedor">
            <div class="pelicula-header-vendedor" onclick="toggleFuncionesVendedor(this)">
                ${imagen}
                <div class="pelicula-info-vendedor">
                    <h3>${pelicula.titulo_pelicula}</h3>
                    <span class="badge-total-vendedor">${pelicula.funciones.length} función(es)</span>
                </div>
                <div class="toggle-icon-vendedor"><i class="fas fa-chevron-down"></i></div>
            </div>
            <div class="funciones-lista-vendedor" style="display:none;">
                ${filas}
            </div>
        </div>
    `;
    }

    // Actualizar contenido de funciones (ahora agrupadas)
    function actualizarFunciones(funciones, dia) {
        const contenedor = document.getElementById('contenedor-funciones');

        if (funciones.length === 0) {
            contenedor.innerHTML = `
            <div class="no-funciones">
                <i class="fas fa-film"></i>
                <h2>No hay funciones disponibles</h2>
                <p>No hay funciones para ${dia.charAt(0).toUpperCase() + dia.slice(1)}</p>
            </div>
        `;
            return;
        }

        const agrupadas = agruparPorPelicula(funciones);
        contenedor.innerHTML = agrupadas.map(crearTarjetaPelicula).join('');
    }

    // Buscar función específica
    async function buscarFuncion() {
        const termino = document.getElementById('buscar-funcion').value.trim();

        if (!termino) {
            mostrarAlerta('Ingresa un término de búsqueda', 'error');
            return;
        }

        document.getElementById('loading').style.display = 'block';

        try {
            const response = await fetch('/vendedor/funciones/buscar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    termino
                })
            });

            const data = await response.json();

            // DESPUÉS
            if (data.ok && data.funcion) {
                const contenedor = document.getElementById('contenedor-funciones');
                const agrupadas = agruparPorPelicula([data.funcion]);
                contenedor.innerHTML = agrupadas.map(crearTarjetaPelicula).join('');
            } else {
                mostrarAlerta(data.mensaje || 'Función no encontrada', 'error');
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error al buscar función', 'error');
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }

    // Permitir buscar con Enter
    document.getElementById('buscar-funcion')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarFuncion();
        }
    });

    function mostrarAlerta(mensaje, tipo) {
        // Crear alerta temporal
        const alerta = document.createElement('div');
        alerta.className = `alerta-temp ${tipo}`;
        alerta.innerHTML = `
        <i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
        <span>${mensaje}</span>
    `;

        alerta.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${tipo === 'error' ? '#fed7d7' : '#c6f6d5'};
        color: ${tipo === 'error' ? '#742a2a' : '#22543d'};
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;

        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => alerta.remove(), 300);
        }, 4000);
    }
</script>