<?php

namespace Controllers;

use Models\Turnos;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class TurnoController
{


    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    public static function index(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 4;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM turnos";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        $turnos = Turnos::obtenerTurnos($paginador);

        $router->render('administrador/turnos/listado', [
            'turnos' => $turnos,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/turnos/crear');
    }
    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $turnos = new Turnos($datos);
            $errores = $turnos->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $turnos->crearTurno();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Turno creado correctamente',
                    'redirigir' => '/administrador/turnos/listado'
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar en la base de datos']);
            }
        }
    }
    public static function editar(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/turnos/listado');
            exit;
        }

        $turno = Turnos::find($id);

        if (!$turno) {
            header('Location: /administrador/turnos/listado');
            exit;
        }

        $router->render('administrador/turnos/editar', [
            'turno' => $turno
        ]);
    }

    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            return;
        }

        $id = $datos['id_turnos'] ?? null;
        $turno_horario = $datos['turno_horario'] ?? '';
        $estado = $datos['estado'] ?? 1;

        // Validaciones
        $errores = [];
        if (!$id || !is_numeric($id)) $errores[] = 'ID del turno no válido';
        if (!$turno_horario || $turno_horario <= 0) $errores[] = 'El horario es obligatorio';


        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $turno = Turnos::find($id);

        if (!$turno) {
            echo json_encode(['ok' => false, 'mensaje' => 'Turno no encontrado']);
            return;
        }

        $turno->turno_horario = $turno_horario;
        $turno->estado = $estado;

        $resultado = $turno->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Turno actualizado correctamente',
                'redirigir' => '/administrador/turnos/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar la turno']);
        }
    }
    public static function eliminar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_turnos'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de Turno inválido']);
            return;
        }

        $turno = Turnos::find($id);

        if (!$turno) {
            echo json_encode(['ok' => false, 'mensaje' => 'Turno no encontrada']);
            return;
        }

        $resultado = $turno->eliminarLogico();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Turno dado de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar la turno']);
        }
    }
    public static function buscar()
    {
        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término está vacío']);
            exit;
        }

        try {
            // Necesitas agregar este método al modelo
            $turno = Turnos::buscarTurno($termino);

            if ($turno) {
                echo json_encode(['ok' => true, 'turno' => $turno]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'No se encontró el turno']);
            }
        } catch (\Exception $e) {
            error_log("Error en búsqueda de turnos: " . $e->getMessage());
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor']);
        }

        exit;
    }

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/turnos/listado');
            exit;
        }

        try {
            $columnas = [
                'id_turnos' => 'ID',
                'turno_horario' => 'Horario',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN TURNOS EXCEL: Buscando término: " . $termino);

                $turno = Turnos::buscarTurno($termino);
                if ($turno) {
                    $turno['estado'] = $turno['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$turno];
                    error_log("EXPORTACIÓN TURNOS EXCEL: Turno encontrado");
                } else {
                    error_log("EXPORTACIÓN TURNOS EXCEL: No se encontró turno");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN TURNOS EXCEL: Exportando todos los turnos");
                $turnos = Turnos::obtenerTurnos();
                foreach ($turnos as $t) {
                    $datos[] = [
                        'id_turnos' => $t->id_turnos,
                        'turno_horario' => $t->turno_horario,
                        'estado' => $t->estado == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "turno_filtrado_" . self::limpiarNombreArchivo($termino) : "turnos_completo";
            $titulo = !empty($termino) ? "Turno Filtrado: $termino" : "Listado Completo de Turnos";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN TURNOS EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/turnos/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/turnos/listado');
            exit;
        }

        try {
            $columnas = [
                'id_turnos' => 'ID',
                'turno_horario' => 'Horario',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $turno = Turnos::buscarTurno($termino);
                if ($turno) {
                    $turno['estado'] = $turno['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$turno];
                } else {
                    $datos = [];
                }
            } else {
                $turnos = Turnos::obtenerTurnos();
                foreach ($turnos as $t) {
                    $datos[] = [
                        'id_turnos' => $t->id_turnos,
                        'turno_horario' => $t->turno_horario,
                        'estado' => $t->estado == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "turno_filtrado_" . self::limpiarNombreArchivo($termino) : "turnos_completo";
            $titulo = !empty($termino) ? "Turno Filtrado: $termino" : "Listado Completo de Turnos";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Turnos',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN TURNOS PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/turnos/listado');
            exit;
        }
    }

    public static function exportar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $tipo = $_GET['tipo'] ?? '';

        switch ($tipo) {
            case 'excel':
                self::exportarExcel();
                break;
            case 'pdf':
                self::exportarPDF();
                break;
            default:
                echo json_encode(['ok' => false, 'mensaje' => 'Tipo de exportación no válido']);
                return;
        }
    }

    public static function reportes(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }
        $router->render('administrador/turnos/reportes', []);
    }

    public static function apiDatosReportes()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $turnos = \Classes\GeneradorGraficos::obtenerVentasPorTurno($fechaDesde, $fechaHasta);

        echo json_encode([
            'ok'      => true,
            'turnos'  => $turnos,
            'totales' => [
                'turnos'    => count($turnos),
                'entradas'  => array_sum(array_column($turnos, 'entradas')),
                'recaudacion' => array_sum(array_column($turnos, 'recaudacion'))
            ]
        ]);
    }
    
    private static function limpiarNombreArchivo($nombre)
    {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        return substr($nombre, 0, 20);
    }
}
