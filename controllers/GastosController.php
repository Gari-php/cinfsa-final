<?php

namespace Controllers;

use MVC\Router;
use Models\Gastos;
use Models\Proveedor;
use Models\Servicio;
use Models\ArqueoCaja;
use Classes\ExportadorDatos;

class GastosController
{

    /**
     * Verificar que el usuario sea administrador
     */
    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    /**
     * Mostrar listado de gastos
     */
    public static function index(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // Obtener página actual
        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 6;

        // Crear instancia del paginador
        $paginador = new \Classes\Paginador($paginaActual, $registrosPorPagina);

        // Contar total de registros
        $totalRegistros = Gastos::contarTotal();

        // Establecer el total en el paginador
        $paginador->setTotal($totalRegistros);

        // Obtener gastos con paginación
        $gastos = Gastos::obtenerTodos($paginador);

        $router->render('administrador/gastos/listado', [
            'gastos' => $gastos,
            'paginador' => $paginador
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $gastos = Gastos::obtenerTodos();

        // Obtener proveedores activos
        $proveedores = Proveedor::obtenerTodos();

        // Obtener servicios activos
        $servicios = Servicio::obtenerTodos();

        // Obtener cajas abiertas para que el admin pueda elegir
        $cajasAbiertas = ArqueoCaja::obtenerTodasCajasAbiertas();

        $router->render('administrador/gastos/crear', [
            'proveedores' => $proveedores,
            'servicios' => $servicios,
            'gastos' => $gastos,
            'cajasAbiertas' => $cajasAbiertas
        ]);
    }

    public static function guardar()
    {
        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Crear log personalizado en el proyecto
            $logFile = __DIR__ . '/../../logs/gastos_debug.log';
            $logDir = dirname($logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }

            try {
                $datos = json_decode(file_get_contents('php://input'), true);

                if (!$datos) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Datos JSON inválidos\n", FILE_APPEND);
                    echo json_encode(['ok' => false, 'mensaje' => 'Datos JSON inválidos']);
                    return;
                }

                // DEBUG: Ver qué datos llegan
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - INICIO DEBUG\n", FILE_APPEND);
                file_put_contents($logFile, "Datos: " . print_r($datos, true) . "\n", FILE_APPEND);
                file_put_contents($logFile, "SESSION id: " . ($_SESSION['id'] ?? 'NO EXISTE') . "\n", FILE_APPEND);

                error_log("=== INICIO DEBUG GASTOS ===");
                error_log("Datos recibidos: " . print_r($datos, true));
                error_log("SESSION id: " . ($_SESSION['id'] ?? 'NO EXISTE'));
                error_log("===========================");

                // VALIDACIÓN: Siempre asignar una caja (requerido por BD)
                if (empty($datos['rela_arqueo_caja'])) {
                    $arqueoCajaAbierto = ArqueoCaja::obtenerCualquierCajaAbierta();

                    if (!$arqueoCajaAbierto) {
                        echo json_encode([
                            'ok' => false,
                            'mensaje' => 'No hay ninguna caja abierta. Por favor, abre una caja antes de registrar gastos.'
                        ]);
                        return;
                    }

                    $datos['rela_arqueo_caja'] = $arqueoCajaAbierto->id_arqueo_caja;
                } else {
                    $arqueoSeleccionado = ArqueoCaja::find($datos['rela_arqueo_caja']);

                    if (!$arqueoSeleccionado || $arqueoSeleccionado->estado_arqueo !== 'abierto') {
                        echo json_encode([
                            'ok' => false,
                            'mensaje' => 'La caja seleccionada no está abierta o no existe.'
                        ]);
                        return;
                    }
                }

                // Validación adicional: Si el estado es "pagado", verificar caja
                if ($datos['estado_egreso'] === 'pagado' && !$datos['rela_arqueo_caja']) {
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'Para registrar un gasto como "pagado" es necesario asignar una caja abierta.'
                    ]);
                    return;
                }

                // Usuario de sesión
                if (isset($_SESSION['id'])) {
                    $datos['rela_usuario'] = $_SESSION['id'];
                }

                // Convertir vacíos a NULL
                $datos['rela_proveedor'] = !empty($datos['rela_proveedor']) ? (int)$datos['rela_proveedor'] : null;
                $datos['rela_servicio'] = !empty($datos['rela_servicio']) ? (int)$datos['rela_servicio'] : null;
                $datos['fecha_vencimiento'] = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null;
                $datos['numero_comprobante'] = $datos['numero_comprobante'] ?? '';
                $datos['observaciones'] = $datos['observaciones'] ?? '';
                $datos['archivo_comprobante'] = null;

                // Crear objeto y validar
                $gasto = new Gastos($datos);
                $errores = $gasto->validar();

                if (!empty($errores)) {
                    error_log("Errores de validación: " . print_r($errores, true));
                    echo json_encode(['ok' => false, 'errores' => $errores]);
                    return;
                }

                // DEBUG: Ver objeto antes de guardar
                file_put_contents($logFile, "Objeto Gasto: " . print_r($gasto, true) . "\n", FILE_APPEND);
                error_log("Objeto Gasto antes de crear: " . print_r($gasto, true));

                // Crear egreso
                $resultado = $gasto->crearEgreso();

                // DEBUG: Ver resultado
                file_put_contents($logFile, "Resultado: " . print_r($resultado, true) . "\n", FILE_APPEND);
                error_log("Resultado de crearEgreso: " . print_r($resultado, true));

                if (!$resultado['resultado']) {
                    error_log("❌ Fallo en crearEgreso");
                    $errorMsg = 'Error al guardar el gasto en la base de datos';
                    if (isset($resultado['error'])) {
                        error_log("❌ Error SQL: " . $resultado['error']);
                        $errorMsg .= ': ' . $resultado['error'];
                    }
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => $errorMsg
                    ]);
                    return;
                }

                // ⭐ SOLO registrar movimiento de caja si el estado es "pagado"
                if ($datos['estado_egreso'] === 'pagado') {
                    $movimientoOk = self::registrarMovimientoCaja(
                        $datos['rela_arqueo_caja'],
                        $datos['monto'],
                        $datos['concepto'],
                        $resultado['id_insertado'],
                        $datos['forma_pago']
                    );

                    if (!$movimientoOk) {
                        error_log("Advertencia: Egreso #{$resultado['id_insertado']} creado pero falló movimiento_caja");
                    } else {
                        error_log("✅ Movimiento de caja registrado exitosamente para egreso #{$resultado['id_insertado']}");
                    }
                } else {
                    error_log("ℹ️ Estado '{$datos['estado_egreso']}' - No se registra movimiento de caja");
                }

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Gasto registrado correctamente',
                    'redirigir' => '/administrador/gastos/listado'
                ]);
            } catch (\Exception $e) {
                error_log("Error en guardar gasto: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error interno: ' . $e->getMessage()
                ]);
            }
        }
    }

    private static function registrarMovimientoCaja($idArqueoCaja, $monto, $concepto, $idEgreso, $formaPago)
    {
        try {
            $db = \Models\ActiveRecord::getDB();

            $query = "INSERT INTO movimientos_caja 
                  (rela_arqueo_caja, tipo_movimiento, concepto_movimiento, 
                   monto, fecha_movimiento, forma_pago, rela_egreso) 
                  VALUES (?, 'egreso', ?, ?, NOW(), ?, ?)";

            $stmt = $db->prepare($query);

            if (!$stmt) {
                error_log("Error prepare movimiento_caja: " . $db->error);
                return false;
            }

            // i = int, s = string, d = decimal
            $stmt->bind_param(
                "isdsi",
                $idArqueoCaja,  // i - int(11)
                $concepto,      // s - varchar(100)
                $monto,         // d - decimal(10,2)
                $formaPago,     // s - enum
                $idEgreso       // i - int(11)
            );

            $resultado = $stmt->execute();

            if (!$resultado) {
                error_log("Error execute movimiento_caja: " . $stmt->error);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log("Exception movimiento_caja: " . $e->getMessage());
            return false;
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
            header('Location: /administrador/gastos/listado');
            exit;
        }

        $gasto = Gastos::find($id);

        if (!$gasto) {
            header('Location: /administrador/gastos/listado');
            exit;
        }

        // Obtener proveedores activos
        $proveedores = Proveedor::obtenerTodos();

        // Obtener servicios activos
        $servicios = Servicio::obtenerTodos();

        // Obtener cajas abiertas (para mostrar info)
        $cajasAbiertas = ArqueoCaja::obtenerTodasCajasAbiertas();

        $router->render('administrador/gastos/editar', [
            'gasto' => $gasto,
            'proveedores' => $proveedores,
            'servicios' => $servicios,
            'cajasAbiertas' => $cajasAbiertas
        ]);
    }

    public static function actualizar()
    {
        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        try {
            $contenido = file_get_contents('php://input');
            $datos = json_decode($contenido, true);

            if (!$datos) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
                return;
            }

            $id = $datos['id_egreso'] ?? null;

            if (!$id || !is_numeric($id)) {
                echo json_encode(['ok' => false, 'mensaje' => 'ID de gasto no válido']);
                return;
            }

            $gasto = Gastos::find($id);

            if (!$gasto) {
                echo json_encode(['ok' => false, 'mensaje' => 'Gasto no encontrado']);
                return;
            }

            // Guardar el estado anterior para comparar
            $estadoAnterior = $gasto->estado_egreso;
            $cajaAnterior = $gasto->rela_arqueo_caja;
            $nuevoEstado = $datos['estado_egreso'] ?? $estadoAnterior;
            $nuevaCaja = $datos['rela_arqueo_caja'] ?? $cajaAnterior;

            error_log("=== ACTUALIZAR GASTO ===");
            error_log("Estado anterior: {$estadoAnterior}");
            error_log("Nuevo estado: {$nuevoEstado}");
            error_log("Caja anterior: {$cajaAnterior}");
            error_log("Nueva caja: {$nuevaCaja}");

            // Validación: Si cambia a "pagado", debe tener una caja asignada
            if ($nuevoEstado === 'pagado' && !$nuevaCaja) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No se puede marcar como "pagado" sin asignar una caja abierta.'
                ]);
                return;
            }

            // Si cambia a "pagado", verificar que la caja esté abierta
            if ($nuevoEstado === 'pagado' && $nuevaCaja) {
                $arqueo = ArqueoCaja::find($nuevaCaja);

                if (!$arqueo || $arqueo->estado_arqueo !== 'abierto') {
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'La caja seleccionada no está abierta. Solo puedes marcar como "pagado" gastos con cajas abiertas.'
                    ]);
                    return;
                }
            }

            // Convertir valores vacíos a NULL para los campos relacionales
            $datos['rela_proveedor'] = !empty($datos['rela_proveedor']) ? (int)$datos['rela_proveedor'] : null;
            $datos['rela_servicio'] = !empty($datos['rela_servicio']) ? (int)$datos['rela_servicio'] : null;
            $datos['fecha_vencimiento'] = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null;

            // Actualizar propiedades
            $gasto->rela_arqueo_caja = $nuevaCaja; // Permitir cambio de caja
            $gasto->rela_proveedor = $datos['rela_proveedor'];
            $gasto->rela_servicio = $datos['rela_servicio'];
            $gasto->numero_comprobante = $datos['numero_comprobante'] ?? $gasto->numero_comprobante;
            $gasto->concepto = $datos['concepto'] ?? $gasto->concepto;
            $gasto->monto = $datos['monto'] ?? $gasto->monto;
            $gasto->forma_pago = $datos['forma_pago'] ?? $gasto->forma_pago;
            $gasto->tipo_egreso = $datos['tipo_egreso'] ?? $gasto->tipo_egreso;
            $gasto->fecha_egreso = $datos['fecha_egreso'] ?? $gasto->fecha_egreso;
            $gasto->fecha_vencimiento = $datos['fecha_vencimiento'];
            $gasto->estado_egreso = $nuevoEstado;
            $gasto->observaciones = $datos['observaciones'] ?? $gasto->observaciones;

            // Validar antes de actualizar
            $errores = $gasto->validar();
            if (!empty($errores)) {
                echo json_encode(['ok' => false, 'errores' => $errores]);
                return;
            }

            // Actualizar el egreso
            $resultado = $gasto->actualizar();

            if (!$resultado) {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el gasto']);
                return;
            }

            // ⭐ LÓGICA PRINCIPAL: Si cambió de otro estado a "pagado", registrar movimiento
            if ($estadoAnterior !== 'pagado' && $nuevoEstado === 'pagado') {
                error_log("✅ Cambio detectado: {$estadoAnterior} → pagado");
                error_log("Registrando movimiento en caja...");

                $movimientoOk = self::registrarMovimientoCaja(
                    $gasto->rela_arqueo_caja,
                    $gasto->monto,
                    $gasto->concepto,
                    $gasto->id_egreso,
                    $gasto->forma_pago
                );

                if (!$movimientoOk) {
                    error_log("❌ Advertencia: Estado cambiado a 'pagado' pero falló registro de movimiento_caja");
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'El gasto se actualizó pero falló el registro del movimiento en caja. Contacta al administrador.'
                    ]);
                    return;
                } else {
                    error_log("✅ Movimiento de caja registrado exitosamente");
                }
            }

            // Si cambió DE "pagado" a otro estado, advertir (pero no eliminar el movimiento)
            if ($estadoAnterior === 'pagado' && $nuevoEstado !== 'pagado') {
                error_log("⚠️ Cambio de estado: pagado → {$nuevoEstado}");
                error_log("El movimiento de caja permanece registrado");
            }

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Gasto actualizado correctamente',
                'redirigir' => '/administrador/gastos/listado'
            ]);
        } catch (\Exception $e) {
            error_log("Error en actualizar gasto: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error interno: ' . $e->getMessage()
            ]);
        }
    }

    public static function eliminar()
    {
        header('Content-Type: application/json');

        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        try {
            $contenido = file_get_contents('php://input');
            $datos = json_decode($contenido, true);

            if (!$datos) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
                return;
            }

            $id = $datos['id_egreso'] ?? null;

            if (!$id || !is_numeric($id)) {
                echo json_encode(['ok' => false, 'mensaje' => 'ID de gasto inválido']);
                return;
            }

            $gasto = Gastos::find($id);

            if (!$gasto) {
                echo json_encode(['ok' => false, 'mensaje' => 'Gasto no encontrado']);
                return;
            }

            // Verificar si ya está anulado
            if ($gasto->estado_egreso === 'anulado') {
                echo json_encode(['ok' => false, 'mensaje' => 'Este gasto ya está anulado']);
                return;
            }

            // Log para auditoría
            error_log("=== ANULAR GASTO ===");
            error_log("ID: {$id}");
            error_log("Concepto: {$gasto->concepto}");
            error_log("Estado anterior: {$gasto->estado_egreso}");

            // Realizar baja lógica
            $resultado = $gasto->darDeBaja();

            if ($resultado) {
                error_log("✅ Gasto anulado exitosamente");
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Gasto anulado correctamente'
                ]);
            } else {
                error_log("❌ Error al anular gasto");
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error al anular el gasto. Intenta nuevamente.'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en eliminar gasto: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Buscar gasto por término
     */
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

        $gastos = Gastos::buscarEgreso($termino);

        echo json_encode(['ok' => true, 'gastos' => is_array($gastos) ? $gastos : [$gastos]]);
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

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_egreso'          => 'ID',
                'fecha_egreso'       => 'Fecha',
                'numero_comprobante' => 'N° Comprobante',
                'concepto'           => 'Concepto',
                'tipo_egreso'        => 'Tipo',
                'monto'              => 'Monto',
                'forma_pago'         => 'Forma de Pago',
                'estado_egreso'      => 'Estado',
                'observaciones'      => 'Observaciones'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerGastosFiltrados($fechaDesde, $fechaHasta);
            $nombreArchivo = self::generarNombreArchivo('gastos', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Gastos', $fechaDesde, $fechaHasta);

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN GASTOS EXCEL: " . $e->getMessage());
            header('Location: /administrador/gastos/listado');
            exit;
        }
    }

    public static function exportarPDF()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_egreso'          => 'ID',
                'fecha_egreso'       => 'Fecha',
                'numero_comprobante' => 'N° Comprobante',
                'concepto'           => 'Concepto',
                'monto'              => 'Monto',
                'forma_pago'         => 'Forma de Pago',
                'estado_egreso'      => 'Estado'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerGastosFiltrados($fechaDesde, $fechaHasta);
            $nombreArchivo = self::generarNombreArchivo('gastos', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Gastos', $fechaDesde, $fechaHasta);

            $config = [
                'orientacion' => 'L',
                'empresa'     => 'CINFSA - Sistema de Gestión Cinematográfica'
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN GASTOS PDF: " . $e->getMessage());
            header('Location: /administrador/gastos/listado');
            exit;
        }
    }

    private static function obtenerGastosFiltrados($fechaDesde = '', $fechaHasta = '')
    {
        $db = \Models\ActiveRecord::getDB();

        $where = "WHERE 1=1";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $where .= " AND DATE(e.fecha_egreso) BETWEEN '" . $db->escape_string($fechaDesde) . "' AND '" . $db->escape_string($fechaHasta) . "'";
        } elseif (!empty($fechaDesde)) {
            $where .= " AND DATE(e.fecha_egreso) >= '" . $db->escape_string($fechaDesde) . "'";
        } elseif (!empty($fechaHasta)) {
            $where .= " AND DATE(e.fecha_egreso) <= '" . $db->escape_string($fechaHasta) . "'";
        }

        $query = "SELECT 
                e.id_egreso,
                DATE_FORMAT(e.fecha_egreso, '%d/%m/%Y') as fecha_egreso,
                COALESCE(e.numero_comprobante, '-') as numero_comprobante,
                e.concepto,
                COALESCE(e.tipo_egreso, 'OPERATIVO') as tipo_egreso,
                e.monto,
                e.forma_pago,
                e.estado_egreso,
                COALESCE(e.observaciones, '-') as observaciones
              FROM egresos e
              {$where}
              ORDER BY e.fecha_egreso DESC";

        $resultado = $db->query($query);
        $datos = [];
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = $row;
        }
        return $datos;
    }

    private static function generarNombreArchivo($prefijo, $fechaDesde, $fechaHasta)
    {
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            return "{$prefijo}_{$fechaDesde}_a_{$fechaHasta}";
        } elseif (!empty($fechaDesde)) {
            return "{$prefijo}_desde_{$fechaDesde}";
        } elseif (!empty($fechaHasta)) {
            return "{$prefijo}_hasta_{$fechaHasta}";
        }
        return "{$prefijo}_completo";
    }

    private static function generarTituloReporte($entidad, $fechaDesde, $fechaHasta)
    {
        $titulo = "Listado de {$entidad}";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $titulo .= " - Del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
        } elseif (!empty($fechaDesde)) {
            $titulo .= " - Desde " . date('d/m/Y', strtotime($fechaDesde));
        } elseif (!empty($fechaHasta)) {
            $titulo .= " - Hasta " . date('d/m/Y', strtotime($fechaHasta));
        } else {
            $titulo .= " - Completo";
        }
        return $titulo;
    }
}
