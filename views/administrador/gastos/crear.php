<div class="form-container" id="pagina-registrar-gasto">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Registrar Gasto</h2>

    <?php if (empty($cajasAbiertas)): ?>
        <div class="alerta error">
            <p> No hay cajas abiertas. Por favor, abre una caja antes de registrar gastos.</p>
        </div>
    <?php else: ?>

        <form method="POST"
            action="/administrador/gastos/guardar"
            data-fetch="true"
            data-alerta=".contenedor-alertas"
            data-redirigir="/administrador/gastos/listado">

            <!-- SELECTOR DE CAJA (solo si hay múltiples cajas abiertas) -->
            <?php if (count($cajasAbiertas) > 1): ?>
                <div class="campo destacado">
                    <label for="rela_arqueo_caja">Caja de Registro *</label>
                    <select name="rela_arqueo_caja" id="rela_arqueo_caja" required>
                        <option value="">-- Seleccionar caja --</option>
                        <?php foreach ($cajasAbiertas as $caja): ?>
                            <option value="<?= $caja->id_arqueo_caja ?>">
                                Caja #<?= $caja->numero_caja ?> - <?= htmlspecialchars($caja->nombre_caja) ?>
                                (Usuario: <?= htmlspecialchars($caja->nombre_usuario) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #4299e1; font-size: 11px;">
                        Selecciona la caja donde se registrará este gasto
                    </small>
                </div>
            <?php else: ?>
                <input type="hidden" name="rela_arqueo_caja" value="<?= $cajasAbiertas[0]->id_arqueo_caja ?>">
                <div class="campo info-caja-activa">
                    <p>
                        <strong>Caja activa:</strong>
                        Caja #<?= $cajasAbiertas[0]->numero_caja ?> - <?= htmlspecialchars($cajasAbiertas[0]->nombre_caja) ?>
                        (<?= htmlspecialchars($cajasAbiertas[0]->nombre_usuario) ?>)
                    </p>
                </div>
            <?php endif; ?>

            <div class="campo">
                <label for="concepto">Concepto *</label>
                <input type="text" name="concepto" id="concepto" placeholder="Ej: Pago de luz del mes" required>
            </div>

            <div class="campo">
                <label for="monto">Monto *</label>
                <input type="number" name="monto" id="monto" placeholder="0.00" step="0.01" min="0.01" required>
            </div>

            <div class="campo">
                <label for="tipo_egreso">Tipo de Egreso *</label>
                <select name="tipo_egreso" id="tipo_egreso" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="operativo">Operativo</option>
                    <option value="administrativo">Administrativo</option>
                    <option value="comercial">Comercial</option>
                </select>
            </div>

            <div class="campo">
                <label for="forma_pago">Forma de Pago *</label>
                <select name="forma_pago" id="forma_pago" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>

            <div class="campo">
                <label for="numero_comprobante">Número de Comprobante</label>
                <input type="text" name="numero_comprobante" id="numero_comprobante" placeholder="Ej: 0001-00001234" maxlength="50">
            </div>

            <div class="campo">
                <label for="fecha_egreso">Fecha del Gasto *</label>
                <input type="datetime-local" name="fecha_egreso" id="fecha_egreso" value="<?= date('Y-m-d\TH:i') ?>" required>
            </div>

            <div class="campo">
                <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento">
                <small style="color: #a0aec0; font-size: 11px;">Solo si aplica (pagos diferidos)</small>
            </div>

            <div class="campo">
                <label for="estado_egreso">Estado:</label>
                <select name="estado_egreso" id="estado_egreso" required>
                    <option value="registrado" selected>Registrado</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="pagado">Pagado</option>
                </select>
            </div>

            <div class="campo">
                <label for="rela_proveedor">Proveedor</label>
                <select name="rela_proveedor" id="rela_proveedor">
                    <option value="">-- Sin proveedor --</option>
                    <?php if (!empty($proveedores)): ?>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor->id_proveedor ?>">
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
                            <option value="<?= $servicio->id_servicio ?>" data-monto="<?= $servicio->monto_base ?>">
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
                <textarea name="observaciones" id="observaciones" placeholder="Información adicional sobre el gasto..." rows="4"></textarea>
            </div>

            <input type="submit" class="boton" value="Registrar Gasto">
            <div class="contenedor-alertas"></div>
            <div class="acciones">
                <a href="/administrador/gastos/listado">Volver</a>
            </div>
        </form>

    <?php endif; ?>
</div>

<style>
    #pagina-registrar-gasto.form-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
        border: 2px solid #ed850f;
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 650px;
        margin: 40px auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    #pagina-registrar-gasto .form-logo {
        width: 90px;
        display: block;
        margin: 0 auto 15px;
    }

    #pagina-registrar-gasto h2 {
        color: #ed850f !important;
        text-align: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
        background: none !important;
    }

    #pagina-registrar-gasto .alerta.error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        border-radius: 10px;
        padding: 16px 20px;
        color: #fca5a5;
        text-align: center;
        font-weight: 600;
    }

    #pagina-registrar-gasto .campo {
        margin-bottom: 1.2rem;
        text-align: left;
    }

    #pagina-registrar-gasto .campo label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #a0aec0;
        font-size: 14px;
    }

    #pagina-registrar-gasto .campo input,
    #pagina-registrar-gasto .campo select,
    #pagina-registrar-gasto .campo textarea {
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

    #pagina-registrar-gasto .campo textarea {
        resize: vertical;
    }

    #pagina-registrar-gasto .campo input::placeholder,
    #pagina-registrar-gasto .campo textarea::placeholder {
        color: #6b7280;
    }

    #pagina-registrar-gasto .campo input:focus,
    #pagina-registrar-gasto .campo select:focus,
    #pagina-registrar-gasto .campo textarea:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    #pagina-registrar-gasto .campo select option {
        background: #2d3748;
        color: #e2e8f0;
    }

    #pagina-registrar-gasto .campo small {
        display: block;
        margin-top: 6px;
        font-size: 12px !important;
        color: #a0aec0 !important;
    }

    #pagina-registrar-gasto .campo.destacado {
        background: rgba(237, 133, 15, 0.08);
        border: 1px solid #ed850f;
        border-radius: 10px;
        padding: 15px;
    }

    #pagina-registrar-gasto .campo.destacado label {
        color: #ed850f;
    }

    #pagina-registrar-gasto .info-caja-activa {
        background: rgba(34, 197, 94, 0.1) !important;
        border: 1px solid #22c55e;
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 15px;
    }

    #pagina-registrar-gasto .info-caja-activa p {
        color: #86efac !important;
        margin: 0 !important;
        font-size: 14px;
    }

    #pagina-registrar-gasto .boton {
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

    #pagina-registrar-gasto .boton:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    #pagina-registrar-gasto .acciones {
        margin-top: 20px;
        text-align: center;
    }

    #pagina-registrar-gasto .acciones a {
        display: inline-block;
        color: #a0aec0;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    #pagina-registrar-gasto .acciones a:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    /* Ensanchar la tarjeta para que entren 2 columnas cómodas */
    #pagina-registrar-gasto.form-container {
        max-width: 780px !important;
    }

    /* Grid de 2 columnas en el formulario */
    #pagina-registrar-gasto form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 18px;
    }

    /* Elementos que ocupan el ancho completo (no se emparejan) */
    #pagina-registrar-gasto .campo.destacado,
    #pagina-registrar-gasto .info-caja-activa,
    #pagina-registrar-gasto .campo.full-ancho,
    #pagina-registrar-gasto .alerta.error,
    #pagina-registrar-gasto .boton,
    #pagina-registrar-gasto .contenedor-alertas,
    #pagina-registrar-gasto .acciones {
        grid-column: 1 / -1;
    }

    /* En mobile, volver a una sola columna */
    @media (max-width: 600px) {
        #pagina-registrar-gasto form {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        #pagina-registrar-gasto.form-container {
            padding: 1.5rem;
            margin: 20px auto;
            border-radius: 16px;
        }

        #pagina-registrar-gasto h2 {
            font-size: 1.3rem;
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
</script>

<script type="module" src="/assets/js/formularios.js"></script>