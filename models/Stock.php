<?php
namespace Models;

class Stock extends ActiveRecord {
    
    // Con esta cantidad o menos, el stock se considera bajo y se avisa al administrador
    const UMBRAL_STOCK_BAJO = 15;

    protected static $tabla = 'stock_cantina';
    protected static $columnasDB = ['id_stock_cantina', 'stock_cantina', 'rela_producto_cantina', 'rela_cantina'];
    
    public $id_stock_cantina;
    public $stock_cantina;
    public $rela_producto_cantina;
    public $rela_cantina;
    
  
    public $nombre_producto_cantina;
    public $nombre_estado_producto;
    public $nombre_cantina;
    
    public function __construct($args = []) {
        $this->id_stock_cantina = $args['id_stock_cantina'] ?? null;
        $this->stock_cantina = $args['stock_cantina'] ?? '';
        $this->rela_producto_cantina = $args['rela_producto_cantina'] ?? '';
        $this->rela_cantina = $args['rela_cantina'] ?? '';
    }
    
   public function validar() {
        $errores = [];
        
        if (!$this->stock_cantina || trim($this->stock_cantina) === '') {
            $errores[] = 'El stock es obligatorio';
        } else {
            $this->stock_cantina = trim($this->stock_cantina);
            
            if (!is_numeric($this->stock_cantina)) {
                $errores[] = 'El stock debe ser un número válido';
            } else {
                if (!ctype_digit($this->stock_cantina)) {
                    $errores[] = 'El stock debe ser un número entero (sin decimales)';
                }
                
                $stock = intval($this->stock_cantina);
                
                if ($stock < 0) {
                    $errores[] = 'El stock no puede ser negativo';
                }
                
                if ($stock > 20000) {
                    $errores[] = 'El stock no puede ser mayor a 20,000 unidades';
                }
            }
        }
        
        if (!$this->rela_producto_cantina || !is_numeric($this->rela_producto_cantina)) {
            $errores[] = 'Debe seleccionar un producto válido';
        }
        
        if (!$this->rela_cantina || !is_numeric($this->rela_cantina)) {
            $errores[] = 'Debe seleccionar una cantina válida';
        }
        
        if ($this->verificarDuplicado()) {
            $errores[] = 'Ya existe stock registrado para este producto en esta cantina';
        }
        
        return $errores;
    }

    private function verificarDuplicado() {
        if (!$this->rela_producto_cantina || !$this->rela_cantina) {
            return false;
        }
        
        $db = self::getDB();
        
        if ($this->id_stock_cantina) {
            $query = "SELECT COUNT(*) as total FROM stock_cantina 
                    WHERE rela_producto_cantina = '{$this->rela_producto_cantina}' 
                    AND rela_cantina = '{$this->rela_cantina}' 
                    AND id_stock_cantina != '{$this->id_stock_cantina}'";
        } else {
            $query = "SELECT COUNT(*) as total FROM stock_cantina 
                    WHERE rela_producto_cantina = '{$this->rela_producto_cantina}' 
                    AND rela_cantina = '{$this->rela_cantina}'";
        }
        
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }
    
    public static function obtenerStockCompleto($paginador = null) {
        $db = self::getDB();
        
        $limit = $paginador ? $paginador->limit() : '';
        
        $query = "SELECT 
                    sc.id_stock_cantina,
                    sc.stock_cantina,
                    sc.rela_producto_cantina,
                    sc.rela_cantina,
                    pc.nombre_producto_cantina,
                    ep.nombre_estado_producto,
                    c.nombre_cantina
                FROM stock_cantina sc
                INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                ORDER BY c.nombre_cantina, pc.nombre_producto_cantina
                {$limit}";
        
        $resultado = $db->query($query);
        $stocks = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $stock = new self();
            $stock->id_stock_cantina = $row['id_stock_cantina'];
            $stock->stock_cantina = $row['stock_cantina'];
            $stock->rela_producto_cantina = $row['rela_producto_cantina'];
            $stock->rela_cantina = $row['rela_cantina'];
            $stock->nombre_producto_cantina = $row['nombre_producto_cantina'];
            $stock->nombre_estado_producto = $row['nombre_estado_producto'];
            $stock->nombre_cantina = $row['nombre_cantina'];
            $stocks[] = $stock;
        }
        
        return $stocks;
    }
    
 
    public static function existeStock($producto_id, $cantina_id, $excluir_id = null) {
        $db = self::getDB();
        $excluir_clause = $excluir_id ? "AND id_stock_cantina != " . intval($excluir_id) : "";
        
        $query = "SELECT COUNT(*) as total 
                  FROM stock_cantina 
                  WHERE rela_producto_cantina = " . intval($producto_id) . " 
                  AND rela_cantina = " . intval($cantina_id) . " 
                  {$excluir_clause}";
        
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }
    
    
    public function crearStock() {
        // Verificar si ya existe
        if (self::existeStock($this->rela_producto_cantina, $this->rela_cantina)) {
            return [
                'resultado' => false,
                'mensaje' => 'Ya existe stock para este producto en la cantina seleccionada'
            ];
        }
        
        $query = "INSERT INTO " . static::$tabla . " (stock_cantina, rela_producto_cantina, rela_cantina)
                  VALUES (?, ?, ?)";
        
        $stmt = self::$db->prepare($query);
        $resultado = $stmt->execute([
            $this->stock_cantina,
            $this->rela_producto_cantina,
            $this->rela_cantina
        ]);
        
        if ($resultado) {
            // Actualizar estado del producto según el stock
            self::actualizarEstadoProducto($this->rela_producto_cantina, $this->stock_cantina);
        }
        
        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }
 
    public function actualizar() {
        // Verificar si ya existe otro registro con la misma combinación
        if (self::existeStock($this->rela_producto_cantina, $this->rela_cantina, $this->id_stock_cantina)) {
            return false;
        }
        
        $query = "UPDATE " . static::$tabla . " 
                  SET stock_cantina = ?, rela_producto_cantina = ?, rela_cantina = ?
                  WHERE id_stock_cantina = ?";
        
        $stmt = self::$db->prepare($query);
        $resultado = $stmt->execute([
            $this->stock_cantina,
            $this->rela_producto_cantina,
            $this->rela_cantina,
            $this->id_stock_cantina
        ]);
        
        if ($resultado) {
            // Actualizar estado del producto según el nuevo stock
            self::actualizarEstadoProducto($this->rela_producto_cantina, $this->stock_cantina);
        }
        
        return $resultado;
    }
    
   
    public function eliminar() {
        // 1. Cambiar estado del producto a Suspendido (id = 3)
        $estado_suspendido = self::obtenerIdEstado('Suspendido');
        if (!$estado_suspendido) {
            return false; // No se encontró el estado Suspendido
        }

        $query_estado = "UPDATE productos_cantina SET rela_estado_producto = ? WHERE id_producto_cantina = ?";
        $stmt_estado = self::$db->prepare($query_estado);
        $stmt_estado->execute([$estado_suspendido, $this->rela_producto_cantina]);

        // 2. Eliminar stock (opcional)
        $query = "DELETE FROM " . static::$tabla . " WHERE id_stock_cantina = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([$this->id_stock_cantina]);
    }

    
   
    public static function buscarStock($termino) {
        $db = self::getDB();
        $termino_escapado = $db->real_escape_string($termino);
        
        $query = "SELECT 
                    sc.id_stock_cantina,
                    sc.stock_cantina,
                    sc.rela_producto_cantina,
                    sc.rela_cantina,
                    pc.nombre_producto_cantina,
                    ep.nombre_estado_producto,
                    c.nombre_cantina
                  FROM stock_cantina sc
                  INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                  WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                    AND (pc.nombre_producto_cantina LIKE '%{$termino_escapado}%'
                         OR c.nombre_cantina LIKE '%{$termino_escapado}%'
                         OR sc.stock_cantina LIKE '%{$termino_escapado}%')
                  ORDER BY c.nombre_cantina, pc.nombre_producto_cantina";
        
        $resultado = $db->query($query);
        $stocks = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $stock = new self();
            $stock->id_stock_cantina = $row['id_stock_cantina'];
            $stock->stock_cantina = $row['stock_cantina'];
            $stock->rela_producto_cantina = $row['rela_producto_cantina'];
            $stock->rela_cantina = $row['rela_cantina'];
            $stock->nombre_producto_cantina = $row['nombre_producto_cantina'];
            $stock->nombre_estado_producto = $row['nombre_estado_producto'];
            $stock->nombre_cantina = $row['nombre_cantina'];
            $stocks[] = $stock;
        }
        
        return $stocks;
    }
    
  
    public static function obtenerProductosDisponibles() {
        $db = self::getDB();
        $query = "SELECT pc.id_producto_cantina, pc.nombre_producto_cantina
                  FROM productos_cantina pc
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                  AND pc.id_producto_cantina NOT IN (
                      SELECT DISTINCT rela_producto_cantina 
                      FROM stock_cantina 
                      WHERE rela_producto_cantina IS NOT NULL
                  )
                  ORDER BY pc.nombre_producto_cantina";
        
        $resultado = $db->query($query);
        $productos = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
        
        return $productos;
    }
    
    
    public static function obtenerProductosDisponiblesPorCantina($cantina_id, $excluir_stock_id = null) {
        $db = self::getDB();
        
        $excluir_clause = $excluir_stock_id ? "AND sc.id_stock_cantina != " . intval($excluir_stock_id) : "";
        
        $query = "SELECT pc.id_producto_cantina, pc.nombre_producto_cantina
                  FROM productos_cantina pc
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                  AND pc.id_producto_cantina NOT IN (
                      SELECT DISTINCT sc.rela_producto_cantina 
                      FROM stock_cantina sc 
                      WHERE sc.rela_cantina = " . intval($cantina_id) . "
                      AND sc.rela_producto_cantina IS NOT NULL
                      {$excluir_clause}
                  )
                  ORDER BY pc.nombre_producto_cantina";
        
        $resultado = $db->query($query);
        $productos = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
        
        return $productos;
    }
    
    
    public static function obtenerTodosLosProductosDisponibles() {
        $db = self::getDB();
        $query = "SELECT pc.id_producto_cantina, pc.nombre_producto_cantina
                  FROM productos_cantina pc
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                  ORDER BY pc.nombre_producto_cantina";
        
        $resultado = $db->query($query);
        $productos = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
        
        return $productos;
    }
    
  
    public static function actualizarEstadoProducto($producto_id, $nuevo_stock) {
        $db = self::getDB();
        
        // Obtener IDs de estados
        $estado_disponible = self::obtenerIdEstado('Disponible');
        $estado_agotado = self::obtenerIdEstado('Agotado');
        
        if (!$estado_disponible || !$estado_agotado) {
            return false;
        }
        
        // Determinar nuevo estado según stock
        $nuevo_estado_id = ($nuevo_stock > 0) ? $estado_disponible : $estado_agotado;
        
        // Actualizar estado del producto
        $query = "UPDATE productos_cantina 
                SET rela_estado_producto = ? 
                WHERE id_producto_cantina = ?";
        
        $stmt = $db->prepare($query);
        $resultado = $stmt->execute([$nuevo_estado_id, $producto_id]);
        
        // NUEVA FUNCIONALIDAD: Notificar stock bajo
        if ($resultado && $nuevo_stock <= self::UMBRAL_STOCK_BAJO && $nuevo_stock > 0) {
            self::notificarStockBajo($producto_id, $nuevo_stock);
        }
        
        return $resultado;
    }

    /**
     * Notificar cuando el stock esté bajo
     */
    private static function notificarStockBajo($producto_id, $stock_actual) {
        try {
            // Obtener información del producto y cantina
            $db = self::getDB();
            $query = "SELECT pc.nombre_producto_cantina, c.nombre_cantina
                    FROM stock_cantina sc
                    INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina  
                    INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                    WHERE sc.rela_producto_cantina = ?
                    LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$producto_id]);
            $resultado = $stmt->get_result();
            $row = $resultado->fetch_assoc();
            
            if ($row) {
                // Usar la clase Notificaciones
                \Classes\Notificaciones::notificarStockBajo(
                    $row['nombre_producto_cantina'],
                    $stock_actual,
                    $row['nombre_cantina']
                );
            }
        } catch (\Exception $e) {
            error_log("Error al notificar stock bajo: " . $e->getMessage());
        }
    }

    private static function obtenerIdEstado($nombre_estado) {
        $db = self::getDB();
        $query = "SELECT id_estado_producto 
                  FROM estados_productos 
                  WHERE nombre_estado_producto = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre_estado]);
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();
        
        return $row ? $row['id_estado_producto'] : null;
    }
  
    // Cantinas activas; $incluirId suma la cantina actual de un stock aunque esté inactiva
    public static function obtenerCantinasDisponibles($incluirId = null) {
        $db = self::getDB();
        $query = "SELECT id_cantina, nombre_cantina, estado 
                  FROM cantina 
                  WHERE estado = 1 OR id_cantina = ?
                  ORDER BY nombre_cantina";
        
        $stmt = $db->prepare($query);
        $stmt->execute([(int) $incluirId]);
        $resultado = $stmt->get_result();
        $cantinas = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $cantinas[] = $row;
        }
        
        return $cantinas;
    }
  
    public function actualizarCantidad($nueva_cantidad) {
        $query = "UPDATE " . static::$tabla . " 
                  SET stock_cantina = ? 
                  WHERE id_stock_cantina = ?";
        
        $stmt = self::$db->prepare($query);
        $resultado = $stmt->execute([$nueva_cantidad, $this->id_stock_cantina]);
        
        if ($resultado) {
            // Actualizar el stock local para mantener consistencia
            $this->stock_cantina = $nueva_cantidad;
            // Actualizar estado del producto según el nuevo stock
            self::actualizarEstadoProducto($this->rela_producto_cantina, $nueva_cantidad);
        }
        
        return $resultado;
    }
    
   
    public function reducirStock($cantidad_vendida) {
        if ($cantidad_vendida <= 0) {
            return [
                'resultado' => false,
                'mensaje' => 'La cantidad a reducir debe ser mayor a 0'
            ];
        }
        
        $nuevo_stock = $this->stock_cantina - $cantidad_vendida;
        
        if ($nuevo_stock < 0) {
            return [
                'resultado' => false,
                'mensaje' => 'No hay suficiente stock disponible'
            ];
        }
        
        $resultado = $this->actualizarCantidad($nuevo_stock);
        
        return [
            'resultado' => $resultado,
            'nuevo_stock' => $nuevo_stock,
            'mensaje' => $resultado ? 
                ($nuevo_stock == 0 ? 'Producto agotado después de la venta' : 'Stock actualizado correctamente') :
                'Error al actualizar el stock'
        ];
    }
    
   
    public function agregarStock($cantidad_agregada) {
        if ($cantidad_agregada <= 0) {
            return [
                'resultado' => false,
                'mensaje' => 'La cantidad a agregar debe ser mayor a 0'
            ];
        }
        
        $nuevo_stock = $this->stock_cantina + $cantidad_agregada;
        $resultado = $this->actualizarCantidad($nuevo_stock);
        
        return [
            'resultado' => $resultado,
            'nuevo_stock' => $nuevo_stock,
            'mensaje' => $resultado ? 'Stock actualizado correctamente' : 'Error al actualizar el stock'
        ];
    }
    
   
    public static function obtenerStockPorProductoYCantina($producto_id, $cantina_id) {
        $db = self::getDB();
        $query = "SELECT sc.*, pc.nombre_producto_cantina, c.nombre_cantina, ep.nombre_estado_producto
                  FROM stock_cantina sc
                  INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                  INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  WHERE sc.rela_producto_cantina = ? AND sc.rela_cantina = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$producto_id, $cantina_id]);
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();
        
        if ($row) {
            $stock = new self();
            $stock->id_stock_cantina = $row['id_stock_cantina'];
            $stock->stock_cantina = $row['stock_cantina'];
            $stock->rela_producto_cantina = $row['rela_producto_cantina'];
            $stock->rela_cantina = $row['rela_cantina'];
            $stock->nombre_producto_cantina = $row['nombre_producto_cantina'];
            $stock->nombre_cantina = $row['nombre_cantina'];
            $stock->nombre_estado_producto = $row['nombre_estado_producto'];
            return $stock;
        }
        
        return null;
    }
    
 

    public static function obtenerStockBajo($limite = 10) {
        $db = self::getDB();
        $query = "SELECT 
                    sc.id_stock_cantina,
                    sc.stock_cantina,
                    sc.rela_producto_cantina,
                    sc.rela_cantina,
                    pc.nombre_producto_cantina,
                    ep.nombre_estado_producto,
                    c.nombre_cantina
                  FROM stock_cantina sc
                  INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                  WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                    AND sc.stock_cantina > 0 
                    AND sc.stock_cantina <= ?
                  ORDER BY sc.stock_cantina ASC, c.nombre_cantina, pc.nombre_producto_cantina";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limite]);
        $resultado = $stmt->get_result();
        $stocks = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $stock = new self();
            $stock->id_stock_cantina = $row['id_stock_cantina'];
            $stock->stock_cantina = $row['stock_cantina'];
            $stock->rela_producto_cantina = $row['rela_producto_cantina'];
            $stock->rela_cantina = $row['rela_cantina'];
            $stock->nombre_producto_cantina = $row['nombre_producto_cantina'];
            $stock->nombre_estado_producto = $row['nombre_estado_producto'];
            $stock->nombre_cantina = $row['nombre_cantina'];
            $stocks[] = $stock;
        }
        
        return $stocks;
    }
    
 
    public static function obtenerStockAgotado() {
        $db = self::getDB();
        $query = "SELECT 
                    sc.id_stock_cantina,
                    sc.stock_cantina,
                    sc.rela_producto_cantina,
                    sc.rela_cantina,
                    pc.nombre_producto_cantina,
                    ep.nombre_estado_producto,
                    c.nombre_cantina
                  FROM stock_cantina sc
                  INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  INNER JOIN cantina c ON sc.rela_cantina = c.id_cantina
                  WHERE sc.stock_cantina = 0
                    AND ep.nombre_estado_producto = 'Agotado'
                  ORDER BY c.nombre_cantina, pc.nombre_producto_cantina";
        
        $resultado = $db->query($query);
        $stocks = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $stock = new self();
            $stock->id_stock_cantina = $row['id_stock_cantina'];
            $stock->stock_cantina = $row['stock_cantina'];
            $stock->rela_producto_cantina = $row['rela_producto_cantina'];
            $stock->rela_cantina = $row['rela_cantina'];
            $stock->nombre_producto_cantina = $row['nombre_producto_cantina'];
            $stock->nombre_estado_producto = $row['nombre_estado_producto'];
            $stock->nombre_cantina = $row['nombre_cantina'];
            $stocks[] = $stock;
        }
        
        return $stocks;
    }
    
  
    public static function obtenerEstadisticas() {
        $db = self::getDB();
        
        // Total de productos en stock
        $query1 = "SELECT COUNT(*) as total_productos,
                          SUM(sc.stock_cantina) as total_unidades
                   FROM stock_cantina sc
                   INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                   INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                   WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')";
        
        $resultado1 = $db->query($query1);
        $totales = $resultado1->fetch_assoc();
        
        // Productos por estado
        $query2 = "SELECT ep.nombre_estado_producto, COUNT(*) as cantidad
                   FROM stock_cantina sc
                   INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                   INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                   WHERE ep.nombre_estado_producto IN ('Disponible', 'Agotado')
                   GROUP BY ep.nombre_estado_producto";
        
        $resultado2 = $db->query($query2);
        $estados = [];
        while ($row = $resultado2->fetch_assoc()) {
            $estados[$row['nombre_estado_producto']] = $row['cantidad'];
        }
        
        // Productos con stock bajo
        $query3 = "SELECT COUNT(*) as productos_stock_bajo
                   FROM stock_cantina sc
                   INNER JOIN productos_cantina pc ON sc.rela_producto_cantina = pc.id_producto_cantina
                   INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                   WHERE ep.nombre_estado_producto = 'Disponible'
                     AND sc.stock_cantina > 0 
                     AND sc.stock_cantina <= " . self::UMBRAL_STOCK_BAJO;
        
        $resultado3 = $db->query($query3);
        $stock_bajo = $resultado3->fetch_assoc();
        
        return [
            'total_productos' => $totales['total_productos'] ?? 0,
            'total_unidades' => $totales['total_unidades'] ?? 0,
            'productos_disponibles' => $estados['Disponible'] ?? 0,
            'productos_agotados' => $estados['Agotado'] ?? 0,
            'productos_stock_bajo' => $stock_bajo['productos_stock_bajo'] ?? 0
        ];
    }
    

    public static function sincronizarEstados() {
        $db = self::getDB();
        
        // Obtener todos los stocks
        $query = "SELECT sc.rela_producto_cantina, sc.stock_cantina
                  FROM stock_cantina sc
                  GROUP BY sc.rela_producto_cantina";
        
        $resultado = $db->query($query);
        $actualizados = 0;
        
        while ($row = $resultado->fetch_assoc()) {
            $producto_id = $row['rela_producto_cantina'];
            $stock_total = $row['stock_cantina'];
            
            if (self::actualizarEstadoProducto($producto_id, $stock_total)) {
                $actualizados++;
            }
        }
        
        return [
            'resultado' => true,
            'productos_actualizados' => $actualizados,
            'mensaje' => "Se sincronizaron {$actualizados} productos correctamente"
        ];
    }
    
    /**
     * Método para debuggear - obtener todos los estados de productos
     */
    public static function obtenerEstadosProductos() {
        $db = self::getDB();
        $query = "SELECT * FROM estados_productos ORDER BY nombre_estado_producto";
        
        $resultado = $db->query($query);
        $estados = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $estados[] = $row;
        }
        
        return $estados;
    }
}