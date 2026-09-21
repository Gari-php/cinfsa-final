<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>

        <h1>Lista de Máquinas</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i>Volver</a></li>
                <li><a href="/administrador/maquinas/crear"><i class="fa-solid fa-desktop"></i> Agregar Máquina</a></li>
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
                    <input id="busqueda-maquina" class="barra_buscador" type="text" placeholder="Buscar por ID o Nombre">
                    <button id="btn-buscar-maquina" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Imagen</th>
                    <th>Tipo de Ficha</th>
                    <th>Precio Ficha</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-maquinas">
                <?php foreach ($maquinas as $m): ?>
                    <tr>
                        <td><?php echo $m->id_maquinas; ?></td>
                        <td><?php echo $m->maquinas_nombre; ?></td>
                        <td class="descripcion-celda"><?php echo substr($m->maquina_descripcion, 0, 50) . '...'; ?></td>
                        <td>
                            <?php if ($m->imagen_maquina): ?>
                                <img src="/assets/img/cantina/<?php echo $m->imagen_maquina; ?>"
                                    alt="Imagen máquina"
                                    class="imagen-maquina">
                            <?php else: ?>
                                <span>Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td>Ficha Universal</td>
                        <td class="precio-celda">$<?php echo number_format($m->precio_ficha ?: 0, 0, ',', '.'); ?></td>
                        <td class="stock-celda"><?php echo $m->cantidad_ficha ?: 0; ?> fichas</td>
                        <td>
                            <?php echo $m->estado == 1
                                ? '<span class="estado-con-icono activo">ACTIVA</span>'
                                : '<span class="estado-con-icono inactivo">INACTIVA</span>'; ?>
                        </td>
                        <td class="acciones">
                            <button class="boton eliminar-maquina"
                                data-id="<?php echo $m->id_maquinas; ?>"
                                data-nombre="<?php echo htmlspecialchars($m->maquinas_nombre); ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/maquinas/editar?id=<?php echo $m->id_maquinas; ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/maquinas/listado');
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
            const input = document.getElementById('busqueda-maquina');
            const btnBuscar = document.getElementById('btn-buscar-maquina');
            const tbody = document.getElementById('tabla-maquinas');

            function renderFila(m) {
                const descripcionCorta = (m.maquina_descripcion || '').substring(0, 50) + '...';
                const imagenHtml = m.imagen_maquina ?
                    `<img src="/assets/img/cantina/${m.imagen_maquina}" alt="Imagen máquina" class="imagen-maquina">` :
                    `<span>Sin imagen</span>`;
                const estadoHtml = m.estado == 1 ?
                    '<span class="estado-con-icono activo">ACTIVA</span>' :
                    '<span class="estado-con-icono inactivo">INACTIVA</span>';

                return `
            <tr>
                <td>${m.id_maquinas}</td>
                <td>${m.maquinas_nombre}</td>
                <td class="descripcion-celda">${descripcionCorta}</td>
                <td>${imagenHtml}</td>
                <td>Ficha Universal</td>
                <td class="precio-celda">$${Number(m.precio_ficha || 0).toLocaleString('es-AR')}</td>
                <td class="stock-celda">${m.cantidad_ficha || 0} fichas</td>
                <td>${estadoHtml}</td>
                <td class="acciones">
                    <button class="boton eliminar-maquina" data-id="${m.id_maquinas}" data-nombre="${m.maquinas_nombre}">Eliminar</button>
                    <a class="boton" href="/administrador/maquinas/editar?id=${m.id_maquinas}">Editar</a>
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
                    const response = await fetch('/administrador/maquinas/buscar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            termino
                        })
                    });

                    const data = await response.json();

                    if (data.ok && data.maquinas.length > 0) {
                        tbody.innerHTML = data.maquinas.map(renderFila).join('');
                    } else {
                        tbody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 40px; color:#a0aec0;">
                            No se encontraron máquinas
                        </td>
                    </tr>
                `;
                    }
                } catch (error) {
                    console.error('Error al buscar máquinas:', error);
                    alert('Error al buscar máquinas');
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