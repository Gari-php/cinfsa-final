<?php

namespace Controllers;

use MVC\Router;

class ModulosController {
    
    /**
     * Gestionar asignación de módulos a perfiles
     */
    public static function asignar(Router $router) {
        // Verificar que es administrador
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 3) {
            header('Location: /');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            $json = file_get_contents('php://input');
            $datos = json_decode($json, true);
            
            $idPerfil = $datos['id_perfil'] ?? null;
            $idModulo = $datos['id_modulo'] ?? null;
            $estado = $datos['estado'] ?? 1;
            
            if (!$idPerfil || !$idModulo) {
                echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
                return;
            }
            
            // ═══════════════════════════════════════════════════════════
            // BLOQUEAR ASIGNACIONES AL PERFIL ADMINISTRADOR (id 3)
            // ═══════════════════════════════════════════════════════════
            if ($idPerfil == 3) {
                echo json_encode([
                    'ok' => false, 
                    'error' => 'El perfil ADMINISTRADOR tiene acceso completo automáticamente. No necesita asignaciones de módulos.'
                ]);
                return;
            }
            
            $db = \Models\ActiveRecord::getDB();

            // Registro de auditoría del cambio de permiso (se llama solo si se guardó bien)
            $registrarPermiso = function ($estadoAntes) use ($db, $idPerfil, $idModulo, $estado) {
                $stmtNombre = $db->prepare("SELECT modulo_nombre FROM modulos WHERE id_modulo = ?");
                $stmtNombre->bind_param('i', $idModulo);
                $stmtNombre->execute();
                $modulo = $stmtNombre->get_result()->fetch_column() ?: "módulo #$idModulo";
                $perfil = \Classes\Auditoria::perfil($idPerfil);
                \Classes\Auditoria::registrar(
                    'permisos.modulos',
                    $estado ? "Le dio el módulo $modulo al perfil $perfil" : "Le quitó el módulo $modulo al perfil $perfil",
                    'modulo_x_tipos_de_usuarios',
                    $idModulo,
                    ['perfil' => $perfil, 'modulo' => $modulo, 'activo' => $estadoAntes === null ? 'sin asignar' : ($estadoAntes ? 'sí' : 'no')],
                    ['perfil' => $perfil, 'modulo' => $modulo, 'activo' => $estado ? 'sí' : 'no']
                );
            };

            // Verificar si ya existe la relación
            $query = "SELECT id_mod_x_tipo, estado FROM modulo_x_tipos_de_usuarios
                      WHERE rela_tipos_de_usuarios = ? AND rela_modulo = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $idPerfil, $idModulo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $existe = $resultado->fetch_assoc();
            
            if ($existe) {
                // Actualizar estado
                $queryUpdate = "UPDATE modulo_x_tipos_de_usuarios 
                               SET estado = ? 
                               WHERE id_mod_x_tipo = ?";
                $stmtUpdate = $db->prepare($queryUpdate);
                $stmtUpdate->bind_param("ii", $estado, $existe['id_mod_x_tipo']);
                $success = $stmtUpdate->execute();
                
                if ($success) {
                    if ((int)$existe['estado'] !== (int)$estado) {
                        $registrarPermiso((int)$existe['estado']);
                    }
                    echo json_encode([
                        'ok' => true,
                        'mensaje' => $estado ? 'Módulo activado correctamente' : 'Módulo desactivado correctamente'
                    ]);
                } else {
                    echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el módulo']);
                }
            } else {
                // Crear nueva relación
                $queryInsert = "INSERT INTO modulo_x_tipos_de_usuarios 
                               (rela_tipos_de_usuarios, rela_modulo, estado) 
                               VALUES (?, ?, ?)";
                $stmtInsert = $db->prepare($queryInsert);
                $stmtInsert->bind_param("iii", $idPerfil, $idModulo, $estado);
                $success = $stmtInsert->execute();
                
                if ($success) {
                    $registrarPermiso(null);
                    echo json_encode([
                        'ok' => true,
                        'mensaje' => 'Módulo asignado correctamente'
                    ]);
                } else {
                    echo json_encode(['ok' => false, 'error' => 'No se pudo asignar el módulo']);
                }
            }
            
            return;
        }
        
        // GET - Mostrar formulario
        $db = \Models\ActiveRecord::getDB();
        
        // ═══════════════════════════════════════════════════════════
        // Obtener perfiles (EXCLUIR ADMINISTRADOR y VENDEDOR genérico)
        // ═══════════════════════════════════════════════════════════
        $queryPerfiles = "SELECT * FROM perfiles 
                         WHERE id_perfiles NOT IN (2, 3) 
                         ORDER BY nombre_perfil";
        $resultadoPerfiles = $db->query($queryPerfiles);
        
        $perfiles = [];
        while ($row = $resultadoPerfiles->fetch_assoc()) {
            $perfiles[] = (object)$row;
        }
        
        // Obtener módulos
        $queryModulos = "SELECT * FROM modulos ORDER BY modulo_nombre";
        $resultadoModulos = $db->query($queryModulos);
        
        $modulos = [];
        while ($row = $resultadoModulos->fetch_assoc()) {
            $modulos[] = (object)$row;
        }
        
        // ═══════════════════════════════════════════════════════════
        // Obtener asignaciones actuales (EXCLUIR ADMINISTRADOR)
        // ═══════════════════════════════════════════════════════════
        $queryAsignaciones = "SELECT mxu.*, m.modulo_nombre, p.nombre_perfil
                             FROM modulo_x_tipos_de_usuarios mxu
                             INNER JOIN modulos m ON m.id_modulo = mxu.rela_modulo
                             INNER JOIN perfiles p ON p.id_perfiles = mxu.rela_tipos_de_usuarios
                             WHERE p.id_perfiles != 3
                             ORDER BY p.nombre_perfil, m.modulo_nombre";
        $resultadoAsignaciones = $db->query($queryAsignaciones);
        
        $asignaciones = [];
        while ($row = $resultadoAsignaciones->fetch_assoc()) {
            $asignaciones[] = $row;
        }
        
        $router->render('administrador/modulos/asignar', [
            'vista' => 'administrador/modulos/asignar',
            'perfiles' => $perfiles,
            'modulos' => $modulos,
            'asignaciones' => $asignaciones
        ]);
    }
    
    /**
     * Obtener módulos de un perfil específico (AJAX)
     */
    public static function modulosPerfil() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 3) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']);
            return;
        }
        
        $idPerfil = $_GET['id_perfil'] ?? null;
        
        if (!$idPerfil) {
            echo json_encode(['ok' => false, 'error' => 'ID de perfil requerido']);
            return;
        }
        
        // ═══════════════════════════════════════════════════════════
        // BLOQUEAR CONSULTA PARA ADMINISTRADOR
        // ═══════════════════════════════════════════════════════════
        if ($idPerfil == 3) {
            echo json_encode([
                'ok' => false, 
                'error' => 'El perfil ADMINISTRADOR no requiere asignación de módulos'
            ]);
            return;
        }
        
        $db = \Models\ActiveRecord::getDB();
        
        // Obtener todos los módulos con su estado para este perfil
        $query = "SELECT m.id_modulo, m.modulo_nombre, 
                  COALESCE(mxu.estado, 0) as asignado,
                  mxu.id_mod_x_tipo
                  FROM modulos m
                  LEFT JOIN modulo_x_tipos_de_usuarios mxu 
                  ON m.id_modulo = mxu.rela_modulo AND mxu.rela_tipos_de_usuarios = ?
                  ORDER BY m.modulo_nombre";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idPerfil);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $modulos = [];
        while ($row = $resultado->fetch_assoc()) {
            $modulos[] = $row;
        }
        
        echo json_encode(['ok' => true, 'modulos' => $modulos]);
    }
}