<div class="detalle-entrada-container">
    <div class="header-detalle">
        <div class="titulo-header">
            <i class="fa-solid fa-ticket"></i>
            <h1>Detalle de Entrada #<?php echo $entrada['id_entrada']; ?></h1>
        </div>
        <a href="/administrador/entradas/listado" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver al listado
        </a>
    </div>

    <div class="card-detalle">
        <div class="card-header-estado">
            <?php
            $estado = $entrada['estado'] ?? 1;
            $estados = [
                1  => ['texto' => 'Activa',     'clase' => 'estado-activa',    'icono' => 'fa-circle-check'],
                2  => ['texto' => 'Usada',      'clase' => 'estado-usada',     'icono' => 'fa-circle-dot'],
                0  => ['texto' => 'Expirada',   'clase' => 'estado-expirada',  'icono' => 'fa-clock'],
                -1 => ['texto' => 'Cancelada',  'clase' => 'estado-cancelada', 'icono' => 'fa-ban']
            ];
            $estado_info = $estados[$estado] ?? $estados[1];
            ?>
            <span class="estado-badge <?php echo $estado_info['clase']; ?>">
                <i class="fa-solid <?php echo $estado_info['icono']; ?>"></i>
                <?php echo $estado_info['texto']; ?>
            </span>
        </div>

        <div class="grid-detalle">
            <div class="seccion">
                <h3><i class="fa-solid fa-film"></i> Función</h3>
                <div class="dato">
                    <span class="label">Película</span>
                    <span class="valor"><?php echo htmlspecialchars($entrada['titulo_pelicula']); ?></span>
                </div>
                <div class="dato">
                    <span class="label">Sala</span>
                    <span class="valor">Sala <?php echo $entrada['id_sala']; ?></span>
                </div>
                <div class="dato">
                    <span class="label">Fecha/Hora Función</span>
                    <span class="valor"><?php echo date('d/m/Y H:i', strtotime($entrada['fecha_hora'])); ?></span>
                </div>
                <div class="dato">
                    <span class="label">Tipo de Entrada</span>
                    <span class="valor"><?php echo htmlspecialchars($entrada['tipo_entrada_desc']); ?></span>
                </div>
                <div class="dato">
                    <span class="label">Precio</span>
                    <span class="valor precio">$<?php echo number_format($entrada['precio_entrada'], 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="seccion">
                <h3><i class="fa-solid fa-receipt"></i> Comprobante</h3>
                <div class="dato">
                    <span class="label">N° Comprobante</span>
                    <span class="valor monospace">
                        <?php if (!empty($entrada['numero_comprobante'])): ?>
                            <?php echo $entrada['numero_comprobante']; ?>
                        <?php elseif (!empty($entrada['numero_orden'])): ?>
                            <span style="color: #3b82f6;"><?php echo $entrada['numero_orden']; ?></span>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
                <div class="dato">
                    <span class="label">Tipo</span>
                    <span class="valor">
                        <?php
                        $tipo = $entrada['tipo_comprobante'] ?? null;
                        if (empty($tipo)) {
                            $tipo = 'WEB';
                            $color = '#3b82f6';
                        } else {
                            $color = $tipo === 'TICKET' ? '#6c757d' : '#f59e0b';
                        }
                        ?>
                        <span style="background: <?php echo $color; ?>; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                            <?php echo $tipo; ?>
                        </span>
                    </span>
                </div>
                <div class="dato">
                    <span class="label">Fecha de Venta</span>
                    <span class="valor">
                        <?php
                        if (!empty($entrada['pago_fecha_hora'])) {
                            echo date('d/m/Y H:i', strtotime($entrada['pago_fecha_hora']));
                        } elseif (!empty($entrada['fecha_venta'])) {
                            echo date('d/m/Y H:i', strtotime($entrada['fecha_venta']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                <div class="dato">
                    <span class="label">Vendedor</span>
                    <span class="valor"><?php echo !empty($entrada['vendedor']) ? $entrada['vendedor'] : 'Compra Web'; ?></span>
                </div>
                <div class="dato">
                    <span class="label">Caja</span>
                    <span class="valor"><?php echo !empty($entrada['numero_caja']) ? 'Caja #' . $entrada['numero_caja'] : '-'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --color-principal: #ed850f;
        --color-principal-hover: #d97706;
        --color-fondo-oscuro: #1a202c;
        --color-fondo-gris: #2d3748;
        --color-hover-gris: #4a5568;
        --color-texto-claro: #fff;
    }

    .detalle-entrada-container {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
        padding: 2rem;
    }

    .header-detalle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .titulo-header {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .titulo-header i {
        font-size: 2rem;
        color: var(--color-principal);
    }

    .titulo-header h1 {
        color: var(--color-texto-claro);
        margin: 0;
        font-size: 1.8rem;
    }

    .btn-volver {
        background: var(--color-fondo-gris);
        color: var(--color-texto-claro);
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: 2px solid var(--color-hover-gris);
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .btn-volver:hover {
        background: var(--color-hover-gris);
        transform: translateY(-2px);
    }

    .card-detalle {
        background: var(--color-fondo-gris);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        max-width: 900px;
        margin: 0 auto;
    }

    .card-header-estado {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--color-hover-gris);
    }

    .estado-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        font-size: 1rem;
        font-weight: 700;
    }

    .estado-activa {
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
        border: 2px solid #22c55e;
    }

    .estado-usada {
        background: rgba(108, 117, 125, 0.2);
        color: #cbd5e1;
        border: 2px solid #6c757d;
    }

    .estado-expirada {
        background: rgba(245, 158, 11, 0.2);
        color: #fcd34d;
        border: 2px solid #f59e0b;
    }

    .estado-cancelada {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border: 2px solid #ef4444;
    }

    .grid-detalle {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .seccion {
        background: var(--color-fondo-oscuro);
        border-radius: 15px;
        padding: 1.5rem;
    }

    .seccion h3 {
        color: var(--color-principal);
        margin: 0 0 1.2rem 0;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.1rem;
        border-bottom: 2px solid var(--color-hover-gris);
        padding-bottom: 0.75rem;
    }

    .dato {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.65rem 0;
        border-bottom: 1px solid var(--color-hover-gris);
    }

    .dato:last-child {
        border-bottom: none;
    }

    .label {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.9rem;
    }

    .valor {
        color: var(--color-texto-claro);
        font-weight: 600;
        text-align: right;
    }

    .valor.precio {
        color: var(--color-principal);
        font-size: 1.2rem;
    }

    .valor.monospace {
        font-family: monospace;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .detalle-entrada-container {
            padding: 1rem;
        }

        .grid-detalle {
            grid-template-columns: 1fr;
        }

        .header-detalle {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>