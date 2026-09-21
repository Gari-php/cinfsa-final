<?php
namespace Controllers;

use Models\Butaca;
use Models\Sala;
use MVC\Router;
use Classes\ExportadorDatos;

class ButacaController {

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

        $butacas = Butaca::obtenerTodas();
        
        $router->render('administrador/butacas/listado', [
            'butacas' => $butacas
        ]);
    }
    // Método para generar butacas automáticamente
    public static function generarButacas() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $idSala = $datos['id_sala'] ?? null;

        if (!$idSala || !is_numeric($idSala)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de sala inválido']);
            return;
        }

        $resultado = Butaca::generarButacasPorSala($idSala);

        if ($resultado['resultado']) {
            echo json_encode([
                'ok' => true,
                'mensaje' => $resultado['mensaje'],
                'butacas_creadas' => $resultado['butacas_creadas']
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => $resultado['mensaje']]);
        }
    }

    // Método para obtener butacas por sala (AJAX)
    public static function obtenerPorSala() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idSala = $_GET['id_sala'] ?? null;

        if (!$idSala || !is_numeric($idSala)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de sala inválido']);
            return;
        }

        try {
            $butacas = Butaca::obtenerPorSala($idSala);
            
            $butacasArray = [];
            foreach ($butacas as $butaca) {
                $butacasArray[] = [
                    'id_butaca' => $butaca->id_butaca,
                    'fila_butaca' => $butaca->fila_butaca,
                    'numero_butaca' => $butaca->numero_butaca,
                    'rela_estado_butaca' => $butaca->rela_estado_butaca,
                    'nombre_estado' => $butaca->nombre_estado
                ];
            }

            echo json_encode([
                'ok' => true,
                'butacas' => $butacasArray,
                'total' => count($butacasArray)
            ]);

        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener butacas']);
        }
    }
    public static function gestionSalas(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // Obtener salas activas
        $db = \Models\ActiveRecord::getDB();
        $salas = $db->query("SELECT * FROM salas WHERE estado = 1 ORDER BY id_sala ASC")->fetch_all(MYSQLI_ASSOC);
        
        $router->render('administrador/butacas/gestion-salas', [
            'salas' => $salas
        ]);
    }

    public static function verMapaSala(Router $router) {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $idSala = $_GET['id_sala'] ?? null;

        if (!$idSala || !is_numeric($idSala)) {
            header('Location: /administrador/butacas/gestion');
            exit;
        }

        // Obtener datos de la sala
        $db = \Models\ActiveRecord::getDB();
        $querySala = "SELECT * FROM salas WHERE id_sala = '$idSala' AND estado = 1";
        $resultadoSala = $db->query($querySala);
        
        if (!$resultadoSala || $resultadoSala->num_rows === 0) {
            header('Location: /administrador/butacas/gestion');
            exit;
        }
        
        $sala = $resultadoSala->fetch_assoc();
        
        // Obtener layout de butacas
        $layout = Butaca::obtenerLayoutSala($idSala);
        
        if (isset($layout['error'])) {
            header('Location: /administrador/butacas/gestion');
            exit;
        }
        
        $router->render('administrador/butacas/mapa-sala', [
            'sala' => $sala,
            'layout' => $layout
        ]);
    }

    public static function cambiarEstadoButaca() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);
        $idButaca = $datos['id_butaca'] ?? null;
        $nuevoEstado = $datos['estado'] ?? null;

        if (!$idButaca || !is_numeric($idButaca)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de butaca inválido']);
            return;
        }

        if (!in_array($nuevoEstado, [1, 2])) { // Solo disponible(1) o no disponible(2)
            echo json_encode(['ok' => false, 'mensaje' => 'Estado inválido']);
            return;
        }

        try {
            $db = \Models\ActiveRecord::getDB();
            
            // Verificar que la butaca no esté reservada
            $queryVerificar = "SELECT rela_estado_butaca FROM butacas WHERE id_butaca = '$idButaca'";
            $resultado = $db->query($queryVerificar);
            
            if (!$resultado || $resultado->num_rows === 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Butaca no encontrada']);
                return;
            }
            
            $butaca = $resultado->fetch_assoc();
            
            if ($butaca['rela_estado_butaca'] == 3) {
                echo json_encode(['ok' => false, 'mensaje' => 'No se puede cambiar el estado de una butaca reservada']);
                return;
            }

            // Actualizar estado
            $queryActualizar = "UPDATE butacas SET rela_estado_butaca = '$nuevoEstado' WHERE id_butaca = '$idButaca'";
            $resultadoActualizar = $db->query($queryActualizar);

            if ($resultadoActualizar) {
                $estadoTexto = $nuevoEstado == 1 ? 'disponible' : 'no disponible';
                echo json_encode([
                    'ok' => true,
                    'mensaje' => "Butaca marcada como $estadoTexto",
                    'nuevo_estado' => $nuevoEstado
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el estado']);
            }

        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error del servidor']);
        }
    }
    
  

}