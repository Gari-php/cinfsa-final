<body>
    <div class="listado" data-modulo="tipos_entradas">   
            <div>
                <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
            </div>
        <h1>Lista de Tipo Entradas</h1>
        <nav class="nav-listado" >
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i>Volver</a></li>
                <li><a href="/administrador/tipos_entradas/crear"><i class="fa-solid fa-clapperboard"></i> Agregar Tipo Entrada</a></li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descripcion</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-tipo_entradas">
            <?php foreach($tipos_entradas as $t): ?>
                <tr>
                    <td><?php echo $t->id_tipo_entrada; ?></td>
                    <td><?php echo $t->tipo_entrada_desc; ?></td>
                    <td><?php echo $t->precio_entrada; ?></td>
                    <td>
                        <?php echo $t->estado == 1 
                            ? '<span class="estado-con-icono activo">ACTIVA</span>' 
                            : '<span class="estado-con-icono inactivo">INACTIVA</span>'; ?>
                    </td>
                    <td>
                        <div class="acciones">
                            <button class="boton eliminar-tipoentrada"
                                    data-id="<?php echo $t->id_tipo_entrada; ?>"
                                    data-nombre="<?php echo htmlspecialchars($t->tipo_entrada_desc); ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/tipos_entradas/editar?id=<?php echo $t->id_tipo_entrada; ?>">Editar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
   
        <?php 
        if (isset($paginador)) {
            echo $paginador->render('/administrador/tipos_entradas/listado');
        }
        ?>
        <div id="alerta-accion" class="form-container"></div>
        <script type="module" src="/assets/js/formularios.js"></script> 
    </div> 
    <?php 
    use Classes\Paginador;
    echo Paginador::renderCSS(); 
    ?>
</body>     