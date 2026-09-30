<?php
namespace Controllers;

use Models\Ficha;
use MVC\Router;

class FichaController {

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
        
        $fichas = Ficha::obtenerTodas();
        $router->render('administrador/fichas/listado', [
            'fichas' => $fichas
        ]);
    }

    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // Cargar solo máquinas activas
        $db = \Models\ActiveRecord::getDB();
        $maquinas = $db->query("SELECT * FROM maquinas WHERE estado = 1 ORDER BY maquinas_nombre ASC")->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/fichas/crear', [
            'maquinas' => $maquinas
        ]);
    }


    

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $ficha = new Ficha($datos);
            $errores = $ficha->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $ficha->crearFicha();
            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Ficha creada correctamente',
                    'redirigir' => '/administrador/fichas/listado'
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
            header('Location: /administrador/fichas/listado');
            exit;
        }
        $ficha = Ficha::find($id);
        if (!$ficha) {
            header('Location: /administrador/fichas/listado');
            exit;
        }
        $router->render('administrador/fichas/editar', [
            'ficha' => $ficha
        ]);
    }

    public static function actualizar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }
        $datos = json_decode(file_get_contents('php://input'), true);
        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        $id = $datos['id_fichas'] ?? null;
        $precio = $datos['precio_ficha'] ?? '';
        $cantidad = $datos['cantidad_ficha'] ?? '';
        $maquina = $datos['rela_maquinas'] ?? '';

        $errores = [];
        if (!$id || !is_numeric($id)) $errores[] = 'ID inválido';
        if (!$precio || $precio <= 0) $errores[] = 'El precio es obligatorio';
        if (!$cantidad || $cantidad <= 0) $errores[] = 'La cantidad es obligatoria';


        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $ficha = Ficha::find($id);
        if (!$ficha) {
            echo json_encode(['ok' => false, 'mensaje' => 'Ficha no encontrada']);
            return;
        }

        $ficha->precio_ficha = $precio;
        $ficha->cantidad_ficha = $cantidad;
        $resultado = $ficha->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Ficha actualizada correctamente',
                'redirigir' => '/administrador/fichas/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar']);
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
        $ficha = Ficha::buscarFicha($termino);
        if ($ficha) {
            echo json_encode(['ok' => true, 'ficha' => $ficha]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la ficha']);
        }
    }
}
