<?php

namespace Controllers;

use Classes\Auditoria;
use Classes\Paginador;
use Models\ActiveRecord;
use MVC\Router;

/**
 * Control → Registro de auditoría. Solo lectura: desde el sistema nadie puede editar ni borrar registros.
 */
class AuditoriaController
{
    const POR_PAGINA = 25;
    const MAXIMO_EXPORTACION = 50000;
    const SIN_USUARIO = '__ninguno__';

    private static function verificarAdmin(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    /**
     * Lee los filtros de la URL y arma el WHERE con parámetros (nunca concatena texto del usuario).
     * @return array [$filtros, $where, $tipos, $valores]
     */
    private static function filtros(): array
    {
        $fecha = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : '';
        $filtros = [
            'usuario' => trim($_GET['usuario'] ?? ''),
            'accion' => array_key_exists($_GET['accion'] ?? '', Auditoria::ACCIONES) ? $_GET['accion'] : '',
            'fecha_desde' => $fecha($_GET['fecha_desde'] ?? ''),
            'fecha_hasta' => $fecha($_GET['fecha_hasta'] ?? ''),
            'busqueda' => mb_substr(trim($_GET['busqueda'] ?? ''), 0, 100),
        ];

        $condiciones = [];
        $tipos = '';
        $valores = [];

        if ($filtros['usuario'] === self::SIN_USUARIO) {
            $condiciones[] = 'nombre_usuario IS NULL';
        } elseif ($filtros['usuario'] !== '') {
            $condiciones[] = 'nombre_usuario = ?';
            $tipos .= 's';
            $valores[] = $filtros['usuario'];
        }
        if ($filtros['accion'] !== '') {
            $condiciones[] = 'accion = ?';
            $tipos .= 's';
            $valores[] = $filtros['accion'];
        }
        if ($filtros['fecha_desde'] !== '') {
            $condiciones[] = 'fecha >= ?';
            $tipos .= 's';
            $valores[] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if ($filtros['fecha_hasta'] !== '') {
            $condiciones[] = 'fecha <= ?';
            $tipos .= 's';
            $valores[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        if ($filtros['busqueda'] !== '') {
            $condiciones[] = 'descripcion LIKE ?';
            $tipos .= 's';
            $valores[] = '%' . $filtros['busqueda'] . '%';
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        return [$filtros, $where, $tipos, $valores];
    }

    private static function consultar(string $sql, string $tipos, array $valores): \mysqli_result
    {
        $stmt = ActiveRecord::getDB()->prepare($sql);
        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$valores);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public static function listado(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        [$filtros, $where, $tipos, $valores] = self::filtros();

        $paginador = new Paginador($_GET['pagina'] ?? 1, self::POR_PAGINA);
        $total = self::consultar("SELECT COUNT(*) FROM auditoria $where", $tipos, $valores)->fetch_column();
        $paginador->setTotal($total);

        $registros = self::consultar(
            "SELECT * FROM auditoria $where ORDER BY fecha DESC, id_auditoria DESC " . $paginador->limit(),
            $tipos,
            $valores
        )->fetch_all(MYSQLI_ASSOC);

        // Usuarios que aparecen en el registro (para el filtro)
        $usuarios = ActiveRecord::getDB()
            ->query("SELECT DISTINCT nombre_usuario FROM auditoria WHERE nombre_usuario IS NOT NULL ORDER BY nombre_usuario")
            ->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/auditoria/listado', [
            'registros' => $registros,
            'paginador' => $paginador,
            'filtros' => $filtros,
            'usuarios' => array_column($usuarios, 'nombre_usuario'),
            'acciones' => Auditoria::ACCIONES,
            'destacadas' => Auditoria::ACCIONES_DESTACADAS,
            'sinUsuario' => self::SIN_USUARIO,
        ]);
    }

    // Exporta a CSV (abre en Excel) lo que coincide con los filtros actuales
    public static function exportar()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        [, $where, $tipos, $valores] = self::filtros();

        $filas = self::consultar(
            "SELECT * FROM auditoria $where ORDER BY fecha DESC, id_auditoria DESC LIMIT " . self::MAXIMO_EXPORTACION,
            $tipos,
            $valores
        )->fetch_all(MYSQLI_ASSOC);

        $datos = array_map(fn($r) => [
            'fecha' => date('d/m/Y H:i:s', strtotime($r['fecha'])),
            'usuario' => $r['nombre_usuario'] ?? '—',
            'perfil' => $r['perfil'] ?? '',
            'accion' => Auditoria::nombreAccion($r['accion']),
            'descripcion' => $r['descripcion'],
            'antes' => $r['datos_antes'] ?? '',
            'despues' => $r['datos_despues'] ?? '',
            'ip' => $r['ip'] ?? '',
        ], $filas);

        self::enviarCsv($datos, [
            'fecha' => 'Fecha',
            'usuario' => 'Usuario',
            'perfil' => 'Perfil',
            'accion' => 'Acción',
            'descripcion' => 'Detalle',
            'antes' => 'Antes',
            'despues' => 'Después',
            'ip' => 'IP',
        ]);
    }

    /**
     * CSV propio (mismo formato que ExportadorDatos: BOM UTF-8 y ";" para que Excel lo abra bien), pero:
     *  - sin recortar textos: en una auditoría el detalle completo importa;
     *  - neutraliza celdas que empiezan con = + - @ para que Excel no las ejecute como fórmulas.
     */
    private static function enviarCsv(array $datos, array $columnas): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Registro de auditoría - Generado el ' . date('d/m/Y H:i:s')], ';');
        fputcsv($salida, [''], ';');
        fputcsv($salida, array_values($columnas), ';');

        foreach ($datos as $fila) {
            $celdas = [];
            foreach (array_keys($columnas) as $clave) {
                $valor = (string)($fila[$clave] ?? '');
                $celdas[] = preg_match('/^[=+\-@\t\r]/', $valor) ? "'" . $valor : $valor;
            }
            fputcsv($salida, $celdas, ';');
        }

        fclose($salida);
        exit;
    }
}
