<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo" class="form-logo">
        </div>
        <h1>Lista de Gastos</h1>
        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/gastos/crear"><i class="fa-solid fa-plus"></i> Agregar Gasto</a></li>
                <li class="buscador">
                    <input id="busqueda-gasto" type="text" placeholder="Buscar por concepto, número de comprobante...">
                    <button id="btn-buscar-gasto" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
                <li class="exportacion">
                    <span><i class="fa-solid fa-download"></i> Exportar:</span>
                    <button class="btn-exportar excel" data-tipo="excel" title="Exportar a Excel">
                        <i class="fa-solid fa-file-excel"></i> Excel
                    </button>
                    <button class="btn-exportar pdf" data-tipo="pdf" title="Exportar a PDF">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Comprobante</th>
                    <th>Concepto</th>
                    <th>Tipo</th>
                    <th>Monto</th>
                    <th>Forma de Pago</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gastos)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #a0aec0;">
                            <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                            No hay gastos registrados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td><?= $g->id_egreso ?></td>
                            <td><?= date('d/m/Y', strtotime($g->fecha_egreso)) ?></td>
                            <td style="font-family: monospace;"><?= htmlspecialchars($g->numero_comprobante) ?: '-' ?></td>
                            <td><?= htmlspecialchars($g->concepto) ?></td>
                            <td>
                                <?php
                                $tipos = [
                                    'operativo' => '<span style="background: #22c55e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">OPERATIVO</span>',
                                    'administrativo' => '<span style="background: #3b82f6; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">ADMINISTRATIVO</span>',
                                    'comercial' => '<span style="background: #ed850f; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">COMERCIAL</span>'
                                ];
                                echo $tipos[$g->tipo_egreso] ?? '<span style="background: #6c757d; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">' . strtoupper($g->tipo_egreso) . '</span>';
                                ?>
                            </td>
                            <td style="font-weight: bold; color: #ef4444;">$<?= number_format($g->monto, 2, ',', '.') ?></td>
                            <td>
                                <?php
                                $formas_pago = [
                                    'efectivo' => 'Efectivo',
                                    'transferencia' => 'Transferencia',
                                    'tarjeta' => 'Tarjeta',
                                    'cheque' => 'Cheque'
                                ];
                                echo $formas_pago[$g->forma_pago] ?? $g->forma_pago;
                                ?>
                            </td>
                            <td>
                                <?php
                                $estados = [
                                    'registrado' => '<span style="color: #3b82f6;"> Registrado</span>',
                                    'aprobado' => '<span style="color: #f59e0b;"> Aprobado</span>',
                                    'pagado' => '<span style="color: #22c55e;"> Pagado</span>',
                                    'anulado' => '<span style="color: #ef4444;"> Anulado</span>'
                                ];
                                echo $estados[$g->estado_egreso] ?? $g->estado_egreso;
                                ?>
                            </td>
                            <td>
                                <div class="acciones">
                                    <?php if ($g->estado_egreso !== 'anulado'): ?>
                                        <button class="boton eliminar-gasto"
                                            data-id="<?= $g->id_egreso ?>"
                                            data-concepto="<?= htmlspecialchars($g->concepto) ?>"
                                            style="background: #ef4444;">
                                            Anular
                                        </button>
                                        <a class="boton" href="/administrador/gastos/editar?id=<?= $g->id_egreso ?>">Editar</a>
                                    <?php else: ?>
                                        <span style="color: #6c757d; font-size: 12px;">Anulado</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <style>
                    #busqueda-gasto {
                        background: #1a202c !important;
                        color: #fff !important;
                        border: 1px solid #4a5568;
                        border-radius: 4px;
                        padding: 6px 10px;
                    }

                    #busqueda-gasto::placeholder {
                        color: #a0aec0;
                    }

                    #busqueda-gasto:focus {
                        outline: none;
                        border-color: #ed850f;
                        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.15);
                    }
                </style>
            </tbody>
        </table>

        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/gastos/listado');
        }
        ?>
    </div>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>

    <script>
        // El concepto lo escribe el vendedor: se escapa antes de insertarlo como HTML
        function escaparHtml(valor) {
            return String(valor ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Anular un gasto (botón del listado o de los resultados de búsqueda): confirmación y aviso en el modal del sistema
        async function anularGasto(boton, alTerminar) {
            const idGasto = boton.dataset.id;
            const conceptoGasto = boton.dataset.concepto;

            const confirmacion = await window.mostrarConfirmacionModal(
                '¿Estás seguro de que deseas <strong>ANULAR</strong> este gasto?<br><br>' +
                `Concepto: ${escaparHtml(conceptoGasto)}<br>` +
                `ID: ${escaparHtml(idGasto)}<br><br>` +
                'Esta acción cambiará el estado a "Anulado" y no se podrá revertir.',
                'danger'
            );
            if (!confirmacion) return;

            boton.disabled = true;
            boton.textContent = 'Anulando...';

            try {
                const response = await fetch('/administrador/gastos/eliminar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_egreso: idGasto
                    })
                });
                const data = await response.json();

                if (data.ok) {
                    window.mostrarAlerta(escaparHtml(data.mensaje || 'Gasto anulado'), 'exito');
                    alTerminar();
                    return;
                }
                window.mostrarAlerta(escaparHtml(data.mensaje || 'No se pudo anular el gasto'), 'error');
            } catch (error) {
                console.error('Error al anular gasto:', error);
                window.mostrarAlerta('Error de conexión. Intenta nuevamente.', 'error');
            }
            boton.disabled = false;
            boton.textContent = 'Anular';
        }

        document.addEventListener('DOMContentLoaded', function() {

            // Un solo manejador para todos los botones, también los que agrega la búsqueda
            document.addEventListener('click', (e) => {
                const boton = e.target.closest('.eliminar-gasto');
                if (!boton) return;
                e.preventDefault();
                const termino = document.getElementById('busqueda-gasto')?.value.trim();
                // Con una búsqueda activa se rehace la búsqueda; si no, se recarga el listado
                anularGasto(boton, () => termino ? buscarGastos() : setTimeout(() => window.location.reload(), 1500));
            });

            // Buscador de gastos
            document.getElementById('btn-buscar-gasto')?.addEventListener('click', buscarGastos);
            document.getElementById('busqueda-gasto')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') buscarGastos();
            });

            async function buscarGastos() {
                const termino = document.getElementById('busqueda-gasto').value.trim();

                if (!termino) {
                    location.reload();
                    return;
                }

                try {
                    const res = await fetch('/administrador/gastos/buscar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            termino
                        })
                    });

                    const data = await res.json();
                    const tbody = document.querySelector('.tabla-listado tbody');

                    if (data.ok && data.gastos.length > 0) {
                        tbody.innerHTML = data.gastos.map(g => {
                            const estados = {
                                'registrado': '<span style="color:#a0aec0; font-weight:600;">Registrado</span>',
                                'aprobado': '<span style="color:#3b82f6; font-weight:600;">Aprobado</span>',
                                'pagado': '<span style="color:#22c55e; font-weight:600;">Pagado</span>',
                                'anulado': '<span style="color:#ef4444; font-weight:600;">Anulado</span>',
                                'devolucion_pendiente': '<span style="color:#f59e0b; font-weight:600;">Dev. Pendiente</span>'
                            };

                            const estadoHtml = estados[g.estado_egreso] ?? g.estado_egreso;
                            const fecha = g.fecha_egreso ? g.fecha_egreso.substring(0, 10) : '-';
                            const monto = parseFloat(g.monto || 0).toLocaleString('es-AR', {
                                minimumFractionDigits: 2
                            });

                            return `
                    <tr>
                        <td>${g.id_egreso}</td>
                        <td>${fecha}</td>
                        <td>${g.numero_comprobante || '-'}</td>
                        <td>${escaparHtml(g.concepto || '-')}</td>
                        <td><span class="badge-tipo operativo">OPERATIVO</span></td>
                        <td style="color:#ed850f; font-weight:bold;">$${monto}</td>
                        <td>${g.forma_pago || '-'}</td>
                        <td>${estadoHtml}</td>
                        <td>
                            ${g.estado_egreso !== 'anulado' ? `
                            <button class="boton eliminar-gasto"
                                    data-id="${g.id_egreso}"
                                    data-concepto="${escaparHtml(g.concepto)}">
                                Anular
                            </button>` : ''}
                            <a class="boton" href="/administrador/gastos/editar?id=${g.id_egreso}">Editar</a>
                        </td>
                    </tr>
                `;
                        }).join('');

                    } else {
                        tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align:center; padding:40px; color:#a0aec0;">
                        No se encontraron gastos
                    </td>
                </tr>
            `;
                    }
                } catch (err) {
                    console.error('Error al buscar gastos:', err);
                    window.mostrarAlerta('Error de conexión', 'error');
                }
            }
        });
    </script>

    <script src="/assets/js/exportar.js"></script>

    <?php

    use Classes\Paginador;

    echo Paginador::renderCSS();
    ?>
</body>