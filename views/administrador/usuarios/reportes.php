<div class="contenedor-reportes">
    <div class="reporte-header">
        <h1><i class="fa-solid fa-chart-pie"></i> Reportes de Usuarios</h1>
        <div class="acciones-reporte">
            <a href="/administrador/usuarios/listado" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button id="btnModoOscuro" class="btn-modo-oscuro">
                <i class="fa-solid fa-moon"></i> Modo Nocturno
            </button>
        </div>
    </div>

    <div class="filtros-reportes">
        <div class="filtro-grupo">
            <label>Total de usuarios registrados:</label>
            <span id="totalUsuarios" style="color: #ed850f; font-weight: bold; font-size: 1.2rem;">
                Cargando...
            </span>
        </div>
    </div>

    <div class="graficos-grid">
        <div class="grafico-container grafico-grande">
            <h3 class="grafico-titulo">
                <i class="fa-solid fa-venus-mars"></i> Distribución por Sexo
            </h3>
            <div class="grafico-wrapper-pie">
                <canvas id="graficoPie"></canvas>
            </div>
            <div id="leyendaSexo" class="leyenda-custom"></div>
        </div>

        <div class="grafico-container grafico-chico">
            <h3 class="grafico-titulo">
                <i class="fa-solid fa-bars"></i> Usuarios por Sexo
            </h3>
            <canvas id="graficoBarras"></canvas>
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
        transition: background 0.3s ease, color 0.3s ease;
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

    .filtros-reportes {
        background: #f8f9fa;
        padding: 20px 25px;
        border-radius: 10px;
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .filtro-grupo label {
        color: #4a5568;
        font-weight: 600;
        font-size: 14px !important;
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

    .grafico-wrapper-pie {
        width: 280px;
        height: 280px;
        margin: 0 auto 20px auto;
    }

    .leyenda-custom {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }

    .leyenda-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px !important;
    }

    .leyenda-color {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .leyenda-nombre {
        flex: 1;
        color: #4a5568;
    }

    .leyenda-valor {
        font-weight: bold;
        color: #1a202c;
    }

    .leyenda-pct {
        color: #ed850f;
        font-weight: 600;
        min-width: 45px;
        text-align: right;
    }

    /* ===== MODO OSCURO ===== */
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

    .contenedor-reportes.modo-oscuro .filtro-grupo label {
        color: #cbd5e0;
    }

    .contenedor-reportes.modo-oscuro .grafico-container {
        background: #2d3748;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro .grafico-titulo {
        color: #fff;
        border-color: #4a5568;
    }

    .contenedor-reportes.modo-oscuro .leyenda-nombre {
        color: #cbd5e0;
    }

    .contenedor-reportes.modo-oscuro .leyenda-valor {
        color: #fff;
    }

    @media (max-width: 768px) {
        .graficos-grid {
            grid-template-columns: 1fr;
        }
        .grafico-wrapper-pie {
            width: 220px;
            height: 220px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const COLORES = [
    '#ed850f', '#3b82f6', '#22c55e', '#a855f7',
    '#ef4444', '#f59e0b', '#06b6d4', '#ec4899'
];

let graficoPie = null;
let graficoBarras = null;

document.addEventListener('DOMContentLoaded', async () => {
    await cargarDatos();
    iniciarModoOscuro();
});

async function cargarDatos() {
    try {
        const response = await fetch('/administrador/usuarios/reportes/api-sexo');
        const data = await response.json();

        if (!data.ok) {
            console.error('Error al cargar datos:', data.mensaje);
            return;
        }

        document.getElementById('totalUsuarios').textContent =
            data.total + ' usuario' + (data.total !== 1 ? 's' : '');

        const labels = data.datos.map(d => d.nombre);
        const valores = data.datos.map(d => d.total);
        const colores = labels.map((_, i) => COLORES[i % COLORES.length]);

        renderPie(labels, valores, colores, data.total);
        renderBarras(labels, valores, colores);
        renderLeyenda(data.datos, colores, data.total);

    } catch (error) {
        console.error('Error al cargar datos:', error);
    }
}

function renderPie(labels, valores, colores, total) {
    const ctx = document.getElementById('graficoPie').getContext('2d');
    if (graficoPie) graficoPie.destroy();

    graficoPie = new Chart(ctx, {
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
            cutout: '60%'
        }
    });
}

function renderBarras(labels, valores, colores) {
    const ctx = document.getElementById('graficoBarras').getContext('2d');
    if (graficoBarras) graficoBarras.destroy();

    graficoBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Usuarios',
                data: valores,
                backgroundColor: colores,
                borderRadius: 6,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.raw} usuario${ctx.raw !== 1 ? 's' : ''}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: { color: 'rgba(0,0,0,0.08)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

function renderLeyenda(datos, colores, total) {
    const leyenda = document.getElementById('leyendaSexo');
    leyenda.innerHTML = datos.map((d, i) => {
        const pct = total > 0 ? ((d.total / total) * 100).toFixed(1) : '0.0';
        return `
            <div class="leyenda-item">
                <span class="leyenda-color" style="background:${colores[i]}"></span>
                <span class="leyenda-nombre">${d.nombre}</span>
                <span class="leyenda-valor">${d.total}</span>
                <span class="leyenda-pct">${pct}%</span>
            </div>
        `;
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
        const colorGrid = oscuro ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';

        if (graficoBarras) {
            graficoBarras.options.scales.y.ticks.color = colorTexto;
            graficoBarras.options.scales.x.ticks.color = colorTexto;
            graficoBarras.options.scales.y.grid.color = colorGrid;
            graficoBarras.update();
        }
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