<?php
require_once __DIR__ . '/../includes/app.php';

$db = \Models\ActiveRecord::getDB();

// Limpiar reservas temporales expiradas
$query = "DELETE FROM reservas_butacas 
          WHERE estado_reserva = 'temporal' 
          AND fecha_expiracion <= NOW()";

$resultado = $db->query($query);

if ($resultado) {
    $eliminadas = $db->affected_rows;
    echo "Se liberaron $eliminadas reservas expiradas\n";
} else {
    echo "Error al limpiar reservas\n";
}