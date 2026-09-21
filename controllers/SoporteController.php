<?php

namespace Controllers;

use MVC\Router;
use Classes\Email;

class SoporteController
{

    public static function formulario(Router $router)
    {
        $router->render('cliente/soporte/formulario', [
            'titulo' => 'Soporte y Reclamos'
        ]);
    }

    public static function enviar()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || !$_SESSION['login']) {
            echo json_encode(['ok' => false, 'mensaje' => 'Debes iniciar sesión para enviar un reclamo']);
            exit;
        }

        $asunto = $_POST['asunto'] ?? '';
        $mensaje = $_POST['mensaje'] ?? '';
        $numeroOrden = $_POST['numero_orden'] ?? null;

        $errores = [];

        if (empty($asunto)) {
            $errores[] = 'El tipo de reclamo es obligatorio';
        }

        if (empty($mensaje)) {
            $errores[] = 'Debes describir tu problema';
        } elseif (strlen($mensaje) < 20) {
            $errores[] = 'La descripción debe tener al menos 20 caracteres';
        }

        $comprobante = null;
        $urlComprobante = null;

        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['comprobante'];
            $tipoPermitido = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];

            if (!in_array($archivo['type'], $tipoPermitido)) {
                $errores[] = 'Solo se permiten imágenes (JPG, PNG, WEBP) o archivos PDF';
            }

            if ($archivo['size'] > 5 * 1024 * 1024) {
                $errores[] = 'El archivo no puede pesar más de 5MB';
            }

            if (empty($errores)) {
                // Para el email: se sigue mandando en base64, como ya tenías
                $contenido = file_get_contents($archivo['tmp_name']);
                $base64 = base64_encode($contenido);

                $comprobante = [
                    'content' => $base64,
                    'type' => $archivo['type'],
                    'filename' => $archivo['name']
                ];

                // NUEVO: guardar una copia física para poder verla desde las notificaciones
                $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . '/uploads/reclamos/';
                if (!is_dir($directorioDestino)) {
                    mkdir($directorioDestino, 0755, true);
                }

                $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $nombreArchivo = 'reclamo_' . time() . '_' . uniqid() . '.' . $extension;
                $rutaDestino = $directorioDestino . $nombreArchivo;

                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    $urlComprobante = '/uploads/reclamos/' . $nombreArchivo;
                }
            }
        }

        if (!empty($errores)) {
            echo json_encode(['ok' => false, 'errores' => $errores]);
            exit;
        }

        try {
            $nombreCliente = $_SESSION['nombre_usuario'] ?? ($_SESSION['nombre'] ?? 'Cliente');
            $emailCliente = $_SESSION['email_usuario'] ?? ($_SESSION['email'] ?? 'sin-email@cinfsa.com');

            $resultado = false;
            try {
                $resultado = Email::enviarReclamo(
                    $asunto,
                    $mensaje,
                    $numeroOrden,
                    $comprobante,
                    $nombreCliente,
                    $emailCliente
                );
            } catch (\Exception $e) {
                error_log('Error al enviar email de reclamo: ' . $e->getMessage());
            }

            $idUsuario = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;
            try {
                \Classes\Notificaciones::notificarNuevoReclamo(
                    $nombreCliente,
                    $asunto,
                    $mensaje,
                    $idUsuario,
                    $numeroOrden,
                    $urlComprobante
                );
            } catch (\Exception $e) {
                error_log('Error al notificar reclamo a administradores: ' . $e->getMessage());
            }

            if ($resultado) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => '¡Reclamo enviado correctamente! Te responderemos pronto por email.'
                ]);
            } else {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Tu reclamo fue recibido y será atendido a la brevedad.'
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}
