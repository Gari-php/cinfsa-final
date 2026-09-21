<div class="dashboard-vendedor-productos">
    <div class="header-vendedor">
        <div class="info-caja-header">
            <div class="icono-caja">
                <i class="fas fa-cash-register"></i>
            </div>
            <div class="datos-caja">
                <h1>VENTA DE PRODUCTOS Y FICHAS</h1>
                <p class="nombre-caja"><?php echo $arqueo->nombre_caja ?? 'Caja Productos'; ?></p>
                <p class="numero-caja">Caja #<?php echo $arqueo->numero_caja ?? 'N/A'; ?></p>
            </div>
        </div>

        <div class="acciones-header">
            <a href="/vendedorproductos/caja/estado" class="btn-header">
                <i class="fas fa-arrow-left"></i> Volver a Caja
            </a>
        </div>
    </div>

    <div class="contenedor-flex">
        <!-- Formulario de Venta -->
        <div class="form-venta">
            <!-- Selector de Tipo -->
            <div class="selector-tipo">
                <button class="btn-tipo active" data-tipo="producto" id="btn-productos">
                    <i class="fas fa-box"></i> Productos
                </button>
                <button class="btn-tipo" data-tipo="ficha" id="btn-fichas">
                    <i class="fas fa-ticket-alt"></i> Fichas
                </button>
            </div>

            <!-- Formulario Productos -->
            <div class="fila" id="form-productos">
                <div class="input-wrapper">
                    <input type="text" id="busqueda" placeholder="Buscar producto por código o nombre">
                    <div class="sugerencias" id="sugerencias"></div>
                </div>

                <div class="fila-nombre">
                    <input type="hidden" id="id_producto_cantina">
                    <input type="hidden" id="id_stock">
                    <input type="hidden" id="stock_disponible_producto">
                    <input type="text" id="nombre" placeholder="Nombre del producto" readonly>
                </div>

                <div class="fila-campos">
                    <input type="number" id="cantidad" placeholder="Cantidad" min="1" value="1">
                    <input type="number" id="precio" placeholder="Precio" readonly>
                    <input type="text" id="subtotal" placeholder="Subtotal" readonly>
                    <button id="btnAgregar" class="btn-agregar">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </div>
            </div>

            <!-- Formulario Fichas -->
            <div class="fila" id="form-fichas" style="display:none;">
                <div class="input-wrapper">
                    <select id="select_fichas" class="select-fichas">
                        <option value="">Seleccione una ficha...</option>
                    </select>
                </div>

                <div class="fila-nombre">
                    <input type="hidden" id="id_ficha">
                    <input type="text" id="info_ficha" placeholder="Información de la ficha" readonly class="input-readonly">
                </div>

                <div class="fila-campos">
                    <input type="number" id="cantidad_ficha" placeholder="Cantidad" min="1" value="1" class="input-field">
                    <input type="number" id="precio_ficha" placeholder="Precio" readonly class="input-field">
                    <input type="text" id="subtotal_ficha" placeholder="Subtotal" readonly class="input-field">
                    <button id="btnAgregarFicha" class="btn-agregar">
                        <i class="fas fa-plus"></i> Agregar Ficha
                    </button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody id="tablaProductos"></tbody>
            </table>

            <div class="total-section">
                <h2>Total: $<span id="totalGeneral">0.00</span></h2>
                <button class="btn-completar" id="btnCompletar">
                    <i class="fas fa-check-circle"></i> Completar Venta
                </button>
            </div>
        </div>

        <!-- Control de Caja -->
        <div class="card">
            <h2><i class="fas fa-cash-register"></i> Control de Caja</h2>
            <div class="caja-card">
                <div class="totales">
                    <div>
                        <div class="small muted">Monto inicial</div>
                        <div id="monto_inicial_display">$<?php echo number_format($arqueo->monto_inicial, 0, ',', '.'); ?></div>
                    </div>
                    <div>
                        <div class="small muted">Saldo esperado</div>
                        <div id="saldo_esperado">$0.00</div>
                    </div>
                </div>

                <a href="/vendedorproductos/movimientos">
                    <button class="btn-ghost">
                        <i class="fas fa-exchange-alt"></i> Gestionar Movimientos
                    </button>
                </a>

                <div class="movimientos" id="movimientos_list">
                    <p class="cargando">Cargando movimientos...</p>
                </div>

                <button class="btn-primary" id="btn-cerrar">
                    <i class="fas fa-lock"></i> Cerrar Caja
                </button>
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

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    .dashboard-vendedor-productos {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
        padding: 2rem;
    }

    .header-vendedor {
        background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
        border-radius: 20px;
        padding: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }

    .info-caja-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        color: var(--color-texto-claro);
    }

    .icono-caja {
        font-size: 4rem;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .datos-caja h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }

    .datos-caja p {
        margin: 0.2rem 0;
        opacity: 0.9;
    }

    .btn-header {
        background: rgba(255, 255, 255, 0.2);
        color: var(--color-texto-claro);
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .btn-header:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .contenedor-flex {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
    }

    .form-venta {
        background: var(--color-fondo-gris);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }

    /* Selector de Tipo */
    .selector-tipo {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .btn-tipo {
        padding: 1rem;
        background: var(--color-fondo-oscuro);
        color: rgba(255, 255, 255, 0.6);
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-tipo:hover {
        border-color: var(--color-principal);
        color: var(--color-principal);
    }

    .btn-tipo.active {
        background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
        color: var(--color-texto-claro);
        border-color: var(--color-principal);
        box-shadow: 0 5px 15px rgba(237, 133, 15, 0.4);
    }

    .fila {
        margin-bottom: 2rem;
    }

    .input-wrapper {
        position: relative;
        margin-bottom: 1.5rem;
    }

    #busqueda,
    .select-fichas {
        width: 100%;
        padding: 1rem;
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        font-size: 1.1rem;
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
        transition: all 0.3s ease;
    }

    #busqueda:focus,
    .select-fichas:focus {
        outline: none;
        border-color: var(--color-principal);
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    .select-fichas option {
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
    }

    .sugerencias {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--color-fondo-oscuro);
        border: 2px solid var(--color-principal);
        border-radius: 10px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        margin-top: 5px;
    }

    .sugerencia-item {
        padding: 1rem;
        cursor: pointer;
        border-bottom: 1px solid var(--color-hover-gris);
        color: var(--color-texto-claro);
        transition: all 0.3s ease;
    }

    .sugerencia-item:hover {
        background: var(--color-hover-gris);
    }

    .sugerencia-item strong {
        color: var(--color-principal);
        display: block;
        margin-bottom: 0.25rem;
    }

    .sugerencia-item small {
        color: rgba(255, 255, 255, 0.7);
    }

    .fila-nombre {
        margin-bottom: 1.5rem;
    }

    #nombre,
    .input-readonly {
        width: 100%;
        padding: 1rem;
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        font-size: 1.1rem;
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
    }

    .fila-campos {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1.5fr;
        gap: 1rem;
    }

    .fila-campos input,
    .fila-campos button,
    .input-field {
        padding: 1rem;
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        font-size: 1rem;
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
    }

    .fila-campos input:focus,
    .input-field:focus {
        outline: none;
        border-color: var(--color-principal);
    }

    .btn-agregar {
        background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
        color: var(--color-texto-claro);
        border: none !important;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-agregar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(237, 133, 15, 0.4);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
    }

    thead th {
        background: var(--color-fondo-oscuro);
        padding: 1rem;
        text-align: left;
        color: var(--color-texto-claro);
        font-weight: 600;
        border-bottom: 2px solid var(--color-principal);
    }

    tbody td {
        padding: 1rem;
        border-bottom: 1px solid var(--color-hover-gris);
        color: var(--color-texto-claro);
    }

    tbody tr:hover {
        background: var(--color-hover-gris);
    }

    .badge-tipo {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .badge-producto {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        border: 1px solid #22c55e;
    }

    .badge-ficha {
        background: rgba(59, 130, 246, 0.2);
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }

    .btn-eliminar {
        background: rgba(239, 68, 68, 0.2);
        border: 1px solid #ef4444;
        color: #fca5a5;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-eliminar:hover {
        background: rgba(239, 68, 68, 0.3);
        transform: scale(1.05);
    }

    .total-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        background: var(--color-fondo-oscuro);
        border-radius: 15px;
        border: 2px solid var(--color-principal);
    }

    .total-section h2 {
        color: var(--color-texto-claro);
        margin: 0;
        font-size: 2rem;
    }

    .total-section h2 span {
        color: var(--color-principal);
    }

    .btn-completar {
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: var(--color-texto-claro);
        border: none;
        border-radius: 12px;
        font-size: 1.2rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
    }

    .btn-completar:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    }

    .card {
        background: var(--color-fondo-gris);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }

    .card h2 {
        color: var(--color-texto-claro);
        margin: 0 0 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.5rem;
    }

    .caja-card {
        background: var(--color-fondo-oscuro);
        border-radius: 15px;
        padding: 1.5rem;
    }

    .totales {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--color-hover-gris);
    }

    .totales>div {
        text-align: center;
    }

    .small {
        font-size: 0.85rem;
        display: block;
        margin-bottom: 0.5rem;
    }

    .muted {
        color: rgba(255, 255, 255, 0.6);
    }

    #monto_inicial_display,
    #saldo_esperado {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--color-principal);
    }

    .btn-ghost {
        width: 100%;
        padding: 0.75rem;
        background: transparent;
        border: 2px solid var(--color-principal);
        color: var(--color-principal);
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        text-decoration: none;
    }

    .btn-ghost:hover {
        background: rgba(237, 133, 15, 0.1);
        transform: translateY(-2px);
    }

    .movimientos {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
    }

    .cargando {
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
        padding: 2rem;
    }

    .btn-primary {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        color: var(--color-texto-claro);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
    }

    @media (max-width: 1200px) {
        .contenedor-flex {
            grid-template-columns: 1fr;
        }

        .fila-campos {
            grid-template-columns: 1fr 1fr;
        }

        .btn-agregar {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 768px) {
        .dashboard-vendedor-productos {
            padding: 1rem;
        }

        .header-vendedor {
            flex-direction: column;
            gap: 1rem;
        }

        .fila-campos {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    console.log('✅ Script cargando...');

    // ==================== VARIABLES GLOBALES ====================
    const busqueda = document.getElementById('busqueda');
    const sugerencias = document.getElementById('sugerencias');
    const id_producto_cantina = document.getElementById('id_producto_cantina');
    const id_stock = document.getElementById('id_stock');
    const nombre = document.getElementById('nombre');
    const cantidad = document.getElementById('cantidad');
    const precio = document.getElementById('precio');
    const subtotal = document.getElementById('subtotal');
    const btnAgregar = document.getElementById('btnAgregar');


    const select_fichas = document.getElementById('select_fichas');
    const id_ficha = document.getElementById('id_ficha');
    const info_ficha = document.getElementById('info_ficha');
    const cantidad_ficha = document.getElementById('cantidad_ficha');
    const precio_ficha = document.getElementById('precio_ficha');
    const subtotal_ficha = document.getElementById('subtotal_ficha');
    const btnAgregarFicha = document.getElementById('btnAgregarFicha');
    const stock_disponible_producto = document.getElementById('stock_disponible_producto');

    const tablaProductos = document.getElementById('tablaProductos');
    const totalGeneral = document.getElementById('totalGeneral');
    const btnCompletar = document.getElementById('btnCompletar');

    let items = []; // Array unificado para productos y fichas
    let contador = 0;
    let timeoutBusqueda;
    let fichasDisponibles = [];

    // ==================== SELECTOR DE TIPO ====================
    const btnProductos = document.getElementById('btn-productos');
    const btnFichas = document.getElementById('btn-fichas');
    const formProductos = document.getElementById('form-productos');
    const formFichas = document.getElementById('form-fichas');

    btnProductos.addEventListener('click', () => {
        btnProductos.classList.add('active');
        btnFichas.classList.remove('active');
        formProductos.style.display = 'block';
        formFichas.style.display = 'none';
        limpiarCamposProductos();
    });

    btnFichas.addEventListener('click', () => {
        btnFichas.classList.add('active');
        btnProductos.classList.remove('active');
        formFichas.style.display = 'block';
        formProductos.style.display = 'none';
        cargarFichas();
        limpiarCamposFichas();
    });

    // ==================== BÚSQUEDA DE PRODUCTOS ====================
    busqueda.addEventListener('input', () => {
        clearTimeout(timeoutBusqueda);
        const termino = busqueda.value.trim();

        if (termino.length < 2) {
            sugerencias.style.display = 'none';
            return;
        }

        timeoutBusqueda = setTimeout(() => {
            buscarProducto(termino);
        }, 300);
    });

    async function buscarProducto(termino) {
        try {
            const response = await fetch('/vendedorproductos/buscar-producto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    busqueda: termino
                })
            });

            const data = await response.json();

            if (data.ok && data.productos.length > 0) {
                mostrarSugerencias(data.productos);
            } else {
                sugerencias.innerHTML = '<div class="sugerencia-item">No se encontraron productos</div>';
                sugerencias.style.display = 'block';
            }
        } catch (error) {
            console.error('Error al buscar producto:', error);
            mostrarAlerta('Error al buscar producto', 'error');
        }
    }

    function mostrarSugerencias(productos) {
        sugerencias.innerHTML = '';

        productos.forEach(prod => {
            const div = document.createElement('div');
            div.className = 'sugerencia-item';
            div.innerHTML = `
            <strong>${prod.nombre_producto_cantina}</strong><br>
            <small>Código: ${prod.codigo || 'N/A'} | Precio: ${parseFloat(prod.precio_producto).toFixed(0)} | Stock: ${prod.stock_cantina}</small>
        `;

            div.addEventListener('click', () => {
                seleccionarProducto(prod);
            });

            sugerencias.appendChild(div);
        });

        sugerencias.style.display = 'block';
    }

    function seleccionarProducto(prod) {
        id_producto_cantina.value = prod.id_producto_cantina;
        id_stock.value = prod.id_stock_cantina;
        busqueda.value = prod.codigo || prod.nombre_producto_cantina;
        nombre.value = prod.nombre_producto_cantina;
        precio.value = parseFloat(prod.precio_producto).toFixed(2);
        stock_disponible_producto.value = prod.stock_cantina; // NUEVO

        sugerencias.style.display = 'none';
        calcularSubtotal();
        cantidad.focus();
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.input-wrapper')) {
            sugerencias.style.display = 'none';
        }
    });

    cantidad.addEventListener('input', () => {
        calcularSubtotal();
        validarStockProducto();
    });

    function validarStockProducto() {
        const cant = parseInt(cantidad.value) || 0;
        if (!id_producto_cantina.value) return true;

        const stockDisp = parseInt(stock_disponible_producto.value) || 0; // NUEVO

        const cantidadEnCarrito = items
            .filter(item => item.tipo === 'producto' && item.id_producto_cantina === id_producto_cantina.value)
            .reduce((acc, item) => acc + item.cantidad, 0);

        const totalSolicitado = cant + cantidadEnCarrito;

        if (totalSolicitado > stockDisp) {
            mostrarAlerta(
                `Stock insuficiente. Disponible: ${stockDisp}` +
                (cantidadEnCarrito > 0 ? ` (ya tenés ${cantidadEnCarrito} en el carrito)` : ''),
                'error'
            );
            cantidad.style.borderColor = '#ef4444';
            return false;
        }

        cantidad.style.borderColor = '';
        return true;
    }

    function calcularSubtotal() {
        const cant = parseFloat(cantidad.value) || 0;
        const prec = parseFloat(precio.value) || 0;
        subtotal.value = (cant * prec).toFixed(2);
    }

    // ==================== AGREGAR PRODUCTO ====================
    btnAgregar.addEventListener('click', () => {
        if (!id_producto_cantina.value || !nombre.value || !cantidad.value || !precio.value) {
            mostrarAlerta('Por favor, complete todos los campos', 'error');
            return;
        }

        const cant = parseFloat(cantidad.value);
        const prec = parseFloat(precio.value);

        if (isNaN(cant) || cant <= 0) {
            mostrarAlerta('La cantidad debe ser mayor a 0', 'error');
            return;
        }

        if (isNaN(prec) || prec <= 0) {
            mostrarAlerta('El precio debe ser mayor a 0', 'error');
            return;
        }

        if (!validarStockProducto()) {
            return;
        }

        contador++;
        items.push({
            num: contador,
            tipo: 'producto',
            id_producto_cantina: id_producto_cantina.value,
            id_stock: id_stock.value,
            codigo: busqueda.value,
            nombre: nombre.value,
            cantidad: cant,
            precio: prec,
            total: parseFloat(subtotal.value)
        });

        renderTabla();
        limpiarCamposProductos();
    });

    // ==================== CARGAR Y GESTIONAR FICHAS ====================
    async function cargarFichas() {
        if (fichasDisponibles.length > 0) return;

        try {
            const response = await fetch('/vendedorproductos/buscar-ficha', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    busqueda: 'all'
                })
            });

            const data = await response.json();

            if (data.ok && data.fichas.length > 0) {
                fichasDisponibles = data.fichas;
                select_fichas.innerHTML = '<option value="">Seleccione una ficha...</option>';

                data.fichas.forEach(ficha => {
                    const option = document.createElement('option');
                    option.value = ficha.id_fichas;
                    const displayName = ficha.nombre_ficha || `Ficha $${parseFloat(ficha.precio_ficha).toFixed(0)}`;
                    option.textContent = `${displayName} - Stock: ${ficha.cantidad_ficha}`;
                    option.dataset.precio = ficha.precio_ficha;
                    option.dataset.stock = ficha.cantidad_ficha;
                    option.dataset.nombre = ficha.nombre_ficha;
                    select_fichas.appendChild(option);
                });
            } else {
                select_fichas.innerHTML = '<option value="">No hay fichas disponibles</option>';
            }
        } catch (error) {
            console.error('Error al cargar fichas:', error);
            mostrarAlerta('Error al cargar fichas', 'error');
        }
    }

    // ✅ EVENTO CHANGE DEL SELECT (esto es lo que faltaba)
    select_fichas.addEventListener('change', () => {
        const selectedOption = select_fichas.options[select_fichas.selectedIndex];

        if (!selectedOption.value) {
            limpiarCamposFichas();
            return;
        }

        // Llenar los campos con los datos de la ficha seleccionada
        id_ficha.value = selectedOption.value;
        precio_ficha.value = parseFloat(selectedOption.dataset.precio).toFixed(2);

        const displayName = selectedOption.dataset.nombre || `Ficha $${parseFloat(selectedOption.dataset.precio).toFixed(0)}`;
        info_ficha.value = `${displayName} - Stock disponible: ${selectedOption.dataset.stock}`;

        calcularSubtotalFicha();
        cantidad_ficha.focus();
    });

    // Calcular subtotal cuando cambie la cantidad
    cantidad_ficha.addEventListener('input', calcularSubtotalFicha);

    function calcularSubtotalFicha() {
        const cant = parseFloat(cantidad_ficha.value) || 0;
        const prec = parseFloat(precio_ficha.value) || 0;
        subtotal_ficha.value = (cant * prec).toFixed(2);
    }

    // ==================== AGREGAR FICHA ====================
    btnAgregarFicha.addEventListener('click', () => {
        if (!id_ficha.value || !precio_ficha.value || !cantidad_ficha.value) {
            mostrarAlerta('Por favor, seleccione una ficha y la cantidad', 'error');
            return;
        }

        const cant = parseFloat(cantidad_ficha.value);
        const prec = parseFloat(precio_ficha.value);
        const selectedOption = select_fichas.options[select_fichas.selectedIndex];
        const stockDisponible = parseInt(selectedOption.dataset.stock);
        const nombreFichaDisplay = selectedOption.dataset.nombre || `Ficha $${prec.toFixed(0)}`;

        if (isNaN(cant) || cant <= 0) {
            mostrarAlerta('La cantidad debe ser mayor a 0', 'error');
            return;
        }

        if (cant > stockDisponible) {
            mostrarAlerta(`Stock insuficiente. Disponible: ${stockDisponible}`, 'error');
            return;
        }

        contador++;
        items.push({
            num: contador,
            tipo: 'ficha',
            id_fichas: id_ficha.value,
            nombre: nombreFichaDisplay,
            nombre_ficha: nombreFichaDisplay,
            cantidad: cant,
            precio: prec,
            total: parseFloat(subtotal_ficha.value)
        });

        renderTabla();
        limpiarCamposFichas();
    });

    // ==================== RENDERIZAR TABLA ====================
    function renderTabla() {
        tablaProductos.innerHTML = '';
        let total = 0;

        items.forEach((item, index) => {
            total += item.total;

            const tipoBadge = item.tipo === 'producto' ?
                '<span class="badge-tipo badge-producto">Producto</span>' :
                '<span class="badge-tipo badge-ficha">Ficha</span>';

            tablaProductos.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${item.num}</td>
                <td>${tipoBadge}</td>
                <td>${item.nombre}</td>
                <td>${item.precio.toFixed(2)}</td>
                <td>${item.cantidad}</td>
                <td>${item.total.toFixed(2)}</td>
                <td>
                    <button class="btn-eliminar" onclick="eliminarItem(${index})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
        });

        totalGeneral.textContent = total.toFixed(2);
    }

    window.eliminarItem = function(index) {
        if (confirm('¿Eliminar este item?')) {
            items.splice(index, 1);
            renderTabla();
            mostrarAlerta('Item eliminado', 'info');
        }
    };

    // ==================== LIMPIAR CAMPOS ====================
    function limpiarCamposProductos() {
        busqueda.value = '';
        id_producto_cantina.value = '';
        id_stock.value = '';
        stock_disponible_producto.value = ''; // reemplaza a "stockDisponibleProducto = 0;"
        nombre.value = '';
        cantidad.value = '1';
        precio.value = '';
        subtotal.value = '';
        busqueda.focus();
    }

    function limpiarCamposFichas() {
        select_fichas.value = '';
        id_ficha.value = '';
        info_ficha.value = '';
        cantidad_ficha.value = '1';
        precio_ficha.value = '';
        subtotal_ficha.value = '';
    }

    // ==================== COMPLETAR VENTA CON MODAL FACTURACIÓN ====================
    btnCompletar.addEventListener('click', async () => {
        if (items.length === 0) {
            mostrarAlerta('No hay items en la venta', 'error');
            return;
        }

        try {
            console.log('📡 Obteniendo datos para la venta...');

            const responseMet = await fetch('/vendedorproductos/obtener-metodos-pago', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const dataMet = await responseMet.json();

            if (!dataMet.ok) {
                throw new Error(dataMet.mensaje || 'Error al obtener métodos de pago');
            }

            const responseTipo = await fetch('/vendedorproductos/obtener-tipos-comprobante', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const dataTipo = await responseTipo.json();

            if (!dataTipo.ok) {
                throw new Error(dataTipo.mensaje || 'Error al obtener tipos de comprobante');
            }

            let opcionesMetodosPago = '';
            dataMet.metodos.forEach(metodo => {
                const id = metodo.id_metodo_pago || metodo.id_tipo_pago;
                const nombre = metodo.nombre_metodo_pago || metodo.descripcion_tipo_pago;
                opcionesMetodosPago += `<option value="${id}">${nombre}</option>`;
            });

            let opcionesTiposComprobante = '';
            dataTipo.tipos.forEach(tipo => {
                opcionesTiposComprobante += `<option value="${tipo.codigo}" data-requiere-datos="${tipo.requiere_datos_facturacion}">${tipo.descripcion}</option>`;
            });

            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;animation:fadeIn 0.3s ease;overflow-y:auto;padding:20px';
            modal.innerHTML = `
            <div style="background:#2d3748;padding:30px;border-radius:20px;max-width:800px;width:95%;border:3px solid #ed850f;box-shadow:0 20px 60px rgba(0,0,0,0.5);animation:slideIn 0.3s ease;max-height:90vh;overflow-y:auto">
                <h3 style="margin:0 0 20px 0;color:#fff;font-size:1.5rem;display:flex;align-items:center;gap:10px">
                    <i class="fas fa-cash-register" style="color:#ed850f"></i>
                    Completar Venta
                </h3>
                
                <div style="background:#1a202c;padding:20px;border-radius:12px;margin-bottom:20px;border:2px solid #ed850f">
                    <div style="color:#fff;font-size:1.1rem;margin-bottom:5px">Total a cobrar:</div>
                    <div style="color:#ed850f;font-size:2rem;font-weight:bold">$${parseFloat(totalGeneral.textContent).toFixed(0)}</div>
                </div>
                
                <!-- Datos básicos -->
                <div style="margin-bottom:20px">
                    <label style="display:block;margin-bottom:8px;color:#fff;font-weight:600">
                        <i class="fas fa-credit-card"></i> Método de Pago:
                    </label>
                    <select id="metodo_pago_select" style="width:100%;padding:12px;font-size:15px;background:#1a202c;color:#fff;border:2px solid #4a5568;border-radius:10px;cursor:pointer;transition:all 0.3s ease">
                        ${opcionesMetodosPago}
                    </select>
                </div>
                
                <div style="margin-bottom:20px">
                    <label style="display:block;margin-bottom:8px;color:#fff;font-weight:600">
                        <i class="fas fa-file-invoice"></i> Tipo de Comprobante:
                    </label>
                    <select id="tipo_comprobante_select" style="width:100%;padding:12px;font-size:15px;background:#1a202c;color:#fff;border:2px solid #4a5568;border-radius:10px;cursor:pointer;transition:all 0.3s ease">
                        ${opcionesTiposComprobante}
                    </select>
                </div>

                <!-- Contenedor de datos de facturación -->
                <div id="datos_facturacion_container" style="display:none;background:#1a202c;padding:20px;border-radius:12px;margin-bottom:20px;border:2px solid #22c55e">
                    <h4 style="color:#22c55e;margin:0 0 15px 0;font-size:1.2rem">
                        <i class="fas fa-building"></i> Datos del Receptor (Factura A/B)
                    </h4>

                    <!-- Tipo de documento y número -->
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:15px;margin-bottom:15px">
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Tipo Doc. *</label>
                            <select id="tipo_documento" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                                <option value="DNI">DNI</option>
                                <option value="CUIT">CUIT</option>
                                <option value="CUIL">CUIL</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Número de Documento *</label>
                            <input type="text" id="numero_documento" placeholder="Sin guiones ni puntos" maxlength="20" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                            <small style="color:rgba(255,255,255,0.6);font-size:0.75rem">CUIT/CUIL: 11 dígitos (ej: 20345678901)</small>
                        </div>
                    </div>

                    <!-- Nombre/Razón Social -->
                    <div style="margin-bottom:15px">
                        <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Nombre Completo / Razón Social *</label>
                        <input type="text" id="razon_social" placeholder="Ej: Juan Pérez o Empresa SA" maxlength="255" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                    </div>

                    <!-- Domicilio -->
                    <div style="margin-bottom:15px">
                        <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Domicilio Fiscal</label>
                        <input type="text" id="domicilio" placeholder="Ej: Av. Corrientes 1234" maxlength="255" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                    </div>

                    <!-- Localidad, Provincia y CP -->
                    <div style="display:grid;grid-template-columns:1fr 1fr 0.7fr;gap:15px;margin-bottom:15px">
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Localidad</label>
                            <input type="text" id="localidad" placeholder="Ej: Buenos Aires" maxlength="100" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Provincia</label>
                            <input type="text" id="provincia" placeholder="Ej: Buenos Aires" maxlength="100" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">CP</label>
                            <input type="text" id="codigo_postal" placeholder="1000" maxlength="10" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                        </div>
                    </div>

                    <!-- Email y Teléfono -->
                    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:15px;margin-bottom:15px">
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Email (opcional)</label>
                            <input type="email" id="email_facturacion" placeholder="cliente@ejemplo.com" maxlength="250" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:5px;color:#fff;font-size:0.9rem">Teléfono</label>
                            <input type="tel" id="telefono_facturacion" placeholder="11 1234-5678" maxlength="50" style="width:100%;padding:10px;background:#2d3748;color:#fff;border:1px solid #4a5568;border-radius:8px">
                        </div>
                    </div>

                    <p style="color:rgba(255,255,255,0.6);font-size:0.85rem;margin:10px 0 0 0">
                        <i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios para Factura A y B
                    </p>
                </div>
                
                <div style="margin-bottom:20px">
                    <label style="display:block;margin-bottom:8px;color:#fff;font-weight:600">
                        <i class="fas fa-comment"></i> Observaciones (opcional):
                    </label>
                    <textarea id="observaciones_venta" placeholder="Notas adicionales sobre la venta" style="width:100%;padding:12px;font-size:14px;background:#1a202c;color:#fff;border:2px solid #4a5568;border-radius:10px;resize:vertical;min-height:80px;font-family:inherit" rows="3"></textarea>
                </div>
                
                <div style="display:flex;gap:12px;justify-content:flex-end">
                    <button id="btn-cancelar-venta" style="padding:12px 24px;background:#4a5568;color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;font-size:15px;transition:all 0.3s ease;display:flex;align-items:center;gap:8px">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button id="btn-confirmar-venta" style="padding:12px 24px;background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;font-size:15px;transition:all 0.3s ease;display:flex;align-items:center;gap:8px;box-shadow:0 4px 15px rgba(34,197,94,0.4)">
                        <i class="fas fa-check-circle"></i> Confirmar Venta
                    </button>
                </div>
            </div>
        `;

            const style = document.createElement('style');
            style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            #metodo_pago_select:hover,
            #tipo_comprobante_select:hover,
            #observaciones_venta:hover,
            #datos_facturacion_container input:hover,
            #datos_facturacion_container select:hover {
                border-color: #ed850f;
            }
            #metodo_pago_select:focus,
            #tipo_comprobante_select:focus,
            #observaciones_venta:focus,
            #datos_facturacion_container input:focus,
            #datos_facturacion_container select:focus {
                outline: none;
                border-color: #ed850f;
                box-shadow: 0 0 0 3px rgba(237,133,15,0.2);
            }
            #btn-cancelar-venta:hover {
                background: #5a6578;
                transform: translateY(-2px);
            }
            #btn-confirmar-venta:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(34,197,94,0.5);
            }
            #btn-confirmar-venta:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                transform: none;
            }
        `;
            document.head.appendChild(style);

            document.body.appendChild(modal);
            // ==================== VALIDACIÓN DE CAMPOS NUMÉRICOS ====================
            const numeroDocumentoInput = document.getElementById('numero_documento');
            const tipoDocumentoSelect = document.getElementById('tipo_documento');

            // Validar en tiempo real mientras se escribe
            numeroDocumentoInput.addEventListener('input', function(e) {
                // Remover cualquier caracter que no sea número
                const valorOriginal = e.target.value;
                const valorLimpio = valorOriginal.replace(/[^0-9]/g, '');

                // Si se detectaron caracteres no numéricos, mostrar alerta
                if (valorOriginal !== valorLimpio) {
                    mostrarAlerta('Solo se permiten números en el documento', 'error');
                    e.target.value = valorLimpio;
                }
            });

            // Validar también al perder el foco
            numeroDocumentoInput.addEventListener('blur', function(e) {
                const tipoDoc = tipoDocumentoSelect.value;
                const numeroDoc = e.target.value.replace(/[^0-9]/g, '');

                // Validar longitud según tipo de documento
                if (numeroDoc.length > 0) {
                    if (tipoDoc === 'DNI' && (numeroDoc.length < 7 || numeroDoc.length > 8)) {
                        mostrarAlerta('El DNI debe tener 7 u 8 dígitos', 'error');
                    } else if ((tipoDoc === 'CUIT' || tipoDoc === 'CUIL') && numeroDoc.length !== 11) {
                        mostrarAlerta('El CUIT/CUIL debe tener exactamente 11 dígitos', 'error');
                    }
                }

                e.target.value = numeroDoc;
            });

            // Prevenir pegado de texto no numérico
            numeroDocumentoInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                const numerosLimpios = pasteData.replace(/[^0-9]/g, '');

                if (pasteData !== numerosLimpios) {
                    mostrarAlerta('Solo se permiten números. Se eliminaron los caracteres no numéricos.', 'info');
                }

                e.target.value = numerosLimpios;
            });

            // Validar también el código postal para que solo acepte números
            const codigoPostalInput = document.getElementById('codigo_postal');
            if (codigoPostalInput) {
                codigoPostalInput.addEventListener('input', function(e) {
                    const valorOriginal = e.target.value;
                    const valorLimpio = valorOriginal.replace(/[^0-9]/g, '');

                    if (valorOriginal !== valorLimpio) {
                        mostrarAlerta('Solo se permiten números en el código postal', 'error');
                        e.target.value = valorLimpio;
                    }
                });
            }
            // ==================== VALIDACIÓN DE CAMPOS DE TEXTO Y CÓDIGO POSTAL ====================

            // Validar Nombre/Razón Social - solo letras, espacios y algunos caracteres permitidos
            const razonSocialInput = document.getElementById('razon_social');
            if (razonSocialInput) {
                razonSocialInput.addEventListener('input', function(e) {
                    const valorOriginal = e.target.value;
                    // Permitir letras (con acentos), espacios, puntos, comas y guiones
                    const valorLimpio = valorOriginal.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s.,\-]/g, '');

                    if (valorOriginal !== valorLimpio) {
                        mostrarAlerta('Solo se permiten letras en el nombre/razón social', 'error');
                        e.target.value = valorLimpio;
                    }
                });

                razonSocialInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                    const textoLimpio = pasteData.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s.,\-]/g, '');

                    if (pasteData !== textoLimpio) {
                        mostrarAlerta('Se eliminaron caracteres no permitidos del texto pegado', 'info');
                    }

                    e.target.value = textoLimpio;
                });
            }

            // Validar Localidad - solo letras y espacios
            const localidadInput = document.getElementById('localidad');
            if (localidadInput) {
                localidadInput.addEventListener('input', function(e) {
                    const valorOriginal = e.target.value;
                    // Permitir solo letras (con acentos), espacios y guiones
                    const valorLimpio = valorOriginal.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]/g, '');

                    if (valorOriginal !== valorLimpio) {
                        mostrarAlerta('Solo se permiten letras en la localidad', 'error');
                        e.target.value = valorLimpio;
                    }
                });

                localidadInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                    const textoLimpio = pasteData.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]/g, '');

                    if (pasteData !== textoLimpio) {
                        mostrarAlerta('Se eliminaron caracteres no permitidos del texto pegado', 'info');
                    }

                    e.target.value = textoLimpio;
                });
            }

            // Validar Provincia - solo letras y espacios
            const provinciaInput = document.getElementById('provincia');
            if (provinciaInput) {
                provinciaInput.addEventListener('input', function(e) {
                    const valorOriginal = e.target.value;
                    // Permitir solo letras (con acentos), espacios y guiones
                    const valorLimpio = valorOriginal.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]/g, '');

                    if (valorOriginal !== valorLimpio) {
                        mostrarAlerta('Solo se permiten letras en la provincia', 'error');
                        e.target.value = valorLimpio;
                    }
                });

                provinciaInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                    const textoLimpio = pasteData.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]/g, '');

                    if (pasteData !== textoLimpio) {
                        mostrarAlerta('Se eliminaron caracteres no permitidos del texto pegado', 'info');
                    }

                    e.target.value = textoLimpio;
                });
            }


            // ==================== MOSTRAR/OCULTAR DATOS DE FACTURACIÓN ====================
            const tipoComprobanteSelect = document.getElementById('tipo_comprobante_select');
            const datosFacturacionContainer = document.getElementById('datos_facturacion_container');

            function verificarTipoComprobante() {
                const selectedOption = tipoComprobanteSelect.options[tipoComprobanteSelect.selectedIndex];
                const requiereDatos = selectedOption.dataset.requiereDatos === '1';

                if (requiereDatos) {
                    datosFacturacionContainer.style.display = 'block';
                    datosFacturacionContainer.style.animation = 'slideIn 0.3s ease';
                } else {
                    datosFacturacionContainer.style.display = 'none';
                }
            }

            tipoComprobanteSelect.addEventListener('change', verificarTipoComprobante);

            // Verificar estado inicial después de que el modal esté en el DOM
            setTimeout(() => {
                verificarTipoComprobante();
            }, 100);

            // Verificar al cargar
            const firstOption = tipoComprobanteSelect.options[tipoComprobanteSelect.selectedIndex];
            if (firstOption && firstOption.dataset.requiereDatos === '1') {
                datosFacturacionContainer.style.display = 'block';
            }

            document.getElementById('btn-cancelar-venta').addEventListener('click', () => {
                document.body.removeChild(modal);
                document.head.removeChild(style);
            });

            const btnConfirmarVenta = document.getElementById('btn-confirmar-venta');
            btnConfirmarVenta.addEventListener('click', async () => {
                btnConfirmarVenta.disabled = true;
                btnConfirmarVenta.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                const metodo_pago = document.getElementById('metodo_pago_select').value;
                const tipo_comprobante = document.getElementById('tipo_comprobante_select').value;
                const observaciones = document.getElementById('observaciones_venta').value.trim();

                if (!metodo_pago) {
                    mostrarAlerta('Seleccione un método de pago', 'error');
                    btnConfirmarVenta.disabled = false;
                    btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                    return;
                }

                if (!tipo_comprobante) {
                    mostrarAlerta('Seleccione un tipo de comprobante', 'error');
                    btnConfirmarVenta.disabled = false;
                    btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                    return;
                }

                // Validar datos de facturación si es necesario
                const selectedOption = tipoComprobanteSelect.options[tipoComprobanteSelect.selectedIndex];
                const requiereDatos = selectedOption.dataset.requiereDatos === '1';

                let datosFacturacion = null;

                if (requiereDatos) {
                    const tipoDocumento = document.getElementById('tipo_documento').value;
                    const numeroDocumento = document.getElementById('numero_documento').value.trim();
                    const razonSocial = document.getElementById('razon_social').value.trim();
                    const domicilio = document.getElementById('domicilio').value.trim();
                    const localidad = document.getElementById('localidad').value.trim();
                    const provincia = document.getElementById('provincia').value.trim();
                    const codigoPostal = document.getElementById('codigo_postal').value.trim();
                    const email = document.getElementById('email_facturacion').value.trim();
                    const telefono = document.getElementById('telefono_facturacion').value.trim();

                    if (!numeroDocumento) {
                        mostrarAlerta('Complete el número de documento', 'error');
                        btnConfirmarVenta.disabled = false;
                        btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                        return;
                    }

                    if (!razonSocial) {
                        mostrarAlerta('Complete el nombre o razón social', 'error');
                        btnConfirmarVenta.disabled = false;
                        btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                        return;
                    }

                    // Validar longitud de CUIT/CUIL
                    if ((tipoDocumento === 'CUIT' || tipoDocumento === 'CUIL') && numeroDocumento.replace(/[^0-9]/g, '').length !== 11) {
                        mostrarAlerta('El CUIT/CUIL debe tener 11 dígitos', 'error');
                        btnConfirmarVenta.disabled = false;
                        btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                        return;
                    }

                    datosFacturacion = {
                        tipo_documento: tipoDocumento,
                        numero_documento: numeroDocumento,
                        razon_social: razonSocial,
                        domicilio: domicilio,
                        localidad: localidad,
                        provincia: provincia,
                        codigo_postal: codigoPostal,
                        email_facturacion: email,
                        telefono_facturacion: telefono
                    };
                }

                // Separar productos y fichas
                const productos = items.filter(item => item.tipo === 'producto').map(p => ({
                    id_producto_cantina: p.id_producto_cantina,
                    id_stock: p.id_stock,
                    cantidad: p.cantidad,
                    precio: p.precio,
                    total: p.total
                }));

                const fichas = items.filter(item => item.tipo === 'ficha').map(f => ({
                    id_fichas: f.id_fichas,
                    cantidad: f.cantidad,
                    precio: f.precio,
                    nombre_ficha: f.nombre_ficha,
                    total: f.total
                }));

                const datosVenta = {
                    productos: productos,
                    fichas: fichas,
                    metodo_pago: metodo_pago,
                    tipo_comprobante: tipo_comprobante,
                    observaciones: observaciones,
                    total: parseFloat(totalGeneral.textContent),
                    datos_facturacion: datosFacturacion
                };

                console.log('📤 Enviando venta:', datosVenta);

                try {
                    const response = await fetch('/vendedorproductos/completar-venta', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(datosVenta)
                    });

                    const data = await response.json();
                    console.log('📥 Respuesta:', data);

                    if (data.ok) {
                        mostrarAlerta('✅ ' + data.mensaje, 'exito');

                        if (data.ver_ticket) {
                            setTimeout(() => {
                                window.location.href = data.ver_ticket;
                            }, 500);
                        }

                        items = [];
                        renderTabla();
                        document.body.removeChild(modal);
                        document.head.removeChild(style);

                        await cargarMovimientos();

                        if (fichas.length > 0) {
                            fichasDisponibles = [];
                            await cargarFichas();
                        }
                    } else {
                        mostrarAlerta('❌ Error: ' + (data.mensaje || 'Error desconocido'), 'error');
                        btnConfirmarVenta.disabled = false;
                        btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                    }
                } catch (error) {
                    console.error('❌ Error:', error);
                    mostrarAlerta('Error al completar la venta: ' + error.message, 'error');
                    btnConfirmarVenta.disabled = false;
                    btnConfirmarVenta.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Venta';
                }
            });

        } catch (error) {
            console.error('❌ Error:', error);
            mostrarAlerta('Error: ' + error.message, 'error');
        }
    });

    // ==================== CONTROL DE CAJA ====================
    let saldoEsperadoCaja = 0;

    document.addEventListener('DOMContentLoaded', () => {
        cargarMovimientos();
    });

    async function cargarMovimientos() {
        try {
            const response = await fetch('/vendedorproductos/obtener-movimientos', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.ok) {
                saldoEsperadoCaja = parseFloat(data.saldo_esperado);
                document.getElementById('saldo_esperado').textContent = +saldoEsperadoCaja.toFixed(0);
                renderMovimientos(data.movimientos);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function renderMovimientos(movimientos) {
        const lista = document.getElementById('movimientos_list');
        if (!lista) return;

        if (!movimientos || movimientos.length === 0) {
            lista.innerHTML = '<p class="cargando">Sin movimientos</p>';
            return;
        }

        lista.innerHTML = '';
        movimientos.forEach(mov => {
            const signo = mov.tipo_movimiento === 'ingreso' ? '+' : '-';
            const color = mov.tipo_movimiento === 'ingreso' ? '#22c55e' : '#ef4444';
            const div = document.createElement('div');
            div.style.cssText = 'padding:8px;border-bottom:1px solid #4a5568;display:flex;justify-content:space-between';
            div.innerHTML = `
            <div>
                <div style="font-weight:500;color:#fff">${mov.concepto_movimiento}</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.6)">${new Date(mov.fecha_movimiento).toLocaleString('es-AR')}</div>
            </div>
            <div style="font-weight:600;color:${color}">${signo}${parseFloat(mov.monto).toFixed(0)}</div>
        `;
            lista.appendChild(div);
        });
    }

    document.getElementById('btn-cerrar').addEventListener('click', () => {
        window.location.href = '/vendedorproductos/caja/estado';
    });

    // ==================== SISTEMA DE ALERTAS ====================
    function mostrarAlerta(mensaje, tipo) {
        const alertas = {
            'exito': {
                color: '#22c55e',
                icono: 'fa-check-circle'
            },
            'error': {
                color: '#ef4444',
                icono: 'fa-times-circle'
            },
            'info': {
                color: '#3b82f6',
                icono: 'fa-info-circle'
            }
        };

        const config = alertas[tipo] || alertas['info'];

        const alerta = document.createElement('div');
        alerta.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${config.color};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;

        alerta.innerHTML = `
        <i class="fas ${config.icono}" style="font-size: 1.2rem"></i>
        <span>${mensaje}</span>
    `;

        const style = document.createElement('style');
        style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
        document.head.appendChild(style);

        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => {
                document.body.removeChild(alerta);
                document.head.removeChild(style);
            }, 300);
        }, 3000);
    }

    console.log('✅ Script completamente cargado - Productos y Fichas');
</script>