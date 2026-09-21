class SistemaGraficos {
    constructor() {
        this.chart = null;
        this.config = window.GRAFICO_CONFIG || {};
        this.tipoActual = 'ventas-periodo';
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.cargarGrafico();
        this.controlarVisibilidadFiltros();
    }
    
    setupEventListeners() {
        document.getElementById('btnActualizar')?.addEventListener('click', () => {
            this.cargarGrafico();
        });
        
        document.getElementById('tipoGrafico')?.addEventListener('change', (e) => {
            this.tipoActual = e.target.value;
            this.controlarVisibilidadFiltros();
        });
    }
    
    controlarVisibilidadFiltros() {
        const grupoAgrupar = document.getElementById('grupoAgrupar');
        if (grupoAgrupar) {
            grupoAgrupar.style.display = this.tipoActual === 'ventas-periodo' ? 'flex' : 'none';
        }
    }
    
    async cargarGrafico() {
        try {
            const fechaDesde = document.getElementById('fechaDesde').value;
            const fechaHasta = document.getElementById('fechaHasta').value;
            const agrupar = document.getElementById('agruparPor')?.value || 'dia';
            
            const url = `${this.config.urlDatos}?tipo=${this.tipoActual}&fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&agrupar=${agrupar}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.ok) {
                this.renderizarGrafico(data.datos);
                this.mostrarResumen(data.datos);
            } else {
                alert(data.mensaje || 'Error al cargar datos');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al cargar el gráfico');
        }
    }
    
    renderizarGrafico(datos) {
        const ctx = document.getElementById('chartPrincipal');
        
        if (this.chart) {
            this.chart.destroy();
        }
        
        const config = this.obtenerConfiguracion(datos);
        this.chart = new Chart(ctx, config);
    }
    
    obtenerConfiguracion(datos) {
        switch(this.tipoActual) {
            case 'ventas-periodo':
                return this.configVentasPeriodo(datos);
            case 'peliculas-populares':
                return this.configPeliculasPopulares(datos);
            case 'tipos-entrada':
                return this.configTiposEntrada(datos);
            case 'ocupacion-salas':
                return this.configOcupacionSalas(datos);
            case 'estados-entradas':
                return this.configEstadosEntradas(datos);
            default:
                return this.configVentasPeriodo(datos);
        }
    }
    
    configVentasPeriodo(datos) {
        return {
            type: 'line',
            data: {
                labels: datos.map(d => d.periodo),
                datasets: [
                    {
                        label: 'Entradas Vendidas',
                        data: datos.map(d => d.total_entradas),
                        borderColor: '#ed850f',
                        backgroundColor: 'rgba(237, 133, 15, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Recaudación ($)',
                        data: datos.map(d => d.total_recaudado),
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Ventas por Período',
                        font: { size: 16 }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Entradas'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Recaudación ($)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        };
    }
    
    configPeliculasPopulares(datos) {
        return {
            type: 'bar',
            data: {
                labels: datos.map(d => d.pelicula),
                datasets: [{
                    label: 'Entradas Vendidas',
                    data: datos.map(d => d.total_entradas),
                    backgroundColor: '#ed850f',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Películas Más Vendidas',
                        font: { size: 16 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Entradas'
                        }
                    }
                }
            }
        };
    }
    
    configTiposEntrada(datos) {
        return {
            type: 'doughnut',
            data: {
                labels: datos.map(d => d.tipo),
                datasets: [{
                    data: datos.map(d => d.cantidad),
                    backgroundColor: [
                        '#ed850f',
                        '#4caf50',
                        '#2196f3',
                        '#ff9800',
                        '#9c27b0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución por Tipo de Entrada',
                        font: { size: 16 }
                    },
                    legend: {
                        position: 'right'
                    }
                }
            }
        };
    }
    
    configOcupacionSalas(datos) {
        return {
            type: 'bar',
            data: {
                labels: datos.map(d => d.sala),
                datasets: [{
                    label: 'Ocupación (%)',
                    data: datos.map(d => d.ocupacion),
                    backgroundColor: datos.map(d => 
                        d.ocupacion >= 80 ? '#4caf50' :
                        d.ocupacion >= 50 ? '#ff9800' : '#f44336'
                    ),
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Ocupación de Salas',
                        font: { size: 16 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentaje %'
                        }
                    }
                }
            }
        };
    }
    
    configEstadosEntradas(datos) {
        return {
            type: 'pie',
            data: {
                labels: datos.map(d => d.estado),
                datasets: [{
                    data: datos.map(d => d.cantidad),
                    backgroundColor: [
                        '#4caf50', // Activas
                        '#2196f3', // Usadas
                        '#ff9800', // Expiradas
                        '#f44336'  // Canceladas
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Estados de Entradas',
                        font: { size: 16 }
                    },
                    legend: {
                        position: 'right'
                    }
                }
            }
        };
    }
    
    mostrarResumen(datos) {
        const contenedor = document.getElementById('resumenEstadistico');
        
        if (this.tipoActual === 'ventas-periodo') {
            const totalEntradas = datos.reduce((sum, d) => sum + d.total_entradas, 0);
            const totalRecaudacion = datos.reduce((sum, d) => sum + d.total_recaudado, 0);
            const promedioEntradas = datos.length > 0 ? (totalEntradas / datos.length).toFixed(2) : 0;
            
            contenedor.innerHTML = `
                <div class="stat-card">
                    <h3>Total Entradas</h3>
                    <div class="valor">${totalEntradas}</div>
                    <div class="detalle">Promedio: ${promedioEntradas}/período</div>
                </div>
                <div class="stat-card">
                    <h3>Recaudación Total</h3>
                    <div class="valor">$${totalRecaudacion.toLocaleString()}</div>
                    <div class="detalle">Promedio: $${datos.length > 0 ? (totalRecaudacion / datos.length).toFixed(2) : 0}/período</div>
                </div>
                <div class="stat-card">
                    <h3>Períodos</h3>
                    <div class="valor">${datos.length}</div>
                    <div class="detalle">analizados</div>
                </div>
            `;
        } else {
            contenedor.innerHTML = '';
        }
    }
}

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    new SistemaGraficos();
});