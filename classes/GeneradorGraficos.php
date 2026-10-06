<?php

namespace Classes;

class GeneradorGraficos
{

    public static function obtenerVentasEntradas($fechaDesde, $fechaHasta, $agruparPor = 'dia')
    {
        $db = \Models\ActiveRecord::getDB();

        $formatoFecha = match ($agruparPor) {
            'dia' => '%Y-%m-%d',
            'mes' => '%Y-%m',
            'anio' => '%Y',
            default => '%Y-%m-%d'
        };

        $query = "SELECT 
                    DATE_FORMAT(cf.pago_fecha_hora, '$formatoFecha') as periodo,
                    COUNT(DISTINCT e.id_entrada) as total_entradas,
                    SUM(df.precio_venta) as total_recaudado,
                    COUNT(DISTINCT cf.id_pagos) as total_ventas
                  FROM cabecera_fact_cine cf
                  INNER JOIN detalle_fact_cine df ON df.rela_cabecera_fact = cf.id_pagos
                  INNER JOIN entradas e ON e.id_entrada = df.rela_entrada
                  WHERE DATE(cf.pago_fecha_hora) BETWEEN ? AND ?
                  GROUP BY periodo
                  ORDER BY periodo ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'periodo' => $row['periodo'],
                'total_entradas' => (int)$row['total_entradas'],
                'total_recaudado' => (float)$row['total_recaudado'],
                'total_ventas' => (int)$row['total_ventas']
            ];
        }

        return $datos;
    }

    public static function obtenerPeliculasMasVendidas($fechaDesde, $fechaHasta, $limite = 10)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    p.titulo_pelicula,
                    COUNT(DISTINCT e.id_entrada) as total_entradas,
                    SUM(df.precio_venta) as recaudacion
                  FROM peliculas p
                  INNER JOIN funciones f ON f.rela_peliculas = p.id_pelicula
                  INNER JOIN entradas e ON e.rela_funcion = f.id_funcion
                  INNER JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
                  INNER JOIN cabecera_fact_cine cf ON cf.id_pagos = df.rela_cabecera_fact
                  WHERE DATE(cf.pago_fecha_hora) BETWEEN ? AND ?
                    AND e.estado IN (1, 2)
                  GROUP BY p.id_pelicula, p.titulo_pelicula
                  ORDER BY total_entradas DESC
                  LIMIT ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ssi', $fechaDesde, $fechaHasta, $limite);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'pelicula' => $row['titulo_pelicula'],
                'total_entradas' => (int)$row['total_entradas'],
                'recaudacion' => (float)$row['recaudacion']
            ];
        }

        return $datos;
    }

    public static function obtenerEstadisticasTipoEntrada($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    te.tipo_entrada_desc,
                    COUNT(DISTINCT e.id_entrada) as cantidad,
                    SUM(df.precio_venta) as total
                  FROM tipo_entradas te
                  INNER JOIN entradas e ON e.rela_tipo_entrada = te.id_tipo_entrada
                  INNER JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
                  INNER JOIN cabecera_fact_cine cf ON cf.id_pagos = df.rela_cabecera_fact
                  WHERE DATE(cf.pago_fecha_hora) BETWEEN ? AND ?
                    AND e.estado IN (1, 2)
                  GROUP BY te.id_tipo_entrada, te.tipo_entrada_desc
                  ORDER BY cantidad DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'tipo' => $row['tipo_entrada_desc'],
                'cantidad' => (int)$row['cantidad'],
                'total' => (float)$row['total']
            ];
        }

        return $datos;
    }

    public static function obtenerOcupacionSalas($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    s.id_sala,
                    CONCAT('Sala ', s.id_sala) AS nombre_sala,
                    s.capacidad_sala,
                    COALESCE(COUNT(DISTINCT f.id_funcion), 0) AS total_funciones,
                    COALESCE(COUNT(DISTINCT bv.id_entrada), 0) AS entradas_vendidas,
                    (COALESCE(COUNT(DISTINCT f.id_funcion), 0) * s.capacidad_sala) AS capacidad_total_periodo,
                    ROUND(
                        (COALESCE(COUNT(DISTINCT bv.id_entrada), 0) / NULLIF((COALESCE(COUNT(DISTINCT f.id_funcion), 0) * s.capacidad_sala), 0)) * 100, 
                        2
                    ) AS porcentaje_ocupacion
                  FROM salas s
                  LEFT JOIN funciones f ON f.rela_salas = s.id_sala AND DATE(f.fecha_hora) BETWEEN ? AND ? AND f.estado = 1
                  LEFT JOIN butacas_vendidas bv ON bv.id_funcion = f.id_funcion
                  WHERE s.estado = 1
                  GROUP BY s.id_sala, s.capacidad_sala
                  ORDER BY porcentaje_ocupacion DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'sala' => 'Sala ' . $row['id_sala'],
                'capacidad' => (int)$row['capacidad_sala'],
                'funciones' => (int)$row['total_funciones'],
                'entradas_vendidas' => (int)$row['entradas_vendidas'],
                'capacidad_total' => (int)$row['capacidad_total_periodo'],
                'ocupacion' => (float)($row['porcentaje_ocupacion'] ?? 0)
            ];
        }

        return $datos;
    }

    public static function obtenerEstadosEntradas($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    CASE 
                        WHEN e.estado = 1 THEN 'Activas'
                        WHEN e.estado = 2 THEN 'Usadas'
                        WHEN e.estado = 0 THEN 'Expiradas'
                        WHEN e.estado = -1 THEN 'Canceladas'
                    END as estado_desc,
                    COUNT(*) as cantidad
                  FROM entradas e
                  INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
                  WHERE DATE(f.fecha_hora) BETWEEN ? AND ?
                  GROUP BY e.estado
                  ORDER BY e.estado DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'estado' => $row['estado_desc'],
                'cantidad' => (int)$row['cantidad']
            ];
        }

        return $datos;
    }


    public static function obtenerVentasWeb($fechaDesde, $fechaHasta, $agruparPor = 'dia')
    {
        $db = \Models\ActiveRecord::getDB();

        $formatoFecha = match ($agruparPor) {
            'dia' => '%Y-%m-%d',
            'mes' => '%Y-%m',
            'anio' => '%Y',
            default => '%Y-%m-%d'
        };

        $query = "SELECT 
                    DATE_FORMAT(o.fecha_pago, '$formatoFecha') as periodo,
                    COUNT(DISTINCT o.id_orden) as total_ordenes,
                    SUM(o.total) as total_recaudado,
                    SUM(CASE WHEN do.tipo_producto = 'butacas' THEN do.cantidad ELSE 0 END) as total_butacas,
                    SUM(CASE WHEN do.tipo_producto = 'cantina' THEN do.cantidad ELSE 0 END) as total_cantina,
                    SUM(CASE WHEN do.tipo_producto = 'fichas' THEN do.cantidad ELSE 0 END) as total_fichas
                  FROM ordenes o
                  INNER JOIN detalle_orden do ON do.id_orden = o.id_orden
                  WHERE o.estado = 'pagado'
                    AND DATE(o.fecha_pago) BETWEEN ? AND ?
                  GROUP BY periodo
                  ORDER BY periodo ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'periodo' => $row['periodo'],
                'total_ordenes' => (int)$row['total_ordenes'],
                'total_recaudado' => (float)$row['total_recaudado'],
                'total_butacas' => (int)$row['total_butacas'],
                'total_cantina' => (int)$row['total_cantina'],
                'total_fichas' => (int)$row['total_fichas']
            ];
        }

        return $datos;
    }

    public static function obtenerDistribucionProductosWeb($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    CASE do.tipo_producto
                        WHEN 'butacas' THEN 'Entradas de Cine'
                        WHEN 'cantina' THEN 'Productos de Cantina'
                        WHEN 'fichas' THEN 'Fichas de Juegos'
                        ELSE do.tipo_producto
                    END as categoria,
                    do.tipo_producto,
                    SUM(do.cantidad) as cantidad_total,
                    SUM(do.subtotal) as recaudacion_total,
                    COUNT(DISTINCT o.id_orden) as ordenes_con_producto
                  FROM ordenes o
                  INNER JOIN detalle_orden do ON do.id_orden = o.id_orden
                  WHERE o.estado = 'pagado'
                    AND DATE(o.fecha_pago) BETWEEN ? AND ?
                  GROUP BY do.tipo_producto
                  ORDER BY recaudacion_total DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'categoria' => $row['categoria'],
                'tipo' => $row['tipo_producto'],
                'cantidad' => (int)$row['cantidad_total'],
                'recaudacion' => (float)$row['recaudacion_total'],
                'ordenes' => (int)$row['ordenes_con_producto']
            ];
        }

        return $datos;
    }

    public static function obtenerTopProductosCantinaWeb($fechaDesde, $fechaHasta, $limite = 10)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    p.nombre_producto,
                    SUM(do.cantidad) as cantidad_vendida,
                    SUM(do.subtotal) as recaudacion,
                    COUNT(DISTINCT o.id_orden) as ordenes
                  FROM ordenes o
                  INNER JOIN detalle_orden do ON do.id_orden = o.id_orden
                  INNER JOIN productos p ON p.id_producto = do.id_producto
                  WHERE o.estado = 'pagado'
                    AND do.tipo_producto = 'cantina'
                    AND DATE(o.fecha_pago) BETWEEN ? AND ?
                  GROUP BY p.id_producto, p.nombre_producto
                  ORDER BY cantidad_vendida DESC
                  LIMIT ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ssi', $fechaDesde, $fechaHasta, $limite);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'producto' => $row['nombre_producto'],
                'cantidad' => (int)$row['cantidad_vendida'],
                'recaudacion' => (float)$row['recaudacion'],
                'ordenes' => (int)$row['ordenes']
            ];
        }

        return $datos;
    }


    public static function obtenerPeliculasMasVendidasWeb($fechaDesde, $fechaHasta, $limite = 10)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    p.titulo_pelicula,
                    COUNT(DISTINCT do.id_detalle) as total_butacas,
                    SUM(do.subtotal) as recaudacion,
                    COUNT(DISTINCT o.id_orden) as ordenes
                  FROM ordenes o
                  INNER JOIN detalle_orden do ON do.id_orden = o.id_orden
                  INNER JOIN funciones f ON f.id_funcion = do.id_funcion
                  INNER JOIN peliculas p ON p.id_pelicula = f.rela_peliculas
                  WHERE o.estado = 'pagado'
                    AND do.tipo_producto = 'butacas'
                    AND DATE(o.fecha_pago) BETWEEN ? AND ?
                  GROUP BY p.id_pelicula, p.titulo_pelicula
                  ORDER BY total_butacas DESC
                  LIMIT ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ssi', $fechaDesde, $fechaHasta, $limite);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'pelicula' => $row['titulo_pelicula'],
                'total_butacas' => (int)$row['total_butacas'],
                'recaudacion' => (float)$row['recaudacion'],
                'ordenes' => (int)$row['ordenes']
            ];
        }

        return $datos;
    }

    public static function obtenerEstadosOrdenesWeb($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    CASE o.estado
                        WHEN 'pendiente' THEN 'Pendientes'
                        WHEN 'pagado' THEN 'Pagadas'
                        WHEN 'cancelado' THEN 'Canceladas'
                        WHEN 'fallido' THEN 'Fallidas'
                        ELSE o.estado
                    END as estado_desc,
                    o.estado as estado_original,
                    COUNT(*) as cantidad,
                    SUM(o.total) as monto_total
                  FROM ordenes o
                  WHERE DATE(o.fecha_creacion) BETWEEN ? AND ?
                  GROUP BY o.estado
                  ORDER BY cantidad DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'estado' => $row['estado_desc'],
                'estado_code' => $row['estado_original'],
                'cantidad' => (int)$row['cantidad'],
                'monto' => (float)$row['monto_total']
            ];
        }

        return $datos;
    }


    public static function obtenerMetodosPagoWeb($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    COALESCE(o.metodo_pago, 'No especificado') as metodo,
                    COUNT(*) as cantidad,
                    SUM(o.total) as total_recaudado
                  FROM ordenes o
                  WHERE o.estado = 'pagado'
                    AND DATE(o.fecha_pago) BETWEEN ? AND ?
                  GROUP BY o.metodo_pago
                  ORDER BY cantidad DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'metodo' => $row['metodo'],
                'cantidad' => (int)$row['cantidad'],
                'total' => (float)$row['total_recaudado']
            ];
        }

        return $datos;
    }


    public static function obtenerTopMaquinasWeb($fechaDesde, $fechaHasta, $limite = 10)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    m.nombre_maquina,
                    SUM(do.cantidad) as fichas_vendidas,
                    SUM(do.subtotal) as recaudacion,
                    COUNT(DISTINCT o.id_orden) as ordenes
                  FROM ordenes o
                  INNER JOIN detalle_orden do ON do.id_orden = o.id_orden
                  INNER JOIN maquinas m ON m.id_maquina = do.id_producto
                  WHERE o.estado = 'pagado'
                    AND do.tipo_producto = 'fichas'
                    AND DATE(o.fecha_pago) BETWEEN ? AND ?
                  GROUP BY m.id_maquina, m.nombre_maquina
                  ORDER BY fichas_vendidas DESC
                  LIMIT ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ssi', $fechaDesde, $fechaHasta, $limite);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'maquina' => $row['nombre_maquina'],
                'fichas' => (int)$row['fichas_vendidas'],
                'recaudacion' => (float)$row['recaudacion'],
                'ordenes' => (int)$row['ordenes']
            ];
        }

        return $datos;
    }


    public static function obtenerOcupacionSalasWeb($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                    s.id_sala,
                    CONCAT('Sala ', s.id_sala) AS nombre_sala,
                    s.capacidad_sala,
                    COUNT(DISTINCT f.id_funcion) AS total_funciones,
                    COUNT(DISTINCT do.id_detalle) AS butacas_vendidas_web,
                    (COUNT(DISTINCT f.id_funcion) * s.capacidad_sala) AS capacidad_total,
                    ROUND(
                        (COUNT(DISTINCT do.id_detalle) / NULLIF((COUNT(DISTINCT f.id_funcion) * s.capacidad_sala), 0)) * 100,
                        2
                    ) AS porcentaje_ocupacion_web
                  FROM salas s
                  LEFT JOIN funciones f ON f.rela_salas = s.id_sala 
                    AND DATE(f.fecha_hora) BETWEEN ? AND ? 
                    AND f.estado = 1
                  LEFT JOIN detalle_orden do ON do.id_funcion = f.id_funcion
                  LEFT JOIN ordenes o ON o.id_orden = do.id_orden AND o.estado = 'pagado'
                  WHERE s.estado = 1 AND do.tipo_producto = 'butacas'
                  GROUP BY s.id_sala, s.capacidad_sala
                  ORDER BY porcentaje_ocupacion_web DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'sala' => $row['nombre_sala'],
                'capacidad' => (int)$row['capacidad_sala'],
                'funciones' => (int)$row['total_funciones'],
                'butacas_web' => (int)$row['butacas_vendidas_web'],
                'capacidad_total' => (int)$row['capacidad_total'],
                'ocupacion' => (float)($row['porcentaje_ocupacion_web'] ?? 0)
            ];
        }

        return $datos;
    }

    public static function obtenerVentasProductos($fechaDesde, $fechaHasta, $agruparPor = 'dia')
    {
        $db = \Models\ActiveRecord::getDB();

        $formatoFecha = match ($agruparPor) {
            'dia' => '%Y-%m-%d',
            'mes' => '%Y-%m',
            'anio' => '%Y',
            default => '%Y-%m-%d'
        };

        $query = "SELECT 
                DATE_FORMAT(cc.fecha_hora_pago_cantina, '$formatoFecha') as periodo,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as total_ventas,
                SUM(dc.cantidad) as total_productos_vendidos,
                SUM(dc.cantidad * dc.precio_unitario) as total_recaudado,
                ROUND(AVG(cc.monto_total_cantina), 2) as promedio_venta
              FROM cabecera_fact_cantina cc
              INNER JOIN detalle_fact_cantina dc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
              GROUP BY periodo
              ORDER BY periodo ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'periodo' => $row['periodo'],
                'total_ventas' => (int)$row['total_ventas'],
                'cantidad_vendida' => (int)$row['total_productos_vendidos'],
                'total_recaudado' => (float)$row['total_recaudado'],
                'promedio_venta' => (float)$row['promedio_venta']
            ];
        }

        return $datos;
    }


    public static function obtenerProductosMasVendidos($fechaDesde, $fechaHasta, $limite = 10)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                COALESCE(p.nombre_producto_cantina, f.nombre_ficha) as producto,
                SUM(dc.cantidad) as cantidad_vendida,
                SUM(dc.cantidad * dc.precio_unitario) as recaudacion,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as num_ventas,
                CASE 
                    WHEN p.id_producto_cantina IS NOT NULL THEN 'producto'
                    ELSE 'ficha'
                END as tipo
              FROM detalle_fact_cantina dc
              INNER JOIN cabecera_fact_cantina cc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
              LEFT JOIN productos_cantina p ON dc.rela_producto_cantina = p.id_producto_cantina
              LEFT JOIN fichas f ON dc.rela_fichas = f.id_fichas
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
              GROUP BY producto, tipo
              ORDER BY cantidad_vendida DESC
              LIMIT ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ssi', $fechaDesde, $fechaHasta, $limite);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'producto' => $row['producto'],
                'cantidad_vendida' => (int)$row['cantidad_vendida'],
                'recaudacion' => (float)$row['recaudacion'],
                'num_ventas' => (int)$row['num_ventas'],
                'tipo' => $row['tipo']
            ];
        }

        return $datos;
    }

    /**
     * Obtiene estadísticas por tipo de pago
     */
    public static function obtenerEstadisticasFormasPago($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                tp.descripcion_tipo_pago as forma_pago,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as cantidad_ventas,
                SUM(cc.monto_total_cantina) as total_recaudado,
                ROUND(AVG(cc.monto_total_cantina), 2) as promedio_venta,
                MIN(cc.monto_total_cantina) as venta_minima,
                MAX(cc.monto_total_cantina) as venta_maxima
              FROM tipos_de_pagos tp
              INNER JOIN cabecera_fact_cantina cc ON cc.rela_tipo_de_pagos = tp.id_tipo_pago
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
                AND tp.activo = 1
              GROUP BY tp.id_tipo_pago, tp.descripcion_tipo_pago
              ORDER BY cantidad_ventas DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'forma_pago' => $row['forma_pago'],
                'cantidad' => (int)$row['cantidad_ventas'],
                'total' => (float)$row['total_recaudado'],
                'promedio' => (float)$row['promedio_venta'],
                'venta_minima' => (float)$row['venta_minima'],
                'venta_maxima' => (float)$row['venta_maxima']
            ];
        }

        return $datos;
    }

    /**
     * Obtiene estadísticas de stock de productos
     */
    public static function obtenerEstadisticasStock($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                p.nombre_producto_cantina as producto,
                s.stock_cantina as stock_actual,
                COALESCE(SUM(dc.cantidad), 0) as cantidad_vendida,
                p.precio_producto,
                e.nombre_estado_producto,
                CASE 
                    WHEN s.stock_cantina = 0 THEN 'Sin Stock'
                    WHEN s.stock_cantina <= 15 THEN 'Bajo'
                    WHEN s.stock_cantina < 30 THEN 'Medio'
                    ELSE 'Alto'
                END as nivel_stock
              FROM productos_cantina p
              INNER JOIN stock_cantina s ON p.id_producto_cantina = s.rela_producto_cantina
              INNER JOIN estados_productos e ON p.rela_estado_producto = e.id_estado_producto
              LEFT JOIN detalle_fact_cantina dc ON dc.rela_producto_cantina = p.id_producto_cantina
              LEFT JOIN cabecera_fact_cantina cc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
                AND DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
              WHERE p.rela_estado_producto IN (1, 2)
              GROUP BY p.id_producto_cantina, p.nombre_producto_cantina, s.stock_cantina, 
                       p.precio_producto, e.nombre_estado_producto
              ORDER BY s.stock_cantina ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'producto' => $row['producto'],
                'stock_actual' => (int)$row['stock_actual'],
                'cantidad_vendida' => (int)$row['cantidad_vendida'],
                'precio' => (float)$row['precio_producto'],
                'estado' => $row['nombre_estado_producto'],
                'nivel_stock' => $row['nivel_stock']
            ];
        }

        return $datos;
    }

    /**
     * Obtiene rendimiento de vendedores (para cajas 3 y 4)
     */
    public static function obtenerRendimientoVendedores($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                u.nombre_usuario as vendedor,
                c.numero_caja,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as total_ventas,
                SUM(cc.monto_total_cantina) as total_recaudado,
                ROUND(AVG(cc.monto_total_cantina), 2) as promedio_venta,
                SUM(dc.cantidad) as productos_vendidos
              FROM usuarios u
              INNER JOIN arqueo_cajas ac ON u.id_usuario = ac.rela_usuario
              INNER JOIN cajas c ON ac.rela_caja = c.id_caja
              INNER JOIN cabecera_fact_cantina cc ON cc.rela_arqueo_caja = ac.id_arqueo_caja
              INNER JOIN detalle_fact_cantina dc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
                AND c.id_caja IN (3, 4)
              GROUP BY u.id_usuario, u.nombre_usuario, c.numero_caja
              ORDER BY total_recaudado DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'vendedor' => $row['vendedor'],
                'caja' => 'Caja ' . $row['numero_caja'],
                'total_ventas' => (int)$row['total_ventas'],
                'total_recaudado' => (float)$row['total_recaudado'],
                'promedio_venta' => (float)$row['promedio_venta'],
                'productos_vendidos' => (int)$row['productos_vendidos']
            ];
        }

        return $datos;
    }

    /**
     * Obtiene tipos de comprobantes utilizados
     */
    public static function obtenerEstadisticasTiposComprobante($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                tc.descripcion_corta as tipo_comprobante,
                tc.codigo,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as cantidad,
                SUM(cc.monto_total_cantina) as total_recaudado,
                ROUND(AVG(cc.monto_total_cantina), 2) as promedio
              FROM tipos_comprobantes tc
              INNER JOIN cabecera_fact_cantina cc ON cc.tipo_comprobante = tc.codigo
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
                AND tc.activo = 1
              GROUP BY tc.id_tipo_comprobante, tc.descripcion_corta, tc.codigo
              ORDER BY cantidad DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'tipo' => $row['tipo_comprobante'],
                'codigo' => $row['codigo'],
                'cantidad' => (int)$row['cantidad'],
                'total' => (float)$row['total_recaudado'],
                'promedio' => (float)$row['promedio']
            ];
        }

        return $datos;
    }

    /**
     * Obtiene ventas por caja (específico para cajas 3 y 4)
     */
    public static function obtenerVentasPorCaja($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                c.numero_caja,
                c.nombre_caja,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as total_ventas,
                SUM(cc.monto_total_cantina) as total_recaudado,
                ROUND(AVG(cc.monto_total_cantina), 2) as promedio_venta,
                COUNT(DISTINCT ac.rela_usuario) as vendedores_activos
              FROM cajas c
              INNER JOIN arqueo_cajas ac ON c.id_caja = ac.rela_caja
              INNER JOIN cabecera_fact_cantina cc ON cc.rela_arqueo_caja = ac.id_arqueo_caja
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
                AND c.id_caja IN (3, 4)
                AND c.activo = 1
              GROUP BY c.id_caja, c.numero_caja, c.nombre_caja
              ORDER BY c.numero_caja ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'caja' => $row['nombre_caja'],
                'numero' => (int)$row['numero_caja'],
                'total_ventas' => (int)$row['total_ventas'],
                'total_recaudado' => (float)$row['total_recaudado'],
                'promedio_venta' => (float)$row['promedio_venta'],
                'vendedores' => (int)$row['vendedores_activos']
            ];
        }

        return $datos;
    }

    /**
     * Obtiene ventas de fichas específicamente
     */
    public static function obtenerVentasFichas($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                f.nombre_ficha,
                f.precio_ficha,
                SUM(dc.cantidad) as cantidad_vendida,
                SUM(dc.cantidad * dc.precio_unitario) as recaudacion,
                COUNT(DISTINCT cc.id_cabecera_fact_cantin) as num_ventas,
                f.cantidad_ficha as stock_actual
              FROM fichas f
              INNER JOIN detalle_fact_cantina dc ON dc.rela_fichas = f.id_fichas
              INNER JOIN cabecera_fact_cantina cc ON cc.id_cabecera_fact_cantin = dc.rela_cabecera_fact_cantin
              WHERE DATE(cc.fecha_hora_pago_cantina) BETWEEN ? AND ?
              GROUP BY f.id_fichas, f.nombre_ficha, f.precio_ficha, f.cantidad_ficha
              ORDER BY cantidad_vendida DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'ficha' => $row['nombre_ficha'],
                'precio' => (float)$row['precio_ficha'],
                'cantidad_vendida' => (int)$row['cantidad_vendida'],
                'recaudacion' => (float)$row['recaudacion'],
                'num_ventas' => (int)$row['num_ventas'],
                'stock_actual' => (int)$row['stock_actual']
            ];
        }

        return $datos;
    }

    public static function obtenerVentasPorTurno($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                t.id_turnos,
                t.turno_horario,
                COUNT(DISTINCT f.id_funcion) as total_funciones,
                COUNT(DISTINCT e.id_entrada) as total_entradas,
                SUM(te.precio_entrada) as recaudacion
              FROM turnos t
              LEFT JOIN funciones f ON f.rela_turnos = t.id_turnos
                AND DATE(f.fecha_hora) BETWEEN ? AND ?
              LEFT JOIN entradas e ON e.rela_funcion = f.id_funcion
                AND e.estado != -1
              LEFT JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
              WHERE t.estado = 1
              GROUP BY t.id_turnos, t.turno_horario
              ORDER BY total_entradas DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ss', $fechaDesde, $fechaHasta);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = [
                'turno'      => $row['turno_horario'],
                'funciones'  => (int) $row['total_funciones'],
                'entradas'   => (int) $row['total_entradas'],
                'recaudacion' => (float) $row['recaudacion']
            ];
        }

        return $datos;
    }
}
