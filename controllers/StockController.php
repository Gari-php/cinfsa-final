<?php

namespace Controllers;

use Models\Stock;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class StockController
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

        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 4;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total 
                    FROM stock_cantina sc
                    INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                    INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                    WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        $stocks = Stock::obtenerStockCompleto($paginador);

        $router->render('administrador/stock/listado', [
            'stocks' => $stocks,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $cantinas = Stock::obtenerCantinasDisponibles();

        $router->render('administrador/stock/crear', [
            'cantinas' => $cantinas
        ]);
    }

    public static function apiProductosPorCantina()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $cantina_id = $_GET['cantina_id'] ?? null;

        if (!$cantina_id || !is_numeric($cantina_id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de cantina inválido']);
            return;
        }

        $productos = Stock::obtenerProductosDisponiblesPorCantina($cantina_id);

        echo json_encode(['ok' => true, 'productos' => $productos]);
    }

    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $stock = new Stock($datos);
            $errores = $stock->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $stock->crearStock();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Stock creado correctamente. Estado del producto actualizado automáticamente.',
                    'redirigir' => '/administrador/stock/listado'
                ]);
            } else {
                $mensaje = $resultado['mensaje'] ?? 'Error al guardar en la base de datos';
                echo json_encode(['ok' => false, 'mensaje' => $mensaje]);
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
            header('Location: /administrador/stock/listado');
            exit;
        }

        $stock = Stock::find($id);

        if (!$stock) {
            header('Location: /administrador/stock/listado');
            exit;
        }

        $productos = Stock::obtenerTodosLosProductosDisponibles();
        $cantinas = Stock::obtenerCantinasDisponibles();

        $router->render('administrador/stock/editar', [
            'stock' => $stock,
            'productos' => $productos,
            'cantinas' => $cantinas
        ]);
    }

    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            exit;
        }

        $stock = Stock::find($datos['id_stock_cantina']);

        if (!$stock) {
            echo json_encode(['ok' => false, 'mensaje' => 'Stock no encontrado']);
            exit;
        }

        $stock->stock_cantina = $datos['stock_cantina'] ?? '';
        $stock->rela_producto_cantina = $datos['rela_producto_cantina'] ?? '';
        $stock->rela_cantina = $datos['rela_cantina'] ?? '';

        $errores = $stock->validar();

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            exit;
        }

        $resultado = $stock->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Stock actualizado correctamente',
                'redirigir' => '/administrador/stock/listado'
            ]);
        } else {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al actualizar el stock'
            ]);
        }
    }
    public static function eliminar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_stock_cantina'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de Stock inválido']);
            return;
        }

        $stock = Stock::find($id);

        if (!$stock) {
            echo json_encode(['ok' => false, 'mensaje' => 'Stock no encontrado']);
            return;
        }

        $resultado = $stock->eliminar();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Stock eliminado correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar el stock']);
        }
    }

    public static function buscar()
    {
        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término está vacío']);
            exit;
        }

        try {
            $stocks = Stock::buscarStock($termino);
            echo json_encode(['ok' => true, 'stocks' => $stocks]);
        } catch (\Exception $e) {
            error_log("Error en búsqueda de stock: " . $e->getMessage());
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor']);
        }

        exit;
    }

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/stock/listado');
            exit;
        }

        try {
            $columnas = [
                'id_stock_cantina' => 'ID',
                'nombre_producto_cantina' => 'Producto',
                'nombre_cantina' => 'Cantina',
                'stock_cantina' => 'Stock',
                'nombre_estado_producto' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN STOCK EXCEL: Buscando término: " . $termino);

                $stocks = Stock::buscarStock($termino);
                if (!empty($stocks)) {
                    foreach ($stocks as $s) {
                        $datos[] = [
                            'id_stock_cantina' => $s->id_stock_cantina,
                            'nombre_producto_cantina' => $s->nombre_producto_cantina,
                            'nombre_cantina' => $s->nombre_cantina,
                            'stock_cantina' => $s->stock_cantina,
                            'nombre_estado_producto' => $s->nombre_estado_producto
                        ];
                    }
                    error_log("EXPORTACIÓN STOCK EXCEL: " . count($stocks) . " registros encontrados");
                } else {
                    error_log("EXPORTACIÓN STOCK EXCEL: No se encontraron registros");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN STOCK EXCEL: Exportando todo el stock");
                $stocks = Stock::obtenerStockCompleto();
                foreach ($stocks as $s) {
                    $datos[] = [
                        'id_stock_cantina' => $s->id_stock_cantina,
                        'nombre_producto_cantina' => $s->nombre_producto_cantina,
                        'nombre_cantina' => $s->nombre_cantina,
                        'stock_cantina' => $s->stock_cantina,
                        'nombre_estado_producto' => $s->nombre_estado_producto
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "stock_filtrado_" . self::limpiarNombreArchivo($termino) : "stock_completo";
            $titulo = !empty($termino) ? "Stock Filtrado: $termino" : "Listado Completo de Stock";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN STOCK EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/stock/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/stock/listado');
            exit;
        }

        try {
            $columnas = [
                'id_stock_cantina' => 'ID',
                'nombre_producto_cantina' => 'Producto',
                'nombre_cantina' => 'Cantina',
                'stock_cantina' => 'Stock',
                'nombre_estado_producto' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $stocks = Stock::buscarStock($termino);
                if (!empty($stocks)) {
                    foreach ($stocks as $s) {
                        $datos[] = [
                            'id_stock_cantina' => $s->id_stock_cantina,
                            'nombre_producto_cantina' => $s->nombre_producto_cantina,
                            'nombre_cantina' => $s->nombre_cantina,
                            'stock_cantina' => $s->stock_cantina,
                            'nombre_estado_producto' => $s->nombre_estado_producto
                        ];
                    }
                }
            } else {
                $stocks = Stock::obtenerStockCompleto();
                foreach ($stocks as $s) {
                    $datos[] = [
                        'id_stock_cantina' => $s->id_stock_cantina,
                        'nombre_producto_cantina' => $s->nombre_producto_cantina,
                        'nombre_cantina' => $s->nombre_cantina,
                        'stock_cantina' => $s->stock_cantina,
                        'nombre_estado_producto' => $s->nombre_estado_producto
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "stock_filtrado_" . self::limpiarNombreArchivo($termino) : "stock_completo";
            $titulo = !empty($termino) ? "Stock Filtrado: $termino" : "Listado Completo de Stock";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Stock',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN STOCK PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/stock/listado');
            exit;
        }
    }

    public static function exportar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $tipo = $_GET['tipo'] ?? '';

        switch ($tipo) {
            case 'excel':
                self::exportarExcel();
                break;
            case 'pdf':
                self::exportarPDF();
                break;
            default:
                echo json_encode(['ok' => false, 'mensaje' => 'Tipo de exportación no válido']);
                return;
        }
    }
    private static function limpiarNombreArchivo($nombre)
    {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        return substr($nombre, 0, 20);
    }
}
