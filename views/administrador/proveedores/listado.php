<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo" class="form-logo">
        </div>
        <h1>Lista de Proveedores</h1>
        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/proveedores/crear"><i class="fa-solid fa-plus"></i> Agregar Proveedor</a></li>
                <li class="buscador">
                    <input id="busqueda-proveedor" type="text" placeholder="Buscar por Razón Social, RUT...">
                    <button id="btn-buscar-proveedor" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Razón Social</th>
                    <th>Nombre Comercial</th>
                    <th>RUT/CUIT</th>
                    <th>Tipo</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($proveedores)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #a0aec0;">
                            <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                            No hay proveedores registrados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($proveedores as $p): ?>
                        <tr>
                            <td><?= $p->id_proveedor ?></td>
                            <td><?= htmlspecialchars($p->razon_social) ?></td>
                            <td><?= s($p->nombre_comercial) ?></td>
                            <td style="font-family: monospace;"><?= $p->rut ?></td>
                            <td>
                                <?php
                                $tipos = [
                                    'peliculas' => '<span style="background: #ed850f; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">PELÍCULAS</span>',
                                    'servicios' => '<span style="background: #3b82f6; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">SERVICIOS</span>',
                                    'productos' => '<span style="background: #22c55e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">PRODUCTOS</span>',
                                    'otros' => '<span style="background: #6c757d; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">OTROS</span>'
                                ];
                                echo $tipos[$p->tipo_proveedor] ?? $tipos['otros'];
                                ?>
                            </td>
                            <td><?= $p->telefono ?: '-' ?></td>
                            <td style="font-size: 11px;"><?= $p->email ?: '-' ?></td>
                            <td>
                                <?php if ($p->activo == 1): ?>
                                    <span style="color: #22c55e;">✓ Activo</span>
                                <?php else: ?>
                                    <span style="color: #ef4444;">✗ Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="boton eliminar-proveedor"
                                        data-id="<?= $p->id_proveedor ?>"
                                        data-nombre="<?= htmlspecialchars($p->razon_social) ?>">
                                        Dar de Baja
                                    </button>
                                    <a class="boton" href="/administrador/proveedores/editar?id=<?= $p->id_proveedor ?>">Editar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <style>
                #busqueda-proveedor {
                    background: #1a202c !important;
                    color: #fff !important;
                    border: 1px solid #4a5568;
                    border-radius: 4px;
                    padding: 6px 10px;
                }

                #busqueda-proveedor::placeholder {
                    color: #a0aec0;
                }

                #busqueda-proveedor:focus {
                    outline: none;
                    border-color: #ed850f;
                    box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.15);
                }
            </style>
        </table>


        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/proveedores/listado');
        }
        ?>
    </div>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('busqueda-proveedor');
            const btnBuscar = document.getElementById('btn-buscar-proveedor');
            const tbody = document.querySelector('.tabla-listado tbody');

            const tiposBadge = {
                'peliculas': '<span style="background: #ed850f; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">PELÍCULAS</span>',
                'servicios': '<span style="background: #3b82f6; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">SERVICIOS</span>',
                'productos': '<span style="background: #22c55e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">PRODUCTOS</span>',
                'otros': '<span style="background: #6c757d; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px;">OTROS</span>'
            };

            function renderFila(p) {
                const tipoHtml = tiposBadge[p.tipo_proveedor] || tiposBadge['otros'];
                const estadoHtml = p.activo == 1 ?
                    '<span style="color: #22c55e;">✓ Activo</span>' :
                    '<span style="color: #ef4444;">✗ Inactivo</span>';

                return `
            <tr>
                <td>${p.id_proveedor}</td>
                <td>${p.razon_social}</td>
                <td>${p.nombre_comercial}</td>
                <td style="font-family: monospace;">${p.rut}</td>
                <td>${tipoHtml}</td>
                <td>${p.telefono || '-'}</td>
                <td style="font-size: 11px;">${p.email || '-'}</td>
                <td>${estadoHtml}</td>
                <td>
                    <div class="acciones">
                        <button class="boton eliminar-proveedor" data-id="${p.id_proveedor}" data-nombre="${p.razon_social}">Dar de Baja</button>
                        <a class="boton" href="/administrador/proveedores/editar?id=${p.id_proveedor}">Editar</a>
                    </div>
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
                    const response = await fetch('/administrador/proveedores/buscar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            termino
                        })
                    });

                    const data = await response.json();

                    if (data.ok && data.proveedores.length > 0) {
                        tbody.innerHTML = data.proveedores.map(renderFila).join('');
                    } else {
                        tbody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 40px; color:#a0aec0;">
                            No se encontraron proveedores
                        </td>
                    </tr>
                `;
                    }
                } catch (error) {
                    console.error('Error al buscar proveedores:', error);
                    alert('Error al buscar proveedores');
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