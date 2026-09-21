<?php
namespace Models;

class Ficha extends ActiveRecord {
    protected static $tabla = 'fichas';
    protected static $columnasDB = ['id_fichas', 'precio_ficha', 'cantidad_ficha'];

    public $id_fichas;
    public $precio_ficha;
    public $cantidad_ficha;

    public function __construct($args = []) {
        $this->id_fichas = $args['id_fichas'] ?? null;
        $this->precio_ficha = $args['precio_ficha'] ?? '';
        $this->cantidad_ficha = $args['cantidad_ficha'] ?? '';
    }

    public function validar() {
        $errores = [];
        
        if (!$this->precio_ficha || trim($this->precio_ficha) === '') {
            $errores[] = 'El precio es obligatorio';
        } else {
            $this->precio_ficha = trim($this->precio_ficha);
            
            if (preg_match('/[eE]/', $this->precio_ficha)) {
                $errores[] = 'El precio no puede contener la letra "e"';
            }
            
            if (!is_numeric($this->precio_ficha)) {
                $errores[] = 'El precio debe ser un número válido';
            } else {
                $precio = floatval($this->precio_ficha);
                
                if ($precio <= 0) {
                    $errores[] = 'El precio debe ser mayor a 0';
                }
                
                if ($precio > 10000) {
                    $errores[] = 'El precio no puede exceder $10,000';
                }
                
                if (strpos($this->precio_ficha, '.') !== false) {
                    $decimales = explode('.', $this->precio_ficha)[1];
                    if (strlen($decimales) > 2) {
                        $errores[] = 'El precio no puede tener más de 2 decimales';
                    }
                }
            }
        }
        
        if (!$this->cantidad_ficha || trim($this->cantidad_ficha) === '') {
            $errores[] = 'La cantidad es obligatoria';
        } else {
            $this->cantidad_ficha = trim($this->cantidad_ficha);
            
            if (!is_numeric($this->cantidad_ficha)) {
                $errores[] = 'La cantidad debe ser un número válido';
            } else {
                if (!ctype_digit($this->cantidad_ficha)) {
                    $errores[] = 'La cantidad debe ser un número entero';
                }
                
                $cantidad = intval($this->cantidad_ficha);
                
                if ($cantidad <= 0) {
                    $errores[] = 'La cantidad debe ser mayor a 0';
                }
                
                if ($cantidad > 50000) {
                    $errores[] = 'La cantidad no puede exceder 50,000 fichas';
                }
            }
        }
        
        return $errores;
    }

    public function crearFicha() {
        $query = "INSERT INTO " . static::$tabla . " (precio_ficha, cantidad_ficha) 
                  VALUES ('{$this->precio_ficha}', '{$this->cantidad_ficha}')";
        $resultado = self::$db->query($query);
        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar() {
        $query = "UPDATE fichas SET precio_ficha = ?, cantidad_ficha = ? WHERE id_fichas = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->precio_ficha,
            $this->cantidad_ficha,
            $this->id_fichas
        ]);
    }

    public static function obtenerTodas() {
        $db = self::getDB();
        $resultado = $db->query("SELECT * FROM fichas");
        $fichas = [];
        while($row = $resultado->fetch_assoc()) {
            $ficha = new self;
            $ficha->id_fichas = $row['id_fichas'];
            $ficha->precio_ficha = $row['precio_ficha'];
            $ficha->cantidad_ficha = $row['cantidad_ficha'];
            $fichas[] = $ficha;
        }
        return $fichas;
    }

    public static function buscarFicha($termino) {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT * FROM fichas WHERE id_fichas = '$termino' OR precio_ficha = '$termino' LIMIT 1";
        $resultado = self::$db->query($query);
        return ($resultado && $resultado->num_rows) ? $resultado->fetch_assoc() : null;
    }
}