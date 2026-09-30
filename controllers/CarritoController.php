<?php

namespace Controllers;

use Models\Carrito;
use Models\Maquina;
use Models\Stock;
use Models\Ficha;
use MVC\Router;

class CarritoController
{


    private static function verificarUsuario()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['login'] && isset($_SESSION['id_usuario']);
    }


    public static function agregar()
    {
        if (!self::verificarUsuario()) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);

        $idProducto = $datos['id_producto'] ?? null;
        $cantidad = (int)($datos['cantidad'] ?? 1);
        $tipo = $datos['tipo'] ?? null;
        $idUsuario = $_SESSION['id_usuario'];

        if (!$idProducto || !$tipo || $cantidad <= 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        if ($tipo === 'butacas') {
            // Las butacas se reservan por /api/butacas/reservar, que valida
            // disponibilidad y fija el precio real de la función. Este
            // endpoint genérico no debe aceptarlas directamente.
            echo json_encode(['ok' => false, 'mensaje' => 'Tipo de producto inválido']);
            return;
        }

        try {
            $precio = null;

            // Verificar stock según tipo
            if ($tipo === 'cantina') {
                $stock = Stock::obtenerStockPorProductoYCantina($idProducto, 1);
                if (!$stock || $stock->stock_cantina < $cantidad) {
                    $disponible = $stock ? $stock->stock_cantina : 0;
                    echo json_encode(['ok' => false, 'mensaje' => "Stock insuficiente. Disponible: {$disponible}"]);
                    return;
                }
            } elseif ($tipo === 'fichas') {
                // Obtener la máquina para sacar rela_fichas y precio
                $maquina = Maquina::find($idProducto);
                if (!$maquina || !$maquina->rela_fichas) {
                    echo json_encode(['ok' => false, 'mensaje' => 'Máquina no encontrada']);
                    return;
                }

                // Obtener la ficha asociada
                $ficha = Ficha::find($maquina->rela_fichas);
                if (!$ficha || $ficha->cantidad_ficha < $cantidad) {
                    $disponible = $ficha ? $ficha->cantidad_ficha : 0;
                    echo json_encode(['ok' => false, 'mensaje' => "Fichas insuficientes. Disponibles: {$disponible}"]);
                    return;
                }

                // IMPORTANTE: Guardar el precio de la ficha
                $precio = $ficha->precio_ficha;
            }

            // Verificar si ya existe en el carrito
            $itemExistente = Carrito::obtenerItemCarrito($idUsuario, $idProducto, $tipo);

            if ($itemExistente) {
                $nuevaCantidad = $itemExistente['cantidad'] + $cantidad;

                if (!Carrito::verificarStockDisponible($idProducto, $nuevaCantidad, $tipo)) {
                    $stockActual = Carrito::obtenerStockActual($idProducto, $tipo);
                    echo json_encode(['ok' => false, 'mensaje' => "No puedes agregar más de {$stockActual} unidades"]);
                    return;
                }

                $resultado = Carrito::actualizarCantidad($itemExistente['id'], $nuevaCantidad);
            } else {
                // Agregar con precio
                $resultado = Carrito::agregarItem($idUsuario, $idProducto, $tipo, $cantidad, $precio);
            }

            if ($resultado) {
                echo json_encode(['ok' => true, 'mensaje' => 'Producto agregado al carrito']);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al agregar al carrito']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }


    public static function actualizar()
    {
        if (!self::verificarUsuario()) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);

        $idItem = $datos['id_item'] ?? null;
        $incremento = (int)($datos['incremento'] ?? 0);
        $idUsuario = $_SESSION['id_usuario'];

        if (!$idItem) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        try {
            // Obtener el item actual
            $item = Carrito::obtenerItemPorId($idItem, $idUsuario);

            if (!$item) {
                echo json_encode(['ok' => false, 'mensaje' => 'Item no encontrado']);
                return;
            }

            $nuevaCantidad = $item['cantidad'] + $incremento;

            // Si cantidad es 0 o menor, eliminar item
            if ($nuevaCantidad <= 0) {
                $resultado = Carrito::eliminarItem($idItem, $idUsuario);
                echo json_encode(['ok' => true, 'mensaje' => 'Item eliminado del carrito']);
                return;
            }

            // Validar stock según tipo
            if (!Carrito::verificarStockDisponible($item['id_producto'], $nuevaCantidad, $item['tipo_producto'])) {
                $stockDisponible = Carrito::obtenerStockActual($item['id_producto'], $item['tipo_producto']);
                echo json_encode(['ok' => false, 'mensaje' => "Stock insuficiente. Disponible: {$stockDisponible}"]);
                return;
            }

            $resultado = Carrito::actualizarCantidad($idItem, $nuevaCantidad);

            if ($resultado) {
                echo json_encode(['ok' => true, 'mensaje' => 'Cantidad actualizada']);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar cantidad']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }


    public static function eliminar()
    {
        if (!self::verificarUsuario()) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);
        $idItem = $datos['id_item'] ?? null;
        $idUsuario = $_SESSION['id_usuario'];

        if (!$idItem) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de item requerido']);
            return;
        }

        try {
            $resultado = Carrito::eliminarItem($idItem, $idUsuario);

            if ($resultado) {
                echo json_encode(['ok' => true, 'mensaje' => 'Item eliminado del carrito']);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar item']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }


    public static function vaciar()
    {
        if (!self::verificarUsuario()) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
            return;
        }

        header('Content-Type: application/json');

        $idUsuario = $_SESSION['id_usuario'];

        try {
            $resultado = Carrito::vaciarCarrito($idUsuario);

            if ($resultado) {
                echo json_encode(['ok' => true, 'mensaje' => 'Carrito vaciado']);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al vaciar carrito']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }
    public static function checkout(Router $router)
    {
        if (!self::verificarUsuario()) {
            header('Location: /');
            exit;
        }

        $idUsuario = $_SESSION['id_usuario'];

        try {
            $items = Carrito::obtenerCarritoCompleto($idUsuario);
            $estadisticas = Carrito::obtenerEstadisticas($idUsuario);

            if (empty($items)) {
                header('Location: /menu');
                exit;
            }

            // Calcular total y validar stock
            $itemsValidos = [];
            $total = 0;

            foreach ($items as $item) {
                // Para butacas no verificar stock
                if ($item['tipo_producto'] === 'butacas') {
                    $item['disponible'] = true;
                } else {
                    $stockActual = Carrito::obtenerStockActual($item['id_producto'], $item['tipo_producto']);
                    $item['stock_actual'] = $stockActual;
                    $item['disponible'] = $stockActual >= $item['cantidad'];
                }

                if ($item['disponible']) {
                    $item['subtotal'] = $item['precio'] * $item['cantidad'];
                    $total += $item['subtotal'];
                }

                $itemsValidos[] = $item;
            }

            $router->render('cliente/checkout', [
                'items' => $itemsValidos,
                'estadisticas' => $estadisticas,
                'total' => $total,
                'usuario' => $_SESSION
            ]);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al procesar el carrito';
            header('Location: /menu');
            exit;
        }
    }

    public static function contarItems()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || !isset($_SESSION['id_usuario'])) {
            echo json_encode(['ok' => true, 'total' => 0]);
            return;
        }

        try {
            $idUsuario = $_SESSION['id_usuario'];
            $estadisticas = Carrito::obtenerEstadisticas($idUsuario);

            echo json_encode([
                'ok' => true,
                'total' => (int)($estadisticas['total_productos'] ?? 0)
            ]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => true, 'total' => 0]);
        }
    }

    public static function obtenerItems()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || !isset($_SESSION['id_usuario'])) {
            echo json_encode(['ok' => true, 'items' => []]);
            return;
        }

        try {
            $idUsuario = $_SESSION['id_usuario'];
            $items = Carrito::obtenerCarritoCompleto($idUsuario);

            echo json_encode([
                'ok' => true,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => true, 'items' => []]);
        }
    }


    public static function retorno()
    {
        // Mercado Pago devuelve estos parámetros en la URL
        $status = $_GET['status'] ?? null;
        $paymentId = $_GET['payment_id'] ?? null;
        $externalReference = $_GET['external_reference'] ?? null;
        $merchantOrderId = $_GET['merchant_order_id'] ?? null;
        $preferenceId = $_GET['preference_id'] ?? null;

        // Guardamos los datos para depuración
        file_put_contents(
            __DIR__ . '/../debug_mp.log',
            "RETORNO MERCADO PAGO:\n" .
                print_r([
                    'status' => $status,
                    'payment_id' => $paymentId,
                    'external_reference' => $externalReference,
                    'merchant_order_id' => $merchantOrderId,
                    'preference_id' => $preferenceId
                ], true) .
                "\n-----------------------------\n",
            FILE_APPEND
        );

        // Si no tenemos referencia de la orden, no podemos continuar
        if (!$externalReference) {
            http_response_code(400);
            echo "No se recibió la referencia de la orden.";
            exit;
        }

        // Redirigimos según el estado informado por Mercado Pago
        switch ($status) {

            case 'approved':
                header(
                    'Location: /pago/exitoso?' .
                        http_build_query([
                            'orden' => $externalReference,
                            'payment_id' => $paymentId
                        ])
                );
                exit;

            case 'pending':
                header(
                    'Location: /pago/pendiente?' .
                        http_build_query([
                            'orden' => $externalReference,
                            'payment_id' => $paymentId
                        ])
                );
                exit;

            case 'rejected':
            case 'cancelled':
                header(
                    'Location: /pago/fallido?' .
                        http_build_query([
                            'orden' => $externalReference,
                            'payment_id' => $paymentId
                        ])
                );
                exit;

            default:
                // Estado desconocido
                header(
                    'Location: /pago/pendiente?' .
                        http_build_query([
                            'orden' => $externalReference,
                            'payment_id' => $paymentId,
                            'status' => $status
                        ])
                );
                exit;
        }
    }
}
