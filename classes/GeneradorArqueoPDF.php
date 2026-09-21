<?php

namespace Classes;

use TCPDF;

class GeneradorArqueoPDF {
    
    public static function generar($arqueo, $totales) {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('CINFSA');
        $pdf->SetAuthor('Sistema de Gestión CINFSA');
        $pdf->SetTitle('Arqueo de Caja');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        $pdf->AddPage();
        
        $ingresosEfectivo = $totales['ingresos_efectivo'] ?? 0;
        $ingresosTransferencia = $totales['ingresos_transferencia'] ?? 0;
        $egresos = $totales['egresos'] ?? 0;
        
        $montoEsperadoEfectivo = $arqueo['monto_inicial'] + $ingresosEfectivo - $egresos;
        $diferencia = $arqueo['diferencia'] ?? 0;
        
        $html = '
        <style>
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-size: 20px; font-weight: bold; color:  #000;; }
            .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
            .info-box { border: 2px solid  #000; border-radius: 5px; padding: 10px; margin: 15px 0; }
            .info-row { margin: 8px 0; }
            .label { font-weight: bold; color: #333; display: inline-block; width: 180px; }
            .value { color: #000; }
            .section-title { font-size: 16px; font-weight: bold; color: #000; margin: 20px 0 10px 0; border-bottom: 2px solid #ed850f; padding-bottom: 5px; }
            .totales-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .totales-table th { background:  #000; color: white; padding: 8px; text-align: left; }
            .totales-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .total-row { font-weight: bold; font-size: 14px; background: #f5f5f5; }
            .diferencia-positiva { color:  #000; font-weight: bold; }
            .diferencia-negativa { color:  #000; font-weight: bold; }
            .footer-text { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
        </style>
        
        <div class="header">
            <div class="title">CINFSA - SISTEMA DE GESTIÓN</div>
            <div class="subtitle">ARQUEO DE CAJA</div>
        </div>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">Caja:</span>
                <span class="value">' . $arqueo['nombre_caja'] . ' (N° ' . $arqueo['numero_caja'] . ')</span>
            </div>
            <div class="info-row">
                <span class="label">Vendedor:</span>
                <span class="value">' . strtoupper($arqueo['nombre_usuario']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha/Hora Apertura:</span>
                <span class="value">' . date('d/m/Y H:i', strtotime($arqueo['fecha_inicio'])) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha/Hora Cierre:</span>
                <span class="value">' . date('d/m/Y H:i', strtotime($arqueo['fecha_cierre'])) . '</span>
            </div>
        </div>
        
        <div class="section-title">RESUMEN DE MOVIMIENTOS</div>
        
        <table class="totales-table">
            <tr>
                <th>CONCEPTO</th>
                <th style="text-align: right;">MONTO</th>
            </tr>
            <tr>
                <td>Monto Inicial (Efectivo)</td>
                <td style="text-align: right;">$' . number_format($arqueo['monto_inicial'], 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>+ Ingresos en Efectivo</td>
                <td style="text-align: right; color:  #000;">$' . number_format($ingresosEfectivo, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>+ Ingresos en Transferencia</td>
                <td style="text-align: right; color:  #000;">$' . number_format($ingresosTransferencia, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>- Egresos</td>
                <td style="text-align: right; color:  #000;">$' . number_format($egresos, 0, ',', '.') . '</td>
            </tr>
            <tr class="total-row">
                <td>SALDO ESPERADO EN EFECTIVO</td>
                <td style="text-align: right;">$' . number_format($montoEsperadoEfectivo, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>Monto Final Declarado (Efectivo)</td>
                <td style="text-align: right;">$' . number_format($arqueo['monto_final'], 0, ',', '.') . '</td>
            </tr>
            <tr class="total-row">
                <td>DIFERENCIA</td>
                <td style="text-align: right;" class="' . ($diferencia >= 0 ? 'diferencia-positiva' : 'diferencia-negativa') . '">
                    ' . ($diferencia >= 0 ? '+' : '') . '$' . number_format($diferencia, 0, ',', '.') . '
                </td>
            </tr>
        </table>';
        
        if (!empty($arqueo['observaciones_cierre'])) {
            $html .= '
            <div class="section-title">OBSERVACIONES</div>
            <div style="padding: 10px; background: #f5f5f5; border-radius: 5px;">
                ' . nl2br(htmlspecialchars($arqueo['observaciones_cierre'])) . '
            </div>';
        }
        
        $html .= '
        <div class="footer-text">
            Documento generado el ' . date('d/m/Y H:i:s') . '<br>
            CINFSA - Sistema de Gestión de Cine
        </div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $nombreArchivo = 'Arqueo_Caja_' . $arqueo['numero_caja'] . '_' . date('Ymd_His', strtotime($arqueo['fecha_cierre'])) . '.pdf';
        $pdf->Output($nombreArchivo, 'D');
        exit;
    }
}