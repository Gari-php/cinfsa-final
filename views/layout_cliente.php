<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CINFSA</title>

    <!-- Fuente Google-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Montserrat+Alternates|Poppins&display=swap" rel="stylesheet">

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script>window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";</script>
    <script src="/assets/js/csrf.js"></script>

    <!-- Estilo para fuente -->
    <style>
        * {
            font-family: 'Montserrat', 'Poppins', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            font-size: 15px !important;
        }
    </style>

    <!-- mis estilos -->
    <?php if (empty($sinBase)): ?>
        <link rel="stylesheet" href="/assets/css/base.css">
    <?php endif; ?>

    <?php if (isset($vista)): ?>
        <link rel="stylesheet" href="/assets/css/<?php echo $vista; ?>.css">
    <?php endif; ?>
    <script src="/assets/js/carrito.js"></script>
    <link rel="stylesheet" href="/assets/css/alertas.css">
</head>

<body class="<?php echo $esPerfil ? 'perfil-abierto' : ''; ?>">

    <?php
    $session_user   = $_SESSION['nombre_usuario'] ?? null;
    $session_perfil = $_SESSION['perfil'] ?? null;
    $session_id     = $_SESSION['id_usuario'] ?? null;
    $esPerfil = (strpos($_SERVER['REQUEST_URI'], '/perfil') === 0);

    // Buscar datos del usuario actual directo desde la sesión
    // (no depende de que el controller pase $perfil)
    $usuarioActual = null;
    if ($session_id) {
        $db = \Models\ActiveRecord::getDB();
        $idEscaped = $db->escape_string($session_id);
        $queryUsuario = "SELECT u.id_usuario, u.nombre_usuario, u.email, u.foto_perfil,
                        p.nombre_persona, p.apellido_persona, p.rela_sexo,
                        s.nombre_sexo, pe.nombre_perfil
                 FROM usuarios u
                 LEFT JOIN personas p ON u.id_persona = p.id_persona
                 LEFT JOIN sexo s ON p.rela_sexo = s.id_sexo
                 LEFT JOIN perfiles pe ON u.rela_perfil = pe.id_perfiles
                 WHERE u.id_usuario = '{$idEscaped}'
                 LIMIT 1";
        $resUsuario = $db->query($queryUsuario);
        if ($resUsuario && $resUsuario->num_rows > 0) {
            $usuarioActual = $resUsuario->fetch_assoc();
        }
    }
    ?>

    <header class="header">
        <div class="container">
            <div class="btn-menu">
                <label for="btn-menu"><i class="fa-solid fa-lines-leaning"></i></label>
            </div>
            <div class="logo">
                <img src="../../assets/img/LOGO.png" alt="logo">
            </div>

            <div class="mobile-menu">
                <a href="#navegacion">
                    <i class="fas fa-bars"></i>
                </a>
            </div>
        </div>
    </header>

    <nav class="urls" id="navegacion">
        <ul>
            <li><a href="/menu"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="/juegos"><i class="fa-solid fa-gamepad"></i> Sala de Juegos</a></li>
            <li><a href="/funciones"><i class="fa-solid fa-ticket"></i> Funciones</a></li>
            <li><a href="/cantina"><i class="fa-solid fa-store"></i> Cantina</a></li>
            <li><a href="/peliculas"><i class="fa-solid fa-film"></i> Películas</a></li>
            <li><a href="/calificacion-peliculas"><i class="fa-solid fa-film"></i> Ver Calificaciones</a></li>


            <li class="carrito-nav">
                <a href="#" id="carritoNavBtn" class="carrito-nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Carrito</span>
                    <span class="carrito-badge" id="carritoCount">0</span>
                </a>


                <div class="carrito-dropdown" id="carritoDropdown">
                    <div class="carrito-header">
                        <div class="carrito-titulo">
                            <h3><i class="fas fa-shopping-cart"></i> Mi Carrito</h3>
                        </div>
                        <button class="btn-cerrar-carrito" id="btnCerrarCarrito">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="carrito-contenido">
                        <!-- CARRITO VACÍO -->
                        <div class="carrito-vacio" id="carritoVacio">
                            <i class="fas fa-shopping-cart carrito-vacio-icono"></i>
                            <p>Tu carrito está vacío</p>
                        </div>

                        <!-- TABLA DEL CARRITO -->
                        <table class="lista-carrito" id="listaCarrito" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="carritoTableBody">
                                <!-- Items se cargan dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <div class="carrito-footer" id="carritoFooter" style="display: none;">
                        <div class="carrito-total">
                            <strong>Total: $<span id="carritoTotalAmount">0</span></strong>
                        </div>
                        <div class="carrito-acciones">
                            <button class="btn-vaciar-carrito" id="btnVaciarCarrito">
                                <i class="fas fa-trash-alt"></i> Vaciar
                            </button>
                            <button class="btn-ir-comprar" id="btnIrComprar">
                                <i class="fas fa-credit-card"></i> Ir a Comprar
                            </button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </nav>

    <div class="notificacion" id="notificacion"></div>
    <div class="capa"></div>
    <input type="checkbox" id="btn-menu" />
    <div class="container-menu">
        <div class="cont-menu">
            <figure class="full-box nav-lateral-avatar">
                <?php if (!empty($usuarioActual['foto_perfil'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo trim($usuarioActual['foto_perfil']); ?>"
                        alt="Foto perfil"
                        onerror="this.src='../../assets/img/LOGO.png'">
                <?php else: ?>
                    <img src="../../assets/img/perfil-avatar.png" alt="Avatar por defecto">
                <?php endif; ?>

                <figcaption class="roboto-medium text-center">
                    <?php echo "CLIENTE" ?> <br>
                    <small class="roboto-condensed-light">@<?php echo $session_user ?> </small>
                </figcaption>
            </figure>
            <nav>
                <a href="/menu">INICIO</a>
                <a href="/perfil">Mi Perfil</a>
                <?php if ($esPerfil): ?>
                    <a href="javascript:void(0)" onclick="mostrarDatos()">Mis Datos</a>
                    <a href="javascript:void(0)" onclick="mostrarCambioPassword()">Cambiar Contraseña</a>
                    <a href="javascript:void(0)" onclick="mostrarMisCompras()">Mis Compras</a>
                <?php else: ?>
                    <a href="/funciones">FUNCIONES</a>
                    <a href="/cantina">CANTINA</a>
                    <a href="/juegos">SALA DE JUEGOS</a>
                    <a href="/calificacion-peliculas">CALIFICACIÓN DE PELÍCULAS</a>
                <?php endif; ?>
                <a href="/calificar-peliculas">CALIFICAR PELÍCULAS</a>
                <a href="/">CERRAR SESIÓN</a>
            </nav>
            <label for="btn-menu"><i class="fa-regular fa-circle-xmark"></i></label>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        <?php echo $contenido; ?>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Columna 1: Logo y descripción -->
            <div class="footer-column">
                <div class="footer-logo">
                    <img src="../../assets/img/LOGO.png" alt="CINFSA Logo">
                    <h3>CINFSA</h3>
                </div>
                <p class="footer-description">
                    Tu complejo de entretenimiento favorito. Disfruta de las mejores películas,
                    juegos y experiencias gastronómicas en un solo lugar.
                </p>
                <div class="footer-social">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Columna 2: Enlaces rápidos -->
            <div class="footer-column">
                <h4>Enlaces Rápidos</h4>
                <ul class="footer-links">
                    <li><a href="/menu"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="/peliculas"><i class="fas fa-film"></i> Cartelera</a></li>
                    <li><a href="/funciones"><i class="fas fa-ticket"></i> Funciones</a></li>
                    <li><a href="/cantina"><i class="fas fa-utensils"></i> Cantina</a></li>
                    <li><a href="/juegos"><i class="fas fa-gamepad"></i> Sala de Juegos</a></li>
                    <li><a href="/calificacion-peliculas"><i class="fa-solid fa-film"></i> Ver Calificaciones</a></li>
                </ul>
            </div>

            <!-- Columna 3: Información y FAQ -->
            <div class="footer-column">
                <h4>Información</h4>
                <ul class="footer-links">
                    <li><a href="#" onclick="abrirModalQuienesSomos()"><i class="fas fa-users"></i> Quiénes Somos</a></li>
                    <li><a href="#" onclick="abrirModalFAQ()"><i class="fas fa-question-circle"></i> Preguntas Frecuentes</a></li>
                    <li><a href="#" onclick="abrirModalPrivacidad()"><i class="fas fa-shield-alt"></i> Política de Privacidad</a></li>
                    <li><a href="#" onclick="abrirModalTerminos()"><i class="fas fa-file-contract"></i> Términos y Condiciones</a></li>
                    <li><a href="#" onclick="abrirModalContacto()"><i class="fas fa-envelope"></i> Contacto</a></li>
                    <li><a href="/soporte"><i class="fas fa-headset"></i> Soporte y Reclamos</a></li>
                </ul>
            </div>

            <!-- Columna 4: Contacto -->
            <div class="footer-column">
                <h4>Contacto</h4>
                <div class="footer-contact">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email:</strong>
                            <a href="mailto:cinfsa3@gmail.com">cinfsa3@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Teléfono:</strong>
                            <span>+54 11 1234-5678</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Dirección:</strong>
                            <span>Av. Italia, Formosa, Argentina</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Horarios:</strong>
                            <span>Lun-Dom: 14:00 - 02:00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <div class="footer-container">
                <div class="footer-copyright">
                    <p>&copy; 2025 CINFSA - Complejo de Entretenimiento. Todos los derechos reservados.</p>
                    <p class="footer-dev">Desarrollado con para brindar la mejor experiencia de entretenimiento</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- MODALES -->
    <!-- Modal Quiénes Somos -->
    <div id="modalQuienesSomos" class="modal-footer">
        <div class="modal-footer-content">
            <div class="modal-footer-header">
                <h3><i class="fas fa-users"></i> Quiénes Somos</h3>
                <span class="close-modal-footer" onclick="cerrarModal('modalQuienesSomos')">&times;</span>
            </div>
            <div class="modal-footer-body">
                <p>CINFSA es un complejo de entretenimiento moderno que combina la magia del cine con experiencias gastronómicas y de juegos únicas.</p>

                <h4>Nuestra Historia</h4>
                <p>Fundado en 2020, CINFSA nació con la visión de crear un espacio donde las familias y amigos puedan disfrutar de momentos inolvidables. Desde nuestros inicios, nos hemos comprometido a ofrecer la mejor tecnología en proyección, sonido envolvente y comodidad para nuestros visitantes.</p>

                <h4>Nuestra Misión</h4>
                <p>Brindar experiencias de entretenimiento excepcionales, combinando tecnología de vanguardia con un servicio cálido y profesional, creando momentos memorables para toda la familia.</p>

                <h4>Nuestros Valores</h4>
                <ul>
                    <li><strong>Calidad:</strong> Comprometidos con la excelencia en cada detalle</li>
                    <li><strong>Innovación:</strong> Incorporamos las últimas tecnologías</li>
                    <li><strong>Familia:</strong> Un espacio seguro y acogedor para todos</li>
                    <li><strong>Comunidad:</strong> Apoyamos el crecimiento local</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal FAQ -->
    <div id="modalFAQ" class="modal-footer">
        <div class="modal-footer-content">
            <div class="modal-footer-header">
                <h3><i class="fas fa-question-circle"></i> Preguntas Frecuentes</h3>
                <span class="close-modal-footer" onclick="cerrarModal('modalFAQ')">&times;</span>
            </div>
            <div class="modal-footer-body">
                <div class="faq-item">
                    <h4>¿Cuáles son los horarios de atención?</h4>
                    <p>Estamos abiertos de lunes a domingo de 14:00 a 02:00 horas.</p>
                </div>

                <div class="faq-item">
                    <h4>¿Puedo reservar entradas con anticipación?</h4>
                    <p>Sí, puedes reservar tus entradas a través de nuestra plataforma online o visitando nuestras taquillas.</p>
                </div>

                <div class="faq-item">
                    <h4>¿Tienen descuentos para estudiantes?</h4>
                    <p>Ofrecemos descuentos especiales para estudiantes los días miércoles presentando credencial vigente.</p>
                </div>

                <div class="faq-item">
                    <h4>¿Puedo ingresar alimentos del exterior?</h4>
                    <p>Por políticas de la empresa, no se permite el ingreso de alimentos externos. Contamos con una amplia variedad en nuestra cantina.</p>
                </div>

                <div class="faq-item">
                    <h4>¿Tienen sala de juegos para niños?</h4>
                    <p>Contamos con una sala de juegos con opciones para todas las edades, supervisada y segura.</p>
                </div>

                <div class="faq-item">
                    <h4>¿Qué métodos de pago aceptan?</h4>
                    <p>Aceptamos efectivo, tarjetas de débito, crédito y transferencias bancarias.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Contacto -->
    <div id="modalContacto" class="modal-footer">
        <div class="modal-footer-content">
            <div class="modal-footer-header">
                <h3><i class="fas fa-envelope"></i> Contáctanos</h3>
                <span class="close-modal-footer" onclick="cerrarModal('modalContacto')">&times;</span>
            </div>
            <div class="modal-footer-body">
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="nombre">Nombre completo:</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="asunto">Asunto:</label>
                            <select id="asunto" name="asunto" required>
                                <option value="">Selecciona un asunto</option>
                                <option value="consulta">Consulta general</option>
                                <option value="reserva">Reservas</option>
                                <option value="reclamo">Reclamo</option>
                                <option value="sugerencia">Sugerencia</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="mensaje">Mensaje:</label>
                            <textarea id="mensaje" name="mensaje" rows="5" required></textarea>
                        </div>

                        <button type="submit" class="btn-enviar">
                            <i class="fas fa-paper-plane"></i> Enviar Mensaje
                        </button>
                    </form>
                </div>

                <div class="contact-info-modal">
                    <h4>Información de Contacto</h4>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> cinfsa3@gmail.com</p>
                    <p><i class="fas fa-phone"></i> <strong>Teléfono:</strong> +54 11 1234-5678</p>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Dirección:</strong> Av. Principal 123, Formosa, Argentina</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Privacidad -->
    <div id="modalPrivacidad" class="modal-footer">
        <div class="modal-footer-content">
            <div class="modal-footer-header">
                <h3><i class="fas fa-shield-alt"></i> Política de Privacidad</h3>
                <span class="close-modal-footer" onclick="cerrarModal('modalPrivacidad')">&times;</span>
            </div>
            <div class="modal-footer-body">
                <h4>Recopilación de Información</h4>
                <p>En CINFSA, recopilamos información que nos proporcionas directamente, como cuando creas una cuenta, realizas una reserva o te comunicas con nosotros.</p>

                <h4>Uso de la Información</h4>
                <p>Utilizamos la información recopilada para:</p>
                <ul>
                    <li>Procesar tus reservas y pagos</li>
                    <li>Mejorar nuestros servicios</li>
                    <li>Comunicarnos contigo sobre promociones</li>
                    <li>Personalizar tu experiencia</li>
                </ul>

                <h4>Protección de Datos</h4>
                <p>Implementamos medidas de seguridad técnicas y organizativas para proteger tu información personal contra accesos no autorizados, alteración, divulgación o destrucción.</p>

                <h4>Compartir Información</h4>
                <p>No vendemos, intercambiamos ni transferimos tu información personal a terceros sin tu consentimiento, excepto en casos requeridos por ley.</p>
            </div>
        </div>
    </div>

    <!-- Modal Términos -->
    <div id="modalTerminos" class="modal-footer">
        <div class="modal-footer-content">
            <div class="modal-footer-header">
                <h3><i class="fas fa-file-contract"></i> Términos y Condiciones</h3>
                <span class="close-modal-footer" onclick="cerrarModal('modalTerminos')">&times;</span>
            </div>
            <div class="modal-footer-body">
                <h4>Términos de Uso</h4>
                <p>Al utilizar nuestros servicios, aceptas cumplir con estos términos y condiciones.</p>

                <h4>Reservas y Pagos</h4>
                <ul>
                    <li>Las reservas están sujetas a disponibilidad</li>
                    <li>Los pagos deben realizarse al momento de la reserva</li>
                    <li>Las cancelaciones deben hacerse con 2 horas de anticipación</li>
                </ul>

                <h4>Comportamiento en las Instalaciones</h4>
                <ul>
                    <li>Mantener un comportamiento respetuoso</li>
                    <li>No está permitido fumar en las instalaciones</li>
                    <li>Seguir las indicaciones del personal</li>
                    <li>No ingresar alimentos o bebidas del exterior</li>
                </ul>

                <h4>Responsabilidades</h4>
                <p>CINFSA no se hace responsable por objetos perdidos o dañados dentro de las instalaciones.</p>
            </div>
        </div>
    </div>

    <!-- Modal de Alerta Personalizada -->
    <div id="modalAlerta" class="modal-alerta">
        <div class="modal-alerta-content">
            <div class="modal-alerta-header">
                <i class="fas fa-exclamation-circle"></i>
                <h3 id="modalAlertaTitulo">Confirmación</h3>
            </div>
            <div class="modal-alerta-body">
                <p id="modalAlertaMensaje"></p>
            </div>
            <div class="modal-alerta-footer">
                <button class="btn-alerta btn-alerta-cancelar" onclick="cerrarModalAlerta()">Cancelar</button>
                <button class="btn-alerta btn-alerta-confirmar" id="btnConfirmarAlerta">Confirmar</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="/assets/css/alertas.css">
    <script type="module" src="/assets/js/formularios.js"></script>

    <!-- ⭐ SCRIPT MENÚ HAMBURGUESA ⭐ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            eventListeners();
        });

        function eventListeners() {
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenu) {
                mobileMenu.addEventListener('click', navegacionResponsive);
            }
        }

        function navegacionResponsive() {
            const navegacion = document.querySelector('.urls');

            if (navegacion.classList.contains('mostrar')) {
                navegacion.classList.remove('mostrar');
            } else {
                navegacion.classList.add('mostrar');
            }
        }
    </script>

    <script>
        let accionConfirmada = null;

        function mostrarAlertaPersonalizada(titulo, mensaje, callback) {
            const modal = document.getElementById('modalAlerta');
            const tituloEl = document.getElementById('modalAlertaTitulo');
            const mensajeEl = document.getElementById('modalAlertaMensaje');
            const btnConfirmar = document.getElementById('btnConfirmarAlerta');

            tituloEl.textContent = titulo;
            mensajeEl.textContent = mensaje;
            modal.style.display = 'block';

            // Limpiar listeners anteriores
            const nuevoBtn = btnConfirmar.cloneNode(true);
            btnConfirmar.parentNode.replaceChild(nuevoBtn, btnConfirmar);

            // Agregar nuevo listener
            document.getElementById('btnConfirmarAlerta').onclick = function() {
                cerrarModalAlerta();
                if (callback) callback();
            };
        }

        function cerrarModalAlerta() {
            document.getElementById('modalAlerta').style.display = 'none';
        }

        // Cerrar al hacer clic fuera del modal
        window.onclick = function(event) {
            const modal = document.getElementById('modalAlerta');
            if (event.target === modal) {
                cerrarModalAlerta();
            }
        };

        function abrirModalQuienesSomos() {
            document.getElementById('modalQuienesSomos').style.display = 'flex';
        }

        function abrirModalFAQ() {
            document.getElementById('modalFAQ').style.display = 'flex';
        }

        function abrirModalContacto() {
            document.getElementById('modalContacto').style.display = 'flex';
        }

        function abrirModalPrivacidad() {
            document.getElementById('modalPrivacidad').style.display = 'flex';
        }

        function abrirModalTerminos() {
            document.getElementById('modalTerminos').style.display = 'flex';
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Cerrar modales al hacer click fuera
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-footer')) {
                e.target.style.display = 'none';
            }
        });

        // Manejar formulario de contacto
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formulario = this;
            const btnEnviar = formulario.querySelector('.btn-enviar');
            const textoOriginal = btnEnviar.innerHTML;
            const datos = new FormData(formulario);

            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            try {
                const respuesta = await fetch('/contacto/enviar', {
                    method: 'POST',
                    body: datos
                });
                const data = await respuesta.json();

                if (data.ok) {
                    if (typeof mostrarAlerta === 'function') {
                        mostrarAlerta(data.mensaje, 'exito');
                    } else {
                        alert(data.mensaje);
                    }
                    formulario.reset();
                    cerrarModal('modalContacto');
                } else {
                    const mensajeError = (data.errores || []).join('<br>') || data.mensaje || 'Ocurrió un error al enviar el mensaje';
                    if (typeof mostrarAlerta === 'function') {
                        mostrarAlerta(mensajeError, 'error');
                    } else {
                        alert(mensajeError);
                    }
                }
            } catch (error) {
                console.error('Error al enviar contacto:', error);
                if (typeof mostrarAlerta === 'function') {
                    mostrarAlerta('Error de conexión. Intenta nuevamente.', 'error');
                } else {
                    alert('Error de conexión. Intenta nuevamente.');
                }
            } finally {
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = textoOriginal;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const btnComprar = document.getElementById('btnIrComprar');
            if (btnComprar) {
                btnComprar.addEventListener('click', function() {
                    window.location.href = '/carrito/checkout';
                });
            }
        });
    </script>

</body>

<style>
    /* ===== ESTILOS BASE ===== */
    body {
        background-color: #363130;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    body.perfil-abierto .main-content {
        margin-left: 280px;
        transition: margin-left 0.3s ease;
    }

    body.perfil-abierto .container-menu {
        background: transparent;
    }

    body.perfil-abierto .cont-menu label {
        display: none;
    }

    @media (max-width: 768px) {
        body.perfil-abierto .main-content {
            margin-left: 0;
        }
    }

    .main-content {
        flex: 1;
        min-height: calc(100vh - 200px);
    }

    button {
        border: none;
        background-color: transparent;
        padding: 0;
        cursor: pointer;
    }

    button:focus {
        outline: none;
    }

    button img {
        position: relative;
        top: 5px;
        display: block;
    }

    /* ===== HEADER ===== */
    .nav-arriba {
        background-color: none !important;
        padding-top: -20px;
    }

    header {
        display: flex;
        justify-content: space-between;
        padding: 10px;
    }

    .header {
        width: 100%;
        height: 100px;
        position: relative;
        top: 0;
        left: 0;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: auto;
    }

    .container .btn-menu,
    .logo {
        float: left;
        line-height: 100px;
    }

    .logo img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        margin-top: 8%;
    }

    .container .btn-menu label {
        color: #ed850f;
        font-size: 25px;
        cursor: pointer;
    }

    .container .menu {
        float: right;
        line-height: 100px;
    }

    .container .menu a {
        display: inline-block;
        padding: 15px;
        line-height: normal;
        text-decoration: none;
        color: #ed850f;
        transition: all 0.3s ease;
        border-bottom: 2px solid transparent;
        font-size: 15px;
        margin-right: 5px;
    }

    .container .menu a:hover {
        border-bottom: 2px solid #ed850f;
        padding-bottom: 5px;
    }

    /* ⭐ MENÚ HAMBURGUESA MÓVIL ⭐ */
    .mobile-menu {
        display: none;
    }

    .mobile-menu a {
        color: #ed850f;
        font-size: 28px;
        cursor: pointer;
    }

    /* ===== NAVEGACIÓN ===== */
    .urls {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        width: 100%;
        text-align: center;
        height: 55px;
        margin-top: 2%;
        border-top: 3px solid #ed850f;
        border-bottom: 3px solid #ed850f;
        box-shadow:
            0 -2px 8px rgba(237, 133, 15, 0.3),
            0 2px 8px rgba(237, 133, 15, 0.3);
    }

    nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }

    nav ul li {
        color: #fff;
        line-height: 55px;
        margin: 0 8px;
        transition: all 0.3s ease;
        border-radius: 8px;
        position: relative;
    }

    nav ul li a {
        text-decoration: none;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        font-size: 14px !important;
        font-weight: 500;
        white-space: nowrap;
        transition: all 0.3s ease;
    }

    nav ul li a i {
        font-size: 16px !important;
        color: #ed850f;
    }

    nav ul li:hover a {
        color: #fff;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .carrito-nav {
        position: relative;
    }

    .carrito-nav-link {
        position: relative;
        cursor: pointer;
    }

    .buscador {
        display: flex !important;
        align-items: center;
        width: auto !important;
        margin-left: 20px !important;
    }

    .buscador button {
        background: rgba(45, 55, 72, 0.8);
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px 0 0 6px;
        transition: all 0.3s ease;
    }

    .buscador button:hover {
        background: rgba(45, 55, 72, 1);
    }

    .barra_buscador {
        width: 180px !important;
        height: 32px !important;
        margin-left: 0 !important;
        padding: 0 12px;
        border: none;
        border-radius: 0 6px 6px 0;
        background: rgba(255, 255, 255, 0.95);
        color: #333;
        font-size: 13px !important;
        transition: all 0.3s ease;
    }

    .barra_buscador:focus {
        outline: none;
        background: #fff;
        box-shadow: 0 0 8px rgba(255, 107, 53, 0.4);
    }

    .barra_buscador::placeholder {
        color: #666;
        font-size: 13px !important;
    }

    /* Avatar del usuario */
    .nav-lateral-avatar {
        position: relative;
        top: 20px;
        text-align: center;
        margin-bottom: 30px;
        position: relative;
    }

    .nav-lateral-avatar img {
        width: 90px;
        border-radius: 50%;
        border: 3px solid #3498db;
        margin-bottom: 10px;
    }

    .nav-lateral-avatar i {
        position: absolute;
        top: 10px;
        right: 10px;
        color: #bbb;
        font-size: 20px;
        cursor: pointer;
    }

    /* Nombre de usuario */
    .nav-lateral-avatar figcaption {
        font-weight: bold;
        font-size: 16px;
        color: #ecf0f1;
    }

    .container .btn-menu,
    .logo {
        float: left;
        line-height: 100px;
    }

    .logo img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        margin-top: 8%;
    }

    .container .btn-menu label {
        color: #ed850f;
        font-size: 25px;
        cursor: pointer;
    }

    .logo h1 {
        color: #fff;
        font-weight: 400;
        font-size: 22px;
        margin-left: 10px;
    }

    .container .menu {
        float: right;
        line-height: 100px;
    }

    .container .menu a {
        display: inline-block;
        padding: 15px;
        line-height: normal;
        text-decoration: none;
        color: #ed850f;
        transition: all 0.3s ease;
        border-bottom: 2px solid transparent;
        font-size: 15px;
        margin-right: 5px;
    }

    .container .menu a:hover {
        border-bottom: 2px solid #ed850f;
        padding-bottom: 5px;
    }

    /*Fin de Estilos para el encabezado*/

    /*Menù lateral*/
    #btn-menu {
        display: none;
    }

    .container-menu {
        position: fixed;
        width: 100%;
        height: 100vh;
        top: 0;
        left: 0;
        transition: all 500ms ease;
        opacity: 0;
        visibility: hidden;
        z-index: 1300;
        background: rgba(0, 0, 0, 0.5);
    }

    #btn-menu:checked~.container-menu {
        opacity: 1;
        visibility: visible;
    }

    .cont-menu {
        width: 100%;
        max-width: 280px;
        background: #1a1a1a;
        height: 100vh;
        position: relative;
        transition: all 500ms ease;
        transform: translateX(-100%);
        padding: 20px 15px;
        box-sizing: border-box;
        overflow-y: auto;
    }

    #btn-menu:checked~.container-menu .cont-menu {
        transform: translateX(0%);
    }

    .cont-menu::-webkit-scrollbar {
        width: 8px;
    }

    .cont-menu::-webkit-scrollbar-track {
        background: #1a1a1a;
    }

    .cont-menu::-webkit-scrollbar-thumb {
        background-color: #ed850f;
        border-radius: 4px;
    }

    .cont-menu::-webkit-scrollbar-thumb:hover {
        background-color: #ffa733;
    }

    .nav-lateral-avatar {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #3a3a3a;
        position: relative;
        top: 0;
    }

    .nav-lateral-avatar img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #ed850f;
        margin-bottom: 12px;
    }

    .nav-lateral-avatar figcaption {
        font-weight: bold;
        font-size: 14px !important;
        color: #ed850f;
        letter-spacing: 0.5px;
    }

    .cont-menu nav {
        transform: none;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cont-menu nav a {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        padding: 12px 15px;
        color: #fff;
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 8px;
        font-size: 14px !important;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .cont-menu nav a:hover {
        border-color: #ed850f;
        background: #364154;
        box-shadow: 0 0 8px rgba(237, 133, 15, 0.3);
    }

    .cont-menu nav a:last-child {
        margin-top: 10px;
        background: rgba(200, 35, 51, 0.15);
        border-color: #c82333;
        color: #fca5a5;
    }

    .cont-menu nav a:last-child:hover {
        background: #c82333;
        color: #fff;
        box-shadow: none;
    }

    .cont-menu label {
        position: absolute;
        right: 15px;
        top: 15px;
        color: #fff;
        cursor: pointer;
        font-size: 22px !important;
        z-index: 10;
    }

    /*Fin de Menù lateral*/
    /* ===== CARRITO EN NAVEGACIÓN ===== */
    .carrito-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px !important;
        font-weight: bold;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }

        100% {
            transform: scale(1);
        }
    }

    /* ===== CARRITO DROPDOWN ===== */
    .carrito-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 450px;
        max-height: 500px;
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
        margin-top: 10px;
    }

    .carrito-dropdown.active {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .carrito-header {
        background: linear-gradient(135deg, #ff6b35, #f7931e);
        color: white;
        padding: 1rem;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .carrito-titulo h3 {
        margin: 0;
        font-size: 16px !important;
        font-weight: bold;
    }

    .btn-cerrar-carrito {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px !important;
        transition: all 0.3s ease;
    }

    .btn-cerrar-carrito:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* ===== TABLA DEL CARRITO ===== */
    .carrito-contenido {
        max-height: 350px;
        overflow-y: auto;
        padding: 1rem;
    }

    .lista-carrito {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
    }

    .lista-carrito thead {
        background: #4a5568;
        position: sticky;
        top: 0;
    }

    .lista-carrito th {
        padding: 12px 8px;
        text-align: left;
        font-size: 13px !important;
        font-weight: 600;
        color: #e2e8f0;
        border-bottom: 2px solid #ff6b35;
    }

    .lista-carrito td {
        padding: 10px 8px;
        border-bottom: 1px solid #4a5568;
        font-size: 12px !important;
    }

    .lista-carrito tbody tr:hover {
        background: rgba(255, 107, 53, 0.1);
    }

    .item-imagen {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
    }

    .item-nombre {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 500;
    }

    .item-precio {
        color: #ff6b35;
        font-weight: bold;
    }

    .cantidad-controles-tabla {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-cantidad-tabla {
        background: #ff6b35;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px !important;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-cantidad-tabla:hover {
        background: #f7931e;
    }

    .cantidad-display-tabla {
        font-weight: bold;
        min-width: 20px;
        text-align: center;
        font-size: 11px !important;
    }

    .btn-eliminar-tabla {
        background: #dc3545;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px !important;
        transition: all 0.3s ease;
    }

    .btn-eliminar-tabla:hover {
        background: #c82333;
    }

    /* ===== CARRITO VACÍO ===== */
    .carrito-vacio {
        text-align: center;
        padding: 2rem;
        color: #a0aec0;
    }

    .carrito-vacio-icono {
        font-size: 2.5rem !important;
        color: #4a5568;
        margin-bottom: 1rem;
    }

    .carrito-vacio p {
        margin: 0.5rem 0;
        font-size: 14px !important;
        color: #e2e8f0;
    }

    /* ===== FOOTER DEL CARRITO ===== */
    .carrito-footer {
        border-top: 1px solid #4a5568;
        padding: 1rem;
        background: #2d3748;
        border-radius: 0 0 12px 12px;
    }

    .carrito-total {
        text-align: center;
        margin-bottom: 1rem;
        font-size: 16px !important;
        font-weight: bold;
        color: #e2e8f0;
    }

    .carrito-acciones {
        display: flex;
        gap: 8px;
    }

    .btn-vaciar-carrito,
    .btn-ir-comprar {
        flex: 1;
        padding: 10px;
        border-radius: 6px;
        font-weight: bold;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-size: 12px !important;
    }

    .btn-vaciar-carrito {
        background: #dc3545;
        color: white;
    }

    .btn-vaciar-carrito:hover {
        background: #c82333;
    }

    .btn-ir-comprar {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-ir-comprar:hover {
        background: linear-gradient(135deg, #218838, #1ea080);
    }

    /* ===== NOTIFICACIONES ===== */
    .notificacion {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        z-index: 1001;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s ease;
        font-size: 13px !important;
    }

    .notificacion.show {
        transform: translateX(0);
        opacity: 1;
    }

    /* ===== ESTILOS DEL FOOTER ===== */
    .footer {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        color: #e2e8f0;
        padding: 3rem 0 0;
        margin-top: 4rem;
        border-top: 3px solid #ed850f;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
    }

    .footer-column h4 {
        color: #ed850f;
        font-size: 18px !important;
        font-weight: bold;
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.5rem;
    }

    .footer-column h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 2px;
        background: #ed850f;
    }

    /* Logo del footer */
    .footer-logo {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .footer-logo img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
    }

    .footer-logo h3 {
        color: #ed850f;
        font-size: 24px !important;
        font-weight: bold;
        margin: 0;
    }

    .footer-description {
        color: #a0aec0;
        line-height: 1.6;
        margin-bottom: 1.5rem;
        font-size: 14px !important;
    }

    /* Redes sociales */
    .footer-social {
        display: flex;
        gap: 1rem;
    }

    .footer-social a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(237, 133, 15, 0.1);
        border: 2px solid rgba(237, 133, 15, 0.3);
        border-radius: 50%;
        color: #ed850f;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 16px !important;
    }

    .footer-social a:hover {
        background: #ed850f;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(237, 133, 15, 0.4);
    }

    /* Enlaces del footer */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 0.8rem;
    }

    .footer-links a {
        color: #a0aec0;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        transition: all 0.3s ease;
        font-size: 14px !important;
        padding: 0.3rem 0;
    }

    .footer-links a i {
        color: #ed850f;
        width: 16px;
        font-size: 14px !important;
    }

    .footer-links a:hover {
        color: #ed850f;
        transform: translateX(5px);
    }

    /* Información de contacto */
    .footer-contact {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .contact-item i {
        color: #ed850f;
        font-size: 16px !important;
        margin-top: 0.2rem;
        min-width: 16px;
    }

    .contact-item div {
        flex: 1;
    }

    .contact-item strong {
        color: #e2e8f0;
        display: block;
        margin-bottom: 0.2rem;
        font-size: 13px !important;
    }

    .contact-item span,
    .contact-item a {
        color: #a0aec0;
        font-size: 13px !important;
        text-decoration: none;
    }

    .contact-item a:hover {
        color: #ed850f;
    }

    /* Copyright */
    .footer-bottom {
        background: #1a1a1a;
        border-top: 1px solid #333;
        padding: 1.5rem 0;
        margin-top: 2rem;
    }

    .footer-copyright {
        text-align: center;
        color: #a0aec0;
    }

    .footer-copyright p {
        margin: 0.5rem 0;
        font-size: 13px !important;
    }

    .footer-dev {
        color: #666;
        font-style: italic;
    }

    /* ===== MODALES DEL FOOTER ===== */
    .modal-footer {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }

    .modal-footer-content {
        background: #2d3748;
        border: 1px solid #4a5568;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease;
    }

    .modal-footer-header {
        background: #ed850f;
        color: white;
        padding: 1.5rem;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-footer-header h3 {
        margin: 0;
        font-size: 18px !important;
        font-weight: bold;
    }

    .close-modal-footer {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px !important;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }

    .close-modal-footer:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    .modal-footer-body {
        padding: 2rem;
        color: #e2e8f0;
        line-height: 1.6;
    }

    .modal-footer-body h4 {
        color: #ed850f;
        font-size: 16px !important;
        font-weight: bold;
        margin: 1.5rem 0 1rem 0;
        border-bottom: 1px solid #4a5568;
        padding-bottom: 0.5rem;
    }

    .modal-footer-body p {
        margin-bottom: 1rem;
        font-size: 14px !important;
        color: #a0aec0;
    }

    .modal-footer-body ul {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }

    .modal-footer-body li {
        margin-bottom: 0.5rem;
        font-size: 14px !important;
        color: #a0aec0;
    }

    .modal-footer-body strong {
        color: #e2e8f0;
    }

    /* FAQ específico */
    .faq-item {
        background: rgba(74, 85, 104, 0.3);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-left: 4px solid #ed850f;
    }

    .faq-item h4 {
        color: #ed850f;
        margin: 0 0 0.8rem 0;
        font-size: 15px !important;
        border: none;
        padding: 0;
    }

    .faq-item p {
        margin: 0;
        color: #e2e8f0;
    }

    /* Formulario de contacto */
    .contact-form {
        background: rgba(74, 85, 104, 0.2);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        color: #ed850f;
        font-weight: bold;
        margin-bottom: 0.5rem;
        font-size: 14px !important;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #4a5568;
        border-radius: 6px;
        background: #2d3748;
        color: #e2e8f0;
        font-size: 14px !important;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #ed850f;
        box-shadow: 0 0 0 2px rgba(237, 133, 15, 0.2);
    }

    .btn-enviar {
        background: #ed850f;
        color: white;
        padding: 0.8rem 2rem;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 auto;
    }

    .btn-enviar:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(237, 133, 15, 0.4);
    }

    .contact-info-modal {
        background: rgba(237, 133, 15, 0.1);
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid rgba(237, 133, 15, 0.3);
    }

    .contact-info-modal h4 {
        color: #ed850f;
        margin: 0 0 1rem 0;
        font-size: 16px !important;
        border: none;
        padding: 0;
    }

    .contact-info-modal p {
        margin: 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-size: 13px !important;
    }

    .contact-info-modal i {
        color: #ed850f;
        width: 16px;
        font-size: 14px !important;
    }


    @media (max-width: 768px) {

        .mobile-menu {
            display: block;
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Ocultar nav por defecto */
        .urls {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-top: 3px solid #ed850f;
            border-bottom: 3px solid #ed850f;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            margin-top: 0;
            height: auto;
            z-index: 999;
        }

        /* Mostrar nav cuando tiene clase .mostrar */
        .urls.mostrar {
            display: block;
        }

        /* Lista vertical */
        .urls ul {
            flex-direction: column;
            height: auto;
            padding: 0;
        }

        .urls ul li {
            width: 100%;
            margin: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .urls ul li a {
            padding: 15px 20px;
            width: 100%;
            justify-content: flex-start;
        }

        .urls ul li:hover {
            background: rgba(237, 133, 15, 0.1);
        }

        /* Buscador en móvil */
        .buscador {
            width: 100% !important;
            margin: 10px 0 !important;
            padding: 10px 20px;
        }

        .barra_buscador {
            width: 100% !important;
        }

        /* Carrito en móvil */
        .carrito-nav {
            width: 100%;
        }

        .carrito-dropdown {
            width: 90%;
            left: 5%;
            right: auto;
        }

        /* Ocultar menú lateral original en móvil */
        .container .btn-menu {
            display: none;
        }

        /* Ajustar logo */
        .logo img {
            width: 80px;
            height: 80px;
        }

        /* FOOTER RESPONSIVE */
        .footer {
            padding: 2rem 0 0;
            margin-top: 2rem;
        }

        .footer-container {
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 0 1rem;
        }

        .footer-logo {
            justify-content: center;
            text-align: center;
        }

        .footer-description {
            text-align: center;
        }

        .footer-social {
            justify-content: center;
        }

        .footer-column {
            text-align: center;
        }

        .footer-links a {
            justify-content: center;
        }

        .contact-item {
            justify-content: center;
            text-align: left;
        }

        .modal-footer-content {
            width: 95%;
            margin: 2rem;
        }

        .modal-footer-body {
            padding: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            font-size: 16px !important;
        }
    }

    @media (max-width: 480px) {
        .footer-container {
            padding: 0 0.5rem;
        }

        .modal-footer-header {
            padding: 1rem;
        }

        .modal-footer-header h3 {
            font-size: 16px !important;
        }

        .contact-form {
            padding: 1rem;
        }

        .faq-item {
            padding: 1rem;
        }
    }
</style>

</html>