<?php

namespace Controllers;

use Models\Sala;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class SalaController
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
        $queryCount = "SELECT COUNT(*) as total FROM salas";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);


        $salas = Sala::obtenerTodas($paginador);

        $router->render('administrador/salas/listado', [
            'salas' => $salas,
            'paginador' => $paginador
        ]);
    }


    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/salas/crear');
    }


    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $sala = new Sala($datos);
            $errores = $sala->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $sala->crearSala();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Sala creada correctamente',
                    'redirigir' => '/administrador/salas/listado'
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
            header('Location: /administrador/salas/listado');
            exit;
        }

        $sala = Sala::find($id);

        if (!$sala) {
            header('Location: /administrador/salas/listado');
            exit;
        }

        $router->render('administrador/salas/editar', [
            'sala' => $sala
        ]);
    }

    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        $id = $datos['id_sala'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de sala inválido']);
            return;
        }

        $sala = Sala::find($id);

        if (!$sala) {
            echo json_encode(['ok' => false, 'mensaje' => 'Sala no encontrada']);
            return;
        }

        // Actualizar propiedades con los datos recibidos
        $sala->capacidad_sala = $datos['capacidad_sala'] ?? $sala->capacidad_sala;
        $sala->filas_sala = $datos['filas_sala'] ?? $sala->filas_sala;
        $sala->columnas_sala = $datos['columnas_sala'] ?? $sala->columnas_sala;
        $sala->estado = $datos['estado'] ?? $sala->estado;

        $errores = $sala->validar();

        if (!empty($errores)) {
            echo json_encode(['ok' => false, 'errores' => $errores]);
            return;
        }

        $resultado = $sala->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Sala actualizada correctamente',
                'redirigir' => '/administrador/salas/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar en la base de datos']);
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

        $id = $datos['id_sala'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de sala inválido']);
            return;
        }

        $sala = Sala::find($id);

        if (!$sala) {
            echo json_encode(['ok' => false, 'mensaje' => 'Sala no encontrada']);
            return;
        }

        $resultado = $sala->darDeBaja();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Sala desactivada correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar la sala']);
        }
    }


    public static function buscar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $termino = trim($datos['termino'] ?? '');

        if (!$termino) {
            echo json_encode(['ok' => false, 'mensaje' => 'El término está vacío']);
            return;
        }

        $sala = Sala::buscarSala($termino);

        if ($sala) {
            echo json_encode(['ok' => true, 'sala' => $sala]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la sala']);
        }
    }


    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/salas/listado');
            exit;
        }

        try {
            $columnas = [
                'id_sala' => 'ID',
                'capacidad_sala' => 'Capacidad',
                'filas_sala' => 'Filas',
                'columnas_sala' => 'Columnas',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN SALAS EXCEL: Buscando término: " . $termino);

                $sala = Sala::buscarSala($termino);
                if ($sala) {
                    // Convertir estado numérico a texto
                    $sala['estado'] = $sala['estado'] == 1 ? 'Activa' : 'Inactiva';
                    $datos = [$sala];
                    error_log("EXPORTACIÓN SALAS EXCEL: Sala encontrada");
                } else {
                    error_log("EXPORTACIÓN SALAS EXCEL: No se encontró sala");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN SALAS EXCEL: Exportando todas las salas");
                $salas = Sala::obtenerTodas();
                foreach ($salas as $s) {
                    $datos[] = [
                        'id_sala' => $s->id_sala,
                        'capacidad_sala' => $s->capacidad_sala,
                        'filas_sala' => $s->filas_sala,
                        'columnas_sala' => $s->columnas_sala,
                        'estado' => $s->estado == 1 ? 'Activa' : 'Inactiva'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "sala_filtrada_" . self::limpiarNombreArchivo($termino) : "salas_completo";
            $titulo = !empty($termino) ? "Sala Filtrada: $termino" : "Listado Completo de Salas";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN SALAS EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/salas/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/salas/listado');
            exit;
        }

        try {
            $columnas = [
                'id_sala' => 'ID',
                'capacidad_sala' => 'Capacidad',
                'filas_sala' => 'Filas',
                'columnas_sala' => 'Columnas',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $sala = Sala::buscarSala($termino);
                if ($sala) {
                    $sala['estado'] = $sala['estado'] == 1 ? 'Activa' : 'Inactiva';
                    $datos = [$sala];
                } else {
                    $datos = [];
                }
            } else {
                $salas = Sala::obtenerTodas();
                foreach ($salas as $s) {
                    $datos[] = [
                        'id_sala' => $s->id_sala,
                        'capacidad_sala' => $s->capacidad_sala,
                        'filas_sala' => $s->filas_sala,
                        'columnas_sala' => $s->columnas_sala,
                        'estado' => $s->estado == 1 ? 'Activa' : 'Inactiva'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "sala_filtrada_" . self::limpiarNombreArchivo($termino) : "salas_completo";
            $titulo = !empty($termino) ? "Sala Filtrada: $termino" : "Listado Completo de Salas";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Salas',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN SALAS PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/salas/listado');
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
    private static function limpiarNombreArchivo($nombre)
    {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        return substr($nombre, 0, 20);
    }

    public static function reportes(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }
        $router->render('administrador/salas/reportes', []);
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

        $salas = \Classes\GeneradorGraficos::obtenerOcupacionSalas($fechaDesde, $fechaHasta);

        $totalEntradas   = array_sum(array_column($salas, 'entradas_vendidas'));
        $totalFunciones  = array_sum(array_column($salas, 'funciones'));
        $totalCapacidad  = array_sum(array_column($salas, 'capacidad'));

        echo json_encode([
            'ok'      => true,
            'salas'   => $salas,
            'totales' => [
                'salas'     => count($salas),
                'capacidad' => $totalCapacidad,
                'funciones' => $totalFunciones,
                'entradas'  => $totalEntradas
            ]
        ]);
    }
}
