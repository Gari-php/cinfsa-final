<?php

namespace Models;

class Persona extends ActiveRecord{
    public static $tabla='personas';
    public static $columnasDB=['id_persona','nombre_persona','apellido_persona','fecha_nacimiento',
            'fecha_alta','estado','fecha_baja','rela_sexo'];
    public $id_persona;
    public $nombre_persona;
    public $apellido_persona;
    public $fecha_nacimiento;
    public $fecha_alta;
    public $estado;
    public $fecha_baja;
    public $rela_sexo;
    
    public function __construct($args=[]){
        $this->id_persona=$args['id_persona'] ?? null;
        $this->nombre_persona=$args['nombre_persona'] ?? '';
        $this->apellido_persona=$args['apellido_persona'] ?? '';
        $this->fecha_nacimiento=$args['fecha_nacimiento'] ?? '';
        $this->fecha_alta=$args['fecha_alta'] ?? date('Y-m-d H:i:s');
        $this->estado=$args['estado'] ?? 1;
        $this->fecha_baja = $args['fecha_baja'] ?? null;
        $this->rela_sexo=$args['rela_sexo'] ?? '';
    }
    
    public function validar() {
        $errores = [];
        
        // Validar nombre
        if (!$this->nombre_persona || trim($this->nombre_persona) === '') {
            $errores[] = 'El nombre es obligatorio';
        } else {
            $this->nombre_persona = trim($this->nombre_persona);
            
            if (strlen($this->nombre_persona) < 2) {
                $errores[] = 'El nombre debe tener al menos 2 caracteres';
            }
            
            if (strlen($this->nombre_persona) > 50) {
                $errores[] = 'El nombre no puede tener más de 50 caracteres';
            }
            
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $this->nombre_persona)) {
                $errores[] = 'El nombre solo puede contener letras y espacios';
            }
        }
        
        // Validar apellido
        if (!$this->apellido_persona || trim($this->apellido_persona) === '') {
            $errores[] = 'El apellido es obligatorio';
        } else {
            $this->apellido_persona = trim($this->apellido_persona);
            
            if (strlen($this->apellido_persona) < 3) {
                $errores[] = 'El apellido debe tener al menos 3 caracteres';
            }
            
            if (strlen($this->apellido_persona) > 50) {
                $errores[] = 'El apellido no puede tener más de 50 caracteres';
            }
            
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $this->apellido_persona)) {
                $errores[] = 'El apellido solo puede contener letras y espacios';
            }
        }
        
        // Validar fecha de nacimiento
        if (!$this->fecha_nacimiento || trim($this->fecha_nacimiento) === '') {
            $errores[] = 'La fecha de nacimiento es obligatoria';
        } else {
            $this->fecha_nacimiento = trim($this->fecha_nacimiento);
            
            // Validar formato de fecha
            $fecha = \DateTime::createFromFormat('Y-m-d', $this->fecha_nacimiento);
            if (!$fecha || $fecha->format('Y-m-d') !== $this->fecha_nacimiento) {
                $errores[] = 'El formato de fecha de nacimiento no es válido (YYYY-MM-DD)';
            } else {
                $hoy = new \DateTime();
                $edad = $hoy->diff($fecha)->y;
                
                // Validar edad mínima (13 años para registro)
                if ($edad < 13) {
                    $errores[] = 'Debe ser mayor de 13 años para registrarse';
                }
                
                // Validar edad máxima (120 años es razonable)
                if ($edad > 120) {
                    $errores[] = 'La fecha de nacimiento no es válida';
                }
                
                // Validar que la fecha no sea futura
                if ($fecha > $hoy) {
                    $errores[] = 'La fecha de nacimiento no puede ser futura';
                }
            }
        }
        
        // Validar sexo
        if (!$this->rela_sexo || trim($this->rela_sexo) === '') {
            $errores[] = 'Debe seleccionar un sexo';
        } else {
            $this->rela_sexo = trim($this->rela_sexo);
            
            if (!is_numeric($this->rela_sexo)) {
                $errores[] = 'El valor de sexo no es válido';
            } else {
                $sexo_id = intval($this->rela_sexo);
                
                // Validar que el ID del sexo exista en la base de datos
                $db = self::getDB();
                $resultado = $db->query("SELECT id_sexo FROM sexo WHERE id_sexo = $sexo_id");
                
                if (!$resultado || $resultado->num_rows === 0) {
                    $errores[] = 'El sexo seleccionado no es válido';
                }
            }
        }
        
        return $errores;
    }
    
    public function crear() {
        $atributos = $this->sanitizarAtributos();

        // Eliminar el id si existe (para no insertarlo manualmente)
        unset($atributos['id_persona']);

        // Armamos valores con manejo de NULL (sin comillas si es null)
        $valores = array_map(function($valor) {
            return $valor === null ? 'NULL' : "'" . self::$db->real_escape_string($valor) . "'";
        }, array_values($atributos));

        $query = "INSERT INTO " . static::$tabla . " (";
        $query .= join(', ', array_keys($atributos));
        $query .= ") VALUES (";
        $query .= join(', ', $valores);
        $query .= ")";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id' => self::$db->insert_id
        ];
    }

    public function guardar() {
        if (!is_null($this->id_persona)) {
            $resultado = $this->actualizar();
            return ['resultado' => $resultado];
        } else {
            return $this->crear();
        }
    }
}