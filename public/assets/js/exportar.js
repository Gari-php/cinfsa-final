document.addEventListener('DOMContentLoaded', function() {
    // Configuración de módulos con sus rutas de exportación
    const MODULOS_CONFIG = {
        'peliculas': '/administrador/peliculas/exportar',
        'funciones': '/administrador/funciones/exportar',
        'usuarios': '/administrador/usuarios/exportar',
        'salas': '/administrador/salas/exportar',
        'productos': '/administrador/productos/exportar',
        'maquinas': '/administrador/maquinas/exportar',
        'fichas': '/administrador/fichas/exportar',
        'stock': '/administrador/stock/exportar',
        'cantina': '/administrador/cantina/exportar',
        'entradas': '/administrador/entradas/exportar',
        'turnos': '/administrador/turnos/exportar',
        'generos': '/administrador/generos/exportar',
        'sexo': '/administrador/sexo/exportar',
        'tipos_entradas': '/administrador/tipos_entradas/exportar',
        'estados_peliculas': '/administrador/estados_peliculas/exportar',
        'movimientos-web': '/administrador/movimientos-web/exportar',
        'gastos': '/administrador/gastos/exportar',
    };

 
    inicializarExportacion();

   
    function inicializarExportacion() {
        const botonesExportar = document.querySelectorAll('.btn-exportar');
        
        if (botonesExportar.length === 0) return;

        botonesExportar.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                const tipo = this.dataset.tipo;
                const modulo = detectarModulo();
                
                if (!modulo) {
                    mostrarNotificacion('No se pudo detectar el módulo para exportar', 'error');
                    return;
                }

                if (!MODULOS_CONFIG[modulo]) {
                    mostrarNotificacion(`Exportación no configurada para el módulo: ${modulo}`, 'error');
                    return;
                }

                exportarDatos(tipo, boton, modulo);
            });
        });
    }

  
    function detectarModulo() {
        const contenedor = document.querySelector('[data-modulo]');
        if (contenedor && contenedor.dataset.modulo) {
            return contenedor.dataset.modulo;
        }

        const path = window.location.pathname;
        const matches = path.match(/\/administrador\/([^\/]+)\//);
        if (matches && matches[1]) {
            return matches[1];
        }
        
        return null;
    }

   
    function exportarDatos(tipo, boton, modulo) {
        if (!tipo || !boton || !modulo) {
            mostrarNotificacion('Parámetros de exportación inválidos', 'error');
            return;
        }

        if (!['excel', 'pdf'].includes(tipo)) {
            mostrarNotificacion('Tipo de exportación no válido', 'error');
            return;
        }

        iniciarEstadoCarga(boton);

        try {
            let url = `${MODULOS_CONFIG[modulo]}?tipo=${tipo}`;
            
            // Capturar filtros de fecha
            const filtroFecha = obtenerFiltrosFecha();
            
            if (filtroFecha.hayFiltro) {
                url += `&fecha_desde=${filtroFecha.desde}&fecha_hasta=${filtroFecha.hasta}`;
                
                let mensajeFiltro = '';
                if (filtroFecha.desde && filtroFecha.hasta) {
                    mensajeFiltro = ` del ${formatearFecha(filtroFecha.desde)} al ${formatearFecha(filtroFecha.hasta)}`;
                } else if (filtroFecha.desde) {
                    mensajeFiltro = ` desde ${formatearFecha(filtroFecha.desde)}`;
                } else if (filtroFecha.hasta) {
                    mensajeFiltro = ` hasta ${formatearFecha(filtroFecha.hasta)}`;
                }
                
                mostrarNotificacion(`Exportando ${tipo.toUpperCase()} de ${modulo}${mensajeFiltro}`, 'success');
            } else {
                mostrarNotificacion(`Exportando ${tipo.toUpperCase()} de todas las ${modulo}`, 'success');
            }
            
            console.log('URL de exportación:', url);
            
            // Abrir en nueva ventana/pestaña para descargar
            window.open(url, '_blank');

        } catch (error) {
            console.error('Error en exportación:', error);
            mostrarNotificacion('Error al iniciar la exportación', 'error');
        } finally {
            setTimeout(() => restaurarEstadoBoton(boton), 1500);
        }
    }

 
    function obtenerFiltrosFecha() {
        const inputDesde = document.getElementById('fecha-desde-export') || 
                          document.querySelector('input[name="fecha_desde"]') ||
                          document.querySelector('.filtro-fecha-desde');
                          
        const inputHasta = document.getElementById('fecha-hasta-export') || 
                          document.querySelector('input[name="fecha_hasta"]') ||
                          document.querySelector('.filtro-fecha-hasta');
        
        const desde = inputDesde ? inputDesde.value : '';
        const hasta = inputHasta ? inputHasta.value : '';
        
        return {
            desde: desde,
            hasta: hasta,
            hayFiltro: !!(desde || hasta)
        };
    }

  
    function formatearFecha(fecha) {
        if (!fecha) return '';
        const partes = fecha.split('-');
        if (partes.length === 3) {
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        return fecha;
    }

  
    function iniciarEstadoCarga(boton) {
        boton.disabled = true;
        boton.classList.add('loading-export');
        if (!boton.dataset.textoOriginal) {
            boton.dataset.textoOriginal = boton.innerHTML;
        }
        const tipo = boton.dataset.tipo;
        boton.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Exportando ${tipo.toUpperCase()}...`;
    }

  
    function restaurarEstadoBoton(boton) {
        boton.disabled = false;
        boton.classList.remove('loading-export');
        if (boton.dataset.textoOriginal) {
            boton.innerHTML = boton.dataset.textoOriginal;
        }
    }

    
    function mostrarNotificacion(mensaje, tipo = 'info') {
        let contenedor = document.getElementById('alerta-accion');
        
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'alerta-accion';
            contenedor.className = 'form-container';
            document.body.appendChild(contenedor);
        }

        const alerta = document.createElement('div');
        alerta.className = `alerta ${tipo}`;
        alerta.innerHTML = `
            <i class="fa-solid ${getIconoTipo(tipo)}"></i>
            <span>${mensaje}</span>
        `;

        contenedor.innerHTML = '';
        contenedor.appendChild(alerta);

        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 3000);
    }

   
    function getIconoTipo(tipo) {
        switch (tipo) {
            case 'success': return 'fa-check-circle';
            case 'error': return 'fa-exclamation-triangle';
            case 'warning': return 'fa-exclamation-circle';
            default: return 'fa-info-circle';
        }
    }

    // API pública
    window.CINFSA = window.CINFSA || {};
    window.CINFSA.exportar = {
        exportarDatos: exportarDatos,
        detectarModulo: detectarModulo,
        obtenerFiltrosFecha: obtenerFiltrosFecha
    };
});