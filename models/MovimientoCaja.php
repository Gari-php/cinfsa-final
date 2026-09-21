<?php
namespace Models;

class MovimientoCaja extends ActiveRecord {
    
    protected static $tabla = 'movimientos_caja';
    protected static $columnasDB = [
        'id_movimiento_caja',
        'rela_arqueo_caja',
        'tipo_movimiento',
        'concepto_movimiento',
        'monto',
        'fecha_movimiento',
        'forma_pago'
    ];

    public $id_movimiento_caja;
    public $rela_arqueo_caja;
    public $tipo_movimiento;
    public $concepto_movimiento;
    public $monto;
    public $fecha_movimiento;
    public $forma_pago;

    public function __construct($args = []) {
        $this->id_movimiento_caja = $args['id_movimiento_caja'] ?? null;
        $this->rela_arqueo_caja = $args['rela_arqueo_caja'] ?? '';
        $this->tipo_movimiento = $args['tipo_movimiento'] ?? 'ingreso';
        $this->concepto_movimiento = $args['concepto_movimiento'] ?? '';
        $this->monto = $args['monto'] ?? 0;
        $this->fecha_movimiento = $args['fecha_movimiento'] ?? date('Y-m-d H:i:s');
        $this->forma_pago = $args['forma_pago'] ?? 'efectivo';
    }

    /**
     * Registrar movimiento
     */
    public function registrar() {
        $db = self::getDB();
        
        $query = "INSERT INTO movimientos_caja 
                  (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, monto, fecha_movimiento, forma_pago)
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("issdss", 
            $this->rela_arqueo_caja,
            $this->tipo_movimiento,
            $this->concepto_movimiento,
            $this->monto,
            $this->fecha_movimiento,
            $this->forma_pago
        );
        
        $resultado = $stmt->execute();
        
        return [
            'resultado' => $resultado,
            'id_insertado' => $db->insert_id
        ];
    }

    /**
     * Obtener movimientos de un arqueo
     */
    public static function obtenerPorArqueo($idArqueo) {
        $db = self::getDB();
        
        $query = "SELECT * FROM movimientos_caja 
                  WHERE rela_arqueo_caja = ?
                  ORDER BY fecha_movimiento DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idArqueo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $movimientos = [];
        while ($row = $resultado->fetch_assoc()) {
            $movimientos[] = new self($row);
        }
        
        return $movimientos;
    }

    public function validar() {
        $alertas = [];
        
        if (!$this->rela_arqueo_caja) {
            $alertas[] = "El arqueo de caja es obligatorio";
        }
        
        if (!in_array($this->tipo_movimiento, ['ingreso', 'egreso'])) {
            $alertas[] = "Tipo de movimiento inválido";
        }
        
        if (empty(trim($this->concepto_movimiento))) {
            $alertas[] = "El concepto es obligatorio";
        }
        
        if ($this->monto <= 0) {
            $alertas[] = "El monto debe ser mayor a 0";
        }
        
        if (!in_array($this->forma_pago, ['efectivo', 'tarjeta', 'transferencia', 'otro'])) {
            $alertas[] = "Forma de pago inválida";
        }
        
        return $alertas;
    }
}

