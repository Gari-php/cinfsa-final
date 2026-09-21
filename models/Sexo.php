<?php
namespace Models;

class Sexo extends ActiveRecord {
    protected static $tabla = 'sexo';
    protected static $columnasDB = ['id_sexo', 'nombre_sexo','estado'];

    public $id_sexo;
    public $nombre_sexo;
    public $estado;


    public function __construct($args = []) {
        $this->id_sexo = $args['id_sexo'] ?? null;
        $this->nombre_sexo = $args['nombre_sexo'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }

   public function validar() {
    $errores = [];
    
    // 1. Validar que el campo no esté vacío
    if (!$this->nombre_sexo || trim($this->nombre_sexo) === '') {
        $errores[] = 'El nombre del sexo es obligatorio';
        return $errores; // Si está vacío, no validar el resto
    }
    
    // 2. Limpiar espacios en blanco
    $this->nombre_sexo = trim($this->nombre_sexo);
    
    // 3. Validar longitud mínima y máxima
    if (strlen($this->nombre_sexo) < 2) {
        $errores[] = 'El nombre del sexo debe tener al menos 2 caracteres';
    }
    
    if (strlen($this->nombre_sexo) > 20) {
        $errores[] = 'El nombre del sexo no puede exceder 20 caracteres';
    }
    
    // 4. Validar que solo contenga letras y espacios (sin números ni caracteres especiales)
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $this->nombre_sexo)) {
        $errores[] = 'El nombre del sexo solo puede contener letras y espacios';
    }
    
    // 5. Validar que no contenga múltiples espacios consecutivos
    if (preg_match('/\s{2,}/', $this->nombre_sexo)) {
        $errores[] = 'El nombre del sexo no puede tener espacios consecutivos';
    }
    
    // 6. Validar que no empiece o termine con espacios (ya limpiado, pero por seguridad)
    if ($this->nombre_sexo !== trim($this->nombre_sexo)) {
        $errores[] = 'El nombre del sexo no puede empezar o terminar con espacios';
    }
     
    // 8. Validar palabras no permitidas (opcional - puedes personalizar esta lista)
    $palabrasProhibidas = ['test', 'prueba', 'ejemplo', 'null', 'undefined'];
    $nombreLower = strtolower($this->nombre_sexo);
    
    foreach ($palabrasProhibidas as $palabra) {
        if (strpos($nombreLower, $palabra) !== false) {
            $errores[] = 'El nombre del sexo contiene palabras no permitidas';
            break;
        }
    }
    
    return $errores;
}

    public static function obtenerTodos($paginador = null) {
        $db = self::getDB();
        
        $limit = $paginador ? $paginador->limit() : '';
        
        $query = "SELECT * FROM sexo ORDER BY id_sexo ASC {$limit}";
        $resultado = $db->query($query);
        
        $sexos = [];
        while($row = $resultado->fetch_assoc()) {
            $sexo = new self;
            $sexo->id_sexo = $row['id_sexo'];
            $sexo->nombre_sexo = $row['nombre_sexo'];
            $sexo->estado = $row['estado'];
            $sexos[] = $sexo;
        }
        return $sexos;
    }

    public function crearSexo() {
        $query = "INSERT INTO " . static::$tabla . " (nombre_sexo) VALUES ('{$this->nombre_sexo}')";
        $resultado = self::$db->query($query);
        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public static function buscarSexo($termino) {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT * FROM sexo WHERE id_sexo = '$termino' OR nombre_sexo LIKE '%$termino%' LIMIT 1";
        $resultado = self::$db->query($query);
        return ($resultado && $resultado->num_rows) ? $resultado->fetch_assoc() : null;
    }

    public function actualizar() {
        $query = "UPDATE sexo SET nombre_sexo = ? WHERE id_sexo = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([$this->nombre_sexo, $this->id_sexo]);
    }
}
