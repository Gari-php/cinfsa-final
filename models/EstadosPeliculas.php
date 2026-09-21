<?php
namespace Models;

class EstadosPeliculas extends ActiveRecord {
    protected static $tabla = 'estados_peliculas';
    protected static $columnasDB = ['id_estado_pelicula', 'nombre_estado_pelicula', 'estado'];

    public $id_estado_pelicula;
    public $nombre_estado_pelicula;
    public $estado;

    public function __construct($args = []) {
        $this->id_estado_pelicula = $args['id_estado_pelicula'] ?? null;
        $this->nombre_estado_pelicula = $args['nombre_estado_pelicula'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }

    public function validar() {
        $errores = []; // Array simple de strings
        
        if (!$this->nombre_estado_pelicula || trim($this->nombre_estado_pelicula) === '') {
            $errores[] = 'El nombre del estado es obligatorio';
        }
        
        if (strlen($this->nombre_estado_pelicula) > 45) {
            $errores[] = 'El nombre del estado no puede exceder 45 caracteres';
        }
        
        if (!isset($this->estado) || !in_array($this->estado, [0, 1])) {
            $errores[] = 'El estado debe ser válido (0 o 1)';
        }
        
        return $errores; // Retorna array simple
    }

    public static function obtenerEstadosPeliculas() {
        $db = self::getDB();
        $resultado = $db->query("SELECT * FROM estados_peliculas ORDER BY id_estado_pelicula DESC");
        $estados_peliculas = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $estado_pelicula = new self;
            $estado_pelicula->id_estado_pelicula = $row['id_estado_pelicula'];
            $estado_pelicula->nombre_estado_pelicula = $row['nombre_estado_pelicula'];
            $estado_pelicula->estado = $row['estado'];
            $estados_peliculas[] = $estado_pelicula;
        }
        
        return $estados_peliculas;
    }

    public static function buscarEstadosPeliculas($termino) {
        $db = self::getDB();
        $termino = $db->real_escape_string($termino);
        
        // Búsqueda más flexible - insensible a mayúsculas/minúsculas
        $query = "SELECT * FROM estados_peliculas 
                 WHERE CAST(id_estado_pelicula AS CHAR) LIKE '%{$termino}%' 
                    OR LOWER(nombre_estado_pelicula) LIKE LOWER('%{$termino}%')
                 ORDER BY id_estado_pelicula DESC";
        
        $resultado = $db->query($query);
        $estados_peliculas = [];
        
        if ($resultado && $resultado->num_rows > 0) {
            while ($row = $resultado->fetch_assoc()) {
                $estado_pelicula = new self;
                $estado_pelicula->id_estado_pelicula = $row['id_estado_pelicula'];
                $estado_pelicula->nombre_estado_pelicula = $row['nombre_estado_pelicula'];
                $estado_pelicula->estado = $row['estado'];
                $estados_peliculas[] = $estado_pelicula;
            }
        }
        
        return $estados_peliculas;
    }

    public function crearEstadoPelicula() {
        $query = "INSERT INTO " . static::$tabla . " (nombre_estado_pelicula, estado)
                 VALUES ('{$this->nombre_estado_pelicula}', '{$this->estado}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar() {
        $query = "UPDATE estados_peliculas SET nombre_estado_pelicula = ?, estado = ? WHERE id_estado_pelicula = ?";
        $stmt = self::$db->prepare($query);
        
        return $stmt->execute([
            $this->nombre_estado_pelicula,
            $this->estado,
            $this->id_estado_pelicula
        ]);
    }

    public function eliminarLogico() {
        $query = "UPDATE estados_peliculas SET estado = 0 WHERE id_estado_pelicula = " . self::$db->real_escape_string($this->id_estado_pelicula);
        $resultado = self::$db->query($query);
        return $resultado;
    }
}