<?php

namespace Controllers;

use MVC\Router;
use Middlewares\ValidarModulo;

class VentasConsultaController
{

    private static function verificarVendedor()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login'])) {
            return false;
        }

        return ValidarModulo::tiene('CONSULTA_VENTAS_FUNCIONES');
    }

    public static function index(Router $router)
    {
        if (!self::verificarVendedor()) {
            header('Location: /');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();
        $cajas = $db->query("SELECT * FROM cajas WHERE activo = 1")->fetch_all(MYSQLI_ASSOC);

        $router->render('vendedor/ventas/consulta', [
            'cajas' => $cajas
        ]);
    }

    public static function apiObtenerVendedores()
    {
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

            $query = "SELECT DISTINCT u.id_usuario, u.nombre_usuario
                      FROM usuarios u
                      WHERE u.rela_perfil = 4
                        AND u.estado = 1
                      ORDER BY u.nombre_usuario";

            $result = $db->query($query);

            if (!$result) {
                throw new \Exception('Error en la consulta: ' . $db->error);
            }

            $vendedores = [];
            while ($row = $result->fetch_assoc()) {
                $vendedores[] = [
                    'id_usuario' => (int)$row['id_usuario'],
                    'nombre_usuario' => $row['nombre_usuario']
                ];
            }

            echo json_encode([
                'ok' => true,
                'vendedores' => $vendedores
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }


    public static function apiConsultarVentas()
    {
        // Forzar inicio de sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Encabezado JSON
        header('Content-Type: application/json');

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

            $db = \Models\ActiveRecord::getDB();

            // Consulta principal
            $query = "SELECT 
    cf.id_pagos,
    cf.numero_comprobante,
    cf.pago_fecha_hora,
    cf.monto_total,
    u.nombre_usuario AS vendedor,
    ca.nombre_caja,
    ca.numero_caja,
    ac.id_arqueo_caja,
    f.id_funcion,
    p.titulo_pelicula,
    TIMESTAMP(f.fecha_hora, t.turno_horario) AS fecha_funcion,
    s.id_sala,
    te.tipo_entrada_desc,
    te.precio_entrada,
    tp.descripcion_tipo_pago,
    COUNT(DISTINCT e.id_entrada) AS cantidad_entradas,
    SUM(CASE WHEN e.estado = 1 THEN 1 ELSE 0 END) AS entradas_activas,
    GROUP_CONCAT(
        CONCAT('F', b.fila_butaca, '-C', b.numero_butaca)
        ORDER BY b.fila_butaca, b.numero_butaca
        SEPARATOR ', '
    ) AS butacas,
    GROUP_CONCAT(e.id_entrada SEPARATOR ',') as ids_entradas
FROM cabecera_fact_cine cf
                INNER JOIN arqueo_cajas ac ON ac.id_arqueo_caja = cf.rela_arqueo_caja
                INNER JOIN cajas ca ON ca.id_caja = ac.rela_caja
                INNER JOIN usuarios u ON u.id_usuario = cf.rela_usuario_vendedor
                INNER JOIN detalle_fact_cine df ON df.rela_cabecera_fact = cf.id_pagos
                INNER JOIN entradas e ON e.id_entrada = df.rela_entrada
                LEFT JOIN butacas_vendidas bv ON bv.id_entrada = e.id_entrada
                LEFT JOIN butacas b ON b.id_butaca = bv.id_butaca
                INNER JOIN funciones f ON f.id_funcion = e.rela_funcion
                INNER JOIN turnos t ON t.id_turnos = f.rela_turnos
                INNER JOIN peliculas p ON p.id_pelicula = f.rela_peliculas
                INNER JOIN salas s ON s.id_sala = f.rela_salas
                INNER JOIN tipo_entradas te ON te.id_tipo_entrada = e.rela_tipo_entrada
                INNER JOIN tipos_de_pagos tp ON tp.id_tipo_pago = cf.rela_tipos_de_pagos
                WHERE DATE(cf.pago_fecha_hora) BETWEEN ? AND ?";

            $params = [$fechaDesde, $fechaHasta];
            $types = "ss";

            // Filtro por caja
            if ($idCaja) {
                $query .= " AND ca.id_caja = ?";
                $params[] = (int)$idCaja;
                $types .= "i";
            }

            // Filtro por vendedor
            if ($idVendedor) {
                $query .= " AND u.id_usuario = ?";
                $params[] = (int)$idVendedor;
                $types .= "i";
            }

            // Agrupación ajustada para ONLY_FULL_GROUP_BY
            $query .= " 
            GROUP BY 
                cf.id_pagos,
                cf.numero_comprobante,
                cf.pago_fecha_hora,
                cf.monto_total,
                u.nombre_usuario,
                ca.nombre_caja,
                ca.numero_caja,
                ac.id_arqueo_caja,
                f.id_funcion,
                p.titulo_pelicula,
                f.fecha_hora,
                t.turno_horario,
                s.id_sala,
                te.tipo_entrada_desc,
                te.precio_entrada,
                tp.descripcion_tipo_pago
            ORDER BY cf.pago_fecha_hora DESC";

            $stmt = $db->prepare($query);

            if (!$stmt) {
                throw new \Exception('Error al preparar consulta: ' . $db->error);
            }

            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new \Exception('Error al ejecutar consulta: ' . $stmt->error);
            }

            $result = $stmt->get_result();

            $ventas = [];
            while ($row = $result->fetch_assoc()) {
                $ventas[] = [
                    'id_pagos' => (int)$row['id_pagos'],
                    'numero_comprobante' => $row['numero_comprobante'],
                    'fecha_hora' => date('d/m/Y H:i', strtotime($row['pago_fecha_hora'])),
                    'vendedor' => $row['vendedor'],
                    'caja' => $row['nombre_caja'],
                    'numero_caja' => $row['numero_caja'],
                    'pelicula' => $row['titulo_pelicula'],
                    'sala' => $row['id_sala'],
                    'hora_funcion' => date('H:i', strtotime($row['fecha_funcion'])),
                    'tipo_entrada' => $row['tipo_entrada_desc'],
                    'precio_unitario' => (float)$row['precio_entrada'],
                    'cantidad' => (int)$row['cantidad_entradas'],
                    'butacas' => $row['butacas'],
                    'forma_pago' => $row['descripcion_tipo_pago'],
                    'monto_total' => (float)$row['monto_total'],
                    'ids_entradas' => $row['ids_entradas'],
                    'entradas_activas' => (int) $row['entradas_activas']
                ];
            }

            // Calcular totales
            $totalVentas = count($ventas);
            $totalEntradas = array_sum(array_column($ventas, 'cantidad'));
            $montoTotal = array_sum(array_column($ventas, 'monto_total'));

            echo json_encode([
                'ok' => true,
                'ventas' => $ventas,
                'resumen' => [
                    'total_ventas' => $totalVentas,
                    'total_entradas' => $totalEntradas,
                    'monto_total' => $montoTotal
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
}
