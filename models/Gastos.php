<?php

namespace Models;

class Gastos extends ActiveRecord
{
    protected static $tabla = 'egresos';
    protected static $columnasDB = [
        'id_egreso',
        'rela_arqueo_caja',
        'rela_proveedor',
        'rela_servicio',
        'rela_usuario',
        'numero_comprobante',
        'concepto',
        'monto',
        'forma_pago',
        'tipo_egreso',
        'fecha_egreso',
        'fecha_vencimiento',
        'estado_egreso',
        'observaciones',
        'archivo_comprobante'
    ];

    public $id_egreso;
    public $rela_arqueo_caja;
    public $rela_proveedor;
    public $rela_servicio;
    public $rela_usuario;
    public $numero_comprobante;
    public $concepto;
    public $monto;
    public $forma_pago;
    public $tipo_egreso;
    public $fecha_egreso;
    public $fecha_vencimiento;
    public $estado_egreso;
    public $observaciones;
    public $archivo_comprobante;

    public function __construct($args = [])
    {
        $this->id_egreso          = $args['id_egreso'] ?? null;
        $this->rela_arqueo_caja   = $args['rela_arqueo_caja'] ?? null;
        $this->rela_proveedor     = $args['rela_proveedor'] ?? null;
        $this->rela_servicio      = $args['rela_servicio'] ?? null;
        $this->rela_usuario       = $args['rela_usuario'] ?? null;
        $this->numero_comprobante = $args['numero_comprobante'] ?? '';
        $this->concepto           = $args['concepto'] ?? '';
        $this->monto              = $args['monto'] ?? 0;
        $this->forma_pago         = $args['forma_pago'] ?? '';
        $this->tipo_egreso        = $args['tipo_egreso'] ?? '';
        $this->fecha_egreso       = $args['fecha_egreso'] ?? date('Y-m-d H:i:s');
        $this->fecha_vencimiento  = $args['fecha_vencimiento'] ?? null;
        $this->estado_egreso      = $args['estado_egreso'] ?? 'registrado';
        $this->observaciones      = $args['observaciones'] ?? '';
        $this->archivo_comprobante = $args['archivo_comprobante'] ?? null;
    }

    public function validar()
    {
        $errores = [];

        // Validar concepto
        if (!$this->concepto || trim($this->concepto) === '') {
            $errores[] = "El concepto es obligatorio";
        } else {
            $this->concepto = trim($this->concepto);
            if (strlen($this->concepto) < 3) {
                $errores[] = "El concepto debe tener al menos 3 caracteres";
            }
        }

        // Validar monto
        if (!$this->monto || !is_numeric($this->monto)) {
            $errores[] = "El monto debe ser un número válido";
        } else {
            if ($this->monto <= 0) {
                $errores[] = "El monto debe ser mayor a 0";
            }
            if ($this->monto > 9999999.99) {
                $errores[] = "El monto no puede exceder $9,999,999.99";
            }
        }

        // Validar forma de pago (según ENUM)
        $formasPagoValidas = ['efectivo', 'tarjeta', 'transferencia', 'cheque'];
        if (!$this->forma_pago || !in_array($this->forma_pago, $formasPagoValidas)) {
            $errores[] = "La forma de pago no es válida";
        }

        // Validar tipo de egreso (según ENUM)
        $tiposEgresoValidos = ['operativo', 'administrativo', 'comercial'];
        if (!$this->tipo_egreso || !in_array($this->tipo_egreso, $tiposEgresoValidos)) {
            $errores[] = "El tipo de egreso no es válido";
        }

        // Validar estado (según ENUM)
        $estadosValidos = ['registrado', 'aprobado', 'pagado', 'anulado'];
        if (!$this->estado_egreso || !in_array($this->estado_egreso, $estadosValidos)) {
            $errores[] = "El estado del egreso no es válido";
        }

        // Validar fecha de egreso
        if (!$this->fecha_egreso) {
            $errores[] = "La fecha del egreso es obligatoria";
        }

        // Validar número de comprobante (opcional)
        if ($this->numero_comprobante && strlen($this->numero_comprobante) > 50) {
            $errores[] = "El número de comprobante no puede exceder 50 caracteres";
        }

        // Validar relaciones (opcional pero si existen deben ser números)
        if ($this->rela_proveedor !== null && !is_numeric($this->rela_proveedor)) {
            $errores[] = "El ID del proveedor no es válido";
        }

        if ($this->rela_servicio !== null && !is_numeric($this->rela_servicio)) {
            $errores[] = "El ID del servicio no es válido";
        }

        // Limpiar observaciones
        if ($this->observaciones) {
            $this->observaciones = trim($this->observaciones);
        }

        return $errores;
    }

    public function crearEgreso()
    {
        try {
            $db = self::getDB();

            // Log inicial
            error_log("=== INICIO crearEgreso ===");
            error_log("rela_arqueo_caja: " . ($this->rela_arqueo_caja ?? 'NULL'));
            error_log("rela_proveedor: " . ($this->rela_proveedor ?? 'NULL'));
            error_log("rela_servicio: " . ($this->rela_servicio ?? 'NULL'));
            error_log("rela_usuario: " . ($this->rela_usuario ?? 'NULL'));
            error_log("monto: " . $this->monto);

            // Si son NULL, convertir a NULL real de MySQL (no 0)
            $query = "INSERT INTO egresos
            (rela_arqueo_caja, rela_proveedor, rela_servicio, rela_usuario,
            numero_comprobante, concepto, monto, forma_pago, tipo_egreso,
            fecha_egreso, fecha_vencimiento, estado_egreso, observaciones, archivo_comprobante)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);

            if (!$stmt) {
                error_log("❌ Error en prepare(): " . $db->error);
                return [
                    'resultado' => false,
                    'id_insertado' => null,
                    'error' => $db->error
                ];
            }

            error_log("✅ Prepare exitoso");

            // Bind con tipos correctos
            $stmt->bind_param(
                "iiiissdsssssss",
                $this->rela_arqueo_caja,
                $this->rela_proveedor,
                $this->rela_servicio,
                $this->rela_usuario,
                $this->numero_comprobante,
                $this->concepto,
                $this->monto,
                $this->forma_pago,
                $this->tipo_egreso,
                $this->fecha_egreso,
                $this->fecha_vencimiento,
                $this->estado_egreso,
                $this->observaciones,
                $this->archivo_comprobante
            );

            error_log("✅ Bind exitoso");

            $resultado = $stmt->execute();

            if (!$resultado) {
                error_log("❌ Error en execute(): " . $stmt->error);
                error_log("❌ Error number: " . $stmt->errno);
                return [
                    'resultado' => false,
                    'id_insertado' => null,
                    'error' => $stmt->error
                ];
            }

            error_log("✅ Execute exitoso - ID insertado: " . $db->insert_id);

            return [
                'resultado' => true,
                'id_insertado' => $db->insert_id
            ];
        } catch (\Exception $e) {
            error_log("❌ Exception en crearEgreso: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'resultado' => false,
                'id_insertado' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    public function actualizar()
    {
        $query = "UPDATE " . static::$tabla . " SET
        rela_arqueo_caja = ?,
        rela_proveedor = ?,
        rela_servicio = ?,
        rela_usuario = ?,
        numero_comprobante = ?,
        concepto = ?,
        monto = ?,
        forma_pago = ?,
        tipo_egreso = ?,
        fecha_egreso = ?,
        fecha_vencimiento = ?,
        estado_egreso = ?,
        observaciones = ?,
        archivo_comprobante = ?
        WHERE id_egreso = ?";

        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->rela_arqueo_caja,
            $this->rela_proveedor,
            $this->rela_servicio,
            $this->rela_usuario,
            $this->numero_comprobante,
            $this->concepto,
            $this->monto,
            $this->forma_pago,
            $this->tipo_egreso,
            $this->fecha_egreso,
            $this->fecha_vencimiento,
            $this->estado_egreso,
            $this->observaciones,
            $this->archivo_comprobante,
            $this->id_egreso
        ]);
    }

    public function darDeBaja()
    {
        $query = "UPDATE " . static::$tabla . " 
              SET estado_egreso = 'anulado'
              WHERE id_egreso = ?";

        $stmt = self::$db->prepare($query);
        return $stmt->execute([$this->id_egreso]);
    }

    /**
     * Contar total de registros (para paginación)
     */
    public static function contarTotal()
    {
        $db = self::getDB();
        $query = "SELECT COUNT(*) as total FROM egresos";
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        return (int)$row['total'];
    }

    public static function obtenerTodos($paginador = null)
    {
        $db = self::getDB();
        $limit = $paginador ? $paginador->limit() : '';

        $query = "SELECT * FROM egresos ORDER BY fecha_egreso DESC, id_egreso DESC {$limit}";
        $resultado = $db->query($query);

        $egresos = [];
        while ($row = $resultado->fetch_assoc()) {
            $egreso = new self($row);
            $egresos[] = $egreso;
        }

        return $egresos;
    }

    public static function buscarEgreso($termino)
    {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT * FROM egresos 
              WHERE concepto LIKE '%{$termino}%' 
              OR numero_comprobante LIKE '%{$termino}%'
              OR id_egreso = '{$termino}'
              ORDER BY id_egreso DESC";

        $resultado = self::$db->query($query);
        $gastos = [];
        while ($row = $resultado->fetch_assoc()) {
            $gastos[] = new self($row);
        }
        return $gastos;
    }

    public static function obtenerPorEstado($estado, $paginador = null)
    {
        $db = self::getDB();
        $estado = $db->escape_string($estado);
        $limit = $paginador ? $paginador->limit() : '';

        $query = "SELECT * FROM egresos 
                  WHERE estado_egreso = '$estado' 
                  ORDER BY fecha_egreso DESC 
                  {$limit}";

        $resultado = $db->query($query);

        $egresos = [];
        while ($row = $resultado->fetch_assoc()) {
            $egresos[] = new self($row);
        }

        return $egresos;
    }

    public static function obtenerPorRangoFechas($fecha_inicio, $fecha_fin)
    {
        $db = self::getDB();
        $fecha_inicio = $db->escape_string($fecha_inicio);
        $fecha_fin = $db->escape_string($fecha_fin);

        $query = "SELECT * FROM egresos 
                  WHERE fecha_egreso BETWEEN '$fecha_inicio' AND '$fecha_fin'
                  ORDER BY fecha_egreso DESC";

        $resultado = $db->query($query);

        $egresos = [];
        while ($row = $resultado->fetch_assoc()) {
            $egresos[] = new self($row);
        }

        return $egresos;
    }

    public static function obtenerTotalPorTipo($tipo, $fecha_inicio = null, $fecha_fin = null)
    {
        $db = self::getDB();
        $tipo = $db->escape_string($tipo);

        $query = "SELECT SUM(monto) as total FROM egresos 
                  WHERE tipo_egreso = '$tipo' AND estado_egreso != 'anulado'";

        if ($fecha_inicio && $fecha_fin) {
            $fecha_inicio = $db->escape_string($fecha_inicio);
            $fecha_fin = $db->escape_string($fecha_fin);
            $query .= " AND fecha_egreso BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] ?? 0;
    }
}
