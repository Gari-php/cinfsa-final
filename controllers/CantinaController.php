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

        $estado = isset($datos['estado']) ? (int) $datos['estado'] : (int) $cantina->estado;
        if ($estado !== 0 && $estado !== 1) {
            echo json_encode(['errores' => ['Estado de cantina no válido']]);
            return;
        }

        // La venta de productos necesita al menos una cantina activa
        if ($estado === 0 && (int) $cantina->estado === 1 && Cantina::contarActivasExcepto($id) === 0) {
            echo json_encode(['errores' => ['No podés desactivar la única cantina activa']]);
            return;
        }

        $antes = ['nombre' => $cantina->nombre_cantina, 'estado' => self::nombreEstado($cantina->estado)];

        $cantina->nombre_cantina = $nombre_cantina;
        $cantina->estado = $estado;

        $resultado = $cantina->actualizar();

        if ($resultado) {
            $despues = ['nombre' => $cantina->nombre_cantina, 'estado' => self::nombreEstado($cantina->estado)];
            [$antes, $despues] = \Classes\Auditoria::cambios($antes, $despues, ['nombre', 'estado']);
            if ($despues) {
                \Classes\Auditoria::registrar(
                    'cantina.modificar',
                    "Modificó la cantina {$cantina->nombre_cantina}",
                    'cantina',
                    $id,
                    $antes,
                    $despues
                );
            }
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Cantina actualizada correctamente',
                'redirigir' => '/administrador/cantina/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar la cantina']);
        }
    }

    // Baja lógica: la cantina queda inactiva (se puede reactivar desde Editar)
    public static function eliminar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $id = $datos['id_cantina'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de cantina no válido']);
            return;
        }

        $cantina = Cantina::find($id);

        if (!$cantina) {
            echo json_encode(['ok' => false, 'mensaje' => 'Cantina no encontrada']);
            return;
        }

        if ((int) $cantina->estado === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'La cantina ya está inactiva']);
            return;
        }

        if (Cantina::contarActivasExcepto($id) === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'No podés dar de baja la única cantina activa']);
            return;
        }

        if ($cantina->darDeBaja()) {
            \Classes\Auditoria::registrar(
                'cantina.baja',
                "Dio de baja (desactivó) la cantina {$cantina->nombre_cantina}",
                'cantina',
                $id
            );
            echo json_encode(['ok' => true, 'mensaje' => 'Cantina dada de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al dar de baja la cantina']);
        }
    }

    private static function nombreEstado($estado) {
        return (int) $estado === 1 ? 'Activa' : 'Inactiva';
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