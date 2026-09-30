<?php
namespace Controllers;

use Models\EstadosPeliculas;
use MVC\Router;

class EstadoPeliculaController {


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

        $estados_peliculas = EstadosPeliculas::obtenerEstadosPeliculas();
        
        $router->render('administrador/estados_peliculas/listado', [
            'estados_peliculas' => $estados_peliculas
        ]);
    }

    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/estados_peliculas/crear');
    }

    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $estado_pelicula = new EstadosPeliculas($datos);
            $errores = $estado_pelicula->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $estado_pelicula->crearEstadoPelicula();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Estado de película creado correctamente',
                    'redirigir' => '/administrador/estados_peliculas/listado'
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
            header('Location: /administrador/estados_peliculas/listado');
            exit;
        }

        $estado_pelicula = EstadosPeliculas::find($id);

        if (!$estado_pelicula) {
            header('Location: /administrador/estados_peliculas/listado');
            exit;
        }

        $router->render('administrador/estados_peliculas/editar', [
            'estado_pelicula' => $estado_pelicula
        ]);
    }

    public static function actualizar() {
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

        $id = $datos['id_estado_pelicula'] ?? null;
        $nombre_estado_pelicula = $datos['nombre_estado_pelicula'] ?? '';
        $estado = $datos['estado'] ?? 1;

        // Validaciones
        $errores = [];
        if (!$id || !is_numeric($id)) $errores[] = 'ID del estado de película no válido';
        if (!$nombre_estado_pelicula || trim($nombre_estado_pelicula) === '') $errores[] = 'El nombre del estado es obligatorio';
        
        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $estado_pelicula = EstadosPeliculas::find($id);

        if (!$estado_pelicula) {
            echo json_encode(['ok' => false, 'mensaje' => 'Estado de película no encontrado']);
            return;
        }

        $estado_pelicula->nombre_estado_pelicula = $nombre_estado_pelicula;
        $estado_pelicula->estado = $estado;
        
        $resultado = $estado_pelicula->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true, 
                'mensaje' => 'Estado de película actualizado correctamente', 
                'redirigir' => '/administrador/estados_peliculas/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el estado de película']);
        }
    }

    public static function eliminar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_estado_pelicula'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de Estado de película inválido']);
            return;
        }

        $estado_pelicula = EstadosPeliculas::find($id);

        if (!$estado_pelicula) {
            echo json_encode(['ok' => false, 'mensaje' => 'Estado de película no encontrado']);
            return;
        }

        $resultado = $estado_pelicula->eliminarLogico();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Estado de película dado de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar el estado de película']);
        }
    }

    public static function buscar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $termino = $datos['termino'] ?? '';

        if (trim($termino) === '') {
            $estados_peliculas = EstadosPeliculas::obtenerEstadosPeliculas();
        } else {
            $estados_peliculas = EstadosPeliculas::buscarEstadosPeliculas($termino);
        }

        // Agregar información de debug
        echo json_encode([
            'ok' => true, 
            'estados_peliculas' => $estados_peliculas,
            'total_encontrados' => count($estados_peliculas),
            'termino_buscado' => $termino
        ]);
    }
}