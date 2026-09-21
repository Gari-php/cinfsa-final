<div class="resultado-pago pendiente">
    <div class="icono-resultado">
        <i class="fas fa-clock"></i>
    </div>
    <h1>Pago pendiente</h1>
    <p>Tu orden <strong><?php echo $numero_orden ?? 'N/A'; ?></strong> está pendiente de confirmación.</p>
    <p>Recibirás un email cuando se confirme el pago.</p>
    <div class="acciones-resultado">
        <a href="/menu" class="btn-volver-menu">
            <i class="fas fa-home"></i> Volver al inicio
        </a>
    </div>
</div>

<style>
    .resultado-pago {
        max-width: 500px;
        margin: 60px auto;
        padding: 50px 40px;
        border-radius: 20px;
        text-align: center;
        background: #2d3748;
        border: 1px solid #4a5568;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.4);
    }

    .icono-resultado i {
        font-size: 70px !important;
        display: block;
        margin-bottom: 20px;
    }

    .resultado-pago h1 {
        font-size: 1.8rem;
        margin: 0 0 15px 0;
    }

    .resultado-pago p {
        color: #a0aec0;
        font-size: 15px !important;
        line-height: 1.6;
        margin-bottom: 12px;
    }

    .resultado-pago p strong {
        color: #fff;
        font-family: monospace;
    }

    .acciones-resultado {
        margin-top: 30px;
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-volver-menu {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px !important;
        transition: all 0.2s ease;
        background: #2d3748;
        color: #fff;
        border: 1px solid #4a5568;
    }

    .btn-volver-menu:hover {
        border-color: #ed850f;
        background: #364154;
        transform: translateY(-2px);
    }

    /* Color específico por estado */
    .resultado-pago.pendiente .icono-resultado i {
        color: #f59e0b;
    }

    .resultado-pago.pendiente h1 {
        color: #f59e0b;
    }

    .resultado-pago.aprobado .icono-resultado i {
        color: #22c55e;
    }

    .resultado-pago.aprobado h1 {
        color: #22c55e;
    }

    .resultado-pago.fallido .icono-resultado i {
        color: #ef4444;
    }

    .resultado-pago.fallido h1 {
        color: #ef4444;
    }
</style>