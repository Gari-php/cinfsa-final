<body>
    <div class="listado">
            <div>
                <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
            </div>

        <h1>Lista de Cantinas</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i>Volver</a></li>
                <li><a href="/administrador/cantina/crear"><i class="fa-solid fa-store"></i> Agregar Cantina</a></li>
                <li><a href="/administrador/cantina/reportes"><i class="fa-solid fa-book"></i> Reportes de Cantina</a></li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Cantina</th>
                    <th>Cajas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-cantinas">
            <?php foreach($cantinas as $c): ?>
                <tr>
                    <td><?php echo $c->id_cantina; ?></td>
                    <td><?php echo s($c->nombre_cantina); ?></td>
                    <td><?php echo $c->cajas ? s($c->cajas) : 'Sin cajas'; ?></td>
                    <td>
                        <?php echo $c->estado == 1
                            ? '<span class="estado-con-icono activo">ACTIVA</span>'
                            : '<span class="estado-con-icono inactivo">INACTIVA</span>'; ?>
                    </td>
                    <td>
                        <div class="acciones">
                            <a class="boton" href="/administrador/cantina/contenido?id=<?php echo $c->id_cantina; ?>">Ver Contenido</a>
                            <a class="boton" href="/administrador/cantina/editar?id=<?php echo $c->id_cantina; ?>">Editar</a>
                            <?php if ($c->estado == 1): ?>
                                <button class="boton eliminar-cantina"
                                    data-id="<?php echo $c->id_cantina; ?>"
                                    data-nombre="<?php echo s($c->nombre_cantina); ?>">
                                    Dar de baja
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="alerta-accion" class="form-container"></div>
        <script type="module" src="/assets/js/formularios.js"></script>
    </div>
</body>    