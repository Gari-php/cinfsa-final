<?php

namespace Controllers;

use MVC\Router;

class AdministradorController
{


    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 3) {
            return false;
        }
        return true;
    }


    private static function redirigirSiNoEsAdmin()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }
    }


    public static function index(Router $router)
    {
        self::redirigirSiNoEsAdmin();
        $router->render('administrador/index');
    }

    public static function respuestaNoAutorizado()
    {
        echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
        return;
    }

    public static function actualizarFoto()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'mensaje' => 'No se recibió ninguna imagen']);
            return;
        }

        $file = $_FILES['foto'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $extension = \Classes\SubidaSegura::validarYObtenerExtension($file, [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ]);

        if (!$extension) {
            echo json_encode(['ok' => false, 'mensaje' => 'Formato no permitido. Usá JPG, PNG, WEBP o GIF']);
            return;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['ok' => false, 'mensaje' => 'La imagen no puede superar los 5MB']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $idUsuario = $_SESSION['id'];

        // Carpeta de destino
        $uploadDir = __DIR__ . '/../public/assets/img/perfiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Nombre único para el archivo (extensión ya validada arriba contra el contenido real)
        $nombreArchivo = 'admin_' . $idUsuario . '_' . time() . '.' . $extension;
        $rutaCompleta = $uploadDir . $nombreArchivo;
        $rutaPublica = '/assets/img/perfiles/' . $nombreArchivo;

        // Borrar foto anterior si existe (para no acumular archivos)
        $db = \Models\ActiveRecord::getDB();
        $idEscaped = $db->escape_string($idUsuario);
        $queryFotoActual = "SELECT foto_perfil FROM usuarios WHERE id_usuario = '{$idEscaped}'";
        $resFotoActual = $db->query($queryFotoActual);
        if ($resFotoActual && $row = $resFotoActual->fetch_assoc()) {
            $fotoAnterior = $row['foto_perfil'];
            if (!empty($fotoAnterior) && strpos($fotoAnterior, '/assets/img/perfiles/') === 0) {
                $rutaAnterior = __DIR__ . '/../public' . $fotoAnterior;
                if (file_exists($rutaAnterior)) {
                    unlink($rutaAnterior);
                }
            }
        }

        // Mover el archivo subido
        if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar la imagen en el servidor']);
            return;
        }

        // Guardar la ruta en la base de datos
        $rutaEscaped = $db->escape_string($rutaPublica);
        $query = "UPDATE usuarios SET foto_perfil = '{$rutaEscaped}' WHERE id_usuario = '{$idEscaped}'";
        $resultado = $db->query($query);

        if (!$resultado) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar en la base de datos']);
            return;
        }

        // Actualizar la sesión
        $_SESSION['foto_perfil'] = $rutaPublica;

        echo json_encode([
            'ok'      => true,
            'mensaje' => 'Foto actualizada correctamente',
            'foto'    => $rutaPublica
        ]);
    }
}
