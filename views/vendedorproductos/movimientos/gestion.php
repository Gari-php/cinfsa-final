<div class="movimientos-container">
    <!-- Header -->
    <div class="header-movimientos">
        <div>
            <h1><i class="fas fa-exchange-alt"></i> Ingresos y Egresos</h1>
            <p>Gestiona los movimientos de tu caja actual</p>
        </div>
        <a href="/vendedorproductos/caja/estado" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver a Caja
        </a>
    </div>

    <!-- Info de caja actual -->
    <div class="card-info-caja">
        <div class="info-item">
            <i class="fas fa-cash-register"></i>
            <div>
                <span>Caja</span>
                <strong><?php echo $arqueo->nombre_caja ?? 'Caja Productos'; ?></strong>
            </div>
        </div>
        <div class="info-item">
            <i class="fas fa-user"></i>
            <div>
                <span>Usuario</span>
                <strong><?php echo $_SESSION['nombre_usuario']; ?></strong>
            </div>
        </div>
        <div class="info-item">
            <i class="fas fa-clock"></i>
            <div>
                <span>Abierta desde</span>
                <strong><?php echo date('d/m/Y H:i', strtotime($arqueo->fecha_inicio)); ?></strong>
            </div>
        </div>
        <div class="info-item">
            <i class="fas fa-dollar-sign"></i>
            <div>
                <span>Monto Inicial</span>
                <strong>$<?php echo number_format($arqueo->monto_inicial, 0, ',', '.'); ?></strong>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="acciones-movimientos">
        <button class="btn-accion ingreso" onclick="mostrarModalIngreso()">
            <i class="fas fa-plus-circle"></i>
            <span>Registrar Ingreso</span>
            <small>Agregar dinero a caja</small>
        </button>

        <button class="btn-accion egreso" onclick="mostrarModalEgreso()">
            <i class="fas fa-minus-circle"></i>
            <span>Registrar Egreso</span>
            <small>Pago a proveedores</small>
        </button>
    </div>

    <!-- Resumen de movimientos -->
    <div class="card-resumen-movs">
        <div class="resumen-item ingreso-total">
            <i class="fas fa-arrow-up"></i>
            <div>
                <span>Total Ingresos</span>
                <strong>$<?php echo number_format($total_ingresos ?? 0, 0, ',', '.'); ?></strong>
            </div>
        </div>
        <div class="resumen-item egreso-total">
            <i class="fas fa-arrow-down"></i>
            <div>
                <span>Total Egresos</span>
                <strong>$<?php echo number_format($total_egresos ?? 0, 0, ',', '.'); ?></strong>
            </div>
        </div>
        <div class="resumen-item saldo-actual">
            <i class="fas fa-wallet"></i>
            <div>
                <span>Saldo Actual</span>
                <strong>$<?php echo number_format($arqueo->monto_inicial + $total_ingresos - $total_egresos, 0, ',', '.'); ?></strong>
            </div>
        </div>
    </div>

    <!-- Lista de movimientos -->
    <div class="card-lista-movimientos">
        <div class="header-lista">
            <h2><i class="fas fa-list"></i> Historial de Movimientos</h2>
            <span class="badge-count"><?php echo count($movimientos); ?> registros</span>
        </div>

        <?php if (empty($movimientos)): ?>
            <div class="sin-movimientos">
                <i class="fas fa-inbox"></i>
                <p>No hay movimientos registrados aún</p>
                <small>Los ingresos y egresos que agregues aparecerán aquí</small>
            </div>
        <?php else: ?>
            <div class="tabla-wrapper">
                <table class="tabla-movimientos">
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
                        <tr class="mov-<?php echo $mov->tipo_movimiento; ?>">
                            <td>
                                <span class="badge-tipo <?php echo $mov->tipo_movimiento; ?>">
                                    <i class="fas fa-<?php echo $mov->tipo_movimiento === 'ingreso' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo ucfirst($mov->tipo_movimiento); ?>
                                </span>
                            </td>
                            <td class="concepto-cell"><?php echo $mov->concepto_movimiento; ?></td>
                            <td><?php echo ucfirst($mov->forma_pago); ?></td>
                            <td class="monto-cell <?php echo $mov->tipo_movimiento; ?>">
                                <?php echo $mov->tipo_movimiento === 'ingreso' ? '+' : '-'; ?>
                                $<?php echo number_format($mov->monto, 0, ',', '.'); ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mov->fecha_movimiento)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ingreso -->
<div id="modal-ingreso" class="modal">
    <div class="modal-content">
        <div class="modal-header ingreso">
            <h2><i class="fas fa-plus-circle"></i> Registrar Ingreso</h2>
            <button onclick="cerrarModalIngreso()" class="btn-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="form-ingreso">
            <div class="modal-body">
                <div class="form-group">
                    <label for="concepto_ingreso">
                        <i class="fas fa-file-alt"></i> Concepto *
                    </label>
                    <input 
                        type="text" 
                        id="concepto_ingreso" 
                        placeholder="Ej: Propina, Devolución, Ajuste..."
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="monto_ingreso">
                        <i class="fas fa-dollar-sign"></i> Monto *
                    </label>
                    <input 
                        type="number" 
                        id="monto_ingreso" 
                        step="0.01" 
                        min="0.01"
                        placeholder="0.00"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="id_tipo_pago_ingreso">
                        <i class="fas fa-credit-card"></i> Forma de Pago *
                    </label>
                    <select id="id_tipo_pago_ingreso" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="cerrarModalIngreso()" class="btn-cancelar">
                    Cancelar
                </button>
                <button type="submit" class="btn-confirmar ingreso">
                    <i class="fas fa-check"></i> Registrar Ingreso
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Egreso -->
<div id="modal-egreso" class="modal">
    <div class="modal-content modal-egreso-grande">
        <div class="modal-header egreso">
            <h2><i class="fas fa-minus-circle"></i> Registrar Egreso</h2>
            <button onclick="cerrarModalEgreso()" class="btn-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="form-egreso">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_proveedor">
                            <i class="fas fa-building"></i> Proveedor *
                        </label>
                        <select id="id_proveedor" required>
                            <option value="">-- Seleccionar Proveedor --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_servicio">
                            <i class="fas fa-cogs"></i> Servicio/Concepto
                        </label>
                        <select id="id_servicio" disabled>
                            <option value="">-- Primero selecciona un proveedor --</option>
                        </select>
                        <small class="text-muted">Opcional: deja en blanco para "Otros"</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_comprobante">
                            <i class="fas fa-receipt"></i> N° Comprobante/Factura
                        </label>
                        <input 
                            type="text" 
                            id="numero_comprobante" 
                            placeholder="Ej: 0001-00001234"
                        >
                    </div>

                    <div class="form-group">
                        <label for="monto_egreso">
                            <i class="fas fa-dollar-sign"></i> Monto * <span id="monto-sugerido"></span>
                        </label>
                        <input 
                            type="number" 
                            id="monto_egreso" 
                            step="0.01" 
                            min="0.01"
                            placeholder="0.00"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="concepto_egreso">
                        <i class="fas fa-file-alt"></i> Concepto/Descripción *
                    </label>
                    <textarea 
                        id="concepto_egreso" 
                        rows="2"
                        placeholder="Describe el motivo del egreso..."
                        required
                    ></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_tipo_pago_egreso">
                            <i class="fas fa-credit-card"></i> Forma de Pago *
                        </label>
                        <select id="id_tipo_pago_egreso" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observaciones_egreso">
                        <i class="fas fa-sticky-note"></i> Observaciones
                    </label>
                    <textarea 
                        id="observaciones_egreso" 
                        rows="2"
                        placeholder="Información adicional (opcional)..."
                    ></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="cerrarModalEgreso()" class="btn-cancelar">
                    Cancelar
                </button>
                <button type="submit" class="btn-confirmar egreso">
                    <i class="fas fa-check"></i> Registrar Egreso
                </button>
            </div>
        </form>
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

.movimientos-container {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--color-fondo-oscuro) 0%, var(--color-fondo-gris) 100%);
    padding: 2rem;
}

.header-movimientos {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    color: var(--color-texto-claro);
}

.header-movimientos h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-movimientos p {
    margin: 0;
    opacity: 0.8;
}

.btn-volver {
    background: var(--color-principal);
    color: var(--color-texto-claro);
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-volver:hover {
    background: var(--color-principal-hover);
    transform: translateY(-2px);
}

.card-info-caja,
.card-resumen-movs,
.card-lista-movimientos {
    background: var(--color-fondo-gris);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.card-info-caja {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.info-item i {
    font-size: 2rem;
    color: var(--color-principal);
}

.info-item span {
    display: block;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
}

.info-item strong {
    display: block;
    font-size: 1.1rem;
    color: var(--color-texto-claro);
}

.acciones-movimientos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.btn-accion {
    background: var(--color-fondo-gris);
    border: 2px solid var(--color-hover-gris);
    border-radius: 15px;
    padding: 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    text-align: center;
    color: var(--color-texto-claro);
}

.btn-accion:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    border-color: var(--color-principal);
}

.btn-accion i {
    font-size: 3rem;
    color: var(--color-principal);
}

.btn-accion span {
    font-size: 1.2rem;
    font-weight: bold;
}

.btn-accion small {
    font-size: 0.85rem;
    opacity: 0.8;
}

.card-resumen-movs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.resumen-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 15px;
    background: var(--color-fondo-oscuro);
    border: 2px solid var(--color-hover-gris);
}

.resumen-item i {
    font-size: 2rem;
}

.resumen-item.ingreso-total i { color: #22c55e; }
.resumen-item.egreso-total i { color: #ef4444; }
.resumen-item.saldo-actual i { color: var(--color-principal); }

.resumen-item span {
    display: block;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

.resumen-item strong {
    display: block;
    font-size: 1.5rem;
    color: var(--color-texto-claro);
}

.header-lista {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    color: var(--color-texto-claro);
}

.header-lista h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.badge-count {
    background: var(--color-principal);
    color: var(--color-texto-claro);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.sin-movimientos {
    text-align: center;
    padding: 3rem;
    color: rgba(255, 255, 255, 0.5);
}

.sin-movimientos i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.tabla-wrapper {
    overflow-x: auto;
}

.tabla-movimientos {
    width: 100%;
    border-collapse: collapse;
}

.tabla-movimientos thead th {
    background: var(--color-fondo-oscuro);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--color-texto-claro);
    border-bottom: 2px solid var(--color-principal);
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

.monto-cell {
    font-weight: bold;
    font-size: 1.1rem;
}

.monto-cell.ingreso { color: #6ee7b7; }
.monto-cell.egreso { color: #fca5a5; }

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
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
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-egreso-grande {
    max-width: 700px !important;
}

.modal-header {
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--color-texto-claro);
    background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
}

.modal-header h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: var(--color-texto-claro);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.modal-body {
    padding: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--color-texto-claro);
    margin-bottom: 0.5rem;
}

.form-group label i {
    color: var(--color-principal);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--color-hover-gris);
    border-radius: 10px;
    font-size: 1rem;
    background: var(--color-fondo-oscuro);
    color: var(--color-texto-claro);
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-principal);
    box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.1);
}

.form-group select:disabled {
    background: var(--color-hover-gris);
    cursor: not-allowed;
}

.text-muted {
    display: block;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.6);
    margin-top: 0.25rem;
}

#monto-sugerido {
    font-size: 0.9rem;
    color: #10b981;
    font-weight: normal;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    padding: 0 2rem 2rem;
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
    background: var(--color-hover-gris);
    color: var(--color-texto-claro);
}

.btn-cancelar:hover {
    background: var(--color-fondo-oscuro);
}

.btn-confirmar {
    background: linear-gradient(135deg, var(--color-principal) 0%, var(--color-principal-hover) 100%);
    color: var(--color-texto-claro);
}

.btn-confirmar:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237,133,15,0.4);
}

@media (max-width: 768px) {
    .movimientos-container {
        padding: 1rem;
    }

    .header-movimientos {
        flex-direction: column;
        gap: 1rem;
    }

    .card-info-caja,
    .card-resumen-movs {
        grid-template-columns: 1fr;
    }

    .acciones-movimientos {
        grid-template-columns: 1fr;
    }

    .modal-egreso-grande {
        max-width: 95% !important;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let proveedores = [];
let servicios = [];
let formasPago = [];

async function cargarFormasPago() {
    try {
        const response = await fetch('/api/movimientos/formas-pago');
        const data = await response.json();
        
        if (data.ok) {
            formasPago = data.formas_pago;
            
            const selectIngreso = document.getElementById('id_tipo_pago_ingreso');
            selectIngreso.innerHTML = '<option value="">-- Seleccionar --</option>';
            data.formas_pago.forEach(fp => {
                const option = document.createElement('option');
                option.value = fp.id_tipo_pago;
                option.textContent = fp.descripcion;
                selectIngreso.appendChild(option);
            });
            
            const selectEgreso = document.getElementById('id_tipo_pago_egreso');
            selectEgreso.innerHTML = '<option value="">-- Seleccionar --</option>';
            data.formas_pago.forEach(fp => {
                const option = document.createElement('option');
                option.value = fp.id_tipo_pago;
                option.textContent = fp.descripcion;
                selectEgreso.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando formas de pago:', error);
        mostrarAlerta('Error al cargar formas de pago', 'error');
    }
}

async function cargarProveedores() {
    try {
        const response = await fetch('/api/movimientos/proveedores');
        const data = await response.json();
        
        if (data.ok) {
            proveedores = data.proveedores;
            const select = document.getElementById('id_proveedor');
            
            select.innerHTML = '<option value="">-- Seleccionar Proveedor --</option>';
            
            data.proveedores.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov.id_proveedor;
                option.textContent = prov.nombre_comercial || prov.razon_social;
                select.appendChild(option);
            });
        } else {
            mostrarAlerta('Error cargando proveedores: ' + data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error cargando proveedores:', error);
        mostrarAlerta('Error de conexión al cargar proveedores', 'error');
    }
}

async function cargarServicios(idProveedor) {
    const selectServicio = document.getElementById('id_servicio');
    const inputMonto = document.getElementById('monto_egreso');
    const montoSugerido = document.getElementById('monto-sugerido');
    
    if (!idProveedor) {
        selectServicio.disabled = true;
        selectServicio.innerHTML = '<option value="">-- Primero selecciona un proveedor --</option>';
        montoSugerido.textContent = '';
        return;
    }
    
    try {
        const response = await fetch(`/api/movimientos/servicios?id_proveedor=${idProveedor}`);
        const data = await response.json();
        
        if (data.ok) {
            servicios = data.servicios;
            selectServicio.disabled = false;
            selectServicio.innerHTML = '<option value="">-- Seleccionar Servicio (Opcional) --</option>';
            
            data.servicios.forEach(serv => {
                const option = document.createElement('option');
                option.value = serv.id_servicio;
                option.textContent = serv.nombre_servicio;
                option.dataset.monto = serv.monto_base;
                option.dataset.variable = serv.tiene_monto_variable;
                selectServicio.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando servicios:', error);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    cargarFormasPago();
    
    const selectServicio = document.getElementById('id_servicio');
    const inputMonto = document.getElementById('monto_egreso');
    const montoSugerido = document.getElementById('monto-sugerido');
    
    selectServicio.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const monto = parseFloat(option.dataset.monto || 0);
        const variable = parseInt(option.dataset.variable || 0);
        
        if (monto > 0 && !variable) {
            inputMonto.value = monto;
            montoSugerido.textContent = `(Sugerido: $${monto.toLocaleString('es-AR')})`;
        } else if (variable) {
            montoSugerido.textContent = '(Monto variable)';
        } else {
            montoSugerido.textContent = '';
        }
    });
    
    document.getElementById('id_proveedor').addEventListener('change', function() {
        cargarServicios(this.value);
    });
});

async function mostrarModalIngreso() {
    await cargarFormasPago();
    document.getElementById('modal-ingreso').classList.add('active');
}

function cerrarModalIngreso() {
    document.getElementById('modal-ingreso').classList.remove('active');
    document.getElementById('form-ingreso').reset();
}

async function mostrarModalEgreso() {
    await cargarFormasPago();
    await cargarProveedores();
    document.getElementById('modal-egreso').classList.add('active');
}

function cerrarModalEgreso() {
    document.getElementById('modal-egreso').classList.remove('active');
    document.getElementById('form-egreso').reset();
    document.getElementById('id_servicio').disabled = true;
    document.getElementById('monto-sugerido').textContent = '';
}

document.getElementById('form-ingreso').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const concepto = document.getElementById('concepto_ingreso').value;
    const monto = parseFloat(document.getElementById('monto_ingreso').value);
    const idTipoPago = parseInt(document.getElementById('id_tipo_pago_ingreso').value);
    
    try {
        const response = await fetch('/vendedorproductos/movimientos/registrar-ingreso', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ concepto, monto, id_tipo_pago: idTipoPago })
        });
        
        const data = await response.json();
        
        if (data.ok) {
            mostrarAlerta(data.mensaje, 'exito');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarAlerta(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión', 'error');
    }
});

document.getElementById('form-egreso').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const idProveedor = parseInt(document.getElementById('id_proveedor').value);
    const idServicio = document.getElementById('id_servicio').value ? 
                       parseInt(document.getElementById('id_servicio').value) : null;
    const numeroComprobante = document.getElementById('numero_comprobante').value;
    const concepto = document.getElementById('concepto_egreso').value;
    const monto = parseFloat(document.getElementById('monto_egreso').value);
    const idTipoPago = parseInt(document.getElementById('id_tipo_pago_egreso').value);
    const observaciones = document.getElementById('observaciones_egreso').value;
    
    if (!idProveedor || monto <= 0) {
        mostrarAlerta('Proveedor y monto son obligatorios', 'error');
        return;
    }
    
    try {
        const response = await fetch('/vendedorproductos/movimientos/registrar-egreso', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_proveedor: idProveedor,
                id_servicio: idServicio,
                numero_comprobante: numeroComprobante,
                concepto,
                monto,
                id_tipo_pago: idTipoPago,
                observaciones
            })
        });
        
        const data = await response.json();
        
        if (data.ok) {
            mostrarAlerta(data.mensaje, 'exito');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarAlerta(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión', 'error');
    }
});
</script>