<?php
$estado = $datos['estado'] ?? 'unknown';
$numeroOrden = $datos['numero_orden'] ?? '';
$mensaje = $datos['mensaje'] ?? '';

$config = [
    'approved' => [
        'icono'  => 'fa-circle-check',
        'color'  => '#22c55e',
        'titulo' => '¡Pago aprobado!',
        'texto'  => 'Tu compra fue procesada correctamente.',
    ],
    'pending' => [
        'icono'  => 'fa-clock',
        'color'  => '#f59e0b',
        'titulo' => 'Pago pendiente',
        'texto'  => 'Tu pago está siendo procesado. Te notificaremos cuando se confirme.',
    ],
    'failure' => [
        'icono'  => 'fa-circle-xmark',
        'color'  => '#ef4444',
        'titulo' => 'Pago rechazado',
        'texto'  => 'Hubo un problema con tu pago. Podés intentarlo de nuevo.',
    ],
    'unknown' => [
        'icono'  => 'fa-circle-question',
        'color'  => '#6c757d',
        'titulo' => 'Estado desconocido',
        'texto'  => 'No pudimos confirmar el estado de tu pago.',
    ],
];

$info = $config[$estado] ?? $config['unknown'];
?>

<div class="retorno-container">
    <div class="retorno-card">
        <div class="retorno-icono">
            <i class="fa-solid <?php echo $info['icono']; ?>" style="color: <?php echo $info['color']; ?>"></i>
        </div>

        <h1 class="retorno-titulo" style="color: <?php echo $info['color']; ?>">
            <?php echo $info['titulo']; ?>
        </h1>

        <p class="retorno-texto"><?php echo $info['texto']; ?></p>

        <?php if (!empty($numeroOrden)): ?>
            <div class="retorno-orden">
                <span class="retorno-orden-label">N° de Orden:</span>
                <span class="retorno-orden-valor"><?php echo htmlspecialchars($numeroOrden); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje)): ?>
            <p class="retorno-mensaje"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <div class="retorno-acciones">
            <a href="/carrito/checkout" class="btn-retorno btn-principal">
                <i class="fa-solid fa-bag-shopping"></i> Volver a la tienda
            </a>
            <?php if ($estado === 'approved'): ?>
                <a href="/perfil" class="btn-retorno btn-secundario">
                    <i class="fa-solid fa-ticket"></i> Ver mis compras
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .retorno-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .retorno-card {
        background: #2d3748;
        border-radius: 20px;
        padding: 50px 40px;
        max-width: 500px;
        width: 100%;
        text-align: center;
        border: 1px solid #4a5568;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.4);
    }

    .retorno-icono i {
        font-size: 70px !important;
        margin-bottom: 20px;
        display: block;
    }

    .retorno-titulo {
        font-size: 1.8rem;
        margin: 0 0 15px 0;
    }

    .retorno-texto {
        color: #a0aec0;
        font-size: 15px !important;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .retorno-orden {
        background: #1a202c;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid #4a5568;
    }

    .retorno-orden-label {
        color: #a0aec0;
        font-size: 14px !important;
    }

    .retorno-orden-valor {
        color: #ed850f;
        font-weight: bold;
        font-family: monospace;
        font-size: 14px !important;
    }

    .retorno-mensaje {
        color: #718096;
        font-size: 13px !important;
        margin-bottom: 25px;
    }

    .retorno-acciones {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-retorno {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px !important;
        transition: all 0.2s ease;
    }

    .btn-principal {
        background: #ed850f;
        color: #fff;
    }

    .btn-principal:hover {
        background: #d97706;
        transform: translateY(-2px);
    }

    .btn-secundario {
        background: #2d3748;
        color: #fff;
        border: 1px solid #4a5568;
    }

    .btn-secundario:hover {
        border-color: #ed850f;
        background: #364154;
        transform: translateY(-2px);
    }
</style>