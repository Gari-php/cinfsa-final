<div class="consulta-ventas-container">
    <!-- Header -->
    <div class="header-consulta">
        <div>
            <h1><i class="fas fa-search-dollar"></i> Consulta de Ventas de Productos</h1>
            <p>Visualiza todas las ventas de productos realizadas</p>
        </div>
        <a href="/vendedorproductos/caja/estado" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver a Caja
        </a>
    </div>

    <!-- Filtros -->
    <div class="card-filtros">
        <h2><i class="fas fa-filter"></i> Filtros de Búsqueda</h2>

        <form id="form-filtros">
            <div class="filtros-grid">
                <div class="form-group">
                    <label for="fecha_desde">
                        <i class="fas fa-calendar-alt"></i> Desde
                    </label>
                    <input
                        type="date"
                        id="fecha_desde"
                        value="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="fecha_hasta">
                        <i class="fas fa-calendar-check"></i> Hasta
                    </label>
                    <input
                        type="date"
                        id="fecha_hasta"
                        value="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="id_caja">
                        <i class="fas fa-cash-register"></i> Caja
                    </label>
                    <select id="id_caja">
                        <option value="">Todas las cajas</option>
                        <?php foreach ($cajas as $caja): ?>
                            <option value="<?php echo $caja['id_caja']; ?>">
                                <?php echo $caja['nombre_caja']; ?> (Nº <?php echo $caja['numero_caja']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_vendedor">
                        <i class="fas fa-user"></i> Vendedor
                    </label>
                    <select id="id_vendedor">
                        <option value="">Cargando vendedores...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="numero_comprobante">
                        <i class="fas fa-receipt"></i> N° Comprobante
                    </label>
                    <input
                        type="text"
                        id="numero_comprobante"
                        placeholder="Ej: 0003-00000010">
                </div>
            </div>

            <div class="filtros-actions">
                <button type="submit" class="btn-buscar">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- Resumen -->
    <div class="card-resumen" id="card-resumen" style="display: none;">
        <div class="resumen-item">
            <i class="fas fa-receipt"></i>
            <div>
                <span>Total Ventas</span>
                <strong id="resumen-ventas">0</strong>
            </div>
        </div>
        <div class="resumen-item">
            <i class="fas fa-box"></i>
            <div>
                <span>Total Productos</span>
                <strong id="resumen-productos">0</strong>
            </div>
        </div>
        <div class="resumen-item">
            <i class="fas fa-dollar-sign"></i>
            <div>
                <span>Monto Total</span>
                <strong id="resumen-monto">$0</strong>
            </div>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card-resultados" id="card-resultados" style="display: none;">
        <div class="header-resultados">
            <h2><i class="fas fa-list"></i> Resultados de la Búsqueda</h2>
        </div>

        <div class="tabla-wrapper">
            <table id="tabla-ventas">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Comprobante</th>
                        <th>Fecha/Hora</th>
                        <th>Vendedor</th>
                        <th>Caja</th>
                        <th>Producto/Item</th>
                        <th>Tipo</th>
                        <th>Cant.</th>
                        <th>P. Unit.</th>
                        <th>Subtotal</th>
                        <th>Estado</th>
                        <th>Forma Pago</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-ventas">
                    <!-- Se llena dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sin resultados -->
    <div class="sin-resultados" id="sin-resultados" style="display: none;">
        <i class="fas fa-inbox"></i>
        <p>No se encontraron ventas de productos con los filtros seleccionados</p>
        <small>Intenta ajustar los filtros de fecha o seleccionar otra caja/vendedor</small>
    </div>
</div>

<!-- Modal de Devolución -->
<div class="modal-devolucion" id="modal-devolucion">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-undo-alt"></i>
            <h3>Procesar Devolución</h3>
        </div>
        <div class="modal-body">
            <div class="info-venta" id="info-venta-devolucion">
                <!-- Se llena dinámicamente -->
            </div>
            <div class="form-group-modal">
                <label for="motivo-devolucion">
                    <i class="fas fa-comment"></i> Motivo de la devolución *
                </label>
                <textarea
                    id="motivo-devolucion"
                    placeholder="Describe el motivo de la devolución..."
                    required></textarea>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn-cancelar-modal" onclick="cerrarModalDevolucion()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn-confirmar" onclick="confirmarDevolucion()">
                <i class="fas fa-check"></i> Confirmar Devolución
            </button>
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
        --color-exito: #10b981;
        --color-info: #3b82f6;
    }

    .consulta-ventas-container {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
        padding: 2rem;
    }

    .header-consulta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        color: var(--color-texto-claro);
    }

    .header-consulta h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-consulta p {
        margin: 0;
        opacity: 0.8;
        font-size: 0.95rem;
    }

    .btn-volver {
        background: var(--color-principal);
        color: var(--color-texto-claro);
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }

    .btn-volver:hover {
        background: var(--color-principal-hover);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.3);
    }

    .card-filtros,
    .card-resumen,
    .card-resultados,
    .sin-resultados {
        background: var(--color-fondo-gris);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }

    .card-filtros h2 {
        margin: 0 0 1.5rem 0;
        color: var(--color-texto-claro);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filtros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: var(--color-texto-claro);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-group label i {
        color: var(--color-principal);
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--color-principal);
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    .filtros-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-buscar,
    .btn-limpiar {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .btn-buscar {
        background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
        color: var(--color-texto-claro);
        flex: 1;
    }

    .btn-buscar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(237, 133, 15, 0.4);
    }

    .btn-limpiar {
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
        border: 2px solid var(--color-hover-gris);
    }

    .btn-limpiar:hover {
        background: var(--color-hover-gris);
    }

    .card-resumen {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
        gap: 1.5rem;
    }

    .resumen-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: var(--color-fondo-oscuro);
        border-radius: 15px;
        border: 2px solid var(--color-hover-gris);
        transition: all 0.3s ease;
    }

    .resumen-item:hover {
        border-color: var(--color-principal);
        transform: translateY(-2px);
    }

    .resumen-item i {
        font-size: 2rem;
        color: var(--color-principal);
    }

    .resumen-item span {
        display: block;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .resumen-item strong {
        display: block;
        font-size: 1.5rem;
        color: var(--color-texto-claro);
        font-weight: 700;
    }

    .header-resultados {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .header-resultados h2 {
        margin: 0;
        color: var(--color-texto-claro);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tabla-wrapper {
        overflow-x: auto;
        border-radius: 10px;
    }

    #tabla-ventas {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    #tabla-ventas thead th {
        background: var(--color-fondo-oscuro);
        padding: 1rem 0.75rem;
        text-align: left;
        font-weight: 600;
        color: var(--color-texto-claro);
        border-bottom: 2px solid var(--color-principal);
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    #tabla-ventas tbody td {
        padding: 0.75rem;
        border-bottom: 1px solid var(--color-hover-gris);
        color: var(--color-texto-claro);
    }

    #tabla-ventas tbody tr {
        transition: all 0.2s ease;
    }

    #tabla-ventas tbody tr:hover {
        background: var(--color-hover-gris);
    }

    .badge-tipo {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        background: var(--color-info);
        color: white;
    }

    .badge-pago {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        background: var(--color-principal);
        color: white;
    }

    .btn-devolucion {
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-devolucion:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }

    .btn-devolucion:disabled {
        background: #6b7280;
        cursor: not-allowed;
        transform: none;
    }

    .modal-devolucion {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-devolucion.active {
        display: flex;
    }

    .modal-content {
        background: var(--color-fondo-gris);
        border-radius: 20px;
        padding: 2rem;
        max-width: 600px;
        /* 👈 Aumentado de 500px a 600px */
        width: 90%;
        max-height: 90vh;
        /* 👈 NUEVO: Limita la altura máxima */
        display: flex;
        flex-direction: column;
        /* 👈 NUEVO: Para controlar el scroll */
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
        overflow: hidden;
        /* 👈 NUEVO: Evita scroll en el contenedor principal */
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        color: var(--color-texto-claro);
        flex-shrink: 0;
        /* 👈 NUEVO: El header no se comprime */
    }

    .modal-header i {
        font-size: 2rem;
        color: #ef4444;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
    }

    .modal-body {
        flex: 1;
        /* 👈 NUEVO: Toma todo el espacio disponible */
        overflow-y: auto;
        /* 👈 NUEVO: Scroll solo en el body */
        overflow-x: hidden;
        margin-bottom: 1.5rem;
        padding-right: 0.5rem;
        /* 👈 NUEVO: Espacio para el scrollbar */
    }

    /* 👇 NUEVO: Estilos del scrollbar del modal-body */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #1a202c;
        border-radius: 10px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #ed850f;
        border-radius: 10px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #d97706;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        flex-shrink: 0;
        /* 👈 NUEVO: Los botones no se comprimen */
        border-top: 2px solid #4a5568;
        /* 👈 NUEVO: Línea separadora */
        padding-top: 1rem;
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        color: var(--color-texto-claro);
    }

    .modal-header i {
        font-size: 2rem;
        color: #ef4444;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
    }

    .modal-body {
        margin-bottom: 1.5rem;
    }

    .info-venta {
        background: var(--color-fondo-oscuro);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        color: var(--color-texto-claro);
    }

    .info-venta p {
        margin: 0.5rem 0;
        display: flex;
        justify-content: space-between;
    }

    .info-venta strong {
        color: var(--color-principal);
    }

    .form-group-modal {
        margin-bottom: 1rem;
    }

    .form-group-modal label {
        display: block;
        color: var(--color-texto-claro);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .form-group-modal textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
        font-family: inherit;
        resize: vertical;
        min-height: 80px;
    }

    .form-group-modal textarea:focus {
        outline: none;
        border-color: var(--color-principal);
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-confirmar,
    .btn-cancelar-modal {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-confirmar {
        background: #ef4444;
        color: white;
    }

    .btn-confirmar:hover {
        background: #dc2626;
    }

    .btn-cancelar-modal {
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
        border: 2px solid var(--color-hover-gris);
    }

    .btn-cancelar-modal:hover {
        background: var(--color-hover-gris);
    }

    .sin-resultados {
        text-align: center;
        padding: 4rem 2rem;
    }

    .sin-resultados i {
        font-size: 4rem;
        color: var(--color-hover-gris);
        margin-bottom: 1rem;
    }

    .sin-resultados p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }

    .sin-resultados small {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.9rem;
    }

    .items-devolucion {
        max-height: 300px;
        overflow-y: auto;
        padding: 0.5rem;
        background: #1a202c;
        border-radius: 10px;
        margin-top: 1rem;
    }

    .item-checkbox {
        background: #2d3748;
        border: 2px solid #4a5568;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }

    .item-checkbox:hover {
        border-color: #ed850f;
        background: #374151;
    }

    .item-checkbox label {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        cursor: pointer;
        width: 100%;
    }

    .item-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        margin-top: 3px;
        flex-shrink: 0;
    }

    .item-checkbox input[type="checkbox"]:checked {
        accent-color: #ed850f;
    }

    .item-info {
        flex: 1;
        color: var(--color-texto-claro);
    }

    .item-info strong {
        color: #ed850f;
        font-size: 1rem;
    }

    .item-info small {
        color: rgba(255, 255, 255, 0.8);
        display: block;
        margin-top: 0.25rem;
    }

    .btn-toggle-all {
        width: 100%;
        padding: 0.75rem;
        background: #4a5568;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-toggle-all:hover {
        background: #ed850f;
        transform: translateY(-2px);
    }

    #resumen-devolucion {
        animation: slideIn 0.3s ease;
    }

    .badge-estado {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-estado-vendido {
        background: #10b981;
        color: white;
    }

    .badge-estado-devuelto {
        background: #ef4444;
        color: white;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Scrollbar personalizado para la lista de items */
    .items-devolucion::-webkit-scrollbar {
        width: 8px;
    }

    .items-devolucion::-webkit-scrollbar-track {
        background: #1a202c;
        border-radius: 10px;
    }

    .items-devolucion::-webkit-scrollbar-thumb {
        background: #ed850f;
        border-radius: 10px;
    }

    .items-devolucion::-webkit-scrollbar-thumb:hover {
        background: #d97706;
    }

    @media (max-width: 768px) {
        .consulta-ventas-container {
            padding: 1rem;
        }

        .header-consulta {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .header-consulta h1 {
            font-size: 1.5rem;
        }

        .filtros-grid {
            grid-template-columns: 1fr;
        }

        .filtros-actions {
            flex-direction: column;
        }

        .card-resumen {
            grid-template-columns: 1fr;
        }

        .header-resultados {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<script>
    // Devolver productos requiere el módulo DEVOLUCION_PRODUCTOS
    const PUEDE_DEVOLVER = <?php echo \Middlewares\ValidarModulo::tiene('DEVOLUCION_PRODUCTOS') ? 'true' : 'false'; ?>;
    let ventasActuales = [];
    let ventaSeleccionada = null;
    let itemsParaDevolver = [];

    document.addEventListener('DOMContentLoaded', async function() {
        console.log(' Página cargada - Iniciando carga de vendedores');
        await cargarVendedores();
    });

    async function cargarVendedores() {
        const select = document.getElementById('id_vendedor');

        console.log(' Cargando vendedores...');

        try {
            const response = await fetch('/ventas/consulta/api/vendedores', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            console.log('📡 Respuesta recibida:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            console.log(' Content-Type:', contentType);

            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ Respuesta no JSON:', text);
                throw new Error('La respuesta no es JSON válido');
            }

            const data = await response.json();

            if (data.ok) {
                select.innerHTML = '<option value="">Todos los vendedores</option>';

                if (data.vendedores && data.vendedores.length > 0) {
                    data.vendedores.forEach(v => {
                        const option = document.createElement('option');
                        option.value = v.id_usuario;
                        option.textContent = v.nombre_usuario;
                        select.appendChild(option);
                    });
                    console.log(`✅ ${data.vendedores.length} vendedores cargados`);
                } else {
                    select.innerHTML += '<option value="" disabled>No hay vendedores disponibles</option>';
                    console.warn('⚠️ No hay vendedores disponibles');
                }
            } else {
                throw new Error(data.mensaje || 'Error al cargar vendedores');
            }
        } catch (error) {
            console.error('❌ Error al cargar vendedores:', error);
            select.innerHTML = '<option value="">Error al cargar vendedores</option>';
            window.mostrarAlerta('No se pudieron cargar los vendedores: ' + error.message, 'error');
        }
    }

    document.getElementById('form-filtros').addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log(' Formulario enviado - Iniciando búsqueda');
        await buscarVentas();
    });

    async function buscarVentas() {
        const fechaDesde = document.getElementById('fecha_desde').value;
        const fechaHasta = document.getElementById('fecha_hasta').value;
        const idCaja = document.getElementById('id_caja').value;
        const idVendedor = document.getElementById('id_vendedor').value;
        const numeroComprobante = document.getElementById('numero_comprobante').value.trim();


        if (new Date(fechaDesde) > new Date(fechaHasta)) {
            window.mostrarAlerta('La fecha "Desde" no puede ser mayor que "Hasta"', 'error');
            return;
        }

        // Mostrar loading
        const tbody = document.getElementById('tbody-ventas');
        tbody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Buscando ventas...</td></tr>';
        document.getElementById('card-resultados').style.display = 'block';
        document.getElementById('sin-resultados').style.display = 'none';
        document.getElementById('card-resumen').style.display = 'none';

        try {
            const payload = {
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                id_caja: idCaja || null,
                id_vendedor: idVendedor || null,
                numero_comprobante: numeroComprobante || null
            };


            const response = await fetch('/ventas/consulta/api/consultar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            console.log(' Respuesta recibida:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ Respuesta no JSON:', text);
                throw new Error('La respuesta del servidor no es JSON válido');
            }

            const data = await response.json();

            if (data.ok) {
                ventasActuales = data.ventas;
                mostrarResultados(data.ventas, data.resumen);
            } else {
                throw new Error(data.mensaje || 'Error desconocido al consultar ventas');
            }
        } catch (error) {
            console.error('❌ Error completo:', error);
            tbody.innerHTML = '';
            document.getElementById('card-resultados').style.display = 'none';
            document.getElementById('sin-resultados').style.display = 'block';
            window.mostrarAlerta('Error al buscar ventas: ' + error.message, 'error');
        }
    }

    function mostrarResultados(ventas, resumen) {

        if (ventas.length === 0) {
            document.getElementById('card-resumen').style.display = 'none';
            document.getElementById('card-resultados').style.display = 'none';
            document.getElementById('sin-resultados').style.display = 'block';
            return;
        }

        // Mostrar resumen
        document.getElementById('resumen-ventas').textContent = resumen.total_ventas;
        document.getElementById('resumen-productos').textContent = resumen.total_productos;
        document.getElementById('resumen-monto').textContent =
            '$' + new Intl.NumberFormat('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(resumen.monto_total);
        document.getElementById('card-resumen').style.display = 'grid';

        // Llenar tabla
        const tbody = document.getElementById('tbody-ventas');
        tbody.innerHTML = '';

        ventas.forEach(v => {
            const tr = document.createElement('tr');

            // Verificar si ya fue devuelto
            const yaDevuelto = v.numero_comprobante && v.numero_comprobante.includes('[DEVUELTO]');

            tr.innerHTML = `
                <td><strong>#${v.id_venta}</strong></td>
                <td><small>${v.numero_comprobante || 'S/N'}</small></td>
                <td>${v.fecha_hora}</td>
                <td>${v.vendedor}</td>
                <td>${v.caja} <small>(${v.numero_caja})</small></td>
                <td><strong>${v.producto}</strong></td>
                <td><span class="badge-tipo">${v.tipo_item}</span></td>
                <td><strong>${v.cantidad}</strong></td>
                <td>${new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(v.precio_unitario)}</td>
                <td>${new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(v.subtotal)}</td>
                <td><span class="badge-estado badge-estado-${v.estado}">${v.estado.toUpperCase()}</span></td> <!-- 👈 NUEVO -->
                <td><span class="badge-pago">${v.forma_pago}</span></td>
                <td><strong style="color: var(--color-exito);">${new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(v.monto_total)}</strong></td>
                <td>
                    ${!PUEDE_DEVOLVER ? '<span class="sin-permiso" title="Sin permiso de devoluciones">—</span>' : `<button 
                        class="btn-devolucion" 
                        onclick="abrirModalDevolucion(${v.id_venta})"
                        ${yaDevuelto || v.estado === 'devuelto' ? 'disabled title="Ya fue devuelto"' : ''}
                    >
                        <i class="fas fa-undo-alt"></i>
                    </button>`}
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('card-resultados').style.display = 'block';
        document.getElementById('sin-resultados').style.display = 'none';

        console.log(`✅ Se mostraron ${ventas.length} registro(s) de ${resumen.total_ventas} venta(s)`);
    }

    function limpiarFiltros() {
        console.log(' Limpiando filtros');
        document.getElementById('fecha_desde').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('fecha_hasta').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('id_caja').value = '';
        document.getElementById('id_vendedor').value = '';
        document.getElementById('numero_comprobante').value = '';

        document.getElementById('card-resumen').style.display = 'none';
        document.getElementById('card-resultados').style.display = 'none';
        document.getElementById('sin-resultados').style.display = 'none';

        ventasActuales = [];
    }

    function abrirModalDevolucion(idVenta) {
        console.log(' Abriendo modal de devolución para venta:', idVenta);

        // Obtener TODOS los items de esta venta (pueden ser varios)
        const itemsVenta = ventasActuales.filter(v => v.id_venta === idVenta);

        if (itemsVenta.length === 0) {
            window.mostrarAlerta('No se encontraron items de la venta', 'error');
            return;
        }

        // Verificar si ya fue devuelto
        const yaDevuelto = itemsVenta[0].numero_comprobante &&
            itemsVenta[0].numero_comprobante.includes('[DEVUELTO]');

        if (yaDevuelto) {
            window.mostrarAlerta('Esta venta ya fue devuelta anteriormente', 'error');
            return;
        }

        ventaSeleccionada = {
            id_venta: idVenta,
            numero_comprobante: itemsVenta[0].numero_comprobante,
            fecha_hora: itemsVenta[0].fecha_hora,
            items: itemsVenta
        };

        // Resetear items para devolver
        itemsParaDevolver = [];

        // Construir HTML con checkboxes para cada item
        let itemsHTML = '<div class="items-devolucion">';
        itemsHTML += '<p style="color: #ed850f; font-weight: bold; margin-bottom: 1rem;">✓ Selecciona los items a devolver:</p>';

        itemsVenta.forEach((item, index) => {
            const itemId = `item_${index}`;
            itemsHTML += `
            <div class="item-checkbox">
                <label>
                    <input 
                        type="checkbox" 
                        id="${itemId}" 
                        value="${index}"
                        onchange="actualizarSeleccion(${index})"
                    >
                    <span class="item-info">
                        <strong>${item.producto}</strong> (${item.tipo_item})
                        <br>
                        <small>
                            Cantidad: ${item.cantidad} × $${new Intl.NumberFormat('es-AR', { 
                                minimumFractionDigits: 2 
                            }).format(item.precio_unitario)} = 
                            <strong style="color: #10b981;">$${new Intl.NumberFormat('es-AR', { 
                                minimumFractionDigits: 2 
                            }).format(item.subtotal)}</strong>
                        </small>
                    </span>
                </label>
            </div>
        `;
        });

        itemsHTML += '</div>';

        // Agregar botón para seleccionar/deseleccionar todo
        itemsHTML += `
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #4a5568;">
            <button 
                type="button" 
                class="btn-toggle-all" 
                onclick="toggleSeleccionarTodo()"
            >
                <i class="fas fa-check-double"></i> Seleccionar/Deseleccionar Todo
            </button>
        </div>
    `;

        // Información general
        const infoDiv = document.getElementById('info-venta-devolucion');
        infoDiv.innerHTML = `
        <div style="background: #1a202c; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <p><span>Comprobante:</span> <strong>${itemsVenta[0].numero_comprobante}</strong></p>
            <p><span>Fecha:</span> <strong>${itemsVenta[0].fecha_hora}</strong></p>
            <p><span>Total items:</span> <strong>${itemsVenta.length}</strong></p>
            <p><span>Monto total ticket:</span> <strong style="color: #10b981;">$${new Intl.NumberFormat('es-AR', { 
                minimumFractionDigits: 2 
            }).format(itemsVenta[0].monto_total)}</strong></p>
        </div>
        ${itemsHTML}
        <div id="resumen-devolucion" style="display: none; margin-top: 1rem; padding: 1rem; background: #1e40af; border-radius: 8px;">
            <p style="color: white; font-weight: bold;">
                <i class="fas fa-calculator"></i> Monto a devolver: 
                <span id="monto-devolver" style="font-size: 1.2rem; color: #fbbf24;">$0.00</span>
            </p>
            <p style="color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-top: 0.5rem;">
                Items seleccionados: <span id="items-count">0</span>
            </p>
        </div>
    `;

        // Limpiar motivo
        document.getElementById('motivo-devolucion').value = '';

        // Mostrar modal
        document.getElementById('modal-devolucion').classList.add('active');
    }

    function actualizarSeleccion(index) {
        const checkbox = document.getElementById(`item_${index}`);
        const item = ventaSeleccionada.items[index];

        if (checkbox.checked) {
            // Agregar item a la lista
            itemsParaDevolver.push({
                index: index,
                producto: item.producto,
                tipo_item: item.tipo_item,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario,
                subtotal: item.subtotal
            });
        } else {
            // Remover item de la lista
            itemsParaDevolver = itemsParaDevolver.filter(i => i.index !== index);
        }

        actualizarResumenDevolucion();
    }

    function toggleSeleccionarTodo() {
        const todosCheckboxes = document.querySelectorAll('.item-checkbox input[type="checkbox"]');
        const algunoSeleccionado = itemsParaDevolver.length > 0;

        todosCheckboxes.forEach(checkbox => {
            checkbox.checked = !algunoSeleccionado;
        });

        // Actualizar array
        if (!algunoSeleccionado) {
            itemsParaDevolver = ventaSeleccionada.items.map((item, index) => ({
                index: index,
                producto: item.producto,
                tipo_item: item.tipo_item,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario,
                subtotal: item.subtotal
            }));
        } else {
            itemsParaDevolver = [];
        }

        actualizarResumenDevolucion();
    }

    function actualizarResumenDevolucion() {
        const resumenDiv = document.getElementById('resumen-devolucion');
        const montoSpan = document.getElementById('monto-devolver');
        const itemsCountSpan = document.getElementById('items-count');

        if (itemsParaDevolver.length === 0) {
            resumenDiv.style.display = 'none';
            return;
        }

        const montoTotal = itemsParaDevolver.reduce((sum, item) => sum + item.subtotal, 0);

        montoSpan.textContent = '$' + new Intl.NumberFormat('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(montoTotal);

        itemsCountSpan.textContent = itemsParaDevolver.length;

        resumenDiv.style.display = 'block';
    }

    async function confirmarDevolucion() {
        const motivo = document.getElementById('motivo-devolucion').value.trim();

        if (!motivo) {
            window.mostrarAlerta('Por favor ingresá el motivo de la devolución', 'error');
            return;
        }

        if (!ventaSeleccionada) {
            window.mostrarAlerta('No hay venta seleccionada', 'error');
            return;
        }

        if (itemsParaDevolver.length === 0) {
            window.mostrarAlerta('Debés seleccionar al menos un item para devolver', 'error');
            return;
        }

        const montoTotal = itemsParaDevolver.reduce((sum, item) => sum + item.subtotal, 0);
        const montoFormateado = new Intl.NumberFormat('es-AR', {
            minimumFractionDigits: 2
        }).format(montoTotal);

        // Construir lista de items
        let detalleItems = itemsParaDevolver.map(item =>
            `• ${item.producto} (${item.tipo_item}) x ${item.cantidad}`
        ).join('\n');

        const detalleItemsHTML = itemsParaDevolver.map(item =>
            `${item.producto} (${item.tipo_item}) x ${item.cantidad}`
        ).join('<br>');

        const confirmado = await window.mostrarConfirmacionModal(
            `¿Confirmás procesar la devolución?<br><br>` +
            `<strong>Comprobante:</strong> ${ventaSeleccionada.numero_comprobante}<br>` +
            `<strong>Items a devolver (${itemsParaDevolver.length}):</strong><br>${detalleItemsHTML}<br><br>` +
            `<strong>Monto a devolver:</strong> $${montoFormateado}<br><br>` +
            `Esta acción devolverá el stock, descontará el monto de la caja, y no se puede revertir.`,
            'warning'
        );

        if (!confirmado) {
            return;
        }

        console.log('✅ Confirmando devolución:', ventaSeleccionada.id_venta);

        // Deshabilitar botón
        const btnConfirmar = document.querySelector('.btn-confirmar');
        const textoOriginal = btnConfirmar.innerHTML;
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        try {
            // Preparar datos para enviar al backend
            const itemsParaEnviar = itemsParaDevolver.map(item => {
                const itemOriginal = ventaSeleccionada.items[item.index];
                return {
                    producto: itemOriginal.producto,
                    tipo_item: itemOriginal.tipo_item,
                    cantidad: itemOriginal.cantidad,
                    precio_unitario: itemOriginal.precio_unitario,
                    subtotal: itemOriginal.subtotal
                };
            });

            const response = await fetch('/ventas/consulta/api/devolucion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id_venta: ventaSeleccionada.id_venta,
                    items: itemsParaEnviar,
                    motivo: motivo
                })
            });

            console.log(' Respuesta recibida:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            // DESPUÉS
            if (data.ok) {
                window.mostrarAlerta(
                    `Devolución procesada — Comprobante ${data.detalles.numero_comprobante}, $${data.detalles.monto_devuelto} devuelto (${data.detalles.items_devueltos} items)`,
                    'exito'
                );

                cerrarModalDevolucion();
                await buscarVentas();
            } else {
                window.mostrarAlerta(data.mensaje || 'Error al procesar la devolución', 'error');
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = textoOriginal;
            }
        } catch (error) {
            console.error('❌ Error al procesar devolución:', error);
            window.mostrarAlerta('Error al procesar la devolución: ' + error.message, 'error');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = textoOriginal;
        }
    }

    function cerrarModalDevolucion() {
        console.log('❌ Cerrando modal de devolución');
        document.getElementById('modal-devolucion').classList.remove('active');
        ventaSeleccionada = null;
        itemsParaDevolver = [];
    }
    // Cerrar modal al hacer clic fuera
    document.getElementById('modal-devolucion').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalDevolucion();
        }
    });
</script>

<script type="module" src="/assets/js/formularios.js"></script>