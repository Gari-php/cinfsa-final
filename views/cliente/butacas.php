
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
    margin-bottom: 2rem;
}

.butacas-header h1 {
    font-size: 2.5rem;
    color: #ed850f;
    margin-bottom: 1rem;
}

/* ===== INFORMACIÓN DE LA FUNCIÓN ===== */
.funcion-info {
    background: #2d3748;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.pelicula-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.pelicula-poster {
    width: 100px;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
}

.detalles h2 {
    color: #ed850f;
    margin-bottom: 1rem;
}

.detalles p {
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detalles i {
    color: #ed850f;
    width: 20px;
}

/* ===== LEYENDA - COLORES ESPECÍFICOS ===== */
.leyenda {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
    background-color: #2d3748;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 2px solid #ed850f;
    flex-wrap: wrap;
}

.leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    font-weight: 500;
    color: white;
}

/* COLORES ESPECÍFICOS DE LA LEYENDA */
.disponible-icon {
    color: #28a745 !important;
    font-size: 25px !important;
}

.seleccionada-icon {
    color: #2563eb !important;
    font-size: 25px !important;
}

.bloqueada-icon {
    color: #dc3545 !important;
    font-size: 25px !important;
}

.ocupada-icon {
    color: #ff8c00 !important;
    font-size: 25px !important;
}

/* ===== PANTALLA ===== */
.pantalla {
    text-align: center;
    margin-bottom: 3rem;
}

.pantalla-texto {
    background: linear-gradient(135deg, #4a5568, #2d3748);
    color: #fff;
    padding: 1rem 3rem;
    border-radius: 50px;
    display: inline-block;
    font-weight: bold;
    font-size: 1.2rem;
    border: 2px solid #ed850f;
    box-shadow: 0 4px 15px rgba(237, 133, 15, 0.3);
}

/* ===== MAPA DE BUTACAS ===== */
.mapa-butacas {
    margin-bottom: 2rem;
    min-height: 400px;
    display: flex;
    justify-content: center;
    color: #fff !important;
}

.sala-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
    width: 100%;
    max-width: 800px;
}

.fila-butacas {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ed850f;
    justify-content: center;
}

.fila-numero {
    font-weight: bold;
    color: #ed850f;
    min-width: 80px;
    text-align: center;
    font-size: 0.9rem;
    margin-right: 10px;
}

/* ===== BUTACAS - ESTILOS BASE ===== */
.butaca {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
}

.butaca-icon {
    font-size: 20px !important;
    transition: all 0.3s ease;
    font-weight: 900 !important;
}

.numero-butaca {
    font-size: 9px !important;
    font-weight: bold;
    margin-top: 2px;
    color: white !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}

/* ===== ESTADOS DE BUTACAS - MÁXIMA ESPECIFICIDAD ===== */

/* DISPONIBLES - VERDE */
.butaca.disponible .butaca-icon {
    color: #28a745 !important;
}

.butaca.disponible:hover .butaca-icon {
    color: #22c55e !important;
    transform: scale(1.2);
}

/* SELECCIONADAS - AZUL OSCURO */
.butaca.seleccionada .butaca-icon {
    color: #2563eb !important;
    transform: scale(1.1) !important;
}

/* BLOQUEADAS - ROJO */
.butaca.bloqueada .butaca-icon {
    color: #dc3545 !important;
    opacity: 0.8;
}

.butaca.bloqueada {
    cursor: not-allowed !important;
}

/* OCUPADAS/RESERVADAS - NARANJA */
.butaca.ocupada .butaca-icon {
    color: #ff8c00 !important;
    opacity: 0.8;
}

.butaca.ocupada {
    cursor: not-allowed !important;
}

/* ESPACIOS VACÍOS */
.butaca-vacia {
    width: 45px;
    height: 45px;
}

/* ===== RESUMEN DE SELECCIÓN ===== */
.resumen-seleccion {
    background: #2d3748;
    border-radius: 12px;
    padding: 2rem;
    margin-top: 2rem;
}

.resumen-info h3 {
    color: #ed850f;
    margin-bottom: 1rem;
    font-size: 1.3rem;
}

.lista-seleccionadas {
    margin-bottom: 1rem;
}

.butaca-seleccionada {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #4a5568;
    color: #e2e8f0;
}

.butaca-seleccionada:last-child {
    border-bottom: none;
}

.total-precio {
    text-align: center;
    font-size: 1.5rem;
    color: #ed850f;
    margin: 1.5rem 0;
    padding: 1rem;
    background: rgba(237, 133, 15, 0.1);
    border-radius: 8px;
    border: 2px solid rgba(237, 133, 15, 0.3);
}

/* ===== ACCIONES ===== */
.acciones-butacas {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-limpiar,
.btn-agregar-carrito,
.btn-volver {
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.btn-limpiar {
    background: #6b7280;
    color: #fff;
}

.btn-limpiar:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.btn-agregar-carrito {
    background: #22c55e;
    color: #fff;
}

.btn-agregar-carrito:hover:not(:disabled) {
    background: #16a34a;
    transform: translateY(-2px);
}

.btn-agregar-carrito:disabled {
    background: #6b7280;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-volver {
    background: #ed850f;
    color: #fff;
}

.btn-volver:hover {
    background: #d67607;
    transform: translateY(-2px);
}

/* ===== LOADING Y ERROR ===== */
.loading {
    text-align: center;
    padding: 3rem;
    color: #ed850f;
}

.loading i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.error-butacas {
    text-align: center;
    padding: 3rem;
    color: #ef4444;
}

.error-butacas i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.btn-reintentar {
    background: #ef4444;
    color: #fff;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 1rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .butacas-container {
        padding: 1rem;
    }
    
    .butacas-header h1 {
        font-size: 2rem;
    }
    
    .pelicula-info {
        flex-direction: column;
        text-align: center;
    }
    
    .leyenda {
        gap: 1rem;
    }
    
    .leyenda-item {
        flex-direction: column;
        text-align: center;
        gap: 0.3rem;
    }
    
    .fila-butacas {
        gap: 0.3rem;
    }
    
    .fila-numero {
        min-width: 50px;
        font-size: 0.8rem;
    }
    
    .butaca,
    .butaca-vacia {
        width: 35px;
        height: 35px;
    }
    
    .butaca-icon {
        font-size: 16px !important;
    }
    
    .numero-butaca {
        font-size: 8px !important;
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

@media (max-width: 480px) {
    .butaca,
    .butaca-vacia {
        width: 30px;
        height: 30px;
    }
    
    .butaca-icon {
        font-size: 14px !important;
    }
    
    .numero-butaca {
        font-size: 7px !important;
    }
    
    .fila-numero {
        min-width: 40px;
        font-size: 0.7rem;
    }
    
    .sala-grid {
        gap: 0.5rem;
    }
}
</style>