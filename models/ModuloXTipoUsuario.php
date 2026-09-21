<?php
namespace Models;
class ModuloXTipoUsuario extends ActiveRecord {
    protected static $tabla = 'modulo_x_tipos_de_usuarios';
    protected static $columnasDB = ['id_mod_x_tipo', 'estado', 'rela_tipos_de_usuarios', 'rela_modulo'];
    
    public $id_mod_x_tipo;
    public $estado;
    public $rela_tipos_de_usuarios;
    public $rela_modulo;
    
    public function __construct($args = []) {
        $this->id_mod_x_tipo = $args['id_mod_x_tipo'] ?? null;
        $this->estado = $args['estado'] ?? 1;
        $this->rela_tipos_de_usuarios = $args['rela_tipos_de_usuarios'] ?? null;
        $this->rela_modulo = $args['rela_modulo'] ?? null;
    }
}