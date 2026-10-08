<?php
namespace Classes;

use TCPDF;


class ExportadorDatos {

    // Márgenes y tipografía de la tabla del PDF (mm / puntos)
    const PDF_MARGEN = 10;
    const PDF_FUENTE = 8;
    const PDF_LINEA = 4;          // alto de cada línea de texto
    const PDF_RELLENO = 1.5;      // espacio vertical dentro de la celda
    const PDF_ANCHO_MIN = 12;     // ninguna columna más angosta que esto
    const PDF_TEXTO_MAX = 300;    // textos muy largos (sinopsis) se recortan a esto

    /**
     * Exportar datos a Excel (.xlsx real, con anchos de columna según el contenido)
     * @param array $datos - Array de datos a exportar
     * @param array $columnas - Array con las columnas ['clave' => 'Título', ...]
     * @param string $nombreArchivo - Nombre del archivo sin extensión
     * @param string $titulo - Título del reporte
     */
    public static function exportarExcel($datos, $columnas, $nombreArchivo = 'reporte', $titulo = 'Reporte') {
        $filas = [];
        foreach ($datos as $fila) {
            $fila = (array)$fila;
            $filaExportar = [];
            foreach (array_keys($columnas) as $clave) {
                $filaExportar[] = self::textoPlano($fila[$clave] ?? '');
            }
            $filas[] = $filaExportar;
        }

        GeneradorXlsx::descargar(
            $nombreArchivo . '_' . date('Y-m-d_H-i-s'),
            $titulo,
            array_values($columnas),
            $filas
        );
    }

    /**
     * Exportar datos a PDF
     * @param array $datos - Array de datos a exportar
     * @param array $columnas - Array con las columnas ['clave' => 'Título', ...]
     * @param string $nombreArchivo - Nombre del archivo sin extensión
     * @param string $titulo - Título del reporte
     * @param array $config - Configuraciones adicionales
     */
    public static function exportarPDF($datos, $columnas, $nombreArchivo = 'reporte', $titulo = 'Reporte', $config = []) {
        $configuracion = array_merge([
            'orientacion' => 'L',
            'empresa' => 'CINFSA - Sistema de Gestión',
            'mostrar_imagenes' => false,
            'columna_imagen' => 'imagen_pelicula',
            'ruta_imagenes' => '/assets/img/peliculas/',
            'ancho_imagen' => 20,
            'alto_imagen' => 25
        ], $config);

        $pdf = new TCPDF($configuracion['orientacion'], PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('CINFSA System');
        $pdf->SetAuthor($configuracion['empresa']);
        $pdf->SetTitle($titulo);
        $pdf->SetSubject($titulo);

        // Encabezado de página (empresa + título) y pie con "página X / Y"
        $pdf->SetHeaderData('', 0, $configuracion['empresa'], $titulo . "\nGenerado el: " . date('d/m/Y H:i'));
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);

        $pdf->SetMargins(self::PDF_MARGEN, 28, self::PDF_MARGEN);
        $pdf->SetHeaderMargin(8);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(false);

        $pdf->AddPage();

        // Datos como texto plano, una sola vez
        $filas = [];
        foreach ($datos as $fila) {
            $fila = (array)$fila;
            $limpia = [];
            foreach (array_keys($columnas) as $clave) {
                $limpia[$clave] = self::esColumnaImagen($clave, $configuracion)
                    ? ($fila[$clave] ?? '')
                    : self::recortar(self::textoPlano($fila[$clave] ?? ''), self::PDF_TEXTO_MAX);
            }
            $filas[] = $limpia;
        }

        $alineaciones = self::alineacionesPDF($filas, $columnas, $configuracion);
        $anchos = self::anchosColumnasPDF($pdf, $filas, $columnas, $configuracion, $alineaciones);

        self::crearTablaPDF($pdf, $filas, $columnas, $anchos, $alineaciones, $configuracion);

        $pdf->Output($nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
        exit;
    }

    /**
     * Valor como texto plano: sin etiquetas HTML ni entidades (&amp; → &), sin espacios de más.
     */
    private static function textoPlano($valor): string {
        if (is_object($valor)) {
            $valor = (string)$valor;
        }
        $valor = html_entity_decode(strip_tags((string)$valor), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[ \t]+/', ' ', $valor));
    }

    private static function recortar(string $texto, int $maximo): string {
        return mb_strlen($texto, 'UTF-8') > $maximo ? mb_substr($texto, 0, $maximo - 1, 'UTF-8') . '…' : $texto;
    }

    private static function esColumnaImagen($clave, array $config): bool {
        return $config['mostrar_imagenes'] && $clave === $config['columna_imagen'];
    }

    /**
     * Ancho de cada columna según su contenido: se mide el encabezado y los textos reales,
     * se limita cada columna a un máximo y se reparte el ancho de la hoja en proporción.
     * Los textos que no entran pasan a otra línea (la fila crece).
     */
    private static function anchosColumnasPDF(TCPDF $pdf, array $filas, array $columnas, array $config, array $alineaciones): array {
        $disponible = $pdf->getPageWidth() - 2 * self::PDF_MARGEN;
        $maximoColumna = $disponible * 0.35;
        $fijos = [];
        $naturales = [];   // lo que mide el texto más largo en una sola línea
        $minimos = [];     // la palabra más larga: por debajo de esto se cortarían palabras
        $palabraMasLarga = fn(string $texto) => max(array_map(fn($p) => $pdf->GetStringWidth($p), explode(' ', $texto)));

        foreach ($columnas as $clave => $encabezado) {
            if (self::esColumnaImagen($clave, $config)) {
                $fijos[$clave] = $config['ancho_imagen'] + 4;
                continue;
            }

            // El encabezado puede partirse en palabras: alcanza con que entre su palabra más larga
            $pdf->SetFont('helvetica', 'B', self::PDF_FUENTE + 0.5);
            $minimo = $palabraMasLarga($encabezado);
            $ancho = $minimo;

            $pdf->SetFont('helvetica', '', self::PDF_FUENTE);
            foreach ($filas as $fila) {
                if ($fila[$clave] === '') {
                    continue;
                }
                $ancho = max($ancho, $pdf->GetStringWidth($fila[$clave]));
                $minimo = max($minimo, $palabraMasLarga($fila[$clave]));
            }
            $naturales[$clave] = min($maximoColumna, max(self::PDF_ANCHO_MIN, $ancho + 4));
            $minimos[$clave] = min($naturales[$clave], max(self::PDF_ANCHO_MIN, $minimo + 4));
        }

        $libre = $disponible - array_sum($fijos);
        $sumaNaturales = array_sum($naturales) ?: 1;
        $anchos = [];

        if ($sumaNaturales <= $libre) {
            // Sobra lugar: cada columna mide lo que necesita y el sobrante va a las columnas
            // de texto (alineadas a la izquierda), así un ID o un estado no quedan anchísimos
            $deTexto = array_filter($naturales, fn($c) => ($alineaciones[$c] ?? 'L') === 'L', ARRAY_FILTER_USE_KEY) ?: $naturales;
            $sumaTexto = array_sum($deTexto) ?: 1;
            $sobrante = $libre - $sumaNaturales;
            foreach ($columnas as $clave => $_) {
                $anchos[$clave] = $fijos[$clave]
                    ?? $naturales[$clave] + (isset($deTexto[$clave]) ? $sobrante * $deTexto[$clave] / $sumaTexto : 0);
            }
            return $anchos;
        }

        // Falta lugar: cada columna arranca con su mínimo (no se cortan palabras) y lo que queda
        // se reparte según cuánto le falta a cada una; así solo los textos largos pasan a otra línea
        $sumaMinimos = array_sum($minimos);
        if ($sumaMinimos >= $libre) {
            // Ni los mínimos entran (muchísimas columnas): se achican en proporción
            foreach ($columnas as $clave => $_) {
                $anchos[$clave] = $fijos[$clave] ?? ($minimos[$clave] / ($sumaMinimos ?: 1) * $libre);
            }
            return $anchos;
        }

        $resto = $libre - $sumaMinimos;
        $faltantes = [];
        foreach ($minimos as $clave => $minimo) {
            $faltantes[$clave] = $naturales[$clave] - $minimo;
        }
        $sumaFaltantes = array_sum($faltantes) ?: 1;
        foreach ($columnas as $clave => $_) {
            $anchos[$clave] = $fijos[$clave] ?? ($minimos[$clave] + $resto * $faltantes[$clave] / $sumaFaltantes);
        }

        return $anchos;
    }

    /**
     * Alineación por columna: números y montos a la derecha; IDs, fechas, horas y valores cortos
     * centrados; el resto (textos) a la izquierda.
     */
    private static function alineacionesPDF(array $filas, array $columnas, array $config): array {
        $alineaciones = [];
        foreach ($columnas as $clave => $encabezado) {
            if (self::esColumnaImagen($clave, $config)) {
                $alineaciones[$clave] = 'C';
                continue;
            }

            $valores = array_filter(array_column($filas, $clave), fn($v) => $v !== '');
            if (!$valores) {
                $alineaciones[$clave] = 'C';
                continue;
            }

            $todos = fn($patron) => count(array_filter($valores, fn($v) => preg_match($patron, $v))) === count($valores);

            if ($todos('/^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}|^\d{4}-\d{2}-\d{2}|^\d{1,2}:\d{2}/')) {
                $alineaciones[$clave] = 'C';                                    // fechas y horas
            } elseif ($todos('/^-?\$?\s?-?[\d.,]+%?$/')) {
                $esId = stripos($encabezado, 'id') === 0 || $encabezado === '#';
                $alineaciones[$clave] = $esId ? 'C' : 'R';                      // números y montos
            } elseif (max(array_map(fn($v) => mb_strlen($v, 'UTF-8'), $valores)) <= 12
                      && count(array_unique($valores)) <= 5) {
                $alineaciones[$clave] = 'C';                                    // estados (pocos valores repetidos)
            } else {
                $alineaciones[$clave] = 'L';                                    // textos
            }
        }
        return $alineaciones;
    }

    private static function encabezadoTablaPDF(TCPDF $pdf, array $columnas, array $anchos): void {
        $pdf->SetFont('helvetica', 'B', self::PDF_FUENTE + 0.5);
        $pdf->SetFillColor(237, 133, 15);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(200, 200, 200);

        // Alto según el encabezado que más líneas necesite
        $lineas = 1;
        foreach ($columnas as $clave => $titulo) {
            $lineas = max($lineas, $pdf->getNumLines($titulo, $anchos[$clave]));
        }
        $alto = $lineas * self::PDF_LINEA + 2 * self::PDF_RELLENO;

        $x = self::PDF_MARGEN;
        $y = $pdf->GetY();
        foreach ($columnas as $clave => $titulo) {
            $pdf->MultiCell($anchos[$clave], $alto, $titulo, 1, 'C', true, 0, $x, $y, true, 0, false, true, $alto, 'M');
            $x += $anchos[$clave];
        }
        $pdf->SetXY(self::PDF_MARGEN, $y + $alto);

        $pdf->SetFont('helvetica', '', self::PDF_FUENTE);
        $pdf->SetTextColor(40, 40, 40);
    }

    private static function crearTablaPDF(TCPDF $pdf, array $filas, array $columnas, array $anchos, array $alineaciones, array $config) {
        $limiteInferior = $pdf->getPageHeight() - 18;

        self::encabezadoTablaPDF($pdf, $columnas, $anchos);

        $par = false;
        foreach ($filas as $fila) {
            // Alto de la fila: la celda con más líneas (o la imagen)
            $lineas = 1;
            foreach ($columnas as $clave => $_) {
                if (!self::esColumnaImagen($clave, $config) && $fila[$clave] !== '') {
                    $lineas = max($lineas, $pdf->getNumLines($fila[$clave], $anchos[$clave]));
                }
            }
            $alto = $lineas * self::PDF_LINEA + 2 * self::PDF_RELLENO;
            if ($config['mostrar_imagenes'] && !empty($fila[$config['columna_imagen']] ?? '')) {
                $alto = max($alto, $config['alto_imagen']);
            }

            // Si no entra, página nueva con el encabezado de la tabla repetido
            if ($pdf->GetY() + $alto > $limiteInferior) {
                $pdf->AddPage();
                self::encabezadoTablaPDF($pdf, $columnas, $anchos);
            }

            $pdf->SetFillColor($par ? 248 : 255, $par ? 248 : 255, $par ? 248 : 255);
            $x = self::PDF_MARGEN;
            $y = $pdf->GetY();

            foreach ($columnas as $clave => $_) {
                $ancho = $anchos[$clave];

                if (self::esColumnaImagen($clave, $config)) {
                    $pdf->MultiCell($ancho, $alto, '', 1, 'C', true, 0, $x, $y, true, 0, false, true, $alto, 'M');
                    $archivo = basename((string)($fila[$clave] ?? ''));
                    $rutaImagen = __DIR__ . '/../public' . $config['ruta_imagenes'] . $archivo;
                    if ($archivo !== '' && is_file($rutaImagen)) {
                        $pdf->Image($rutaImagen, $x + 2, $y + 1.5, $config['ancho_imagen'], $alto - 3, '', '', '', true, 150, '', false, false, 0, true);
                    }
                } else {
                    $pdf->MultiCell($ancho, $alto, $fila[$clave], 1, $alineaciones[$clave], true, 0, $x, $y, true, 0, false, true, $alto, 'M');
                }
                $x += $ancho;
            }

            $pdf->SetXY(self::PDF_MARGEN, $y + $alto);
            $par = !$par;
        }

        // Cantidad de registros al pie de la tabla
        if ($pdf->GetY() + 8 > $limiteInferior) {
            $pdf->AddPage();
        }
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'I', self::PDF_FUENTE);
        $pdf->SetTextColor(100, 100, 100);
        $total = count($filas);
        $pdf->Cell(0, 5, $total === 1 ? '1 registro' : "$total registros", 0, 1, 'L');
    }


    /**
     * Método para generar reportes desde cualquier controlador
     * @param string $modelo - Nombre del modelo
     * @param string $metodo - Método del modelo para obtener datos
     * @param array $columnas - Columnas a exportar
     * @param string $tipo - 'excel' o 'pdf'
     * @param array $config - Configuraciones adicionales
     */
    public static function generarReporte($modelo, $metodo, $columnas, $tipo, $config = []) {
        // Obtener datos del modelo
        $claseModelo = "\\Models\\$modelo";
        if (!class_exists($claseModelo)) {
            throw new \Exception("Modelo $modelo no encontrado");
        }

        if (!method_exists($claseModelo, $metodo)) {
            throw new \Exception("Método $metodo no encontrado en $modelo");
        }

        $datos = $claseModelo::$metodo();

        // Convertir objetos a arrays
        $datosArray = [];
        foreach ($datos as $item) {
            if (is_object($item)) {
                $datosArray[] = (array)$item;
            } else {
                $datosArray[] = $item;
            }
        }

        $nombreArchivo = strtolower($modelo) . 's_reporte';
        $titulo = 'Reporte de ' . ucfirst($modelo) . 's';

        if ($tipo === 'excel') {
            self::exportarExcel($datosArray, $columnas, $nombreArchivo, $titulo);
        } elseif ($tipo === 'pdf') {
            self::exportarPDF($datosArray, $columnas, $nombreArchivo, $titulo, $config);
        } else {
            throw new \Exception("Tipo de exportación no válido: $tipo");
        }
    }
}
