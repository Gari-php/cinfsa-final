<div class="dashboard-container">

    <!-- Bienvenida -->
    <div class="dashboard-bienvenida">
        <div class="bienvenida-texto">
            <h1>
                <span class="bienvenida-saludo">¡Bienvenido,</span>
                <span class="bienvenida-nombre"><?php echo htmlspecialchars(strtoupper($_SESSION['nombre'] ?? 'Administrador')); ?>!</span>
            </h1>
            <p>Panel de Administración CINFSA — <?php echo date('l d \d\e F \d\e Y'); ?></p>
        </div>
        <div class="bienvenida-logo">
            <img src="/assets/img/logo.png" alt="CINFSA">
        </div>
    </div>

    <!-- Tarjetas de acceso rápido -->
    <div class="dashboard-grid">

        <!-- Funciones -->
        <a href="/administrador/funciones/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(237,133,15,0.15); color:#ed850f;">
                <i class="fa-solid fa-clapperboard"></i>
            </div>
            <div class="dash-card-info">
                <h3>Funciones</h3>
                <p>Gestionar cartelera y funciones</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Películas -->
        <a href="/administrador/peliculas/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(59,130,246,0.15); color:#3b82f6;">
                <i class="fa-solid fa-film"></i>
            </div>
            <div class="dash-card-info">
                <h3>Películas</h3>
                <p>Catálogo y gestión de films</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Usuarios -->
        <a href="/administrador/usuarios/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(34,197,94,0.15); color:#22c55e;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="dash-card-info">
                <h3>Usuarios</h3>
                <p>Gestionar clientes y personal</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Cantina -->
        <a href="/administrador/cantina/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(245,158,11,0.15); color:#f59e0b;">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <div class="dash-card-info">
                <h3>Cantina</h3>
                <p>Productos, stock y ventas</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Entradas -->
        <a href="/administrador/entradas/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(168,85,247,0.15); color:#a855f7;">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div class="dash-card-info">
                <h3>Entradas</h3>
                <p>Control de tickets vendidos</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Cajas -->
        <a href="/administrador/actividadcajas/monitor" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(6,182,212,0.15); color:#06b6d4;">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div class="dash-card-info">
                <h3>Cajas</h3>
                <p>Monitoreo de actividad de cajas</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Ventas Web -->
        <a href="/administrador/movimientos-web/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(236,72,153,0.15); color:#ec4899;">
                <i class="fa-solid fa-globe"></i>
            </div>
            <div class="dash-card-info">
                <h3>Ventas Web</h3>
                <p>Órdenes y reportes online</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Proveedores -->
        <a href="/administrador/proveedores/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(239,68,68,0.15); color:#ef4444;">
                <i class="fa-solid fa-truck"></i>
            </div>
            <div class="dash-card-info">
                <h3>Proveedores</h3>
                <p>Gestionar proveedores y gastos</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

        <!-- Salas -->
        <a href="/administrador/salas/listado" class="dash-card">
            <div class="dash-card-icon" style="background: rgba(16,185,129,0.15); color:#10b981;">
                <i class="fa-solid fa-building"></i>
            </div>
            <div class="dash-card-info">
                <h3>Salas</h3>
                <p>Gestionar salas y reportes</p>
            </div>
            <i class="fa-solid fa-chevron-right dash-card-arrow"></i>
        </a>

    </div>

    <!-- Accesos rápidos secundarios -->
    <div class="dashboard-secundario">
        <h2 class="dashboard-secundario-titulo">
            <i class="fa-solid fa-bolt"></i> Acciones rápidas
        </h2>
        <div class="acciones-rapidas">
            <a href="/administrador/funciones/crear" class="accion-rapida">
                <i class="fa-solid fa-plus"></i> Nueva Función
            </a>
            <a href="/administrador/peliculas/crear" class="accion-rapida">
                <i class="fa-solid fa-plus"></i> Nueva Película
            </a>
            <a href="/administrador/productos/crear" class="accion-rapida">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </a>
            <a href="/administrador/usuarios/crear" class="accion-rapida">
                <i class="fa-solid fa-plus"></i> Nuevo Usuario
            </a>
            <a href="/administrador/gastos/crear" class="accion-rapida">
                <i class="fa-solid fa-plus"></i> Registrar Gasto
            </a>
        </div>
    </div>

</div>

<style>
    /* ===== DASHBOARD ===== */
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* ===== BIENVENIDA ===== */
    .dashboard-bienvenida {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border-radius: 20px;
        padding: 2.5rem 3rem;
        margin-bottom: 2rem;
        border: 1px solid #4a5568;
        box-shadow: 0 0 30px rgba(237, 133, 15, 0.15);
        position: relative;
        overflow: hidden;
    }

    .dashboard-bienvenida::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #ed850f, #d97706);
    }

    .bienvenida-texto h1 {
        margin: 0 0 0.5rem 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .bienvenida-saludo {
        font-size: 1rem;
        color: #a0aec0;
        font-weight: 400;
    }

    .bienvenida-nombre {
        font-size: 2rem;
        color: #ed850f;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .bienvenida-texto p {
        color: rgba(255, 255, 255, 0.5);
        margin: 0;
        font-size: 0.9rem !important;
        text-transform: capitalize;
    }

    .bienvenida-logo img {
        width: 80px;
        opacity: 0.8;
        filter: drop-shadow(0 0 15px rgba(237, 133, 15, 0.4));
    }

    /* ===== GRID DE TARJETAS ===== */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .dash-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .dash-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: #ed850f;
        transition: width 0.3s ease;
    }

    .dash-card:hover {
        border-color: #ed850f;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        background: #364154;
    }

    .dash-card:hover::after {
        width: 100%;
    }

    .dash-card-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.4rem !important;
    }

    .dash-card-info {
        flex: 1;
    }

    .dash-card-info h3 {
        color: #fff;
        margin: 0 0 4px 0;
        font-size: 1rem;
    }

    .dash-card-info p {
        color: #a0aec0;
        margin: 0;
        font-size: 12px !important;
        line-height: 1.4;
    }

    .dash-card-arrow {
        color: #4a5568;
        font-size: 0.9rem !important;
        transition: all 0.2s ease;
    }

    .dash-card:hover .dash-card-arrow {
        color: #ed850f;
        transform: translateX(4px);
    }

    /* ===== ACCIONES RÁPIDAS ===== */
    .dashboard-secundario {
        background: #2d3748;
        border-radius: 16px;
        padding: 1.5rem 2rem;
        border: 1px solid #4a5568;
    }

    .dashboard-secundario-titulo {
        color: #fff;
        font-size: 1rem;
        margin: 0 0 1.25rem 0;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 1rem;
        border-bottom: 1px solid #4a5568;
    }

    .dashboard-secundario-titulo i {
        color: #ed850f;
    }

    .acciones-rapidas {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .accion-rapida {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        background: rgba(237, 133, 15, 0.1);
        border: 1px solid rgba(237, 133, 15, 0.4);
        border-radius: 8px;
        color: #ed850f;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px !important;
        transition: all 0.2s ease;
    }

    .accion-rapida:hover {
        background: #ed850f;
        color: #fff;
        border-color: #ed850f;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 133, 15, 0.3);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }

        .dashboard-bienvenida {
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
            padding: 2rem 1.5rem;
        }

        .dashboard-bienvenida::before {
            display: none;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .acciones-rapidas {
            flex-direction: column;
        }

        .accion-rapida {
            justify-content: center;
        }
    }
</style>