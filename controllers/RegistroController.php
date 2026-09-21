<?php

namespace Controllers;

use Classes\Email;
use Models\Persona;
use Models\Usuario;
use MVC\Router;
use Classes\Notificaciones;

class RegistroController {

    public static function crear(Router $router) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos) {
                echo json_encode(['errores' => ['No se recibieron datos válidos']]);
                return;
            }

            // Crear instancia de Persona para validar
            $persona = new Persona([
                'nombre_persona' => $datos['nombre'] ?? '',
                'apellido_persona' => $datos['apellido'] ?? '',
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? '',
                'rela_sexo' => $datos['sexo'] ?? '',
                'estado' => 1
            ]);

            // Validar Persona
            $erroresPersona = $persona->validar();

            // Crear instancia de Usuario para validar
            $usuario = new Usuario([
                'nombre_usuario' => $datos['nombre_usuario'] ?? '',
                'clave_usuario' => $datos['password'] ?? '',
                'email' => $datos['email'] ?? '',
                'rela_perfil' => 1, // Perfil usuario normal
                'estado' => 1
            ]);

            // Validar Usuario
            $erroresUsuario = $usuario->validar();

            // Combinar errores
            $errores = array_merge($erroresPersona, $erroresUsuario);

            // Verificar si ya existe el usuario (email o nombre de usuario duplicado)
            if (empty($errores)) {
                $existeUsuario = $usuario->existeUsuario();
                if ($existeUsuario) {
                    $errores[] = $existeUsuario['mensaje'];
                }
            }

            // Si hay errores, retornarlos
            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            // Si no hay errores, proceder a guardar
            
            // 1. Guardar Persona
            $resPersona = $persona->crear();

            if (!$resPersona['resultado']) {
                echo json_encode(['errores' => ['Error al guardar los datos personales. Intente nuevamente.']]);
                return;
            }

            // 2. Asignar el ID de persona al usuario y guardar
            $usuario->id_persona = $resPersona['id'];
            $usuario->token_verificacion = bin2hex(random_bytes(32));
            $usuario->verificado = 0;

            $resultado = $usuario->crear();

            if ($resultado['resultado']) {
                // 3. Enviar notificación a administradores
                try {
                    Notificaciones::notificarNuevoUsuario(
                        $resultado['id'],
                        $datos['nombre_usuario']
                    );
                } catch (\Exception $e) {
                    error_log("❌ Error al enviar notificación: " . $e->getMessage());
                    // No interrumpir el flujo, solo logear el error
                }

                // 4. Enviar email de confirmación
                try {
                    $email = new Email($usuario->email, $usuario->nombre_usuario, $usuario->token_verificacion);
                    $emailEnviado = $email->enviarConfirmacion();
                    
                    $mensaje = 'Cuenta creada exitosamente. Revisa tu email para confirmar tu cuenta.';
                    if (!$emailEnviado) {
                        $mensaje = 'Cuenta creada exitosamente. ⚠️ Hubo un problema al enviar el email de confirmación, contacta al administrador.';
                    }
                    
                } catch (\Exception $e) {
                    error_log("❌ Error al enviar email: " . $e->getMessage());
                    $mensaje = 'Cuenta creada exitosamente. ⚠️ Hubo un problema al enviar el email de confirmación, contacta al administrador.';
                }

                echo json_encode([
                    'ok' => true,
                    'mensaje' => $mensaje,
                    'redirigir' => '/'
                ]);
                return;
            }

            // Si falló el guardado del usuario, limpiar la persona creada
            if ($resPersona['id']) {
                $persona->id_persona = $resPersona['id'];
                $persona->eliminar();
            }

            echo json_encode(['errores' => ['Error al crear la cuenta. Intente nuevamente.']]);
        }

        // Si es GET, mostrar formulario
        $router->render('auth/crear-cuenta');
    }

    
    private static function validarEdadMinima($fechaNacimiento, $edadMinima = 13) {
        $fecha = \DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$fecha) {
            return false;
        }

        $hoy = new \DateTime();
        $edad = $hoy->diff($fecha)->y;

        return $edad >= $edadMinima;
    }

    /**
     * Método auxiliar para limpiar y validar strings
     */
    private static function limpiarString($string, $longitudMinima = 2, $longitudMaxima = 50) {
        $string = trim($string);
        
        if (strlen($string) < $longitudMinima || strlen($string) > $longitudMaxima) {
            return false;
        }
        
        return $string;
    }

    /**
     * Método auxiliar para validar formato de email
     */
    private static function validarEmail($email) {
        $email = trim(strtolower($email));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Opcional: validar dominios específicos
        $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com'];
        $dominio = substr(strrchr($email, "@"), 1);
        
        return in_array($dominio, $dominiosPermitidos) ? $email : false;
    }

    /**
     * Método auxiliar para validar fortaleza de contraseña
     */
    private static function validarFortalezaPassword($password) {
        $errores = [];
        
        if (strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        if (!preg_match('/[a-zA-Z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un número';
        }
        
        // Lista de contraseñas comunes
        $passwordsComunes = [
            '123456', 'password', '123456789', '12345678', '12345',
            'qwerty', 'abc123', 'password123', 'admin123'
        ];
        
        if (in_array(strtolower($password), $passwordsComunes)) {
            $errores[] = 'La contraseña es muy común, elige una más segura';
        }
        
        return $errores;
    }
}