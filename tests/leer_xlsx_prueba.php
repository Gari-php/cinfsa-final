<?php
/**
 * Lee un .xlsx generado por el sistema y devuelve sus filas como JSON (lista de listas de textos),
 * más los anchos de columna. Lo usa la tarea `leerXlsx` de Cypress.
 *
 *   php tests/leer_xlsx_prueba.php <archivo.xlsx>
 */

if (PHP_SAPI !== 'cli' || !isset($argv[1]) || !is_file($argv[1])) {
    fwrite(STDERR, "Uso: php tests/leer_xlsx_prueba.php <archivo.xlsx>\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($argv[1]) !== true) {
    fwrite(STDERR, "No es un .xlsx válido\n");
    exit(1);
}

$hoja = $zip->getFromName('xl/worksheets/sheet1.xml');
if ($hoja === false || ($xml = simplexml_load_string($hoja)) === false) {
    fwrite(STDERR, "El .xlsx no tiene una hoja válida\n");
    exit(1);
}

$filas = [];
foreach ($xml->sheetData->row as $fila) {
    $celdas = [];
    foreach ($fila->c as $celda) {
        $celdas[] = isset($celda->is) ? (string)$celda->is->t : (string)$celda->v;
    }
    $filas[] = $celdas;
}

$anchos = [];
foreach ($xml->cols->col ?? [] as $col) {
    $anchos[] = (float)$col['width'];
}

echo json_encode(['filas' => $filas, 'anchos' => $anchos], JSON_UNESCAPED_UNICODE);
