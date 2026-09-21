<?php
namespace Controllers;

use Models\Perfil;
use MVC\Router;

class PerfilController {

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

        // PAGINACIÓN
        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 5;
        $paginador = new \Classes\Paginador($paginaActual, $registrosPorPagina);

        // Contar total
        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM perfiles";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener perfiles con LIMIT
        $perfiles = Perfil::obtenerTodos($paginador);
        
        $router->render('administrador/perfiles/listado', [
            'perfiles' => $perfiles,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/perfiles/crear', []);
    }

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $perfil = new Perfil($datos);
            $errores = $perfil->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $perfil->crearPerfil();
            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Perfil creado correctamente',
                    'redirigir' => '/administrador/perfiles/listado'
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
            header('Location: /administrador/perfiles/listado');
            exit;
        }

        $perfil = Perfil::find($id);
        if (!$perfil) {
            header('Location: /administrador/perfiles/listado');
            exit;
        }

        $router->render('administrador/perfiles/editar', [
            'perfil' => $perfil
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

        $id = $datos['id_perfiles'] ?? null;
        $nombre = $datos['nombre_perfil'] ?? '';
        $permiso = $datos['permiso_perfil'] ?? '';
        $estado = $datos['estado'] ?? 1;

        $errores = [];
        if (!$id || !is_numeric($id)) $errores[] = 'ID inválido';
        if (!$nombre || trim($nombre) === '') $errores[] = 'El nombre es obligatorio';
        if (!$permiso || trim($permiso) === '') $errores[] = 'El permiso es obligatorio';

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $perfil = Perfil::find($id);
        if (!$perfil) {
            echo json_encode(['ok' => false, 'mensaje' => 'Perfil no encontrado']);
            return;
        }

        $perfil->nombre_perfil = $nombre;
        $perfil->permiso_perfil = $permiso;
        $perfil->estado = $estado;
        $resultado = $perfil->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Perfil actualizado correctamente',
                'redirigir' => '/administrador/perfiles/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar']);
        }
    }

    public static function eliminar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $id = $datos['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
            return;
        }

        $perfil = Perfil::find($id);
        if (!$perfil) {
            echo json_encode(['ok' => false, 'mensaje' => 'Perfil no encontrado']);
            return;
        }

        $resultado = $perfil->darDeBaja();
        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Perfil dado de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al dar de baja']);
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

        $perfil = Perfil::buscarPerfil($termino);
        if ($perfil) {
            echo json_encode(['ok' => true, 'perfil' => $perfil]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró el perfil']);
        }
    }
}