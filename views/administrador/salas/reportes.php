<div class="contenedor-reportes">
    <div class="reporte-header">
        <h1><i class="fa-solid fa-door-open"></i> Reportes de Salas</h1>
        <div class="acciones-reporte">
            <a href="/administrador/salas/listado" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button id="btnModoOscuro" class="btn-modo-oscuro">
                <i class="fa-solid fa-moon"></i> Modo Nocturno
            </button>
        </div>
    </div>

    <!-- Filtro de fechas -->
    <div class="filtros-reportes" style="margin-bottom:25px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span style="font-weight:600; color:#ed850f;">
                <i class="fa-solid fa-calendar"></i> Período:
            </span>
            <input type="date" id="fechaDesde"
                value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>"
                style="padding:8px 12px; background:#1a202c; border:1px solid #4a5568; border-radius:6px; color:#fff;">
            <span style="color:#a0aec0;">hasta</span>
            <input type="date" id="fechaHasta"
                value="<?php echo date('Y-m-d'); ?>"
                style="padding:8px 12px; background:#1a202c; border:1px solid #4a5568; border-radius:6px; color:#fff;">
            <button onclick="cargarDatos()"
                style="padding:8px 16px; background:#ed850f; border:none; border-radius:6px; color:#fff; cursor:pointer; font-weight:600;">
                <i class="fa-solid fa-magnifying-glass"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Tarjetas -->
    <div class="tarjetas-totales" style="grid-template-columns:repeat(4,1fr);">
        <div class="tarjeta-total">
            <i class="fa-solid fa-door-open"></i>
            <div class="tarjeta-valor" id="totalSalas">-</div>
            <div class="tarjeta-label">Salas</div>
        </div>
        <div class="tarjeta-total">
            <i class="fa-solid fa-couch"></i>
            <div class="tarjeta-valor" id="totalCapacidad">-</div>
            <div class="tarjeta-label">Capacidad total</div>
        </div>
        <div class="tarjeta-total">
            <i class="fa-solid fa-clapperboard"></i>
            <div class="tarjeta-valor" id="totalFunciones">-</div>
            <div class="tarjeta-label">Funciones</div>
        </div>
        <div class="tarjeta-total">
            <i class="fa-solid fa-ticket"></i>
            <div class="tarjeta-valor" id="totalEntradas">-</div>
            <div class="tarjeta-label">Entradas vendidas</div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="graficos-grid">
        <div class="grafico-container">
            <h3 class="grafico-titulo"><i class="fa-solid fa-ticket"></i> Entradas por sala</h3>
            <canvas id="graficoEntradas"></canvas>
        </div>
        <div class="grafico-container">
            <h3 class="grafico-titulo"><i class="fa-solid fa-percent"></i> % Ocupación por sala</h3>
            <canvas id="graficoOcupacion"></canvas>
        </div>
    </div>

    <div style="margin-top:25px;">
        <div class="grafico-container">
            <h3 class="grafico-titulo"><i class="fa-solid fa-list"></i> Detalle por sala</h3>
            <div id="tablaSalas" style="overflow-x:auto;"></div>
        </div>
    </div>
</div>

<style>
    .contenedor-reportes {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
        background: #fff;
        border-radius: 12px;
        transition: background 0.3s ease;
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
        color: #1a202c;
        font-size: 1.6rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .reporte-header h1 i {
        color: #ed850f;
    }

    .acciones-reporte {
        display: flex;
        gap: 12px;
    }

    .btn-volver,
    .btn-modo-oscuro {
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
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-volver:hover,
    .btn-modo-oscuro:hover {
        border-color: #ed850f;
        background: #364154;
    }

    .filtros-reportes {
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }

    .tarjetas-totales {
        display: grid;
        gap: 20px;
        margin-bottom: 25px;
    }

    .tarjeta-total {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }

    .tarjeta-total i {
        font-size: 2rem !important;
        color: #ed850f;
        margin-bottom: 12px;
        display: block;
    }

    .tarjeta-valor {
        font-size: 2rem !important;
        font-weight: bold;
        color: #1a202c;
        margin-bottom: 5px;
    }

    .tarjeta-label {
        color: #718096;
        font-size: 13px !important;
    }

    .graficos-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .grafico-container {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        border: 1px solid #e2e8f0;
    }

    .grafico-titulo {
        color: #1a202c;
        font-size: 1rem;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 12px;
    }

    .grafico-titulo i {
        color: #ed850f;
    }

    /* MODO OSCURO */
    .contenedor-reportes.modo-oscuro {
        background: #1a202c;
    }

    .contenedor-reportes.modo-oscuro .reporte-header h1 {
        color: #fff;
    }

    .contenedor-reportes.modo-oscuro .filtros-reportes {
        background: #2d3748;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro .tarjeta-total {
        background: #2d3748;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro .tarjeta-valor {
        color: #fff;
    }

    .contenedor-reportes.modo-oscuro .tarjeta-label {
        color: #a0aec0;
    }

    .contenedor-reportes.modo-oscuro .grafico-container {
        background: #2d3748;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro .grafico-titulo {
        color: #fff;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro table {
        color: #fff;
    }

    .contenedor-reportes.modo-oscuro th {
        background: #1a202c !important;
        color: #ed850f !important;
        border-color: #4a5568 !important;
    }

    .contenedor-reportes.modo-oscuro td {
        border-color: #4a5568 !important;
    }

    @media (max-width:768px) {
        .graficos-grid {
            grid-template-columns: 1fr;
        }

        .tarjetas-totales {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const COLORES = ['#ed850f', '#3b82f6', '#22c55e', '#a855f7', '#ef4444', '#f59e0b', '#06b6d4', '#ec4899'];
    let charts = {};

    document.addEventListener('DOMContentLoaded', () => {
        cargarDatos();
        iniciarModoOscuro();
    });

    async function cargarDatos() {
        const desde = document.getElementById('fechaDesde').value;
        const hasta = document.getElementById('fechaHasta').value;

        try {
            const res = await fetch(`/administrador/salas/reportes/api-datos?fecha_desde=${desde}&fecha_hasta=${hasta}`);
            const data = await res.json();
            if (!data.ok) return;

            document.getElementById('totalSalas').textContent = data.totales.salas;
            document.getElementById('totalCapacidad').textContent = data.totales.capacidad;
            document.getElementById('totalFunciones').textContent = data.totales.funciones;
            document.getElementById('totalEntradas').textContent = data.totales.entradas;

            const labels = data.salas.map(s => s.sala);
            const colores = labels.map((_, i) => COLORES[i % COLORES.length]);

            renderBarras('graficoEntradas', labels, data.salas.map(s => s.entradas_vendidas), 'Entradas', colores);
            renderBarras('graficoOcupacion', labels, data.salas.map(s => s.ocupacion), '% Ocupación', colores);
            renderTabla(data.salas);
        } catch (err) {
            console.error(err);
        }
    }

    function renderBarras(id, labels, valores, label, colores) {
        const ctx = document.getElementById(id).getContext('2d');
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label,
                    data: valores,
                    backgroundColor: colores,
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderTabla(salas) {
        document.getElementById('tablaSalas').innerHTML = `
        <table style="width:100%; border-collapse:collapse; font-size:13px !important;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:10px 14px; text-align:left; border-bottom:2px solid #e2e8f0; color:#ed850f;">Sala</th>
                    <th style="padding:10px 14px; text-align:right; border-bottom:2px solid #e2e8f0; color:#ed850f;">Capacidad</th>
                    <th style="padding:10px 14px; text-align:right; border-bottom:2px solid #e2e8f0; color:#ed850f;">Funciones</th>
                    <th style="padding:10px 14px; text-align:right; border-bottom:2px solid #e2e8f0; color:#ed850f;">Entradas</th>
                    <th style="padding:10px 14px; text-align:right; border-bottom:2px solid #e2e8f0; color:#ed850f;">Cap. Total Período</th>
                    <th style="padding:10px 14px; text-align:right; border-bottom:2px solid #e2e8f0; color:#ed850f;">% Ocupación</th>
                </tr>
            </thead>
            <tbody>
                ${salas.map(s => `
                    <tr>
                        <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; font-weight:600;">${s.sala}</td>
                        <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:right;">${s.capacidad}</td>
                        <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:right;">${s.funciones}</td>
                        <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:right;">${s.entradas_vendidas}</td>
                        <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:right;">${s.capacidad_total}</td>
                        <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:right; color:#ed850f; font-weight:600;">${s.ocupacion}%</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>`;
    }

    function iniciarModoOscuro() {
        const contenedor = document.querySelector('.contenedor-reportes');
        const btn = document.getElementById('btnModoOscuro');
        const KEY = 'modo_oscuro_reportes';

        function aplicar(oscuro) {
            contenedor.classList.toggle('modo-oscuro', oscuro);
            btn.innerHTML = oscuro ? '<i class="fa-solid fa-sun"></i> Modo Día' : '<i class="fa-solid fa-moon"></i> Modo Nocturno';
            const ct = oscuro ? '#cbd5e0' : '#666';
            const cg = oscuro ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';
            Object.values(charts).forEach(c => {
                if (c.options.scales?.y) {
                    c.options.scales.y.ticks.color = ct;
                    c.options.scales.y.grid.color = cg;
                }
                if (c.options.scales?.x) c.options.scales.x.ticks.color = ct;
                c.update();
            });
        }
        aplicar(localStorage.getItem(KEY) === 'true');
        btn.addEventListener('click', () => {
            const n = !contenedor.classList.contains('modo-oscuro');
            aplicar(n);
            localStorage.setItem(KEY, n);
        });
    }
</script>