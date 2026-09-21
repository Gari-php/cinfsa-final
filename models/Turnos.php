<?php
namespace Models;

class Turnos extends ActiveRecord{
    protected static $tabla='turnos';
    protected static $columnasDB =['id_turnos','turno_horario','estado'];

    public $id_turnos;
    public $turno_horario;
    public $estado;
    public $estado_texto;

    public function __construct($args=[]){
        $this->id_turnos = $args['id_turnos'] ?? null;
        $this->turno_horario = $args['turno_horario'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }

    public function validar() {
        $errores = [];

        if (!$this->turno_horario || trim($this->turno_horario) === '') {
            $errores[] = 'El horario es obligatorio';
            return $errores; // Si está vacío, no validar el resto
        }
        
        // 2. Limpiar espacios en blanco
        $this->turno_horario = trim($this->turno_horario);
        
        // 3. Validar formato de hora (HH:MM)
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $this->turno_horario)) {
            $errores[] = 'El horario debe tener un formato válido (HH:MM)';
        }
        
        // 4. Validar horarios de negocio (ejemplo: 08:00 a 23:59)
        $hora = explode(':', $this->turno_horario);
        if (count($hora) === 2) {
            $horas = intval($hora[0]);
            $minutos = intval($hora[1]);
            
            // Horario mínimo: 08:00
            if ($horas < 8) {
                $errores[] = 'El horario no puede ser anterior a las 08:00';
            }
            
            // Horario máximo: 23:59
            if ($horas > 23 || ($horas === 23 && $minutos > 59)) {
                $errores[] = 'El horario no puede ser posterior a las 23:59';
            }
        }
        
        // 5. Validar que no sea un horario duplicado
        if ($this->turno_horario) {
            $existe = $this->verificarHorarioUnico();
            if ($existe) {
                $errores[] = 'Ya existe un turno con ese horario';
            }
        }
        
        // 6. Validar que el estado sea válido
        if ($this->estado !== null && !in_array($this->estado, [0, 1, '0', '1'])) {
            $errores[] = 'El estado debe ser válido (0 o 1)';
        }
        
        // 7. Validar horarios múltiples de 15 minutos (opcional - común en cines)
        if ($this->turno_horario && preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $this->turno_horario, $matches)) {
            $minutos = intval($matches[2]);
            if ($minutos % 15 !== 0) {
                $errores[] = 'El horario debe ser en intervalos de 15 minutos (00, 15, 30, 45)';
            }
        }
        
        // 8. Validar horarios de alta demanda (evitar horarios muy tempranos o muy tardíos)
        if ($this->turno_horario && preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $this->turno_horario, $matches)) {
            $horas = intval($matches[0]);
            $minutos = intval($matches[1]);
            
            // Evitar horarios muy tempranos (antes de 10:00) o muy tardíos (después de 22:00)
            if ($horas < 10) {
                $errores[] = 'Se recomienda no programar funciones antes de las 10:00';
            }
            
            if ($horas > 22) {
                $errores[] = 'Se recomienda no programar funciones después de las 22:00';
            }
        }
        
        return $errores;
    }

    private function verificarHorarioUnico() {
        $db = self::getDB();
        
        // Si estamos editando, excluir el registro actual
        if ($this->id_turnos) {
            $query = "SELECT COUNT(*) as total FROM turnos 
                    WHERE turno_horario = ? 
                    AND id_turnos != ? 
                    AND estado = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$this->turno_horario, $this->id_turnos]);
        } else {
            // Si estamos creando un nuevo registro
            $query = "SELECT COUNT(*) as total FROM turnos 
                    WHERE turno_horario = ? 
                    AND estado = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$this->turno_horario]);
        }
        
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }
    public static function obtenerTurnos($paginador = null){
    $db = self::getDB();
    
    $limit = $paginador ? $paginador->limit() : '';
    
    $query = "SELECT * FROM turnos ORDER BY id_turnos ASC {$limit}";
    $resultado = $db->query($query);
    
    $turnos = [];
    while($row = $resultado->fetch_assoc()){
        $turno = new self;
        $turno->id_turnos = $row['id_turnos'];
        $turno->turno_horario = $row['turno_horario'];
        $turno->estado = $row['estado'];
        $turno->estado_texto = ($row['estado'] == 1) ? 'Activo' : 'Inactivo';
        $turnos[] = $turno;
    }
    return $turnos;
}

    public function crearTurno() {
        $query = "INSERT INTO " . static::$tabla . " (turno_horario, estado) 
                VALUES ('{$this->turno_horario}', '{$this->estado}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar(){
        $query = "UPDATE turnos SET turno_horario = ?,estado = ? WHERE id_turnos = ?";
        $stmt = self::$db->prepare($query);
      return $stmt->execute([
            $this->turno_horario,
            $this->estado,
            $this->id_turnos
            
        ]);
    }
    public function eliminarLogico(){
        $query = "UPDATE turnos SET estado = 0 WHERE id_turnos = " . self::$db->real_escape_string($this->id_turnos);
        $resultado = self::$db->query($query);
        return $resultado;
    }
    public static function buscarTurno($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT * FROM turnos WHERE id_turnos = '$termino' OR turno_horario LIKE '%$termino%' LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }
}