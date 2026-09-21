<?php
require_once __DIR__ . '/../includes/app.php';

use Models\Entrada;

$fecha = date('Y-m-d H:i:s');

try {
    $totalExpiradas = Entrada::expirarEntradasVencidas();

    if ($totalExpiradas !== false) {
        echo "[{$fecha}] OK: Se expiraron {$totalExpiradas} entrada(s) automáticamente." . PHP_EOL;
    } else {
        echo "[{$fecha}] ERROR: No se pudo ejecutar la expiración de entradas." . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo "[{$fecha}] EXCEPCIÓN: " . $e->getMessage() . PHP_EOL;
}