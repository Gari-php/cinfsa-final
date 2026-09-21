// ===== BUSCADOR GENÉRICO =====
// Configuración por tipo de listado
const CONFIGURACIONES_BUSQUEDA = {
    usuarios: {
        inputId: 'busqueda-usuario',
        btnId: 'btn-buscar-usuario',
        tbodyId: 'tabla-usuarios',
        url: '/administrador/usuarios/buscar',
        responseKey: 'usuario',
        renderFila: (data) => `
            <tr>
                <td>${data.id_usuario}</td>
                <td>${data.nombre_usuario}</td>
                <td>${data.nombre_persona}</td>
                <td>${data.apellido_persona}</td>
                <td>${data.email}</td>
                <td>${data.nombre_sexo}</td>
                <td>${data.nombre_perfil}</td>
                <td>
                    ${data.estado == 1 
                        ? '<span class="estado-con-icono activo">ACTIVO</span>' 
                        : '<span class="estado-con-icono inactivo">INACTIVO</span>'}
                </td>
                <td class="acciones">
                    <button class="boton eliminar-usuario" 
                            data-id="${data.id_usuario}" 
                            data-nombre="${data.nombre_usuario}">
                        Eliminar
                    </button>
                    <a class="boton" href="/administrador/usuarios/editar?id=${data.id_usuario}">Editar</a>
                </td>
            </tr>
        `
    },
    ordenes: {
        inputId: 'busqueda-orden',
        btnId: 'btn-buscar-orden',
        tbodyId: 'tabla-ordenes',
        url: '/administrador/movimientos-web/buscar',
        responseKey: 'orden',
        renderFila: (orden) => {
            const estados = {
                'pagado': {texto: 'Pagada', clase: 'estado-pagada', icono: 'fa-circle-check'},
                'pendiente': {texto: 'Pendiente', clase: 'estado-pendiente', icono: 'fa-clock'},
                'cancelado': {texto: 'Cancelada', clase: 'estado-cancelada', icono: 'fa-ban'},
                'fallido': {texto: 'Fallida', clase: 'estado-fallida', icono: 'fa-exclamation-triangle'}
            };
            const estado_info = estados[orden.estado] || estados['pendiente'];
            
            const numeroOrden = orden.numero_orden || 'ORD-' + String(orden.id_orden).padStart(6, '0');
            const fechaPago = orden.fecha_pago 
                ? new Date(orden.fecha_pago).toLocaleString('es-AR')
                : '<span style="color: #a0aec0;">-</span>';
            
            return `
                <tr data-estado="${orden.estado}">
                    <td>${orden.id_orden}</td>
                    <td>
                        <span style="font-family: monospace; font-size: 11px; color: #fff;">
                            ${numeroOrden}
                        </span>
                    </td>
                    <td style="color: #fff;">
                        <div style="font-weight: bold;">${orden.nombre_usuario}</div>
                        <div style="font-size: 11px; color: #a0aec0;">${orden.email}</div>
                    </td>
                    <td>
                        <span class="estado-badge ${estado_info.clase}">
                            <i class="fa-solid ${estado_info.icono}"></i>
                            ${estado_info.texto}
                        </span>
                    </td>
                    <td style="color: #fff; text-align: center;">
                        <strong>${orden.total_items}</strong> items
                    </td>
                    <td style="color: #fff; font-size: 11px;">
                        ${orden.butacas > 0 ? `<div><i class="fa-solid fa-ticket" style="color: #ed850f;"></i> ${orden.butacas} butacas</div>` : ''}
                        ${orden.cantina > 0 ? `<div><i class="fa-solid fa-utensils" style="color: #22c55e;"></i> ${orden.cantina} productos</div>` : ''}
                        ${orden.fichas > 0 ? `<div><i class="fa-solid fa-gamepad" style="color: #3b82f6;"></i> ${orden.fichas} fichas</div>` : ''}
                    </td>
                    <td style="color: #fff;">
                        <i class="fa-solid fa-credit-card"></i>
                        ${(orden.metodo_pago || 'No especificado').replace('_', ' ')}
                    </td>
                    <td style="color: #fff; font-weight: bold; font-size: 14px;">
                        $${Number(orden.total).toLocaleString('es-AR')}
                    </td>
                    <td style="color: #fff; font-size: 11px;">
                        ${new Date(orden.fecha_creacion).toLocaleString('es-AR')}
                    </td>
                    <td style="color: #fff; font-size: 11px;">
                        ${fechaPago}
                    </td>
                    <td>
                        <div class="acciones">
                            <a class="boton" href="/administrador/movimientos-web/detalle?id=${orden.id_orden}" 
                               style="background: #0891b2;" title="Ver detalles">
                                <i class="fa-solid fa-eye"></i> Ver
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }
        
    },
    peliculas: {
        inputId: 'busqueda-pelicula',
        btnId: 'btn-buscar-pelicula',
        tbodyId: 'tabla-peliculas',
        url: '/administrador/peliculas/buscar',
        responseKey: 'pelicula',
        renderFila: (p) => {
            let estadoHtml = '';
            switch (p.nombre_estado_pelicula) {
                case 'Emision':
                    estadoHtml = '<span class="estado-con-icono activo">Emisión</span>';
                    break;
                case 'Proximamente':
                    estadoHtml = '<span class="estado-con-icono neutral">Próximamente</span>';
                    break;
                case 'Finalizada':
                    estadoHtml = '<span class="estado-con-icono inactivo">Finalizada</span>';
                    break;
                default:
                    estadoHtml = '<span class="estado-desconocido">Sin estado</span>';
            }

            const imagenHtml = p.imagen_pelicula 
                ? `<img src="/assets/img/peliculas/${p.imagen_pelicula}" alt="Poster de ${p.titulo_pelicula}" class="imagen-pelicula">`
                : '<span>Sin imagen</span>';

            const fechaCreado = p.creado 
                ? new Date(p.creado).toLocaleString('es-AR', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'})
                : '-';

            return `
                <tr>
                    <td>${p.id_pelicula}</td>
                    <td>${p.titulo_pelicula}</td>
                    <td>${imagenHtml}</td>
                    <td class="sinopsis-celda">${p.sinopsis_pelicula}</td>
                    <td>${p.anyo_pelicula}</td>
                    <td>${p.duracion_pelicula} min</td>
                    <td>${p.nombre_tipo_clasificacion}</td>
                    <td>${p.nombre_idioma_pelicula}</td>
                    <td>${p.generos || ''}</td>
                    <td>${estadoHtml}</td>
                    <td style="font-size: 11px !important; color: #a0aec0;">${fechaCreado}</td>
                    <td class="acciones">
                        <button class="boton eliminar-pelicula"
                                data-id="${p.id_pelicula}"
                                data-nombre="${p.titulo_pelicula}">
                            Eliminar
                        </button>
                        <a class="boton" href="/administrador/peliculas/editar?id=${p.id_pelicula}">Editar</a>
                    </td>
                </tr>
            `;
        }
    }
    
};

// Inicializar buscadores
document.addEventListener('DOMContentLoaded', function() {
    // Detectar qué tipo de listado es
    const listado = document.querySelector('.listado');
    const modulo = listado?.dataset.modulo;
    
    if (!modulo || !CONFIGURACIONES_BUSQUEDA[modulo]) return;
    
    const config = CONFIGURACIONES_BUSQUEDA[modulo];
    const input = document.getElementById(config.inputId);
    const btn = document.getElementById(config.btnId);
    
    if (!input || !btn) return;
    
    // Click en buscar
    btn.addEventListener('click', () => buscar(config));
    
    // Enter en input
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscar(config);
        }
    });
});

async function buscar(config) {
    const input = document.getElementById(config.inputId);
    const termino = input.value.trim();
    
    if (!termino) {
        alert('Ingresa un término de búsqueda');
        return;
    }

    try {
        const response = await fetch(config.url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({termino})
        });

        const data = await response.json();

        if (data.ok) {
            const tbody = document.getElementById(config.tbodyId);
            const resultado = data[config.responseKey];
            tbody.innerHTML = config.renderFila(resultado);
        } else {
            alert(data.mensaje);
        }
    } catch (error) {
        console.error('Error en búsqueda:', error);
        alert('Error al buscar');
    }
}