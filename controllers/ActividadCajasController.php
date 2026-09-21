<?php

namespace Controllers;

use Models\ArqueoCaja;
use MVC\Router;
use Middlewares\ValidarModulo;

class ActividadCajasController {

    private static function verificarAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['login'])) {
            return false;
        }

        // Verificar que sea administrador (perfil 1)
        return $_SESSION['perfil'] == 3;
    }

   
    public static function monitor(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        // Obtener cajas activas
        $queryCajas = "SELECT * FROM cajas WHERE activo = 1 ORDER BY numero_caja";
        $cajas = $db->query($queryCajas)->fetch_all(MYSQLI_ASSOC);

        // Obtener vendedores (perfil 4)
        $queryVendedores = "SELECT id_usuario, nombre_usuario 
                           FROM usuarios 
                           WHERE rela_perfil = 4 AND estado = 1
                           ORDER BY nombre_usuario";
        $vendedores = $db->query($queryVendedores)->fetch_all(MYSQLI_ASSOC);

        // Obtener cajas abiertas actualmente
        $queryCajasAbiertas = "SELECT 
                                ac.id_arqueo_caja,
                                u.nombre_usuario as vendedor,
                                c.nombre_caja,
                                c.numero_caja,
                                DATE_FORMAT(ac.fecha_inicio, '%d/%m/%Y %H:%i') as apertura,
                                TIMESTAMPDIFF(HOUR, ac.fecha_inicio, NOW()) as horas_abierta,
                                TIMESTAMPDIFF(MINUTE, ac.fecha_inicio, NOW()) as minutos_abierta,
                                ac.monto_inicial,
                                ac.total_ventas,
                                COALESCE(
                                    (SELECT SUM(monto) FROM movimientos_caja 
                                     WHERE rela_arqueo_caja = ac.id_arqueo_caja 
                                     AND tipo_movimiento = 'ingreso'
                                     AND concepto_movimiento != 'Apertura de caja - Monto inicial'), 0
                                ) as ingresos,
                                COALESCE(
                                    (SELECT SUM(monto) FROM movimientos_caja 
                                     WHERE rela_arqueo_caja = ac.id_arqueo_caja 
                                     AND tipo_movimiento = 'egreso'), 0
                                ) as egresos
                            FROM arqueo_cajas ac
                            INNER JOIN usuarios u ON u.id_usuario = ac.rela_usuario
                            INNER JOIN cajas c ON c.id_caja = ac.rela_caja
                            WHERE ac.estado_arqueo = 'abierto'
                            ORDER BY ac.fecha_inicio DESC";
        
        $cajasAbiertas = $db->query($queryCajasAbiertas)->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/cajas/monitor', [
            'cajas' => $cajas,
            'vendedores' => $vendedores,
            'cajas_abiertas' => $cajasAbiertas
        ]);
    }

    public static function consultarHistorial() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        try {
            $datos = json_decode(file_get_contents('php://input'), true);

            $fechaDesde = $datos['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days'));
            $fechaHasta = $datos['fecha_hasta'] ?? date('Y-m-d');
            $idCaja = $datos['id_caja'] ?? null;
            $idVendedor = $datos['id_vendedor'] ?? null;
            $estadoArqueo = $datos['estado_arqueo'] ?? null; // 'abierto', 'cerrado', o null (todos)

            $db = \Models\ActiveRecord::getDB();

            // Query base
            $query = "SELECT 
                        ac.id_arqueo_caja,
                        c.nombre_caja,
                        c.numero_caja,
                        u.nombre_usuario as vendedor,
                        
                        DATE_FORMAT(ac.fecha_inicio, '%d/%m/%Y') as fecha,
                        DATE_FORMAT(ac.fecha_inicio, '%H:%i') as hora_apertura,
                        DAYNAME(ac.fecha_inicio) as dia_semana,
                        
                        CASE 
                            WHEN ac.fecha_cierre IS NULL THEN 'ABIERTA'
                            ELSE DATE_FORMAT(ac.fecha_cierre, '%H:%i')
                        END as hora_cierre,
                        
                        CASE 
                            WHEN ac.fecha_cierre IS NULL THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, ac.fecha_inicio, NOW()), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, ac.fecha_inicio, NOW()), 60), 'm')
                            ELSE 
                                CONCAT(TIMESTAMPDIFF(HOUR, ac.fecha_inicio, ac.fecha_cierre), 'h ', 
                                       MOD(TIMESTAMPDIFF(MINUTE, ac.fecha_inicio, ac.fecha_cierre), 60), 'm')
                        END as duracion_turno,
                        
                        ac.monto_inicial,
                        ac.total_ventas,
                        COALESCE(ac.monto_final, 0) as monto_final,
                        ac.diferencia,
                        ac.estado_arqueo,
                        COALESCE(ac.observaciones_cierre, '') as observaciones
                    FROM arqueo_cajas ac
                    INNER JOIN usuarios u ON u.id_usuario = ac.rela_usuario
                    INNER JOIN cajas c ON c.id_caja = ac.rela_caja
                    WHERE DATE(ac.fecha_inicio) BETWEEN ? AND ?";

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

            // Filtro por estado
            if ($estadoArqueo) {
                $query .= " AND ac.estado_arqueo = ?";
                $params[] = $estadoArqueo;
                $types .= "s";
            }

            $query .= " ORDER BY ac.fecha_inicio DESC";

            $stmt = $db->prepare($query);
            
            if (!$stmt) {
                throw new \Exception('Error al preparar consulta: ' . $db->error);
            }

            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new \Exception('Error al ejecutar consulta: ' . $stmt->error);
            }

            $result = $stmt->get_result();

            $arqueos = [];
            while ($row = $result->fetch_assoc()) {
                // Formatear diferencia
                if ($row['diferencia'] === null) {
                    $row['diferencia_texto'] = '-';
                } elseif ($row['diferencia'] > 0) {
                    $row['diferencia_texto'] = '+$' . number_format($row['diferencia'], 0, ',', '.');
                    $row['diferencia_tipo'] = 'positivo';
                } elseif ($row['diferencia'] < 0) {
                    $row['diferencia_texto'] = '-$' . number_format(abs($row['diferencia']), 0, ',', '.');
                    $row['diferencia_tipo'] = 'negativo';
                } else {
                    $row['diferencia_texto'] = 'Cuadrado';
                    $row['diferencia_tipo'] = 'cuadrado';
                }

                $arqueos[] = $row;
            }

            // Calcular resumen
            $totalArqueos = count($arqueos);
            $arqueosCerrados = count(array_filter($arqueos, fn($a) => $a['estado_arqueo'] === 'cerrado'));
            $arqueosAbiertos = $totalArqueos - $arqueosCerrados;
            
            $montoTotalInicial = array_sum(array_column($arqueos, 'monto_inicial'));
            $montoTotalVentas = array_sum(array_column($arqueos, 'total_ventas'));
            $montoTotalFinal = array_sum(array_column($arqueos, 'monto_final'));

            echo json_encode([
                'ok' => true,
                'arqueos' => $arqueos,
                'resumen' => [
                    'total_arqueos' => $totalArqueos,
                    'arqueos_abiertos' => $arqueosAbiertos,
                    'arqueos_cerrados' => $arqueosCerrados,
                    'monto_total_inicial' => $montoTotalInicial,
                    'monto_total_ventas' => $montoTotalVentas,
                    'monto_total_final' => $montoTotalFinal
                ]
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }


    public static function verDetalle(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $idArqueo = $_GET['id'] ?? null;

        if (!$idArqueo || !is_numeric($idArqueo)) {
            header('Location: /administrador/actividadcajas/monitor');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        // Obtener info del arqueo
        $queryArqueo = "SELECT 
                            ac.*,
                            c.nombre_caja,
                            c.numero_caja,
                            u.nombre_usuario as vendedor
                        FROM arqueo_cajas ac
                        INNER JOIN usuarios u ON u.id_usuario = ac.rela_usuario
                        INNER JOIN cajas c ON c.id_caja = ac.rela_caja
                        WHERE ac.id_arqueo_caja = ?";
        
        $stmt = $db->prepare($queryArqueo);
        $stmt->bind_param("i", $idArqueo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header('Location: /administrador/actividadcajas/monitor');
            exit;
        }

        $arqueo = $result->fetch_assoc();

        // Obtener movimientos
        $queryMovimientos = "SELECT * FROM movimientos_caja 
                            WHERE rela_arqueo_caja = ?
                            ORDER BY fecha_movimiento DESC";
        
        $stmt2 = $db->prepare($queryMovimientos);
        $stmt2->bind_param("i", $idArqueo);
        $stmt2->execute();
        $movimientos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

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

        $router->render('administrador/cajas/detalle', [
            'arqueo' => $arqueo,
            'movimientos' => $movimientos,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos
        ]);
    }

    public static function forzarCierre() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $idArqueo = $datos['id_arqueo'] ?? null;
            $observaciones = trim($datos['observaciones'] ?? 'Cierre forzado por administrador');

            if (!$idArqueo) {
                throw new \Exception('ID de arqueo requerido');
            }

            $db = \Models\ActiveRecord::getDB();
            $db->begin_transaction();

            // Obtener info del arqueo
            $queryArqueo = "SELECT * FROM arqueo_cajas WHERE id_arqueo_caja = ? AND estado_arqueo = 'abierto'";
            $stmt = $db->prepare($queryArqueo);
            $stmt->bind_param("i", $idArqueo);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new \Exception('Arqueo no encontrado o ya está cerrado');
            }

            $arqueo = $result->fetch_assoc();

            // Calcular totales
            $queryTotales = "SELECT 
                                SUM(CASE 
                                    WHEN tipo_movimiento = 'ingreso' 
                                    AND concepto_movimiento != 'Apertura de caja - Monto inicial'
                                    THEN monto 
                                    ELSE 0 
                                END) as ingresos,
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

            $totalIngresos = $totales['ingresos'] ?? 0;
            $totalEgresos = $totales['egresos'] ?? 0;
            $montoFinal = $arqueo['monto_inicial'] + $totalIngresos - $totalEgresos;

            // Cerrar arqueo
            $queryCerrar = "UPDATE arqueo_cajas 
                           SET fecha_cierre = NOW(),
                               monto_final = ?,
                               total_ventas = ?,
                               diferencia = 0,
                               estado_arqueo = 'cerrado',
                               observaciones_cierre = ?
                           WHERE id_arqueo_caja = ?";
            
            $stmt3 = $db->prepare($queryCerrar);
            $stmt3->bind_param("ddsi", $montoFinal, $totalIngresos, $observaciones, $idArqueo);
            
            if (!$stmt3->execute()) {
                throw new \Exception('Error al cerrar arqueo');
            }

            $db->commit();

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Caja cerrada correctamente',
                'monto_final' => $montoFinal
            ]);

        } catch (\Exception $e) {
            $db->rollback();
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}