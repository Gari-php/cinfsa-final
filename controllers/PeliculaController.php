<?php

namespace Controllers;

use Models\Pelicula;
use MVC\Router;
use Classes\ExportadorDatos;
use Classes\Paginador;

class PeliculaController
{

    private static function verificarAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['login']) && $_SESSION['perfil'] === 3;
    }

    // Datos de una película con nombres en vez de ids, para el registro de auditoría
    private static function datosAuditoria($pelicula): array
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
            'titulo' => $pelicula->titulo_pelicula,
            'anio' => $pelicula->anyo_pelicula,
            'duracion_min' => $pelicula->duracion_pelicula,
            'clasificacion' => $buscar("SELECT nombre_tipo_clasificacion FROM tipos_clasificaciones WHERE id_tipo_clasificacion = ?", $pelicula->rela_tipo_clasificacion),
            'estado' => $buscar("SELECT nombre_estado_pelicula FROM estados_peliculas WHERE id_estado_pelicula = ?", $pelicula->rela_estado_pelicula),
            'trailer' => $pelicula->trailer_url,
            'imagen' => $pelicula->imagen_pelicula,
            // La sinopsis es larga: solo interesa saber si cambió
            'sinopsis' => md5((string)$pelicula->sinopsis_pelicula),
        ];
    }

    public static function index(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $fechaDesde = $_GET['fecha_desde'] ?? '';
        $fechaHasta = $_GET['fecha_hasta'] ?? '';

        $paginaActual = $_GET['pagina'] ?? 1;
        $registrosPorPagina = 4;
        $paginador = new Paginador($paginaActual, $registrosPorPagina);

        $db = \Models\ActiveRecord::getDB();

        $whereFechas = "";
        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(p.creado) BETWEEN '" . $db->escape_string($fechaDesde) . "' AND '" . $db->escape_string($fechaHasta) . "'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(p.creado) >= '" . $db->escape_string($fechaDesde) . "'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(p.creado) <= '" . $db->escape_string($fechaHasta) . "'";
        }

        $queryCount = "SELECT COUNT(*) as total FROM peliculas p {$whereFechas}";
        $totalRegistros = $db->query($queryCount)->fetch_object()->total;
        $paginador->setTotal($totalRegistros);

        $query = "SELECT 
        p.id_pelicula,
        p.titulo_pelicula,
        p.sinopsis_pelicula,
        p.anyo_pelicula,
        p.duracion_pelicula,
        p.imagen_pelicula,
        p.trailer_url,
        p.creado,
        e.nombre_estado_pelicula,
        t.nombre_tipo_clasificacion,
        GROUP_CONCAT(DISTINCT ip.nombre_idioma_pelicula SEPARATOR ', ') AS nombre_idioma_pelicula,
        GROUP_CONCAT(DISTINCT gp.genero_pelicula SEPARATOR ', ') AS generos
    FROM peliculas p
    LEFT JOIN estados_peliculas e ON p.rela_estado_pelicula = e.id_estado_pelicula
    LEFT JOIN tipos_clasificaciones t ON p.rela_tipo_clasificacion = t.id_tipo_clasificacion
    LEFT JOIN idiomas_por_peliculas ipp ON p.id_pelicula = ipp.rela_pelicula
    LEFT JOIN idiomas_peliculas ip ON ipp.rela_idioma = ip.id_idioma_pelicula
    LEFT JOIN generos_por_peliculas ghp ON p.id_pelicula = ghp.rela_pelicula
    LEFT JOIN generos_peliculas gp ON ghp.rela_genero_pelicula = gp.id_genero_pelicula
    {$whereFechas}
    GROUP BY p.id_pelicula
    ORDER BY p.id_pelicula DESC
    {$paginador->limit()}";

        $peliculas = Pelicula::consultarSQL($query);

        $router->render('administrador/peliculas/listado', [
            'peliculas' => $peliculas,
            'paginador' => $paginador,
            'usuario' => $_SESSION,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);
    }

    public static function crear(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        // Cargar datos para los selects
        $db = \Models\ActiveRecord::getDB();
        $clasificaciones = $db->query("SELECT * FROM tipos_clasificaciones")->fetch_all(MYSQLI_ASSOC);
        $idiomas = $db->query("SELECT * FROM idiomas_peliculas")->fetch_all(MYSQLI_ASSOC);
        $estados = $db->query("SELECT * FROM estados_peliculas")->fetch_all(MYSQLI_ASSOC);
        $generos = $db->query("SELECT * FROM generos_peliculas")->fetch_all(MYSQLI_ASSOC);

        $router->render('administrador/peliculas/crear', [
            'clasificaciones' => $clasificaciones,
            'idiomas' => $idiomas,
            'estados' => $estados,
            'generos' => $generos
        ]);
    }

    public static function guardar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        // Obtener datos del formulario
        $titulo = $_POST['titulo_pelicula'] ?? '';
        $sinopsis = $_POST['sinopsis_pelicula'] ?? '';
        $anyo = $_POST['anyo_pelicula'] ?? '';
        $duracion = $_POST['duracion_pelicula'] ?? '';
        $clasificacion = $_POST['rela_tipo_clasificacion'] ?? '';
        $idiomas = $_POST['idiomas'] ?? [];
        $estado = $_POST['rela_estado_pelicula'] ?? '';
        $trailer_url = trim($_POST['trailer_url'] ?? '');
        $generos = $_POST['generos'] ?? [];

        // Crear película para validar
        $pelicula = new Pelicula([
            'titulo_pelicula' => $titulo,
            'sinopsis_pelicula' => $sinopsis,
            'anyo_pelicula' => $anyo,
            'duracion_pelicula' => $duracion,
            'rela_tipo_clasificacion' => $clasificacion,
            'idiomas' => $idiomas,
            'rela_estado_pelicula' => $estado,
            'trailer_url' => $trailer_url,
            'generos' => $generos
        ]);

        // Obtener errores de validación del modelo
        $errores = $pelicula->validar();

        // Procesar imagen
        $nombreImagenFinal = '';
        if (!empty($_FILES['imagen_pelicula']['name'])) {
            $extension = \Classes\SubidaSegura::validarYObtenerExtension($_FILES['imagen_pelicula'], [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ]);

            if (!$extension) {
                $errores[] = 'El archivo debe ser una imagen válida (JPG, PNG, GIF o WEBP)';
            } else {
                $carpeta = __DIR__ . '/../public/assets/img/peliculas/';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }

                $nombreImagenFinal = 'poster_' . time() . '.' . $extension;

                if (!move_uploaded_file($_FILES['imagen_pelicula']['tmp_name'], $carpeta . $nombreImagenFinal)) {
                    $errores[] = 'Error al subir la imagen';
                }
            }
        }

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        // Actualizar los campos en el objeto película
        $pelicula->imagen_pelicula = $nombreImagenFinal;
        $pelicula->trailer_url = $trailer_url;

        $resultado = $pelicula->guardar();

        if ($resultado['resultado']) {
            $idPelicula = $resultado['id'];

            // Insertar géneros
            $db = \Models\ActiveRecord::getDB();
            foreach ($generos as $idGenero) {
                $stmt = $db->prepare("INSERT INTO generos_por_peliculas (rela_pelicula, rela_genero_pelicula) VALUES (?, ?)");
                $stmt->bind_param('ii', $idPelicula, $idGenero);
                $stmt->execute();
            }

            $datosNueva = self::datosAuditoria($pelicula);
            unset($datosNueva['sinopsis']);
            \Classes\Auditoria::registrar(
                'pelicula.crear',
                "Creó la película {$pelicula->titulo_pelicula}" . ($pelicula->anyo_pelicula ? " ({$pelicula->anyo_pelicula})" : ''),
                'peliculas',
                $idPelicula,
                null,
                $datosNueva
            );

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Película guardada correctamente',
                'redirigir' => '/administrador/peliculas/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar la película']);
        }

        // Insertar idiomas
        foreach ($idiomas as $idIdioma) {
            $stmt = $db->prepare("INSERT INTO idiomas_por_peliculas (rela_pelicula, rela_idioma) VALUES (?, ?)");
            $stmt->bind_param('ii', $idPelicula, $idIdioma);
            $stmt->execute();
        }
    }

    public static function editar(Router $router)
    {
        if (!self::verificarAdmin()) {
            header('Location: /');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /administrador/peliculas/listado');
            exit;
        }

        $pelicula = Pelicula::find($id);
        if (!$pelicula) {
            header('Location: /administrador/peliculas/listado');
            exit;
        }

        // Obtener datos para selects
        $db = Pelicula::getDB();
        $generos = $db->query("SELECT * FROM generos_peliculas")->fetch_all(MYSQLI_ASSOC);
        $clasificaciones = $db->query("SELECT * FROM tipos_clasificaciones")->fetch_all(MYSQLI_ASSOC);
        $idiomas = $db->query("SELECT * FROM idiomas_peliculas")->fetch_all(MYSQLI_ASSOC);
        $estados = $db->query("SELECT * FROM estados_peliculas")->fetch_all(MYSQLI_ASSOC); // AGREGADO

        // Idiomas actuales de la película
        $queryIdiomas = "SELECT rela_idioma FROM idiomas_por_peliculas WHERE rela_pelicula = {$pelicula->id_pelicula}";
        $resIdiomas = $db->query($queryIdiomas);
        $idiomasSeleccionados = [];
        while ($row = $resIdiomas->fetch_assoc()) {
            $idiomasSeleccionados[] = $row['rela_idioma'];
        }

        // Obtener géneros relacionados
        $stmt = $db->prepare("SELECT rela_genero_pelicula FROM generos_por_peliculas WHERE rela_pelicula = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['rela_genero_pelicula'];
        }
        $pelicula->generos_ids = $ids;

        $router->render('administrador/peliculas/editar', [
            'pelicula' => $pelicula,
            'generos' => $generos,
            'clasificaciones' => $clasificaciones,
            'idiomas' => $idiomas,
            'idiomasSeleccionados' => $idiomasSeleccionados,
            'estados' => $estados // AGREGADO
        ]);
    }

    public static function actualizar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        header('Content-Type: application/json');

        $id_pelicula = $_POST['id_pelicula'] ?? null;
        if (!$id_pelicula) {
            echo json_encode(['errores' => ['ID no válido']]);
            exit;
        }

        $pelicula = Pelicula::find($id_pelicula);
        if (!$pelicula) {
            echo json_encode(['ok' => false, 'mensaje' => 'Película no encontrada']);
            exit;
        }

        $datosAntes = self::datosAuditoria($pelicula);

        $pelicula->titulo_pelicula        = $_POST['titulo_pelicula'] ?? '';
        $pelicula->sinopsis_pelicula      = $_POST['sinopsis_pelicula'] ?? '';
        $pelicula->anyo_pelicula          = $_POST['anyo_pelicula'] ?? '';
        $pelicula->duracion_pelicula      = $_POST['duracion_pelicula'] ?? '';
        $pelicula->rela_tipo_clasificacion = $_POST['rela_tipo_clasificacion'] ?? '';
        $pelicula->rela_estado_pelicula   = $_POST['rela_estado_pelicula'] ?? '';
        $pelicula->trailer_url            = trim($_POST['trailer_url'] ?? '');
        $pelicula->generos                = $_POST['generos'] ?? [];

        $errores = $pelicula->validar();

        $nombreImagenFinal = $_POST['imagen_actual'] ?? '';
        if (!empty($_FILES['imagen_pelicula']['name'])) {
            $carpeta = __DIR__ . '/../public/assets/img/peliculas/';
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = strtolower(pathinfo($_FILES['imagen_pelicula']['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $tiposPermitidos)) {
                $errores[] = 'Solo se permiten archivos JPG, PNG o GIF';
            } else {
                if ($_FILES['imagen_pelicula']['size'] > 5242880) {
                    $errores[] = 'El archivo de imagen no puede exceder 5MB';
                } else {
                    $nombreImagenFinal = 'poster_' . time() . '.' . $extension;
                    if (!move_uploaded_file($_FILES['imagen_pelicula']['tmp_name'], $carpeta . $nombreImagenFinal)) {
                        $errores[] = 'Error al subir la nueva imagen';
                    }
                }
            }
        }

        if (!empty($errores)) {
            echo json_encode(['errores' => $errores]);
            return;
        }

        $pelicula->imagen_pelicula = $nombreImagenFinal;

        $resultado = $pelicula->actualizar();

        if ($resultado) {
            $db = \Models\ActiveRecord::getDB();

            // Actualizar géneros
            $db->query("DELETE FROM generos_por_peliculas WHERE rela_pelicula = {$id_pelicula}");
            foreach ($pelicula->generos as $idGenero) {
                $stmt = $db->prepare("INSERT INTO generos_por_peliculas (rela_pelicula, rela_genero_pelicula) VALUES (?, ?)");
                $stmt->bind_param('ii', $id_pelicula, $idGenero);
                $stmt->execute();
            }

            // Actualizar idiomas
            $db->query("DELETE FROM idiomas_por_peliculas WHERE rela_pelicula = {$id_pelicula}");
            $idiomas = $_POST['idiomas'] ?? [];
            foreach ($idiomas as $idIdioma) {
                $stmt = $db->prepare("INSERT INTO idiomas_por_peliculas (rela_pelicula, rela_idioma) VALUES (?, ?)");
                $stmt->bind_param('ii', $id_pelicula, $idIdioma);
                $stmt->execute();
            }

            [$antes, $despues] = \Classes\Auditoria::cambios($datosAntes, self::datosAuditoria($pelicula), array_keys($datosAntes));
            if ($antes) {
                // La sinopsis se compara por su huella: en el registro solo figura que cambió
                if (isset($despues['sinopsis'])) {
                    $antes['sinopsis'] = '(anterior)';
                    $despues['sinopsis'] = '(modificada)';
                }
                \Classes\Auditoria::registrar(
                    'pelicula.modificar',
                    "Modificó la película {$pelicula->titulo_pelicula} (" . implode(', ', array_keys($despues)) . ')',
                    'peliculas',
                    $id_pelicula,
                    $antes,
                    $despues
                );
            }

            echo json_encode([
                'ok'       => true,
                'mensaje'  => 'Película actualizada correctamente',
                'redirigir' => '/administrador/peliculas/listado'
            ]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar la película']);
        }
    }

    public static function eliminar()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        $json = json_decode(file_get_contents('php://input'), true);
        $id = $json['id'] ?? null;

        if (!$id) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
            return;
        }

        $peliculaBaja = Pelicula::find($id);
        $res = Pelicula::eliminarLogico($id);
        if ($res) {
            \Classes\Auditoria::registrar(
                'pelicula.baja',
                'Dio de baja la película ' . ($peliculaBaja->titulo_pelicula ?? "#$id"),
                'peliculas',
                $id
            );
            echo json_encode(['ok' => true, 'mensaje' => 'Película eliminada correctamente']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar']);
        }
    }

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

        $pelicula = Pelicula::buscarPorCampoUnico($termino);

        if ($pelicula) {
            echo json_encode(['ok' => true, 'pelicula' => $pelicula]);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'No se encontró la película']);
        }
    }
    public static function obtenerPeliculasProximamente()
    {
        $query = "
        SELECT 
            p.id_pelicula,
            p.titulo_pelicula,
            p.anyo_pelicula,
            p.duracion_pelicula,
            p.imagen_pelicula,
            p.trailer_url
        FROM peliculas p
        LEFT JOIN estados_peliculas e ON p.rela_estado_pelicula = e.id_estado_pelicula
        WHERE e.nombre_estado_pelicula = 'Próximamente'
        ORDER BY p.id_pelicula DESC
        ";

        return Pelicula::consultarSQL($query);
    }

    public static function obtenerImagen()
    {
        if (!self::verificarAdmin()) {
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents('php://input'), true);
            $idPelicula = intval($datos['id_pelicula'] ?? 0);

            if ($idPelicula > 0) {
                $db = \Models\ActiveRecord::getDB();
                $stmt = $db->prepare("SELECT imagen_pelicula FROM peliculas WHERE id_pelicula = ?");
                $stmt->bind_param('i', $idPelicula);
                $stmt->execute();
                $resultado = $stmt->get_result()->fetch_assoc();

                if ($resultado && !empty($resultado['imagen_pelicula'])) {
                    echo json_encode([
                        'ok' => true,
                        'imagen' => '/assets/img/peliculas/' . $resultado['imagen_pelicula']
                    ]);
                    return;
                }
            }
            echo json_encode(['ok' => false, 'error' => 'No se encontró imagen']);
        }
    }

    public static function obtenerTrailer()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $idPelicula = intval($input['id_pelicula'] ?? 0);

        if ($idPelicula <= 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID de película inválido']);
            return;
        }

        try {
            $db = \Models\ActiveRecord::getDB();
            $stmt = $db->prepare("SELECT trailer_url FROM peliculas WHERE id_pelicula = ?");
            $stmt->bind_param('i', $idPelicula);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();

            if ($resultado && !empty($resultado['trailer_url'])) {
                echo json_encode([
                    'ok' => true,
                    'trailer_url' => $resultado['trailer_url']
                ]);
            } else {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'No hay tráiler disponible para esta película'
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error del servidor'
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
                'id_pelicula' => 'ID',
                'titulo_pelicula' => 'Título',
                'sinopsis_pelicula' => 'Sinopsis',
                'anyo_pelicula' => 'Año',
                'duracion_pelicula' => 'Duración (min)',
                'nombre_tipo_clasificacion' => 'Clasificación',
                'nombre_idioma_pelicula' => 'Idioma',
                'generos' => 'Géneros',
                'nombre_estado_pelicula' => 'Estado',
                'fecha_agregada' => 'Fecha Agregada'
            ];

            // Capturar filtros de fecha
            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerPeliculasFiltradas($fechaDesde, $fechaHasta);

            // Generar nombre descriptivo
            $nombreArchivo = self::generarNombreArchivo('peliculas', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Películas', $fechaDesde, $fechaHasta);

            ExportadorDatos::exportarExcel($datos, $columnas, $nombreArchivo, $titulo);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN PELÍCULAS EXCEL: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte Excel: ' . $e->getMessage();
            header('Location: /administrador/peliculas/listado');
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
                'id_pelicula' => 'ID',
                'titulo_pelicula' => 'Título',
                'imagen_pelicula' => 'Imagen',
                'anyo_pelicula' => 'Año',
                'duracion_pelicula' => 'Duración',
                'nombre_tipo_clasificacion' => 'Clasificación',
                'nombre_idioma_pelicula' => 'Idioma',
                'generos' => 'Géneros',
                'nombre_estado_pelicula' => 'Estado',
                'fecha_agregada' => 'Fecha Agregada'
            ];

            // Capturar filtros de fecha
            $fechaDesde = $_GET['fecha_desde'] ?? '';
            $fechaHasta = $_GET['fecha_hasta'] ?? '';

            $datos = self::obtenerPeliculasFiltradas($fechaDesde, $fechaHasta);

            // Generar nombre descriptivo
            $nombreArchivo = self::generarNombreArchivo('peliculas', $fechaDesde, $fechaHasta);
            $titulo = self::generarTituloReporte('Películas', $fechaDesde, $fechaHasta);

            $config = [
                'orientacion' => 'L',
                'empresa' => 'CINFSA - Sistema de Gestión Cinematográfica',
                'mostrar_imagenes' => true,
                'columna_imagen' => 'imagen_pelicula',
                'ruta_imagenes' => '/assets/img/peliculas/',
                'ancho_imagen' => 25,
                'alto_imagen' => 30
            ];

            ExportadorDatos::exportarPDF($datos, $columnas, $nombreArchivo, $titulo, $config);
        } catch (\Exception $e) {
            error_log("ERROR EXPORTACIÓN PELÍCULAS PDF: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte PDF: ' . $e->getMessage();
            header('Location: /administrador/peliculas/listado');
            exit;
        }
    }

    /**
     * Obtener películas filtradas por fecha de creación (campo 'creado')
     */
    private static function obtenerPeliculasFiltradas($fechaDesde, $fechaHasta)
    {
        $db = \Models\ActiveRecord::getDB();

        $whereFechas = "";
        $fechaDesde = $db->escape_string($fechaDesde);
        $fechaHasta = $db->escape_string($fechaHasta);

        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(p.creado) BETWEEN '{$fechaDesde}' AND '{$fechaHasta}'";
        } elseif (!empty($fechaDesde)) {
            $whereFechas = " WHERE DATE(p.creado) >= '{$fechaDesde}'";
        } elseif (!empty($fechaHasta)) {
            $whereFechas = " WHERE DATE(p.creado) <= '{$fechaHasta}'";
        }

        $query = "SELECT 
                    p.id_pelicula,
                    p.titulo_pelicula,
                    p.sinopsis_pelicula,
                    p.anyo_pelicula,
                    p.duracion_pelicula,
                    p.imagen_pelicula,
                    p.creado,
                    DATE_FORMAT(p.creado, '%d/%m/%Y %H:%i') as fecha_agregada,
                    e.nombre_estado_pelicula,
                    t.nombre_tipo_clasificacion,
                    i.nombre_idioma_pelicula,
                    GROUP_CONCAT(gp.genero_pelicula SEPARATOR ', ') AS generos
                FROM peliculas p
                LEFT JOIN estados_peliculas e ON p.rela_estado_pelicula = e.id_estado_pelicula
                LEFT JOIN tipos_clasificaciones t ON p.rela_tipo_clasificacion = t.id_tipo_clasificacion
                LEFT JOIN idiomas_peliculas i ON p.rela_idioma_pelicula = i.id_idioma_pelicula
                LEFT JOIN generos_por_peliculas ghp ON p.id_pelicula = ghp.rela_pelicula
                LEFT JOIN generos_peliculas gp ON ghp.rela_genero_pelicula = gp.id_genero_pelicula
                {$whereFechas}
                GROUP BY p.id_pelicula
                ORDER BY p.creado DESC";

        $resultado = $db->query($query);
        $datos = [];

        while ($row = $resultado->fetch_assoc()) {
            $datos[] = $row;
        }

        return $datos;
    }

    /**
     * Generar nombre de archivo descriptivo
     */
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

    /**
     * Generar título de reporte descriptivo
     */
    private static function generarTituloReporte($entidad, $fechaDesde, $fechaHasta)
    {
        $titulo = "Listado de {$entidad}";

        if (!empty($fechaDesde) && !empty($fechaHasta)) {
            $titulo .= " - Agregadas del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
        } elseif (!empty($fechaDesde)) {
            $titulo .= " - Agregadas desde " . date('d/m/Y', strtotime($fechaDesde));
        } elseif (!empty($fechaHasta)) {
            $titulo .= " - Agregadas hasta " . date('d/m/Y', strtotime($fechaHasta));
        } else {
            $titulo .= " - Completo";
        }

        return $titulo;
    }


    private static function limpiarNombreArchivo($nombre)
    {
        // Remover caracteres especiales y espacios
        $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
        // Limitar longitud
        return substr($nombre, 0, 20);
    }
}
