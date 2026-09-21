<?php
namespace Controllers;

use Models\Servicio;
use MVC\Router;

class ServicioController {

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
        $registrosPorPagina = 10;
        $paginador = new \Classes\Paginador($paginaActual, $registrosPorPagina);

        // Contar total
        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM servicios_proveedor";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener servicios con LIMIT
        $servicios = Servicio::obtenerTodos($paginador);
        
        $router->render('administrador/servicios/listado', [
            'servicios' => $servicios,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $proveedores = Servicio::obtenerProveedoresActivos();

        $router->render('administrador/servicios/crear', [
            'proveedores' => $proveedores
        ]);
    }

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $servicio = new Servicio($datos);
            $errores = $servicio->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $servicio->crearServicio();
            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Servicio creado correctamente',
                    'redirigir' => '/administrador/servicios/listado'
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
            header('Location: /administrador/servicios/listado');
            exit;
        }

        $servicio = Servicio::find($id);
        if (!$servicio) {
            header('Location: /administrador/servicios/listado');
            exit;
        }

        $proveedores = Servicio::obtenerProveedoresActivos();

        $router->render('administrador/servicios/editar', [
            'servicio' => $servicio,
            'proveedores' => $proveedores
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

        $id = $datos['id_servicio'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['errores' => ['ID inválido']]);
            return;
        }

        $servicio = Servicio::find($id);
        if (!$servicio) {
            echo json_encode(['ok' => false, 'mensaje' => 'Servicio no encontrado']);
            return;
        }

        $servicio->rela_proveedor = $datos['rela_proveedor'] ?? null;
        $servicio->nombre_servicio = $datos['nombre_servicio'] ?? '';
        $servicio->descripcion = $datos['descripcion'] ?? '';
        $servicio->codigo_servicio = $datos['codigo_servicio'] ?? '';
        $servicio->categoria_servicio = $datos['categoria_servicio'] ?? 'otros';
        $servicio->monto_base = $datos['monto_base'] ?? 0;
        $servicio->tiene_monto_variable = $datos['tiene_monto_variable'] ?? 0;
        $servicio->frecuencia_pago = $datos['frecuencia_pago'] ?? 'mensual';
        $servicio->activo = $datos['activo'] ?? 1;

        $errores = $servicio->validar();

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $resultado = $servicio->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Servicio actualizado correctamente',
                'redirigir' => '/administrador/servicios/listado'
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

        $servicio = Servicio::find($id);
        if (!$servicio) {
            echo json_encode(['ok' => false, 'mensaje' => 'Servicio no encontrado']);
            return;
        }

        $resultado = $servicio->darDeBaja();
        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Servicio dado de baja correctamente']);
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

        $servicio = Servicio::buscarServicio($termino);
        if ($servicio) {
            echo json_encode(['ok' => true, 'servicio' => $servicio]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró el servicio']);
        }
    }
}