<?php
/**
 * Crea una función de prueba que empieza dentro de N minutos (negativo = ya empezó), en la sala 1
 * con la película de prueba (120 min). Lo usa la tarea `crearFuncion` de Cypress para probar la
 * ventana horaria del control de entradas. Solo opera sobre la base de prueba.
 *
 * Uso: php tests/funcion_prueba.php <minutos_desde_ahora>
 * Devuelve el id de la función creada.
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

if (!isset($argv[1]) || !is_numeric($argv[1])) {
    fwrite(STDERR, "Uso: php tests/funcion_prueba.php <minutos_desde_ahora>\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''), $base);
$db->set_charset('utf8mb4');

// La función guarda el día en fecha_hora y la hora en su turno
$inicio = time() + (int)$argv[1] * 60;
$dia = date('Y-m-d', $inicio);
$hora = date('H:i:00', $inicio);

$stmt = $db->prepare("INSERT INTO turnos (turno_horario, estado) VALUES (?, 1)");
$stmt->bind_param('s', $hora);
$stmt->execute();
$idTurno = $db->insert_id;

$stmt = $db->prepare("INSERT INTO funciones (fecha_hora, fecha_finalizacion, rela_salas, rela_peliculas, rela_turnos, rela_tipo_entrada, estado, rela_idioma)
    VALUES (?, ?, 1, 1, ?, 1, 1, 1)");
$stmt->bind_param('ssi', $dia, $dia, $idTurno);
$stmt->execute();

echo $db->insert_id;
