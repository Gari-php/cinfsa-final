<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo" class="form-logo">
        </div>
        <h1>Lista de Sexos</h1>
        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/sexo/crear"><i class="fa-solid fa-plus"></i> Agregar Sexo</a></li>
                <li class="exportacion">
                </li>
            </ul>
        </nav>

        <table class="tabla-listado" >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-sexo">
                <?php foreach($sexos as $s): ?>
                <tr>
                    <td><?= $s->id_sexo ?></td>
                    <td><?= htmlspecialchars($s->nombre_sexo) ?></td>
                    <td>
                        <?php echo $s->estado == 1 
                            ? '<span class="estado-con-icono activo">Activo</span>' 
                            : '<span class="estado-con-icono inactivo">Inactivo</span>'; ?>
                    </td>
                    <td class="acciones">
                        <button class="boton eliminar-sexo"
                                data-id="<?php echo $s->id_sexo; ?>"
                                data-nombre="<?php echo htmlspecialchars($s->nombre_sexo); ?>">
                            Eliminar
                        </button>
                       <a class="boton" href="/administrador/sexo/editar?id=<?= $s->id_sexo ?>">Editar</a>
                    </td>
                   
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php 
        if (isset($paginador)) {
            echo $paginador->render('/administrador/sexo/listado');
        }
        ?>
    </div>    
    <?php 
    use Classes\Paginador;
    echo Paginador::renderCSS(); 
    ?>
</body>