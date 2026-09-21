<body>
    <div class="listado" data-modulo="movimientos-web">

        <div>
            <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>

        <h1><i class="fa-solid fa-globe"></i> Movimientos Web - Órdenes de Compra</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/movimientos-web/reportes"><i class="fa-solid fa-chart-line"></i> Ver Reportes</a></li>
                <li class="filtros-exportacion">
                    <div class="grupo-filtros-fecha">
                        <span><i class="fa-solid fa-calendar-plus"></i> Filtrar por Fecha de Creación:</span>
                        <input type="date"
                            id="fecha-desde-export"
                            placeholder="Desde"
                            value="<?php echo htmlspecialchars($fecha_desde ?? ''); ?>"
                            style="padding: 6px 10px; border: 1px solid #4a5568; border-radius: 4px; background: #1a202c; color: #fff;">
                        <span style="color: #a0aec0;">hasta</span>
                        <input type="date"
                            id="fecha-hasta-export"
                            placeholder="Hasta"
                            value="<?php echo htmlspecialchars($fecha_hasta ?? ''); ?>"
                            style="padding: 6px 10px; border: 1px solid #4a5568; border-radius: 4px; background: #1a202c; color: #fff;">
                        <button id="btn-buscar-fecha" type="button" class="btn-exportar" style="background: #ed850f;">
                            <i class="fa-solid fa-magnifying-glass"></i> Buscar
                        </button>
                        <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                            <a href="/administrador/movimientos-web/listado"
                                style="background: #6c757d; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-size: 14px;">
                                <i class="fa-solid fa-times"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </li>

                <li class="exportacion">
                    <div class="grupo-exportacion">
                        <span><i class="fa-solid fa-download"></i> Exportar:</span>
                        <button class="btn-exportar excel" data-tipo="excel" title="Exportar a Excel">
                            <i class="fa-solid fa-file-excel"></i> Excel
                        </button>
                        <button class="btn-exportar pdf" data-tipo="pdf" title="Exportar a PDF">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </li>
                <li class="buscador">
                    <input id="busqueda-orden" class="barra_buscador" type="text" placeholder="Buscar por num Orden">
                    <button id="btn-buscar-orden" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Filtros por estado -->
        <div class="filtros-estado">
            <h3><i class="fa-solid fa-filter"></i> Filtrar por Estado:</h3>
            <button class="filtro-estado active" data-estado="todos">
                <i class="fa-solid fa-list"></i> Todas
            </button>
            <button class="filtro-estado" data-estado="pagado">
                <i class="fa-solid fa-circle-check"></i> Pagadas
            </button>
            <button class="filtro-estado" data-estado="pendiente">
                <i class="fa-solid fa-clock"></i> Pendientes
            </button>
            <button class="filtro-estado" data-estado="cancelado">
                <i class="fa-solid fa-ban"></i> Canceladas
            </button>
            <button class="filtro-estado" data-estado="fallido">
                <i class="fa-solid fa-exclamation-triangle"></i> Fallidas
            </button>
        </div>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>N° Orden</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Items</th>
                    <th>Detalle</th>
                    <th>Método Pago</th>
                    <th>Total</th>
                    <th>Fecha Creación</th>
                    <th>Fecha Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-ordenes">
                <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px; color: #a0aec0;">
                            <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                            No hay órdenes registradas
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordenes as $orden): ?>
                        <tr data-estado="<?php echo $orden->estado; ?>">
                            <td><?php echo $orden->id_orden; ?></td>

                            <!-- N° ORDEN -->
                            <td>
                                <span style="font-family: monospace; font-size: 11px; color: #fff;">
                                    <?php echo $orden->numero_orden ?? 'ORD-' . str_pad($orden->id_orden, 6, '0', STR_PAD_LEFT); ?>
                                </span>
                            </td>

                            <!-- CLIENTE -->
                            <td style="color: #fff;">
                                <div style="font-weight: bold;"><?php echo $orden->nombre_usuario; ?></div>
                                <div style="font-size: 11px; color: #a0aec0;"><?php echo $orden->email; ?></div>
                            </td>

                            <!-- ESTADO -->
                            <td>
                                <?php
                                $estados = [
                                    'pagado' => ['texto' => 'Pagada', 'clase' => 'estado-pagada', 'icono' => 'fa-circle-check'],
                                    'pendiente' => ['texto' => 'Pendiente', 'clase' => 'estado-pendiente', 'icono' => 'fa-clock'],
                                    'cancelado' => ['texto' => 'Cancelada', 'clase' => 'estado-cancelada', 'icono' => 'fa-ban'],
                                    'fallido' => ['texto' => 'Fallida', 'clase' => 'estado-fallida', 'icono' => 'fa-exclamation-triangle']
                                ];
                                $estado_info = $estados[$orden->estado] ?? $estados['pendiente'];
                                ?>
                                <span class="estado-badge <?php echo $estado_info['clase']; ?>">
                                    <i class="fa-solid <?php echo $estado_info['icono']; ?>"></i>
                                    <?php echo $estado_info['texto']; ?>
                                </span>
                            </td>

                            <!-- TOTAL ITEMS -->
                            <td style="color: #fff; text-align: center;">
                                <strong><?php echo $orden->total_items; ?></strong> items
                            </td>

                            <!-- DETALLE PRODUCTOS -->
                            <td style="color: #fff; font-size: 11px;">
                                <?php if ($orden->butacas > 0): ?>
                                    <div><i class="fa-solid fa-ticket" style="color: #ed850f;"></i> <?php echo $orden->butacas; ?> butacas</div>
                                <?php endif; ?>
                                <?php if ($orden->cantina > 0): ?>
                                    <div><i class="fa-solid fa-utensils" style="color: #22c55e;"></i> <?php echo $orden->cantina; ?> productos</div>
                                <?php endif; ?>
                                <?php if ($orden->fichas > 0): ?>
                                    <div><i class="fa-solid fa-gamepad" style="color: #3b82f6;"></i> <?php echo $orden->fichas; ?> fichas</div>
                                <?php endif; ?>
                            </td>

                            <!-- MÉTODO PAGO -->
                            <td style="color: #fff;">
                                <?php
                                $metodo = $orden->metodo_pago ?? 'No especificado';
                                $iconMetodo = match ($metodo) {
                                    'credit_card' => 'fa-credit-card',
                                    'debit_card' => 'fa-credit-card',
                                    'account_money' => 'fa-wallet',
                                    default => 'fa-money-bill'
                                };
                                ?>
                                <i class="fa-solid <?php echo $iconMetodo; ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $metodo)); ?>
                            </td>

                            <!-- TOTAL -->
                            <td style="color: #fff; font-weight: bold; font-size: 14px;">
                                $<?php echo number_format($orden->total, 0, ',', '.'); ?>
                            </td>

                            <!-- FECHA CREACIÓN -->
                            <td style="color: #fff; font-size: 11px;">
                                <?php echo date('d/m/Y H:i', strtotime($orden->fecha_creacion)); ?>
                            </td>

                            <!-- FECHA PAGO -->
                            <td style="color: #fff; font-size: 11px;">
                                <?php
                                echo $orden->fecha_pago
                                    ? date('d/m/Y H:i', strtotime($orden->fecha_pago))
                                    : '<span style="color: #a0aec0;">-</span>';
                                ?>
                            </td>

                            <!-- ACCIONES -->
                            <td>
                                <div class="acciones">
                                    <a class="boton" href="/administrador/movimientos-web/detalle?id=<?php echo $orden->id_orden; ?>"
                                        style="background: #0891b2;" title="Ver detalles">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </a>

                                    <?php if ($orden->estado === 'pagado' || $orden->estado === 'pendiente'): ?>
                                        <button class="boton eliminar-entrada"
                                            data-id="<?php echo $orden->id_orden; ?>"
                                            data-nombre="Orden <?php echo $orden->numero_orden ?? '#' . $orden->id_orden; ?>"
                                            data-accion="cancelar"
                                            title="Cancelar orden">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($orden->payment_id): ?>
                                        <button class="boton"
                                            style="background: #6366f1;"
                                            onclick="copiarAlPortapapeles('<?php echo $orden->payment_id; ?>')"
                                            title="Copiar ID de pago MP">
                                            <i class="fa-solid fa-copy"></i> ID MP
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <style>
                    /* Mejoras puntuales SOLO para esta vista (Movimientos Web) */

                    [data-modulo="movimientos-web"] .tabla-listado td {
                        padding: 16px 14px;
                        vertical-align: middle;
                    }

                    [data-modulo="movimientos-web"] .tabla-listado tbody tr {
                        transition: background-color 0.15s ease;
                    }

                    [data-modulo="movimientos-web"] .tabla-listado tbody tr:hover {
                        background-color: rgba(237, 133, 15, 0.06);
                    }

                    /* Columna de Acciones: de fila apretada a columna respirada */
                    [data-modulo="movimientos-web"] .acciones {
                        display: flex;
                        flex-direction: column;
                        align-items: stretch;
                        gap: 8px;
                        min-width: 140px;
                    }

                    [data-modulo="movimientos-web"] .acciones .boton {
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        gap: 7px;
                        padding: 9px 14px !important;
                        border-radius: 8px !important;
                        font-size: 13px !important;
                        font-weight: 600;
                        line-height: 1.2;
                        color: #fff !important;
                        text-decoration: none;
                        border: none;
                        cursor: pointer;
                        white-space: nowrap;
                        transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
                    }

                    [data-modulo="movimientos-web"] .acciones .boton:hover {
                        transform: translateY(-2px);
                        filter: brightness(1.12);
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
                    }

                    [data-modulo="movimientos-web"] .acciones .boton:active {
                        transform: translateY(0);
                    }

                    [data-modulo="movimientos-web"] .acciones .boton i {
                        font-size: 13px;
                    }

                    /* Badge de estado un poco más marcado */
                    [data-modulo="movimientos-web"] .estado-badge {
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 700;
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
                </style>
            </tbody>
        </table>


        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/movimientos-web/listado');
        }
        ?>

        <div id="alerta-accion" class="form-container"></div>

        <script type="module" src="/assets/js/formularios.js"></script>
        <script>
            // Filtros por estado
            document.querySelectorAll('.filtro-estado').forEach(button => {
                button.addEventListener('click', function() {
                    document.querySelectorAll('.filtro-estado').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const estadoFiltro = this.dataset.estado;
                    const filas = document.querySelectorAll('#tabla-ordenes tr');

                    filas.forEach(fila => {
                        if (estadoFiltro === 'todos') {
                            fila.style.display = '';
                        } else {
                            const estadoFila = fila.dataset.estado;
                            fila.style.display = estadoFila === estadoFiltro ? '' : 'none';
                        }
                    });
                });
            });

            // Buscador
            const inputBusqueda = document.getElementById('busqueda-orden');
            const btnBuscar = document.getElementById('btn-buscar-orden');

            function buscarOrdenes() {
                const termino = inputBusqueda.value.toLowerCase();
                const filas = document.querySelectorAll('#tabla-ordenes tr');

                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = texto.includes(termino) ? '' : 'none';
                });
            }

            btnBuscar?.addEventListener('click', buscarOrdenes);
            inputBusqueda?.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') buscarOrdenes();
            });

            // Copiar al portapapeles
            function copiarAlPortapapeles(texto) {
                navigator.clipboard.writeText(texto).then(() => {
                    mostrarAlerta('ID de pago copiado: ' + texto, 'exito');
                });
            }

            // Manejar cancelación de órdenes
            document.addEventListener('click', async function(e) {
                if (e.target.closest('[data-accion="cancelar"]')) {
                    const button = e.target.closest('[data-accion="cancelar"]');
                    const id = button.dataset.id;
                    const nombre = button.dataset.nombre;

                    const confirmado = await confirmarAccionPersonalizada(
                        `¿Confirma la cancelación de ${nombre}? Esta acción devolverá el stock.`
                    );

                    if (confirmado) {
                        fetch('/administrador/movimientos-web/cancelar', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    id_orden: id
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.ok) {
                                    mostrarAlerta(data.mensaje, 'exito');
                                    setTimeout(() => location.reload(), 1200);
                                } else {
                                    mostrarAlerta(data.mensaje || 'Error al cancelar la orden', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                mostrarAlerta('Error de conexión', 'error');
                            });
                    }
                }
            });
        </script>

        <script>
            document.getElementById('btn-buscar-fecha')?.addEventListener('click', () => {
                const desde = document.getElementById('fecha-desde-export').value;
                const hasta = document.getElementById('fecha-hasta-export').value;

                const params = new URLSearchParams();
                if (desde) params.set('fecha_desde', desde);
                if (hasta) params.set('fecha_hasta', hasta);

                window.location.href = '/administrador/movimientos-web/listado?' + params.toString();
            });
        </script>

        <script>
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
        </script>


        <?php

        use Classes\Paginador;

        echo Paginador::renderCSS();
        ?>

        </style>
    </div>
</body>

</html>