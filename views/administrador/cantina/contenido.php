<div class="cantina-contenido-container" id="pagina-contenido-cantina">
    <div class="header-contenido-cantina">
        <img src="../../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        <h1><i class="fa-solid fa-store"></i> Contenido de Cantina: <?= htmlspecialchars($cantina->nombre_cantina) ?></h1>

        <a href="/administrador/cantina/listado" class="btn-volver-cantina">
            <i class="fa-solid fa-hand-point-left"></i> Volver a Cantinas
        </a>
    </div>

    <!-- SECCIÓN PRODUCTOS -->
    <section class="seccion-cantina">
        <h2><i class="fa-solid fa-cookie-bite"></i> Productos Disponibles</h2>

        <?php if (empty($productos)): ?>
            <div class="sin-datos-cantina">
                <i class="fa-solid fa-box-open"></i>
                <p>No hay productos disponibles en esta cantina.</p>
            </div>
        <?php else: ?>
            <div class="tabla-wrapper-cantina">
                <table class="tabla-cantina">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Stock Disponible</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td>#<?= $producto['id_producto_cantina'] ?></td>
                                <td><?= htmlspecialchars($producto['nombre_producto_cantina']) ?></td>
                                <td class="precio-cantina">$<?= number_format($producto['precio_producto'], 0, ',', '.') ?></td>
                                <td><?= $producto['stock_cantina'] ?> unidades</td>
                                <td><span class="badge-estado-cantina"><?= htmlspecialchars($producto['nombre_estado_producto']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECCIÓN MÁQUINAS/FICHAS -->
    <section class="seccion-cantina">
        <h2><i class="fa-solid fa-gamepad"></i> Máquinas y Fichas Disponibles</h2>

        <?php if (empty($maquinas)): ?>
            <div class="sin-datos-cantina">
                <i class="fa-solid fa-gamepad"></i>
                <p>No hay máquinas con fichas disponibles en esta cantina.</p>
            </div>
        <?php else: ?>
            <div class="maquinas-grid-cantina">
                <?php foreach ($maquinas as $maquina): ?>
                    <div class="maquina-card-cantina">
                        <?php if (!empty($maquina['imagen_maquina'])): ?>
                            <img src="/assets/img/cantina/<?= $maquina['imagen_maquina'] ?>"
                                alt="<?= htmlspecialchars($maquina['maquinas_nombre']) ?>"
                                class="maquina-imagen-cantina">
                        <?php else: ?>
                            <div class="maquina-imagen-cantina sin-imagen-cantina">
                                <i class="fa-solid fa-gamepad"></i>
                            </div>
                        <?php endif; ?>

                        <div class="maquina-info-cantina">
                            <h3><?= htmlspecialchars($maquina['maquinas_nombre']) ?></h3>
                            <p class="maquina-desc-cantina"><?= htmlspecialchars(substr($maquina['maquina_descripcion'], 0, 100)) ?>...</p>

                            <div class="ficha-info-cantina">
                                <div class="ficha-dato">
                                    <span>Precio por Ficha</span>
                                    <strong class="precio-cantina">$<?= number_format($maquina['precio_ficha'], 0, ',', '.') ?></strong>
                                </div>
                                <div class="ficha-dato">
                                    <span>Fichas Disponibles</span>
                                    <strong><?= $maquina['cantidad_ficha'] ?></strong>
                                </div>
                                <div class="ficha-dato">
                                    <span>Estado</span>
                                    <span class="badge-estado-maquina <?= $maquina['estado'] == 1 ? 'activa' : 'inactiva' ?>">
                                        <?= $maquina['estado'] == 1 ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>