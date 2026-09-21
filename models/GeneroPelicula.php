<?php
namespace Models;

class GeneroPelicula extends ActiveRecord {
    protected static $tabla = 'generos_peliculas';
    protected static $columnasDB = ['id_genero_pelicula', 'genero_pelicula', 'estado'];

    public $id_genero_pelicula;
    public $genero_pelicula;
    public $estado;

    public function __construct($args = []) {
        $this->id_genero_pelicula = $args['id_genero_pelicula'] ?? null;
        $this->genero_pelicula = $args['genero_pelicula'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }

    public function validar() {
        $errores = []; // Array simple de strings
        
        if (!$this->genero_pelicula) {
            $errores[] = 'El género de película es obligatorio';
        }
        
        return $errores; // Retorna array simple
    }

    public static function obtenerTodos() {
        $db = self::getDB();
        // Traer todos los géneros, sin filtrar
        $resultado = $db->query("SELECT * FROM generos_peliculas");
        $generos = [];
        while($row = $resultado->fetch_assoc()) {
            $genero = new self;
            $genero->id_genero_pelicula = $row['id_genero_pelicula'];
            $genero->genero_pelicula = $row['genero_pelicula'];
            $genero->estado = $row['estado']; // guardar el estado
            $generos[] = $genero;
        }
        return $generos;
    }

    public function crearGenero() {
        $query = "INSERT INTO " . static::$tabla . " (genero_pelicula, estado) 
                 VALUES ('{$this->genero_pelicula}', '{$this->estado}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public static function buscarGenero($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT * FROM generos_peliculas WHERE id_genero_pelicula = '$termino' OR genero_pelicula LIKE '%$termino%' LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function actualizar(){
        $query = "UPDATE generos_peliculas SET genero_pelicula = ?, estado = ? WHERE id_genero_pelicula = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->genero_pelicula,
            $this->estado,
            $this->id_genero_pelicula
        ]);
    }

    public function darDeBaja(){
        $query = "UPDATE generos_peliculas SET estado = 0 WHERE id_genero_pelicula = " . self::$db->real_escape_string($this->id_genero_pelicula);
        $resultado = self::$db->query($query);
        return $resultado;
    }
}