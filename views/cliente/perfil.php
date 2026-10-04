<?php
$session_user   = $_SESSION['nombre_usuario'] ?? null;
$session_perfil = $_SESSION['perfil'] ?? null;
$session_id     = $_SESSION['id_usuario'] ?? null;

// Buscar el perfil SOLO del usuario en sesión
$usuarioActual = null;

foreach ($perfil as $perfiles) {
    if ($perfiles['id_usuario'] == $session_id) {
        $usuarioActual = $perfiles;
        break;
    }
}
?>

<div id="pagina-perfil-cliente">

    <div class="contenedor">
        <div class="page-header">
            <h1 class='user'><?php echo $session_user ?></h1>
            <p class="parrafo">Bienvenido a tu perfil de cliente. Aquí puedes gestionar tu información personal, ver tus entradas, beneficios y pedidos de cantina.
                Para continuar, selecciona una opción del menú lateral.
            </p>
        </div>
    </div>

    <!-- Modal para mostrar/editar datos -->
    <div id="modalPerfil" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalPerfil()">&times;</span>
            <div id="contenidoModal">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Cargando datos...</p>
                </div>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnMenu = document.getElementById('btn-menu');
            if (btnMenu) {
                btnMenu.checked = true;
            }
        });

        // Variables globales
        let modoEdicion = false;
        let datosOriginales = {};

        // Datos del usuario actual desde PHP
        const usuarioActualData = <?php echo json_encode($usuarioActual); ?>;

        // Función principal para mostrar datos
        function mostrarDatos() {
            document.getElementById('modalPerfil').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevenir scroll del fondo

            // Como ya tenemos los datos del usuario, los mostramos directamente
            if (usuarioActualData) {
                datosOriginales = usuarioActualData;
                mostrarFormularioPerfil(usuarioActualData);
            } else {
                mostrarError('No se pudieron cargar los datos del usuario');
            }
        }

        // Función para cerrar modal
        function cerrarModalPerfil() {
            document.getElementById('modalPerfil').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaurar scroll
            modoEdicion = false;
        }

        // Cerrar modal al hacer clic fuera 
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalPerfil');
            if (event.target == modal) {
                cerrarModalPerfil();
            }
        });

        // Cerrar modal con ESC 
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalPerfil();
            }
        });


        // Mostrar formulario con datos del perfil
        function mostrarFormularioPerfil(usuario) {
            // Manejar la foto de perfil
            let fotoSrc = '';
            if (usuario.foto_perfil) {
                // Si la foto viene como base64 desde PHP
                fotoSrc = `data:image/png;base64,${usuario.foto_perfil}`;
            } else {
                fotoSrc = '../../../assets/img/default-avatar.png';
            }

            const html = `
        <div class="perfil-container">
            <div class="perfil-header">
                <div class="foto-perfil-container">
                    <img src="${fotoSrc}" alt="Foto de perfil" class="foto-perfil" id="fotoPerfil">
                    <button type="button" class="cambiar-foto" onclick="cambiarFoto()" 
                            ${!modoEdicion ? 'style="display:none"' : ''}><i class="fa-solid fa-plus"></i></button>
                </div>
                <h2 class="nombre">${usuario.nombre_persona} ${usuario.apellido_persona}</h2>
            </div>

            <div id="mensajeRespuesta"></div>

            <form id="formPerfil">
                <div class="form-row">
                    <div class="form-group">
                        <label class="dato-user" >Nombre de Usuario:</label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" 
                               value="${usuario.nombre_usuario || ''}" ${!modoEdicion ? 'disabled' : ''}>
                    </div>
                    <div class="form-group">
                        <label class="dato-user">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="${usuario.email || ''}" ${!modoEdicion ? 'disabled' : ''}>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="dato-user">Nombre:</label>
                        <input type="text" id="nombre_persona" name="nombre_persona" 
                               value="${usuario.nombre_persona || ''}" ${!modoEdicion ? 'disabled' : ''}>
                    </div>
                    <div class="form-group">
                        <label class="dato-user">Apellido:</label>
                        <input type="text" id="apellido_persona" name="apellido_persona" 
                               value="${usuario.apellido_persona || ''}" ${!modoEdicion ? 'disabled' : ''}>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="dato-user">Sexo:</label>
                        <select id="sexo" name="sexo" ${!modoEdicion ? 'disabled' : ''}>
                            <option value="">Seleccionar...</option>
                            <option value="1" ${usuario.nombre_sexo === 'MASCULINO' ? 'selected' : ''}>Masculino</option>
                            <option value="2" ${usuario.nombre_sexo === 'FEMENINO' ? 'selected' : ''}>Femenino</option>
                            <option value="3" ${usuario.nombre_sexo === 'NO BINARIO' ? 'selected' : ''}>No Binario</option>
                            <option value="4" ${usuario.nombre_sexo === 'PREFIERO NO DECIR' ? 'selected' : ''}>Prefiero No Decir</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="dato-user">Perfil:</label>
                        <input type="text" value="${usuario.nombre_perfil || ''}" disabled>
                    </div>
                </div>

                <input type="hidden" id="id_usuario" value="${usuario.id_usuario}">
            </form>

            <div style="text-align: center; margin-top: 30px;">
                ${!modoEdicion ? 
                    '<button type="button" class="btn btn-primary" onclick="habilitarEdicion()"> Editar Datos</button>' :
                    `<button type="button" class="btn btn-success" onclick="guardarCambios()"> Guardar Cambios</button>
                     <button type="button" class="btn btn-secondary" onclick="cancelarEdicion()"> Cancelar</button>`
                }
            </div>
        </div>
    `;

            document.getElementById('contenidoModal').innerHTML = html;
        }

        // Habilitar modo edición
        function habilitarEdicion() {
            modoEdicion = true;
            mostrarFormularioPerfil(datosOriginales);
        }

        // Cancelar edición
        function cancelarEdicion() {
            modoEdicion = false;
            mostrarFormularioPerfil(datosOriginales);
        }

        // Guardar cambios
        function guardarCambios() {
            // Validaciones básicas
            const nombre_usuario = document.getElementById('nombre_usuario').value.trim();
            const email = document.getElementById('email').value.trim();
            const nombre_persona = document.getElementById('nombre_persona').value.trim();
            const apellido_persona = document.getElementById('apellido_persona').value.trim();
            const sexo = document.getElementById('sexo').value;

            if (!nombre_usuario || !email || !nombre_persona || !apellido_persona || !sexo) {
                mostrarError('Todos los campos son obligatorios');
                return;
            }

            if (!validarEmail(email)) {
                mostrarError('Por favor ingresa un email válido');
                return;
            }

            const formData = new FormData();
            formData.append('id_usuario', document.getElementById('id_usuario').value);
            formData.append('nombre_usuario', nombre_usuario);
            formData.append('nombre_persona', nombre_persona);
            formData.append('apellido_persona', apellido_persona);
            formData.append('email', email);
            formData.append('sexo', sexo);

            // Si hay una nueva foto, agregarla
            const inputFoto = document.getElementById('inputFoto');
            if (inputFoto && inputFoto.files.length > 0) {
                formData.append('foto_perfil', inputFoto.files[0]);
            }

            // Mostrar mensaje de carga
            document.getElementById('mensajeRespuesta').innerHTML = `
        <div class="alert alert-info">
            <div class="spinner" style="width: 20px; height: 20px; margin-right: 10px; display: inline-block;"></div>
            Guardando cambios...
        </div>
    `;


            fetch('/perfil/actualizar', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {

                    // NUEVO: Obtener el texto completo de la respuesta para ver qué está devolviendo
                    return response.text();
                })
                .then(responseText => {

                    // Intentar parsear como JSON
                    try {
                        const data = JSON.parse(responseText);

                        if (data.success) {
                            mostrarExito('✅ Datos actualizados correctamente');
                            setTimeout(() => {
                                location.reload();
                            }, 1200);
                        } else {
                            mostrarError('❌ Error al actualizar: ' + (data.message || 'Error desconocido'));
                        }
                    } catch (error) {
                        console.error('Error al parsear JSON:', error);
                        mostrarError('❌ Error del servidor. Revisa la consola para más detalles.');
                    }
                })
                .catch(error => {
                    console.error('Error de red:', error);
                    mostrarError('❌ Error de conexión: ' + error.message);
                });
        }

        // Función para cambiar foto
        function cambiarFoto() {
            let inputFoto = document.getElementById('inputFoto');
            if (!inputFoto) {
                inputFoto = document.createElement('input');
                inputFoto.type = 'file';
                inputFoto.id = 'inputFoto';
                inputFoto.accept = 'image/*';
                inputFoto.style.display = 'none';
                document.body.appendChild(inputFoto);

                inputFoto.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validar tamaño (máximo 2MB)
                        if (file.size > 2 * 1024 * 1024) {
                            mostrarError('La imagen es demasiado grande. Máximo 2MB');
                            return;
                        }

                        // Validar tipo
                        if (!file.type.startsWith('image/')) {
                            mostrarError('Por favor selecciona una imagen válida');
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('fotoPerfil').src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            inputFoto.click();
        }

        // Validar email
        function validarEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Mostrar mensaje de éxito
        function mostrarExito(mensaje) {
            document.getElementById('mensajeRespuesta').innerHTML = `
        <div class="alert alert-success">${mensaje}</div>
    `;
            setTimeout(() => {
                document.getElementById('mensajeRespuesta').innerHTML = '';
            }, 4000);
        }

        // Mostrar mensaje de error
        function mostrarError(mensaje) {
            document.getElementById('mensajeRespuesta').innerHTML = `
        <div class="alert alert-danger">${mensaje}</div>
    `;
            setTimeout(() => {
                document.getElementById('mensajeRespuesta').innerHTML = '';
            }, 5000);
        }


        function mostrarCambioPassword() {
            document.getElementById('modalPerfil').style.display = 'block';
            document.body.style.overflow = 'hidden';
            mostrarFormularioPassword();
        }

        function mostrarFormularioPassword() {
            const html = `
        <div class="perfil-container">
            <div class="perfil-header">
                <h2 class="nombre">Cambiar Contraseña</h2>
            </div>

            <div id="mensajeRespuestaPassword"></div>

            <form id="formPassword">
                <div class="form-group">
                    <label class="dato-user">Contraseña Actual:</label>
                    <input type="password" id="password_actual" autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label class="dato-user">Nueva Contraseña:</label>
                    <input type="password" id="password_nueva" autocomplete="new-password">
                    <small style="display:block; margin-top:8px; color:#a0aec0; font-size:12px;">
                        Mínimo 6 caracteres, al menos una letra y un número.
                    </small>
                </div>

                <div class="form-group">
                    <label class="dato-user">Repetir Nueva Contraseña:</label>
                    <input type="password" id="password_confirmar" autocomplete="new-password">
                    <small id="matchPassword" style="display:block; margin-top:8px; font-size:12px;"></small>
                </div>
            </form>

            <div style="text-align: center; margin-top: 30px;">
                <button type="button" class="btn btn-success" onclick="guardarPassword()">Actualizar Contraseña</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModalPerfil()">Cancelar</button>
            </div>
        </div>
    `;

            document.getElementById('contenidoModal').innerHTML = html;

            const nueva = document.getElementById('password_nueva');
            const confirmar = document.getElementById('password_confirmar');

            function validarCoincidencia() {
                const matchEl = document.getElementById('matchPassword');
                if (!confirmar.value) {
                    matchEl.textContent = '';
                    return;
                }
                if (nueva.value === confirmar.value) {
                    matchEl.textContent = '✔ Las contraseñas coinciden';
                    matchEl.style.color = '#22c55e';
                } else {
                    matchEl.textContent = '✘ Las contraseñas no coinciden';
                    matchEl.style.color = '#ef4444';
                }
            }

            nueva.addEventListener('input', validarCoincidencia);
            confirmar.addEventListener('input', validarCoincidencia);
        }

        function validarFortalezaPasswordFrontend(password) {
            const errores = [];
            if (password.length < 6) errores.push('al menos 6 caracteres');
            if (!/[a-zA-Z]/.test(password)) errores.push('al menos una letra');
            if (!/[0-9]/.test(password)) errores.push('al menos un número');
            return errores;
        }

        function guardarPassword() {
            const passwordActual = document.getElementById('password_actual').value;
            const passwordNueva = document.getElementById('password_nueva').value;
            const passwordConfirmar = document.getElementById('password_confirmar').value;

            if (!passwordActual || !passwordNueva || !passwordConfirmar) {
                mostrarErrorPassword('Todos los campos son obligatorios');
                return;
            }

            const erroresFortaleza = validarFortalezaPasswordFrontend(passwordNueva);
            if (erroresFortaleza.length > 0) {
                mostrarErrorPassword('La contraseña debe tener: ' + erroresFortaleza.join(', '));
                return;
            }

            if (passwordNueva !== passwordConfirmar) {
                mostrarErrorPassword('Las contraseñas nuevas no coinciden');
                return;
            }

            document.getElementById('mensajeRespuestaPassword').innerHTML = `
        <div class="alert alert-info">
            <div class="spinner" style="width: 20px; height: 20px; margin-right: 10px; display: inline-block;"></div>
            Actualizando contraseña...
        </div>
    `;

            fetch('/perfil/cambiar-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        password_actual: passwordActual,
                        password_nueva: passwordNueva,
                        password_confirmar: passwordConfirmar
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        document.getElementById('mensajeRespuestaPassword').innerHTML = `
                <div class="alert alert-success">✅ ${data.mensaje}. Te enviamos un email de confirmación.</div>
            `;
                        setTimeout(() => cerrarModalPerfil(), 2500);
                    } else {
                        mostrarErrorPassword('❌ ' + (data.mensaje || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarErrorPassword('❌ Error de conexión: ' + error.message);
                });
        }

        function mostrarErrorPassword(mensaje) {
            document.getElementById('mensajeRespuestaPassword').innerHTML = `
        <div class="alert alert-danger">${mensaje}</div>
    `;
        }

        function mostrarMisCompras() {
            document.getElementById('modalPerfil').style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.getElementById('contenidoModal').innerHTML = `
        <div class="perfil-container">
            <div class="perfil-header"><h2 class="nombre">Mis Compras</h2></div>
            <div class="loading">
                <div class="spinner"></div>
                <p>Cargando tus compras...</p>
            </div>
        </div>
    `;

            fetch('/perfil/mis-compras')
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        renderizarMisCompras(data.entradas, data.pedidos);
                    } else {
                        mostrarErrorMisCompras(data.mensaje || 'Error al cargar tus compras');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarErrorMisCompras('Error de conexión: ' + error.message);
                });
        }

        function mostrarErrorMisCompras(mensaje) {
            document.getElementById('contenidoModal').innerHTML = `
        <div class="perfil-container">
            <div class="perfil-header"><h2 class="nombre">Mis Compras</h2></div>
            <div class="alert alert-danger">${mensaje}</div>
        </div>
    `;
        }

        function renderizarMisCompras(entradas, pedidos) {
            const html = `
        <div class="perfil-container">
            <div class="perfil-header"><h2 class="nombre">Mis Compras</h2></div>

            <div class="tabs-compras">
                <button type="button" class="tab-btn active" id="tabBtnEntradas" onclick="cambiarTabCompras('entradas')">
                    <i class="fa-solid fa-ticket"></i> Entradas
                </button>
                <button type="button" class="tab-btn" id="tabBtnPedidos" onclick="cambiarTabCompras('pedidos')">
                    <i class="fa-solid fa-cookie-bite"></i> Cantina y fichas
                </button>
            </div>

            <div id="tabEntradas" class="tab-content-compras">
                ${renderizarEntradas(entradas)}
            </div>

            <div id="tabPedidos" class="tab-content-compras" style="display:none;">
                ${renderizarPedidos(pedidos)}
            </div>
        </div>
    `;

            document.getElementById('contenidoModal').innerHTML = html;
        }

        function cambiarTabCompras(tab) {
            document.getElementById('tabEntradas').style.display = tab === 'entradas' ? 'block' : 'none';
            document.getElementById('tabPedidos').style.display = tab === 'pedidos' ? 'block' : 'none';

            document.getElementById('tabBtnEntradas').classList.toggle('active', tab === 'entradas');
            document.getElementById('tabBtnPedidos').classList.toggle('active', tab === 'pedidos');
        }

        function renderizarEntradas(entradas) {
            if (!entradas || entradas.length === 0) {
                return '<p style="text-align:center; color:#a0aec0; padding: 30px 0;">Todavía no compraste entradas.</p>';
            }

            const vigentes = entradas.filter(e => e.estado_vigencia === 'vigente')
                .sort((a, b) => a.fecha_hora.localeCompare(b.fecha_hora)); // más próxima primero

            const historial = entradas.filter(e => e.estado_vigencia !== 'vigente')
                .sort((a, b) => b.fecha_hora.localeCompare(a.fecha_hora)); // más reciente primero

            let html = '';
            if (vigentes.length > 0) {
                html += '<h4 style="color:#ed850f; margin: 15px 0 10px;">Vigentes</h4>';
                html += vigentes.map(tarjetaEntrada).join('');
            }
            if (historial.length > 0) {
                html += '<h4 style="color:#a0aec0; margin: 25px 0 10px;">Historial</h4>';
                html += historial.map(tarjetaEntrada).join('');
            }
            return html;
        }

        function tarjetaEntrada(e) {
            const badges = {
                'vigente': '<span class="badge-compra badge-vigente">Vigente</span>',
                'vencida': '<span class="badge-compra badge-vencida">Vencida</span>',
                'usada': '<span class="badge-compra badge-usada">Usada</span>',
                'cancelada': '<span class="badge-compra badge-cancelada">Cancelada</span>'
            };
            const fecha = new Date(e.fecha_hora).toLocaleString('es-AR', {
                dateStyle: 'short',
                timeStyle: 'short'
            });

            const butaca = e.fila_butaca != null ? ` · Fila ${escaparHtml(e.fila_butaca)} · Asiento ${escaparHtml(e.numero_butaca)}` : '';
            const ticket = e.numero_ticket_entrada ? `Ticket #${escaparHtml(e.numero_ticket_entrada)} · ` : '';

            // Las vigentes traen su QR: se despliega debajo de la tarjeta con "Ver QR"
            const botonQR = e.qr ? `
                <button type="button" class="btn-ver-qr" aria-expanded="false" aria-controls="qr-entrada-${e.id_entrada}"
                    onclick="alternarQREntrada(this, 'qr-entrada-${e.id_entrada}')">
                    <i class="fa-solid fa-qrcode"></i> Ver QR
                </button>` : '';
            const panelQR = e.qr ? `
                <div class="entrada-qr-panel" id="qr-entrada-${e.id_entrada}" hidden>
                    <img src="${e.qr}" alt="Código QR de la entrada">
                    <p>Mostrá este código en la entrada de la sala</p>
                    <p class="entrada-qr-codigo">Código: <strong>${escaparHtml(e.codigo_corto)}</strong></p>
                </div>` : '';

            return `
        <div class="entrada-compra">
            <div class="tarjeta-compra">
                <div class="tarjeta-compra-info">
                    <h4>${escaparHtml(e.titulo_pelicula)}</h4>
                    <p>Sala ${escaparHtml(e.id_sala)} — ${fecha}${butaca}</p>
                    <p>${ticket}${escaparHtml(e.tipo_entrada_desc)}</p>
                </div>
                <div class="tarjeta-compra-estado">${badges[e.estado_vigencia] || ''}${botonQR}</div>
            </div>
            ${panelQR}
        </div>
    `;
        }

        function alternarQREntrada(boton, idPanel, textoVer = 'Ver QR') {
            const panel = document.getElementById(idPanel);
            panel.hidden = !panel.hidden;
            boton.setAttribute('aria-expanded', String(!panel.hidden));
            boton.innerHTML = panel.hidden
                ? `<i class="fa-solid fa-qrcode"></i> ${textoVer}`
                : '<i class="fa-solid fa-xmark"></i> Ocultar QR';
        }

        function escaparHtml(valor) {
            const div = document.createElement('div');
            div.textContent = valor ?? '';
            return div.innerHTML;
        }

        // Productos y fichas comprados por la web: un pedido por compra, con su QR de retiro
        // mientras quede algo por retirar en la cantina
        function renderizarPedidos(pedidos) {
            if (!pedidos || pedidos.length === 0) {
                return '<p style="text-align:center; color:#a0aec0; padding: 30px 0;">Todavía no compraste productos ni fichas.</p>';
            }

            const pendientes = pedidos.filter(p => !p.retirado);
            const retirados = pedidos.filter(p => p.retirado);

            let html = '';
            if (pendientes.length > 0) {
                html += '<h4 style="color:#ed850f; margin: 15px 0 10px;">Para retirar</h4>';
                html += pendientes.map(tarjetaPedido).join('');
            }
            if (retirados.length > 0) {
                html += '<h4 style="color:#a0aec0; margin: 25px 0 10px;">Retirados</h4>';
                html += retirados.map(tarjetaPedido).join('');
            }
            return html;
        }

        function tarjetaPedido(p) {
            const fecha = new Date(String(p.fecha).replace(' ', 'T')).toLocaleString('es-AR', {
                dateStyle: 'short',
                timeStyle: 'short'
            });
            const productos = p.productos.map(x => `
                <li>${escaparHtml(x.nombre)} — compró ${x.comprado} · ${x.quedan === 0
                    ? '<span class="pedido-retirado">✓ retirado</span>'
                    : `retiró ${x.entregado} · <strong>quedan ${x.quedan}</strong>`}</li>`).join('');

            const idPanel = `qr-pedido-${p.id_orden}`;
            const botonQR = p.qr ? `
                <button type="button" class="btn-ver-qr" aria-expanded="false" aria-controls="${idPanel}"
                    onclick="alternarQREntrada(this, '${idPanel}', 'Ver QR de retiro')">
                    <i class="fa-solid fa-qrcode"></i> Ver QR de retiro
                </button>` : '';
            const panelQR = p.qr ? `
                <div class="entrada-qr-panel" id="${idPanel}" hidden>
                    <img src="${p.qr}" alt="Código QR de retiro del pedido">
                    <p>Mostralo en la cantina: podés retirar todo junto o de a poco</p>
                    <p class="entrada-qr-codigo">Código: <strong>${escaparHtml(p.codigo_corto)}</strong></p>
                </div>` : '';

            return `
        <div class="entrada-compra">
            <div class="tarjeta-compra">
                <div class="tarjeta-compra-info">
                    <h4>Pedido #${escaparHtml(p.numero_orden || p.id_orden)}</h4>
                    <p>${fecha}</p>
                    <ul class="pedido-productos">${productos}</ul>
                </div>
                <div class="tarjeta-compra-estado">
                    <strong style="color:#ed850f;">$${Number(p.total).toLocaleString('es-AR')}</strong>
                    ${p.retirado ? '<span class="badge-compra badge-usada">Retirado</span>' : ''}
                    ${botonQR}
                </div>
            </div>
            ${panelQR}
        </div>
    `;
        }
    </script>

    <script src="../../../assets/js/submenu-adm.js"></script>
</div>