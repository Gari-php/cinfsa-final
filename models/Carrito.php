<?php

namespace Models;

class Carrito extends ActiveRecord
{

    protected static $tabla = 'carrito_temporal';
    protected static $columnasDB = [
        'id',
        'id_usuario',
        'id_producto',
        'tipo_producto',
        'id_butaca',
        'id_funcion', // AGREGAR ESTOS
        'cantidad',
        'precio_unitario',
        'fecha_agregado',
        'fecha_modificado'
    ];

    public $id;
    public $id_usuario;
    public $id_producto;
    public $tipo_producto;
    public $cantidad;
    public $fecha_agregado;
    public $fecha_modificado;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->id_usuario = $args['id_usuario'] ?? null;
        $this->id_producto = $args['id_producto'] ?? null;
        $this->tipo_producto = $args['tipo_producto'] ?? '';
        $this->cantidad = $args['cantidad'] ?? 1;
        $this->fecha_agregado = $args['fecha_agregado'] ?? date('Y-m-d H:i:s');
        $this->fecha_modificado = $args['fecha_modificado'] ?? null;
    }

    public static function agregarItem($idUsuario, $idProducto, $tipo, $cantidad, $precio = null, $idButaca = null, $idFuncion = null)
    {
        $idUsuario = self::$db->escape_string($idUsuario);
        $idProducto = self::$db->escape_string($idProducto);
        $tipo = self::$db->escape_string($tipo);
        $cantidad = (int)$cantidad;
        $idButaca = $idButaca ? self::$db->escape_string($idButaca) : 'NULL';
        $idFuncion = $idFuncion ? self::$db->escape_string($idFuncion) : 'NULL';

        // Para butacas
        if ($tipo === 'butacas' || $tipo === 'funciones') {
            $precio = $precio !== null ? (float)$precio : 'NULL';

            $query = "INSERT INTO " . static::$tabla . " 
                    (id_usuario, id_producto, tipo_producto, id_butaca, id_funcion, cantidad, precio_unitario, fecha_agregado) 
                    VALUES ('{$idUsuario}', '{$idProducto}', '{$tipo}', {$idButaca}, {$idFuncion}, {$cantidad}, {$precio}, NOW())";

            return self::$db->query($query);
        }

        // Para cantina y fichas: verificar si existe
        $queryCheck = "SELECT id, cantidad FROM " . static::$tabla . " 
                    WHERE id_usuario = '{$idUsuario}' 
                    AND id_producto = '{$idProducto}' 
                    AND tipo_producto = '{$tipo}'
                    AND id_butaca IS NULL";

        $resultado = self::$db->query($queryCheck);

        if ($resultado && $resultado->num_rows > 0) {
            $item = $resultado->fetch_assoc();
            $nuevaCantidad = $item['cantidad'] + $cantidad;

            $queryUpdate = "UPDATE " . static::$tabla . " 
                        SET cantidad = {$nuevaCantidad}, fecha_modificado = NOW() 
                        WHERE id = '{$item['id']}'";

            return self::$db->query($queryUpdate);
        } else {
            // NUEVO ITEM - INCLUIR PRECIO
            if ($precio !== null) {
                $precio = (float)$precio;
                $query = "INSERT INTO " . static::$tabla . " 
                        (id_usuario, id_producto, tipo_producto, cantidad, precio_unitario, fecha_agregado) 
                        VALUES ('{$idUsuario}', '{$idProducto}', '{$tipo}', {$cantidad}, {$precio}, NOW())";
            } else {
                $query = "INSERT INTO " . static::$tabla . " 
                        (id_usuario, id_producto, tipo_producto, cantidad, fecha_agregado) 
                        VALUES ('{$idUsuario}', '{$idProducto}', '{$tipo}', {$cantidad}, NOW())";
            }

            return self::$db->query($query);
        }
    }


    public static function obtenerItemCarrito($idUsuario, $idProducto, $tipo)
    {
        $idUsuario = self::$db->escape_string($idUsuario);
        $idProducto = self::$db->escape_string($idProducto);
        $tipo = self::$db->escape_string($tipo);

        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE id_usuario = '{$idUsuario}' 
                  AND id_producto = '{$idProducto}' 
                  AND tipo_producto = '{$tipo}' 
                  LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }


    public static function obtenerItemPorId($idItem, $idUsuario)
    {
        $idItem = self::$db->escape_string($idItem);
        $idUsuario = self::$db->escape_string($idUsuario);

        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE id = '{$idItem}' AND id_usuario = '{$idUsuario}' 
                  LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }


    public static function obtenerCarritoCompleto($idUsuario)
    {
        $idUsuario = self::$db->escape_string($idUsuario);

        $query = "SELECT 
                ct.id,
                ct.id_producto,
                ct.tipo_producto,
                ct.id_butaca,
                ct.id_funcion,
                ct.cantidad,
                ct.precio_unitario,
                ct.fecha_agregado,
                CASE 
                    WHEN ct.tipo_producto = 'cantina' THEN pc.nombre_producto_cantina
                    WHEN ct.tipo_producto = 'fichas' THEN CONCAT('Fichas - ', m.maquinas_nombre)
                    WHEN ct.tipo_producto = 'butacas' THEN CONCAT(p.titulo_pelicula, ' - Butaca ', b.fila_butaca, '-', b.numero_butaca)
                    ELSE 'Producto'
                END as nombre,
                CASE 
                    WHEN ct.tipo_producto = 'cantina' THEN pc.precio_producto
                    WHEN ct.tipo_producto = 'fichas' THEN f.precio_ficha
                    WHEN ct.tipo_producto = 'butacas' THEN ct.precio_unitario
                    ELSE 0
                END as precio,
                CASE 
                    WHEN ct.tipo_producto = 'cantina' THEN CONCAT('/assets/img/productos/', pc.imagen_producto)
                    WHEN ct.tipo_producto = 'butacas' THEN CONCAT('/assets/img/peliculas/', p.imagen_pelicula)
                    ELSE '/assets/img/cart.png'
                END as imagen
            FROM " . static::$tabla . " ct
            LEFT JOIN productos_cantina pc ON ct.id_producto = pc.id_producto_cantina 
                AND ct.tipo_producto = 'cantina'
            LEFT JOIN maquinas m ON ct.id_producto = m.id_maquinas 
                AND ct.tipo_producto = 'fichas'
            LEFT JOIN fichas f ON m.rela_fichas = f.id_fichas
                AND ct.tipo_producto = 'fichas'
            LEFT JOIN butacas b ON ct.id_butaca = b.id_butaca
                AND ct.tipo_producto = 'butacas'
            LEFT JOIN funciones fun ON ct.id_funcion = fun.id_funcion
                AND ct.tipo_producto = 'butacas'
            LEFT JOIN peliculas p ON fun.rela_peliculas = p.id_pelicula
            WHERE ct.id_usuario = '{$idUsuario}'
            ORDER BY ct.fecha_agregado DESC";

        $resultado = self::$db->query($query);
        $items = [];

        while ($fila = $resultado->fetch_assoc()) {
            $items[] = $fila;
        }

        return $items;
    }


    public static function actualizarCantidad($idItem, $nuevaCantidad)
    {
        $idItem = self::$db->escape_string($idItem);
        $nuevaCantidad = (int)$nuevaCantidad;

        $query = "UPDATE " . static::$tabla . " 
                  SET cantidad = {$nuevaCantidad}, fecha_modificado = NOW() 
                  WHERE id = '{$idItem}'";

        return self::$db->query($query);
    }


    public static function eliminarItem($idItem, $idUsuario)
    {
        $idItem = self::$db->escape_string($idItem);
        $idUsuario = self::$db->escape_string($idUsuario);

        $query = "DELETE FROM " . static::$tabla . " 
                  WHERE id = '{$idItem}' AND id_usuario = '{$idUsuario}'";

        return self::$db->query($query);
    }


    public static function vaciarCarrito($idUsuario)
    {
        $idUsuario = self::$db->escape_string($idUsuario);

        $query = "DELETE FROM " . static::$tabla . " 
                  WHERE id_usuario = '{$idUsuario}'";

        return self::$db->query($query);
    }


    public static function verificarStockDisponible($idProducto, $cantidad, $tipo)
    {
        if ($tipo === 'cantina') {
            $stock = Stock::obtenerStockPorProductoYCantina($idProducto, 1);
            return $stock && $stock->stock_cantina >= $cantidad;
        } elseif ($tipo === 'fichas') {
            // CAMBIO: Buscar la máquina primero, luego la ficha
            $maquina = Maquina::find($idProducto);
            if (!$maquina || !$maquina->rela_fichas) {
                return false;
            }

            $ficha = Ficha::find($maquina->rela_fichas);
            return $ficha && $ficha->cantidad_ficha >= $cantidad;
        }

        return false;
    }
    public static function obtenerStockActual($idProducto, $tipo)
    {
        if ($tipo === 'cantina') {
            $stock = Stock::obtenerStockPorProductoYCantina($idProducto, 1);
            return $stock ? $stock->stock_cantina : 0;
        } elseif ($tipo === 'fichas') {
            // CAMBIO: Buscar la máquina primero, luego la ficha
            $maquina = Maquina::find($idProducto);
            if (!$maquina || !$maquina->rela_fichas) {
                return 0;
            }

            $ficha = Ficha::find($maquina->rela_fichas);
            return $ficha ? $ficha->cantidad_ficha : 0;
        }

        return 0;
    }


    public static function calcularTotal($idUsuario)
    {
        $idUsuario = self::$db->escape_string($idUsuario);

        $query = "SELECT 
                SUM(
                    CASE 
                        WHEN ct.tipo_producto = 'cantina' THEN pc.precio_producto * ct.cantidad
                        WHEN ct.tipo_producto = 'fichas' THEN f.precio_ficha * ct.cantidad
                        WHEN ct.tipo_producto = 'butacas' THEN ct.precio_unitario * ct.cantidad
                        ELSE 0
                    END
                ) as total
            FROM " . static::$tabla . " ct
            LEFT JOIN productos_cantina pc ON ct.id_producto = pc.id_producto_cantina 
                AND ct.tipo_producto = 'cantina'
            LEFT JOIN maquinas m ON ct.id_producto = m.id_maquinas 
                AND ct.tipo_producto = 'fichas'
            LEFT JOIN fichas f ON m.rela_fichas = f.id_fichas
                AND ct.tipo_producto = 'fichas'
            WHERE ct.id_usuario = '{$idUsuario}'";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            $fila = $resultado->fetch_assoc();
            return (float)($fila['total'] ?? 0);
        }

        return 0;
    }

    public static function obtenerEstadisticas($idUsuario)
    {
        $idUsuario = self::$db->escape_string($idUsuario);

        $query = "SELECT 
                    COUNT(*) as total_items,
                    SUM(ct.cantidad) as total_productos,
                    SUM(
                        CASE 
                            WHEN ct.tipo_producto = 'cantina' THEN pc.precio_producto * ct.cantidad
                            WHEN ct.tipo_producto = 'fichas' THEN f.precio_ficha * ct.cantidad
                            WHEN ct.tipo_producto = 'butacas' THEN ct.precio_unitario * ct.cantidad
                            ELSE 0
                        END
                    ) as total_precio
                FROM " . static::$tabla . " ct
                LEFT JOIN productos_cantina pc ON ct.id_producto = pc.id_producto_cantina 
                    AND ct.tipo_producto = 'cantina'
                LEFT JOIN maquinas m ON ct.id_producto = m.id_maquinas 
                    AND ct.tipo_producto = 'fichas'
                LEFT JOIN fichas f ON m.rela_fichas = f.id_fichas
                    AND ct.tipo_producto = 'fichas'
                WHERE ct.id_usuario = '{$idUsuario}'";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return [
            'total_items' => 0,
            'total_productos' => 0,
            'total_precio' => 0
        ];
    }


    public static function procesarVentaCarrito($idUsuario, $idOrden = null)
    {
        $items = self::obtenerCarritoCompleto($idUsuario);
        $errores = [];

        foreach ($items as $item) {
            if ($item['tipo_producto'] === 'cantina') {
                $stock = Stock::obtenerStockPorProductoYCantina($item['id_producto'], 1);
                if ($stock) {
                    $resultado = $stock->reducirStock($item['cantidad']);
                    if (!$resultado['resultado']) {
                        $errores[] = "Error con {$item['nombre']}: {$resultado['mensaje']}";
                    } else {
                        self::registrarMovimiento('cantina', $item['id_producto'], 'venta', $item['cantidad'], $idUsuario, $idOrden);
                    }
                }
            } elseif ($item['tipo_producto'] === 'fichas') {
                $maquina = Maquina::find($item['id_producto']);
                if ($maquina && $maquina->rela_fichas) {
                    $ficha = Ficha::find($maquina->rela_fichas);
                    if ($ficha && $ficha->cantidad_ficha >= $item['cantidad']) {
                        $ficha->cantidad_ficha -= $item['cantidad'];
                        $ficha->guardar();

                        self::registrarMovimiento('fichas', $item['id_producto'], 'venta', $item['cantidad'], $idUsuario, $idOrden);
                    } else {
                        $errores[] = "Fichas insuficientes para {$item['nombre']}";
                    }
                }
            }
        }

        if (empty($errores)) {
            self::vaciarCarrito($idUsuario);
            return ['resultado' => true, 'mensaje' => 'Venta procesada correctamente'];
        }

        return ['resultado' => false, 'errores' => $errores];
    }


    private static function registrarMovimiento($tipoProducto, $idProducto, $tipoMovimiento, $cantidad, $idUsuario, $idOrden = null)
    {
        $query = "INSERT INTO movimientos_stock 
                  (tipo_producto, id_producto, tipo_movimiento, cantidad, id_usuario, id_orden, fecha) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = self::$db->prepare($query);
        return $stmt->execute([$tipoProducto, $idProducto, $tipoMovimiento, $cantidad, $idUsuario, $idOrden]);
    }

    public static function limpiarCarritosAbandonados()
    {
        $query = "DELETE FROM " . static::$tabla . " 
                  WHERE fecha_agregado < DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        return self::$db->query($query);
    }
}
