<?php

require 'funciones.php';
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// En producción no se muestran errores (revelan rutas y código): se guardan en logs/php_errors.log
if (env('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

    set_exception_handler(function (\Throwable $e) {
        error_log('Excepción no controlada: ' . $e);
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'Ocurrió un error inesperado. Intentá de nuevo en unos minutos.';
    });
}

require 'database.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Conectarnos a la base de datos
use Models\ActiveRecord;
ActiveRecord::setDB($db);
