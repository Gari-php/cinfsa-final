<?php
namespace Models;

class DetalleFactCine extends ActiveRecord {
    
    protected static $tabla = 'detalle_fact_cine';
    protected static $columnasDB = [
        'id_detalle',
        'rela_cabecera_fact',
        'rela_entrada',
        'precio_venta',
        'fecha_venta'
    ];

    public $id_detalle;
    public $rela_cabecera_fact;
    public $rela_entrada;
    public $precio_venta;
    public $fecha_venta;

    public function __construct($args = []) {
        $this->id_detalle = $args['id_detalle'] ?? null;
        $this->rela_cabecera_fact = $args['rela_cabecera_fact'] ?? '';
        $this->rela_entrada = $args['rela_entrada'] ?? '';
        $this->precio_venta = $args['precio_venta'] ?? 0;
        $this->fecha_venta = $args['fecha_venta'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Crear detalle de venta
     */
    public function crear() {
        $db = self::getDB();
        
        $query = "INSERT INTO detalle_fact_cine 
                  (rela_cabecera_fact, rela_entrada, precio_venta, fecha_venta)
                  VALUES (?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("iids", 
            $this->rela_cabecera_fact,
            $this->rela_entrada,
            $this->precio_venta,
            $this->fecha_venta
        );
        
        $resultado = $stmt->execute();
        
        return [
            'resultado' => $resultado,
            'id_insertado' => $db->insert_id
        ];
    }

    public function validar() {
        $alertas = [];
        
        if (!$this->rela_cabecera_fact) {
            $alertas[] = "La cabecera es obligatoria";
        }
        
        if (!$this->rela_entrada) {
            $alertas[] = "La entrada es obligatoria";
        }
        
        if ($this->precio_venta <= 0) {
            $alertas[] = "El precio debe ser mayor a 0";
        }
        
        return $alertas;
    }
}