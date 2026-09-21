<body>
    <div>
        <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
    </div>

<h1>Lista de Géneros de Películas</h1>

<nav>
    <ul>
        <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i>Volver</a></li>
        <li><a href="/administrador/generos/crear"><i class="fa-solid fa-film"></i> Agregar Género</a></li>
        <li><a href="#"><i class="fa-solid fa-book"></i> Reportes de Géneros</a></li>
        <li class="buscador" style="width:300px;">
            <input id="busqueda-genero" class="barra_buscador" type="text" placeholder="Buscar por ID o Género" style="width: 250px; height: 30px;">
            <button id="btn-buscar-genero" type="button">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </li>
    </ul>
</nav>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Género</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="tabla-generos">
    <?php foreach($generos as $g): ?>
        <tr>
            <td><?php echo $g->id_genero_pelicula; ?></td>
            <td><?php echo $g->genero_pelicula; ?></td>
            <td><?php echo $g->estado == 1 ? 'Activo' : 'Baja'; ?></td>
            <td>
                <div class="acciones">
                    <button class="boton eliminar-genero"
                            data-id="<?php echo $g->id_genero_pelicula; ?>"
                            data-nombre="<?php echo htmlspecialchars($g->genero_pelicula); ?>">
                        Eliminar
                    </button>
                    <a class="boton" href="/administrador/generos/editar?id=<?php echo $g->id_genero_pelicula; ?>">Editar</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div id="alerta-accion" class="form-container"></div>
<script type="module" src="/assets/js/formularios.js"></script>