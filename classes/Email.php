<?php

namespace Classes;

use SendGrid;
use SendGrid\Mail\Mail;

class Email
{
    public $email;
    public $nombre_usuario;
    public $token_verificacion;

    public function __construct($email, $nombre, $token_verificacion)
    {
        $this->email = $email;
        $this->nombre_usuario = $nombre;
        $this->token_verificacion = $token_verificacion;
    }

    private static function obtenerApiKey()
    {
        return $_ENV['SENDGRID_API_KEY'] ?? getenv('SENDGRID_API_KEY') ?: '';
    }

    // URL pública del sitio para armar los enlaces de los emails (APP_URL en .env)
    private static function obtenerUrlBase()
    {
        $url = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'https://multiramose-connectional-hanna.ngrok-free.dev';
        return rtrim($url, '/');
    }

    public function enviarConfirmacion()
    {
        $email = new Mail();
        $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA Cinema");
        $email->setSubject("🎬 Verificá tu cuenta");
        $email->addTo($this->email, $this->nombre_usuario);

        $url = self::obtenerUrlBase() . "/confirmar-cuenta?token=" . $this->token_verificacion;

        $contenido = '
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"></head>
        <body style="margin: 0; padding: 20px; background-color: #f5f5f5; font-family: \'Courier New\', monospace;">
            <div style="max-width: 400px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                
                <div style="background: #000; padding: 20px; text-align: center;">
                    <div style="color: #ed850f; font-size: 28px; font-weight: bold; letter-spacing: 2px;">CINFSA</div>
                    <div style="color: #fff; font-size: 12px; margin-top: 5px;">CINEMA</div>
                </div>

                <div style="padding: 30px; background: #fff; color: #000;">
                    <div style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                        VERIFICACIÓN DE CUENTA
                    </div>

                    <p style="font-size: 14px; line-height: 1.6;">
                        Hola <strong>' . htmlspecialchars($this->nombre_usuario) . '</strong>,
                    </p>

                    <p style="font-size: 13px; line-height: 1.6;">
                        Gracias por registrarte en CINFSA Cinema. Para completar tu registro, necesitamos que verifiques tu cuenta.
                    </p>

                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $url . '" style="display: inline-block; background: #ed850f; color: #fff; padding: 15px 40px; text-decoration: none; font-weight: bold; font-size: 14px; border-radius: 0;">
                            VERIFICAR CUENTA
                        </a>
                    </div>

                    <div style="border-top: 1px dashed #000; padding-top: 15px; margin-top: 20px; font-size: 11px; color: #666;">
                        Si no te registraste en CINFSA Cinema, ignorá este correo.
                    </div>
                </div>

                <div style="background: #000; padding: 15px; text-align: center;">
                    <div style="color: #fff; font-size: 10px;">© ' . date('Y') . ' CINFSA Cinema</div>
                </div>
            </div>
        </body>
        </html>';

        $email->addContent("text/html", $contenido);
        $apiKey = self::obtenerApiKey();
        $sendgrid = new SendGrid($apiKey);

        try {
            $response = $sendgrid->send($email);
            return $response->statusCode() === 202;
        } catch (\Exception $e) {
            error_log("❌ Error: " . $e->getMessage());
            return false;
        }
    }

    public function enviarRecuperacion($token)
    {
        $email = new Mail();
        $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA Cinema");
        $email->setSubject(" Restablecer Contraseña");
        $email->addTo($this->email, $this->nombre_usuario);

        $url = self::obtenerUrlBase() . "/restablecer?token=" . $token;

        $contenido = '
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"></head>
        <body style="margin: 0; padding: 20px; background-color: #f5f5f5; font-family: \'Courier New\', monospace;">
            <div style="max-width: 400px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                
                <div style="background: #000; padding: 20px; text-align: center;">
                    <div style="color: #ed850f; font-size: 28px; font-weight: bold; letter-spacing: 2px;">CINFSA</div>
                    <div style="color: #fff; font-size: 12px; margin-top: 5px;">CINEMA</div>
                </div>

                <div style="padding: 30px; background: #fff; color: #000;">
                    <div style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                        RESTABLECER CONTRASEÑA
                    </div>

                    <p style="font-size: 14px; line-height: 1.6;">
                        Hola <strong>' . htmlspecialchars($this->nombre_usuario) . '</strong>,
                    </p>

                    <p style="font-size: 13px; line-height: 1.6;">
                        Recibimos una solicitud para restablecer tu contraseña. Si fuiste vos, hacé clic en el botón de abajo:
                    </p>

                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $url . '" style="display: inline-block; background: #ed850f; color: #fff; padding: 15px 40px; text-decoration: none; font-weight: bold; font-size: 14px; border-radius: 0;">
                            CAMBIAR CONTRASEÑA
                        </a>
                    </div>

                    <div style="border-top: 1px dashed #000; padding-top: 15px; margin-top: 20px; font-size: 11px; color: #666;">
                        Si no solicitaste este cambio, ignorá este correo. Tu contraseña permanecerá sin cambios.
                    </div>
                </div>

                <div style="background: #000; padding: 15px; text-align: center;">
                    <div style="color: #fff; font-size: 10px;">© ' . date('Y') . ' CINFSA Cinema</div>
                </div>
            </div>
        </body>
        </html>';

        $email->addContent("text/html", $contenido);
        $apiKey = self::obtenerApiKey();
        $sendgrid = new SendGrid($apiKey);

        try {
            $response = $sendgrid->send($email);
            return $response->statusCode() === 202;
        } catch (\Exception $e) {
            error_log(" Error: " . $e->getMessage());
            return false;
        }
    }
    public function enviarTicketCompra($numeroOrden, $items, $total, $paymentId)
    {
        $ticket = self::armarTicketCompra($this->nombre_usuario, $numeroOrden, $items, $total, $paymentId);

        $email = new Mail();
        $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA Cinema");
        $email->setSubject($ticket['asunto']);
        $email->addTo($this->email, $this->nombre_usuario);
        $email->addContent("text/html", $ticket['html']);

        // Los QR van adjuntos "inline" y el HTML los muestra con cid:...
        // (Gmail y otros bloquean las imágenes data: incrustadas en el HTML)
        foreach ($ticket['adjuntos'] as $cid => $png) {
            $email->addAttachment(base64_encode($png), 'image/png', $cid . '.png', 'inline', $cid);
        }

        $apiKey = self::obtenerApiKey();
        $sendgrid = new SendGrid($apiKey);

        try {
            $response = $sendgrid->send($email);
            error_log("✔️ Ticket enviado a: " . $this->email);
            return $response->statusCode() === 202;
        } catch (\Exception $e) {
            error_log("❌ Error al enviar ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Arma el mail del ticket de compra: una tarjeta por entrada con su QR, los productos y
     * fichas a retirar en la cantina, y el detalle de pago.
     *
     * Cada ítem de tipo 'butacas' puede traer 'entrada' (ver PagoController::datosEntradaParaMail):
     * ['pelicula', 'cuando', 'donde', 'codigo_acceso'], ['no_asignada' => true] o null.
     *
     * @return array ['asunto' => string, 'html' => string, 'adjuntos' => [cid => png binario]]
     */
    public static function armarTicketCompra($nombreCliente, $numeroOrden, $items, $total, $paymentId): array
    {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        $pesos = fn($v) => '$' . number_format((float)$v, 0, ',', '.');

        $entradas = array_values(array_filter($items, fn($i) => ($i['tipo_producto'] ?? '') === 'butacas'));
        $otros = array_values(array_filter($items, fn($i) => ($i['tipo_producto'] ?? '') !== 'butacas'));
        $adjuntos = [];

        // ── Una tarjeta por entrada, con su QR ──
        $entradasHTML = '';
        foreach ($entradas as $n => $item) {
            $entrada = $item['entrada'] ?? null;

            if (!empty($entrada['no_asignada'])) {
                $entradasHTML .= '
                <div style="border: 2px solid #c0392b; background: #fdecea; padding: 12px; margin-bottom: 12px; text-align: center; font-size: 12px; color: #c0392b;">
                    <strong>' . $h($item['nombre']) . '</strong><br>
                    Esta butaca no se pudo asignar: se vendió mientras se procesaba tu pago.<br>
                    Vamos a gestionar el reintegro de ese importe.
                </div>';
                continue;
            }

            if (empty($entrada['codigo_acceso'])) {
                $entradasHTML .= '
                <div style="border: 2px dashed #999; padding: 12px; margin-bottom: 12px; text-align: center; font-size: 12px; color: #333;">
                    <strong>' . $h($item['nombre']) . '</strong><br>
                    Presentá el número de orden en boletería para retirar esta entrada.
                </div>';
                continue;
            }

            $cid = 'qr-entrada-' . ($n + 1);
            $adjuntos[$cid] = CodigoQR::png(CodigoQR::textoEntrada($entrada['codigo_acceso']), 6);

            $entradasHTML .= '
                <div style="border: 2px dashed #ed850f; background: #fffaf3; padding: 14px 12px; margin-bottom: 12px; text-align: center;">
                    <div style="font-size: 15px; font-weight: bold; color: #000;">' . $h(mb_strtoupper($entrada['pelicula'], 'UTF-8')) . '</div>
                    <div style="font-size: 12px; font-weight: bold; color: #ed850f; margin-top: 4px;">' . $h($entrada['cuando']) . '</div>
                    <div style="font-size: 12px; color: #333; margin-top: 2px;">' . $h($entrada['donde']) . '</div>
                    <img src="cid:' . $cid . '" width="170" height="170" alt="QR de la entrada" style="display: block; margin: 10px auto 6px; width: 170px; height: 170px;">
                    <div style="font-size: 10px; color: #666;">Mostrá este QR en la entrada de la sala</div>
                    <div style="font-size: 11px; color: #333; margin-top: 4px; letter-spacing: 1px;">CÓDIGO: <strong>' . $h(\Models\Entrada::codigoCorto($entrada['codigo_acceso'])) . '</strong></div>
                </div>';
        }

        // ── Productos y fichas: se retiran en la cantina ──
        $otrosHTML = '';
        foreach ($otros as $item) {
            $otrosHTML .= '
                <tr>
                    <td style="padding: 6px 4px; border-bottom: 1px dashed #ddd; font-size: 13px; color: #333;">' . $h($item['nombre']) . '</td>
                    <td style="padding: 6px 4px; border-bottom: 1px dashed #ddd; font-size: 13px; text-align: right; font-weight: bold;">x' . (int)$item['cantidad'] . '</td>
                </tr>';
        }

        // ── Detalle de pago (todos los ítems) ──
        $detalleHTML = '';
        foreach ($items as $item) {
            $detalleHTML .= '
            <tr>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; font-size: 13px;">' . $h($item['nombre']) . '</td>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; text-align: center; font-size: 13px;">' . (int)$item['cantidad'] . '</td>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; text-align: right; font-size: 12px; color: #666;">' . $pesos($item['precio']) . '</td>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; text-align: right; font-size: 13px; color: #ed850f; font-weight: 600;">' . $pesos($item['subtotal']) . '</td>
            </tr>';
        }

        $titulo = fn($texto) => '
                    <div style="font-size: 14px; font-weight: bold; color: #000; margin: 22px 0 10px; padding-bottom: 6px; border-bottom: 2px solid #000;">' . $texto . '</div>';

        $importante = [];
        if ($entradas) {
            $importante[] = 'Cada entrada tiene su propio QR: si van por separado, cada uno muestra el suyo';
            $importante[] = 'También podés ver tus QR en "Mis compras" de la web';
        }
        if ($otros) {
            $importante[] = 'Productos y fichas: retiralos en la cantina con el número de orden';
        }
        $importante[] = 'Conservá este mail hasta usar todo lo comprado';

        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"></head>
        <body style="margin: 0; padding: 20px; background-color: #f5f5f5; font-family: \'Courier New\', monospace;">
            <div style="max-width: 420px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.1);">

                <div style="background: #000; padding: 20px; text-align: center; border-bottom: 2px dashed #ed850f;">
                    <div style="color: #ed850f; font-size: 28px; font-weight: bold; letter-spacing: 2px;">CINFSA</div>
                    <div style="color: #fff; font-size: 12px; margin-top: 5px;">CINEMA</div>
                </div>

                <div style="padding: 20px; background: #fff;">

                    <div style="text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #000;">
                        <div style="font-size: 18px; font-weight: bold; color: #000;">COMPROBANTE DE COMPRA</div>
                    </div>

                    <table style="width: 100%; margin-bottom: 15px; font-size: 12px; color: #000;">
                        <tr>
                            <td style="padding: 3px 0;">CLIENTE:</td>
                            <td style="text-align: right; font-weight: bold;">' . $h(mb_strtoupper($nombreCliente ?? '', 'UTF-8')) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;">FECHA:</td>
                            <td style="text-align: right; font-weight: bold;">' . date('d/m/Y H:i') . '</td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin: 20px 0; padding: 15px; background: #f9f9f9; border: 2px dashed #ed850f;">
                        <div style="font-size: 11px; color: #666; margin-bottom: 5px;">ORDEN Nº</div>
                        <div style="font-size: 20px; font-weight: bold; color: #ed850f; letter-spacing: 2px;">' . $h($numeroOrden) . '</div>
                        <div style="font-size: 10px; color: #666; margin-top: 5px;">ID PAGO: ' . $h($paymentId) . '</div>
                    </div>'

                    . ($entradas ? $titulo('TUS ENTRADAS') . $entradasHTML : '')

                    . ($otros ? $titulo('PRODUCTOS Y FICHAS') . '
                    <table style="width: 100%; border-collapse: collapse;">' . $otrosHTML . '</table>
                    <div style="font-size: 11px; color: #333; margin-top: 8px;">Retiralos en la cantina mostrando el número de orden.</div>' : '')

                    . $titulo('DETALLE DE PAGO') . '
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 6px 4px; text-align: left; font-size: 11px; color: #000;">PRODUCTO</th>
                                <th style="padding: 6px 4px; text-align: center; font-size: 11px; color: #000;">CANT</th>
                                <th style="padding: 6px 4px; text-align: right; font-size: 11px; color: #000;">P.UNIT</th>
                                <th style="padding: 6px 4px; text-align: right; font-size: 11px; color: #000;">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody style="color: #333;">' . $detalleHTML . '</tbody>
                    </table>

                    <table style="width: 100%; margin: 10px 0; border-top: 2px solid #000;">
                        <tr>
                            <td style="font-size: 18px; font-weight: bold; color: #000; padding: 10px 0;">TOTAL PAGADO:</td>
                            <td style="font-size: 24px; font-weight: bold; color: #ed850f; text-align: right; padding: 10px 0;">' . $pesos($total) . '</td>
                        </tr>
                    </table>

                    <div style="background: #f9f9f9; padding: 15px; margin: 20px 0 0; border-left: 3px solid #ed850f;">
                        <div style="font-size: 11px; color: #000; line-height: 1.6;">
                            <strong>IMPORTANTE:</strong><br>• ' . implode('<br>• ', array_map($h, $importante)) . '
                        </div>
                    </div>

                </div>

                <div style="background: #000; padding: 15px; text-align: center;">
                    <div style="color: #fff; font-size: 10px;">Gracias por tu compra</div>
                    <div style="color: #ed850f; font-size: 10px; margin-top: 5px;">www.cinfsa.com</div>
                </div>

            </div>
        </body>
        </html>';

        return [
            'asunto' => "🎬 Tu Ticket de Compra - " . $numeroOrden,
            'html' => $html,
            'adjuntos' => $adjuntos,
        ];
    }
    public static function enviarReclamo($asunto, $mensaje, $numeroOrden = null, $comprobante = null, $nombreCliente = null, $emailCliente = null)
    {
        // Obtener datos del usuario de la sesión si no se pasan como parámetros
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Usar valores de parámetros o de sesión, con fallbacks seguros
        $nombreCliente = $nombreCliente ?? ($_SESSION['nombre_usuario'] ?? ($_SESSION['nombre'] ?? 'Cliente'));
        $emailCliente = $emailCliente ?? ($_SESSION['email_usuario'] ?? ($_SESSION['email'] ?? 'sin-email@cinfsa.com'));

        // Sanitizar todos los valores para evitar XSS
        $nombreCliente = htmlspecialchars($nombreCliente ?? 'Cliente', ENT_QUOTES, 'UTF-8');
        $emailCliente = htmlspecialchars($emailCliente ?? 'sin-email@cinfsa.com', ENT_QUOTES, 'UTF-8');
        $asunto = htmlspecialchars($asunto ?? 'Reclamo sin asunto', ENT_QUOTES, 'UTF-8');
        $mensaje = htmlspecialchars($mensaje ?? '', ENT_QUOTES, 'UTF-8');
        $numeroOrden = $numeroOrden ? htmlspecialchars($numeroOrden, ENT_QUOTES, 'UTF-8') : null;

        $fecha = date('d/m/Y H:i');

        // Construir el contenido HTML del email
        $contenidoHTML = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #ed850f, #f7931e);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 30px;
            }
            .info-box {
                background: #f8f9fa;
                border-left: 4px solid #ed850f;
                padding: 15px;
                margin: 15px 0;
                border-radius: 4px;
            }
            .info-label {
                font-weight: bold;
                color: #e3dfd9ff;
                margin-bottom: 5px;
            }
            .info-value {
                color: #333;
            }
            .mensaje-box {
                background: #fff;
                border: 2px solid #e0e0e0;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
            }
            .footer {
                background: #2d2d2d;
                color: #a0aec0;
                padding: 20px;
                text-align: center;
                font-size: 14px;
            }
            .btn-responder {
                display: inline-block;
                background: #ed850f;
                color: white !important;
                text-decoration: none;
                padding: 12px 30px;
                border-radius: 25px;
                margin: 20px 0;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>🎬 Nuevo Reclamo - CINFSA</h1>
            </div>
            
            <div class='content'>
                <h2 style='color: #e60c0cff;'>Detalles del Reclamo</h2>
                
                <div class='info-box'>
                    <div class='info-label'> Cliente:</div>
                    <div class='info-value'>{$nombreCliente}</div>
                </div>
                
                <div class='info-box'>
                    <div class='info-label'> Email:</div>
                    <div class='info-value'>{$emailCliente}</div>
                </div>
                
                <div class='info-box'>
                    <div class='info-label'> Fecha:</div>
                    <div class='info-value'>{$fecha}</div>
                </div>
                
                <div class='info-box'>
                    <div class='info-label'> Tipo de Reclamo:</div>
                    <div class='info-value'>{$asunto}</div>
                </div>
                " . ($numeroOrden ? "
                <div class='info-box'>
                    <div class='info-label'> Número de Orden:</div>
                    <div class='info-value'>{$numeroOrden}</div>
                </div>
                " : "") . "
                
                <h3 style='color:  #ed850f; margin-top: 30px;'> Descripción del Problema:</h3>
                <div class='mensaje-box'>
                    " . nl2br($mensaje) . "
                </div>
                
                <center>
                    <a href='mailto:{$emailCliente}' class='btn-responder'>
                        ↩️ Responder al Cliente
                    </a>
                </center>
            </div>
            
            <div class='footer'>
                <p>Este es un reclamo recibido desde el sistema CINFSA</p>
                <p>Responde directamente al email del cliente para dar seguimiento</p>
            </div>
        </div>
    </body>
    </html>
    ";

        try {
            // Obtener la API Key de SendGrid (ajusta según tu configuración)
            $apiKey = self::obtenerApiKey();

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA - Soporte");
            $email->setSubject("🎬 Nuevo Reclamo: {$asunto}");
            $email->addTo("cinecinfsa@gmail.com", "Soporte CINFSA");
            $email->setReplyTo($emailCliente, $nombreCliente);
            $email->addContent("text/html", $contenidoHTML);

            // Adjuntar comprobante si existe
            if ($comprobante && isset($comprobante['content'])) {
                $attachment = new \SendGrid\Mail\Attachment();
                $attachment->setContent($comprobante['content']);
                $attachment->setType($comprobante['type']);
                $attachment->setFilename($comprobante['filename']);
                $attachment->setDisposition("attachment");
                $email->addAttachment($attachment);
            }

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);

            return $response->statusCode() >= 200 && $response->statusCode() < 300;
        } catch (\Exception $e) {
            error_log("Error enviando reclamo: " . $e->getMessage());
            return false;
        }
    }

    public function enviarNotificacionCambioPassword()
    {
        $email = new Mail();
        $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA Cinema");
        $email->setSubject("🔒 Tu contraseña fue actualizada");
        $email->addTo($this->email, $this->nombre_usuario);

        $fecha = date('d/m/Y H:i');

        $contenido = '
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"></head>
    <body style="margin: 0; padding: 20px; background-color: #f5f5f5; font-family: \'Courier New\', monospace;">
        <div style="max-width: 400px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
            <div style="background: #000; padding: 20px; text-align: center;">
                <div style="color: #ed850f; font-size: 28px; font-weight: bold; letter-spacing: 2px;">CINFSA</div>
                <div style="color: #fff; font-size: 12px; margin-top: 5px;">CINEMA</div>
            </div>
            <div style="padding: 30px; background: #fff; color: #000;">
                <div style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                    CONTRASEÑA ACTUALIZADA
                </div>
                <p style="font-size: 14px; line-height: 1.6;">
                    Hola <strong>' . htmlspecialchars($this->nombre_usuario) . '</strong>,
                </p>
                <p style="font-size: 13px; line-height: 1.6;">
                    Te confirmamos que la contraseña de tu cuenta fue cambiada el <strong>' . $fecha . '</strong>.
                </p>
                <div style="border-top: 1px dashed #000; padding-top: 15px; margin-top: 20px; font-size: 11px; color: #666;">
                    Si vos no realizaste este cambio, contactanos de inmediato respondiendo este correo.
                </div>
            </div>
            <div style="background: #000; padding: 15px; text-align: center;">
                <div style="color: #fff; font-size: 10px;">© ' . date('Y') . ' CINFSA Cinema</div>
            </div>
        </div>
    </body>
    </html>';

        $email->addContent("text/html", $contenido);
        $apiKey = self::obtenerApiKey();
        $sendgrid = new SendGrid($apiKey);

        try {
            $response = $sendgrid->send($email);
            return $response->statusCode() === 202;
        } catch (\Exception $e) {
            error_log("❌ Error al enviar notificación de cambio de contraseña: " . $e->getMessage());
            return false;
        }
    }

    public static function enviarContacto($nombre, $emailCliente, $asunto, $mensaje)
    {
        // Sanitizar para evitar XSS
        $nombre = htmlspecialchars($nombre ?? 'Sin nombre', ENT_QUOTES, 'UTF-8');
        $emailCliente = htmlspecialchars($emailCliente ?? 'sin-email@cinfsa.com', ENT_QUOTES, 'UTF-8');
        $asunto = htmlspecialchars($asunto ?? 'Sin asunto', ENT_QUOTES, 'UTF-8');
        $mensaje = htmlspecialchars($mensaje ?? '', ENT_QUOTES, 'UTF-8');

        $fecha = date('d/m/Y H:i');

        $mapaAsuntos = [
            'consulta'   => 'Consulta general',
            'reserva'    => 'Reservas',
            'reclamo'    => 'Reclamo',
            'sugerencia' => 'Sugerencia',
            'otro'       => 'Otro'
        ];
        $asuntoLegible = $mapaAsuntos[$asunto] ?? $asunto;

        $contenidoHTML = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
            .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #ed850f, #f7931e); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .info-box { background: #f8f9fa; border-left: 4px solid #ed850f; padding: 15px; margin: 15px 0; border-radius: 4px; }
            .info-label { font-weight: bold; color: #555; margin-bottom: 5px; }
            .info-value { color: #333; }
            .mensaje-box { background: #fff; border: 2px solid #e0e0e0; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .footer { background: #2d2d2d; color: #a0aec0; padding: 20px; text-align: center; font-size: 14px; }
            .btn-responder { display: inline-block; background: #ed850f; color: white !important; text-decoration: none; padding: 12px 30px; border-radius: 25px; margin: 20px 0; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>📩 Nuevo Mensaje de Contacto - CINFSA</h1>
            </div>
            <div class='content'>
                <h2 style='color: #ed850f;'>Detalles del Mensaje</h2>
                <div class='info-box'>
                    <div class='info-label'>Nombre:</div>
                    <div class='info-value'>{$nombre}</div>
                </div>
                <div class='info-box'>
                    <div class='info-label'>Email:</div>
                    <div class='info-value'>{$emailCliente}</div>
                </div>
                <div class='info-box'>
                    <div class='info-label'>Fecha:</div>
                    <div class='info-value'>{$fecha}</div>
                </div>
                <div class='info-box'>
                    <div class='info-label'>Asunto:</div>
                    <div class='info-value'>{$asuntoLegible}</div>
                </div>
                <h3 style='color: #ed850f; margin-top: 30px;'>Mensaje:</h3>
                <div class='mensaje-box'>
                    " . nl2br($mensaje) . "
                </div>
                <center>
                    <a href='mailto:{$emailCliente}' class='btn-responder'>↩️ Responder</a>
                </center>
            </div>
            <div class='footer'>
                <p>Este mensaje fue enviado desde el formulario de contacto de CINFSA</p>
            </div>
        </div>
    </body>
    </html>
    ";

        try {
            $apiKey = self::obtenerApiKey();

            $email = new Mail();
            $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA - Contacto");
            $email->setSubject("📩 Nuevo Contacto: {$asuntoLegible}");
            $email->addTo("cinecinfsa@gmail.com", "CINFSA");
            $email->setReplyTo($emailCliente, $nombre);
            $email->addContent("text/html", $contenidoHTML);

            $sendgrid = new SendGrid($apiKey);
            $response = $sendgrid->send($email);

            return $response->statusCode() >= 200 && $response->statusCode() < 300;
        } catch (\Exception $e) {
            error_log("Error enviando contacto: " . $e->getMessage());
            return false;
        }
    }
}
