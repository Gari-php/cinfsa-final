<!-- views/administrador/butacas/mapa-sala.php -->

<body>
    <div class="mapa-admin-container">
        <div class="header-admin">
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
            <h1>Gestión de Butacas - Sala <?php echo $sala['id_sala']; ?></h1>
            <div class="info-sala">
                <span>Capacidad: <?php echo $sala['capacidad_sala']; ?> personas</span>
                <span>|</span>
                <span><?php echo $sala['filas_sala']; ?> filas × <?php echo $sala['columnas_sala']; ?> columnas</span>
            </div>
        </div>

        <nav class="nav-admin">
            <a href="/administrador/butacas/gestion" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Salas
            </a>
        </nav>

        <!-- SELECTOR DE FUNCIÓN (solo si hay funciones activas para esta sala) -->
        <?php if (!empty($funciones)): ?>
            <div class="selector-funcion-admin">
                <label for="selectFuncion"><i class="fas fa-clock"></i> Ver ocupación por función:</label>
                <select id="selectFuncion" onchange="cambiarFuncion(this.value)">
                    <option value="">Vista general (mantenimiento de butacas)</option>
                    <?php foreach ($funciones as $funcion): ?>
                        <option value="<?php echo (int) $funcion['id_funcion']; ?>">
                            <?php echo s(date('d/m/Y', strtotime($funcion['fecha_hora']))); ?> -
                            <?php echo s(substr($funcion['turno_horario'], 0, 5)); ?> hs -
                            <?php echo s($funcion['titulo_pelicula']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- LEYENDA -->
        <div class="leyenda-admin">
            <div class="leyenda-item">
                <i class="fa-solid fa-chair disponible-icon"></i>
                <span>Disponible (click para bloquear)</span>
            </div>
            <div class="leyenda-item">
                <i class="fa-solid fa-chair bloqueada-icon"></i>
                <span>Bloqueada (click para habilitar)</span>
            </div>
            <div class="leyenda-item">
                <i class="fa-solid fa-chair reservada-icon"></i>
                <span>Vendida (no modificable)</span>
            </div>
            <div class="leyenda-item">
                <i class="fa-solid fa-chair en-carrito-icon"></i>
                <span>En carrito de un cliente (no modificable)</span>
            </div>
        </div>

        <!-- PANTALLA -->
        <div class="pantalla-admin">
            <div class="pantalla-texto">PANTALLA</div>
        </div>

        <!-- MAPA DE BUTACAS -->
        <div id="mapa-butacas-admin" class="mapa-butacas-admin">
            <?php
            $layout = $layout;
            $filas = $layout['sala']['filas'];
            $columnas = $layout['sala']['columnas'];

            // Crear matriz organizada
            $matrizButacas = [];
            foreach ($layout['butacas'] as $butaca) {
                $matrizButacas[$butaca['fila']][$butaca['numero']] = $butaca;
            }
            ?>

            <div class="sala-grid-admin">
                <?php for ($fila = 1; $fila <= $filas; $fila++): ?>
                    <div class="fila-admin" data-fila="<?php echo $fila; ?>">
                        <div class="fila-numero-admin">Fila <?php echo $fila; ?></div>

                        <?php for ($numero = 1; $numero <= $columnas; $numero++): ?>
                            <?php if (isset($matrizButacas[$fila][$numero])):
                                $butaca = $matrizButacas[$fila][$numero];
                                $claseEstado = '';
                                $clickeable = true;

                                switch ($butaca['estado']) {
                                    case 1:
                                        $claseEstado = 'disponible';
                                        break;
                                    case 2:
                                        $claseEstado = 'bloqueada';
                                        break;
                                    case 3:
                                        $claseEstado = 'reservada';
                                        $clickeable = false;
                                        break;
                                }
                            ?>
                                <div class="butaca-admin <?php echo $claseEstado; ?>"
                                    data-id="<?php echo $butaca['id']; ?>"
                                    data-fila="<?php echo $butaca['fila']; ?>"
                                    data-numero="<?php echo $butaca['numero']; ?>"
                                    data-estado="<?php echo $butaca['estado']; ?>"
                                    <?php echo $clickeable ? 'onclick="cambiarEstadoButaca(this)"' : ''; ?>>
                                    <i class="fa-solid fa-chair"></i>
                                    <span class="numero-butaca"><?php echo $butaca['numero']; ?></span>
                                </div>
                            <?php else: ?>
                                <div class="butaca-vacia-admin"></div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- ESTADÍSTICAS -->
        <div class="estadisticas-admin">
            <div class="stat-item">
                <div class="stat-numero" id="disponibles">0</div>
                <div class="stat-label">Disponibles</div>
            </div>
            <div class="stat-item">
                <div class="stat-numero" id="bloqueadas">0</div>
                <div class="stat-label">Bloqueadas</div>
            </div>
            <div class="stat-item">
                <div class="stat-numero" id="reservadas">0</div>
                <div class="stat-label">Vendidas</div>
            </div>
            <div class="stat-item">
                <div class="stat-numero" id="enCarrito">0</div>
                <div class="stat-label">En carrito</div>
            </div>
        </div>
    </div>



    <style>
        .mapa-admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            background-color: #363130;
            min-height: 100vh;
        }

        .header-admin {
            text-align: center;
            margin-bottom: 2rem;
            background-color: #363130;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid #ed850f;
        }

        .header-admin h1 {
            color: #ed850f;
            font-size: 2.5rem;
            margin: 1rem 0;
        }

        .form-logo {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }

        .info-sala {
            color: #fff;
            font-size: 1.1rem;
        }

        .nav-admin {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: center;
        }

        .btn-volver,
        .btn-accion {
            background: #ed850f;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-volver:hover,
        .btn-accion:hover {
            background: #d67607;
            transform: translateY(-2px);
        }

        .selector-funcion-admin {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            background-color: #363130;
            padding: 1.2rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 2px solid #ed850f;
        }

        .selector-funcion-admin label {
            color: #ed850f;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .selector-funcion-admin select {
            background: #1a202c;
            color: #fff;
            border: 1px solid #4a5568;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            min-width: 280px;
            max-width: 100%;
        }

        .selector-funcion-admin select:focus {
            outline: none;
            border-color: #ed850f;
        }

        .leyenda-admin {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            background-color: #363130;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 2px solid #ed850f;
        }

        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
            color: white;
        }

        .disponible-icon {
            color: #28a745 !important;
            font-size: 25px;
        }

        .bloqueada-icon {
            color: #dc3545 !important;
            font-size: 25px;
        }

        .reservada-icon {
            color: #ffc107 !important;
            font-size: 25px;
        }

        .en-carrito-icon {
            color: #3b82f6 !important;
            font-size: 25px;
        }

        .pantalla-admin {
            text-align: center;
            margin-bottom: 2rem;
        }

        .pantalla-texto {
            background: linear-gradient(135deg, #4a5568, #2d3748);
            color: #fff;
            padding: 1rem 4rem;
            border-radius: 50px;
            display: inline-block;
            font-weight: bold;
            font-size: 1.3rem;
            border: 3px solid #ed850f;
            box-shadow: 0 4px 15px rgba(237, 133, 15, 0.3);
        }

        .mapa-butacas-admin {
            background-color: #363130;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow-x: auto;
            border: 2px solid #ed850f;
        }

        .sala-grid-admin {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            align-items: center;
            /* Toma el ancho de la fila más larga: centrada si entra, desplazable completa si no */
            width: max-content;
            min-width: 100%;
        }

        .fila-admin {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fila-numero-admin {
            font-weight: bold;
            color: #ed850f;
            min-width: 80px;
            text-align: center;
            font-size: 0.9rem;
        }

        .butaca-admin {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
            position: relative;
        }

        .butaca-admin i {
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .numero-butaca {
            font-size: 10px;
            font-weight: bold;
            margin-top: 2px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        .butaca-admin.disponible i {
            color: #28a745;
        }

        .butaca-admin.disponible:hover i {
            color: #dc3545;
            transform: scale(1.2);
        }

        .butaca-admin.bloqueada i {
            color: #dc3545;
        }

        .butaca-admin.bloqueada:hover i {
            color: #28a745;
            transform: scale(1.2);
        }

        .butaca-admin.reservada i {
            color: #ffc107;
            opacity: 0.8;
        }

        .butaca-admin.reservada {
            cursor: not-allowed;
        }

        .butaca-admin.en-carrito i {
            color: #3b82f6;
            opacity: 0.8;
        }

        .butaca-admin.en-carrito {
            cursor: not-allowed;
        }

        .butaca-vacia-admin {
            width: 50px;
            height: 50px;
        }

        /* Estadísticas */
        .estadisticas-admin {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            background-color: #363130;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid #ed850f;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: rgba(237, 133, 15, 0.1);
            border: 1px solid #ed850f;
        }

        .stat-numero {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-item:nth-child(1) .stat-numero {
            color: #28a745;
        }

        .stat-item:nth-child(2) .stat-numero {
            color: #dc3545;
        }

        .stat-item:nth-child(3) .stat-numero {
            color: #ffc107;
        }

        .stat-item:nth-child(4) .stat-numero {
            color: #3b82f6;
        }

        .stat-label {
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .modal-confirmacion-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        .modal-confirmacion-box {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border: 2px solid #ed850f;
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .modal-confirmacion-box p {
            color: #e2e8f0;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-confirmacion-botones {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn-confirmar-modal,
        .btn-cancelar-modal {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-confirmar-modal {
            background: linear-gradient(135deg, #ed850f, #f7931e);
            color: white;
        }

        .btn-confirmar-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(237, 133, 15, 0.4);
        }

        .btn-cancelar-modal {
            background: transparent;
            color: #e2e8f0;
            border: 2px solid #4a5568;
        }

        .btn-cancelar-modal:hover {
            border-color: #ef4444;
            color: #ef4444;
        }

        @media (max-width: 480px) {
            .modal-confirmacion-box {
                padding: 22px 18px;
            }

            .modal-confirmacion-botones {
                flex-direction: column;
            }

            .btn-confirmar-modal,
            .btn-cancelar-modal {
                width: 100%;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mapa-admin-container {
                padding: 1rem;
            }

            .header-admin {
                padding: 1rem;
            }

            .header-admin h1 {
                font-size: 1.8rem;
            }

            .leyenda-admin {
                flex-direction: column;
                gap: 1rem;
            }

            .selector-funcion-admin {
                flex-direction: column;
            }

            .selector-funcion-admin select {
                width: 100%;
                min-width: 0;
            }

            .nav-admin {
                flex-direction: column;
                align-items: center;
            }

            .fila-numero-admin {
                min-width: 50px;
                font-size: 0.8rem;
            }

            .butaca-admin,
            .butaca-vacia-admin {
                width: 30px;
                height: 30px;
            }

            .butaca-admin i {
                font-size: 18px;
            }

            .numero-butaca {
                font-size: 8px;
            }

            .estadisticas-admin {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: left;
            }

            .stat-numero {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {

            .butaca-admin,
            .butaca-vacia-admin {
                width: 25px;
                height: 25px;
            }

            .butaca-admin i {
                font-size: 14px;
            }

            .numero-butaca {
                font-size: 7px;
            }

            .fila-admin {
                gap: 0.3rem;
            }

            .sala-grid-admin {
                gap: 0.5rem;
            }
        }
    </style>
</body>

<script type="module" src="/assets/js/formularios.js"></script>
<script>
    window.LAYOUT_GENERAL = <?php echo json_encode($layout, JSON_HEX_TAG | JSON_HEX_AMP); ?>;

    // Cambia el mapa entre la vista general (mantenimiento) y la ocupación de una función puntual
    async function cambiarFuncion(idFuncion) {
        const select = document.getElementById('selectFuncion');
        select.disabled = true;

        try {
            if (!idFuncion) {
                renderizarMapaAdmin(window.LAYOUT_GENERAL);
                return;
            }

            const response = await fetch(`/administrador/butacas/layout-funcion?id_funcion=${idFuncion}`);
            const data = await response.json();

            if (data.ok) {
                renderizarMapaAdmin(data.layout);
            } else {
                mostrarAlerta(data.mensaje, 'error');
            }
        } catch (error) {
            mostrarAlerta('Error al cargar la ocupación de la función', 'error');
        } finally {
            select.disabled = false;
        }
    }

    // Reconstruye la grilla de butacas a partir de un layout (general o de una función)
    function renderizarMapaAdmin(layout) {
        const filas = layout.sala.filas;
        const columnas = layout.sala.columnas;

        const matriz = {};
        layout.butacas.forEach(butaca => {
            if (!matriz[butaca.fila]) matriz[butaca.fila] = {};
            matriz[butaca.fila][butaca.numero] = butaca;
        });

        let html = '';
        for (let fila = 1; fila <= filas; fila++) {
            html += `<div class="fila-admin" data-fila="${fila}"><div class="fila-numero-admin">Fila ${fila}</div>`;

            for (let numero = 1; numero <= columnas; numero++) {
                const butaca = matriz[fila] && matriz[fila][numero];

                if (butaca) {
                    let claseEstado = '';
                    let clickeable = true;

                    if (butaca.estado === 1) {
                        claseEstado = 'disponible';
                    } else if (butaca.estado === 2) {
                        claseEstado = 'bloqueada';
                    } else if (butaca.estado === 3) {
                        claseEstado = 'reservada';
                        clickeable = false;
                    } else if (butaca.estado === 4) {
                        claseEstado = 'en-carrito';
                        clickeable = false;
                    }

                    html += `<div class="butaca-admin ${claseEstado}"
                                data-id="${butaca.id}"
                                data-fila="${butaca.fila}"
                                data-numero="${butaca.numero}"
                                data-estado="${butaca.estado}"
                                ${clickeable ? 'onclick="cambiarEstadoButaca(this)"' : ''}>
                                <i class="fa-solid fa-chair"></i>
                                <span class="numero-butaca">${butaca.numero}</span>
                            </div>`;
                } else {
                    html += '<div class="butaca-vacia-admin"></div>';
                }
            }

            html += '</div>';
        }

        document.querySelector('.sala-grid-admin').innerHTML = html;
        actualizarEstadisticas();
    }

    // Función para cambiar estado de butaca
    async function cambiarEstadoButaca(elemento) {
        const id = elemento.getAttribute('data-id');
        const estadoActual = parseInt(elemento.getAttribute('data-estado'));
        const nuevoEstado = estadoActual === 1 ? 2 : 1; // Toggle entre disponible(1) y bloqueada(2)

        const confirmado = await confirmarAccionPersonalizada(
            `¿Cambiar butaca a ${nuevoEstado === 1 ? 'disponible' : 'bloqueada'}?`
        );

        if (!confirmado) {
            return;
        }

        try {
            const response = await fetch('/administrador/butacas/cambiar-estado', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_butaca: id,
                    estado: nuevoEstado
                })
            });

            const data = await response.json();

            if (data.ok) {
                // Actualizar visualmente
                elemento.classList.remove('disponible', 'bloqueada');
                elemento.classList.add(nuevoEstado === 1 ? 'disponible' : 'bloqueada');
                elemento.setAttribute('data-estado', nuevoEstado);

                // Actualizar estadísticas
                actualizarEstadisticas();

                // Mostrar mensaje
                mostrarAlerta(data.mensaje, 'exito');
            } else {
                mostrarAlerta(data.mensaje, 'error');
            }

        } catch (error) {
            mostrarAlerta('Error al cambiar estado de butaca', 'error');
        }
    }

    // Función para actualizar estadísticas
    function actualizarEstadisticas() {
        const disponibles = document.querySelectorAll('.butaca-admin.disponible').length;
        const bloqueadas = document.querySelectorAll('.butaca-admin.bloqueada').length;
        const reservadas = document.querySelectorAll('.butaca-admin.reservada').length;
        const enCarrito = document.querySelectorAll('.butaca-admin.en-carrito').length;

        document.getElementById('enCarrito').textContent = enCarrito;
        document.getElementById('disponibles').textContent = disponibles;
        document.getElementById('bloqueadas').textContent = bloqueadas;
        document.getElementById('reservadas').textContent = reservadas;
    }

    function confirmarAccionPersonalizada(mensaje) {
        return new Promise((resolve) => {
            let overlay = document.getElementById('modalConfirmacionOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'modalConfirmacionOverlay';
                overlay.className = 'modal-confirmacion-overlay';
                overlay.innerHTML = `
                <div class="modal-confirmacion-box">
                    <p id="modalConfirmacionMensaje"></p>
                    <div class="modal-confirmacion-botones">
                        <button id="modalConfirmacionAceptar" class="btn-confirmar-modal">Confirmar</button>
                        <button id="modalConfirmacionCancelar" class="btn-cancelar-modal">Cancelar</button>
                    </div>
                </div>
            `;
                document.body.appendChild(overlay);
            }

            document.getElementById('modalConfirmacionMensaje').textContent = mensaje;
            overlay.style.display = 'flex';

            const btnAceptar = document.getElementById('modalConfirmacionAceptar');
            const btnCancelar = document.getElementById('modalConfirmacionCancelar');

            function limpiar(resultado) {
                overlay.style.display = 'none';
                btnAceptar.removeEventListener('click', onAceptar);
                btnCancelar.removeEventListener('click', onCancelar);
                resolve(resultado);
            }

            function onAceptar() {
                limpiar(true);
            }

            function onCancelar() {
                limpiar(false);
            }

            btnAceptar.addEventListener('click', onAceptar);
            btnCancelar.addEventListener('click', onCancelar);
        });
    }

    // Inicializar estadísticas al cargar
    document.addEventListener('DOMContentLoaded', function() {
        actualizarEstadisticas();
    });
</script>