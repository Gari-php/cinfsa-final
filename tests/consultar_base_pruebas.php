<?php
/**
 * Ejecuta un SELECT sobre la base de prueba y devuelve las filas en JSON.
 * Lo usa la tarea `consultarBase` de Cypress para verificar resultados.
 *
 * Uso: php tests/consultar_base_pruebas.php "SELECT ..."
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/funciones.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$base = env('DB_NAME_TEST', 'cinfsa1_test');
$sql = trim($argv[1] ?? '');

if (!preg_match('/_test$/', $base)) {
    fwrite(STDERR, "La base de prueba debe terminar en '_test'.\n");
    exit(1);
}
if (!preg_match('/^SELECT\s/i', $sql) || str_contains($sql, ';')) {
    fwrite(STDERR, "Solo se permite una única consulta SELECT.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''), $base);
$db->set_charset('utf8mb4');

echo json_encode($db->query($sql)->fetch_all(MYSQLI_ASSOC));
