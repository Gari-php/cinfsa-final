
    <div class="reportes-container" data-modulo="peliculas-reportes">
        <div class="header-reportes">
            <img src="../../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
            <h1>Reportes de Películas</h1>
            <nav class="nav-reportes">
                <a href="/administrador"><i class="fa-solid fa-house"></i> Inicio</a>

                <a href="/administrador/peliculas/listado"><i class="fa-solid fa-film"></i> Películas</a>

                <span>Reportes</span>
            </nav>
        </div>

        <div class="reportes-grid">

            <div class="reporte-card">
                <div class="card-header">
                    <i class="fa-solid fa-calendar-week"></i>
                    <h3>Películas Agregadas - Última Semana</h3>
                </div>
                <div class="card-description">
                    <p>Reporte de todas las películas agregadas en los últimos 7 días</p>
                    <small>Incluye: título, año, duración, clasificación, géneros y fecha de agregado</small>
                </div>
                <div class="card-actions">
                    <div class="grupo-exportacion">
                        <button class="btn-reporte excel" data-reporte="semanal" data-tipo="excel">
                            <i class="fa-solid fa-file-excel"></i> Exportar Excel
                        </button>
                        <button class="btn-reporte pdf" data-reporte="semanal" data-tipo="pdf">
                            <i class="fa-solid fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>
            </div>
            <div class="reporte-card">
                <div class="card-header">
                    <i class="fa-solid fa-star"></i>
                    <h3>Géneros Más Populares</h3>
                </div>
                <div class="card-description">
                    <p>Ranking de géneros más utilizados en el catálogo de películas</p>
                    <small>Incluye: conteo por género, porcentajes y lista de películas por género</small>
                </div>
                <div class="card-actions">
                    <div class="grupo-exportacion">
                        <button class="btn-reporte excel" data-reporte="ranking-generos" data-tipo="excel">
                            <i class="fa-solid fa-file-excel"></i> Exportar Excel
                        </button>
                        <button class="btn-reporte pdf" data-reporte="ranking-generos" data-tipo="pdf">
                            <i class="fa-solid fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>
            </div>

            <div class="reporte-card">
                <div class="card-header">
                    <i class="fa-solid fa-chart-pie"></i>
                    <h3>Proximo Reporte</h3>
                </div>
                <div class="card-description">
                    <p>Nuevo Reporte</p>
                    <small>Diseñar un nuevo reporte espesifico Proximamente</small>
                </div>
                <div class="card-actions">
                    <div class="grupo-exportacion">
                        <button class="btn-reporte excel" data-reporte="por-estado" data-tipo="excel" disabled>
                            <i class="fa-solid fa-file-excel"></i> Próximamente
                        </button>
                        <button class="btn-reporte pdf" data-reporte="por-estado" data-tipo="pdf" disabled>
                            <i class="fa-solid fa-file-pdf"></i> Próximamente
                        </button>
                    </div>
                </div>
            </div>
           
    </div>

    <div id="alerta-accion" class="form-container"></div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const botonesReporte = document.querySelectorAll('.btn-reporte');
        
        botonesReporte.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.disabled) return;
                
                const reporte = this.dataset.reporte;
                const tipo = this.dataset.tipo;
                
                exportarReporte(reporte, tipo, this);
            });
        });
        
        function exportarReporte(reporte, tipo, boton) {
    
            boton.disabled = true;
            boton.classList.add('loading-export');
            const textoOriginal = boton.innerHTML;
            boton.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Generando...`;
            
     
            const url = `/administrador/peliculas/exportar-reporte?reporte=${reporte}&tipo=${tipo}`;
            
            const link = document.createElement('a');
            link.href = url;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            

            setTimeout(() => {
                boton.disabled = false;
                boton.classList.remove('loading-export');
                boton.innerHTML = textoOriginal;
            }, 2500);
        }
    });
    </script>
