<?php

namespace Controllers;

use Models\Usuario;
use Models\Persona;
use Classes\ExportadorDatos;
use MVC\Router;
use Classes\Paginador;

class UsuarioController
{

    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    public static function index(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // PAGINACIÓN
        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 3;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        // Contar total
        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM usuarios";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener usuarios con LIMIT
        $usuarios = Usuario::obtenerConDetalle($paginador);

        $router->render('administrador/usuarios/listado', [
            'usuarios' => $usuarios,
            'paginador' => $paginador
        ]);
    }
    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $usuario = new Usuario;
        $persona = new Persona;

        $db = \Models\ActiveRecord::getDB();
        $perfiles = $db->query("SELECT * FROM perfiles")->fetch_all(MYSQLI_ASSOC);
        $sexos = $db->query("SELECT * FROM sexo")->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/usuarios/crear', [
            'usuario' => $usuario,
            'persona' => $persona,
            'perfiles' => $perfiles,
            'sexos' => $sexos
        ]);
    }
    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!$datos) {
            echo json_encode(['errores' => ['No se recibieron datos válidos']]);
            return;
        }

        // Crear instancia de Persona para validar
        $persona = new Persona([
            'nombre_persona' => $datos['nombre_persona'] ?? '',
            'apellido_persona' => $datos['apellido_persona'] ?? '',
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? '',
            'rela_sexo' => $datos['rela_sexo'] ?? '',
            'estado' => 1
        ]);

        // Validar Persona
        $erroresPersona = $persona->validar();

        // Crear instancia de Usuario para validar
        $usuario = new Usuario([
            'nombre_usuario' => $datos['nombre_usuario'] ?? '',
            'clave_usuario' => $datos['clave_usuario'] ?? '',
            'email' => $datos['email'] ?? '',
            'rela_perfil' => $datos['rela_perfil'] ?? null,
            'estado' => 1
        ]);

        // Validar Usuario
        $erroresUsuario = $usuario->validar();

        // Combinar errores
        $errores = array_merge($erroresPersona, $erroresUsuario);

        // Verificar duplicados solo si no hay errores de validación
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

        // Proceder a guardar si no hay errores
        $resPersona = $persona->crear();

        if (!$resPersona['resultado']) {
            echo json_encode(['errores' => ['Error al guardar la persona']]);
            return;
        }

        // Generar token y crear usuario
        $usuario->id_persona = $resPersona['id'];
        $usuario->token_verificacion = bin2hex(random_bytes(32));
        $usuario->verificado = 0;

        $resUsuario = $usuario->crear();

        if ($resUsuario['resultado']) {
            // Enviar email de confirmación
            try {
                $email = new \Classes\Email($usuario->email, $usuario->nombre_usuario, $usuario->token_verificacion);
                $emailEnviado = $email->enviarConfirmacion();

                $mensaje = 'Usuario creado correctamente.';
                if (!$emailEnviado) {
                    $mensaje .= ' ⚠️ Pero no se pudo enviar el correo de verificación.';
                }

                echo json_encode([
                    'ok' => true,
                    'mensaje' => $mensaje,
                    'redirigir' => '/administrador/usuarios/listado'
                ]);
            } catch (\Exception $e) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Usuario creado correctamente. ⚠️ Error al enviar email de verificación.',
                    'redirigir' => '/administrador/usuarios/listado'
                ]);
            }
        } else {
            // Si falla el usuario, eliminar la persona creada
            if ($resPersona['id']) {
                $persona->id_persona = $resPersona['id'];
                $persona->eliminar();
            }
            echo json_encode(['errores' => ['Error al guardar el usuario']]);
        }
    }

    public static function editar(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/usuarios');
            return;
        }

        $usuario = Usuario::find($id);
        if (!$usuario) {
            header('Location: /administrador/usuarios');
            return;
        }

        $persona = Persona::find($usuario->id_persona);

        $db = \Models\ActiveRecord::getDB();
        $perfiles = $db->query("SELECT * FROM perfiles")->fetch_all(MYSQLI_ASSOC);
        $sexos = $db->query("SELECT * FROM sexo")->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/usuarios/editar', [
            'usuario' => $usuario,
            'persona' => $persona,
            'perfiles' => $perfiles,
            'sexos' => $sexos
        ]);
    }

    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!$datos) {
            echo json_encode(['errores' => ['Datos inválidos']]);
            exit;
        }

        // Validar que existan los IDs
        if (!isset($datos['id_usuario']) || !isset($datos['id_persona'])) {
            echo json_encode(['errores' => ['IDs requeridos no encontrados']]);
            exit;
        }

        // Obtener instancias existentes
        $persona = Persona::find($datos['id_persona']);
        $usuario = Usuario::find($datos['id_usuario']);

        if (!$persona || !$usuario) {
            echo json_encode(['errores' => ['Usuario o persona no encontrados']]);
            exit;
        }

        // Actualizar datos de persona
        $persona->nombre_persona = $datos['nombre_persona'] ?? '';
        $persona->apellido_persona = $datos['apellido_persona'] ?? '';
        $persona->fecha_nacimiento = $datos['fecha_nacimiento'] ?? '';
        $persona->rela_sexo = $datos['rela_sexo'] ?? '';

        // Validar persona
        $erroresPersona = $persona->validar();

        // Actualizar datos de usuario
        $usuario->nombre_usuario = $datos['nombre_usuario'] ?? '';
        $usuario->email = $datos['email'] ?? '';
        $usuario->rela_perfil = $datos['rela_perfil'] ?? null;

        // Solo validar contraseña si se proporciona una nueva
        if (!empty($datos['clave_usuario'])) {
            $usuario->clave_usuario = $datos['clave_usuario'];
        } else {
            // Temporal para validación - no cambiar la existente
            $usuario->clave_usuario = 'temporal123';
        }

        // Validar usuario
        $erroresUsuario = $usuario->validar();

        // Combinar errores
        $errores = array_merge($erroresPersona, $erroresUsuario);

        // Verificar duplicados (excluyendo el usuario actual)
        if (empty($errores)) {
            $db = \Models\ActiveRecord::getDB();

            // Verificar email duplicado
            $email = $db->escape_string($usuario->email);
            $resultado = $db->query("SELECT id_usuario FROM usuarios WHERE email = '$email' AND id_usuario != {$usuario->id_usuario}");
            if ($resultado && $resultado->num_rows > 0) {
                $errores[] = 'El email ya está en uso por otro usuario';
            }

            // Verificar nombre de usuario duplicado
            $nombreUsuario = $db->escape_string($usuario->nombre_usuario);
            $resultado = $db->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario = '$nombreUsuario' AND id_usuario != {$usuario->id_usuario}");
            if ($resultado && $resultado->num_rows > 0) {
                $errores[] = 'El nombre de usuario ya está en uso';
            }
        }

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            exit;
        }

        // Actualizar contraseña solo si se proporcionó una nueva
        if (!empty($datos['clave_usuario'])) {
            $usuario->clave_usuario = password_hash($datos['clave_usuario'], PASSWORD_BCRYPT);
        } else {
            // Restaurar la contraseña original
            $usuarioOriginal = Usuario::find($datos['id_usuario']);
            $usuario->clave_usuario = $usuarioOriginal->clave_usuario;
        }

        $resPersona = $persona->guardar();
        $resUsuario = $usuario->guardar();

        if ($resPersona['resultado'] && $resUsuario['resultado']) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Usuario actualizado correctamente',
                'redirigir' => '/administrador/usuarios/listado'
            ]);
        } else {
            echo json_encode(['errores' => ['Error al actualizar el usuario']]);
        }

        exit;
    }

    public static function eliminar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $id = $datos['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de usuario inválido']);
            return;
        }

        $usuario = Usuario::find($id);
        if (!$usuario) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no encontrado']);
            return;
        }

        $resultado = Usuario::eliminarLogico($id);

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Usuario dado de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar el usuario']);
        }
    }

    public static function buscar()
    {
        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término de búsqueda está vacío']);
            return;
        }

        $usuario = Usuario::buscarPorCampoUnico($termino);

        if ($usuario) {
            echo json_encode(['ok' => true, 'usuario' => $usuario]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró ningún usuario con ese email o nombre de usuario']);
        }
    }

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_usuario' => 'ID',
                'nombre_usuario' => 'Usuario',
                'nombre_persona' => 'Nombre',
                'apellido_persona' => 'Apellido',
                'email' => 'Email',
                'nombre_sexo' => 'Sexo',
                'nombre_perfil' => 'Perfil',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN USUARIOS EXCEL: Buscando término: " . $termino);

                $usuario = Usuario::buscarPorCampoUnico($termino);
                if ($usuario) {
                    $usuario['estado'] = $usuario['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$usuario];
                    error_log("EXPORTACIÓN USUARIOS EXCEL: Usuario encontrado para exportar");
                } else {
                    error_log("EXPORTACIÓN USUARIOS EXCEL: No se encontró usuario");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN USUARIOS EXCEL: Exportando todos los usuarios");
                $usuarios = Usuario::obtenerConDetalle();
                foreach ($usuarios as $u) {
                    $datos[] = [
                        'id_usuario' => $u['id_usuario'],
                        'nombre_usuario' => $u['nombre_usuario'],
                        'nombre_persona' => $u['nombre_persona'],
                        'apellido_persona' => $u['apellido_persona'],
                        'email' => $u['email'],
                        'nombre_sexo' => $u['nombre_sexo'],
                        'nombre_perfil' => $u['nombre_perfil'],
                        'estado' => $u['estado'] == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "usuario_filtrado_" . self::limpiarNombreArchivo($termino) : "usuarios_completo";
            $titulo = !empty($termino) ? "Usuario Filtrado: $termino" : "Listado Completo de Usuarios";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN USUARIOS EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/usuarios/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_usuario' => 'ID',
                'nombre_usuario' => 'Usuario',
                'nombre_persona' => 'Nombre',
                'apellido_persona' => 'Apellido',
                'email' => 'Email',
                'nombre_sexo' => 'Sexo',
                'nombre_perfil' => 'Perfil',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $usuario = Usuario::buscarPorCampoUnico($termino);
                if ($usuario) {
                    $usuario['estado'] = $usuario['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$usuario];
                } else {
                    $datos = [];
                }
            } else {
                $usuarios = Usuario::obtenerConDetalle();
                foreach ($usuarios as $u) {
                    $datos[] = [
                        'id_usuario' => $u['id_usuario'],
                        'nombre_usuario' => $u['nombre_usuario'],
                        'nombre_persona' => $u['nombre_persona'],
                        'apellido_persona' => $u['apellido_persona'],
                        'email' => $u['email'],
                        'nombre_sexo' => $u['nombre_sexo'],
                        'nombre_perfil' => $u['nombre_perfil'],
                        'estado' => $u['estado'] == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "usuario_filtrado_" . self::limpiarNombreArchivo($termino) : "usuarios_completo";
            $titulo = !empty($termino) ? "Usuario Filtrado: $termino" : "Listado Completo de Usuarios";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Usuarios',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN USUARIOS PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/usuarios/listado');
            exit;
        }
    }

    public static function exportar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $tipo = $_GET['tipo'] ?? '';

        switch ($tipo) {
            case 'excel':
                self::exportarExcel();
                break;
            case 'pdf':
                self::exportarPDF();
                break;
            default:
                echo json_encode(['ok' => false, 'mensaje' => 'Tipo de exportación no válido']);
                return;
        }
    }

    private static function limpiarNombreArchivo($nombre)
    {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        return substr($nombre, 0, 20);
    }

    public static function reportes(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/usuarios/reportes', []);
    }

    public static function apiDatosSexo()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
            s.nombre_sexo,
            COUNT(u.id_usuario) as total
          FROM sexo s
          LEFT JOIN personas p ON p.rela_sexo = s.id_sexo
          LEFT JOIN usuarios u ON u.id_persona = p.id_persona
          GROUP BY s.id_sexo, s.nombre_sexo
          ORDER BY total DESC";

        $resultado = $db->query($query);
        $datos = [];

        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'nombre' => ucfirst(strtolower($row['nombre_sexo'])),
                'total'  => (int) $row['total']
            ];
        }

        $totalUsuarios = array_sum(array_column($datos, 'total'));

        echo json_encode([
            'ok'     => true,
            'datos'  => $datos,
            'total'  => $totalUsuarios
        ]);
    }
}
