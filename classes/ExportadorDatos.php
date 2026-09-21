<?php
namespace Classes;

use TCPDF;


class ExportadorDatos {
    
    /**
     * Exportar datos a Excel (CSV)
     * @param array $datos - Array de datos a exportar
     * @param array $columnas - Array con las columnas ['clave' => 'Título', ...]
     * @param string $nombreArchivo - Nombre del archivo sin extensión
     * @param string $titulo - Título del reporte
     */
    public static function exportarExcel($datos, $columnas, $nombreArchivo = 'reporte', $titulo = 'Reporte') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
       
        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir título
        fputcsv($output, [$titulo . ' - Generado el ' . date('d/m/Y H:i:s')], ';');
        fputcsv($output, [''], ';'); // Línea vacía
        
        //  encabezados
        $encabezados = array_values($columnas);
        fputcsv($output, $encabezados, ';');
        
        //datos
        foreach ($datos as $fila) {
            $filaExportar = [];
            foreach (array_keys($columnas) as $clave) {
                $valor = $fila[$clave] ?? '';
                
                // Procesar valores especiales
                $valor = self::procesarValorExcel($valor);
                $filaExportar[] = $valor;
            }
            fputcsv($output, $filaExportar, ';');
        }
        
        fclose($output);
        exit;
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
        
        // Configurar encabezado y pie
        $pdf->SetHeaderData('', 0, $configuracion['empresa'], $titulo . "\nGenerado el: " . date('d/m/Y H:i:s'));
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);
        
        $anchoTotal = $pdf->getPageWidth() - 30;
        $numColumnas = count($columnas);
        $anchoColumna = $anchoTotal / $numColumnas;
        
        if ($configuracion['mostrar_imagenes'] && isset($columnas[$configuracion['columna_imagen']])) {
            $anchoImagenCol = $configuracion['ancho_imagen'] + 5;
            $anchoColumna = ($anchoTotal - $anchoImagenCol) / ($numColumnas - 1);
        }
        
        self::crearTablaPDF($pdf, $datos, $columnas, $anchoColumna, $configuracion);
        
        $pdf->Output($nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
        exit;
    }
    
    
    private static function procesarValorExcel($valor) {
        // Si es un objeto, convertir a string
        if (is_object($valor)) {
            $valor = (string)$valor;
        }
        
        // Limpiar HTML
        $valor = strip_tags($valor);
        
        // Limitar longitud para sinopsis largas
        if (strlen($valor) > 100) {
            $valor = substr($valor, 0, 97) . '...';
        }
        
        return $valor;
    }
    
    
  
    private static function crearTablaPDF($pdf, $datos, $columnas, $anchoColumna, $config) {
        // Encabezados de tabla
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        
        foreach ($columnas as $clave => $titulo) {
            $ancho = ($clave === $config['columna_imagen'] && $config['mostrar_imagenes']) 
                    ? $config['ancho_imagen'] + 5 
                    : $anchoColumna;
            $pdf->Cell($ancho, 8, $titulo, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Datos de la tabla
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(245, 245, 245);
        
        $fill = false;
        foreach ($datos as $fila) {
            $alturaFila = 8;
            
            //imagen, calcular altura necesaria
            if ($config['mostrar_imagenes'] && isset($fila[$config['columna_imagen']]) && !empty($fila[$config['columna_imagen']])) {
                $alturaFila = $config['alto_imagen'];
            }
            
            // Verificar si hay espacio en la página
            if ($pdf->GetY() + $alturaFila > $pdf->getPageHeight() - 20) {
                $pdf->AddPage();
                
                // Repetir encabezados
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(200, 200, 200);
                foreach ($columnas as $clave => $titulo) {
                    $ancho = ($clave === $config['columna_imagen'] && $config['mostrar_imagenes']) 
                            ? $config['ancho_imagen'] + 5 
                            : $anchoColumna;
                    $pdf->Cell($ancho, 8, $titulo, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 8);
            }
            
            $posY = $pdf->GetY();
            
            foreach ($columnas as $clave => $titulo) {
                $ancho = ($clave === $config['columna_imagen'] && $config['mostrar_imagenes']) 
                        ? $config['ancho_imagen'] + 5 
                        : $anchoColumna;
                
                if ($clave === $config['columna_imagen'] && $config['mostrar_imagenes'] && !empty($fila[$clave])) {
                    // Dibujar celda para imagen
                    $pdf->Cell($ancho, $alturaFila, '', 1, 0, 'C', $fill);
                    
                    // Insertar imagen si existe
                    $rutaImagen = __DIR__ . '/../public' . $config['ruta_imagenes'] . $fila[$clave];
                    if (file_exists($rutaImagen)) {
                        $pdf->Image($rutaImagen, $pdf->GetX() - $ancho + 2, $posY + 2, $config['ancho_imagen'], $config['alto_imagen'] - 4);
                    }
                } else {
                    $valor = $fila[$clave] ?? '';
                    $valor = strip_tags($valor);
                    
                    // Limitar texto largo
                    if (strlen($valor) > 50) {
                        $valor = substr($valor, 0, 47) . '...';
                    }
                    
                    $pdf->Cell($ancho, $alturaFila, $valor, 1, 0, 'C', $fill);
                }
            }
            
            $pdf->Ln();
            $fill = !$fill;
        }
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