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

    /**
     * Cancela UNA entrada de una compra web pagada (el cliente pidió devolver parte de la orden).
     * La entrada queda cancelada y la butaca libre. La plata se cobró por Mercado Pago: el
     * reembolso lo hace el administrador desde su cuenta de Mercado Pago (acá no se toca ninguna caja).
     */
    public static function cancelarEntrada()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $idDetalle = filter_var($datos['id_detalle'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$idDetalle) {
            echo json_encode(['ok' => false, 'mensaje' => 'Entrada inválida']);
            return;
        }

        $db = ActiveRecord::getDB();
        $db->begin_transaction();

        try {
            $stmt = $db->prepare("SELECT do.id_orden, do.id_butaca, do.id_funcion, do.precio_unitario,
                                         o.estado, o.numero_orden, o.payment_id,
                                         p.titulo_pelicula, f.rela_salas, b.fila_butaca, b.numero_butaca
                                  FROM detalle_orden do
                                  INNER JOIN ordenes o ON o.id_orden = do.id_orden
                                  INNER JOIN funciones f ON f.id_funcion = do.id_funcion
                                  INNER JOIN peliculas p ON p.id_pelicula = f.rela_peliculas
                                  INNER JOIN butacas b ON b.id_butaca = do.id_butaca
                                  WHERE do.id_detalle = ? AND do.tipo_producto = 'butacas'
                                  FOR UPDATE");
            $stmt->bind_param('i', $idDetalle);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();

            if (!$item) {
                throw new \RuntimeException('Entrada no encontrada');
            }
            if ($item['estado'] !== 'pagado') {
                throw new \RuntimeException('Solo se pueden cancelar entradas de compras pagadas');
            }

            // La entrada de esa butaca en esta orden (bloqueada mientras se cancela)
            $stmt = $db->prepare("SELECT bv.id_venta_butaca, e.id_entrada, e.estado
                                  FROM butacas_vendidas bv
                                  INNER JOIN entradas e ON e.id_entrada = bv.id_entrada
                                  WHERE bv.id_butaca = ? AND bv.id_funcion = ? AND bv.id_orden = ?
                                  FOR UPDATE");
            $stmt->bind_param('iii', $item['id_butaca'], $item['id_funcion'], $item['id_orden']);
            $stmt->execute();
            $venta = $stmt->get_result()->fetch_assoc();

            if (!$venta || (int)$venta['estado'] === -1) {
                throw new \RuntimeException('Esta entrada ya fue cancelada');
            }
            if ((int)$venta['estado'] === 2) {
                throw new \RuntimeException('Esta entrada ya se usó para ingresar a la función: no se puede cancelar');
            }

            $stmt = $db->prepare("UPDATE entradas SET estado = -1 WHERE id_entrada = ? AND estado IN (0, 1)");
            $stmt->bind_param('i', $venta['id_entrada']);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) {
                throw new \RuntimeException('Esta entrada ya fue cancelada');
            }

            // Sin el registro de venta, la butaca queda libre para esa función
            $stmt = $db->prepare("DELETE FROM butacas_vendidas WHERE id_venta_butaca = ?");
            $stmt->bind_param('i', $venta['id_venta_butaca']);
            $stmt->execute();

            $db->commit();
        } catch (\RuntimeException $e) {
            $db->rollback();
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
            return;
        } catch (\Throwable $e) {
            $db->rollback();
            error_log('cancelarEntrada(): ' . $e->getMessage());
            echo json_encode(['ok' => false, 'mensaje' => 'Error al cancelar la entrada']);
            return;
        }

        $monto = '$' . number_format((float)$item['precio_unitario'], 0, ',', '.');
        $butaca = "{$item['titulo_pelicula']}, Sala {$item['rela_salas']} · Fila {$item['fila_butaca']} · Asiento {$item['numero_butaca']}";
        \Classes\Auditoria::registrar(
            'orden.cancelar_entrada',
            "Canceló la entrada #{$venta['id_entrada']} de la compra online {$item['numero_orden']} ({$butaca}); "
                . "reembolso de {$monto} a hacer por Mercado Pago",
            'entradas',
            (int)$venta['id_entrada'],
            ['estado' => 'activa'],
            ['estado' => 'cancelada', 'reembolso_pendiente' => $monto]
        );

        echo json_encode([
            'ok' => true,
            'mensaje' => "Entrada cancelada y butaca liberada. Recordá hacer el reembolso de {$monto} desde tu cuenta de Mercado Pago"
                . ($item['payment_id'] ? " (pago N° {$item['payment_id']})." : '.'),
        ]);
    }

    /**
     * Devuelve parte de una línea de productos o fichas de una compra web pagada. Solo se puede
     * devolver lo que el cliente todavía no retiró; vuelve al stock y deja de estar para retirar.
     * El dinero se reintegra por Mercado Pago, desde la cuenta del administrador.
     */
    public static function devolverProducto()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $entero = ['options' => ['min_range' => 1]];
        $idDetalle = filter_var($datos['id_detalle'] ?? null, FILTER_VALIDATE_INT, $entero);
        $cantidad = filter_var($datos['cantidad'] ?? null, FILTER_VALIDATE_INT, $entero);
        if (!$idDetalle || !$cantidad) {
            echo json_encode(['ok' => false, 'mensaje' => 'Indicá cuántas unidades devolver (un número entero mayor a 0)']);
            return;
        }

        $db = ActiveRecord::getDB();
        $db->begin_transaction();

        try {
            $stmt = $db->prepare("SELECT o.id_orden, o.estado, o.numero_orden, o.payment_id
                                  FROM detalle_orden d
                                  INNER JOIN ordenes o ON o.id_orden = d.id_orden
                                  WHERE d.id_detalle = ? AND d.tipo_producto IN ('cantina', 'fichas')
                                  FOR UPDATE");
            $stmt->bind_param('i', $idDetalle);
            $stmt->execute();
            $orden = $stmt->get_result()->fetch_assoc();

            if (!$orden) {
                throw new \RuntimeException('Producto no encontrado en la orden');
            }
            if ($orden['estado'] !== 'pagado') {
                throw new \RuntimeException('Solo se pueden devolver productos de compras pagadas');
            }

            // Las líneas quedan bloqueadas hasta el commit: no se cruza con una entrega en la cantina
            $items = \Models\RetiroOrden::items((int)$orden['id_orden'], true);
            $item = current(array_filter($items, fn($i) => (int)$i['id_detalle'] === $idDetalle));

            if ($item['quedan'] === 0) {
                throw new \RuntimeException('No queda nada para devolver: ya se retiró o se devolvió todo');
            }
            if ($cantidad > $item['quedan']) {
                throw new \RuntimeException("Solo se pueden devolver {$item['quedan']}: es lo que el cliente todavía no retiró");
            }

            $stmt = $db->prepare("UPDATE detalle_orden SET cantidad_devuelta = cantidad_devuelta + ? WHERE id_detalle = ?");
            $stmt->bind_param('ii', $cantidad, $idDetalle);
            $stmt->execute();

            self::reponerStock($item['tipo_producto'], (int)$item['id_producto'], $cantidad, (int)$orden['id_orden'], 'Devolución parcial de orden web');

            $db->commit();
        } catch (\RuntimeException $e) {
            $db->rollback();
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
            return;
        } catch (\Throwable $e) {
            $db->rollback();
            error_log('devolverProducto(): ' . $e->getMessage());
            echo json_encode(['ok' => false, 'mensaje' => 'Error al devolver el producto']);
            return;
        }

        $monto = '$' . number_format($cantidad * (float)$item['precio_unitario'], 0, ',', '.');
        \Classes\Auditoria::registrar(
            'orden.devolver_producto',
            "Devolvió {$cantidad} × {$item['nombre_producto']} de la compra online {$orden['numero_orden']}; "
                . "reembolso de {$monto} a hacer por Mercado Pago",
            'ordenes',
            (int)$orden['id_orden'],
            ['pendiente_de_retirar' => $item['quedan']],
            ['pendiente_de_retirar' => $item['quedan'] - $cantidad, 'devuelto' => $cantidad, 'reembolso_pendiente' => $monto]
        );

        echo json_encode([
            'ok' => true,
            'mensaje' => "Se devolvió {$cantidad} × {$item['nombre_producto']} al stock. Recordá hacer el reembolso de {$monto} "
                . 'desde tu cuenta de Mercado Pago' . ($orden['payment_id'] ? " (pago N° {$orden['payment_id']})." : '.'),
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
            $query = "SELECT estado, numero_orden, total FROM ordenes WHERE id_orden = ?";
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
                // Si era pagada, devolver stock (menos lo que ya se entregó en la cantina)
                $entregado = [];
                if ($orden['estado'] === 'pagado') {
                    $entregado = self::devolverStockOrden($idOrden);
                }
                $textoEntregado = implode(', ', array_map(fn($n, $c) => "$c × $n", array_keys($entregado), $entregado));

                \Classes\Auditoria::registrar(
                    'orden.cancelar',
                    "Canceló la compra online {$orden['numero_orden']} por $" . number_format((float)$orden['total'], 0, ',', '.')
                        . ($entregado ? " (ya se había entregado: $textoEntregado; eso no volvió al stock)" : ''),
                    'ordenes',
                    $idOrden,
                    ['estado' => $orden['estado']],
                    ['estado' => 'cancelado'] + ($entregado ? ['ya_entregado' => $textoEntregado] : [])
                );

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Orden cancelada correctamente'
                        . ($entregado ? ". Lo que ya se había entregado no vuelve al stock: $textoEntregado" : '')
                ]);
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
                WHEN do.tipo_producto = 'butacas' THEN CONCAT('Fila ', b.fila_butaca, ' · Asiento ', b.numero_butaca)
                ELSE NULL
            END as info_butaca,
            CASE
                WHEN do.tipo_producto = 'butacas' THEN DATE_FORMAT(f.fecha_hora, '%d/%m/%Y %H:%i')
                ELSE NULL
            END as fecha_funcion,
            e.id_entrada,
            e.estado AS estado_entrada,
            COALESCE((SELECT SUM(eo.cantidad) FROM entregas_orden eo WHERE eo.id_detalle = do.id_detalle), 0) AS entregado
          FROM detalle_orden do
          LEFT JOIN butacas_vendidas bv ON bv.id_butaca = do.id_butaca AND bv.id_funcion = do.id_funcion
                                       AND bv.id_orden = do.id_orden AND do.tipo_producto = 'butacas'
          LEFT JOIN entradas e ON e.id_entrada = bv.id_entrada
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

    /**
     * Vuelve al stock una cantidad de un producto (cantina 1, como la venta web) o de las fichas
     * de una máquina, y deja el movimiento registrado.
     */
    private static function reponerStock(string $tipo, int $idProducto, int $cantidad, int $idOrden, string $motivo): void
    {
        $db = ActiveRecord::getDB();
        $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);

        if ($tipo === 'cantina') {
            $stmt = $db->prepare("UPDATE stock_cantina SET stock_cantina = stock_cantina + ?
                                  WHERE rela_producto_cantina = ? AND rela_cantina = 1");
            $stmt->bind_param('ii', $cantidad, $idProducto);
            $stmt->execute();

            // Sincronizar el estado del producto (Disponible/Agotado) según el nuevo stock
            $stmt = $db->prepare("SELECT stock_cantina FROM stock_cantina WHERE rela_producto_cantina = ? AND rela_cantina = 1");
            $stmt->bind_param('i', $idProducto);
            $stmt->execute();
            $filaStock = $stmt->get_result()->fetch_assoc();
            if ($filaStock) {
                \Models\Stock::actualizarEstadoProducto($idProducto, $filaStock['stock_cantina']);
            }
        } elseif ($tipo === 'fichas') {
            // id_producto de una ficha web es la máquina; sus fichas están en rela_fichas
            $stmt = $db->prepare("SELECT rela_fichas FROM maquinas WHERE id_maquinas = ?");
            $stmt->bind_param('i', $idProducto);
            $stmt->execute();
            $relaFichas = $stmt->get_result()->fetch_assoc()['rela_fichas'] ?? null;
            if (!$relaFichas) {
                return;
            }
            $stmt = $db->prepare("UPDATE fichas SET cantidad_ficha = cantidad_ficha + ? WHERE id_fichas = ?");
            $stmt->bind_param('ii', $cantidad, $relaFichas);
            $stmt->execute();
        } else {
            return;
        }

        $stmt = $db->prepare("INSERT INTO movimientos_stock (tipo_producto, id_producto, tipo_movimiento, cantidad, id_usuario, id_orden, fecha, observaciones)
                              VALUES (?, ?, 'entrada', ?, ?, ?, NOW(), ?)");
        $stmt->bind_param('siiiis', $tipo, $idProducto, $cantidad, $idUsuario, $idOrden, $motivo);
        $stmt->execute();
    }

    /**
     * Devuelve al stock lo de una orden cancelada. De productos y fichas vuelve solo lo que el
     * cliente todavía no retiró en la cantina: lo entregado ya no está.
     *
     * @return array lo que ya se había entregado y por eso no volvió: [nombre => cantidad]
     */
    private static function devolverStockOrden($idOrden)
    {
        $db = ActiveRecord::getDB();
        $yaEntregado = [];

        // Detalles de la orden, con lo que ya se entregó de cada uno
        $query = "SELECT d.*, COALESCE((SELECT SUM(eo.cantidad) FROM entregas_orden eo WHERE eo.id_detalle = d.id_detalle), 0) AS entregado
                  FROM detalle_orden d WHERE d.id_orden = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($detalle = $resultado->fetch_assoc()) {
            $tipo = $detalle['tipo_producto'];
            $idProducto = $detalle['id_producto'];
            $cantidad = (int)$detalle['cantidad'];

            if ($tipo === 'cantina' || $tipo === 'fichas') {
                $entregado = (int)$detalle['entregado'];
                if ($entregado > 0) {
                    $yaEntregado[$detalle['nombre_producto']] = $entregado;
                }
                // Lo retirado ya no está y lo devuelto antes por el admin ya volvió al stock
                $cantidad -= $entregado + (int)$detalle['cantidad_devuelta'];
                if ($cantidad <= 0) {
                    continue; // no queda nada para devolver
                }
                self::reponerStock($tipo, (int)$idProducto, $cantidad, (int)$idOrden, 'Devolución por cancelación de orden web');
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

                // Al borrar la venta la butaca ya queda libre para esa función. No se toca
                // rela_estado_butaca: es el bloqueo manual del administrador (mantenimiento) y
                // vale para todas las funciones; ponerlo en "Disponible" borraba ese bloqueo.
            }
        }

        return $yaEntregado;
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
