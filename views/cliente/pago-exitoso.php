<div class="resultado-pago">
    <div class="contenido-resultado exitoso">
        <div class="icono-resultado">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>¡Pago exitoso!</h1>
        
        <div class="info-orden">
            <p>Tu orden</p>
            <p class="numero-orden"><?php echo $numero_orden ?? 'N/A'; ?></p>
            <p>ha sido procesada correctamente</p>
        </div>

        <div class="mensaje-email">
            <i class="fas fa-envelope"></i>
            <p>Revisa tu bandeja de entrada.<br>Ahí se enviará el ticket de compra.</p>
        </div>

        <div class="acciones-resultado">
            <a href="/menu" class="btn-volver-menu">
                <i class="fas fa-home"></i> Volver al inicio
            </a>
        </div>
    </div>
</div>

<style>
.resultado-pago {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
}

.contenido-resultado {
    max-width: 500px;
    width: 100%;
    background: #2d3748;
    border-radius: 16px;
    padding: 3rem 2rem;
    text-align: center;
    border: 2px solid #4a5568;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

/* Icono superior */
.icono-resultado {
    margin-bottom: 2rem;
}

.icono-resultado i {
    font-size: 5rem;
    animation: scaleIn 0.5s ease-out;
}

.exitoso .icono-resultado i {
    color: #48bb78;
}

.fallido .icono-resultado i {
    color: #f56565;
}

.pendiente .icono-resultado i {
    color: #ed850f;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

/* Título */
.contenido-resultado h1 {
    color: #e2e8f0;
    font-size: 2rem;
    margin-bottom: 2rem;
    font-weight: 700;
}

/* Información de la orden */
.info-orden {
    background: #1a202c;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid #4a5568;
}

.info-orden p {
    color: #a0aec0;
    font-size: 1rem;
    margin: 0.3rem 0;
}

.numero-orden {
    color: #ed850f !important;
    font-size: 1.5rem !important;
    font-weight: 700;
    margin: 0.5rem 0 !important;
    letter-spacing: 1px;
}

/* Mensaje de email */
.mensaje-email {
    background: #1a202c;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2.5rem;
    border: 1px solid #4a5568;
}

.mensaje-email i {
    font-size: 2rem;
    color: #ed850f;
    margin-bottom: 1rem;
}

.mensaje-email p {
    color: #a0aec0;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

/* Botón */
.acciones-resultado {
    margin-top: 0;
}

.btn-volver-menu,
.btn-reintentar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    width: 80%;
}

.btn-volver-menu {
    background: #ed850f;
    color: white;
}

.btn-volver-menu:hover {
    background: #d67607;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237, 133, 15, 0.3);
}

.btn-reintentar {
    background: #ed850f;
    color: white;
    margin-bottom: 1rem;
}

.btn-reintentar:hover {
    background: #d67607;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237, 133, 15, 0.3);
}

/* Responsive */
@media (max-width: 600px) {
    .contenido-resultado {
        padding: 2rem 1.5rem;
    }
    
    .contenido-resultado h1 {
        font-size: 1.75rem;
    }
    
    .icono-resultado i {
        font-size: 4rem;
    }
    
    .numero-orden {
        font-size: 1.25rem !important;
    }
    
    .btn-volver-menu {
        padding: 0.875rem 2rem;
        font-size: 1rem;
    }
}
</style>