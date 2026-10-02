<?php

namespace Models;

class ArqueoCaja extends ActiveRecord
{

    protected static $tabla = 'arqueo_cajas';
    protected static $columnasDB = [
        'id_arqueo_caja',
        'rela_usuario',
        'fecha_inicio',
        'fecha_fin',
        'monto_inicial',
        'total_ventas',
        'estado_arqueo',
        'monto_final',
        'rela_caja'
    ];

    public $id_arqueo_caja;
    public $rela_usuario;
    public $fecha_inicio;
    public $fecha_fin;
    public $monto_inicial;
    public $total_ventas;
    public $estado_arqueo;
    public $monto_final;
    public $rela_caja;

    // Propiedades adicionales para vistas
    public $nombre_usuario;
    public $nombre_caja;
    public $numero_caja;
    public $codigo_caja;
    public $id_caja;
    public function __construct($args = [])
    {
        $this->id_arqueo_caja = $args['id_arqueo_caja'] ?? null;
        $this->rela_usuario = $args['rela_usuario'] ?? '';
        $this->fecha_inicio = $args['fecha_inicio'] ?? date('Y-m-d H:i:s');
        $this->fecha_fin = $args['fecha_fin'] ?? null;
        $this->monto_inicial = $args['monto_inicial'] ?? 0;
        $this->total_ventas = $args['total_ventas'] ?? 0;
        $this->estado_arqueo = $args['estado_arqueo'] ?? 'abierto';
        $this->monto_final = $args['monto_final'] ?? 0;
        $this->rela_caja = $args['rela_caja'] ?? '';

        $this->nombre_usuario = $args['nombre_usuario'] ?? '';
        $this->nombre_caja = $args['nombre_caja'] ?? '';
        $this->numero_caja = $args['numero_caja'] ?? 0;
        $this->codigo_caja = $args['codigo_caja'] ?? '';
        $this->id_caja = $args['id_caja'] ?? null;
    }


    public static function obtenerArqueoAbiertoUsuario($idUsuario)
    {
        $db = self::getDB();
        $query = "SELECT ac.*, 
                u.nombre_usuario, 
                c.id_caja,
                c.nombre_caja, 
                c.numero_caja,
                c.codigo_caja,      
                c.folio
                FROM arqueo_cajas ac
                INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                INNER JOIN cajas c ON ac.rela_caja = c.id_caja
                WHERE ac.rela_usuario = ? 
                AND ac.estado_arqueo = 'abierto'
                LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($row = $resultado->fetch_assoc()) {
            return new self($row);
        }

        return null;
    }
    public function abrir()
    {
        $db = self::getDB();

        $arqueoAbierto = self::obtenerArqueoAbiertoUsuario($this->rela_usuario);
        if ($arqueoAbierto) {
            return [
                'resultado' => false,
                'mensaje' => 'Ya tienes una caja abierta'
            ];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idPerfil = $_SESSION['perfil'] ?? null;

        $queryCaja = "SELECT numero_caja FROM cajas WHERE id_caja = ?";
        $stmt = $db->prepare($queryCaja);
        $stmt->bind_param("i", $this->rela_caja);
        $stmt->execute();
        $resultCaja = $stmt->get_result();
        $caja = $resultCaja->fetch_assoc();
        $numeroCaja = $caja['numero_caja'] ?? 0;

        if ($idPerfil == 4 && !in_array($numeroCaja, [1, 2])) {
            return [
                'resultado' => false,
                'mensaje' => 'No tienes permisos para abrir esta caja'
            ];
        }

        if ($idPerfil == 5 && !in_array($numeroCaja, [3, 4])) {
            return [
                'resultado' => false,
                'mensaje' => 'No tienes permisos para abrir esta caja'
            ];
        }

        $query = "INSERT INTO arqueo_cajas 
                (rela_usuario, fecha_inicio, monto_inicial, estado_arqueo, rela_caja)
                VALUES (?, ?, ?, 'abierto', ?)";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            "isdi",
            $this->rela_usuario,
            $this->fecha_inicio,
            $this->monto_inicial,
            $this->rela_caja
        );

        $resultado = $stmt->execute();

        return [
            'resultado' => $resultado,
            'id_insertado' => $db->insert_id,
            'mensaje' => $resultado ? 'Caja abierta correctamente' : 'Error al abrir caja'
        ];
    }


    public function cerrar()
    {
        $db = self::getDB();

        $this->fecha_fin = date('Y-m-d H:i:s');
        $this->estado_arqueo = 'cerrado';

        // Calcular monto final
        $this->calcularMontoFinal();

        $query = "UPDATE arqueo_cajas 
                  SET fecha_fin = ?, 
                      estado_arqueo = 'cerrado',
                      monto_final = ?
                  WHERE id_arqueo_caja = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            "sdi",
            $this->fecha_fin,
            $this->monto_final,
            $this->id_arqueo_caja
        );

        return $stmt->execute();
    }

    /**
     * Calcular monto final
     */
    private function calcularMontoFinal()
    {
        $db = self::getDB();

        // Sumar ingresos y restar egresos
        $query = "SELECT 
                    SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                    SUM(CASE WHEN tipo_movimiento = 'egreso' THEN monto ELSE 0 END) as egresos
                  FROM movimientos_caja
                  WHERE rela_arqueo_caja = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $this->id_arqueo_caja);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();

        $ingresos = $row['ingresos'] ?? 0;
        $egresos = $row['egresos'] ?? 0;

        $this->monto_final = $this->monto_inicial + $ingresos - $egresos;
    }

    /**
     * Actualizar total de ventas
     */
    public function actualizarTotalVentas()
    {
        $db = self::getDB();

        $query = "SELECT COALESCE(SUM(monto_total), 0) as total
                  FROM cabecera_fact_cine
                  WHERE rela_arqueo_caja = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $this->id_arqueo_caja);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();

        $this->total_ventas = $row['total'];

        $updateQuery = "UPDATE arqueo_cajas SET total_ventas = ? WHERE id_arqueo_caja = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param("di", $this->total_ventas, $this->id_arqueo_caja);

        return $stmt->execute();
    }

    public function validar()
    {
        $alertas = [];

        if (!$this->rela_usuario) {
            $alertas[] = "El usuario es obligatorio";
        }

        if (!$this->rela_caja) {
            $alertas[] = "La caja es obligatoria";
        }

        if ($this->monto_inicial < 0) {
            $alertas[] = "El monto inicial no puede ser negativo";
        }

        return $alertas;
    }

    public static function verificarCajaEnUso($idCaja)
    {
        $db = self::getDB();
        $query = "SELECT ac.*, u.nombre_usuario
                FROM arqueo_cajas ac
                INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
                WHERE ac.rela_caja = ? 
                AND ac.estado_arqueo = 'abierto'
                LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idCaja);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($row = $resultado->fetch_assoc()) {
            return $row;
        }

        return null;
    }

    public static function obtenerCualquierCajaAbierta()
    {
        $db = self::getDB();
        $query = "SELECT ac.*, 
            u.nombre_usuario, 
            c.id_caja,
            c.nombre_caja, 
            c.numero_caja,
            c.codigo_caja,      
            c.folio
            FROM arqueo_cajas ac
            INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
            INNER JOIN cajas c ON ac.rela_caja = c.id_caja
            WHERE ac.estado_arqueo = 'abierto'
            ORDER BY ac.fecha_inicio DESC
            LIMIT 1";

        $resultado = $db->query($query);

        if ($row = $resultado->fetch_assoc()) {
            return new self($row);
        }

        return null;
    }

    public static function obtenerTodasCajasAbiertas()
    {
        $db = self::getDB();
        $query = "SELECT ac.*, 
            u.nombre_usuario, 
            c.id_caja,
            c.nombre_caja, 
            c.numero_caja,
            c.codigo_caja,      
            c.folio
            FROM arqueo_cajas ac
            INNER JOIN usuarios u ON ac.rela_usuario = u.id_usuario
            INNER JOIN cajas c ON ac.rela_caja = c.id_caja
            WHERE ac.estado_arqueo = 'abierto'
            ORDER BY c.numero_caja ASC";

        $resultado = $db->query($query);

        $cajas = [];
        while ($row = $resultado->fetch_assoc()) {
            $cajas[] = new self($row);
        }

        return $cajas;
    }

    // Nombre legible de una caja (ej. "Caja Principal Funciones") para el registro de auditoría
    public static function nombreCaja($idCaja): string
    {
        $stmt = self::$db->prepare("SELECT nombre_caja FROM cajas WHERE id_caja = ?");
        $idCaja = (int)$idCaja;
        $stmt->bind_param('i', $idCaja);
        $stmt->execute();
        return $stmt->get_result()->fetch_column() ?: "Caja #$idCaja";
    }
}
