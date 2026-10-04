<?php
/**
 * Crea un pedido web de productos y fichas (orden + detalle_orden + código de retiro), sin pasar
 * por MercadoPago. Lo usa la tarea `crearPedidoWeb` de Cypress para probar el retiro en la cantina.
 * Solo opera sobre la base de prueba.
 *
 * El pedido trae 10 "Pochoclo Chico" (producto 1) y 6 fichas de la "Maquina de Prueba" (máquina 1).
 *
 * Uso: php tests/pedido_web_prueba.php <id_usuario> [estado]   (estado: pagado por defecto)
 * Devuelve JSON: {"id_orden": ..., "numero_orden": "...", "codigo": "..."}
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/funciones.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$base = env('DB_NAME_TEST', 'cinfsa1_test');
if (!preg_match('/_test$/', $base)) {
    fwrite(STDERR, "La base de prueba debe terminar en '_test'.\n");
    exit(1);
}

$idUsuario = (int)($argv[1] ?? 0);
$estado = $argv[2] ?? 'pagado';
if (!$idUsuario || !in_array($estado, ['pendiente', 'pagado', 'cancelado', 'fallido'], true)) {
    fwrite(STDERR, "Uso: php tests/pedido_web_prueba.php <id_usuario> [pendiente|pagado|cancelado|fallido]\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''), $base);
$db->set_charset('utf8mb4');

$db->begin_transaction();

$numeroOrden = 'TEST-' . bin2hex(random_bytes(4));
$codigo = bin2hex(random_bytes(16));
$stmt = $db->prepare("INSERT INTO ordenes (id_usuario, numero_orden, total, estado, metodo_pago, payment_id, fecha_pago, codigo_retiro)
    VALUES (?, ?, 26000, ?, 'mercadopago', 'TEST', NOW(), ?)");
$stmt->bind_param('isss', $idUsuario, $numeroOrden, $estado, $codigo);
$stmt->execute();
$idOrden = $db->insert_id;

$db->query("INSERT INTO detalle_orden (id_orden, id_producto, tipo_producto, nombre_producto, cantidad, precio_unitario, subtotal) VALUES
    ($idOrden, 1, 'cantina', 'Pochoclo Chico', 10, 2000, 20000),
    ($idOrden, 1, 'fichas', 'Fichas - Maquina de Prueba', 6, 1000, 6000)");

$db->commit();
echo json_encode(['id_orden' => $idOrden, 'numero_orden' => $numeroOrden, 'codigo' => $codigo]);
