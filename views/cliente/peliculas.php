
    <div class="peliculas-container">
       <<?php
// Obtener películas próximamente para el carrusel
$peliculasProximamente = \Controllers\PeliculaController::obtenerPeliculasProximamente();
?>

<!-- Carrusel de Películas Próximamente -->
<div class="carrusel-container">
    <div class="carrusel-wrapper">
        <?php if (!empty($peliculasProximamente)): ?>
            <!-- Indicador de autoplay -->
            <div class="autoplay-indicator" id="autoplayIndicator">
                <i class="fas fa-play"></i>
                <span>Auto</span>
            </div>
            
            <!-- Slides -->
            <div class="carrusel-slides" id="carruselSlides">
                <!-- Los slides se generarán dinámicamente con JavaScript -->
            </div>

            <!-- Controles -->
            <div class="carrusel-controls">
                <button class="control-btn" id="prevBtn" onclick="anteriorSlide()" aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <button class="control-btn" id="nextBtn" onclick="siguienteSlide()" aria-label="Siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <div class="slide-counter" id="slideCounter">1 / 1</div>
            </div>
        <?php else: ?>
            <div class="no-peliculas-carrusel">
                <h3><i class="fas fa-film"></i> No hay próximos estrenos</h3>
                <p>Pronto tendremos nuevas películas para ti</p>
            </div>
        <?php endif; ?>
    </div>
</div>

        <?php if (empty($peliculas)): ?>
        <div class="no-peliculas">
            <h2>🎭 No hay películas disponibles</h2>
            <p>Próximamente tendremos nuevos estrenos para ti</p>
        </div>
        <?php else: ?>
        <div class="peliculas-grid">
            <?php foreach ($peliculas as $pelicula): ?>
            <div class="pelicula-card">
                <!-- ESTADO DE LA PELÍCULA -->
                <div class="estado-pelicula">
                    <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $pelicula->nombre_estado_pelicula ?? '')); ?>">
                        <?php if (($pelicula->nombre_estado_pelicula ?? '') === 'En emisión'): ?>
                            🔴 EN EMISIÓN
                        <?php elseif (($pelicula->nombre_estado_pelicula ?? '') === 'Próximamente'): ?>
                            🟡 PRÓXIMAMENTE
                        <?php else: ?>
                            📽️ <?php echo strtoupper($pelicula->nombre_estado_pelicula ?? 'DISPONIBLE'); ?>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- IMAGEN DE LA PELÍCULA -->
                <div class="poster-container">
                    <?php if (!empty($pelicula->imagen_pelicula)): ?>
                        <img src="/assets/img/peliculas/<?php echo htmlspecialchars($pelicula->imagen_pelicula); ?>" 
                             alt="<?php echo htmlspecialchars($pelicula->titulo_pelicula); ?>">
                    <?php else: ?>
                        <div class="poster-placeholder">
                            <i class="fas fa-film"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- BOTÓN DE YOUTUBE SUPERPUESTO -->
                    <div class="youtube-overlay" onclick="abrirTrailer('<?php echo htmlspecialchars($pelicula->id_pelicula); ?>', '<?php echo htmlspecialchars($pelicula->titulo_pelicula); ?>')">
                        <div class="youtube-button">
                            <i class="fab fa-youtube"></i>
                            <span>Ver Tráiler</span>
                        </div>
                    </div>
                </div>

                <!-- INFORMACIÓN DE LA PELÍCULA -->
                <div class="card-body">
                    <h3 class="titulo-pelicula">
                        <?php echo htmlspecialchars($pelicula->titulo_pelicula); ?>
                    </h3>

                    <div class="info-adicional">
                        <?php if (!empty($pelicula->anyo_pelicula)): ?>
                        <div class="detalle-item">
                            <span class="detalle-icono">📅</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($pelicula->anyo_pelicula); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pelicula->duracion_pelicula)): ?>
                        <div class="detalle-item">
                            <span class="detalle-icono">⏱️</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($pelicula->duracion_pelicula); ?> min</span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pelicula->nombre_tipo_clasificacion)): ?>
                        <div class="detalle-item">
                            <span class="detalle-icono">🔞</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($pelicula->nombre_tipo_clasificacion); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pelicula->generos)): ?>
                        <div class="detalle-item generos">
                            <span class="detalle-icono">🎭</span>
                            <span class="detalle-valor"><?php echo htmlspecialchars($pelicula->generos); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- SINOPSIS -->
                    <div class="sinopsis">
                        <h4>📖 Sinopsis</h4>
                        <p><?php echo htmlspecialchars($pelicula->sinopsis_pelicula ?? 'Descripción no disponible'); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- MODAL PARA TRÁILER -->
    <div id="trailerModal" class="modal-trailer">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitulo" >Tráiler de la Película Desde CINFSA</h3>
                <span class="close-modal" onclick="cerrarTrailer()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="video-container">
                    <iframe id="youtubeFrame" 
                            width="100%" 
                            height="100%" 
                            src="" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                    </iframe>
                </div>
                <div class="video-loading" id="videoLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando tráiler desde CINFSA...</p>
                </div>
                <div class="video-error" id="videoError" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>No se pudo cargar el tráiler para esta película</p>
                </div>
            </div>
        </div>
    </div>

    <script>
       // Función para abrir el tráiler 
        function abrirTrailer(idPelicula, tituloPelicula) {
            console.log('Abriendo tráiler para película ID:', idPelicula, 'Título:', tituloPelicula);
            
            const modal = document.getElementById('trailerModal');
            const iframe = document.getElementById('youtubeFrame');
            const titulo = document.getElementById('modalTitulo');
            const loading = document.getElementById('videoLoading');
            const error = document.getElementById('videoError');
            
            // Pausar carrusel si existe
            if (typeof carrusel !== 'undefined' && carrusel) {
                carrusel.detenerAutoPlay();
            }
            
            // Configurar título
            titulo.textContent = `Tráiler Desde CINFSA: ${tituloPelicula}`;
            
            // Mostrar modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Mostrar loading
            loading.style.display = 'flex';
            error.style.display = 'none';
            iframe.style.display = 'none';
            
            // Obtener tráiler desde la base de datos
            obtenerTrailerDesdeDB(idPelicula, tituloPelicula, iframe, loading, error);
        }

        // Función para cerrar el modal (actualizada para reanudar carrusel)
        function cerrarTrailer() {
            const modal = document.getElementById('trailerModal');
            const iframe = document.getElementById('youtubeFrame');
            
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Detener video
            iframe.src = '';
            
            // Reanudar carrusel si existe
            if (typeof carrusel !== 'undefined' && carrusel) {
                carrusel.iniciarAutoPlay();
            }
        }

        // Función para obtener tráiler desde base de datos
       async function obtenerTrailerDesdeDB(idPelicula, tituloPelicula, iframe, loading, error) {
            try {
                console.log('=== OBTENIENDO TRAILER ===');
                console.log('Película ID:', idPelicula);
                console.log('Título:', tituloPelicula);
                
                const requestData = {
                    id_pelicula: parseInt(idPelicula) // Asegurar que sea un número
                };
                console.log('Datos enviados:', requestData);
                
                const response = await fetch('/api/obtener-trailer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });

                console.log('Respuesta HTTP:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Respuesta completa:', data);

                if (data.ok && data.trailer_url) {
                    let trailerUrl = convertirAEmbedYoutube(data.trailer_url);
                    console.log('URL original:', data.trailer_url);
                    console.log('URL convertida:', trailerUrl);
                    iframe.src = trailerUrl;
                    
                    setTimeout(() => {
                        loading.style.display = 'none';
                        iframe.style.display = 'block';
                    }, 1000);
                } else {
                    console.log('No hay tráiler en DB para:', tituloPelicula);
                    buscarEnYoutube(tituloPelicula, iframe, loading, error);
                }
            } catch (err) {
                console.error('Error completo:', err);
                buscarEnYoutube(tituloPelicula, iframe, loading, error);
            }
        }
        // Función para convertir URL a formato embed
        function convertirAEmbedYoutube(url) {
            const videoId = extraerVideoIdYoutube(url);
            if (videoId) {
                return `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
            }
            return url;
        }

        // Función para extraer ID de video de YouTube
        function extraerVideoIdYoutube(url) {
            const patrones = [
                /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
                /youtube\.com\/v\/([^&\n?#]+)/
            ];
            
            for (const patron of patrones) {
                const match = url.match(patron);
                if (match) {
                    return match[1];
                }
            }
            
            return null;
        }

        // Función fallback para buscar en YouTube
        function buscarEnYoutube(tituloPelicula, iframe, loading, error) {
            console.log('Buscando tráiler alternativo para:', tituloPelicula);
            
            const trailersPopulares = {
                'spider-man': 'JfVOs4VSpmA',
                'spiderman': 'JfVOs4VSpmA',
                'avengers': '6ZfuNTqbHE8',
                'batman': 'mqqft2x_Aa4',
                'superman': 'T6DJcgm3wNY',
                'iron man': 'KAE5ymVLmZg',
                'thor': 'JOddp-nlNvQ',
                'test': 'dQw4w9WgXcQ' // Video de prueba
            };

            const tituloLower = tituloPelicula.toLowerCase();
            let youtubeId = null;

            // Buscar coincidencia
            for (const [key, id] of Object.entries(trailersPopulares)) {
                if (tituloLower.includes(key)) {
                    youtubeId = id;
                    break;
                }
            }

            if (youtubeId) {
                iframe.src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1&rel=0`;
                setTimeout(() => {
                    loading.style.display = 'none';
                    iframe.style.display = 'block';
                }, 1000);
            } else {
                setTimeout(() => {
                    loading.style.display = 'none';
                    error.style.display = 'flex';
                }, 1500);
            }
        }

        // Cerrar modal al hacer clic fuera del contenido
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('trailerModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        cerrarTrailer();
                    }
                });
            }
        });

        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarTrailer();
            }
        });
    </script>
<script>
// Pasar datos de PHP a JavaScript - VERIFICAR QUE ESTA LÍNEA ESTÉ PRESENTE
const peliculasProximamente = <?php echo json_encode($peliculasProximamente ?? []); ?>;

// Verificar que tenemos datos
console.log('=== DEBUG PELÍCULAS PRÓXIMAMENTE ===');
console.log('Datos recibidos:', peliculasProximamente);
console.log('Total películas:', peliculasProximamente ? peliculasProximamente.length : 0);

if (peliculasProximamente && peliculasProximamente.length > 0) {
    peliculasProximamente.forEach((pelicula, index) => {
        console.log(`Película ${index + 1}:`, {
            id: pelicula.id_pelicula,
            titulo: pelicula.titulo_pelicula,
            imagen: pelicula.imagen_pelicula,
            trailer_url: pelicula.trailer_url
        });
    });
} else {
    console.warn('No hay películas próximamente o los datos están vacíos');
}

class CarruselPeliculas {
    constructor(peliculas) {
        this.peliculas = peliculas;
        this.slideActual = 0;
        this.totalSlides = peliculas.length;
        this.autoPlayInterval = null;
        this.autoPlayDuracion = 5000;
        
        this.init();
    }
    
    init() {
        this.crearSlides();
        this.iniciarAutoPlay();
    }
    
    crearSlides() {
        const container = document.getElementById('carruselSlides');
        if (!this.peliculas || this.peliculas.length === 0) {
            console.warn('No hay películas para mostrar en el carrusel');
            return;
        }
        
        let slidesHTML = '';
        
        this.peliculas.forEach((pelicula, index) => {
            const imagenSrc = pelicula.imagen_pelicula ? 
                `/assets/img/peliculas/${pelicula.imagen_pelicula}` : '';
            
            slidesHTML += `
                <div class="slide ${index === 0 ? 'active' : ''}">
                    <div class="slide-pelicula-single">
                        <!-- Poster de la película -->
                        <div class="slide-poster">
                            ${imagenSrc ? 
                                `<img src="${imagenSrc}" alt="${pelicula.titulo_pelicula}" loading="lazy">` :
                                `<div class="poster-placeholder">
                                    <i class="fas fa-film"></i>
                                    <p>Sin imagen</p>
                                </div>`
                            }
                        </div>
                        
                        <!-- Información de la película -->
                        <div class="slide-info">
                            <div class="slide-estado">
                                <span class="slide-badge">
                                    <i class="fas fa-calendar-star"></i>
                                    PRÓXIMAMENTE
                                </span>
                            </div>
                            
                            <h2 class="slide-titulo">${pelicula.titulo_pelicula}</h2>
                            
                            <div class="slide-detalles">
                                ${pelicula.anyo_pelicula ? `
                                    <div class="detalle-item">
                                        <div class="detalle-icon">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="detalle-texto">
                                            <span class="detalle-label">Año:</span>
                                            ${pelicula.anyo_pelicula}
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${pelicula.duracion_pelicula ? `
                                    <div class="detalle-item">
                                        <div class="detalle-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="detalle-texto">
                                            <span class="detalle-label">Duración:</span>
                                            ${pelicula.duracion_pelicula} minutos
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${pelicula.genero ? `
                                    <div class="detalle-item">
                                        <div class="detalle-icon">
                                            <i class="fas fa-tags"></i>
                                        </div>
                                        <div class="detalle-texto">
                                            <span class="detalle-label">Género:</span>
                                            ${pelicula.genero}
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${pelicula.clasificacion ? `
                                    <div class="detalle-item">
                                        <div class="detalle-icon">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="detalle-texto">
                                            <span class="detalle-label">Clasificación:</span>
                                            ${pelicula.clasificacion}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = slidesHTML;
        this.actualizarContador();
    }
    
    siguiente() {
        const slides = document.querySelectorAll('.slide');
        if (slides.length === 0) return;
        
        slides[this.slideActual].classList.remove('active');
        this.slideActual = (this.slideActual + 1) % this.totalSlides;
        slides[this.slideActual].classList.add('active');
        this.actualizarContador();
    }
    
    anterior() {
        const slides = document.querySelectorAll('.slide');
        if (slides.length === 0) return;
        
        slides[this.slideActual].classList.remove('active');
        this.slideActual = this.slideActual === 0 ? this.totalSlides - 1 : this.slideActual - 1;
        slides[this.slideActual].classList.add('active');
        this.actualizarContador();
    }
    
    actualizarContador() {
        const contador = document.getElementById('slideCounter');
        if (contador) {
            contador.textContent = `${this.slideActual + 1} / ${this.totalSlides}`;
        }
    }
    
    iniciarAutoPlay() {
        if (this.totalSlides <= 1) return;
        
        this.autoPlayInterval = setInterval(() => {
            this.siguiente();
        }, this.autoPlayDuracion);
    }
    
    detenerAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }
}

let carruselInstance = null;

function siguienteSlide() {
    if (carruselInstance) {
        carruselInstance.siguiente();
    }
}

function anteriorSlide() {
    if (carruselInstance) {
        carruselInstance.anterior();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof peliculasProximamente !== 'undefined' && peliculasProximamente.length > 0) {
        carruselInstance = new CarruselPeliculas(peliculasProximamente);
        console.log('Carrusel inicializado con', peliculasProximamente.length, 'películas');
    }
});


</script>
</body>
</html>