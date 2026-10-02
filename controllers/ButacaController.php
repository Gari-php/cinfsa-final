<?php
namespace Controllers;

use Models\Butaca;
use MVC\Router;

class ButacaController {

    private static function verificarAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
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

        $funciones = Butaca::obtenerFuncionesPorSala($idSala);

        $router->render('administrador/butacas/mapa-sala', [
            'sala' => $sala,
            'layout' => $layout,
            'funciones' => $funciones
        ]);
    }

    // Método para obtener el layout de butacas de una sala para una función específica (AJAX)
    public static function layoutPorFuncion() {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $idFuncion = $_GET['id_funcion'] ?? null;

        if (!$idFuncion || !is_numeric($idFuncion)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de función inválido']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();
        $query = "SELECT rela_salas FROM funciones WHERE id_funcion = ? AND estado = 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $idFuncion);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Función no encontrada']);
            return;
        }

        $idSala = $resultado->fetch_assoc()['rela_salas'];
        $layout = Butaca::obtenerLayoutSalaPorFuncion($idSala, $idFuncion);

        if (isset($layout['error'])) {
            echo json_encode(['ok' => false, 'mensaje' => $layout['error']]);
            return;
        }

        echo json_encode(['ok' => true, 'layout' => $layout]);
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
            $queryVerificar = "SELECT rela_estado_butaca, fila_butaca, numero_butaca, rela_salas FROM butacas WHERE id_butaca = '$idButaca'";
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

                if ((int)$butaca['rela_estado_butaca'] !== (int)$nuevoEstado) {
                    $textoEstado = fn($e) => (int)$e === 1 ? 'Disponible' : 'Bloqueada (mantenimiento)';
                    \Classes\Auditoria::registrar(
                        'butaca.estado',
                        ($nuevoEstado == 1 ? 'Desbloqueó' : 'Bloqueó') . " la butaca F{$butaca['fila_butaca']}-C{$butaca['numero_butaca']} de la sala {$butaca['rela_salas']}",
                        'butacas',
                        $idButaca,
                        ['estado' => $textoEstado($butaca['rela_estado_butaca'])],
                        ['estado' => $textoEstado($nuevoEstado)]
                    );
                }

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