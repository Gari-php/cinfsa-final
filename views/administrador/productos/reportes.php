
    <div class="reportes-container" data-modulo="peliculas-reportes">
        <div class="header-reportes">
            <img src="../../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
            <h1>Reportes de Producto</h1>
            <nav class="nav-reportes">
                <a href="/administrador"><i class="fa-solid fa-house"></i> Inicio</a>

                <a href="/administrador/productos/listado"><i class="fa-solid fa-film"></i> Productos</a>

                <span>Reportes</span>
            </nav>
        </div>

        <div class="reportes-grid">

            <div class="reporte-card">
                <div class="card-header">
                    <i class="fa-solid fa-calendar-week"></i>
                    <h3>Producto mas Vendido en el mes</h3>
                </div>
                <div class="card-description">
                    <p>Reporte de los 3 Productoas mas vendidos en el mes</p>
                    <small>Incluye: Cantidad, Nombre, Precio y fecha de Venta</small>
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
                    <h3>Productos Suspendidos</h3>
                </div>
                <div class="card-description">
                    <p>Todos los productos Suspendidos de la cantina</p>
                    <small>Incluye: imagen del Producto,nombre del Producto y precio del Producto</small>
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


    
