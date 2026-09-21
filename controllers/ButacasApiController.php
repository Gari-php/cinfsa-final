<?php

namespace Controllers\API;

use Models\ActiveRecord;

class ButacasAPIController {
    

    public static function obtenerPorFuncion() {
        header('Content-Type: application/json');
        
        $idFuncion = $_GET['id_funcion'] ?? null;
        
        if (!$idFuncion || !is_numeric($idFuncion)) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'ID de función inválido'
            ]);
            return;
        }
        
        $db = ActiveRecord::getDB();
        $queryFuncion = "SELECT 
                            f.id_funcion,
                            f.rela_salas,
                            s.capacidad_sala,
                            s.filas_sala,
                            s.columnas_sala
                        FROM funciones f
                        INNER JOIN salas s ON f.rela_salas = s.id_sala
                        WHERE f.id_funcion = ? AND f.estado = 1";
        
        $stmt = $db->prepare($queryFuncion);
        $stmt->bind_param("i", $idFuncion);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Función no encontrada'
            ]);
            return;
        }
        
        $funcion = $resultado->fetch_assoc();
        $idSala = $funcion['rela_salas'];

        $queryButacas = "SELECT 
                            b.id_butaca,
                            b.fila_butaca,
                            b.numero_butaca,
                            b.rela_estado_butaca,
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
                'estado' => (int)$row['estado'], // 
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
    }
    
    public static function verificarDisponibilidad() {
        header('Content-Type: application/json');
        
        $idButaca = $_GET['id_butaca'] ?? null;
        $idFuncion = $_GET['id_funcion'] ?? null;
        
        if (!$idButaca || !$idFuncion) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Parámetros incompletos'
            ]);
            return;
        }
        
        $db = ActiveRecord::getDB();
        
        $query = "SELECT 
                    b.id_butaca,
                    b.fila_butaca,
                    b.numero_butaca,
                    CASE 
                        WHEN bv.id_venta_butaca IS NOT NULL THEN 'vendida'
                        WHEN b.rela_estado_butaca = 2 THEN 'bloqueada'
                        ELSE 'disponible'
                    END as estado
                FROM butacas b
                LEFT JOIN butacas_vendidas bv ON bv.id_butaca = b.id_butaca 
                    AND bv.id_funcion = ?
                WHERE b.id_butaca = ?";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $idFuncion, $idButaca);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Butaca no encontrada'
            ]);
            return;
        }
        
        $butaca = $resultado->fetch_assoc();
        
        echo json_encode([
            'ok' => true,
            'butaca' => [
                'id' => (int)$butaca['id_butaca'],
                'fila' => (int)$butaca['fila_butaca'],
                'numero' => (int)$butaca['numero_butaca'],
                'estado' => $butaca['estado'],
                'disponible' => $butaca['estado'] === 'disponible'
            ]
        ]);
    }
}