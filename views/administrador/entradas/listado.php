<div class="listado">

    <div>
        <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
    </div>

    <h1>Lista de Entradas</h1>

    <nav class="nav-listado">
        <ul>
            <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
            <li><a href="/administrador/entradas/reportes"><i class="fa-solid fa-book"></i> Reportes de Entradas</a></li>
            <!-- BOTÓN TEMPORAL DE PRUEBA — sacar después de testear -->
            <li>
                <button id="btn-expirar-test" type="button" style="background:#7c3aed; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                    <i class="fa-solid fa-flask"></i> Expirar Vencidas (TEST)
                </button>
            </li>
            <li class="filtros-exportacion">
                <div class="grupo-filtros-fecha">
                    <span><i class="fa-solid fa-calendar-plus"></i> Filtrar por Fecha de Venta:</span>
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
                    <button id="btn-buscar-fecha" type="button" style="background: #ed850f; color: #fff; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                    <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                        <a href="/administrador/entradas/listado"
                            style="background: #6c757d; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
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
                <input type="text"
                    id="buscar-comprobante"
                    class="barra_buscador"
                    placeholder="Buscar por N° Comprobante..."
                    value="<?php echo htmlspecialchars($comprobante ?? ''); ?>">
                <button id="btn-buscar-comprobante">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </li>
        </ul>
    </nav>

    <!-- Filtros por estado -->
    <div class="filtros-estado">
        <h3><i class="fa-solid fa-filter"></i> Filtrar por Estado:</h3>
        <button class="filtro-estado active" data-estado="todos">
            <i class="fa-solid fa-list"></i> Todos
        </button>
        <button class="filtro-estado" data-estado="1">
            <i class="fa-solid fa-circle-check"></i> Activas
        </button>
        <button class="filtro-estado" data-estado="2">
            <i class="fa-solid fa-circle-dot"></i> Usadas
        </button>
        <button class="filtro-estado" data-estado="0">
            <i class="fa-solid fa-clock"></i> Expiradas
        </button>
        <button class="filtro-estado" data-estado="-1">
            <i class="fa-solid fa-ban"></i> Canceladas
        </button>
    </div>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>ID</th>
                <th>N° Comprobante</th>
                <th>Tipo</th>
                <th>Caja</th>
                <th>Vendedor</th>
                <th>Estado</th>
                <th>Tipo de Entrada</th>
                <th>Función</th>
                <th>Película</th>
                <th>Sala</th>
                <th>Fecha/Hora</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-entradas">
            <?php if (empty($entradas)): ?>
                <tr>
                    <td colspan="13" style="text-align: center; padding: 40px; color: #a0aec0;">
                        <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                        No hay entradas registradas
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($entradas as $entrada): ?>
                    <tr data-estado="<?php echo $entrada->estado ?? 1; ?>">
                        <td><?php echo $entrada->id_entrada; ?></td>

                        <!-- N° COMPROBANTE -->
                        <td>
                            <?php if (!empty($entrada->numero_comprobante) && $entrada->numero_comprobante !== 'N/A'): ?>
                                <span style="font-family: monospace; font-size: 11px; color: #fff;">
                                    <?php echo $entrada->numero_comprobante; ?>
                                </span>
                            <?php elseif (!empty($entrada->numero_orden)): ?>
                                <span style="font-family: monospace; font-size: 10px; color: #3b82f6;">
                                    <?php echo $entrada->numero_orden; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #6b7280;">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- TIPO -->
                        <td>
                            <?php
                            $tipo = $entrada->tipo_comprobante;
                            if (empty($tipo)) {
                                $tipo = 'WEB';
                                $color = '#3b82f6';
                            } else {
                                $color = $tipo === 'TICKET' ? '#6c757d' : '#f59e0b';
                            }
                            ?>
                            <span style="background: <?php echo $color; ?>; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold;">
                                <?php echo $tipo; ?>
                            </span>
                        </td>

                        <!-- CAJA -->
                        <td style="color: #fff;">Caja #<?php echo $entrada->numero_caja; ?></td>

                        <!-- VENDEDOR -->
                        <td style="color: #fff;"><?php echo $entrada->vendedor; ?></td>

                        <!-- ESTADO -->
                        <td>
                            <?php
                            $estado = $entrada->estado ?? 1;
                            $estados = [
                                1 => ['texto' => 'Activa', 'clase' => 'estado-activa', 'icono' => 'fa-circle-check'],
                                2 => ['texto' => 'Usada', 'clase' => 'estado-usada', 'icono' => 'fa-circle-dot'],
                                0 => ['texto' => 'Expirada', 'clase' => 'estado-expirada', 'icono' => 'fa-clock'],
                                -1 => ['texto' => 'Cancelada', 'clase' => 'estado-cancelada', 'icono' => 'fa-ban']
                            ];
                            $estado_info = $estados[$estado] ?? $estados[1];
                            ?>
                            <span class="estado-badge <?php echo $estado_info['clase']; ?>">
                                <i class="fa-solid <?php echo $estado_info['icono']; ?>"></i>
                                <?php echo $estado_info['texto']; ?>
                            </span>
                        </td>

                        <!-- DATOS DE LA ENTRADA -->
                        <td style="color: #fff;"><?php echo $entrada->tipo_entrada_desc; ?></td>
                        <td style="color: #fff;">Función #<?php echo $entrada->rela_funcion; ?></td>
                        <td style="color: #fff;"><?php echo $entrada->titulo_pelicula; ?></td>
                        <td style="color: #fff;">Sala <?php echo $entrada->id_sala; ?></td>
                        <td style="color: #fff;"><?php echo date('d/m/Y H:i', strtotime($entrada->fecha_hora)); ?></td>
                        <td style="color: #fff;">$<?php echo number_format($entrada->precio_entrada, 0, ',', '.'); ?></td>

                        <!-- ACCIONES -->
                        <td>
                            <div class="acciones">
                                <?php
                                $estado = $entrada->estado ?? 1;

                                // Botones según el estado actual
                                if ($estado == 1): // Activa
                                ?>
                                    <button class="boton usar"
                                        data-id="<?php echo $entrada->id_entrada; ?>"
                                        data-accion="usar"
                                        title="Marcar como usada">
                                        <i class="fa-solid fa-check"></i> Usar
                                    </button>
                                    <button class="boton eliminar-entrada"
                                        data-id="<?php echo $entrada->id_entrada; ?>"
                                        data-nombre="Entrada #<?php echo $entrada->id_entrada; ?>"
                                        data-accion="cancelar">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </button>
                                <?php elseif ($estado == 2): // Usada 
                                ?>
                                    <span class="boton" style="background: #6c757d; cursor: not-allowed;">
                                        <i class="fa-solid fa-check-circle"></i> Ya usada
                                    </span>
                                <?php elseif ($estado == 0): // Expirada 
                                ?>
                                    <span class="boton" style="background: #6c757d; cursor: not-allowed;">
                                        <i class="fa-solid fa-clock"></i> Expirada
                                    </span>
                                <?php elseif ($estado == -1): // Cancelada 
                                ?>
                                    <button class="boton restaurar"
                                        data-id="<?php echo $entrada->id_entrada; ?>"
                                        data-accion="restaurar"
                                        title="Restaurar entrada"
                                        style="background: #16a34a;">
                                        <i class="fa-solid fa-undo"></i> Restaurar
                                    </button>
                                <?php endif; ?>

                                <!-- Botón ver detalles siempre disponible -->
                                <a class="boton" href="/administrador/entradas/detalle?id=<?php echo $entrada->id_entrada; ?>" style="background: #0891b2;">
                                    <i class="fa-solid fa-eye"></i> Ver
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <style>
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
        echo $paginador->render('/administrador/entradas/listado');
    }
    ?>
    <div id="alerta-accion" class="form-container"></div>

    <script type="module" src="/assets/js/formularios.js"></script>
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

        // Filtros por estado
        document.querySelectorAll('.filtro-estado').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.filtro-estado').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const estadoFiltro = this.dataset.estado;
                const filas = document.querySelectorAll('#tabla-entradas tr');

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

        document.getElementById('btn-buscar-comprobante')?.addEventListener('click', () => {
            const comprobante = document.getElementById('buscar-comprobante').value.trim();
            const params = new URLSearchParams(window.location.search);

            if (comprobante) {
                params.set('comprobante', comprobante);
            } else {
                params.delete('comprobante');
            }

            window.location.href = '/administrador/entradas/listado?' + params.toString();
        });

        document.getElementById('buscar-comprobante')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('btn-buscar-comprobante').click();
            }
        });

        // Manejar acciones de estado
        document.addEventListener('click', async function(e) {
            if (e.target.closest('[data-accion]')) {
                const button = e.target.closest('[data-accion]');
                const accion = button.dataset.accion;
                const id = button.dataset.id;

                let confirmMessage = '';
                let endpoint = '';

                switch (accion) {
                    case 'usar':
                        confirmMessage = '¿Marcar esta entrada como usada?';
                        endpoint = '/administrador/entradas/usar';
                        break;
                    case 'cancelar':
                        confirmMessage = '¿Cancelar esta entrada? No podrá ser usada.';
                        endpoint = '/administrador/entradas/eliminar';
                        break;
                    case 'restaurar':
                        confirmMessage = '¿Restaurar esta entrada? Volverá a estar activa.';
                        endpoint = '/administrador/entradas/restaurar';
                        break;
                }

                const confirmado = await confirmarAccionPersonalizada(confirmMessage);

                if (confirmado) {
                    fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id_entrada: id,
                                accion: accion
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.ok) {
                                mostrarAlerta(data.mensaje, 'exito');
                                setTimeout(() => location.reload(), 1200);
                            } else {
                                mostrarAlerta(data.mensaje || 'Error al procesar la acción', 'error');
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
    
    <script> //PRUEBA DE TEST — Expirar entradas vencidas automáticamente
    document.getElementById('btn-expirar-test')?.addEventListener('click', async () => {
    try {
        const res = await fetch('/administrador/entradas/expirar-automaticamente', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();

        if (data.ok) {
            mostrarAlerta(data.mensaje, 'exito');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarAlerta(data.mensaje || 'Error al expirar entradas', 'error');
        }
    } catch (error) {
        mostrarAlerta('Error de conexión', 'error');
    }
});
                    </script>
</div>

<?php

use Classes\Paginador;

echo Paginador::renderCSS();
?>