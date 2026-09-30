<?php

// Desde PHP 8.1 mysqli lanza mysqli_sql_exception si no puede conectar
try {
    $db = mysqli_connect(
        env('DB_HOST', 'localhost'),
        env('DB_USER', 'root'),
        env('DB_PASSWORD', ''),
        env('DB_NAME', 'cinfsa1')
    );
} catch (\mysqli_sql_exception $e) {
    $db = false;
}

if (!$db) {
    error_log('Error de conexión a MySQL (' . mysqli_connect_errno() . '): ' . mysqli_connect_error());
    http_response_code(500);
    if (env('APP_ENV') === 'production') {
        echo "El sitio no está disponible en este momento. Intentá más tarde.";
    } else {
        echo "Error: No se pudo conectar a MySQL.";
        echo "errno de depuración: " . mysqli_connect_errno();
        echo "error de depuración: " . mysqli_connect_error();
    }
    exit;
}
