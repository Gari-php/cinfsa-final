<!-- views/vendedor/ventas/consulta.php -->
<div class="consulta-ventas-container">
    <!-- Header -->
    <div class="header-consulta">
        <div>
            <h1><i class="fas fa-search-dollar"></i> Consulta de Ventas</h1>
            <p>Visualiza todas las ventas realizadas en el sistema</p>
        </div>
        <a href="/vendedor/caja/estado" class="btn-volver">
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
                                <?php echo $caja['nombre_caja']; ?>
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
            <i class="fas fa-ticket-alt"></i>
            <div>
                <span>Total Entradas</span>
                <strong id="resumen-entradas">0</strong>
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
            <h2><i class="fas fa-list"></i> Resultados</h2>
        </div>

        <div class="tabla-wrapper">
            <table id="tabla-ventas">
                <thead>
                    <tr>
                        <th>Comprobante</th>
                        <th>Fecha/Hora</th>
                        <th>Vendedor</th>
                        <th>Caja</th>
                        <th>Película</th>
                        <th>Sala</th>
                        <th>Función</th>
                        <th>Butacas</th>
                        <th>Cant.</th>
                        <th>Tipo</th>
                        <th>P. Unit.</th>
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

        <div id="modalDevolucion" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#2d3748; border-radius:16px; padding:35px; max-width:520px; width:90%; border:2px solid #ed850f; box-shadow:0 20px 60px rgba(0,0,0,0.5); max-height:85vh; overflow-y:auto;">

                <h3 style="color:#fff; margin:0 0 8px 0; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-undo" style="color:#ed850f;"></i> Procesar Devolución
                </h3>
                <p style="color:#a0aec0; font-size:13px !important; margin:0 0 20px 0;">
                    Elegí qué entradas querés devolver (solo se muestran las activas)
                </p>

                <div id="listaEntradasDevolucion" style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;"></div>

                <div id="resumenDevolucionSeleccion" style="background:#1a202c; border-radius:10px; padding:15px; margin-bottom:20px; border:1px solid #4a5568; display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; color:#fff;">
                        <span>Total a devolver:</span>
                        <strong id="montoTotalDevolucion" style="color:#ed850f; font-size:1.2rem;">$0</strong>
                    </div>
                </div>

                <div style="margin-bottom:25px;">
                    <label style="color:#fff; font-weight:600; display:block; margin-bottom:8px;">
                        Observaciones (opcional):
                    </label>
                    <textarea id="obsDevolucion" placeholder="Motivo de la devolución..."
                        style="width:100%; padding:10px; background:#1a202c; color:#fff; border:1px solid #4a5568; border-radius:8px; resize:vertical; min-height:60px; font-family:inherit; box-sizing:border-box;"></textarea>
                </div>

                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button onclick="cerrarModalDevolucion()"
                        style="padding:10px 20px; background:#4a5568; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; transition: background 0.2s;">
                        Cancelar
                    </button>
                    <button id="btnConfirmarDevolucion" onclick="confirmarDevolucionMultiple()"
                        style="padding:10px 20px; background:linear-gradient(135deg,#ed850f,#d97706); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">
                        <i class="fas fa-check"></i> Confirmar Devolución
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sin resultados -->
    <div class="sin-resultados" id="sin-resultados" style="display: none;">
        <i class="fas fa-inbox"></i>
        <p>No se encontraron ventas con los filtros seleccionados</p>
    </div>
</div>

<style>
    .btn-devolver {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 1px solid #ef4444;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px !important;
        transition: all 0.2s ease;
    }

    .btn-devolver:hover {
        background: #ef4444;
        color: #fff;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
    }

    :root {
        --color-principal: #ed850f;
        --color-principal-hover: #d97706;
        --color-fondo-oscuro: #1a202c;
        --color-fondo-gris: #2d3748;
        --color-hover-gris: #4a5568;
        --color-texto-claro: #fff;
        --color-texto-oscuro: #1e293b;
    }

    .consulta-ventas-container {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
        padding: 2rem;
    }

    /* === HEADER === */
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
    }

    .btn-volver {
        background: rgba(255, 255, 255, 0.1);
        color: var(--color-texto-claro);
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-volver:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    /* === CARDS === */
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

    /* === FILTROS === */
    .filtros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
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

    /* === BOTONES === */
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

    /* === RESUMEN === */
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
    }

    /* === TABLA === */
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
        border-bottom: 2px solid var(--color-hover-gris);
        white-space: nowrap;
    }

    #tabla-ventas tbody td {
        padding: 0.75rem;
        border-bottom: 1px solid var(--color-hover-gris);
        color: var(--color-texto-claro);
    }

    #tabla-ventas tbody tr:hover {
        background: var(--color-hover-gris);
    }

    .butacas-cell {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
    }

    /* === SIN RESULTADOS === */
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
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .consulta-ventas-container {
            padding: 1rem;
        }

        .header-consulta {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
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
    }
</style>

<script>
    // Devolver entradas requiere el módulo DEVOLUCION_ENTRADAS
    const PUEDE_DEVOLVER = <?php echo \Middlewares\ValidarModulo::tiene('DEVOLUCION_ENTRADAS') ? 'true' : 'false'; ?>;
    // Cargar vendedores al iniciar
    document.addEventListener('DOMContentLoaded', async function() {
        await cargarVendedores();
    });

    async function cargarVendedores() {
        const select = document.getElementById('id_vendedor');

        try {
            const response = await fetch('/api/ventas/vendedores', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON. Verificar ruta /api/ventas/vendedores');
            }

            const data = await response.json();

            if (data.ok) {
                select.innerHTML = '<option value="">Todos los vendedores</option>';

                data.vendedores.forEach(v => {
                    const option = document.createElement('option');
                    option.value = v.id_usuario;
                    option.textContent = v.nombre_usuario;
                    select.appendChild(option);
                });
            } else {
                throw new Error(data.mensaje || 'Error al cargar vendedores');
            }
        } catch (error) {
            console.error('Error al cargar vendedores:', error);
            select.innerHTML = '<option value="">Error al cargar vendedores</option>';
            mostrarAlerta('Error al cargar vendedores: ' + error.message, 'error');
        }
    }

    document.getElementById('form-filtros').addEventListener('submit', async function(e) {
        e.preventDefault();
        await buscarVentas();
    });

    async function buscarVentas() {
        const fechaDesde = document.getElementById('fecha_desde').value;
        const fechaHasta = document.getElementById('fecha_hasta').value;
        const idCaja = document.getElementById('id_caja').value;
        const idVendedor = document.getElementById('id_vendedor').value;

        // Validar fechas
        if (new Date(fechaDesde) > new Date(fechaHasta)) {
            mostrarAlerta('La fecha "Desde" no puede ser mayor que "Hasta"', 'error');
            return;
        }

        try {
            const response = await fetch('/api/ventas/consultar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta,
                    id_caja: idCaja || null,
                    id_vendedor: idVendedor || null
                })
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON. Verificar ruta /api/ventas/consultar');
            }

            const data = await response.json();

            if (data.ok) {
                mostrarResultados(data.ventas, data.resumen);
            } else {
                mostrarAlerta(data.mensaje || 'Error desconocido', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'error');
        }
    }

    function mostrarResultados(ventas, resumen) {
        if (ventas.length === 0) {
            document.getElementById('card-resumen').style.display = 'none';
            document.getElementById('card-resultados').style.display = 'none';
            document.getElementById('sin-resultados').style.display = 'block';
            return;
        }

        document.getElementById('resumen-ventas').textContent = resumen.total_ventas;
        document.getElementById('resumen-entradas').textContent = resumen.total_entradas;
        document.getElementById('resumen-monto').textContent =
            '$' + new Intl.NumberFormat('es-AR').format(resumen.monto_total);
        document.getElementById('card-resumen').style.display = 'grid';

        const tbody = document.getElementById('tbody-ventas');
        tbody.innerHTML = '';

        ventas.forEach(v => {
            const tieneActivas = v.entradas_activas > 0;

            const btnDevolver = !PUEDE_DEVOLVER ? '<span class="sin-permiso" title="Sin permiso de devoluciones">—</span>' : tieneActivas ?
                `<button class="btn-devolver" id="btn-devolver-${v.id_pagos}" onclick='abrirModalDevolucion(${JSON.stringify(v)})'>
                    <i class="fas fa-undo"></i>
               </button>` :
                `<button class="btn-devolver" disabled 
                    style="background:rgba(107,114,128,0.2); border-color:#4a5568; color:#6b7280; cursor:not-allowed;">
                    <i class="fas fa-check"></i> Devuelto
               </button>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${v.numero_comprobante}</td>
            <td>${v.fecha_hora}</td>
            <td>${v.vendedor}</td>
            <td>${v.caja}</td>
            <td>${v.pelicula}</td>
            <td>${v.sala}</td>
            <td>${v.hora_funcion}</td>
            <td class="butacas-cell">${v.butacas}</td>
            <td>${v.cantidad}</td>
            <td>${v.tipo_entrada}</td>
            <td>$${new Intl.NumberFormat('es-AR').format(v.precio_unitario)}</td>
            <td>${v.forma_pago}</td>
            <td><strong>$${new Intl.NumberFormat('es-AR').format(v.monto_total)}</strong></td>
            <td>${btnDevolver}</td>
        `;
            tbody.appendChild(tr);
        });

        document.getElementById('card-resultados').style.display = 'block';
        document.getElementById('sin-resultados').style.display = 'none';

    }

    function limpiarFiltros() {
        document.getElementById('fecha_desde').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('fecha_hasta').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('id_caja').value = '';
        document.getElementById('id_vendedor').value = '';

        document.getElementById('card-resumen').style.display = 'none';
        document.getElementById('card-resultados').style.display = 'none';
        document.getElementById('sin-resultados').style.display = 'none';
    }

    let entradasDeLaVenta = [];

    async function abrirModalDevolucion(venta) {
        window.ventaActual = venta;
        if (!venta.ids_entradas) {
            mostrarAlerta('Esta venta no tiene entradas registradas', 'error');
            return;
        }

        const ids = venta.ids_entradas.split(',');
        const butacas = venta.butacas ? venta.butacas.split(', ') : [];

        const modal = document.getElementById('modalDevolucion');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const lista = document.getElementById('listaEntradasDevolucion');
        lista.innerHTML = '<p style="color:#a0aec0; text-align:center;"><i class="fas fa-spinner fa-spin"></i> Cargando entradas...</p>';
        document.getElementById('resumenDevolucionSeleccion').style.display = 'none';
        document.getElementById('obsDevolucion').value = '';

        entradasDeLaVenta = [];

        for (let i = 0; i < ids.length; i++) {
            try {
                const res = await fetch(`/vendedor/entradas/datos-devolucion?id=${ids[i].trim()}`);
                const data = await res.json();
                if (data.ok && data.estado == 1) {
                    entradasDeLaVenta.push({
                        id: ids[i].trim(),
                        butaca: butacas[i] || `Entrada ${ids[i]}`,
                        precio: data.precio,
                        forma_pago: data.forma_pago,
                        es_efectivo: data.es_efectivo
                    });
                }
            } catch (err) {
                console.error('Error al cargar entrada', ids[i], err);
            }
        }

        if (entradasDeLaVenta.length === 0) {
            lista.innerHTML = '<p style="color:#a0aec0; text-align:center;">No hay entradas activas para devolver en esta venta</p>';
            return;
        }

        renderListaEntradas();
    }

    function renderListaEntradas() {
        const lista = document.getElementById('listaEntradasDevolucion');
        lista.innerHTML = entradasDeLaVenta.map(e => `
        <label style="display:flex; align-items:center; gap:12px; background:#1a202c; padding:12px 15px; border-radius:8px; border:1px solid #4a5568; cursor:pointer; transition: border-color 0.2s;">
            <input type="checkbox" class="chk-entrada-devolucion" value="${e.id}" onchange="actualizarResumenDevolucion()" style="width:18px; height:18px; cursor:pointer;">
            <div style="flex:1;">
                <div style="color:#fff; font-weight:600;">Butaca ${e.butaca}</div>
                <div style="color:#a0aec0; font-size:12px !important;">
                    ${e.es_efectivo ? '💵' : '🏦'} ${e.forma_pago}
                </div>
            </div>
            <div style="color:#ed850f; font-weight:bold;">$${Number(e.precio).toLocaleString('es-AR')}</div>
        </label>
    `).join('');
    }

    function actualizarResumenDevolucion() {
        const seleccionadas = document.querySelectorAll('.chk-entrada-devolucion:checked');
        const resumen = document.getElementById('resumenDevolucionSeleccion');

        if (seleccionadas.length === 0) {
            resumen.style.display = 'none';
            return;
        }

        let total = 0;
        seleccionadas.forEach(chk => {
            const entrada = entradasDeLaVenta.find(e => e.id == chk.value);
            if (entrada) total += Number(entrada.precio);
        });

        document.getElementById('montoTotalDevolucion').textContent =
            '$' + total.toLocaleString('es-AR');
        resumen.style.display = 'block';
    }

    function cerrarModalDevolucion() {
        document.getElementById('modalDevolucion').style.display = 'none';
        document.body.style.overflow = '';
        entradasDeLaVenta = [];
    }

    async function confirmarDevolucionMultiple() {
        const seleccionadas = Array.from(
            document.querySelectorAll('.chk-entrada-devolucion:checked')
        ).map(chk => chk.value);

        if (seleccionadas.length === 0) {
            mostrarAlerta('Seleccioná al menos una entrada para devolver', 'error');
            return;
        }

        const btn = document.getElementById('btnConfirmarDevolucion');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        const observaciones = document.getElementById('obsDevolucion').value.trim();
        let exitos = 0,
            errores = 0;

        for (const idEntrada of seleccionadas) {
            try {
                const res = await fetch('/vendedor/entradas/procesar-devolucion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_entrada: idEntrada,
                        observaciones
                    })
                });
                const data = await res.json();
                data.ok ? exitos++ : errores++;
            } catch (err) {
                errores++;
            }
        }

        cerrarModalDevolucion();

        if (errores === 0) {
            mostrarAlerta(`✅ ${exitos} entrada(s) devuelta(s) correctamente`, 'exito');
        } else if (exitos > 0) {
            mostrarAlerta(`⚠️ ${exitos} devuelta(s), ${errores} con error`, 'error');
        } else {
            mostrarAlerta('❌ No se pudo procesar ninguna devolución', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Devolución';

        // Desactivar botón si se devolvieron TODAS las entradas activas
        if (exitos === entradasDeLaVenta.length && errores === 0) {
            const btnDevolver = document.getElementById(`btn-devolver-${window.ventaActual.id_pagos}`);
            if (btnDevolver) {
                btnDevolver.disabled = true;
                btnDevolver.style.background = 'rgba(107, 114, 128, 0.2)';
                btnDevolver.style.borderColor = '#4a5568';
                btnDevolver.style.color = '#6b7280';
                btnDevolver.style.cursor = 'not-allowed';
                btnDevolver.innerHTML = '<i class="fas fa-check"></i> Devuelto';
            }
        }

        // Recargar resultados siempre, haya errores o no
        setTimeout(() => buscarVentas(), 2000);
    }
</script>