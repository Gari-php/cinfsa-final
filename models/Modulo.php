<?php

namespace Models;

class Modulo extends ActiveRecord {
    protected static $tabla = 'modulos';
    protected static $columnasDB = ['id_modulo', 'modulo_nombre'];
    
    public $id_modulo;
    public $modulo_nombre;
    
    public function __construct($args = []) {
        $this->id_modulo = $args['id_modulo'] ?? null;
        $this->modulo_nombre = $args['modulo_nombre'] ?? '';
    }
}