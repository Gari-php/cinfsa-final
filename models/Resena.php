<?php

namespace Models;

class Resena extends ActiveRecord
{
    protected static $tabla = 'resenas';
    protected static $columnasDB = ['id_resena', 'rela_usuario', 'rela_pelicula', 'puntuacion', 'comentario', 'fecha_creacion'];

    public $id_resena;
    public $rela_usuario;
    public $rela_pelicula;
    public $puntuacion;
    public $comentario;

    public function __construct($args = [])
    {
        $this->id_resena = $args['id_resena'] ?? null;
        $this->rela_usuario = $args['rela_usuario'] ?? null;
        $this->rela_pelicula = $args['rela_pelicula'] ?? null;
        $this->puntuacion = $args['puntuacion'] ?? null;
        $this->comentario = $args['comentario'] ?? null;
    }

    public static function obtenerPeliculasCalificables($idUsuario)
    {
        $db = self::getDB();

        $query = "SELECT 
                    p.id_pelicula,
                    p.titulo_pelicula,
                    p.imagen_pelicula,
                    r.puntuacion as mi_puntuacion
                  FROM peliculas p
                  INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula
                  LEFT JOIN resenas r ON r.rela_pelicula = p.id_pelicula AND r.rela_usuario = ?
                  WHERE ep.nombre_estado_pelicula IN ('Emision', 'Finalizada')
                  ORDER BY p.titulo_pelicula ASC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $peliculas = [];
        while ($row = $resultado->fetch_assoc()) {
            $row['ya_calificada'] = $row['mi_puntuacion'] !== null;
            $peliculas[] = $row;
        }

        return $peliculas;
    }

    public static function obtenerEstadisticasPeliculas()
    {
        $db = self::getDB();

        $query = "SELECT 
                    p.id_pelicula,
                    p.titulo_pelicula,
                    p.imagen_pelicula,
                    ROUND(AVG(r.puntuacion), 1) as promedio,
                    COUNT(r.id_resena) as total_resenas
                  FROM peliculas p
                  INNER JOIN estados_peliculas ep ON p.rela_estado_pelicula = ep.id_estado_pelicula
                  LEFT JOIN resenas r ON r.rela_pelicula = p.id_pelicula
                  WHERE ep.nombre_estado_pelicula IN ('Emision', 'Finalizada')
                  GROUP BY p.id_pelicula, p.titulo_pelicula, p.imagen_pelicula
                  ORDER BY p.titulo_pelicula ASC";

        $resultado = $db->query($query);

        $peliculas = [];
        while ($row = $resultado->fetch_assoc()) {
            $peliculas[] = $row;
        }

        return $peliculas;
    }

    public function guardar()
    {
        $db = self::getDB();

        $query = "INSERT INTO resenas (rela_usuario, rela_pelicula, puntuacion, comentario, fecha_creacion)
                  VALUES (?, ?, ?, ?, NOW())";

        $stmt = $db->prepare($query);
        $stmt->bind_param('iids', $this->rela_usuario, $this->rela_pelicula, $this->puntuacion, $this->comentario);

        try {
            $resultado = $stmt->execute();
            return ['resultado' => $resultado];
        } catch (\mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'uniq_usuario_pelicula') !== false) {
                return ['resultado' => false, 'error' => 'Ya calificaste esta película'];
            }
            return ['resultado' => false, 'error' => $e->getMessage()];
        }
    }
}
