<!-- HTML SALA DE JUEGOS LIMPIO -->
<div class="sala-juegos-container">
 

    <?php if (!empty($maquinasDisponibles)): ?>
    <div class="maquinas-grid">
        <?php foreach ($maquinasDisponibles as $maquina): ?>
        <div class="maquina-card" data-maquina-id="<?php echo $maquina->id_maquinas; ?>">
            <!-- Imagen de la máquina -->
            <div class="card-imagen">
                <?php if (!empty($maquina->imagen_maquina)): ?>
                    <img src="../../assets/img/cantina/<?php echo htmlspecialchars($maquina->imagen_maquina); ?>"
                         alt="<?php echo htmlspecialchars($maquina->maquinas_nombre); ?>">
                <?php else: ?>
                    <div class="imagen-placeholder">
                        <i class="fas fa-gamepad"></i>
                    </div>
                <?php endif; ?>
                
                <!-- SOLO OVERLAY HOVER -->
                <div class="maquina-overlay">
                    <div class="agregar-carrito-btn" 
                         onclick="agregarMaquinaAlCarrito(this)"
                         data-id="<?php echo $maquina->id_maquinas; ?>"
                         data-nombre="<?php echo htmlspecialchars($maquina->maquinas_nombre); ?>"
                         data-precio="<?php echo $maquina->precio_ficha ?: 2000; ?>"
                         data-imagen="../../assets/img/cantina/<?php echo htmlspecialchars($maquina->imagen_maquina); ?>"
                         data-stock="<?php echo $stockGlobalFichas ?? 500; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Agregar Fichas</span>
                    </div>
                </div>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="card-content">
                <!-- Nombre de la máquina -->
                <h3 class="maquina-nombre">
                    <?php echo htmlspecialchars($maquina->maquinas_nombre); ?>
                </h3>

                <!-- Descripción -->
                <p class="maquina-descripcion">
                    <?php echo htmlspecialchars($maquina->maquina_descripcion); ?>
                </p>

                
                <div class="info-fichas">
                    <div class="precio-ficha">
                        💰 $<?php echo number_format($maquina->precio_ficha ?: 2000, 0, ',', '.'); ?> por ficha
                    </div>
                    <div class="stock-info">
                        📦 Stock: <span id="stock-display-<?php echo $maquina->id_maquinas; ?>"><?php echo $maquina->cantidad_ficha ?? 0; ?></span> fichas
                    </div>
                </div>

                <!-- Selector de cantidad -->
                <div class="cantidad-controls">
                    <button class="btn-cantidad btn-menos" 
                            data-id="<?php echo $maquina->id_maquinas; ?>"
                            onclick="cambiarCantidadMaquina(this, -1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="cantidad-display" id="cantidad-<?php echo $maquina->id_maquinas; ?>">1</span>
                    <button class="btn-cantidad btn-mas" 
                            data-id="<?php echo $maquina->id_maquinas; ?>"
                            onclick="cambiarCantidadMaquina(this, 1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($maquinasDisponibles)): ?>
    <div class="no-maquinas">
        <h2>🎮 No hay máquinas disponibles</h2>
        <p>Próximamente tendremos nuevas máquinas para ti</p>
    </div>
    <?php endif; ?>
</div>

