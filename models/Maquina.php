<?php

namespace Models;

class Maquina extends ActiveRecord
{
    protected static $tabla = 'maquinas';
    protected static $columnasDB = ['id_maquinas', 'maquinas_nombre', 'maquina_descripcion', 'imagen_maquina', 'estado', 'rela_fichas'];

    public $id_maquinas;
    public $maquinas_nombre;
    public $maquina_descripcion;
    public $imagen_maquina;
    public $estado;
    public $rela_fichas;

    // Propiedades adicionales para mostrar info de fichas
    public $precio_ficha;
    public $cantidad_ficha;
    public $id_fichas;

    public function __construct($args = [])
    {
        $this->id_maquinas = $args['id_maquinas'] ?? null;
        $this->maquinas_nombre = $args['maquinas_nombre'] ?? '';
        $this->maquina_descripcion = $args['maquina_descripcion'] ?? '';
        $this->imagen_maquina = $args['imagen_maquina'] ?? '';
        $this->estado = $args['estado'] ?? 1;
        $this->rela_fichas = $args['rela_fichas'] ?? 1;
    }

    public function validar()
    {
        $errores = [];

        if (!$this->maquinas_nombre || trim($this->maquinas_nombre) === '') {
            $errores[] = 'El nombre de la máquina es obligatorio';
        } else {
            $this->maquinas_nombre = trim($this->maquinas_nombre);

            if (strlen($this->maquinas_nombre) < 3) {
                $errores[] = 'El nombre debe tener al menos 3 caracteres';
            }

            if (strlen($this->maquinas_nombre) > 50) {
                $errores[] = 'El nombre no puede exceder 50 caracteres';
            }

            if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\_]+$/', $this->maquinas_nombre)) {
                $errores[] = 'El nombre solo puede contener letras, números, espacios, guiones y guiones bajos';
            }

            if ($this->verificarNombreUnico()) {
                $errores[] = 'Ya existe una máquina con ese nombre';
            }
        }

        if (!$this->maquina_descripcion || trim($this->maquina_descripcion) === '') {
            $errores[] = 'La descripción es obligatoria';
        } else {
            $this->maquina_descripcion = trim($this->maquina_descripcion);

            if (strlen($this->maquina_descripcion) < 10) {
                $errores[] = 'La descripción debe tener al menos 10 caracteres';
            }

            if (strlen($this->maquina_descripcion) > 200) {
                $errores[] = 'La descripción no puede exceder 200 caracteres';
            }
        }

        return $errores;
    }

    private function verificarNombreUnico()
    {
        $db = self::getDB();

        if ($this->id_maquinas) {
            $query = "SELECT COUNT(*) as total FROM maquinas 
                    WHERE LOWER(maquinas_nombre) = LOWER('{$this->maquinas_nombre}') 
                    AND id_maquinas != '{$this->id_maquinas}'";
        } else {
            $query = "SELECT COUNT(*) as total FROM maquinas 
                    WHERE LOWER(maquinas_nombre) = LOWER('{$this->maquinas_nombre}')";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }

    public static function obtenerTodas($paginador = null)
    {
        $db = self::getDB();

        $limit = $paginador ? $paginador->limit() : '';

        $query = "SELECT 
                    m.id_maquinas,
                    m.maquinas_nombre,
                    m.maquina_descripcion,
                    m.imagen_maquina,
                    m.estado,
                    m.rela_fichas,
                    f.precio_ficha,
                    f.cantidad_ficha
                FROM maquinas m
                LEFT JOIN fichas f ON m.rela_fichas = f.id_fichas
                ORDER BY m.id_maquinas DESC
                {$limit}";

        $resultado = $db->query($query);

        $maquinas = [];
        while ($row = $resultado->fetch_assoc()) {
            $maquina = new self;
            $maquina->id_maquinas = $row['id_maquinas'];
            $maquina->maquinas_nombre = $row['maquinas_nombre'];
            $maquina->maquina_descripcion = $row['maquina_descripcion'];
            $maquina->imagen_maquina = $row['imagen_maquina'];
            $maquina->estado = $row['estado'];
            $maquina->rela_fichas = $row['rela_fichas'];
            $maquina->precio_ficha = $row['precio_ficha'] ?? 0;
            $maquina->cantidad_ficha = $row['cantidad_ficha'] ?? 0;
            $maquinas[] = $maquina;
        }
        return $maquinas;
    }

    public function crearMaquina()
    {
        $query = "INSERT INTO " . static::$tabla . " (maquinas_nombre, maquina_descripcion, imagen_maquina, estado, rela_fichas) 
                 VALUES ('{$this->maquinas_nombre}', '{$this->maquina_descripcion}', '{$this->imagen_maquina}', '{$this->estado}', '{$this->rela_fichas}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }


    public static function buscarMaquina($termino)
    {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT 
                    m.*,
                    f.precio_ficha,
                    f.cantidad_ficha
              FROM maquinas m
              LEFT JOIN fichas f ON m.rela_fichas = f.id_fichas
              WHERE m.id_maquinas = '$termino' OR m.maquinas_nombre LIKE '%$termino%'
              ORDER BY m.id_maquinas DESC";

        $resultado = self::$db->query($query);
        $maquinas = [];

        while ($row = $resultado->fetch_assoc()) {
            $maquinas[] = $row;
        }

        return $maquinas;
    }


    public function actualizar()
    {
        $query = "UPDATE maquinas SET maquinas_nombre = ?, maquina_descripcion = ?, imagen_maquina = ?, estado = ?, rela_fichas = ? WHERE id_maquinas = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->maquinas_nombre,
            $this->maquina_descripcion,
            $this->imagen_maquina,
            $this->estado,
            $this->rela_fichas,
            $this->id_maquinas
        ]);
    }

    public function darDeBaja()
    {
        $query = "UPDATE maquinas SET estado = 0 WHERE id_maquinas = " . self::$db->real_escape_string($this->id_maquinas);
        $resultado = self::$db->query($query);
        return $resultado;
    }


    public static function obtenerMaquinasActivas()
    {
        $db = self::getDB();

        $resultado = $db->query("SELECT 
                                        m.id_maquinas,
                                        m.maquinas_nombre,
                                        m.maquina_descripcion,
                                        m.imagen_maquina,
                                        m.estado,
                                        m.rela_fichas,
                                        f.precio_ficha,
                                        f.cantidad_ficha,
                                        f.id_fichas
                                        FROM maquinas m
                                        INNER JOIN fichas f ON m.rela_fichas = f.id_fichas
                                        WHERE m.estado = 1 AND f.cantidad_ficha > 0
                                        ORDER BY m.maquinas_nombre ASC");

        $maquinas = [];
        while ($row = $resultado->fetch_assoc()) {
            $maquina = new self;
            $maquina->id_maquinas = $row['id_maquinas'];
            $maquina->maquinas_nombre = $row['maquinas_nombre'];
            $maquina->maquina_descripcion = $row['maquina_descripcion'];
            $maquina->imagen_maquina = $row['imagen_maquina'];
            $maquina->estado = $row['estado'];
            $maquina->rela_fichas = $row['rela_fichas'];

            // Información de fichas
            $maquina->precio_ficha = $row['precio_ficha'];
            $maquina->cantidad_ficha = $row['cantidad_ficha'];
            $maquina->id_fichas = $row['id_fichas'];

            $maquinas[] = $maquina;
        }
        return $maquinas;
    }

    public static function obtenerMaquinasConFichas()
    {
        $query = "SELECT 
                    m.*,
                    f.precio_ficha,
                    f.cantidad_ficha
                FROM maquinas m
                LEFT JOIN fichas f ON m.rela_fichas = f.id_fichas
                WHERE m.estado = 1
                ORDER BY m.maquinas_nombre ASC";

        $resultado = self::$db->query($query);
        $disponibles = [];
        $noDisponibles = [];

        while ($fila = $resultado->fetch_object()) {
            if ($fila->cantidad_ficha > 0) {
                $disponibles[] = $fila;
            } else {
                $noDisponibles[] = $fila;
            }
        }

        return [
            'disponibles' => $disponibles,
            'no_disponibles' => $noDisponibles
        ];
    }
}
