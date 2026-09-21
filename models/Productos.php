<?php

namespace Models;

class Productos extends ActiveRecord
{
    protected static $tabla = 'productos_cantina';
    protected static $columnasDB = ['id_producto_cantina', 'nombre_producto_cantina', 'codigo', 'rela_estado_producto', 'precio_producto', 'imagen_producto'];

    public $id_producto_cantina;
    public $nombre_producto_cantina;
    public $codigo;
    public $rela_estado_producto;
    public $precio_producto;
    public $imagen_producto;
    public $nombre_estado_producto;

    public function __construct($args = [])
    {
        $this->id_producto_cantina = $args['id_producto_cantina'] ?? null;
        $this->nombre_producto_cantina = $args['nombre_producto_cantina'] ?? '';
        $this->rela_estado_producto = $args['rela_estado_producto'] ?? 1;
        $this->precio_producto = $args['precio_producto'] ?? '';
        $this->imagen_producto = $args['imagen_producto'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
    }

    public function validar()
    {
        $errores = [];

        if (!$this->nombre_producto_cantina || trim($this->nombre_producto_cantina) === '') {
            $errores[] = 'El nombre es obligatorio';
        } else {
            $this->nombre_producto_cantina = trim($this->nombre_producto_cantina);

            if (strlen($this->nombre_producto_cantina) < 5) {
                $errores[] = 'El nombre debe tener al menos 5 caracteres';
            }

            if (strlen($this->nombre_producto_cantina) > 40) {
                $errores[] = 'El nombre no puede exceder 40 caracteres';
            }

            if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.]+$/', $this->nombre_producto_cantina)) {
                $errores[] = 'El nombre solo puede contener letras, números, espacios, guiones y puntos';
            }

            if ($this->verificarNombreUnico()) {
                $errores[] = 'Ya existe un producto con ese nombre';
            }
        }

        if (!$this->precio_producto || trim($this->precio_producto) === '') {
            $errores[] = 'El precio es obligatorio';
        } else {
            $this->precio_producto = trim($this->precio_producto);

            if (preg_match('/[eE]/', $this->precio_producto)) {
                $errores[] = 'El precio no puede contener la letra "e"';
            }

            if (!is_numeric($this->precio_producto)) {
                $errores[] = 'El precio debe ser un número válido';
            } else {
                $precio = floatval($this->precio_producto);

                if ($precio <= 0) {
                    $errores[] = 'El precio debe ser mayor a 0';
                }

                if ($precio > 10000) {
                    $errores[] = 'El precio no puede exceder $10,000';
                }

                if (strpos($this->precio_producto, '.') !== false) {
                    $decimales = explode('.', $this->precio_producto)[1];
                    if (strlen($decimales) > 2) {
                        $errores[] = 'El precio no puede tener más de 2 decimales';
                    }
                }
            }
        }

        return $errores;
    }

    private function verificarNombreUnico()
    {
        $db = self::getDB();

        if ($this->id_producto_cantina) {
            $query = "SELECT COUNT(*) as total FROM productos_cantina 
                    WHERE LOWER(nombre_producto_cantina) = LOWER('{$this->nombre_producto_cantina}') 
                    AND id_producto_cantina != '{$this->id_producto_cantina}'";
        } else {
            $query = "SELECT COUNT(*) as total FROM productos_cantina 
                    WHERE LOWER(nombre_producto_cantina) = LOWER('{$this->nombre_producto_cantina}')";
        }

        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();

        return $row['total'] > 0;
    }

    public function validarImagen($archivo = null)
    {
        $errores = [];

        if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            // Si es actualización y no hay nueva imagen, no es error
            if ($this->id_producto_cantina) {
                return $errores;
            }
            // Si es creación nueva, la imagen es obligatoria
            $errores[] = 'La imagen es obligatoria';
            return $errores;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'Error al subir la imagen';
            return $errores;
        }

        // Validar tipo de archivo
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($archivo['type'], $tiposPermitidos)) {
            $errores[] = 'Solo se permiten imágenes JPG, JPEG, PNG o WEBP';
        }

        // Validar tamaño (5MB máximo)
        $tamanoMaximo = 5 * 1024 * 1024; // 5MB en bytes
        if ($archivo['size'] > $tamanoMaximo) {
            $errores[] = 'La imagen no puede pesar más de 5MB';
        }

        return $errores;
    }

    public function subirImagen($archivo)
    {
        // Crear directorio si no existe
        $carpeta = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/productos/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;

        // Ruta completa
        $rutaCompleta = $carpeta . $nombreArchivo;

        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            return $nombreArchivo;
        }

        return false;
    }

    public function eliminarImagen()
    {
        if ($this->imagen_producto) {
            $rutaImagen = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/productos/' . $this->imagen_producto;
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }
    }

    public static function obtenerProductos($paginador = null)
    {
        $db = self::getDB();

        $limit = $paginador ? $paginador->limit() : '';

        $query = "SELECT 
            p.id_producto_cantina,
            p.nombre_producto_cantina,
            p.codigo,
            p.rela_estado_producto,
            p.precio_producto,
            p.imagen_producto,
            e.nombre_estado_producto
        FROM productos_cantina p 
        LEFT JOIN estados_productos e ON p.rela_estado_producto = e.id_estado_producto
        ORDER BY p.id_producto_cantina DESC
        {$limit}";

        $resultado = $db->query($query);
        $productos = [];

        while ($row = $resultado->fetch_assoc()) {
            $producto = new self;
            $producto->id_producto_cantina = $row['id_producto_cantina'];
            $producto->nombre_producto_cantina = $row['nombre_producto_cantina'];
            $producto->codigo = $row['codigo'];
            $producto->rela_estado_producto = $row['rela_estado_producto'];
            $producto->precio_producto = $row['precio_producto'];
            $producto->imagen_producto = $row['imagen_producto'];
            $producto->nombre_estado_producto = $row['nombre_estado_producto'];
            $productos[] = $producto;
        }
        return $productos;
    }

    public static function buscarProductos($termino)
    {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT 
                p.id_producto_cantina,
                p.nombre_producto_cantina,
                p.codigo,
                p.rela_estado_producto,
                p.precio_producto,
                p.imagen_producto,
                e.nombre_estado_producto
            FROM productos_cantina p 
            LEFT JOIN estados_productos e ON p.rela_estado_producto = e.id_estado_producto
            WHERE p.nombre_producto_cantina LIKE '%$termino%'
            OR p.codigo LIKE '%$termino%'
            OR p.id_producto_cantina = '$termino'";

        $resultado = self::$db->query($query);
        $productos = [];

        while ($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }

        return $productos;
    }

    public function crearProducto()
    {
        $query = "INSERT INTO " . static::$tabla . " (nombre_producto_cantina, rela_estado_producto, precio_producto, imagen_producto)
                 VALUES ('{$this->nombre_producto_cantina}', '{$this->rela_estado_producto}', '{$this->precio_producto}', '{$this->imagen_producto}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public function actualizar()
    {
        $query = "UPDATE productos_cantina SET 
                    nombre_producto_cantina = ?, 
                    rela_estado_producto = ?, 
                    precio_producto = ?";

        $params = [
            $this->nombre_producto_cantina,
            $this->rela_estado_producto,
            $this->precio_producto
        ];

        // Solo actualizar imagen si hay una nueva
        if ($this->imagen_producto) {
            $query .= ", imagen_producto = ?";
            $params[] = $this->imagen_producto;
        }

        $query .= " WHERE id_producto_cantina = ?";
        $params[] = $this->id_producto_cantina;

        $stmt = self::$db->prepare($query);
        return $stmt->execute($params);
    }

    public function darDeBaja()
    {
        $query = "UPDATE productos_cantina SET rela_estado_producto = 3 WHERE id_producto_cantina = " . self::$db->real_escape_string($this->id_producto_cantina);
        $resultado = self::$db->query($query);
        return $resultado;
    }
    public static function obtenerProductosActivos()
    {
        $query = "
            SELECT 
                p.id_producto_cantina,
                p.nombre_producto_cantina,
                p.rela_estado_producto,
                p.precio_producto,
                p.imagen_producto,
                e.nombre_estado_producto
            FROM productos_cantina p
            LEFT JOIN estados_productos e ON p.rela_estado_producto = e.id_estado_producto
            WHERE p.rela_estado_producto IN (1, 2)
            ORDER BY p.rela_estado_producto ASC, p.nombre_producto_cantina ASC
        ";

        return self::consultarSQL($query);
    }

    public static function obtenerProductosDisponiblesConStock($termino = '')
    {
        $db = self::getDB();

        $query = "SELECT 
                    p.id_producto_cantina,
                    p.nombre_producto_cantina,
                    p.precio_producto,
                    p.codigo,
                    s.id_stock_cantina,
                    s.stock_cantina,
                    e.nombre_estado_producto
                FROM productos_cantina p
                INNER JOIN stock_cantina s ON p.id_producto_cantina = s.rela_producto_cantina
                INNER JOIN estados_productos e ON p.rela_estado_producto = e.id_estado_producto
                WHERE s.stock_cantina > 0
                AND p.rela_estado_producto IN (1, 2)";

        if (!empty($termino)) {
            $termino = $db->escape_string($termino);
            $query .= " AND (p.nombre_producto_cantina LIKE '%{$termino}%' OR p.codigo LIKE '%{$termino}%')";
        }

        $query .= " ORDER BY p.nombre_producto_cantina ASC";

        $result = $db->query($query);
        $productos = [];

        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }

        return $productos;
    }
}
