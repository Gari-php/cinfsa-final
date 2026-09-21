<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>

        <h1>Lista de Turnos</h1>

        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i> Volver</a></li>
                <li><a href="/administrador/turnos/crear"><i class="fa-solid fa-clock"></i> Agregar Turnos</a></li>
                <li><a href="/administrador/turnos/reportes"><i class="fa-solid fa-chart-bar"></i> Reportes de Turnos</a></li>
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
                    <input id="busqueda-turno" class="barra_buscador" type="text" placeholder="Buscar por ID o horario">
                    <button id="btn-buscar-turno" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Horario</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-turnos">
                <?php foreach ($turnos as $turno) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($turno->id_turnos); ?></td>
                        <td><?php echo htmlspecialchars($turno->turno_horario); ?></td>
                        <td>
                            <?php echo $turno->estado == 1
                                ? '<span class="estado-con-icono activo">Activo</span>'
                                : '<span class="estado-con-icono inactivo">Inactivo</span>'; ?>
                        </td>
                        <td class="acciones">
                            <button class="boton eliminar-turno"
                                data-id="<?php echo $turno->id_turnos; ?>"
                                data-nombre="<?php echo htmlspecialchars($turno->turno_horario); ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/turnos/editar?id=<?php echo $turno->id_turnos; ?>">Editar</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
        if (isset($paginador)) {
            echo $paginador->render('/administrador/turnos/listado');
        }
        ?>
    </div>

    <div id="alerta-accion" class="form-container"></div>
    <script type="module" src="/assets/js/formularios.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputBusqueda = document.getElementById('busqueda-turno');
            const btnBuscar = document.getElementById('btn-buscar-turno');
            const tbody = document.getElementById('tabla-turnos');
            const filasOriginales = tbody.innerHTML; // Para poder volver al listado completo

            async function buscarTurno() {
                const termino = inputBusqueda.value.trim();

                if (!termino) {
                    tbody.innerHTML = filasOriginales;
                    return;
                }

                try {
                    const respuesta = await fetch('/administrador/turnos/buscar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            termino: termino
                        })
                    });
                    const data = await respuesta.json();

                    if (data.ok && data.turno) {
                        const t = data.turno;
                        const estadoHtml = t.estado == 1 ?
                            '<span class="estado-con-icono activo">Activo</span>' :
                            '<span class="estado-con-icono inactivo">Inactivo</span>';

                        tbody.innerHTML = `
                    <tr>
                        <td>${t.id_turnos}</td>
                        <td>${t.turno_horario}</td>
                        <td>${estadoHtml}</td>
                        <td class="acciones">
                            <button class="boton eliminar-turno" data-id="${t.id_turnos}" data-nombre="${t.turno_horario}">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/turnos/editar?id=${t.id_turnos}">Editar</a>
                        </td>
                    </tr>
                `;
                    } else {
                        tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align:center; padding: 20px; color:#a0aec0;">
                            No se encontró ningún turno con "${termino}"
                        </td>
                    </tr>
                `;
                    }
                } catch (error) {
                    console.error('Error al buscar turno:', error);
                    tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align:center; padding: 20px; color:#ef4444;">
                        Error de conexión al buscar
                    </td>
                </tr>
            `;
                }
            }

            btnBuscar?.addEventListener('click', buscarTurno);
            inputBusqueda?.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') buscarTurno();
                if (inputBusqueda.value.trim() === '') {
                    tbody.innerHTML = filasOriginales;
                }
            });
        });
    </script>

    <?php

    use Classes\Paginador;

    echo Paginador::renderCSS();
    ?>
</body>