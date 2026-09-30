<?php

namespace Models;


class Entrada extends ActiveRecord
{
    protected static $table = 'entradas';
    protected static $columnasDB = ['id_entrada', 'numero_ticket_entrada', 'rela_tipo_entrada', 'rela_funcion', 'estado'];

    public $id_entrada;
    public $numero_ticket_entrada;
    public $rela_tipo_entrada;
    public $rela_funcion;
    public $estado;


    public $tipo_entrada_desc;
    public $precio_entrada;
    public $titulo_pelicula;
    public $id_sala;
    public $id_funcion;
    public $fecha_hora;
    public $fecha_finalizacion;
    public $numero_comprobante;
    public $punto_venta;
    public $numero_caja;
    public $forma_pago;
    public $vendedor;
    public $monto_venta;
    public $pago_fecha_hora;
    public $numero_ticket;
    public $tipo_comprobante;
    public $numero_orden; //
    // CONSTANTES para estados
    const ESTADO_ACTIVA = 1;
    const ESTADO_USADA = 2;
    const ESTADO_EXPIRADA = 0;
    const ESTADO_CANCELADA = -1;

    public function __construct($args = [])
    {
        $this->id_entrada = $args['id_entrada'] ?? '';
        $this->numero_ticket_entrada = $args['numero_ticket_entrada'] ?? '';
        $this->rela_tipo_entrada = $args['rela_tipo_entrada'] ?? '';
        $this->rela_funcion = $args['rela_funcion'] ?? '';
        $this->estado = $args['estado'] ?? self::ESTADO_ACTIVA;
    }

    public function validar()
    {
        $errores = [];

        if (!$this->numero_ticket_entrada) {
            $errores['numero_ticket_entrada'] = 'El número de ticket es obligatorio';
        }

        if (!$this->rela_tipo_entrada) {
            $errores['rela_tipo_entrada'] = 'El tipo de entrada es obligatorio';
        }

        if (!$this->rela_funcion) {
            $errores['rela_funcion'] = 'La función es obligatoria';
        }

        // Validar que el número de ticket no esté duplicado
        if ($this->numero_ticket_entrada && $this->existeTicket($this->numero_ticket_entrada, $this->id_entrada)) {
            $errores['numero_ticket_entrada'] = 'El número de ticket ya existe';
        }

        return $errores;
    }

    public static function obtenerTodas($incluir_inactivas = false)
    {
        $db = self::getDB();

        $where_estado = $incluir_inactivas ? "" : " AND e.estado >= 0";

        $query = "SELECT 
                e.*, 
                te.tipo_entrada_desc, 
                te.precio_entrada,
                f.fecha_hora,
                f.fecha_finalizacion,
                p.titulo_pelicula,
                s.id_sala,
                -- CAMPOS DEL TICKET PRESENCIAL
                cf.numero_comprobante,
                cf.tipo_comprobante,
                cf.punto_venta,
                cf.numero_ticket,
                cf.pago_fecha_hora,
                cf.monto_total as monto_venta,
                u.nombre_usuario as vendedor,
                tp.descripcion_tipo_pago as forma_pago,
                c.numero_caja,
                -- CAMPO DE LA VENTA WEB (equivalente para entradas compradas online)
                o.numero_orden
            FROM entradas e
            INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
            INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
            INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
            INNER JOIN salas s ON f.rela_salas = s.id_sala
            LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
            LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
            LEFT JOIN usuarios u ON cf.rela_usuario_vendedor = u.id_usuario
            LEFT JOIN tipos_de_pagos tp ON cf.rela_tipos_de_pagos = tp.id_tipo_pago
            LEFT JOIN arqueo_cajas ac ON cf.rela_arqueo_caja = ac.id_arqueo_caja
            LEFT JOIN cajas c ON ac.rela_caja = c.id_caja
            LEFT JOIN butacas_vendidas bv ON bv.id_entrada = e.id_entrada
            LEFT JOIN ordenes o ON o.id_orden = bv.id_orden
            WHERE 1=1 $where_estado
            ORDER BY e.id_entrada DESC";

        $resultado = $db->query($query);
        $entradas = [];

        while ($row = $resultado->fetch_assoc()) {
            $entrada = new self;
            $entrada->id_entrada = $row['id_entrada'];
            $entrada->rela_tipo_entrada = $row['rela_tipo_entrada'];
            $entrada->rela_funcion = $row['rela_funcion'];
            $entrada->estado = $row['estado'];
            $entrada->tipo_entrada_desc = $row['tipo_entrada_desc'];
            $entrada->precio_entrada = $row['precio_entrada'];
            $entrada->titulo_pelicula = $row['titulo_pelicula'];
            $entrada->id_sala = $row['id_sala'];
            $entrada->fecha_hora = $row['fecha_hora'];
            $entrada->fecha_finalizacion = $row['fecha_finalizacion'];
            $entrada->numero_comprobante = $row['numero_comprobante'] ?? 'N/A';
            $entrada->tipo_comprobante = $row['tipo_comprobante'] ?? 'N/A';
            $entrada->punto_venta = $row['punto_venta'] ?? null;
            $entrada->numero_ticket = $row['numero_ticket'] ?? null;
            $entrada->pago_fecha_hora = $row['pago_fecha_hora'] ?? null;
            $entrada->monto_venta = $row['monto_venta'] ?? null;
            $entrada->vendedor = $row['vendedor'] ?? 'N/A';
            $entrada->forma_pago = $row['forma_pago'] ?? 'N/A';
            $entrada->numero_caja = $row['numero_caja'] ?? 'N/A';
            $entrada->numero_orden = $row['numero_orden'] ?? null; // NUEVO

            $entradas[] = $entrada;
        }

        return $entradas;
    }

    public function crearEntrada()
    {
        // Generar número de ticket automáticamente si no se proporciona
        if (!$this->numero_ticket_entrada) {
            $this->numero_ticket_entrada = $this->generarNumeroTicket();
        }

        $db = self::getDB();

        $query = "INSERT INTO " . static::$table . " (numero_ticket_entrada, rela_tipo_entrada, rela_funcion, estado)
                  VALUES (?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        if (!$stmt) {
            return [
                'resultado' => false,
                'error' => 'Error al preparar la consulta: ' . $db->error
            ];
        }

        $stmt->bind_param('iiii', $this->numero_ticket_entrada, $this->rela_tipo_entrada, $this->rela_funcion, $this->estado);
        $resultado = $stmt->execute();

        if ($resultado) {
            return [
                'resultado' => true,
                'id_insertado' => $db->insert_id
            ];
        } else {
            return [
                'resultado' => false,
                'error' => 'Error al ejecutar la consulta: ' . $stmt->error
            ];
        }
    }

    // NUEVO MÉTODO: Expirar entradas automáticamente
    public static function expirarEntradasVencidas()
    {
        $db = self::getDB();

        $query = "UPDATE entradas e 
              INNER JOIN funciones f ON e.rela_funcion = f.id_funcion 
              INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
              INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
              SET e.estado = ? 
              WHERE e.estado = ? 
              AND DATE_ADD(TIMESTAMP(f.fecha_hora, t.turno_horario), INTERVAL p.duracion_pelicula MINUTE) < NOW()";

        $stmt = $db->prepare($query);

        $estadoExpirada = self::ESTADO_EXPIRADA;
        $estadoActiva = self::ESTADO_ACTIVA;
        $stmt->bind_param('ii', $estadoExpirada, $estadoActiva);

        $resultado = $stmt->execute();

        if ($resultado) {
            return $stmt->affected_rows;
        }

        return false;
    }

    // NUEVO MÉTODO: Marcar entrada como usada
    public function marcarComoUsada()
    {
        $this->estado = self::ESTADO_USADA;
        return $this->actualizar();
    }

    // MODIFICADO: Eliminar lógicamente
    public function eliminar()
    {
        $this->estado = self::ESTADO_CANCELADA;
        return $this->actualizar();
    }

    // NUEVO MÉTODO: Restaurar entrada cancelada
    public function restaurar()
    {
        $this->estado = self::ESTADO_ACTIVA;
        return $this->actualizar();
    }

    // NUEVO MÉTODO: Verificar si la entrada es válida para uso
    public function esValida()
    {
        if ($this->estado !== self::ESTADO_ACTIVA) {
            return false;
        }

        $db = self::getDB();
        $query = "SELECT f.fecha_hora, t.turno_horario, p.duracion_pelicula
              FROM funciones f
              INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
              INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
              WHERE f.id_funcion = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $this->rela_funcion);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($row = $resultado->fetch_assoc()) {
            $inicio = strtotime($row['fecha_hora'] . ' ' . $row['turno_horario']);
            $duracionMinutos = (int) ($row['duracion_pelicula'] ?? 0);
            $finReal = $inicio + ($duracionMinutos * 60);
            return $finReal > time();
        }

        return false;
    }

    public static function find($id)
    {
        $db = self::getDB();
        $query = "SELECT * FROM " . static::$table . " WHERE id_entrada = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($row = $resultado->fetch_assoc()) {
            $entrada = new self();
            $entrada->id_entrada = $row['id_entrada'];
            $entrada->numero_ticket_entrada = null;
            $entrada->rela_tipo_entrada = $row['rela_tipo_entrada'];
            $entrada->rela_funcion = $row['rela_funcion'];
            $entrada->estado = $row['estado'] ?? self::ESTADO_ACTIVA;
            return $entrada;
        }

        return null;
    }

    private function generarNumeroTicket()
    {
        $numero = rand(100000, 999999);

        while ($this->existeTicket($numero)) {
            $numero = rand(100000, 999999);
        }

        return $numero;
    }

    private function existeTicket($numero, $id_excluir = null)
    {
        $db = self::getDB();
        $query = "SELECT id_entrada FROM " . static::$table . " WHERE numero_ticket_entrada = ? AND estado >= 0";

        if ($id_excluir) {
            $query .= " AND id_entrada != ?";
        }

        $stmt = $db->prepare($query);

        if ($id_excluir) {
            $stmt->bind_param('ii', $numero, $id_excluir);
        } else {
            $stmt->bind_param('i', $numero);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }

    public function actualizar()
    {
        $db = self::getDB();
        $query = "UPDATE " . static::$table . " SET 
              rela_tipo_entrada = ?, 
              rela_funcion = ?,
              estado = ?
              WHERE id_entrada = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            'iiii',
            $this->rela_tipo_entrada,
            $this->rela_funcion,
            $this->estado,
            $this->id_entrada
        );

        return $stmt->execute();
    }



    public static function buscarEntrada($termino)
    {
        $db = self::getDB();
        $termino = $db->real_escape_string($termino);

        $query = "SELECT 
                    e.id_entrada,
                    e.rela_tipo_entrada,
                    e.rela_funcion,
                    e.estado,
                    te.tipo_entrada_desc, 
                    te.precio_entrada,
                    p.titulo_pelicula, 
                    s.id_sala,
                    f.fecha_hora
                FROM entradas e
                INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
                INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
                INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                INNER JOIN salas s ON f.rela_salas = s.id_sala
                WHERE (
                    e.id_entrada = '$termino' 
                    OR p.titulo_pelicula LIKE '%$termino%'
                    OR DATE(f.fecha_hora) = '$termino'
                ) AND e.estado >= 0 
                LIMIT 1";

        $resultado = $db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public static function obtenerComprasWebPorUsuario($idUsuario)
    {
        $db = self::getDB();

        $query = "SELECT DISTINCT
                e.id_entrada,
                e.numero_ticket_entrada,
                e.estado,
                te.tipo_entrada_desc,
                te.precio_entrada,
                f.id_funcion,
                TIMESTAMP(f.fecha_hora, t.turno_horario) as fecha_hora,
                f.fecha_finalizacion,
                p.titulo_pelicula,
                p.imagen_pelicula,
                s.id_sala,
                o.id_orden,
                o.total,
                bv.fecha_venta,
                CASE 
                    WHEN e.estado = -1 THEN 'cancelada'
                    WHEN e.estado = 2 THEN 'usada'
                    WHEN TIMESTAMP(f.fecha_hora, t.turno_horario) > NOW() THEN 'vigente'
                    ELSE 'vencida'
                END as estado_vigencia
              FROM butacas_vendidas bv
              INNER JOIN entradas e ON bv.id_entrada = e.id_entrada
              INNER JOIN ordenes o ON bv.id_orden = o.id_orden
              INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
              INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
              INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
              INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
              INNER JOIN salas s ON f.rela_salas = s.id_sala
              WHERE o.id_usuario = ?
              ORDER BY TIMESTAMP(f.fecha_hora, t.turno_horario) DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $entradas = [];
        while ($row = $resultado->fetch_assoc()) {
            $entradas[] = $row;
        }

        return $entradas;
    }
}
