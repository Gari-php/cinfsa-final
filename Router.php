<?php

namespace MVC;

use Middlewares\ValidarModulo;

class Router {
    public array $getRoutes = [];
    public array $postRoutes = [];

    public function get($url, $fn)
    {
        $this->getRoutes[$url] = $fn;
    }

    public function post($url, $fn)
    {
        $this->postRoutes[$url] = $fn;
    }

    public function put($url, $fn)
    {
        $this->postRoutes[$url] = $fn;
    }

    public function comprobarRutas()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if (!$this->validarAccesoConModulos($currentUrl)) {
            // Si es AJAX, devolver JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No tienes permisos para acceder a este módulo'
                ]);
                exit;
            }
            
            // Si no es AJAX, redirigir
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /');
            exit;
        }

        if ($method === 'GET') {
            $fn = $this->getRoutes[$currentUrl] ?? null;
        } else {
            $fn = $this->postRoutes[$currentUrl] ?? null;
        }

        if ($fn) {
            call_user_func($fn, $this);
        } else {
            $this->mostrar404();
        }
    }

    private function validarAccesoConModulos($ruta)
    {
        // ═══════════════════════════════════════════════════════════
        // RUTAS PÚBLICAS (sin login requerido)
        // ═══════════════════════════════════════════════════════════
        $rutasPublicas = [
            '/',
            '/crear-cuenta',
            '/olvide',
            '/recuperar',
            '/confirmar-cuenta',
            '/restablecer',
            '/logaut',
            '/menu',
            '/funciones',
            '/funciones/dia',
            '/peliculas',
            '/api/obtener-trailer',
            '/juegos',
            '/cantina',
            '/mapa',
            '/api/funciones/por-dia',
            '/api/butacas/funcion',
            '/api/carrito/contar'
        ];

        if (in_array($ruta, $rutasPublicas)) {
            return true;
        }

        // ═══════════════════════════════════════════════════════════
        // VERIFICAR LOGIN
        // ═══════════════════════════════════════════════════════════
        if (!isset($_SESSION['login']) || !$_SESSION['login']) {
            return false;
        }

        // ═══════════════════════════════════════════════════════════
        // BUSCAR MÓDULO REQUERIDO
        // ═══════════════════════════════════════════════════════════
        $mapaRutasModulos = $this->obtenerMapaRutasModulos();

        $moduloRequerido = $this->encontrarModuloParaRuta($ruta, $mapaRutasModulos);
        
        // Si no requiere módulo específico, permitir acceso
        if ($moduloRequerido === null) {
            return true;
        }

        // Validar que tenga el módulo
        return ValidarModulo::tiene($moduloRequerido);
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * MAPA DE RUTAS POR MÓDULO
     * ═══════════════════════════════════════════════════════════
     * Define qué prefijos de URL requieren cada módulo
     */
    private function obtenerMapaRutasModulos()
    {
        return [
            // ═══════════════════════════════════════════════════════════
            // MÓDULO: ADMINISTRADOR (perfil 3)
            // ═══════════════════════════════════════════════════════════
            'ADMINISTRADOR' => [
                '/administrador',                        // Dashboard
                
                // Usuarios
                '/administrador/usuarios/',
                
                // Sexo
                '/administrador/sexo/',
                
                // Películas
                '/administrador/peliculas/',
                
                // Salas
                '/administrador/salas/',
                
                // Funciones
                '/administrador/funciones/',
                
                // Turnos
                '/administrador/turnos/',
                
                // Tipos de Entrada
                '/administrador/tipos_entradas/',
                
                // Entradas
                '/administrador/entradas/',
                
                // Productos
                '/administrador/productos/',
                
                // Stock
                '/administrador/stock/',
                
                // Fichas
                '/administrador/fichas/',
                
                // Máquinas
                '/administrador/maquinas/',
                
                // Géneros de Películas
                '/administrador/generos/',
                
                // Cantina
                '/administrador/cantina/',
                
                // Estados de Películas
                '/administrador/estados_peliculas/',
                
                // Butacas
                '/administrador/butacas/',
                
                // Actividad de Cajas
                '/administrador/actividadcajas/',
                
                // Módulos
                '/administrador/modulos/',
                
                // Notificaciones
                '/administrador/notificaciones',
                '/api/notificaciones/',
                
                // API Admin
                '/administrador/api/',
                
                // Carrito (admin puede ver)
                '/api/carrito/contar',
            ],

            // ═══════════════════════════════════════════════════════════
            // MÓDULO: VENTA_FUNCIONES (perfil 4)
            // ═══════════════════════════════════════════════════════════
            'VENTA_FUNCIONES' => [
                '/vendedor/funciones/',
                '/api/vendedor/butacas/',
                '/api/vendedor/ticket/',
                '/api/vendedor/venta/',
                '/vendedor/caja/pdf-arqueo',
                '/vendedor/caja/resumen-cierre'
            ],

            // ═══════════════════════════════════════════════════════════
            // MÓDULO: GESTION_CAJA (perfil 4)
            // ═══════════════════════════════════════════════════════════
            'GESTION_CAJA' => [
                '/vendedor/caja',
                '/vendedor/caja/',
                '/api/caja/',
                '/vendedor/ventas/',
                '/api/ventas/',
                '/vendedor/movimientos',
                '/api/movimientos/',
                '/vendedorproductos/caja',      // NUEVO
                '/vendedorproductos/caja/',     // NUEVO
                '/vendedorproductos/productos/', // NUEVO
                '/vendedorproductos/ventas/',   // NUEVO
                '/vendedorproductos/movimientos',
                '/vendedorproductos/api/',
                '/vendedorproductos/',
                '/vendedorproductos/ventas/ticket',
                '/vendedor/caja/pdf-arqueo',
                '/vendedor/caja/resumen-cierre',
                '/vendedorproductos/buscar-ficha'
            ],
            'VENTA_PRODUCTOS' => [
                '/vendedorproductos',
                '/vendedorproductos/',
                '/vendedorproductos/api/vendedores',
                '/vendedorproductos/ventas/ticket',
                
            ],
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * ENCUENTRA EL MÓDULO REQUERIDO PARA UNA RUTA
     * ═══════════════════════════════════════════════════════════
     */
    
    
    private function encontrarModuloParaRuta($ruta, $mapaRutasModulos)
    {
 
        foreach ($mapaRutasModulos as $modulo => $prefijos) {
            foreach ($prefijos as $prefijo) {
                // Verificar si la ruta comienza con el prefijo
                if (strpos($ruta, $prefijo) === 0) {
                    return $modulo;
                }
            }
        }

        // ═══════════════════════════════════════════════════════════
        // RUTAS ESPECIALES QUE NO REQUIEREN MÓDULO PERO SÍ LOGIN
        // ═══════════════════════════════════════════════════════════
        $rutasConLoginSinModulo = [
            '/vendedor',        // Dashboard general de vendedor
            '/sala-juegos',
            '/perfil',
            '/perfil/actualizar'
        ];

        if (in_array($ruta, $rutasConLoginSinModulo)) {
            return null; // Permite acceso si está logueado
        }

        // Si no coincide con ningún patrón, requiere login pero no módulo específico
        return null;
    }

    private function mostrar404()
    {
        http_response_code(404);
        echo "
        <html>
        <head><title>404 - Página no encontrada</title></head>
        <body style='font-family: Arial; text-align: center; margin-top: 100px;'>
            <h1>404 - Página no encontrada</h1>
            <p>La ruta solicitada no existe o no tienes permisos para acceder.</p>
            <a href='/'>Volver al inicio</a>
        </body>
        </html>";
    }

    public function render($view, $datos = [])
    {
        /** @var array<string, mixed> $datos */
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        ob_start();
        $vista = basename($view);
        include_once __DIR__ . "/views/$view.php";
        $contenido = ob_get_clean();

        if (strpos($view, 'cliente/') === 0) {
            include_once __DIR__ . '/views/layout_cliente.php';
        } elseif (strpos($view, 'administrador/') === 0) {
            include_once __DIR__ . '/views/layout_administrador.php';
        } elseif (strpos($view, 'vendedor/') === 0) {
            include_once __DIR__ . '/views/layout_vendedor.php';
        } elseif (strpos($view, 'vendedorproductos/') === 0) {
            include_once __DIR__ . '/views/layout_vendedor.php';  // Usa el mismo layout del vendedor por ahora
        } else {
            include_once __DIR__ . '/views/layout.php';
        }
    }
}