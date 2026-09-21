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

    public function enviarConfirmacion()
    {
        $email = new Mail();
        $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA Cinema");
        $email->setSubject("🎬 Verificá tu cuenta");
        $email->addTo($this->email, $this->nombre_usuario);

        $url = "https://multiramose-connectional-hanna.ngrok-free.dev/confirmar-cuenta?token=" . $this->token_verificacion;

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

        $url = "https://multiramose-connectional-hanna.ngrok-free.dev/restablecer?token=" . $token;

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
        $email = new Mail();
        $email->setFrom("no-reply@mail.cineavenida.online", "CINFSA Cinema");
        $email->setSubject("🎬 Tu Ticket de Compra - " . $numeroOrden);
        $email->addTo($this->email, $this->nombre_usuario);

        // Zona horaria Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');

        // Items con precio unitario y subtotal
        $itemsHTML = '';
        foreach ($items as $item) {
            $itemsHTML .= '
            <tr>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; font-size: 13px;">' . htmlspecialchars($item['nombre']) . '</td>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; text-align: center; font-size: 13px;">' . $item['cantidad'] . '</td>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; text-align: right; font-size: 12px; color: #666;">$' . number_format($item['precio'], 0, ',', '.') . '</td>
                <td style="padding: 8px 4px; border-bottom: 1px dashed #ddd; text-align: right; font-size: 13px; color: #ed850f; font-weight: 600;">$' . number_format($item['subtotal'], 0, ',', '.') . '</td>
            </tr>';
        }

        $contenido = '
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
                            <td style="text-align: right; font-weight: bold;">' . strtoupper(htmlspecialchars($this->nombre_usuario)) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;">FECHA:</td>
                            <td style="text-align: right; font-weight: bold;">' . date('d/m/Y H:i') . '</td>
                        </tr>
                    </table>

                    <div style="border-top: 1px dashed #000; margin: 15px 0;"></div>

                    <div style="text-align: center; margin: 20px 0; padding: 15px; background: #f9f9f9; border: 2px dashed #ed850f;">
                        <div style="font-size: 11px; color: #666; margin-bottom: 5px;">ORDEN Nº</div>
                        <div style="font-size: 20px; font-weight: bold; color: #ed850f; letter-spacing: 2px;">' . htmlspecialchars($numeroOrden) . '</div>
                        <div style="font-size: 10px; color: #666; margin-top: 5px;">ID PAGO: ' . htmlspecialchars($paymentId) . '</div>
                    </div>

                    <div style="border-top: 1px dashed #000; margin: 15px 0;"></div>

                    <div style="margin: 20px 0;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #000;">
                                    <th style="padding: 8px 4px; text-align: left; font-size: 11px; color: #000;">PRODUCTO</th>
                                    <th style="padding: 8px 4px; text-align: center; font-size: 11px; color: #000;">CANT</th>
                                    <th style="padding: 8px 4px; text-align: right; font-size: 11px; color: #000;">P.UNIT</th>
                                    <th style="padding: 8px 4px; text-align: right; font-size: 11px; color: #000;">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody style="color: #333;">
                                ' . $itemsHTML . '
                            </tbody>
                        </table>
                    </div>

                    <div style="border-top: 2px solid #000; margin: 15px 0;"></div>

                    <table style="width: 100%; margin: 10px 0;">
                        <tr>
                            <td style="font-size: 18px; font-weight: bold; color: #000; padding: 10px 0;">TOTAL A PAGAR:</td>
                            <td style="font-size: 24px; font-weight: bold; color: #ed850f; text-align: right; padding: 10px 0;">$' . number_format($total, 0, ',', '.') . '</td>
                        </tr>
                    </table>

                    <div style="border-top: 2px solid #000; margin: 15px 0;"></div>

                    <div style="background: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 3px solid #ed850f;">
                        <div style="font-size: 11px; color: #000; line-height: 1.6;">
                            <strong>IMPORTANTE:</strong><br>
                            • Presentá este ticket en boletería<br>
                            • Conservalo hasta recibir tus productos<br>
                            • Para entradas, mostrar número de orden
                        </div>
                    </div>

                    <div style="text-align: center; margin: 20px 0;">
                        <div style="display: inline-block; background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px); height: 60px; width: 200px;"></div>
                        <div style="font-size: 10px; color: #666; margin-top: 5px;">' . htmlspecialchars($numeroOrden) . '</div>
                    </div>

                </div>

                <div style="background: #000; padding: 15px; text-align: center;">
                    <div style="color: #fff; font-size: 10px;">Gracias por tu compra</div>
                    <div style="color: #ed850f; font-size: 10px; margin-top: 5px;">www.cinfsa.com</div>
                </div>

            </div>
        </body>
        </html>';

        $email->addContent("text/html", $contenido);

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
