<?php

namespace Models;

class Funciones extends ActiveRecord
{

    protected static $tabla = 'funciones';
    protected static $columnasDB = [
        'id_funcion',
        'fecha_hora',
        'fecha_finalizacion',
        'rela_salas',
        'rela_peliculas',
        'rela_turnos',
        'rela_tipo_entrada',
        'estado',
        'rela_idioma'
    ];
    public $id_funcion;
    public $fecha_hora;
    public $fecha_finalizacion;
    public $rela_salas;
    public $rela_peliculas;
    public $rela_turnos;
    public $rela_tipo_entrada; // NUEVO CAMPO

    public $titulo_pelicula;
    public $turno_horario;
    public $estado;
    public $imagen_pelicula;

    // Propiedades adicionales para vista del usuario
    public $duracion_pelicula;
    public $rela_tipo_clasificacion;
    public $capacidad_sala;
    public $nombre_sala;
    public $tipo_entrada_desc; // NUEVO CAMPO
    public $precio_entrada;    // NUEVO CAMPO
    public $clasificacion_texto;
    public $rela_idioma; // NUEVO CAMPO

    public function __construct($args = [])
    {
        $this->id_funcion = $args['id_funcion'] ?? null;
        $this->fecha_hora = $args['fecha_hora'] ?? '';
        $this->fecha_finalizacion = $args['fecha_finalizacion'] ?? '';
        $this->rela_salas = $args['rela_salas'] ?? '';
        $this->rela_peliculas = $args['rela_peliculas'] ?? '';
        $this->rela_turnos = $args['rela_turnos'] ?? '';
        $this->rela_tipo_entrada = $args['rela_tipo_entrada'] ?? ''; // NUEVO
        //
        $this->titulo_pelicula = $args['titulo_pelicula'] ?? '';
        $this->imagen_pelicula = $args['imagen_pelicula'] ?? '';
        $this->turno_horario = $args['turno_horario'] ?? '';
        $this->estado = $args['estado'] ?? 1;

        // Propiedades adicionales
        $this->duracion_pelicula = $args['duracion_pelicula'] ?? '';
        $this->rela_tipo_clasificacion = $args['rela_tipo_clasificacion'] ?? '';
        $this->capacidad_sala = $args['capacidad_sala'] ?? '';
        $this->nombre_sala = $args['nombre_sala'] ?? '';
        $this->tipo_entrada_desc = $args['tipo_entrada_desc'] ?? '2D'; // NUEVO
        $this->precio_entrada = $args['precio_entrada'] ?? 0; // NUEVO
        $this->clasificacion_texto = $args['clasificacion_texto'] ?? 'ATP'; // NUEVO
        $this->rela_idioma = $args['rela_idioma'] ?? null;
    }

    // Método para administrador - ACTUALIZADO
    public static function obtenerFunciones()
    {
        $db = self::getDB();
        $resultado = $db->query("SELECT 
                                        f.id_funcion,
                                        f.fecha_hora,
                                        f.fecha_finalizacion,
                                        f.rela_salas,
                                        f.rela_tipo_entrada,
                                        p.titulo_pelicula,
                                        p.imagen_pelicula,
                                        t.turno_horario,
                                        te.tipo_entrada_desc,
                                        te.precio_entrada,
                                        f.estado
                                        FROM funciones f
                                        INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                                        INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
                                        LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada;");
        $funciones = [];
        while ($row = $resultado->fetch_assoc()) {
            $funcion = new self;
            $funcion->id_funcion = $row['id_funcion'];
            $funcion->fecha_hora = $row['fecha_hora'];
            $funcion->fecha_finalizacion = $row['fecha_finalizacion'];
            $funcion->rela_salas = $row['rela_salas'];
            $funcion->rela_tipo_entrada = $row['rela_tipo_entrada'];
            $funcion->titulo_pelicula = $row['titulo_pelicula'];
            $funcion->imagen_pelicula = $row['imagen_pelicula'];
            $funcion->turno_horario = $row['turno_horario'];
            $funcion->tipo_entrada_desc = $row['tipo_entrada_desc'];
            $funcion->precio_entrada = $row['precio_entrada'];
            $funcion->estado = $row['estado'];
            $funciones[] = $funcion;
        }
        return $funciones;
    }

    /**
     * Método mejorado para obtener funciones activas con toda la información
     */
    public static function obtenerFuncionesActivas()
    {
        $db = self::getDB();

        $resultado = $db->query("SELECT 
                                        f.id_funcion,
                                        f.fecha_hora,
                                        f.fecha_finalizacion,
                                        f.rela_salas,
                                        f.rela_peliculas,
                                        f.rela_turnos,
                                        f.rela_tipo_entrada,
                                        p.titulo_pelicula,
                                        p.imagen_pelicula,
                                        p.duracion_pelicula,
                                        p.rela_tipo_clasificacion,
                                        t.turno_horario,
                                        s.capacidad_sala,
                                        te.tipo_entrada_desc,
                                        te.precio_entrada,
                                        tc.nombre_tipo_clasificacion,
                                        f.estado
                                        FROM funciones f
                                INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                                INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
                                INNER JOIN salas s ON f.rela_salas = s.id_sala
                                LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
                                LEFT JOIN tipos_clasificaciones tc ON p.rela_tipo_clasificacion = tc.id_tipo_clasificacion
                                WHERE f.estado = 1 
                                AND s.estado = 1 
                                AND t.estado = 1
                                AND (te.estado = 1 OR te.estado IS NULL)
                                AND TIMESTAMP(f.fecha_hora, t.turno_horario) >= NOW()
                                ORDER BY f.fecha_hora ASC, t.turno_horario ASC");

        $funciones = [];
        while ($row = $resultado->fetch_assoc()) {
            $funcion = new self;
            $funcion->id_funcion = $row['id_funcion'];
            $funcion->fecha_hora = $row['fecha_hora'];
            $funcion->fecha_finalizacion = $row['fecha_finalizacion'];
            $funcion->rela_salas = $row['rela_salas'];
            $funcion->rela_peliculas = $row['rela_peliculas'];
            $funcion->rela_turnos = $row['rela_turnos'];
            $funcion->rela_tipo_entrada = $row['rela_tipo_entrada'];
            $funcion->titulo_pelicula = $row['titulo_pelicula'];
            $funcion->imagen_pelicula = $row['imagen_pelicula'];
            $funcion->turno_horario = $row['turno_horario'];
            $funcion->estado = $row['estado'];

            // Datos sobre peliculas, salas y entradas
            $funcion->duracion_pelicula = $row['duracion_pelicula'] ?? '';
            $funcion->rela_tipo_clasificacion = $row['rela_tipo_clasificacion'] ?? '';
            $funcion->capacidad_sala = $row['capacidad_sala'] ?? '';
            $funcion->tipo_entrada_desc = $row['tipo_entrada_desc'] ?? '2D';
            $funcion->precio_entrada = $row['precio_entrada'] ?? 0;
            $funcion->clasificacion_texto = $row['nombre_tipo_clasificacion'] ?? 'ATP';

            // Generar nombres basados en IDs
            $funcion->nombre_sala = 'Sala ' . $row['rela_salas'];

            $funciones[] = $funcion;
        }
        return $funciones;
    }

    public static function obtenerFuncionesPorPelicula()
    {
        $funciones = self::obtenerFuncionesActivas();
        $funcionesPorPelicula = [];

        foreach ($funciones as $funcion) {
            $idPelicula = $funcion->rela_peliculas;

            if (!isset($funcionesPorPelicula[$idPelicula])) {
                $funcionesPorPelicula[$idPelicula] = [
                    'pelicula' => [
                        'id' => $idPelicula,
                        'titulo' => $funcion->titulo_pelicula,
                        'imagen' => $funcion->imagen_pelicula,
                        'duracion' => $funcion->duracion_pelicula ?? '',
                        'clasificacion' => $funcion->clasificacion_texto ?? ''
                    ],
                    'funciones' => []
                ];
            }

            $funcionesPorPelicula[$idPelicula]['funciones'][] = $funcion;
        }

        return $funcionesPorPelicula;
    }


    public function validar()
    {
        $alertas = [];

        if (!$this->fecha_hora || trim($this->fecha_hora) === '') {
            $alertas[] = "La fecha de inicio es obligatoria";
        } else {
            if (!\DateTime::createFromFormat('Y-m-d', $this->fecha_hora)) {
                $alertas[] = "La fecha de inicio debe tener un formato válido (YYYY-MM-DD)";
            } else {
                $hoy = date('Y-m-d');
                if ($this->fecha_hora < $hoy) {
                    $alertas[] = "La fecha de inicio debe ser posterior a la fecha actual";
                }
                $fechaLimite = date('Y-m-d', strtotime('+1 month'));
                if ($this->fecha_hora > $fechaLimite) {
                    $alertas[] = "No se pueden programar funciones con más de 1 mes de anticipación";
                }
            }
        }

        if (empty($this->rela_idioma)) {
            $errores[] = 'Debe seleccionar un idioma para la función';
        }

        if (!$this->fecha_finalizacion || trim($this->fecha_finalizacion) === '') {
            $alertas[] = "La fecha de finalización es obligatoria";
        } else {
            if (!\DateTime::createFromFormat('Y-m-d', $this->fecha_finalizacion)) {
                $alertas[] = "La fecha de finalización debe tener un formato válido (YYYY-MM-DD)";
            } else {
                if ($this->fecha_finalizacion < $this->fecha_hora) {
                    $alertas[] = "La fecha de finalización no puede ser anterior a la fecha de inicio";
                }
                if ($this->fecha_hora && $this->fecha_finalizacion) {
                    $inicio = new \DateTime($this->fecha_hora);
                    $fin = new \DateTime($this->fecha_finalizacion);
                    $diferencia = $inicio->diff($fin);

                    if ($diferencia->days > 31) {
                        $alertas[] = "La función no puede durar más de 1 mes";
                    }
                }
            }
        }

        if (!$this->rela_salas || trim($this->rela_salas) === '') {
            $alertas[] = "La sala es obligatoria";
        } else {
            if (!is_numeric($this->rela_salas)) {
                $alertas[] = "La sala seleccionada no es válida";
            }
        }

        if (!$this->rela_peliculas || trim($this->rela_peliculas) === '') {
            $alertas[] = "La película es obligatoria";
        } else {
            if (!is_numeric($this->rela_peliculas)) {
                $alertas[] = "La película seleccionada no es válida";
            }
        }

        if (!$this->rela_turnos || trim($this->rela_turnos) === '') {
            $alertas[] = "El turno es obligatorio";
        } else {
            if (!is_numeric($this->rela_turnos)) {
                $alertas[] = "El turno seleccionado no es válido";
            }
        }

        if (!$this->rela_tipo_entrada || trim($this->rela_tipo_entrada) === '') {
            $alertas[] = "El tipo de entrada es obligatorio";
        } else {
            if (!is_numeric($this->rela_tipo_entrada)) {
                $alertas[] = "El tipo de entrada seleccionado no es válido";
            }
        }

        if ($this->verificarConflictoSalaTurno()) {
            $alertas[] = "Ya hay una función programada en esa sala y turno en las mismas fechas";
        }

        return $alertas;
    }

    private function verificarConflictoSalaTurno()
    {
        if (!$this->rela_salas || !$this->rela_turnos || !$this->fecha_hora) {
            return false;
        }

        $db = self::getDB();

        $query = "SELECT COUNT(*) as total FROM funciones 
            WHERE rela_salas = '{$this->rela_salas}' 
            AND rela_turnos = '{$this->rela_turnos}'
            AND fecha_hora = '{$this->fecha_hora}'
            AND estado = 1";

        if ($this->id_funcion) {
            $query .= " AND id_funcion != '{$this->id_funcion}'";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }

    private function verificarFuncionDuplicada()
    {
        if (!$this->rela_peliculas || !$this->rela_salas || !$this->rela_turnos) {
            return false;
        }

        $db = self::getDB();

        if ($this->id_funcion) {
            // Editando
            $query = "SELECT COUNT(*) as total FROM funciones 
                    WHERE rela_peliculas = '{$this->rela_peliculas}' 
                    AND rela_salas = '{$this->rela_salas}' 
                    AND rela_turnos = '{$this->rela_turnos}'
                    AND fecha_hora = '{$this->fecha_hora}'
                    AND fecha_finalizacion = '{$this->fecha_finalizacion}'
                    AND id_funcion != '{$this->id_funcion}'
                    AND estado = 1";
        } else {
            // Creando
            $query = "SELECT COUNT(*) as total FROM funciones 
                    WHERE rela_peliculas = '{$this->rela_peliculas}' 
                    AND rela_salas = '{$this->rela_salas}' 
                    AND rela_turnos = '{$this->rela_turnos}'
                    AND fecha_hora = '{$this->fecha_hora}'
                    AND fecha_finalizacion = '{$this->fecha_finalizacion}'
                    AND estado = 1";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }
    private function verificarConflictoSala()
    {
        if (!$this->rela_salas || !$this->fecha_hora || !$this->fecha_finalizacion || !$this->rela_turnos) {
            return false;
        }

        $db = self::getDB();

        if ($this->id_funcion) {
            $query = "SELECT COUNT(*) as total FROM funciones 
                    WHERE rela_salas = '{$this->rela_salas}' 
                    AND rela_turnos = '{$this->rela_turnos}'
                    AND id_funcion != '{$this->id_funcion}'
                    AND estado = 1
                    AND (
                        (fecha_hora <= '{$this->fecha_finalizacion}' AND fecha_finalizacion >= '{$this->fecha_hora}')
                    )";
        } else {
            $query = "SELECT COUNT(*) as total FROM funciones 
                    WHERE rela_salas = '{$this->rela_salas}' 
                    AND rela_turnos = '{$this->rela_turnos}'
                    AND estado = 1
                    AND (
                        (fecha_hora <= '{$this->fecha_finalizacion}' AND fecha_finalizacion >= '{$this->fecha_hora}')
                    )";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }

    private function verificarPeliculaDuplicada()
    {
        if (!$this->rela_peliculas || !$this->fecha_hora || !$this->fecha_finalizacion) {
            return false;
        }

        $db = self::getDB();

        if ($this->id_funcion) {
            $query = "SELECT COUNT(*) as total FROM funciones 
                    WHERE rela_peliculas = '{$this->rela_peliculas}' 
                    AND id_funcion != '{$this->id_funcion}'
                    AND estado = 1
                    AND (
                        (fecha_hora <= '{$this->fecha_finalizacion}' AND fecha_finalizacion >= '{$this->fecha_hora}')
                    )";
        } else {
            $query = "SELECT COUNT(*) as total FROM funciones 
                    WHERE rela_peliculas = '{$this->rela_peliculas}' 
                    AND estado = 1
                    AND (
                        (fecha_hora <= '{$this->fecha_finalizacion}' AND fecha_finalizacion >= '{$this->fecha_hora}')
                    )";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }

    public function crearFuncion()
    {
        $query = "INSERT INTO " . static::$tabla . " (fecha_hora, fecha_finalizacion,rela_salas,rela_peliculas,rela_turnos,rela_tipo_entrada,estado) 
                VALUES ('{$this->fecha_hora}', '{$this->fecha_finalizacion}', '{$this->rela_salas}', '{$this->rela_peliculas}','{$this->rela_turnos}','{$this->rela_tipo_entrada}','{$this->estado}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar()
    {
        $query = "UPDATE funciones SET fecha_hora = ?, fecha_finalizacion = ?, rela_salas = ?,rela_peliculas = ?,rela_turnos = ?,rela_tipo_entrada = ?,estado = ? WHERE id_funcion = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->fecha_hora,
            $this->fecha_finalizacion,
            $this->rela_salas,
            $this->rela_peliculas,
            $this->rela_turnos,
            $this->rela_tipo_entrada,
            $this->estado,
            $this->id_funcion
        ]);
    }

    public function eliminarLogico()
    {
        $query = "UPDATE funciones SET estado = 0 WHERE id_funcion = " . self::$db->real_escape_string($this->id_funcion);
        $resultado = self::$db->query($query);
        return $resultado;
    }

    public static function buscarFuncion($termino)
    {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT 
                f.id_funcion,
                f.fecha_hora,
                f.fecha_finalizacion,
                f.rela_salas,
                f.rela_peliculas,
                f.rela_turnos,
                f.rela_tipo_entrada,
                f.estado,
                p.titulo_pelicula,
                p.imagen_pelicula,
                t.turno_horario,
                te.tipo_entrada_desc,
                te.precio_entrada
            FROM funciones f
            LEFT JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
            LEFT JOIN turnos t ON f.rela_turnos = t.id_turnos
            LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
            WHERE (f.id_funcion = '$termino' 
               OR p.titulo_pelicula LIKE '%$termino%'
               OR f.rela_salas = '$termino')
               AND f.estado = 1
               AND TIMESTAMP(f.fecha_hora, t.turno_horario) >= NOW()
            LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public static function obtenerFuncionesPorDia($dia = null)
    {
        $db = self::getDB();

        $diasSemana = [
            'domingo' => 1,
            'lunes' => 2,
            'martes' => 3,
            'miercoles' => 4,
            'jueves' => 5,
            'viernes' => 6,
            'sabado' => 7
        ];

        $query = "SELECT f.*, p.titulo_pelicula, p.imagen_pelicula, p.duracion_pelicula,
                    t.turno_horario, s.id_sala, s.capacidad_sala, 
                    te.tipo_entrada_desc, te.precio_entrada,
                    tc.nombre_tipo_clasificacion
            FROM funciones f
            INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
            INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
            INNER JOIN salas s ON f.rela_salas = s.id_sala
            LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
            LEFT JOIN tipos_clasificaciones tc ON p.rela_tipo_clasificacion = tc.id_tipo_clasificacion
            WHERE f.estado = 1 AND TIMESTAMP(f.fecha_hora, t.turno_horario) >= NOW()";

        if ($dia && isset($diasSemana[strtolower($dia)])) {
            $numeroDia = $diasSemana[strtolower($dia)];
            $query .= " AND DAYOFWEEK(f.fecha_hora) = $numeroDia";
        }

        $query .= " ORDER BY f.fecha_hora ASC, t.turno_horario ASC";

        $resultado = $db->query($query);
        $funciones = [];

        while ($row = $resultado->fetch_assoc()) {
            $funcion = new self($row);
            // Generar nombre de sala basado en el ID
            $funcion->nombre_sala = 'Sala ' . $row['id_sala'];
            $funcion->capacidad_sala = $row['capacidad_sala'] ?? 0;
            $funcion->clasificacion_texto = $row['nombre_tipo_clasificacion'] ?? 'ATP';
            $funciones[] = $funcion;
        }

        return $funciones;
    }

    public static function obtenerFuncionesPorDiasSemana()
    {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $funcionesPorDia = [];

        foreach ($dias as $dia) {
            $funcionesPorDia[$dia] = self::obtenerFuncionesPorDia($dia);
        }

        return $funcionesPorDia;
    }
}
