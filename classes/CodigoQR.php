<?php

namespace Classes;

/**
 * Genera imágenes QR (PNG) con TCPDF + GD.
 *
 * TCPDF solo calcula la matriz del código; la imagen se dibuja acá con fondo blanco opaco y
 * margen ("zona de silencio") de 4 módulos. El PNG que arma TCPDF trae fondo transparente y sin
 * margen, y en un mail o una pantalla con modo oscuro eso lo vuelve imposible de escanear.
 */
class CodigoQR
{
    // Prefijo del texto de cada tipo de QR, para que el escáner sepa qué está leyendo
    const PREFIJO_ENTRADA = 'CINFSA-E-';
    const PREFIJO_ORDEN = 'CINFSA-O-';   // retiro de productos y fichas de una compra web

    // Texto que lleva el QR de una entrada a partir de su codigo_acceso
    public static function textoEntrada(string $codigoAcceso): string
    {
        return self::PREFIJO_ENTRADA . $codigoAcceso;
    }

    // Texto que lleva el QR de retiro de una orden a partir de su codigo_retiro
    public static function textoOrden(string $codigoRetiro): string
    {
        return self::PREFIJO_ORDEN . $codigoRetiro;
    }

    /**
     * PNG del QR como string binario.
     * @param int $modulo  píxeles por cada cuadradito del QR
     * @param int $margen  módulos de borde blanco alrededor (el estándar pide 4)
     */
    public static function png(string $texto, int $modulo = 6, int $margen = 4): string
    {
        $matriz = (new \TCPDF2DBarcode($texto, 'QRCODE,M'))->getBarcodeArray();
        $filas = $matriz['num_rows'];
        $columnas = $matriz['num_cols'];

        $ancho = ($columnas + 2 * $margen) * $modulo;
        $alto = ($filas + 2 * $margen) * $modulo;

        $imagen = imagecreate($ancho, $alto);
        imagecolorallocate($imagen, 255, 255, 255); // el primer color asignado es el fondo
        $negro = imagecolorallocate($imagen, 0, 0, 0);

        for ($f = 0; $f < $filas; $f++) {
            for ($c = 0; $c < $columnas; $c++) {
                if ($matriz['bcode'][$f][$c]) {
                    $x = ($c + $margen) * $modulo;
                    $y = ($f + $margen) * $modulo;
                    imagefilledrectangle($imagen, $x, $y, $x + $modulo - 1, $y + $modulo - 1, $negro);
                }
            }
        }

        ob_start();
        imagepng($imagen);
        $png = ob_get_clean();
        imagedestroy($imagen);

        return $png;
    }

    // El PNG listo para usar en un <img src="...">
    public static function dataUri(string $texto, int $modulo = 6, int $margen = 4): string
    {
        return 'data:image/png;base64,' . base64_encode(self::png($texto, $modulo, $margen));
    }
}
