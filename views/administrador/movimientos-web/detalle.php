<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Orden Web</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="detalle-orden-container">
        <div class="header-detalle">
            <img src="/assets/img/logo.png" alt="Logo" class="logo-detalle">
            <h1><i class="fa-solid fa-receipt"></i> Detalle de Orden Web</h1>
        </div>

        <div class="nav-detalle">
            <a href="/administrador/movimientos-web/listado" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver al Listado
            </a>
        </div>

        <!-- Información de la orden -->
        <div class="info-orden-grid">
            <div class="info-card">
                <h3><i class="fa-solid fa-hashtag"></i> Información General</h3>
                <div class="info-row">
                    <span class="label">N° Orden:</span>
                    <span class="value"><?php echo $orden->numero_orden ?? 'ORD-' . str_pad($orden->id_orden, 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Estado:</span>
                    <span class="value">
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
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Total:</span>
                    <span class="value total-monto">$<?php echo number_format($orden->total, 0, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Método de Pago:</span>
                    <span class="value"><?php echo ucfirst(str_replace('_', ' ', $orden->metodo_pago ?? 'No especificado')); ?></span>
                </div>
            </div>

            <div class="info-card">
                <h3><i class="fa-solid fa-user"></i> Datos del Cliente</h3>
                <div class="info-row">
                    <span class="label">Nombre:</span>
                    <span class="value"><?php echo s($orden->nombre_usuario); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo s($orden->email); ?></span>
                </div>
                <!-- Los deja el cliente en el checkout de esta compra (órdenes anteriores: sin dato) -->
                <div class="info-row">
                    <span class="label">Teléfono:</span>
                    <span class="value"><?php echo s($orden->telefono_contacto ?? '') ?: 'No especificado'; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">DNI:</span>
                    <span class="value"><?php echo s($orden->dni_contacto ?? '') ?: 'No especificado'; ?></span>
                </div>
            </div>

            <div class="info-card">
                <h3><i class="fa-solid fa-calendar"></i> Fechas</h3>
                <div class="info-row">
                    <span class="label">Creación:</span>
                    <span class="value"><?php echo date('d/m/Y H:i', strtotime($orden->fecha_creacion)); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Pago:</span>
                    <span class="value">
                        <?php echo $orden->fecha_pago ? date('d/m/Y H:i', strtotime($orden->fecha_pago)) : 'Pendiente'; ?>
                    </span>
                </div>
                <?php if ($orden->fecha_actualizacion): ?>
                <div class="info-row">
                    <span class="label">Actualización:</span>
                    <span class="value"><?php echo date('d/m/Y H:i', strtotime($orden->fecha_actualizacion)); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($orden->payment_id || $orden->preference_id): ?>
            <div class="info-card">
                <h3><i class="fa-brands fa-cc-mercadopago"></i> Mercado Pago</h3>
                <?php if ($orden->payment_id): ?>
                <div class="info-row">
                    <span class="label">Payment ID:</span>
                    <span class="value" style="font-family: monospace; font-size: 11px;">
                        <?php echo $orden->payment_id; ?>
                        <button onclick="copiarTexto('<?php echo $orden->payment_id; ?>')" class="btn-copy">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($orden->preference_id): ?>
                <div class="info-row">
                    <span class="label">Preference ID:</span>
                    <span class="value" style="font-family: monospace; font-size: 11px;">
                        <?php echo $orden->preference_id; ?>
                        <button onclick="copiarTexto('<?php echo $orden->preference_id; ?>')" class="btn-copy">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Detalle de productos -->
        <div class="productos-detalle">
            <h2><i class="fa-solid fa-shopping-cart"></i> Productos de la Orden</h2>
            
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Detalles</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orden->detalles as $detalle): ?>
                    <tr>
                        <!-- TIPO -->
                        <td>
                            <?php 
                            $tipo_badges = [
                                'butacas' => ['texto' => 'Entrada', 'color' => '#ed850f', 'icono' => 'fa-ticket'],
                                'cantina' => ['texto' => 'Cantina', 'color' => '#22c55e', 'icono' => 'fa-utensils'],
                                'fichas' => ['texto' => 'Juegos', 'color' => '#3b82f6', 'icono' => 'fa-gamepad']
                            ];
                            $badge = $tipo_badges[$detalle->tipo_producto] ?? ['texto' => $detalle->tipo_producto, 'color' => '#6b7280', 'icono' => 'fa-box'];
                            ?>
                            <span class="tipo-badge" style="background: <?php echo $badge['color']; ?>">
                                <i class="fa-solid <?php echo $badge['icono']; ?>"></i>
                                <?php echo $badge['texto']; ?>
                            </span>
                        </td>
                        
                        <!-- PRODUCTO -->
                        <td class="producto-nombre">
                            <?php echo $detalle->producto_nombre ?? $detalle->nombre_producto; ?>
                        </td>
                        
                        <!-- DETALLES -->
                        <td class="producto-detalles">
                            <?php if ($detalle->tipo_producto === 'butacas'): ?>
                                <div><strong>Butaca:</strong> <?php echo s($detalle->info_butaca); ?></div>
                                <div><strong>Función:</strong> <?php echo s($detalle->fecha_funcion); ?></div>
                                <?php
                                $estadosEntrada = [1 => 'Activa', 2 => 'Usada', 0 => 'Expirada', -1 => 'Cancelada'];
                                $estadoEntrada = $detalle->id_entrada !== null ? (int)$detalle->estado_entrada : -1;
                                ?>
                                <div><strong>Entrada:</strong>
                                    <?php echo $detalle->id_entrada !== null ? '#' . (int)$detalle->id_entrada . ' · ' : ''; ?>
                                    <?php echo $estadosEntrada[$estadoEntrada] ?? 'Sin entrada'; ?>
                                </div>
                                <?php if ($orden->estado === 'pagado' && $estadoEntrada === 1): ?>
                                    <!-- Devolución de una entrada web: solo el administrador; el dinero vuelve por Mercado Pago -->
                                    <button type="button" class="btn-cancelar-entrada-web"
                                        data-id-detalle="<?php echo (int)$detalle->id_detalle; ?>"
                                        data-descripcion="<?php echo s('#' . $detalle->id_entrada . ' — ' . $detalle->producto_nombre . ', ' . $detalle->info_butaca); ?>"
                                        data-monto="<?php echo s(number_format($detalle->precio_unitario, 0, ',', '.')); ?>"
                                        data-pago="<?php echo s($orden->payment_id ?? ''); ?>">
                                        <i class="fa-solid fa-ban"></i> Cancelar entrada
                                    </button>
                                <?php endif; ?>
                            <?php elseif (in_array($detalle->tipo_producto, ['cantina', 'fichas'], true)): ?>
                                <?php
                                $entregado = (int)$detalle->entregado;
                                $devuelto = (int)$detalle->cantidad_devuelta;
                                $pendiente = max(0, (int)$detalle->cantidad - $entregado - $devuelto);
                                ?>
                                <div><strong>Retiró:</strong> <?php echo $entregado; ?>
                                    <?php if ($devuelto > 0): ?> · <strong>Devuelto:</strong> <?php echo $devuelto; ?><?php endif; ?>
                                    · <strong>Pendiente:</strong> <?php echo $pendiente; ?></div>
                                <?php if ($orden->estado === 'pagado' && $pendiente > 0): ?>
                                    <!-- Devolución de lo que todavía no se retiró: solo el administrador; el dinero vuelve por Mercado Pago -->
                                    <button type="button" class="btn-devolver-producto-web"
                                        data-id-detalle="<?php echo (int)$detalle->id_detalle; ?>"
                                        data-nombre="<?php echo s($detalle->producto_nombre ?? $detalle->nombre_producto); ?>"
                                        data-pendiente="<?php echo $pendiente; ?>"
                                        data-precio="<?php echo (float)$detalle->precio_unitario; ?>"
                                        data-pago="<?php echo s($orden->payment_id ?? ''); ?>">
                                        <i class="fa-solid fa-rotate-left"></i> Devolver
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #a0aec0;">-</span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- CANTIDAD -->
                        <td class="text-center">
                            <strong><?php echo $detalle->cantidad; ?></strong>
                        </td>
                        
                        <!-- PRECIO UNITARIO -->
                        <td class="text-right">
                            $<?php echo number_format($detalle->precio_unitario, 0, ',', '.'); ?>
                        </td>
                        
                        <!-- SUBTOTAL -->
                        <td class="text-right subtotal-cell">
                            $<?php echo number_format($detalle->subtotal, 0, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right total-final">
                            <strong>$<?php echo number_format($orden->total, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    <script>
        // Cancelar una entrada de la compra: pide confirmación aclarando que el dinero no vuelve solo
        document.addEventListener('click', (e) => {
            const boton = e.target.closest('.btn-cancelar-entrada-web');
            if (!boton) return;

            const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const pago = boton.dataset.pago ? ` (pago N° ${esc(boton.dataset.pago)})` : '';

            window.confirmarAccionModal({
                id: boton.dataset.idDetalle,
                nombre: esc(boton.dataset.descripcion),
                mensajeConfirmacion: '¿Cancelar la entrada {nombre}? La entrada deja de servir y la butaca queda libre.<br><br>'
                    + `<strong>Esto NO devuelve el dinero:</strong> hacé el reembolso de $${esc(boton.dataset.monto)} `
                    + `desde tu cuenta de Mercado Pago${pago}. No se puede deshacer.`,
                url: '/administrador/movimientos-web/cancelar-entrada',
                dataKey: 'id_detalle',
                mensajeError: 'No se pudo cancelar la entrada'
            });
        });

        // Devolver productos o fichas que el cliente todavía no retiró: se elige cuántos
        document.addEventListener('click', (e) => {
            const boton = e.target.closest('.btn-devolver-producto-web');
            if (!boton) return;

            const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const pendiente = Number(boton.dataset.pendiente);
            const precio = Number(boton.dataset.precio);
            const plata = (n) => '$' + Math.round(n).toLocaleString('es-AR');
            const pago = boton.dataset.pago ? ` (pago N° ${esc(boton.dataset.pago)})` : '';

            const overlay = document.createElement('div');
            overlay.className = 'modal-alertas-overlay';
            overlay.id = 'modal-devolver-producto';
            overlay.style.display = 'flex';
            overlay.innerHTML = `
                <div class="alerta-modal confirmacion" role="dialog" aria-modal="true">
                    <div class="alerta-modal-content confirmacion-content">
                        <div class="alerta-modal-icon" style="color:#ed850f"><i class="fa-solid fa-rotate-left"></i></div>
                        <div class="alerta-modal-message">
                            Devolver <strong>${esc(boton.dataset.nombre)}</strong> que el cliente todavía no retiró
                            (pendientes: ${pendiente}).
                            <div class="devolver-cantidad">
                                <label for="devolver-cantidad">Cantidad a devolver</label>
                                <input type="number" id="devolver-cantidad" min="1" max="${pendiente}" step="1" value="${pendiente}">
                            </div>
                            Vuelven al stock y ya no se pueden retirar.<br><br>
                            <strong>Esto NO devuelve el dinero:</strong> hacé el reembolso de
                            <strong id="devolver-monto">${plata(pendiente * precio)}</strong>
                            desde tu cuenta de Mercado Pago${pago}. No se puede deshacer.
                        </div>
                        <div class="confirmacion-botones">
                            <button type="button" class="boton-modal cancelar" data-accion="cancelar"><i class="fa-solid fa-times"></i> Cancelar</button>
                            <button type="button" class="boton-modal confirmar" data-accion="confirmar"><i class="fa-solid fa-check"></i> Devolver</button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(overlay);

            const campo = overlay.querySelector('#devolver-cantidad');
            campo.addEventListener('input', () => {
                const n = Math.min(pendiente, Math.max(0, Math.floor(Number(campo.value) || 0)));
                overlay.querySelector('#devolver-monto').textContent = plata(n * precio);
            });

            overlay.addEventListener('click', async (ev) => {
                const accion = ev.target.closest('[data-accion]')?.dataset.accion;
                if (ev.target === overlay || accion === 'cancelar') {
                    overlay.remove();
                    return;
                }
                if (accion !== 'confirmar') return;

                const cantidad = Number(campo.value);
                if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > pendiente) {
                    campo.focus();
                    return;
                }
                overlay.remove();

                try {
                    const respuesta = await fetch('/administrador/movimientos-web/devolver-producto', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_detalle: boton.dataset.idDetalle, cantidad })
                    });
                    const datos = await respuesta.json();
                    window.mostrarAlerta(datos.mensaje || 'No se pudo devolver', datos.ok ? 'exito' : 'error');
                    if (datos.ok) setTimeout(() => location.reload(), 2500);
                } catch (error) {
                    window.mostrarAlerta('No se pudo devolver el producto', 'error');
                }
            });
        });

        function copiarTexto(texto) {
            navigator.clipboard.writeText(texto).then(() => {
                alert('Texto copiado al portapapeles');
            });
        }
    </script>

    <style>
        .detalle-orden-container {
            max-width: 1200px;
            margin: 140px auto 40px;
            padding: 30px;
            background: #1a202c;
            border-radius: 12px;
            color: #fff;
        }

        .header-detalle {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-detalle {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .header-detalle h1 {
            color: #ed850f;
            font-size: 28px;
        }

        .nav-detalle {
            margin-bottom: 30px;
        }

        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #4a5568;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-volver:hover {
            background: #2d3748;
        }

        .info-orden-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .info-card {
            background: #2d3748;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ed850f;
        }

        .info-card h3 {
            color: #ed850f;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #4a5568;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row .label {
            color: #a0aec0;
            font-size: 13px;
        }

        .info-row .value {
            color: #fff;
            font-weight: 500;
        }

        .total-monto {
            color: #22c55e;
            font-size: 20px;
            font-weight: bold;
        }

        .estado-badge {
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .estado-badge.estado-pagada {
            background: #22c55e;
        }

        .estado-badge.estado-pendiente {
            background: #f59e0b;
        }

        .estado-badge.estado-cancelada {
            background: #ef4444;
        }

        .estado-badge.estado-fallida {
            background: #6b7280;
        }

        .btn-copy {
            background: none;
            border: none;
            color: #ed850f;
            cursor: pointer;
            padding: 5px;
            margin-left: 10px;
        }

        .btn-copy:hover {
            color: #ff9f40;
        }

        .productos-detalle {
            background: #2d3748;
            padding: 30px;
            border-radius: 10px;
        }

        .productos-detalle h2 {
            color: #ed850f;
            margin-bottom: 25px;
            font-size: 20px;
        }

        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-productos thead {
            background: #1a202c;
        }

        .tabla-productos th {
            padding: 15px;
            text-align: left;
            color: #ed850f;
            font-size: 13px;
            text-transform: uppercase;
        }

        .tabla-productos td {
            padding: 15px;
            border-bottom: 1px solid #4a5568;
            color: #fff;
        }

        .tabla-productos tbody tr:hover {
            background: #374151;
        }

        .tipo-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }

        .producto-nombre {
            font-weight: 600;
        }

        .producto-detalles {
            font-size: 12px;
            color: #a0aec0;
        }

        .producto-detalles div {
            margin: 3px 0;
        }

        .btn-cancelar-entrada-web {
            margin-top: 8px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            background: #dc2626;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancelar-entrada-web:hover {
            background: #b91c1c;
        }

        .btn-devolver-producto-web {
            margin-top: 8px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            background: #ed850f;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-devolver-producto-web:hover {
            background: #c96d08;
        }

        .devolver-cantidad {
            margin: 14px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .devolver-cantidad input {
            width: 80px;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #4a5568;
            background: #1a202c;
            color: #fff;
            font-size: 15px;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .subtotal-cell {
            font-weight: 600;
            color: #22c55e;
        }

        .tabla-productos tfoot {
            background: #1a202c;
        }

        .tabla-productos tfoot td {
            padding: 20px 15px;
            border-bottom: none;
        }

        .total-final {
            color: #22c55e;
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .detalle-orden-container {
                margin: 24px 12px;
                padding: 18px;
            }

            .info-orden-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                gap: 12px;
            }

            .info-row .value {
                text-align: right;
                word-break: break-word;
            }

            .tabla-productos {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</body>
</html>