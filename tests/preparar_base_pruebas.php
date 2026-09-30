<?php
/**
 * Crea (o recrea desde cero) la base de prueba a partir de:
 *   - cinfsa1_schema.sql       estructura
 *   - tests/db/datos_prueba.sql catálogos + usuarios/funciones de prueba
 *
 * Uso: php tests/preparar_base_pruebas.php
 * La base destino es DB_NAME_TEST (por defecto cinfsa1_test) y DEBE terminar en "_test":
 * este script borra la base completa antes de recrearla.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/funciones.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$baseDestino = env('DB_NAME_TEST', 'cinfsa1_test');
$baseReal = env('DB_NAME', 'cinfsa1');

function abortar(string $mensaje): void
{
    fwrite(STDERR, "ERROR: $mensaje\n");
    exit(1);
}

if (!preg_match('/^[A-Za-z0-9_]+_test$/', $baseDestino) || $baseDestino === $baseReal) {
    abortar("La base de prueba debe terminar en '_test' y ser distinta de DB_NAME (recibido: '$baseDestino'). No se tocó nada.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''));
} catch (mysqli_sql_exception $e) {
    abortar('No se pudo conectar a MySQL: ' . $e->getMessage());
}
$db->set_charset('utf8mb4');

// Esquema: se reescriben CREATE DATABASE / USE para que apunten SIEMPRE a la base de prueba
$schema = file_get_contents(__DIR__ . '/../cinfsa1_schema.sql');
$schema = preg_replace('/^(CREATE DATABASE |-- Current Database: ).*$/m', '', $schema);
$schema = preg_replace('/^USE `[^`]+`;$/m', "USE `$baseDestino`;", $schema);

$datos = file_get_contents(__DIR__ . '/db/datos_prueba.sql');

foreach (['cinfsa1_schema.sql' => $schema, 'datos_prueba.sql' => $datos] as $nombre => $sql) {
    // Cualquier referencia a otra base (`base`.`tabla` o USE `otra`) aborta antes de ejecutar nada
    if (preg_match_all('/USE `([^`]+)`/', $sql, $m) && array_diff($m[1], [$baseDestino])) {
        abortar("$nombre contiene USE de otra base: " . implode(', ', array_diff($m[1], [$baseDestino])));
    }
    if (str_contains($sql, "`$baseReal`")) {
        abortar("$nombre todavía menciona la base real `$baseReal`. No se ejecutó nada.");
    }
}

function ejecutarScript(mysqli $db, string $sql, string $nombre): void
{
    try {
        $db->multi_query($sql);
        do {
            if ($resultado = $db->store_result()) {
                $resultado->free();
            }
        } while ($db->more_results() && $db->next_result());
    } catch (mysqli_sql_exception $e) {
        abortar("Falló $nombre: " . $e->getMessage());
    }
}

$db->query("DROP DATABASE IF EXISTS `$baseDestino`");
$db->query("CREATE DATABASE `$baseDestino` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$db->select_db($baseDestino);

ejecutarScript($db, $schema, 'cinfsa1_schema.sql');
ejecutarScript($db, $datos, 'datos_prueba.sql');

$tablas = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$baseDestino'")->fetch_row()[0];
$usuarios = $db->query("SELECT COUNT(*) FROM `$baseDestino`.usuarios")->fetch_row()[0];

echo "Base '$baseDestino' lista: $tablas tablas, $usuarios usuarios de prueba.\n";
