<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo" class="form-logo">
        </div>
        <h1>Lista de Servicios</h1>
        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/servicios/crear"><i class="fa-solid fa-plus"></i> Agregar Servicio</a></li>
                <li class="buscador">
                    <input id="busqueda-servicio" type="text" placeholder="Buscar por Nombre, Código...">
                    <button id="btn-buscar-servicio" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Servicio</th>
                    <th>Código</th>
                    <th>Categoría</th>
                    <th>Monto Base</th>
                    <th>Variable</th>
                    <th>Frecuencia</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servicios)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px; color: #a0aec0;">
                            <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                            No hay servicios registrados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($servicios as $s): ?>
                        <tr>
                            <td><?= $s->id_servicio ?></td>
                            <td style="font-size: 11px;">
                                <strong><?= htmlspecialchars($s->razon_social) ?></strong><br>
                                <span style="color: #a0aec0;"><?= htmlspecialchars($s->nombre_comercial) ?></span>
                            </td>
                            <td><?= htmlspecialchars($s->nombre_servicio) ?></td>
                            <td style="font-family: monospace; font-size: 11px;"><?= $s->codigo_servicio ?: '-' ?></td>
                            <td>
                                <?php
                                $categorias = [
                                    'licencia_pelicula' => '<span style="background: #ed850f; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">LICENCIA</span>',
                                    'distribucion' => '<span style="background: #f59e0b; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">DISTRIBUCIÓN</span>',
                                    'agua' => '<span style="background: #3b82f6; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">AGUA</span>',
                                    'luz' => '<span style="background: #eab308; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">LUZ</span>',
                                    'gas' => '<span style="background: #ef4444; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">GAS</span>',
                                    'internet' => '<span style="background: #8b5cf6; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">INTERNET</span>',
                                    'telefonia' => '<span style="background: #06b6d4; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">TELEFONÍA</span>',
                                    'limpieza' => '<span style="background: #10b981; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">LIMPIEZA</span>',
                                    'mantenimiento' => '<span style="background: #f97316; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">MANTENIMIENTO</span>',
                                    'seguridad' => '<span style="background: #dc2626; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">SEGURIDAD</span>',
                                    'alquiler' => '<span style="background: #84cc16; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">ALQUILER</span>',
                                    'impuestos' => '<span style="background: #6366f1; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">IMPUESTOS</span>',
                                    'otros' => '<span style="background: #6c757d; color: white; padding: 3px 6px; border-radius: 3px; font-size: 9px;">OTROS</span>'
                                ];
                                echo $categorias[$s->categoria_servicio] ?? $categorias['otros'];
                                ?>
                            </td>
                            <td style="font-weight: bold;">$<?= number_format($s->monto_base, 2, ',', '.') ?></td>
                            <td style="text-align: center;">
                                <?php if ($s->tiene_monto_variable == 1): ?>
                                    <span style="color: #f59e0b;">✓ Sí</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 11px; text-transform: capitalize;"><?= str_replace('_', ' ', $s->frecuencia_pago) ?></td>
                            <td>
                                <?php if ($s->activo == 1): ?>
                                    <span style="color: #22c55e;">✓ Activo</span>
                                <?php else: ?>
                                    <span style="color: #ef4444;">✗ Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="boton eliminar-servicio"
                                        data-id="<?= $s->id_servicio ?>"
                                        data-nombre="<?= htmlspecialchars($s->nombre_servicio) ?>">
                                        Dar de Baja
                                    </button>
                                    <a class="boton" href="/administrador/servicios/editar?id=<?= $s->id_servicio ?>">Editar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <style>
                    #busqueda-servicio {
                        background: #1a202c !important;
                        color: #fff !important;
                        border: 1px solid #4a5568;
                        border-radius: 4px;
                        padding: 6px 10px;
                    }

                    #busqueda-servicio::placeholder {
                        color: #a0aec0;
                    }

                    #busqueda-servicio:focus {
                        outline: none;
                        border-color: #ed850f;
                        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.15);
                    }
                </style>
            </tbody>
        </table>


        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/servicios/listado');
        }
        ?>
    </div>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>


    <?php

    use Classes\Paginador;

    echo Paginador::renderCSS();
    ?>
</body>