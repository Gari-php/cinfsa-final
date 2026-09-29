<!-- views/administrador/cajas/monitor.php -->
<div class="monitor-cajas-container">
    <div class="header-monitor">
        <h1><i class="fas fa-chart-line"></i> Monitor de Actividad de Cajas</h1>
        <p>Visualiza y gestiona la actividad de todas las cajas</p>
    </div>

    <!-- Cajas Abiertas Actualmente -->
    <div class="card-cajas-abiertas">
        <div class="card-header">
            <h2><i class="fas fa-door-open"></i> Cajas Abiertas Actualmente</h2>
            <span class="badge-count"><?php echo count($cajas_abiertas); ?> cajas</span>
        </div>

        <?php if (empty($cajas_abiertas)): ?>
            <div class="sin-cajas-abiertas">
                <i class="fas fa-lock"></i>
                <p>No hay cajas abiertas en este momento</p>
            </div>
        <?php else: ?>
            <div class="grid-cajas-abiertas">
                <?php foreach ($cajas_abiertas as $caja):
                    $saldoActual = $caja['monto_inicial'] + $caja['ingresos'] - $caja['egresos'];
                ?>
                    <div class="card-caja-abierta">
                        <div class="caja-abierta-header">
                            <div class="caja-info">
                                <h3><?php echo $caja['nombre_caja']; ?></h3>
                                <span class="badge-vendedor"><?php echo $caja['vendedor']; ?></span>
                            </div>
                            <div class="caja-tiempo">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $caja['horas_abierta']; ?>h <?php echo $caja['minutos_abierta'] % 60; ?>m</span>
                            </div>
                        </div>

                        <div class="caja-abierta-stats">
                            <div class="stat-item">
                                <span class="stat-label">Apertura</span>
                                <strong><?php echo $caja['apertura']; ?></strong>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Monto Inicial</span>
                                <strong>$<?php echo number_format($caja['monto_inicial'], 0, ',', '.'); ?></strong>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Saldo Actual</span>
                                <strong class="saldo-destacado">$<?php echo number_format($saldoActual, 0, ',', '.'); ?></strong>
                            </div>
                        </div>

                        <div class="caja-acciones">
                            <a href="/administrador/actividadcajas/detalle?id=<?php echo $caja['id_arqueo_caja']; ?>" class="btn-ver">
                                <i class="fas fa-eye"></i> Ver Detalle
                            </a>
                            <button class="btn-forzar-cierre" onclick="forzarCierre(<?php echo $caja['id_arqueo_caja']; ?>)">
                                <i class="fas fa-lock"></i> Forzar Cierre
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="card-filtros">
        <div class="card-header">
            <h2><i class="fas fa-filter"></i> Historial de Cajas</h2>
        </div>

        <form id="form-filtros" class="filtros-form">
            <div class="filtros-grid">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Desde</label>
                    <input type="date" id="fecha_desde" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Hasta</label>
                    <input type="date" id="fecha_hasta" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-cash-register"></i> Caja</label>
                    <select id="id_caja">
                        <option value="">Todas las cajas</option>
                        <?php foreach ($cajas as $caja): ?>
                            <option value="<?php echo $caja['id_caja']; ?>">
                                <?php echo $caja['nombre_caja']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Vendedor</label>
                    <select id="id_vendedor">
                        <option value="">Todos los vendedores</option>
                        <?php foreach ($vendedores as $vendedor): ?>
                            <option value="<?php echo $vendedor['id_usuario']; ?>">
                                <?php echo $vendedor['nombre_usuario']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Estado</label>
                    <select id="estado_arqueo">
                        <option value="">Todos</option>
                        <option value="abierto">Abiertas</option>
                        <option value="cerrado">Cerradas</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-buscar">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Resumen de Resultados -->
    <div id="resumen-container" class="card-resumen" style="display: none;">
        <div class="resumen-grid">
            <div class="resumen-item">
                <i class="fas fa-list"></i>
                <div>
                    <span>Total Arqueos</span>
                    <strong id="total-arqueos">0</strong>
                </div>
            </div>
            <div class="resumen-item">
                <i class="fas fa-door-open"></i>
                <div>
                    <span>Abiertas</span>
                    <strong id="arqueos-abiertos">0</strong>
                </div>
            </div>
            <div class="resumen-item">
                <i class="fas fa-lock"></i>
                <div>
                    <span>Cerradas</span>
                    <strong id="arqueos-cerrados">0</strong>
                </div>
            </div>
            <div class="resumen-item">
                <i class="fas fa-dollar-sign"></i>
                <div>
                    <span>Total Ventas</span>
                    <strong id="monto-total-ventas">$0</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div id="resultados-container" class="card-resultados" style="display: none;">
        <div class="tabla-wrapper">
            <table class="tabla-arqueos" id="tabla-arqueos">
                <thead>
                    <tr>
                        <th>Caja</th>
                        <th>Vendedor</th>
                        <th>Fecha</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Duración</th>
                        <th>M. Inicial</th>
                        <th>Ventas</th>
                        <th>M. Final</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-arqueos">
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .monitor-cajas-container {
        padding: 2rem;
        min-height: 100vh;
    }

    .header-monitor {
        margin-bottom: 2rem;
    }

    .header-monitor h1 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        font-weight: bold;
        color: #ed850f;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-monitor p {
        margin: 0;
        color: #a0aec0;
    }

    .card-cajas-abiertas,
    .card-filtros,
    .card-resumen,
    .card-resultados {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        color: #e2e8f0;
    }

    .card-header h2 {
        margin: 0;
        color: #ed850f;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-count {
        background: rgba(237, 133, 15, 0.15);
        color: #ed850f;
        border: 1px solid #ed850f;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .sin-cajas-abiertas {
        text-align: center;
        padding: 3rem;
        color: #a0aec0;
    }

    .sin-cajas-abiertas i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #4a5568;
    }

    .grid-cajas-abiertas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
        gap: 1.5rem;
    }

    .card-caja-abierta {
        background: #1a202c;
        border-radius: 10px;
        padding: 1.5rem;
        border: 1px solid #4a5568;
        transition: border-color 0.2s ease;
    }

    .card-caja-abierta:hover {
        border-color: #ed850f;
    }

    .caja-abierta-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        color: #e2e8f0;
    }

    .caja-info h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.3rem;
        color: #e2e8f0;
    }

    .badge-vendedor {
        background: rgba(237, 133, 15, 0.15);
        color: #ed850f;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .caja-tiempo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.1rem;
        color: #a0aec0;
    }

    .caja-abierta-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1rem 0;
        border-top: 1px solid #4a5568;
        border-bottom: 1px solid #4a5568;
    }

    .stat-item {
        text-align: center;
    }

    .stat-label {
        display: block;
        font-size: 0.85rem;
        color: #a0aec0;
    }

    .stat-item strong {
        display: block;
        font-size: 1.2rem;
        margin-top: 0.25rem;
        color: #e2e8f0;
    }

    .saldo-destacado {
        color: #22c55e !important;
        font-size: 1.4rem !important;
    }

    .caja-acciones {
        display: flex;
        gap: 0.75rem;
    }

    .btn-ver,
    .btn-forzar-cierre {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-ver {
        background: transparent;
        color: #e2e8f0;
        border: 2px solid #4a5568;
    }

    .btn-ver:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    .btn-forzar-cierre {
        background: #ef4444;
        color: #fff;
    }

    .btn-forzar-cierre:hover {
        background: #dc2626;
        transform: translateY(-2px);
    }

    .filtros-form {
        color: #e2e8f0;
    }

    .filtros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
        gap: 1rem;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #a0aec0;
        font-size: 0.9rem;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #4a5568;
        border-radius: 8px;
        background: #2d3748;
        color: #e2e8f0;
        font-size: 1rem;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    .form-group select option {
        background: #2d3748;
        color: #e2e8f0;
        padding: 0.5rem;
    }

    .form-group input::placeholder {
        color: #6b7280;
    }

    .btn-buscar {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1.75rem;
        box-shadow: 0 6px 18px rgba(237, 133, 15, 0.35);
    }

    .btn-buscar:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    .card-resumen {
        color: #e2e8f0;
    }

    .resumen-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
        gap: 1.5rem;
    }

    .resumen-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #1a202c;
        border: 1px solid #4a5568;
        border-radius: 10px;
    }

    .resumen-item i {
        font-size: 2rem;
        color: #ed850f;
    }

    .resumen-item span {
        display: block;
        font-size: 0.9rem;
        color: #a0aec0;
    }

    .resumen-item strong {
        display: block;
        font-size: 1.5rem;
        color: #e2e8f0;
    }

    .tabla-wrapper {
        overflow-x: auto;
    }

    .tabla-arqueos {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
    }

    .tabla-arqueos thead th {
        background: #1a202c;
        color: #ed850f;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #ed850f;
    }

    .tabla-arqueos tbody td {
        padding: 1rem;
        border-bottom: 1px solid #4a5568;
    }

    .tabla-arqueos tbody tr:hover {
        background: rgba(237, 133, 15, 0.06);
    }

    .badge-estado {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .badge-estado.abierto {
        background: #22c55e;
        color: #fff;
    }

    .badge-estado.cerrado {
        background: #6b7280;
        color: #fff;
    }

    .dif-positivo {
        color: #6ee7b7;
    }

    .dif-negativo {
        color: #fca5a5;
    }

    .dif-cuadrado {
        color: #e2e8f0;
    }

    @media (max-width: 768px) {
        .monitor-cajas-container {
            padding: 1rem;
        }

        .filtros-grid {
            grid-template-columns: 1fr;
        }

        .grid-cajas-abiertas {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .card-caja-abierta {
            padding: 1rem;
        }

        .caja-abierta-stats {
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .caja-acciones {
            flex-direction: column;
        }
    }
</style>

<script>
    // Buscar historial
    document.getElementById('form-filtros').addEventListener('submit', async function(e) {
        e.preventDefault();

        const fechaDesde = document.getElementById('fecha_desde').value;
        const fechaHasta = document.getElementById('fecha_hasta').value;
        const idCaja = document.getElementById('id_caja').value;
        const idVendedor = document.getElementById('id_vendedor').value;
        const estadoArqueo = document.getElementById('estado_arqueo').value;

        try {
            const response = await fetch('/administrador/actividadcajas/consultar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta,
                    id_caja: idCaja,
                    id_vendedor: idVendedor,
                    estado_arqueo: estadoArqueo
                })
            });

            const data = await response.json();

            if (data.ok) {
                mostrarResultados(data.arqueos, data.resumen);
            } else {
                alert('Error: ' + data.mensaje);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión');
        }
    });

    function mostrarResultados(arqueos, resumen) {
        // Mostrar resumen
        document.getElementById('resumen-container').style.display = 'block';
        document.getElementById('total-arqueos').textContent = resumen.total_arqueos;
        document.getElementById('arqueos-abiertos').textContent = resumen.arqueos_abiertos;
        document.getElementById('arqueos-cerrados').textContent = resumen.arqueos_cerrados;
        document.getElementById('monto-total-ventas').textContent = '$' + resumen.monto_total_ventas.toLocaleString('es-AR');

        // Llenar tabla
        const tbody = document.getElementById('tbody-arqueos');
        tbody.innerHTML = '';

        if (arqueos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 2rem;">No se encontraron resultados</td></tr>';
        } else {
            arqueos.forEach(arqueo => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${arqueo.nombre_caja}</td>
                <td>${arqueo.vendedor}</td>
                <td>${arqueo.fecha}</td>
                <td>${arqueo.hora_apertura}</td>
                <td>${arqueo.hora_cierre}</td>
                <td>${arqueo.duracion_turno}</td>
                <td>$${parseFloat(arqueo.monto_inicial).toLocaleString('es-AR')}</td>
                <td>$${parseFloat(arqueo.total_ventas).toLocaleString('es-AR')}</td>
                <td>$${parseFloat(arqueo.monto_final).toLocaleString('es-AR')}</td>
                <td class="dif-${arqueo.diferencia_tipo}">${arqueo.diferencia_texto}</td>
                <td><span class="badge-estado ${arqueo.estado_arqueo}">${arqueo.estado_arqueo.toUpperCase()}</span></td>
                <td>
                    <a href="/administrador/actividadcajas/detalle?id=${arqueo.id_arqueo_caja}" class="btn-ver" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('resultados-container').style.display = 'block';
    }

    // Forzar cierre
    async function forzarCierre(idArqueo) {
        if (!confirm('¿Estás seguro de forzar el cierre de esta caja?\n\nEsta acción cerrará la caja del vendedor.')) {
            return;
        }

        const observaciones = prompt('Observaciones del cierre forzado:', 'Cierre forzado por administrador');

        if (observaciones === null) return;

        try {
            const response = await fetch('/administrador/actividadcajas/forzar-cierre', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_arqueo: idArqueo,
                    observaciones: observaciones
                })
            });

            const data = await response.json();

            if (data.ok) {
                alert('✅ ' + data.mensaje);
                location.reload();
            } else {
                alert('❌ ' + data.mensaje);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error de conexión');
        }
    }
</script>