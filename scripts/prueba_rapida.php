<?php
/**
 * Prueba rápida del sistema (unos 2 minutos). Correr antes de cada commit:
 *
 *   php scripts/prueba_rapida.php            (o `npm run test:rapido`)
 *
 * 1. Verifica que cada ruta de public/index.php apunte a un método existente.
 * 2. Recrea la base de prueba (cinfsa1_test) — nunca toca la base real.
 * 3. Levanta un servidor PHP propio, inicia sesión con cada rol y pide todas las rutas GET.
 * 4. Informa errores de PHP (fatales, warnings, notices) y respuestas 500.
 *
 * Sale con código 0 si todo está bien y 1 si encontró problemas.
 */

$raiz = dirname(__DIR__);
chdir($raiz);
require $raiz . '/vendor/autoload.php';

$php = PHP_BINARY;
$puerto = (int)(getenv('PUERTO_PRUEBA') ?: 3099);
$base = "http://localhost:$puerto";
$tmp = sys_get_temp_dir() . '/cinfsa_prueba_' . getmypid();
@mkdir($tmp);
$logErrores = "$tmp/php_errores.log";
$problemas = [];

$usuarios = json_decode(file_get_contents("$raiz/cypress/fixtures/usuarios.json"), true);
$roles = ['anonimo' => null] + array_intersect_key($usuarios, array_flip(['admin', 'vfunciones', 'vproductos', 'cliente', 'control']));

// Rutas que no se piden: cierran la sesión o escriben en logs de pagos
$excluidas = ['/logaut', '/carrito/retorno'];

function titulo(string $texto): void { echo "\n== $texto\n"; }

// ─── 1. Rutas → métodos ──────────────────────────────────────────
titulo('Rutas registradas');
$fuente = file_get_contents("$raiz/public/index.php");
preg_match_all('/^\s*use\s+([A-Za-z_\x5c]+);/mi', $fuente, $usos);
$alias = [];
foreach ($usos[1] as $fq) { $partes = explode('\\', $fq); $alias[end($partes)] = $fq; }

preg_match_all('/\$router->(get|post)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[\s*(\w+)::class\s*,\s*[\'"](\w+)[\'"]\s*\]/', $fuente, $rutas, PREG_SET_ORDER);
$rutasGet = [];
$vistas = [];
foreach ($rutas as [, $verbo, $url, $clase, $metodo]) {
    $fq = $alias[$clase] ?? "Controllers\\$clase";
    if (!method_exists($fq, $metodo)) {
        $problemas[] = "Ruta $verbo $url apunta a $fq::$metodo, que no existe";
    }
    $clave = "$verbo $url";
    if (isset($vistas[$clave])) $problemas[] = "Ruta duplicada: $clave";
    $vistas[$clave] = true;
    if ($verbo === 'get' && !in_array($url, $excluidas, true)) $rutasGet[] = $url;
}
$rutasGet = array_values(array_unique($rutasGet));
echo count($rutas) . " rutas, " . count($rutasGet) . " GET a probar\n";

// ─── 2. Base de prueba ───────────────────────────────────────────
titulo('Base de prueba');
passthru(escapeshellarg($php) . ' ' . escapeshellarg("$raiz/tests/preparar_base_pruebas.php"), $codigo);
if ($codigo !== 0) {
    fwrite(STDERR, "No se pudo preparar la base de prueba.\n");
    exit(1);
}

// ─── 3. Servidor propio ──────────────────────────────────────────
titulo("Servidor en $base");
$comando = [$php, '-d', 'display_errors=1', '-d', 'error_reporting=32767', '-d', 'log_errors=1',
    '-d', "error_log=$logErrores", '-S', "localhost:$puerto", '-t', "$raiz/public", "$raiz/tests/servidor_pruebas.php"];
$servidor = proc_open($comando, [1 => ['file', "$tmp/servidor.log", 'a'], 2 => ['file', "$tmp/servidor.log", 'a']], $tuberias);

function pedir(string $url, ?string $cookies = null, array $opciones = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 30] + $opciones);
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
    }
    $cuerpo = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return [$estado, (string)$cuerpo];
}

$listo = false;
for ($i = 0; $i < 50 && !$listo; $i++) {
    usleep(200000);
    [$estado] = pedir("$base/");
    $listo = $estado > 0;
}
if (!$listo) {
    proc_terminate($servidor);
    fwrite(STDERR, "El servidor no arrancó en el puerto $puerto (¿está ocupado? usá PUERTO_PRUEBA=otro).\n");
    exit(1);
}

// ─── 4. Barrido por rol ──────────────────────────────────────────
$resumen = [];
foreach ($roles as $rol => $datos) {
    $cookies = "$tmp/cookies_$rol.txt";
    @unlink($cookies);

    if ($datos) {
        [, $html] = pedir("$base/", $cookies);
        preg_match('/CSRF_TOKEN = "([a-f0-9]+)"/', $html, $m);
        [, $respuesta] = pedir("$base/", $cookies, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['nombre_usuario' => $datos['usuario'], 'clave_usuario' => $datos['clave']]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-CSRF-Token: ' . ($m[1] ?? '')],
        ]);
        if (!(json_decode($respuesta, true)['ok'] ?? false)) {
            $problemas[] = "No se pudo iniciar sesión como $rol: $respuesta";
            continue;
        }
    }

    foreach ($rutasGet as $ruta) {
        [$estado, $cuerpo] = pedir($base . $ruta, $datos ? $cookies : null);
        $resumen[$rol][$estado] = ($resumen[$rol][$estado] ?? 0) + 1;
        if ($estado >= 500) {
            $problemas[] = "[$rol] GET $ruta respondió $estado";
        }
        if (preg_match('/<b>(Fatal error|Warning|Notice|Deprecated)<\/b>:\s*(.{0,200}?)<br/s', $cuerpo, $e)) {
            $problemas[] = "[$rol] GET $ruta: {$e[1]}: " . trim(strip_tags($e[2]));
        }
    }
    printf("%-11s %s\n", $rol, implode('  ', array_map(fn($c, $n) => "{$c}: {$n}", array_keys($resumen[$rol] ?? []), $resumen[$rol] ?? [])));
}

proc_terminate($servidor);
proc_close($servidor);

// Errores que PHP registró del lado del servidor (incluye los que no llegan al HTML)
if (is_file($logErrores)) {
    foreach (array_unique(file($logErrores, FILE_IGNORE_NEW_LINES)) as $linea) {
        if (preg_match('/PHP (Fatal error|Warning|Notice|Deprecated|Parse error):\s*(.*)/', $linea, $e)) {
            $problemas[] = "Log del servidor: {$e[1]}: {$e[2]}";
        }
    }
}

// ─── Resultado ───────────────────────────────────────────────────
$problemas = array_values(array_unique($problemas));
array_map('unlink', glob("$tmp/*"));
@rmdir($tmp);

titulo('Resultado');
if ($problemas) {
    echo count($problemas) . " problema(s):\n";
    foreach ($problemas as $p) echo "  - $p\n";
    exit(1);
}
echo "OK: sin errores de PHP en " . (count($rutasGet) * count($roles)) . " pedidos.\n";
exit(0);
