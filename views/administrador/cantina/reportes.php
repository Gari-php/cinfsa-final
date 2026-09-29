<div class="contenedor-reportes">
    <h1><i class="fa-solid fa-chart-line"></i> Reportes Gráficos de Cantina</h1>

    <div class="acciones-reporte">
        <a href="/administrador/cantina/listado" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
        <button id="btnModoOscuro" class="btn-modo-oscuro">
            <i class="fa-solid fa-moon"></i> Modo Nocturno
        </button>
    </div>

    <!-- Filtros -->
    <div class="filtros-reportes">
        <div class="filtro-grupo">
            <label>Tipo de Reporte:</label>
            <select id="tipoGrafico">
                <option value="ventas-periodo">Ventas por Período</option>
                <option value="productos-populares">Productos Más Vendidos</option>
                <option value="ventas-fichas">Ventas de Fichas</option>
                <option value="formas-pago">Formas de Pago Utilizadas</option>
                <option value="stock-productos">Stock de Productos</option>
                <option value="rendimiento-vendedores">Rendimiento de Vendedores</option>
                <option value="ventas-caja">Ventas por Caja</option>
                <option value="tipos-comprobante">Tipos de Comprobante</option>
            </select>
        </div>

        <div class="filtro-grupo">
            <label>Desde:</label>
            <input type="date" id="fechaDesde" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
        </div>

        <div class="filtro-grupo">
            <label>Hasta:</label>
            <input type="date" id="fechaHasta" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="filtro-grupo" id="grupoAgrupar">
            <label>Agrupar por:</label>
            <select id="agruparPor">
                <option value="dia">Día</option>
                <option value="mes">Mes</option>
                <option value="anio">Año</option>
            </select>
        </div>

        <button id="btnActualizar" class="btn-primary">
            <i class="fa-solid fa-sync"></i> Actualizar
        </button>
    </div>

    <!-- Canvas para el gráfico -->
    <div class="grafico-container">
        <canvas id="chartPrincipal"></canvas>
    </div>

    <!-- Resumen estadístico -->
    <div id="resumenEstadistico" class="resumen-estadistico"></div>
</div>

<style>
    .contenedor-reportes {
        max-width: 1400px;
        margin: 140px auto 40px;
        padding: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .contenedor-reportes h1 {
        color: #2d3748;
        margin-bottom: 30px;
        font-size: 24px !important;
    }

    .filtros-reportes {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: end;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .filtro-grupo {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filtro-grupo label {
        font-weight: 600;
        color: #495057;
        font-size: 13px !important;
    }

    .filtro-grupo select,
    .filtro-grupo input[type="date"] {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        font-size: 14px !important;
        min-width: 150px;
    }

    .btn-primary {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        font-size: 14px !important;
        background: #ed850f;
        color: white;
    }

    .btn-primary:hover {
        background: #d67a0e;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(237, 133, 15, 0.3);
    }

    .grafico-container {
        position: relative;
        height: 450px;
        margin: 30px 0;
        padding: 20px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    .resumen-estadistico {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .stat-card {
        padding: 20px;
        background: linear-gradient(135deg, #ed850f 0%, #ff9f40 100%);
        color: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px !important;
        opacity: 0.9;
        font-weight: 600;
    }

    .stat-card .valor {
        font-size: 32px !important;
        font-weight: bold;
        margin: 5px 0;
    }

    .stat-card .detalle {
        font-size: 12px !important;
        opacity: 0.8;
    }

    /* Estilos para loading */
    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 400px;
    }

    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #ed850f;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    .acciones-reporte {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
    }

    .btn-volver {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: #2d3748;
        color: #fff;
        border: 1px solid #4a5568;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px !important;
        transition: all 0.2s ease;
    }

    .btn-volver:hover {
        border-color: #ed850f;
        background: #364154;
    }

    .btn-modo-oscuro {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: #2d3748;
        color: #fff;
        border: 1px solid #4a5568;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px !important;
        transition: all 0.2s ease;
    }

    .btn-modo-oscuro:hover {
        border-color: #ed850f;
        background: #364154;
    }

    /* ===== MODO OSCURO ===== */
    .contenedor-reportes.modo-oscuro {
        background: #1a202c;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
    }

    .contenedor-reportes.modo-oscuro h1 {
        color: #ed850f;
    }

    .contenedor-reportes.modo-oscuro .filtros-reportes {
        background: #2d3748;
    }

    .contenedor-reportes.modo-oscuro .filtro-grupo label {
        color: #cbd5e0;
    }

    .contenedor-reportes.modo-oscuro .filtro-grupo select,
    .contenedor-reportes.modo-oscuro .filtro-grupo input[type="date"] {
        background: #1a202c;
        color: #fff;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro .filtro-grupo input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    .contenedor-reportes.modo-oscuro .grafico-container {
        background: #2d3748;
        border-color: #4a5568;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filtros-reportes {
            flex-direction: column;
        }

        .filtro-grupo {
            width: 100%;
        }

        .filtro-grupo select,
        .filtro-grupo input[type="date"] {
            width: 100%;
        }

        .btn-primary {
            width: 100%;
        }

        .grafico-container {
            height: 350px;
        }

        .resumen-estadistico {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script src="/assets/js/graficos-productos.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const contenedor = document.querySelector('.contenedor-reportes');
        const btnModoOscuro = document.getElementById('btnModoOscuro');
        const STORAGE_KEY = 'modo_oscuro_reportes';

        function aplicarModo(oscuro) {
            contenedor.classList.toggle('modo-oscuro', oscuro);
            btnModoOscuro.innerHTML = oscuro ?
                '<i class="fa-solid fa-sun"></i> Modo Día' :
                '<i class="fa-solid fa-moon"></i> Modo Nocturno';
        }

        // Aplicar preferencia guardada al cargar
        const preferenciaGuardada = localStorage.getItem(STORAGE_KEY) === 'true';
        aplicarModo(preferenciaGuardada);

        btnModoOscuro.addEventListener('click', () => {
            const nuevoEstado = !contenedor.classList.contains('modo-oscuro');
            aplicarModo(nuevoEstado);
            localStorage.setItem(STORAGE_KEY, nuevoEstado);
        });
    });
</script>