<div class="calificar-container">
    <div class="calificar-header">
        <h1><i class="fa-solid fa-star"></i> Calificar Películas</h1>
        <p>Elegí una película que ya viste y contanos qué te pareció</p>
    </div>

    <div class="buscador-peliculas">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscadorTitulo" placeholder="Buscar por título..." oninput="filtrarPeliculas()">
    </div>

    <div class="peliculas-grid" id="peliculasGrid">
        <?php foreach ($peliculas as $pelicula): ?>
            <div class="pelicula-card" data-titulo="<?php echo strtolower(htmlspecialchars($pelicula['titulo_pelicula'])); ?>">
                <img src="/assets/img/peliculas/<?php echo s($pelicula['imagen_pelicula']); ?>"
                    alt="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>"
                    onerror="this.src='/assets/img/LOGO.png'">
                <div class="pelicula-info">
                    <h3><?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?></h3>
                    <?php if ($pelicula['ya_calificada']): ?>
                        <div class="ya-calificada">
                            <i class="fa-solid fa-star"></i> Ya calificaste: <?php echo number_format($pelicula['mi_puntuacion'], 1); ?>/10
                        </div>
                    <?php else: ?>
                        <button class="btn-calificar" onclick="abrirModalCalificar(<?php echo $pelicula['id_pelicula']; ?>, '<?php echo htmlspecialchars(addslashes($pelicula['titulo_pelicula'])); ?>')">
                            Calificar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($peliculas)): ?>
        <p style="text-align:center; color:#a0aec0; margin-top: 40px;">No hay películas disponibles para calificar todavía.</p>
    <?php endif; ?>
</div>

<div id="modalCalificar" class="modal-calificar" style="display:none;">
    <div class="modal-calificar-content">
        <span class="cerrar-modal-calificar" onclick="cerrarModalCalificar()">&times;</span>
        <h2 id="tituloModalCalificar"></h2>

        <div id="mensajeCalificar"></div>

        <label class="dato-user">Tu puntuación (0.0 a 10.0):</label>
        <input type="number" id="inputPuntuacion" min="0" max="10" step="0.1" placeholder="Ej: 8.5">

        <label class="dato-user" style="margin-top:15px; display:block;">Comentario (opcional):</label>
        <textarea id="inputComentario" rows="4" placeholder="¿Qué te pareció la película? (sin spoilers, por favor)"></textarea>

        <div style="text-align:center; margin-top:20px;">
            <button class="btn btn-success" onclick="guardarCalificacion()">Guardar Calificación</button>
            <button class="btn btn-secondary" onclick="cerrarModalCalificar()">Cancelar</button>
        </div>
    </div>
</div>

<style>
    .calificar-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .calificar-header {
        text-align: center;
        margin-bottom: 25px;
    }

    .calificar-header h1 {
        color: #ed850f;
        font-size: 2rem;
    }

    .calificar-header p {
        color: #a0aec0;
    }

    .buscador-peliculas {
        max-width: 400px;
        margin: 0 auto 30px;
        position: relative;
    }

    .buscador-peliculas i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
    }

    .buscador-peliculas input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border-radius: 25px;
        border: 2px solid #4a5568;
        background: #2d3748;
        color: #e2e8f0;
        box-sizing: border-box;
    }

    .buscador-peliculas input:focus {
        outline: none;
        border-color: #ed850f;
    }

    .peliculas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
    }

    .pelicula-card {
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s ease;
    }

    .pelicula-card:hover {
        transform: translateY(-4px);
    }

    .pelicula-card img {
        width: 100%;
        height: 260px;
        object-fit: cover;
    }

    .pelicula-info {
        padding: 12px;
        text-align: center;
    }

    .pelicula-info h3 {
        color: #e2e8f0;
        font-size: 14px;
        margin: 0 0 10px;
        min-height: 36px;
    }

    .btn-calificar {
        background: #ed850f;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        width: 100%;
    }

    .btn-calificar:hover {
        background: #d67607;
    }

    .ya-calificada {
        color: #22c55e;
        font-size: 13px;
        font-weight: 600;
    }

    .modal-calificar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-calificar-content {
        background: #2d3748;
        border-radius: 15px;
        padding: 30px;
        max-width: 450px;
        width: 90%;
        position: relative;
        border: 2px solid #ed850f;
    }

    .cerrar-modal-calificar {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 28px;
        color: #a0aec0;
        cursor: pointer;
    }

    .modal-calificar-content h2 {
        color: #ed850f;
        margin-bottom: 20px;
        padding-right: 30px;
    }

    .modal-calificar-content input,
    .modal-calificar-content textarea {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 2px solid #4a5568;
        background: #1a202c;
        color: #e2e8f0;
        box-sizing: border-box;
        margin-top: 6px;
    }

    .modal-calificar-content input:focus,
    .modal-calificar-content textarea:focus {
        outline: none;
        border-color: #ed850f;
    }

    .btn {
        padding: 10px 24px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin: 0 5px;
    }

    .btn-success {
        background: #22c55e;
        color: white;
    }

    .btn-success:hover {
        background: #16a34a;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #4a5568;
        color: white;
    }

    .btn-secondary:hover {
        background: #6b7280;
        transform: translateY(-1px);
    }

    .alert {
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .alert-success {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid #22c55e;
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid #ef4444;
    }

    .alert-info {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }
</style>

<script>
    function filtrarPeliculas() {
        const termino = document.getElementById('buscadorTitulo').value.toLowerCase();
        document.querySelectorAll('.pelicula-card').forEach(card => {
            card.style.display = card.getAttribute('data-titulo').includes(termino) ? '' : 'none';
        });
    }

    let peliculaSeleccionadaId = null;

    function abrirModalCalificar(idPelicula, titulo) {
        peliculaSeleccionadaId = idPelicula;
        document.getElementById('tituloModalCalificar').textContent = titulo;
        document.getElementById('inputPuntuacion').value = '';
        document.getElementById('inputComentario').value = '';
        document.getElementById('mensajeCalificar').innerHTML = '';
        document.getElementById('modalCalificar').style.display = 'flex';
    }

    function cerrarModalCalificar() {
        document.getElementById('modalCalificar').style.display = 'none';
    }

    function guardarCalificacion() {
        const puntuacion = parseFloat(document.getElementById('inputPuntuacion').value);
        const comentario = document.getElementById('inputComentario').value.trim();
        const mensajeEl = document.getElementById('mensajeCalificar');

        if (isNaN(puntuacion) || puntuacion < 0 || puntuacion > 10) {
            mensajeEl.innerHTML = '<div class="alert alert-danger">Ingresá un número entre 0.0 y 10.0</div>';
            return;
        }

        mensajeEl.innerHTML = '<div class="alert alert-info">Guardando...</div>';

        fetch('/calificar-peliculas/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_pelicula: peliculaSeleccionadaId,
                    puntuacion: puntuacion,
                    comentario: comentario
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    mensajeEl.innerHTML = `<div class="alert alert-success">${data.mensaje}</div>`;
                    setTimeout(() => location.reload(), 1200);
                } else {
                    mensajeEl.innerHTML = `<div class="alert alert-danger">${data.mensaje}</div>`;
                }
            })
            .catch(error => {
                mensajeEl.innerHTML = `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }
</script>