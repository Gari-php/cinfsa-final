<?php
namespace Models;

class Sala extends ActiveRecord {
    protected static $tabla = 'salas';
    protected static $columnasDB = ['id_sala', 'capacidad_sala', 'filas_sala', 'columnas_sala', 'estado'];

    public $id_sala;
    public $capacidad_sala;
    public $filas_sala;
    public $columnas_sala;
    public $estado;

    public function __construct($args = []) {
        $this->id_sala = $args['id_sala'] ?? null;
        $this->capacidad_sala = $args['capacidad_sala'] ?? '';
        $this->filas_sala = $args['filas_sala'] ?? '';
        $this->columnas_sala = $args['columnas_sala'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }
    public function validar() {
        $errores = [];
        
        // Validar capacidad
        if (!$this->capacidad_sala || trim($this->capacidad_sala) === '') {
            $errores[] = 'La capacidad es obligatoria';
        } else {
            // Limpiar y validar que sea solo números
            $this->capacidad_sala = trim($this->capacidad_sala);
            
            if (!is_numeric($this->capacidad_sala)) {
                $errores[] = 'La capacidad debe ser un número válido';
            } else {
                $capacidad = intval($this->capacidad_sala);
                
                if ($capacidad <= 0) {
                    $errores[] = 'La capacidad debe ser mayor a 0';
                }
                
                if ($capacidad < 50) {
                    $errores[] = 'La capacidad mínima para una sala de cine es de 50 personas';
                }
                
                if ($capacidad > 500) {
                    $errores[] = 'La capacidad máxima permitida es de 500 personas';
                }
            }
        }
          // Validar filas
        if (!$this->filas_sala || trim($this->filas_sala) === '') {
            $errores[] = 'Las filas son obligatorias';
        } else {
            $this->filas_sala = trim($this->filas_sala);
            
            if (!is_numeric($this->filas_sala)) {
                $errores[] = 'Las filas deben ser un número válido';
            } else {
                $filas = intval($this->filas_sala);
                if ($filas <= 0) {
                    $errores[] = 'Las filas deben ser mayor a 0';
                }
                if ($filas < 5) {
                    $errores[] = 'Una sala de cine debe tener mínimo 5 filas';
                }
                if ($filas > 30) {
                    $errores[] = 'El máximo de filas permitido es 30';
                }
            }
        }
        // Validar columnas
        if (!$this->columnas_sala || trim($this->columnas_sala) === '') {
            $errores[] = 'Las columnas son obligatorias';
        } else {
            $this->columnas_sala = trim($this->columnas_sala);
            
            if (!is_numeric($this->columnas_sala)) {
                $errores[] = 'Las columnas deben ser un número válido';
            } else {
                $columnas = intval($this->columnas_sala);
                if ($columnas <= 0) {
                    $errores[] = 'Las columnas deben ser mayor a 0';
                }
                if ($columnas < 6) {
                    $errores[] = 'Una sala de cine debe tener mínimo 6 columnas';
                }
                if ($columnas > 25) {
                    $errores[] = 'El máximo de columnas permitido es 25';
                }
                // Validar números pares (común en cines para simetría)
                if ($columnas % 2 !== 0) {
                    $errores[] = 'Se recomienda usar un número par de columnas para mejor distribución';
                }
            }
        }
        // Validar coherencia entre filas, columnas y capacidad
        if (is_numeric($this->filas_sala) && is_numeric($this->columnas_sala) && is_numeric($this->capacidad_sala)) {
            $filas = intval($this->filas_sala);
            $columnas = intval($this->columnas_sala);
            $capacidad = intval($this->capacidad_sala);
            
            $asientos_calculados = $filas * $columnas;
            
            // La capacidad debe ser menor o igual a los asientos calculados
            if ($capacidad > $asientos_calculados) {
                $errores[] = "La capacidad ($capacidad) no puede ser mayor a los asientos disponibles ($asientos_calculados)";
            }
            
            // Tolerancia del 80% mínimo
            $capacidad_minima = floor($asientos_calculados * 0.8);
            if ($capacidad < $capacidad_minima) {
                $errores[] = "La capacidad es muy baja comparada con las dimensiones de la sala (mínimo recomendado: $capacidad_minima)";
            }
        }
        
        return $errores;
    }
    public static function obtenerTodas($paginador = null) {
        $db = self::getDB();
        
        $limit = $paginador ? $paginador->limit() : '';
        
        $query = "SELECT * FROM salas ORDER BY id_sala ASC {$limit}";
        $resultado = $db->query($query);
        
        $salas = [];
        while($row = $resultado->fetch_assoc()) {
            $sala = new self;
            $sala->id_sala = $row['id_sala'];
            $sala->capacidad_sala = $row['capacidad_sala'];
            $sala->filas_sala = $row['filas_sala'];
            $sala->columnas_sala = $row['columnas_sala'];
            $sala->estado = $row['estado'];
            $salas[] = $sala;
        }
        return $salas;
    }
    public function crearSala() {
        $query = "INSERT INTO " . static::$tabla . " (capacidad_sala, filas_sala, columnas_sala, estado) 
                VALUES ('{$this->capacidad_sala}', '{$this->filas_sala}', '{$this->columnas_sala}', '{$this->estado}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public static function buscarSala($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT * FROM salas WHERE id_sala = '$termino' OR capacidad_sala = '$termino' LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function actualizar(){
        $query = "UPDATE salas SET capacidad_sala = ?, filas_sala = ?, columnas_sala = ?,estado = ? WHERE id_sala = ?";
        $stmt = self::$db->prepare($query);
      return $stmt->execute([
            $this->capacidad_sala,
            $this->filas_sala,
            $this->columnas_sala,
            $this->estado, // 
            $this->id_sala
        ]);
    }

    public function darDeBaja(){
        $query = "UPDATE salas SET estado = 0 WHERE id_sala = " . self::$db->real_escape_string($this->id_sala);
        $resultado = self::$db->query($query);
        return $resultado;
    }

}
