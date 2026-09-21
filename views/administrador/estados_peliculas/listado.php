<body>
    <div>
        <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
    </div>

    <h1>Lista de Estados de Películas</h1>

    <nav>
        <ul>
            <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
            <li><a href="/administrador/estados_peliculas/crear"><i class="fa-solid fa-film"></i> Agregar Estado de Película</a></li>
            <li><a href="#"><i class="fa-solid fa-book"></i> Reportes de Estados de Películas</a></li>
            <li class="buscador" style="width:300px;">
                <input id="busqueda-estado" class="barra_buscador" type="text" placeholder="Buscar por ID o nombre" style="width: 250px; height: 30px;">
                <button id="btn-buscar-estado" type="button">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </li>
        </ul>
    </nav>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre del Estado</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-estados-peliculas">
        <?php foreach ($estados_peliculas as $estado_pelicula) { ?>
            <tr>
                <td><?php echo htmlspecialchars($estado_pelicula->id_estado_pelicula); ?></td>
                <td><?php echo htmlspecialchars($estado_pelicula->nombre_estado_pelicula); ?></td>
                <td><?php echo $estado_pelicula->estado == 1 ? 'Activo' : 'Baja'; ?></td>
                <td>
                    <div class="acciones">
                    <button class="boton eliminar-estado-pelicula"
                            data-id="<?php echo $estado_pelicula->id_estado_pelicula; ?>"
                            data-nombre="<?php echo htmlspecialchars($estado_pelicula->nombre_estado_pelicula); ?>">
                        Eliminar
                    </button>
                    <a class="boton" href="/administrador/estados_peliculas/editar?id=<?php echo $estado_pelicula->id_estado_pelicula; ?>">Editar</a>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>