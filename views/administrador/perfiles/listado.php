<body>
    <div class="listado">  
        <div>
            <img src="../../assets/img/logo.png" alt="Logo" class="form-logo">
        </div>
        <h1>Lista de Perfiles</h1>
        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/perfiles/crear"><i class="fa-solid fa-plus"></i> Agregar Perfil</a></li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Permiso</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($perfiles as $p): ?>
                <tr>
                    <td><?= $p->id_perfiles ?></td>
                    <td><?= $p->nombre_perfil ?></td>
                    <td><?= $p->permiso_perfil ?></td>
                    <td>
                        <?php if ($p->estado == 1): ?>
                            <span style="color: #22c55e;">✓ Activo</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">✗ Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acciones">
                            <button class="boton eliminar-perfil"
                                    data-id="<?= $p->id_perfiles ?>"
                                    data-nombre="<?= htmlspecialchars($p->nombre_perfil) ?>">
                                Dar de Baja
                            </button>
                            <a class="boton" href="/administrador/perfiles/editar?id=<?= $p->id_perfiles ?>">Editar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- PAGINADOR -->
        <?php 
        if (isset($paginador)) {
            echo $paginador->render('/administrador/perfiles/listado');
        }
        ?>
    </div>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>

    <!-- CSS DEL PAGINADOR -->
    <?php 
    use Classes\Paginador;
    echo Paginador::renderCSS(); 
    ?>
</body>