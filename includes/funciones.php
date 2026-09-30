<?php

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}

// Lee una variable de configuración (.env o entorno del proceso).
// $_ENV puede venir vacío según variables_order, por eso se miran las tres fuentes.
function env(string $clave, $defecto = null) {
    if (isset($_ENV[$clave])) return $_ENV[$clave];
    if (isset($_SERVER[$clave]) && is_string($_SERVER[$clave])) return $_SERVER[$clave];
    $valor = getenv($clave);
    return $valor !== false ? $valor : $defecto;
}

// Escapa / Sanitizar el HTML
function s($html) : string {
    $s = htmlspecialchars($html ?? '', ENT_QUOTES, 'UTF-8');
    return $s;
}

// Token CSRF ligado a la sesión, generado una vez y reutilizado mientras dure
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}