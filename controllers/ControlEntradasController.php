<?php

namespace Controllers;

use Classes\Auditoria;
use Classes\CodigoQR;
use Middlewares\ValidarModulo;
use Models\Entrada;
use MVC\Router;

/**
 * Control de entradas en la puerta de la sala: se escanea el QR (o se escribe el código corto)
 * y la entrada se marca como usada.
 *
 * Resultado de cada escaneo:
 *   verde    → válida y dentro de horario: se marca usada (se puede deshacer unos segundos)
 *   amarillo → válida pero fuera de horario: no se marca; el controlador puede dejarla pasar
 *              igual y eso queda en la auditoría
 *   rojo     → no sirve (inexistente, ya usada, cancelada o vencida)
 */
class ControlEntradasController
{
    // Se deja entrar desde esta cantidad de minutos antes del inicio hasta que termina la función
    const MINUTOS_ANTES = 60;

    private static function verificarAcceso(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['login']) && ValidarModulo::tiene('CONTROL_ENTRADAS');
    }

    public static function index(Router $router)
    {
        if (!self::verificarAcceso()) {
            header('Location: /');
            exit;
        }

        $router->render('control/entradas', [
            'nombreUsuario' => $_SESSION['nombre_usuario'] ?? '',
            // El administrador y los vendedores vuelven a su panel; el controlador solo tiene esta pantalla
            'urlVolver' => match ((int)($_SESSION['perfil'] ?? 0)) {
                3 => '/administrador',
                4 => '/vendedor/caja',
                5 => '/vendedorproductos',
                default => null,
            },
            'segundosDeshacer' => Entrada::SEGUNDOS_PARA_DESHACER,
        ]);
    }

    // POST {codigo}: valida la entrada y, si corresponde, la marca usada
    public static function validar()
    {
        if (!self::verificarAcceso()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $entrada = self::buscar();
        if (isset($entrada['respuesta'])) {
            echo json_encode($entrada['respuesta']);
            return;
        }

        $rojo = self::motivoRojo($entrada);
        if ($rojo) {
            echo json_encode(self::respuesta('rojo', $rojo[0], $rojo[1], $entrada));
            return;
        }

        $fueraDeHorario = self::motivoFueraDeHorario($entrada);
        if ($fueraDeHorario) {
            echo json_encode(self::respuesta('amarillo', $fueraDeHorario[0], $fueraDeHorario[1], $entrada) + ['puede_forzar' => true]);
            return;
        }

        if (!Entrada::marcarUsadaEnPuerta((int)$entrada['id_entrada'], (int)$_SESSION['id_usuario'])) {
            // Otro controlador la aceptó en el mismo instante
            self::responderYaUsada($entrada);
            return;
        }

        echo json_encode(self::respuesta('verde', 'Puede pasar', 'Entrada válida', $entrada) + ['puede_deshacer' => true]);
    }

    // POST {codigo}: deja pasar una entrada fuera de horario (queda en la auditoría)
    public static function forzar()
    {
        if (!self::verificarAcceso()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $entrada = self::buscar();
        if (isset($entrada['respuesta'])) {
            echo json_encode($entrada['respuesta']);
            return;
        }

        // Solo se fuerza el horario: una entrada usada, cancelada o vencida nunca pasa
        $rojo = self::motivoRojo($entrada);
        if ($rojo) {
            echo json_encode(self::respuesta('rojo', $rojo[0], $rojo[1], $entrada));
            return;
        }

        if (!Entrada::marcarUsadaEnPuerta((int)$entrada['id_entrada'], (int)$_SESSION['id_usuario'])) {
            self::responderYaUsada($entrada);
            return;
        }

        $motivo = self::motivoFueraDeHorario($entrada);
        Auditoria::registrar(
            'entrada.fuera_horario',
            'Dejó pasar fuera de horario la entrada de ' . self::describir($entrada)
                . ($motivo ? ' (' . $motivo[1] . ')' : ''),
            'entradas',
            (int)$entrada['id_entrada'],
            ['estado' => 'activa'],
            ['estado' => 'usada', 'funcion' => self::cuando($entrada['inicio']), 'escaneada' => date('d/m/Y H:i')]
        );

        echo json_encode(self::respuesta('verde', 'Puede pasar', 'Ingreso fuera de horario autorizado', $entrada) + ['puede_deshacer' => true]);
    }

    // POST {codigo}: deshace el último "usada" de este controlador (escaneó la entrada equivocada)
    public static function deshacer()
    {
        if (!self::verificarAcceso()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $entrada = self::buscar();
        if (isset($entrada['respuesta'])) {
            echo json_encode($entrada['respuesta']);
            return;
        }

        if (!Entrada::deshacerUsoEnPuerta((int)$entrada['id_entrada'], (int)$_SESSION['id_usuario'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Ya no se puede deshacer: pasó el tiempo o la marcó otra persona']);
            return;
        }

        Auditoria::registrar(
            'entrada.deshacer_ingreso',
            'Deshizo el ingreso de la entrada de ' . self::describir($entrada),
            'entradas',
            (int)$entrada['id_entrada'],
            ['estado' => 'usada'],
            ['estado' => 'activa']
        );

        echo json_encode(['ok' => true, 'mensaje' => 'Ingreso deshecho: la entrada vuelve a estar activa']);
    }

    /**
     * Lee el código del pedido y busca la entrada. Acepta el texto del QR (CINFSA-E-...), el código
     * completo o el código corto. Si no la encuentra devuelve ['respuesta' => ...] para cortar.
     */
    private static function buscar(): array
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?: [];
        $texto = trim((string)($datos['codigo'] ?? ''));

        if (stripos($texto, CodigoQR::PREFIJO_ENTRADA) === 0) {
            $texto = substr($texto, strlen(CodigoQR::PREFIJO_ENTRADA));
        }
        $codigo = strtolower(preg_replace('/[\s-]/', '', $texto));

        // Otro QR del sistema (o cualquier otro): no es una entrada
        if (!preg_match('/^[0-9a-f]{' . Entrada::LARGO_CODIGO_CORTO . ',32}$/', $codigo)) {
            return ['respuesta' => self::respuesta('rojo', 'QR no válido', 'No es una entrada de CINFSA')];
        }

        $encontradas = Entrada::buscarParaControl($codigo);

        if (count($encontradas) > 1) {
            return ['respuesta' => self::respuesta('rojo', 'Código incompleto', 'Hay más de una entrada con ese código: escaneá el QR')];
        }
        if (!$encontradas) {
            return ['respuesta' => self::respuesta('rojo', 'Entrada inexistente', 'No hay ninguna entrada con ese código')];
        }

        return $encontradas[0];
    }

    // [título, mensaje] si la entrada no puede pasar de ninguna manera
    private static function motivoRojo(array $entrada): ?array
    {
        return match ((int)$entrada['estado']) {
            Entrada::ESTADO_ACTIVA => null,
            Entrada::ESTADO_USADA => ['Ya ingresó', 'Usada el ' . self::fechaHora($entrada['usada_en'])
                . ($entrada['usada_por_nombre'] ? ' (controló ' . $entrada['usada_por_nombre'] . ')' : '')],
            Entrada::ESTADO_CANCELADA => ['Entrada cancelada', 'Esta entrada fue cancelada'],
            Entrada::ESTADO_EXPIRADA => ['Entrada vencida', 'La función fue el ' . self::cuando($entrada['inicio'])],
            default => ['Entrada no válida', 'Estado desconocido'],
        };
    }

    // [título, mensaje] si la entrada es válida pero se escanea fuera de horario
    private static function motivoFueraDeHorario(array $entrada): ?array
    {
        $ahora = time();
        $inicio = strtotime($entrada['inicio']);
        $fin = strtotime($entrada['fin']);

        if ($ahora < $inicio - self::MINUTOS_ANTES * 60) {
            return ['Todavía no', 'Es para el ' . self::cuando($entrada['inicio'])];
        }
        if ($ahora > $fin) {
            return ['La función ya terminó', 'Era el ' . self::cuando($entrada['inicio'])];
        }
        return null;
    }

    private static function responderYaUsada(array $entrada): void
    {
        $actual = Entrada::buscarParaControl($entrada['codigo_acceso'])[0] ?? $entrada;
        $rojo = self::motivoRojo($actual) ?? ['Ya ingresó', 'La acaba de marcar otro controlador'];
        echo json_encode(self::respuesta('rojo', $rojo[0], $rojo[1], $actual));
    }

    private static function respuesta(string $resultado, string $titulo, string $mensaje, ?array $entrada = null): array
    {
        return [
            'ok' => true,
            'resultado' => $resultado,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entrada' => $entrada ? [
                'pelicula' => $entrada['titulo_pelicula'],
                'cuando' => self::cuando($entrada['inicio']),
                'sala' => 'Sala ' . $entrada['sala'],
                'butaca' => $entrada['fila_butaca'] !== null
                    ? 'Fila ' . $entrada['fila_butaca'] . ' · Asiento ' . $entrada['numero_butaca']
                    : null,
                'tipo' => $entrada['tipo_entrada_desc'],
                'codigo' => Entrada::codigoCorto($entrada['codigo_acceso']),
            ] : null,
        ];
    }

    // "Sáb 17/10 21:00"
    private static function cuando(string $fecha): string
    {
        $t = strtotime($fecha);
        return diaCorto($t) . ' ' . date('d/m H:i', $t);
    }

    private static function fechaHora(?string $fecha): string
    {
        return $fecha ? date('d/m', strtotime($fecha)) . ' a las ' . date('H:i', strtotime($fecha)) : '—';
    }

    // "Duna (Sáb 17/10 21:00, Sala 2, Fila 6 · Asiento 7)" para la auditoría
    private static function describir(array $entrada): string
    {
        $butaca = $entrada['fila_butaca'] !== null
            ? ', Fila ' . $entrada['fila_butaca'] . ' · Asiento ' . $entrada['numero_butaca']
            : '';
        return $entrada['titulo_pelicula'] . ' (' . self::cuando($entrada['inicio']) . ', Sala ' . $entrada['sala'] . $butaca . ')';
    }
}
