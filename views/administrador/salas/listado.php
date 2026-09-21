<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>
    <h1>Lista de Salas</h1>
    <nav class="nav-listado">
        <ul>
            <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i>Volver</a></li>
            <li><a href="/administrador/salas/crear"><i class="fa-solid fa-masks-theater"></i> Agregar Salas</a></li>
            <li><a href="/administrador/salas/reportes"><i class="fa-solid fa-book"></i> Reportes de Salas</a></li>
            <li class="exportacion">
                    <div class="grupo-exportacion">
                        <span><i class="fa-solid fa-download"></i> Exportar:</span>
                        <button class="btn-exportar excel" data-tipo="excel">
                            <i class="fa-solid fa-file-excel"></i> Excel
                        </button>
                        <button class="btn-exportar pdf" data-tipo="pdf">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
            </li>
            <li class="buscador" style="width:300px;">
                <input id="busqueda-sala" class="barra_buscador" type="text" placeholder="Buscar por ID o Capacidad" style="width: 250px; height: 30px;">
                <button id="btn-buscar-sala" type="button">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </li>
        </ul>
    </nav>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>ID</th>
                <th>Capacidad</th>
                <th>Filas</th>
                <th>Columnas</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-salas">
        <?php foreach($salas as $s): ?>
            <tr>
                <td><?php echo $s->id_sala; ?></td>
                <td><?php echo $s->capacidad_sala; ?></td>
                <td><?php echo $s->filas_sala; ?></td>
                <td><?php echo $s->columnas_sala; ?></td>
                <td>
                        <?php echo $s->estado == 1 
                            ? '<span class="estado-con-icono activo">ACTIVA</span>' 
                            : '<span class="estado-con-icono inactivo">INACTIVA</span>'; ?>
                </td>
                <td>
                    <div class="acciones">
                        <button class="boton eliminar-sala"
                                data-id="<?php echo $s->id_sala; ?>"
                                data-nombre="<?php echo htmlspecialchars($s->capacidad_sala); ?>">
                            Eliminar
                        </button>
                        <a class="boton" href="/administrador/salas/editar?id=<?php echo $s->id_sala; ?>">Editar</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        </div>
    </table>

    <?php 
    if (isset($paginador)) {
        echo $paginador->render('/administrador/salas/listado');
    }
    ?>
    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>
    <?php 
    use Classes\Paginador;
    echo Paginador::renderCSS(); 
    ?>
</body>