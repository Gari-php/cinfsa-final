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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-cantinas">
            <?php foreach($cantinas as $c): ?>
                <tr>
                    <td><?php echo $c->id_cantina; ?></td>
                    <td><?php echo $c->nombre_cantina; ?></td>
                    <td>
                        <div class="acciones">
                            <a class="boton" href="/administrador/cantina/contenido?id=<?php echo $c->id_cantina; ?>">Ver Contenido</a>
                            <a class="boton" href="/administrador/cantina/editar?id=<?php echo $c->id_cantina; ?>">Editar</a>
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