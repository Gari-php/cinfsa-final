<div class="contenedor-principal">
    <div class="header-seccion">
        <h1><i class="fa-solid fa-puzzle-piece"></i> Gestión de Módulos por Perfil</h1>
        <p class="subtitulo">Asigna o desactiva módulos para cada tipo de usuario</p>
        <div class="info-admin">
            <i class="fa-solid fa-info-circle"></i>
            <strong>Nota:</strong> El perfil ADMINISTRADOR tiene acceso automático a todos los módulos del sistema y no requiere asignaciones.
        </div>
    </div>

    <div class="panel-asignacion">
        <!-- Selector de Perfil -->
        <div class="selector-perfil">
            <label for="select-perfil">
                <i class="fa-solid fa-user-tag"></i> Seleccionar Perfil:
            </label>
            <select id="select-perfil" class="form-select">
                <option value="">-- Selecciona un perfil --</option>
                <?php foreach ($perfiles as $perfil): ?>
                    <option value="<?php echo $perfil->id_perfiles; ?>">
                        <?php echo htmlspecialchars($perfil->nombre_perfil); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Grid de Módulos -->
        <div id="modulos-grid" class="modulos-grid" style="display: none;">
            <div class="loading">
                <i class="fa-solid fa-spinner fa-spin"></i> Cargando módulos...
            </div>
        </div>
    </div>

    <!-- Tabla de Asignaciones Actuales -->
    <div class="tabla-asignaciones">
        <h2><i class="fa-solid fa-list-check"></i> Asignaciones Actuales</h2>

        <div class="tabla-responsive">
            <table class="tabla-moderna">
                <thead>
                    <tr>
                        <th>Perfil</th>
                        <th>Módulo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-asignaciones-body">
                    <?php if (empty($asignaciones)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay asignaciones registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($asignaciones as $asignacion): ?>
                            <tr data-id="<?php echo $asignacion['id_mod_x_tipo']; ?>">
                                <td>
                                    <span class="badge-perfil"><?php echo htmlspecialchars($asignacion['nombre_perfil']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($asignacion['modulo_nombre']); ?></td>
                                <td>
                                    <?php if ($asignacion['estado'] == 1): ?>
                                        <span class="badge-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="badge-inactivo">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-toggle-estado"
                                        data-perfil="<?php echo $asignacion['rela_tipos_de_usuarios']; ?>"
                                        data-modulo="<?php echo $asignacion['rela_modulo']; ?>"
                                        data-estado="<?php echo $asignacion['estado']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($asignacion['modulo_nombre']); ?>">
                                        <?php if ($asignacion['estado'] == 1): ?>
                                            <i class="fa-solid fa-toggle-on"></i> Desactivar
                                        <?php else: ?>
                                            <i class="fa-solid fa-toggle-off"></i> Activar
                                        <?php endif; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .contenedor-principal {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
    }

    .header-seccion {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        color: #fff;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .header-seccion h1 {
        margin: 0 0 10px 0;
        font-size: 28px !important;
        color: #ed850f;
    }

    .subtitulo {
        margin: 0 0 15px 0;
        color: #a0aec0;
        font-size: 14px !important;
    }

    .info-admin {
        background: rgba(237, 133, 15, 0.1);
        color: #e2e8f0;
        padding: 12px;
        border-radius: 8px;
        font-size: 13px !important;
        border-left: 4px solid #ed850f;
    }

    .info-admin i {
        margin-right: 8px;
        color: #ed850f;
    }

    .panel-asignacion,
    .tabla-asignaciones {
        background: #2d3748;
        border: 1px solid #4a5568;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        margin-bottom: 30px;
    }

    .selector-perfil {
        margin-bottom: 30px;
    }

    .selector-perfil label {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
        color: #a0aec0;
        font-size: 15px !important;
    }

    .form-select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #4a5568;
        border-radius: 8px;
        background: #1a202c;
        color: #e2e8f0;
        font-size: 15px !important;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .form-select:focus {
        outline: none;
        border-color: #ed850f;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    .form-select option {
        background: #2d3748;
        color: #e2e8f0;
    }

    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(250px, 100%), 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .modulo-card {
        background: #1a202c;
        border: 2px solid #4a5568;
        border-radius: 10px;
        padding: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }

    .modulo-card:hover {
        border-color: #ed850f;
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
    }

    .modulo-card.activo {
        background: rgba(34, 197, 94, 0.08);
        border-color: #22c55e;
    }

    .modulo-card.activo::before {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 10px;
        right: 10px;
        color: #22c55e;
        font-size: 20px !important;
    }

    .modulo-nombre {
        font-weight: bold;
        font-size: 16px !important;
        color: #e2e8f0;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modulo-nombre i {
        color: #ed850f;
    }

    .modulo-estado {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px !important;
        font-weight: bold;
    }

    .estado-activo {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid #22c55e;
    }

    .estado-inactivo {
        background: rgba(107, 114, 128, 0.15);
        color: #9ca3af;
        border: 1px solid #6b7280;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: #ed850f;
        font-size: 16px !important;
    }

    .tabla-asignaciones h2 {
        margin: 0 0 20px 0;
        color: #ed850f;
        font-size: 22px !important;
    }

    .tabla-responsive {
        overflow-x: auto;
    }

    .tabla-moderna {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .tabla-moderna thead {
        background: #1a202c;
        color: #ed850f;
    }

    .tabla-moderna th {
        padding: 15px;
        text-align: left;
        font-weight: bold;
        font-size: 14px !important;
        border-bottom: 2px solid #ed850f;
    }

    .tabla-moderna td {
        padding: 12px 15px;
        border-bottom: 1px solid #4a5568;
        font-size: 14px !important;
        color: #e2e8f0;
    }

    .tabla-moderna tbody tr:hover {
        background: rgba(237, 133, 15, 0.06);
    }

    .badge-perfil {
        display: inline-block;
        padding: 6px 12px;
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border: 1px solid #3b82f6;
        border-radius: 20px;
        font-size: 13px !important;
        font-weight: bold;
    }

    .badge-activo {
        display: inline-block;
        padding: 6px 12px;
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid #22c55e;
        border-radius: 20px;
        font-size: 12px !important;
        font-weight: bold;
    }

    .badge-inactivo {
        display: inline-block;
        padding: 6px 12px;
        background: rgba(107, 114, 128, 0.15);
        color: #9ca3af;
        border: 1px solid #6b7280;
        border-radius: 20px;
        font-size: 12px !important;
        font-weight: bold;
    }

    .btn-toggle-estado {
        padding: 8px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 13px !important;
        font-weight: bold;
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: #fff;
    }

    .btn-toggle-estado:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.4);
    }

    .text-center {
        text-align: center;
        padding: 30px;
        color: #a0aec0;
    }

    @media (max-width: 768px) {
        .contenedor-principal {
            padding: 12px;
        }

        .header-seccion {
            padding: 20px;
        }

        .header-seccion h1 {
            font-size: 22px !important;
        }

        .panel-asignacion,
        .tabla-asignaciones {
            padding: 18px;
        }

        .modulos-grid {
            grid-template-columns: 1fr;
        }

        .tabla-moderna {
            font-size: 13px !important;
        }

        .tabla-moderna th,
        .tabla-moderna td {
            padding: 10px;
        }
    }
</style>

<script>
    function esperarFormulariosJS() {
        return new Promise((resolve) => {
            // Si ya está disponible, resolver inmediatamente
            if (typeof window.mostrarAlerta === 'function' &&
                typeof window.mostrarConfirmacionModal === 'function') {
                resolve();
                return;
            }

            // Si no, esperar hasta que se cargue
            const intervalo = setInterval(() => {
                if (typeof window.mostrarAlerta === 'function' &&
                    typeof window.mostrarConfirmacionModal === 'function') {
                    clearInterval(intervalo);
                    resolve();
                }
            }, 100);

            // Timeout de seguridad (10 segundos)
            setTimeout(() => {
                clearInterval(intervalo);
                console.error('❌ formularios.js no se cargó correctamente');
                resolve();
            }, 10000);
        });
    }

    document.addEventListener('DOMContentLoaded', async function() {
        // Esperar a que formularios.js esté disponible
        await esperarFormulariosJS();

        console.log('✅ formularios.js cargado:', {
            mostrarAlerta: typeof window.mostrarAlerta,
            mostrarConfirmacionModal: typeof window.mostrarConfirmacionModal
        });

        const selectPerfil = document.getElementById('select-perfil');
        const modulosGrid = document.getElementById('modulos-grid');


        selectPerfil.addEventListener('change', async function() {
            const idPerfil = this.value;

            if (!idPerfil) {
                modulosGrid.style.display = 'none';
                return;
            }

            modulosGrid.style.display = 'block';
            modulosGrid.innerHTML = '<div class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando módulos...</div>';

            try {
                const response = await fetch(`/administrador/modulos/modulosPerfil?id_perfil=${idPerfil}`);
                const data = await response.json();

                if (data.ok) {
                    mostrarModulos(data.modulos, idPerfil);
                } else {
                    modulosGrid.innerHTML = `<div class="text-center">${data.error || 'Error al cargar módulos'}</div>`;
                    if (typeof window.mostrarAlerta === 'function') {
                        window.mostrarAlerta(data.error || 'Error al cargar módulos', 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof window.mostrarAlerta === 'function') {
                    window.mostrarAlerta('Error de conexión', 'error');
                }
            }
        });

        function mostrarModulos(modulos, idPerfil) {
            if (modulos.length === 0) {
                modulosGrid.innerHTML = '<div class="text-center">No hay módulos disponibles</div>';
                return;
            }

            let html = '';
            modulos.forEach(modulo => {
                const activo = modulo.asignado == 1;
                html += `
                <div class="modulo-card ${activo ? 'activo' : ''}" 
                     data-modulo-id="${modulo.id_modulo}"
                     data-perfil-id="${idPerfil}"
                     data-activo="${activo ? 1 : 0}"
                     data-nombre="${modulo.modulo_nombre}">
                    <div class="modulo-nombre">
                        <i class="fa-solid fa-puzzle-piece"></i>
                        ${modulo.modulo_nombre}
                    </div>
                    <span class="modulo-estado ${activo ? 'estado-activo' : 'estado-inactivo'}">
                        ${activo ? 'ACTIVO' : 'INACTIVO'}
                    </span>
                </div>
            `;
            });

            modulosGrid.innerHTML = html;

            // Agregar eventos click a las cards
            document.querySelectorAll('.modulo-card').forEach(card => {
                card.addEventListener('click', function() {
                    toggleModulo(this);
                });
            });
        }


        async function toggleModulo(card) {
            const idPerfil = card.dataset.perfilId;
            const idModulo = card.dataset.moduloId;
            const estadoActual = parseInt(card.dataset.activo);
            const nuevoEstado = estadoActual === 1 ? 0 : 1;
            const nombreModulo = card.dataset.nombre;

            const accion = nuevoEstado === 1 ? 'activar' : 'desactivar';

            // Verificar que la función existe
            if (typeof window.mostrarConfirmacionModal !== 'function') {
                console.error('❌ window.mostrarConfirmacionModal no disponible');
                if (!confirm(`¿Deseas ${accion} el módulo ${nombreModulo}?`)) return;
            } else {
                const confirmacion = await window.mostrarConfirmacionModal(
                    `¿Deseas ${accion} el módulo <strong>${nombreModulo}</strong>?`,
                    'warning'
                );

                if (!confirmacion) return;
            }

            try {
                const response = await fetch('/administrador/modulos/asignar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_perfil: idPerfil,
                        id_modulo: idModulo,
                        estado: nuevoEstado
                    })
                });

                const data = await response.json();

                if (data.ok) {
                    // Actualizar la card
                    card.dataset.activo = nuevoEstado;

                    if (nuevoEstado === 1) {
                        card.classList.add('activo');
                        card.querySelector('.modulo-estado').textContent = 'ACTIVO';
                        card.querySelector('.modulo-estado').classList.remove('estado-inactivo');
                        card.querySelector('.modulo-estado').classList.add('estado-activo');
                    } else {
                        card.classList.remove('activo');
                        card.querySelector('.modulo-estado').textContent = 'INACTIVO';
                        card.querySelector('.modulo-estado').classList.remove('estado-activo');
                        card.querySelector('.modulo-estado').classList.add('estado-inactivo');
                    }

                    if (typeof window.mostrarAlerta === 'function') {
                        window.mostrarAlerta(data.mensaje, 'exito');
                    }

                    // Recargar la tabla de asignaciones
                    setTimeout(() => {
                        location.reload();
                    }, 1500);

                } else {
                    if (typeof window.mostrarAlerta === 'function') {
                        window.mostrarAlerta(data.error || 'Error al actualizar el módulo', 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof window.mostrarAlerta === 'function') {
                    window.mostrarAlerta('Error de conexión', 'error');
                }
            }
        }


        document.querySelectorAll('.btn-toggle-estado').forEach(btn => {
            btn.addEventListener('click', async function() {
                const idPerfil = this.dataset.perfil;
                const idModulo = this.dataset.modulo;
                const estadoActual = parseInt(this.dataset.estado);
                const nuevoEstado = estadoActual === 1 ? 0 : 1;
                const nombreModulo = this.dataset.nombre;

                const accion = nuevoEstado === 1 ? 'activar' : 'desactivar';

                // Verificar que la función existe
                if (typeof window.mostrarConfirmacionModal !== 'function') {
                    console.error('❌ window.mostrarConfirmacionModal no disponible');
                    if (!confirm(`¿Deseas ${accion} el módulo ${nombreModulo}?`)) return;
                } else {
                    const confirmacion = await window.mostrarConfirmacionModal(
                        `¿Deseas ${accion} el módulo <strong>${nombreModulo}</strong>?`,
                        'warning'
                    );

                    if (!confirmacion) return;
                }

                try {
                    const response = await fetch('/administrador/modulos/asignar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id_perfil: idPerfil,
                            id_modulo: idModulo,
                            estado: nuevoEstado
                        })
                    });

                    const data = await response.json();

                    if (data.ok) {
                        if (typeof window.mostrarAlerta === 'function') {
                            window.mostrarAlerta(data.mensaje, 'exito');
                        }
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        if (typeof window.mostrarAlerta === 'function') {
                            window.mostrarAlerta(data.error || 'Error al actualizar', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof window.mostrarAlerta === 'function') {
                        window.mostrarAlerta('Error de conexión', 'error');
                    }
                }
            });
        });
    });
</script>