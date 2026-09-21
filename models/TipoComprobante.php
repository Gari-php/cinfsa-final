<?php
namespace Models;

class TipoComprobante extends ActiveRecord {
    protected static $tabla = 'tipos_comprobantes';
    protected static $columnasDB = [
        'id_tipo_comprobante', 'codigo', 'descripcion', 'descripcion_corta',
        'valido_afip', 'requiere_cuit', 'requiere_datos_facturacion', 
        'activo', 'orden'
    ];

    public $id_tipo_comprobante;
    public $codigo;
    public $descripcion;
    public $descripcion_corta;
    public $valido_afip;
    public $requiere_cuit;
    public $requiere_datos_facturacion;
    public $activo;
    public $orden;

    public function __construct($args = []) {
        $this->id_tipo_comprobante = $args['id_tipo_comprobante'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->descripcion_corta = $args['descripcion_corta'] ?? '';
        $this->valido_afip = $args['valido_afip'] ?? 0;
        $this->requiere_cuit = $args['requiere_cuit'] ?? 0;
        $this->requiere_datos_facturacion = $args['requiere_datos_facturacion'] ?? 0;
        $this->activo = $args['activo'] ?? 1;
        $this->orden = $args['orden'] ?? 0;
    }

    public static function obtenerActivos() {
        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE activo = 1 
                  ORDER BY orden ASC";
        
        $resultado = self::$db->query($query);
        $tipos = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $tipo = new self($row);
            $tipos[] = $tipo;
        }
        
        return $tipos;
    }

    public static function obtenerPorCodigo($codigo) {
        $codigo = self::$db->escape_string($codigo);
        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE codigo = '{$codigo}' AND activo = 1 
                  LIMIT 1";
        
        $resultado = self::$db->query($query);
        
        if ($resultado && $resultado->num_rows > 0) {
            return new self($resultado->fetch_assoc());
        }
        
        return null;
    }
}