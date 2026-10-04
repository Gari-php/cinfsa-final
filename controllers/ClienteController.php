<?php

namespace Controllers;

use Models\Usuario;
use Models\Funciones;
use Models\Pelicula;
use Models\Producto;
use Models\Maquina;
use Models\Ficha;
use Models\Productos;
use MVC\Router;
use Models\Butaca;
use Classes\Email;

class ClienteController
{
    // ⭐ MÉTODO HELPER PARA OBTENER PERFIL DEL USUARIO ACTUAL
    private static function obtenerPerfilUsuarioActual()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return null;
        }

        $id_usuario = $_SESSION['id_usuario'];

        // ⭐ USAR CONSULTA DIRECTA EN LUGAR DE consultarSQL()
        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                u.id_usuario,
                u.nombre_usuario,
                u.email,
                u.foto_perfil,
                p.nombre_persona,
                p.apellido_persona,
                p.rela_sexo,
                s.nombre_sexo,
                per.nombre_perfil
            FROM usuarios u
            LEFT JOIN personas p ON u.id_persona = p.id_persona
            LEFT JOIN sexo s ON p.rela_sexo = s.id_sexo
            LEFT JOIN perfiles per ON u.rela_perfil = per.id_perfiles
            WHERE u.id_usuario = '{$id_usuario}'
            LIMIT 1";

        $resultado = $db->query($query);

        if ($resultado && $resultado->num_rows > 0) {
            return $resultado->fetch_assoc(); // ⭐ RETORNA ARRAY ASOCIATIVO
        }

        return null;
    }

    public static function apiObtenerPorDia()
    {
        header('Content-Type: application/json');

        $dia = $_GET['dia'] ?? $_POST['dia'] ?? null;

        if (!$dia) {
            echo json_encode(['ok' => false, 'mensaje' => 'Día no especificado']);
            return;
        }

        $diasValidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

        if (!in_array(strtolower($dia), $diasValidos)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Día no válido']);
            return;
        }

        try {
            $funciones = Funciones::obtenerFuncionesPorDia($dia);

            $funcionesArray = [];
            foreach ($funciones as $funcion) {
                $funcionesArray[] = [
                    'id_funcion' => $funcion->id_funcion,
                    'titulo_pelicula' => $funcion->titulo_pelicula,
                    'imagen_pelicula' => $funcion->imagen_pelicula,
                    'fecha_hora' => $funcion->fecha_hora,
                    'turno_horario' => $funcion->turno_horario,
                    'nombre_sala' => $funcion->nombre_sala,
                    'tipo_entrada_desc' => $funcion->tipo_entrada_desc,
                    'precio_entrada' => $funcion->precio_entrada,
                    'clasificacion_texto' => $funcion->clasificacion_texto,
                    'duracion_pelicula' => $funcion->duracion_pelicula,
                    'fecha_formato' => date('d/m/Y', strtotime($funcion->fecha_hora))
                ];
            }

            echo json_encode([
                'ok' => true,
                'funciones' => $funcionesArray,
                'total' => count($funcionesArray),
                'dia' => ucfirst($dia)
            ]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener funciones']);
        }
    }


    public static function menu(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login'])) {
            header('Location: /');
            exit;
        }

        if ($_SESSION['perfil'] !== 1) {
            $redirigir = match ($_SESSION['perfil']) {
                2 => '/vendedor',
                3 => '/administrador',
                default => '/'
            };
            header("Location: $redirigir");
            exit;
        }

        // ⭐ OBTENER PERFIL PARA EL LAYOUT
        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/menu', [
            'usuario' => $_SESSION,
            'perfil' => $perfil  // ⭐ AGREGAR ESTO
        ]);
    }

    public static function funciones(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 1) {
            header('Location: /');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();

        $query = "SELECT 
                f.id_funcion,
                f.fecha_hora,
                f.rela_salas,
                p.id_pelicula,
                p.titulo_pelicula,
                p.imagen_pelicula,
                p.sinopsis_pelicula,
                p.duracion_pelicula,
                p.trailer_url,
                s.id_sala,
                t.turno_horario,
                te.tipo_entrada_desc,
                te.precio_entrada,
                ip.nombre_idioma_pelicula,
                tc.nombre_tipo_clasificacion,
                ep.nombre_estado_pelicula,
                DAYOFWEEK(f.fecha_hora) as dia_semana
              FROM funciones f
              INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
              INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula
              INNER JOIN salas s ON f.rela_salas = s.id_sala
              INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
              LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
              LEFT JOIN idiomas_peliculas ip ON f.rela_idioma = ip.id_idioma_pelicula
              LEFT JOIN tipos_clasificaciones tc ON p.rela_tipo_clasificacion = tc.id_tipo_clasificacion
              WHERE f.estado = 1
              AND ep.nombre_estado_pelicula IN ('Emision', 'Proximamente')
              AND f.fecha_hora >= CURDATE()
              ORDER BY p.id_pelicula ASC, f.fecha_hora ASC";

        $resultado = $db->query($query);

        // Agrupar por película
        $peliculas = [];
        $funcionesPorDia = [
            'lunes' => [],
            'martes' => [],
            'miercoles' => [],
            'jueves' => [],
            'viernes' => [],
            'sabado' => [],
            'domingo' => []
        ];
        $mapaDias = [2 => 'lunes', 3 => 'martes', 4 => 'miercoles', 5 => 'jueves', 6 => 'viernes', 7 => 'sabado', 1 => 'domingo'];

        while ($row = $resultado->fetch_assoc()) {
            $idPelicula = $row['id_pelicula'];

            if (!isset($peliculas[$idPelicula])) {
                $peliculas[$idPelicula] = [
                    'id_pelicula'            => $idPelicula,
                    'titulo_pelicula'        => $row['titulo_pelicula'],
                    'imagen_pelicula'        => $row['imagen_pelicula'],
                    'sinopsis_pelicula'      => $row['sinopsis_pelicula'],
                    'duracion_pelicula'      => $row['duracion_pelicula'],
                    'trailer_url'            => $row['trailer_url'],
                    'nombre_tipo_clasificacion' => $row['nombre_tipo_clasificacion'],
                    'nombre_estado_pelicula' => $row['nombre_estado_pelicula'],
                    'funciones'              => []
                ];
            }

            $funcion = [
                'id_funcion'       => $row['id_funcion'],
                'fecha_hora'       => $row['fecha_hora'],
                'id_sala'          => $row['id_sala'],
                'turno_horario'    => $row['turno_horario'],
                'tipo_entrada_desc' => $row['tipo_entrada_desc'],
                'precio_entrada'   => $row['precio_entrada'],
                'idioma'           => $row['nombre_idioma_pelicula'],
                'dia_semana'       => $row['dia_semana']
            ];

            $peliculas[$idPelicula]['funciones'][] = $funcion;

            $diaNombre = $mapaDias[$row['dia_semana']] ?? null;
            if ($diaNombre) {
                $funcionesPorDia[$diaNombre][] = $funcion;
            }
        }

        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/funciones', [
            'peliculas'       => array_values($peliculas),
            'funcionesPorDia' => $funcionesPorDia,
            'perfil'          => $perfil,
            'usuario'         => $_SESSION
        ]);
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

    public static function funcionesPorDia(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 1) {
            header('Location: /');
            exit;
        }

        $dia = $_GET['dia'] ?? null;
        $diasValidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

        if (!$dia || !in_array(strtolower($dia), $diasValidos)) {
            header('Location: /funciones');
            exit;
        }

        $funciones = Funciones::obtenerFuncionesPorDia($dia);
        $funcionesPorDia = Funciones::obtenerFuncionesPorDiasSemana();

        $proximasFechas = [];
        $diasSemana = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'domingo' => 0];
        $hoy = new \DateTime();

        foreach ($diasSemana as $d => $numeroDia) {
            $fecha = clone $hoy;
            $diasHasta = ($numeroDia - $fecha->format('w') + 7) % 7;
            if ($diasHasta == 0) $diasHasta = 7;
            $fecha->modify("+{$diasHasta} days");
            $proximasFechas[$d] = $fecha->format('Y-m-d');
        }

        // ⭐ OBTENER PERFIL PARA EL LAYOUT
        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/funciones', [
            'funciones' => $funciones,
            'funcionesPorDia' => $funcionesPorDia,
            'proximasFechas' => $proximasFechas,
            'diaSeleccionado' => strtolower($dia),
            'usuario' => $_SESSION,
            'perfil' => $perfil  // ⭐ AGREGAR ESTO
        ]);
    }

    public static function salaJuegos(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 1) {
            header('Location: /');
            exit;
        }

        $maquinasData = Maquina::obtenerMaquinasConFichas();

        // ⭐ OBTENER PERFIL PARA EL LAYOUT
        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/juegos', [
            'maquinasDisponibles' => $maquinasData['disponibles'],
            'maquinasNoDisponibles' => $maquinasData['no_disponibles'],
            'usuario' => $_SESSION,
            'perfil' => $perfil  // ⭐ AGREGAR ESTO
        ]);
    }

    public static function cantina(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 1) {
            header('Location: /');
            exit;
        }

        $productos = Productos::obtenerProductosActivos();

        // ⭐ OBTENER PERFIL PARA EL LAYOUT
        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/cantina', [
            'productos' => $productos,
            'usuario' => $_SESSION,
            'perfil' => $perfil  // ⭐ AGREGAR ESTO
        ]);
    }

    public static function peliculas(Router $router)
    {
        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.sinopsis_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                p.imagen_pelicula,
                p.trailer_url,
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
            WHERE p.rela_estado_pelicula IN (1, 2)
            GROUP BY p.id_pelicula
            ORDER BY p.rela_estado_pelicula ASC, p.id_pelicula DESC
        ";

        $peliculas = Pelicula::consultarSQL($query);

        // ⭐ OBTENER PERFIL PARA EL LAYOUT
        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/peliculas', [
            'peliculas' => $peliculas,
            'perfil' => $perfil  // ⭐ AGREGAR ESTO
        ]);
    }

    public static function verButacasFuncion(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 1) {
            header('Location: /');
            exit;
        }

        $idFuncion = $_GET['id_funcion'] ?? null;

        if (!$idFuncion || !is_numeric($idFuncion)) {
            header('Location: /funciones');
            exit;
        }

        $db = \Models\ActiveRecord::getDB();
        $queryFuncion = "SELECT 
                            f.*,
                            p.titulo_pelicula,
                            p.imagen_pelicula,
                            t.turno_horario,
                            te.tipo_entrada_desc,
                            te.precio_entrada
                        FROM funciones f
                        INNER JOIN peliculas p ON f.rela_peliculas = p.id_pelicula
                        INNER JOIN turnos t ON f.rela_turnos = t.id_turnos
                        LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
                        WHERE f.id_funcion = '$idFuncion' AND f.estado = 1";

        $resultado = $db->query($queryFuncion);

        if (!$resultado || $resultado->num_rows === 0) {
            header('Location: /funciones');
            exit;
        }

        $funcion = $resultado->fetch_assoc();

        // ⭐ OBTENER PERFIL PARA EL LAYOUT
        $perfil = [self::obtenerPerfilUsuarioActual()];

        $router->render('cliente/butacas', [
            'funcion' => $funcion,
            'usuario' => $_SESSION,
            'perfil' => $perfil  // ⭐ AGREGAR ESTO
        ]);
    }

    public static function apiObtenerButacasFuncion()
    {
        header('Content-Type: application/json');

        $idFuncion = $_GET['id_funcion'] ?? null;

        if (!$idFuncion || !is_numeric($idFuncion)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
            return;
        }

        $db = \Models\ActiveRecord::getDB();

        try {
            // Obtener datos de función
            $queryFuncion = "SELECT f.id_funcion, f.rela_salas, s.capacidad_sala, 
                               s.filas_sala, s.columnas_sala
                        FROM funciones f
                        INNER JOIN salas s ON f.rela_salas = s.id_sala
                        WHERE f.id_funcion = ? AND f.estado = 1";
            $stmt = $db->prepare($queryFuncion);
            $stmt->bind_param("i", $idFuncion);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Función no encontrada']);
                return;
            }

            $funcion = $resultado->fetch_assoc();
            $idSala = $funcion['rela_salas'];

            // Obtener butacas con LEFT JOIN a butacas_vendidas
            $queryButacas = "SELECT 
                b.id_butaca,
                b.fila_butaca,
                b.numero_butaca,
                b.rela_estado_butaca,
                CASE
                    WHEN bv.id_venta_butaca IS NOT NULL AND (e.id_entrada IS NULL OR e.estado != -1) THEN 3
                    WHEN EXISTS (
                        SELECT 1 FROM reservas_butacas rb
                        WHERE rb.id_butaca = b.id_butaca AND rb.id_funcion = ?
                          AND rb.estado_reserva = 'temporal' AND rb.fecha_expiracion > NOW()
                          AND (rb.id_usuario IS NULL OR rb.id_usuario <> ?)
                    ) THEN 3
                    WHEN b.rela_estado_butaca = 2 THEN 2
                    ELSE 1
                END as estado,
                CONCAT('F', b.fila_butaca, '-C', b.numero_butaca) as label
            FROM butacas b
            LEFT JOIN butacas_vendidas bv ON bv.id_butaca = b.id_butaca
                AND bv.id_funcion = ?
            LEFT JOIN entradas e ON e.id_entrada = bv.id_entrada
            WHERE b.rela_salas = ?
            ORDER BY b.fila_butaca, b.numero_butaca";

            // Las butacas que otro cliente está pagando se muestran ocupadas (las propias no)
            $idUsuarioActual = (int)($_SESSION['id_usuario'] ?? 0);
            $stmt2 = $db->prepare($queryButacas);
            $stmt2->bind_param("iiii", $idFuncion, $idUsuarioActual, $idFuncion, $idSala);
            $stmt2->execute();
            $resultado2 = $stmt2->get_result();

            $butacas = [];
            while ($row = $resultado2->fetch_assoc()) {
                $butacas[] = [
                    'id' => (int)$row['id_butaca'],
                    'fila' => (int)$row['fila_butaca'],
                    'numero' => (int)$row['numero_butaca'],
                    'estado' => (int)$row['estado'],
                    'label' => $row['label']
                ];
            }

            echo json_encode([
                'ok' => true,
                'layout' => [
                    'sala' => [
                        'id' => (int)$idSala,
                        'filas' => (int)$funcion['filas_sala'],
                        'columnas' => (int)$funcion['columnas_sala'],
                        'capacidad' => (int)$funcion['capacidad_sala']
                    ],
                    'butacas' => $butacas
                ]
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al obtener butacas',
                'error' => $e->getMessage()
            ]);
        }
    }

    public static function apiReservarButacas()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario no autenticado']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $butacasIds = $datos['butacas'] ?? [];
        $idFuncion = $datos['id_funcion'] ?? null;
        $idUsuario = $_SESSION['id_usuario'];

        if (empty($butacasIds) || !$idFuncion) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
            return;
        }

        try {
            $db = \Models\ActiveRecord::getDB();

            // ⭐ VERIFICAR QUE LAS BUTACAS NO ESTÉN VENDIDAS NI SIENDO PAGADAS POR OTRO CLIENTE
            $idsButacas = implode(',', array_map('intval', $butacasIds));
            $pares = array_map(fn($id) => ['id_butaca' => $id, 'id_funcion' => $idFuncion], $butacasIds);
            $noDisponibles = \Models\Butaca::noDisponibles($pares, (int)$idUsuario);

            if (in_array('vendida', $noDisponibles, true)) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Una o más butacas ya fueron vendidas. Recarga la página.'
                ]);
                return;
            }
            if ($noDisponibles) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Una o más butacas están siendo compradas por otro cliente. Elegí otras o probá en unos minutos.'
                ]);
                return;
            }

            // ⭐ VERIFICAR QUE NINGUNA BUTACA ESTÉ BLOQUEADA POR EL ADMINISTRADOR
            $queryBloqueadas = "SELECT id_butaca FROM butacas WHERE id_butaca IN ($idsButacas) AND rela_estado_butaca = 2";
            $bloqueadas = $db->query($queryBloqueadas);

            if ($bloqueadas && $bloqueadas->num_rows > 0) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Una o más butacas seleccionadas no están disponibles. Recarga la página.'
                ]);
                return;
            }

            // Obtener precio de la función
            $queryFuncion = "SELECT te.precio_entrada 
                        FROM funciones f
                        LEFT JOIN tipo_entradas te ON f.rela_tipo_entrada = te.id_tipo_entrada
                        WHERE f.id_funcion = ?";
            $stmtFunc = $db->prepare($queryFuncion);
            $stmtFunc->execute([$idFuncion]);
            $resultFunc = $stmtFunc->get_result();

            if ($resultFunc->num_rows === 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Función no encontrada']);
                return;
            }

            $funcion = $resultFunc->fetch_assoc();
            $precio = $funcion['precio_entrada'] ?? 2500;

            // ⭐ AGREGAR DIRECTAMENTE AL CARRITO (SIN RESERVAS)
            foreach ($butacasIds as $idButaca) {
                \Models\Carrito::agregarItem(
                    $idUsuario,
                    $idFuncion,
                    'butacas',
                    1,
                    $precio,
                    $idButaca,
                    $idFuncion
                );
            }

            echo json_encode([
                'ok' => true,
                'mensaje' => count($butacasIds) . ' butaca(s) agregada(s) al carrito',
                'butacas_reservadas' => count($butacasIds)
            ]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    public static function mi_perfil(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login'])) {
            header('Location: /');
            exit;
        }

        // ⭐ CAMBIAR ESTO: Obtener solo el perfil del usuario actual
        $perfilActual = self::obtenerPerfilUsuarioActual();

        // Para mantener compatibilidad con el layout, lo pasamos como array
        $perfil = [$perfilActual];

        $router->render('cliente/perfil', [
            'perfil' => $perfil,
            'perfilActual' => $perfilActual  // Por si lo necesitas individualmente
        ]);
    }

    // ... resto de métodos (actualizar_perfil, apiObtenerButacasFuncion, etc.) permanecen igual

    public static function actualizar_perfil()
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['id_usuario'])) {
                throw new \Exception('Sesión expirada');
            }

            $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
            $nombre_usuario = trim(filter_input(INPUT_POST, 'nombre_usuario', FILTER_UNSAFE_RAW));
            $nombre_persona = trim(filter_input(INPUT_POST, 'nombre_persona', FILTER_UNSAFE_RAW));
            $apellido_persona = trim(filter_input(INPUT_POST, 'apellido_persona', FILTER_UNSAFE_RAW));
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $sexo = filter_input(INPUT_POST, 'sexo', FILTER_VALIDATE_INT);

            $nombre_usuario = htmlspecialchars($nombre_usuario, ENT_QUOTES, 'UTF-8');
            $nombre_persona = htmlspecialchars($nombre_persona, ENT_QUOTES, 'UTF-8');
            $apellido_persona = htmlspecialchars($apellido_persona, ENT_QUOTES, 'UTF-8');

            if ($id_usuario != $_SESSION['id_usuario']) {
                throw new \Exception('No autorizado para editar este perfil');
            }

            if (
                !$id_usuario || empty($nombre_usuario) || empty($nombre_persona) ||
                empty($apellido_persona) || !$email || !$sexo
            ) {
                throw new \Exception('Todos los campos son obligatorios');
            }

            if (strlen($nombre_usuario) < 3) {
                throw new \Exception('El nombre de usuario debe tener al menos 3 caracteres');
            }

            if (strlen($nombre_persona) < 2) {
                throw new \Exception('El nombre debe tener al menos 2 caracteres');
            }

            if (strlen($apellido_persona) < 2) {
                throw new \Exception('El apellido debe tener al menos 2 caracteres');
            }

            if (!in_array($sexo, [1, 2, 3])) {
                throw new \Exception('Valor de sexo inválido');
            }

            $foto_base64 = null;
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $foto_base64 = self::procesarFotoPerfil($_FILES['foto_perfil']);
            }

            $datos = [
                'nombre_usuario' => $nombre_usuario,
                'nombre_persona' => $nombre_persona,
                'apellido_persona' => $apellido_persona,
                'email' => $email,
                'sexo' => $sexo,
                'foto_perfil' => $foto_base64
            ];

            $resultado = Usuario::actualizarPerfil($id_usuario, $datos);

            if ($resultado) {
                $_SESSION['nombre_usuario'] = $nombre_usuario;

                echo json_encode([
                    'success' => true,
                    'message' => 'Perfil actualizado correctamente'
                ]);
            } else {
                throw new \Exception('Error al actualizar los datos en la base de datos');
            }
        } catch (\Exception $e) {
            error_log("Error en actualizar_perfil: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }

    private static function procesarFotoPerfil($archivo)
    {
        try {
            if (!\extension_loaded('gd')) {
                throw new \Exception('La extensión GD no está habilitada en PHP');
            }

            $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = \finfo_open(FILEINFO_MIME_TYPE);
            $tipo_real = \finfo_file($finfo, $archivo['tmp_name']);
            \finfo_close($finfo);

            if (!in_array($tipo_real, $tipos_permitidos)) {
                throw new \Exception('Tipo de archivo no permitido. Solo JPG, PNG y GIF');
            }

            if ($archivo['size'] > 2 * 1024 * 1024) {
                throw new \Exception('El archivo es demasiado grande. Máximo 2MB');
            }

            $imagen_redimensionada = self::redimensionarImagen($archivo['tmp_name'], 300, 300);

            if ($imagen_redimensionada) {
                return base64_encode($imagen_redimensionada);
            } else {
                return base64_encode(file_get_contents($archivo['tmp_name']));
            }
        } catch (\Exception $e) {
            throw new \Exception('Error al procesar la imagen: ' . $e->getMessage());
        }
    }

    private static function redimensionarImagen($ruta_archivo, $ancho_max, $alto_max)
    {
        try {
            $info_imagen = \getimagesize($ruta_archivo);
            if (!$info_imagen) {
                return false;
            }

            $tipo = $info_imagen[2];
            $ancho_original = $info_imagen[0];
            $alto_original = $info_imagen[1];

            if ($ancho_original <= $ancho_max && $alto_original <= $alto_max) {
                return file_get_contents($ruta_archivo);
            }

            switch ($tipo) {
                case IMAGETYPE_JPEG:
                    $imagen_original = \imagecreatefromjpeg($ruta_archivo);
                    break;
                case IMAGETYPE_PNG:
                    $imagen_original = \imagecreatefrompng($ruta_archivo);
                    break;
                case IMAGETYPE_GIF:
                    $imagen_original = \imagecreatefromgif($ruta_archivo);
                    break;
                default:
                    return false;
            }

            if (!$imagen_original) {
                return false;
            }

            $ratio_original = $ancho_original / $alto_original;
            $ratio_nuevo = $ancho_max / $alto_max;

            if ($ratio_original > $ratio_nuevo) {
                $nuevo_ancho = $ancho_max;
                $nuevo_alto = round($ancho_max / $ratio_original);
            } else {
                $nuevo_alto = $alto_max;
                $nuevo_ancho = round($alto_max * $ratio_original);
            }

            $nueva_imagen = \imagecreatetruecolor($nuevo_ancho, $nuevo_alto);

            if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                \imagealphablending($nueva_imagen, false);
                \imagesavealpha($nueva_imagen, true);
                $color_transparente = \imagecolorallocatealpha($nueva_imagen, 0, 0, 0, 127);
                \imagefill($nueva_imagen, 0, 0, $color_transparente);
            }

            \imagecopyresampled(
                $nueva_imagen,
                $imagen_original,
                0,
                0,
                0,
                0,
                $nuevo_ancho,
                $nuevo_alto,
                $ancho_original,
                $alto_original
            );

            ob_start();
            switch ($tipo) {
                case IMAGETYPE_JPEG:
                    \imagejpeg($nueva_imagen, null, 85);
                    break;
                case IMAGETYPE_PNG:
                    \imagepng($nueva_imagen);
                    break;
                case IMAGETYPE_GIF:
                    \imagegif($nueva_imagen);
                    break;
            }
            $imagen_data = ob_get_contents();
            ob_end_clean();

            \imagedestroy($imagen_original);
            \imagedestroy($nueva_imagen);

            return $imagen_data;
        } catch (\Exception $e) {
            error_log("Error al redimensionar imagen: " . $e->getMessage());
            return false;
        }
    }

    public static function cambiarPassword()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        $passwordActual = $datos['password_actual'] ?? '';
        $passwordNueva = $datos['password_nueva'] ?? '';
        $passwordConfirmar = $datos['password_confirmar'] ?? '';

        if (empty($passwordActual) || empty($passwordNueva) || empty($passwordConfirmar)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Todos los campos son obligatorios']);
            return;
        }

        if ($passwordNueva !== $passwordConfirmar) {
            echo json_encode(['ok' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden']);
            return;
        }

        $resultado = Usuario::cambiarPassword($_SESSION['id_usuario'], $passwordActual, $passwordNueva);

        if ($resultado['ok']) {
            try {
                $email = new Email($resultado['email'], $resultado['nombre_usuario'], '');
                $email->enviarNotificacionCambioPassword();
            } catch (\Exception $e) {
                error_log("Error al enviar notificación de cambio de contraseña: " . $e->getMessage());
            }
        }

        echo json_encode($resultado);
    }

    public static function misCompras()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || !isset($_SESSION['id_usuario'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
            return;
        }

        $idUsuario = $_SESSION['id_usuario'];

        try {
            $entradas = \Models\Entrada::obtenerComprasWebPorUsuario($idUsuario);

            // QR solo de las vigentes: es lo que se muestra en la puerta de la sala
            foreach ($entradas as &$entrada) {
                $entrada['qr'] = null;
                if ($entrada['estado_vigencia'] === 'vigente') {
                    $codigo = \Models\Entrada::asegurarCodigoAcceso((int)$entrada['id_entrada']);
                    if ($codigo) {
                        $entrada['qr'] = \Classes\CodigoQR::dataUri(\Classes\CodigoQR::textoEntrada($codigo), 8);
                        $entrada['codigo_corto'] = \Models\Entrada::codigoCorto($codigo);
                    }
                }
            }
            unset($entrada);

            // Cantina y fichas: un pedido por compra web, con su QR de retiro mientras quede algo
            $pedidos = [];
            foreach (\Models\RetiroOrden::pedidosDelCliente((int)$idUsuario) as $orden) {
                $items = \Models\RetiroOrden::items((int)$orden['id_orden']);
                $quedan = array_sum(array_column($items, 'quedan'));

                $qr = null;
                $codigoCorto = null;
                if ($quedan > 0) {
                    $codigo = \Models\RetiroOrden::asegurarCodigoRetiro((int)$orden['id_orden']);
                    if ($codigo) {
                        $qr = \Classes\CodigoQR::dataUri(\Classes\CodigoQR::textoOrden($codigo), 8);
                        $codigoCorto = \Models\RetiroOrden::codigoCorto($codigo);
                    }
                }

                $pedidos[] = [
                    'id_orden' => (int)$orden['id_orden'],
                    'numero_orden' => $orden['numero_orden'],
                    'fecha' => $orden['fecha'],
                    'total' => array_sum(array_map(fn($i) => (float)$i['subtotal'], $items)),
                    'retirado' => $quedan === 0,
                    'qr' => $qr,
                    'codigo_corto' => $codigoCorto,
                    'productos' => array_map(fn($i) => [
                        'nombre' => $i['nombre_producto'],
                        'comprado' => $i['comprado'],
                        'entregado' => $i['entregado'],
                        'quedan' => $i['quedan'],
                    ], $items),
                ];
            }

            echo json_encode([
                'ok' => true,
                'entradas' => $entradas,
                'pedidos' => $pedidos
            ]);
        } catch (\Exception $e) {
            error_log('Error en misCompras: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener las compras']);
        }
    }

    public static function calificarPeliculas(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['login'])) {
            header('Location: /');
            exit;
        }

        $peliculas = \Models\Resena::obtenerPeliculasCalificables($_SESSION['id_usuario']);

        $router->render('cliente/calificar-peliculas', [
            'peliculas' => $peliculas
        ]);
    }

    public static function guardarResena()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['login']) || !isset($_SESSION['id_usuario'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada']);
            return;
        }

        $datos = json_decode(file_get_contents('php://input'), true);
        $idPelicula = $datos['id_pelicula'] ?? null;
        $puntuacion = $datos['puntuacion'] ?? null;
        $comentario = trim($datos['comentario'] ?? '');

        if (!$idPelicula || !is_numeric($idPelicula)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Película no válida']);
            return;
        }

        if ($puntuacion === null || !is_numeric($puntuacion) || $puntuacion < 0 || $puntuacion > 10) {
            echo json_encode(['ok' => false, 'mensaje' => 'La puntuación debe ser un número entre 0.0 y 10.0']);
            return;
        }

        $resena = new \Models\Resena([
            'rela_usuario' => $_SESSION['id_usuario'],
            'rela_pelicula' => $idPelicula,
            'puntuacion' => round((float)$puntuacion, 1),
            'comentario' => $comentario !== '' ? $comentario : null
        ]);

        $resultado = $resena->guardar();

        if ($resultado['resultado']) {
            echo json_encode(['ok' => true, 'mensaje' => '¡Gracias por tu calificación!']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => $resultado['error'] ?? 'Error al guardar la calificación']);
        }
    }

    public static function verCalificaciones(Router $router)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['login'])) {
            header('Location: /');
            exit;
        }

        $peliculas = \Models\Resena::obtenerEstadisticasPeliculas();

        $router->render('cliente/ver-calificaciones', [
            'peliculas' => $peliculas
        ]);
    }

    // ... resto de métodos API permanecen igual
}
