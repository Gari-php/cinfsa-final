<?php
namespace Controllers;

use Models\Cantina;
use MVC\Router;

class CantinaController {

    
    private static function verificarAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    public static function index(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $cantinas = Cantina::obtenerTodas();
        
        $router->render('administrador/cantina/listado', [
            'cantinas' => $cantinas
        ]);
    }


    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/cantina/crear');
    }

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $cantina = new Cantina($datos);
            $errores = $cantina->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $cantina->crearCantina();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Cantina creada correctamente',
                    'redirigir' => '/administrador/cantina/listado'
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar en la base de datos']);
            }
        }
    }

    
    public static function editar(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/cantina/listado');
            exit;
        }

        $cantina = Cantina::find($id);

        if (!$cantina) {
            header('Location: /administrador/cantina/listado');
            exit;
        }

        $router->render('administrador/cantina/editar', [
            'cantina' => $cantina
        ]);
    }

    
    public static function actualizar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        $id = $datos['id_cantina'] ?? null;
        $nombre_cantina = $datos['nombre_cantina'] ?? '';

        // Validaciones
        $errores = [];
        if (!$id || !is_numeric($id)) $errores[] = 'ID de cantina no válido';
        if (!$nombre_cantina) $errores[] = 'El nombre de la cantina es obligatorio';

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $cantina = Cantina::find($id);

        if (!$cantina) {
            echo json_encode(['ok' => false, 'mensaje' => 'Cantina no encontrada']);
            return;
        }

        $cantina->nombre_cantina = $nombre_cantina;

        $resultado = $cantina->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Cantina actualizada correctamente',
                'redirigir' => '/administrador/cantina/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar la cantina']);
        }
    }

   
    public static function buscar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término está vacío']);
            return;
        }

        $cantina = Cantina::buscarCantina($termino);

        if ($cantina) {
            echo json_encode(['ok' => true, 'cantina' => $cantina]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la cantina']);
        }
    }

    
    public static function verContenido(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/cantina/listado');
            exit;
        }

        $cantina = Cantina::find($id);

        if (!$cantina) {
            header('Location: /administrador/cantina/listado');
            exit;
        }

        $contenido = Cantina::obtenerContenidoCantina($id);

        $router->render('administrador/cantina/contenido', [
            'cantina' => $cantina,
            'productos' => $contenido['productos'],
            'maquinas' => $contenido['maquinas']
        ]);
    }
}