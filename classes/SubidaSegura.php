<?php

namespace Classes;

class SubidaSegura
{
    /**
     * Valida un archivo subido ($_FILES[...]) inspeccionando su contenido real
     * (no la extensión ni el Content-Type que manda el navegador, ambos
     * falsificables por el cliente) y devuelve la extensión segura a usar
     * para guardarlo, o null si el archivo no coincide con ningún tipo
     * permitido.
     *
     * @param array $archivo Un elemento de $_FILES.
     * @param array $tiposPermitidos Mapa mime real => extensión, ej: ['image/jpeg' => 'jpg']
     */
    public static function validarYObtenerExtension(array $archivo, array $tiposPermitidos): ?string
    {
        if (empty($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        return $tiposPermitidos[$mimeReal] ?? null;
    }
}
