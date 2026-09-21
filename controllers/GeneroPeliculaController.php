<?php
namespace Controllers;

use Models\GeneroPelicula;
use MVC\Router;

class GeneroPeliculaController {

  
     
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

        $generos = GeneroPelicula::obtenerTodos();
        
        $router->render('administrador/generos/listado', [
            'generos' => $generos
        ]);
    }

  
     
    public static function crear(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/generos/crear');
    }

  
     
    public static function guardar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $genero = new GeneroPelicula($datos);
            $errores = $genero->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $genero->crearGenero();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Género creado correctamente',
                    'redirigir' => '/administrador/generos/listado'
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
            header('Location: /administrador/generos');
            exit;
        }

        $genero = GeneroPelicula::find($id);

        if (!$genero) {
            header('Location: /administrador/generos');
            exit;
        }

        $router->render('administrador/generos/editar', [
            'genero' => $genero
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

        $id = $datos['id_genero_pelicula'] ?? null;
        $genero_pelicula = $datos['genero_pelicula'] ?? '';
        $estado = $datos['estado'] ?? 1;

        // Validaciones
        $errores = [];
        if (!$id || !is_numeric($id)) $errores[] = 'ID de género no válido';
        if (!$genero_pelicula) $errores[] = 'El género de película es obligatorio';

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $genero = GeneroPelicula::find($id);

        if (!$genero) {
            echo json_encode(['ok' => false, 'mensaje' => 'Género no encontrado']);
            return;
        }

        $genero->genero_pelicula = $genero_pelicula;
        $genero->estado = $estado;

        $resultado = $genero->actualizar();

        if ($resultado) {
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Género actualizado correctamente',
                'redirigir' => '/administrador/generos/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el género']);
        }
    }

  
    public static function eliminar() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_genero_pelicula'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de género inválido']);
            return;
        }

        $genero = GeneroPelicula::find($id);

        if (!$genero) {
            echo json_encode(['ok' => false, 'mensaje' => 'Género no encontrado']);
            return;
        }

        $resultado = $genero->darDeBaja();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Género desactivado correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar el género']);
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

        $genero = GeneroPelicula::buscarGenero($termino);

        if ($genero) {
            echo json_encode(['ok' => true, 'genero' => $genero]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró el género']);
        }
    }
}