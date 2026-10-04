<?php

namespace Controllers;

use Classes\CodigoQR;
use Middlewares\ValidarModulo;
use Models\RetiroOrden;
use MVC\Router;

/**
 * Retiro en la cantina de los productos y fichas comprados por la web: se escanea el QR de retiro
 * del pedido (o se escribe su código corto), se ve qué compró, qué retiró y qué le queda, y se
 * registra lo que se entrega ahora (puede ser una parte).
 */
class ControlRetirosController
{
    private static function verificarAcceso(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['login']) && ValidarModulo::tiene('ENTREGA_PRODUCTOS');
    }

    public static function index(Router $router)
    {
        if (!self::verificarAcceso()) {
            header('Location: /');
            exit;
        }

        $router->render('control/retiros', [
            'nombreUsuario' => $_SESSION['nombre_usuario'] ?? '',
            'urlVolver' => match ((int)($_SESSION['perfil'] ?? 0)) {
                3 => '/administrador',
                4 => '/vendedor/caja',
                5 => '/vendedorproductos',
                default => null,
            },
        ]);
    }

    // POST {codigo}: muestra el pedido con lo que queda por retirar
    public static function buscar()
    {
        if (!self::verificarAcceso()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $orden = self::buscarOrden();
        echo json_encode(isset($orden['respuesta']) ? $orden['respuesta'] : self::respuestaPedido($orden));
    }

    // POST {codigo, items: {id_detalle: cantidad}}: registra lo que se entrega ahora
    public static function entregar()
    {
        if (!self::verificarAcceso()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $orden = self::buscarOrden();
        if (isset($orden['respuesta'])) {
            echo json_encode($orden['respuesta']);
            return;
        }
        if ($orden['estado'] !== 'pagado') {
            echo json_encode(self::respuestaPedido($orden));
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true) ?: [];
        $resultado = RetiroOrden::entregar((int)$orden['id_orden'], (array)($datos['items'] ?? []), (int)$_SESSION['id_usuario']);

        if (!$resultado['ok']) {
            echo json_encode(self::respuestaPedido($orden) + ['error' => $resultado['mensaje']]);
            return;
        }

        $respuesta = self::respuestaPedido($orden);
        $respuesta['resultado'] = 'verde';
        $respuesta['titulo'] = 'Entregado';
        $respuesta['mensaje'] = $resultado['mensaje'] . ($respuesta['puede_entregar'] ? '' : '. Pedido completo.');
        $respuesta['puede_deshacer'] = true;
        echo json_encode($respuesta);
    }

    // POST {codigo}: deshace la última entrega de este empleado en el pedido (se equivocó)
    public static function deshacer()
    {
        if (!self::verificarAcceso()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $orden = self::buscarOrden();
        if (isset($orden['respuesta'])) {
            echo json_encode($orden['respuesta']);
            return;
        }

        if (!RetiroOrden::deshacerUltimaEntrega((int)$orden['id_orden'], (int)$_SESSION['id_usuario'])) {
            echo json_encode(self::respuestaPedido($orden) + ['error' => 'Ya no se puede deshacer: pasó el tiempo o la hizo otra persona']);
            return;
        }

        $respuesta = self::respuestaPedido($orden);
        $respuesta['mensaje'] = 'Entrega deshecha. ' . $respuesta['mensaje'];
        echo json_encode($respuesta);
    }

    /**
     * Lee el código del pedido (texto del QR CINFSA-O-..., código completo o corto) y busca la orden.
     * Si no la encuentra devuelve ['respuesta' => ...].
     */
    private static function buscarOrden(): array
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?: [];
        $texto = trim((string)($datos['codigo'] ?? ''));

        if (stripos($texto, CodigoQR::PREFIJO_ENTRADA) === 0) {
            return ['respuesta' => self::rojo('Es una entrada', 'Ese QR es de una entrada al cine: se controla en la puerta de la sala')];
        }
        if (stripos($texto, CodigoQR::PREFIJO_ORDEN) === 0) {
            $texto = substr($texto, strlen(CodigoQR::PREFIJO_ORDEN));
        }
        $codigo = strtolower(preg_replace('/[\s-]/', '', $texto));

        if (!preg_match('/^[0-9a-f]{' . RetiroOrden::LARGO_CODIGO_CORTO . ',32}$/', $codigo)) {
            return ['respuesta' => self::rojo('QR no válido', 'No es un pedido de CINFSA')];
        }

        $ordenes = RetiroOrden::buscarPorCodigo($codigo);
        if (count($ordenes) > 1) {
            return ['respuesta' => self::rojo('Código incompleto', 'Hay más de un pedido con ese código: escaneá el QR')];
        }
        if (!$ordenes) {
            return ['respuesta' => self::rojo('Pedido inexistente', 'No hay ningún pedido con ese código')];
        }
        return $ordenes[0];
    }

    // Estado del pedido para la pantalla: qué compró, qué retiró, qué queda y el historial
    private static function respuestaPedido(array $orden): array
    {
        $items = RetiroOrden::items((int)$orden['id_orden']);
        $quedan = array_sum(array_column($items, 'quedan'));

        if ($orden['estado'] === 'cancelado') {
            [$resultado, $titulo, $mensaje] = ['rojo', 'Pedido cancelado', 'Esta compra fue cancelada: no se entrega'];
        } elseif ($orden['estado'] !== 'pagado') {
            [$resultado, $titulo, $mensaje] = ['rojo', 'Pedido sin pagar', 'El pago de esta compra no se completó'];
        } elseif (!$items) {
            [$resultado, $titulo, $mensaje] = ['rojo', 'Sin productos', 'Este pedido no tiene productos ni fichas para retirar'];
        } elseif ($quedan === 0) {
            [$resultado, $titulo, $mensaje] = ['rojo', 'Ya retiró todo', 'Este pedido ya se entregó completo'];
        } else {
            [$resultado, $titulo, $mensaje] = ['pendiente', 'Pedido para retirar', "Quedan $quedan para entregar"];
        }

        return [
            'ok' => true,
            'resultado' => $resultado,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'puede_entregar' => $resultado === 'pendiente',
            'pedido' => [
                'numero' => $orden['numero_orden'],
                'cliente' => $orden['cliente'],
                'fecha' => $orden['fecha_pago'] ? date('d/m/Y H:i', strtotime($orden['fecha_pago'])) : null,
                'codigo' => $orden['codigo_retiro'] ? RetiroOrden::codigoCorto($orden['codigo_retiro']) : null,
                'items' => array_map(fn($i) => [
                    'id_detalle' => (int)$i['id_detalle'],
                    'nombre' => $i['nombre_producto'],
                    'tipo' => $i['tipo_producto'] === 'fichas' ? 'Fichas' : 'Cantina',
                    'comprado' => $i['comprado'],
                    'entregado' => $i['entregado'],
                    'quedan' => $i['quedan'],
                ], $items),
                'historial' => array_map(fn($h) => [
                    'fecha' => date('d/m H:i', strtotime($h['fecha'])),
                    'detalle' => $h['cantidad'] . ' × ' . $h['nombre_producto'],
                    'usuario' => $h['nombre_usuario'],
                ], RetiroOrden::historial((int)$orden['id_orden'])),
            ],
        ];
    }

    private static function rojo(string $titulo, string $mensaje): array
    {
        return ['ok' => true, 'resultado' => 'rojo', 'titulo' => $titulo, 'mensaje' => $mensaje, 'puede_entregar' => false, 'pedido' => null];
    }
}
