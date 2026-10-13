<?php

namespace Controllers;

use Models\Usuario;
use Models\Funciones; // Agregar esta importación
use MVC\Router;
use Classes\Auditoria;
use Classes\Email;


class LoginController
{
    // Recuperación de contraseña: el link dura 30 minutos y entre un mail y el siguiente
    // a la misma cuenta tienen que pasar 5 (así nadie puede llenar una casilla de mails)
    const MINUTOS_TOKEN_RECUPERACION = 30;
    const MINUTOS_ENTRE_MAILS_RECUPERACION = 5;

    /**
     * Manda el mail de recuperación, salvo que a esa cuenta se le haya mandado uno hace
     * menos de MINUTOS_ENTRE_MAILS_RECUPERACION. Si el link anterior sigue vigente se reenvía
     * el mismo (con el plazo renovado): un pedido nuevo no anula el link que ya llegó.
     */
    private static function enviarRecuperacion(Usuario $usuario): void
    {
        $ahora = time();
        $vence = $usuario->token_recuperacion_expira ? strtotime($usuario->token_recuperacion_expira) : 0;
        $vigente = !empty($usuario->token_recuperacion) && $vence > $ahora;
        $enviadoHace = $ahora - ($vence - self::MINUTOS_TOKEN_RECUPERACION * 60);

        if ($vigente && $enviadoHace < self::MINUTOS_ENTRE_MAILS_RECUPERACION * 60) {
            return;
        }

        if (!$vigente) {
            $usuario->token_recuperacion = bin2hex(random_bytes(20));
        }
        $usuario->token_recuperacion_expira = date('Y-m-d H:i:s', $ahora + self::MINUTOS_TOKEN_RECUPERACION * 60);

        if (!$usuario->guardar()) {
            error_log("olvide(): no se pudo guardar el token de recuperación para {$usuario->email}");
            return;
        }

        $emailSender = new \Classes\Email($usuario->email, $usuario->nombre_usuario ?? '', '');
        if (!$emailSender->enviarRecuperacion($usuario->token_recuperacion)) {
            error_log("olvide(): no se pudo enviar el email de recuperación a {$usuario->email}");
        }
    }

    public static function login(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);

            if (!isset($datos['nombre_usuario']) || !isset($datos['clave_usuario'])) {
                header('Location: /');
                exit;
            }

            header('Content-Type: application/json');

            $usuario_input = $datos['nombre_usuario'] ?? '';
            $clave_input = $datos['clave_usuario'] ?? '';

            if (!$usuario_input || !$clave_input) {
                echo json_encode(['error' => 'Todos los campos son obligatorios']);
                return;
            }

            $usuario = Usuario::buscarEmailoNombre($usuario_input);

            // Mensaje genérico en ambos casos: no revelar si el usuario existe o no
            if (!$usuario || !password_verify($clave_input, $usuario->clave_usuario)) {
                // Nunca se guarda lo que se escribió (podría ser una contraseña tipeada en el campo usuario)
                Auditoria::registrar(
                    'sesion.login_fallido',
                    $usuario ? "Contraseña incorrecta para la cuenta {$usuario->nombre_usuario}" : 'Intento con un usuario inexistente',
                    'usuarios',
                    $usuario->id_usuario ?? null,
                    null,
                    null,
                    $usuario
                        ? ['id' => $usuario->id_usuario, 'nombre' => $usuario->nombre_usuario, 'perfil' => $usuario->rela_perfil]
                        : ['id' => null, 'nombre' => null, 'perfil' => null]
                );
                echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
                return;
            }

            if (!$usuario->verificado) {
                echo json_encode(['error' => 'Por favor, confirmá tu cuenta desde el correo para iniciar sesión']);
                return;
            }

            // Cuenta dada de baja por el administrador (se avisa solo con la contraseña correcta)
            if ($usuario->estado !== null && (int)$usuario->estado === 0) {
                echo json_encode(['error' => 'Tu cuenta está desactivada. Consultá con el administrador.']);
                return;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Emitir un ID de sesión nuevo tras autenticar, para que un ID
            // fijado antes del login (session fixation) deje de servir
            session_regenerate_id(true);

            $_SESSION['id'] = $usuario->id_usuario;
            $_SESSION['id_usuario'] = $usuario->id_usuario;
            $_SESSION['nombre_usuario'] = $usuario->nombre_usuario;
            $_SESSION['email'] = $usuario->email;
            $_SESSION['perfil'] = (int)$usuario->rela_perfil;
            $_SESSION['login'] = true;
            $_SESSION['huella_clave'] = huellaClave($usuario->clave_usuario);
            $_SESSION['ultima_actividad'] = time();

            Auditoria::registrar('sesion.login', "Inició sesión", 'usuarios', $usuario->id_usuario);

            // Redirigir según tipo de usuario (ACTUALIZADO)
            $redirigir = match ($_SESSION['perfil']) {
                1 => '/menu',                    // Cliente
                2 => '/vendedor',                // Vendedor general
                3 => '/administrador',           // Administrador
                4 => '/vendedor/caja',      // Vendedor de funciones
                5 => '/vendedorproductos',              // Vendedor de productos
                // Perfiles nuevos (como CONTROL_ACCESO): su id depende de la base, se resuelven por módulo
                default => match (true) {
                    \Middlewares\ValidarModulo::tiene('CONTROL_ENTRADAS') => '/control/entradas',
                    \Middlewares\ValidarModulo::tiene('ENTREGA_PRODUCTOS') => '/control/retiros',
                    default => '/',
                }
            };

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Inicio de sesión exitoso',
                'redirigir' => $redirigir
            ]);
            return;
        }

        // Aviso de una sesión que cerró el sistema (inactividad, baja o cambio de contraseña)
        $aviso = $_SESSION['aviso_sesion'] ?? null;
        unset($_SESSION['aviso_sesion']);

        $router->render('auth/login', ['aviso' => $aviso]);
    }

    public static function olvide(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);

            $email = strtolower(trim($datos['email'] ?? ''));

            // Validaciones básicas (los mismos proveedores que se aceptan al registrarse)
            if (!$email) {
                echo json_encode(['error' => 'El campo email es obligatorio']);
                return;
            }

            if (!emailValido($email)) {
                echo json_encode(['error' => 'El formato del email no es válido']);
                return;
            }

            if (!in_array(substr(strrchr($email, '@'), 1), Usuario::DOMINIOS_PERMITIDOS, true)) {
                echo json_encode(['error' => 'Solo se permiten correos de: ' . implode(', ', Usuario::DOMINIOS_PERMITIDOS)]);
                return;
            }

            // Buscar usuario por email. La respuesta es siempre el mismo mensaje
            // genérico, exista o no la cuenta (y aunque no se reenvíe por la espera),
            // para no revelar qué emails están registrados en el sistema.
            $usuario = Usuario::where('email', $email);

            if ($usuario && $usuario->verificado && (int)($usuario->estado ?? 1) !== 0) {
                self::enviarRecuperacion($usuario);
            }

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Si el correo está registrado y verificado, vas a recibir instrucciones para restablecer tu contraseña. '
                    . 'Si no te llega, revisá la carpeta de spam y esperá unos minutos antes de pedirlo de nuevo.'
            ]);
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

            // Una cuenta dada de baja no puede ponerse contraseña nueva
            if (!$usuario || (int)($usuario->estado ?? 1) === 0) {
                echo json_encode(['error' => 'El token es inválido o ha expirado']);
                return;
            }

            if (!$usuario->token_recuperacion_expira || strtotime($usuario->token_recuperacion_expira) < time()) {
                $usuario->token_recuperacion = null;
                $usuario->token_recuperacion_expira = null;
                $usuario->guardar();
                echo json_encode(['error' => 'El token es inválido o ha expirado']);
                return;
            }

            // Actualizar la contraseña
            $usuario->clave_usuario = password_hash($password, PASSWORD_BCRYPT);
            $usuario->token_recuperacion = null;
            $usuario->token_recuperacion_expira = null;
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
            $router->render('auth/restablecer-password', [
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

        // Antes de limpiar la sesión, para saber quién la cerró
        if (!empty($_SESSION['login'])) {
            Auditoria::registrar('sesion.logout', 'Cerró sesión', 'usuarios', $_SESSION['id_usuario'] ?? null);
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
