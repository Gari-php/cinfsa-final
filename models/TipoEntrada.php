<?php
namespace Models;

class TipoEntrada extends ActiveRecord{
   protected static $tabla='tipo_entradas';
   protected static $columnasDB=['id_tipo_entrada','tipo_entrada_desc','precio_entrada','estado'];

   public $id_tipo_entrada;
   public $tipo_entrada_desc;
   public $precio_entrada;
   public $estado;

   public function __construct($args=[]){
        $this->id_tipo_entrada = $args['id_tipo_entrada'] ?? '';
        $this->tipo_entrada_desc = $args['tipo_entrada_desc'] ?? '';
        $this->precio_entrada = $args['precio_entrada'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }   

    public function validar(){
        $errores = [];
        if (!$this->tipo_entrada_desc || trim($this->tipo_entrada_desc) === '') {
            $errores[] = 'La descripción es obligatoria';
        } else {
            $this->tipo_entrada_desc = trim($this->tipo_entrada_desc);
            if (strlen($this->tipo_entrada_desc) > 10) {
                $errores[] = 'La descripción no puede exceder 10 caracteres';
            }
            if (strlen($this->tipo_entrada_desc) < 2) {
                $errores[] = 'La descripción debe tener al menos 2 caracteres';
            }
            if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s]+$/', $this->tipo_entrada_desc)) {
                $errores[] = 'La descripción solo puede contener letras, números y espacios';
            }
            if ($this->verificarDescripcionUnica()) {
                $errores[] = 'Ya existe un tipo de entrada con esa descripción';
            }
        }

        if (!$this->precio_entrada || trim($this->precio_entrada) === '') {
            $errores[] = 'El precio es obligatorio';
        } else {
            $this->precio_entrada = trim($this->precio_entrada);
            if (preg_match('/[eE]/', $this->precio_entrada)) {
                $errores[] = 'El precio no puede contener la letra "e"';
            }
            if (!is_numeric($this->precio_entrada)) {
                $errores[] = 'El precio debe ser un número válido';
            } else {
                $precio = floatval($this->precio_entrada);
                
                if ($precio <= 0) {
                    $errores[] = 'El precio debe ser mayor a 0';
                }
                
                if ($precio > 10000) {
                    $errores[] = 'El precio no puede exceder $10,000';
                }
                if (strpos($this->precio_entrada, '.') !== false) {
                    $decimales = explode('.', $this->precio_entrada)[1];
                    if (strlen($decimales) > 2) {
                        $errores[] = 'El precio no puede tener más de 2 decimales';
                    }
                }
            }
        }
        if ($this->estado !== null && !in_array($this->estado, [0, 1, '0', '1'])) {
            $errores[] = 'El estado debe ser válido (0 o 1)';
        }
        
        return $errores;
    }
    private function verificarDescripcionUnica() {
        $db = self::getDB();
        
        if ($this->id_tipo_entrada) {
            $query = "SELECT COUNT(*) as total FROM tipo_entradas 
                    WHERE LOWER(tipo_entrada_desc) = LOWER('{$this->tipo_entrada_desc}') 
                    AND id_tipo_entrada != '{$this->id_tipo_entrada}' 
                    AND estado = 1";
        } else {
            $query = "SELECT COUNT(*) as total FROM tipo_entradas 
                    WHERE LOWER(tipo_entrada_desc) = LOWER('{$this->tipo_entrada_desc}') 
                    AND estado = 1";
        }
        
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }
    public static function obtenerTodas($paginador = null) {
        $db = self::getDB();
        
        $limit = $paginador ? $paginador->limit() : '';
        
        $query = "SELECT * FROM tipo_entradas ORDER BY id_tipo_entrada ASC {$limit}";
        $resultado = $db->query($query);
        
        $tipoentradas = [];
        while($row = $resultado->fetch_assoc()) {
            $tipoentrada = new self;
            $tipoentrada->id_tipo_entrada = $row['id_tipo_entrada'];
            $tipoentrada->tipo_entrada_desc = $row['tipo_entrada_desc'];
            $tipoentrada->precio_entrada = $row['precio_entrada'];
            $tipoentrada->estado = $row['estado'];
            $tipoentradas[] = $tipoentrada;
        }
        return $tipoentradas;
    }
    public function creartipoentrada() {
        $query = "INSERT INTO " . static::$tabla . " (tipo_entrada_desc, precio_entrada, estado) 
                VALUES ('{$this->tipo_entrada_desc}', '{$this->precio_entrada}', '{$this->estado}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }
    public static function buscartipoentrada($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT * FROM tipo_entradas WHERE id_tipo_entrada = '$termino' OR tipo_entrada_desc = '$termino' LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }
    public function actualizar(){
        $query = "UPDATE tipo_entradas SET tipo_entrada_desc = ?, precio_entrada = ?,estado = ? WHERE id_tipo_entrada = ?";
        $stmt = self::$db->prepare($query);
      return $stmt->execute([
            $this->tipo_entrada_desc,
            $this->precio_entrada,
            $this->estado, // 
            $this->id_tipo_entrada
        ]);
    }

    public function darDeBaja(){
        $query = "UPDATE tipo_entradas SET estado = 0 WHERE id_tipo_entrada = " . self::$db->real_escape_string($this->id_tipo_entrada);
        $resultado = self::$db->query($query);
        return $resultado;
    }
   
}