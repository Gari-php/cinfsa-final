<?php

namespace Classes;

use Models\ActiveRecord;

/**
 * Registro de auditoría: quién hizo cada acción sensible, cuándo y desde qué IP.
 * Se consulta en Control → Registro de auditoría (/administrador/auditoria/listado).
 *
 * Uso (después de que la acción se completó bien):
 *   Auditoria::registrar('precio.tipo_entrada', 'Cambió el precio de 2D', 'tipo_entradas', $id,
 *                        ['precio' => 3000], ['precio' => 3500]);
 */
class Auditoria
{
    // Acciones que se registran, con su nombre para la pantalla
    const ACCIONES = [
        'sesion.login'          => 'Inicio de sesión',
        'sesion.login_fallido'  => 'Inicio de sesión fallido',
        'sesion.logout'         => 'Cierre de sesión',
        'entrada.cancelar'      => 'Cancelación de entrada',
        'entrada.restaurar'     => 'Restauración de entrada',
        'entrada.fuera_horario' => 'Ingreso fuera de horario',
        'entrada.deshacer_ingreso' => 'Ingreso deshecho',
        'devolucion.entrada'    => 'Devolución de entrada',
        'devolucion.productos'  => 'Devolución de productos',
        'orden.cancelar'        => 'Cancelación de compra online',
        'caja.abrir'            => 'Apertura de caja',
        'caja.cerrar'           => 'Cierre de caja',
        'precio.tipo_entrada'   => 'Cambio de precio de entrada',
        'precio.producto'       => 'Cambio de precio de producto',
        'precio.ficha'          => 'Cambio de precio de ficha',
        'producto.crear'        => 'Alta de producto',
        'producto.modificar'    => 'Modificación de producto',
        'producto.baja'         => 'Baja de producto',
        'stock.crear'           => 'Alta de stock',
        'stock.modificar'       => 'Modificación de stock',
        'stock.baja'            => 'Baja de stock',
        'maquina.crear'         => 'Alta de máquina',
        'maquina.modificar'     => 'Modificación de máquina',
        'maquina.baja'          => 'Baja de máquina',
        'ficha.crear'           => 'Alta de ficha',
        'ficha.modificar'       => 'Modificación de ficha',
        'usuario.crear'         => 'Alta de usuario',
        'usuario.modificar'     => 'Modificación de usuario',
        'usuario.baja'          => 'Baja de usuario',
        'permisos.modulos'      => 'Cambio de permisos',
        'butaca.estado'         => 'Bloqueo/desbloqueo de butaca',
        'funcion.crear'         => 'Alta de función',
        'funcion.modificar'     => 'Modificación de función',
        'funcion.baja'          => 'Baja de función',
        'pelicula.crear'        => 'Alta de película',
        'pelicula.modificar'    => 'Modificación de película',
        'pelicula.baja'         => 'Baja de película',
    ];

    // Acciones que la pantalla resalta (posible problema)
    const ACCIONES_DESTACADAS = ['sesion.login_fallido'];

    /**
     * Guarda un registro. Nunca interrumpe la acción principal: si falla, queda en el log de errores.
     *
     * @param array|null $usuario ['id' =>, 'nombre' =>, 'perfil' =>] para cuando todavía no hay
     *                            sesión (inicio de sesión); por defecto se toma de la sesión.
     */
    public static function registrar(
        string $accion,
        string $descripcion,
        ?string $entidad = null,
        $idEntidad = null,
        ?array $antes = null,
        ?array $despues = null,
        ?array $usuario = null
    ): void {
        try {
            // Si se pasa un usuario explícito se usa tal cual (aunque venga vacío, como en un
            // inicio de sesión fallido); si no, el de la sesión
            if ($usuario !== null) {
                $idUsuario = $usuario['id'] ?? null;
                $nombre    = $usuario['nombre'] ?? null;
                $idPerfil  = $usuario['perfil'] ?? null;
            } else {
                $idUsuario = $_SESSION['id_usuario'] ?? null;
                $nombre    = $_SESSION['nombre_usuario'] ?? null;
                $idPerfil  = $_SESSION['perfil'] ?? null;
            }

            $db = ActiveRecord::getDB();
            $perfil = $idPerfil ? self::nombrePerfil($db, (int)$idPerfil) : null;

            $idUsuario  = $idUsuario !== null ? (int)$idUsuario : null;
            $idEntidad  = $idEntidad !== null ? (int)$idEntidad : null;
            $descripcion = mb_substr($descripcion, 0, 500);
            $jsonAntes   = self::aJson($antes);
            $jsonDespues = self::aJson($despues);
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            $stmt = $db->prepare("INSERT INTO auditoria
                (id_usuario, nombre_usuario, perfil, accion, entidad, id_entidad, descripcion, datos_antes, datos_despues, ip)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'issssissss',
                $idUsuario, $nombre, $perfil, $accion, $entidad, $idEntidad,
                $descripcion, $jsonAntes, $jsonDespues, $ip
            );
            $stmt->execute();
        } catch (\Throwable $e) {
            error_log("Auditoría: no se pudo registrar '$accion': " . $e->getMessage());
        }
    }

    /**
     * Compara dos versiones de un registro y devuelve solo los campos que cambiaron: [$antes, $despues].
     * Útil para "modificar": así el registro muestra exactamente qué se tocó.
     */
    public static function cambios(array $antes, array $despues, array $campos): array
    {
        $a = [];
        $d = [];
        foreach ($campos as $campo) {
            $valorAntes = $antes[$campo] ?? null;
            $valorDespues = $despues[$campo] ?? null;
            if ((string)$valorAntes !== (string)$valorDespues) {
                $a[$campo] = $valorAntes;
                $d[$campo] = $valorDespues;
            }
        }
        return [$a, $d];
    }

    public static function nombreAccion(string $accion): string
    {
        return self::ACCIONES[$accion] ?? $accion;
    }

    // Nombre de un perfil por su id (ej. 4 → "VENDEDOR_FUNCIONES"), para descripciones legibles
    public static function perfil($idPerfil): string
    {
        return self::nombrePerfil(ActiveRecord::getDB(), (int)$idPerfil) ?? "perfil $idPerfil";
    }

    private static function nombrePerfil(\mysqli $db, int $idPerfil): ?string
    {
        static $cache = [];
        if (!array_key_exists($idPerfil, $cache)) {
            $stmt = $db->prepare("SELECT nombre_perfil FROM perfiles WHERE id_perfiles = ?");
            $stmt->bind_param('i', $idPerfil);
            $stmt->execute();
            $cache[$idPerfil] = $stmt->get_result()->fetch_column() ?: null;
        }
        return $cache[$idPerfil];
    }

    // JSON de los datos, sin contraseñas ni tokens aunque se pasen por error
    private static function aJson(?array $datos): ?string
    {
        if ($datos === null || $datos === []) {
            return null;
        }
        array_walk_recursive($datos, function (&$valor, $clave) {
            if (is_string($clave) && preg_match('/clave|password|contrase|token|secret/i', $clave)) {
                $valor = '(oculto)';
            }
        });
        return json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
