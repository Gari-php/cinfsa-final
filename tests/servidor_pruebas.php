<?php
/**
 * Router para el servidor embebido de PHP apuntando SIEMPRE a la base de prueba.
 *
 *   php -S localhost:3000 -t public tests/servidor_pruebas.php
 *
 * (o `npm run test:servidor`). Nunca usa la base de DB_NAME del .env.
 */

$baseDePrueba = getenv('DB_NAME_TEST') ?: 'cinfsa1_test';

if (!preg_match('/_test$/', $baseDePrueba)) {
    http_response_code(500);
    exit("La base de prueba debe terminar en '_test'.");
}

// $_ENV tiene prioridad en env() y Dotenv (inmutable) no lo pisa al cargar el .env
$_ENV['DB_NAME'] = $baseDePrueba;
putenv("DB_NAME=$baseDePrueba");

// Archivos estáticos de /public: los sirve el propio servidor
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($ruta !== '/' && is_file(__DIR__ . '/../public' . $ruta)) {
    return false;
}

chdir(__DIR__ . '/../public');
require __DIR__ . '/../public/index.php';
