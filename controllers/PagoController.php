<?php

namespace Controllers;

use Models\Carrito;
use MVC\Router;

class PagoController
{

    private static function verificarUsuario()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['id_usuario'];
    }

    public static function crearOrden()
    {

        // URL pública del sitio (APP_URL en .env): MercadoPago vuelve a /carrito/retorno de este dominio
        $baseUrl = rtrim($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'https://outcome-mammal-sudoku.ngrok-free.dev', '/');
        if (!self::verificarUsuario()) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
            return;
        }

        header('Content-Type: application/json');

        try {
            $datos = json_decode(file_get_contents('php://input'), true);
            $idUsuario = $_SESSION['id_usuario'];

            $items = Carrito::obtenerCarritoCompleto($idUsuario);

            if (empty($items)) {
                echo json_encode(['ok' => false, 'mensaje' => 'El carrito está vacío']);
                return;
            }

            $total = 0;
            foreach ($items as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }

            $numeroOrden = 'CINFSA-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

            $db = \Models\ActiveRecord::getDB();
            $query = "INSERT INTO ordenes (id_usuario, numero_orden, total, estado, metodo_pago, fecha_creacion) 
                    VALUES (?, ?, ?, 'pendiente', 'Mercado Pago', NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([$idUsuario, $numeroOrden, $total]);
            $idOrden = $db->insert_id;

            foreach ($items as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $query = "INSERT INTO detalle_orden 
                        (id_orden, id_producto, tipo_producto, id_butaca, id_funcion, nombre_producto, cantidad, precio_unitario, subtotal) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $idOrden,
                    $item['id_producto'],
                    $item['tipo_producto'],
                    $item['id_butaca'] ?? null,
                    $item['id_funcion'] ?? null,
                    $item['nombre'],
                    $item['cantidad'],
                    $item['precio'],
                    $subtotal
                ]);
            }

            // USAR API REST DIRECTA DE MERCADO PAGO
            $mpConfig = require __DIR__ . '/../includes/config/mercadopago.php';

            // Preparar items para MP
            $itemsMP = [];
            foreach ($items as $item) {
                $itemsMP[] = [
                    'title' => substr($item['nombre'], 0, 100),
                    'quantity' => (int)$item['cantidad'],
                    'unit_price' => (float)$item['precio'],
                    'currency_id' => 'ARS'
                ];
            }


            // Crear preferencia usando cURL
            $preferenceData = [
                'external_reference' => $numeroOrden,
                'items' => $itemsMP,
                'back_urls' => [
                    'success' => $baseUrl . '/carrito/retorno',
                    'failure' => $baseUrl . '/carrito/retorno',
                    'pending' => $baseUrl . '/carrito/retorno'
                ],

                'auto_return' => 'approved',

            ];

            $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $mpConfig['access_token'],
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preferenceData));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 201) {
                file_put_contents(
                    __DIR__ . '/../debug_mp.log',
                    "Error HTTP {$httpCode}: {$response}" . PHP_EOL,
                    FILE_APPEND
                );
                $preference = json_decode($response, true);
                file_put_contents(
                    __DIR__ . '/../debug_mp.log',
                    "PREFERENCIA CREADA:\n" .
                        json_encode($preference, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) .
                        "\n\n",
                    FILE_APPEND
                );
                throw new \Exception("Error al crear preferencia en Mercado Pago");
            }

            $preference = json_decode($response, true);

            if (!isset($preference['init_point'])) {
                throw new \Exception("Mercado Pago no devolvió URL de pago");
            }

            // Actualizar orden
            $query = "UPDATE ordenes SET preference_id = ? WHERE id_orden = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$preference['id'], $idOrden]);

            echo json_encode([
                'ok' => true,
                'init_point' => $preference['init_point'],
                'numero_orden' => $numeroOrden
            ]);
        } catch (\Exception $e) {
            file_put_contents(
                __DIR__ . '/../debug_mp.log',
                "ERROR: " . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al procesar el pago',
                'detalle' => $e->getMessage()
            ]);
        }
    }

    /**
     * Confirma contra la API de MercadoPago (server-to-server) que un pago
     * existe, está aprobado y corresponde a la orden indicada. Nunca hay que
     * confiar en el estado que manda el navegador por la URL de retorno.
     */
    private static function verificarPagoAprobado($paymentId, $numeroOrden, $montoEsperado)
    {
        if (!$paymentId || !ctype_digit((string)$paymentId)) {
            return false;
        }

        $mpConfig = require __DIR__ . '/../includes/config/mercadopago.php';

        $ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $mpConfig['access_token']
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }

        $pago = json_decode($response, true);

        if (!$pago || ($pago['status'] ?? null) !== 'approved') {
            return false;
        }

        if (($pago['external_reference'] ?? null) !== $numeroOrden) {
            return false;
        }

        // Tolerancia de 1 peso por redondeo
        if (abs((float)($pago['transaction_amount'] ?? 0) - (float)$montoEsperado) > 1) {
            return false;
        }

        return true;
    }

    public static function exitoso(Router $router)
    {
        if (!self::verificarUsuario()) {
            header('Location: /');
            exit;
        }

        $numeroOrden = $_GET['orden'] ?? null;
        $paymentId = $_GET['payment_id'] ?? null;

        if ($numeroOrden && $paymentId) {
            $db = \Models\ActiveRecord::getDB();
            $idUsuario = $_SESSION['id_usuario'];

            // La orden debe existir, pertenecer al usuario en sesión y seguir pendiente
            $queryOrden = "SELECT id_orden, total FROM ordenes WHERE numero_orden = ? AND id_usuario = ? AND estado = 'pendiente'";
            $stmtOrden = $db->prepare($queryOrden);
            $stmtOrden->execute([$numeroOrden, $idUsuario]);
            $resultOrden = $stmtOrden->get_result();
            $orden = $resultOrden->fetch_assoc();
            $idOrden = $orden['id_orden'] ?? null;

            // Verificación real del pago contra la API de MercadoPago antes de acreditar nada
            $pagoValido = $idOrden && self::verificarPagoAprobado($paymentId, $numeroOrden, $orden['total']);

            if (!$pagoValido) {
                $router->render('cliente/pago-exitoso', [
                    'numero_orden' => $numeroOrden,
                    'payment_id' => $paymentId,
                    'error_verificacion' => true
                ]);
                return;
            }

            // PASO 1: Actualizar orden
            $query = "UPDATE ordenes SET estado = 'pagado', payment_id = ?, fecha_pago = NOW(), fecha_actualizacion = NOW()
                WHERE numero_orden = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$paymentId, $numeroOrden]);

            // ⭐ PASO 3: MARCAR BUTACAS VENDIDAS Y CREAR LA ENTRADA
            if ($idOrden) {
                $queryDetalles = "SELECT id_butaca, id_funcion FROM detalle_orden 
                 WHERE id_orden = ? AND tipo_producto = 'butacas' AND id_butaca IS NOT NULL";
                $stmtDetalles = $db->prepare($queryDetalles);
                $stmtDetalles->execute([$idOrden]);
                $resultDetalles = $stmtDetalles->get_result();

                while ($detalle = $resultDetalles->fetch_assoc()) {
                    $idButaca = $detalle['id_butaca'];
                    $idFuncion = $detalle['id_funcion'];

                    $stmtCheck = $db->prepare("SELECT id_venta_butaca FROM butacas_vendidas 
                              WHERE id_butaca = ? AND id_funcion = ?");
                    $stmtCheck->execute([$idButaca, $idFuncion]);

                    if ($stmtCheck->get_result()->num_rows === 0) {
                        // NUEVO: crear la entrada real, usando el tipo de entrada de la función
                        $idEntrada = null;
                        $stmtTipoEntrada = $db->prepare("SELECT rela_tipo_entrada FROM funciones WHERE id_funcion = ?");
                        $stmtTipoEntrada->execute([$idFuncion]);
                        $resTipoEntrada = $stmtTipoEntrada->get_result()->fetch_assoc();

                        if ($resTipoEntrada) {
                            $entrada = new \Models\Entrada([
                                'rela_tipo_entrada' => $resTipoEntrada['rela_tipo_entrada'],
                                'rela_funcion' => $idFuncion,
                                'estado' => \Models\Entrada::ESTADO_ACTIVA
                            ]);

                            $resultadoEntrada = $entrada->crearEntrada();

                            if ($resultadoEntrada['resultado']) {
                                $idEntrada = $resultadoEntrada['id_insertado'];
                            } else {
                                error_log("⚠️ WEB: No se pudo crear la entrada para butaca {$idButaca} - Orden {$idOrden}: " . ($resultadoEntrada['error'] ?? ''));
                            }
                        }

                        // Insertar en butacas_vendidas, ahora con id_entrada real (o NULL si algo falló arriba)
                        $stmtInsert = $db->prepare("INSERT INTO butacas_vendidas (
                        id_butaca, id_funcion, id_entrada, id_orden, fecha_venta
                        ) VALUES (?, ?, ?, ?, NOW())");

                        $stmtInsert->execute([$idButaca, $idFuncion, $idEntrada, $idOrden]);

                        // NOTA: no se toca rela_estado_butaca acá. Ese flag es exclusivo del bloqueo
                        // manual del administrador (mantenimiento); la disponibilidad real por función
                        // ya queda registrada en butacas_vendidas. Marcarlo acá bloqueaba la butaca
                        // para TODAS las demás funciones (otros días/horarios) de forma global.

                        error_log("✅ WEB: Butaca {$idButaca} vendida - Orden {$idOrden}" . ($idEntrada ? " - Entrada {$idEntrada}" : " - SIN ENTRADA"));
                    }
                }
            }

            // PASO 4: Procesar carrito (productos y fichas)
            $itemsOriginales = \Models\Carrito::obtenerCarritoCompleto($idUsuario);

            $items = [];
            $total = 0;
            foreach ($itemsOriginales as $item) {
                $precio = isset($item['precio']) ? (float)$item['precio'] : 0;
                $cantidad = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                $item['subtotal'] = $precio * $cantidad;
                $total += $item['subtotal'];
                $items[] = $item;
            }

            \Models\Carrito::procesarVentaCarrito($idUsuario, $idOrden);
            \Models\Carrito::vaciarCarrito($idUsuario);

            // PASO 5: Enviar email
            try {
                $email = new \Classes\Email($_SESSION['email'], $_SESSION['nombre_usuario'], null);
                $email->enviarTicketCompra($numeroOrden, $items, $total, $paymentId);
            } catch (\Exception $e) {
                error_log("Error email: " . $e->getMessage());
            }
        }

        $router->render('cliente/pago-exitoso', [
            'numero_orden' => $numeroOrden,
            'payment_id' => $paymentId
        ]);
    }
    public static function fallido(Router $router)
    {
        $numeroOrden = $_GET['orden'] ?? null;

        // Marcar orden como fallida
        if ($numeroOrden) {
            $db = \Models\ActiveRecord::getDB();
            $query = "UPDATE ordenes SET estado = 'fallido' WHERE numero_orden = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$numeroOrden]);
        }

        $router->render('cliente/pago-fallido', [
            'numero_orden' => $numeroOrden
        ]);
    }

    public static function pendiente(Router $router)
    {
        $numeroOrden = $_GET['orden'] ?? null;

        $router->render('cliente/pago-pendiente', [
            'numero_orden' => $numeroOrden
        ]);
    }
}
