<div class="calificar-container">
    <div class="calificar-header">
        <h1><i class="fa-solid fa-chart-simple"></i> Calificación de Películas</h1>
        <p>Lo que opinan los usuarios de CINFSA</p>
    </div>

    <div class="buscador-peliculas">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscadorTitulo" placeholder="Buscar por título..." oninput="filtrarPeliculas()">
    </div>

    <div class="peliculas-grid" id="peliculasGrid">
        <?php foreach ($peliculas as $pelicula): ?>
            <div class="pelicula-card" data-titulo="<?php echo strtolower(htmlspecialchars($pelicula['titulo_pelicula'])); ?>">
                <img src="/assets/img/peliculas/<?php echo htmlspecialchars($pelicula['imagen_pelicula']); ?>"
                    alt="<?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?>"
                    onerror="this.src='/assets/img/LOGO.png'">
                <div class="pelicula-info">
                    <h3><?php echo htmlspecialchars($pelicula['titulo_pelicula']); ?></h3>
                    <?php if ($pelicula['total_resenas'] > 0): ?>
                        <div class="promedio-estrella"><i class="fa-solid fa-star"></i> <?php echo number_format($pelicula['promedio'], 1); ?>/10</div>
                        <div class="total-resenas"><?php echo $pelicula['total_resenas']; ?> calificaciones</div>
                    <?php else: ?>
                        <div class="sin-calificaciones">Sin calificaciones aún</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
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

    .promedio-estrella {
        color: #ed850f;
        font-weight: 700;
        font-size: 16px;
    }

    .total-resenas {
        color: #a0aec0;
        font-size: 12px;
        margin-top: 3px;
    }

    .sin-calificaciones {
        color: #6b7280;
        font-size: 13px;
        font-style: italic;
    }
</style>

<script>
    function filtrarPeliculas() {
        const termino = document.getElementById('buscadorTitulo').value.toLowerCase();
        document.querySelectorAll('.pelicula-card').forEach(card => {
            card.style.display = card.getAttribute('data-titulo').includes(termino) ? '' : 'none';
        });
    }
</script>