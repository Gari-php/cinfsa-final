<?php

namespace Classes;

use Pusher\Pusher;

class Notificaciones
{

    private static $pusher = null;
    private static $db = null;

    private static function getPusherConfig()
    {
        return [
            'app_id' => $_ENV['PUSHER_APP_ID'] ?? '',
            'key' => $_ENV['PUSHER_KEY'] ?? '',
            'secret' => $_ENV['PUSHER_SECRET'] ?? '',
            'cluster' => $_ENV['PUSHER_CLUSTER'] ?? 'us2',
            'useTLS' => true
        ];
    }

    private static function getDB()
    {
        if (self::$db === null) {
            self::$db = new \mysqli(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                $_ENV['DB_NAME'] ?? 'cinfsa1'
            );

            if (self::$db->connect_error) {
                throw new \Exception("Error de conexión: " . self::$db->connect_error);
            }

            self::$db->set_charset("utf8");
        }

        return self::$db;
    }


    private static function getPusher()
    {
        if (self::$pusher === null) {
            $config = self::getPusherConfig();
            self::$pusher = new Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                [
                    'cluster' => $config['cluster'],
                    'useTLS' => $config['useTLS']
                ]
            );
        }

        return self::$pusher;
    }


    public static function crear($usuario_id, $titulo, $descripcion, $tipo = 'sistema')
    {
        try {
            $db = self::getDB();

            $query = "INSERT INTO notificaciones (rela_usuario, titulo, descripcion, tipo, leido, fecha_creacion) 
                      VALUES (?, ?, ?, ?, 0, NOW())";

            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id, $titulo, $descripcion, $tipo]);

            $notificacion_id = $db->insert_id;

            // Enviar notificación en tiempo real via Pusher
            self::enviarTiempoReal($usuario_id, [
                'id' => $notificacion_id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'tipo' => $tipo,
                'fecha' => date('Y-m-d H:i:s')
            ]);

            return [
                'ok' => true,
                'id' => $notificacion_id,
                'mensaje' => 'Notificación creada correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
            return [
                'ok' => false,
                'mensaje' => 'Error al crear la notificación'
            ];
        }
    }

    private static function enviarTiempoReal($usuario_id, $data)
    {
        try {
            $pusher = self::getPusher();

            // Canal específico para el usuario
            $canal = "usuario-{$usuario_id}";

            $pusher->trigger($canal, 'nueva-notificacion', $data);

            // También enviar a canal general de administradores si es admin
            if (self::esAdministrador($usuario_id)) {
                $pusher->trigger('admin-notifications', 'nueva-notificacion', $data);
            }
        } catch (\Exception $e) {
            error_log("Error al enviar notificación en tiempo real: " . $e->getMessage());
        }
    }


    public static function obtenerPorUsuario($usuario_id, $limite = 20, $solo_no_leidas = false)
    {
        try {
            $db = self::getDB();

            $where_leido = $solo_no_leidas ? "AND leido = 0" : "";

            $query = "SELECT id_notificacion, titulo, descripcion, tipo, leido, 
                             fecha_creacion, fecha_lectura
                      FROM notificaciones 
                      WHERE rela_usuario = ? {$where_leido}
                      ORDER BY fecha_creacion DESC 
                      LIMIT ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id, $limite]);
            $resultado = $stmt->get_result();

            $notificaciones = [];
            while ($row = $resultado->fetch_assoc()) {
                $notificaciones[] = $row;
            }

            return $notificaciones;
        } catch (\Exception $e) {
            error_log("Error al obtener notificaciones: " . $e->getMessage());
            return [];
        }
    }

    public static function contarNoLeidas($usuario_id)
    {
        try {
            $db = self::getDB();

            $query = "SELECT COUNT(*) as total 
                      FROM notificaciones 
                      WHERE rela_usuario = ? AND leido = 0";

            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id]);
            $resultado = $stmt->get_result();
            $row = $resultado->fetch_assoc();

            return intval($row['total']);
        } catch (\Exception $e) {
            error_log("Error al contar notificaciones: " . $e->getMessage());
            return 0;
        }
    }

    public static function marcarComoLeida($notificacion_id, $usuario_id)
    {
        try {
            $db = self::getDB();

            $query = "UPDATE notificaciones 
                      SET leido = 1, fecha_lectura = NOW() 
                      WHERE id_notificacion = ? AND rela_usuario = ?";

            $stmt = $db->prepare($query);
            $resultado = $stmt->execute([$notificacion_id, $usuario_id]);

            return $resultado;
        } catch (\Exception $e) {
            error_log("Error al marcar notificación como leída: " . $e->getMessage());
            return false;
        }
    }

    public static function marcarTodasComoLeidas($usuario_id)
    {
        try {
            $db = self::getDB();

            $query = "UPDATE notificaciones 
                    SET leido = 1, fecha_lectura = NOW() 
                    WHERE rela_usuario = ? AND leido = 0";

            $stmt = $db->prepare($query);
            if (!$stmt) {
                error_log("Error preparando query marcarTodasComoLeidas: " . $db->error);
                return false;
            }

            $stmt->bind_param("i", $usuario_id);
            $resultado = $stmt->execute();

            if (!$resultado) {
                error_log("Error ejecutando query marcarTodasComoLeidas: " . $stmt->error);
                return false;
            }

            error_log("Filas afectadas marcarTodasComoLeidas: " . $stmt->affected_rows);
            return true; // Cambiar para que siempre devuelva true si no hay error

        } catch (\Exception $e) {
            error_log("Error al marcar todas las notificaciones como leídas: " . $e->getMessage());
            return false;
        }
    }

    public static function notificarNuevoUsuario($nuevo_usuario_id, $nombre_usuario)
    {
        file_put_contents('debug_notificaciones.txt', "EJECUTANDO notificarNuevoUsuario: $nombre_usuario\n", FILE_APPEND);
        error_log("🔍 INICIO notificarNuevoUsuario - Usuario: $nombre_usuario");

        // Obtener todos los administradores
        $administradores = self::obtenerAdministradores();
        error_log("👥 Administradores encontrados: " . count($administradores));

        foreach ($administradores as $admin) {
            error_log("📧 Enviando notificación a admin ID: " . $admin['id_usuario']);
            $resultado = self::crear(
                $admin['id_usuario'],
                'Nuevo Usuario Registrado',
                "Se ha registrado un nuevo usuario: {$nombre_usuario}",
                'registro_usuario'
            );
            error_log("✅ Resultado notificación: " . ($resultado['ok'] ? 'OK' : 'ERROR - ' . $resultado['mensaje']));
        }
    }


    public static function notificarStockBajo($producto_nombre, $stock_actual, $cantina_nombre)
    {
        $administradores = self::obtenerAdministradores();

        foreach ($administradores as $admin) {
            self::crear(
                $admin['id_usuario'],
                'Stock Bajo',
                "El producto '{$producto_nombre}' en {$cantina_nombre} tiene solo {$stock_actual} unidades",
                'stock_bajo'
            );
        }
    }


    public static function notificarNuevaVenta($total_venta, $usuario_comprador)
    {
        $administradores = self::obtenerAdministradores();

        foreach ($administradores as $admin) {
            self::crear(
                $admin['id_usuario'],
                'Nueva Venta Realizada',
                "Venta de \${$total_venta} realizada por {$usuario_comprador}",
                'nueva_venta'
            );
        }
    }

    private static function esAdministrador($usuario_id)
    {
        try {
            $db = self::getDB();
            $query = "SELECT rela_perfil FROM usuarios WHERE id_usuario = ?"; // CORREGIDO
            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id]);
            $resultado = $stmt->get_result();
            $usuario = $resultado->fetch_assoc();

            return $usuario && $usuario['rela_perfil'] == 3; // CORREGIDO
        } catch (\Exception $e) {
            error_log("Error al verificar administrador: " . $e->getMessage());
            return false;
        }
    }


    private static function obtenerAdministradores()
    {
        try {
            $db = self::getDB();

            $query = "SELECT id_usuario, nombre_usuario 
                    FROM usuarios 
                    WHERE rela_perfil = 3 AND estado = 1";
            $resultado = $db->query($query);
            $administradores = [];

            while ($row = $resultado->fetch_assoc()) {
                $administradores[] = $row;
            }
            return $administradores;
        } catch (\Exception $e) {
            return [];
        }
    }


    public static function limpiarAntiguas()
    {
        try {
            $db = self::getDB();

            $query = "DELETE FROM notificaciones 
                      WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $resultado = $db->query($query);

            return [
                'ok' => true,
                'eliminadas' => $db->affected_rows
            ];
        } catch (\Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'Error al limpiar notificaciones'
            ];
        }
    }

    public static function notificarFuncionVencida($id_funcion, $titulo_pelicula, $fecha_fin)
    {
        $administradores = self::obtenerAdministradores();

        foreach ($administradores as $admin) {
            self::crear(
                $admin['id_usuario'],
                'Función Finalizada',
                "La función '{$titulo_pelicula}' (ID: {$id_funcion}) finalizó el {$fecha_fin}",
                'sistema'
            );
        }
    }

    public static function verificarFuncionesVencidas()
    {
        try {
            $db = self::getDB();
            $hoy = date('Y-m-d');

            // Buscar funciones que ya pasaron su fecha de finalización y están activas
            $query = "SELECT f.id_funcion, f.fecha_finalizacion, p.titulo_pelicula
                    FROM funciones f
                    INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                    WHERE f.fecha_finalizacion < ? 
                    AND f.estado = 1";

            $stmt = $db->prepare($query);
            $stmt->execute([$hoy]);
            $resultado = $stmt->get_result();

            $funcionesVencidas = [];
            while ($row = $resultado->fetch_assoc()) {
                $funcionesVencidas[] = $row;
            }

            // Notificar por cada función vencida
            foreach ($funcionesVencidas as $funcion) {
                self::notificarFuncionVencida(
                    $funcion['id_funcion'],
                    $funcion['titulo_pelicula'],
                    date('d/m/Y', strtotime($funcion['fecha_finalizacion']))
                );

                // Opcional: Desactivar automáticamente la función
                $updateQuery = "UPDATE funciones SET estado = 0 WHERE id_funcion = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$funcion['id_funcion']]);
            }

            return [
                'ok' => true,
                'funciones_procesadas' => count($funcionesVencidas)
            ];
        } catch (\Exception $e) {
            error_log("Error al verificar funciones vencidas: " . $e->getMessage());
            return [
                'ok' => false,
                'mensaje' => 'Error al verificar funciones vencidas'
            ];
        }
    }

    public static function notificarNuevoReclamo($nombreCliente, $asunto, $mensaje, $idUsuarioCliente = null, $numeroOrden = null, $urlComprobante = null)
    {
        $administradores = self::obtenerAdministradores();

        $descripcionCorta = strlen($mensaje) > 100
            ? substr($mensaje, 0, 100) . '...'
            : $mensaje;

        $infoOrden = $numeroOrden ? " | Orden: {$numeroOrden}" : '';
        $infoAdjunto = $urlComprobante ? " | 📎 {$urlComprobante}" : '';

        foreach ($administradores as $admin) {
            self::crear(
                $admin['id_usuario'],
                '📩 Nuevo Reclamo/Soporte',
                "Cliente: {$nombreCliente} | Asunto: {$asunto}{$infoOrden} | {$descripcionCorta}{$infoAdjunto}",
                'soporte'
            );
        }
    }

    public static function notificarNuevoContacto($nombre, $email, $asunto, $mensaje)
    {
        $administradores = self::obtenerAdministradores();

        $descripcionCorta = strlen($mensaje) > 100
            ? substr($mensaje, 0, 100) . '...'
            : $mensaje;

        foreach ($administradores as $admin) {
            self::crear(
                $admin['id_usuario'],
                '📩 Nuevo Mensaje de Contacto',
                "De: {$nombre} ({$email}) | Asunto: {$asunto} | {$descripcionCorta}",
                'contacto'
            );
        }
    }
}
