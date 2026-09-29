<!-- views/vendedor/caja/estado.php -->
<div class="dashboard-vendedor">
    <div class="header-caja-estado">
        <div class="info-caja">
            <div class="icono-caja">
                <i class="fas fa-cash-register"></i>
            </div>
            <div class="datos-caja">
                <h1>CAJA ABIERTA</h1>
                <p class="nombre-caja"><?php echo $arqueo->nombre_caja ?? 'Caja Principal'; ?></p>
                <p class="numero-caja">Número: <?php echo $arqueo->numero_caja ?? 'N/A'; ?></p>
            </div>
        </div>
        
        <div class="stats-caja">
            <div class="stat-item">
                <span class="stat-label">Monto Inicial</span>
                <span class="stat-value">$<?php echo number_format($arqueo->monto_inicial, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Ingresos</span>
                <span class="stat-value vendido">$<?php echo number_format($total_ingresos ?? 0, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Egresos</span>
                <span class="stat-value egreso">$<?php echo number_format($total_egresos ?? 0, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Saldo Esperado</span>
                <span class="stat-value total">$<?php echo number_format($saldo_final ?? 0, 0, ',', '.'); ?></span>
            </div>
        </div>

        <div class="info-usuario-caja">
            <p><i class="fas fa-user"></i> <?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></p>
            <p><i class="fas fa-clock"></i> Abierta desde: <?php echo date('d/m/Y H:i', strtotime($arqueo->fecha_inicio)); ?></p>
        </div>
    </div>

    <div class="acciones-principales">
        <a href="/vendedor/funciones/listado" class="btn-accion btn-vender">
            <i class="fas fa-ticket-alt"></i>
            <span>Vender Entradas</span>
            <small>Seleccionar función y butacas</small>
        </a>

        <a href="/vendedor/ventas/consulta" class="btn-accion btn-consultar">
            <i class="fas fa-search-dollar"></i>
            <span>Consultar Ventas</span>
            <small>Ver historial completo</small>
        </a>

        <a href="/vendedor/movimientos" class="btn-accion btn-ingresos-egresos">
            <i class="fas fa-exchange-alt"></i>
            <span>Ingresos/Egresos</span>
            <small>Gestionar movimientos</small>
        </a>

        <button type="button" class="btn-accion btn-movimientos" onclick="verMovimientos()">
            <i class="fas fa-list"></i>
            <span>Ver Movimientos</span>
            <small>Historial de transacciones</small>
        </button>

        <button type="button" class="btn-accion btn-cerrar-caja" onclick="mostrarModalCerrar()">
            <i class="fas fa-lock"></i>
            <span>Cerrar Caja</span>
            <small>Finalizar turno</small>
        </button>
    </div>

    <div class="resumen-movimientos" id="resumen-movimientos" style="display: none;">
        <div class="header-resumen">
            <h2><i class="fas fa-list"></i> Movimientos de Caja</h2>
            <button type="button" class="btn-cerrar-movimientos" onclick="cerrarMovimientos()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="tabla-movimientos">
            <?php if (empty($movimientos)): ?>
                <div class="sin-movimientos">
                    <i class="fas fa-inbox"></i>
                    <p>No hay movimientos registrados aún</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Forma Pago</th>
                            <th>Monto</th>
                            <th>Fecha/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td>
                                <span class="badge-tipo <?php echo $mov->tipo_movimiento; ?>">
                                    <i class="fas fa-<?php echo $mov->tipo_movimiento === 'ingreso' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo ucfirst($mov->tipo_movimiento); ?>
                                </span>
                            </td>
                            <td><?php echo $mov->concepto_movimiento; ?></td>
                            <td><?php echo ucfirst($mov->forma_pago ?? 'N/A'); ?></td>
                            <td class="monto <?php echo $mov->tipo_movimiento; ?>">
                                <?php echo $mov->tipo_movimiento === 'ingreso' ? '+' : '-'; ?>
                                $<?php echo number_format($mov->monto, 0, ',', '.'); ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mov->fecha_movimiento)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="modal-cerrar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-lock"></i> Cerrar Caja</h2>
            <button type="button" onclick="cerrarModal()" class="btn-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" id="modal-body-resumen">
            <div class="resumen-cierre">
                <h3>Resumen del Turno</h3>
                
                <div class="item-resumen">
                    <span>Monto Inicial (Efectivo):</span>
                    <strong id="resumen-inicial">$<?php echo number_format($arqueo->monto_inicial, 0, ',', '.'); ?></strong>
                </div>
                
                <div class="item-resumen ingreso">
                    <span>+ Ingresos Efectivo:</span>
                    <strong id="resumen-efectivo">$0</strong>
                </div>
                
                <div class="item-resumen ingreso-transfer">
                    <span>+ Ingresos Transferencia:</span>
                    <strong id="resumen-transferencia">$0</strong>
                </div>
                
                <div class="item-resumen egreso-res">
                    <span>- Egresos:</span>
                    <strong id="resumen-egresos">$0</strong>
                </div>
                
                <div class="item-resumen total">
                    <span>SALDO ESPERADO (Efectivo):</span>
                    <strong id="resumen-saldo-efectivo">$0</strong>
                </div>
            </div>

            <form id="form-cerrar-caja">
                <div class="form-group">
                    <label for="monto_final">
                        <i class="fas fa-money-bill-wave"></i> Monto Final Contado (Solo Efectivo)
                    </label>
                    <input 
                        type="number" 
                        id="monto_final" 
                        step="0.01" 
                        min="0"
                        placeholder="0.00"
                        required
                    >
                    <small>Cuenta el efectivo físico en caja (no incluir transferencias)</small>
                </div>

                <div id="diferencia-info" class="diferencia-info" style="display: none;">
                    <div class="diferencia-valor">
                        <span>Diferencia:</span>
                        <strong id="diferencia-monto">$0</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observaciones">
                        <i class="fas fa-comment"></i> Observaciones (opcional)
                    </label>
                    <textarea 
                        id="observaciones" 
                        rows="3"
                        placeholder="Agregar comentarios sobre el cierre..."
                    ></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="cerrarModal()" class="btn-cancelar">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-confirmar" id="btn-confirmar-cierre">
                        <i class="fas fa-check"></i>
                        Confirmar Cierre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --color-principal: #ed850f;
    --color-principal-hover: #d97706;
    --color-fondo-oscuro: #1a202c;
    --color-fondo-gris: #2d3748;
    --color-hover-gris: #4a5568;
    --color-texto-claro: #fff;
    --color-texto-oscuro: #1e293b;
}

.dashboard-vendedor {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
    padding: 2rem;
}

/* === HEADER === */
.header-caja-estado {
    background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
    border-radius: 20px;
    padding: 2rem;
    color: var(--color-texto-claro);
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    margin-bottom: 2rem;
}

.info-caja {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.icono-caja {
    font-size: 4rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.datos-caja h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.datos-caja p {
    margin: 0.2rem 0;
    opacity: 0.9;
}

.stats-caja {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 2px solid rgba(255,255,255,0.2);
}

.stat-label {
    display: block;
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: bold;
}

.stat-value.vendido { color: #105410ff; }
.stat-value.egreso { color: #860303ff; }
.stat-value.total { color: #947f08ff; }

.info-usuario-caja {
    display: flex;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 2px solid rgba(255,255,255,0.2);
}

.info-usuario-caja p {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* === ACCIONES === */
.acciones-principales {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(250px, 100%), 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.btn-accion {
    background: var(--color-fondo-gris);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    border: 2px solid var(--color-hover-gris);
    font-family: inherit;
}

.btn-accion:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}

.btn-accion i {
    font-size: 3rem;
}

.btn-vender,
.btn-consultar,
.btn-ingresos-egresos,
.btn-movimientos i { color: var(--color-principal); }
.btn-cerrar-caja i { color: #dc2626; }

.btn-accion span {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--color-texto-claro);
}

.btn-accion small {
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
}

.btn-vender:hover,
.btn-movimientos:hover,
.btn-ingresos-egresos:hover,
.btn-consultar:hover  {
    background: linear-gradient(135deg, var( --color-hover-gris) 0%, var( --color-hover-gris) 100%);
    border-color: var( --color-hover-gris);
}

.btn-cerrar-caja:hover {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    border-color: #dc2626;
}

/* === MOVIMIENTOS === */
.resumen-movimientos {
    background: var(--color-fondo-gris);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    margin-bottom: 2rem;
}

.header-resumen {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--color-hover-gris);
}

.header-resumen h2 {
    margin: 0;
    color: var(--color-texto-claro);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-cerrar-movimientos {
    background: var(--color-hover-gris);
    border: none;
    color: var(--color-texto-claro);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cerrar-movimientos:hover {
    background: var(--color-principal);
    transform: rotate(90deg);
}

.tabla-movimientos {
    overflow-x: auto;
}

.tabla-movimientos table {
    width: 100%;
    border-collapse: collapse;
}

.tabla-movimientos thead th {
    background: var(--color-fondo-oscuro);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--color-texto-claro);
    border-bottom: 2px solid var(--color-hover-gris);
}

.tabla-movimientos tbody td {
    padding: 1rem;
    border-bottom: 1px solid var(--color-hover-gris);
    color: var(--color-texto-claro);
}

.tabla-movimientos tbody tr:hover {
    background: var(--color-hover-gris);
}

.badge-tipo {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-tipo.ingreso {
    background: rgba(34,197,94,0.2);
    color: #86efac;
    border: 1px solid #22c55e;
}

.badge-tipo.egreso {
    background: rgba(239,68,68,0.2);
    color: #fca5a5;
    border: 1px solid #ef4444;
}

.monto {
    font-weight: bold;
    font-size: 1.1rem;
}

.monto.ingreso { color: #6ee7b7; }
.monto.egreso { color: #fca5a5; }

.sin-movimientos {
    text-align: center;
    padding: 3rem;
    color: rgba(255,255,255,0.5);
}

.sin-movimientos i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* === MODAL === */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--color-fondo-gris);
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: var(--color-texto-claro);
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: var(--color-texto-claro);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-close:hover {
    background: rgba(255,255,255,0.3);
}

.modal-body {
    padding: 2rem;
}

.resumen-cierre {
    background: var(--color-fondo-oscuro);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1.5rem;
}

.resumen-cierre h3 {
    margin: 0 0 1rem 0;
    color: var(--color-texto-claro);
}

.item-resumen {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--color-hover-gris);
    color: var(--color-texto-claro);
}

.item-resumen:last-child {
    border-bottom: none;
}

.item-resumen.total {
    border-top: 2px solid var(--color-principal);
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-size: 1.1rem;
}

.item-resumen.ingreso { color: #6ee7b7; }
.item-resumen.egreso-res { color: #fca5a5; }

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--color-texto-claro);
}

.form-group label i {
    color: var(--color-principal);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--color-hover-gris);
    border-radius: 10px;
    font-size: 1rem;
    background: var(--color-fondo-oscuro);
    color: var(--color-texto-claro);
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-principal);
    box-shadow: 0 0 0 3px rgba(237,133,15,0.2);
}

.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: rgba(255,255,255,0.6);
    font-size: 0.85rem;
}

.diferencia-info {
    background: rgba(245,158,11,0.2);
    border: 2px solid #f59e0b;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.diferencia-valor {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--color-texto-claro);
}

.diferencia-valor strong {
    font-size: 1.3rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.modal-actions button {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cancelar {
    background: var(--color-fondo-oscuro);
    color: var(--color-texto-claro);
    border: 2px solid var(--color-hover-gris);
}

.btn-cancelar:hover {
    background: var(--color-hover-gris);
}

.btn-confirmar {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: var(--color-texto-claro);
}

.btn-confirmar:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220,38,38,0.4);
}

.btn-confirmar:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.item-resumen.ingreso-transfer { 
    color: #60a5fa; 
}
/* === RESPONSIVE === */
@media (max-width: 768px) {
    .dashboard-vendedor {
        padding: 1rem;
    }

    .header-caja-estado {
        padding: 1.5rem;
    }

    .info-caja {
        flex-direction: column;
        text-align: center;
    }

    .stats-caja {
        grid-template-columns: 1fr 1fr;
    }

    .info-usuario-caja {
        flex-direction: column;
        gap: 0.5rem;
    }

    .acciones-principales {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let datosResumen = null;

function verMovimientos() {
    const resumen = document.getElementById('resumen-movimientos');
    resumen.style.display = 'block';
    resumen.scrollIntoView({ behavior: 'smooth' });
}

function cerrarMovimientos() {
    document.getElementById('resumen-movimientos').style.display = 'none';
}

async function mostrarModalCerrar() {
    try {
        const response = await fetch('/vendedor/caja/resumen-cierre');
        const data = await response.json();
        
        if (data.ok) {
            datosResumen = data.resumen;
            
            document.getElementById('resumen-inicial').textContent = 
                '$' + new Intl.NumberFormat('es-AR').format(datosResumen.monto_inicial);
            document.getElementById('resumen-efectivo').textContent = 
                '$' + new Intl.NumberFormat('es-AR').format(datosResumen.ingresos_efectivo);
            document.getElementById('resumen-transferencia').textContent = 
                '$' + new Intl.NumberFormat('es-AR').format(datosResumen.ingresos_transferencia);
            document.getElementById('resumen-egresos').textContent = 
                '$' + new Intl.NumberFormat('es-AR').format(datosResumen.total_egresos);
            document.getElementById('resumen-saldo-efectivo').textContent = 
                '$' + new Intl.NumberFormat('es-AR').format(datosResumen.monto_esperado_efectivo);
            
            document.getElementById('modal-cerrar').classList.add('active');
        } else {
            mostrarAlerta(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al cargar datos del cierre', 'error');
    }
}

function cerrarModal() {
    document.getElementById('modal-cerrar').classList.remove('active');
}

document.getElementById('monto_final').addEventListener('input', function() {
    if (!datosResumen) return;
    
    const montoFinal = parseFloat(this.value) || 0;
    const diferencia = montoFinal - datosResumen.monto_esperado_efectivo;
    
    const diferenciaInfo = document.getElementById('diferencia-info');
    const diferenciaMonto = document.getElementById('diferencia-monto');
    
    if (this.value) {
        diferenciaInfo.style.display = 'block';
        diferenciaMonto.textContent = (diferencia >= 0 ? '+' : '') + '$' + new Intl.NumberFormat('es-AR').format(Math.abs(diferencia));
        diferenciaMonto.style.color = diferencia >= 0 ? '#6ee7b7' : '#fca5a5';
    } else {
        diferenciaInfo.style.display = 'none';
    }
});

document.getElementById('form-cerrar-caja').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!datosResumen) {
        mostrarAlerta('Error: no se pudieron cargar los datos del cierre', 'error');
        return;
    }
    
    const montoFinal = parseFloat(document.getElementById('monto_final').value);
    const diferencia = montoFinal - datosResumen.monto_esperado_efectivo;
    
    let mensaje = `<strong>¿Confirmar cierre de caja?</strong><br><br>`;
    mensaje += `<strong>EFECTIVO:</strong><br>`;
    mensaje += `Saldo esperado: <strong>$${new Intl.NumberFormat('es-AR').format(datosResumen.monto_esperado_efectivo)}</strong><br>`;
    mensaje += `Monto contado: <strong>$${new Intl.NumberFormat('es-AR').format(montoFinal)}</strong><br>`;
    if (diferencia !== 0) {
        mensaje += `Diferencia: <strong style="color: ${diferencia >= 0 ? '#6ee7b7' : '#fca5a5'}">${diferencia >= 0 ? '+' : ''}$${new Intl.NumberFormat('es-AR').format(Math.abs(diferencia))}</strong><br>`;
    }
    mensaje += `<br><strong>TRANSFERENCIAS:</strong><br>`;
    mensaje += `Total: <strong>$${new Intl.NumberFormat('es-AR').format(datosResumen.ingresos_transferencia)}</strong>`;
    
    const confirmado = await mostrarConfirmacionModal(mensaje, 'warning');
    if (!confirmado) return;
    
    const btn = document.getElementById('btn-confirmar-cierre');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando...';
    
    try {
        const response = await fetch('/vendedor/caja/cerrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                monto_final: montoFinal,
                observaciones: document.getElementById('observaciones').value
            })
        });
        
        const data = await response.json();
        
        if (data.ok) {
            mostrarAlerta(data.mensaje, 'exito');
            
            if (data.generar_pdf) {
                window.open('/vendedor/caja/pdf-arqueo?id=' + data.id_arqueo, '_blank');
            }
            
            setTimeout(() => {
                window.location.href = '/vendedor/caja/abrir';
            }, 2000);
        } else {
            mostrarAlerta(data.mensaje, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Cierre';
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Cierre';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});
</script>