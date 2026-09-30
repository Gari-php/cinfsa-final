<?php
/**
 * Simula que un cliente está pagando una butaca (reserva temporal de pago), sin pasar por MercadoPago.
 * Lo usa la tarea `reservarButaca` de Cypress. Solo opera sobre la base de prueba.
 *
 * Uso: php tests/reservar_butaca_prueba.php <id_butaca> <id_funcion> <id_usuario>
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/funciones.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$base = env('DB_NAME_TEST', 'cinfsa1_test');
if (!preg_match('/_test$/', $base)) {
    fwrite(STDERR, "La base de prueba debe terminar en '_test'.\n");
    exit(1);
}

[, $idButaca, $idFuncion, $idUsuario] = array_map('intval', $argv + [0, 0, 0, 0]);
if (!$idButaca || !$idFuncion || !$idUsuario) {
    fwrite(STDERR, "Uso: php tests/reservar_butaca_prueba.php <id_butaca> <id_funcion> <id_usuario>\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''), $base);
$db->set_charset('utf8mb4');
Models\ActiveRecord::setDB($db);

Models\Butaca::reservarParaPago([['id_butaca' => $idButaca, 'id_funcion' => $idFuncion]], $idUsuario);
echo "ok";
