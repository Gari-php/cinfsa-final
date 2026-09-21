<?php

namespace Models;

class Proveedor extends ActiveRecord
{
    protected static $tabla = 'proveedores';
    protected static $columnasDB = [
        'id_proveedor',
        'razon_social',
        'nombre_comercial',
        'rut',
        'tipo_proveedor',
        'telefono',
        'email',
        'direccion',
        'sitio_web',
        'notas',
        'activo',
        'fecha_alta',
        'fecha_modificacion'
    ];

    public $id_proveedor;
    public $razon_social;
    public $nombre_comercial;
    public $rut;
    public $tipo_proveedor;
    public $telefono;
    public $email;
    public $direccion;
    public $sitio_web;
    public $notas;
    public $activo;
    public $fecha_alta;
    public $fecha_modificacion;

    public function __construct($args = [])
    {
        $this->id_proveedor = $args['id_proveedor'] ?? null;
        $this->razon_social = $args['razon_social'] ?? '';
        $this->nombre_comercial = $args['nombre_comercial'] ?? '';
        $this->rut = $args['rut'] ?? '';
        $this->tipo_proveedor = $args['tipo_proveedor'] ?? 'otros';
        $this->telefono = $args['telefono'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->direccion = $args['direccion'] ?? '';
        $this->sitio_web = $args['sitio_web'] ?? '';
        $this->notas = $args['notas'] ?? '';
        $this->activo = $args['activo'] ?? 1;
        $this->fecha_alta = $args['fecha_alta'] ?? date('Y-m-d H:i:s');
        $this->fecha_modificacion = date('Y-m-d H:i:s');
    }

    public function validar()
    {
        $errores = [];

        // Validar razón social
        if (!$this->razon_social || trim($this->razon_social) === '') {
            $errores[] = 'La razón social es obligatoria';
        } else {
            $this->razon_social = trim($this->razon_social);

            if (strlen($this->razon_social) < 3) {
                $errores[] = 'La razón social debe tener al menos 3 caracteres';
            }

            if (strlen($this->razon_social) > 200) {
                $errores[] = 'La razón social no puede exceder 200 caracteres';
            }
        }

        // Validar nombre comercial
        if (!$this->nombre_comercial || trim($this->nombre_comercial) === '') {
            $errores[] = 'El nombre comercial es obligatorio';
        } else {
            $this->nombre_comercial = trim($this->nombre_comercial);

            if (strlen($this->nombre_comercial) < 3) {
                $errores[] = 'El nombre comercial debe tener al menos 3 caracteres';
            }

            if (strlen($this->nombre_comercial) > 150) {
                $errores[] = 'El nombre comercial no puede exceder 150 caracteres';
            }
        }

        // Validar RUT/CUIT
        if (!$this->rut || trim($this->rut) === '') {
            $errores[] = 'El RUT/CUIT es obligatorio';
        } else {
            $this->rut = trim($this->rut);

            if (strlen($this->rut) > 20) {
                $errores[] = 'El RUT/CUIT no puede exceder 20 caracteres';
            }

            // Validar formato argentino básico (XX-XXXXXXXX-X)
            if (!preg_match('/^\d{2}-\d{8}-\d{1}$/', $this->rut)) {
                $errores[] = 'El RUT/CUIT debe tener formato válido (XX-XXXXXXXX-X)';
            }

            // Verificar que sea único
            if ($this->verificarRutUnico()) {
                $errores[] = 'Ya existe un proveedor con ese RUT/CUIT';
            }
        }

        // Validar tipo de proveedor
        $tiposValidos = ['peliculas', 'servicios', 'productos', 'otros'];
        if (!in_array($this->tipo_proveedor, $tiposValidos)) {
            $errores[] = 'El tipo de proveedor no es válido';
        }

        // Validar teléfono (opcional pero con formato)
        if ($this->telefono && trim($this->telefono) !== '') {
            $this->telefono = trim($this->telefono);

            if (strlen($this->telefono) > 30) {
                $errores[] = 'El teléfono no puede exceder 30 caracteres';
            }
        }

        // Validar email (opcional pero con formato)
        if ($this->email && trim($this->email) !== '') {
            $this->email = trim($this->email);

            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El formato del email no es válido';
            }

            if (strlen($this->email) > 100) {
                $errores[] = 'El email no puede exceder 100 caracteres';
            }
        }

        // Validar sitio web (opcional)
        if ($this->sitio_web && trim($this->sitio_web) !== '') {
            $this->sitio_web = trim($this->sitio_web);

            if (strlen($this->sitio_web) > 200) {
                $errores[] = 'El sitio web no puede exceder 200 caracteres';
            }
        }

        // Validar dirección (opcional)
        if ($this->direccion && trim($this->direccion) !== '') {
            $this->direccion = trim($this->direccion);
        }

        // Validar notas (opcional)
        if ($this->notas && trim($this->notas) !== '') {
            $this->notas = trim($this->notas);
        }

        return $errores;
    }

    private function verificarRutUnico()
    {
        $db = self::getDB();

        if ($this->id_proveedor) {
            $query = "SELECT COUNT(*) as total FROM proveedores 
                      WHERE rut = '{$this->rut}' 
                      AND id_proveedor != '{$this->id_proveedor}'";
        } else {
            $query = "SELECT COUNT(*) as total FROM proveedores 
                      WHERE rut = '{$this->rut}'";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }

    public function crearProveedor()
    {
        $query = "INSERT INTO " . static::$tabla . " 
                  (razon_social, nombre_comercial, rut, tipo_proveedor, telefono, 
                   email, direccion, sitio_web, notas, activo, fecha_alta, fecha_modificacion) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = self::$db->prepare($query);
        $resultado = $stmt->execute([
            $this->razon_social,
            $this->nombre_comercial,
            $this->rut,
            $this->tipo_proveedor,
            $this->telefono,
            $this->email,
            $this->direccion,
            $this->sitio_web,
            $this->notas,
            $this->activo,
            $this->fecha_alta,
            $this->fecha_modificacion
        ]);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar()
    {
        $query = "UPDATE proveedores SET 
                  razon_social = ?, 
                  nombre_comercial = ?, 
                  rut = ?, 
                  tipo_proveedor = ?, 
                  telefono = ?, 
                  email = ?, 
                  direccion = ?, 
                  sitio_web = ?, 
                  notas = ?, 
                  activo = ?, 
                  fecha_modificacion = ?
                  WHERE id_proveedor = ?";

        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->razon_social,
            $this->nombre_comercial,
            $this->rut,
            $this->tipo_proveedor,
            $this->telefono,
            $this->email,
            $this->direccion,
            $this->sitio_web,
            $this->notas,
            $this->activo,
            $this->fecha_modificacion,
            $this->id_proveedor
        ]);
    }

    public function darDeBaja()
    {
        $query = "UPDATE proveedores SET activo = 0, fecha_modificacion = NOW() WHERE id_proveedor = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([$this->id_proveedor]);
    }

    public static function obtenerTodos($paginador = null)
    {
        $db = self::getDB();

        $limit = $paginador ? $paginador->limit() : '';

        $query = "SELECT * FROM proveedores ORDER BY id_proveedor DESC {$limit}";
        $resultado = $db->query($query);

        $proveedores = [];
        while ($row = $resultado->fetch_assoc()) {
            $proveedor = new self;
            $proveedor->id_proveedor = $row['id_proveedor'];
            $proveedor->razon_social = $row['razon_social'];
            $proveedor->nombre_comercial = $row['nombre_comercial'];
            $proveedor->rut = $row['rut'];
            $proveedor->tipo_proveedor = $row['tipo_proveedor'];
            $proveedor->telefono = $row['telefono'];
            $proveedor->email = $row['email'];
            $proveedor->direccion = $row['direccion'];
            $proveedor->sitio_web = $row['sitio_web'];
            $proveedor->notas = $row['notas'];
            $proveedor->activo = $row['activo'];
            $proveedor->fecha_alta = $row['fecha_alta'];
            $proveedor->fecha_modificacion = $row['fecha_modificacion'];
            $proveedores[] = $proveedor;
        }
        return $proveedores;
    }

    public static function buscarProveedor($termino)
    {
        $termino = self::$db->escape_string($termino);
        $query = "SELECT * FROM proveedores 
              WHERE id_proveedor = '$termino' 
              OR razon_social LIKE '%$termino%' 
              OR nombre_comercial LIKE '%$termino%' 
              OR rut LIKE '%$termino%'
              ORDER BY id_proveedor DESC";
        $resultado = self::$db->query($query);

        $proveedores = [];
        while ($row = $resultado->fetch_assoc()) {
            $proveedores[] = $row;
        }

        return $proveedores;
    }
}
