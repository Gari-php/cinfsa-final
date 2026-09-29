
<?php
// DEBUG - Eliminar después
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_log("Usuario ID: " . ($_SESSION['id_usuario'] ?? 'NO EXISTE'));
error_log("Items en carrito: " . print_r(\Models\Carrito::obtenerCarritoCompleto($_SESSION['id_usuario'] ?? 0), true));
?>
<div class="butacas-container">
    <div class="butacas-header">
        <h1>SELECCIÓN DE BUTACAS</h1>
        <div class="funcion-info">
            <div class="pelicula-info">
                <img src="/assets/img/peliculas/<?php echo $funcion['imagen_pelicula']; ?>" 
                     alt="<?php echo $funcion['titulo_pelicula']; ?>" class="pelicula-poster">
                <div class="detalles">
                    <h2><?php echo $funcion['titulo_pelicula']; ?></h2>
                    <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($funcion['fecha_hora'])); ?></p>
                    <p><i class="fas fa-clock"></i> <?php echo $funcion['turno_horario']; ?></p>
                    <p><i class="fas fa-couch"></i> Sala <?php echo $funcion['rela_salas']; ?></p>
                    <p><i class="fas fa-dollar-sign"></i> $<?php echo number_format($funcion['precio_entrada'] ?? 2500, 0, ',', '.'); ?> por entrada</p>
                </div>
            </div>
        </div>
    </div>

    <!-- LEYENDA -->
    <div class="leyenda">
        <div class="leyenda-item">
            <i class="fa-solid fa-chair disponible-icon"></i>
            <span>Disponible</span>
        </div>
        <div class="leyenda-item">
            <i class="fa-solid fa-chair seleccionada-icon"></i>
            <span>Seleccionada</span>
        </div>
        <div class="leyenda-item">
            <i class="fa-solid fa-chair bloqueada-icon"></i>
            <span>No disponible</span>
        </div>
        <div class="leyenda-item">
            <i class="fa-solid fa-chair ocupada-icon"></i>
            <span>Reservada</span>
        </div>
    </div>

    <!-- PANTALLA -->
    <div class="pantalla">
        <div class="pantalla-texto">PANTALLA</div>
    </div>

    <!-- MAPA DE BUTACAS -->
    <div id="mapa-butacas" class="mapa-butacas">
        <div id="loading-butacas" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Cargando mapa de butacas...</p>
        </div>
    </div>

    <!-- RESUMEN DE SELECCIÓN -->
    <div class="resumen-seleccion">
        <div class="resumen-info">
            <h3>Resumen de selección</h3>
            <div id="butacas-seleccionadas">
                <p>No hay butacas seleccionadas</p>
            </div>
            <div class="total-precio">
                <strong>Total: $<span id="total-precio">0</span></strong>
            </div>
        </div>
        
        <div class="acciones-butacas">
            <button class="btn-limpiar" onclick="limpiarSeleccion()">
                <i class="fas fa-broom"></i>
                Limpiar selección
            </button>
            <button class="btn-agregar-carrito" onclick="agregarButacasAlCarrito()" disabled>
                <i class="fas fa-shopping-cart"></i>
                Agregar al carrito
            </button>
            <a href="/funciones" class="btn-volver">
                <i class="fas fa-arrow-left"></i>
                Volver a funciones
            </a>
        </div>
    </div>
</div>

<script>

// Variables globales
const idFuncion = <?php echo $funcion['id_funcion']; ?>;
const precioEntrada = <?php echo $funcion['precio_entrada'] ?? 2500; ?>;
let butacasSeleccionadas = [];
let layoutSala = null;

// Estados de butacas
const ESTADOS = {
    DISPONIBLE: 1,
    BLOQUEADA: 2,
    RESERVADA: 3
};

// Cargar mapa de butacas al iniciar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando aplicación de butacas');
    cargarMapaButacas();
});

async function cargarMapaButacas() {
    console.log('📡 Cargando mapa de butacas para función:', idFuncion);
    
    try {
        const url = `/api/butacas/funcion?id_funcion=${idFuncion}`;
        console.log('🔗 URL:', url);
        
        const response = await fetch(url);
        console.log('📊 Response status:', response.status);
        
        const data = await response.json();
        console.log('📦 Datos recibidos:', data);
        
        if (data.ok) {
            layoutSala = data.layout;
            console.log('🏢 Layout de sala:', layoutSala);
            
            if (!layoutSala || !layoutSala.butacas || layoutSala.butacas.length === 0) {
                mostrarError('No hay butacas generadas para esta sala');
                return;
            }
            
            generarMapaButacas(layoutSala);
        } else {
            console.error('❌ Error en respuesta:', data.mensaje);
            mostrarError(data.mensaje);
        }
    } catch (error) {
        console.error('💥 Error completo:', error);
        mostrarError('Error al cargar el mapa de butacas: ' + error.message);
    }
}

function generarMapaButacas(layout) {
    console.log('🎨 Generando mapa visual de butacas');
    
    const container = document.getElementById('mapa-butacas');
    const loading = document.getElementById('loading-butacas');
    
    loading.style.display = 'none';
    
    const filas = layout.sala.filas;
    const columnas = layout.sala.columnas;
    
    let html = '<div class="sala-grid">';
    
    // Crear matriz de butacas organizadas por fila
    const matrizButacas = {};
    layout.butacas.forEach(butaca => {
        if (!matrizButacas[butaca.fila]) {
            matrizButacas[butaca.fila] = {};
        }
        matrizButacas[butaca.fila][butaca.numero] = butaca;
        
        // Debug del estado de cada butaca
        console.log(`🪑 Butaca ${butaca.fila}-${butaca.numero} - Estado: ${butaca.estado} (${getEstadoTexto(butaca.estado)})`);
    });
    
    // Generar filas
    for (let fila = 1; fila <= filas; fila++) {
        html += `<div class="fila-butacas" data-fila="${fila}">`;
        html += `<div class="fila-numero">Fila ${fila}</div>`;
        
        for (let numero = 1; numero <= columnas; numero++) {
            const butaca = matrizButacas[fila] && matrizButacas[fila][numero];
            
            if (butaca) {
                const claseEstado = getClaseEstado(butaca.estado);
                const esClickeable = butaca.estado === ESTADOS.DISPONIBLE;
                
                console.log(`🎯 Butaca ${butaca.fila}-${butaca.numero}: Estado=${butaca.estado}, Clase=${claseEstado}, Clickeable=${esClickeable}`);
                
                html += `<div class="butaca ${claseEstado}" 
                            data-id="${butaca.id}" 
                            data-fila="${butaca.fila}" 
                            data-numero="${butaca.numero}"
                            data-label="${butaca.label}"
                            data-estado-num="${butaca.estado}"
                            data-estado-texto="${claseEstado}"
                            ${esClickeable ? 'onclick="toggleButaca(this)"' : ''}
                            style="cursor: ${esClickeable ? 'pointer' : 'not-allowed'}">
                            <i class="fa-solid fa-chair butaca-icon"></i>
                            <span class="numero-butaca">${butaca.numero}</span>
                        </div>`;
            } else {
                // Espacio vacío (pasillo)
                html += '<div class="butaca-vacia"></div>';
            }
        }
        
        html += '</div>';
    }
    
    html += '</div>';
    container.innerHTML = html;
    
    // Aplicar colores INMEDIATAMENTE después de crear el HTML
    console.log('🎨 Aplicando colores después de generar HTML');
    setTimeout(() => {
        aplicarColoresButacas();
        verificarColoresAplicados();
    }, 100);
}

function getClaseEstado(estado) {
    switch(parseInt(estado)) {
        case ESTADOS.DISPONIBLE:
            return 'disponible';
        case ESTADOS.BLOQUEADA:
            return 'bloqueada';
        case ESTADOS.RESERVADA:
            return 'ocupada';
        default:
            console.warn(`⚠️ Estado desconocido: ${estado}`);
            return 'bloqueada';
    }
}

function getEstadoTexto(estado) {
    switch(parseInt(estado)) {
        case ESTADOS.DISPONIBLE:
            return 'DISPONIBLE';
        case ESTADOS.BLOQUEADA:
            return 'BLOQUEADA';
        case ESTADOS.RESERVADA:
            return 'RESERVADA';
        default:
            return 'DESCONOCIDO';
    }
}

function aplicarColoresButacas() {
    console.log('🎨 Aplicando colores a las butacas...');
    
    // Aplicar colores a la leyenda
    document.querySelectorAll('.disponible-icon').forEach(icon => {
        icon.style.setProperty('color', '#28a745', 'important');
    });
    
    document.querySelectorAll('.seleccionada-icon').forEach(icon => {
        icon.style.setProperty('color', '#2563eb', 'important');
    });
    
    document.querySelectorAll('.bloqueada-icon').forEach(icon => {
        icon.style.setProperty('color', '#dc3545', 'important');
    });
    
    document.querySelectorAll('.ocupada-icon').forEach(icon => {
        icon.style.setProperty('color', '#ff8c00', 'important');
    });
    
    // Aplicar colores a las butacas según su estado
    document.querySelectorAll('.butaca').forEach(butaca => {
        const icon = butaca.querySelector('.butaca-icon');
        const estadoTexto = butaca.getAttribute('data-estado-texto');
        const estadoNum = butaca.getAttribute('data-estado-num');
        
        if (icon) {
            let color = '#dc3545'; // Default rojo
            
            switch(estadoTexto) {
                case 'disponible':
                    color = '#28a745'; // Verde
                    break;
                case 'seleccionada':
                    color = '#2563eb'; // Azul oscuro
                    break;
                case 'bloqueada':
                    color = '#dc3545'; // Rojo
                    break;
                case 'ocupada':
                    color = '#ff8c00'; // Naranja
                    break;
            }
            
            // Aplicar color con máxima prioridad
            icon.style.setProperty('color', color, 'important');
            icon.style.color = color;
            
            console.log(`🎨 Butaca ${butaca.getAttribute('data-label')}: Estado=${estadoNum}(${estadoTexto}) → Color=${color}`);
        }
    });
}

function verificarColoresAplicados() {
    console.log('🔍 Verificando colores aplicados:');
    
    const disponibles = document.querySelectorAll('.butaca.disponible .butaca-icon');
    const bloqueadas = document.querySelectorAll('.butaca.bloqueada .butaca-icon');
    const ocupadas = document.querySelectorAll('.butaca.ocupada .butaca-icon');
    
    console.log(`✅ Butacas disponibles (verdes): ${disponibles.length}`);
    disponibles.forEach((icon, index) => {
        const color = window.getComputedStyle(icon).color;
        console.log(`   ${index + 1}. Color aplicado: ${color}`);
    });
    
    console.log(`🔴 Butacas bloqueadas (rojas): ${bloqueadas.length}`);
    console.log(`🔴 Butacas ocupadas (rojas): ${ocupadas.length}`);
}

function toggleButaca(elemento) {
    const id = elemento.getAttribute('data-id');
    const label = elemento.getAttribute('data-label');
    const estadoActual = elemento.getAttribute('data-estado-texto');
    
    console.log(`🖱️ Click en butaca ${label}, estado actual: ${estadoActual}`);
    
    if (estadoActual === 'seleccionada') {
        // Deseleccionar
        elemento.classList.remove('seleccionada');
        elemento.classList.add('disponible');
        elemento.setAttribute('data-estado-texto', 'disponible');
        
        butacasSeleccionadas = butacasSeleccionadas.filter(b => b.id !== id);
        
        // Aplicar color verde
        const icon = elemento.querySelector('.butaca-icon');
        if (icon) {
            icon.style.setProperty('color', '#28a745', 'important');
        }
        
        console.log(`✅ Butaca ${label} deseleccionada`);
        
    } else if (estadoActual === 'disponible') {
        // Seleccionar (máximo 8 butacas)
        if (butacasSeleccionadas.length >= 8) {
            mostrarAlerta('Máximo 8 butacas por compra', 'neutral');
            return;
        }
        
        elemento.classList.remove('disponible');
        elemento.classList.add('seleccionada');
        elemento.setAttribute('data-estado-texto', 'seleccionada');
        
        butacasSeleccionadas.push({
            id: id,
            label: label,
            fila: elemento.getAttribute('data-fila'),
            numero: elemento.getAttribute('data-numero')
        });
        
        // Aplicar color azul oscuro
        const icon = elemento.querySelector('.butaca-icon');
        if (icon) {
            icon.style.setProperty('color', '#2563eb', 'important');
        }
        
        console.log(`🟠 Butaca ${label} seleccionada`);
    }
    
    actualizarResumen();
}

function limpiarSeleccion() {
    console.log('🧹 Limpiando selección de butacas');
    
    document.querySelectorAll('.butaca.seleccionada').forEach(elemento => {
        elemento.classList.remove('seleccionada');
        elemento.classList.add('disponible');
        elemento.setAttribute('data-estado-texto', 'disponible');
        
        const icon = elemento.querySelector('.butaca-icon');
        if (icon) {
            icon.style.setProperty('color', '#28a745', 'important');
        }
    });
    
    butacasSeleccionadas = [];
    actualizarResumen();
}

function actualizarResumen() {
    const resumenDiv = document.getElementById('butacas-seleccionadas');
    const totalDiv = document.getElementById('total-precio');
    const btnAgregar = document.querySelector('.btn-agregar-carrito');
    
    if (butacasSeleccionadas.length === 0) {
        resumenDiv.innerHTML = '<p>No hay butacas seleccionadas</p>';
        totalDiv.textContent = '0';
        btnAgregar.disabled = true;
    } else {
        let html = '<div class="lista-seleccionadas">';
        butacasSeleccionadas.forEach(butaca => {
            html += `<div class="butaca-seleccionada">
                        <span>Fila ${butaca.fila} - Butaca ${butaca.numero}</span>
                        <span>$${new Intl.NumberFormat('es-AR').format(precioEntrada)}</span>
                     </div>`;
        });
        html += '</div>';
        
        resumenDiv.innerHTML = html;
        totalDiv.textContent = new Intl.NumberFormat('es-AR').format(butacasSeleccionadas.length * precioEntrada);
        btnAgregar.disabled = false;
    }
}

async function agregarButacasAlCarrito() {
    if (butacasSeleccionadas.length === 0) {
        mostrarAlerta('Debe seleccionar al menos una butaca', 'neutral');
        return;
    }
    
    console.log('🛒 Agregando butacas al carrito:', butacasSeleccionadas);
    
    const btn = document.querySelector('.btn-agregar-carrito');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    
    try {
        const idsButacas = butacasSeleccionadas.map(b => b.id);
        
        const response = await fetch('/api/butacas/reservar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                butacas: idsButacas,
                id_funcion: idFuncion
            })
        });
        
        const data = await response.json();
        
        if (data.ok) {
            if (window.carritoGlobal) {
                butacasSeleccionadas.forEach(butaca => {
                    const nombre = `${document.querySelector('.detalles h2').textContent} - Fila ${butaca.fila} Butaca ${butaca.numero}`;
                    window.carritoGlobal.agregarItem(
                        `butaca_${butaca.id}`, 
                        nombre, 
                        precioEntrada, 
                        'butacas', 
                        '', 
                        1
                    );
                });
                
                // Mostrar alerta de éxito con información detallada
                const mensaje = `${butacasSeleccionadas.length} ${butacasSeleccionadas.length === 1 ? 'butaca agregada' : 'butacas agregadas'} al carrito correctamente`;
                mostrarAlerta(mensaje, 'exito');
                
                // Redirigir después de un momento para que vean la alerta
                setTimeout(() => {
                    window.location.href = '/funciones';
                }, 2000);
            } else {
                mostrarAlerta('Butacas reservadas correctamente', 'exito');
            }
        } else {
            mostrarAlerta('Error: ' + data.mensaje, 'error');
            cargarMapaButacas(); // Recargar para actualizar estados
        }
        
    } catch (error) {
        console.error('💥 Error al procesar reserva:', error);
        mostrarAlerta('Error al procesar la reserva. Intente nuevamente.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Agregar al carrito';
    }
}

function mostrarError(mensaje) {
    const container = document.getElementById('mapa-butacas');
    container.innerHTML = `
        <div class="error-butacas">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Error</h3>
            <p>${mensaje}</p>
            <button onclick="location.reload()" class="btn-reintentar">Reintentar</button>
        </div>
    `;
}
function mostrarAlerta(mensaje, tipo = 'success') {
    const notif = document.getElementById('notificacion');
    if (notif) {
        notif.textContent = mensaje;
        notif.className = 'notificacion show ' + tipo;
        setTimeout(() => notif.classList.remove('show'), 3000);
    } else {
        alert(mensaje);
    }
}
async function agregarButacasAlCarrito() {
    if (butacasSeleccionadas.length === 0) {
        mostrarAlerta('Debe seleccionar al menos una butaca', 'neutral');
        return;
    }
    
    console.log('🛒 Agregando butacas al carrito:', butacasSeleccionadas);
    
    const btn = document.querySelector('.btn-agregar-carrito');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    
    try {
        const idsButacas = butacasSeleccionadas.map(b => b.id);
        
        const response = await fetch('/api/butacas/reservar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                butacas: idsButacas,
                id_funcion: idFuncion
            })
        });
        
        const data = await response.json();
        
        if (data.ok) {
            mostrarAlerta(`${butacasSeleccionadas.length} butaca(s) agregada(s) al carrito`, 'exito');
            
            // Sincronizar el contador del carrito
            if (window.carritoGlobal) {
                await window.carritoGlobal.sincronizar();
            }
            
            // Redirigir después de un momento
            setTimeout(() => {
                window.location.href = '/funciones';
            }, 1500);
        } else {
            mostrarAlerta('Error: ' + data.mensaje, 'error');
            cargarMapaButacas(); // Recargar para actualizar estados
        }
        
    } catch (error) {
        console.error('💥 Error al procesar reserva:', error);
        mostrarAlerta('Error al procesar la reserva. Intente nuevamente.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Agregar al carrito';
    }
}
</script>

<style>
/* ===== RESET Y BASE ===== */
.butacas-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    color: #fff;
}

.butacas-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.butacas-header h1 {
    font-size: 2.2rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #ed850f;
    margin-bottom: 1.25rem;
    text-shadow: 0 2px 12px rgba(237, 133, 15, 0.25);
}

/* ===== INFORMACIÓN DE LA FUNCIÓN ===== */
.funcion-info {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
    border: 1px solid #4a5568;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}

.pelicula-info {
    display: flex;
    align-items: center;
    gap: 1.75rem;
}

.pelicula-poster {
    width: 100px;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(237, 133, 15, 0.3);
    flex-shrink: 0;
}

.detalles h2 {
    color: #fff;
    margin: 0 0 0.9rem;
    font-size: 1.4rem;
}

.detalles p {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: #cbd5e0;
    font-size: 0.9rem;
}

.detalles p + p {
    margin-top: 0.55rem;
}

.detalles i {
    color: #ed850f;
    width: 18px;
    text-align: center;
    flex-shrink: 0;
}

/* ===== LEYENDA - CHIPS ===== */
.leyenda {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
}

.leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
    font-size: 0.82rem;
    color: #cbd5e0;
    background: #1a202c;
    border: 1px solid #4a5568;
    padding: 0.55rem 1rem;
    border-radius: 999px;
}

/* COLORES ESPECÍFICOS DE LA LEYENDA (coinciden con los que aplica el JS a las butacas) */
.disponible-icon {
    color: #28a745 !important;
    font-size: 16px !important;
}

.seleccionada-icon {
    color: #2563eb !important;
    font-size: 16px !important;
}

.bloqueada-icon {
    color: #dc3545 !important;
    font-size: 16px !important;
}

.ocupada-icon {
    color: #ff8c00 !important;
    font-size: 16px !important;
}

/* ===== PANTALLA ===== */
.pantalla {
    text-align: center;
    margin-bottom: 2.5rem;
    padding-top: 0.5rem;
}

.pantalla-texto {
    position: relative;
    width: min(560px, 85%);
    margin: 0 auto;
    padding: 0.7rem 0;
    background: linear-gradient(180deg, rgba(237, 133, 15, 0.9), rgba(237, 133, 15, 0.15));
    clip-path: polygon(6% 0%, 94% 0%, 100% 100%, 0% 100%);
    color: transparent;
    font-size: 0;
}

.pantalla-texto::after {
    content: 'PANTALLA';
    display: block;
    position: absolute;
    top: -1.6rem;
    left: 0;
    right: 0;
    text-align: center;
    color: #718096;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 4px;
}

.pantalla-texto {
    box-shadow: 0 25px 40px -15px rgba(237, 133, 15, 0.45);
}

/* ===== MAPA DE BUTACAS ===== */
.mapa-butacas {
    margin-bottom: 1.75rem;
    min-height: 400px;
    display: flex;
    justify-content: center;
    color: #fff !important;
}

.sala-grid {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    align-items: center;
    width: 100%;
    max-width: 800px;
    background: radial-gradient(ellipse at top, rgba(255, 255, 255, 0.04), transparent 70%);
    padding: 2rem 1.5rem 1.5rem;
}

.fila-butacas {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    justify-content: center;
}

.fila-numero {
    font-weight: 700;
    color: #718096;
    min-width: 34px;
    text-align: center;
    font-size: 0.75rem;
    margin-right: 6px;
}

/* ===== BUTACAS - ESTILOS BASE ===== */
.butaca {
    width: 42px;
    height: 42px;
    border-radius: 9px 9px 5px 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    background: #1a202c;
    border: 1px solid #2d3748;
    position: relative;
}

.butaca-icon {
    font-size: 18px !important;
    transition: all 0.15s ease;
}

.numero-butaca {
    font-size: 8px !important;
    font-weight: 700;
    margin-top: 1px;
    color: rgba(255, 255, 255, 0.65) !important;
}

/* ===== ESTADOS DE BUTACAS ===== */

/* DISPONIBLES - VERDE */
.butaca.disponible {
    border-color: rgba(40, 167, 69, 0.35);
}

.butaca.disponible:hover {
    background: rgba(40, 167, 69, 0.12);
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
    transform: translateY(-2px);
}

/* SELECCIONADAS - AZUL */
.butaca.seleccionada {
    background: rgba(37, 99, 235, 0.18);
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3), 0 6px 14px rgba(37, 99, 235, 0.35);
    transform: translateY(-2px);
}

.butaca.seleccionada .numero-butaca {
    color: #fff !important;
}

/* BLOQUEADAS - ROJO */
.butaca.bloqueada {
    background: rgba(220, 53, 69, 0.08);
    border-color: rgba(220, 53, 69, 0.25);
    cursor: not-allowed !important;
}

.butaca.bloqueada .butaca-icon {
    opacity: 0.6;
}

/* OCUPADAS/RESERVADAS - ÁMBAR */
.butaca.ocupada {
    background: rgba(255, 140, 0, 0.08);
    border-color: rgba(255, 140, 0, 0.25);
    cursor: not-allowed !important;
}

.butaca.ocupada .butaca-icon {
    opacity: 0.6;
}

/* ESPACIOS VACÍOS */
.butaca-vacia {
    width: 42px;
    height: 42px;
}

/* ===== RESUMEN DE SELECCIÓN ===== */
.resumen-seleccion {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
    border: 1px solid #4a5568;
    border-radius: 16px;
    padding: 1.75rem;
    margin-top: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}

.resumen-info h3 {
    color: #fff;
    margin: 0 0 1rem;
    font-size: 1.05rem;
    font-weight: 700;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #4a5568;
}

#butacas-seleccionadas > p {
    color: #718096;
    font-size: 0.9rem;
    font-style: italic;
}

.lista-seleccionadas {
    margin-bottom: 0.25rem;
}

.butaca-seleccionada {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0;
    border-bottom: 1px solid rgba(74, 85, 104, 0.5);
    color: #e2e8f0;
    font-size: 0.9rem;
}

.butaca-seleccionada:last-child {
    border-bottom: none;
}

.total-precio {
    text-align: center;
    font-size: 1.4rem;
    color: #fff;
    margin: 1.25rem 0;
    padding: 1rem;
    background: rgba(237, 133, 15, 0.12);
    border-radius: 10px;
    border: 1px solid rgba(237, 133, 15, 0.35);
}

.total-precio strong {
    color: #ed850f;
}

/* ===== ACCIONES ===== */
.acciones-butacas {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-limpiar,
.btn-agregar-carrito,
.btn-volver {
    padding: 0.8rem 1.6rem;
    border-radius: 10px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-limpiar {
    background: transparent;
    color: #cbd5e0;
    border: 1px solid #4a5568;
}

.btn-limpiar:hover {
    border-color: #ef4444;
    color: #fca5a5;
}

.btn-agregar-carrito {
    background: linear-gradient(135deg, #ed850f, #f7931e);
    color: #fff;
    box-shadow: 0 4px 15px rgba(237, 133, 15, 0.35);
}

.btn-agregar-carrito:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(237, 133, 15, 0.5);
}

.btn-agregar-carrito:disabled {
    background: #4a5568;
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
    box-shadow: none;
}

.btn-volver {
    background: transparent;
    color: #a0aec0;
    border: 1px solid #4a5568;
}

.btn-volver:hover {
    border-color: #ed850f;
    color: #ed850f;
}

/* ===== LOADING Y ERROR ===== */
.loading {
    text-align: center;
    padding: 3rem;
    color: #ed850f;
}

.loading i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.error-butacas {
    text-align: center;
    padding: 3rem;
    color: #ef4444;
}

.error-butacas i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.btn-reintentar {
    background: #ef4444;
    color: #fff;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 1rem;
    font-weight: 600;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .butacas-container {
        padding: 1rem;
    }

    .butacas-header h1 {
        font-size: 1.7rem;
    }

    .pelicula-info {
        flex-direction: column;
        text-align: center;
    }

    .detalles p {
        justify-content: center;
    }

    .leyenda {
        gap: 0.5rem;
    }

    .leyenda-item {
        font-size: 0.75rem;
        padding: 0.45rem 0.8rem;
    }

    .fila-butacas {
        gap: 0.3rem;
    }

    .fila-numero {
        min-width: 26px;
        font-size: 0.65rem;
    }

    .butaca,
    .butaca-vacia {
        width: 34px;
        height: 34px;
    }

    .butaca-icon {
        font-size: 15px !important;
    }

    .numero-butaca {
        font-size: 7px !important;
    }

    .acciones-butacas {
        flex-direction: column;
    }

    .btn-limpiar,
    .btn-agregar-carrito,
    .btn-volver {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    /* Salas anchas: el mapa se desplaza dentro de su recuadro. Los márgenes auto lo centran
       cuando entra y, a diferencia de justify-content:center, no lo recortan cuando desborda. */
    .mapa-butacas {
        justify-content: flex-start;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .sala-grid {
        width: max-content;
        max-width: none;
        margin: 0 auto;
    }
}

@media (max-width: 480px) {
    .butaca,
    .butaca-vacia {
        width: 28px;
        height: 28px;
    }

    .butaca-icon {
        font-size: 13px !important;
    }

    .numero-butaca {
        font-size: 6px !important;
    }

    .fila-numero {
        min-width: 20px;
        font-size: 0.6rem;
    }

    .sala-grid {
        gap: 0.4rem;
        padding: 1.5rem 0.75rem 1rem;
    }
}
</style>