<div class="form-container" id="pagina-editar-proveedor">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Proveedor</h2>
    <form method="POST"
        action="/administrador/proveedores/actualizar"
        data-fetch="true"
        data-alerta=".contenedor-alertas"
        data-redirigir="/administrador/proveedores/listado">

        <input type="hidden" name="id_proveedor" value="<?= $proveedor->id_proveedor ?>">

        <div class="campo">
            <label for="razon_social">Razón Social *</label>
            <input type="text" name="razon_social" id="razon_social" value="<?= $proveedor->razon_social ?>" maxlength="200" required>
        </div>

        <div class="campo">
            <label for="nombre_comercial">Nombre Comercial *</label>
            <input type="text" name="nombre_comercial" id="nombre_comercial" value="<?= $proveedor->nombre_comercial ?>" maxlength="150" required>
        </div>

        <div class="campo">
            <label for="rut">RUT/CUIT *</label>
            <input type="text" name="rut" id="rut" value="<?= $proveedor->rut ?>" maxlength="20" required>
            <small style="color: #a0aec0; font-size: 11px;">Formato: XX-XXXXXXXX-X</small>
        </div>

        <div class="campo">
            <label for="tipo_proveedor">Tipo de Proveedor *</label>
            <select name="tipo_proveedor" id="tipo_proveedor" required>
                <option value="">-- Seleccionar --</option>
                <option value="peliculas" <?= $proveedor->tipo_proveedor == 'peliculas' ? 'selected' : '' ?>>Películas</option>
                <option value="servicios" <?= $proveedor->tipo_proveedor == 'servicios' ? 'selected' : '' ?>>Servicios</option>
                <option value="productos" <?= $proveedor->tipo_proveedor == 'productos' ? 'selected' : '' ?>>Productos</option>
                <option value="otros" <?= $proveedor->tipo_proveedor == 'otros' ? 'selected' : '' ?>>Otros</option>
            </select>
        </div>

        <div class="campo">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono" value="<?= $proveedor->telefono ?>" maxlength="30">
        </div>

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= $proveedor->email ?>" maxlength="100">
        </div>

        <div class="campo full-ancho">
            <label for="direccion">Dirección</label>
            <textarea name="direccion" id="direccion" rows="3"><?= $proveedor->direccion ?></textarea>
        </div>

        <div class="campo">
            <label for="sitio_web">Sitio Web</label>
            <input type="text" name="sitio_web" id="sitio_web" value="<?= $proveedor->sitio_web ?>" maxlength="200">
        </div>

        <div class="campo full-ancho">
            <label for="notas">Notas / Observaciones</label>
            <textarea name="notas" id="notas" rows="4"><?= $proveedor->notas ?></textarea>
        </div>

        <div class="campo">
            <label for="activo">Estado</label>
            <select name="activo" id="activo">
                <option value="1" <?= $proveedor->activo == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $proveedor->activo == 0 ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <input type="submit" class="boton" value="Actualizar Proveedor">
        <div class="contenedor-alertas"></div>
        <div class="acciones">
            <a href="/administrador/proveedores/listado">Volver</a>
        </div>
    </form>
</div>

<style>
    #pagina-editar-proveedor.form-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
        border: 2px solid #ed850f;
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 780px;
        margin: 40px auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    #pagina-editar-proveedor .form-logo {
        width: 90px;
        display: block;
        margin: 0 auto 15px;
    }

    #pagina-editar-proveedor h2 {
        color: #ed850f !important;
        text-align: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
        background: none !important;
    }

    #pagina-editar-proveedor form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 18px;
    }

    #pagina-editar-proveedor .campo {
        margin-bottom: 1.2rem;
        text-align: left;
    }

    #pagina-editar-proveedor .campo.full-ancho {
        grid-column: 1 / -1;
    }

    #pagina-editar-proveedor .campo label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #a0aec0;
        font-size: 14px;
    }

    #pagina-editar-proveedor .campo input,
    #pagina-editar-proveedor .campo select,
    #pagina-editar-proveedor .campo textarea {
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

    #pagina-editar-proveedor .campo textarea {
        resize: vertical;
    }

    #pagina-editar-proveedor .campo input::placeholder,
    #pagina-editar-proveedor .campo textarea::placeholder {
        color: #6b7280;
    }

    #pagina-editar-proveedor .campo input:focus,
    #pagina-editar-proveedor .campo select:focus,
    #pagina-editar-proveedor .campo textarea:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    #pagina-editar-proveedor .campo select option {
        background: #2d3748;
        color: #e2e8f0;
    }

    #pagina-editar-proveedor .campo small {
        display: block;
        margin-top: 6px;
        font-size: 12px !important;
        color: #a0aec0 !important;
    }

    #pagina-editar-proveedor .boton {
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

    #pagina-editar-proveedor .boton:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    #pagina-editar-proveedor .contenedor-alertas {
        grid-column: 1 / -1;
    }

    #pagina-editar-proveedor .acciones {
        grid-column: 1 / -1;
        margin-top: 20px;
        text-align: center;
    }

    #pagina-editar-proveedor .acciones a {
        display: inline-block;
        color: #a0aec0;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    #pagina-editar-proveedor .acciones a:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    @media (max-width: 600px) {
        #pagina-editar-proveedor.form-container {
            padding: 1.5rem;
            margin: 20px auto;
            border-radius: 16px;
        }

        #pagina-editar-proveedor h2 {
            font-size: 1.3rem;
        }

        #pagina-editar-proveedor form {
            grid-template-columns: 1fr;
        }
    }
</style>
<script type="module" src="/assets/js/formularios.js"></script>