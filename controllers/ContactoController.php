<?php

namespace Controllers;

use Classes\Email;
use Classes\Notificaciones;

class ContactoController
{
    public static function enviar()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $input = $_POST;
        if (empty($input)) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        $nombre  = trim($input['nombre'] ?? '');
        $email   = trim($input['email'] ?? '');
        $asunto  = trim($input['asunto'] ?? '');
        $mensaje = trim($input['mensaje'] ?? '');

        $errores = [];

        if (empty($nombre)) {
            $errores[] = 'El nombre es obligatorio';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Debes ingresar un email válido';
        }

        if (empty($asunto)) {
            $errores[] = 'Debes seleccionar un asunto';
        }

        if (empty($mensaje)) {
            $errores[] = 'El mensaje no puede estar vacío';
        } elseif (strlen($mensaje) < 10) {
            $errores[] = 'El mensaje debe tener al menos 10 caracteres';
        }

        if (!empty($errores)) {
            echo json_encode(['ok' => false, 'errores' => $errores]);
            exit;
        }

        // Intentar enviar el email; si falla, NO bloquea la notificación al admin
        $emailEnviado = false;
        try {
            $emailEnviado = Email::enviarContacto($nombre, $email, $asunto, $mensaje);
        } catch (\Exception $e) {
            error_log('Error al enviar email de contacto: ' . $e->getMessage());
        }

        // La notificación interna (campanita del admin) SIEMPRE se intenta
        try {
            Notificaciones::notificarNuevoContacto($nombre, $email, $asunto, $mensaje);
        } catch (\Exception $e) {
            error_log('Error al notificar contacto a administradores: ' . $e->getMessage());
        }

        echo json_encode([
            'ok' => true,
            'mensaje' => '¡Gracias por tu mensaje! Te responderemos a la brevedad.'
        ]);
        exit;
    }
}
