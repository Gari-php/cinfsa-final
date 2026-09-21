<?php
namespace Controllers;

use Models\TipoEntrada;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;
class TipoEntradaController {

   
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
        $registrosPorPagina = 3;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM tipo_entradas";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        $tipos_entradas = TipoEntrada::obtenerTodas($paginador);
        
        $router->render('administrador/tipos_entradas/listado', [
            'tipos_entradas' => $tipos_entradas,
            'paginador' => $paginador
        ]);
    }


    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/tipos_entradas/crear');
    }

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $tipos_entradas = new TipoEntrada($datos);
            $errores = $tipos_entradas->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $tipos_entradas->creartipoentrada();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'tipo de entrada creada correctamente',
                    'redirigir' => '/administrador/tipos_entradas/listado'
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
            header('Location: /administrador/salas');
            exit;
        }

        $tipos_entradas = TipoEntrada::find($id);

        if (!$tipos_entradas) {
            header('Location: /administrador/tipos_entradas/listado');
            exit;
        }

        $router->render('administrador/tipos_entradas/editar', [
            'tipos_entradas' => $tipos_entradas
        ]);
    }
    public static function actualizar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            exit;
        }

        // Buscar el tipo de entrada
        $tipos_entradas = TipoEntrada::find($datos['id_tipo_entrada']);

        if (!$tipos_entradas) {
            echo json_encode(['ok' => false, 'mensaje' => 'Tipo de Entrada no encontrado']);
            exit;
        }

        // Actualizar propiedades
        $tipos_entradas->tipo_entrada_desc = $datos['tipo_entrada_desc'] ?? '';
        $tipos_entradas->precio_entrada = $datos['precio_entrada'] ?? '';
        $tipos_entradas->estado = $datos['estado'] ?? 1;

        // Validar
        $errores = $tipos_entradas->validar();

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            exit;
        }

        // Actualizar en la base de datos
        $resultado = $tipos_entradas->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true, 
                'mensaje' => 'Tipo de entrada actualizado correctamente', 
                'redirigir' => '/administrador/tipos_entradas/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el tipo de entrada']);
        }
    }
    public static function eliminar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_tipo_entrada'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID del tipo de entrada inválido']);
            return;
        }

        $tipos_entradas = TipoEntrada::find($id);

        if (!$tipos_entradas) {
            echo json_encode(['ok' => false, 'mensaje' => 'tipo de entrada no encontrada']);
            return;
        }

        $resultado = $tipos_entradas->darDeBaja();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'tipo de entrada desactivada correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar el tipo de entrada']);
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

        $tipos_entradas = TipoEntrada::buscartipoentrada($termino);

        if ($tipos_entradas) {
            echo json_encode(['ok' => true, 'tipos_entradas' => $tipos_entradas]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró el tipo entrada']);
        }
    }
    public static function exportarExcel() {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/tipos_entradas/listado');
            exit;
        }

        try {
            $columnas = [
                'id_tipo_entrada' => 'ID',
                'tipo_entrada_desc' => 'Descripción',
                'precio_entrada' => 'Precio',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN TIPOS ENTRADA EXCEL: Buscando término: " . $termino);
                
                $tipoEntrada = TipoEntrada::buscartipoentrada($termino);
                if ($tipoEntrada) {
                    $tipoEntrada['estado'] = $tipoEntrada['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$tipoEntrada];
                    error_log("EXPORTACIÓN TIPOS ENTRADA EXCEL: Tipo de entrada encontrado");
                } else {
                    error_log("EXPORTACIÓN TIPOS ENTRADA EXCEL: No se encontró tipo de entrada");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN TIPOS ENTRADA EXCEL: Exportando todos los tipos");
                $tiposEntrada = TipoEntrada::obtenerTodas();
                foreach ($tiposEntrada as $te) {
                    $datos[] = [
                        'id_tipo_entrada' => $te->id_tipo_entrada,
                        'tipo_entrada_desc' => $te->tipo_entrada_desc,
                        'precio_entrada' => $te->precio_entrada,
                        'estado' => $te->estado == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "tipo_entrada_filtrado_" . self::limpiarNombreArchivo($termino) : "tipos_entrada_completo";
            $titulo = !empty($termino) ? "Tipo Entrada Filtrado: $termino" : "Listado Completo de Tipos de Entrada";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
            
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN TIPOS ENTRADA EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/tipos_entradas/listado');
            exit;
        }
    }

    public static function exportarPDF() {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/tipos_entradas/listado');
            exit;
        }

        try {
            $columnas = [
                'id_tipo_entrada' => 'ID',
                'tipo_entrada_desc' => 'Descripción',
                'precio_entrada' => 'Precio',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $tipoEntrada = TipoEntrada::buscartipoentrada($termino);
                if ($tipoEntrada) {
                    $tipoEntrada['estado'] = $tipoEntrada['estado'] == 1 ? 'Activo' : 'Inactivo';
                    $datos = [$tipoEntrada];
                } else {
                    $datos = [];
                }
            } else {
                $tiposEntrada = TipoEntrada::obtenerTodas();
                foreach ($tiposEntrada as $te) {
                    $datos[] = [
                        'id_tipo_entrada' => $te->id_tipo_entrada,
                        'tipo_entrada_desc' => $te->tipo_entrada_desc,
                        'precio_entrada' => $te->precio_entrada,
                        'estado' => $te->estado == 1 ? 'Activo' : 'Inactivo'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "tipo_entrada_filtrado_" . self::limpiarNombreArchivo($termino) : "tipos_entrada_completo";
            $titulo = !empty($termino) ? "Tipo Entrada Filtrado: $termino" : "Listado Completo de Tipos de Entrada";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Tipos de Entrada',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
            
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN TIPOS ENTRADA PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/tipos_entradas/listado');
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
