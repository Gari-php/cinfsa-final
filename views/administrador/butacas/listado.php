<body>
    <div class="listado">
        <div>
            <img src="../../assets/img/logo.png" alt="Logo del cine" class="form-logo">
        </div>
        <h1>Lista de Butacas</h1>
        <nav class="nav-listado">
            <ul>
                <li><a href="/administrador"><i class="fa-solid fa-hand-point-left"></i>Volver</a></li>
                <li><a href="/administrador/butacas/crear"><i class="fa-solid fa-chair"></i> Agregar Butaca</a></li>
                <li><a href="#"><i class="fa-solid fa-book"></i> Reportes de Butacas</a></li>
                <li class="exportacion">
                    <div class="grupo-exportacion">
                        <span><i class="fa-solid fa-download"></i> Exportar:</span>
                        <button class="btn-exportar excel" data-tipo="excel">
                            <i class="fa-solid fa-file-excel"></i> Excel
                        </button>
                        <button class="btn-exportar pdf" data-tipo="pdf">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </li>
                <li class="buscador" style="width:300px;">
                    <input id="busqueda-butaca" class="barra_buscador" type="text" placeholder="Buscar por ID, Fila o Sala" style="width: 250px; height: 30px;">
                    <button id="btn-buscar-butaca" type="button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sala</th>
                    <th>Fila</th>
                    <th>Número</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-butacas">
            <?php foreach($butacas as $b): ?>
                <tr>
                    <td><?php echo $b->id_butaca; ?></td>
                    <td>Sala <?php echo $b->rela_salas; ?></td>
                    <td><?php echo $b->fila_butaca; ?></td>
                    <td><?php echo $b->numero_butaca; ?></td>
                    <td>
                        <?php 
                        $claseEstado = '';
                        switch($b->rela_estado_butaca) {
                            case 1: $claseEstado = 'disponible'; break;
                            case 2: $claseEstado = 'no-disponible'; break;
                            case 3: $claseEstado = 'reservado'; break;
                        }
                        ?>
                        <span class="estado-butaca <?php echo $claseEstado; ?>">
                            <?php echo $b->nombre_estado; ?>
                        </span>
                    </td>
                    <td>
                        <div class="acciones">
                            <button class="boton eliminar-butaca"
                                    data-id="<?php echo $b->id_butaca; ?>"
                                    data-nombre="Fila <?php echo $b->fila_butaca; ?> - N° <?php echo $b->numero_butaca; ?>">
                                Eliminar
                            </button>
                            <a class="boton" href="/administrador/butacas/editar?id=<?php echo $b->id_butaca; ?>">Editar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div id="alerta-accion" class="form-container"></div>
        <script type="module" src="/assets/js/formularios.js"></script>
        
        <script>
        // Event listeners para exportación
        document.querySelectorAll('.btn-exportar').forEach(btn => {
            btn.addEventListener('click', function() {
                const tipo = this.getAttribute('data-tipo');
                const busqueda = document.getElementById('busqueda-butaca').value;
                let url = `/administrador/butacas/exportar?tipo=${tipo}`;
                
                if (busqueda.trim()) {
                    url += `&busqueda=${encodeURIComponent(busqueda)}`;
                }
                
                window.open(url, '_blank');
            });
        });

        // Event listener para búsqueda
        document.getElementById('btn-buscar-butaca').addEventListener('click', function() {
            const termino = document.getElementById('busqueda-butaca').value.trim();
            
            if (!termino) {
                alert('Ingrese un término de búsqueda');
                return;
            }
            
            fetch('/administrador/butacas/buscar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ termino: termino })
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('tabla-butacas');
                
                if (data.ok) {
                    const butaca = data.butaca;
                    let claseEstado = '';
                    switch(parseInt(butaca.rela_estado_butaca)) {
                        case 1: claseEstado = 'disponible'; break;
                        case 2: claseEstado = 'no-disponible'; break;
                        case 3: claseEstado = 'reservado'; break;
                    }
                    
                    tbody.innerHTML = `
                        <tr>
                            <td>${butaca.id_butaca}</td>
                            <td>Sala ${butaca.rela_salas}</td>
                            <td>${butaca.fila_butaca}</td>
                            <td>${butaca.numero_butaca}</td>
                            <td><span class="estado-butaca ${claseEstado}">${butaca.nombre_estado_butaca}</span></td>
                            <td>
                                <div class="acciones">
                                    <button class="boton eliminar-butaca" data-id="${butaca.id_butaca}" data-nombre="Fila ${butaca.fila_butaca} - N° ${butaca.numero_butaca}">Eliminar</button>
                                    <a class="boton" href="/administrador/butacas/editar?id=${butaca.id_butaca}">Editar</a>
                                </div>
                            </td>
                        </tr>
                    `;
                    
                    // Reactivar event listeners para el nuevo botón
                    activarEventListeners();
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="text-align: center; color: #ff6b35; font-weight: bold;">
                                ${data.mensaje}
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                alert('Error en la búsqueda');
            });
        });

        // Función para reactivar event listeners después de búsqueda
        function activarEventListeners() {
            document.querySelectorAll('.eliminar-butaca').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nombre = this.getAttribute('data-nombre');
                    
                    if (confirm(`¿Está seguro de eliminar la butaca ${nombre}?`)) {
                        fetch('/administrador/butacas/eliminar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id_butaca: id })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.ok) {
                                alert(data.mensaje);
                                location.reload();
                            } else {
                                alert('Error: ' + data.mensaje);
                            }
                        })
                        .catch(error => {
                            alert('Error al eliminar butaca');
                        });
                    }
                });
            });
        }

        // Activar event listeners al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            activarEventListeners();
        });

        // Enter para buscar
        document.getElementById('busqueda-butaca').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('btn-buscar-butaca').click();
            }
        });
        </script>

        <style>
        .estado-butaca {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .estado-butaca.disponible {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .estado-butaca.no-disponible {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .estado-butaca.reservado {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        </style>
    </div>
</body>