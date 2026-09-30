<div class="checkout-container">
    <div class="checkout-header">
        <h1>Finalizar Compra</h1>
        <p>Revisa tu pedido antes de proceder al pago</p>
    </div>

    <div class="checkout-content">
        <!-- Resumen de productos -->
        <div class="checkout-items">
            <h2>Tu pedido</h2>
            
            <?php foreach ($items as $item): ?>
                <?php if ($item['disponible']): ?>
                <div class="checkout-item">
                    <img src="<?php echo $item['imagen']; ?>" 
                         alt="<?php echo $item['nombre']; ?>"
                         onerror="this.src='/assets/img/cart.png'">
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                        <p class="item-cantidad">Cantidad: <?php echo $item['cantidad']; ?></p>
                        <p class="item-precio">$<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="checkout-total">
                <h3>Total a pagar</h3>
                <p class="total-amount">$<?php echo number_format($total, 0, ',', '.'); ?></p>
            </div>
        </div>

        <!-- Formulario de datos -->
        <div class="checkout-form">
            <h2>Tus datos</h2>
            
            <form id="checkoutForm">
                <div class="form-group">
                    <label>Nombre completo</label>
                    <input type="text" 
                           name="nombre" 
                           value="<?php echo $_SESSION['nombre_usuario'] ?? ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" 
                           name="email" 
                           value="<?php echo $_SESSION['email'] ?? ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" 
                           name="telefono" 
                           placeholder="+54 9 11 1234-5678" 
                           required>
                </div>

                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" 
                           name="dni" 
                           placeholder="12345678" 
                           required>
                </div>

                <div class="checkout-actions">
                    <a href="/menu" class="btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver al inicio
                    </a>
                    <button type="submit" class="btn-pagar">
                        <i class="fas fa-credit-card"></i> Proceder al pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btnPagar = this.querySelector('.btn-pagar');
    const textoOriginal = btnPagar.innerHTML;
    btnPagar.disabled = true;
    btnPagar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    
    const formData = new FormData(this);
    const datos = Object.fromEntries(formData);
    
    try {
        const response = await fetch('/pago/crear-orden', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La respuesta no es JSON válido');
        }
        
        const data = await response.json();
        
        if (data.ok && data.init_point) {
            window.location.href = data.init_point;
        } else {
            alert(data.mensaje || 'Error desconocido al procesar el pago');
            btnPagar.disabled = false;
            btnPagar.innerHTML = textoOriginal;
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al procesar el pago: ' + error.message);
        btnPagar.disabled = false;
        btnPagar.innerHTML = textoOriginal;
    }
});
</script>


<style>
    /* Checkout Container */
.checkout-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
    color: #e2e8f0;
}

.checkout-header {
    text-align: center;
    margin-bottom: 3rem;
}

.checkout-header h1 {
    color: #ed850f;
    font-size: 2.5rem !important;
    margin-bottom: 0.5rem;
}

.checkout-header p {
    color: #a0aec0;
    font-size: 1.1rem !important;
}

/* Checkout Content - Grid de 2 columnas */
.checkout-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

/* Lista de productos */
.checkout-items {
    background: #2d3748;
    border-radius: 12px;
    padding: 2rem;
    border: 2px solid #4a5568;
}

.checkout-items h2 {
    color: #ed850f;
    font-size: 1.5rem !important;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #ed850f;
    padding-bottom: 0.5rem;
}

.checkout-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #1a202c;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #4a5568;
}

.checkout-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.item-info {
    flex: 1;
}

.item-info h3 {
    color: #e2e8f0;
    font-size: 1rem !important;
    margin-bottom: 0.5rem;
}

.item-cantidad {
    color: #a0aec0;
    font-size: 0.9rem !important;
    margin: 0.3rem 0;
}

.item-precio {
    color: #ed850f;
    font-weight: bold;
    font-size: 1.1rem !important;
    margin: 0;
}

.checkout-total {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #ed850f;
    text-align: right;
}

.checkout-total h3 {
    color: #e2e8f0;
    font-size: 1.2rem !important;
    margin-bottom: 0.5rem;
}

.total-amount {
    color: #ed850f;
    font-size: 2rem !important;
    font-weight: bold;
    margin: 0;
}

/* Formulario */
.checkout-form {
    background: #2d3748;
    border-radius: 12px;
    padding: 2rem;
    border: 2px solid #4a5568;
}

.checkout-form h2 {
    color: #ed850f;
    font-size: 1.5rem !important;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #ed850f;
    padding-bottom: 0.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    color: #e2e8f0;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.95rem !important;
}

.form-group input {
    width: 100%;
    padding: 0.8rem;
    background: #1a202c;
    border: 2px solid #4a5568;
    border-radius: 8px;
    color: #e2e8f0;
    font-size: 1rem !important;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #ed850f;
    background: #2d3748;
}

.form-group input::placeholder {
    color: #6b7280;
}

/* Acciones del checkout */
.checkout-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-volver,
.btn-pagar {
    flex: 1;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1rem !important;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-volver {
    background: #6b7280;
    color: white;
}

.btn-volver:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.btn-pagar {
    background: #ed850f;
    color: white;
}

.btn-pagar:hover {
    background: #d67607;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237, 133, 15, 0.3);
}

/* Responsive */
@media (max-width: 968px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
    
    .checkout-container {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .checkout-header h1 {
        font-size: 2rem !important;
    }
    
    .checkout-item {
        flex-direction: column;
        text-align: center;
    }
    
    .checkout-item img {
        width: 100%;
        height: 150px;
    }
    
    .checkout-actions {
        flex-direction: column;
    }
}
</style>