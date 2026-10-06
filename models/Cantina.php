<?php
namespace Models;

class Cantina extends ActiveRecord {
    protected static $tabla = 'cantina';
    protected static $columnasDB = ['id_cantina', 'nombre_cantina', 'estado'];

    // Cajas de venta de productos (las de funciones son la 1 y la 2)
    const CAJAS_PRODUCTOS = [3, 4];

    public $id_cantina;
    public $nombre_cantina;
    public $estado;
    public $cajas = ''; // nombres de las cajas asignadas, para el listado

    public function __construct($args = []) {
        $this->id_cantina = $args['id_cantina'] ?? null;
        $this->nombre_cantina = $args['nombre_cantina'] ?? '';
        $this->estado = $args['estado'] ?? 1;
    }

    public function validar() {
        $errores = []; // Array simple de strings
        
        if (!$this->nombre_cantina) {
            $errores[] = 'El nombre de la cantina es obligatorio';
        }
        
        return $errores; // Retorna array simple
    }

    public static function obtenerTodas() {
        $db = self::getDB();
        // Traer todas las cantinas, con los nombres de sus cajas
        $resultado = $db->query("SELECT cant.*, GROUP_CONCAT(c.nombre_caja ORDER BY c.numero_caja SEPARATOR ', ') AS cajas
                                 FROM cantina cant
                                 LEFT JOIN cajas c ON c.rela_cantina = cant.id_cantina AND c.activo = 1
                                 GROUP BY cant.id_cantina
                                 ORDER BY cant.id_cantina");
        $cantinas = [];
        while($row = $resultado->fetch_assoc()) {
            $cantina = new self;
            $cantina->id_cantina = $row['id_cantina'];
            $cantina->nombre_cantina = $row['nombre_cantina'];
            $cantina->estado = $row['estado'];
            $cantina->cajas = $row['cajas'] ?? '';
            $cantinas[] = $cantina;
        }
        return $cantinas;
    }

    public function crearCantina() {
        $query = "INSERT INTO " . static::$tabla . " (nombre_cantina)
                  VALUES ('{$this->nombre_cantina}')";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id_insertado' => self::$db->insert_id
        ];
    }

    public static function buscarCantina($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "SELECT * FROM cantina WHERE id_cantina = '$termino' OR nombre_cantina LIKE '%$termino%' LIMIT 1";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function actualizar(){
        $query = "UPDATE cantina SET nombre_cantina = ?, estado = ? WHERE id_cantina = ?";
        $stmt = self::$db->prepare($query);
        return $stmt->execute([
            $this->nombre_cantina,
            (int) $this->estado,
            $this->id_cantina
        ]);
    }

    // Baja lógica: la cantina queda inactiva, no se borra
    public function darDeBaja() {
        $stmt = self::$db->prepare("UPDATE cantina SET estado = 0 WHERE id_cantina = ?");
        return $stmt->execute([$this->id_cantina]);
    }

    // Cajas de productos con la cantina a la que pertenecen y si están abiertas ahora
    public static function obtenerCajasProductos() {
        $ids = implode(',', self::CAJAS_PRODUCTOS);
        $resultado = self::getDB()->query("SELECT c.id_caja, c.codigo_caja, c.nombre_caja, c.rela_cantina,
                                                  cant.nombre_cantina,
                                                  EXISTS(SELECT 1 FROM arqueo_cajas ac
                                                         WHERE ac.rela_caja = c.id_caja AND ac.estado_arqueo = 'abierto') AS abierta
                                           FROM cajas c
                                           LEFT JOIN cantina cant ON cant.id_cantina = c.rela_cantina
                                           WHERE c.activo = 1 AND c.id_caja IN ($ids)
                                           ORDER BY c.numero_caja");
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // Asigna una caja a una cantina (null = sin cantina, no se puede abrir)
    public static function asignarCaja($idCaja, $idCantina) {
        $stmt = self::getDB()->prepare("UPDATE cajas SET rela_cantina = ? WHERE id_caja = ?");
        return $stmt->execute([$idCantina, $idCaja]);
    }

    // Cuántas cantinas activas hay sin contar la indicada (para no dejar el sistema sin ninguna)
    public static function contarActivasExcepto($id_cantina) {
        $stmt = self::getDB()->prepare("SELECT COUNT(*) AS total FROM cantina WHERE estado = 1 AND id_cantina <> ?");
        $stmt->execute([$id_cantina]);
        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    // Obtener productos con stock de una cantina específica
    public static function obtenerProductosConStock($id_cantina) {
        $db = self::getDB();
        $query = "SELECT 
                    pc.id_producto_cantina,
                    pc.nombre_producto_cantina, 
                    pc.precio_producto,
                    pc.rela_estado_producto,
                    ep.nombre_estado_producto,
                    sc.stock_cantina,
                    sc.id_stock_cantina
                  FROM productos_cantina pc
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  INNER JOIN stock_cantina sc ON pc.id_producto_cantina = sc.rela_producto_cantina
                  WHERE sc.rela_cantina = ? AND pc.rela_estado_producto = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $id_cantina);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $productos = [];
        while($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
        return $productos;
    }

    // Obtener productos disponibles (estado = 1) - método general sin cantina específica
    public static function obtenerProductosDisponibles() {
        $db = self::getDB();
        $query = "SELECT pc.*, ep.nombre_estado_producto
                  FROM productos_cantina pc
                  INNER JOIN estados_productos ep ON pc.rela_estado_producto = ep.id_estado_producto
                  WHERE pc.rela_estado_producto = 1";
        
        $resultado = $db->query($query);
        $productos = [];
        while($row = $resultado->fetch_assoc()) {
            $productos[] = $row;
        }
        return $productos;
    }

    // Obtener máquinas activas con sus fichas
    public static function obtenerMaquinasConFichas() {
        $db = self::getDB();
        $query = "SELECT m.*, f.precio_ficha, f.cantidad_ficha, f.id_fichas
                  FROM maquinas m 
                  INNER JOIN fichas f ON f.id_fichas = m.rela_fichas
                  WHERE m.estado = 1 AND f.cantidad_ficha > 0";
        
        $resultado = $db->query($query);
        $maquinas = [];
        while($row = $resultado->fetch_assoc()) {
            $maquinas[] = $row;
        }
        return $maquinas;
    }

    // Obtener todo el contenido de una cantina específica
    public static function obtenerContenidoCantina($id_cantina) {
        $productos = self::obtenerProductosConStock($id_cantina);
        $maquinas = self::obtenerMaquinasConFichas();
        
        return [
            'productos' => $productos,
            'maquinas' => $maquinas
        ];
    }
}