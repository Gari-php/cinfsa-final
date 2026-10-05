<?php
/**
 * Simula una compra web ya pagada de una butaca (orden pagada + entrada + butaca vendida), sin pasar
 * por MercadoPago. Lo usa la tarea `venderButacaWeb` de Cypress. Solo opera sobre la base de prueba.
 *
 * Por defecto la entrada se crea SIN codigo_acceso, como las vendidas antes del QR: así se prueba
 * que se le asigna uno la primera vez que se muestra. Con <con_codigo> = 1 se le genera uno.
 *
 * Uso: php tests/venta_web_prueba.php <id_butaca> <id_funcion> <id_usuario> [con_codigo]
 * Devuelve el id de la entrada creada.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/funciones.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$base = env('DB_NAME_TEST', 'cinfsa1_test');
if (!preg_match('/_test$/', $base)) {
    fwrite(STDERR, "La base de prueba debe terminar en '_test'.\n");
    exit(1);
}

[, $idButaca, $idFuncion, $idUsuario, $conCodigo] = array_map('intval', $argv + [0, 0, 0, 0, 0]);
if (!$idButaca || !$idFuncion || !$idUsuario) {
    fwrite(STDERR, "Uso: php tests/venta_web_prueba.php <id_butaca> <id_funcion> <id_usuario>\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASSWORD', ''), $base);
$db->set_charset('utf8mb4');

$db->begin_transaction();

$numeroOrden = 'TEST-' . bin2hex(random_bytes(4));
$stmt = $db->prepare("INSERT INTO ordenes (id_usuario, numero_orden, total, estado, metodo_pago, payment_id, fecha_pago)
    VALUES (?, ?, 3000, 'pagado', 'mercadopago', 'TEST', NOW())");
$stmt->bind_param('is', $idUsuario, $numeroOrden);
$stmt->execute();
$idOrden = $db->insert_id;

$codigo = $conCodigo ? bin2hex(random_bytes(16)) : null;
$stmt = $db->prepare("INSERT INTO entradas (rela_tipo_entrada, rela_funcion, estado, codigo_acceso)
    SELECT rela_tipo_entrada, id_funcion, 1, ? FROM funciones WHERE id_funcion = ?");
$stmt->bind_param('si', $codigo, $idFuncion);
$stmt->execute();
$idEntrada = $db->insert_id;

// Como en una compra real: la butaca también figura en el detalle de la orden
$stmt = $db->prepare("INSERT INTO detalle_orden (id_orden, id_producto, tipo_producto, id_butaca, id_funcion, nombre_producto, cantidad, precio_unitario, subtotal)
    VALUES (?, ?, 'butacas', ?, ?, 'Butaca de prueba', 1, 3000, 3000)");
$stmt->bind_param('iiii', $idOrden, $idButaca, $idButaca, $idFuncion);
$stmt->execute();

$stmt = $db->prepare("INSERT INTO butacas_vendidas (id_butaca, id_funcion, id_entrada, id_orden) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iiii', $idButaca, $idFuncion, $idEntrada, $idOrden);
$stmt->execute();

$db->commit();
echo $idEntrada;
