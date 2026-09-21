<div class="form-container" id="pagina-editar-servicio">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Servicio</h2>
    <form method="POST"
        action="/administrador/servicios/actualizar"
        data-fetch="true"
        data-alerta=".contenedor-alertas"
        data-redirigir="/administrador/servicios/listado">

        <input type="hidden" name="id_servicio" value="<?= $servicio->id_servicio ?>">

        <div class="campo">
            <label for="rela_proveedor">Proveedor *</label>
            <select name="rela_proveedor" id="rela_proveedor" required>
                <option value="">-- Seleccionar Proveedor --</option>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?= $proveedor['id_proveedor'] ?>"
                        <?= $servicio->rela_proveedor == $proveedor['id_proveedor'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proveedor['razon_social']) ?> - <?= htmlspecialchars($proveedor['nombre_comercial']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="nombre_servicio">Nombre del Servicio *</label>
            <input type="text" name="nombre_servicio" id="nombre_servicio" value="<?= $servicio->nombre_servicio ?>" maxlength="150" required>
        </div>

        <div class="campo">
            <label for="codigo_servicio">Código del Servicio</label>
            <input type="text" name="codigo_servicio" id="codigo_servicio" value="<?= $servicio->codigo_servicio ?>" maxlength="50">
            <small style="color: #a0aec0; font-size: 11px;">Opcional - Código único interno</small>
        </div>

        <div class="campo">
            <label for="categoria_servicio">Categoría *</label>
            <select name="categoria_servicio" id="categoria_servicio" required>
                <option value="">-- Seleccionar --</option>
                <option value="licencia_pelicula" <?= $servicio->categoria_servicio == 'licencia_pelicula' ? 'selected' : '' ?>>Licencia de Película</option>
                <option value="distribucion" <?= $servicio->categoria_servicio == 'distribucion' ? 'selected' : '' ?>>Distribución</option>
                <option value="agua" <?= $servicio->categoria_servicio == 'agua' ? 'selected' : '' ?>>Agua</option>
                <option value="luz" <?= $servicio->categoria_servicio == 'luz' ? 'selected' : '' ?>>Luz</option>
                <option value="gas" <?= $servicio->categoria_servicio == 'gas' ? 'selected' : '' ?>>Gas</option>
                <option value="internet" <?= $servicio->categoria_servicio == 'internet' ? 'selected' : '' ?>>Internet</option>
                <option value="telefonia" <?= $servicio->categoria_servicio == 'telefonia' ? 'selected' : '' ?>>Telefonía</option>
                <option value="limpieza" <?= $servicio->categoria_servicio == 'limpieza' ? 'selected' : '' ?>>Limpieza</option>
                <option value="mantenimiento" <?= $servicio->categoria_servicio == 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                <option value="seguridad" <?= $servicio->categoria_servicio == 'seguridad' ? 'selected' : '' ?>>Seguridad</option>
                <option value="alquiler" <?= $servicio->categoria_servicio == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                <option value="impuestos" <?= $servicio->categoria_servicio == 'impuestos' ? 'selected' : '' ?>>Impuestos</option>
                <option value="otros" <?= $servicio->categoria_servicio == 'otros' ? 'selected' : '' ?>>Otros</option>
            </select>
        </div>

        <div class="campo">
            <label for="monto_base">Monto Base</label>
            <input type="number" step="0.01" name="monto_base" id="monto_base" value="<?= $servicio->monto_base ?>">
            <small style="color: #a0aec0; font-size: 11px;">Monto fijo o base del servicio</small>
        </div>

        <div class="campo">
            <label for="tiene_monto_variable">¿Tiene Monto Variable?</label>
            <select name="tiene_monto_variable" id="tiene_monto_variable">
                <option value="0" <?= $servicio->tiene_monto_variable == 0 ? 'selected' : '' ?>>No - Monto Fijo</option>
                <option value="1" <?= $servicio->tiene_monto_variable == 1 ? 'selected' : '' ?>>Sí - Monto Variable</option>
            </select>
            <small style="color: #a0aec0; font-size: 11px;">Ej: Luz/Agua varían según consumo</small>
        </div>

        <div class="campo">
            <label for="frecuencia_pago">Frecuencia de Pago *</label>
            <select name="frecuencia_pago" id="frecuencia_pago" required>
                <option value="mensual" <?= $servicio->frecuencia_pago == 'mensual' ? 'selected' : '' ?>>Mensual</option>
                <option value="bimestral" <?= $servicio->frecuencia_pago == 'bimestral' ? 'selected' : '' ?>>Bimestral</option>
                <option value="trimestral" <?= $servicio->frecuencia_pago == 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                <option value="unico" <?= $servicio->frecuencia_pago == 'unico' ? 'selected' : '' ?>>Pago Único</option>
                <option value="variable" <?= $servicio->frecuencia_pago == 'variable' ? 'selected' : '' ?>>Variable</option>
            </select>
        </div>

        <div class="campo">
            <label for="activo">Estado</label>
            <select name="activo" id="activo">
                <option value="1" <?= $servicio->activo == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $servicio->activo == 0 ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div class="campo full-ancho">
            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="4"><?= $servicio->descripcion ?></textarea>
        </div>

        <input type="submit" class="boton" value="Actualizar Servicio">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/servicios/listado">Volver</a>
        </div>
    </form>
</div>

<style>
    #pagina-editar-servicio.form-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
        border: 2px solid #ed850f;
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 780px;
        margin: 40px auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    #pagina-editar-servicio .form-logo {
        width: 90px;
        display: block;
        margin: 0 auto 15px;
    }

    #pagina-editar-servicio h2 {
        color: #ed850f !important;
        text-align: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
        background: none !important;
    }

    #pagina-editar-servicio form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 18px;
    }

    #pagina-editar-servicio .campo {
        margin-bottom: 1.2rem;
        text-align: left;
    }

    #pagina-editar-servicio .campo.full-ancho {
        grid-column: 1 / -1;
    }

    #pagina-editar-servicio .campo label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #a0aec0;
        font-size: 14px;
    }

    #pagina-editar-servicio .campo input,
    #pagina-editar-servicio .campo select,
    #pagina-editar-servicio .campo textarea {
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

    #pagina-editar-servicio .campo textarea {
        resize: vertical;
    }

    #pagina-editar-servicio .campo input::placeholder,
    #pagina-editar-servicio .campo textarea::placeholder {
        color: #6b7280;
    }

    #pagina-editar-servicio .campo input:focus,
    #pagina-editar-servicio .campo select:focus,
    #pagina-editar-servicio .campo textarea:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    #pagina-editar-servicio .campo select option {
        background: #2d3748;
        color: #e2e8f0;
    }

    #pagina-editar-servicio .campo small {
        display: block;
        margin-top: 6px;
        font-size: 12px !important;
        color: #a0aec0 !important;
    }

    #pagina-editar-servicio .boton {
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

    #pagina-editar-servicio .boton:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    #pagina-editar-servicio .contenedor-alertas {
        grid-column: 1 / -1;
    }

    #pagina-editar-servicio .acciones {
        grid-column: 1 / -1;
        margin-top: 20px;
        text-align: center;
    }

    #pagina-editar-servicio .acciones a {
        display: inline-block;
        color: #a0aec0;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    #pagina-editar-servicio .acciones a:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    @media (max-width: 600px) {
        #pagina-editar-servicio.form-container {
            padding: 1.5rem;
            margin: 20px auto;
            border-radius: 16px;
        }

        #pagina-editar-servicio h2 {
            font-size: 1.3rem;
        }

        #pagina-editar-servicio form {
            grid-template-columns: 1fr;
        }
    }
</style>

<script type="module" src="/assets/js/formularios.js"></script>