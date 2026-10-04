<?php

namespace Models;

/**
 * Retiro en la cantina de los productos y fichas comprados por la web.
 *
 * Lo comprado sale de detalle_orden (lo que se pagó) y cada entrega, total o parcial, se guarda
 * por ítem en entregas_orden. Lo que queda por retirar es la diferencia. Las butacas de la orden
 * no cuentan: se controlan con el QR de cada entrada.
 */
class RetiroOrden extends ActiveRecord
{
    const LARGO_CODIGO_CORTO = 8;

    // Segundos durante los que quien hizo una entrega puede deshacerla (se equivocó de cantidad)
    const SEGUNDOS_PARA_DESHACER = 120;

    public static function codigoCorto(string $codigoRetiro): string
    {
        return strtoupper(substr($codigoRetiro, 0, self::LARGO_CODIGO_CORTO));
    }

    /**
     * Devuelve el código de retiro de la orden; si todavía no tiene, se lo asigna.
     * El WHERE codigo_retiro IS NULL evita pisar uno asignado por otro pedido.
     */
    public static function asegurarCodigoRetiro(int $idOrden): ?string
    {
        $db = self::getDB();

        $codigo = bin2hex(random_bytes(16));
        $stmt = $db->prepare("UPDATE ordenes SET codigo_retiro = ? WHERE id_orden = ? AND codigo_retiro IS NULL");
        $stmt->bind_param('si', $codigo, $idOrden);
        $stmt->execute();

        $stmt = $db->prepare("SELECT codigo_retiro FROM ordenes WHERE id_orden = ?");
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();

        return $stmt->get_result()->fetch_column() ?: null;
    }

    /**
     * Busca órdenes por el código completo (32 hex, el del QR) o por el código corto (8 o más).
     * Más de un resultado = código corto ambiguo.
     */
    public static function buscarPorCodigo(string $codigo): array
    {
        $exacto = strlen($codigo) === 32;
        $stmt = self::getDB()->prepare("SELECT o.id_orden, o.numero_orden, o.estado, o.codigo_retiro, o.fecha_pago,
                u.nombre_usuario AS cliente
            FROM ordenes o
            LEFT JOIN usuarios u ON u.id_usuario = o.id_usuario
            WHERE " . ($exacto ? "o.codigo_retiro = ?" : "o.codigo_retiro LIKE CONCAT(?, '%')") . "
            LIMIT 5");
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Ítems de productos y fichas de la orden con lo comprado, lo entregado y lo que queda.
     * Con $bloquear = true (dentro de una transacción) se bloquean las filas hasta el commit.
     */
    public static function items(int $idOrden, bool $bloquear = false): array
    {
        $stmt = self::getDB()->prepare("SELECT d.id_detalle, d.tipo_producto, d.nombre_producto, d.cantidad AS comprado, d.subtotal,
                COALESCE((SELECT SUM(eo.cantidad) FROM entregas_orden eo WHERE eo.id_detalle = d.id_detalle), 0) AS entregado
            FROM detalle_orden d
            WHERE d.id_orden = ? AND d.tipo_producto IN ('cantina', 'fichas')
            ORDER BY d.tipo_producto, d.id_detalle" . ($bloquear ? " FOR UPDATE" : ""));
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();

        $items = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
            $fila['comprado'] = (int)$fila['comprado'];
            $fila['entregado'] = (int)$fila['entregado'];
            $fila['quedan'] = max(0, $fila['comprado'] - $fila['entregado']);
            $items[] = $fila;
        }
        return $items;
    }

    // Entregas ya hechas de la orden, la más reciente primero
    public static function historial(int $idOrden): array
    {
        $stmt = self::getDB()->prepare("SELECT eo.fecha, eo.cantidad, d.nombre_producto, u.nombre_usuario
            FROM entregas_orden eo
            INNER JOIN detalle_orden d ON d.id_detalle = eo.id_detalle
            LEFT JOIN usuarios u ON u.id_usuario = eo.id_usuario
            WHERE d.id_orden = ?
            ORDER BY eo.fecha DESC, eo.id_entrega");
        $stmt->bind_param('i', $idOrden);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Registra una entrega (total o parcial) de varios ítems a la vez.
     * $pedido: [id_detalle => cantidad]. Nunca se entrega más de lo que queda: las filas se bloquean
     * para que dos empleados no entreguen lo mismo al mismo tiempo.
     *
     * @return array ['ok' => bool, 'mensaje' => string]
     */
    public static function entregar(int $idOrden, array $pedido, int $idUsuario): array
    {
        $pedido = array_filter(array_map('intval', $pedido), fn($c) => $c > 0);
        if (!$pedido) {
            return ['ok' => false, 'mensaje' => 'Elegí qué entregar'];
        }

        $db = self::getDB();
        $db->begin_transaction();
        try {
            $items = [];
            foreach (self::items($idOrden, true) as $item) {
                $items[$item['id_detalle']] = $item;
            }

            foreach ($pedido as $idDetalle => $cantidad) {
                if (!isset($items[$idDetalle])) {
                    $db->rollback();
                    return ['ok' => false, 'mensaje' => 'Ese producto no es de este pedido'];
                }
                if ($cantidad > $items[$idDetalle]['quedan']) {
                    $db->rollback();
                    return ['ok' => false, 'mensaje' => "De {$items[$idDetalle]['nombre_producto']} quedan {$items[$idDetalle]['quedan']}"];
                }
            }

            $ahora = date('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO entregas_orden (id_detalle, cantidad, id_usuario, fecha) VALUES (?, ?, ?, ?)");
            foreach ($pedido as $idDetalle => $cantidad) {
                $stmt->bind_param('iiis', $idDetalle, $cantidad, $idUsuario, $ahora);
                $stmt->execute();
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            error_log('Retiro: no se pudo registrar la entrega de la orden ' . $idOrden . ': ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la entrega'];
        }

        $detalle = implode(', ', array_map(fn($id, $c) => "$c × {$items[$id]['nombre_producto']}", array_keys($pedido), $pedido));
        return ['ok' => true, 'mensaje' => "Entregado: $detalle"];
    }

    /**
     * Deshace la última entrega que hizo este usuario en la orden, si fue hace poco.
     * Una entrega de varios ítems se guarda con la misma fecha: se deshace completa.
     */
    public static function deshacerUltimaEntrega(int $idOrden, int $idUsuario): bool
    {
        $db = self::getDB();
        $limite = date('Y-m-d H:i:s', time() - self::SEGUNDOS_PARA_DESHACER);

        $stmt = $db->prepare("SELECT MAX(eo.fecha) FROM entregas_orden eo
            INNER JOIN detalle_orden d ON d.id_detalle = eo.id_detalle
            WHERE d.id_orden = ? AND eo.id_usuario = ? AND eo.fecha >= ?");
        $stmt->bind_param('iis', $idOrden, $idUsuario, $limite);
        $stmt->execute();
        $fecha = $stmt->get_result()->fetch_column();
        if (!$fecha) {
            return false;
        }

        $stmt = $db->prepare("DELETE eo FROM entregas_orden eo
            INNER JOIN detalle_orden d ON d.id_detalle = eo.id_detalle
            WHERE d.id_orden = ? AND eo.id_usuario = ? AND eo.fecha = ?");
        $stmt->bind_param('iis', $idOrden, $idUsuario, $fecha);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // Pedidos web pagados del cliente que tienen productos o fichas (para "Mis compras")
    public static function pedidosDelCliente(int $idUsuario): array
    {
        $stmt = self::getDB()->prepare("SELECT DISTINCT o.id_orden, o.numero_orden, COALESCE(o.fecha_pago, o.fecha_creacion) AS fecha
            FROM ordenes o
            INNER JOIN detalle_orden d ON d.id_orden = o.id_orden AND d.tipo_producto IN ('cantina', 'fichas')
            WHERE o.id_usuario = ? AND o.estado = 'pagado'
            ORDER BY fecha DESC");
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
