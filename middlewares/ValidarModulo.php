<?php

namespace Middlewares;

class ValidarModulo {
    
    /**
     * 
     * 
     * @param string $nombreModulo Nombre del módulo a verificar
     * @return bool
     */
    public static function tiene($nombreModulo) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        
        if (!isset($_SESSION['login']) || !isset($_SESSION['id'])) {
            return false;
        }

        $idUsuario = $_SESSION['id'];
        $idPerfil = $_SESSION['perfil'] ?? null;

        if (!$idPerfil) {
            return false;
        }

        // El administrador tiene acceso a todo
        if ($idPerfil == 3) {
            return true;
        }

        // Verificar en base de datos
        return self::verificarAccesoModulo($idPerfil, $nombreModulo);
    }

    /**
     * Verifica en la base de datos si el perfil tiene acceso al módulo
     * 
     * @param int $idPerfil
     * @param string $nombreModulo
     * @return bool
     */
    private static function verificarAccesoModulo($idPerfil, $nombreModulo) {
        $db = \Models\ActiveRecord::getDB();
        
        $query = "SELECT COUNT(*) as tiene_acceso 
                  FROM modulo_x_tipos_de_usuarios mxu
                  INNER JOIN modulos m ON m.id_modulo = mxu.rela_modulo
                  WHERE mxu.rela_tipos_de_usuarios = ? 
                  AND m.modulo_nombre = ?
                  AND mxu.estado = 1";

        $stmt = $db->prepare($query);
        $stmt->bind_param("is", $idPerfil, $nombreModulo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();

        return $row['tiene_acceso'] > 0;
    }

    /**
     * 
     * 
     * @param string $nombreModulo
     * @param string $redirigirA URL a donde redirigir si no tiene acceso
     * @return void
     */
    public static function requerir($nombreModulo, $redirigirA = '/') {
        if (!self::tiene($nombreModulo)) {
            if (self::esAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'ok' => false, 
                    'mensaje' => 'No tienes permisos para acceder a este módulo'
                ]);
                exit;
            } else {
                $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
                header("Location: $redirigirA");
                exit;
            }
        }
    }

    /**
     * Verifica si la petición es AJAX
     * 
     * @return bool
     */
    private static function esAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Obtiene todos los módulos a los que tiene acceso el usuario actual
     * 
     * @return array
     */
    public static function obtenerModulosUsuario() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['perfil'])) {
            return [];
        }

        $idPerfil = $_SESSION['perfil'];

        // Admin tiene todos
        if ($idPerfil == 3) {
            $db = \Models\ActiveRecord::getDB();
            $resultado = $db->query("SELECT modulo_nombre FROM modulos");
            $modulos = [];
            while($row = $resultado->fetch_assoc()) {
                $modulos[] = $row['modulo_nombre'];
            }
            return $modulos;
        }

        // Buscar módulos del perfil
        $db = \Models\ActiveRecord::getDB();
        $query = "SELECT m.modulo_nombre 
                  FROM modulo_x_tipos_de_usuarios mxu
                  INNER JOIN modulos m ON m.id_modulo = mxu.rela_modulo
                  WHERE mxu.rela_tipos_de_usuarios = ? 
                  AND mxu.estado = 1";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idPerfil);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $modulos = [];
        while($row = $resultado->fetch_assoc()) {
            $modulos[] = $row['modulo_nombre'];
        }

        return $modulos;
    }

    /**
     * Verifica múltiples módulos (el usuario debe tener al menos uno)
     * 
     * @param array $modulos
     * @return bool
     */
    public static function tieneAlguno(array $modulos) {
        foreach ($modulos as $modulo) {
            if (self::tiene($modulo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica múltiples módulos (el usuario debe tener TODOS)
     * 
     * @param array $modulos
     * @return bool
     */
    public static function tieneTodos(array $modulos) {
        foreach ($modulos as $modulo) {
            if (!self::tiene($modulo)) {
                return false;
            }
        }
        return true;
    }
}