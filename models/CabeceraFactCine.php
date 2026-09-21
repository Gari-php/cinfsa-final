<?php 
namespace Models;

class CabeceraFactCine extends ActiveRecord {
    
    protected static $tabla = 'cabecera_fact_cine';
    protected static $columnasDB = [
        'id_pagos',
        'pago_fecha_hora',
        'monto_total',
        'rela_tipos_de_pagos',
        'rela_arqueo_caja',
        'rela_usuario_vendedor',
        'numero_comprobante',
        'observaciones'
    ];

    public $id_pagos;
    public $pago_fecha_hora;
    public $monto_total;
    public $rela_tipos_de_pagos;
    public $rela_arqueo_caja;
    public $rela_usuario_vendedor;
    public $numero_comprobante;
    public $observaciones;

    public function __construct($args = []) {
        $this->id_pagos = $args['id_pagos'] ?? null;
        $this->pago_fecha_hora = $args['pago_fecha_hora'] ?? date('Y-m-d H:i:s');
        $this->monto_total = $args['monto_total'] ?? 0;
        $this->rela_tipos_de_pagos = $args['rela_tipos_de_pagos'] ?? '';
        $this->rela_arqueo_caja = $args['rela_arqueo_caja'] ?? '';
        $this->rela_usuario_vendedor = $args['rela_usuario_vendedor'] ?? '';
        $this->numero_comprobante = $args['numero_comprobante'] ?? '';
        $this->observaciones = $args['observaciones'] ?? '';
    }

    /**
     * Crear venta
     */
    public function crear() {
        $db = self::getDB();
        
        // Generar número de comprobante si no existe
        if (empty($this->numero_comprobante)) {
            $this->numero_comprobante = $this->generarNumeroComprobante();
        }
        
        $query = "INSERT INTO cabecera_fact_cine 
                  (pago_fecha_hora, monto_total, rela_tipos_de_pagos, rela_arqueo_caja, rela_usuario_vendedor, numero_comprobante, observaciones)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("sdiiiis", 
            $this->pago_fecha_hora,
            $this->monto_total,
            $this->rela_tipos_de_pagos,
            $this->rela_arqueo_caja,
            $this->rela_usuario_vendedor,
            $this->numero_comprobante,
            $this->observaciones
        );
        
        $resultado = $stmt->execute();
        
        return [
            'resultado' => $resultado,
            'id_insertado' => $db->insert_id
        ];
    }

    /**
     * Generar número de comprobante único
     */
    private function generarNumeroComprobante() {
        return 'VC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function validar() {
        $alertas = [];
        
        if ($this->monto_total <= 0) {
            $alertas[] = "El monto total debe ser mayor a 0";
        }
        
        if (!$this->rela_tipos_de_pagos) {
            $alertas[] = "El tipo de pago es obligatorio";
        }
        
        if (!$this->rela_arqueo_caja) {
            $alertas[] = "El arqueo de caja es obligatorio";
        }
        
        if (!$this->rela_usuario_vendedor) {
            $alertas[] = "El vendedor es obligatorio";
        }
        
        return $alertas;
    }
}