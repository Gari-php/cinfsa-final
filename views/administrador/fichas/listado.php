<body>
    <div class="listado">  
        <div>
            <img src="../../assets/img/logo.png" alt="Logo" class="form-logo">
        </div>
        <h1>Lista de Fichas</h1>
        <nav class="nav-listado" >
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/fichas/crear"><i class="fa-solid fa-plus"></i> Agregar Ficha</a></li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($fichas as $f): ?>
                <tr>
                    <td><?= $f->id_fichas ?></td>
                    <td><?= $f->precio_ficha ?></td>
                    <td><?= $f->cantidad_ficha ?></td>
                    <td>
                        <div class="acciones">
                        <button class="boton eliminar-ficha"
                                data-id="<?php echo $f->id_fichas; ?>"
                                data-nombre="<?php echo htmlspecialchars($f->precio_ficha); ?>">
                            Eliminar
                        </button>
                        <a class="boton" href="/administrador/fichas/editar?id=<?= $f->id_fichas ?>">Editar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>  s