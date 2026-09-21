<?php

namespace Controllers;

use Models\Usuario;
use Models\Funciones; // Agregar esta importación
use MVC\Router;
use Classes\Email;


class LoginController
{

    public static function login(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);

            if (!isset($datos['nombre_usuario']) || !isset($datos['clave_usuario'])) {
                header('Location: /');
                exit;
            }

            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);

            header('Content-Type: application/json');

            $usuario_input = $datos['nombre_usuario'] ?? '';
            $clave_input = $datos['clave_usuario'] ?? '';

            if (!$usuario_input || !$clave_input) {
                echo json_encode(['error' => 'Todos los campos son obligatorios']);
                return;
            }

            $usuario = Usuario::buscarEmailoNombre($usuario_input);

            if (!$usuario) {
                echo json_encode(['error' => 'Usuario o Gmail no encontrado']);
                return;
            }

            if (!password_verify($clave_input, $usuario->clave_usuario)) {
                echo json_encode(['error' => 'La contraseña es incorrecta']);
                return;
            }

            if (!$usuario->verificado) {
                echo json_encode(['error' => 'Por favor, confirmá tu cuenta desde el correo para iniciar sesión']);
                return;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }


            $_SESSION['id'] = $usuario->id_usuario;
            $_SESSION['id_usuario'] = $usuario->id_usuario;
            $_SESSION['nombre_usuario'] = $usuario->nombre_usuario;
            $_SESSION['email'] = $usuario->email;
            $_SESSION['perfil'] = (int)$usuario->rela_perfil;
            $_SESSION['login'] = true;

            // Redirigir según tipo de usuario (ACTUALIZADO)
            $redirigir = match ($_SESSION['perfil']) {
                1 => '/menu',                    // Cliente
                2 => '/vendedor',                // Vendedor general
                3 => '/administrador',           // Administrador
                4 => '/vendedor/caja',      // Vendedor de funciones
                5 => '/vendedorproductos',              // Vendedor de productos
                default => '/'
            };

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Inicio de sesión exitoso',
                'redirigir' => $redirigir
            ]);
            return;
        }

        $router->render('auth/login');
    }

    public static function olvide(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);

            $email = trim($datos['email'] ?? '');

            // Validaciones básicas
            if (!$email) {
                echo json_encode(['error' => 'El campo email es obligatorio']);
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
                echo json_encode(['error' => 'Debes ingresar un Gmail válido']);
                return;
            }

            // Buscar usuario por email
            $usuario = Usuario::where('email', $email);

            if (!$usuario) {
                echo json_encode(['error' => 'Este correo no está registrado']);
                return;
            }

            if (!$usuario->verificado) {
                echo json_encode(['error' => 'Este correo aún no fue verificado. Revisa tu bandeja.']);
                return;
            }

            // Generar token único y guardarlo
            $token = bin2hex(random_bytes(20));
            $usuario->token_recuperacion = $token;

            $guardado = $usuario->guardar();
            if (!$guardado) {
                echo json_encode(['error' => 'No se pudo guardar el token de recuperación']);
                return;
            }

            // ✅ Enviar email de recuperación
            $emailSender = new \Classes\Email(
                $usuario->email,
                $usuario->nombre_usuario ?? '',
                '' // El token de verificación no se usa acá
            );

            $enviado = $emailSender->enviarRecuperacion($token);

            if ($enviado) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Te enviamos un correo con instrucciones para restablecer tu contraseña'
                ]);
            } else {
                echo json_encode([
                    'error' => 'No se pudo enviar el correo de recuperación'
                ]);
            }

            return;
        }

        // SOLO si es GET
        $router->render('auth/olvide-password');
    }



    public static function confirmar(Router $router)
    {
        $token = $_GET['token'] ?? ''; // asegúrate que sea GET si el enlace lo pasa así

        if (!$token) {
            $router->render('auth/confirmar', [
                'mensaje' => 'Token inválido o ausente.',
                'tipo' => 'error'
            ]);
            return;
        }

        /** @var \Models\Usuario $usuario */
        $usuario = Usuario::where('token_verificacion', $token);

        if (!$usuario) {
            $router->render('auth/confirmar', [
                'mensaje' => 'El token no es válido.',
                'tipo' => 'error'
            ]);
            return;
        }

        $usuario->verificado = 1;
        $usuario->token_verificacion = null;
        $usuario->actualizar(); // o $usuario->guardar() si lo definís

        $router->render('auth/confirmar', [
            'mensaje' => 'Tu cuenta ha sido confirmada correctamente. Ahora podés iniciar sesión.',
            'tipo' => 'exito'
        ]);
    }

    public static function restablecer(Router $router)
    {
        // Si es POST, procesamos el cambio de contraseña
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);

            $token = $datos['token'] ?? '';
            $password = trim($datos['password'] ?? '');
            $confirm_password = trim($datos['confirm_password'] ?? '');

            // Validaciones básicas
            if (!$token || !$password || !$confirm_password) {
                echo json_encode(['error' => 'Todos los campos son obligatorios']);
                return;
            }

            if (strlen($password) < 6) {
                echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
                return;
            }

            if ($password !== $confirm_password) {
                echo json_encode(['error' => 'Las contraseñas no coinciden']);
                return;
            }

            // Buscar usuario por token
            $usuario = Usuario::where('token_recuperacion', $token);

            if (!$usuario) {
                echo json_encode(['error' => 'El token es inválido o ha expirado']);
                return;
            }

            // Actualizar la contraseña
            $usuario->clave_usuario = password_hash($password, PASSWORD_BCRYPT);
            $usuario->token_recuperacion = null;
            $usuario->guardar();

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Tu contraseña fue actualizada correctamente',
                'redirigir' => '/'
            ]);
            return;
        }

        // Si es GET, mostrar el formulario
        $token = $_GET['token'] ?? '';

        if (!$token) {
            $router->render('auth/restablecer', [
                'tokenValido' => false,
                'mensaje' => 'Token inválido o faltante.'
            ]);
            return;
        }

        $usuario = Usuario::where('token_recuperacion', $token);

        if (!$usuario) {
            $router->render('auth/restablecer-password', [
                'tokenValido' => false,
                'mensaje' => 'El token es inválido o ha expirado.'
            ]);
            return;
        }

        $router->render('auth/restablecer-password', [
            'tokenValido' => true,
            'token' => $token
        ]);
    }

    public static function logaut()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar todas las variables de sesión
        $_SESSION = array();

        // Si se desea destruir la sesión completamente
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finalmente, destruir la sesión
        session_destroy();

        // Redirigir al inicio
        header('Location: /');
        exit;
    }
}
