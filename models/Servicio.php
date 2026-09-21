<?php
namespace Models;

class Servicio extends ActiveRecord {
    protected static $tabla = 'servicios_proveedor';
    protected static $columnasDB = [
        'id_servicio', 'rela_proveedor', 'nombre_servicio', 'descripcion', 
        'codigo_servicio', 'categoria_servicio', 'monto_base', 'tiene_monto_variable',
        'frecuencia_pago', 'activo', 'fecha_alta'
    ];

    public $id_servicio;
    public $rela_proveedor;
    public $nombre_servicio;
    public $descripcion;
    public $codigo_servicio;
    public $categoria_servicio;
    public $monto_base;
    public $tiene_monto_variable;
    public $frecuencia_pago;
    public $activo;
    public $fecha_alta;
    
    // Propiedades adicionales para JOIN
    public $razon_social;
    public $nombre_comercial;

    public function __construct($args = []) {
        $this->id_servicio = $args['id_servicio'] ?? null;
        $this->rela_proveedor = $args['rela_proveedor'] ?? null;
        $this->nombre_servicio = $args['nombre_servicio'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->codigo_servicio = $args['codigo_servicio'] ?? '';
        $this->categoria_servicio = $args['categoria_servicio'] ?? 'otros';
        $this->monto_base = $args['monto_base'] ?? 0;
        $this->tiene_monto_variable = $args['tiene_monto_variable'] ?? 0;
        $this->frecuencia_pago = $args['frecuencia_pago'] ?? 'mensual';
        $this->activo = $args['activo'] ?? 1;
        $this->fecha_alta = $args['fecha_alta'] ?? date('Y-m-d H:i:s');
    }

    public function validar() {
        $errores = [];
        
        // Validar proveedor
        if (!$this->rela_proveedor || !is_numeric($this->rela_proveedor)) {
            $errores[] = 'Debe seleccionar un proveedor válido';
        }
        
        // Validar nombre del servicio
        if (!$this->nombre_servicio || trim($this->nombre_servicio) === '') {
            $errores[] = 'El nombre del servicio es obligatorio';
        } else {
            $this->nombre_servicio = trim($this->nombre_servicio);
            
            if (strlen($this->nombre_servicio) < 3) {
                $errores[] = 'El nombre del servicio debe tener al menos 3 caracteres';
            }
            
            if (strlen($this->nombre_servicio) > 150) {
                $errores[] = 'El nombre del servicio no puede exceder 150 caracteres';
            }
        }
        
        // Validar código del servicio (opcional)
        if ($this->codigo_servicio && trim($this->codigo_servicio) !== '') {
            $this->codigo_servicio = trim($this->codigo_servicio);
            
            if (strlen($this->codigo_servicio) > 50) {
                $errores[] = 'El código del servicio no puede exceder 50 caracteres';
            }
            
            // Verificar que sea único
            if ($this->verificarCodigoUnico()) {
                $errores[] = 'Ya existe un servicio con ese código';
            }
        }
        
        // Validar categoría
        $categoriasValidas = [
            'licencia_pelicula', 'distribucion', 'agua', 'luz', 'gas', 
            'internet', 'telefonia', 'limpieza', 'mantenimiento', 
            'seguridad', 'alquiler', 'impuestos', 'otros'
        ];
        
        if (!in_array($this->categoria_servicio, $categoriasValidas)) {
            $errores[] = 'La categoría del servicio no es válida';
        }
        
        // Validar monto base
        if (!is_numeric($this->monto_base)) {
            $errores[] = 'El monto base debe ser un número válido';
        } else {
            if ($this->monto_base < 0) {
                $errores[] = 'El monto base no puede ser negativo';
            }
            
            if ($this->monto_base > 9999999.99) {
                $errores[] = 'El monto base no puede exceder $9,999,999.99';
            }
        }
        
        // Validar frecuencia de pago
        $frecuenciasValidas = ['mensual', 'bimestral', 'trimestral', 'unico', 'variable'];
        if (!in_array($this->frecuencia_pago, $frecuenciasValidas)) {
            $errores[] = 'La frecuencia de pago no es válida';
        }
        
        // Validar descripción (opcional)
        if ($this->descripcion && trim($this->descripcion) !== '') {
            $this->descripcion = trim($this->descripcion);
        }
        
        return $errores;
    }

    private function verificarCodigoUnico() {
        if (!$this->codigo_servicio) return false;
        
        $db = self::getDB();
        
        if ($this->id_servicio) {
            $query = "SELECT COUNT(*) as total FROM servicios_proveedor 
                      WHERE codigo_servicio = '{$this->codigo_servicio}' 
                      AND id_servicio != '{$this->id_servicio}'";
        } else {
            $query = "SELECT COUNT(*) as total FROM servicios_proveedor 
                      WHERE codigo_servicio = '{$this->codigo_servicio}'";
        }
        
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }

    public function crearServicio() {
        $query = "INSERT INTO " . static::$tabla . " 
                  (rela_proveedor, nombre_servicio, descripcion, codigo_servicio, 
                   categoria_servicio, monto_base, tiene_monto_variable, 
                   frecuencia_pago, activo, fecha_alta) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = self::$db->prepare($query);
        $resultado = $stmt->execute([
            $this->rela_proveedor,
            $this->nombre_servicio,
            $this->descripcion,
            $this->codigo_servicio,
            $this->categoria_servicio,
            $this->monto_base,
            $this->tiene_monto_variable,
            $this->frecuencia_pago,
            $this->activo,
            $this->fecha_alta
        ]);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar() {
        $query = "UPDATE servicios_proveedor SET 
                  rela_proveedor = ?, 
                  nombre_servicio = ?, 
                  descripcion = ?, 
                  codigo_servicio = ?, 
                  categoria_servicio = ?, 
                  monto_base = ?, 
                  tiene_monto_variable = ?, 
                  frecuencia_pago = ?, 
                  activo = ?
                  WHERE id_servicio = ?";
        
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->rela_proveedor,
            $this->nombre_servicio,
            $this->descripcion,
            $this->codigo_servicio,
            $this->categoria_servicio,
            $this->monto_base,
            $this->tiene_monto_variable,
            $this->frecuencia_pago,
            $this->activo,
            $this->id_servicio
        ]);
    }

    public function darDeBaja() {
        $query = "UPDATE servicios_proveedor SET activo = 0 WHERE id_servicio = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([$this->id_servicio]);
    }

    public static function obtenerTodos($paginador = null) {
        $db = self::getDB();
        
        $limit = $paginador ? $paginador->limit() : '';
        
        $query = "SELECT 
                    s.*,
                    p.razon_social,
                    p.nombre_comercial
                  FROM servicios_proveedor s
                  INNER JOIN proveedores p ON s.rela_proveedor = p.id_proveedor
                  ORDER BY s.id_servicio DESC 
                  {$limit}";
        
        $resultado = $db->query($query);
        
        $servicios = [];
        while($row = $resultado->fetch_assoc()) {
            $servicio = new self;
            $servicio->id_servicio = $row['id_servicio'];
            $servicio->rela_proveedor = $row['rela_proveedor'];
            $servicio->nombre_servicio = $row['nombre_servicio'];
            $servicio->descripcion = $row['descripcion'];
            $servicio->codigo_servicio = $row['codigo_servicio'];
            $servicio->categoria_servicio = $row['categoria_servicio'];
            $servicio->monto_base = $row['monto_base'];
            $servicio->tiene_monto_variable = $row['tiene_monto_variable'];
            $servicio->frecuencia_pago = $row['frecuencia_pago'];
            $servicio->activo = $row['activo'];
            $servicio->fecha_alta = $row['fecha_alta'];
            $servicio->razon_social = $row['razon_social'];
            $servicio->nombre_comercial = $row['nombre_comercial'];
            $servicios[] = $servicio;
        }
        return $servicios;
    }

    public static function buscarServicio($termino) {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT 
                    s.*,
                    p.razon_social,
                    p.nombre_comercial
                  FROM servicios_proveedor s
                  INNER JOIN proveedores p ON s.rela_proveedor = p.id_proveedor
                  WHERE s.id_servicio = '$termino' 
                  OR s.nombre_servicio LIKE '%$termino%' 
                  OR s.codigo_servicio = '$termino'
                  LIMIT 1";
        $resultado = self::$db->query($query);
        return ($resultado && $resultado->num_rows) ? $resultado->fetch_assoc() : null;
    }

    public static function obtenerProveedoresActivos() {
        $db = self::getDB();
        $query = "SELECT id_proveedor, razon_social, nombre_comercial 
                  FROM proveedores 
                  WHERE activo = 1 
                  ORDER BY razon_social ASC";
        return $db->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}