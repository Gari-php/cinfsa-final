<?php
namespace Models;

/**
 * Modelo de Película
 *
 * @property int $id_pelicula
 * @property string $titulo_pelicula
 * @property string $sinopsis_pelicula
 * @property int $anyo_pelicula
 * @property int $duracion_pelicula
 * @property int $rela_estado_pelicula
 * @property int $rela_tipo_clasificacion
 * @property int $rela_idioma_pelicula
 * @property string $imagen_pelicula
 * @property string $trailer_url
 */
class Pelicula extends ActiveRecord {
    protected static $tabla = 'peliculas';
    protected static $columnasDB = [
        'id_pelicula',
        'titulo_pelicula',
        'sinopsis_pelicula',
        'anyo_pelicula',
        'duracion_pelicula',
        'rela_estado_pelicula',
        'rela_tipo_clasificacion',
        'rela_idioma_pelicula',
        'imagen_pelicula',
        'trailer_url'
    ];

    // Campos reales de la tabla
    public $id_pelicula;
    public $titulo_pelicula;
    public $sinopsis_pelicula;
    public $anyo_pelicula;
    public $duracion_pelicula;
    public $rela_estado_pelicula;
    public $rela_tipo_clasificacion;
    public $rela_idioma_pelicula;
    public $imagen_pelicula;
    public $trailer_url;

    // Campos extra opcionales (para mostrar datos del JOIN, no se guardan directamente)
    public $nombre_estado_pelicula;
    public $nombre_tipo_clasificacion;
    public $nombre_idioma_pelicula;
    public $generos;
    public $generos_ids = [];
    public $creado;
    public $fecha_agregada;

    public function __construct($args = []) {
        $this->id_pelicula          = $args['id_pelicula'] ?? null;
        $this->titulo_pelicula      = $args['titulo_pelicula'] ?? '';
        $this->sinopsis_pelicula    = $args['sinopsis_pelicula'] ?? '';
        $this->anyo_pelicula        = $args['anyo_pelicula'] ?? '';
        $this->duracion_pelicula    = $args['duracion_pelicula'] ?? '';
        $this->rela_estado_pelicula = $args['rela_estado_pelicula'] ?? 1; 
        $this->rela_tipo_clasificacion = $args['rela_tipo_clasificacion'] ?? 1;
        $this->rela_idioma_pelicula = $args['rela_idioma_pelicula'] ?? 1;
        $this->imagen_pelicula      = $args['imagen_pelicula'] ?? '';
        $this->trailer_url          = $args['trailer_url'] ?? '';

        // Estos solo para mostrar
        $this->nombre_estado_pelicula    = $args['nombre_estado_pelicula'] ?? '';
        $this->nombre_tipo_clasificacion = $args['nombre_tipo_clasificacion'] ?? '';
        $this->nombre_idioma_pelicula    = $args['nombre_idioma_pelicula'] ?? '';
        $this->generos                   = $args['generos'] ?? [];
    }

    public function validar() {
        $alertas = [];
        
        //Validar título
        if (!$this->titulo_pelicula || trim($this->titulo_pelicula) === '') {
            $alertas[] = "El título es obligatorio";
        } else {
            $this->titulo_pelicula = trim($this->titulo_pelicula);
            if (strlen($this->titulo_pelicula) < 2) {
                $alertas[] = "El título debe tener al menos 2 caracteres";
            }
            if (strlen($this->titulo_pelicula) > 50) {
                $alertas[] = "El título no puede exceder 50 caracteres";
            }
            if ($this->verificarTituloUnico()) {
                $alertas[] = "Ya existe una película con ese título";
            }
        }
        
        // Validar sinopsis
        if (!$this->sinopsis_pelicula || trim($this->sinopsis_pelicula) === '') {
            $alertas[] = "La sinopsis es obligatoria";
        } else {
            $this->sinopsis_pelicula = trim($this->sinopsis_pelicula);
            
            if (strlen($this->sinopsis_pelicula) < 20) {
                $alertas[] = "La sinopsis debe tener al menos 20 caracteres";
            }
            
            if (strlen($this->sinopsis_pelicula) > 400) {
                $alertas[] = "La sinopsis no puede exceder 400 caracteres";
            }
        }
        
        // Validar año
        if (!$this->anyo_pelicula) {
            $alertas[] = "El año es obligatorio";
        } else {
            if (!is_numeric($this->anyo_pelicula)) {
                $alertas[] = "El año debe ser un número válido";
            } else {
                $anyo_actual = (int)date('Y');
                $anyo_pelicula = (int)$this->anyo_pelicula;
                
                if ($anyo_pelicula > $anyo_actual) {
                    $alertas[] = "El año no puede ser mayor al año actual ($anyo_actual)";
                }
                if ($anyo_pelicula < 1900) {
                    $alertas[] = "El año debe ser mayor a 1900";
                }
            }
        }
        
        //Validar duración
        if (!$this->duracion_pelicula) {
            $alertas[] = "La duración es obligatoria";
        } else {
            if (!is_numeric($this->duracion_pelicula)) {
                $alertas[] = "La duración debe ser un número válido";
            } else {
                $duracion = (int)$this->duracion_pelicula;
                
                if ($duracion <= 0) {
                    $alertas[] = "La duración debe ser mayor a 0";
                }
                
                if ($duracion < 40) {
                    $alertas[] = "La duración mínima para una película es de 40 minutos";
                }
                if ($duracion > 300) {
                    $alertas[] = "La duración máxima permitida es de 300 minutos (5 horas)";
                }
            }
        }
        
        //Validar clasificación
        if (!$this->rela_tipo_clasificacion) {
            $alertas[] = "Seleccione una clasificación";
        } else {
            if (!is_numeric($this->rela_tipo_clasificacion)) {
                $alertas[] = "La clasificación debe ser válida";
            }
        }
        
        if (!$this->rela_idioma_pelicula) {
            $alertas[] = "Seleccione un idioma";
        }

        if (!$this->rela_estado_pelicula) {
            $alertas[] = "Seleccione un estado";
        }
        
        //  Validar géneros
        if (!isset($this->generos) || !is_array($this->generos) || empty($this->generos)) {
            $alertas[] = "Seleccione al menos un género";
        } else {
            if (count($this->generos) > 5) {
                $alertas[] = "No puede seleccionar más de 5 géneros";
            }
        }
        
        if (!empty($this->trailer_url)) {
            $url = trim($this->trailer_url);
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $alertas[] = "La URL del tráiler no tiene un formato válido";
            } elseif (!$this->esUrlYouTubeValida($url)) {
                $alertas[] = "La URL del tráiler debe ser una URL válida de YouTube";
            }
        }
        
        return $alertas;
    }
    private function verificarTituloUnico() {
        $db = self::getDB();
        
        if ($this->id_pelicula) {
            $query = "SELECT COUNT(*) as total FROM peliculas 
                    WHERE LOWER(titulo_pelicula) = LOWER('{$this->titulo_pelicula}') 
                    AND id_pelicula != '{$this->id_pelicula}'";
        } else {
            $query = "SELECT COUNT(*) as total FROM peliculas 
                    WHERE LOWER(titulo_pelicula) = LOWER('{$this->titulo_pelicula}')";
        }
        
        $resultado = $db->query($query);
        $row = $resultado->fetch_assoc();
        
        return $row['total'] > 0;
    }
    
    // url valida
    private function esUrlYouTubeValida($url) {
        $patrones = [
            '/^https?:\/\/(?:www\.)?youtube\.com\/watch\?v=[\w\-_]{11}/',
            '/^https?:\/\/(?:www\.)?youtu\.be\/[\w\-_]{11}/',
            '/^https?:\/\/(?:www\.)?youtube\.com\/embed\/[\w\-_]{11}/',
            '/^https?:\/\/(?:www\.)?youtube\.com\/v\/[\w\-_]{11}/'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $url)) {
                return true;
            }
        }
        
        return false;
    }

    public static function obtenerConDetalle() {
        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.sinopsis_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                p.rela_estado_pelicula,
                p.rela_tipo_clasificacion,
                p.rela_idioma_pelicula,
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
            GROUP BY p.id_pelicula
            ORDER BY p.id_pelicula DESC
        ";
        return self::consultarSQL($query);
    }

    public static function eliminarLogico($id) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE peliculas SET rela_estado_pelicula = 3 WHERE id_pelicula = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

   public function actualizar() {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE peliculas SET titulo_pelicula=?, sinopsis_pelicula=?, anyo_pelicula=?, duracion_pelicula=?, rela_tipo_clasificacion=?, rela_idioma_pelicula=?, rela_estado_pelicula=?, imagen_pelicula=?, trailer_url=? WHERE id_pelicula=?");
        $stmt->bind_param(
            'ssiiiiissi',
            $this->titulo_pelicula,
            $this->sinopsis_pelicula,
            $this->anyo_pelicula,
            $this->duracion_pelicula,
            $this->rela_tipo_clasificacion,
            $this->rela_idioma_pelicula,
            $this->rela_estado_pelicula,
            $this->imagen_pelicula,
            $this->trailer_url,
            $this->id_pelicula
        );
        return $stmt->execute();
    }
  
    public static function buscarPorCampoUnico($termino) {
        $termino = self::$db->escape_string($termino);

        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.sinopsis_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                p.imagen_pelicula,
                p.trailer_url,
                ep.nombre_estado_pelicula,
                tc.nombre_tipo_clasificacion,
                ip.nombre_idioma_pelicula,
                GROUP_CONCAT(gp.genero_pelicula SEPARATOR ', ') AS generos
            FROM peliculas p
            LEFT JOIN estados_peliculas ep ON ep.id_estado_pelicula = p.rela_estado_pelicula
            LEFT JOIN tipos_clasificaciones tc ON tc.id_tipo_clasificacion = p.rela_tipo_clasificacion
            LEFT JOIN idiomas_peliculas ip ON ip.id_idioma_pelicula = p.rela_idioma_pelicula
            LEFT JOIN generos_por_peliculas ghp ON ghp.rela_pelicula = p.id_pelicula
            LEFT JOIN generos_peliculas gp ON gp.id_genero_pelicula = ghp.rela_genero_pelicula
            WHERE p.id_pelicula = '$termino' OR p.titulo_pelicula LIKE '%$termino%'
            GROUP BY p.id_pelicula
            LIMIT 1
        ";

        $resultado = self::$db->query($query);

        if ($resultado && $resultado->num_rows) {
            return $resultado->fetch_assoc();
        }

        return null;
    }
    public static function obtenerPeliculasUltimaSemana() {
        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.sinopsis_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                p.imagen_pelicula,
                p.trailer_url,
                p.creado as fecha_agregada,
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
            WHERE p.creado >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY p.id_pelicula
            ORDER BY p.creado DESC
        ";
        return self::consultarSQL($query);
    }
    public static function obtenerPeliculasUltimoDia() {
        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.sinopsis_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                p.imagen_pelicula,
                p.trailer_url,
                p.creado as fecha_agregada,
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
            WHERE p.creado >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY p.id_pelicula
            ORDER BY p.creado DESC
        ";
        return self::consultarSQL($query);
    }

 
    public static function obtenerPeliculasUltimoMes() {
        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.sinopsis_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                p.imagen_pelicula,
                p.trailer_url,
                p.creado as fecha_agregada,
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
            WHERE p.creado >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            GROUP BY p.id_pelicula
            ORDER BY p.creado DESC
        ";
        return self::consultarSQL($query);
    }
    
    public static function obtenerRankingGeneros() {
        $query = "
            SELECT 
                gp.genero_pelicula,
                COUNT(*) as total_peliculas,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM peliculas), 2) as porcentaje,
                GROUP_CONCAT(p.titulo_pelicula SEPARATOR ', ') as peliculas
            FROM generos_peliculas gp
            INNER JOIN generos_por_peliculas ghp ON gp.id_genero_pelicula = ghp.rela_genero_pelicula
            INNER JOIN peliculas p ON ghp.rela_pelicula = p.id_pelicula
            GROUP BY gp.id_genero_pelicula, gp.genero_pelicula
            ORDER BY total_peliculas DESC
        ";
        

        $db = self::getDB();
        $resultado = $db->query($query);
        $datos = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $datos[] = $row;
        }
        
        return $datos;
    }

    public static function obtenerPeliculasPorAnio() {
        $query = "
            SELECT 
                p.id_pelicula,
                p.titulo_pelicula,
                p.anyo_pelicula,
                p.duracion_pelicula,
                e.nombre_estado_pelicula,
                t.nombre_tipo_clasificacion,
                GROUP_CONCAT(gp.genero_pelicula SEPARATOR ', ') AS generos
            FROM peliculas p
            LEFT JOIN estados_peliculas e ON p.rela_estado_pelicula = e.id_estado_pelicula
            LEFT JOIN tipos_clasificaciones t ON p.rela_tipo_clasificacion = t.id_tipo_clasificacion
            LEFT JOIN generos_por_peliculas ghp ON p.id_pelicula = ghp.rela_pelicula
            LEFT JOIN generos_peliculas gp ON ghp.rela_genero_pelicula = gp.id_genero_pelicula
            WHERE p.anyo_pelicula >= YEAR(NOW()) - 5
            GROUP BY p.id_pelicula
            ORDER BY p.anyo_pelicula DESC, p.titulo_pelicula
        ";
        return self::consultarSQL($query);
    }
}