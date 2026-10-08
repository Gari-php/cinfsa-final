<?php
namespace Classes;

use ZipArchive;

/**
 * Genera un .xlsx real (Office Open XML) con una hoja: título, subtítulo, encabezados y filas.
 * Sin dependencias: arma los XML y los comprime con ZipArchive.
 *
 * - Ancho de cada columna según su contenido (entre ANCHO_MIN y ANCHO_MAX caracteres).
 * - Encabezados en negrita con fondo, fijos al hacer scroll y con filtros.
 * - Textos largos con ajuste de línea; números guardados como números.
 * - Los textos van como texto literal: una celda que empieza con "=" no se ejecuta como fórmula.
 */
class GeneradorXlsx
{
    const ANCHO_MIN = 8;
    const ANCHO_MAX = 60;

    // Índices de estilo (ver estilos())
    const EST_TITULO = 1;
    const EST_SUBTITULO = 2;
    const EST_ENCABEZADO = 3;
    const EST_TEXTO = 4;
    const EST_NUMERO = 5;

    /**
     * Arma el archivo y lo envía como descarga. No vuelve (termina el pedido).
     *
     * @param string $nombreArchivo nombre sin extensión
     * @param string $titulo        título del reporte (primera fila)
     * @param array  $encabezados   títulos de las columnas
     * @param array  $filas         filas de valores, en el mismo orden que los encabezados
     */
    public static function descargar(string $nombreArchivo, string $titulo, array $encabezados, array $filas): void
    {
        $ruta = self::generar($titulo, $encabezados, $filas);

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xlsx"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($ruta);
        unlink($ruta);
        exit;
    }

    /**
     * Arma el .xlsx en un archivo temporal y devuelve su ruta.
     */
    public static function generar(string $titulo, array $encabezados, array $filas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo Excel');
        }

        $zip->addFromString('[Content_Types].xml', self::tiposContenido());
        $zip->addFromString('_rels/.rels', self::relacionesPaquete());
        $zip->addFromString('docProps/core.xml', self::propiedades($titulo));
        $zip->addFromString('xl/workbook.xml', self::libro());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::relacionesLibro());
        $zip->addFromString('xl/styles.xml', self::estilos());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::hoja($titulo, $encabezados, $filas));
        $zip->close();

        return $ruta;
    }

    private static function hoja(string $titulo, array $encabezados, array $filas): string
    {
        $cantColumnas = max(1, count($encabezados));
        $ultimaCol = self::letraColumna($cantColumnas - 1);

        // Anchos según el contenido (en caracteres; la línea más larga de cada celda)
        $anchos = array_map(fn($e) => self::largoTexto($e) + 2, array_values($encabezados));
        foreach ($filas as $fila) {
            foreach (array_values($fila) as $i => $valor) {
                if ($i < $cantColumnas) {
                    $anchos[$i] = max($anchos[$i], self::largoTexto($valor) + 2);
                }
            }
        }
        $cols = '';
        foreach ($anchos as $i => $ancho) {
            $ancho = min(self::ANCHO_MAX, max(self::ANCHO_MIN, $ancho));
            $n = $i + 1;
            $cols .= "<col min=\"$n\" max=\"$n\" width=\"$ancho\" customWidth=\"1\"/>";
        }

        // Fila 1: título; fila 2: fecha de generación; fila 4: encabezados; desde la 5: datos
        $filaEncabezado = 4;
        $xml = '<row r="1" ht="22" customHeight="1">' . self::celda('A1', $titulo, self::EST_TITULO, true) . '</row>';
        $xml .= '<row r="2">' . self::celda('A2', 'Generado el ' . date('d/m/Y H:i'), self::EST_SUBTITULO, true) . '</row>';

        $xml .= "<row r=\"$filaEncabezado\" ht=\"20\" customHeight=\"1\">";
        foreach (array_values($encabezados) as $i => $encabezado) {
            $xml .= self::celda(self::letraColumna($i) . $filaEncabezado, $encabezado, self::EST_ENCABEZADO, true);
        }
        $xml .= '</row>';

        $r = $filaEncabezado;
        foreach ($filas as $fila) {
            $r++;
            $xml .= "<row r=\"$r\">";
            foreach (array_values($fila) as $i => $valor) {
                if ($i < $cantColumnas) {
                    $xml .= self::celda(self::letraColumna($i) . $r, $valor);
                }
            }
            $xml .= '</row>';
        }

        $rangoFiltro = "A$filaEncabezado:$ultimaCol" . max($r, $filaEncabezado);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="' . $filaEncabezado . '" topLeftCell="A' . ($filaEncabezado + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . "<cols>$cols</cols>"
            . "<sheetData>$xml</sheetData>"
            . "<autoFilter ref=\"$rangoFiltro\"/>"
            . '<mergeCells count="2">'
            . "<mergeCell ref=\"A1:{$ultimaCol}1\"/><mergeCell ref=\"A2:{$ultimaCol}2\"/>"
            . '</mergeCells>'
            . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    /**
     * Una celda: número si el valor es numérico "limpio"; si no, texto literal (inlineStr).
     */
    private static function celda(string $ref, $valor, ?int $estilo = null, bool $forzarTexto = false): string
    {
        if ($valor === null || $valor === '') {
            return "<c r=\"$ref\" s=\"" . ($estilo ?? self::EST_TEXTO) . '"/>';
        }

        if (!$forzarTexto && self::esNumero($valor)) {
            return "<c r=\"$ref\" s=\"" . ($estilo ?? self::EST_NUMERO) . "\"><v>" . (0 + $valor) . '</v></c>';
        }

        $texto = self::xml((string)$valor);
        return "<c r=\"$ref\" s=\"" . ($estilo ?? self::EST_TEXTO) . "\" t=\"inlineStr\"><is><t xml:space=\"preserve\">$texto</t></is></c>";
    }

    /**
     * Número "limpio": entero o decimal con punto, sin ceros a la izquierda (los códigos como
     * "00123" o un DNI con cero adelante quedan como texto) y de hasta 15 dígitos.
     */
    private static function esNumero($valor): bool
    {
        if (is_int($valor) || is_float($valor)) {
            return is_finite((float)$valor);
        }
        return is_string($valor)
            && strlen($valor) <= 15
            && preg_match('/^-?(0|[1-9]\d*)(\.\d+)?$/', $valor) === 1;
    }

    private static function largoTexto($valor): int
    {
        $lineas = explode("\n", (string)$valor);
        return max(array_map(fn($l) => mb_strlen($l, 'UTF-8'), $lineas));
    }

    private static function letraColumna(int $indice): string
    {
        $letra = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letra = chr(65 + ($n - 1) % 26) . $letra;
        }
        return $letra;
    }

    private static function xml(string $texto): string
    {
        // Caracteres de control que XML no admite (salvo tab y salto de línea)
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto) ?? '';
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function estilos(): string
    {
        // fuentes: 0 normal, 1 título, 2 encabezado (blanca), 3 subtítulo (gris)
        // rellenos: 0 y 1 obligatorios, 2 encabezado (naranja del sistema)
        // bordes: 0 sin borde, 1 fino gris
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF666666"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFED850F"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD0D0D0"/></left><right style="thin"><color rgb="FFD0D0D0"/></right>'
            . '<top style="thin"><color rgb="FFD0D0D0"/></top><bottom style="thin"><color rgb="FFD0D0D0"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            . '<alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="right" vertical="top"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function tiposContenido(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';
    }

    private static function relacionesPaquete(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>';
    }

    private static function relacionesLibro(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function libro(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function propiedades(string $titulo): string
    {
        $fecha = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::xml($titulo) . '</dc:title><dc:creator>CINFSA</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $fecha . '</dcterms:created>'
            . '</cp:coreProperties>';
    }
}
