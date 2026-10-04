<?php

namespace Controllers;

use Models\Funciones;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class FuncionController
{


    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    // Datos de una función con nombres en vez de ids, para el registro de auditoría
    private static function datosAuditoria($funcion): array
    {
        $db = \Models\ActiveRecord::getDB();
        $buscar = function (string $sql, $id) use ($db) {
            $id = (int)$id;
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_column() ?: null;
        };

        return [
            'pelicula' => $buscar("SELECT titulo_pelicula FROM peliculas WHERE id_pelicula = ?", $funcion->rela_peliculas),
            'fecha' => $funcion->fecha_hora,
            'fecha_finalizacion' => $funcion->fecha_finalizacion,
            'sala' => 'Sala ' . $funcion->rela_salas,
            'turno' => substr((string)$buscar("SELECT turno_horario FROM turnos WHERE id_turnos = ?", $funcion->rela_turnos), 0, 5),
            'tipo_entrada' => $buscar("SELECT tipo_entrada_desc FROM tipo_entradas WHERE id_tipo_entrada = ?", $funcion->rela_tipo_entrada),
            'estado' => (int)$funcion->estado === 1 ? 'Activa' : 'Baja',
        ];
    }

    // "función #12 (Duna III, 05/10/2026 21:00, Sala 1)"
    private static function describirFuncion(array $d, $id): string
    {
        $fecha = $d['fecha'] ? date('d/m/Y', strtotime($d['fecha'])) : '';
        return "función #$id ({$d['pelicula']}, $fecha {$d['turno']}, {$d['sala']})";
    }


    public static function index(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        // Filtro de fechas
        $fechaDesde = $_GET['fecha_desde'] ?? '';
        $fechaHasta = $_GET['fecha_hasta'] ?? '';

        $whereFechas = "";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " AND DATE(f.fecha_hora) BETWEEN '{$db->escape_string($fechaDesde)}' AND '{$db->escape_string($fechaHasta)}'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " AND DATE(f.fecha_hora) >= '{$db->escape_string($fechaDesde)}'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " AND DATE(f.fecha_hora) <= '{$db->escape_string($fechaHasta)}'";
        }

        $query = "SELECT 
        f.id_funcion,
        f.fecha_hora,
        f.fecha_finalizacion,
        f.estado,
        p.id_pelicula,
        p.titulo_pelicula,
        p.imagen_pelicula,
        s.id_sala,
        t.turno_horario,
        te.tipo_entrada_desc,
        te.precio_entrada,
        ip.nombre_idioma_pelicula
      FROM funciones f
      INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
      INNER JOIN salas s ON f.rela_salas = s.id_sala
      INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
      INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula
      LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
      LEFT JOIN idiomas_peliculas ip ON f.rela_idioma = ip.id_idioma_pelicula
      WHERE ep.nombre_estado_pelicula IN ('Emision', 'Proximamente')
        AND TIMESTAMP(f.fecha_hora, t.turno_horario) >= NOW()
        {$whereFechas}
        ORDER BY TIMESTAMP(f.fecha_hora, t.turno_horario) ASC";

        $resultado = $db->query($query);

        // Agrupar por película
        $peliculasConFunciones = [];
        while ($row = $resultado->fetch_assoc()) {
            $idPelicula = $row['id_pelicula'];
            if (!isset($peliculasConFunciones[$idPelicula])) {
                $peliculasConFunciones[$idPelicula] = [
                    'id_pelicula'      => $idPelicula,
                    'titulo_pelicula'  => $row['titulo_pelicula'],
                    'imagen_pelicula'  => $row['imagen_pelicula'],
                    'funciones'        => []
                ];
            }
            $peliculasConFunciones[$idPelicula]['funciones'][] = [
                'id_funcion'        => $row['id_funcion'],
                'fecha_hora'        => $row['fecha_hora'],
                'fecha_finalizacion' => $row['fecha_finalizacion'],
                'id_sala'           => $row['id_sala'],
                'turno_horario'     => $row['turno_horario'],
                'tipo_entrada_desc' => $row['tipo_entrada_desc'],
                'precio_entrada'    => $row['precio_entrada'],
                'idioma'            => $row['nombre_idioma_pelicula'],
                'estado'            => $row['estado']
            ];
        }

        $router->render('administrador/funciones/listado', [
            'peliculasConFunciones' => array_values($peliculasConFunciones),
            'fecha_desde'           => $fechaDesde,
            'fecha_hasta'           => $fechaHasta,
            'usuario'               => $_SESSION
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        $salas = $db->query("SELECT * FROM salas WHERE estado = 1")->fetch_all(MYSQLI_ASSOC);
        $peliculas = $db->query("
        SELECT p.*, ep.nombre_estado_pelicula
        FROM peliculas p 
        INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula 
        WHERE ep.nombre_estado_pelicula = 'Emision' 
        AND ep.estado = 1
        ORDER BY p.titulo_pelicula ASC
    ")->fetch_all(MYSQLI_ASSOC);
        $turnos = $db->query("SELECT * FROM turnos WHERE estado = 1")->fetch_all(MYSQLI_ASSOC);
        $tipos_entrada = $db->query("SELECT * FROM tipo_entradas WHERE estado = 1 ORDER BY tipo_entrada_desc ASC")->fetch_all(MYSQLI_ASSOC);
        $idiomas = $db->query("SELECT * FROM idiomas_peliculas")->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/funciones/crear', [
            'salas'         => $salas,
            'peliculas'     => $peliculas,
            'turnos'        => $turnos,
            'tipos_entrada' => $tipos_entrada,
            'idiomas'       => $idiomas
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

            $funcion = new Funciones($datos);
            $errores = $funcion->validar();

            if (!empty($errores)) {
                echo json_encode(['errores' => $errores]);
                return;
            }

            $resultado = $funcion->crearFuncion();

            if ($resultado['resultado']) {
                $idNueva = $resultado['id_insertado'] ?? null;
                $datosNueva = self::datosAuditoria($funcion);
                \Classes\Auditoria::registrar('funcion.crear', 'Creó la ' . self::describirFuncion($datosNueva, $idNueva), 'funciones', $idNueva, null, $datosNueva);

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Función creada correctamente',
                    'redirigir' => '/administrador/funciones/listado'
                ]);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar en la base de datos']);
            }
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
            header('Location: /administrador/funciones/listado');
            exit;
        }

        $funcion = Funciones::find($id);

        if (!$funcion) {
            header('Location: /administrador/funciones/listado');
            exit;
        }

        // Cargar los datos para los selects
        $db = \Models\ActiveRecord::getDB();
        $salas = $db->query("SELECT * FROM salas WHERE estado = 1")->fetch_all(MYSQLI_ASSOC);
        $peliculas = $db->query("
            SELECT p.*, ep.nombre_estado_pelicula
            FROM peliculas p 
            INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula 
            WHERE ep.nombre_estado_pelicula = 'Emision' 
            AND ep.estado = 1
            ORDER BY p.titulo_pelicula ASC
            ")->fetch_all(MYSQLI_ASSOC);
        $turnos = $db->query("SELECT * FROM turnos WHERE estado = 1")->fetch_all(MYSQLI_ASSOC);

        // NUEVO: Cargar tipos de entrada
        $tipos_entrada = $db->query("SELECT * FROM tipo_entradas WHERE estado = 1 ORDER BY tipo_entrada_desc ASC")->fetch_all(MYSQLI_ASSOC);

        $idiomas = $db->query("SELECT * FROM idiomas_peliculas")->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/funciones/editar', [
            'funcion' => $funcion,
            'salas' => $salas,
            'peliculas' => $peliculas,
            'turnos' => $turnos,
            'tipos_entrada' => $tipos_entrada,
            'idiomas' => $idiomas
        ]);
    }


    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!$datos) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
            exit;
        }

        // Buscar la función a actualizar
        $funcion = Funciones::find($datos['id_funcion']);

        if (!$funcion) {
            echo json_encode(['ok' => false, 'mensaje' => 'Función no encontrada']);
            exit;
        }

        $datosAntes = self::datosAuditoria($funcion);

        // Actualizar propiedades
        $funcion->fecha_hora = $datos['fecha_hora'] ?? '';
        $funcion->fecha_finalizacion = $datos['fecha_finalizacion'] ?? '';
        $funcion->rela_salas = $datos['rela_salas'] ?? '';
        $funcion->rela_peliculas = $datos['rela_peliculas'] ?? '';
        $funcion->rela_turnos = $datos['rela_turnos'] ?? '';
        $funcion->rela_tipo_entrada = $datos['rela_tipo_entrada'] ?? ''; // NUEVO
        $funcion->estado = $datos['estado'] ?? 1;

        // Validar
        $errores = $funcion->validar();

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            exit;
        }

        // Actualizar en la base de datos
        $resultado = $funcion->actualizar();

        if ($resultado) {
            $datosDespues = self::datosAuditoria($funcion);
            [$antes, $despues] = \Classes\Auditoria::cambios($datosAntes, $datosDespues, array_keys($datosDespues));
            if ($antes) {
                \Classes\Auditoria::registrar(
                    'funcion.modificar',
                    'Modificó la ' . self::describirFuncion($datosDespues, $funcion->id_funcion) . ' (' . implode(', ', array_keys($despues)) . ')',
                    'funciones',
                    $funcion->id_funcion,
                    $antes,
                    $despues
                );
            }

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Función actualizada correctamente',
                'redirigir' => '/administrador/funciones/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar la función']);
        }
    }

    public static function eliminar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $contenido = file_get_contents('php://input');
        $datos = json_decode($contenido, true);

        $id = $datos['id_funcion'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de Funcion inválido']);
            return;
        }

        $funcion = Funciones::find($id);

        if (!$funcion) {
            echo json_encode(['ok' => false, 'mensaje' => 'Función no encontrada']);
            return;
        }

        // Si ya estaba dada de baja (pedido repetido) no se vuelve a registrar en la auditoría
        if ((int)$funcion->estado === 0) {
            echo json_encode(['ok' => true, 'mensaje' => 'La función ya estaba dada de baja']);
            return;
        }

        $resultado = $funcion->eliminarLogico();

        if ($resultado) {
            \Classes\Auditoria::registrar('funcion.baja', 'Dio de baja la ' . self::describirFuncion(self::datosAuditoria($funcion), $id), 'funciones', $id);
            echo json_encode(['ok' => true, 'mensaje' => 'Función dada de baja correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al desactivar la Función']);
        }
    }


    public static function buscar()
    {
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

        $funcion = Funciones::buscarFuncion($termino);

        if ($funcion) {
            echo json_encode(['ok' => true, 'funcion' => $funcion]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la función']);
        }
    }


    public static function obtenerInfoPelicula()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $id_pelicula = $datos['id_pelicula'] ?? null;

        if (!$id_pelicula) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de película requerido']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();
        $resultado = $db->query(
            "
            SELECT p.imagen_pelicula, p.duracion_pelicula, p.titulo_pelicula,
                   tc.nombre_tipo_clasificacion
            FROM peliculas p
            LEFT JOIN tipos_clasificacion tc ON p.rela_tipo_clasificacion = tc.id_tipo_clasificacion
            WHERE p.id_pelicula = " . intval($id_pelicula)
        );

        if ($resultado && $resultado->num_rows > 0) {
            $pelicula = $resultado->fetch_assoc();
            echo json_encode(['ok' => true, 'pelicula' => $pelicula]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Película no encontrada']);
        }
    }

    public static function exportar()
    {
        // LIMPIAR TODO EL BUFFER
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!self::verificarAdmin()) {
            header('Location: /');
            die();
        }

        $tipo = $_GET['tipo'] ?? '';

        if ($tipo === 'excel') {
            self::exportarExcel();
        } elseif ($tipo === 'pdf') {
            self::exportarPDF();
        } else {
            header('Location: /administrador/funciones/listado');
            die();
        }
    }

    private static function exportarExcel()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!self::verificarAdmin()) {
            header('Location: /');
            die();
        }

        try {
            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerFuncionesFiltradas($fechaDesde, $fechaHasta);

            $nombreArchivo = self::generarNombreArchivo('funciones', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Funciones', $fechaDesde, $fechaHasta);

            // Headers para CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Título
            fputcsv($output, [$titulo . ' - Generado el ' . date('d/m/Y H:i:s')], ';');
            fputcsv($output, [''], ';');

            // Encabezados
            $columnas = ['ID', 'Fecha Inicio', 'Fecha Fin', 'Película', 'Sala', 'Turno', 'Tipo Entrada', 'Precio', 'Estado'];
            fputcsv($output, $columnas, ';');

            // Datos
            foreach ($datos as $fila) {
                $filaExportar = [
                    $fila['id_funcion'],
                    $fila['fecha_hora'],
                    $fila['fecha_finalizacion'],
                    $fila['titulo_pelicula'],
                    $fila['id_sala'],
                    $fila['turno_horario'],
                    $fila['tipo_entrada_desc'] ?? '',
                    $fila['precio_entrada'] ?? '0',
                    $fila['estado']
                ];
                fputcsv($output, $filaExportar, ';');
            }

            fclose($output);
            die();
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN EXCEL: " . $e->getMessage());
            die('Error al generar el archivo CSV');
        }
    }

    private static function exportarPDF()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!self::verificarAdmin()) {
            header('Location: /');
            die();
        }

        try {
            $columnas = [
                'id_funcion' => 'ID',
                'fecha_hora' => 'Inicio',
                'fecha_finalizacion' => 'Fin',
                'titulo_pelicula' => 'Película',
                'id_sala' => 'Sala',
                'turno_horario' => 'Turno',
                'tipo_entrada_desc' => 'Tipo',
                'precio_entrada' => 'Precio',
                'estado' => 'Estado'
            ];

            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerFuncionesFiltradas($fechaDesde, $fechaHasta);

            if (empty($datos)) {
                die('No hay datos para exportar');
            }

            $nombreArchivo = self::generarNombreArchivo('funciones', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Funciones', $fechaDesde, $fechaHasta);

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión Cinematográfica',
                'mostrar_imagenes' => false
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR PDF: " . $e->getMessage());
            die('Error: ' . $e->getMessage());
        }
    }

    private static function obtenerFuncionesFiltradas($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $whereFechas = "";
        $fechaDesde = $db->escape_string($fechaDesde);
        $fechaHasta = $db->escape_string($fechaHasta);

        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(f.fecha_hora) BETWEEN '{$fechaDesde}' AND '{$fechaHasta}'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(f.fecha_hora) >= '{$fechaDesde}'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(f.fecha_hora) <= '{$fechaHasta}'";
        }

        $query = "SELECT 
                f.id_funcion,
                DATE_FORMAT(f.fecha_hora, '%d/%m/%Y %H:%i') as fecha_hora,
                DATE_FORMAT(f.fecha_finalizacion, '%d/%m/%Y %H:%i') as fecha_finalizacion,
                f.estado,
                p.titulo_pelicula,
                p.imagen_pelicula,
                s.id_sala,
                t.turno_horario,
                te.tipo_entrada_desc,
                te.precio_entrada
            FROM funciones f
            INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
            INNER JOIN salas s ON f.rela_salas = s.id_sala
            INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
            LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
            {$whereFechas}
            ORDER BY f.fecha_hora DESC";

        $resultado = $db->query($query);
        $datos = [];

        while ($row = $resultado->fetch_assoc()) {
            $row['estado'] = $row['estado'] == 1 ? 'ACTIVA' : 'INACTIVA';

            if (isset($row['precio_entrada'])) {
                $row['precio_entrada'] = number_format($row['precio_entrada'], 0, ',', '.');
            }

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

    public static function reportes(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $router->render('administrador/funciones/reportes', []);
    }

    public static function apiDatosReportes()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');
        $db = \Models\ActiveRecord::getDB();

        // 1. Películas más vendidas (top 5 por entradas)
        $queryPeliculas = "SELECT 
                        p.titulo_pelicula,
                        COUNT(e.id_entrada) as total_entradas,
                        SUM(te.precio_entrada) as recaudacion
                       FROM peliculas p
                       INNER JOIN funciones f ON f.rela_peliculas = p.id_pelicula
                       INNER JOIN entradas e ON e.rela_funcion = f.id_funcion
                       INNER JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada
                       WHERE e.estado != -1
                       GROUP BY p.id_pelicula, p.titulo_pelicula
                       ORDER BY total_entradas DESC
                       LIMIT 5";

        $resPeliculas = $db->query($queryPeliculas);
        $peliculas = [];
        while ($row = $resPeliculas->fetch_assoc()) {
            $peliculas[] = [
                'titulo'         => $row['titulo_pelicula'],
                'entradas'       => (int) $row['total_entradas'],
                'recaudacion'    => (float) $row['recaudacion']
            ];
        }

        // 2. Salas más ocupadas (por entradas vendidas)
        $querySalas = "SELECT 
                    s.id_sala,
                    COUNT(e.id_entrada) as total_entradas,
                    s.capacidad_sala
                   FROM salas s
                   INNER JOIN funciones f ON f.rela_salas = s.id_sala
                   INNER JOIN entradas e ON e.rela_funcion = f.id_funcion
                   WHERE e.estado != -1
                   GROUP BY s.id_sala, s.capacidad_sala
                   ORDER BY total_entradas DESC";

        $resSalas = $db->query($querySalas);
        $salas = [];
        while ($row = $resSalas->fetch_assoc()) {
            $salas[] = [
                'nombre'      => 'Sala ' . $row['id_sala'],
                'entradas'    => (int) $row['total_entradas'],
                'capacidad'   => (int) $row['capacidad_sala']
            ];
        }

        // 3. Funciones por estado
        $queryEstados = "SELECT 
                ep.nombre_estado_pelicula as estado,
                COUNT(f.id_funcion) as total
             FROM funciones f
             INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
             INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula
             GROUP BY ep.id_estado_pelicula, ep.nombre_estado_pelicula
             ORDER BY total DESC";

        $resEstados = $db->query($queryEstados);
        $estados = [];
        while ($row = $resEstados->fetch_assoc()) {
            $estados[] = [
                'estado' => $row['estado'],
                'total'  => (int) $row['total']
            ];
        }

        // 4. Horario pico (franja horaria con más entradas)
        $queryHorarios = "SELECT 
                        CASE 
                            WHEN HOUR(f.fecha_hora) BETWEEN 8 AND 12 THEN 'Mañana (8-12h)'
                            WHEN HOUR(f.fecha_hora) BETWEEN 12 AND 17 THEN 'Tarde (12-17h)'
                            WHEN HOUR(f.fecha_hora) BETWEEN 17 AND 21 THEN 'Noche (17-21h)'
                            ELSE 'Madrugada (21-8h)'
                        END as franja,
                        COUNT(e.id_entrada) as total_entradas
                      FROM funciones f
                      INNER JOIN entradas e ON e.rela_funcion = f.id_funcion
                      WHERE e.estado != -1
                      GROUP BY franja
                      ORDER BY total_entradas DESC";

        $resHorarios = $db->query($queryHorarios);
        $horarios = [];
        while ($row = $resHorarios->fetch_assoc()) {
            $horarios[] = [
                'franja'  => $row['franja'],
                'total'   => (int) $row['total_entradas']
            ];
        }

        // 5. Totales generales
        $queryTotales = "SELECT 
                        COUNT(DISTINCT f.id_funcion) as total_funciones,
                        COUNT(e.id_entrada) as total_entradas,
                        SUM(te.precio_entrada) as recaudacion_total
                     FROM funciones f
                     LEFT JOIN entradas e ON e.rela_funcion = f.id_funcion AND e.estado != -1
                     LEFT JOIN tipo_entradas te ON e.rela_tipo_entrada = te.id_tipo_entrada";

        $resTotales = $db->query($queryTotales);
        $totales = $resTotales->fetch_assoc();

        echo json_encode([
            'ok'        => true,
            'peliculas' => $peliculas,
            'salas'     => $salas,
            'estados'   => $estados,
            'horarios'  => $horarios,
            'totales'   => [
                'funciones'    => (int) $totales['total_funciones'],
                'entradas'     => (int) $totales['total_entradas'],
                'recaudacion'  => (float) $totales['recaudacion_total']
            ]
        ]);
    }

    public static function guardarMasivo()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $datos = json_decode(file_get_contents('php://input'), true);

        $idPelicula    = $datos['id_pelicula'] ?? null;
        $fechaDesde    = $datos['fecha_desde'] ?? null;
        $fechaHasta    = $datos['fecha_hasta'] ?? null;
        $diasSemana    = $datos['dias_semana'] ?? []; // [1,2,3,4,5] (1=lunes...7=domingo)
        $horarios      = $datos['horarios'] ?? [];    // [{turno, idioma, sala, tipo_entrada}]
        $estado        = $datos['estado'] ?? 1;

        if (!$idPelicula || !$fechaDesde || !$fechaHasta || empty($diasSemana) || empty($horarios)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();
        $db->begin_transaction();

        try {
            $fechaActual = new \DateTime($fechaDesde);
            $fechaFin    = new \DateTime($fechaHasta);
            $fechaFin->modify('+1 day'); // inclusive

            $funcionesCreadas = 0;
            $conflictos = [];

            $queryConflicto = "SELECT COUNT(*) as total FROM funciones
                                WHERE rela_salas = ? AND rela_turnos = ? AND fecha_hora = ? AND estado = 1";
            $stmtConflicto = $db->prepare($queryConflicto);

            $queryInsert = "INSERT INTO funciones
                          (fecha_hora, fecha_finalizacion, rela_salas, rela_peliculas,
                           rela_turnos, rela_tipo_entrada, rela_idioma, estado)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $db->prepare($queryInsert);

            while ($fechaActual < $fechaFin) {
                $diaSemana = (int) $fechaActual->format('N'); // 1=lunes...7=domingo

                if (in_array($diaSemana, $diasSemana)) {
                    foreach ($horarios as $horario) {
                        $idSala       = (int) ($horario['sala'] ?? 0);
                        $idTurno      = (int) ($horario['turno'] ?? 0);
                        $idIdioma     = (int) ($horario['idioma'] ?? 0);
                        $idTipoEntrada = (int) ($horario['tipo_entrada'] ?? 0);

                        if (!$idSala || !$idTurno || !$idIdioma || !$idTipoEntrada) continue;

                        $fechaStr = $fechaActual->format('Y-m-d');

                        // Evitar dos funciones en la misma sala y turno el mismo día
                        $stmtConflicto->bind_param("iis", $idSala, $idTurno, $fechaStr);
                        $stmtConflicto->execute();
                        $existe = $stmtConflicto->get_result()->fetch_assoc()['total'] > 0;

                        if ($existe) {
                            $conflictos[] = "Sala {$idSala} - {$fechaStr}";
                            continue;
                        }

                        $stmtInsert->bind_param(
                            "ssiiiiii",
                            $fechaStr,
                            $datos['fecha_hasta'],
                            $idSala,
                            $idPelicula,
                            $idTurno,
                            $idTipoEntrada,
                            $idIdioma,
                            $estado
                        );
                        $stmtInsert->execute();
                        $funcionesCreadas++;
                    }
                }

                $fechaActual->modify('+1 day');
            }

            if ($funcionesCreadas === 0 && !empty($conflictos)) {
                $db->rollback();
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No se creó ninguna función: todos los horarios elegidos ya están ocupados (' . implode(', ', array_slice($conflictos, 0, 5)) . (count($conflictos) > 5 ? '...' : '') . ')'
                ]);
                return;
            }

            $db->commit();

            // Un solo registro de auditoría por tanda (no uno por función)
            $stmtTitulo = $db->prepare("SELECT titulo_pelicula FROM peliculas WHERE id_pelicula = ?");
            $idPeliculaInt = (int)$idPelicula;
            $stmtTitulo->bind_param('i', $idPeliculaInt);
            $stmtTitulo->execute();
            $titulo = $stmtTitulo->get_result()->fetch_column() ?: "película #$idPelicula";
            \Classes\Auditoria::registrar(
                'funcion.crear',
                "Creó {$funcionesCreadas} función(es) de $titulo, del " . date('d/m/Y', strtotime($fechaDesde)) . ' al ' . date('d/m/Y', strtotime($fechaHasta))
                    . (!empty($conflictos) ? ' (' . count($conflictos) . ' omitida(s) por sala/turno ocupado)' : ''),
                'peliculas',
                $idPelicula,
                null,
                ['pelicula' => $titulo, 'desde' => $fechaDesde, 'hasta' => $fechaHasta, 'dias_semana' => $diasSemana, 'horarios' => count($horarios), 'funciones_creadas' => $funcionesCreadas]
            );

            $mensaje = "Se crearon {$funcionesCreadas} función(es) correctamente";
            if (!empty($conflictos)) {
                $mensaje .= ". Se omitieron " . count($conflictos) . " por conflicto de sala/turno ya ocupado (" . implode(', ', array_slice($conflictos, 0, 5)) . (count($conflictos) > 5 ? '...' : '') . ")";
            }

            echo json_encode([
                'ok'      => true,
                'mensaje' => $mensaje,
                'redirigir' => '/administrador/funciones/listado'
            ]);
        } catch (\Exception $e) {
            $db->rollback();
            echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }
}
