<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>

        <h1>Lista de Stock de Cantina</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/stock/crear"><i class="fa-solid fa-box"></i> Agregar Stock</a></li>
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
                    <input id="busqueda-stock" class="barra_buscador" type="text" placeholder="Buscar por producto o cantina">
                    <button id="btn-buscar-stock" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Cantina</th>
                    <th>Stock Actual</th>
                    <th>Estado Producto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-stocks">
                <?php foreach ($stocks as $stock) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stock->id_stock_cantina); ?></td>
                        <td><?php echo htmlspecialchars($stock->nombre_producto_cantina); ?></td>
                        <td><?php echo htmlspecialchars($stock->nombre_cantina); ?></td>
                        <td class="stock-celda">
                            <span class="stock-cantidad <?php echo $stock->stock_cantina <= 10 ? ($stock->stock_cantina == 0 ? 'stock-agotado' : 'stock-bajo') : ''; ?>">
                                <?php echo htmlspecialchars($stock->stock_cantina); ?>
                                <?php if ($stock->stock_cantina == 0) echo ' (AGOTADO)'; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            switch ($stock->nombre_estado_producto) {
                                case 'Disponible':
                                    echo '<span class="estado-con-icono activo">Disponible</span>';
                                    break;
                                case 'Agotado':
                                    echo '<span class="estado-con-icono neutral">Agotado</span>';
                                    break;
                                default:
                                    echo '<span class="estado-con-icono inactivo">Inactivo</span>';
                                    break;
                            }
                            ?>
                        </td>
                        <td class="acciones">
                            <button class="boton eliminar-stock"
                                data-id="<?php echo $stock->id_stock_cantina; ?>"
                                data-nombre="<?php echo htmlspecialchars($stock->nombre_producto_cantina . ' - ' . $stock->nombre_cantina); ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/stock/editar?id=<?php echo $stock->id_stock_cantina; ?>">Editar</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/stock/listado');
        }
        ?>
    </div>
    <?php

    use Classes\Paginador;

    echo Paginador::renderCSS();
    ?>
    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('busqueda-stock');
            const btnBuscar = document.getElementById('btn-buscar-stock');
            const tbody = document.getElementById('tabla-stocks');

            function renderFila(s) {
                const estadoHtml = {
                    'Disponible': '<span class="estado-con-icono activo">Disponible</span>',
                    'Agotado': '<span class="estado-con-icono neutral">Agotado</span>'
                } [s.nombre_estado_producto] || '<span class="estado-con-icono inactivo">Inactivo</span>';

                const stock = parseInt(s.stock_cantina);
                let claseStock = '';
                let sufijo = '';
                if (stock <= 10) {
                    claseStock = stock === 0 ? 'stock-agotado' : 'stock-bajo';
                    if (stock === 0) sufijo = ' (AGOTADO)';
                }

                return `
            <tr>
                <td>${s.id_stock_cantina}</td>
                <td>${s.nombre_producto_cantina}</td>
                <td>${s.nombre_cantina}</td>
                <td class="stock-celda">
                    <span class="stock-cantidad ${claseStock}">${stock}${sufijo}</span>
                </td>
                <td>${estadoHtml}</td>
                <td class="acciones">
                    <button class="boton eliminar-stock" data-id="${s.id_stock_cantina}" data-nombre="${s.nombre_producto_cantina} - ${s.nombre_cantina}">Eliminar</button>
                    <a class="boton" href="/administrador/stock/editar?id=${s.id_stock_cantina}">Editar</a>
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
                    const response = await fetch('/administrador/stock/buscar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            termino
                        })
                    });

                    const data = await response.json();

                    if (data.ok && data.stocks.length > 0) {
                        tbody.innerHTML = data.stocks.map(renderFila).join('');
                    } else {
                        tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 40px; color:#a0aec0;">
                            No se encontró stock
                        </td>
                    </tr>
                `;
                    }
                } catch (error) {
                    console.error('Error al buscar stock:', error);
                    alert('Error al buscar stock');
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
</body>