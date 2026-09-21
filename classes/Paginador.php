<?php

namespace Classes;

class Paginador {
    
    private $paginaActual;
    private $registrosPorPagina;
    private $totalRegistros;
    private $totalPaginas;
    private $offset;
    
    /**
     * Constructor del paginador
     * 
     * @param int $paginaActual Página actual (viene de $_GET['pagina'])
     * @param int $registrosPorPagina Cantidad de registros por página (default: 10)
     */
    public function __construct($paginaActual = 1, $registrosPorPagina = 10) {
        $this->paginaActual = max(1, (int)$paginaActual); // Asegurar que sea mínimo 1
        $this->registrosPorPagina = max(1, (int)$registrosPorPagina);
        $this->offset = ($this->paginaActual - 1) * $this->registrosPorPagina;
    }
    
    /**
     * Establecer el total de registros (se debe llamar después de contar)
     * 
     * @param int $total Total de registros en la base de datos
     */
    public function setTotal($total) {
        $this->totalRegistros = (int)$total;
        $this->totalPaginas = (int)ceil($this->totalRegistros / $this->registrosPorPagina);
    }
    
    /**
     * Obtener el LIMIT para la consulta SQL
     * 
     * @return string Ejemplo: "LIMIT 10 OFFSET 20"
     */
    public function limit() {
        return "LIMIT {$this->registrosPorPagina} OFFSET {$this->offset}";
    }
    
    /**
     * Obtener el OFFSET actual
     * 
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }
    
    /**
     * Obtener registros por página
     * 
     * @return int
     */
    public function getRegistrosPorPagina() {
        return $this->registrosPorPagina;
    }
    
    /**
     * Obtener página actual
     * 
     * @return int
     */
    public function getPaginaActual() {
        return $this->paginaActual;
    }
    
    /**
     * Obtener total de páginas
     * 
     * @return int
     */
    public function getTotalPaginas() {
        return $this->totalPaginas;
    }
    
    /**
     * Obtener total de registros
     * 
     * @return int
     */
    public function getTotalRegistros() {
        return $this->totalRegistros;
    }
    
    /**
     * Verificar si hay página anterior
     * 
     * @return bool
     */
    public function hayPaginaAnterior() {
        return $this->paginaActual > 1;
    }
    
    /**
     * Verificar si hay página siguiente
     * 
     * @return bool
     */
    public function hayPaginaSiguiente() {
        return $this->paginaActual < $this->totalPaginas;
    }
    
    /**
     * Obtener número de página anterior
     * 
     * @return int
     */
    public function getPaginaAnterior() {
        return max(1, $this->paginaActual - 1);
    }
    
    /**
     * Obtener número de página siguiente
     * 
     * @return int
     */
    public function getPaginaSiguiente() {
        return min($this->totalPaginas, $this->paginaActual + 1);
    }
    
    /**
     * Generar HTML de paginación
     * 
     * @param string $urlBase URL base sin el parámetro de página
     * @param array $parametrosAdicionales Parámetros GET adicionales a mantener
     * @return string HTML del paginador
     */
    public function render($urlBase = '', $parametrosAdicionales = []) {
        if ($this->totalPaginas <= 1) {
            return ''; // No mostrar paginador si solo hay 1 página
        }
        
        $html = '<div class="paginador-container">';
        $html .= '<div class="paginador-info">';
        $html .= "Mostrando " . $this->getRangoMostrado() . " de {$this->totalRegistros} registros";
        $html .= '</div>';
        
        $html .= '<ul class="paginador">';
        
        // Botón anterior
        if ($this->hayPaginaAnterior()) {
            $url = $this->construirUrl($urlBase, $this->getPaginaAnterior(), $parametrosAdicionales);
            $html .= '<li><a href="' . $url . '" class="paginador-btn"><i class="fa-solid fa-chevron-left"></i> Anterior</a></li>';
        } else {
            $html .= '<li><span class="paginador-btn disabled"><i class="fa-solid fa-chevron-left"></i> Anterior</span></li>';
        }
        
        // Números de página
        $html .= $this->generarNumerosPagina($urlBase, $parametrosAdicionales);
        
        // Botón siguiente
        if ($this->hayPaginaSiguiente()) {
            $url = $this->construirUrl($urlBase, $this->getPaginaSiguiente(), $parametrosAdicionales);
            $html .= '<li><a href="' . $url . '" class="paginador-btn">Siguiente <i class="fa-solid fa-chevron-right"></i></a></li>';
        } else {
            $html .= '<li><span class="paginador-btn disabled">Siguiente <i class="fa-solid fa-chevron-right"></i></span></li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generar números de página con lógica de puntos suspensivos
     * 
     * @param string $urlBase
     * @param array $parametrosAdicionales
     * @return string
     */
    private function generarNumerosPagina($urlBase, $parametrosAdicionales) {
        $html = '';
        $rango = 2; // Mostrar 2 páginas a cada lado de la actual
        
        // Siempre mostrar primera página
        $url = $this->construirUrl($urlBase, 1, $parametrosAdicionales);
        $clase = $this->paginaActual == 1 ? 'active' : '';
        $html .= '<li><a href="' . $url . '" class="paginador-numero ' . $clase . '">1</a></li>';
        
        // Puntos suspensivos si hay páginas entre 1 y el rango
        if ($this->paginaActual - $rango > 2) {
            $html .= '<li><span class="paginador-puntos">...</span></li>';
        }
        
        // Páginas del rango central
        $inicio = max(2, $this->paginaActual - $rango);
        $fin = min($this->totalPaginas - 1, $this->paginaActual + $rango);
        
        for ($i = $inicio; $i <= $fin; $i++) {
            $url = $this->construirUrl($urlBase, $i, $parametrosAdicionales);
            $clase = $this->paginaActual == $i ? 'active' : '';
            $html .= '<li><a href="' . $url . '" class="paginador-numero ' . $clase . '">' . $i . '</a></li>';
        }
        
        // Puntos suspensivos si hay páginas entre el rango y la última
        if ($this->paginaActual + $rango < $this->totalPaginas - 1) {
            $html .= '<li><span class="paginador-puntos">...</span></li>';
        }
        
        // Siempre mostrar última página (si hay más de 1)
        if ($this->totalPaginas > 1) {
            $url = $this->construirUrl($urlBase, $this->totalPaginas, $parametrosAdicionales);
            $clase = $this->paginaActual == $this->totalPaginas ? 'active' : '';
            $html .= '<li><a href="' . $url . '" class="paginador-numero ' . $clase . '">' . $this->totalPaginas . '</a></li>';
        }
        
        return $html;
    }
    
    /**
     * Construir URL con parámetros
     * 
     * @param string $urlBase
     * @param int $pagina
     * @param array $parametrosAdicionales
     * @return string
     */
    private function construirUrl($urlBase, $pagina, $parametrosAdicionales = []) {
        $parametros = array_merge($parametrosAdicionales, ['pagina' => $pagina]);
        $queryString = http_build_query($parametros);
        
        // Si la URL base ya tiene parámetros, usar & en lugar de ?
        $separador = strpos($urlBase, '?') !== false ? '&' : '?';
        
        return $urlBase . $separador . $queryString;
    }
    
    /**
     * Obtener el rango de registros mostrados
     * 
     * @return string Ejemplo: "11-20"
     */
    private function getRangoMostrado() {
        $inicio = $this->offset + 1;
        $fin = min($this->offset + $this->registrosPorPagina, $this->totalRegistros);
        return "{$inicio}-{$fin}";
    }
    
    /**
     * Renderizar CSS del paginador (para incluir una vez en el layout)
     * 
     * @return string
     */
    public static function renderCSS() {
        return <<<CSS
<style>
.paginador-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 30px 0;
    padding: 20px;
    background: #2d3748;
    border-radius: 8px;
    flex-wrap: wrap;
    gap: 20px;
}

.paginador-info {
    color: #a0aec0;
    font-size: 14px;
}

.paginador {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 5px;
}

.paginador li {
    display: inline-block;
}

.paginador-btn,
.paginador-numero,
.paginador-puntos {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 14px;
}

.paginador-btn {
    background: #4a5568;
    color: #fff;
    border: 1px solid #4a5568;
}

.paginador-btn:hover:not(.disabled) {
    background: #ed850f;
    border-color: #ed850f;
    transform: translateY(-2px);
}

.paginador-btn.disabled {
    background: #2d3748;
    color: #718096;
    cursor: not-allowed;
    border-color: #2d3748;
}

.paginador-numero {
    background: #374151;
    color: #fff;
    border: 1px solid #4a5568;
    min-width: 40px;
    text-align: center;
}

.paginador-numero:hover:not(.active) {
    background: #4a5568;
    transform: translateY(-2px);
}

.paginador-numero.active {
    background: #ed850f;
    color: #fff;
    border-color: #ed850f;
    font-weight: bold;
    cursor: default;
}

.paginador-puntos {
    background: transparent;
    color: #a0aec0;
    border: none;
    cursor: default;
}

@media (max-width: 768px) {
    .paginador-container {
        flex-direction: column;
        text-align: center;
    }
    
    .paginador {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .paginador-btn,
    .paginador-numero {
        padding: 6px 10px;
        font-size: 12px;
    }
}
</style>
CSS;
    }
}
