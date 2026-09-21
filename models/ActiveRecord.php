<?php
namespace Models;
/**
 * @property int $id_usuario
 * @property string $nombre_usuario
 * @property string $clave_usuario
 * @property string $email
 * @property int|null $rela_perfil
 * @property int|null $id_persona
 * @property string|null $token_verificacion
 * @property int|null $verificado
 * @property string|null $token_recuperacion
 */
class ActiveRecord {

    // Base DE DATOS
    protected static $db;
    protected static $tabla = '';
    protected static $columnasDB = [];

    // Alertas y Mensajes
    protected static $alertas = [];
    
    // Definir la conexión a la BD - includes/database.php
    public static function setDB($database) {
        self::$db = $database;
    }

    public static function setAlerta($tipo, $mensaje) {
        static::$alertas[$tipo][] = $mensaje;
    }

    // Validación
    public static function getAlertas() {
        return static::$alertas;
    }
    public static function getDB() {
        return self::$db;
    }

    public function validar() {
        static::$alertas = [];
        return static::$alertas;
    }

    // Consulta SQL para crear un objeto en Memoria
    public static function consultarSQL($query) {
        $resultado = self::$db->query($query);
        $array = [];
        while($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }
        $resultado->free();
        return $array;
    }

    // Crea el objeto en memoria que es igual al de la BD
    protected static function crearObjeto($registro) {
        $objeto = new static;
        foreach($registro as $key => $value ) {
            if(property_exists($objeto, $key)) {
                $objeto->$key = $value;
            }
        }
        return $objeto;
    }

    // Identificar y unir los atributos de la BD (sin incluir id)
    public function atributos() {
        $atributos = [];
        // Excluye la columna id, que es la primera de columnasDB (ej: id_persona, id_usuario)
        foreach(static::$columnasDB as $columna) {
            if($columna === static::$columnasDB[0]) continue;
            $atributos[$columna] = $this->$columna;
        }
        return $atributos;
    }

    // Sanitizar los datos antes de guardarlos en la BD (permite null)
    public function sanitizarAtributos() {
        $atributos = $this->atributos();
        $sanitizados = [];
        foreach($atributos as $key => $value) {
            $sanitizados[$key] = is_null($value) ? null : self::$db->real_escape_string($value);
        }
        return $sanitizados;
    }

    // Sincroniza BD con Objetos en memoria
    public function sincronizar($args=[]) { 
        foreach($args as $key => $value) {
            if(property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }

    // Crear un nuevo registro con manejo de null y escape
    public function crear() {
        $atributos = $this->sanitizarAtributos();

        $valores = array_map(function($valor) {
            return is_null($valor) ? "NULL" : "'".self::$db->real_escape_string($valor)."'";
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

    // Actualizar el registro
    public function actualizar() {
        $atributos = $this->sanitizarAtributos();
        $valores = [];
        foreach($atributos as $key => $value) {
            $valores[] = "{$key}=" . (is_null($value) ? "NULL" : "'{$value}'");
        }

        $query = "UPDATE " . static::$tabla ." SET ";
        $query .= join(', ', $valores);
        $query .= " WHERE " . static::$columnasDB[0] . " = '" . self::$db->real_escape_string($this->{static::$columnasDB[0]}) . "' ";
        $query .= " LIMIT 1 ";

        $resultado = self::$db->query($query);
        return $resultado;
    }
    public function guardar() {
        if (!is_null($this->{static::$columnasDB[0]})) {
            $resultado = $this->actualizar();
            return [
                'resultado' => $resultado
            ];
        } else {
            return $this->crear();
        }
    }



    // Eliminar un registro por su id
    public function eliminar() {
        $query = "DELETE FROM " . static::$tabla . " WHERE " . static::$columnasDB[0] . " = '" . self::$db->real_escape_string($this->{static::$columnasDB[0]}) . "' LIMIT 1";
        $resultado = self::$db->query($query);
        return $resultado;
    }

    public static function where($columna, $valor) {
        $columna = self::$db->real_escape_string($columna);
        $valor = self::$db->real_escape_string($valor);

        $query = "SELECT * FROM " . static::$tabla . " WHERE $columna = '$valor' LIMIT 1";
        $resultado = static::consultarSQL($query);

        return array_shift($resultado); // Devuelve el primer resultado o null
    }

   public static function find($id) {
        $id = self::$db->escape_string($id);
        $columnaID = static::$columnasDB[0];

        $query = "SELECT * FROM " . static::$tabla . " WHERE $columnaID = {$id} LIMIT 1";
        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            $datos = $resultado->fetch_assoc();
            return new static($datos);
        }

        return null;
    }


}
