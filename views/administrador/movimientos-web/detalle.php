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
                    <span class="value"><?php echo $orden->nombre_usuario; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo $orden->email; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Teléfono:</span>
                    <span class="value">No especificado</span>
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
                                <div><strong>Butaca:</strong> <?php echo $detalle->info_butaca; ?></div>
                                <div><strong>Función:</strong> <?php echo $detalle->fecha_funcion; ?></div>
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