class SistemaGraficosWeb {
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
            case 'distribucion-productos':
                return this.configDistribucionProductos(datos);
            case 'peliculas-populares':
                return this.configPeliculasPopulares(datos);
            case 'top-cantina':
                return this.configTopCantina(datos);
            case 'top-maquinas':
                return this.configTopMaquinas(datos);
            case 'estados-ordenes':
                return this.configEstadosOrdenes(datos);
            case 'metodos-pago':
                return this.configMetodosPago(datos);
            case 'ocupacion-salas':
                return this.configOcupacionSalas(datos);
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
                        label: 'Órdenes',
                        data: datos.map(d => d.total_ordenes),
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
                        text: 'Ventas Web por Período',
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
                            text: 'Órdenes'
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
    
    configDistribucionProductos(datos) {
        return {
            type: 'doughnut',
            data: {
                labels: datos.map(d => d.categoria),
                datasets: [{
                    data: datos.map(d => d.recaudacion),
                    backgroundColor: [
                        '#ed850f',
                        '#22c55e',
                        '#3b82f6',
                        '#f59e0b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución de Ventas por Categoría',
                        font: { size: 16 }
                    },
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return `${label}: $${value.toLocaleString()}`;
                            }
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
                    label: 'Butacas Vendidas',
                    data: datos.map(d => d.total_butacas),
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
                        text: 'Películas Más Vendidas (Web)',
                        font: { size: 16 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Butacas'
                        }
                    }
                }
            }
        };
    }
    
    configTopCantina(datos) {
        return {
            type: 'bar',
            data: {
                labels: datos.map(d => d.producto),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: datos.map(d => d.cantidad),
                    backgroundColor: '#22c55e',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    title: {
                        display: true,
                        text: 'Top Productos de Cantina (Web)',
                        font: { size: 16 }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Unidades'
                        }
                    }
                }
            }
        };
    }
    
    configTopMaquinas(datos) {
        return {
            type: 'bar',
            data: {
                labels: datos.map(d => d.maquina),
                datasets: [{
                    label: 'Fichas Vendidas',
                    data: datos.map(d => d.fichas),
                    backgroundColor: '#3b82f6',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    title: {
                        display: true,
                        text: 'Top Máquinas de Juegos (Web)',
                        font: { size: 16 }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Fichas'
                        }
                    }
                }
            }
        };
    }
    
    configEstadosOrdenes(datos) {
        return {
            type: 'pie',
            data: {
                labels: datos.map(d => d.estado),
                datasets: [{
                    data: datos.map(d => d.cantidad),
                    backgroundColor: [
                        '#22c55e', // Pagadas
                        '#f59e0b', // Pendientes
                        '#ef4444', // Canceladas
                        '#6b7280'  // Fallidas
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Estados de Órdenes Web',
                        font: { size: 16 }
                    },
                    legend: {
                        position: 'right'
                    }
                }
            }
        };
    }
    
    configMetodosPago(datos) {
        return {
            type: 'doughnut',
            data: {
                labels: datos.map(d => d.metodo),
                datasets: [{
                    data: datos.map(d => d.cantidad),
                    backgroundColor: [
                        '#6366f1',
                        '#8b5cf6',
                        '#ec4899',
                        '#f59e0b',
                        '#22c55e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Métodos de Pago Utilizados',
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
                    label: 'Ocupación Web (%)',
                    data: datos.map(d => d.ocupacion),
                    backgroundColor: datos.map(d => 
                        d.ocupacion >= 80 ? '#22c55e' :
                        d.ocupacion >= 50 ? '#f59e0b' : '#ef4444'
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
                        text: 'Ocupación de Salas - Ventas Web',
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
    
    mostrarResumen(datos) {
        const contenedor = document.getElementById('resumenEstadistico');
        
        if (this.tipoActual === 'ventas-periodo') {
            const totalOrdenes = datos.reduce((sum, d) => sum + d.total_ordenes, 0);
            const totalRecaudacion = datos.reduce((sum, d) => sum + d.total_recaudado, 0);
            const totalButacas = datos.reduce((sum, d) => sum + d.total_butacas, 0);
            const totalCantina = datos.reduce((sum, d) => sum + d.total_cantina, 0);
            const totalFichas = datos.reduce((sum, d) => sum + d.total_fichas, 0);
            
            contenedor.innerHTML = `
                <div class="stat-card">
                    <h3>Total Órdenes</h3>
                    <div class="valor">${totalOrdenes}</div>
                    <div class="detalle">Promedio: ${datos.length > 0 ? (totalOrdenes / datos.length).toFixed(2) : 0}/período</div>
                </div>
                <div class="stat-card">
                    <h3>Recaudación Total</h3>
                    <div class="valor">$${totalRecaudacion.toLocaleString()}</div>
                    <div class="detalle">Promedio: $${datos.length > 0 ? (totalRecaudacion / datos.length).toFixed(2) : 0}/período</div>
                </div>
                <div class="stat-card">
                    <h3>Butacas Vendidas</h3>
                    <div class="valor">${totalButacas}</div>
                    <div class="detalle">Entradas de cine</div>
                </div>
                <div class="stat-card">
                    <h3>Productos Cantina</h3>
                    <div class="valor">${totalCantina}</div>
                    <div class="detalle">Unidades vendidas</div>
                </div>
                <div class="stat-card">
                    <h3>Fichas de Juegos</h3>
                    <div class="valor">${totalFichas}</div>
                    <div class="detalle">Fichas vendidas</div>
                </div>
            `;
        } else if (this.tipoActual === 'distribucion-productos') {
            const totalRecaudacion = datos.reduce((sum, d) => sum + d.recaudacion, 0);
            const totalUnidades = datos.reduce((sum, d) => sum + d.cantidad, 0);
            
            contenedor.innerHTML = `
                <div class="stat-card">
                    <h3>Total Recaudado</h3>
                    <div class="valor">$${totalRecaudacion.toLocaleString()}</div>
                    <div class="detalle">Todas las categorías</div>
                </div>
                <div class="stat-card">
                    <h3>Total Unidades</h3>
                    <div class="valor">${totalUnidades}</div>
                    <div class="detalle">Productos vendidos</div>
                </div>
            `;
        } else {
            contenedor.innerHTML = '';
        }
    }
}

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    new SistemaGraficosWeb();
});