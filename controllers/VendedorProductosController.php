<?php

namespace Controllers;

use Models\ArqueoCaja;
use Models\MovimientoCaja;
use MVC\Router;
use Middlewares\ValidarModulo;

class VendedorProductosController
{

    private static function verificarPermisoCaja()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login'])) {
            return false;
        }
        // Caja de productos: además de gestionar caja, debe vender productos
        return ValidarModulo::tiene('GESTION_CAJA') && ValidarModulo::tiene('VENTA_PRODUCTOS');
    }

    private static function verificarPermisoVenta()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login'])) {
            return false;
        }
        return ValidarModulo::tiene('VENTA_PRODUCTOS');
    }

    private static function verificarPermisoMovimientos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login'])) {
            return false;
        }
        return ValidarModulo::tiene('MOVIMIENTOS_CAJA');
    }

    private static function verificarPermisoConsulta()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login'])) {
            return false;
        }
        return ValidarModulo::tiene('CONSULTA_VENTAS_PRODUCTOS');
    }

    public static function index(Router $router)
    {
        if (!self::verificarPermisoCaja()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if ($arqueo) {
            header('Location: /vendedorproductos/caja/estado');
        } else {
            header('Location: /vendedorproductos/caja/abrir');
        }
        exit;
    }

    public static function estado(Router $router)
    {
        if (!self::verificarPermisoCaja()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            header('Location: /vendedorproductos/caja/abrir');
            exit;
        }

        $movimientos = MovimientoCaja::obtenerPorArqueo($arqueo->id_arqueo_caja);

        $totalIngresos = 0;
        $totalEgresos = 0;

        foreach ($movimientos as $mov) {
            if ($mov->concepto_movimiento !== 'Apertura de caja - Monto inicial') {
                if ($mov->tipo_movimiento === 'ingreso') {
                    $totalIngresos += $mov->monto;
                } else {
                    $totalEgresos += $mov->monto;
                }
            }
        }

        $saldoFinal = $arqueo->monto_inicial + $totalIngresos - $totalEgresos;

        $router->render('vendedorproductos/caja/estado', [
            'arqueo' => $arqueo,
            'movimientos' => $movimientos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'saldo_final' => $saldoFinal
        ]);
    }

    public static function vistaAbrir(Router $router)
    {
        if (!self::verificarPermisoCaja()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if ($arqueo) {
            header('Location: /vendedorproductos/caja/estado');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT c.*,
                ac.id_arqueo_caja,
                ac.rela_usuario as usuario_usando,
                u.nombre_usuario as usuario_usando_nombre
                FROM cajas c
                LEFT JOIN arqueo_cajas ac ON c.id_caja = ac.rela_caja 
                    AND ac.estado_arqueo = 'abierto'
                LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                WHERE c.activo = 1 AND c.id_caja IN (3, 4)
                ORDER BY c.numero_caja";

        $result = $db->query($query);
        $cajas = $result->fetch_all(MYSQLI_ASSOC);

        $router->render('vendedorproductos/caja/abrir', [
            'cajas' => $cajas
        ]);
    }

    public static function abrirCaja()
    {
        if (!self::verificarPermisoCaja()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $idUsuario = $_SESSION['id'];
            $relaCaja = $datos['rela_caja'] ?? null;

            if (!in_array($relaCaja, [3, 4])) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No tienes permisos para usar esta caja'
                ]);
                return;
            }

            $cajaEnUso = ArqueoCaja::verificarCajaEnUso($relaCaja);
            if ($cajaEnUso) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Esta caja ya está siendo utilizada por ' . $cajaEnUso['nombre_usuario']
                ]);
                return;
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                $montoInicial = $datos['monto_inicial'] ?? 0;

                $queryArqueo = "INSERT INTO arqueo_cajas 
                               (rela_usuario, rela_caja, fecha_inicio, monto_inicial, 
                                total_ventas, estado_arqueo)
                               VALUES (?, ?, NOW(), ?, 0, 'abierto')";

                $stmt = $db->prepare($queryArqueo);
                $stmt->bind_param("iid", $idUsuario, $relaCaja, $montoInicial);

                if (!$stmt->execute()) {
                    throw new \Exception('Error al abrir caja');
                }

                $idArqueo = $db->insert_id;

                if ($montoInicial > 0) {
                    $queryMov = "INSERT INTO movimientos_caja 
                                (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, 
                                 monto, fecha_movimiento, forma_pago)
                                VALUES (?, 'ingreso', 'Apertura de caja - Monto inicial', ?, NOW(), 'Efectivo')";

                    $stmtMov = $db->prepare($queryMov);
                    $stmtMov->bind_param("id", $idArqueo, $montoInicial);
                    $stmtMov->execute();
                }

                $db->commit();

                \Classes\Auditoria::registrar(
                    'caja.abrir',
                    'Abrió la ' . \Models\ArqueoCaja::nombreCaja($relaCaja) . ' con $' . number_format((float)$montoInicial, 0, ',', '.'),
                    'arqueo_cajas',
                    $idArqueo,
                    null,
                    ['caja' => (int)$relaCaja, 'monto_inicial' => (float)$montoInicial]
                );

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Caja abierta correctamente',
                    'redirigir' => '/vendedorproductos/caja/estado'
                ]);
            } catch (\Exception $e) {
                $db->rollback();
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }

    public static function cerrarCaja()
    {
        if (!self::verificarPermisoCaja()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $idUsuario = $_SESSION['id'];
            $montoFinalDeclarado = $datos['monto_final'] ?? null;
            $observaciones = $datos['observaciones'] ?? '';

            if ($montoFinalDeclarado === null) {
                echo json_encode(['ok' => false, 'mensaje' => 'Debe declarar el monto final']);
                return;
            }

            $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

            if (!$arqueo) {
                echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
                return;
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                $queryTotales = "SELECT 
                                SUM(CASE 
                                    WHEN tipo_movimiento = 'ingreso' 
                                    AND forma_pago = 'efectivo'
                                    AND concepto_movimiento != 'Apertura de caja - Monto inicial'
                                    THEN monto 
                                    ELSE 0 
                                END) as ingresos_efectivo,
                                SUM(CASE 
                                    WHEN tipo_movimiento = 'ingreso' 
                                    AND forma_pago = 'transferencia'
                                    THEN monto 
                                    ELSE 0 
                                END) as ingresos_transferencia,
                                SUM(CASE 
                                    WHEN tipo_movimiento = 'egreso' 
                                    THEN monto 
                                    ELSE 0 
                                END) as egresos
                            FROM movimientos_caja
                            WHERE rela_arqueo_caja = ?";

                $stmt = $db->prepare($queryTotales);
                $stmt->bind_param("i", $arqueo->id_arqueo_caja);
                $stmt->execute();
                $result = $stmt->get_result();
                $totales = $result->fetch_assoc();

                $ingresosEfectivo = $totales['ingresos_efectivo'] ?? 0;
                $ingresosTransferencia = $totales['ingresos_transferencia'] ?? 0;
                $totalEgresos = $totales['egresos'] ?? 0;

                $montoEsperadoEfectivo = $arqueo->monto_inicial + $ingresosEfectivo - $totalEgresos;
                $diferencia = $montoFinalDeclarado - $montoEsperadoEfectivo;
                $totalIngresos = $ingresosEfectivo + $ingresosTransferencia;

                $queryCerrar = "UPDATE arqueo_cajas 
                           SET fecha_cierre = NOW(),
                               monto_final = ?,
                               total_ventas = ?,
                               diferencia = ?,
                               estado_arqueo = 'cerrado',
                               observaciones_cierre = ?
                           WHERE id_arqueo_caja = ?";

                $stmtCerrar = $db->prepare($queryCerrar);
                $stmtCerrar->bind_param(
                    "dddsi",
                    $montoFinalDeclarado,
                    $totalIngresos,
                    $diferencia,
                    $observaciones,
                    $arqueo->id_arqueo_caja
                );

                if (!$stmtCerrar->execute()) {
                    throw new \Exception('Error al cerrar caja');
                }

                $db->commit();

                \Classes\Auditoria::registrar(
                    'caja.cerrar',
                    'Cerró la ' . \Models\ArqueoCaja::nombreCaja($arqueo->rela_caja)
                        . ' · esperado $' . number_format((float)$montoEsperadoEfectivo, 0, ',', '.')
                        . ', declarado $' . number_format((float)$montoFinalDeclarado, 0, ',', '.')
                        . ($diferencia != 0 ? ', diferencia ' . ($diferencia > 0 ? '+' : '-') . '$' . number_format(abs((float)$diferencia), 0, ',', '.') : ', sin diferencia'),
                    'arqueo_cajas',
                    $arqueo->id_arqueo_caja,
                    null,
                    [
                        'caja' => (int)$arqueo->rela_caja,
                        'monto_inicial' => (float)$arqueo->monto_inicial,
                        'esperado_efectivo' => (float)$montoEsperadoEfectivo,
                        'declarado' => (float)$montoFinalDeclarado,
                        'diferencia' => (float)$diferencia,
                        'total_ventas' => (float)$totalIngresos,
                    ]
                );

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Caja cerrada correctamente',
                    'diferencia' => $diferencia,
                    'monto_esperado_efectivo' => $montoEsperadoEfectivo,
                    'monto_declarado' => $montoFinalDeclarado,
                    'ingresos_efectivo' => $ingresosEfectivo,
                    'ingresos_transferencia' => $ingresosTransferencia,
                    'egresos' => $totalEgresos,
                    'id_arqueo' => $arqueo->id_arqueo_caja,
                    'generar_pdf' => true
                ]);
            } catch (\Exception $e) {
                $db->rollback();
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }

    public static function apiResumenCierre()
    {
        if (!self::verificarPermisoCaja()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();

        $queryTotales = "SELECT 
                        SUM(CASE 
                            WHEN tipo_movimiento = 'ingreso' 
                            AND forma_pago = 'efectivo'
                            AND concepto_movimiento != 'Apertura de caja - Monto inicial'
                            THEN monto 
                            ELSE 0 
                        END) as ingresos_efectivo,
                        SUM(CASE 
                            WHEN tipo_movimiento = 'ingreso' 
                            AND forma_pago = 'transferencia'
                            THEN monto 
                            ELSE 0 
                        END) as ingresos_transferencia,
                        SUM(CASE 
                            WHEN tipo_movimiento = 'egreso' 
                            THEN monto 
                            ELSE 0 
                        END) as egresos,
                        COUNT(*) as cantidad_movimientos
                    FROM movimientos_caja
                    WHERE rela_arqueo_caja = ?";

        $stmt = $db->prepare($queryTotales);
        $stmt->bind_param("i", $arqueo->id_arqueo_caja);
        $stmt->execute();
        $result = $stmt->get_result();
        $totales = $result->fetch_assoc();

        $ingresosEfectivo = $totales['ingresos_efectivo'] ?? 0;
        $ingresosTransferencia = $totales['ingresos_transferencia'] ?? 0;
        $egresos = $totales['egresos'] ?? 0;
        $montoEsperadoEfectivo = $arqueo->monto_inicial + $ingresosEfectivo - $egresos;

        echo json_encode([
            'ok' => true,
            'resumen' => [
                'monto_inicial' => $arqueo->monto_inicial,
                'ingresos_efectivo' => $ingresosEfectivo,
                'ingresos_transferencia' => $ingresosTransferencia,
                'total_egresos' => $egresos,
                'monto_esperado_efectivo' => $montoEsperadoEfectivo,
                'cantidad_movimientos' => $totales['cantidad_movimientos'],
                'fecha_inicio' => $arqueo->fecha_inicio
            ]
        ]);
    }

    public static function generarPDFArqueo()
    {
        if (!self::verificarPermisoCaja()) {
            header('Location: /');
            exit;
        }

        $idArqueo = $_GET['id'] ?? null;

        if (!$idArqueo || !is_numeric($idArqueo)) {
            header('Location: /vendedorproductos/caja/estado');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        // El admin puede ver cualquier arqueo; un vendedor solo el propio
        $esAdmin = (int)($_SESSION['perfil'] ?? 0) === 3;

        $queryArqueo = "SELECT
                            ac.*,
                            u.nombre_usuario,
                            c.nombre_caja,
                            c.numero_caja
                        FROM arqueo_cajas ac
                        INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                        INNER JOIN cajas c ON ac.rela_caja = c.id_caja
                        WHERE ac.id_arqueo_caja = ?" . ($esAdmin ? "" : " AND ac.rela_usuario = ?");

        $stmt = $db->prepare($queryArqueo);
        if ($esAdmin) {
            $stmt->bind_param("i", $idArqueo);
        } else {
            $idUsuarioSesion = $_SESSION['id_usuario'];
            $stmt->bind_param("ii", $idArqueo, $idUsuarioSesion);
        }
        $stmt->execute();
        $arqueo = $stmt->get_result()->fetch_assoc();

        if (!$arqueo) {
            header('Location: /vendedorproductos/caja/estado');
            exit;
        }

        $queryTotales = "SELECT 
                            SUM(CASE 
                                WHEN tipo_movimiento = 'ingreso' 
                                AND forma_pago = 'efectivo'
                                AND concepto_movimiento != 'Apertura de caja - Monto inicial'
                                THEN monto 
                                ELSE 0 
                            END) as ingresos_efectivo,
                            SUM(CASE 
                                WHEN tipo_movimiento = 'ingreso' 
                                AND forma_pago = 'transferencia'
                                THEN monto 
                                ELSE 0 
                            END) as ingresos_transferencia,
                            SUM(CASE 
                                WHEN tipo_movimiento = 'egreso' 
                                THEN monto 
                                ELSE 0 
                            END) as egresos
                        FROM movimientos_caja
                        WHERE rela_arqueo_caja = ?";

        $stmt2 = $db->prepare($queryTotales);
        $stmt2->bind_param("i", $idArqueo);
        $stmt2->execute();
        $totales = $stmt2->get_result()->fetch_assoc();

        \Classes\GeneradorArqueoPDF::generar($arqueo, $totales);
    }

    public static function listadoProductos(Router $router)
    {
        if (!self::verificarPermisoVenta()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            header('Location: /vendedorproductos/caja/abrir');
            exit;
        }

        // Obtener productos con stock disponible
        $productos = \Models\Productos::obtenerProductosActivos();

        $router->render('vendedorproductos/productos/listado', [
            'productos' => $productos,
            'arqueo' => $arqueo
        ]);
    }


    public static function buscarProducto()
    {
        header('Content-Type: application/json');

        if (!self::verificarPermisoVenta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = $datos['busqueda'] ?? '';

        if (strlen($termino) < 2) {
            echo json_encode(['ok' => false, 'productos' => []]);
            return;
        }

        $db = \Models\ActiveRecord::getDB();

        // Buscar productos CON STOCK disponible
        $query = "SELECT 
                p.id_producto_cantina,
                p.nombre_producto_cantina,
                p.precio_producto,
                p.codigo,
                s.id_stock_cantina,
                s.stock_cantina,
                e.nombre_estado_producto
              FROM productos_cantina p
              INNER JOIN stock_cantina s ON p.id_producto_cantina = s.rela_producto_cantina
              INNER JOIN estados_productos e ON p.rela_estado_producto = e.id_estado_producto
              WHERE s.stock_cantina > 0
              AND p.rela_estado_producto IN (1, 2)
              AND (p.nombre_producto_cantina LIKE ? OR p.codigo LIKE ?)
              ORDER BY p.nombre_producto_cantina ASC
              LIMIT 10";

        try {
            $stmt = $db->prepare($query);
            $terminoBusqueda = "%{$termino}%";
            $stmt->bind_param("ss", $terminoBusqueda, $terminoBusqueda);
            $stmt->execute();
            $result = $stmt->get_result();

            $productos = [];
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }

            echo json_encode([
                'ok' => true,
                'productos' => $productos
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public static function buscarFicha()
    {
        header('Content-Type: application/json');

        if (!self::verificarPermisoVenta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        try {
            $db = \Models\ActiveRecord::getDB();

            // Obtener todas las fichas disponibles
            $query = "SELECT 
                    id_fichas,
                    precio_ficha,
                    cantidad_ficha,
                    nombre_ficha
                  FROM fichas 
                  WHERE cantidad_ficha > 0
                  ORDER BY precio_ficha ASC";

            $resultado = $db->query($query);

            if (!$resultado) {
                throw new \Exception('Error en la consulta: ' . $db->error);
            }

            $fichas = [];
            while ($row = $resultado->fetch_assoc()) {
                $fichas[] = [
                    'id_fichas' => $row['id_fichas'],
                    'precio_ficha' => $row['precio_ficha'],
                    'cantidad_ficha' => $row['cantidad_ficha'],
                    'nombre_ficha' => $row['nombre_ficha'],
                    'tipo' => 'ficha' // Identificador
                ];
            }

            echo json_encode([
                'ok' => true,
                'fichas' => $fichas
            ]);
        } catch (\Exception $e) {
            error_log("Error al buscar fichas: " . $e->getMessage());
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al buscar fichas'
            ]);
        }
    }

    public static function obtenerMetodosPago()
    {
        // Limpiar buffer
        if (ob_get_level()) ob_clean();

        header('Content-Type: application/json');

        if (!self::verificarPermisoVenta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $db = \Models\ActiveRecord::getDB();

            if (!$db) {
                throw new \Exception('No se pudo conectar a la base de datos');
            }

            // Query para obtener métodos de pago activos
            $query = "SELECT 
                    id_tipo_pago,
                    descripcion_tipo_pago
                  FROM tipos_de_pagos 
                  WHERE activo = 1
                  ORDER BY descripcion_tipo_pago ASC";

            $result = $db->query($query);

            if (!$result) {
                throw new \Exception('Error en la consulta: ' . $db->error);
            }

            $metodos = [];
            while ($row = $result->fetch_assoc()) {
                $metodos[] = [
                    'id_metodo_pago' => $row['id_tipo_pago'],
                    'nombre_metodo_pago' => $row['descripcion_tipo_pago']
                ];
            }

            echo json_encode([
                'ok' => true,
                'metodos' => $metodos
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
    public static function gestionMovimientos(Router $router)
    {
        if (!self::verificarPermisoMovimientos()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            header('Location: /vendedorproductos/caja/abrir');
            exit;
        }

        // Obtener movimientos
        $movimientos = MovimientoCaja::obtenerPorArqueo($arqueo->id_arqueo_caja);

        // Calcular totales
        $totalIngresos = 0;
        $totalEgresos = 0;

        foreach ($movimientos as $mov) {
            if ($mov->concepto_movimiento !== 'Apertura de caja - Monto inicial') {
                if ($mov->tipo_movimiento === 'ingreso') {
                    $totalIngresos += $mov->monto;
                } else {
                    $totalEgresos += $mov->monto;
                }
            }
        }

        $router->render('vendedorproductos/movimientos/gestion', [
            'arqueo' => $arqueo,
            'movimientos' => $movimientos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos
        ]);
    }

    public static function registrarIngreso()
    {
        if (!self::verificarPermisoMovimientos()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $idUsuario = $_SESSION['id'];
            $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

            if (!$arqueo) {
                echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
                return;
            }

            $concepto = $datos['concepto'] ?? '';
            $monto = $datos['monto'] ?? 0;
            $idTipoPago = $datos['id_tipo_pago'] ?? null;

            if (empty($concepto) || $monto <= 0 || !$idTipoPago) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
                return;
            }

            $db = \Models\ActiveRecord::getDB();

            try {
                // Obtener forma de pago
                $queryFormaPago = "SELECT descripcion_tipo_pago FROM tipos_de_pagos WHERE id_tipo_pago = ?";
                $stmt = $db->prepare($queryFormaPago);
                $stmt->bind_param("i", $idTipoPago);
                $stmt->execute();
                $result = $stmt->get_result();
                $formaPago = $result->fetch_assoc();

                if (!$formaPago) {
                    echo json_encode(['ok' => false, 'mensaje' => 'Forma de pago no válida']);
                    return;
                }

                // Registrar movimiento
                $queryMovimiento = "INSERT INTO movimientos_caja 
                               (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, 
                                monto, fecha_movimiento, forma_pago)
                               VALUES (?, 'ingreso', ?, ?, NOW(), ?)";

                $stmt = $db->prepare($queryMovimiento);
                $stmt->bind_param(
                    "isds",
                    $arqueo->id_arqueo_caja,
                    $concepto,
                    $monto,
                    $formaPago['descripcion_tipo_pago']
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'ok' => true,
                        'mensaje' => 'Ingreso registrado correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'Error al registrar ingreso'
                    ]);
                }
            } catch (\Exception $e) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }

    public static function registrarEgreso()
    {
        if (!self::verificarPermisoMovimientos()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $idUsuario = $_SESSION['id'];
            $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

            if (!$arqueo) {
                echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
                return;
            }

            $idProveedor = $datos['id_proveedor'] ?? null;
            $idServicio = $datos['id_servicio'] ?? null;
            $numeroComprobante = trim($datos['numero_comprobante'] ?? '');
            $concepto = trim($datos['concepto'] ?? '');
            $monto = $datos['monto'] ?? 0;
            $idTipoPago = $datos['id_tipo_pago'] ?? null;
            $observaciones = trim($datos['observaciones'] ?? '');

            if (!$idProveedor || empty($concepto) || $monto <= 0 || !$idTipoPago) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
                return;
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                // Obtener descripción de la forma de pago
                $queryFormaPago = "SELECT descripcion_tipo_pago FROM tipos_de_pagos WHERE id_tipo_pago = ?";
                $stmt = $db->prepare($queryFormaPago);
                $stmt->bind_param("i", $idTipoPago);
                $stmt->execute();
                $result = $stmt->get_result();
                $formaPago = $result->fetch_assoc();

                if (!$formaPago) {
                    throw new \Exception('Forma de pago no válida');
                }

                // 1. Registrar el EGRESO en la tabla egresos
                $queryEgreso = "INSERT INTO egresos 
                           (rela_arqueo_caja, rela_proveedor, rela_servicio, 
                            rela_usuario, numero_comprobante, concepto, monto, 
                            forma_pago, fecha_egreso, estado_egreso, observaciones)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'registrado', ?)";

                $stmtEgreso = $db->prepare($queryEgreso);
                $stmtEgreso->bind_param(
                    "iiisssdss",
                    $arqueo->id_arqueo_caja,
                    $idProveedor,
                    $idServicio,
                    $idUsuario,
                    $numeroComprobante,
                    $concepto,
                    $monto,
                    $formaPago['descripcion_tipo_pago'],
                    $observaciones
                );

                if (!$stmtEgreso->execute()) {
                    throw new \Exception('Error al registrar egreso: ' . $stmtEgreso->error);
                }

                $idEgreso = $db->insert_id;

                // 2. Registrar en movimientos_caja (para afectar el saldo)
                $queryMovimiento = "INSERT INTO movimientos_caja 
                               (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, 
                                monto, fecha_movimiento, forma_pago, rela_egreso)
                               VALUES (?, 'egreso', ?, ?, NOW(), ?, ?)";

                $stmtMov = $db->prepare($queryMovimiento);
                $stmtMov->bind_param(
                    "isdsi",
                    $arqueo->id_arqueo_caja,
                    $concepto,
                    $monto,
                    $formaPago['descripcion_tipo_pago'],
                    $idEgreso
                );

                if (!$stmtMov->execute()) {
                    throw new \Exception('Error al registrar movimiento: ' . $stmtMov->error);
                }

                $db->commit();

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Egreso registrado correctamente',
                    'id_egreso' => $idEgreso
                ]);
            } catch (\Exception $e) {
                $db->rollback();
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }

    public static function obtenerMovimientos()
    {
        if (!self::verificarPermisoMovimientos()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();

        try {
            // Obtener movimientos
            $queryMovimientos = "SELECT * FROM movimientos_caja 
                            WHERE rela_arqueo_caja = ? 
                            ORDER BY fecha_movimiento DESC";

            $stmt = $db->prepare($queryMovimientos);
            $stmt->bind_param("i", $arqueo->id_arqueo_caja);
            $stmt->execute();
            $result = $stmt->get_result();

            $movimientos = [];
            while ($row = $result->fetch_assoc()) {
                $movimientos[] = $row;
            }

            // Calcular totales
            $totalIngresos = 0;
            $totalEgresos = 0;

            foreach ($movimientos as $mov) {
                if ($mov['concepto_movimiento'] !== 'Apertura de caja - Monto inicial') {
                    if ($mov['tipo_movimiento'] === 'ingreso') {
                        $totalIngresos += $mov['monto'];
                    } else {
                        $totalEgresos += $mov['monto'];
                    }
                }
            }

            $saldoEsperado = $arqueo->monto_inicial + $totalIngresos - $totalEgresos;

            echo json_encode([
                'ok' => true,
                'movimientos' => $movimientos,
                'monto_inicial' => $arqueo->monto_inicial,
                'saldo_esperado' => $saldoEsperado
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public static function consultaVentas(Router $router)
    {
        if (!self::verificarPermisoConsulta()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            header('Location: /vendedorproductos/caja/abrir');
            exit;
        }

        // Obtener cajas 3 y 4
        $db = \Models\ActiveRecord::getDB();
        $query = "SELECT * FROM cajas WHERE id_caja IN (3, 4) AND activo = 1";
        $result = $db->query($query);
        $cajas = $result->fetch_all(MYSQLI_ASSOC);

        $router->render('vendedorproductos/ventas/consulta', [
            'cajas' => $cajas
        ]);
    }

    public static function apiObtenerVendedores()
    {
        // Desactivar el buffer de salida
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Establecer headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        if (!self::verificarPermisoConsulta()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $db = \Models\ActiveRecord::getDB();

            if (!$db) {
                throw new \Exception('No se pudo conectar a la base de datos');
            }

            // Query para obtener vendedores de productos (perfil 5)
            $query = "SELECT u.id_usuario, u.nombre_usuario 
                  FROM usuarios u
                  WHERE u.rela_perfil = 5
                  AND u.estado = 1
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
        header('Content-Type: application/json');

        if (!self::verificarPermisoConsulta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        $fechaDesde = $datos['fecha_desde'] ?? date('Y-m-d');
        $fechaHasta = $datos['fecha_hasta'] ?? date('Y-m-d');
        $idCaja = $datos['id_caja'] ?? null;
        $idVendedor = $datos['id_vendedor'] ?? null;

        $db = \Models\ActiveRecord::getDB();

        // Construir query base
        $query = "SELECT 
                    cc.id_cabecera_fact_cantin AS id_venta,
                    cc.fecha_hora_pago_cantina AS fecha_hora,
                    u.nombre_usuario AS vendedor,
                    c.nombre_caja AS caja,
                    p.nombre_producto_cantina AS producto,
                    dc.cantidad,
                    dc.precio_unitario,
                    (dc.cantidad * dc.precio_unitario) AS subtotal,
                    tp.descripcion_tipo_pago AS forma_pago,
                    cc.monto_total_cantina AS monto_total
                FROM cabecera_fact_cantina cc
                INNER JOIN detalle_fact_cantina dc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
                INNER JOIN productos_cantina p ON dc.rela_producto_cantina = p.id_producto_cantina
                INNER JOIN cantina cant ON cc.rela_cantina = cant.id_cantina
                INNER JOIN arqueo_cajas ac ON ac.rela_caja = cc.rela_arqueo_caja
                INNER JOIN cajas c ON ac.rela_caja = c.id_caja
                INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                INNER JOIN tipos_de_pagos tp ON cc.rela_tipo_de_pagos = tp.id_tipo_pago
                WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
                AND c.id_caja IN (3, 4)";

        $params = [$fechaDesde, $fechaHasta];
        $types = "ss";

        if ($idCaja) {
            $query .= " AND c.id_caja = ?";
            $params[] = $idCaja;
            $types .= "i";
        }

        if ($idVendedor) {
            $query .= " AND u.id_usuario = ?";
            $params[] = $idVendedor;
            $types .= "i";
        }

        $query .= " ORDER BY cc.fecha_hora_pago_cantina DESC";

        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $ventas = [];
            $totalVentas = 0;
            $totalProductos = 0;
            $montoTotal = 0;

            while ($row = $result->fetch_assoc()) {
                $ventas[] = $row;
                $totalVentas++;
                $totalProductos += $row['cantidad'];
                $montoTotal += $row['monto_total'];
            }

            // Agrupar ventas únicas
            $ventasUnicas = [];
            foreach ($ventas as $v) {
                $ventasUnicas[$v['id_venta']] = true;
            }

            echo json_encode([
                'ok' => true,
                'ventas' => $ventas,
                'resumen' => [
                    'total_ventas' => count($ventasUnicas),
                    'total_productos' => $totalProductos,
                    'monto_total' => $montoTotal
                ]
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }


    public static function verTicket(Router $router)
    {
        if (!self::verificarPermisoVenta()) {
            header('Location: /');
            exit;
        }

        $idVenta = $_GET['id_venta'] ?? null;

        if (!$idVenta) {
            header('Location: /vendedorproductos/caja/estado');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        try {
            // Obtener datos de la venta CON datos de facturación relacionados
            $queryVenta = "SELECT 
                    cc.id_cabecera_fact_cantin as id_venta,
                    cc.fecha_hora_pago_cantina as fecha_hora,
                    cc.monto_total_cantina as monto_total,
                    cc.tipo_comprobante,
                    cc.punto_venta,
                    cc.numero_ticket,
                    cc.numero_comprobante,
                    cc.observaciones,
                    
                    -- Datos de facturación
                    df.id_dato_facturacion,
                    df.tipo_documento,
                    df.numero_documento,
                    df.razon_social,
                    df.domicilio,
                    df.localidad,
                    df.provincia,
                    df.codigo_postal,
                    df.email_facturacion,
                    df.telefono_facturacion,
                    
                    -- Datos del vendedor
                    u.nombre_usuario as vendedor,
                    c.numero_caja,
                    c.nombre_caja,
                    tp.descripcion_tipo_pago as forma_pago
                    
                  FROM cabecera_fact_cantina cc
                  INNER JOIN arqueo_cajas ac ON cc.rela_arqueo_caja = ac.id_arqueo_caja
                  INNER JOIN cajas c ON ac.rela_caja = c.id_caja
                  INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                  INNER JOIN tipos_de_pagos tp ON cc.rela_tipo_de_pagos = tp.id_tipo_pago
                  LEFT JOIN datos_facturacion df ON cc.rela_datos_facturacion = df.id_dato_facturacion
                  WHERE cc.id_cabecera_fact_cantin = ?";

            $stmt = $db->prepare($queryVenta);
            $stmt->bind_param("i", $idVenta);
            $stmt->execute();
            $result = $stmt->get_result();
            $venta = $result->fetch_assoc();

            if (!$venta) {
                $_SESSION['error'] = 'Venta no encontrada';
                header('Location: /vendedorproductos/caja/estado');
                exit;
            }

            // Obtener detalles de PRODUCTOS
            $queryProductos = "SELECT 
                        p.nombre_producto_cantina as producto,
                        dc.cantidad,
                        dc.precio_unitario,
                        'producto' as tipo
                      FROM detalle_fact_cantina dc
                      INNER JOIN productos_cantina p ON dc.rela_producto_cantina = p.id_producto_cantina
                      WHERE dc.rela_cabecera_fact_cantin = ?
                      ORDER BY p.nombre_producto_cantina";

            $stmt = $db->prepare($queryProductos);
            $stmt->bind_param("i", $idVenta);
            $stmt->execute();
            $resultProductos = $stmt->get_result();

            // Obtener detalles de FICHAS
            $queryFichas = "SELECT 
          f.nombre_ficha as producto,
          dc.cantidad,
          dc.precio_unitario,
          'ficha' as tipo
        FROM detalle_fact_cantina dc
        INNER JOIN fichas f ON dc.rela_fichas = f.id_fichas
        WHERE dc.rela_cabecera_fact_cantin = ?
        ORDER BY f.precio_ficha";

            $stmt = $db->prepare($queryFichas);
            $stmt->bind_param("i", $idVenta);
            $stmt->execute();
            $resultFichas = $stmt->get_result();

            // Combinar productos y fichas
            $detalles = [];
            $totalItems = 0;

            while ($row = $resultProductos->fetch_assoc()) {
                $detalles[] = $row;
                $totalItems += $row['cantidad'];
            }

            while ($row = $resultFichas->fetch_assoc()) {
                $detalles[] = $row;
                $totalItems += $row['cantidad'];
            }

            $router->render('vendedorproductos/ventas/ticket', [
                'venta' => $venta,
                'detalles' => $detalles,
                'total_productos' => $totalItems
            ]);
        } catch (\Exception $e) {
            error_log("Error al generar ticket: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar el ticket: ' . $e->getMessage();
            header('Location: /vendedorproductos/caja/estado');
            exit;
        }
    }

    public static function completarVenta()
    {
        header('Content-Type: application/json');

        if (!self::verificarPermisoVenta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        // Validar que haya productos O fichas
        $productos = $datos['productos'] ?? [];
        $fichas = $datos['fichas'] ?? [];

        if (empty($productos) && empty($fichas)) {
            echo json_encode(['ok' => false, 'mensaje' => 'No hay productos ni fichas en la venta']);
            return;
        }

        if (!isset($datos['metodo_pago']) || empty($datos['metodo_pago'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Debe seleccionar un método de pago']);
            return;
        }

        if (!isset($datos['tipo_comprobante']) || empty($datos['tipo_comprobante'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Debe seleccionar un tipo de comprobante']);
            return;
        }

        if (!isset($datos['total']) || $datos['total'] <= 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'El total debe ser mayor a 0']);
            return;
        }

        // Validar datos de facturación si es necesario
        $datosFacturacion = $datos['datos_facturacion'] ?? null;
        $codigoComprobante = $datos['tipo_comprobante'];

        // Verificar si el comprobante requiere datos de facturación
        $tipoComprobante = \Models\TipoComprobante::obtenerPorCodigo($codigoComprobante);

        if (!$tipoComprobante) {
            echo json_encode(['ok' => false, 'mensaje' => 'Tipo de comprobante no válido']);
            return;
        }

        if ($tipoComprobante->requiere_datos_facturacion == 1 && !$datosFacturacion) {
            echo json_encode(['ok' => false, 'mensaje' => 'Debe completar los datos de facturación']);
            return;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();
        $db->begin_transaction();

        try {
            $idMetodoPago = $datos['metodo_pago'];
            $montoTotal = $datos['total'];
            $idDatosFacturacion = null;

            // PASO 1: Guardar datos de facturación si existen
            if ($datosFacturacion && $tipoComprobante->requiere_datos_facturacion == 1) {
                $datosFactObj = \Models\DatosFacturacion::buscarOCrear($datosFacturacion);

                if ($datosFactObj) {
                    $idDatosFacturacion = $datosFactObj->id_dato_facturacion;
                } else {
                    throw new \Exception('Error al guardar datos de facturación');
                }
            }

            // Determinar punto de venta según la caja
            $puntoVenta = ($arqueo->rela_caja == 3) ? 3 : 4;

            // PASO 2: Obtener y bloquear el siguiente número de ticket
            $queryBloqueo = "SELECT ultimo_numero 
                    FROM control_numeracion_tickets 
                    WHERE tipo_comprobante = ? AND punto_venta = ?
                    FOR UPDATE";

            $stmt = $db->prepare($queryBloqueo);
            $stmt->bind_param("si", $codigoComprobante, $puntoVenta);
            $stmt->execute();
            $result = $stmt->get_result();
            $control = $result->fetch_assoc();

            if (!$control) {
                $queryCrear = "INSERT INTO control_numeracion_tickets (tipo_comprobante, punto_venta, ultimo_numero, fecha_actualizacion)
                      VALUES (?, ?, 0, NOW())";
                $stmt = $db->prepare($queryCrear);
                $stmt->bind_param("si", $codigoComprobante, $puntoVenta);
                $stmt->execute();
                $numeroTicket = 1;
            } else {
                $numeroTicket = $control['ultimo_numero'] + 1;
            }

            // Generar número de comprobante
            $numeroComprobante = str_pad($puntoVenta, 4, '0', STR_PAD_LEFT) . '-' .
                str_pad($numeroTicket, 8, '0', STR_PAD_LEFT);

            // PASO 3: Actualizar el contador
            $queryActualizar = "UPDATE control_numeracion_tickets 
                       SET ultimo_numero = ?, fecha_actualizacion = NOW()
                       WHERE tipo_comprobante = ? AND punto_venta = ?";

            $stmt = $db->prepare($queryActualizar);
            $stmt->bind_param("isi", $numeroTicket, $codigoComprobante, $puntoVenta);
            $stmt->execute();

            // PASO 4: Obtener cantina
            $queryCantina = "SELECT id_cantina FROM cantina LIMIT 1";
            $result = $db->query($queryCantina);
            $cantina = $result->fetch_assoc();

            if (!$cantina) {
                throw new \Exception('No hay cantina configurada en el sistema');
            }

            $idCantina = $cantina['id_cantina'];

            // PASO 5: Insertar cabecera de venta
            $observaciones = $datos['observaciones'] ?? '';

            $queryCabecera = "INSERT INTO cabecera_fact_cantina 
                     (rela_cantina, rela_tipo_de_pagos, rela_arqueo_caja, rela_datos_facturacion,
                      tipo_comprobante, punto_venta, numero_ticket, numero_comprobante,
                      monto_total_cantina, observaciones, fecha_hora_pago_cantina)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($queryCabecera);
            $stmt->bind_param(
                "iiiissisds",
                $idCantina,
                $idMetodoPago,
                $arqueo->id_arqueo_caja,
                $idDatosFacturacion,
                $codigoComprobante,
                $puntoVenta,
                $numeroTicket,
                $numeroComprobante,
                $montoTotal,
                $observaciones
            );

            if (!$stmt->execute()) {
                throw new \Exception('Error al registrar la venta: ' . $stmt->error);
            }

            $idVenta = $db->insert_id;

            // PASO 6A: Insertar detalles de PRODUCTOS
            if (!empty($productos)) {
                foreach ($productos as $producto) {
                    $idProducto = $producto['id_producto_cantina'];
                    $idStock = $producto['id_stock'];
                    $cantidad = $producto['cantidad'];
                    $precioUnitario = $producto['precio'];

                    // Verificar stock disponible
                    $queryStock = "SELECT stock_cantina FROM stock_cantina WHERE id_stock_cantina = ?";
                    $stmt = $db->prepare($queryStock);
                    $stmt->bind_param("i", $idStock);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stockActual = $result->fetch_assoc();

                    if (!$stockActual) {
                        throw new \Exception("No se encontró stock para el producto ID: {$idProducto}");
                    }

                    if ($stockActual['stock_cantina'] < $cantidad) {
                        throw new \Exception("Stock insuficiente para el producto ID: {$idProducto}");
                    }

                    // Insertar detalle de producto
                    $queryDetalle = "INSERT INTO detalle_fact_cantina 
                            (rela_cabecera_fact_cantin, fecha_reserva_cantina, rela_producto_cantina, cantidad, precio_unitario)
                            VALUES (?, NOW(), ?, ?, ?)";

                    $stmt = $db->prepare($queryDetalle);
                    $stmt->bind_param("iiid", $idVenta, $idProducto, $cantidad, $precioUnitario);

                    if (!$stmt->execute()) {
                        throw new \Exception('Error al registrar detalle de producto: ' . $stmt->error);
                    }

                    // Actualizar stock
                    $queryActualizarStock = "UPDATE stock_cantina 
                                    SET stock_cantina = stock_cantina - ? 
                                    WHERE id_stock_cantina = ?";

                    $stmt = $db->prepare($queryActualizarStock);
                    $stmt->bind_param("ii", $cantidad, $idStock);
                    $stmt->execute();

                    // Verificar stock resultante + datos para notificación
                    $queryVerificar = "SELECT sc.stock_cantina, pc.nombre_producto_cantina, pc.stock_minimo, c.nombre_cantina
                                    FROM stock_cantina sc
                                    INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                                    INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                                    WHERE sc.id_stock_cantina = ?";
                    $stmt = $db->prepare($queryVerificar);
                    $stmt->bind_param("i", $idStock);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $infoStock = $result->fetch_assoc();

                    if ($infoStock) {
                        $nuevoStock = (int) $infoStock['stock_cantina'];
                        $stockMinimo = (int) $infoStock['stock_minimo'];
                        $stockAntesDeVenta = $nuevoStock + $cantidad;

                        // Marcar sin stock si llegó a 0 (lógica que ya tenías)
                        if ($nuevoStock == 0) {
                            $queryEstado = "UPDATE productos_cantina SET rela_estado_producto = 2 WHERE id_producto_cantina = ?";
                            $stmt = $db->prepare($queryEstado);
                            $stmt->bind_param("i", $idProducto);
                            $stmt->execute();
                        }

                        // Notificar SOLO en el momento en que cruza el umbral hacia abajo
                        if ($stockAntesDeVenta > $stockMinimo && $nuevoStock <= $stockMinimo) {
                            \Classes\Notificaciones::notificarStockBajo(
                                $infoStock['nombre_producto_cantina'],
                                $nuevoStock,
                                $infoStock['nombre_cantina']
                            );
                        }
                    }
                }
            }

            // PASO 6B: Insertar detalles de FICHAS
            if (!empty($fichas)) {
                foreach ($fichas as $ficha) {
                    $idFicha = $ficha['id_fichas'];
                    $cantidad = $ficha['cantidad'];
                    $precioUnitario = $ficha['precio'];

                    // Verificar stock de fichas
                    $queryStockFicha = "SELECT cantidad_ficha FROM fichas WHERE id_fichas = ?";
                    $stmt = $db->prepare($queryStockFicha);
                    $stmt->bind_param("i", $idFicha);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stockFicha = $result->fetch_assoc();

                    if (!$stockFicha) {
                        throw new \Exception("No se encontró la ficha ID: {$idFicha}");
                    }

                    if ($stockFicha['cantidad_ficha'] < $cantidad) {
                        throw new \Exception("Stock insuficiente de fichas");
                    }

                    // Insertar detalle de ficha
                    $queryDetalleFicha = "INSERT INTO detalle_fact_cantina 
                         (rela_cabecera_fact_cantin, fecha_reserva_cantina, rela_fichas, cantidad, precio_unitario)
                         VALUES (?, NOW(), ?, ?, ?)";

                    $stmt = $db->prepare($queryDetalleFicha);
                    $stmt->bind_param("iiid", $idVenta, $idFicha, $cantidad, $precioUnitario);

                    if (!$stmt->execute()) {
                        throw new \Exception('Error al registrar detalle de ficha: ' . $stmt->error);
                    }

                    // Actualizar cantidad de fichas
                    $queryActualizarFichas = "UPDATE fichas 
                             SET cantidad_ficha = cantidad_ficha - ? 
                             WHERE id_fichas = ?";

                    $stmt = $db->prepare($queryActualizarFichas);
                    $stmt->bind_param("ii", $cantidad, $idFicha);
                    $stmt->execute();
                }
            }

            // PASO 7: Obtener forma de pago
            $queryMetodo = "SELECT descripcion_tipo_pago FROM tipos_de_pagos WHERE id_tipo_pago = ?";
            $stmt = $db->prepare($queryMetodo);
            $stmt->bind_param("i", $idMetodoPago);
            $stmt->execute();
            $result = $stmt->get_result();
            $metodoPago = $result->fetch_assoc();

            $formaPago = $metodoPago['descripcion_tipo_pago'] ?? 'Efectivo';

            // Concepto detallado
            $totalProductos = count($productos);
            $totalFichas = count($fichas);
            $conceptoParts = [];
            if ($totalProductos > 0) $conceptoParts[] = "{$totalProductos} producto(s)";
            if ($totalFichas > 0) $conceptoParts[] = "{$totalFichas} ficha(s)";
            $conceptoMovimiento = "Venta: " . implode(" + ", $conceptoParts) . " - {$codigoComprobante} {$numeroComprobante}";

            // PASO 8: Registrar movimiento de caja
            $queryMovimiento = "INSERT INTO movimientos_caja 
                       (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, monto, fecha_movimiento, forma_pago)
                       VALUES (?, 'ingreso', ?, ?, NOW(), ?)";

            $stmt = $db->prepare($queryMovimiento);
            $stmt->bind_param(
                "isds",
                $arqueo->id_arqueo_caja,
                $conceptoMovimiento,
                $montoTotal,
                $formaPago
            );

            if (!$stmt->execute()) {
                throw new \Exception('Error al registrar movimiento: ' . $stmt->error);
            }

            // PASO 9: Actualizar arqueo
            $queryArqueo = "UPDATE arqueo_cajas 
                   SET total_ventas = total_ventas + ? 
                   WHERE id_arqueo_caja = ?";

            $stmt = $db->prepare($queryArqueo);
            $stmt->bind_param("di", $montoTotal, $arqueo->id_arqueo_caja);
            $stmt->execute();

            // Commit
            $db->commit();

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Venta completada correctamente',
                'id_venta' => $idVenta,
                'numero_comprobante' => $numeroComprobante,
                'tipo_comprobante' => $codigoComprobante,
                'monto_total' => $montoTotal,
                'total_productos' => $totalProductos,
                'total_fichas' => $totalFichas,
                'ver_ticket' => "/vendedorproductos/ventas/ticket?id_venta={$idVenta}"
            ]);
        } catch (\Exception $e) {
            $db->rollback();
            error_log("ERROR en completarVenta: " . $e->getMessage());

            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al procesar la venta: ' . $e->getMessage()
            ]);
        }
    }


    public static function obtenerTiposComprobante()
    {
        header('Content-Type: application/json');

        if (!self::verificarPermisoVenta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $tipos = \Models\TipoComprobante::obtenerActivos();

            $tiposArray = [];
            foreach ($tipos as $tipo) {
                $tiposArray[] = [
                    'id' => $tipo->id_tipo_comprobante,
                    'codigo' => $tipo->codigo,
                    'descripcion' => $tipo->descripcion,
                    'descripcion_corta' => $tipo->descripcion_corta,
                    'valido_afip' => $tipo->valido_afip,
                    'requiere_cuit' => $tipo->requiere_cuit,
                    'requiere_datos_facturacion' => $tipo->requiere_datos_facturacion
                ];
            }

            echo json_encode([
                'ok' => true,
                'tipos' => $tiposArray
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}
