<?php

namespace Models;

class DatosFacturacion extends ActiveRecord
{
    protected static $tabla = 'datos_facturacion';
    protected static $columnasDB = [
        'id_dato_facturacion',
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'domicilio',
        'localidad',
        'provincia',
        'codigo_postal',
        'email_facturacion',
        'telefono_facturacion',
        'fecha_creacion_facturacion',
        'fecha_actualizacion_facturacion',
        'activo'
    ];

    public $id_dato_facturacion;
    public $tipo_documento;
    public $numero_documento;
    public $razon_social;
    public $domicilio;
    public $localidad;
    public $provincia;
    public $codigo_postal;
    public $email_facturacion;
    public $telefono_facturacion;
    public $fecha_creacion_facturacion;
    public $fecha_actualizacion_facturacion;
    public $activo;

    public function __construct($args = [])
    {
        $this->id_dato_facturacion = $args['id_dato_facturacion'] ?? null;
        $this->tipo_documento = $args['tipo_documento'] ?? 'DNI';
        $this->numero_documento = $args['numero_documento'] ?? '';
        $this->razon_social = $args['razon_social'] ?? '';
        $this->domicilio = $args['domicilio'] ?? '';
        $this->localidad = $args['localidad'] ?? '';
        $this->provincia = $args['provincia'] ?? '';
        $this->codigo_postal = $args['codigo_postal'] ?? '';
        $this->email_facturacion = $args['email_facturacion'] ?? '';
        $this->telefono_facturacion = $args['telefono_facturacion'] ?? '';
        $this->activo = $args['activo'] ?? 1;
    }

    public static function buscarOCrear($datos)
    {
        // Limpiar número de documento
        $numeroDocumento = self::limpiarNumeroDocumento($datos['numero_documento'] ?? '');
        
        if (empty($numeroDocumento)) {
            return null;
        }

        $db = self::getDB();
        
        // Buscar si ya existe
        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE tipo_documento = ? 
                  AND numero_documento = ? 
                  AND activo = 1
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $tipoDoc = $datos['tipo_documento'] ?? 'DNI';
        $stmt->bind_param("ss", $tipoDoc, $numeroDocumento);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Actualizar datos si han cambiado
            $actualizar = false;
            $obj = new self($row);
            
            if (!empty($datos['razon_social']) && $obj->razon_social !== $datos['razon_social']) {
                $obj->razon_social = $datos['razon_social'];
                $actualizar = true;
            }
            
            if (!empty($datos['email_facturacion']) && $obj->email_facturacion !== $datos['email_facturacion']) {
                $obj->email_facturacion = $datos['email_facturacion'];
                $actualizar = true;
            }
            
            if (!empty($datos['domicilio']) && $obj->domicilio !== $datos['domicilio']) {
                $obj->domicilio = $datos['domicilio'];
                $actualizar = true;
            }
            
            if ($actualizar) {
                $obj->guardar();
            }
            
            return $obj;
        }
        
        // Crear nuevo registro
        $nuevo = new self([
            'tipo_documento' => $datos['tipo_documento'] ?? 'DNI',
            'numero_documento' => $numeroDocumento,
            'razon_social' => strtoupper($datos['razon_social'] ?? ''),
            'domicilio' => $datos['domicilio'] ?? '',
            'localidad' => $datos['localidad'] ?? '',
            'provincia' => $datos['provincia'] ?? '',
            'codigo_postal' => $datos['codigo_postal'] ?? '',
            'email_facturacion' => $datos['email_facturacion'] ?? '',
            'telefono_facturacion' => $datos['telefono_facturacion'] ?? ''
        ]);
        
        $resultado = $nuevo->guardar();
        
        return $resultado ? $nuevo : null;
    }


    private static function limpiarNumeroDocumento($numero)
    {
        return preg_replace('/[^0-9]/', '', $numero);
    }


    public static function validarCUIT($cuit)
    {
        $cuit = self::limpiarNumeroDocumento($cuit);
        
        if (strlen($cuit) != 11) {
            return false;
        }
        
        // Validar dígito verificador
        $suma = 0;
        $multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 10; $i++) {
            $suma += intval($cuit[$i]) * $multiplicadores[$i];
        }
        
        $resto = $suma % 11;
        $digitoVerificador = $resto == 0 ? 0 : (11 - $resto);
        
        return intval($cuit[10]) === $digitoVerificador;
    }

    public static function formatearCUIT($cuit)
    {
        $cuit = self::limpiarNumeroDocumento($cuit);
        
        if (strlen($cuit) != 11) {
            return $cuit;
        }
        
        return substr($cuit, 0, 2) . '-' . substr($cuit, 2, 8) . '-' . substr($cuit, 10, 1);
    }

    public static function buscarPorDocumento($tipoDocumento, $numeroDocumento)
    {
        $numeroDocumento = self::limpiarNumeroDocumento($numeroDocumento);
        
        $db = self::getDB();
        $query = "SELECT * FROM " . static::$tabla . " 
                  WHERE tipo_documento = ? 
                  AND numero_documento = ? 
                  AND activo = 1
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $tipoDocumento, $numeroDocumento);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return new self($row);
        }
        
        return null;
    }
}