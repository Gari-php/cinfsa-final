<?php
namespace Controllers;

use Classes\Notificaciones;
use MVC\Router;

class NotificacionesController {

    private static function verificarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && isset($_SESSION['id_usuario']);
    }

    public static function obtener() {
        header('Content-Type: application/json');
        
        if (!self::verificarSesion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $usuario_id = $_SESSION['id_usuario'];
        
        try {
            // CAMBIO: Solo obtener notificaciones NO LEÍDAS para la campanita
            $notificaciones = Notificaciones::obtenerPorUsuario($usuario_id, 10, true); // true = solo_no_leidas
            $total_no_leidas = Notificaciones::contarNoLeidas($usuario_id);

            echo json_encode([
                'ok' => true,
                'notificaciones' => $notificaciones,
                'total_no_leidas' => $total_no_leidas
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al obtener notificaciones'
            ]);
        }
        
        exit;
    }

    public static function contarNoLeidas() {
        header('Content-Type: application/json');
        
        if (!self::verificarSesion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $usuario_id = $_SESSION['id_usuario'];
        $contador = Notificaciones::contarNoLeidas($usuario_id);

        echo json_encode([
            'ok' => true,
            'contador' => $contador
        ]);
        
        exit;
    }

    public static function marcarLeida() {
        header('Content-Type: application/json');
        
        if (!self::verificarSesion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $notificacion_id = $datos['id_notificacion'] ?? null;
        $usuario_id = $_SESSION['id_usuario'];

        if (!$notificacion_id) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de notificación requerido']);
            exit;
        }

        $resultado = Notificaciones::marcarComoLeida($notificacion_id, $usuario_id);

        if ($resultado) {
            $nuevo_contador = Notificaciones::contarNoLeidas($usuario_id);
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Notificación marcada como leída',
                'nuevo_contador' => $nuevo_contador
            ]);
        } else {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al marcar notificación como leída'
            ]);
        }
        
        exit;
    }

  
public static function marcarTodasLeidas() {

    
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    if (!self::verificarSesion()) {
        echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
        exit;
    }

    try {
        $usuario_id = $_SESSION['id_usuario'];
       
        
        $resultado = Notificaciones::marcarTodasComoLeidas($usuario_id);
        

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Todas las notificaciones marcadas como leídas',
                'nuevo_contador' => 0
            ]);
        } else {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al marcar notificaciones como leídas'
            ]);
        }
    } catch (\Exception $e) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno: ' . $e->getMessage()
        ]);
    }
    
    exit;
}
    public static function index() {
        if (!self::verificarSesion()) {
            header('Location: /login');
            exit;
        }

        $usuario_id = $_SESSION['id_usuario'];
        
        try {
            // Obtener todas las notificaciones del usuario (no solo 10)
            $notificaciones = Notificaciones::obtenerPorUsuario($usuario_id, 100);
            $no_leidas = Notificaciones::contarNoLeidas($usuario_id);
            
            $router = new Router();
            $router->render('administrador/notificaciones/index', [
                'notificaciones' => $notificaciones,
                'no_leidas' => $no_leidas
            ]);
        } catch (\Exception $e) {
            $router = new Router();
            $router->render('administrador/notificaciones/index', [
                'notificaciones' => [],
                'no_leidas' => 0,
                'error' => 'Error al cargar notificaciones'
            ]);
        }
    }
    public static function verificarFuncionesVencidas() {
        header('Content-Type: application/json');
        
        if (!self::verificarSesion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $resultado = Notificaciones::verificarFuncionesVencidas();
            echo json_encode($resultado);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al verificar funciones vencidas'
            ]);
        }
        
        exit;
    }
}