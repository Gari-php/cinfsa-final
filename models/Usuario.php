<?php

namespace Models;

class Usuario extends ActiveRecord
{

    protected static $tabla = 'usuarios';

    protected static $columnasDB = [
        'id_usuario',
        'nombre_usuario',
        'clave_usuario',
        'email',
        'rela_perfil',
        'id_persona',
        'token_verificacion',
        'verificado',
        'token_recuperacion',
        'estado',
        'foto_perfil'
    ];

    public $id_usuario;
    public $nombre_usuario;
    public $clave_usuario;
    public $email;
    public $rela_perfil;
    public $id_persona;
    public $token_verificacion;
    public $verificado;
    public $token_recuperacion;
    public $estado;
    public $foto_perfil;

    public function __construct($args = [])
    {
        $this->id_usuario = $args['id_usuario'] ?? null;
        $this->nombre_usuario = $args['nombre_usuario'] ?? '';
        $this->clave_usuario = $args['clave_usuario'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->rela_perfil = $args['rela_perfil'] ?? null;
        $this->id_persona = $args['id_persona'] ?? null;
        $this->token_verificacion = $args['token_verificacion'] ?? '';
        $this->verificado = $args['verificado'] ?? null;
        $this->token_recuperacion = $args['token_recuperacion'] ?? '';
        $this->estado = $args['estado'] ?? 1;
        $this->foto_perfil = $args['foto_perfil'] ?? null;
    }

    public function validar()
    {
        $errores = [];

        // Validar nombre de usuario
        if (!$this->nombre_usuario || trim($this->nombre_usuario) === '') {
            $errores[] = 'El nombre de usuario es obligatorio';
        } else {
            $this->nombre_usuario = trim($this->nombre_usuario);

            if (strlen($this->nombre_usuario) < 3) {
                $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres';
            }

            if (strlen($this->nombre_usuario) > 20) {
                $errores[] = 'El nombre de usuario no puede tener más de 20 caracteres';
            }

            // Solo letras, números, guiones y guiones bajos
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $this->nombre_usuario)) {
                $errores[] = 'El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos';
            }

            // No puede empezar con número
            if (preg_match('/^[0-9]/', $this->nombre_usuario)) {
                $errores[] = 'El nombre de usuario no puede empezar con un número';
            }

            // Verificar palabras prohibidas
            $palabras_prohibidas = ['admin', 'administrator', 'root', 'system', 'null', 'undefined'];
            if (in_array(strtolower($this->nombre_usuario), $palabras_prohibidas)) {
                $errores[] = 'El nombre de usuario no está permitido';
            }
        }

        // Validar email
        if (!$this->email || trim($this->email) === '') {
            $errores[] = 'El email es obligatorio';
        } else {
            $this->email = trim(strtolower($this->email));

            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El formato del email no es válido';
            } else {
                // Validar longitud
                if (strlen($this->email) > 100) {
                    $errores[] = 'El email no puede tener más de 100 caracteres';
                }

                // Validar dominios permitidos (opcional, puedes comentar si no quieres restricción)
                $dominios_permitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com'];
                $dominio = substr(strrchr($this->email, "@"), 1);

                if (!in_array($dominio, $dominios_permitidos)) {
                    $errores[] = 'Solo se permiten correos de: ' . implode(', ', $dominios_permitidos);
                }
            }
        }

        // Validar contraseña (solo cuando se está creando o cambiando)
        if (!empty($this->clave_usuario)) {
            if (strlen($this->clave_usuario) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            }

            if (strlen($this->clave_usuario) > 100) {
                $errores[] = 'La contraseña no puede tener más de 100 caracteres';
            }

            // Validar que tenga al menos una letra y un número
            if (!preg_match('/[a-zA-Z]/', $this->clave_usuario)) {
                $errores[] = 'La contraseña debe contener al menos una letra';
            }

            if (!preg_match('/[0-9]/', $this->clave_usuario)) {
                $errores[] = 'La contraseña debe contener al menos un número';
            }

            // Validar contraseñas débiles comunes
            $contraseñas_debiles = [
                '123456',
                'password',
                '123456789',
                '12345678',
                '12345',
                '1234567',
                'admin123',
                'qwerty',
                'abc123',
                'password123'
            ];

            if (in_array(strtolower($this->clave_usuario), $contraseñas_debiles)) {
                $errores[] = 'La contraseña es muy común, elige una más segura';
            }
        }

        // Validar perfil
        if ($this->rela_perfil === null || trim($this->rela_perfil) === '') {
            $errores[] = 'Debe seleccionar un perfil';
        } else {
            if (!is_numeric($this->rela_perfil)) {
                $errores[] = 'El perfil seleccionado no es válido';
            } else {
                $perfil_id = intval($this->rela_perfil);

                // Validar que el perfil exista
                $db = self::getDB();
                $resultado = $db->query("SELECT id_perfiles FROM perfiles WHERE id_perfiles = $perfil_id");

                if (!$resultado || $resultado->num_rows === 0) {
                    $errores[] = 'El perfil seleccionado no existe';
                }
            }
        }

        // Validar persona asociada (para actualizaciones)
        if ($this->id_persona !== null) {
            if (!is_numeric($this->id_persona)) {
                $errores[] = 'El ID de persona no es válido';
            } else {
                $persona_id = intval($this->id_persona);

                $db = self::getDB();
                $resultado = $db->query("SELECT id_persona FROM personas WHERE id_persona = $persona_id");

                if (!$resultado || $resultado->num_rows === 0) {
                    $errores[] = 'La persona asociada no existe';
                }
            }
        }

        return $errores;
    }

    public function existeUsuario()
    {
        $email = self::$db->escape_string($this->email);
        $nombre_usuario = self::$db->escape_string($this->nombre_usuario);

        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE email = '{$email}' 
                  OR nombre_usuario = '{$nombre_usuario}' 
                  LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            $datos = $resultado->fetch_assoc();

            // Retornar información específica sobre qué campo está duplicado
            if ($datos['email'] === $this->email) {
                return ['campo' => 'email', 'mensaje' => 'El email ya está registrado'];
            }

            if ($datos['nombre_usuario'] === $this->nombre_usuario) {
                return ['campo' => 'usuario', 'mensaje' => 'El nombre de usuario ya está en uso'];
            }
        }

        return false;
    }

    public function crear()
    {
        $this->clave_usuario = password_hash($this->clave_usuario, PASSWORD_BCRYPT);
        $this->token_verificacion = uniqid();
        $this->verificado = 0;

        $atributos = $this->sanitizarAtributos();

        // Eliminar id_usuario para no insertarlo manualmente
        unset($atributos['id_usuario']);

        // Mapear los valores, permitiendo NULL sin comillas
        $valores = array_map(function ($valor) {
            return $valor === null ? "NULL" : "'" . self::$db->real_escape_string($valor) . "'";
        }, array_values($atributos));

        $query = "INSERT INTO " . static::$tabla . " (";
        $query .= join(', ', array_keys($atributos));
        $query .= ") VALUES (";
        $query .= join(', ', $valores);
        $query .= ")";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id' => self::$db->insert_id
        ];
    }

    public static function buscarEmailoNombre($input)
    {
        $input = self::$db->escape_string($input);

        $query = "SELECT * FROM " . static::$tabla . "
                WHERE email = '{$input}' OR nombre_usuario = '{$input}'
                LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            $datos = $resultado->fetch_assoc();
            return new static($datos);
        }

        return null;
    }

    public static function obtenerConDetalle($paginador = null)
    {
        $limit = $paginador ? $paginador->limit() : '';

        $query = "SELECT 
                    u.id_usuario,
                    u.nombre_usuario,
                    p.nombre_persona,
                    p.apellido_persona,
                    u.email,
                    s.nombre_sexo,
                    per.nombre_perfil,
                    u.estado
                FROM usuarios u
                INNER JOIN personas p ON u.id_persona = p.id_persona
                INNER JOIN sexo s ON p.rela_sexo = s.id_sexo
                INNER JOIN perfiles per ON u.rela_perfil = per.id_perfiles
                WHERE u.verificado = 1
                ORDER BY u.id_usuario DESC
                {$limit}";

        $resultado = self::$db->query($query);
        $usuarios = [];
        while ($fila = $resultado->fetch_assoc()) {
            $usuarios[] = $fila;
        }
        return $usuarios;
    }

    public static function eliminarLogico($id)
    {
        $id = self::$db->escape_string($id);

        $query = "UPDATE " . static::$tabla . " SET estado = 0 WHERE id_usuario = '{$id}' LIMIT 1";
        return self::$db->query($query);
    }

    public static function buscarPorCampoUnico($termino)
    {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT 
                    u.id_usuario,
                    u.nombre_usuario,
                    p.nombre_persona,
                    p.apellido_persona,
                    u.email,
                    s.nombre_sexo,
                    per.nombre_perfil,
                    u.estado
                FROM usuarios u
                INNER JOIN personas p ON u.id_persona = p.id_persona
                INNER JOIN sexo s ON p.rela_sexo = s.id_sexo
                INNER JOIN perfiles per ON u.rela_perfil = per.id_perfiles
                WHERE 
                    u.nombre_usuario LIKE '%$termino%' OR 
                    u.email LIKE '%$termino%'
                LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public static function actualizarPerfil($id_usuario, $datos)
    {
        try {
            // Usar la conexión mysqli existente en lugar de PDO
            $db = self::$db;

            // Iniciar transacción
            $db->autocommit(false);

            // Verificar que el usuario existe y obtener su id_persona
            $id_usuario_escaped = $db->escape_string($id_usuario);
            $query = "SELECT id_persona FROM usuarios WHERE id_usuario = '{$id_usuario_escaped}'";
            $resultado = $db->query($query);

            if (!$resultado || $resultado->num_rows === 0) {
                throw new \Exception('Usuario no encontrado');
            }

            $usuario = $resultado->fetch_assoc();
            $id_persona = $usuario['id_persona'];

            // Verificar que el nombre de usuario no esté en uso por otro usuario
            $nombre_usuario_escaped = $db->escape_string($datos['nombre_usuario']);
            $query = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = '{$nombre_usuario_escaped}' AND id_usuario != '{$id_usuario_escaped}'";
            $resultado = $db->query($query);

            if ($resultado && $resultado->num_rows > 0) {
                throw new \Exception('El nombre de usuario ya está en uso por otro usuario');
            }

            // Verificar que el email no esté en uso por otro usuario
            $email_escaped = $db->escape_string($datos['email']);
            $query = "SELECT id_usuario FROM usuarios WHERE email = '{$email_escaped}' AND id_usuario != '{$id_usuario_escaped}'";
            $resultado = $db->query($query);

            if ($resultado && $resultado->num_rows > 0) {
                throw new \Exception('El email ya está registrado por otro usuario');
            }

            // Actualizar tabla personas
            $nombre_persona_escaped = $db->escape_string($datos['nombre_persona']);
            $apellido_persona_escaped = $db->escape_string($datos['apellido_persona']);
            $sexo_escaped = $db->escape_string($datos['sexo']);
            $id_persona_escaped = $db->escape_string($id_persona);

            $query_personas = "UPDATE personas SET 
                              nombre_persona = '{$nombre_persona_escaped}', 
                              apellido_persona = '{$apellido_persona_escaped}', 
                              rela_sexo = '{$sexo_escaped}' 
                              WHERE id_persona = '{$id_persona_escaped}'";

            $result1 = $db->query($query_personas);

            if (!$result1) {
                throw new \Exception('Error al actualizar datos personales: ' . $db->error);
            }

            // Actualizar tabla usuarios
            $query_usuarios = "UPDATE usuarios SET 
                              nombre_usuario = '{$nombre_usuario_escaped}', 
                              email = '{$email_escaped}'";

            // Si hay nueva foto, agregarla a la consulta
            if (!empty($datos['foto_perfil'])) {
                $foto_escaped = $db->escape_string($datos['foto_perfil']);
                $query_usuarios .= ", foto_perfil = '{$foto_escaped}'";
            }

            $query_usuarios .= " WHERE id_usuario = '{$id_usuario_escaped}'";

            $result2 = $db->query($query_usuarios);

            if (!$result2) {
                throw new \Exception('Error al actualizar datos de usuario: ' . $db->error);
            }

            // Confirmar transacción
            $db->commit();
            $db->autocommit(true);

            return true;
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            if (isset($db)) {
                $db->rollback();
                $db->autocommit(true);
            }

            // Log del error para debugging
            error_log("Error en actualizarPerfil: " . $e->getMessage());

            return false;
        }
    }

    public static function validarPasswordFortaleza($password)
    {
        $errores = [];

        if (strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        if (strlen($password) > 100) {
            $errores[] = 'La contraseña no puede tener más de 100 caracteres';
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un número';
        }

        $contraseñas_debiles = [
            '123456',
            'password',
            '123456789',
            '12345678',
            '12345',
            '1234567',
            'admin123',
            'qwerty',
            'abc123',
            'password123'
        ];
        if (in_array(strtolower($password), $contraseñas_debiles)) {
            $errores[] = 'La contraseña es muy común, elige una más segura';
        }

        return $errores;
    }

    public static function cambiarPassword($id_usuario, $passwordActual, $passwordNueva)
    {
        $db = self::getDB();
        $id_usuario_escaped = $db->escape_string($id_usuario);

        $query = "SELECT clave_usuario, nombre_usuario, email FROM usuarios WHERE id_usuario = '{$id_usuario_escaped}'";
        $resultado = $db->query($query);

        if (!$resultado || $resultado->num_rows === 0) {
            return ['ok' => false, 'mensaje' => 'Usuario no encontrado'];
        }

        $usuario = $resultado->fetch_assoc();

        if (!password_verify($passwordActual, $usuario['clave_usuario'])) {
            return ['ok' => false, 'mensaje' => 'La contraseña actual es incorrecta'];
        }

        if (password_verify($passwordNueva, $usuario['clave_usuario'])) {
            return ['ok' => false, 'mensaje' => 'La nueva contraseña debe ser distinta a la actual'];
        }

        $errores = self::validarPasswordFortaleza($passwordNueva);
        if (!empty($errores)) {
            return ['ok' => false, 'mensaje' => implode('. ', $errores)];
        }

        $nuevoHash = password_hash($passwordNueva, PASSWORD_BCRYPT);
        $nuevoHash_escaped = $db->escape_string($nuevoHash);

        $queryUpdate = "UPDATE usuarios SET clave_usuario = '{$nuevoHash_escaped}' WHERE id_usuario = '{$id_usuario_escaped}'";
        $resultUpdate = $db->query($queryUpdate);

        if (!$resultUpdate) {
            return ['ok' => false, 'mensaje' => 'Error al actualizar la contraseña'];
        }

        return [
            'ok' => true,
            'mensaje' => 'Contraseña actualizada correctamente',
            'nombre_usuario' => $usuario['nombre_usuario'],
            'email' => $usuario['email']
        ];
    }
}
