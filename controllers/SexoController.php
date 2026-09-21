<?php
namespace Controllers;

use Models\Sexo;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;
class SexoController {

    private static function verificarAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    public static function index(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        
        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 4;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        
        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM sexo";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener sexos con LIMIT
        $sexos = Sexo::obtenerTodos($paginador);
        
        $router->render('administrador/sexo/listado', [
            'sexos' => $sexos,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }
        $router->render('administrador/sexo/crear');
    }

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $sexo = new Sexo($datos);
            $errores = $sexo->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $sexo->crearSexo();
            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Sexo creado correctamente',
                    'redirigir' => '/administrador/sexo/listado'
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar en la base de datos']);
            }
        }
    }

    public static function editar(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/sexo');
            exit;
        }
        $sexo = Sexo::find($id);
        if (!$sexo) {
            header('Location: /administrador/sexo');
            exit;
        }
        $router->render('administrador/sexo/editar', [
            'sexo' => $sexo
        ]);
    }

    public static function actualizar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json'); // Agregar esta línea

        $datos = json_decode(file_get_contents('php://input'), true);
        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            exit; // Cambiar return por exit
        }

        // Buscar el sexo a actualizar
        $sexo = Sexo::find($datos['id_sexo']);
        if (!$sexo) {
            echo json_encode(['ok' => false, 'mensaje' => 'Sexo no encontrado']);
            exit;
        }

        // Actualizar las propiedades del objeto
        $sexo->nombre_sexo = $datos['nombre_sexo'] ?? '';
        $sexo->estado = $datos['estado'] ?? 1;

        // AHORA SÍ validar el objeto
        $errores = $sexo->validar();

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            exit;
        }

        // Actualizar en la base de datos
        $resultado = $sexo->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Sexo actualizado correctamente',
                'redirigir' => '/administrador/sexo/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar']);
        }
    }

    public static function buscar() {
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
        $sexo = Sexo::buscarSexo($termino);
        if ($sexo) {
            echo json_encode(['ok' => true, 'sexo' => $sexo]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró el sexo']);
        }
    }
    public static function exportarExcel() {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/sexo/listado');
            exit;
        }

        try {
            $columnas = [
                'id_sexo' => 'ID',
                'nombre_sexo' => 'Nombre',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN SEXO EXCEL: Buscando término: " . $termino);
                
                $sexo = Sexo::buscarSexo($termino);
                if ($sexo) {
                    $sexo['estado'] = $sexo['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$sexo];
                    error_log("EXPORTACIÓN SEXO EXCEL: Registro encontrado");
                } else {
                    error_log("EXPORTACIÓN SEXO EXCEL: No se encontró registro");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN SEXO EXCEL: Exportando todos los registros");
                $sexos = Sexo::obtenerTodos();
                foreach ($sexos as $s) {
                    $datos[] = [
                        'id_sexo' => $s->id_sexo,
                        'nombre_sexo' => $s->nombre_sexo,
                        'estado' => $s->estado == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "sexo_filtrado_" . self::limpiarNombreArchivo($termino) : "sexo_completo";
            $titulo = !empty($termino) ? "Sexo Filtrado: $termino" : "Listado Completo de Sexo";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
            
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN SEXO EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/sexo/listado');
            exit;
        }
    }

    public static function exportarPDF() {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/sexo/listado');
            exit;
        }

        try {
            $columnas = [
                'id_sexo' => 'ID',
                'nombre_sexo' => 'Nombre',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $sexo = Sexo::buscarSexo($termino);
                if ($sexo) {
                    $sexo['estado'] = $sexo['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$sexo];
                }
            } else {
                $sexos = Sexo::obtenerTodos();
                foreach ($sexos as $s) {
                    $datos[] = [
                        'id_sexo' => $s->id_sexo,
                        'nombre_sexo' => $s->nombre_sexo,
                        'estado' => $s->estado == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "sexo_filtrado_" . self::limpiarNombreArchivo($termino) : "sexo_completo";
            $titulo = !empty($termino) ? "Sexo Filtrado: $termino" : "Listado Completo de Sexo";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
            
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN SEXO PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/sexo/listado');
            exit;
        }
    }

    public static function exportar() {
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

    // MÉTODO AUXILIAR COMÚN
    private static function limpiarNombreArchivo($nombre) {
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        return substr($nombre, 0, 20);
    }
}
