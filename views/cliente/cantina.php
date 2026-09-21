<!-- HTML CANTINA CORREGIDO -->
<div class="cantina-container">
    <?php if (empty($productos)): ?>
    <div class="no-productos">
        <h2>🍿 No hay productos disponibles</h2>
        <p>Próximamente tendremos nuevos productos para ti</p>
    </div>
    <?php else: ?>
    <div class="productos-grid">
        <?php foreach ($productos as $producto): ?>
        <!-- AGREGADO: data-producto-id para identificación -->
        <div class="producto-card" data-producto-id="<?php echo $producto->id_producto_cantina; ?>">

            <div class="estado-producto">
                <span class="badge estado-<?php echo strtolower(str_replace(' ', '-', $producto->nombre_estado_producto ?? '')); ?>">
                    <?php if (($producto->nombre_estado_producto ?? '') === 'Disponible'): ?>
                        🟢 DISPONIBLE
                    <?php elseif (($producto->nombre_estado_producto ?? '') === 'Agotado'): ?>
                        🔴 AGOTADO
                    <?php else: ?>
                        📦 <?php echo strtoupper($producto->nombre_estado_producto ?? 'DISPONIBLE'); ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="imagen-container">
                <?php if (!empty($producto->imagen_producto)): ?>
                    <img src="/assets/img/productos/<?php echo htmlspecialchars($producto->imagen_producto); ?>" 
                         alt="<?php echo htmlspecialchars($producto->nombre_producto_cantina); ?>">
                <?php else: ?>
                    <div class="imagen-placeholder">
                        <i class="fas fa-utensils"></i>
                    </div>
                <?php endif; ?>
                
                <div class="producto-overlay">
                    <!-- MEJORADO: Ahora usa los data attributes y detecta la cantidad actual -->
                    <div class="agregar-carrito-btn" 
                         onclick="agregarProductoCantina(this)"
                         data-id="<?php echo $producto->id_producto_cantina; ?>"
                         data-nombre="<?php echo htmlspecialchars($producto->nombre_producto_cantina); ?>"
                         data-precio="<?php echo $producto->precio_producto; ?>"
                         data-imagen="/assets/img/productos/<?php echo htmlspecialchars($producto->imagen_producto); ?>"
                         <?php echo ($producto->rela_estado_producto == 2) ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                        <i class="fas fa-shopping-cart"></i>
                        <span>
                            <?php echo ($producto->rela_estado_producto == 2) ? 'Agotado' : 'Agregar'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <h3 class="nombre-producto">
                    <?php echo htmlspecialchars($producto->nombre_producto_cantina); ?>
                </h3>

                <div class="precio-container">
                    <div class="precio-principal">
                        $<?php echo number_format($producto->precio_producto, 0, ',', '.'); ?>
                    </div>
                    <div class="precio-info">
                        <span class="moneda">ARS</span>
                    </div>
                </div>

                <?php if ($producto->rela_estado_producto == 1): ?>
                <div class="cantidad-controls">
                    <!-- MEJORADO: Usando data-id y funciones específicas -->
                    <button class="btn-cantidad btn-menos" 
                            data-id="<?php echo $producto->id_producto_cantina; ?>"
                            onclick="cambiarCantidadProducto(this, -1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="cantidad-display" id="cantidad-<?php echo $producto->id_producto_cantina; ?>">1</span>
                    <button class="btn-cantidad btn-mas" 
                            data-id="<?php echo $producto->id_producto_cantina; ?>"
                            onclick="cambiarCantidadProducto(this, 1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script src="/assets/js/carrito.js"></script>
