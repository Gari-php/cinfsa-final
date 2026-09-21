
<div class="caja-abrir-container">

    <a href="/" class="btn-logout" title="Cerrar sesión">
        <i class="fas fa-sign-out-alt"></i>
    </a>

    <div class="caja-card">
   
        <div class="caja-header">
            <div class="icon-container">
                <i class="fas fa-cash-register"></i>
            </div>
            <h1>Apertura de Caja</h1>
            <p>Inicia tu turno registrando el monto inicial</p>
        </div>

 
        <form method="POST"
              class="caja-form"
              id="form-container" 
              action="/vendedor/caja/abrir"
              data-fetch="true"
              data-alerta=".contenedor-alertas"
              data-redirigir="/vendedor/caja/estado">
            
          
            <div class="info-usuario">
                <i class="fas fa-user-circle"></i>
                <div>
                    <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong>
                    <small><?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>

            <div class="campo form-group">
                <label for="rela_caja">
                    <i class="fas fa-store"></i> Seleccionar Caja
                </label>
                <select id="rela_caja" name="rela_caja" required>
                <option value="">Elegir caja...</option>
                <?php foreach ($cajas as $caja): ?>
                    <option 
                        value="<?php echo $caja['id_caja']; ?>"
                        <?php echo $caja['usuario_usando'] ? 'disabled' : ''; ?>
                        data-ocupada="<?php echo $caja['usuario_usando'] ? 'true' : 'false'; ?>"
                    >
                        <?php echo $caja['codigo_caja']; ?> - <?php echo $caja['nombre_caja']; ?>
                        <?php if ($caja['usuario_usando']): ?>
                            (EN USO - <?php echo $caja['usuario_usando_nombre']; ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
                <small>Selecciona la caja física que vas a utilizar</small>
            </div>

            <div class="campo form-group">
                <label for="monto_inicial">
                    <i class="fas fa-money-bill-wave"></i> Monto Inicial
                </label>
                <input 
                    type="number" 
                    id="monto_inicial" 
                    name="monto_inicial" 
                    step="0.01" 
                    min="0" 
                    value="0"
                    placeholder="0.00"
                    required
                >
                <small>Ingresa el efectivo con el que inicias la caja</small>
            </div>

     
            <div class="form-actions">
                <button type="submit" class="btn-abrir boton" id="btn-abrir">
                    <i class="fas fa-lock-open"></i>
                    Abrir Caja
                </button>
            </div>
        </form>
    </div>
</div>

<style>

.caja-abrir-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
}


.btn-logout {
    position: fixed;
    top: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: rgba(220, 38, 38, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
    z-index: 1000;
}

.btn-logout:hover {
    background: rgba(220, 38, 38, 1);
    transform: scale(1.1) rotate(10deg);
    box-shadow: 0 6px 25px rgba(220, 38, 38, 0.5);
}


.caja-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 100%;
    overflow: hidden;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header */
.caja-header {
    background: linear-gradient(135deg, #ed850f 0%, #d67607 100%);
    color: #fff;
    padding: 3rem 2rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.caja-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.icon-container {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    position: relative;
    z-index: 1;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
    }
}

.icon-container i {
    font-size: 2.5rem;
}

.caja-header h1 {
    font-size: 2rem;
    margin: 0 0 0.5rem 0;
    position: relative;
    z-index: 1;
}

.caja-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
    position: relative;
    z-index: 1;
}

/* Formulario */
.caja-form {
    padding: 2rem;
}

.info-usuario {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #fff5eb 0%, #ffe8d1 100%);
    border: 2px solid #fed7aa;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.info-usuario i {
    font-size: 2.5rem;
    color: #ed850f;
}

.info-usuario strong {
    display: block;
    font-size: 1.1rem;
    color: #1e293b;
}

.info-usuario small {
    display: block;
    color: #64748b;
    font-size: 0.9rem;
}

.campo,
.form-group {
    margin-bottom: 1.5rem;
}

.campo label,
.form-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.campo label i,
.form-group label i {
    color: #ed850f;
    font-size: 1.1rem;
}

.campo input,
.campo select,
.form-group input,
.form-group select {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fff;
}

.campo input:focus,
.campo select:focus,
.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #ed850f;
    box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.1);
}

.campo select,
.form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ed850f' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

.campo small,
.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #64748b;
    font-size: 0.85rem;
}

.form-actions {
    margin-top: 2rem;
}

.btn-abrir,
.boton {
    width: 100%;
    padding: 1.1rem;
    background: linear-gradient(135deg, #ed850f 0%, #d67607 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    box-shadow: 0 4px 15px rgba(237, 133, 15, 0.3);
}

.btn-abrir:hover,
.boton:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(237, 133, 15, 0.4);
    background: linear-gradient(135deg, #f59e0b 0%, #ed850f 100%);
}

.btn-abrir:active,
.boton:active {
    transform: translateY(0);
}

.btn-abrir:disabled,
.boton:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-abrir i {
    font-size: 1.2rem;
}

.campo select option:disabled,
.form-group select option:disabled {
    color: #94a3b8;
    font-style: italic;
    background-color: #f1f5f9;
}


.campo select option[data-ocupada="true"],
.form-group select option[data-ocupada="true"] {
    background-color: #fee2e2;
}

/* Responsive */
@media (max-width: 768px) {
    .caja-abrir-container {
        padding: 1rem;
    }

    .btn-logout {
        top: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }

    .caja-card {
        max-width: 100%;
    }

    .caja-header {
        padding: 2rem 1.5rem 1.5rem;
    }

    .icon-container {
        width: 60px;
        height: 60px;
    }

    .icon-container i {
        font-size: 2rem;
    }

    .caja-header h1 {
        font-size: 1.5rem;
    }

    .caja-form {
        padding: 1.5rem;
    }

    .info-usuario {
        flex-direction: column;
        text-align: center;
    }
}
</style>
