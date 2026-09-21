<?php
$tipoComprobante = $venta['tipo_comprobante'] ?? 'TICKET';
$esFactura = ($tipoComprobante !== 'TICKET');
$tieneDatosFacturacion = !empty($venta['id_dato_facturacion']);
?>

<div class="ticket-page">
    <div class="ticket-wrapper">
        <div id="ticket-content" class="ticket-termic">
            <!-- ENCABEZADO -->
            <div class="ticket-header">
                <h1>CINFSA</h1>
                <p class="subtitle">CANTINA & SNACKS</p>
                <p class="info">Formosa, Formosa - Argentina</p>
                <p class="info">Tel: (0370) 123-4567</p>
                <?php if ($esFactura): ?>
                    <p class="info">CUIT: 20-12345678-9</p>
                    <p class="info">Ingresos Brutos: 901-123456-7</p>
                    <p class="info">Inicio de Actividades: 01/01/2020</p>
                <?php endif; ?>
                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>
            </div>


            <div class="ticket-numero <?php echo $esFactura ? 'factura' : 'ticket'; ?>">
                <?php if ($tipoComprobante === 'TICKET'): ?>
                    <p class="tipo-doc">TICKET VENTA PRODUCTOS</p>
                    <p class="subtipo">NO VÁLIDO COMO FACTURA</p>
                <?php else: ?>
                    <p class="tipo-doc">FACTURA "<?php echo $tipoComprobante; ?>"</p>
                    <p class="subtipo">COMPROBANTE FISCAL</p>
                <?php endif; ?>

                <p class="numero-grande">N° <?php echo $venta['numero_comprobante']; ?></p>
            </div>
            <div class="separator-light">- - - - - - - - - - -</div>

            <!-- INFORMACIÓN -->
            <div class="ticket-body">
                <table class="info-compact">
                    <tr>
                        <td>Fecha:</td>
                        <td class="right"><strong><?php echo date('d/m/Y', strtotime($venta['fecha_hora'])); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Hora:</td>
                        <td class="right"><strong><?php echo date('H:i', strtotime($venta['fecha_hora'])); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Vendedor:</td>
                        <td class="right"><?php echo strtoupper($venta['vendedor']); ?></td>
                    </tr>
                    <tr>
                        <td>Caja:</td>
                        <td class="right">N° <?php echo str_pad($venta['numero_caja'] ?? '3', 2, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td>Pago:</td>
                        <td class="right"><?php echo strtoupper($venta['forma_pago']); ?></td>
                    </tr>
                </table>

                <?php if ($tieneDatosFacturacion && $esFactura): ?>
                    <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                    <!-- DATOS DEL RECEPTOR -->
                    <div class="datos-receptor">
                        <p class="seccion-titulo">DATOS DEL RECEPTOR</p>
                        <table class="info-compact">
                            <tr>
                                <td colspan="2" class="receptor-nombre">
                                    <strong><?php echo s(strtoupper($venta['razon_social'])); ?></strong>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo s(strtoupper($venta['tipo_documento'])); ?>:</td>
                                <td class="right">
                                    <?php
                                    $doc = $venta['numero_documento'];
                                    // Formatear CUIT/CUIL si tiene 11 dígitos
                                    if (strlen($doc) === 11 && in_array(strtoupper($venta['tipo_documento']), ['CUIT', 'CUIL'])) {
                                        echo s(substr($doc, 0, 2) . '-' . substr($doc, 2, 8) . '-' . substr($doc, 10, 1));
                                    } else {
                                        echo s($doc);
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if (!empty($venta['domicilio'])): ?>
                                <tr>
                                    <td colspan="2" class="domicilio"><?php echo s(ucwords(strtolower($venta['domicilio']))); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($venta['localidad']) || !empty($venta['provincia'])): ?>
                                <tr>
                                    <td colspan="2" class="localidad">
                                        <?php
                                        $ubicacion = [];
                                        if (!empty($venta['localidad'])) $ubicacion[] = ucwords(strtolower($venta['localidad']));
                                        if (!empty($venta['provincia'])) $ubicacion[] = ucwords(strtolower($venta['provincia']));
                                        if (!empty($venta['codigo_postal'])) $ubicacion[] = '(CP: ' . $venta['codigo_postal'] . ')';
                                        echo s(implode(', ', $ubicacion));
                                        ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($venta['email_facturacion'])): ?>
                                <tr>
                                    <td>Email:</td>
                                    <td class="right"><?php echo s(strtolower($venta['email_facturacion'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($venta['telefono_facturacion'])): ?>
                                <tr>
                                    <td>Tel:</td>
                                    <td class="right"><?php echo s($venta['telefono_facturacion']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                <!-- DETALLE DE PRODUCTOS -->
                <table class="items">
                    <thead>
                        <tr>
                            <th>PRODUCTO</th>
                            <th>CANT</th>
                            <th class="right">P.U.</th>
                            <th class="right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotalGeneral = 0;
                        foreach ($detalles as $detalle):
                            $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
                            $subtotalGeneral += $subtotal;
                        ?>
                            <tr>
                                <td class="producto-nombre"><?php echo strtoupper(substr($detalle['producto'], 0, 15)); ?></td>
                                <td class="center"><?php echo $detalle['cantidad']; ?></td>
                                <td class="right">$<?php echo number_format($detalle['precio_unitario'], 0, ',', '.'); ?></td>
                                <td class="right">$<?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                <!-- TOTAL -->
                <div class="total-section">
                    <table class="total-table">
                        <tr>
                            <td>Total Productos:</td>
                            <td class="right"><strong><?php echo $total_productos; ?></strong></td>
                        </tr>
                        <?php if ($esFactura): ?>
                            <tr>
                                <td>Subtotal:</td>
                                <td class="right">$<?php echo number_format($venta['monto_total'] / 1.21, 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td>IVA (21%):</td>
                                <td class="right">$<?php echo number_format($venta['monto_total'] - ($venta['monto_total'] / 1.21), 0, ',', '.'); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="total-final">
                            <td>TOTAL:</td>
                            <td class="right">$<?php echo number_format($venta['monto_total'], 0, ',', '.'); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                <!-- PIE -->
                <div class="ticket-footer">
                    <?php if ($tipoComprobante === 'TICKET'): ?>
                        <p class="importante">⚠ CONSERVAR COMPROBANTE ⚠</p>
                        <p class="legal">CONSUMIDOR FINAL</p>
                        <p class="legal">DOCUMENTO NO VÁLIDO COMO FACTURA</p>
                    <?php else: ?>
                        <p class="importante">FACTURA VÁLIDA ANTE AFIP</p>
                        <p>CAE: <?php echo str_pad(rand(10000000, 99999999), 14, '0', STR_PAD_LEFT); ?></p>
                        <p>Vto. CAE: <?php echo date('d/m/Y', strtotime('+10 days')); ?></p>
                    <?php endif; ?>
                    <div class="separator-light">- - - - - - - - - - -</div>
                    <p class="gracias">¡GRACIAS POR SU COMPRA!</p>
                    <p class="web">www.cinfsa.com.ar</p>
                    <p class="productos-info">🍿 Disfruta nuestros productos 🥤</p>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="acciones no-print">
            <button onclick="imprimirTicket()" class="btn btn-primary">
                <i class="fas fa-print"></i> IMPRIMIR
            </button>
            <button onclick="window.location.href='/vendedorproductos/productos/listado'" class="btn btn-success">
                <i class="fas fa-plus"></i> NUEVA VENTA
            </button>
            <button onclick="window.location.href='/vendedorproductos/caja/estado'" class="btn btn-info">
                <i class="fas fa-cash-register"></i> CAJA
            </button>
        </div>
    </div>
</div>

<style>
    /* Reset y base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body,
    .ticket-page {
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        font-family: 'Courier New', Courier, monospace;
        padding: 20px;
    }

    /* Contenedor principal */
    .ticket-wrapper {
        max-width: 300px;
        width: 100%;
    }

    .ticket-termic {
        background: #ffffff;
        border: 2px solid #333;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        font-size: 10px;
        line-height: 1.4;
        color: #000;
    }

    /* Encabezado */
    .ticket-header {
        text-align: center;
        margin-bottom: 10px;
    }

    .ticket-header h1 {
        font-size: 24px;
        font-weight: bold;
        letter-spacing: 3px;
        margin-bottom: 5px;
    }

    .subtitle {
        font-size: 11px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .info {
        font-size: 9px;
        margin: 2px 0;
    }

    /* Separadores */
    .separator {
        text-align: center;
        margin: 8px 0;
        font-weight: bold;
        letter-spacing: -1px;
    }

    .separator-light {
        text-align: center;
        margin: 6px 0;
        color: #666;
        font-size: 9px;
    }

    /* Número de ticket */
    .ticket-numero {
        border: 2px solid #000;
        margin: 10px 0;
        padding: 8px;
        text-align: center;
        border-radius: 4px;
    }

    .ticket-numero.ticket {
        background: #f0f0f0;
    }

    .ticket-numero.factura {
        background: #fffbe6;
        border-color: #f59e0b;
    }

    .tipo-doc {
        font-size: 11px;
        font-weight: bold;
        margin-bottom: 3px;
    }

    .subtipo {
        font-size: 8px;
        margin-bottom: 5px;
    }

    .numero-grande {
        font-size: 14px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    /* Cuerpo del ticket */
    .ticket-body {
        margin-top: 10px;
    }

    /* Datos del receptor */
    .datos-receptor {
        background: #f9f9f9;
        border: 1px dashed #333;
        padding: 8px;
        margin: 8px 0;
        border-radius: 4px;
    }

    .seccion-titulo {
        text-align: center;
        font-weight: bold;
        font-size: 10px;
        margin-bottom: 6px;
        padding-bottom: 4px;
        border-bottom: 1px solid #333;
    }

    .receptor-nombre {
        font-size: 10px;
        padding: 3px 0 !important;
        text-align: center !important;
    }

    .domicilio,
    .localidad {
        font-size: 8px !important;
        padding: 2px 0 !important;
        text-align: center !important;
    }

    /* Tablas */
    .info-compact {
        width: 100%;
        margin: 5px 0;
    }

    .info-compact td {
        padding: 2px 0;
        font-size: 9px;
    }

    .info-compact td:first-child {
        width: 40%;
    }

    .info-compact td.right {
        text-align: right;
    }

    /* Tabla de items */
    .items {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0;
    }

    .items thead th {
        background: #333;
        color: #fff;
        padding: 4px 2px;
        font-size: 8px;
        font-weight: bold;
        text-align: left;
    }

    .items tbody td {
        padding: 3px 2px;
        font-size: 9px;
        border-bottom: 1px dashed #ccc;
    }

    .items tbody tr:last-child td {
        border-bottom: none;
    }

    .producto-nombre {
        font-weight: bold;
    }

    .center {
        text-align: center;
    }

    .right {
        text-align: right;
    }

    /* Sección de totales */
    .total-section {
        margin: 10px 0;
    }

    .total-table {
        width: 100%;
    }

    .total-table td {
        padding: 3px 0;
        font-size: 10px;
    }

    .total-final td {
        font-size: 14px;
        font-weight: bold;
        padding-top: 6px;
        border-top: 2px solid #000;
    }

    /* Pie del ticket */
    .ticket-footer {
        text-align: center;
        margin-top: 10px;
    }

    .ticket-footer p {
        margin: 3px 0;
        font-size: 9px;
    }

    .importante {
        font-weight: bold;
        font-size: 11px;
        margin: 8px 0;
    }

    .legal {
        font-size: 8px;
        color: #666;
    }

    .gracias {
        font-size: 12px;
        font-weight: bold;
        margin: 10px 0;
    }

    .web {
        font-size: 9px;
        color: #666;
    }

    .productos-info {
        font-size: 10px;
        margin-top: 8px;
    }

    /* Botones de acción */
    .acciones {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .btn {
        border: none;
        border-radius: 8px;
        padding: 10px 15px;
        color: #fff;
        cursor: pointer;
        font-size: 11px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
        font-family: Arial, sans-serif;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
    }

    .btn-success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #15803d, #166534);
    }

    .btn-info {
        background: linear-gradient(135deg, #0891b2, #0e7490);
    }

    .btn-info:hover {
        background: linear-gradient(135deg, #0e7490, #155e75);
    }

    .btn i {
        font-size: 13px;
    }

    /* Estilos de impresión */
    @media print {
        body {
            background: #fff;
            padding: 0;
        }

        .ticket-page {
            background: #fff;
            padding: 0;
        }

        .ticket-wrapper {
            max-width: none;
        }

        .ticket-termic {
            border: none;
            box-shadow: none;
            border-radius: 0;
            width: 72mm;
            /* Ancho estándar de impresora térmica */
            padding: 5mm;
            margin: 0;
        }

        .no-print {
            display: none !important;
        }

        @page {
            size: 72mm auto;
            margin: 0;
        }

        /* Ajustes de fuente para impresión */
        .ticket-header h1 {
            font-size: 20px;
        }

        .numero-grande {
            font-size: 12px;
        }

        .total-final td {
            font-size: 12px;
        }

        .datos-receptor {
            background: #fff;
            border: 1px solid #333;
        }
    }

    /* Responsive */
    @media (max-width: 400px) {
        .ticket-page {
            padding: 10px;
        }

        .ticket-wrapper {
            max-width: 100%;
        }

        .acciones {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    function imprimirTicket() {
        window.print();
    }

    // Agregar mensaje de confirmación antes de salir
    window.addEventListener('beforeunload', function(e) {
        if (!document.querySelector('.ticket-termic').classList.contains('impreso')) {
            e.preventDefault();
            e.returnValue = '¿Estás seguro de salir sin imprimir el ticket?';
            return e.returnValue;
        }
    });

    // Marcar como impreso después de imprimir
    window.addEventListener('afterprint', function() {
        document.querySelector('.ticket-termic').classList.add('impreso');
    });
</script>