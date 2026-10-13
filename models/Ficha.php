<?php
namespace Models;

class Ficha extends ActiveRecord {
    protected static $tabla = 'fichas';
    protected static $columnasDB = ['id_fichas', 'precio_ficha', 'cantidad_ficha', 'estado'];

    public $id_fichas;
    public $precio_ficha;
    public $cantidad_ficha;
    public $estado;

    public function __construct($args = []) {
        $this->id_fichas = $args['id_fichas'] ?? null;
        $this->precio_ficha = $args['precio_ficha'] ?? '';
        $this->cantidad_ficha = $args['cantidad_ficha'] ?? '';
        $this->estado = $args['estado'] ?? 1;
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
                  VALUES (?, ?)";
        $stmt = self::$db->prepare($query);
        $resultado = $stmt->execute([$this->precio_ficha, $this->cantidad_ficha]);
        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar() {
        $query = "UPDATE fichas SET precio_ficha = ?, cantidad_ficha = ?, estado = ? WHERE id_fichas = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->precio_ficha,
            $this->cantidad_ficha,
            (int)$this->estado,
            $this->id_fichas
        ]);
    }

    // Con $soloActivasMas = id: solo las activas, más esa (la que ya tiene asignada una máquina al editarla)
    public static function obtenerTodas($soloActivasMas = null) {
        $db = self::getDB();
        $resultado = $soloActivasMas === null
            ? $db->query("SELECT * FROM fichas")
            : $db->query("SELECT * FROM fichas WHERE estado = 1 OR id_fichas = " . (int)$soloActivasMas);
        $fichas = [];
        while($row = $resultado->fetch_assoc()) {
            $ficha = new self;
            $ficha->id_fichas = $row['id_fichas'];
            $ficha->precio_ficha = $row['precio_ficha'];
            $ficha->cantidad_ficha = $row['cantidad_ficha'];
            $ficha->estado = $row['estado'];
            $fichas[] = $ficha;
        }
        return $fichas;
    }

    public function darDeBaja() {
        $query = "UPDATE fichas SET estado = 0 WHERE id_fichas = " . (int)$this->id_fichas;
        return self::$db->query($query);
    }

    // Máquinas activas que venden esta ficha (mientras haya alguna no se puede dar de baja)
    public static function maquinasActivas($idFicha): array {
        $stmt = self::$db->prepare("SELECT maquinas_nombre FROM maquinas WHERE rela_fichas = ? AND estado = 1 ORDER BY maquinas_nombre");
        $stmt->execute([(int)$idFicha]);
        return array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'maquinas_nombre');
    }

    public static function buscarFicha($termino) {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT * FROM fichas WHERE id_fichas = '$termino' OR precio_ficha = '$termino' LIMIT 1";
        $resultado = self::$db->query($query);
        return ($resultado && $resultado->num_rows) ? $resultado->fetch_assoc() : null;
    }
}