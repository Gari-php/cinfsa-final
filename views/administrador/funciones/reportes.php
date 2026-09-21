<div class="contenedor-reportes">

    <div class="reporte-header">
        <h1><i class="fa-solid fa-chart-line"></i> Reportes de Funciones</h1>
        <div class="acciones-reporte">
            <a href="/administrador/funciones/listado" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button id="btnModoOscuro" class="btn-modo-oscuro">
                <i class="fa-solid fa-moon"></i> Modo Nocturno
            </button>
        </div>
    </div>

    <!-- Tarjetas de totales -->
    <div class="tarjetas-totales">
        <div class="tarjeta-total">
            <i class="fa-solid fa-clapperboard"></i>
            <div class="tarjeta-valor" id="totalFunciones">-</div>
            <div class="tarjeta-label">Funciones totales</div>
        </div>
        <div class="tarjeta-total">
            <i class="fa-solid fa-ticket"></i>
            <div class="tarjeta-valor" id="totalEntradas">-</div>
            <div class="tarjeta-label">Entradas vendidas</div>
        </div>
        <div class="tarjeta-total">
            <i class="fa-solid fa-dollar-sign"></i>
            <div class="tarjeta-valor" id="totalRecaudacion">-</div>
            <div class="tarjeta-label">Recaudación total</div>
        </div>
    </div>

    <!-- Gráficos fila 1 -->
    <div class="graficos-grid">
        <div class="grafico-container">
            <h3 class="grafico-titulo">
                <i class="fa-solid fa-film"></i> Top 5 Películas más vendidas
            </h3>
            <canvas id="graficoPeliculas"></canvas>
        </div>
        <div class="grafico-container">
            <h3 class="grafico-titulo">
                <i class="fa-solid fa-building"></i> Salas más ocupadas
            </h3>
            <canvas id="graficoSalas"></canvas>
        </div>
    </div>

    <!-- Gráficos fila 2 -->
    <div class="graficos-grid" style="margin-top: 25px;">
        <div class="grafico-container">
            <h3 class="grafico-titulo">
                <i class="fa-solid fa-clock"></i> Horario pico de funciones
            </h3>
            <canvas id="graficoHorarios"></canvas>
        </div>
        <div class="grafico-container">
            <h3 class="grafico-titulo">
                <i class="fa-solid fa-circle-info"></i> Funciones por estado
            </h3>
            <div class="grafico-wrapper-pie">
                <canvas id="graficoEstados"></canvas>
            </div>
            <div id="leyendaEstados" class="leyenda-custom"></div>
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

    .reporte-header h1 i { color: #ed850f; }

    .acciones-reporte { display: flex; gap: 12px; }

    .btn-volver, .btn-modo-oscuro {
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

    .btn-volver:hover, .btn-modo-oscuro:hover {
        border-color: #ed850f;
        background: #364154;
    }

    .tarjetas-totales {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    .tarjeta-total {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .tarjeta-total i {
        font-size: 2rem !important;
        color: #ed850f;
        margin-bottom: 12px;
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

    .grafico-titulo i { color: #ed850f; }

    .grafico-wrapper-pie {
        width: 220px;
        height: 220px;
        margin: 0 auto 15px auto;
    }

    .leyenda-custom {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px !important;
    }

    .leyenda-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .leyenda-nombre { flex: 1; color: #4a5568; }
    .leyenda-valor { font-weight: bold; color: #1a202c; }

    /* MODO OSCURO */
    .contenedor-reportes.modo-oscuro { background: #1a202c; }
    .contenedor-reportes.modo-oscuro .reporte-header h1 { color: #fff; }
    .contenedor-reportes.modo-oscuro .tarjeta-total { background: #2d3748; border-color: #4a5568; }
    .contenedor-reportes.modo-oscuro .tarjeta-valor { color: #fff; }
    .contenedor-reportes.modo-oscuro .tarjeta-label { color: #a0aec0; }
    .contenedor-reportes.modo-oscuro .grafico-container { background: #2d3748; border-color: #4a5568; }
    .contenedor-reportes.modo-oscuro .grafico-titulo { color: #fff; border-color: #4a5568; }
    .contenedor-reportes.modo-oscuro .leyenda-nombre { color: #cbd5e0; }
    .contenedor-reportes.modo-oscuro .leyenda-valor { color: #fff; }

    @media (max-width: 768px) {
        .graficos-grid { grid-template-columns: 1fr; }
        .tarjetas-totales { grid-template-columns: 1fr; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const COLORES = [
    '#ed850f', '#3b82f6', '#22c55e', '#a855f7',
    '#ef4444', '#f59e0b', '#06b6d4', '#ec4899'
];

let charts = {};

document.addEventListener('DOMContentLoaded', async () => {
    await cargarDatos();
    iniciarModoOscuro();
});

async function cargarDatos() {
    try {
        const res = await fetch('/administrador/funciones/reportes/api-datos');
        const data = await res.json();

        if (!data.ok) return;

        // Tarjetas totales
        document.getElementById('totalFunciones').textContent = data.totales.funciones;
        document.getElementById('totalEntradas').textContent = data.totales.entradas;
        document.getElementById('totalRecaudacion').textContent =
            '$' + Number(data.totales.recaudacion).toLocaleString('es-AR', {minimumFractionDigits: 0});

        // Gráficos
        renderBarrasHorizontales('graficoPeliculas', 
            data.peliculas.map(p => p.titulo),
            data.peliculas.map(p => p.entradas),
            'Entradas vendidas'
        );

        renderBarras('graficoSalas',
            data.salas.map(s => s.nombre),
            data.salas.map(s => s.entradas),
            'Entradas vendidas'
        );

        renderBarras('graficoHorarios',
            data.horarios.map(h => h.franja),
            data.horarios.map(h => h.total),
            'Entradas vendidas'
        );

        renderPie('graficoEstados', 'leyendaEstados',
            data.estados.map(e => e.estado),
            data.estados.map(e => e.total)
        );

    } catch (err) {
        console.error('Error al cargar datos:', err);
    }
}

function renderBarrasHorizontales(id, labels, valores, labelDataset) {
    const ctx = document.getElementById(id).getContext('2d');
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: labelDataset,
                data: valores,
                backgroundColor: COLORES,
                borderRadius: 6,
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.08)' } },
                y: { grid: { display: false } }
            }
        }
    });
}

function renderBarras(id, labels, valores, labelDataset) {
    const ctx = document.getElementById(id).getContext('2d');
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: labelDataset,
                data: valores,
                backgroundColor: COLORES,
                borderRadius: 6,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.08)' } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderPie(canvasId, leyendaId, labels, valores) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    if (charts[canvasId]) charts[canvasId].destroy();
    const total = valores.reduce((a, b) => a + b, 0);
    const colores = labels.map((_, i) => COLORES[i % COLORES.length]);

    charts[canvasId] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: valores,
                backgroundColor: colores,
                borderColor: '#fff',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const pct = ((ctx.raw / total) * 100).toFixed(1);
                            return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
                        }
                    }
                }
            },
            cutout: '55%'
        }
    });

    const leyenda = document.getElementById(leyendaId);
    leyenda.innerHTML = labels.map((l, i) => {
        const pct = total > 0 ? ((valores[i] / total) * 100).toFixed(1) : '0.0';
        return `
            <div class="leyenda-item">
                <span class="leyenda-color" style="background:${colores[i]}"></span>
                <span class="leyenda-nombre">${l}</span>
                <span class="leyenda-valor">${valores[i]}</span>
                <span style="color:#ed850f; font-size:12px !important;">${pct}%</span>
            </div>`;
    }).join('');
}

function iniciarModoOscuro() {
    const contenedor = document.querySelector('.contenedor-reportes');
    const btn = document.getElementById('btnModoOscuro');
    const STORAGE_KEY = 'modo_oscuro_reportes';

    function aplicarModo(oscuro) {
        contenedor.classList.toggle('modo-oscuro', oscuro);
        btn.innerHTML = oscuro
            ? '<i class="fa-solid fa-sun"></i> Modo Día'
            : '<i class="fa-solid fa-moon"></i> Modo Nocturno';

        const colorTexto = oscuro ? '#cbd5e0' : '#666';
        const colorGrid  = oscuro ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';

        Object.values(charts).forEach(chart => {
            if (chart.config.type !== 'doughnut') {
                const scales = chart.options.scales;
                if (scales.x) { scales.x.ticks = { ...scales.x.ticks, color: colorTexto }; }
                if (scales.y) { scales.y.ticks = { ...scales.y.ticks, color: colorTexto }; scales.y.grid = { color: colorGrid }; }
                chart.update();
            }
        });
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