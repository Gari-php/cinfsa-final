<?php
/**
 * Atrasa la "última actividad" de una sesión para probar el vencimiento por inactividad
 * sin esperar. Lo usa la tarea `envejecerSesion` de Cypress.
 *
 *   php tests/envejecer_sesion_prueba.php <id_de_sesion> <minutos>
 *
 * Usa el mismo session.save_path que el servidor de pruebas (php.ini de XAMPP).
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$idSesion = $argv[1] ?? '';
$minutos = (int)($argv[2] ?? 0);

if (!preg_match('/^[A-Za-z0-9,-]{20,128}$/', $idSesion) || $minutos <= 0) {
    fwrite(STDERR, "Uso: php tests/envejecer_sesion_prueba.php <id_de_sesion> <minutos>\n");
    exit(1);
}

session_id($idSesion);
session_start();

if (empty($_SESSION['login'])) {
    fwrite(STDERR, "La sesión $idSesion no existe o no está iniciada\n");
    exit(1);
}

// Relativo a la última actividad guardada: así se puede ver si un pedido la renovó o no
$_SESSION['ultima_actividad'] = ($_SESSION['ultima_actividad'] ?? time()) - $minutos * 60;
session_write_close();

echo 'ok';
