<?php

namespace Controllers;

use Models\Proveedor;
use MVC\Router;

class ProveedorController
{

    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    public static function index(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // PAGINACIÓN
        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 4;
        $paginador = new \Classes\Paginador($paginaActual, $registrosPorPagina);

        // Contar total
        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM proveedores";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener proveedores con LIMIT
        $proveedores = Proveedor::obtenerTodos($paginador);

        $router->render('administrador/proveedores/listado', [
            'proveedores' => $proveedores,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/proveedores/crear', []);
    }

    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $proveedor = new Proveedor($datos);
            $errores = $proveedor->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $proveedor->crearProveedor();
            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Proveedor creado correctamente',
                    'redirigir' => '/administrador/proveedores/listado'
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar en la base de datos']);
            }
        }
    }

    public static function editar(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/proveedores/listado');
            exit;
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            header('Location: /administrador/proveedores/listado');
            exit;
        }

        $router->render('administrador/proveedores/editar', [
            'proveedor' => $proveedor
        ]);
    }

    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        $id = $datos['id_proveedor'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['errores' => ['ID inválido']]);
            return;
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            echo json_encode(['ok' => false, 'mensaje' => 'Proveedor no encontrado']);
            return;
        }

        $proveedor->razon_social = $datos['razon_social'] ?? '';
        $proveedor->nombre_comercial = $datos['nombre_comercial'] ?? '';
        $proveedor->rut = $datos['rut'] ?? '';
        $proveedor->tipo_proveedor = $datos['tipo_proveedor'] ?? 'otros';
        $proveedor->telefono = $datos['telefono'] ?? '';
        $proveedor->email = $datos['email'] ?? '';
        $proveedor->direccion = $datos['direccion'] ?? '';
        $proveedor->sitio_web = $datos['sitio_web'] ?? '';
        $proveedor->notas = $datos['notas'] ?? '';
        $proveedor->activo = $datos['activo'] ?? 1;

        $errores = $proveedor->validar();

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $resultado = $proveedor->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Proveedor actualizado correctamente',
                'redirigir' => '/administrador/proveedores/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar']);
        }
    }

    public static function eliminar()
    {
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

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            echo json_encode(['ok' => false, 'mensaje' => 'Proveedor no encontrado']);
            return;
        }

        $resultado = $proveedor->darDeBaja();
        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Proveedor dado de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al dar de baja']);
        }
    }

    public static function buscar()
    {
        header('Content-Type: application/json');

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

        $proveedores = Proveedor::buscarProveedor($termino);

        echo json_encode(['ok' => true, 'proveedores' => $proveedores]);
    }
}
