<?php
$tipoComprobante = $venta['tipo_comprobante'] ?? 'TICKET';
$esFactura = ($tipoComprobante !== 'TICKET');

// Obtener número de caja desde el arqueo
$db = \Models\ActiveRecord::getDB();
$queryArqueo = "SELECT c.numero_caja 
                FROM arqueo_cajas ac
                INNER JOIN cajas c ON ac.rela_caja = c.id_caja
                WHERE ac.id_arqueo_caja = ?";
$stmt = $db->prepare($queryArqueo);
$stmt->bind_param("i", $venta['rela_arqueo_caja']);
$stmt->execute();
$resultArqueo = $stmt->get_result();
$arqueo = $resultArqueo->fetch_assoc();
$numeroCaja = $arqueo['numero_caja'] ?? '1';
?>

<div class="ticket-page">
    <div class="ticket-wrapper">
        <div id="ticket-content" class="ticket-termic">
            <!-- ENCABEZADO -->
            <div class="ticket-header">
                <h1>CINFSA</h1>
                <p class="subtitle">CINEMA & ENTERTAINMENT</p>
                <p class="info">Formosa, Formosa - Argentina</p>
                <p class="info">Tel: (0370) 123-4567</p>
                <?php if ($esFactura): ?>
                    <p class="info">CUIT: 20-12345678-9</p>
                    <p class="info">Ingresos Brutos: 901-123456-7</p>
                    <p class="info">Inicio de Actividades: 01/01/2020</p>
                <?php endif; ?>
                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>
            </div>

            <!-- TIPO DE COMPROBANTE -->
            <div class="ticket-numero <?php echo $esFactura ? 'factura' : 'ticket'; ?>">
                <?php if ($tipoComprobante === 'TICKET'): ?>
                    <p class="tipo-doc">TICKET VENTA DE ENTRADAS</p>
                    <p class="subtipo">NO VÁLIDO COMO FACTURA</p>
                <?php else: ?>
                    <p class="tipo-doc">FACTURA "<?php echo $tipoComprobante; ?>"</p>
                    <p class="subtipo">COMPROBANTE FISCAL</p>
                <?php endif; ?>

                <p class="numero-grande"><?php echo $venta['numero_comprobante']; ?></p>
            </div>

            <div class="separator-light">- - - - - - - - - - -</div>

            <!-- INFORMACIÓN -->
            <div class="ticket-body">
                <table class="info-compact">
                    <tr>
                        <td>Fecha:</td>
                        <td class="right"><strong><?php echo date('d/m/Y', strtotime($venta['pago_fecha_hora'])); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Hora:</td>
                        <td class="right"><strong><?php echo date('H:i', strtotime($venta['pago_fecha_hora'])); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Vendedor:</td>
                        <td class="right"><?php echo strtoupper($venta['vendedor']); ?></td>
                    </tr>
                    <tr>
                        <td>Caja:</td>
                        <td class="right">N° <?php echo str_pad($numeroCaja, 2, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td>Pago:</td>
                        <td class="right"><?php echo strtoupper($venta['tipo_pago']); ?></td>
                    </tr>
                </table>

                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                <!-- FUNCIÓN -->
                <div class="funcion-info">
                    <p class="pelicula"><?php echo strtoupper($venta['titulo_pelicula']); ?></p>
                    <table class="info-compact">
                        <tr>
                            <td>Día:</td>
                            <td class="right"><?php echo date('d/m/Y', strtotime($venta['fecha_funcion'])); ?></td>
                        </tr>
                        <tr>
                            <td>Horario:</td>
                            <td class="right"><?php echo $venta['turno_horario']; ?></td>
                        </tr>
                        <tr>
                            <td>Sala:</td>
                            <td class="right">SALA <?php echo $venta['id_sala']; ?></td>
                        </tr>
                        <tr>
                            <td>Formato:</td>
                            <td class="right"><?php echo $venta['tipo_entrada_desc'] ?? '2D'; ?></td>
                        </tr>
                    </table>
                </div>

                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                <!-- DETALLE -->
                <table class="items">
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>CANT</th>
                            <th class="right">P.U.</th>
                            <th class="right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                        <tr>
                            <td>ENT <?php echo $venta['tipo_entrada_desc'] ?? '2D'; ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                            <td class="right">$<?php echo number_format($detalle['precio_venta'], 0, ',', '.'); ?></td>
                            <td class="right">$<?php echo number_format($detalle['precio_venta'] * $detalle['cantidad'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- BUTACAS -->
                <div class="butacas">
                    <span class="label">BUTACAS:</span>
                    <?php 
                    $todasButacas = [];
                    foreach ($detalles as $detalle) {
                        if (!empty($detalle['butacas_detalle'])) {
                            $todasButacas[] = $detalle['butacas_detalle'];
                        }
                    }
                    echo implode(', ', $todasButacas);
                    ?>
                </div>

                <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>

                <!-- TOTAL -->
                <div class="total-section">
                    <table class="total-table">
                        <tr>
                            <td>Entradas:</td>
                            <td class="right"><strong><?php echo $total_entradas; ?></strong></td>
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

                <!-- QR DE CADA ENTRADA (se escanea en la puerta) -->
                <?php if (!empty($entradasQR)): ?>
                    <div class="entradas-qr">
                        <p class="entradas-qr-titulo">MOSTRAR EN LA ENTRADA DE LA SALA</p>
                        <?php foreach ($entradasQR as $entradaQR): ?>
                            <div class="entrada-qr">
                                <img src="<?php echo s($entradaQR['qr']); ?>" alt="QR de la entrada <?php echo s($entradaQR['butaca']); ?>">
                                <p><?php echo s($entradaQR['butaca']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="separator">━━━━━━━━━━━━━━━━━━━━━━</div>
                <?php endif; ?>

                <!-- PIE -->
                <div class="ticket-footer">
                    <?php if ($tipoComprobante === 'TICKET'): ?>
                        <p class="importante">⚠ PRESENTAR EN SALA ⚠</p>
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
                    <p class="disfrute">🎬 Disfrute la función 🍿</p>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="acciones no-print">
            <button onclick="imprimirTicket()" class="btn btn-primary">
                <i class="fas fa-print"></i> IMPRIMIR
            </button>
            <button onclick="window.location.href='/vendedor/funciones/listado'" class="btn btn-success">
                <i class="fas fa-plus"></i> NUEVA VENTA
            </button>
            <button onclick="window.location.href='/vendedor/caja/estado'" class="btn btn-info">
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

body, .ticket-page {
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    font-family: 'Courier New', Courier, monospace;
    padding: 20px;
}

.ticket-wrapper {
    max-width: 300px;
    width: 100%;
}

.ticket-termic {
    background: #ffffff;
    border: 2px solid #333;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    font-size: 10px;
    line-height: 1.4;
    color: #000;
}

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
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
}

.ticket-body {
    margin-top: 10px;
}

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

.funcion-info {
    margin: 10px 0;
}

.pelicula {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 8px;
    text-align: center;
}

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

.right {
    text-align: right;
}

.butacas {
    background: #f8f8f8;
    border: 1px dashed #999;
    padding: 8px;
    margin: 10px 0;
    font-size: 9px;
    text-align: center;
}

.butacas .label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}

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

.entradas-qr {
    text-align: center;
}

.entradas-qr-titulo {
    font-size: 9px;
    font-weight: bold;
    margin-bottom: 6px;
}

.entrada-qr {
    padding: 6px 0;
    border-bottom: 1px dashed #ccc;
    break-inside: avoid;
}

.entrada-qr:last-child {
    border-bottom: none;
}

.entrada-qr img {
    display: block;
    width: 38mm;
    height: 38mm;
    margin: 0 auto 3px;
    image-rendering: pixelated;
}

.entrada-qr p {
    font-size: 10px;
    font-weight: bold;
}

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

.disfrute {
    font-size: 10px;
    margin-top: 8px;
}

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
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.btn-success {
    background: linear-gradient(135deg, #16a34a, #15803d);
}

.btn-info {
    background: linear-gradient(135deg, #0891b2, #0e7490);
}

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
}
</style>

<script>
function imprimirTicket() {
    window.print();
}
</script>