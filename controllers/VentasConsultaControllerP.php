<?php

namespace Controllers;

use MVC\Router;
use Middlewares\ValidarModulo;

class VentasConsultaControllerP
{

    private static function verificarVendedor()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login'])) {
            return false;
        }
        return ValidarModulo::tiene('CONSULTA_VENTAS_PRODUCTOS');
    }

    private static function verificarPermisoDevolucion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login'])) {
            return false;
        }
        return ValidarModulo::tiene('DEVOLUCION_PRODUCTOS');
    }

    public static function index(Router $router)
    {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();
        // Solo cajas 3 y 4 (productos)
        $cajas = $db->query("SELECT * FROM cajas WHERE activo = 1 AND id_caja IN (3, 4)")->fetch_all(MYSQLI_ASSOC);

        $router->render('vendedor/ventas/consulta', [
            'cajas' => $cajas
        ]);
    }

    public static function apiObtenerVendedores()
    {
        // Limpiar cualquier buffer de salida
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Establecer headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        // Verificar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!self::verificarVendedor()) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No autorizado'
            ]);
            exit;
        }

        try {
            $db = \Models\ActiveRecord::getDB();

            if (!$db) {
                throw new \Exception('No se pudo conectar a la base de datos');
            }

            // Query para obtener vendedores de PRODUCTOS (perfil 5)
            // que hayan usado cajas 3 y 4
            $query = "SELECT DISTINCT u.id_usuario, u.nombre_usuario 
                      FROM usuarios u
                      INNER JOIN arqueo_cajas ac ON u.id_usuario = ac.rela_usuario
                      WHERE u.rela_perfil = 5 
                      AND u.estado = 1
                      AND ac.rela_caja IN (3, 4)
                      ORDER BY u.nombre_usuario ASC";

            $result = $db->query($query);

            if (!$result) {
                throw new \Exception('Error en consulta: ' . $db->error);
            }

            $vendedores = [];
            while ($row = $result->fetch_assoc()) {
                $vendedores[] = [
                    'id_usuario' => (int)$row['id_usuario'],
                    'nombre_usuario' => $row['nombre_usuario']
                ];
            }

            http_response_code(200);
            echo json_encode([
                'ok' => true,
                'vendedores' => $vendedores,
                'total' => count($vendedores)
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'mensaje' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }


    public static function apiConsultarVentas()
    {
        // Limpiar buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        try {
            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos) {
                throw new \Exception('Datos inválidos');
            }

            $fechaDesde = $datos['fecha_desde'] ?? date('Y-m-d');
            $fechaHasta = $datos['fecha_hasta'] ?? date('Y-m-d');
            $idCaja = $datos['id_caja'] ?? null;
            $idVendedor = $datos['id_vendedor'] ?? null;
            $numeroComprobante = $datos['numero_comprobante'] ?? null;

            $db = \Models\ActiveRecord::getDB();

            // Query CORREGIDA: Ventas de PRODUCTOS y FICHAS
            $query = "SELECT 
        cc.id_cabecera_fact_cantin as id_venta,
        cc.numero_comprobante,
        cc.fecha_hora_pago_cantina as fecha_hora,
        cc.monto_total_cantina as monto_total,
        
        -- Datos del vendedor y caja
        u.nombre_usuario as vendedor,
        c.nombre_caja as caja,
        c.numero_caja,
        
        -- Productos o Fichas (uno de los dos será NULL)
        p.nombre_producto_cantina as nombre_producto,
        f.nombre_ficha,
        
        -- Determinar el tipo de item
        CASE 
            WHEN dc.rela_producto_cantina IS NOT NULL THEN 'Producto'
            WHEN dc.rela_fichas IS NOT NULL THEN 'Ficha'
            ELSE 'Desconocido'
        END as tipo_item,
        
        -- Detalles de la venta
        dc.cantidad,
        dc.precio_unitario,
        (dc.cantidad * dc.precio_unitario) as subtotal,
        dc.estado,  -- 👈 NUEVO CAMPO
        
        -- Forma de pago
        tp.descripcion_tipo_pago as forma_pago
        
    FROM cabecera_fact_cantina cc
    INNER JOIN detalle_fact_cantina dc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
    INNER JOIN arqueo_cajas ac ON cc.rela_arqueo_caja = ac.id_arqueo_caja
    INNER JOIN cajas c ON ac.rela_caja = c.id_caja
    INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
    INNER JOIN tipos_de_pagos tp ON cc.rela_tipo_de_pagos = tp.id_tipo_pago
    
    -- LEFT JOIN para productos (puede ser NULL si es ficha)
    LEFT JOIN productos_cantina p ON dc.rela_producto_cantina = p.id_producto_cantina
    
    -- LEFT JOIN para fichas (puede ser NULL si es producto)
    LEFT JOIN fichas f ON dc.rela_fichas = f.id_fichas
    
    WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
    AND c.id_caja IN (3, 4)
    AND u.rela_perfil = 5";

            $params = [$fechaDesde, $fechaHasta];
            $types = "ss";

            // Filtro por caja
            if ($idCaja) {
                $query .= " AND c.id_caja = ?";
                $params[] = (int)$idCaja;
                $types .= "i";
            }

            // Filtro por vendedor
            if ($idVendedor) {
                $query .= " AND u.id_usuario = ?";
                $params[] = (int)$idVendedor;
                $types .= "i";
            }

            // Filtro por número de comprobante
            if ($numeroComprobante) {
                $query .= " AND cc.numero_comprobante LIKE ?";
                $params[] = "%{$numeroComprobante}%";
                $types .= "s";
            }

            $query .= " ORDER BY cc.fecha_hora_pago_cantina DESC, cc.id_cabecera_fact_cantin DESC";

            // Debug logging
            error_log("=== DEBUG CONSULTA VENTAS PRODUCTOS ===");
            error_log("Fecha desde: " . $fechaDesde);
            error_log("Fecha hasta: " . $fechaHasta);
            error_log("ID Caja: " . ($idCaja ?? 'TODAS'));
            error_log("ID Vendedor: " . ($idVendedor ?? 'TODOS'));
            error_log("Número Comprobante: " . ($numeroComprobante ?? 'TODOS'));

            $stmt = $db->prepare($query);

            if (!$stmt) {
                throw new \Exception('Error al preparar consulta: ' . $db->error);
            }

            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new \Exception('Error al ejecutar consulta: ' . $stmt->error);
            }

            $result = $stmt->get_result();

            // Procesar resultados
            $ventas = [];
            $ventasUnicas = [];
            $totalProductos = 0;

            while ($row = $result->fetch_assoc()) {
                $idVenta = (int)$row['id_venta'];

                // Contar ventas únicas para el resumen
                if (!isset($ventasUnicas[$idVenta])) {
                    $ventasUnicas[$idVenta] = (float)$row['monto_total'];
                }

                // Determinar el nombre del producto o ficha
                $nombreItem = $row['nombre_producto'] ?? $row['nombre_ficha'] ?? 'Sin nombre';

                $ventas[] = [
                    'id_venta' => $idVenta,
                    'numero_comprobante' => $row['numero_comprobante'],
                    'fecha_hora' => date('d/m/Y H:i', strtotime($row['fecha_hora'])),
                    'vendedor' => $row['vendedor'],
                    'caja' => $row['caja'],
                    'numero_caja' => $row['numero_caja'],
                    'producto' => $nombreItem,
                    'tipo_item' => $row['tipo_item'],
                    'cantidad' => (int)$row['cantidad'],
                    'precio_unitario' => (float)$row['precio_unitario'],
                    'subtotal' => (float)$row['subtotal'],
                    'estado' => $row['estado'], // 👈 NUEVO
                    'forma_pago' => $row['forma_pago'],
                    'monto_total' => (float)$row['monto_total']
                ];

                $totalProductos += (int)$row['cantidad'];
            }

            // Calcular monto total correcto (suma de ventas únicas)
            $montoTotal = array_sum($ventasUnicas);

            error_log("Total ventas encontradas: " . count($ventas));
            error_log("Ventas únicas: " . count($ventasUnicas));
            error_log("Total productos: " . $totalProductos);
            error_log("Monto total: " . $montoTotal);

            echo json_encode([
                'ok' => true,
                'ventas' => $ventas,
                'resumen' => [
                    'total_ventas' => count($ventasUnicas),
                    'total_productos' => $totalProductos,
                    'monto_total' => $montoTotal
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("ERROR en apiConsultarVentas: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    public static function apiProcesarDevolucion()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!self::verificarPermisoDevolucion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        try {
            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos || !isset($datos['id_venta'])) {
                throw new \Exception('Datos inválidos - ID de venta requerido');
            }

            $idVenta = (int)$datos['id_venta'];
            $itemsSeleccionados = $datos['items'] ?? [];
            $motivo = $datos['motivo'] ?? 'Devolución solicitada';
            $idUsuario = $_SESSION['id'];

            if (empty($itemsSeleccionados)) {
                throw new \Exception('Debes seleccionar al menos un item para devolver');
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                // 1. Verificar venta
                $queryVenta = "SELECT 
                cc.id_cabecera_fact_cantin,
                cc.monto_total_cantina,
                cc.rela_arqueo_caja,
                cc.numero_comprobante,
                cc.fecha_hora_pago_cantina,
                cc.observaciones,
                ac.estado_arqueo,
                ac.rela_usuario
            FROM cabecera_fact_cantina cc
            INNER JOIN arqueo_cajas ac ON cc.rela_arqueo_caja = ac.id_arqueo_caja
            WHERE cc.id_cabecera_fact_cantin = ?";

                $stmt = $db->prepare($queryVenta);
                $stmt->bind_param("i", $idVenta);
                $stmt->execute();
                $result = $stmt->get_result();
                $venta = $result->fetch_assoc();

                if (!$venta) {
                    throw new \Exception('Venta no encontrada');
                }

                if (!empty($venta['observaciones']) && strpos($venta['observaciones'], '[DEVUELTO]') !== false) {
                    throw new \Exception('Esta venta ya fue devuelta anteriormente');
                }

                if ($venta['estado_arqueo'] !== 'abierto') {
                    throw new \Exception('No se puede procesar devolución - La caja ya está cerrada');
                }

                // 2. Obtener todos los detalles de la venta
                $queryDetalles = "SELECT 
                dc.id_detalle_fact_cantina,
                dc.rela_producto_cantina,
                dc.rela_fichas,
                dc.cantidad,
                dc.precio_unitario,
                dc.estado,
                p.nombre_producto_cantina,
                f.nombre_ficha
            FROM detalle_fact_cantina dc
            LEFT JOIN productos_cantina p ON dc.rela_producto_cantina = p.id_producto_cantina
            LEFT JOIN fichas f ON dc.rela_fichas = f.id_fichas
            WHERE dc.rela_cabecera_fact_cantin = ?
            ORDER BY dc.id_detalle_fact_cantina";

                $stmt = $db->prepare($queryDetalles);
                $stmt->bind_param("i", $idVenta);
                $stmt->execute();
                $resultDetalles = $stmt->get_result();
                $detalles = $resultDetalles->fetch_all(MYSQLI_ASSOC);

                if (empty($detalles)) {
                    throw new \Exception('No se encontraron detalles de la venta');
                }

                // 3. Procesar solo los items seleccionados
                $itemsDevueltos = 0;
                $montoTotalDevolucion = 0;
                $detalleDevolucion = [];

                foreach ($itemsSeleccionados as $itemSeleccionado) {
                    $productoNombre = trim($itemSeleccionado['producto'] ?? '');
                    $tipoItem = $itemSeleccionado['tipo_item'] ?? '';
                    $indexItem = $itemSeleccionado['index'] ?? null;

                    // Buscar por índice primero (más confiable), luego por nombre
                    $detalleEncontrado = null;

                    if ($indexItem !== null && isset($detalles[$indexItem])) {
                        $detalleEncontrado = $detalles[$indexItem];
                    } else {
                        // Fallback: buscar por nombre
                        foreach ($detalles as $detalle) {
                            $nombreDB = trim(!empty($detalle['nombre_producto_cantina'])
                                ? $detalle['nombre_producto_cantina']
                                : $detalle['nombre_ficha']);

                            if ($nombreDB === $productoNombre) {
                                $detalleEncontrado = $detalle;
                                break;
                            }
                        }
                    }

                    if (!$detalleEncontrado) {
                        error_log("⚠️ No se encontró item: '{$productoNombre}'");
                        continue;
                    }

                    // Verificar que no esté ya devuelto
                    if ($detalleEncontrado['estado'] === 'devuelto') {
                        error_log("⚠️ Item ya devuelto: {$productoNombre}");
                        continue;
                    }

                    $idDetalle = (int)$detalleEncontrado['id_detalle_fact_cantina'];
                    $cantidad = (int)$detalleEncontrado['cantidad'];
                    $precioUnitario = (float)$detalleEncontrado['precio_unitario'];
                    $subtotal = $cantidad * $precioUnitario;

                    // DEVOLVER PRODUCTOS
                    if (!empty($detalleEncontrado['rela_producto_cantina'])) {
                        $idProducto = $detalleEncontrado['rela_producto_cantina'];

                        $queryStock = "UPDATE stock_cantina 
                              SET stock_cantina = stock_cantina + ?
                              WHERE rela_producto_cantina = ?";

                        $stmt = $db->prepare($queryStock);
                        $stmt->bind_param("ii", $cantidad, $idProducto);

                        if (!$stmt->execute()) {
                            throw new \Exception("Error al devolver stock del producto ID: {$idProducto}");
                        }

                        $queryEstado = "UPDATE productos_cantina 
                               SET rela_estado_producto = 1
                               WHERE id_producto_cantina = ? 
                               AND rela_estado_producto = 2";

                        $stmt = $db->prepare($queryEstado);
                        $stmt->bind_param("i", $idProducto);
                        $stmt->execute();

                        $detalleDevolucion[] = "Producto: {$productoNombre} (Cant: {$cantidad} × \${$precioUnitario})";
                        error_log("✅ Stock devuelto - Producto #{$idProducto}: +{$cantidad} unidades");
                    }

                    // DEVOLVER FICHAS
                    if (!empty($detalleEncontrado['rela_fichas'])) {
                        $idFicha = $detalleEncontrado['rela_fichas'];

                        $queryFichas = "UPDATE fichas 
                               SET cantidad_ficha = cantidad_ficha + ?
                               WHERE id_fichas = ?";

                        $stmt = $db->prepare($queryFichas);
                        $stmt->bind_param("ii", $cantidad, $idFicha);

                        if (!$stmt->execute()) {
                            throw new \Exception("Error al devolver fichas ID: {$idFicha}");
                        }

                        $detalleDevolucion[] = "Ficha: {$productoNombre} (Cant: {$cantidad} × \${$precioUnitario})";
                        error_log("✅ Fichas devueltas - Ficha #{$idFicha}: +{$cantidad} unidades");
                    }

                    // 👇 NUEVO: Actualizar estado del detalle a "devuelto"
                    $queryEstadoDetalle = "UPDATE detalle_fact_cantina 
                                       SET estado = 'devuelto'
                                       WHERE id_detalle_fact_cantina = ?";

                    $stmt = $db->prepare($queryEstadoDetalle);
                    $stmt->bind_param("i", $idDetalle);

                    if (!$stmt->execute()) {
                        throw new \Exception("Error al actualizar estado del detalle ID: {$idDetalle}");
                    }

                    error_log("✅ Estado actualizado a 'devuelto' - Detalle #{$idDetalle}");

                    // Agregar al detalle usando el nombre del item seleccionado directamente
                    $nombreItem = $itemSeleccionado['producto'] ?? 'Item devuelto';
                    $tipoItem = $itemSeleccionado['tipo_item'] ?? '';
                    $cantItem = $itemSeleccionado['cantidad'] ?? $cantidad;
                    $precioItem = $itemSeleccionado['precio_unitario'] ?? $precioUnitario;
                    $detalleDevolucion[] = "{$tipoItem}: {$nombreItem} (Cant: {$cantItem} × \${$precioItem})";

                    $montoTotalDevolucion += $subtotal;
                    $itemsDevueltos++;
                }

                if ($itemsDevueltos === 0) {
                    throw new \Exception('No se pudo devolver ningún item');
                }

                // 4. Registrar en EGRESOS (para que el admin lo vea en Lista de Gastos)
                $detalleTexto = !empty($detalleDevolucion)
                    ? implode(", ", $detalleDevolucion)
                    : "Items devueltos: {$itemsDevueltos}";

                $conceptoMovimiento = "DEVOLUCIÓN - Comp: {$venta['numero_comprobante']} - " .
                    $detalleTexto . " - Motivo: {$motivo}";

                $estadoEgreso = 'pagado'; // siempre efectivo en cantina

                $queryEgreso = "INSERT INTO egresos 
               (rela_arqueo_caja, rela_usuario, concepto, monto, forma_pago,
                fecha_egreso, estado_egreso, observaciones)
               VALUES (?, ?, ?, ?, 'Efectivo', NOW(), ?, ?)";

                $stmt = $db->prepare($queryEgreso);
                $stmt->bind_param(
                    "iisdss",
                    $venta['rela_arqueo_caja'],
                    $idUsuario,
                    $conceptoMovimiento,
                    $montoTotalDevolucion,
                    $estadoEgreso,
                    $motivo
                );

                if (!$stmt->execute()) {
                    throw new \Exception('Error al registrar egreso: ' . $stmt->error);
                }

                $idEgreso = $db->insert_id;

                // 4b. Registrar movimiento en caja (para afectar el saldo)
                $queryMovimiento = "INSERT INTO movimientos_caja 
                   (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, 
                    monto, fecha_movimiento, forma_pago, rela_egreso)
                   VALUES (?, 'egreso', ?, ?, NOW(), 'Efectivo', ?)";

                $stmt = $db->prepare($queryMovimiento);
                $stmt->bind_param(
                    "isdi",
                    $venta['rela_arqueo_caja'],
                    $conceptoMovimiento,
                    $montoTotalDevolucion,
                    $idEgreso
                );

                if (!$stmt->execute()) {
                    throw new \Exception('Error al registrar movimiento en caja');
                }

                error_log("✅ Movimiento registrado - Egreso: {$montoTotalDevolucion}");

                // 5. Marcar como DEVUELTO (agregar nota)
                $observacionDevolucion = "[DEVUELTO PARCIAL el " . date('d/m/Y H:i:s') .
                    " - {$itemsDevueltos} item(s) - Monto: \${$montoTotalDevolucion} - " .
                    "Usuario: {$idUsuario} - Motivo: {$motivo}]";

                $queryActualizar = "UPDATE cabecera_fact_cantina 
                           SET observaciones = CONCAT(
                               IFNULL(observaciones, ''), 
                               ' ', 
                               ?
                           )
                           WHERE id_cabecera_fact_cantin = ?";

                $stmt = $db->prepare($queryActualizar);
                $stmt->bind_param("si", $observacionDevolucion, $idVenta);

                if (!$stmt->execute()) {
                    throw new \Exception('Error al marcar la venta');
                }

                // 6. Actualizar arqueo (restar solo lo devuelto)
                $queryArqueo = "UPDATE arqueo_cajas 
                       SET total_ventas = total_ventas - ?
                       WHERE id_arqueo_caja = ?";

                $stmt = $db->prepare($queryArqueo);
                $stmt->bind_param("di", $montoTotalDevolucion, $venta['rela_arqueo_caja']);

                if (!$stmt->execute()) {
                    throw new \Exception('Error al actualizar el arqueo');
                }

                error_log("✅ Arqueo actualizado - Reducido: {$montoTotalDevolucion}");

                $db->commit();

                error_log("🎉 DEVOLUCIÓN EXITOSA - Venta #{$idVenta} - Monto: {$montoTotalDevolucion} - Items: {$itemsDevueltos}");

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Devolución procesada correctamente',
                    'detalles' => [
                        'id_venta' => $idVenta,
                        'numero_comprobante' => $venta['numero_comprobante'],
                        'monto_devuelto' => number_format($montoTotalDevolucion, 2, ',', '.'),
                        'items_devueltos' => $itemsDevueltos,
                        'detalle_items' => $detalleDevolucion
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("❌ ERROR en devolución: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al procesar devolución: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
