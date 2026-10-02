<?php

namespace Controllers;

use Models\Productos;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class ProductoController
{

    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    // Datos de un producto con nombres en vez de ids, para el registro de auditoría
    private static function datosAuditoria($producto): array
    {
        $stmt = \Models\ActiveRecord::getDB()->prepare("SELECT nombre_estado_producto FROM estados_productos WHERE id_estado_producto = ?");
        $idEstado = (int)$producto->rela_estado_producto;
        $stmt->bind_param('i', $idEstado);
        $stmt->execute();

        return [
            'nombre' => $producto->nombre_producto_cantina,
            'codigo' => $producto->codigo ?? null,
            'precio' => (float)$producto->precio_producto,
            'estado' => $stmt->get_result()->fetch_column() ?: null,
            'imagen' => $producto->imagen_producto,
        ];
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
        $queryCount = "SELECT COUNT(*) as total FROM productos_cantina";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);


        $productos = Productos::obtenerProductos($paginador);

        $router->render('administrador/productos/listado', [
            'productos' => $productos,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/productos/crear');
    }

    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener datos del formulario
            $datos = [
                'nombre_producto_cantina' => $_POST['nombre_producto_cantina'] ?? '',
                'rela_estado_producto' => $_POST['rela_estado_producto'] ?? 1,
                'precio_producto' => $_POST['precio_producto'] ?? ''
            ];

            $producto = new Productos($datos);

            // Validar datos básicos
            $errores = $producto->validar();

            // Validar imagen
            $erroresImagen = $producto->validarImagen($_FILES['imagen_producto'] ?? null);
            $errores = array_merge($errores, $erroresImagen);

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            // Subir imagen
            $nombreImagen = $producto->subirImagen($_FILES['imagen_producto']);
            if (!$nombreImagen) {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al subir la imagen']);
                return;
            }

            $producto->imagen_producto = $nombreImagen;
            $resultado = $producto->crearProducto();

            if ($resultado['resultado']) {
                \Classes\Auditoria::registrar(
                    'producto.crear',
                    "Creó el producto {$producto->nombre_producto_cantina} ($" . number_format((float)$producto->precio_producto, 0, ',', '.') . ')',
                    'productos_cantina',
                    $resultado['id_insertado'] ?? null,
                    null,
                    self::datosAuditoria($producto)
                );

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Producto creado correctamente',
                    'redirigir' => '/administrador/productos/listado'
                ]);
            } else {
                // Si falla la creación, eliminar la imagen subida
                $producto->eliminarImagen();
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
            header('Location: /administrador/productos/listado');
            exit;
        }

        $producto = Productos::find($id);

        if (!$producto) {
            header('Location: /administrador/productos/listado');
            exit;
        }

        $router->render('administrador/productos/editar', [
            'producto' => $producto
        ]);
    }
    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto = Productos::find($_POST['id_producto_cantina']);

            if (!$producto) {
                echo json_encode(['ok' => false, 'mensaje' => 'Producto no encontrado']);
                exit;
            }

            $datosAntes = self::datosAuditoria($producto);

            $producto->nombre_producto_cantina = $_POST['nombre_producto_cantina'] ?? '';
            $producto->rela_estado_producto = $_POST['rela_estado_producto'] ?? '';
            $producto->precio_producto = $_POST['precio_producto'] ?? '';

            $errores = $producto->validar();

            if (isset($_FILES['imagen_producto']) && $_FILES['imagen_producto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $erroresImagen = $producto->validarImagen($_FILES['imagen_producto']);
                $errores = array_merge($errores, $erroresImagen);
            }

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                exit;
            }

            $imagenAnterior = $producto->imagen_producto;

            if (isset($_FILES['imagen_producto']) && $_FILES['imagen_producto']['error'] === UPLOAD_ERR_OK) {
                $nuevaImagen = $producto->subirImagen($_FILES['imagen_producto']);
                if ($nuevaImagen) {
                    $producto->imagen_producto = $nuevaImagen;
                } else {
                    echo json_encode(['ok' => false, 'mensaje' => 'Error al subir la nueva imagen']);
                    exit;
                }
            }

            $resultado = $producto->actualizar();

            if ($resultado) {
                if (isset($nuevaImagen) && $imagenAnterior) {
                    $rutaImagenAnterior = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/productos/' . $imagenAnterior;
                    if (file_exists($rutaImagenAnterior)) {
                        unlink($rutaImagenAnterior);
                    }
                }

                // Un solo registro por guardado: si cambió el precio queda como "cambio de precio"
                // (con el resto de los cambios incluidos); si no, como "modificación de producto"
                [$antes, $despues] = \Classes\Auditoria::cambios($datosAntes, self::datosAuditoria($producto), array_keys($datosAntes));
                if ($antes) {
                    $cambioPrecio = array_key_exists('precio', $despues);
                    $descripcion = $cambioPrecio
                        ? "Cambió el precio de {$producto->nombre_producto_cantina}: $"
                            . number_format((float)$antes['precio'], 0, ',', '.') . ' → $'
                            . number_format((float)$despues['precio'], 0, ',', '.')
                        : "Modificó el producto {$producto->nombre_producto_cantina}";
                    $otros = array_diff(array_keys($despues), ['precio']);
                    if ($otros) {
                        $descripcion .= ($cambioPrecio ? ' (también: ' : ' (') . implode(', ', $otros) . ')';
                    }
                    \Classes\Auditoria::registrar(
                        $cambioPrecio ? 'precio.producto' : 'producto.modificar',
                        $descripcion,
                        'productos_cantina',
                        $producto->id_producto_cantina,
                        $antes,
                        $despues
                    );
                }

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Producto actualizado correctamente',
                    'redirigir' => '/administrador/productos/listado'
                ]);
            } else {
                if (isset($nuevaImagen)) {
                    $rutaNuevaImagen = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/productos/' . $nuevaImagen;
                    if (file_exists($rutaNuevaImagen)) {
                        unlink($rutaNuevaImagen);
                    }
                }
                echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el producto']);
            }
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

        $id = $datos['id_producto_cantina'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de producto inválido']);
            return;
        }

        $producto = Productos::find($id);

        if (!$producto) {
            echo json_encode(['ok' => false, 'mensaje' => 'Producto no encontrado']);
            return;
        }

        $resultado = $producto->darDeBaja();

        if ($resultado) {
            \Classes\Auditoria::registrar(
                'producto.baja',
                "Dio de baja (suspendió) el producto {$producto->nombre_producto_cantina}",
                'productos_cantina',
                $id
            );
            echo json_encode(['ok' => true, 'mensaje' => 'Producto suspendido correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al suspender el producto']);
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

        $productos = Productos::buscarProductos($termino);

        echo json_encode(['ok' => true, 'productos' => $productos]);
    }

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_producto_cantina' => 'ID',
                'nombre_producto_cantina' => 'Producto',
                'precio_producto' => 'Precio',
                'nombre_estado_producto' => 'Estado'
            ];

            // Capturar término de búsqueda
            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN PRODUCTOS EXCEL: Buscando término: " . $termino);

                // Buscar productos específicos
                $productos = Productos::buscarProductos($termino);
                if (!empty($productos)) {
                    $datos = $productos;
                } else {
                    $datos = [];
                }
            } else {
                // Obtener todos los productos
                $productos = Productos::obtenerProductos(); // Asume que tienes este método
                foreach ($productos as $p) {
                    $datos[] = (array)$p;
                }
            }

            $nombreArchivo = !empty($termino) ? "producto_filtrado_" . self::limpiarNombreArchivo($termino) : "productos_completo";
            $titulo = !empty($termino) ? "Producto Filtrado: $termino" : "Listado Completo de Productos";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {

            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/productos/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_producto_cantina' => 'ID',
                'nombre_producto_cantina' => 'Producto',
                'imagen_producto' => 'Imagen',
                'precio_producto' => 'Precio',
                'nombre_estado_producto' => 'Estado'
            ];

            // Capturar término de búsqueda
            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN PRODUCTOS PDF: Buscando término: " . $termino);

                // Buscar productos específicos
                $productos = Productos::buscarProductos($termino);
                if (!empty($productos)) {
                    $datos = $productos;
                    error_log("EXPORTACIÓN PRODUCTOS PDF: Productos encontrados para exportar: " . count($productos));
                } else {
                    error_log("EXPORTACIÓN PRODUCTOS PDF: No se encontraron productos con término: " . $termino);
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN PRODUCTOS PDF: No hay término de búsqueda, exportando todo");
                // Obtener todos los productos
                $productos = Productos::obtenerProductos();
                foreach ($productos as $p) {
                    $datos[] = (array)$p;
                }
            }

            $nombreArchivo = !empty($termino) ? "producto_filtrado_" . self::limpiarNombreArchivo($termino) : "productos_completo";
            $titulo = !empty($termino) ? "Producto Filtrado: $termino" : "Listado Completo de Productos";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Productos',
                'mostrar_imagenes' => true,
                'columna_imagen' => 'imagen_producto',
                'ruta_imagenes' => '/assets/img/productos/', // Ajusta según tu estructura
                'ancho_imagen' => 25,
                'alto_imagen' => 30
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN PRODUCTOS PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/productos/listado');
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

    public static function reportes(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/cantina/reportes', [
            'titulo' => 'Reportes Gráficos de Entradas'
        ]);
    }

    public static function obtenerDatosGrafico()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $tipo = $_GET['tipo'] ?? 'ventas-periodo';
        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $agrupar = $_GET['agrupar'] ?? 'dia';

        try {
            $datos = match ($tipo) {
                'ventas-periodo' => \Classes\GeneradorGraficos::obtenerVentasProductos($fechaDesde, $fechaHasta, $agrupar),
                'productos-populares' => \Classes\GeneradorGraficos::obtenerProductosMasVendidos($fechaDesde, $fechaHasta),
                'formas-pago' => \Classes\GeneradorGraficos::obtenerEstadisticasFormasPago($fechaDesde, $fechaHasta),
                'stock-productos' => \Classes\GeneradorGraficos::obtenerEstadisticasStock($fechaDesde, $fechaHasta),
                'rendimiento-vendedores' => \Classes\GeneradorGraficos::obtenerRendimientoVendedores($fechaDesde, $fechaHasta),
                'tipos-comprobante' => \Classes\GeneradorGraficos::obtenerEstadisticasTiposComprobante($fechaDesde, $fechaHasta),
                'ventas-caja' => \Classes\GeneradorGraficos::obtenerVentasPorCaja($fechaDesde, $fechaHasta),
                'ventas-fichas' => \Classes\GeneradorGraficos::obtenerVentasFichas($fechaDesde, $fechaHasta),
                default => []
            };

            echo json_encode([
                'ok' => true,
                'datos' => $datos,
                'tipo' => $tipo
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // Método auxiliar para limpiar nombres de archivo
    private static function limpiarNombreArchivo($nombre)
    {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        return substr($nombre, 0, 20);
    }
}
