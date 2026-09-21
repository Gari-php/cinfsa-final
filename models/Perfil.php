<?php
namespace Models;

class Perfil extends ActiveRecord {
    protected static $tabla = 'perfiles';
    protected static $columnasDB = ['id_perfiles', 'nombre_perfil', 'permiso_perfil', 'estado'];

    public $id_perfiles;
    public $nombre_perfil;
    public $permiso_perfil;
    public $estado;

    public function __construct($args = []) {
        $this->id_perfiles = $args['id_perfiles'] ?? null;
        $this->nombre_perfil = $args['nombre_perfil'] ?? '';
        $this->permiso_perfil = $args['permiso_perfil'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }

    public function validar() {
        $errores = [];
        
        if (!$this->nombre_perfil || trim($this->nombre_perfil) === '') {
            $errores[] = 'El nombre del perfil es obligatorio';
        } else {
            $this->nombre_perfil = trim($this->nombre_perfil);
            
            if (strlen($this->nombre_perfil) < 3) {
                $errores[] = 'El nombre debe tener al menos 3 caracteres';
            }
            
            if (strlen($this->nombre_perfil) > 45) {
                $errores[] = 'El nombre no puede exceder 45 caracteres';
            }
        }
        
        if (!$this->permiso_perfil || trim($this->permiso_perfil) === '') {
            $errores[] = 'El permiso del perfil es obligatorio';
        } else {
            $this->permiso_perfil = trim($this->permiso_perfil);
            
            if (strlen($this->permiso_perfil) > 45) {
                $errores[] = 'El permiso no puede exceder 45 caracteres';
            }
        }
        
        return $errores;
    }

    public function crearPerfil() {
        $query = "INSERT INTO " . static::$tabla . " (nombre_perfil, permiso_perfil, estado) 
                  VALUES ('{$this->nombre_perfil}', '{$this->permiso_perfil}', '{$this->estado}')";
        $resultado = self::$db->query($query);
        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar() {
        $query = "UPDATE perfiles SET nombre_perfil = ?, permiso_perfil = ?, estado = ? WHERE id_perfiles = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->nombre_perfil,
            $this->permiso_perfil,
            $this->estado,
            $this->id_perfiles
        ]);
    }

    public function darDeBaja() {
        $query = "UPDATE perfiles SET estado = 0 WHERE id_perfiles = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([$this->id_perfiles]);
    }

    public static function obtenerTodos($paginador = null) {
        $db = self::getDB();
        
        $limit = $paginador ? $paginador->limit() : '';
        
        $query = "SELECT * FROM perfiles ORDER BY id_perfiles ASC {$limit}";
        $resultado = $db->query($query);
        
        $perfiles = [];
        while($row = $resultado->fetch_assoc()) {
            $perfil = new self;
            $perfil->id_perfiles = $row['id_perfiles'];
            $perfil->nombre_perfil = $row['nombre_perfil'];
            $perfil->permiso_perfil = $row['permiso_perfil'];
            $perfil->estado = $row['estado'];
            $perfiles[] = $perfil;
        }
        return $perfiles;
    }

    public static function buscarPerfil($termino) {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT * FROM perfiles WHERE id_perfiles = '$termino' OR nombre_perfil LIKE '%$termino%' LIMIT 1";
        $resultado = self::$db->query($query);
        return ($resultado && $resultado->num_rows) ? $resultado->fetch_assoc() : null;
    }
}