<body>
    <div class="listado" data-modulo="productos">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>

        <h1>Lista de Productos</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/productos/crear"><i class="fa-solid fa-film"></i> Agregar productos</a></li>
                <li><a href="/administrador/productos/reportes"><i class="fa-solid fa-book"></i> Reportes de Productos</a></li>
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
                    <input id="busqueda-productos" class="barra_buscador" type="text" placeholder="Buscar Producto">
                    <button id="btn-buscar-productos" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Codigo del Producto</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-productos">
                <?php foreach ($productos as $producto) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto->id_producto_cantina); ?></td>
                        <td>
                            <?php if ($producto->imagen_producto): ?>
                                <img src="/assets/img/productos/<?php echo htmlspecialchars($producto->imagen_producto); ?>"
                                    alt="<?php echo htmlspecialchars($producto->nombre_producto_cantina); ?>"
                                    class="imagen-producto">
                            <?php else: ?>
                                <div class="sin-imagen">Sin imagen</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($producto->nombre_producto_cantina); ?></td>
                        <td><?php echo htmlspecialchars($producto->codigo ?? 'N/A'); ?></td>
                        <td class="precio-celda">$<?php echo number_format($producto->precio_producto, 0, ',', '.'); ?></td>
                        <td>
                            <?php
                            switch ($producto->nombre_estado_producto) {
                                case 'Disponible':
                                    echo '<span class="estado-con-icono activo">Disponible</span>';
                                    break;
                                case 'Agotado':
                                    echo '<span class="estado-con-icono neutral">Agotado</span>';
                                    break;
                                case 'Suspendido':
                                    echo '<span class="estado-con-icono inactivo">Suspendido</span>';
                                    break;
                                default:
                                    echo '<span class="estado-desconocido">Sin estado</span>';
                                    break;
                            }
                            ?>
                        </td>
                        <td class="acciones">
                            <button class="boton eliminar-producto"
                                data-id="<?php echo $producto->id_producto_cantina; ?>"
                                data-nombre="<?php echo htmlspecialchars($producto->nombre_producto_cantina); ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/productos/editar?id=<?php echo $producto->id_producto_cantina; ?>">Editar</a>
                        </td>
                    </tr>
                <?php } ?>


            </tbody>
        </table>
        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/productos/listado');
        }
        ?>
    </div>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('busqueda-productos');
            const btnBuscar = document.getElementById('btn-buscar-productos');
            const tbody = document.getElementById('tabla-productos');

            function renderFila(p) {
                const estadoClases = {
                    'Disponible': 'activo',
                    'Agotado': 'neutral',
                    'Suspendido': 'inactivo'
                };
                const claseEstado = estadoClases[p.nombre_estado_producto] || null;
                const estadoHtml = claseEstado ?
                    `<span class="estado-con-icono ${claseEstado}">${p.nombre_estado_producto}</span>` :
                    `<span class="estado-desconocido">Sin estado</span>`;

                const imagenHtml = p.imagen_producto ?
                    `<img src="/assets/img/productos/${p.imagen_producto}" alt="${p.nombre_producto_cantina}" class="imagen-producto">` :
                    `<div class="sin-imagen">Sin imagen</div>`;

                return `
            <tr>
                <td>${p.id_producto_cantina}</td>
                <td>${imagenHtml}</td>
                <td>${p.nombre_producto_cantina}</td>
                <td>${p.codigo ?? 'N/A'}</td>
                <td class="precio-celda">$${Number(p.precio_producto).toLocaleString('es-AR')}</td>
                <td>${estadoHtml}</td>
                <td class="acciones">
                    <button class="boton eliminar-producto" data-id="${p.id_producto_cantina}" data-nombre="${p.nombre_producto_cantina}">Eliminar</button>
                    <a class="boton" href="/administrador/productos/editar?id=${p.id_producto_cantina}">Editar</a>
                </td>
            </tr>
        `;
            }

            async function buscar() {
                const termino = input.value.trim();

                if (!termino) {
                    location.reload();
                    return;
                }

                try {
                    const response = await fetch('/administrador/productos/buscar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            termino
                        })
                    });

                    const data = await response.json();

                    if (data.ok && data.productos.length > 0) {
                        tbody.innerHTML = data.productos.map(renderFila).join('');
                    } else {
                        tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 40px; color:#a0aec0;">
                            No se encontraron productos
                        </td>
                    </tr>
                `;
                    }
                } catch (error) {
                    console.error('Error al buscar productos:', error);
                    alert('Error al buscar productos');
                }
            }

            btnBuscar.addEventListener('click', buscar);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscar();
                }
            });
        });
    </script>


    <?php

    use Classes\Paginador;

    echo Paginador::renderCSS();
    ?>
</body>