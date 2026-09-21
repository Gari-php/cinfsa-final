<?php

namespace Controllers;

use Models\ArqueoCaja;
use Models\MovimientoCaja;
use MVC\Router;
use Middlewares\ValidarModulo;

class CajaController {

  
    private static function verificarVendedor() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['login'])) {
            return false;
        }

        return ValidarModulo::tiene('GESTION_CAJA');
    }

  
    public static function index(Router $router) {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if ($arqueo) {
            header('Location: /vendedor/caja/estado');
        } else {
            header('Location: /vendedor/caja/abrir');
        }
        exit;
    }

    
    public static function estado(Router $router) {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if (!$arqueo) {
            header('Location: /vendedor/caja/abrir');
            exit;
        }

        // Obtener movimientos
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

        $router->render('vendedor/caja/estado', [
            'arqueo' => $arqueo,
            'movimientos' => $movimientos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'saldo_final' => $saldoFinal
        ]);
    }

    public static function vistaAbrir(Router $router) {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id'];
        $idPerfil = $_SESSION['perfil'];
        
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if ($arqueo) {
            header('Location: /vendedor/caja/estado');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();
        
        if ($idPerfil == 4) {
            $query = "SELECT c.*,
                        ac.id_arqueo_caja,
                        ac.rela_usuario as usuario_usando,
                        u.nombre_usuario as usuario_usando_nombre
                        FROM cajas c
                        LEFT JOIN arqueo_cajas ac ON c.id_caja = ac.rela_caja 
                            AND ac.estado_arqueo = 'abierto'
                        LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                        WHERE c.activo = 1 AND c.numero_caja IN (1, 2)
                        ORDER BY c.numero_caja";
        } elseif ($idPerfil == 5) {
            $query = "SELECT c.*,
                        ac.id_arqueo_caja,
                        ac.rela_usuario as usuario_usando,
                        u.nombre_usuario as usuario_usando_nombre
                        FROM cajas c
                        LEFT JOIN arqueo_cajas ac ON c.id_caja = ac.rela_caja 
                            AND ac.estado_arqueo = 'abierto'
                        LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                        WHERE c.activo = 1 AND c.numero_caja IN (3, 4)
                        ORDER BY c.numero_caja";
        } else {
            $query = "SELECT c.*,
                        ac.id_arqueo_caja,
                        ac.rela_usuario as usuario_usando,
                        u.nombre_usuario as usuario_usando_nombre
                        FROM cajas c
                        LEFT JOIN arqueo_cajas ac ON c.id_caja = ac.rela_caja 
                            AND ac.estado_arqueo = 'abierto'
                        LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                        WHERE c.activo = 1
                        ORDER BY c.numero_caja";
        }
        
        $result = $db->query($query);
        $cajas = $result->fetch_all(MYSQLI_ASSOC);

        $router->render('vendedor/caja/abrir', [
            'cajas' => $cajas
        ]);
    }

    
    public static function abrirCaja() {
        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $idUsuario = $_SESSION['id'];
            $idPerfil = $_SESSION['perfil'];
            $relaCaja = $datos['rela_caja'] ?? 1;

            $db = \Models\ActiveRecord::getDB();
            
            $queryCaja = "SELECT numero_caja FROM cajas WHERE id_caja = ?";
            $stmt = $db->prepare($queryCaja);
            $stmt->bind_param("i", $relaCaja);
            $stmt->execute();
            $resultCaja = $stmt->get_result();
            $caja = $resultCaja->fetch_assoc();
            $numeroCaja = $caja['numero_caja'] ?? 0;
            
            if ($idPerfil == 4 && !in_array($numeroCaja, [1, 2])) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No tienes permisos para abrir esta caja'
                ]);
                return;
            }
            
            if ($idPerfil == 5 && !in_array($numeroCaja, [3, 4])) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No tienes permisos para abrir esta caja'
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

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Caja abierta correctamente',
                    'redirigir' => '/vendedor/caja/estado'
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

   public static function cerrarCaja() {
        if (!self::verificarVendedor()) {
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
                $stmtCerrar->bind_param("dddsi", 
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

    public static function apiVerificarEstado() {
        if (!self::verificarVendedor()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idUsuario = $_SESSION['id'];
        $arqueo = ArqueoCaja::obtenerArqueoAbiertoUsuario($idUsuario);

        if ($arqueo) {
            echo json_encode([
                'ok' => true,
                'tiene_caja_abierta' => true,
                'arqueo' => [
                    'id_arqueo_caja' => $arqueo->id_arqueo_caja,
                    'fecha_inicio' => $arqueo->fecha_inicio,
                    'monto_inicial' => $arqueo->monto_inicial,
                    'nombre_caja' => $arqueo->nombre_caja ?? 'Caja 1',
                    'numero_caja' => $arqueo->numero_caja ?? 1
                ]
            ]);
        } else {
            echo json_encode([
                'ok' => true,
                'tiene_caja_abierta' => false,
                'mensaje' => 'No tienes caja abierta'
            ]);
        }
    }

    
    public static function apiResumenCierre() {
        if (!self::verificarVendedor()) {
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

    public static function generarPDFArqueo() {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        $idArqueo = $_GET['id'] ?? null;

        if (!$idArqueo || !is_numeric($idArqueo)) {
            header('Location: /vendedor/caja');
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
            header('Location: /vendedor/caja');
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
}