<?php
namespace Models;

class Butaca extends ActiveRecord {
    protected static $tabla = 'butacas';
    protected static $columnasDB = ['id_butaca', 'fila_butaca', 'numero_butaca', 'rela_salas', 'rela_estado_butaca'];

    public $id_butaca;
    public $fila_butaca;
    public $numero_butaca;
    public $rela_salas;
    public $rela_estado_butaca;
    
    // Propiedades adicionales para vista
    public $nombre_estado;
    public $capacidad_sala;
    public $filas_sala;
    public $columnas_sala;

    public function __construct($args = []) {
        $this->id_butaca = $args['id_butaca'] ?? null;
        $this->fila_butaca = $args['fila_butaca'] ?? '';
        $this->numero_butaca = $args['numero_butaca'] ?? '';
        $this->rela_salas = $args['rela_salas'] ?? '';
        $this->rela_estado_butaca = $args['rela_estado_butaca'] ?? 1; // Por defecto disponible
        
        // Propiedades adicionales
        $this->nombre_estado = $args['nombre_estado'] ?? '';
        $this->capacidad_sala = $args['capacidad_sala'] ?? '';
        $this->filas_sala = $args['filas_sala'] ?? '';
        $this->columnas_sala = $args['columnas_sala'] ?? '';
    }

    public function validar() {
        $errores = [];
        
        // Validar fila
        if (!$this->fila_butaca || trim($this->fila_butaca) === '') {
            $errores[] = 'La fila es obligatoria';
        } else {
            $this->fila_butaca = trim($this->fila_butaca);
            
            if (!is_numeric($this->fila_butaca)) {
                $errores[] = 'La fila debe ser un número válido';
            } else {
                $fila = intval($this->fila_butaca);
                if ($fila <= 0) {
                    $errores[] = 'La fila debe ser mayor a 0';
                }
                if ($fila > 30) {
                    $errores[] = 'La fila no puede ser mayor a 30';
                }
            }
        }
        
        // Validar número de butaca
        if (!$this->numero_butaca || trim($this->numero_butaca) === '') {
            $errores[] = 'El número de butaca es obligatorio';
        } else {
            $this->numero_butaca = trim($this->numero_butaca);
            
            if (!is_numeric($this->numero_butaca)) {
                $errores[] = 'El número de butaca debe ser un número válido';
            } else {
                $numero = intval($this->numero_butaca);
                if ($numero <= 0) {
                    $errores[] = 'El número de butaca debe ser mayor a 0';
                }
                if ($numero > 25) {
                    $errores[] = 'El número de butaca no puede ser mayor a 25';
                }
            }
        }
        
        // Validar sala
        if (!$this->rela_salas || trim($this->rela_salas) === '') {
            $errores[] = 'La sala es obligatoria';
        } else {
            if (!is_numeric($this->rela_salas)) {
                $errores[] = 'La sala seleccionada no es válida';
            }
        }
        
        // Validar estado
        if (!$this->rela_estado_butaca || trim($this->rela_estado_butaca) === '') {
            $errores[] = 'El estado es obligatorio';
        } else {
            if (!is_numeric($this->rela_estado_butaca)) {
                $errores[] = 'El estado seleccionado no es válido';
            }
        }
        
        // Validar que no exista butaca duplicada en la misma sala
        if ($this->verificarButacaDuplicada()) {
            $errores[] = 'Ya existe una butaca en la fila ' . $this->fila_butaca . ' número ' . $this->numero_butaca . ' en esta sala';
        }
        
        // Validar que la fila y número estén dentro de los límites de la sala
        if ($this->validarLimitesSala()) {
            $errores = array_merge($errores, $this->validarLimitesSala());
        }
        
        return $errores;
    }
    
    private function verificarButacaDuplicada() {
        if (!$this->fila_butaca || !$this->numero_butaca || !$this->rela_salas) {
            return false;
        }
        
        $db = self::getDB();
        
        if ($this->id_butaca) {
            // Editando - excluir la butaca actual
            $query = "SELECT COUNT(*) as total FROM butacas 
                     WHERE fila_butaca = '{$this->fila_butaca}' 
                     AND numero_butaca = '{$this->numero_butaca}' 
                     AND rela_salas = '{$this->rela_salas}'
                     AND id_butaca != '{$this->id_butaca}'";
        } else {
            // Creando nueva
            $query = "SELECT COUNT(*) as total FROM butacas 
                     WHERE fila_butaca = '{$this->fila_butaca}' 
                     AND numero_butaca = '{$this->numero_butaca}' 
                     AND rela_salas = '{$this->rela_salas}'";
        }
        
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }
    
    private function validarLimitesSala() {
        if (!$this->rela_salas) {
            return [];
        }
        
        $errores = [];
        $db = self::getDB();
        
        // Obtener datos de la sala
        $query = "SELECT filas_sala, columnas_sala FROM salas WHERE id_sala = '{$this->rela_salas}'";
        $resultado = $db->query($query);
        
        if ($resultado && $resultado->num_rows > 0) {
            $sala = $resultado->fetch_assoc();
            
            if ($this->fila_butaca > $sala['filas_sala']) {
                $errores[] = 'La fila ' . $this->fila_butaca . ' excede el máximo de filas de la sala (' . $sala['filas_sala'] . ')';
            }
            
            if ($this->numero_butaca > $sala['columnas_sala']) {
                $errores[] = 'El número ' . $this->numero_butaca . ' excede el máximo de columnas de la sala (' . $sala['columnas_sala'] . ')';
            }
        }
        
        return $errores;
    }

    public static function obtenerTodas() {
        $db = self::getDB();
        $resultado = $db->query("SELECT 
                                    b.*,
                                    eb.nombre_estado_butaca,
                                    s.capacidad_sala,
                                    s.filas_sala,
                                    s.columnas_sala
                                FROM butacas b
                                INNER JOIN estados_butacas eb ON b.rela_estado_butaca = eb.id_estado_butaca
                                INNER JOIN salas s ON b.rela_salas = s.id_sala
                                ORDER BY b.rela_salas ASC, b.fila_butaca ASC, b.numero_butaca ASC");
        
        $butacas = [];
        while($row = $resultado->fetch_assoc()) {
            $butaca = new self;
            $butaca->id_butaca = $row['id_butaca'];
            $butaca->fila_butaca = $row['fila_butaca'];
            $butaca->numero_butaca = $row['numero_butaca'];
            $butaca->rela_salas = $row['rela_salas'];
            $butaca->rela_estado_butaca = $row['rela_estado_butaca'];
            $butaca->nombre_estado = $row['nombre_estado_butaca'];
            $butaca->capacidad_sala = $row['capacidad_sala'];
            $butaca->filas_sala = $row['filas_sala'];
            $butaca->columnas_sala = $row['columnas_sala'];
            $butacas[] = $butaca;
        }
        return $butacas;
    }
    
    public static function obtenerPorSala($idSala) {
        $db = self::getDB();
        $resultado = $db->query("SELECT 
                                    b.*,
                                    eb.nombre_estado_butaca
                                FROM butacas b
                                INNER JOIN estados_butacas eb ON b.rela_estado_butaca = eb.id_estado_butaca
                                WHERE b.rela_salas = '$idSala'
                                ORDER BY b.fila_butaca ASC, b.numero_butaca ASC");
        
        $butacas = [];
        while($row = $resultado->fetch_assoc()) {
            $butaca = new self;
            $butaca->id_butaca = $row['id_butaca'];
            $butaca->fila_butaca = $row['fila_butaca'];
            $butaca->numero_butaca = $row['numero_butaca'];
            $butaca->rela_salas = $row['rela_salas'];
            $butaca->rela_estado_butaca = $row['rela_estado_butaca'];
            $butaca->nombre_estado = $row['nombre_estado_butaca'];
            $butacas[] = $butaca;
        }
        return $butacas;
    }

    public function crearButaca() {
        $query = "INSERT INTO " . static::$tabla . " (fila_butaca, numero_butaca, rela_salas, rela_estado_butaca) 
                  VALUES ('{$this->fila_butaca}', '{$this->numero_butaca}', '{$this->rela_salas}', '{$this->rela_estado_butaca}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public static function buscarButaca($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT 
                     b.*,
                     eb.nombre_estado_butaca,
                     s.capacidad_sala,
                     s.filas_sala,
                     s.columnas_sala
                  FROM butacas b
                  INNER JOIN estados_butacas eb ON b.rela_estado_butaca = eb.id_estado_butaca
                  INNER JOIN salas s ON b.rela_salas = s.id_sala
                  WHERE b.id_butaca = '$termino' 
                     OR b.fila_butaca = '$termino'
                     OR b.rela_salas = '$termino'
                  LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function actualizar() {
        $query = "UPDATE butacas SET fila_butaca = ?, numero_butaca = ?, rela_salas = ?, rela_estado_butaca = ? WHERE id_butaca = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->fila_butaca,
            $this->numero_butaca,
            $this->rela_salas,
            $this->rela_estado_butaca,
            $this->id_butaca
        ]);
    }

    public function eliminar() {
        $query = "DELETE FROM butacas WHERE id_butaca = " . self::$db->real_escape_string($this->id_butaca);
        $resultado = self::$db->query($query);
        return $resultado;
    }
    
    // Método para generar butacas automáticamente para una sala
    public static function generarButacasPorSala($idSala) {
        $db = self::getDB();
        
        // Obtener datos de la sala
        $query = "SELECT filas_sala, columnas_sala FROM salas WHERE id_sala = '$idSala'";
        $resultado = $db->query($query);
        
        if (!$resultado || $resultado->num_rows === 0) {
            return ['resultado' => false, 'mensaje' => 'Sala no encontrada'];
        }
        
        $sala = $resultado->fetch_assoc();
        $filas = $sala['filas_sala'];
        $columnas = $sala['columnas_sala'];
        
        // Verificar si ya existen butacas para esta sala
        $queryExistentes = "SELECT COUNT(*) as total FROM butacas WHERE rela_salas = '$idSala'";
        $resultadoExistentes = $db->query($queryExistentes);
        $existentes = $resultadoExistentes->fetch_assoc();
        
        if ($existentes['total'] > 0) {
            return ['resultado' => false, 'mensaje' => 'Ya existen butacas para esta sala'];
        }
        
        // Generar butacas
        $butacasCreadas = 0;
        for ($fila = 1; $fila <= $filas; $fila++) {
            for ($numero = 1; $numero <= $columnas; $numero++) {
                $butaca = new self([
                    'fila_butaca' => $fila,
                    'numero_butaca' => $numero,
                    'rela_salas' => $idSala,
                    'rela_estado_butaca' => 1 // Disponible por defecto
                ]);
                
                $resultado = $butaca->crearButaca();
                if ($resultado['resultado']) {
                    $butacasCreadas++;
                }
            }
        }
        
        return [
            'resultado' => true,
            'mensaje' => "Se generaron $butacasCreadas butacas para la sala $idSala",
            'butacas_creadas' => $butacasCreadas
        ];
    }
   

    public static function obtenerLayoutSala($idSala) {
        $db = self::getDB();

        // Obtener datos de la sala
        $querySala = "SELECT * FROM salas WHERE id_sala = ? AND estado = 1";
        $stmtSala = $db->prepare($querySala);
        $stmtSala->bind_param("i", $idSala);
        $stmtSala->execute();
        $resultadoSala = $stmtSala->get_result();

        if ($resultadoSala->num_rows === 0) {
            return ['error' => 'Sala no encontrada'];
        }

        $sala = $resultadoSala->fetch_assoc();

        // Obtener todas las butacas de la sala.
        // "Vendida" (3) tiene prioridad sobre "bloqueada" (2): una butaca queda marcada
        // rela_estado_butaca = 2 tanto si el admin la bloqueó como si se vendió por web
        // (ver PagoController::confirmarPago), así que acá se distingue consultando si
        // tiene una venta real vigente antes de asumir que es un bloqueo de mantenimiento.
        $queryButacas = "SELECT
                            b.id_butaca,
                            b.fila_butaca,
                            b.numero_butaca,
                            CASE
                                WHEN EXISTS (
                                    SELECT 1 FROM butacas_vendidas bv
                                    INNER JOIN funciones f ON f.id_funcion = bv.id_funcion
                                    INNER JOIN turnos t ON t.id_turnos = f.rela_turnos
                                    WHERE bv.id_butaca = b.id_butaca
                                        AND f.estado = 1
                                        AND TIMESTAMP(f.fecha_hora, t.turno_horario) >= NOW()
                                ) THEN 3
                                WHEN b.rela_estado_butaca = 2 THEN 2
                                ELSE 1
                            END as estado
                        FROM butacas b
                        WHERE b.rela_salas = ?
                        ORDER BY b.fila_butaca ASC, b.numero_butaca ASC";

        $stmtButacas = $db->prepare($queryButacas);
        $stmtButacas->bind_param("i", $idSala);
        $stmtButacas->execute();
        $resultadoButacas = $stmtButacas->get_result();

        // Crear matriz de butacas
        $matriz = [];
        $butacasData = [];

        while($row = $resultadoButacas->fetch_assoc()) {
            $fila = (int)$row['fila_butaca'];
            $numero = (int)$row['numero_butaca'];
            $estado = (int)$row['estado'];

            if (!isset($matriz[$fila])) {
                $matriz[$fila] = [];
            }


            $matriz[$fila][$numero] = [
                'id' => (int)$row['id_butaca'],
                'fila' => $fila,
                'numero' => $numero,
                'estado' => $estado,
                'disponible' => $estado === 1,
                'reservada' => $estado === 3,
                'bloqueada' => $estado === 2,
                'label' => $fila . '-' . $numero
            ];

            $butacasData[] = $matriz[$fila][$numero];
        }
        
        return [
            'sala' => [
                'id' => $sala['id_sala'],
                'filas' => $sala['filas_sala'],
                'columnas' => $sala['columnas_sala'],
                'capacidad' => $sala['capacidad_sala']
            ],
            'butacas' => $butacasData,
            'matriz' => $matriz
        ];
    }


    public static function obtenerFuncionesPorSala($idSala) {
        $db = self::getDB();

        $query = "SELECT f.id_funcion, f.fecha_hora, t.turno_horario, p.titulo_pelicula
                  FROM funciones f
                  INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
                  INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                  WHERE f.rela_salas = ? AND f.estado = 1
                    AND TIMESTAMP(f.fecha_hora, t.turno_horario) >= NOW()
                  ORDER BY f.fecha_hora ASC, t.turno_horario ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idSala);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function obtenerLayoutSalaPorFuncion($idSala, $idFuncion) {
        $db = self::getDB();

        $querySala = "SELECT * FROM salas WHERE id_sala = ? AND estado = 1";
        $stmtSala = $db->prepare($querySala);
        $stmtSala->bind_param("i", $idSala);
        $stmtSala->execute();
        $resultadoSala = $stmtSala->get_result();

        if ($resultadoSala->num_rows === 0) {
            return ['error' => 'Sala no encontrada'];
        }

        $sala = $resultadoSala->fetch_assoc();

        // Estado por función:
        // 3 = vendida (venta confirmada, por vendedor interno o por pago web)
        // 4 = en carrito de un cliente (todavía no pagada)
        // 2 = bloqueada por el administrador (mantenimiento, aplica a toda función)
        // 1 = disponible
        $queryButacas = "SELECT
                            b.id_butaca,
                            b.fila_butaca,
                            b.numero_butaca,
                            CASE
                                WHEN bv.id_venta_butaca IS NOT NULL THEN 3
                                WHEN EXISTS (
                                    SELECT 1 FROM carrito_temporal ct
                                    WHERE ct.id_butaca = b.id_butaca
                                        AND ct.id_funcion = ?
                                        AND ct.tipo_producto = 'butacas'
                                ) THEN 4
                                WHEN b.rela_estado_butaca = 2 THEN 2
                                ELSE 1
                            END as estado
                        FROM butacas b
                        LEFT JOIN butacas_vendidas bv ON bv.id_butaca = b.id_butaca
                            AND bv.id_funcion = ?
                        WHERE b.rela_salas = ?
                        ORDER BY b.fila_butaca ASC, b.numero_butaca ASC";

        $stmtButacas = $db->prepare($queryButacas);
        $stmtButacas->bind_param("iii", $idFuncion, $idFuncion, $idSala);
        $stmtButacas->execute();
        $resultadoButacas = $stmtButacas->get_result();

        $matriz = [];
        $butacasData = [];

        while ($row = $resultadoButacas->fetch_assoc()) {
            $fila = (int)$row['fila_butaca'];
            $numero = (int)$row['numero_butaca'];
            $estado = (int)$row['estado'];

            $item = [
                'id' => (int)$row['id_butaca'],
                'fila' => $fila,
                'numero' => $numero,
                'estado' => $estado,
                'disponible' => $estado === 1,
                'bloqueada' => $estado === 2,
                'reservada' => $estado === 3,
                'en_carrito' => $estado === 4,
                'label' => $fila . '-' . $numero
            ];

            $matriz[$fila][$numero] = $item;
            $butacasData[] = $item;
        }

        return [
            'sala' => [
                'id' => $sala['id_sala'],
                'filas' => $sala['filas_sala'],
                'columnas' => $sala['columnas_sala'],
                'capacidad' => $sala['capacidad_sala']
            ],
            'butacas' => $butacasData,
            'matriz' => $matriz
        ];
    }

    public static function reservarButacas($butacasIds, $idFuncion = null) {
        $db = self::getDB();
        
        if (empty($butacasIds)) {
            return ['error' => 'No se especificaron butacas'];
        }
        
        // Convertir array a string para query
        $idsString = implode(',', array_map('intval', $butacasIds));
        
        // Verificar que todas las butacas estén disponibles
        $queryVerificar = "SELECT id_butaca, fila_butaca, numero_butaca, rela_estado_butaca 
                        FROM butacas 
                        WHERE id_butaca IN ($idsString)";
        
        $resultado = $db->query($queryVerificar);
        $butacasNoDisponibles = [];
        
        while($row = $resultado->fetch_assoc()) {
            if ($row['rela_estado_butaca'] != 1) { // No disponible
                $butacasNoDisponibles[] = $row['fila_butaca'] . '-' . $row['numero_butaca'];
            }
        }
        
        if (!empty($butacasNoDisponibles)) {
            return [
                'error' => 'Las siguientes butacas ya no están disponibles: ' . implode(', ', $butacasNoDisponibles)
            ];
        }
        
        // Reservar butacas (cambiar estado a 3 = reservado)
        $queryReservar = "UPDATE butacas 
                        SET rela_estado_butaca = 3 
                        WHERE id_butaca IN ($idsString) 
                        AND rela_estado_butaca = 1";
        
        $resultadoReserva = $db->query($queryReservar);
        
        if ($resultadoReserva) {
            return [
                'success' => true,
                'mensaje' => 'Butacas reservadas correctamente',
                'butacas_reservadas' => count($butacasIds)
            ];
        } else {
            return ['error' => 'Error al reservar butacas'];
        }
    }

    /**
     * Liberar butacas reservadas
     */
    public static function liberarButacas($butacasIds) {
        $db = self::getDB();
        
        if (empty($butacasIds)) {
            return ['error' => 'No se especificaron butacas'];
        }
        
        $idsString = implode(',', array_map('intval', $butacasIds));
        
        // Liberar butacas (cambiar estado a 1 = disponible)
        $queryLiberar = "UPDATE butacas 
                        SET rela_estado_butaca = 1 
                        WHERE id_butaca IN ($idsString) 
                        AND rela_estado_butaca = 3";
        
        $resultado = $db->query($queryLiberar);
        
        if ($resultado) {
            return [
                'success' => true,
                'mensaje' => 'Butacas liberadas correctamente'
            ];
        } else {
            return ['error' => 'Error al liberar butacas'];
        }
    }

   
    public static function obtenerButacasPorFuncion($idFuncion) {
    return self::obtenerLayoutPorFuncion($idFuncion);
}

  public static function obtenerLayoutPorFuncion($idFuncion) {
    $db = self::getDB();
    
    // 1. Obtener función y sala
    $queryFuncion = "SELECT f.*, s.* FROM funciones f 
                     INNER JOIN salas s ON f.rela_salas = s.id_sala 
                     WHERE f.id_funcion = '$idFuncion'";
    $resultadoFuncion = $db->query($queryFuncion);
    
    if (!$resultadoFuncion || $resultadoFuncion->num_rows === 0) {
        return ['error' => 'Función no encontrada'];
    }
    
    $funcion = $resultadoFuncion->fetch_assoc();
    $idSala = $funcion['rela_salas'];
    
    // 2. Obtener butacas con su estado para esta función
    $queryButacas = "SELECT 
                        b.*,
                        CASE 
                            WHEN rb.estado_reserva IN ('pagada', 'confirmada') THEN 3
                            WHEN rb.estado_reserva = 'temporal' AND rb.fecha_expiracion > NOW() THEN 3
                            WHEN b.rela_estado_butaca = 2 THEN 2
                            ELSE 1
                        END as estado_final
                    FROM butacas b
                    LEFT JOIN reservas_butacas rb ON (
                        b.id_butaca = rb.id_butaca 
                        AND rb.id_funcion = '$idFuncion'
                        AND rb.estado_reserva IN ('temporal', 'confirmada', 'pagada')
                    )
                    WHERE b.rela_salas = '$idSala'
                    ORDER BY b.fila_butaca ASC, b.numero_butaca ASC";
    
    $resultadoButacas = $db->query($queryButacas);
    
    $matriz = [];
    $butacasData = [];
    
    while($row = $resultadoButacas->fetch_assoc()) {
        $fila = $row['fila_butaca'];
        $numero = $row['numero_butaca'];
        $estadoFinal = (int)$row['estado_final'];
        
        if (!isset($matriz[$fila])) {
            $matriz[$fila] = [];
        }
        
        $matriz[$fila][$numero] = [
            'id' => $row['id_butaca'],
            'fila' => $fila,
            'numero' => $numero,
            'estado' => $estadoFinal,
            'disponible' => $estadoFinal == 1,
            'reservada' => $estadoFinal == 3,
            'bloqueada' => $estadoFinal == 2,
            'label' => $fila . '-' . $numero
        ];
        
        $butacasData[] = $matriz[$fila][$numero];
    }
    
    return [
        'sala' => [
            'id' => $funcion['id_sala'],
            'filas' => $funcion['filas_sala'],
            'columnas' => $funcion['columnas_sala'],
            'capacidad' => $funcion['capacidad_sala']
        ],
        'butacas' => $butacasData,
        'matriz' => $matriz
    ];
}
    // AGREGAR este método para reservar
    public static function reservarButacasPorFuncion($butacasIds, $idFuncion, $idUsuario, $sesionId) {
        $db = self::getDB();
        
        if (empty($butacasIds)) {
            return ['ok' => false, 'mensaje' => 'No se especificaron butacas'];
        }
        
        $db->begin_transaction();
        
        try {
            $idsString = implode(',', array_map('intval', $butacasIds));
            
            // Verificar disponibilidad
            $queryVerificar = "SELECT b.id_butaca, b.fila_butaca, b.numero_butaca,
                                rb.estado_reserva
                            FROM butacas b
                            LEFT JOIN reservas_butacas rb ON (
                                b.id_butaca = rb.id_butaca 
                                AND rb.id_funcion = '$idFuncion'
                                AND rb.estado_reserva IN ('temporal', 'confirmada', 'pagada')
                                AND (rb.fecha_expiracion IS NULL OR rb.fecha_expiracion > NOW())
                            )
                            WHERE b.id_butaca IN ($idsString)
                                AND b.rela_estado_butaca != 2";
            
            $resultado = $db->query($queryVerificar);
            $butacasNoDisponibles = [];
            
            while($row = $resultado->fetch_assoc()) {
                if ($row['estado_reserva']) {
                    $butacasNoDisponibles[] = $row['fila_butaca'] . '-' . $row['numero_butaca'];
                }
            }
            
            if (!empty($butacasNoDisponibles)) {
                $db->rollback();
                return [
                    'ok' => false,
                    'mensaje' => 'Butacas no disponibles: ' . implode(', ', $butacasNoDisponibles)
                ];
            }
            
            // Crear reservas temporales (15 minutos)
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            foreach ($butacasIds as $idButaca) {
                $queryReservar = "INSERT INTO reservas_butacas 
                                (id_butaca, id_funcion, id_usuario, estado_reserva, fecha_expiracion, sesion_id)
                                VALUES ('$idButaca', '$idFuncion', '$idUsuario', 'temporal', '$fechaExpiracion', '$sesionId')
                                ON DUPLICATE KEY UPDATE 
                                estado_reserva = 'temporal',
                                fecha_expiracion = '$fechaExpiracion',
                                sesion_id = '$sesionId'";
                
                $db->query($queryReservar);
            }
            
            $db->commit();
            
            return [
                'ok' => true,
                'mensaje' => 'Butacas reservadas por 15 minutos',
                'butacas_reservadas' => count($butacasIds)
            ];
            
        } catch (\Exception $e) {
            $db->rollback();
            return ['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // DISPONIBILIDAD PARA LA VENTA (online y boletería)
    // Una butaca de una función NO se puede vender si:
    //   - ya está vendida (butacas_vendidas con entrada no cancelada), o
    //   - otro cliente la está pagando (reserva 'temporal' vigente).
    // Al agregar al carrito NO se reserva: la reserva nace al iniciar el pago.
    // ═══════════════════════════════════════════════════════════

    const MINUTOS_RESERVA_PAGO = 15;

    /**
     * Devuelve las butacas que no se pueden vender, con el motivo.
     *
     * @param array    $pares      [['id_butaca' => .., 'id_funcion' => ..], ...]
     * @param int|null $idUsuario  sus propias reservas no le bloquean (null = bloquea cualquier reserva)
     * @return array ["idButaca-idFuncion" => 'vendida'|'reservada']
     */
    public static function noDisponibles(array $pares, ?int $idUsuario = null): array
    {
        $db = self::getDB();
        $usuario = $idUsuario ?? -1;
        $resultado = [];

        $stmtVendida = $db->prepare("SELECT 1 FROM butacas_vendidas bv
                                     LEFT JOIN entradas e ON e.id_entrada = bv.id_entrada
                                     WHERE bv.id_butaca = ? AND bv.id_funcion = ?
                                       AND (e.id_entrada IS NULL OR e.estado != -1)");
        $stmtReservada = $db->prepare("SELECT 1 FROM reservas_butacas
                                       WHERE id_butaca = ? AND id_funcion = ?
                                         AND estado_reserva = 'temporal' AND fecha_expiracion > NOW()
                                         AND (id_usuario IS NULL OR id_usuario <> ?)");

        foreach ($pares as $par) {
            $idButaca = (int)$par['id_butaca'];
            $idFuncion = (int)$par['id_funcion'];
            $clave = "$idButaca-$idFuncion";

            $stmtVendida->bind_param('ii', $idButaca, $idFuncion);
            $stmtVendida->execute();
            if ($stmtVendida->get_result()->num_rows > 0) {
                $resultado[$clave] = 'vendida';
                continue;
            }

            $stmtReservada->bind_param('iii', $idButaca, $idFuncion, $usuario);
            $stmtReservada->execute();
            if ($stmtReservada->get_result()->num_rows > 0) {
                $resultado[$clave] = 'reservada';
            }
        }

        return $resultado;
    }

    /**
     * Bloquea las filas de las butacas hasta el fin de la transacción en curso, para que
     * dos ventas simultáneas (online o boletería) de la misma butaca se atiendan de a una.
     */
    public static function bloquearParaVenta(array $idsButacas): void
    {
        $ids = array_filter(array_map('intval', $idsButacas));
        if ($ids) {
            self::getDB()->query("SELECT id_butaca FROM butacas WHERE id_butaca IN (" . implode(',', $ids) . ") FOR UPDATE");
        }
    }

    /**
     * Reserva las butacas para el usuario mientras paga. Devuelve el timestamp de vencimiento.
     */
    public static function reservarParaPago(array $pares, int $idUsuario): int
    {
        $db = self::getDB();
        self::liberarReservasDeUsuario($pares, $idUsuario);

        // El vencimiento lo calcula MySQL: se compara siempre contra su NOW(), sin depender de la zona horaria de PHP
        $minutos = self::MINUTOS_RESERVA_PAGO;
        $stmt = $db->prepare("INSERT INTO reservas_butacas (id_butaca, id_funcion, id_usuario, estado_reserva, fecha_reserva, fecha_expiracion)
                              VALUES (?, ?, ?, 'temporal', NOW(), DATE_ADD(NOW(), INTERVAL $minutos MINUTE))");
        foreach ($pares as $par) {
            $idButaca = (int)$par['id_butaca'];
            $idFuncion = (int)$par['id_funcion'];
            $stmt->bind_param('iii', $idButaca, $idFuncion, $idUsuario);
            $stmt->execute();
        }

        return time() + $minutos * 60;
    }

    /**
     * Quita las reservas temporales del usuario sobre esas butacas (pago fallido, reintento o venta confirmada).
     */
    public static function liberarReservasDeUsuario(array $pares, int $idUsuario): void
    {
        $stmt = self::getDB()->prepare("DELETE FROM reservas_butacas
                                        WHERE id_butaca = ? AND id_funcion = ? AND id_usuario = ? AND estado_reserva = 'temporal'");
        foreach ($pares as $par) {
            $idButaca = (int)$par['id_butaca'];
            $idFuncion = (int)$par['id_funcion'];
            $stmt->bind_param('iii', $idButaca, $idFuncion, $idUsuario);
            $stmt->execute();
        }
    }
}