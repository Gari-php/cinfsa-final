<!-- views/administrador/cajas/detalle.php -->
<div class="detalle-arqueo-container">
    <div class="header-detalle">
        <div>
            <h1><i class="fas fa-file-invoice-dollar"></i> Detalle de Arqueo #<?php echo $arqueo['id_arqueo_caja']; ?></h1>
            <p><?php echo $arqueo['nombre_caja']; ?> - <?php echo $arqueo['vendedor']; ?></p>
        </div>
        <a href="/administrador/actividadcajas/monitor" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Info del Arqueo -->
    <div class="card-info-arqueo">
        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-calendar"></i>
                <div>
                    <span>Fecha</span>
                    <strong><?php echo date('d/m/Y', strtotime($arqueo['fecha_inicio'])); ?></strong>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <span>Apertura</span>
                    <strong><?php echo date('H:i', strtotime($arqueo['fecha_inicio'])); ?></strong>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <span>Cierre</span>
                    <strong>
                        <?php
                        if ($arqueo['fecha_cierre']) {
                            echo date('H:i', strtotime($arqueo['fecha_cierre']));
                        } else {
                            echo '<span style="color: #10b981;">ABIERTA</span>';
                        }
                        ?>
                    </strong>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-info-circle"></i>
                <div>
                    <span>Estado</span>
                    <strong>
                        <span class="badge-estado <?php echo $arqueo['estado_arqueo']; ?>">
                            <?php echo strtoupper($arqueo['estado_arqueo']); ?>
                        </span>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen Financiero -->
    <div class="card-resumen-financiero">
        <h2><i class="fas fa-chart-bar"></i> Resumen Financiero</h2>
        <div class="resumen-grid">
            <div class="resumen-item inicial">
                <i class="fas fa-piggy-bank"></i>
                <div>
                    <span>Monto Inicial</span>
                    <strong>$<?php echo number_format($arqueo['monto_inicial'], 0, ',', '.'); ?></strong>
                </div>
            </div>
            <div class="resumen-item ingresos">
                <i class="fas fa-arrow-up"></i>
                <div>
                    <span>Total Ingresos</span>
                    <strong>$<?php echo number_format($total_ingresos, 0, ',', '.'); ?></strong>
                </div>
            </div>
            <div class="resumen-item egresos">
                <i class="fas fa-arrow-down"></i>
                <div>
                    <span>Total Egresos</span>
                    <strong>$<?php echo number_format($total_egresos, 0, ',', '.'); ?></strong>
                </div>
            </div>
            <div class="resumen-item final">
                <i class="fas fa-wallet"></i>
                <div>
                    <span>Saldo Final</span>
                    <strong>$<?php echo number_format($arqueo['monto_inicial'] + $total_ingresos - $total_egresos, 0, ',', '.'); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Movimientos -->
    <div class="card-movimientos">
        <h2><i class="fas fa-list"></i> Movimientos de Caja</h2>

        <?php if (empty($movimientos)): ?>
            <div class="sin-movimientos">
                <i class="fas fa-inbox"></i>
                <p>No hay movimientos registrados</p>
            </div>
        <?php else: ?>
            <div class="tabla-wrapper">
                <table class="tabla-movimientos">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Forma Pago</th>
                            <th>Monto</th>
                            <th>Fecha/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td>
                                    <span class="badge-tipo <?php echo $mov['tipo_movimiento']; ?>">
                                        <i class="fas fa-<?php echo $mov['tipo_movimiento'] === 'ingreso' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                        <?php echo ucfirst($mov['tipo_movimiento']); ?>
                                    </span>
                                </td>
                                <td><?php echo $mov['concepto_movimiento']; ?></td>
                                <td><?php echo ucfirst($mov['forma_pago']); ?></td>
                                <td class="monto-cell <?php echo $mov['tipo_movimiento']; ?>">
                                    <?php echo $mov['tipo_movimiento'] === 'ingreso' ? '+' : '-'; ?>
                                    $<?php echo number_format($mov['monto'], 0, ',', '.'); ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($arqueo['observaciones_cierre']): ?>
        <div class="card-observaciones">
            <h3><i class="fas fa-comment"></i> Observaciones de Cierre</h3>
            <p><?php echo nl2br(htmlspecialchars($arqueo['observaciones_cierre'])); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
    .detalle-arqueo-container {
        padding: 2rem;
        min-height: 100vh;
    }

    .header-detalle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .header-detalle h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        font-weight: bold;
        color: #ed850f;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-detalle p {
        margin: 0;
        color: #a0aec0;
    }

    .btn-volver {
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: bold;
        box-shadow: 0 6px 18px rgba(237, 133, 15, 0.35);
    }

    .btn-volver:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    .card-info-arqueo,
    .card-resumen-financiero,
    .card-movimientos,
    .card-observaciones {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        color: #e2e8f0;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
        gap: 1.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .info-item i {
        font-size: 2rem;
        color: #ed850f;
    }

    .info-item span {
        display: block;
        font-size: 0.85rem;
        color: #a0aec0;
    }

    .info-item strong {
        display: block;
        font-size: 1.2rem;
        color: #e2e8f0;
    }

    .badge-estado {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #fff;
    }

    .badge-estado.abierto {
        background: #22c55e;
    }

    .badge-estado.cerrado {
        background: #6b7280;
    }

    .card-resumen-financiero h2,
    .card-movimientos h2 {
        margin: 0 0 1.5rem 0;
        color: #ed850f;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
        gap: 1.5rem;
    }

    .resumen-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: #1a202c;
        border: 1px solid #4a5568;
        border-radius: 10px;
        transition: border-color 0.2s ease;
    }

    .resumen-item:hover {
        border-color: #ed850f;
    }

    .resumen-item i {
        font-size: 2rem;
        color: #ed850f;
    }

    .resumen-item.ingresos i {
        color: #22c55e;
    }

    .resumen-item.egresos i {
        color: #ef4444;
    }

    .resumen-item span {
        display: block;
        font-size: 0.9rem;
        color: #a0aec0;
    }

    .resumen-item strong {
        display: block;
        font-size: 1.5rem;
        color: #e2e8f0;
    }

    .sin-movimientos {
        text-align: center;
        padding: 3rem;
        color: #a0aec0;
    }

    .sin-movimientos i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #4a5568;
    }

    .tabla-wrapper {
        overflow-x: auto;
    }

    .tabla-movimientos {
        width: 100%;
        border-collapse: collapse;
    }

    .tabla-movimientos thead th {
        background: #1a202c;
        color: #ed850f;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #ed850f;
    }

    .tabla-movimientos tbody td {
        padding: 1rem;
        border-bottom: 1px solid #4a5568;
        color: #e2e8f0;
    }

    .tabla-movimientos tbody tr:hover {
        background: rgba(237, 133, 15, 0.06);
    }

    .badge-tipo {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .badge-tipo.ingreso {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid #22c55e;
    }

    .badge-tipo.egreso {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid #ef4444;
    }

    .monto-cell {
        font-weight: bold;
        font-size: 1.1rem;
    }

    .monto-cell.ingreso {
        color: #6ee7b7;
    }

    .monto-cell.egreso {
        color: #fca5a5;
    }

    .card-observaciones h3 {
        margin: 0 0 1rem 0;
        color: #ed850f;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-observaciones p {
        line-height: 1.6;
        color: #e2e8f0;
    }

    @media (max-width: 768px) {
        .detalle-arqueo-container {
            padding: 1rem;
        }

        .header-detalle {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .info-grid,
        .resumen-grid {
            grid-template-columns: 1fr;
        }
    }
</style>