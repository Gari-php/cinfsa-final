<div class="contenedor-reportes">
    <div class="reporte-header">
        <h1><i class="fa-solid fa-chart-line"></i> Reportes Gráficos - Ventas Web</h1>
        <div class="acciones-reporte">
            <a href="/administrador/movimientos-web/listado" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver al Listado
            </a>
            <button id="btnModoOscuro" class="btn-modo-oscuro">
                <i class="fa-solid fa-moon"></i> Modo Nocturno
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-reportes">
        <div class="filtro-grupo">
            <label>Tipo de Reporte:</label>
            <select id="tipoGrafico">
                <option value="ventas-periodo">Ventas por Período</option>
                <option value="distribucion-productos">Distribución de Productos</option>
                <option value="peliculas-populares">Películas Más Vendidas</option>
                <option value="top-cantina">Top Productos de Cantina</option>
                <option value="top-maquinas">Top Máquinas (Fichas)</option>
                <option value="estados-ordenes">Estados de Órdenes</option>
                <option value="metodos-pago">Métodos de Pago</option>
                <option value="ocupacion-salas">Ocupación de Salas (Web)</option>
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

    .btn-primary,
    .btn-secondary {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        font-size: 14px !important;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: #ed850f;
        color: white;
    }

    .btn-primary:hover {
        background: #d67a0e;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
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
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px !important;
        opacity: 0.9;
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

    .reporte-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .reporte-header h1 {
        margin: 0;
    }

    .acciones-reporte {
        display: flex;
        gap: 12px;
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
        font-weight: 600;
        font-size: 14px !important;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-modo-oscuro:hover {
        border-color: #ed850f;
        background: #364154;
    }

    /* MODO OSCURO */
    .contenedor-reportes.modo-oscuro {
        background: #1a202c;
    }

    .contenedor-reportes.modo-oscuro h1 {
        color: #fff;
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
        border-color: #4a5568;
        color: #fff;
    }

    .contenedor-reportes.modo-oscuro .grafico-container {
        background: #2d3748;
        border-color: #4a5568;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Configuración específica para este módulo
    window.GRAFICO_CONFIG = {
        modulo: 'web',
        urlDatos: '/administrador/movimientos-web/datos-grafico'
    };
</script>

<script>
    function iniciarModoOscuro() {
        const contenedor = document.querySelector('.contenedor-reportes');
        const btn = document.getElementById('btnModoOscuro');
        const STORAGE_KEY = 'modo_oscuro_reportes_web';

        function aplicarModo(oscuro) {
            contenedor.classList.toggle('modo-oscuro', oscuro);
            btn.innerHTML = oscuro ?
                '<i class="fa-solid fa-sun"></i> Modo Día' :
                '<i class="fa-solid fa-moon"></i> Modo Nocturno';
        }

        const guardado = localStorage.getItem(STORAGE_KEY) === 'true';
        aplicarModo(guardado);

        btn.addEventListener('click', () => {
            const nuevo = !contenedor.classList.contains('modo-oscuro');
            aplicarModo(nuevo);
            localStorage.setItem(STORAGE_KEY, nuevo);
        });
    }
</script>
<script>
    window.GRAFICO_CONFIG = {
        modulo: 'web',
        urlDatos: '/administrador/movimientos-web/datos-grafico'
    };
</script>
<script src="/assets/js/graficos-web.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', iniciarModoOscuro);
</script>