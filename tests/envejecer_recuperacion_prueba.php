<?php
/**
 * Atrasa N minutos el mail de recuperación de contraseña de un usuario (corre hacia atrás el
 * vencimiento de su token), para probar la espera entre mails sin esperar de verdad.
 * Lo usa la tarea `envejecerRecuperacion` de Cypress. Solo opera sobre la base de prueba.
 *
 * Uso: php tests/envejecer_recuperacion_prueba.php <id_usuario> <minutos>
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/funciones.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$base = env('DB_NAME_TEST', 'cinfsa1_test');
if (!preg_match('/_test$/', $base)) {
    fwrite(STDERR, "La base de prueba debe terminar en '_test'.\n");
    exit(1);
}

[, $idUsuario, $minutos] = array_map('intval', $argv + [0, 0, 0]);
if ($idUsuario <= 0 || $minutos <= 0) {
    fwrite(STDERR, "Uso: php tests/envejecer_recuperacion_prueba.php <id_usuario> <minutos>\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''), $base);

$stmt = $db->prepare("UPDATE usuarios SET token_recuperacion_expira = DATE_SUB(token_recuperacion_expira, INTERVAL ? MINUTE)
                      WHERE id_usuario = ? AND token_recuperacion_expira IS NOT NULL");
$stmt->bind_param('ii', $minutos, $idUsuario);
$stmt->execute();

echo $stmt->affected_rows;
