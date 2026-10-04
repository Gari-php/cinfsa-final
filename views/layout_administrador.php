<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINFSA - Administrador</title>

    <!-- Fuente Google-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Montserrat+Alternates|Poppins&display=swap" rel="stylesheet">

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Pusher desde CDN -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>


    <?php

    use Classes\Notificaciones;
    use Middlewares\ValidarModulo;

    $notificaciones_no_leidas = 0;
    if (isset($_SESSION['id_usuario'])) {
        $notificaciones_no_leidas = Notificaciones::contarNoLeidas($_SESSION['id_usuario']);
    }

    $modulosUsuario = ValidarModulo::obtenerModulosUsuario();
    function tieneModulo($nombreModulo)
    {
        return \Middlewares\ValidarModulo::tiene($nombreModulo);
    }
    ?>

    <!-- Datos del usuario para JavaScript -->
    <script>
        window.USUARIO_DATOS = {
            id: <?= $_SESSION['id_usuario'] ?? 'null' ?>,
            perfil: <?= $_SESSION['perfil'] ?? 'null' ?>
        };
        window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";
    </script>
    <script src="/assets/js/csrf.js"></script>

    <!-- Estilo para fuente -->
    <style>
        * {
            font-family: 'Montserrat', 'Poppins', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            font-size: 15px !important;
        }
    </style>


    <?php if (empty($sinBase)): ?>
        <link rel="stylesheet" href="/assets/css/base.css">
    <?php endif; ?>

    <?php // CSS propio de la vista (assets/css/<vista>.css), solo si existe ?>
    <?php if (isset($vista) && is_file(__DIR__ . "/../public/assets/css/{$vista}.css")): ?>
        <link rel="stylesheet" href="/assets/css/<?php echo $vista; ?>.css">
    <?php endif; ?>

    <link rel="stylesheet" href="/assets/css/alertas.css">
    <script type="module" src="/assets/js/formularios.js"></script>


</head>

<body>
    <header class="header">
        <div class="container">
            <div class="btn-menu">
                <label for="btn-menu"><i class="fa-solid fa-lines-leaning"></i></label>
            </div>
            <div class="logo">
                <img src="../../assets/img/LOGO.png" alt="logo">
            </div>
            <nav class="menu">
                <a href="/administrador">Inicio</a>
                <a href="/"><i class="fa-solid fa-user-xmark"></i></a>

                <!-- CAMPANITA CON DROPDOWN DE NOTIFICACIONES -->
                <div class="notifications-container">
                    <button class="notification-btn" id="notification-toggle">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($notificaciones_no_leidas > 0): ?>
                            <span class="notification-badge" id="notification-count"><?= $notificaciones_no_leidas ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="notifications-dropdown" id="notifications-dropdown">
                        <div class="notifications-header">
                            <h4>Notificaciones</h4>
                            <button class="mark-all-read" id="mark-all-read">
                                <i class="fa-solid fa-check-double"></i> Marcar todas
                            </button>
                        </div>

                        <div class="notifications-list" id="notifications-list">
                            <div class="loading-notifications">
                                <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
                            </div>
                        </div>
                    </div>
                </div>

            </nav>
        </div>
    </header>

    <nav class="nav-modular">
        <ul>
            <?php if (tieneModulo('GESTION_USUARIOS')): ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-users"></i> Usuarios
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/usuarios/listado">Gestionar Usuarios</a></li>
                        <li><a href="/administrador/sexo/listado">Gestionar Sexos</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (tieneModulo('VENTA_FUNCIONES')): ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-film"></i> Funciones
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/peliculas/listado">Gestionar Películas</a></li>
                        <li><a href="/administrador/salas/listado">Gestionar Salas</a></li>
                        <li><a href="/administrador/funciones/listado">Gestionar Funciones</a></li>
                        <li><a href="/administrador/turnos/listado">Gestionar Turnos</a></li>
                        <li><a href="/administrador/tipos_entradas/listado">Tipos de Entrada</a></li>
                        <li><a href="/administrador/butacas/gestion">Gestionar Butacas</a></li>
                        <li><a href="/administrador/entradas/listado">Gestionar Entradas</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (tieneModulo('VENTA_PRODUCTOS')): ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-store"></i> Cantina
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/cantina/listado">Gestionar Cantina</a></li>
                        <li><a href="/administrador/productos/listado">Gestionar Productos</a></li>
                        <li><a href="/administrador/stock/listado">Gestionar Stock</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (tieneModulo('GESTION_JUEGOS')): ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-gamepad"></i> Juegos
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/maquinas/listado">Gestionar Máquinas</a></li>
                        <li><a href="/administrador/fichas/listado">Gestionar Fichas</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (tieneModulo('GESTION_CAJA')): ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-cash-register"></i> Cajas
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/actividadcajas/monitor">Monitoreo de Cajas</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- VENTAS WEB -->
            <li class="modulo-nav dropdown">
                <a href="#" class="dropdown-toggle" aria-expanded="false">
                    <i class="fa-solid fa-globe"></i> Ventas Web
                </a>
                <ul class="dropdown-menu" aria-hidden="true">
                    <li><a href="/administrador/movimientos-web/listado">Listado de Órdenes</a></li>
                    <li><a href="/administrador/movimientos-web/reportes">Reportes Estadísticos</a></li>
                </ul>
            </li>

            <?php if ($_SESSION['perfil'] == 3): // Solo administrador 
            ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-cog"></i> Sistema
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/modulos/asignar">Gestionar Módulos</a></li>
                        <li><a href="/administrador/perfiles/listado">Gestionar Perfiles</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['perfil'] == 3): // Solo administrador 
            ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-truck"></i> Proveedores
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/proveedores/listado">Gestionar Proveedores</a></li>
                        <li><a href="/administrador/servicios/listado">Gestionar Servicios</a></li>
                        <li><a href="/administrador/gastos/listado">Realizar Gastos</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['perfil'] == 3): // Solo administrador
            ?>
                <li class="modulo-nav dropdown">
                    <a href="#" class="dropdown-toggle" aria-expanded="false">
                        <i class="fa-solid fa-shield-halved"></i> Control
                    </a>
                    <ul class="dropdown-menu" aria-hidden="true">
                        <li><a href="/administrador/auditoria/listado">Registro de auditoría</a></li>
                        <li><a href="/control/entradas">Control de entradas (QR)</a></li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="capa"></div>
    <input type="checkbox" id="btn-menu" />
    <div class="container-menu">
        <div class="cont-menu">
            <?php
            $fotoSrc = !empty($_SESSION['foto_perfil'])
                ? $_SESSION['foto_perfil']
                : 'data:image/svg+xml;base64,' . base64_encode('
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="#2d3748"/>
            <circle cx="50" cy="38" r="22" fill="#4a5568"/>
            <ellipse cx="50" cy="90" rx="35" ry="25" fill="#4a5568"/>
        </svg>
    ');
            ?>
            <figure class="nav-lateral-avatar">
                <div class="foto-perfil-wrapper" onclick="document.getElementById('inputFotoAdmin').click()" title="Cambiar foto de perfil">
                    <img id="fotoAdminPreview" src="<?php echo $fotoSrc; ?>" alt="Foto de perfil">
                    <div class="foto-overlay">
                        <i class="fa-solid fa-camera"></i>
                        <span style="font-size: 10px !important; color: #fff; margin-top: 4px;">Cambiar</span>
                    </div>
                </div>
                <input type="file" id="inputFotoAdmin" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
                <figcaption>
                    <?php echo strtoupper($_SESSION['nombre'] ?? 'ADMINISTRADOR'); ?><br>
                    <small>@<?php echo $_SESSION['usuario'] ?? 'admin'; ?></small>
                </figcaption>
            </figure>
            </figcaption>
            </figure>
            <nav>
                <?php if (tieneModulo('GESTION_USUARIOS')): ?>
                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-usuarios">
                            <i class="fa-solid fa-users"></i> Usuarios <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-usuarios">
                            <a href="/administrador/usuarios/listado">• Gestionar Usuarios</a>
                            <a href="/administrador/sexo/listado">• Gestionar Sexos</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (tieneModulo('VENTA_FUNCIONES')): ?>
                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-funciones">
                            <i class="fa-solid fa-film"></i> Funciones <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-funciones">
                            <a href="/administrador/peliculas/listado">• Películas</a>
                            <a href="/administrador/salas/listado">• Salas</a>
                            <a href="/administrador/funciones/listado">• Funciones</a>
                            <a href="/administrador/turnos/listado">• Turnos</a>
                            <a href="/administrador/tipos_entradas/listado">• Tipos Entrada</a>
                            <a href="/administrador/entradas/listado">• Entradas</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (tieneModulo('VENTA_PRODUCTOS')): ?>
                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-cantina">
                            <i class="fa-solid fa-store"></i> Cantina <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-cantina">
                            <a href="/administrador/cantina/listado">• Cantina</a>
                            <a href="/administrador/productos/listado">• Productos</a>
                            <a href="/administrador/stock/listado">• Stock</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (tieneModulo('GESTION_JUEGOS')): ?>
                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-juegos">
                            <i class="fa-solid fa-gamepad"></i> Juegos <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-juegos">
                            <a href="/administrador/maquinas/listado">• Máquinas</a>
                            <a href="/administrador/fichas/listado">• Fichas</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (tieneModulo('GESTION_CAJA')): ?>
                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-cajas">
                            <i class="fa-solid fa-cash-register"></i> Cajas <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-cajas">
                            <a href="/administrador/actividadcajas/monitor">• Monitor de Cajas</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="menu-modulo">
                    <a href="#" class="modulo-toggle" data-target="lateral-ventas-web">
                        <i class="fa-solid fa-globe"></i> Ventas Web <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </a>
                    <div class="submenu-lateral" id="lateral-ventas-web">
                        <a href="/administrador/movimientos-web/listado">• Listado de Órdenes</a>
                        <a href="/administrador/movimientos-web/reportes">• Reportes Estadísticos</a>
                    </div>
                </div>

                <?php if ($_SESSION['perfil'] == 3): // Solo administrador
                ?>
                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-sistema">
                            <i class="fa-solid fa-cog"></i> Sistema <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-sistema">
                            <a href="/administrador/modulos/asignar">• Gestionar Módulos</a>
                            <a href="/administrador/perfiles/listado">• Gestionar Perfiles</a>
                        </div>
                    </div>

                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-proveedores">
                            <i class="fa-solid fa-truck"></i> Proveedores <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-proveedores">
                            <a href="/administrador/proveedores/listado">• Gestionar Proveedores</a>
                            <a href="/administrador/servicios/listado">• Gestionar Servicios</a>
                            <a href="/administrador/gastos/listado">• Realizar Gastos</a>
                        </div>
                    </div>

                    <div class="menu-modulo">
                        <a href="#" class="modulo-toggle" data-target="lateral-control">
                            <i class="fa-solid fa-shield-halved"></i> Control <i class="fa-solid fa-chevron-down toggle-icon"></i>
                        </a>
                        <div class="submenu-lateral" id="lateral-control">
                            <a href="/administrador/auditoria/listado">• Registro de auditoría</a>
                            <a href="/control/entradas">• Control de entradas (QR)</a>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="/">CERRAR SESIÓN</a>
            </nav>
            <label for="btn-menu"><i class="fa-regular fa-circle-xmark"></i></label>
        </div>
    </div>

    <!-- CONTENIDO -->
    <div class="main-content-wrapper">
        <?php echo $contenido; ?>
    </div>

    <style>
        @import url("https://fonts.googleapis.com/css?family=Montserrat|Montserrat+Alternates|Poppins&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Montserrat Alternates", sans-serif;
        }

        body {
            background-color: #363130;
        }

        body.menu-abierto {
            overflow: hidden;
        }

        .capa {
            position: fixed;
            width: 100%;
            height: 100vh;
            background: #363130;
            z-index: -1;
            top: 0;
            left: 0;
        }

        /* seccion de la foto de perfil*/
        .foto-perfil-wrapper {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto;
            cursor: pointer;
            border-radius: 50%;
        }

        .foto-perfil-wrapper img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ed850f;
            display: block;
            transition: filter 0.2s ease;
        }

        .foto-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .foto-overlay i {
            color: #fff;
            font-size: 18px !important;
        }

        .foto-perfil-wrapper:hover .foto-overlay {
            opacity: 1;
        }

        .foto-perfil-wrapper:hover img {
            filter: brightness(0.6);
        }

        /* ESTILOS PARA NOTIFICACIONES DROPDOWN */
        .notifications-container {
            position: relative;
            display: inline-block;
        }

        .notification-btn {
            background: none;
            border: none;
            color: #ed850f;
            font-size: 20px;
            cursor: pointer;
            padding: 15px;
            position: relative;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            background: rgba(237, 133, 15, 0.1);
        }

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px !important;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .notifications-dropdown {
            position: fixed;
            top: 100%;
            right: 20px;
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            max-height: 500px;
            overflow: hidden;
        }

        .notifications-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }

        .notifications-header h4 {
            margin: 0;
            font-size: 16px !important;
            color: #333;
        }

        .mark-all-read {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px !important;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .mark-all-read:hover {
            background: #0056b3;
        }

        .notifications-list {
            max-height: 300px;
            overflow-y: auto;
            z-index: 9999;
        }

        .loading-notifications {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .notification-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s ease;
            position: relative;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #fff8f0;
            border-left: 3px solid #ed850f;
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 15px;
            width: 8px;
            height: 8px;
            background: #ed850f;
            border-radius: 50%;
            transform: translateY(-50%);
        }

        .notification-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .notification-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px !important;
            flex-shrink: 0;
        }

        .icon-usuario {
            background: #e3f2fd;
            color: #1976d2;
        }

        .icon-stock {
            background: #fff3e0;
            color: #f57c00;
        }

        .icon-venta {
            background: #e8f5e8;
            color: #388e3c;
        }

        .icon-sistema {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .notification-text {
            flex: 1;
        }

        .notification-title {
            font-weight: bold;
            font-size: 13px !important;
            color: #333;
            margin-bottom: 3px;
        }

        .notification-desc {
            font-size: 12px !important;
            color: #666;
            line-height: 1.3;
            margin-bottom: 3px;
        }

        .notification-adjunto {
            display: inline-block;
            margin-top: 4px;
            color: #ed850f;
            font-weight: 600;
            text-decoration: underline;
            font-size: 12px !important;
        }

        .notification-adjunto:hover {
            color: #ffa733;
        }

        .notification-time {
            font-size: 11px !important;
            color: #999;
        }

        .notifications-footer {
            padding: 12px 20px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
        }

        .notifications-footer a {
            color: #007bff;
            text-decoration: none;
            font-size: 13px !important;
            display: block;
            text-align: center;
        }

        .notifications-footer a:hover {
            text-decoration: underline;
        }

        .no-notifications {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .no-notifications i {
            font-size: 40px !important;
            margin-bottom: 10px;
            color: #ddd;
        }

        /* Resto de estilos del layout original... */
        .header {
            width: 100%;
            height: 100px;
            position: relative;
            /* ✅ AGREGAR ESTO */
            top: 0;
            left: 0;
            right: 0;
            z-index: 1200;
            background-color: #232323;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transform: translateZ(0);
            will-change: transform;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
        }

        figure {
            top: 50px;
        }

        .tile-container {
            margin-top: 5%;
            text-align: center;
            padding: 20px 25px;
        }

        .tile {
            height: 200px;
            width: 200px;
            margin: 10px;
            display: inline-block;
            text-decoration: none;
            color: var(--accent-color);
            border: 1px solid var(--border-color);
            border-radius: 3px;
            user-select: none;
            transition: all .2s ease-in-out;
            background-color: #ed850f;
        }

        .tile:hover {
            text-decoration: none;
            border-color: var(--color-three);
        }

        .tile:focus,
        .tile:active {
            outline: none;
        }

        .tile-tittle {
            margin: 0;
            width: 100%;
            padding: 0;
            height: 40px;
            line-height: 40px;
            box-sizing: border-box;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
            transition: all .2s ease-in-out;
            font-family: 'roboto_medium_regular';
        }

        .tile:hover .tile-tittle {
            color: #FFF;
            border-color: var(--color-three);
            background-color: var(--color-three);
        }

        .tile-icon {
            width: 100%;
            height: 160px;
            box-sizing: border-box;
            padding-top: 22px;
        }

        .tile-icon>i {
            font-size: 80px !important;
        }

        .tile-icon>p {
            font-family: 'roboto_medium_regular';
            height: 35px;
            line-height: 35px;
        }

        .tile:hover .tile-icon>i,
        .tile:hover .tile-icon>p {
            color: var(--color-three);
        }

        .nav-lateral-avatar {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #3a3a3a;
        }

        .nav-lateral-avatar img {
            width: 80px;
            border-radius: 50%;
            border: 3px solid #ed850f;
            margin-bottom: 12px;
        }

        .nav-lateral-avatar i {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #bbb;
            font-size: 20px;
            cursor: pointer;
        }

        .nav-lateral-avatar figcaption {
            font-weight: bold;
            font-size: 14px !important;
            color: #ed850f;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .nav-lateral-avatar figcaption small {
            color: #a0aec0;
            font-size: 12px !important;
            font-weight: 400;
        }

        .container .btn-menu,
        .logo {
            float: left;
            height: 100px;
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin-top: 0;
        }

        .container .btn-menu label {
            color: #ed850f;
            font-size: 25px;
            cursor: pointer;
        }

        .logo h1 {
            color: #fff;
            font-weight: 400;
            font-size: 22px;
            margin-left: 10px;
        }

        .container .menu {
            float: right;
            line-height: 100px;
        }

        .container .menu a {
            display: inline-block;
            padding: 15px;
            line-height: normal;
            text-decoration: none;
            color: #ed850f;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            font-size: 15px;
            margin-right: 5px;
        }

        .container .menu a:hover {
            border-bottom: 2px solid #ed850f;
            padding-bottom: 5px;
        }

        #btn-menu {
            display: none;
        }

        .container-menu {
            position: fixed;
            width: 100%;
            height: 100vh;
            top: 0;
            left: 0;
            transition: all 500ms ease;
            opacity: 0;
            visibility: hidden;
            z-index: 9998;
            /* mayor que header (1200) y navbar (1150) */
            background: rgba(0, 0, 0, 0.6);
        }

        #btn-menu:checked~.container-menu {
            opacity: 1;
            visibility: visible;
        }

        .cont-menu {
            width: 100%;
            max-width: 280px;
            background: #1a1a1a;
            height: 100vh;
            position: relative;
            transition: all 500ms ease;
            transform: translateX(-100%);
            padding: 20px 15px;
            box-sizing: border-box;
            overflow-y: auto;
            z-index: 9999;
            /* encima del overlay */
        }

        #btn-menu:checked~.container-menu .cont-menu {
            transform: translateX(0%);
        }

        .cont-menu nav {
            transform: none;
        }

        .cont-menu nav a {
            display: block;
            text-decoration: none;
            padding: 20px;
            color: #c7c7c7;
            border-left: 5px solid transparent;
            transition: all 400ms ease;
        }

        .cont-menu nav a:hover {
            border-left: 5px solid#ed850f;
            background: #1f1f1f;
        }

        .cont-menu label {
            position: absolute;
            right: 15px;
            top: 15px;
            color: #fff;
            cursor: pointer;
            font-size: 22px !important;
            z-index: 10000;
        }

        .nav-modular {
            background-color: #ed850f;
            width: calc(100% - 40px);
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 8px;

            top: 100px;
            left: 50%;
            right: auto;

            z-index: 1150;

            padding: 8px 12px;
        }

        .main-content-wrapper {
            padding-top: 60px;
        }

        .nav-modular ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            /* Si las pestañas no entran en una línea, pasan a la siguiente en vez de salirse de la barra */
            flex-wrap: wrap;
            justify-content: space-around;
            align-items: center;
        }

        .modulo-nav {
            position: relative;
        }

        .modulo-nav a {
            display: block;
            padding: 12px 18px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            border-radius: 6px;
            cursor: pointer;
        }

        .modulo-nav:hover>a {
            background-color: rgba(0, 0, 0, 0.12);
        }

        /* La agrega el script al abrir un menú que se saldría por la derecha de la pantalla */
        .dropdown-menu.abre-izquierda {
            left: auto;
            right: 0;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            background: #1a202c;
            min-width: 220px;
            border-radius: 10px;
            list-style: none;
            padding: 6px;
            margin: 0;
            max-height: 0;
            overflow: hidden;
            z-index: 2000;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(237, 133, 15, 0.2);
            opacity: 0;
            transform-origin: top center;
            transition: max-height 260ms ease, opacity 200ms ease, transform 200ms ease;
            transform: translateY(-6px);
        }

        .dropdown-menu.show {
            max-height: 600px;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-menu li {
            border-bottom: none;
        }

        .dropdown-menu a {
            display: block;
            padding: 9px 14px !important;
            color: #cbd5e0;
            text-decoration: none;
            font-size: 13px !important;
            border-radius: 6px !important;
            transition: all 0.15s ease;
        }

        .dropdown-menu a:hover {
            background: rgba(237, 133, 15, 0.15) !important;
            color: #ed850f !important;
            padding-left: 18px !important;
        }

        .menu-modulo {
            margin-bottom: 8px;
        }

        .modulo-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            background: #2d3748;
            border: 1px solid #4a5568;
            border-radius: 8px;
            font-size: 14px !important;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .modulo-toggle i:first-child {
            color: #ed850f;
            margin-right: 8px;
        }

        .modulo-toggle:hover {
            border-color: #ed850f;
            background: #364154;
            box-shadow: 0 0 8px rgba(237, 133, 15, 0.3);
        }

        .modulo-toggle.active {
            border-color: #ed850f;
            background: #364154;
            box-shadow: 0 0 10px rgba(237, 133, 15, 0.35);
        }

        .submenu-lateral {
            max-height: 0;
            overflow: hidden;
            background: #232323;
            border-radius: 0 0 8px 8px;
            transition: max-height 0.3s ease;
        }

        .submenu-lateral.show {
            max-height: 300px;
            margin-top: -1px;
            border: 1px solid #4a5568;
            border-top: none;
        }

        .submenu-lateral a {
            display: block;
            padding: 10px 20px;
            color: #c7c7c7;
            text-decoration: none;
            font-size: 13px !important;
            transition: all 0.2s ease;
        }

        .submenu-lateral a:hover {
            background: rgba(237, 133, 15, 0.15);
            color: #ed850f;
            padding-left: 25px;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            color: #a0aec0;
        }

        .modulo-toggle.active .toggle-icon {
            transform: rotate(180deg);
            color: #ed850f;
        }

        .cont-menu>nav>a {
            display: block;
            margin-top: 15px;
            padding: 12px 15px;
            background: rgba(200, 35, 51, 0.15);
            border: 1px solid #c82333;
            border-radius: 8px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 13px !important;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
        }

        .cont-menu>nav>a:hover {
            background: #c82333;
            color: #fff;
        }

        .cont-menu {
            scrollbar-width: thin;
            scrollbar-color: #ed850f #1a1a1a;
        }

        .cont-menu::-webkit-scrollbar {
            width: 8px;
        }

        .cont-menu::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        .cont-menu::-webkit-scrollbar-thumb {
            background-color: #ed850f;
            border-radius: 4px;
        }

        .cont-menu::-webkit-scrollbar-thumb:hover {
            background-color: #ffa733;
        }

        /* Reportes: los gráficos se ajustan al ancho disponible en cualquier pantalla */
        .graficos-grid > * {
            min-width: 0;
        }

        .main-content-wrapper canvas {
            max-width: 100%;
        }

        @media (max-width: 480px) {
            .grafico-container {
                padding: 16px;
            }
        }

        @media (max-width: 1024px) {
            .nav-modular ul {
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .nav-modular {
                padding: 6px;
                width: calc(100% - 24px);
                margin-top: 12px;
            }

            .main-content-wrapper {
                padding-top: 16px;
            }

            /* Dos columnas de módulos en lugar de una lista vertical que ocupaba toda la pantalla */
            .nav-modular ul {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
                align-items: start;
            }

            .modulo-nav a {
                padding: 10px 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .dropdown-menu {
                position: static;
                box-shadow: none;
                margin-top: 6px;
                border-radius: 6px;
            }

            .notifications-dropdown {
                width: auto;
                left: 12px;
                right: 12px;
                max-height: 70vh;
            }
        }
    </style>

    <script src="/assets/js/exportar.js"></script>
    <script src="/assets/js/notificaciones.js"></script>
    <script>
        // JavaScript para menú lateral y nav superior
        document.addEventListener('DOMContentLoaded', function() {
            const btnMenuCheckbox = document.getElementById('btn-menu');
            if (btnMenuCheckbox) {
                btnMenuCheckbox.addEventListener('change', function() {
                    document.body.classList.toggle('menu-abierto', this.checked);
                });
            }
            const toggles = document.querySelectorAll('.modulo-toggle, .dropdown-toggle');
            const allSubmenus = document.querySelectorAll('.submenu-lateral, .dropdown-menu');

            toggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();

                    let submenu = null;

                    if (this.classList.contains('modulo-toggle')) {
                        const targetId = this.getAttribute('data-target');
                        submenu = document.getElementById(targetId);
                    } else if (this.classList.contains('dropdown-toggle')) {
                        const parentLi = this.closest('.modulo-nav');
                        if (parentLi) {
                            submenu = parentLi.querySelector('.dropdown-menu');
                        }
                    }

                    const isActive = this.classList.contains('active');

                    // Cerrar TODOS los menús (incluido el propio)
                    toggles.forEach(t => t.classList.remove('active'));
                    allSubmenus.forEach(s => s.classList.remove('show'));

                    document.querySelectorAll('.dropdown-toggle').forEach(dt => dt.setAttribute('aria-expanded', 'false'));
                    document.querySelectorAll('.dropdown-menu').forEach(dm => dm.setAttribute('aria-hidden', 'true'));

                    // Si NO estaba activo, ahora lo abrimos
                    if (!isActive) {
                        this.classList.add('active');
                        if (submenu) submenu.classList.add('show');

                        if (this.classList.contains('dropdown-toggle')) {
                            this.setAttribute('aria-expanded', 'true');
                            if (submenu) {
                                submenu.setAttribute('aria-hidden', 'false');
                                // Si el menú se saldría por la derecha (pestaña contra el borde), se abre hacia la izquierda
                                submenu.classList.remove('abre-izquierda');
                                if (submenu.getBoundingClientRect().right > window.innerWidth - 8) {
                                    submenu.classList.add('abre-izquierda');
                                }
                            }
                        }
                    }
                });
            });

            document.addEventListener('click', function(event) {
                const clickInsideNav = event.target.closest('.nav-modular');
                const clickInsideSidebar = event.target.closest('.cont-menu');
                const clickOnBtnMenu = event.target.closest('label[for="btn-menu"], #btn-menu');
                const clickInsideNotifications = event.target.closest('.notifications-container');

                if (!clickInsideNav && !clickInsideSidebar && !clickOnBtnMenu && !clickInsideNotifications) {
                    toggles.forEach(t => t.classList.remove('active'));
                    allSubmenus.forEach(s => s.classList.remove('show'));

                    document.querySelectorAll('.dropdown-toggle').forEach(dt => dt.setAttribute('aria-expanded', 'false'));
                    document.querySelectorAll('.dropdown-menu').forEach(dm => dm.setAttribute('aria-hidden', 'true'));
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputFoto = document.getElementById('inputFotoAdmin');
            if (!inputFoto) return;

            inputFoto.addEventListener('change', async function() {
                if (!this.files || !this.files[0]) return;

                const file = this.files[0];
                const maxSize = 5 * 1024 * 1024;

                if (file.size > maxSize) {
                    alert('La imagen no puede superar los 5MB');
                    this.value = '';
                    return;
                }

                // Preview inmediato antes de subir
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('fotoAdminPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Subir al servidor
                const formData = new FormData();
                formData.append('foto', file);

                try {
                    const response = await fetch('/administrador/perfil/actualizar-foto', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.ok) {
                        document.querySelectorAll('#fotoAdminPreview').forEach(img => {
                            img.src = data.foto;
                        });
                    } else {
                        alert('Error: ' + data.mensaje);
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error al subir foto:', error);
                    alert('Error de conexión al subir la foto');
                    location.reload();
                }

                this.value = '';
            });
        });
    </script>
</body>

</html>