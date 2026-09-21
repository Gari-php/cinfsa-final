<body>
    <div class="form-container" id="pagina-crear-pelicula">
        <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
        <h2>Crear Nueva Película</h2>

        <form method="POST"
            action="/administrador/peliculas/guardar"
            enctype="multipart/form-data"
            data-fetch="true"
            data-alerta=".contenedor-alertas"
            data-redirigir="/administrador/peliculas/listado">

            <div class="campo full-ancho">
                <label for="titulo_pelicula" class="requerido">Título</label>
                <input type="text" name="titulo_pelicula" id="titulo_pelicula" placeholder="Título de la película">
                <div class="ayuda">Ingrese el título completo de la película</div>
            </div>

            <div class="campo full-ancho">
                <label for="sinopsis_pelicula">Sinopsis</label>
                <textarea name="sinopsis_pelicula" id="sinopsis_pelicula" placeholder="Escribe la sinopsis"></textarea>
            </div>

            <div class="campo">
                <label for="anyo_pelicula" class="requerido">Año</label>
                <input type="number" name="anyo_pelicula" id="anyo_pelicula" placeholder="Año de estreno">
                <div class="ayuda">Año de <?php echo date('Y'); ?> o anterior</div>
            </div>

            <div class="campo">
                <label for="duracion_pelicula" class="requerido">Duración (min)</label>
                <input type="number" name="duracion_pelicula" id="duracion_pelicula" placeholder="Duración en minutos" min="1">
            </div>

            <div class="campo">
                <label for="rela_tipo_clasificacion" class="requerido">Clasificación</label>
                <select name="rela_tipo_clasificacion" id="rela_tipo_clasificacion">
                    <option value="" disabled selected>-- Seleccione --</option>
                    <?php foreach ($clasificaciones as $clasificacion): ?>
                        <option value="<?php echo $clasificacion['id_tipo_clasificacion']; ?>">
                            <?php echo $clasificacion['nombre_tipo_clasificacion']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="rela_estado_pelicula" class="requerido">Estado</label>
                <select name="rela_estado_pelicula" id="rela_estado_pelicula">
                    <option value="" disabled selected>-- Seleccione --</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?php echo $estado['id_estado_pelicula']; ?>">
                            <?php echo $estado['nombre_estado_pelicula']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label class="requerido">Idiomas</label>
                <select name="idiomas[]" id="rela_idioma_pelicula" multiple size="4">
                    <?php foreach ($idiomas as $idioma): ?>
                        <option value="<?php echo $idioma['id_idioma_pelicula']; ?>">
                            <?php echo $idioma['nombre_idioma_pelicula']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ayuda">Mantén presionado Ctrl (o Cmd en Mac) para seleccionar múltiples idiomas</div>
            </div>

            <div class="campo">
                <label for="rela_genero_pelicula">Géneros</label>
                <select name="generos[]" id="rela_genero_pelicula" multiple size="5">
                    <?php foreach ($generos as $genero): ?>
                        <option value="<?php echo $genero['id_genero_pelicula']; ?>">
                            <?php echo htmlspecialchars($genero['genero_pelicula']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="ayuda">Mantén presionado Ctrl (o Cmd en Mac) para seleccionar múltiples géneros</div>
            </div>

            <div class="campo full-ancho">
                <label for="trailer_url">URL del Tráiler (Opcional)</label>
                <input type="url" name="trailer_url" id="trailer_url" placeholder="https://www.youtube.com/watch?v=...">
                <div class="ayuda">
                    <strong>Formatos aceptados de YouTube:</strong><br>
                    • https://www.youtube.com/watch?v=VIDEO_ID<br>
                    • https://youtu.be/VIDEO_ID<br>
                    • https://www.youtube.com/embed/VIDEO_ID<br>
                    <em>Este campo es opcional. Si no tienes tráiler, déjalo vacío.</em>
                </div>
                <div class="preview-trailer" id="preview-trailer" style="display: none;">
                    <p><strong>Vista previa del tráiler:</strong></p>
                    <iframe id="iframe-preview" width="300" height="169" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>

            <div class="campo full-ancho">
                <label for="imagen_pelicula">Imagen de la Película</label>
                <input type="file" name="imagen_pelicula" id="imagen_pelicula" accept="image/*">
                <div class="ayuda">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 5MB</div>
                <div class="preview-imagen" id="preview-imagen" style="display: none;">
                    <img id="img-preview" src="" alt="Preview">
                </div>
            </div>

            <input type="submit" class="boton" value="Crear Película">

            <div class="contenedor-alertas"></div>

            <div class="acciones">
                <a href="/administrador/peliculas/listado" class="boton-secundario">Volver al Listado</a>
            </div>
        </form>
    </div>

    <style>
        #pagina-crear-pelicula.form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
            border: 2px solid #ed850f;
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 820px;
            margin: 40px auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        #pagina-crear-pelicula .form-logo {
            width: 90px;
            display: block;
            margin: 0 auto 15px;
        }

        #pagina-crear-pelicula h2 {
            color: #ed850f !important;
            text-align: center;
            font-size: 1.6rem;
            margin-bottom: 1.5rem;
            background: none !important;
        }

        #pagina-crear-pelicula form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 18px;
        }

        #pagina-crear-pelicula .campo {
            margin-bottom: 1.2rem;
            text-align: left;
        }

        #pagina-crear-pelicula .campo.full-ancho {
            grid-column: 1 / -1;
        }

        #pagina-crear-pelicula .campo label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #a0aec0;
            font-size: 14px;
        }

        #pagina-crear-pelicula .campo label.requerido::after {
            content: ' *';
            color: #ed850f;
        }

        #pagina-crear-pelicula .campo input,
        #pagina-crear-pelicula .campo select,
        #pagina-crear-pelicula .campo textarea {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #4a5568;
            border-radius: 10px;
            background: #2d3748;
            color: #e2e8f0;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        #pagina-crear-pelicula .campo textarea {
            resize: vertical;
            min-height: 100px;
        }

        #pagina-crear-pelicula .campo input::placeholder,
        #pagina-crear-pelicula .campo textarea::placeholder {
            color: #6b7280;
        }

        #pagina-crear-pelicula .campo input:focus,
        #pagina-crear-pelicula .campo select:focus,
        #pagina-crear-pelicula .campo textarea:focus {
            outline: none;
            border-color: #ed850f;
            background: #363130;
            box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
        }

        #pagina-crear-pelicula .campo select option {
            background: #2d3748;
            color: #e2e8f0;
        }

        #pagina-crear-pelicula .campo select[multiple] {
            padding: 8px;
        }

        #pagina-crear-pelicula .campo select[multiple] option {
            padding: 8px 10px;
            border-radius: 4px;
        }

        #pagina-crear-pelicula .campo input.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        #pagina-crear-pelicula .campo input[type="file"] {
            padding: 10px 14px;
            cursor: pointer;
        }

        #pagina-crear-pelicula .campo .ayuda {
            font-size: 0.8rem !important;
            color: #a0aec0;
            margin-top: 6px;
            line-height: 1.5;
        }

        #pagina-crear-pelicula .campo .ayuda strong {
            color: #ed850f;
        }

        .preview-trailer {
            margin-top: 1rem;
            padding: 1rem;
            background: #1a202c;
            border: 1px solid #4a5568;
            border-radius: 10px;
        }

        .preview-trailer p {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .preview-trailer iframe {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            max-width: 100%;
        }

        .preview-imagen {
            margin-top: 1rem;
            text-align: center;
        }

        .preview-imagen img {
            max-width: 200px;
            max-height: 300px;
            border-radius: 8px;
            border: 1px solid #4a5568;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        #pagina-crear-pelicula .boton {
            grid-column: 1 / -1;
            width: 100%;
            padding: 13px 15px;
            background: linear-gradient(135deg, #ed850f, #f7931e);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 18px rgba(237, 133, 15, 0.35);
            margin-top: 10px;
        }

        #pagina-crear-pelicula .boton:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
        }

        #pagina-crear-pelicula .contenedor-alertas {
            grid-column: 1 / -1;
        }

        #pagina-crear-pelicula .acciones {
            grid-column: 1 / -1;
            margin-top: 20px;
            text-align: center;
        }

        #pagina-crear-pelicula .boton-secundario {
            display: inline-block;
            color: #a0aec0;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid #4a5568;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        #pagina-crear-pelicula .boton-secundario:hover {
            border-color: #ed850f;
            color: #ed850f;
            background: rgba(237, 133, 15, 0.08);
        }

        @media (max-width: 600px) {
            #pagina-crear-pelicula.form-container {
                padding: 1.5rem;
                margin: 20px auto;
                border-radius: 16px;
            }

            #pagina-crear-pelicula h2 {
                font-size: 1.3rem;
            }

            #pagina-crear-pelicula form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>

<script type="module" src="/assets/js/formularios.js"></script>
<script>
    function extraerVideoIdYoutube(url) {
        const patrones = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/v\/([^&\n?#]+)/
        ];

        for (const patron of patrones) {
            const match = url.match(patron);
            if (match) return match[1];
        }
        return null;
    }

    document.getElementById('trailer_url').addEventListener('input', function() {
        const url = this.value.trim();
        const preview = document.getElementById('preview-trailer');
        const iframe = document.getElementById('iframe-preview');

        if (url === '') {
            preview.style.display = 'none';
            this.classList.remove('error');
            return;
        }

        const videoId = extraerVideoIdYoutube(url);

        if (videoId) {
            iframe.src = `https://www.youtube.com/embed/${videoId}`;
            preview.style.display = 'block';
            this.classList.remove('error');
        } else {
            preview.style.display = 'none';
            this.classList.add('error');
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const trailerUrl = document.getElementById('trailer_url').value.trim();

        if (trailerUrl !== '' && !extraerVideoIdYoutube(trailerUrl)) {
            e.preventDefault();
            window.mostrarAlerta('URL de YouTube no válida. Usá el formato: https://www.youtube.com/watch?v=VIDEO_ID', 'error');
            document.getElementById('trailer_url').focus();
            return false;
        }
    });

    // Vista previa de la imagen seleccionada
    document.getElementById('imagen_pelicula')?.addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('preview-imagen');
        const img = document.getElementById('img-preview');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
</script>