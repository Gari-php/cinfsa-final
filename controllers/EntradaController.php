<?php

namespace Controllers;

use Models\Entrada;
use MVC\Router;
use Classes\Paginador;
use Classes\ExportadorDatos;

class EntradaController
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

        $fechaDesde  = $_GET['fecha_desde']  ?? '';
        $fechaHasta  = $_GET['fecha_hasta']  ?? '';
        $comprobante = trim($_GET['comprobante'] ?? '');

        // PAGINACIÓN
        $paginaActual       = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 6;
        $paginador          = new Paginador($paginaActual, $registrosPorPagina);

        $db = \Models\ActiveRecord::getDB();

        // Filtro de fecha
        $whereFechas = "";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(cf.pago_fecha_hora) BETWEEN '" . $db->escape_string($fechaDesde) . "' AND '" . $db->escape_string($fechaHasta) . "'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(cf.pago_fecha_hora) >= '" . $db->escape_string($fechaDesde) . "'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(cf.pago_fecha_hora) <= '" . $db->escape_string($fechaHasta) . "'";
        }

        // Filtro por comprobante — usa AND si ya hay WHERE, WHERE si no
        $whereComprobante = "";
        if (!empty($comprobante)) {
            $comp = $db->escape_string($comprobante);
            $whereComprobante = (!empty($whereFechas) ? " AND " : " WHERE ") .
                "(cf.numero_comprobante LIKE '%{$comp}%' OR o.numero_orden LIKE '%{$comp}%')";
        }

        $whereFull = $whereFechas . $whereComprobante;

        // Contar total
        // Contar total
        $queryCount = "SELECT COUNT(*) as total
           FROM entradas e
           LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
           LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
           LEFT JOIN butacas_vendidas bv ON bv.id_entrada = e.id_entrada
           LEFT JOIN ordenes o ON o.id_orden = bv.id_orden
           {$whereFull}";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        // Obtener entradas
        $query = "SELECT 
        e.id_entrada,
        e.rela_tipo_entrada,
        e.rela_funcion,
        e.estado,
        te.tipo_entrada_desc,
        te.precio_entrada,
        TIMESTAMP(f.fecha_hora, t.turno_horario) as fecha_hora,
        p.titulo_pelicula,
        s.id_sala,
        cf.numero_comprobante,
        cf.tipo_comprobante,
        cf.pago_fecha_hora,
        u.nombre_usuario as vendedor,
        c.numero_caja,
        o.numero_orden
      FROM entradas e
      INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
      INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
      INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
      INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
      INNER JOIN salas s ON f.rela_salas = s.id_sala
      LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
      LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
      LEFT JOIN arqueo_cajas ac ON cf.rela_arqueo_caja = ac.id_arqueo_caja
      LEFT JOIN cajas c ON ac.rela_caja = c.id_caja
      LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
      LEFT JOIN butacas_vendidas bv ON bv.id_entrada = e.id_entrada
      LEFT JOIN ordenes o ON o.id_orden = bv.id_orden
      {$whereFull}
      ORDER BY e.id_entrada DESC
      {$paginador->limit()}";

        $entradas = Entrada::consultarSQL($query);

        $router->render('administrador/entradas/listado', [
            'entradas'    => $entradas,
            'paginador'   => $paginador,
            'usuario'     => $_SESSION,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'comprobante' => $comprobante
        ]);
    }


    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);

            $entrada = new Entrada($datos);
            $errores = $entrada->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $entrada->crearEntrada();

            if ($resultado['resultado']) {
                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Entrada creada correctamente',
                    'redirigir' => '/administrador/entradas/listado'
                ]);
            } else {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => $resultado['error'] ?? 'Error al guardar en la base de datos'
                ]);
            }
        }
    }

    public static function detalle(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            header('Location: /administrador/entradas/listado');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();
        $query = "SELECT 
        e.id_entrada,
        e.estado,
        te.tipo_entrada_desc,
        te.precio_entrada,
        TIMESTAMP(f.fecha_hora, t.turno_horario) as fecha_hora,
        p.titulo_pelicula,
        s.id_sala,
        cf.numero_comprobante,
        cf.tipo_comprobante,
        cf.pago_fecha_hora,
        u.nombre_usuario as vendedor,
        c.numero_caja,
        o.numero_orden,
        bv.fecha_venta
      FROM entradas e
      INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
      INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
      INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
      INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
      INNER JOIN salas s ON f.rela_salas = s.id_sala
      LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
      LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
      LEFT JOIN arqueo_cajas ac ON cf.rela_arqueo_caja = ac.id_arqueo_caja
      LEFT JOIN cajas c ON ac.rela_caja = c.id_caja
      LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
      LEFT JOIN butacas_vendidas bv ON bv.id_entrada = e.id_entrada
      LEFT JOIN ordenes o ON o.id_orden = bv.id_orden
      WHERE e.id_entrada = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $entrada = $stmt->get_result()->fetch_assoc();

        if (!$entrada) {
            header('Location: /administrador/entradas/listado');
            exit;
        }

        $router->render('administrador/entradas/detalle', [
            'entrada' => $entrada
        ]);
    }


    public static function eliminar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_entrada'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de entrada inválido']);
            return;
        }

        $entrada = Entrada::find($id);

        if (!$entrada) {
            echo json_encode(['ok' => false, 'mensaje' => 'Entrada no encontrada']);
            return;
        }

        // Al cancelar, la butaca queda libre para volver a venderse SOLO en esta función:
        // el registro en butacas_vendidas se conserva ligado a la entrada cancelada (para poder
        // restaurarla) y ya no cuenta como vendida; se reemplaza si alguien la compra.
        // No se toca rela_estado_butaca: ese flag es el bloqueo por mantenimiento de TODAS las funciones.
        $resultado = $entrada->eliminar();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Entrada cancelada correctamente. La butaca quedó disponible para esta función.']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al cancelar la entrada']);
        }
    }


    public static function usar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_entrada'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de entrada inválido']);
            return;
        }

        $entrada = Entrada::find($id);

        if (!$entrada) {
            echo json_encode(['ok' => false, 'mensaje' => 'Entrada no encontrada']);
            return;
        }

        // Verificar que esté activa
        if ($entrada->estado !== Entrada::ESTADO_ACTIVA) {
            echo json_encode(['ok' => false, 'mensaje' => 'La entrada no está activa']);
            return;
        }

        // Verificar que no haya expirado
        if (!$entrada->esValida()) {
            // Marcar como expirada automáticamente si ya venció
            $entrada->estado = Entrada::ESTADO_EXPIRADA;
            $entrada->actualizar();
            echo json_encode(['ok' => false, 'mensaje' => 'La entrada ha expirado']);
            return;
        }

        $resultado = $entrada->marcarComoUsada();

        if ($resultado) {
            echo json_encode(['ok' => true, 'mensaje' => 'Entrada marcada como usada correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al marcar la entrada como usada']);
        }
    }


    public static function restaurar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_entrada'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de entrada inválido']);
            return;
        }

        $entrada = Entrada::find($id);

        if (!$entrada) {
            echo json_encode(['ok' => false, 'mensaje' => 'Entrada no encontrada']);
            return;
        }

        if ($entrada->estado !== Entrada::ESTADO_CANCELADA) {
            echo json_encode(['ok' => false, 'mensaje' => 'La entrada no está cancelada']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();

        // Verificar que la función (fecha real + turno) no haya pasado
        $queryFuncion = "SELECT f.fecha_hora, t.turno_horario 
                      FROM funciones f
                      INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
                      WHERE f.id_funcion = ?";
        $stmtFuncion = $db->prepare($queryFuncion);
        $stmtFuncion->bind_param('i', $entrada->rela_funcion);
        $stmtFuncion->execute();
        $funcion = $stmtFuncion->get_result()->fetch_assoc();

        if (!$funcion || strtotime($funcion['fecha_hora'] . ' ' . $funcion['turno_horario']) <= time()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No se puede restaurar: la función ya ha finalizado']);
            return;
        }

        // La butaca tiene que seguir siendo de esta entrada: si mientras estuvo cancelada se volvió
        // a vender, su registro se reemplazó; si se devolvió el dinero, el registro se borró.
        $stmtButaca = $db->prepare("SELECT id_butaca, id_funcion FROM butacas_vendidas WHERE id_entrada = ?");
        $stmtButaca->bind_param('i', $id);
        $stmtButaca->execute();
        $venta = $stmtButaca->get_result()->fetch_assoc();

        if (!$venta) {
            echo json_encode(['ok' => false, 'mensaje' => 'No se puede restaurar: la butaca de esta entrada ya se vendió a otra persona o se devolvió el dinero']);
            return;
        }

        $db->begin_transaction();
        try {
            // Se bloquea la butaca y se vuelve a verificar, por si se está vendiendo en este instante
            \Models\Butaca::bloquearParaVenta([$venta['id_butaca']]);
            $stmtButaca->execute();
            $venta = $stmtButaca->get_result()->fetch_assoc();

            if (!$venta) {
                $db->rollback();
                echo json_encode(['ok' => false, 'mensaje' => 'No se puede restaurar: la butaca de esta entrada ya se vendió a otra persona']);
                return;
            }

            $par = [['id_butaca' => $venta['id_butaca'], 'id_funcion' => $venta['id_funcion']]];
            if (\Models\Butaca::noDisponibles($par)) {
                $db->rollback();
                echo json_encode(['ok' => false, 'mensaje' => 'No se puede restaurar: un cliente está pagando esa butaca en este momento']);
                return;
            }

            if (!$entrada->restaurar()) {
                throw new \Exception('No se pudo actualizar la entrada');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            echo json_encode(['ok' => false, 'mensaje' => 'Error al restaurar la entrada']);
            return;
        }

        echo json_encode(['ok' => true, 'mensaje' => 'Entrada restaurada correctamente']);
    }


    public static function expirarAutomaticamente()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $entradas_expiradas = Entrada::expirarEntradasVencidas();

        if ($entradas_expiradas !== false) {
            echo json_encode([
                'ok' => true,
                'mensaje' => "Se expiraron $entradas_expiradas entradas automáticamente",
                'entradas_afectadas' => $entradas_expiradas
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al expirar entradas']);
        }
    }


    public static function buscar()
    {
        // AGREGAR headers JSON al inicio
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

        try {

            $entrada = Entrada::buscarEntrada($termino);

            if ($entrada) {
                // Agregar información del estado
                $estados = [
                    Entrada::ESTADO_ACTIVA => 'Activa',
                    Entrada::ESTADO_USADA => 'Usada',
                    Entrada::ESTADO_EXPIRADA => 'Expirada',
                    Entrada::ESTADO_CANCELADA => 'Cancelada'
                ];

                $entrada['estado_desc'] = $estados[$entrada['estado']] ?? 'Desconocido';


                echo json_encode(['ok' => true, 'entrada' => $entrada]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la entrada']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }


    public static function validarQR()
    {
        // Este método lo usarías en el futuro para validar QR en la entrada del cine
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $numero_ticket = $datos['numero_ticket'] ?? '';

        if (!$numero_ticket) {
            echo json_encode(['ok' => false, 'mensaje' => 'Número de ticket requerido']);
            return;
        }

        $entrada_data = Entrada::buscarEntrada($numero_ticket);

        if (!$entrada_data) {
            echo json_encode(['ok' => false, 'mensaje' => 'Entrada no encontrada']);
            return;
        }

        $entrada = Entrada::find($entrada_data['id_entrada']);

        if (!$entrada->esValida()) {
            $estados = [
                Entrada::ESTADO_USADA => 'ya fue utilizada',
                Entrada::ESTADO_EXPIRADA => 'ha expirado',
                Entrada::ESTADO_CANCELADA => 'fue cancelada'
            ];

            $razon = $estados[$entrada->estado] ?? 'no es válida';

            echo json_encode([
                'ok' => false,
                'mensaje' => "Entrada no válida: $razon",
                'estado' => $entrada->estado,
                'estado_desc' => $estados[$entrada->estado] ?? 'Estado desconocido'
            ]);
            return;
        }

        // Si es válida, la marcamos como usada
        $entrada->marcarComoUsada();

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Entrada válida - Acceso permitido',
            'entrada' => [
                'numero_ticket' => $entrada->numero_ticket_entrada,
                'pelicula' => $entrada_data['titulo_pelicula'],
                'sala' => $entrada_data['id_sala'],
                'tipo' => $entrada_data['tipo_entrada_desc']
            ]
        ]);
    }

    public static function reportes(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/entradas/reportes', [
            'titulo' => 'Reportes Gráficos de Entradas'
        ]);
    }

    public static function obtenerDatosGrafico()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $tipo = $_GET['tipo'] ?? 'ventas-periodo';
        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $agrupar = $_GET['agrupar'] ?? 'dia';

        try {
            $datos = match ($tipo) {
                'ventas-periodo' => \Classes\GeneradorGraficos::obtenerVentasEntradas($fechaDesde, $fechaHasta, $agrupar),
                'peliculas-populares' => \Classes\GeneradorGraficos::obtenerPeliculasMasVendidas($fechaDesde, $fechaHasta),
                'tipos-entrada' => \Classes\GeneradorGraficos::obtenerEstadisticasTipoEntrada($fechaDesde, $fechaHasta),
                'ocupacion-salas' => \Classes\GeneradorGraficos::obtenerOcupacionSalas($fechaDesde, $fechaHasta),
                'estados-entradas' => \Classes\GeneradorGraficos::obtenerEstadosEntradas($fechaDesde, $fechaHasta),
                default => []
            };

            echo json_encode([
                'ok' => true,
                'datos' => $datos,
                'tipo' => $tipo
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
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

    public static function exportarExcel()
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        try {
            $columnas = [
                'id_entrada' => 'ID',
                'numero_comprobante' => 'N° Comprobante',
                'tipo_comprobante' => 'Tipo',
                'numero_caja' => 'Caja',
                'vendedor' => 'Vendedor',
                'estado_desc' => 'Estado',
                'tipo_entrada_desc' => 'Tipo Entrada',
                'rela_funcion' => 'Función',
                'titulo_pelicula' => 'Película',
                'id_sala' => 'Sala',
                'fecha_hora' => 'Fecha/Hora Función',
                'precio_entrada' => 'Precio',
                'pago_fecha_hora' => 'Fecha Venta'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerEntradasFiltradas($fechaDesde, $fechaHasta);

            $nombreArchivo = self::generarNombreArchivo('entradas', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Entradas', $fechaDesde, $fechaHasta);

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN ENTRADAS EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/entradas/listado');
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
                'id_entrada' => 'ID',
                'numero_comprobante' => 'N° Comprobante',
                'tipo_comprobante' => 'Tipo',
                'numero_caja' => 'Caja',
                'vendedor' => 'Vendedor',
                'estado_desc' => 'Estado',
                'tipo_entrada_desc' => 'Tipo Entrada',
                'titulo_pelicula' => 'Película',
                'id_sala' => 'Sala',
                'fecha_hora' => 'Fecha/Hora',
                'precio_entrada' => 'Precio',
                'pago_fecha_hora' => 'Fecha Venta'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerEntradasFiltradas($fechaDesde, $fechaHasta);

            $nombreArchivo = self::generarNombreArchivo('entradas', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Entradas', $fechaDesde, $fechaHasta);

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión Cinematográfica'
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN ENTRADAS PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/entradas/listado');
            exit;
        }
    }

    private static function obtenerEntradasFiltradas($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $whereFechas = "";
        $fechaDesde = $db->escape_string($fechaDesde);
        $fechaHasta = $db->escape_string($fechaHasta);

        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(cf.pago_fecha_hora) BETWEEN '{$fechaDesde}' AND '{$fechaHasta}'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(cf.pago_fecha_hora) >= '{$fechaDesde}'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(cf.pago_fecha_hora) <= '{$fechaHasta}'";
        }

        $query = "SELECT 
                e.id_entrada,
                e.rela_tipo_entrada,
                e.rela_funcion,
                e.estado,
                CASE 
                    WHEN e.estado = 1 THEN 'Activa'
                    WHEN e.estado = 2 THEN 'Usada'
                    WHEN e.estado = 0 THEN 'Expirada'
                    WHEN e.estado = -1 THEN 'Cancelada'
                    ELSE 'Desconocido'
                END as estado_desc,
                te.tipo_entrada_desc,
                te.precio_entrada,
                DATE_FORMAT(f.fecha_hora, '%d/%m/%Y %H:%i') as fecha_hora,
                p.titulo_pelicula,
                CONCAT('Sala ', s.id_sala) as id_sala,
                cf.numero_comprobante,
                cf.tipo_comprobante,
                DATE_FORMAT(cf.pago_fecha_hora, '%d/%m/%Y %H:%i') as pago_fecha_hora,
                u.nombre_usuario as vendedor,
                CONCAT('Caja #', c.numero_caja) as numero_caja
              FROM entradas e
              INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
              INNER JOIN funciones f ON e.rela_funcion = f.id_funcion
              INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
              INNER JOIN salas s ON f.rela_salas = s.id_sala
              LEFT JOIN detalle_fact_cine df ON df.rela_entrada = e.id_entrada
              LEFT JOIN cabecera_fact_cine cf ON df.rela_cabecera_fact = cf.id_pagos
              LEFT JOIN arqueo_cajas ac ON cf.rela_arqueo_caja = ac.id_arqueo_caja
              LEFT JOIN cajas c ON ac.rela_caja = c.id_caja
              LEFT JOIN usuarios u ON ac.rela_usuario = u.id_usuario
              {$whereFechas}
              ORDER BY cf.pago_fecha_hora DESC";

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
            $titulo .= " - Vendidas del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
        } elseif (!empty($fechaDesde)) {
            $titulo .= " - Vendidas desde " . date('d/m/Y', strtotime($fechaDesde));
        } elseif (!empty($fechaHasta)) {
            $titulo .= " - Vendidas hasta " . date('d/m/Y', strtotime($fechaHasta));
        } else {
            $titulo .= " - Completo";
        }

        return $titulo;
    }
}
