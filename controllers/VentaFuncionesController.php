<?php

namespace Controllers;

use Models\Funciones;
use Models\Butaca;
use Models\ArqueoCaja;
use MVC\Router;
use Middlewares\ValidarModulo;

class VentaFuncionesController
{

    private static function verificarPermisos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login'])) {
            return false;
        }

        return ValidarModulo::tiene('VENTA_FUNCIONES');
    }

    private static function verificarPermisoDevolucion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login'])) {
            return false;
        }

        return ValidarModulo::tiene('DEVOLUCION_ENTRADAS');
    }

    private static function verificarCajaAbierta()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        return $arqueo !== null;
    }

    public static function index(Router $router)
    {
        if (!self::verificarPermisos()) {
            header('Location: /');
            exit;
        }

        if (!self::verificarCajaAbierta()) {
            $_SESSION['error'] = 'Debes abrir tu caja antes de realizar ventas';
            header('Location: /vendedor/caja/abrir');
            exit;
        }

        $funciones = Funciones::obtenerFuncionesActivas();

        $router->render('vendedor/funciones/listado', [
            'funciones' => $funciones
        ]);
    }

    public static function buscar()
    {
        if (!self::verificarPermisos()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término está vacío']);
            return;
        }

        $funcion = Funciones::buscarFuncion($termino);

        if ($funcion) {
            echo json_encode(['ok' => true, 'funcion' => $funcion]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la función']);
        }
    }

    public static function apiObtenerButacasFuncion()
    {
        if (!self::verificarPermisos()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idFuncion = $_GET['id_funcion'] ?? null;

        if (!$idFuncion || !is_numeric($idFuncion)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de función inválido']);
            return;
        }

        try {
            $db = \Models\ActiveRecord::getDB();

            // Verificar que la función existe y está activa
            $queryFuncion = "SELECT f.id_funcion, f.rela_salas, s.capacidad_sala, 
                           s.filas_sala, s.columnas_sala
                    FROM funciones f
                    INNER JOIN salas s ON f.rela_salas = s.id_sala
                    WHERE f.id_funcion = ? AND f.estado = 1";

            $stmt = $db->prepare($queryFuncion);
            $stmt->bind_param("i", $idFuncion);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Función no encontrada']);
                return;
            }

            $funcion = $resultado->fetch_assoc();
            $idSala = $funcion['rela_salas'];

            // Obtener butacas con su estado actual
            $queryButacas = "SELECT 
                            b.id_butaca,
                            b.fila_butaca,
                            b.numero_butaca,
                            CASE 
                                WHEN bv.id_venta_butaca IS NOT NULL THEN 3
                                WHEN b.rela_estado_butaca = 2 THEN 2
                                ELSE 1
                            END as estado,
                            CONCAT('F', b.fila_butaca, '-C', b.numero_butaca) as label
                        FROM butacas b
                        LEFT JOIN butacas_vendidas bv ON bv.id_butaca = b.id_butaca 
                            AND bv.id_funcion = ?
                        WHERE b.rela_salas = ?
                        ORDER BY b.fila_butaca, b.numero_butaca";

            $stmt2 = $db->prepare($queryButacas);
            $stmt2->bind_param("ii", $idFuncion, $idSala);
            $stmt2->execute();
            $resultado2 = $stmt2->get_result();

            $butacas = [];
            while ($row = $resultado2->fetch_assoc()) {
                $butacas[] = [
                    'id' => (int)$row['id_butaca'],
                    'fila' => (int)$row['fila_butaca'],
                    'numero' => (int)$row['numero_butaca'],
                    'estado' => (int)$row['estado'],
                    'label' => $row['label']
                ];
            }

            echo json_encode([
                'ok' => true,
                'layout' => [
                    'sala' => [
                        'id' => (int)$idSala,
                        'filas' => (int)$funcion['filas_sala'],
                        'columnas' => (int)$funcion['columnas_sala'],
                        'capacidad' => (int)$funcion['capacidad_sala']
                    ],
                    'butacas' => $butacas
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error en apiObtenerButacasFuncion: " . $e->getMessage());
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al obtener butacas',
                'error' => $e->getMessage()
            ]);
        }
    }

    public static function verButacas(Router $router)
    {
        if (!self::verificarPermisos()) {
            header('Location: /');
            exit;
        }

        if (!self::verificarCajaAbierta()) {
            $_SESSION['error'] = 'Debes abrir tu caja antes de realizar ventas';
            header('Location: /vendedor/caja/abrir');
            exit;
        }

        $idFuncion = $_GET['id_funcion'] ?? null;

        if (!$idFuncion || !is_numeric($idFuncion)) {
            header('Location: /vendedor/funciones/listado');
            exit;
        }
        $db = \Models\ActiveRecord::getDB();
        $queryFuncion = "SELECT 
                            f.*,
                            p.titulo_pelicula,
                            p.imagen_pelicula,
                            t.turno_horario,
                            te.tipo_entrada_desc,
                            te.precio_entrada
                        FROM funciones f
                        INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                        INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
                        LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
                        WHERE f.id_funcion = '$idFuncion' AND f.estado = 1";

        $resultado = $db->query($queryFuncion);

        if (!$resultado || $resultado->num_rows === 0) {
            header('Location: /vendedor/funciones/listado');
            exit;
        }

        $funcion = $resultado->fetch_assoc();


        $queryTiposPago = "SELECT id_tipo_pago, descripcion_tipo_pago 
                        FROM tipos_de_pagos 
                        ORDER BY id_tipo_pago";
        $resultadoTiposPago = $db->query($queryTiposPago);
        $tiposPago = $resultadoTiposPago->fetch_all(MYSQLI_ASSOC);

        $router->render('vendedor/funciones/butacas', [
            'funcion' => $funcion,
            'usuario' => $_SESSION,
            'tipos_pago' => $tiposPago
        ]);
    }

    public static function procesarVenta()
    {
        if (!self::verificarPermisos()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if (!self::verificarCajaAbierta()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No tienes caja abierta']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $butacasIds = $datos['butacas'] ?? [];
            $idFuncion = $datos['id_funcion'] ?? null;
            $tipoPago = $datos['tipo_pago'] ?? 1;
            $tipoComprobante = $datos['tipo_comprobante'] ?? 'TICKET';
            $observaciones = $datos['observaciones'] ?? '';

            if (empty($butacasIds) || !$idFuncion) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
                return;
            }
            $tipoComprobanteObj = \Models\TipoComprobante::obtenerPorCodigo($tipoComprobante);

            if (!$tipoComprobanteObj) {
                throw new \Exception('Tipo de comprobante no válido');
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                $idUsuario = $_SESSION['id'];

                $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);
                if (!$arqueo) {
                    throw new \Exception('No tienes caja abierta');
                }
                $puntoVenta = (int)$arqueo->numero_caja;
                if ($puntoVenta <= 0) {
                    throw new \Exception('Número de caja inválido');
                }

                // 1. OBTENER DATOS DE LA FUNCIÓN
                $queryFuncion = "SELECT te.precio_entrada, f.rela_tipo_entrada, f.rela_salas
                        FROM funciones f
                        LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
                        WHERE f.id_funcion = ?";

                $stmt = $db->prepare($queryFuncion);
                $stmt->bind_param("i", $idFuncion);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows === 0) {
                    throw new \Exception('Función no encontrada');
                }

                $funcion = $resultado->fetch_assoc();
                $precioEntrada = $funcion['precio_entrada'] ?? 2500;
                $tipoEntrada = $funcion['rela_tipo_entrada'] ?? 1;
                $idSala = $funcion['rela_salas'];

                $montoTotal = count($butacasIds) * $precioEntrada;

                // 2. GENERAR NÚMERO DE TICKET (NUEVA FUNCIÓN PHP)
                $ticket = self::generarNumeroTicket($db, $tipoComprobante, $puntoVenta);

                // 3. CREAR CABECERA DE FACTURA
                $queryCabecera = "INSERT INTO cabecera_fact_cine 
                            (pago_fecha_hora, monto_total, rela_tipos_de_pagos, rela_arqueo_caja, 
                             rela_usuario_vendedor, numero_comprobante, tipo_comprobante, 
                             punto_venta, numero_ticket, observaciones)
                            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmtCab = $db->prepare($queryCabecera);
                $stmtCab->bind_param(
                    "diiissiis",
                    $montoTotal,
                    $tipoPago,
                    $arqueo->id_arqueo_caja,
                    $idUsuario,
                    $ticket['formato'],
                    $ticket['tipo'],
                    $ticket['punto_venta'],
                    $ticket['numero'],
                    $observaciones
                );

                if (!$stmtCab->execute()) {
                    throw new \Exception('Error al registrar venta: ' . $stmtCab->error);
                }

                $idCabecera = $db->insert_id;
                $fechaHora = date('Y-m-d H:i:s');

                // 4. PROCESAR CADA BUTACA
                foreach ($butacasIds as $idButaca) {
                    // 4.1 Verificar que la butaca existe
                    $queryButaca = "SELECT id_butaca, fila_butaca, numero_butaca, rela_salas, rela_estado_butaca
                               FROM butacas
                               WHERE id_butaca = ? AND rela_salas = ?";
                    $stmtB = $db->prepare($queryButaca);
                    $stmtB->bind_param("ii", $idButaca, $idSala);
                    $stmtB->execute();
                    $resultadoB = $stmtB->get_result();

                    if ($resultadoB->num_rows === 0) {
                        throw new \Exception("Butaca $idButaca no encontrada en sala $idSala");
                    }

                    $butaca = $resultadoB->fetch_assoc();
                    $numeroButaca = $butaca['numero_butaca'];
                    $filaButaca = $butaca['fila_butaca'];

                    // 4.1.1 Verificar que la butaca no esté bloqueada por el administrador
                    if ((int)$butaca['rela_estado_butaca'] === 2) {
                        throw new \Exception("La butaca Fila $filaButaca-$numeroButaca no está disponible (bloqueada)");
                    }

                    // 4.2 Verificar disponibilidad
                    $queryVerif = "SELECT id_venta_butaca 
                              FROM butacas_vendidas 
                              WHERE id_butaca = ? AND id_funcion = ?";
                    $stmtV = $db->prepare($queryVerif);
                    $stmtV->bind_param("ii", $idButaca, $idFuncion);
                    $stmtV->execute();
                    $resultadoV = $stmtV->get_result();

                    if ($resultadoV->num_rows > 0) {
                        throw new \Exception("La butaca Fila $filaButaca-$numeroButaca ya está vendida");
                    }

                    // 4.3 CREAR ENTRADA
                    $queryInsertEntrada = "INSERT INTO entradas 
                                      (rela_tipo_entrada, rela_funcion, estado)
                                      VALUES (?, ?, 1)";
                    $stmtI = $db->prepare($queryInsertEntrada);
                    $stmtI->bind_param("ii", $tipoEntrada, $idFuncion);

                    if (!$stmtI->execute()) {
                        throw new \Exception("Error al crear entrada: " . $stmtI->error);
                    }

                    $idEntrada = $db->insert_id;

                    // 4.4 Crear detalle de factura
                    $queryDetalle = "INSERT INTO detalle_fact_cine 
                               (rela_cabecera_fact, rela_entrada, precio_venta, fecha_venta)
                               VALUES (?, ?, ?, ?)";
                    $stmtD = $db->prepare($queryDetalle);
                    $stmtD->bind_param("iids", $idCabecera, $idEntrada, $precioEntrada, $fechaHora);

                    if (!$stmtD->execute()) {
                        throw new \Exception("Error al registrar detalle: " . $stmtD->error);
                    }

                    // 4.5 Registrar en butacas_vendidas
                    $queryButacaVendida = "INSERT INTO butacas_vendidas 
                                      (id_butaca, id_funcion, id_entrada, fecha_venta)
                                      VALUES (?, ?, ?, NOW())";
                    $stmtBV = $db->prepare($queryButacaVendida);
                    $stmtBV->bind_param("iii", $idButaca, $idFuncion, $idEntrada);

                    if (!$stmtBV->execute()) {
                        throw new \Exception("Error al marcar butaca como vendida: " . $stmtBV->error);
                    }
                }

                // 5. REGISTRAR MOVIMIENTO DE CAJA
                $queryMovimiento = "INSERT INTO movimientos_caja 
                               (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, monto, fecha_movimiento, forma_pago)
                               VALUES (?, 'ingreso', ?, ?, ?, ?)";
                $stmtMov = $db->prepare($queryMovimiento);
                $concepto = "Venta de " . count($butacasIds) . " entrada(s) - Función #$idFuncion";
                $formaPago = self::obtenerFormaPago($tipoPago);
                $stmtMov->bind_param(
                    "isdss",
                    $arqueo->id_arqueo_caja,
                    $concepto,
                    $montoTotal,
                    $fechaHora,
                    $formaPago
                );

                if (!$stmtMov->execute()) {
                    throw new \Exception('Error al registrar movimiento de caja: ' . $stmtMov->error);
                }

                // 6. ACTUALIZAR ARQUEO
                $queryActualizarArqueo = "UPDATE arqueo_cajas
                                     SET total_ventas = total_ventas + ?
                                     WHERE id_arqueo_caja = ?";
                $stmtActualizarArqueo = $db->prepare($queryActualizarArqueo);
                $stmtActualizarArqueo->bind_param("di", $montoTotal, $arqueo->id_arqueo_caja);
                $stmtActualizarArqueo->execute();

                // 7. COMMIT
                $db->commit();

                $nombreComprobante = $tipoComprobante === 'TICKET' ? 'TICKET' : "FACTURA $tipoComprobante";

                echo json_encode([
                    'ok' => true,
                    'mensaje' => "$nombreComprobante procesado correctamente",
                    'id_venta' => $idCabecera,
                    'numero_comprobante' => $ticket['formato'],
                    'numero_ticket' => $ticket['numero'],
                    'tipo_comprobante' => $ticket['tipo'],
                    'punto_venta' => $ticket['punto_venta'],
                    'monto_total' => $montoTotal,
                    'cantidad_entradas' => count($butacasIds),
                    'redirigir' => "/vendedor/funciones/ticket?id=$idCabecera"
                ]);
            } catch (\Exception $e) {
                $db->rollback();
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error al procesar venta: ' . $e->getMessage()
                ]);
            }
        }
    }

    /**
     * NUEVA FUNCIÓN: Genera número de ticket directamente en PHP
     * Sin dependencia de funciones SQL
     */
    private static function generarNumeroTicket($db, $tipoComprobante = 'TICKET', $puntoVenta = 1)
    {
        try {
            // Asegurar tipo en mayúsculas
            $tipoComprobante = strtoupper($tipoComprobante);

            // Validar tipo
            $tiposValidos = ['TICKET', 'A', 'B', 'C'];
            if (!in_array($tipoComprobante, $tiposValidos)) {
                $tipoComprobante = 'TICKET';
            }

            // Verificar/crear registro en control_numeracion_tickets
            $queryVerificar = "SELECT ultimo_numero FROM control_numeracion_tickets 
                            WHERE tipo_comprobante = ? AND punto_venta = ?";
            $stmtV = $db->prepare($queryVerificar);
            $stmtV->bind_param("si", $tipoComprobante, $puntoVenta);
            $stmtV->execute();
            $resultV = $stmtV->get_result();

            $ultimoNumero = 0;

            if ($resultV->num_rows === 0) {
                // Crear registro si no existe
                $queryCrear = "INSERT INTO control_numeracion_tickets 
                            (tipo_comprobante, punto_venta, ultimo_numero) 
                            VALUES (?, ?, 1)";
                $stmtC = $db->prepare($queryCrear);
                $stmtC->bind_param("si", $tipoComprobante, $puntoVenta);

                if (!$stmtC->execute()) {
                    throw new \Exception('Error al crear control de numeración');
                }

                $ultimoNumero = 1;
            } else {
                // Incrementar el último número
                $row = $resultV->fetch_assoc();
                $ultimoNumero = $row['ultimo_numero'] + 1;

                // Actualizar en la base de datos
                $queryActualizar = "UPDATE control_numeracion_tickets 
                                   SET ultimo_numero = ? 
                                   WHERE tipo_comprobante = ? AND punto_venta = ?";
                $stmtA = $db->prepare($queryActualizar);
                $stmtA->bind_param("isi", $ultimoNumero, $tipoComprobante, $puntoVenta);

                if (!$stmtA->execute()) {
                    throw new \Exception('Error al actualizar numeración');
                }
            }

            // Formatear según el tipo
            if ($tipoComprobante === 'TICKET') {
                $numeroFormateado = sprintf(
                    'TICKET COMPROBANTE - N° %s-%s',
                    str_pad($puntoVenta, 4, '0', STR_PAD_LEFT),
                    str_pad($ultimoNumero, 8, '0', STR_PAD_LEFT)
                );
            } else {
                $numeroFormateado = sprintf(
                    'FACTURA "%s" - N° %s-%s',
                    $tipoComprobante,
                    str_pad($puntoVenta, 4, '0', STR_PAD_LEFT),
                    str_pad($ultimoNumero, 8, '0', STR_PAD_LEFT)
                );
            }

            return [
                'tipo' => $tipoComprobante,
                'punto_venta' => $puntoVenta,
                'numero' => $ultimoNumero,
                'formato' => $numeroFormateado
            ];
        } catch (\Exception $e) {
            error_log("Error generando número de ticket: " . $e->getMessage());
            throw new \Exception('No se pudo generar número de ticket: ' . $e->getMessage());
        }
    }

    public static function verTicket(Router $router)
    {
        if (!self::verificarPermisos()) {
            header('Location: /');
            exit;
        }

        $idVenta = $_GET['id'] ?? null;

        if (!$idVenta || !is_numeric($idVenta)) {
            header('Location: /vendedor/funciones/listado');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        // Query para obtener datos generales
        $query = "SELECT 
                cf.id_pagos,
                cf.numero_comprobante,
                cf.tipo_comprobante,        
                cf.punto_venta,              
                cf.numero_ticket,            
                cf.pago_fecha_hora,
                cf.monto_total,
                cf.observaciones,
                cf.rela_arqueo_caja,
                u.nombre_usuario as vendedor,
                tp.descripcion_tipo_pago as tipo_pago,
                f.fecha_hora as fecha_funcion,
                p.titulo_pelicula,
                s.id_sala,
                t.turno_horario,
                te.tipo_entrada_desc
            FROM cabecera_fact_cine cf
            INNER JOIN usuarios u ON cf.rela_usuario_vendedor = u.id_usuario
            INNER JOIN tipos_de_pagos tp ON cf.rela_tipos_de_pagos = tp.id_tipo_pago
            INNER JOIN detalle_fact_cine df ON df.rela_cabecera_fact = cf.id_pagos
            INNER JOIN entradas e ON df.rela_entrada = e.id_entrada
            INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
            INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
            INNER JOIN salas s ON f.rela_salas = s.id_sala
            INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
            LEFT JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
            WHERE cf.id_pagos = ?
            LIMIT 1";


        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idVenta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (!$resultado->num_rows) {
            $_SESSION['error'] = 'Venta no encontrada';
            header('Location: /vendedor/funciones/listado');
            exit;
        }

        $venta = $resultado->fetch_assoc();

        // Query para obtener detalles
        $queryDetalles = "SELECT 
                            df.precio_venta,
                            COUNT(DISTINCT e.id_entrada) as cantidad,
                            e.rela_funcion as id_funcion
                        FROM detalle_fact_cine df
                        INNER JOIN entradas e ON df.rela_entrada = e.id_entrada
                        WHERE df.rela_cabecera_fact = ?
                        GROUP BY df.precio_venta, e.rela_funcion
                        ORDER BY df.precio_venta DESC";

        $stmt2 = $db->prepare($queryDetalles);
        $stmt2->bind_param("i", $idVenta);
        $stmt2->execute();
        $resultado2 = $stmt2->get_result();

        $detalles = [];
        $totalEntradas = 0;

        while ($row = $resultado2->fetch_assoc()) {
            // Obtener butacas de esta venta
            $queryButacas = "SELECT 
                                b.fila_butaca,
                                b.numero_butaca
                            FROM detalle_fact_cine df2
                            INNER JOIN entradas e2 ON df2.rela_entrada = e2.id_entrada
                            INNER JOIN butacas_vendidas bv ON bv.id_entrada = e2.id_entrada
                            INNER JOIN butacas b ON bv.id_butaca = b.id_butaca
                            WHERE df2.rela_cabecera_fact = ?
                            ORDER BY b.fila_butaca, b.numero_butaca";

            $stmtButacas = $db->prepare($queryButacas);
            $stmtButacas->bind_param("i", $idVenta);
            $stmtButacas->execute();
            $resultButacas = $stmtButacas->get_result();

            $butacasArray = [];
            while ($butaca = $resultButacas->fetch_assoc()) {
                $butacasArray[] = 'FILA ' . str_pad($butaca['fila_butaca'], 2, '0', STR_PAD_LEFT) .
                    ' - COL ' . str_pad($butaca['numero_butaca'], 2, '0', STR_PAD_LEFT);
            }

            $row['butacas_detalle'] = implode(', ', $butacasArray);
            $detalles[] = $row;
            $totalEntradas += $row['cantidad'];
        }

        $router->render('vendedor/funciones/ticket', [
            'venta' => $venta,
            'detalles' => $detalles,
            'total_entradas' => $totalEntradas
        ]);
    }

    private static function obtenerFormaPago($idTipoPago)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT descripcion_tipo_pago FROM tipos_de_pagos WHERE id_tipo_pago = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idTipoPago);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($row = $resultado->fetch_assoc()) {
            switch ($idTipoPago) {
                case 1:
                    return 'efectivo';
                case 2:
                    return 'transferencia';
                case 3:
                    return 'tarjeta';
                case 4:
                    return 'otro';
                default:
                    return 'efectivo';
            }
        }

        return 'efectivo';
    }

    public static function obtenerTiposComprobante()
    {
        header('Content-Type: application/json');

        if (!self::verificarPermisos()) {
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

    public static function procesarDevolucion()
    {
        if (!self::verificarPermisoDevolucion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);
        $idEntrada     = $datos['id_entrada'] ?? null;
        $observaciones = trim($datos['observaciones'] ?? '');

        if (!$idEntrada) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
            return;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            echo json_encode(['ok' => false, 'mensaje' => 'No tenés caja abierta']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();
        $db->begin_transaction();

        try {
            $idEntradaEscaped = $db->escape_string($idEntrada);

            // 1. Obtener datos de la entrada + método de pago original
            $queryEntrada = "SELECT 
                            e.id_entrada,
                            e.estado,
                            te.precio_entrada,
                            te.tipo_entrada_desc,
                            p.titulo_pelicula,
                            cf.rela_tipos_de_pagos,
                            tp.descripcion_tipo_pago as forma_pago_desc
                         FROM entradas e
                         INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
                         INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
                         INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                         LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
                         LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
                         LEFT JOIN tipos_de_pagos tp ON cf.rela_tipos_de_pagos = tp.id_tipo_pago
                         WHERE e.id_entrada = '{$idEntradaEscaped}'
                         LIMIT 1";

            $resEntrada = $db->query($queryEntrada);

            if (!$resEntrada || $resEntrada->num_rows === 0) {
                throw new \Exception('Entrada no encontrada');
            }

            $entrada = $resEntrada->fetch_assoc();

            if ($entrada['estado'] != 1) {
                throw new \Exception('Solo se pueden devolver entradas activas');
            }

            $monto           = (float) $entrada['precio_entrada'];
            $tipoPago        = (int) $entrada['rela_tipos_de_pagos'];
            $formaPagoDesc   = $entrada['forma_pago_desc'] ?? 'Efectivo';
            $concepto        = "Devolución entrada #{$idEntrada} - {$entrada['titulo_pelicula']} ({$entrada['tipo_entrada_desc']})";

            // efectivo = 1, transferencia = 2
            $esEfectivo      = $tipoPago === 1;
            $estadoEgreso    = $esEfectivo ? 'pagado' : 'devolucion_pendiente';

            //Liberar las butacas de esta entrada
            $queryLiberar = "DELETE FROM butacas_vendidas WHERE id_entrada = ?";
            $stmtLiberar = $db->prepare($queryLiberar);
            $stmtLiberar->bind_param("i", $idEntrada);

            if (!$stmtLiberar->execute()) {
                throw new \Exception('Error al liberar las butacas: ' . $stmtLiberar->error);
            }

            // 2. Cancelar la entrada
            if (!$db->query("UPDATE entradas SET estado = -1 WHERE id_entrada = '{$idEntradaEscaped}'")) {
                throw new \Exception('Error al cancelar la entrada');
            }

            // 3. Registrar en egresos
            $obsEscaped      = $db->escape_string($observaciones ?: $concepto);
            $conceptoEscaped = $db->escape_string($concepto);
            $formaPagoEsc    = $db->escape_string($formaPagoDesc);
            $estadoEsc       = $db->escape_string($estadoEgreso);
            $idArqueo        = $arqueo->id_arqueo_caja;

            $queryEgreso = "INSERT INTO egresos 
                (rela_arqueo_caja, rela_usuario, concepto, monto, forma_pago,
                 fecha_egreso, estado_egreso, observaciones)
                VALUES 
                ('{$idArqueo}', '{$idUsuario}', '{$conceptoEscaped}', '{$monto}',
                 '{$formaPagoEsc}', NOW(), '{$estadoEsc}', '{$obsEscaped}')";

            if (!$db->query($queryEgreso)) {
                throw new \Exception('Error al registrar el egreso: ' . $db->error);
            }

            $idEgreso = $db->insert_id;

            // 4. Si pagó en efectivo, descontar de la caja
            if ($esEfectivo) {
                $conceptoMovEscaped = $db->escape_string($concepto);
                $queryMov = "INSERT INTO movimientos_caja 
                         (rela_arqueo_caja, tipo_movimiento, concepto_movimiento,
                          monto, fecha_movimiento, forma_pago, rela_egreso)
                         VALUES 
                         ('{$idArqueo}', 'egreso', '{$conceptoMovEscaped}',
                          '{$monto}', NOW(), 'Efectivo', '{$idEgreso}')";

                if (!$db->query($queryMov)) {
                    throw new \Exception('Error al registrar movimiento de caja');
                }
            }

            $db->commit();

            $mensajeFinal = $esEfectivo
                ? "✅ Devolución de \${$monto} procesada en efectivo"
                : "⏳ Devolución de \${$monto} registrada como pendiente — procesala por transferencia desde administración";

            echo json_encode([
                'ok'      => true,
                'mensaje' => $mensajeFinal,
                'monto'   => $monto,
                'metodo'  => $esEfectivo ? 'efectivo' : 'transferencia'
            ]);
        } catch (\Exception $e) {
            $db->rollback();
            echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public static function apiDatosEntradaDevolucion()
    {
        if (!self::verificarPermisoDevolucion()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idEntrada = $_GET['id'] ?? null;

        if (!$idEntrada) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID requerido']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();
        $idEscaped = $db->escape_string($idEntrada);

        $query = "SELECT 
                e.id_entrada,
                e.estado,
                te.precio_entrada,
                te.tipo_entrada_desc,
                p.titulo_pelicula,
                f.fecha_hora,
                cf.rela_tipos_de_pagos,
                tp.descripcion_tipo_pago as forma_pago_desc,
                cf.numero_comprobante
              FROM entradas e
              INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
              INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
              INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
              LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
              LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
              LEFT JOIN tipos_de_pagos tp ON cf.rela_tipos_de_pagos = tp.id_tipo_pago
              WHERE e.id_entrada = '{$idEscaped}'
              LIMIT 1";

        $res = $db->query($query);

        if (!$res || $res->num_rows === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Entrada no encontrada']);
            return;
        }

        $entrada = $res->fetch_assoc();

        echo json_encode([
            'ok'           => true,
            'id_entrada'   => $entrada['id_entrada'],
            'titulo'       => $entrada['titulo_pelicula'],
            'tipo'         => $entrada['tipo_entrada_desc'],
            'precio'       => (float) $entrada['precio_entrada'],
            'fecha_hora'   => $entrada['fecha_hora'],
            'forma_pago'   => $entrada['forma_pago_desc'] ?? 'Efectivo',
            'tipo_pago_id' => (int) $entrada['rela_tipos_de_pagos'],
            'comprobante'  => $entrada['numero_comprobante'] ?? 'N/A',
            'es_efectivo'  => (int) $entrada['rela_tipos_de_pagos'] === 1,
            'estado'       => (int) $entrada['estado']  // AGREGAR ESTO
        ]);
    }
}
