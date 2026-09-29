<!-- views/vendedor/funciones/butacas.php -->
<form method="POST"
    id="form-container"
    action="/vendedor/caja/abrir"
    data-fetch="true"
    data-alerta=".contenedor-alertas"
    data-redirigir="/vendedor/caja/estado">

    <div class="vendedor-butacas-container">
        <!-- Header -->
        <div class="header-butacas-vendedor">
            <a href="/vendedor/funciones/listado" class="btn-volver-vendedor">
                <i class="fas fa-arrow-left"></i> Volver a Funciones
            </a>

            <div class="info-funcion-header">
                <h1>SELECCIÓN DE BUTACAS - VENTA INTERNA</h1>
                <p class="titulo-pelicula"><?php echo $funcion['titulo_pelicula'] ?? 'Película'; ?></p>
                <div class="detalles-rapidos">
                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($funcion['fecha_hora'])); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $funcion['turno_horario'] ?? 'Horario'; ?></span>
                    <span><i class="fas fa-couch"></i> Sala <?php echo $funcion['rela_salas']; ?></span>
                    <span><i class="fas fa-dollar-sign"></i> $<?php echo number_format($funcion['precio_entrada'] ?? 2500, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <div class="sala-contenido">
            <!-- Mapa de la Sala con MISMA ESTRUCTURA que cliente -->
            <div class="mapa-sala-vendedor">
                <!-- LEYENDA - IGUAL que cliente -->
                <div class="leyenda">
                    <div class="leyenda-item">
                        <i class="fa-solid fa-chair disponible-icon"></i>
                        <span>Disponible</span>
                    </div>
                    <div class="leyenda-item">
                        <i class="fa-solid fa-chair seleccionada-icon"></i>
                        <span>Seleccionada</span>
                    </div>
                    <div class="leyenda-item">
                        <i class="fa-solid fa-chair bloqueada-icon"></i>
                        <span>No disponible</span>
                    </div>
                    <div class="leyenda-item">
                        <i class="fa-solid fa-chair ocupada-icon"></i>
                        <span>Vendida/Reservada</span>
                    </div>
                </div>

                <!-- PANTALLA - IGUAL que cliente -->
                <div class="pantalla">
                    <div class="pantalla-texto">PANTALLA</div>
                </div>

                <!-- MAPA DE BUTACAS - IGUAL que cliente -->
                <div id="mapa-butacas" class="mapa-butacas">
                    <div id="loading-butacas" class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Cargando mapa de butacas...</p>
                    </div>
                </div>
            </div>

            <!-- Panel de Resumen -->
            <div class="panel-resumen-vendedor">
                <h2><i class="fas fa-shopping-cart"></i> Resumen de Venta</h2>

                <div class="butacas-seleccionadas-lista" id="lista-seleccionadas">
                    <p class="sin-seleccion">No hay butacas seleccionadas</p>
                </div>

                <div class="totales-venta">
                    <div class="total-item">
                        <span>Cantidad:</span>
                        <strong id="cantidad-total">0</strong>
                    </div>
                    <div class="total-item">
                        <span>Precio unitario:</span>
                        <strong>$<?php echo number_format($funcion['precio_entrada'] ?? 2500, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="total-item total-final">
                        <span>TOTAL A COBRAR:</span>
                        <strong id="monto-total">$0</strong>
                    </div>
                </div>


                <div class="forma-pago-section">
                    <label for="tipo_pago">
                        <i class="fas fa-credit-card"></i> Forma de Pago
                    </label>
                    <select id="tipo_pago" class="select-pago">
                        <?php if (isset($tipos_pago) && !empty($tipos_pago)): ?>
                            <?php foreach ($tipos_pago as $tipo): ?>
                                <option value="<?php echo $tipo['id_tipo_pago']; ?>">
                                    <?php echo htmlspecialchars($tipo['descripcion_tipo_pago']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <?php endif; ?>
                    </select>
                </div>


                <div class="tipo-comprobante-section">
                    <label for="tipo_comprobante">
                        <i class="fas fa-file-invoice"></i> Tipo de Comprobante
                    </label>
                    <select id="tipo_comprobante" class="select-comprobante">
                        <!-- Se cargará dinámicamente desde el backend -->
                    </select>

                    <div class="info-comprobante" id="info-comprobante"></div>
                </div>

                <div class="observaciones-section">
                    <label for="observaciones">
                        <i class="fas fa-comment"></i> Observaciones (opcional)
                    </label>
                    <textarea
                        id="observaciones"
                        placeholder="Ej: Cliente Juan Pérez, DNI 12345678"
                        rows="3"></textarea>
                </div>

                <!-- Botones de Acción -->
                <div class="botones-accion-venta">
                    <button type="button" id="btn-procesar-venta" class="btn-procesar-venta" onclick="procesarVenta()" disabled>
                        <i class="fas fa-check-circle"></i>
                        Procesar Venta
                    </button>

                    <button type="button" class="btn-limpiar" onclick="limpiarSeleccion()">
                        <i class="fas fa-redo"></i>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
<style>
    /* === VARIABLES === */
    :root {
        --color-principal: #ed850f;
        --color-principal-hover: #d97706;
        --color-fondo-oscuro: #1a202c;
        --color-fondo-gris: #2d3748;
        --color-hover-gris: #4a5568;
        --color-texto-claro: #fff;
        --color-texto-oscuro: #1e293b;
    }

    /* === CONTENEDOR === */
    .vendedor-butacas-container {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
        padding: 2rem;
    }

    /* === HEADER === */
    .header-butacas-vendedor {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid var(--color-principal);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        position: relative;
    }

    .btn-volver-vendedor {
        position: absolute;
        top: 2rem;
        left: 2rem;
        background: transparent;
        color: var(--color-texto-claro);
        border: 2px solid var(--color-hover-gris);
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .btn-volver-vendedor:hover {
        border-color: var(--color-principal);
        color: var(--color-principal);
        background: rgba(237, 133, 15, 0.08);
        transform: translateY(-2px);
    }

    .info-funcion-header {
        text-align: center;
        color: var(--color-texto-claro);
    }

    .info-funcion-header h1 {
        margin: 0 0 0.5rem 0;
        font-size: 1.6rem;
        color: var(--color-principal);
        letter-spacing: 0.5px;
    }

    .titulo-pelicula {
        font-size: 1.6rem;
        font-weight: bold;
        margin: 0.5rem 0;
        color: #fff;
    }

    .detalles-rapidos {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .detalles-rapidos span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #cbd5e0;
        font-size: 0.95rem;
    }

    .detalles-rapidos i {
        color: var(--color-principal);
    }

    /* === LAYOUT === */
    .sala-contenido {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 1.5rem;
        align-items: start;
    }

    /* === MAPA Y PANEL OSCUROS === */
    .mapa-sala-vendedor,
    .panel-resumen-vendedor {
        background: var(--color-fondo-gris);
        border: 1px solid var(--color-hover-gris);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    /* === LEYENDA === */
    .leyenda {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--color-hover-gris);
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-weight: 600;
        color: #cbd5e0;
        font-size: 0.9rem;
        background: var(--color-fondo-oscuro);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        border: 1px solid var(--color-hover-gris);
    }

    .leyenda-item i {
        font-size: 1.3rem;
    }

    /* === PANTALLA === */
    .pantalla {
        background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
        color: var(--color-texto-claro);
        text-align: center;
        padding: 1rem;
        border-radius: 15px 15px 50% 50%;
        margin-bottom: 3rem;
        box-shadow: 0 10px 25px rgba(237, 133, 15, 0.35);
    }

    .pantalla-texto {
        font-weight: bold;
        font-size: 1.2rem;
        letter-spacing: 3px;
    }

    /* === LOADING === */
    .loading {
        text-align: center;
        padding: 3rem;
        color: var(--color-principal);
    }

    .loading i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    /* === MAPA BUTACAS === */
    .mapa-butacas {
        min-height: 300px;
        overflow-x: auto;
    }

    .sala-grid {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
        min-width: fit-content;
    }

    .fila-butacas {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .fila-numero {
        min-width: 50px;
        text-align: center;
        font-weight: bold;
        color: var(--color-texto-claro);
        font-size: 0.8rem;
    }

    /* === BUTACAS === */
    .butaca {
        width: 34px;
        height: 34px;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        background: transparent;
    }

    .butaca-icon {
        font-size: 15px !important;
        transition: all 0.2s ease;
        font-weight: 900 !important;
    }

    .numero-butaca {
        font-size: 7px !important;
        font-weight: bold;
        margin-top: 1px;
        color: var(--color-texto-claro) !important;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    }

    .butaca-vacia {
        width: 34px;
        height: 34px;
    }

    /* Estados */
    .butaca.disponible .butaca-icon {
        color: #22c55e !important;
        cursor: pointer;
    }

    .butaca.disponible:hover .butaca-icon {
        color: #4ade80 !important;
        transform: scale(1.25);
    }

    .butaca.seleccionada .butaca-icon {
        color: #3b82f6 !important;
        transform: scale(1.15) !important;
        cursor: pointer;
    }

    .butaca.bloqueada .butaca-icon {
        color: #ef4444 !important;
        opacity: 0.8;
        cursor: not-allowed;
    }

    .butaca.ocupada .butaca-icon {
        color: var(--color-principal) !important;
        opacity: 0.85;
        cursor: not-allowed;
    }

    .disponible-icon {
        color: #22c55e !important;
    }

    .seleccionada-icon {
        color: #3b82f6 !important;
    }

    .bloqueada-icon {
        color: #ef4444 !important;
    }

    .ocupada-icon {
        color: var(--color-principal) !important;
    }

    /* === PANEL RESUMEN === */
    .panel-resumen-vendedor {
        height: fit-content;
        position: sticky;
        top: 2rem;
        min-width: 280px;
    }

    .panel-resumen-vendedor h2 {
        color: var(--color-principal);
        margin: 0 0 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.4rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--color-hover-gris);
    }

    /* === LISTA SELECCIONADAS === */
    .butacas-seleccionadas-lista {
        max-height: 250px;
        overflow-y: auto;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: var(--color-fondo-oscuro);
        border: 1px solid var(--color-hover-gris);
        border-radius: 12px;
        min-height: 100px;
    }

    .sin-seleccion {
        text-align: center;
        color: #6b7280;
        font-style: italic;
        padding: 2rem 0;
    }

    .butaca-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        background: var(--color-fondo-gris);
        border: 1px solid var(--color-hover-gris);
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }

    .butaca-item:last-child {
        margin-bottom: 0;
    }

    .butaca-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--color-texto-claro);
        font-size: 0.9rem;
    }

    .icono-butaca {
        color: var(--color-principal);
        font-size: 1.1rem;
    }

    .btn-quitar {
        background: transparent;
        color: #fca5a5;
        border: 1px solid #ef4444;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-quitar:hover {
        background: #ef4444;
        color: #fff;
    }

    /* === TOTALES === */
    .totales-venta {
        background: var(--color-fondo-oscuro);
        border: 1px solid var(--color-hover-gris);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .total-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--color-hover-gris);
        color: #cbd5e0;
    }

    .total-item:last-child {
        border-bottom: none;
    }

    .total-item strong {
        color: #fff;
    }

    .total-final {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 2px solid var(--color-principal);
        font-size: 1.25rem;
        color: var(--color-principal);
    }

    .total-final strong {
        color: var(--color-principal);
    }

    /* === FORMA DE PAGO === */
    .forma-pago-section,
    .tipo-comprobante-section,
    .observaciones-section {
        margin-bottom: 1.5rem;
    }

    .forma-pago-section label,
    .tipo-comprobante-section label,
    .observaciones-section label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #a0aec0;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .forma-pago-section label i,
    .tipo-comprobante-section label i,
    .observaciones-section label i {
        color: var(--color-principal);
    }

    .select-pago,
    .select-comprobante,
    textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--color-hover-gris);
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: var(--color-fondo-oscuro);
        color: var(--color-texto-claro);
        font-family: inherit;
        box-sizing: border-box;
    }

    textarea {
        resize: vertical;
    }

    .select-pago:focus,
    .select-comprobante:focus,
    textarea:focus {
        outline: none;
        border-color: var(--color-principal);
        background: #131a24;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    textarea::placeholder {
        color: #6b7280;
    }

    /* === INFO COMPROBANTE === */
    .info-comprobante {
        margin-top: 0.75rem;
    }

    .info-box {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 0.85rem;
        line-height: 1.4;
        animation: fadeIn 0.3s ease;
    }

    .info-box i {
        font-size: 1.2rem;
        margin-top: 0.1rem;
    }

    .info-text {
        flex: 1;
    }

    .info-text strong {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }

    .info-text p {
        margin: 0;
        opacity: 0.9;
    }

    .info-ticket {
        background: rgba(14, 165, 233, 0.15);
        border: 1px solid #0ea5e9;
        color: #7dd3fc;
    }

    .info-ticket i {
        color: #38bdf8;
    }

    .info-factura-b {
        background: rgba(34, 197, 94, 0.15);
        border: 1px solid #22c55e;
        color: #86efac;
    }

    .info-factura-b i {
        color: #4ade80;
    }

    .info-factura-a {
        background: rgba(245, 158, 11, 0.15);
        border: 1px solid #f59e0b;
        color: #fcd34d;
    }

    .info-factura-a i {
        color: #fbbf24;
    }

    .info-factura-c {
        background: rgba(236, 72, 153, 0.15);
        border: 1px solid #ec4899;
        color: #f9a8d4;
    }

    .info-factura-c i {
        color: #f472b6;
    }

    .botones-accion-venta {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .btn-procesar-venta,
    .btn-limpiar {
        padding: 1rem;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-procesar-venta {
        background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
        color: var(--color-texto-claro);
        box-shadow: 0 4px 15px rgba(237, 133, 15, 0.3);
    }

    .btn-procesar-venta:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(237, 133, 15, 0.45);
    }

    .btn-procesar-venta:disabled {
        background: var(--color-hover-gris);
        color: rgba(255, 255, 255, 0.5);
        cursor: not-allowed;
        box-shadow: none;
    }

    .btn-limpiar {
        background: transparent;
        color: var(--color-texto-claro);
        border: 2px solid var(--color-hover-gris);
    }

    .btn-limpiar:hover {
        border-color: #ef4444;
        color: #fca5a5;
        background: rgba(239, 68, 68, 0.08);
    }

    /* === SCROLLBAR === */
    .butacas-seleccionadas-lista::-webkit-scrollbar {
        width: 8px;
    }

    .butacas-seleccionadas-lista::-webkit-scrollbar-track {
        background: var(--color-fondo-gris);
        border-radius: 10px;
    }

    .butacas-seleccionadas-lista::-webkit-scrollbar-thumb {
        background: var(--color-hover-gris);
        border-radius: 10px;
    }

    .butacas-seleccionadas-lista::-webkit-scrollbar-thumb:hover {
        background: var(--color-principal);
    }

    /* === ANIMATIONS === */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* === RESPONSIVE === */
    /* Sin esto la celda de la grilla crece hasta el ancho del mapa y el overflow-x de .mapa-butacas nunca se activa */
    .sala-contenido > * {
        min-width: 0;
    }

    @media (max-width: 1024px) {
        .sala-contenido {
            grid-template-columns: 1fr;
        }

        .panel-resumen-vendedor {
            position: static;
        }

        .mapa-butacas {
            overflow-x: auto;
        }
    }

    @media (max-width: 768px) {
        .vendedor-butacas-container {
            padding: 1rem;
        }

        .header-butacas-vendedor {
            padding: 1.5rem;
        }

        .btn-volver-vendedor {
            position: static;
            transform: none;
            margin-bottom: 1rem;
            display: inline-flex;
        }

        .info-funcion-header h1 {
            font-size: 1.3rem;
        }

        .titulo-pelicula {
            font-size: 1.3rem;
        }

        .detalles-rapidos {
            gap: 1rem;
            font-size: 0.85rem;
        }

        .leyenda {
            gap: 0.75rem;
            font-size: 0.8rem;
        }

        .leyenda-item {
            padding: 0.4rem 0.8rem;
        }

        .mapa-sala-vendedor,
        .panel-resumen-vendedor {
            padding: 1.25rem;
        }

        .butaca {
            width: 26px;
            height: 26px;
        }

        .butaca-icon {
            font-size: 12px !important;
        }

        .numero-butaca {
            font-size: 6px !important;
        }

        .butaca-vacia {
            width: 26px;
            height: 26px;
        }

        .fila-numero {
            min-width: 34px;
            font-size: 0.75rem !important;
        }

        .sala-grid {
            width: max-content;
            margin: 0 auto;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('🚀 Cargando tipos de comprobante...');

        const selectTipoComprobante = document.getElementById('tipo_comprobante');
        const infoComprobante = document.getElementById('info-comprobante');

        try {
            // Cargar tipos de comprobante
            const response = await fetch('/vendedor/obtener-tipos-comprobante');
            const data = await response.json();

            if (data.ok && data.tipos) {
                const selectTipoComprobante = document.getElementById('tipo_comprobante');
                selectTipoComprobante.innerHTML = '';

                data.tipos.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.codigo;
                    option.textContent = tipo.descripcion;
                    option.dataset.validoAfip = tipo.valido_afip;
                    selectTipoComprobante.appendChild(option);
                });

                console.log('✅ Tipos de comprobante cargados');
            }
        } catch (error) {
            console.error('❌ Error cargando tipos de comprobante:', error);
        }

        // Función para actualizar información
        function actualizarInfoComprobante() {
            const tipoSeleccionado = selectTipoComprobante.value;
            const info = infoTipos[tipoSeleccionado];

            if (info) {
                infoComprobante.innerHTML = `
                <div class="info-box ${info.clase}">
                    <i class="fas ${info.icono}"></i>
                    <div class="info-text">
                        <strong>${info.titulo}</strong>
                        <p>${info.descripcion}</p>
                    </div>
                </div>
            `;
            }
        }

        // Escuchar cambios
        selectTipoComprobante.addEventListener('change', actualizarInfoComprobante);

        // Inicializar
        actualizarInfoComprobante();
    });

    // Variables globales - IGUAL QUE CLIENTE
    const idFuncion = <?php echo $funcion['id_funcion']; ?>;
    const precioEntrada = <?php echo $funcion['precio_entrada'] ?? 2500; ?>;
    let butacasSeleccionadas = [];
    let layoutSala = null;

    // Estados de butacas - IGUAL QUE CLIENTE
    const ESTADOS = {
        DISPONIBLE: 1,
        BLOQUEADA: 2,
        RESERVADA: 3
    };

    // Cargar mapa de butacas al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Iniciando aplicación de butacas del vendedor');
        cargarMapaButacas();
    });

    // FUNCIÓN PRINCIPAL - IGUAL QUE CLIENTE
    async function cargarMapaButacas() {
        console.log('📡 Cargando mapa de butacas para función:', idFuncion);

        try {
            const url = `/api/vendedor/butacas/funcion?id_funcion=${idFuncion}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.ok) {
                layoutSala = data.layout;

                if (!layoutSala || !layoutSala.butacas || layoutSala.butacas.length === 0) {
                    mostrarError('No hay butacas generadas para esta sala');
                    return;
                }

                generarMapaButacas(layoutSala);
            } else {
                console.error('Error en respuesta:', data.mensaje);
                mostrarError(data.mensaje);
            }
        } catch (error) {
            console.error('Error completo:', error);
            mostrarError('Error al cargar el mapa de butacas: ' + error.message);
        }
    }

    // GENERAR MAPA - IGUAL QUE CLIENTE
    function generarMapaButacas(layout) {
        console.log('🎨 Generando mapa visual de butacas');

        const container = document.getElementById('mapa-butacas');
        const loading = document.getElementById('loading-butacas');

        loading.style.display = 'none';

        const filas = layout.sala.filas;
        const columnas = layout.sala.columnas;

        let html = '<div class="sala-grid">';

        // Crear matriz de butacas organizadas por fila
        const matrizButacas = {};
        layout.butacas.forEach(butaca => {
            if (!matrizButacas[butaca.fila]) {
                matrizButacas[butaca.fila] = {};
            }
            matrizButacas[butaca.fila][butaca.numero] = butaca;

            console.log(`🪑 Butaca ${butaca.fila}-${butaca.numero} - Estado: ${butaca.estado}`);
        });

        // Generar filas
        for (let fila = 1; fila <= filas; fila++) {
            html += `<div class="fila-butacas" data-fila="${fila}">`;
            html += `<div class="fila-numero">Fila ${fila}</div>`;

            for (let numero = 1; numero <= columnas; numero++) {
                const butaca = matrizButacas[fila] && matrizButacas[fila][numero];

                if (butaca) {
                    const claseEstado = getClaseEstado(butaca.estado);
                    const esClickeable = butaca.estado === ESTADOS.DISPONIBLE;

                    html += `<div class="butaca ${claseEstado}" 
                            data-id="${butaca.id}" 
                            data-fila="${butaca.fila}" 
                            data-numero="${butaca.numero}"
                            data-label="${butaca.label}"
                            data-estado-num="${butaca.estado}"
                            data-estado-texto="${claseEstado}"
                            ${esClickeable ? 'onclick="toggleButaca(this)"' : ''}
                            style="cursor: ${esClickeable ? 'pointer' : 'not-allowed'}">
                            <i class="fa-solid fa-chair butaca-icon"></i>
                            <span class="numero-butaca">${butaca.numero}</span>
                        </div>`;
                } else {
                    html += '<div class="butaca-vacia"></div>';
                }
            }

            html += '</div>';
        }

        html += '</div>';
        container.innerHTML = html;

        // Aplicar colores
        setTimeout(() => {
            aplicarColoresButacas();
        }, 100);
    }

    function getClaseEstado(estado) {
        switch (parseInt(estado)) {
            case ESTADOS.DISPONIBLE:
                return 'disponible';
            case ESTADOS.BLOQUEADA:
                return 'bloqueada';
            case ESTADOS.RESERVADA:
                return 'ocupada';
            default:
                return 'bloqueada';
        }
    }

    function aplicarColoresButacas() {
        console.log('🎨 Aplicando colores a las butacas...');

        // Leyenda
        document.querySelectorAll('.disponible-icon').forEach(icon => {
            icon.style.setProperty('color', '#28a745', 'important');
        });

        document.querySelectorAll('.seleccionada-icon').forEach(icon => {
            icon.style.setProperty('color', '#2563eb', 'important');
        });

        document.querySelectorAll('.bloqueada-icon').forEach(icon => {
            icon.style.setProperty('color', '#dc3545', 'important');
        });

        document.querySelectorAll('.ocupada-icon').forEach(icon => {
            icon.style.setProperty('color', '#ff8c00', 'important');
        });

        // Butacas
        document.querySelectorAll('.butaca').forEach(butaca => {
            const icon = butaca.querySelector('.butaca-icon');
            const estadoTexto = butaca.getAttribute('data-estado-texto');

            if (icon) {
                let color = '#dc3545';

                switch (estadoTexto) {
                    case 'disponible':
                        color = '#28a745';
                        break;
                    case 'seleccionada':
                        color = '#2563eb';
                        break;
                    case 'bloqueada':
                        color = '#dc3545';
                        break;
                    case 'ocupada':
                        color = '#ff8c00';
                        break;
                }

                icon.style.setProperty('color', color, 'important');
                icon.style.color = color;
            }
        });
    }

    function toggleButaca(elemento) {
        const id = parseInt(elemento.getAttribute('data-id'));
        const estadoActual = elemento.getAttribute('data-estado-texto');

        if (estadoActual === 'disponible') {
            // Seleccionar
            elemento.classList.remove('disponible');
            elemento.classList.add('seleccionada');
            elemento.setAttribute('data-estado-texto', 'seleccionada');
            butacasSeleccionadas.push(id);
        } else if (estadoActual === 'seleccionada') {
            // Deseleccionar
            elemento.classList.remove('seleccionada');
            elemento.classList.add('disponible');
            elemento.setAttribute('data-estado-texto', 'disponible');
            const index = butacasSeleccionadas.indexOf(id);
            if (index > -1) {
                butacasSeleccionadas.splice(index, 1);
            }
        }

        aplicarColoresButacas();
        actualizarResumen();
    }

    function actualizarResumen() {
        const listaContainer = document.getElementById('lista-seleccionadas');
        const cantidad = butacasSeleccionadas.length;
        const total = cantidad * precioEntrada;

        document.getElementById('cantidad-total').textContent = cantidad;
        document.getElementById('monto-total').textContent = '$' + new Intl.NumberFormat('es-AR').format(total);
        document.getElementById('btn-procesar-venta').disabled = cantidad === 0;

        if (cantidad === 0) {
            listaContainer.innerHTML = '<p class="sin-seleccion">No hay butacas seleccionadas</p>';
        } else {
            let html = '';
            butacasSeleccionadas.forEach(id => {
                const elemento = document.querySelector(`[data-id="${id}"]`);
                const fila = elemento.getAttribute('data-fila');
                const numero = elemento.getAttribute('data-numero');

                html += `
                <div class="butaca-item">
                    <div class="butaca-info">
                        <i class="fas fa-couch icono-butaca"></i>
                        <span>Fila ${fila} - Butaca ${numero}</span>
                    </div>
                    <button class="btn-quitar" onclick="toggleButacaPorId(${id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            });
            listaContainer.innerHTML = html;
        }
    }

    function toggleButacaPorId(id) {
        const elemento = document.querySelector(`[data-id="${id}"]`);
        if (elemento) {
            toggleButaca(elemento);
        }
    }
    window.limpiarSeleccion = async function() {
        if (butacasSeleccionadas.length === 0) return;

        // USA TU FUNCIÓN mostrarConfirmacionModal
        const confirmado = await mostrarConfirmacionModal('¿Deseas limpiar todas las butacas seleccionadas?', 'warning');

        if (!confirmado) return;

        butacasSeleccionadas.forEach(id => {
            const elemento = document.querySelector(`[data-id="${id}"]`);
            if (elemento) {
                elemento.classList.remove('seleccionada');
                elemento.classList.add('disponible');
                elemento.setAttribute('data-estado-texto', 'disponible');
            }
        });

        butacasSeleccionadas = [];
        aplicarColoresButacas();
        actualizarResumen();
    }


    window.procesarVenta = async function() {
        if (butacasSeleccionadas.length === 0) {
            // USA TU FUNCIÓN mostrarAlerta
            mostrarAlerta('Debes seleccionar al menos una butaca', 'error');
            return;
        }

        const tipoPago = document.getElementById('tipo_pago').value;
        const tipoComprobante = document.getElementById('tipo_comprobante').value;
        const observaciones = document.getElementById('observaciones').value.trim();

        const nombreComprobante = tipoComprobante === 'TICKET' ? 'TICKET' : `FACTURA ${tipoComprobante}`;

        const confirmado = await mostrarConfirmacionModal(
            `¿Confirmar venta de ${butacasSeleccionadas.length} entrada(s) por $${new Intl.NumberFormat('es-AR').format(butacasSeleccionadas.length * precioEntrada)}?<br><br>Tipo: <strong>${nombreComprobante}</strong>`,
            'warning'
        );

        if (!confirmado) return;

        const btn = document.getElementById('btn-procesar-venta');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        try {
            const response = await fetch('/vendedor/funciones/procesar-venta', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_funcion: idFuncion,
                    butacas: butacasSeleccionadas,
                    tipo_pago: parseInt(tipoPago),
                    tipo_comprobante: tipoComprobante,
                    observaciones: observaciones
                })
            });

            const data = await response.json();

            if (data.ok) {
                mostrarAlerta(data.mensaje, 'exito');

                // Redirigir automáticamente después de 2 segundos
                setTimeout(() => {
                    window.location.href = data.redirigir;
                }, 2000);
            } else {
                // USA TU ALERTA DE ERROR
                mostrarAlerta(data.mensaje || 'Error al procesar venta', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Procesar Venta';
            }

        } catch (error) {
            console.error('Error:', error);
            // USA TU ALERTA DE ERROR
            mostrarAlerta('Error de conexión al procesar venta', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Procesar Venta';
        }
    }

    function mostrarAlerta(mensaje, tipo) {
        const alerta = document.createElement('div');
        alerta.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${tipo === 'error' ? '#fed7d7' : '#c6f6d5'};
        color: ${tipo === 'error' ? '#742a2a' : '#22543d'};
        padding: 1.5rem 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        font-weight: 600;
        max-width: 400px;
    `;

        alerta.innerHTML = `
        <i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : 'check-circle'}" style="font-size: 1.5rem;"></i>
        <span>${mensaje}</span>
    `;

        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => alerta.remove(), 300);
        }, 4000);
    }

    function mostrarError(mensaje) {
        document.getElementById('mapa-butacas').innerHTML = `
        <div class="error-butacas">
            <i class="fas fa-exclamation-triangle"></i>
            <p>${mensaje}</p>
        </div>
    `;
    }

    // Animaciones CSS
    const style = document.createElement('style');
    style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
    document.head.appendChild(style);
</script>