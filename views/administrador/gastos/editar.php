<div class="form-container" id="pagina-editar-gasto">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Gasto #<?= $gasto->id_egreso ?></h2>

    <form method="POST"
        action="/administrador/gastos/actualizar"
        data-fetch="true"
        data-alerta=".contenedor-alertas"
        data-redirigir="/administrador/gastos/listado">

        <input type="hidden" name="id_egreso" value="<?= $gasto->id_egreso ?>">

        <!-- ALERTA SI NO HAY CAJAS ABIERTAS -->
        <?php if (empty($cajasAbiertas)): ?>
            <div class="alerta error">
                <p>⚠️ <strong>No hay cajas abiertas.</strong></p>
                <p style="margin: 10px 0 0 0; font-size: 14px;">
                    No podrás cambiar el estado a "Pagado" sin una caja abierta.
                    <a href="/administrador/cajas/abrir" style="color: #fff; text-decoration: underline;">Abrir una caja</a>
                </p>
            </div>
        <?php endif; ?>

        <!-- SELECTOR DE CAJA -->
        <?php if (!empty($cajasAbiertas)): ?>
            <?php if (count($cajasAbiertas) > 1): ?>
                <div class="campo destacado">
                    <label for="rela_arqueo_caja">Caja de Registro *</label>
                    <select name="rela_arqueo_caja" id="rela_arqueo_caja" required>
                        <option value="">-- Seleccionar caja --</option>
                        <?php foreach ($cajasAbiertas as $caja): ?>
                            <option value="<?= $caja->id_arqueo_caja ?>" <?= $gasto->rela_arqueo_caja == $caja->id_arqueo_caja ? 'selected' : '' ?>>
                                Caja #<?= $caja->numero_caja ?> - <?= htmlspecialchars($caja->nombre_caja) ?>
                                (Usuario: <?= htmlspecialchars($caja->nombre_usuario) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #4299e1; font-size: 11px;">
                        Puedes cambiar la caja asignada a este gasto
                    </small>
                </div>
            <?php else: ?>
                <input type="hidden" name="rela_arqueo_caja" value="<?= $cajasAbiertas[0]->id_arqueo_caja ?>">
                <div class="campo info-caja-activa">
                    <p>
                        📦 <strong>Caja activa:</strong>
                        Caja #<?= $cajasAbiertas[0]->numero_caja ?> - <?= htmlspecialchars($cajasAbiertas[0]->nombre_caja) ?>
                        (<?= htmlspecialchars($cajasAbiertas[0]->nombre_usuario) ?>)
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Si no hay cajas abiertas, mantener la caja original (si existe) -->
            <?php if ($gasto->rela_arqueo_caja): ?>
                <input type="hidden" name="rela_arqueo_caja" value="<?= $gasto->rela_arqueo_caja ?>">
                <div class="campo info-caja-advertencia">
                    <p>
                        📦 <strong>Caja original:</strong> ID Arqueo #<?= $gasto->rela_arqueo_caja ?>
                    </p>
                    <small>Esta caja está cerrada. No se puede cambiar el estado a "Pagado".</small>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="campo">
            <label for="concepto">Concepto *</label>
            <input type="text" name="concepto" id="concepto" value="<?= htmlspecialchars($gasto->concepto) ?>" required>
        </div>

        <div class="campo">
            <label for="monto">Monto *</label>
            <input type="number" name="monto" id="monto" value="<?= $gasto->monto ?>" step="0.01" min="0.01" required>
        </div>

        <div class="campo">
            <label for="tipo_egreso">Tipo de Egreso *</label>
            <select name="tipo_egreso" id="tipo_egreso" required>
                <option value="">-- Seleccionar --</option>
                <option value="operativo" <?= $gasto->tipo_egreso == 'operativo' ? 'selected' : '' ?>>Operativo</option>
                <option value="administrativo" <?= $gasto->tipo_egreso == 'administrativo' ? 'selected' : '' ?>>Administrativo</option>
                <option value="comercial" <?= $gasto->tipo_egreso == 'comercial' ? 'selected' : '' ?>>Comercial</option>
            </select>
        </div>

        <div class="campo">
            <label for="forma_pago">Forma de Pago *</label>
            <select name="forma_pago" id="forma_pago" required>
                <option value="">-- Seleccionar --</option>
                <option value="efectivo" <?= $gasto->forma_pago == 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                <option value="transferencia" <?= $gasto->forma_pago == 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                <option value="tarjeta" <?= $gasto->forma_pago == 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                <option value="cheque" <?= $gasto->forma_pago == 'cheque' ? 'selected' : '' ?>>Cheque</option>
            </select>
        </div>

        <div class="campo">
            <label for="numero_comprobante">Número de Comprobante</label>
            <input type="text" name="numero_comprobante" id="numero_comprobante" value="<?= htmlspecialchars($gasto->numero_comprobante) ?>" maxlength="50">
        </div>

        <div class="campo">
            <label for="fecha_egreso">Fecha del Gasto *</label>
            <input type="datetime-local" name="fecha_egreso" id="fecha_egreso" value="<?= date('Y-m-d\TH:i', strtotime($gasto->fecha_egreso)) ?>" required>
        </div>

        <div class="campo">
            <label for="fecha_vencimiento">Fecha de Vencimiento</label>
            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="<?= $gasto->fecha_vencimiento ? date('Y-m-d', strtotime($gasto->fecha_vencimiento)) : '' ?>">
            <small style="color: #a0aec0; font-size: 11px;">Solo si aplica (pagos diferidos)</small>
        </div>

        <div class="campo destacado">
            <label for="estado_egreso">Estado del Gasto *</label>
            <select name="estado_egreso" id="estado_egreso" required>
                <option value="registrado" <?= $gasto->estado_egreso == 'registrado' ? 'selected' : '' ?>>Registrado</option>
                <option value="aprobado" <?= $gasto->estado_egreso == 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                <option value="pagado" <?= $gasto->estado_egreso == 'pagado' ? 'selected' : '' ?>>Pagado</option>
                <option value="anulado" <?= $gasto->estado_egreso == 'anulado' ? 'selected' : '' ?>>Anulado</option>
            </select>
            <small style="color: #4299e1; font-size: 11px;">
                ⚠️ Al cambiar a "Pagado" se registrará el movimiento en caja automáticamente
            </small>
        </div>

        <div class="campo">
            <label for="rela_proveedor">Proveedor</label>
            <select name="rela_proveedor" id="rela_proveedor">
                <option value="">-- Sin proveedor --</option>
                <?php if (!empty($proveedores)): ?>
                    <?php foreach ($proveedores as $proveedor): ?>
                        <option value="<?= $proveedor->id_proveedor ?>" <?= $gasto->rela_proveedor == $proveedor->id_proveedor ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proveedor->razon_social) ?>
                            (<?= s($proveedor->nombre_comercial) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small style="color: #a0aec0; font-size: 11px;">Opcional - Solo si el gasto está relacionado a un proveedor</small>
        </div>

        <div class="campo">
            <label for="rela_servicio">Servicio</label>
            <select name="rela_servicio" id="rela_servicio">
                <option value="">-- Sin servicio --</option>
                <?php if (!empty($servicios)): ?>
                    <?php foreach ($servicios as $servicio): ?>
                        <option value="<?= $servicio->id_servicio ?>"
                            data-monto="<?= $servicio->monto_base ?>"
                            <?= $gasto->rela_servicio == $servicio->id_servicio ? 'selected' : '' ?>>
                            <?= htmlspecialchars($servicio->nombre_servicio) ?>
                            - <?= htmlspecialchars($servicio->razon_social) ?>
                            <?php if ($servicio->monto_base > 0): ?>
                                ($<?= number_format($servicio->monto_base, 2, ',', '.') ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small style="color: #a0aec0; font-size: 11px;">Opcional - Al seleccionar un servicio, el monto se cargará automáticamente</small>
        </div>

        <div class="campo full-ancho">
            <label for="observaciones">Observaciones</label>
            <textarea name="observaciones" id="observaciones" rows="4" placeholder="Información adicional sobre el gasto..."><?= htmlspecialchars($gasto->observaciones) ?></textarea>
        </div>

        <input type="submit" class="boton" value="Actualizar Gasto">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/gastos/listado">Volver al listado</a>
        </div>
    </form>
</div>

<style>
    #pagina-editar-gasto.form-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
        border: 2px solid #ed850f;
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 780px;
        margin: 40px auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    #pagina-editar-gasto .form-logo {
        width: 90px;
        display: block;
        margin: 0 auto 15px;
        grid-column: 1 / -1;
    }

    #pagina-editar-gasto h2 {
        color: #ed850f !important;
        text-align: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
        background: none !important;
        grid-column: 1 / -1;
    }

    #pagina-editar-gasto form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 18px;
    }

    #pagina-editar-gasto .alerta.error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        border-radius: 10px;
        padding: 16px 20px;
        color: #fca5a5;
        grid-column: 1 / -1;
    }

    #pagina-editar-gasto .alerta.error a {
        color: #fca5a5 !important;
        text-decoration: underline;
    }

    #pagina-editar-gasto .campo {
        margin-bottom: 1.2rem;
        text-align: left;
    }

    #pagina-editar-gasto .campo label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #a0aec0;
        font-size: 14px;
    }

    #pagina-editar-gasto .campo input,
    #pagina-editar-gasto .campo select,
    #pagina-editar-gasto .campo textarea {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        background: #2d3748;
        color: #e2e8f0;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }

    #pagina-editar-gasto .campo textarea {
        resize: vertical;
    }

    #pagina-editar-gasto .campo input::placeholder,
    #pagina-editar-gasto .campo textarea::placeholder {
        color: #6b7280;
    }

    #pagina-editar-gasto .campo input:focus,
    #pagina-editar-gasto .campo select:focus,
    #pagina-editar-gasto .campo textarea:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    #pagina-editar-gasto .campo select option {
        background: #2d3748;
        color: #e2e8f0;
    }

    #pagina-editar-gasto .campo small {
        display: block;
        margin-top: 6px;
        font-size: 12px !important;
        color: #a0aec0 !important;
    }

    #pagina-editar-gasto .campo.destacado {
        grid-column: 1 / -1;
        background: rgba(237, 133, 15, 0.08);
        border: 1px solid #ed850f;
        border-radius: 10px;
        padding: 15px;
    }

    #pagina-editar-gasto .campo.destacado label {
        color: #ed850f;
    }

    #pagina-editar-gasto .campo.destacado small {
        color: #f7931e !important;
    }

    #pagina-editar-gasto .campo.full-ancho {
        grid-column: 1 / -1;
    }

    #pagina-editar-gasto .info-caja-activa {
        grid-column: 1 / -1;
        background: rgba(34, 197, 94, 0.1) !important;
        border: 1px solid #22c55e;
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 15px;
    }

    #pagina-editar-gasto .info-caja-activa p {
        color: #86efac !important;
        margin: 0 !important;
        font-size: 14px;
    }

    #pagina-editar-gasto .info-caja-advertencia {
        grid-column: 1 / -1;
        background: rgba(245, 158, 11, 0.1) !important;
        border: 1px solid #f59e0b;
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 15px;
    }

    #pagina-editar-gasto .info-caja-advertencia p {
        color: #fcd34d !important;
        margin: 0 0 4px 0 !important;
        font-size: 14px;
    }

    #pagina-editar-gasto .info-caja-advertencia small {
        color: #fbbf24 !important;
    }

    #pagina-editar-gasto .boton {
        grid-column: 1 / -1;
        width: 100%;
        padding: 13px 15px;
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 6px 18px rgba(237, 133, 15, 0.35);
        margin-top: 10px;
    }

    #pagina-editar-gasto .boton:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    #pagina-editar-gasto .contenedor-alertas {
        grid-column: 1 / -1;
    }

    #pagina-editar-gasto .acciones {
        grid-column: 1 / -1;
        margin-top: 20px;
        text-align: center;
    }

    #pagina-editar-gasto .acciones a {
        display: inline-block;
        color: #a0aec0;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    #pagina-editar-gasto .acciones a:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    /* Modal de confirmación */
    .modal-confirmacion-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    .modal-confirmacion-box {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border: 2px solid #ed850f;
        border-radius: 15px;
        padding: 30px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .modal-confirmacion-box p {
        color: #e2e8f0;
        font-size: 16px;
        margin-bottom: 25px;
        line-height: 1.5;
    }

    .modal-confirmacion-botones {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-confirmar-modal,
    .btn-cancelar-modal {
        padding: 10px 24px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-confirmar-modal {
        background: linear-gradient(135deg, #ed850f, #f7931e);
        color: white;
    }

    .btn-confirmar-modal:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.4);
    }

    .btn-cancelar-modal {
        background: transparent;
        color: #e2e8f0;
        border: 2px solid #4a5568;
    }

    .btn-cancelar-modal:hover {
        border-color: #ef4444;
        color: #ef4444;
    }

    @media (max-width: 600px) {
        #pagina-editar-gasto.form-container {
            padding: 1.5rem;
            margin: 20px auto;
            border-radius: 16px;
        }

        #pagina-editar-gasto h2 {
            font-size: 1.3rem;
        }

        #pagina-editar-gasto form {
            grid-template-columns: 1fr;
        }

        .modal-confirmacion-box {
            padding: 22px 18px;
        }

        .modal-confirmacion-botones {
            flex-direction: column;
        }

        .btn-confirmar-modal,
        .btn-cancelar-modal {
            width: 100%;
        }
    }
</style>

<script>
    // Auto-completar monto al seleccionar servicio
    document.getElementById('rela_servicio')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const monto = selectedOption.getAttribute('data-monto');
        const montoInput = document.getElementById('monto');

        if (monto && parseFloat(monto) > 0) {
            montoInput.value = monto;
        }
    });

    // Sincronizar proveedor cuando se selecciona un servicio
    document.getElementById('rela_servicio')?.addEventListener('change', function() {
        if (this.value) {
            const servicioText = this.options[this.selectedIndex].text;
            const proveedorSelect = document.getElementById('rela_proveedor');

            for (let i = 0; i < proveedorSelect.options.length; i++) {
                if (servicioText.includes(proveedorSelect.options[i].text.split('(')[0].trim())) {
                    proveedorSelect.value = proveedorSelect.options[i].value;
                    break;
                }
            }
        }
    });

    // Alerta al cambiar a "pagado"
    const estadoAnterior = '<?= $gasto->estado_egreso ?>';
    const hayCajasAbiertas = <?= !empty($cajasAbiertas) ? 'true' : 'false' ?>;
    const estadoSelect = document.getElementById('estado_egreso');

    // Deshabilitar opción "pagado" si no hay cajas abiertas
    if (!hayCajasAbiertas && estadoSelect) {
        const opcionPagado = Array.from(estadoSelect.options).find(opt => opt.value === 'pagado');
        if (opcionPagado && estadoAnterior !== 'pagado') {
            opcionPagado.disabled = true;
            opcionPagado.text = 'Pagado (requiere caja abierta)';
        }
    }

    estadoSelect?.addEventListener('change', async function() {
        // Prevenir cambio a "pagado" si no hay cajas
        if (this.value === 'pagado' && !hayCajasAbiertas && estadoAnterior !== 'pagado') {
            mostrarAlerta('No hay cajas abiertas. No puedes cambiar el estado a "Pagado" sin una caja abierta.', 'error');
            this.value = estadoAnterior;
            return;
        }

        // Confirmación al cambiar a "pagado"
        if (this.value === 'pagado' && estadoAnterior !== 'pagado') {
            const confirmado = await confirmarAccionPersonalizada(
                'Al cambiar el estado a "Pagado", se registrará automáticamente el movimiento en caja. ¿Deseás continuar?'
            );
            if (!confirmado) {
                this.value = estadoAnterior;
            }
        }
    });

    function confirmarAccionPersonalizada(mensaje) {
        return new Promise((resolve) => {
            let overlay = document.getElementById('modalConfirmacionOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'modalConfirmacionOverlay';
                overlay.className = 'modal-confirmacion-overlay';
                overlay.innerHTML = `
                <div class="modal-confirmacion-box">
                    <p id="modalConfirmacionMensaje"></p>
                    <div class="modal-confirmacion-botones">
                        <button id="modalConfirmacionAceptar" class="btn-confirmar-modal">Confirmar</button>
                        <button id="modalConfirmacionCancelar" class="btn-cancelar-modal">Cancelar</button>
                    </div>
                </div>
            `;
                document.body.appendChild(overlay);
            }

            document.getElementById('modalConfirmacionMensaje').textContent = mensaje;
            overlay.style.display = 'flex';

            const btnAceptar = document.getElementById('modalConfirmacionAceptar');
            const btnCancelar = document.getElementById('modalConfirmacionCancelar');

            function limpiar(resultado) {
                overlay.style.display = 'none';
                btnAceptar.removeEventListener('click', onAceptar);
                btnCancelar.removeEventListener('click', onCancelar);
                resolve(resultado);
            }

            function onAceptar() {
                limpiar(true);
            }

            function onCancelar() {
                limpiar(false);
            }

            btnAceptar.addEventListener('click', onAceptar);
            btnCancelar.addEventListener('click', onCancelar);
        });
    }
</script>

<script type="module" src="/assets/js/formularios.js"></script>