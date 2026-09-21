<div class="listado" data-modulo="peliculas">
    <div>
        <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
    </div>

    <h1>Lista de Películas</h1>

    <nav class="nav-listado">
        <ul>
            <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
            <li><a href="/administrador/peliculas/crear"><i class="fa-solid fa-file-video"></i> Agregar Película</a></li>
            <li class="filtros-exportacion">
            <li class="filtros-exportacion">
                <div class="grupo-filtros-fecha">
                    <span><i class="fa-solid fa-calendar-plus"></i> Filtrar por Fecha de Registro:</span>
                    <input type="date"
                        id="fecha-desde-export"
                        placeholder="Desde"
                        value="<?php echo htmlspecialchars($fecha_desde ?? ''); ?>"
                        style="padding: 6px 10px; border: 1px solid #4a5568; border-radius: 4px; background: #1a202c; color: #fff;">
                    <span style="color: #a0aec0;">hasta</span>
                    <input type="date"
                        id="fecha-hasta-export"
                        placeholder="Hasta"
                        value="<?php echo htmlspecialchars($fecha_hasta ?? ''); ?>"
                        style="padding: 6px 10px; border: 1px solid #4a5568; border-radius: 4px; background: #1a202c; color: #fff;">
                    <button id="btn-buscar-fecha" type="button" style="background: #ed850f; color: #fff; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                    <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                        <a href="/administrador/peliculas/listado"
                            style="background: #6c757d; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </li>
            </li>

            <li class="exportacion">
                <div class="grupo-exportacion">
                    <span><i class="fa-solid fa-download"></i> Exportar:</span>
                    <button class="btn-exportar excel" data-tipo="excel" title="Exportar a Excel">
                        <i class="fa-solid fa-file-excel"></i> Excel
                    </button>
                    <button class="btn-exportar pdf" data-tipo="pdf" title="Exportar a PDF">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </div>
            </li>
            <li class="buscador">
                <input id="busqueda-pelicula" class="barra_buscador" type="text" placeholder="Buscar por nombre">
                <button id="btn-buscar-pelicula" type="button">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </li>
        </ul>
    </nav>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Imagen</th>
                <th>Sinopsis</th>
                <th>Año</th>
                <th>Duración</th>
                <th>Clasificación</th>
                <th>Idioma</th>
                <th>Géneros</th>
                <th>Estado</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-peliculas">
            <?php if (empty($peliculas)): ?>
                <tr>
                    <td colspan="12" style="text-align: center; padding: 40px; color: #a0aec0;">
                        <i class="fa-solid fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                        No hay películas registradas
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($peliculas as $p): ?>
                    <tr>
                        <td><?php echo $p->id_pelicula; ?></td>
                        <td><?php echo htmlspecialchars($p->titulo_pelicula); ?></td>
                        <td>
                            <?php if (!empty($p->imagen_pelicula)): ?>
                                <img src="/assets/img/peliculas/<?php echo htmlspecialchars($p->imagen_pelicula); ?>"
                                    alt="Poster de <?php echo htmlspecialchars($p->titulo_pelicula); ?>"
                                    class="imagen-pelicula">
                            <?php else: ?>
                                <span>Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td class="sinopsis-celda"><?php echo htmlspecialchars($p->sinopsis_pelicula); ?></td>
                        <td><?php echo $p->anyo_pelicula; ?></td>
                        <td><?php echo $p->duracion_pelicula; ?> min</td>
                        <td><?php echo htmlspecialchars($p->nombre_tipo_clasificacion); ?></td>
                        <td><?php echo htmlspecialchars($p->nombre_idioma_pelicula); ?></td>
                        <td><?php echo htmlspecialchars($p->generos ?? ''); ?></td>
                        <td>
                            <?php
                            switch ($p->nombre_estado_pelicula) {
                                case 'Emision':
                                    echo '<span class="estado-con-icono activo">Emisión</span>';
                                    break;
                                case 'Proximamente':
                                    echo '<span class="estado-con-icono neutral">Próximamente</span>';
                                    break;
                                case 'Finalizada':
                                    echo '<span class="estado-con-icono inactivo">Finalizada</span>';
                                    break;
                                default:
                                    echo '<span class="estado-desconocido">Sin estado</span>';
                                    break;
                            }
                            ?>
                        </td>
                        <td style="font-size: 11px !important; color: #a0aec0;">
                            <?php
                            if (isset($p->creado)) {
                                echo date('d/m/Y H:i', strtotime($p->creado));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="acciones">
                            <button class="boton eliminar-pelicula"
                                data-id="<?php echo $p->id_pelicula; ?>"
                                data-nombre="<?php echo htmlspecialchars($p->titulo_pelicula); ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/peliculas/editar?id=<?php echo $p->id_pelicula; ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PAGINADOR -->
    <?php
    if (isset($paginador)) {
        echo $paginador->render('/administrador/peliculas/listado');
    }
    ?>
</div>

<div id="alerta-accion" class="form-container"></div>

<script type="module" src="/assets/js/formularios.js"></script>
<script src="/assets/js/busqueda.js"></script>
<script>
    document.getElementById('btn-buscar-fecha')?.addEventListener('click', () => {
        const desde = document.getElementById('fecha-desde-export').value;
        const hasta = document.getElementById('fecha-hasta-export').value;

        const params = new URLSearchParams();
        if (desde) params.set('fecha_desde', desde);
        if (hasta) params.set('fecha_hasta', hasta);

        window.location.href = '/administrador/peliculas/listado?' + params.toString();
    });
</script>


<?php

use Classes\Paginador;

echo Paginador::renderCSS();
?>
