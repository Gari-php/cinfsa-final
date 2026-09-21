// Configuración específica para este módulo
window.GRAFICO_CONFIG = {
    modulo: 'cantina',
    urlDatos: '/administrador/cantina/datos-grafico'
};

let chartActual = null;

// Configuración de colores
const COLORES = {
    principal: '#ed850f',
    secundario: '#ff9f40',
    exito: '#28a745',
    peligro: '#dc3545',
    advertencia: '#ffc107',
    info: '#17a2b8',
    morado: '#6f42c1',
    rosa: '#e83e8c',
    gradiente: ['#ed850f', '#ff9f40', '#ffb366', '#ffc68c', '#ffd9b2', '#28a745', '#17a2b8', '#6f42c1']
};

document.addEventListener('DOMContentLoaded', function () {
    // Cargar datos iniciales
    toggleFiltroAgrupar();
    cargarDatosGrafico();

    // Event listeners
    document.getElementById('btnActualizar').addEventListener('click', cargarDatosGrafico);
    document.getElementById('tipoGrafico').addEventListener('change', function () {
        toggleFiltroAgrupar();
        cargarDatosGrafico();
    });
});

// Toggle del filtro "Agrupar por"
function toggleFiltroAgrupar() {
    const tipo = document.getElementById('tipoGrafico').value;
    const grupoAgrupar = document.getElementById('grupoAgrupar');

    // Solo mostrar "Agrupar por" en ventas por período
    if (tipo === 'ventas-periodo') {
        grupoAgrupar.style.display = 'flex';
    } else {
        grupoAgrupar.style.display = 'none';
    }
}

// Cargar datos del gráfico
async function cargarDatosGrafico() {
    const tipo = document.getElementById('tipoGrafico').value;
    const fechaDesde = document.getElementById('fechaDesde').value;
    const fechaHasta = document.getElementById('fechaHasta').value;
    const agrupar = document.getElementById('agruparPor').value;

    // Mostrar loading
    mostrarLoading();

    try {
        const url = `${window.GRAFICO_CONFIG.urlDatos}?tipo=${tipo}&fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&agrupar=${agrupar}`;

        const response = await fetch(url);
        const resultado = await response.json();

        if (resultado.ok) {
            if (resultado.datos && resultado.datos.length > 0) {
                renderizarGrafico(resultado.datos, resultado.tipo);
                generarResumen(resultado.datos, resultado.tipo);
            } else {
                mostrarSinDatos();
            }
        } else {
            mostrarError(resultado.mensaje || 'Error al cargar los datos');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión al servidor');
    }
}

// Mostrar loading
function mostrarLoading() {
    const contenedor = document.getElementById('resumenEstadistico');
    contenedor.innerHTML = `
            <div class="loading-spinner" style="grid-column: 1 / -1;">
                <div class="spinner"></div>
            </div>
        `;
}

// Mostrar mensaje sin datos
function mostrarSinDatos() {
    const contenedor = document.getElementById('resumenEstadistico');
    contenedor.innerHTML = `
            <div class="stat-card" style="background: linear-gradient(135deg, #6c757d 0%, #868e96 100%); grid-column: 1 / -1;">
                <h3><i class="fa-solid fa-info-circle"></i> Sin Datos</h3>
                <div class="detalle" style="opacity: 1; font-size: 14px !important;">
                    No se encontraron datos para el período seleccionado
                </div>
            </div>
        `;

    // Limpiar gráfico
    if (chartActual) {
        chartActual.destroy();
        chartActual = null;
    }
}

// Renderizar gráfico según el tipo
function renderizarGrafico(datos, tipo) {
    // Destruir gráfico anterior si existe
    if (chartActual) {
        chartActual.destroy();
    }

    const ctx = document.getElementById('chartPrincipal').getContext('2d');

    switch (tipo) {
        case 'ventas-periodo':
            chartActual = crearGraficoVentasPeriodo(ctx, datos);
            break;
        case 'productos-populares':
            chartActual = crearGraficoProductosPopulares(ctx, datos);
            break;
        case 'ventas-fichas':
            chartActual = crearGraficoVentasFichas(ctx, datos);
            break;
        case 'formas-pago':
            chartActual = crearGraficoFormasPago(ctx, datos);
            break;
        case 'stock-productos':
            chartActual = crearGraficoStock(ctx, datos);
            break;
        case 'rendimiento-vendedores':
            chartActual = crearGraficoRendimientoVendedores(ctx, datos);
            break;
        case 'ventas-caja':
            chartActual = crearGraficoVentasCaja(ctx, datos);
            break;
        case 'tipos-comprobante':
            chartActual = crearGraficoTiposComprobante(ctx, datos);
            break;
    }
}

// Gráfico de ventas por período
function crearGraficoVentasPeriodo(ctx, datos) {
    const labels = datos.map(d => formatearFecha(d.periodo));

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Productos Vendidos',
                data: datos.map(d => d.cantidad_vendida),
                borderColor: COLORES.principal,
                backgroundColor: COLORES.principal + '20',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Recaudación ($)',
                data: datos.map(d => d.total_recaudado),
                borderColor: COLORES.exito,
                backgroundColor: COLORES.exito + '20',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
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
                    text: 'Evolución de Ventas',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.yAxisID === 'y1') {
                                label += '$' + context.parsed.y.toFixed(2);
                            } else {
                                label += context.parsed.y + ' unidades';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Cantidad de Productos'
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
    });
}

// Gráfico de productos populares
function crearGraficoProductosPopulares(ctx, datos) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.producto),
            datasets: [{
                label: 'Cantidad Vendida',
                data: datos.map(d => d.cantidad_vendida),
                backgroundColor: generarGradienteColores(datos.length),
                borderColor: COLORES.principal,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                title: {
                    display: true,
                    text: 'Top Productos Más Vendidos',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            const item = datos[context.dataIndex];
                            return [
                                `Recaudación: $${item.recaudacion.toFixed(2)}`,
                                `N° Ventas: ${item.num_ventas}`,
                                `Tipo: ${item.tipo}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad Vendida'
                    }
                }
            }
        }
    });
}

// Gráfico de ventas de fichas
function crearGraficoVentasFichas(ctx, datos) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.ficha),
            datasets: [{
                label: 'Cantidad Vendida',
                data: datos.map(d => d.cantidad_vendida),
                backgroundColor: COLORES.info,
                borderColor: COLORES.principal,
                borderWidth: 2
            }, {
                label: 'Stock Actual',
                data: datos.map(d => d.stock_actual),
                backgroundColor: COLORES.advertencia,
                borderColor: COLORES.advertencia,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Ventas y Stock de Fichas',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            const item = datos[context.dataIndex];
                            return [
                                `Precio: $${item.precio.toFixed(2)}`,
                                `Recaudación: $${item.recaudacion.toFixed(2)}`,
                                `N° Ventas: ${item.num_ventas}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Gráfico de formas de pago
function crearGraficoFormasPago(ctx, datos) {
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: datos.map(d => d.forma_pago),
            datasets: [{
                data: datos.map(d => d.cantidad),
                backgroundColor: [
                    COLORES.principal,
                    COLORES.exito,
                    COLORES.info,
                    COLORES.advertencia,
                    COLORES.morado,
                    COLORES.rosa
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Formas de Pago',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = datos[context.dataIndex];
                            const total = datos.reduce((sum, d) => sum + d.cantidad, 0);
                            const porcentaje = ((item.cantidad / total) * 100).toFixed(1);
                            return [
                                `${item.forma_pago}: ${item.cantidad} ventas (${porcentaje}%)`,
                                `Total: $${item.total.toFixed(2)}`,
                                `Promedio: $${item.promedio.toFixed(2)}`
                            ];
                        }
                    }
                }
            }
        }
    });
}

// Gráfico de stock
function crearGraficoStock(ctx, datos) {
    const datosLimitados = datos.slice(0, 15);

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datosLimitados.map(d => d.producto),
            datasets: [{
                label: 'Stock Actual',
                data: datosLimitados.map(d => d.stock_actual),
                backgroundColor: datosLimitados.map(d => {
                    if (d.stock_actual === 0) return COLORES.peligro;
                    if (d.stock_actual < 10) return COLORES.peligro;
                    if (d.stock_actual < 30) return COLORES.advertencia;
                    return COLORES.exito;
                }),
                borderColor: COLORES.principal,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                title: {
                    display: true,
                    text: 'Stock Actual de Productos',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            const item = datosLimitados[context.dataIndex];
                            return [
                                `Vendidos: ${item.cantidad_vendida}`,
                                `Precio: $${item.precio.toFixed(2)}`,
                                `Estado: ${item.estado}`,
                                `Nivel: ${item.nivel_stock}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Unidades en Stock'
                    }
                }
            }
        }
    });
}

// Gráfico de rendimiento de vendedores
function crearGraficoRendimientoVendedores(ctx, datos) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => `${d.vendedor} (${d.caja})`),
            datasets: [{
                label: 'Recaudación ($)',
                data: datos.map(d => d.total_recaudado),
                backgroundColor: COLORES.principal,
                borderColor: COLORES.principal,
                borderWidth: 2,
                yAxisID: 'y'
            }, {
                label: 'N° Ventas',
                data: datos.map(d => d.total_ventas),
                backgroundColor: COLORES.info,
                borderColor: COLORES.info,
                borderWidth: 2,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Rendimiento de Vendedores',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            const item = datos[context.dataIndex];
                            return [
                                `Productos vendidos: ${item.productos_vendidos}`,
                                `Promedio venta: $${item.promedio_venta.toFixed(2)}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Recaudación ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Número de Ventas'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Gráfico de ventas por caja
function crearGraficoVentasCaja(ctx, datos) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.caja),
            datasets: [{
                label: 'Total Ventas',
                data: datos.map(d => d.total_ventas),
                backgroundColor: COLORES.principal,
                borderWidth: 2
            }, {
                label: 'Recaudación ($)',
                data: datos.map(d => d.total_recaudado),
                backgroundColor: COLORES.exito,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Comparativa de Ventas por Caja',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            const item = datos[context.dataIndex];
                            return [
                                `Promedio venta: $${item.promedio_venta.toFixed(2)}`,
                                `Vendedores activos: ${item.vendedores}`
                            ];
                        }
                    }
                }
            }
        }
    });
}

// Gráfico de tipos de comprobante
function crearGraficoTiposComprobante(ctx, datos) {
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: datos.map(d => d.tipo),
            datasets: [{
                data: datos.map(d => d.cantidad),
                backgroundColor: COLORES.gradiente,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribución de Tipos de Comprobante',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const item = datos[context.dataIndex];
                            const total = datos.reduce((sum, d) => sum + d.cantidad, 0);
                            const porcentaje = ((item.cantidad / total) * 100).toFixed(1);
                            return [
                                `${item.tipo} (${item.codigo}): ${item.cantidad} (${porcentaje}%)`,
                                `Total: $${item.total.toFixed(2)}`,
                                `Promedio: $${item.promedio.toFixed(2)}`
                            ];
                        }
                    }
                }
            }
        }
    });
}

// Generar resumen estadístico
function generarResumen(datos, tipo) {
    const contenedor = document.getElementById('resumenEstadistico');
    let html = '';

    switch (tipo) {
        case 'ventas-periodo':
            const totalVentas = datos.reduce((sum, d) => sum + d.total_ventas, 0);
            const totalProductos = datos.reduce((sum, d) => sum + d.cantidad_vendida, 0);
            const totalRecaudado = datos.reduce((sum, d) => sum + d.total_recaudado, 0);
            const promedioGeneral = totalVentas > 0 ? (totalRecaudado / totalVentas) : 0;

            html = `
                    <div class="stat-card">
                        <h3>Total Ventas</h3>
                        <div class="valor">${totalVentas}</div>
                        <div class="detalle">transacciones</div>
                    </div>
                    <div class="stat-card">
                        <h3>Productos Vendidos</h3>
                        <div class="valor">${totalProductos}</div>
                        <div class="detalle">unidades</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Total</h3>
                        <div class="valor">$${totalRecaudado.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">en el período</div>
                    </div>
                    <div class="stat-card">
                        <h3>Promedio por Venta</h3>
                        <div class="valor">$${promedioGeneral.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">por transacción</div>
                    </div>
                `;
            break;

        case 'productos-populares':
            const topProducto = datos[0];
            const totalCantidad = datos.reduce((sum, d) => sum + d.cantidad_vendida, 0);
            const totalRec = datos.reduce((sum, d) => sum + d.recaudacion, 0);

            html = `
                    <div class="stat-card">
                        <h3>Producto Más Vendido</h3>
                        <div class="valor" style="font-size: 18px !important;">${topProducto.producto}</div>
                        <div class="detalle">${topProducto.cantidad_vendida} unidades - ${topProducto.tipo}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Vendido (Top ${datos.length})</h3>
                        <div class="valor">${totalCantidad}</div>
                        <div class="detalle">unidades</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Total</h3>
                        <div class="valor">${totalRec.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">del top ${datos.length}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Productos Analizados</h3>
                        <div class="valor">${datos.length}</div>
                        <div class="detalle">productos diferentes</div>
                    </div>
                `;
            break;

        case 'ventas-fichas':
            const totalFichasVendidas = datos.reduce((sum, d) => sum + d.cantidad_vendida, 0);
            const recaudacionFichas = datos.reduce((sum, d) => sum + d.recaudacion, 0);
            const fichaMasVendida = datos[0];
            const stockTotalFichas = datos.reduce((sum, d) => sum + d.stock_actual, 0);

            html = `
                    <div class="stat-card">
                        <h3>Ficha Más Vendida</h3>
                        <div class="valor" style="font-size: 18px !important;">${fichaMasVendida.ficha}</div>
                        <div class="detalle">${fichaMasVendida.cantidad_vendida} unidades</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Fichas Vendidas</h3>
                        <div class="valor">${totalFichasVendidas}</div>
                        <div class="detalle">unidades</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Fichas</h3>
                        <div class="valor">${recaudacionFichas.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">total periodo</div>
                    </div>
                    <div class="stat-card">
                        <h3>Stock Disponible</h3>
                        <div class="valor">${stockTotalFichas}</div>
                        <div class="detalle">fichas totales</div>
                    </div>
                `;
            break;

        case 'formas-pago':
            const formaMasUsada = datos[0];
            const totalVentasFP = datos.reduce((sum, d) => sum + d.cantidad, 0);
            const totalRecaudadoFP = datos.reduce((sum, d) => sum + d.total, 0);
            const promedioGeneralFP = totalVentasFP > 0 ? (totalRecaudadoFP / totalVentasFP) : 0;

            html = `
                    <div class="stat-card">
                        <h3>Forma Más Utilizada</h3>
                        <div class="valor" style="font-size: 18px !important;">${formaMasUsada.forma_pago}</div>
                        <div class="detalle">${formaMasUsada.cantidad} ventas (${((formaMasUsada.cantidad / totalVentasFP) * 100).toFixed(1)}%)</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total de Ventas</h3>
                        <div class="valor">${totalVentasFP}</div>
                        <div class="detalle">transacciones</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Total</h3>
                        <div class="valor">${totalRecaudadoFP.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">en el período</div>
                    </div>
                    <div class="stat-card">
                        <h3>Promedio General</h3>
                        <div class="valor">${promedioGeneralFP.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">por transacción</div>
                    </div>
                `;
            break;

        case 'stock-productos':
            const stockBajo = datos.filter(d => d.stock_actual < 10 && d.stock_actual > 0).length;
            const stockCero = datos.filter(d => d.stock_actual === 0).length;
            const stockMedio = datos.filter(d => d.stock_actual >= 10 && d.stock_actual < 30).length;
            const stockAlto = datos.filter(d => d.stock_actual >= 30).length;
            const totalStock = datos.reduce((sum, d) => sum + d.stock_actual, 0);
            const totalVendidoStock = datos.reduce((sum, d) => sum + d.cantidad_vendida, 0);

            html = `
                    <div class="stat-card" style="background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);">
                        <h3>Sin Stock</h3>
                        <div class="valor">${stockCero}</div>
                        <div class="detalle">productos agotados</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);">
                        <h3>Stock Bajo</h3>
                        <div class="valor">${stockBajo}</div>
                        <div class="detalle">productos &lt; 10 unidades</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffd454 100%);">
                        <h3>Stock Medio</h3>
                        <div class="valor">${stockMedio}</div>
                        <div class="detalle">productos 10-29 unidades</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #4bbe68 100%);">
                        <h3>Stock Alto</h3>
                        <div class="valor">${stockAlto}</div>
                        <div class="detalle">productos ≥ 30 unidades</div>
                    </div>
                `;
            break;

        case 'rendimiento-vendedores':
            const mejorVendedor = datos[0];
            const totalRecaudadoVendedores = datos.reduce((sum, d) => sum + d.total_recaudado, 0);
            const totalVentasVendedores = datos.reduce((sum, d) => sum + d.total_ventas, 0);
            const totalProductosVendedores = datos.reduce((sum, d) => sum + d.productos_vendidos, 0);

            html = `
                    <div class="stat-card">
                        <h3>Mejor Vendedor</h3>
                        <div class="valor" style="font-size: 18px !important;">${mejorVendedor.vendedor}</div>
                        <div class="detalle">${mejorVendedor.total_recaudado.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Total</h3>
                        <div class="valor">${totalRecaudadoVendedores.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">entre todos</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Ventas</h3>
                        <div class="valor">${totalVentasVendedores}</div>
                        <div class="detalle">transacciones</div>
                    </div>
                    <div class="stat-card">
                        <h3>Productos Vendidos</h3>
                        <div class="valor">${totalProductosVendedores}</div>
                        <div class="detalle">unidades totales</div>
                    </div>
                `;
            break;

        case 'ventas-caja':
            const totalRecaudadoCajas = datos.reduce((sum, d) => sum + d.total_recaudado, 0);
            const totalVentasCajas = datos.reduce((sum, d) => sum + d.total_ventas, 0);
            const cajaLider = datos[0];
            const totalVendedores = datos.reduce((sum, d) => sum + d.vendedores, 0);

            html = `
                    <div class="stat-card">
                        <h3>Caja con Más Ventas</h3>
                        <div class="valor" style="font-size: 18px !important;">${cajaLider.caja}</div>
                        <div class="detalle">${cajaLider.total_ventas} transacciones</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Ventas</h3>
                        <div class="valor">${totalVentasCajas}</div>
                        <div class="detalle">entre todas las cajas</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Total</h3>
                        <div class="valor">${totalRecaudadoCajas.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">en el período</div>
                    </div>
                    <div class="stat-card">
                        <h3>Vendedores Activos</h3>
                        <div class="valor">${totalVendedores}</div>
                        <div class="detalle">vendedores únicos</div>
                    </div>
                `;
            break;

        case 'tipos-comprobante':
            const tipoMasUsado = datos[0];
            const totalComprobantes = datos.reduce((sum, d) => sum + d.cantidad, 0);
            const totalRecaudadoComp = datos.reduce((sum, d) => sum + d.total, 0);

            html = `
                    <div class="stat-card">
                        <h3>Tipo Más Usado</h3>
                        <div class="valor" style="font-size: 18px !important;">${tipoMasUsado.tipo}</div>
                        <div class="detalle">${tipoMasUsado.cantidad} comprobantes (${((tipoMasUsado.cantidad / totalComprobantes) * 100).toFixed(1)}%)</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Comprobantes</h3>
                        <div class="valor">${totalComprobantes}</div>
                        <div class="detalle">emitidos</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recaudación Total</h3>
                        <div class="valor">${totalRecaudadoComp.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</div>
                        <div class="detalle">en el período</div>
                    </div>
                    <div class="stat-card">
                        <h3>Tipos Activos</h3>
                        <div class="valor">${datos.length}</div>
                        <div class="detalle">tipos diferentes</div>
                    </div>
                `;
            break;
    }

    contenedor.innerHTML = html;
}

// Utilidades
function formatearFecha(fecha) {
    const partes = fecha.split('-');

    if (partes.length === 3) {
        // Formato día: 2024-01-15
        return `${partes[2]}/${partes[1]}`;
    } else if (partes.length === 2) {
        // Formato mes: 2024-01
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
            'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
        ];
        return `${meses[parseInt(partes[1]) - 1]} ${partes[0]}`;
    } else {
        // Formato año: 2024
        return fecha;
    }
}

function generarGradienteColores(cantidad) {
    const colores = [];
    for (let i = 0; i < cantidad; i++) {
        const opacidad = 1 - (i * 0.05);
        colores.push(COLORES.principal + Math.round(opacidad * 255).toString(16).padStart(2, '0'));
    }
    return colores;
}

function mostrarError(mensaje) {
    const contenedor = document.getElementById('resumenEstadistico');
    contenedor.innerHTML = `
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%); grid-column: 1 / -1;">
                <h3><i class="fa-solid fa-exclamation-triangle"></i> Error</h3>
                <div class="detalle" style="opacity: 1; font-size: 14px !important;">${mensaje}</div>
            </div>
        `;

    // Limpiar gráfico
    if (chartActual) {
        chartActual.destroy();
        chartActual = null;
    }
}