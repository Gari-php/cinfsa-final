<?php

namespace Controllers;

use Models\Maquina;
use Models\Ficha;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class MaquinaController
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
        $registrosPorPagina = 3;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        $db = \Models\ActiveRecord::getDB();
        $queryCount = "SELECT COUNT(*) as total FROM maquinas";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        $maquinas = Maquina::obtenerTodas($paginador);

        $router->render('administrador/maquinas/listado', [
            'maquinas' => $maquinas,
            'paginador' => $paginador
        ]);
    }


    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // Obtener todas las fichas para el select
        $fichas = Ficha::obtenerTodas();

        $router->render('administrador/maquinas/crear', [
            'fichas' => $fichas
        ]);
    }


    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesar imagen
            $nombreImagenFinal = '';
            if (!empty($_FILES['imagen_maquina']['name'])) {
                $carpeta = __DIR__ . '/../public/assets/img/cantina/';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }

                $extension = pathinfo($_FILES['imagen_maquina']['name'], PATHINFO_EXTENSION);
                $nombreImagenFinal = 'maquina_' . time() . '.' . $extension;

                if (!move_uploaded_file($_FILES['imagen_maquina']['tmp_name'], $carpeta . $nombreImagenFinal)) {
                    echo json_encode(['ok' => false, 'mensaje' => 'Error al subir la imagen']);
                    return;
                }
            }

            $datos = [
                'maquinas_nombre' => $_POST['maquinas_nombre'] ?? '',
                'maquina_descripcion' => $_POST['maquina_descripcion'] ?? '',
                'imagen_maquina' => $nombreImagenFinal,
                'estado' => $_POST['estado'] ?? 1,
                'rela_fichas' => $_POST['rela_fichas'] ?? 1  // NUEVO
            ];

            $maquina = new Maquina($datos);
            $errores = $maquina->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $maquina->crearMaquina();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Máquina creada correctamente',
                    'redirigir' => '/administrador/maquinas/listado'
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
            header('Location: /administrador/maquinas');
            exit;
        }

        $maquina = Maquina::find($id);

        if (!$maquina) {
            header('Location: /administrador/maquinas');
            exit;
        }

        // Obtener todas las fichas para el select
        $fichas = Ficha::obtenerTodas();

        $router->render('administrador/maquinas/editar', [
            'maquina' => $maquina,
            'fichas' => $fichas  // NUEVO
        ]);
    }


    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maquina = Maquina::find($_POST['id_maquinas']);

            if (!$maquina) {
                echo json_encode(['ok' => false, 'mensaje' => 'Máquina no encontrada']);
                exit;
            }

            $maquina->maquinas_nombre = $_POST['maquinas_nombre'] ?? '';
            $maquina->maquina_descripcion = $_POST['maquina_descripcion'] ?? '';
            $maquina->rela_fichas = $_POST['rela_fichas'] ?? 1;
            $maquina->estado = $_POST['estado'] ?? 1;

            $errores = $maquina->validar();

            $nombreImagenFinal = $maquina->imagen_maquina;
            if (!empty($_FILES['imagen_maquina']['name'])) {
                $carpeta = __DIR__ . '/../public/assets/img/cantina/';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }

                $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $extension = strtolower(pathinfo($_FILES['imagen_maquina']['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $tiposPermitidos)) {
                    $errores[] = 'Solo se permiten archivos JPG, PNG, GIF o WEBP';
                } elseif ($_FILES['imagen_maquina']['size'] > 5242880) {
                    $errores[] = 'El archivo de imagen no puede exceder 5MB';
                } else {
                    $nombreImagenFinal = 'maquina_' . time() . '.' . $extension;
                    if (!move_uploaded_file($_FILES['imagen_maquina']['tmp_name'], $carpeta . $nombreImagenFinal)) {
                        $errores[] = 'Error al subir la imagen';
                    }
                }
            }

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                exit;
            }

            if (!empty($_FILES['imagen_maquina']['name'])) {
                if ($maquina->imagen_maquina && file_exists($carpeta . $maquina->imagen_maquina)) {
                    unlink($carpeta . $maquina->imagen_maquina);
                }
                $maquina->imagen_maquina = $nombreImagenFinal;
            }

            $resultado = $maquina->actualizar();

            if ($resultado) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Máquina actualizada correctamente',
                    'redirigir' => '/administrador/maquinas/listado'
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar la máquina']);
            }
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

        $id = $datos['id_maquinas'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de máquina inválido']);
            return;
        }

        $maquina = Maquina::find($id);

        if (!$maquina) {
            echo json_encode(['ok' => false, 'mensaje' => 'Máquina no encontrada']);
            return;
        }

        $resultado = $maquina->darDeBaja();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Máquina desactivada correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar la máquina']);
        }
    }

    public static function buscar()
    {
        header('Content-Type: application/json');

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

        $maquinas = Maquina::buscarMaquina($termino);

        echo json_encode(['ok' => true, 'maquinas' => $maquinas]);
    }

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/maquinas/listado');
            exit;
        }

        try {
            $columnas = [
                'id_maquinas' => 'ID',
                'maquinas_nombre' => 'Nombre',
                'maquina_descripcion' => 'Descripción',
                'precio_ficha' => 'Precio Ficha',
                'cantidad_ficha' => 'Stock Fichas',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                error_log("EXPORTACIÓN MÁQUINAS EXCEL: Buscando término: " . $termino);

                $maquina = Maquina::buscarMaquina($termino);
                if ($maquina) {
                    $maquina['estado'] = $maquina['estado'] == 1 ? 'Activa' : 'Inactiva';
                    $datos = [$maquina];
                    error_log("EXPORTACIÓN MÁQUINAS EXCEL: Máquina encontrada");
                } else {
                    error_log("EXPORTACIÓN MÁQUINAS EXCEL: No se encontró máquina");
                    $datos = [];
                }
            } else {
                error_log("EXPORTACIÓN MÁQUINAS EXCEL: Exportando todas las máquinas");
                $maquinas = Maquina::obtenerTodas();
                foreach ($maquinas as $m) {
                    $datos[] = [
                        'id_maquinas' => $m->id_maquinas,
                        'maquinas_nombre' => $m->maquinas_nombre,
                        'maquina_descripcion' => $m->maquina_descripcion,
                        'precio_ficha' => $m->precio_ficha ?? 0,
                        'cantidad_ficha' => $m->cantidad_ficha ?? 0,
                        'estado' => $m->estado == 1 ? 'Activa' : 'Inactiva'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "maquina_filtrada_" . self::limpiarNombreArchivo($termino) : "maquinas_completo";
            $titulo = !empty($termino) ? "Máquina Filtrada: $termino" : "Listado Completo de Máquinas";

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN MÁQUINAS EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/maquinas/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /administrador/maquinas/listado');
            exit;
        }

        try {
            $columnas = [
                'id_maquinas' => 'ID',
                'maquinas_nombre' => 'Nombre',
                'imagen_maquina' => 'Imagen',
                'maquina_descripcion' => 'Descripción',
                'precio_ficha' => 'Precio Ficha',
                'cantidad_ficha' => 'Stock Fichas',
                'estado' => 'Estado'
            ];

            $termino = $_GET['busqueda'] ?? '';
            $datos = [];

            if (!empty($termino)) {
                $maquina = Maquina::buscarMaquina($termino);
                if ($maquina) {
                    $maquina['estado'] = $maquina['estado'] == 1 ? 'Activa' : 'Inactiva';
                    $datos = [$maquina];
                }
            } else {
                $maquinas = Maquina::obtenerTodas();
                foreach ($maquinas as $m) {
                    $datos[] = [
                        'id_maquinas' => $m->id_maquinas,
                        'maquinas_nombre' => $m->maquinas_nombre,
                        'imagen_maquina' => $m->imagen_maquina,
                        'maquina_descripcion' => $m->maquina_descripcion,
                        'precio_ficha' => $m->precio_ficha ?? 0,
                        'cantidad_ficha' => $m->cantidad_ficha ?? 0,
                        'estado' => $m->estado == 1 ? 'Activa' : 'Inactiva'
                    ];
                }
            }

            $nombreArchivo = !empty($termino) ? "maquina_filtrada_" . self::limpiarNombreArchivo($termino) : "maquinas_completo";
            $titulo = !empty($termino) ? "Máquina Filtrada: $termino" : "Listado Completo de Máquinas";

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión de Máquinas',
                'mostrar_imagenes' => true,
                'columna_imagen' => 'imagen_maquina',
                'ruta_imagenes' => '/assets/img/cantina/',
                'ancho_imagen' => 25,
                'alto_imagen' => 30
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {

            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/maquinas/listado');
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
}
