<?php

namespace Controllers;

use Models\ArqueoCaja;
use Models\MovimientoCaja;
use MVC\Router;
use Middlewares\ValidarModulo;

class MovimientosController {

    private static function verificarVendedor() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['login'])) {
            return false;
        }

        return ValidarModulo::tiene('MOVIMIENTOS_CAJA');
    }

    private static function verificarCajaAbierta() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        return $arqueo !== null;
    }

    /**
     * Vista principal de ingresos/egresos
     */
    public static function index(Router $router) {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        if (!self::verificarCajaAbierta()) {
            $_SESSION['error'] = 'Debes abrir tu caja antes de gestionar movimientos';
            header('Location: /vendedor/caja/abrir');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        // Obtener movimientos (excluyendo apertura)
        $db = \Models\ActiveRecord::getDB();
        $queryMovimientos = "SELECT * FROM movimientos_caja 
                            WHERE rela_arqueo_caja = ? 
                            AND concepto_movimiento != 'Apertura de caja - Monto inicial'
                            ORDER BY fecha_movimiento DESC";
        
        $stmt = $db->prepare($queryMovimientos);
        $stmt->bind_param("i", $arqueo->id_arqueo_caja);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $movimientos = [];
        while ($row = $result->fetch_object()) {
            $movimientos[] = $row;
        }

        // Calcular totales (sin apertura)
        $totalIngresos = 0;
        $totalEgresos = 0;
        
        foreach ($movimientos as $mov) {
            if ($mov->tipo_movimiento === 'ingreso') {
                $totalIngresos += $mov->monto;
            } else {
                $totalEgresos += $mov->monto;
            }
        }

        $router->render('vendedor/movimientos/gestion', [
            'arqueo' => $arqueo,
            'movimientos' => $movimientos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos
        ]);
    }

    /**
     * API - Registrar ingreso simple
     */
    public static function apiRegistrarIngreso() {
        if (!self::verificarVendedor()) {
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

            $concepto = trim($datos['concepto'] ?? '');
            $monto = $datos['monto'] ?? 0;
            $idTipoPago = $datos['id_tipo_pago'] ?? 1;

            if (!$concepto || $monto <= 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
                return;
            }

            try {
                $idUsuario = $_SESSION['id'];
                $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

                $db = \Models\ActiveRecord::getDB();

                // Obtener descripción de la forma de pago
                $queryFormaPago = "SELECT descripcion_tipo_pago FROM tipos_de_pagos WHERE id_tipo_pago = ?";
                $stmtFP = $db->prepare($queryFormaPago);
                $stmtFP->bind_param("i", $idTipoPago);
                $stmtFP->execute();
                $resultFP = $stmtFP->get_result();
                
                if ($resultFP->num_rows === 0) {
                    throw new \Exception('Forma de pago no válida');
                }
                
                $formaPago = $resultFP->fetch_assoc()['descripcion_tipo_pago'];

                $query = "INSERT INTO movimientos_caja 
                         (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, 
                          monto, fecha_movimiento, forma_pago)
                         VALUES (?, 'ingreso', ?, ?, NOW(), ?)";
                
                $stmt = $db->prepare($query);
                $stmt->bind_param("isds", 
                    $arqueo->id_arqueo_caja, 
                    $concepto, 
                    $monto, 
                    $formaPago
                );

                if (!$stmt->execute()) {
                    throw new \Exception('Error al registrar ingreso');
                }

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Ingreso registrado correctamente'
                ]);

            } catch (\Exception $e) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }

    /**
     * API - Registrar egreso CON proveedor y servicio
     */
    public static function apiRegistrarEgreso() {
        if (!self::verificarVendedor()) {
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

            $idProveedor = $datos['id_proveedor'] ?? null;
            $idServicio = $datos['id_servicio'] ?? null;
            $numeroComprobante = trim($datos['numero_comprobante'] ?? '');
            $concepto = trim($datos['concepto'] ?? '');
            $monto = $datos['monto'] ?? 0;
            $idTipoPago = $datos['id_tipo_pago'] ?? 1; // ID de la tabla tipos_de_pagos
            $observaciones = trim($datos['observaciones'] ?? '');

            // Validaciones
            if (!$idProveedor || $monto <= 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Proveedor y monto son obligatorios']);
                return;
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                $idUsuario = $_SESSION['id'];
                $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

                // Obtener descripción de la forma de pago
                $queryFormaPago = "SELECT descripcion_tipo_pago FROM tipos_de_pagos WHERE id_tipo_pago = ?";
                $stmtFP = $db->prepare($queryFormaPago);
                $stmtFP->bind_param("i", $idTipoPago);
                $stmtFP->execute();
                $resultFP = $stmtFP->get_result();
                
                if ($resultFP->num_rows === 0) {
                    throw new \Exception('Forma de pago no válida');
                }
                
                $formaPago = $resultFP->fetch_assoc()['descripcion_tipo_pago'];

                // 1. Registrar el EGRESO en la tabla egresos
                $queryEgreso = "INSERT INTO egresos 
                               (rela_arqueo_caja, rela_proveedor, rela_servicio, 
                                rela_usuario, numero_comprobante, concepto, monto, 
                                forma_pago, fecha_egreso, estado_egreso, observaciones)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'registrado', ?)";
                
                $stmtEgreso = $db->prepare($queryEgreso);
                $stmtEgreso->bind_param("iiisssdss",
                    $arqueo->id_arqueo_caja,
                    $idProveedor,
                    $idServicio,
                    $idUsuario,
                    $numeroComprobante,
                    $concepto,
                    $monto,
                    $formaPago,
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
                $stmtMov->bind_param("isdsi",
                    $arqueo->id_arqueo_caja,
                    $concepto,
                    $monto,
                    $formaPago,
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

    /**
     * API - Obtener proveedores activos (filtrados por tipo según el vendedor)
     */
    public static function apiObtenerProveedores() {
        // Forzar inicio de sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // IMPORTANTE: Header ANTES de cualquier output
        header('Content-Type: application/json');

        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $db = \Models\ActiveRecord::getDB();
            
            // Para vendedor de funciones: solo proveedores de películas
            $tipoFiltro = 'peliculas'; // Puedes hacer esto dinámico según el perfil
            
            $query = "SELECT id_proveedor, razon_social, nombre_comercial, tipo_proveedor
                     FROM proveedores 
                     WHERE activo = 1 AND tipo_proveedor = ?
                     ORDER BY razon_social";
            
            $stmt = $db->prepare($query);
            
            if (!$stmt) {
                throw new \Exception('Error preparando consulta: ' . $db->error);
            }
            
            $stmt->bind_param("s", $tipoFiltro);
            
            if (!$stmt->execute()) {
                throw new \Exception('Error ejecutando consulta: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();

            $proveedores = [];
            while ($row = $result->fetch_assoc()) {
                $proveedores[] = [
                    'id_proveedor' => (int)$row['id_proveedor'],
                    'razon_social' => $row['razon_social'],
                    'nombre_comercial' => $row['nombre_comercial'],
                    'tipo_proveedor' => $row['tipo_proveedor']
                ];
            }

            echo json_encode([
                'ok' => true,
                'proveedores' => $proveedores
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API - Obtener formas de pago desde tipos_de_pagos
     */
    public static function apiObtenerFormasPago() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $db = \Models\ActiveRecord::getDB();
            
            $query = "SELECT id_tipo_pago, descripcion_tipo_pago 
                     FROM tipos_de_pagos 
                     ORDER BY id_tipo_pago";
            
            $result = $db->query($query);

            if (!$result) {
                throw new \Exception('Error en consulta: ' . $db->error);
            }

            $formasPago = [];
            while ($row = $result->fetch_assoc()) {
                $formasPago[] = [
                    'id_tipo_pago' => (int)$row['id_tipo_pago'],
                    'descripcion' => $row['descripcion_tipo_pago']
                ];
            }

            echo json_encode([
                'ok' => true,
                'formas_pago' => $formasPago
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API - Obtener servicios de un proveedor
     */
    public static function apiObtenerServicios() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $idProveedor = $_GET['id_proveedor'] ?? null;

        if (!$idProveedor) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de proveedor requerido']);
            exit;
        }

        try {
            $db = \Models\ActiveRecord::getDB();
            
            $query = "SELECT 
                        id_servicio,
                        nombre_servicio,
                        descripcion,
                        categoria_servicio,
                        monto_base,
                        tiene_monto_variable,
                        frecuencia_pago
                     FROM servicios_proveedor
                     WHERE rela_proveedor = ? AND activo = 1
                     ORDER BY nombre_servicio";
            
            $stmt = $db->prepare($query);
            
            if (!$stmt) {
                throw new \Exception('Error preparando consulta: ' . $db->error);
            }
            
            $stmt->bind_param("i", $idProveedor);
            
            if (!$stmt->execute()) {
                throw new \Exception('Error ejecutando consulta: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();

            $servicios = [];
            while ($row = $result->fetch_assoc()) {
                $servicios[] = [
                    'id_servicio' => (int)$row['id_servicio'],
                    'nombre_servicio' => $row['nombre_servicio'],
                    'descripcion' => $row['descripcion'],
                    'categoria_servicio' => $row['categoria_servicio'],
                    'monto_base' => (float)$row['monto_base'],
                    'tiene_monto_variable' => (int)$row['tiene_monto_variable'],
                    'frecuencia_pago' => $row['frecuencia_pago']
                ];
            }

            echo json_encode([
                'ok' => true,
                'servicios' => $servicios
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API - Eliminar movimiento
     */
    public static function apiEliminarMovimiento() {
        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $idMovimiento = $datos['id_movimiento'] ?? null;

            if (!$idMovimiento) {
                echo json_encode(['ok' => false, 'mensaje' => 'ID de movimiento inválido']);
                return;
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            try {
                $idUsuario = $_SESSION['id'];
                $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

                // Verificar que el movimiento pertenece a esta caja
                $queryVerificar = "SELECT rela_egreso FROM movimientos_caja 
                                  WHERE id_movimiento_caja = ? AND rela_arqueo_caja = ?";
                $stmtV = $db->prepare($queryVerificar);
                $stmtV->bind_param("ii", $idMovimiento, $arqueo->id_arqueo_caja);
                $stmtV->execute();
                $resultV = $stmtV->get_result();

                if ($resultV->num_rows === 0) {
                    throw new \Exception('Movimiento no encontrado');
                }

                $movimiento = $resultV->fetch_assoc();
                $idEgreso = $movimiento['rela_egreso'];

                // Eliminar egreso si existe (CASCADE eliminará el movimiento)
                if ($idEgreso) {
                    $queryEliminarEgreso = "DELETE FROM egresos WHERE id_egreso = ?";
                    $stmtE = $db->prepare($queryEliminarEgreso);
                    $stmtE->bind_param("i", $idEgreso);
                    $stmtE->execute();
                } else {
                    // Eliminar movimiento directamente
                    $queryEliminar = "DELETE FROM movimientos_caja WHERE id_movimiento_caja = ?";
                    $stmtD = $db->prepare($queryEliminar);
                    $stmtD->bind_param("i", $idMovimiento);
                    $stmtD->execute();
                }

                $db->commit();

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Movimiento eliminado correctamente'
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
}