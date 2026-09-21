<body>
    <div class="listado" data-modulo="usuarios">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>

        <h1>Lista de Usuarios</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/usuarios/crear"><i class="fa-solid fa-user-plus"></i> Agregar Usuario</a></li>
                <li><a href="/administrador/usuarios/reportes"><i class="fa-solid fa-book"></i> Reportes de Usuario</a></li>
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
                <li class="buscador">
                    <input id="busqueda-usuario" class="barra_buscador" type="text" placeholder="Buscar email o usuario">
                    <button id="btn-buscar-usuario" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID Usuario</th>
                    <th>Nombre de Usuario</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>E-mail</th>
                    <th>Sexo</th>
                    <th>Perfil</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-usuarios">
            <?php foreach ($usuarios as $fila) { ?>
            <tr>
                <td><?php echo $fila['id_usuario']; ?></td>
                <td><?php echo $fila['nombre_usuario']; ?></td>
                <td><?php echo $fila['nombre_persona']; ?></td>
                <td><?php echo $fila['apellido_persona']; ?></td>
                <td><?php echo $fila['email']; ?></td>
                <td><?php echo $fila['nombre_sexo']; ?></td>
                <td><?php echo $fila['nombre_perfil']; ?></td>
                <td>
                    <?php echo $fila['estado'] == 1 
                          ? '<span class="estado-con-icono activo">ACTIVO</span>' 
                          : '<span class="estado-con-icono inactivo">INACTIVO</span>'; ?>
                </td>
                <td class="acciones">
                    <button class="boton eliminar-usuario"
                            data-id="<?php echo $fila['id_usuario']; ?>"
                            data-nombre="<?php echo $fila['nombre_usuario']; ?>">
                        Eliminar
                    </button>
                    <a class="boton" href="/administrador/usuarios/editar?id=<?php echo $fila['id_usuario']; ?>">Editar</a>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php 
        if (isset($paginador)) {
            echo $paginador->render('/administrador/usuarios/listado');
        }
        ?>
    </div>
    <?php 
    use Classes\Paginador;
    echo Paginador::renderCSS(); 
    ?>
    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>
    <script src="/assets/js/busqueda.js"></script>
</body>