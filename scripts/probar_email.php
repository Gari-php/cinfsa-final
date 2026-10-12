<?php
/**
 * Prueba el envío de emails con la configuración del .env: manda un mail de prueba
 * por el mismo camino que usa el sistema (Classes\Email::enviar) y muestra el resultado.
 *
 *   php scripts/probar_email.php [destinatario]
 *
 * MAIL_DRIVER=smtp (Brevo u otro SMTP) o sendgrid. Si no se indica destinatario, se lo manda
 * al propio MAIL_FROM. Nunca muestra contraseñas ni claves.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/app.php';

$proveedor = strtolower(env('MAIL_DRIVER', 'smtp'));
$remitente = env('MAIL_FROM', '');
$destino = $argv[1] ?? $remitente;
$secreto = fn($v) => $v === '' ? 'FALTA' : 'cargada (' . strlen($v) . ' caracteres)';

echo "Proveedor:  $proveedor" . PHP_EOL;
if ($proveedor === 'sendgrid') {
    echo "API key:    " . $secreto(env('SENDGRID_API_KEY', '')) . PHP_EOL;
} else {
    echo "Servidor:   " . env('SMTP_HOST', 'smtp-relay.brevo.com') . ':' . env('SMTP_PORT', 587) . PHP_EOL;
    echo "Usuario:    " . (env('SMTP_USER', '') ?: 'FALTA') . PHP_EOL;
    echo "Clave SMTP: " . $secreto(env('SMTP_PASSWORD', '')) . PHP_EOL;
}
echo "Remitente:  " . ($remitente === '' ? 'FALTA (MAIL_FROM)' : env('MAIL_FROM_NAME', 'CINFSA Cinema') . " - $remitente") . PHP_EOL;
echo "Destino:    " . ($destino ?: '(ninguno)') . PHP_EOL . PHP_EOL;

if ($remitente === '' || !$destino) {
    echo "Completá MAIL_FROM en el .env y volvé a probar." . PHP_EOL;
    exit(1);
}

// Los errores del envío van al log de PHP; acá se muestran en pantalla
ini_set('log_errors', '1');
ini_set('error_log', '');

$ok = \Classes\Email::enviar([
    'para' => [$destino, ''],
    'asunto' => 'Prueba de envío - CINFSA',
    'html' => '<p>Si recibís este mail, el envío de emails de <strong>CINFSA</strong> está bien configurado.</p><p>' . date('d/m/Y H:i') . '</p>',
]);

echo PHP_EOL . ($ok
    ? "OK: el proveedor aceptó el mail. Revisá la bandeja de $destino (y spam)."
    : "No se envió: el motivo está arriba. Revisá usuario, clave SMTP y que el remitente esté verificado en el proveedor.") . PHP_EOL;
exit($ok ? 0 : 1);
