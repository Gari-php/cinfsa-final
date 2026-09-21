<?php

namespace Controllers;

use Models\ActiveRecord;
use Classes\GeneradorGraficos;
use MVC\Router;
use Classes\Paginador;

class MovimientosWebController
{


    public static function listado(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            header('Location: /');
            exit;
        }

        $fechaDesde = $_GET['fecha_desde'] ?? '';
        $fechaHasta = $_GET['fecha_hasta'] ?? '';

        // PAGINACIÓN
        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 4;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        $db = ActiveRecord::getDB();

        $whereFechas = "";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(fecha_creacion) BETWEEN '" . $db->escape_string($fechaDesde) . "' AND '" . $db->escape_string($fechaHasta) . "'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(fecha_creacion) >= '" . $db->escape_string($fechaDesde) . "'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(fecha_creacion) <= '" . $db->escape_string($fechaHasta) . "'";
        }

        // Contar total (con filtro)
        $queryCount = "SELECT COUNT(*) as total FROM ordenes {$whereFechas}";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener órdenes con LIMIT (con filtro)
        $ordenes = self::obtenerOrdenesConDetalle($paginador, $fechaDesde, $fechaHasta);

        $router->render('administrador/movimientos-web/listado', [
            'ordenes' => $ordenes,
            'paginador' => $paginador,
            'usuario' => $_SESSION,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);
    }


    public static function reportes(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/movimientos-web/reportes', [
            'usuario' => $_SESSION
        ]);
    }


    public static function datosGrafico()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $tipo = $_GET['tipo'] ?? 'ventas-periodo';
        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $agrupar = $_GET['agrupar'] ?? 'dia';

        try {
            $datos = match ($tipo) {
                'ventas-periodo' => GeneradorGraficos::obtenerVentasWeb($fechaDesde, $fechaHasta, $agrupar),
                'distribucion-productos' => GeneradorGraficos::obtenerDistribucionProductosWeb($fechaDesde, $fechaHasta),
                'top-cantina' => GeneradorGraficos::obtenerTopProductosCantinaWeb($fechaDesde, $fechaHasta),
                'peliculas-populares' => GeneradorGraficos::obtenerPeliculasMasVendidasWeb($fechaDesde, $fechaHasta),
                'estados-ordenes' => GeneradorGraficos::obtenerEstadosOrdenesWeb($fechaDesde, $fechaHasta),
                'metodos-pago' => GeneradorGraficos::obtenerMetodosPagoWeb($fechaDesde, $fechaHasta),
                'top-maquinas' => GeneradorGraficos::obtenerTopMaquinasWeb($fechaDesde, $fechaHasta),
                'ocupacion-salas' => GeneradorGraficos::obtenerOcupacionSalasWeb($fechaDesde, $fechaHasta),
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
                'mensaje' => 'Error al obtener datos: ' . $e->getMessage()
            ]);
        }
    }


    public static function detalle(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            header('Location: /');
            exit;
        }

        $idOrden = $_GET['id'] ?? null;

        if (!$idOrden) {
            header('Location: /administrador/movimientos-web/listado');
            exit;
        }

        $orden = self::obtenerOrdenCompleta($idOrden);

        if (!$orden) {
            header('Location: /administrador/movimientos-web/listado');
            exit;
        }

        $router->render('administrador/movimientos-web/detalle', [
            'orden' => $orden,
            'usuario' => $_SESSION
        ]);
    }

    public static function cancelar()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $idOrden = $datos['id_orden'] ?? null;

        if (!$idOrden) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de orden requerido']);
            return;
        }

        try {
            $db = ActiveRecord::getDB();

            // Verificar que la orden exista y no esté ya cancelada
            $query = "SELECT estado FROM ordenes WHERE id_orden = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $idOrden);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Orden no encontrada']);
                return;
            }

            $orden = $resultado->fetch_assoc();

            if ($orden['estado'] === 'cancelado') {
                echo json_encode(['ok' => false, 'mensaje' => 'La orden ya está cancelada']);
                return;
            }

            // Cancelar la orden
            $query = "UPDATE ordenes SET estado = 'cancelado', fecha_actualizacion = NOW() WHERE id_orden = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $idOrden);

            if ($stmt->execute()) {
                // Si era pagada, devolver stock
                if ($orden['estado'] === 'pagado') {
                    self::devolverStockOrden($idOrden);
                }

                echo json_encode(['ok' => true, 'mensaje' => 'Orden cancelada correctamente']);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al cancelar la orden']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }
    public static function buscar()
    {
        header('Content-Type: application/json');



        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término de búsqueda está vacío']);
            return;
        }

        $db = ActiveRecord::getDB();
        $termino = $db->escape_string($termino);

        $query = "SELECT 
                    o.id_orden,
                    o.numero_orden,
                    o.total,
                    o.estado,
                    o.metodo_pago,
                    o.payment_id,
                    o.fecha_creacion,
                    o.fecha_pago,
                    u.nombre_usuario,
                    u.email,
                    COUNT(DISTINCT do.id_detalle) as total_items,
                    SUM(CASE WHEN do.tipo_producto = 'butacas' THEN do.cantidad ELSE 0 END) as butacas,
                    SUM(CASE WHEN do.tipo_producto = 'cantina' THEN do.cantidad ELSE 0 END) as cantina,
                    SUM(CASE WHEN do.tipo_producto = 'fichas' THEN do.cantidad ELSE 0 END) as fichas
                FROM ordenes o
                INNER JOIN usuarios u ON u.id_usuario = o.id_usuario
                LEFT JOIN detalle_orden do ON do.id_orden = o.id_orden
                WHERE o.numero_orden LIKE '%$termino%'
                GROUP BY o.id_orden
                LIMIT 1";

        $resultado = $db->query($query);

        if ($resultado && $resultado->num_rows) {
            $orden = $resultado->fetch_object();
            echo json_encode(['ok' => true, 'orden' => $orden]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la orden']);
        }
    }

    private static function obtenerOrdenesConDetalle($paginador = null, $fechaDesde = '', $fechaHasta = '')
    {
        $db = ActiveRecord::getDB();

        $limit = $paginador ? $paginador->limit() : '';

        $whereFechas = "";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(o.fecha_creacion) BETWEEN '" . $db->escape_string($fechaDesde) . "' AND '" . $db->escape_string($fechaHasta) . "'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(o.fecha_creacion) >= '" . $db->escape_string($fechaDesde) . "'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(o.fecha_creacion) <= '" . $db->escape_string($fechaHasta) . "'";
        }

        $query = "SELECT 
                o.id_orden,
                o.numero_orden,
                o.total,
                o.estado,
                o.metodo_pago,
                o.payment_id,
                o.fecha_creacion,
                o.fecha_pago,
                u.nombre_usuario,
                u.email,
                COUNT(DISTINCT do.id_detalle) as total_items,
                SUM(CASE WHEN do.tipo_producto = 'butacas' THEN do.cantidad ELSE 0 END) as butacas,
                SUM(CASE WHEN do.tipo_producto = 'cantina' THEN do.cantidad ELSE 0 END) as cantina,
                SUM(CASE WHEN do.tipo_producto = 'fichas' THEN do.cantidad ELSE 0 END) as fichas
              FROM ordenes o
              INNER JOIN usuarios u ON u.id_usuario = o.id_usuario
              LEFT JOIN detalle_orden do ON do.id_orden = o.id_orden
              {$whereFechas}
              GROUP BY o.id_orden
              ORDER BY o.fecha_creacion DESC
              {$limit}";

        $resultado = $db->query($query);

        $ordenes = [];
        while ($row = $resultado->fetch_object()) {
            $ordenes[] = $row;
        }

        return $ordenes;
    }

    private static function obtenerOrdenCompleta($idOrden)
    {
        $db = ActiveRecord::getDB();

        // Obtener cabecera
        $query = "SELECT 
            o.*,
            u.nombre_usuario,
            u.email
          FROM ordenes o
          INNER JOIN usuarios u ON u.id_usuario = o.id_usuario
          WHERE o.id_orden = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            return null;
        }

        $orden = $resultado->fetch_object();

        // Obtener detalles
        $query = "SELECT 
            do.*,
            CASE 
                WHEN do.tipo_producto = 'cantina' THEN pc.nombre_producto_cantina
                WHEN do.tipo_producto = 'fichas' THEN m.maquinas_nombre
                WHEN do.tipo_producto = 'butacas' THEN pel.titulo_pelicula
                ELSE do.nombre_producto
            END as producto_nombre,
            CASE
                WHEN do.tipo_producto = 'butacas' THEN CONCAT('Fila ', b.fila_butaca, ' - Butaca ', b.numero_butaca)
                ELSE NULL
            END as info_butaca,
            CASE
                WHEN do.tipo_producto = 'butacas' THEN DATE_FORMAT(f.fecha_hora, '%d/%m/%Y %H:%i')
                ELSE NULL
            END as fecha_funcion
          FROM detalle_orden do
          LEFT JOIN productos_cantina pc ON pc.id_producto_cantina = do.id_producto AND do.tipo_producto = 'cantina'
          LEFT JOIN maquinas m ON m.id_maquinas = do.id_producto AND do.tipo_producto = 'fichas'
          LEFT JOIN butacas b ON b.id_butaca = do.id_butaca AND do.tipo_producto = 'butacas'
          LEFT JOIN funciones f ON f.id_funcion = do.id_funcion AND do.tipo_producto = 'butacas'
          LEFT JOIN peliculas pel ON pel.id_pelicula = f.rela_peliculas
          WHERE do.id_orden = ?
          ORDER BY do.id_detalle";

        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $detalles = [];
        while ($row = $resultado->fetch_object()) {
            $detalles[] = $row;
        }

        $orden->detalles = $detalles;
        return $orden;
    }

    private static function devolverStockOrden($idOrden)
    {
        $db = ActiveRecord::getDB();

        // Obtener detalles de la orden
        $query = "SELECT * FROM detalle_orden WHERE id_orden = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($detalle = $resultado->fetch_assoc()) {
            $tipo = $detalle['tipo_producto'];
            $idProducto = $detalle['id_producto'];
            $cantidad = $detalle['cantidad'];

            // Devolver stock según tipo
            if ($tipo === 'cantina') {
                // Devolver stock en la tabla real (stock_cantina), cantina fija = 1 (mismo criterio que usa el checkout)
                $query = "UPDATE stock_cantina SET stock_cantina = stock_cantina + ? 
              WHERE rela_producto_cantina = ? AND rela_cantina = 1";
                $stmt2 = $db->prepare($query);
                $stmt2->bind_param('ii', $cantidad, $idProducto);
                $stmt2->execute();

                // Sincronizar el estado del producto (Disponible/Agotado) según el nuevo stock
                $queryNuevoStock = "SELECT stock_cantina FROM stock_cantina WHERE rela_producto_cantina = ? AND rela_cantina = 1";
                $stmt3 = $db->prepare($queryNuevoStock);
                $stmt3->bind_param('i', $idProducto);
                $stmt3->execute();
                $filaStock = $stmt3->get_result()->fetch_assoc();

                if ($filaStock) {
                    \Models\Stock::actualizarEstadoProducto($idProducto, $filaStock['stock_cantina']);
                }

                // Registrar movimiento de devolución
                $query = "INSERT INTO movimientos_stock (tipo_producto, id_producto, tipo_movimiento, cantidad, id_usuario, id_orden, fecha, observaciones)
              VALUES ('cantina', ?, 'entrada', ?, ?, ?, NOW(), 'Devolución por cancelación de orden web')";
                $stmt2 = $db->prepare($query);
                $stmt2->bind_param('iiii', $idProducto, $cantidad, $_SESSION['id_usuario'], $idOrden);
                $stmt2->execute();
            } elseif ($tipo === 'fichas') {
                // Obtener rela_fichas de la máquina
                $query = "SELECT rela_fichas FROM maquinas WHERE id_maquinas = ?";
                $stmt2 = $db->prepare($query);
                $stmt2->bind_param('i', $idProducto);
                $stmt2->execute();
                $relaFichas = $stmt2->get_result()->fetch_assoc()['rela_fichas'];

                if ($relaFichas) {
                    $query = "UPDATE fichas SET cantidad_ficha = cantidad_ficha + ? WHERE id_fichas = ?";
                    $stmt2 = $db->prepare($query);
                    $stmt2->bind_param('ii', $cantidad, $relaFichas);
                    $stmt2->execute();
                }
            } elseif ($tipo === 'butacas') {
                $idButaca = $detalle['id_butaca'];
                $idFuncion = $detalle['id_funcion'];

                // Buscar el registro de venta real para esta butaca en esta orden
                $query = "SELECT id_venta_butaca, id_entrada FROM butacas_vendidas WHERE id_butaca = ? AND id_funcion = ? AND id_orden = ?";
                $stmt2 = $db->prepare($query);
                $stmt2->bind_param('iii', $idButaca, $idFuncion, $idOrden);
                $stmt2->execute();
                $venta = $stmt2->get_result()->fetch_assoc();

                if ($venta) {
                    // Cancelar la entrada asociada, para que deje de verse como válida en "Mis Compras" del cliente
                    if ($venta['id_entrada']) {
                        $query = "UPDATE entradas SET estado = -1 WHERE id_entrada = ?";
                        $stmt2 = $db->prepare($query);
                        $stmt2->bind_param('i', $venta['id_entrada']);
                        $stmt2->execute();
                    }

                    // Quitar el registro de venta de la butaca
                    $query = "DELETE FROM butacas_vendidas WHERE id_venta_butaca = ?";
                    $stmt2 = $db->prepare($query);
                    $stmt2->bind_param('i', $venta['id_venta_butaca']);
                    $stmt2->execute();
                }

                // Liberar la butaca: vuelve a "Disponible"
                $query = "UPDATE butacas SET rela_estado_butaca = 1 WHERE id_butaca = ?";
                $stmt2 = $db->prepare($query);
                $stmt2->bind_param('i', $idButaca);
                $stmt2->execute();
            }
        }
    }

    public static function exportar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
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

    public static function exportarExcel()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_orden' => 'ID',
                'numero_orden' => 'N° Orden',
                'nombre_usuario' => 'Cliente',
                'email' => 'Email',
                'estado_desc' => 'Estado',
                'total_items' => 'Items',
                'detalle_productos' => 'Detalle',
                'metodo_pago_desc' => 'Método Pago',
                'total' => 'Total',
                'fecha_creacion' => 'Fecha Creación',
                'fecha_pago' => 'Fecha Pago'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerOrdenesFiltradasParaExportar($fechaDesde, $fechaHasta);

            $nombreArchivo = self::generarNombreArchivo('ordenes_web', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Órdenes Web', $fechaDesde, $fechaHasta);

            \Classes\ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN ÓRDENES WEB EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/movimientos-web/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_orden' => 'ID',
                'numero_orden' => 'N° Orden',
                'nombre_usuario' => 'Cliente',
                'estado_desc' => 'Estado',
                'total_items' => 'Items',
                'metodo_pago_desc' => 'Método',
                'total' => 'Total',
                'fecha_creacion' => 'Fecha Creación'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerOrdenesFiltradasParaExportar($fechaDesde, $fechaHasta);

            $nombreArchivo = self::generarNombreArchivo('ordenes_web', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Órdenes Web', $fechaDesde, $fechaHasta);

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión Cinematográfica'
            ];

            \Classes\ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN ÓRDENES WEB PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/movimientos-web/listado');
            exit;
        }
    }

    private static function obtenerOrdenesFiltradasParaExportar($fechaDesde, $fechaHasta)
    {
        $db = ActiveRecord::getDB();

        $whereFechas = "";
        $fechaDesde = $db->escape_string($fechaDesde);
        $fechaHasta = $db->escape_string($fechaHasta);

        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(o.fecha_creacion) BETWEEN '{$fechaDesde}' AND '{$fechaHasta}'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(o.fecha_creacion) >= '{$fechaDesde}'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(o.fecha_creacion) <= '{$fechaHasta}'";
        }

        $query = "SELECT 
                o.id_orden,
                o.numero_orden,
                o.total,
                o.estado,
                CASE 
                    WHEN o.estado = 'pagado' THEN 'Pagada'
                    WHEN o.estado = 'pendiente' THEN 'Pendiente'
                    WHEN o.estado = 'cancelado' THEN 'Cancelada'
                    WHEN o.estado = 'fallido' THEN 'Fallida'
                    ELSE 'Desconocido'
                END as estado_desc,
                o.metodo_pago,
                CASE
                    WHEN o.metodo_pago = 'credit_card' THEN 'Tarjeta Crédito'
                    WHEN o.metodo_pago = 'debit_card' THEN 'Tarjeta Débito'
                    WHEN o.metodo_pago = 'account_money' THEN 'Dinero en Cuenta'
                    ELSE 'No especificado'
                END as metodo_pago_desc,
                o.payment_id,
                DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion,
                DATE_FORMAT(o.fecha_pago, '%d/%m/%Y %H:%i') as fecha_pago,
                u.nombre_usuario,
                u.email,
                COUNT(DISTINCT do.id_detalle) as total_items,
                SUM(CASE WHEN do.tipo_producto = 'butacas' THEN do.cantidad ELSE 0 END) as butacas,
                SUM(CASE WHEN do.tipo_producto = 'cantina' THEN do.cantidad ELSE 0 END) as cantina,
                SUM(CASE WHEN do.tipo_producto = 'fichas' THEN do.cantidad ELSE 0 END) as fichas,
                CONCAT(
                    CASE WHEN SUM(CASE WHEN do.tipo_producto = 'butacas' THEN do.cantidad ELSE 0 END) > 0 
                        THEN CONCAT(SUM(CASE WHEN do.tipo_producto = 'butacas' THEN do.cantidad ELSE 0 END), ' butacas ') 
                        ELSE '' END,
                    CASE WHEN SUM(CASE WHEN do.tipo_producto = 'cantina' THEN do.cantidad ELSE 0 END) > 0 
                        THEN CONCAT(SUM(CASE WHEN do.tipo_producto = 'cantina' THEN do.cantidad ELSE 0 END), ' cantina ') 
                        ELSE '' END,
                    CASE WHEN SUM(CASE WHEN do.tipo_producto = 'fichas' THEN do.cantidad ELSE 0 END) > 0 
                        THEN CONCAT(SUM(CASE WHEN do.tipo_producto = 'fichas' THEN do.cantidad ELSE 0 END), ' fichas') 
                        ELSE '' END
                ) as detalle_productos
              FROM ordenes o
              INNER JOIN usuarios u ON u.id_usuario = o.id_usuario
              LEFT JOIN detalle_orden do ON do.id_orden = o.id_orden
              {$whereFechas}
              GROUP BY o.id_orden
              ORDER BY o.fecha_creacion DESC";

        $resultado = $db->query($query);
        $datos = [];

        while ($row = $resultado->fetch_assoc()) {
            $datos[] = $row;
        }

        return $datos;
    }

    private static function generarNombreArchivo($prefijo, $fechaDesde, $fechaHasta)
    {
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            return "{$prefijo}_{$fechaDesde}_a_{$fechaHasta}";
        } elseif (!empty($fechaDesde)) {
            return "{$prefijo}_desde_{$fechaDesde}";
        } elseif (!empty($fechaHasta)) {
            return "{$prefijo}_hasta_{$fechaHasta}";
        }
        return "{$prefijo}_completo";
    }

    private static function generarTituloReporte($entidad, $fechaDesde, $fechaHasta)
    {
        $titulo = "Listado de {$entidad}";

        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $titulo .= " - Creadas del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
        } elseif (!empty($fechaDesde)) {
            $titulo .= " - Creadas desde " . date('d/m/Y', strtotime($fechaDesde));
        } elseif (!empty($fechaHasta)) {
            $titulo .= " - Creadas hasta " . date('d/m/Y', strtotime($fechaHasta));
        } else {
            $titulo .= " - Completo";
        }

        return $titulo;
    }
}
