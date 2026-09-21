<div class="form-container" id="pagina-editar-usuario">
    <img src="/assets/img/logo.png" alt="Logo del cine" class="form-logo">
    <h2>Editar Usuario</h2>

    <form method="POST" action="/administrador/usuarios/actualizar"
        data-fetch="true" data-alerta=".contenedor-alertas" data-redirigir="/administrador/usuarios/listado">

        <!-- Campos ocultos -->
        <input type="hidden" name="id_usuario" value="<?php echo $usuario->id_usuario; ?>">
        <input type="hidden" name="id_persona" value="<?php echo $persona->id_persona; ?>">

        <div class="campo">
            <label for="nombre_persona">Nombre</label>
            <input type="text" name="nombre_persona" id="nombre_persona" placeholder="Nombre"
                value="<?php echo $persona->nombre_persona; ?>">
        </div>

        <div class="campo">
            <label for="apellido_persona">Apellido</label>
            <input type="text" name="apellido_persona" id="apellido_persona" placeholder="Apellido"
                value="<?php echo $persona->apellido_persona; ?>">
        </div>

        <div class="campo">
            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                value="<?php echo $persona->fecha_nacimiento; ?>">
        </div>

        <div class="campo">
            <label for="rela_sexo">Sexo</label>
            <select name="rela_sexo" id="rela_sexo">
                <option value="" disabled>-- Seleccione --</option>
                <?php foreach ($sexos as $sexo): ?>
                    <option value="<?php echo $sexo['id_sexo']; ?>"
                        <?php echo $persona->rela_sexo == $sexo['id_sexo'] ? 'selected' : ''; ?>>
                        <?php echo $sexo['nombre_sexo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="email">Email</label>
            <input type="text" name="email" id="email" placeholder="Email"
                value="<?php echo $usuario->email; ?>">
        </div>

        <div class="campo">
            <label for="nombre_usuario">Nombre de Usuario</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario" placeholder="Nombre de Usuario"
                value="<?php echo $usuario->nombre_usuario; ?>">
        </div>

        <div class="campo">
            <label for="clave_usuario">Nueva Contraseña (opcional)</label>
            <div class="input-con-icono">
                <input type="password" name="clave_usuario" id="clave_usuario" placeholder="Nueva contraseña">
                <span class="icono" onclick="togglePassword('clave_usuario', this)">👁‍🗨</span>
            </div>
        </div>

        <div class="campo">
            <label for="rela_perfil">Perfil</label>
            <select name="rela_perfil" id="rela_perfil">
                <option value="" disabled>-- Seleccione --</option>
                <?php foreach ($perfiles as $perfil): ?>
                    <option value="<?php echo $perfil['id_perfiles']; ?>"
                        <?php echo $usuario->rela_perfil == $perfil['id_perfiles'] ? 'selected' : ''; ?>>
                        <?php echo $perfil['nombre_perfil']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="contenedor-alertas"></div>
        <div id="alerta-actualizar"></div>
        <input type="submit" class="boton" value="Actualizar Usuario">
        <div class="acciones">
            <a href="/administrador/usuarios/listado">Volver</a>
        </div>



    </form>
</div>

<style>
    #pagina-editar-usuario.form-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
        border: 2px solid #ed850f;
        border-radius: 20px;
        padding: 2.5rem;
        max-width: 780px;
        margin: 40px auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    #pagina-editar-usuario .form-logo {
        width: 90px;
        display: block;
        margin: 0 auto 15px;
    }

    #pagina-editar-usuario h2 {
        color: #ed850f !important;
        text-align: center;
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
        background: none !important;
    }

    #pagina-editar-usuario form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 18px;
    }

    #pagina-editar-usuario .campo {
        margin-bottom: 1.2rem;
        text-align: left;
    }

    #pagina-editar-usuario .campo.full-ancho {
        grid-column: 1 / -1;
    }

    #pagina-editar-usuario .campo label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #a0aec0;
        font-size: 14px;
    }

    #pagina-editar-usuario .campo input,
    #pagina-editar-usuario .campo select {
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

    #pagina-editar-usuario .campo input::placeholder {
        color: #6b7280;
    }

    #pagina-editar-usuario .campo input:focus,
    #pagina-editar-usuario .campo select:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    #pagina-editar-usuario .campo select option {
        background: #2d3748;
        color: #e2e8f0;
    }

    /* Campo de contraseña con ícono de mostrar/ocultar */
    #pagina-editar-usuario .input-con-icono {
        position: relative;
    }

    #pagina-editar-usuario .input-con-icono input {
        width: 100%;
        padding: 12px 44px 12px 14px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        background: #2d3748;
        color: #e2e8f0;
        font-size: 14px;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }

    #pagina-editar-usuario .input-con-icono input:focus {
        outline: none;
        border-color: #ed850f;
        background: #363130;
        box-shadow: 0 0 0 3px rgba(237, 133, 15, 0.2);
    }

    #pagina-editar-usuario .input-con-icono .icono {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 16px;
        color: #a0aec0;
        transition: color 0.2s ease;
    }

    #pagina-editar-usuario .input-con-icono .icono:hover {
        color: #ed850f;
    }

    #pagina-editar-usuario .contenedor-alertas,
    #pagina-editar-usuario #alerta-actualizar {
        grid-column: 1 / -1;
    }

    #pagina-editar-usuario .boton {
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

    #pagina-editar-usuario .boton:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(237, 133, 15, 0.5);
    }

    #pagina-editar-usuario .acciones {
        grid-column: 1 / -1;
        margin-top: 20px;
        text-align: center;
    }

    #pagina-editar-usuario .acciones a {
        display: inline-block;
        color: #a0aec0;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        border: 2px solid #4a5568;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    #pagina-editar-usuario .acciones a:hover {
        border-color: #ed850f;
        color: #ed850f;
        background: rgba(237, 133, 15, 0.08);
    }

    @media (max-width: 600px) {
        #pagina-editar-usuario.form-container {
            padding: 1.5rem;
            margin: 20px auto;
            border-radius: 16px;
        }

        #pagina-editar-usuario h2 {
            font-size: 1.3rem;
        }

        #pagina-editar-usuario form {
            grid-template-columns: 1fr;
        }
    }
</style>

<script type="module" src="/assets/js/formularios.js"></script>

<script>
    function togglePassword(id, icono) {
        const input = document.getElementById(id);
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        icono.textContent = isPassword ? "🚫" : "👁‍🗨";
    }
</script>