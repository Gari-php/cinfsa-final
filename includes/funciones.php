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

// Fecha larga en español de Argentina, ej. "Domingo 4 de octubre de 2026".
// No depende de la extensión intl ni del locale del sistema (date() siempre da los nombres en inglés).
function fechaLarga(?int $timestamp = null): string {
    $timestamp ??= time();
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    $dia = $dias[(int)date('w', $timestamp)];
    return mb_strtoupper(mb_substr($dia, 0, 1)) . mb_substr($dia, 1) . ' '
        . date('j', $timestamp) . ' de ' . $meses[(int)date('n', $timestamp) - 1] . ' de ' . date('Y', $timestamp);
}

// Mes abreviado en español, en mayúsculas, ej. "AGO" (date('M') da "Aug")
function mesCorto(?int $timestamp = null): string {
    $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    return $meses[(int)date('n', $timestamp ?? time()) - 1];
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