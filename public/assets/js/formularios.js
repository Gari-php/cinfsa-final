
function crearContenedorModal() {
    let contenedor = document.getElementById('modal-alertas-container');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'modal-alertas-container';
        contenedor.className = 'modal-alertas-overlay';
        document.body.appendChild(contenedor);
    }
    return contenedor;
}


export function mostrarAlerta(mensaje, tipo = 'error', contenedor = '.form-container', limpiar = true) {
    // Crear contenedor modal
    const modalContainer = crearContenedorModal();

    // Limpiar alertas previas si se solicita
    if (limpiar) {
        modalContainer.innerHTML = '';
    }

    // Crear la alerta modal
    const alertaModal = document.createElement('div');
    alertaModal.className = `alerta-modal ${tipo === 'exito' ? 'exito' : tipo === 'neutral' ? 'neutral' : 'error'}`;

    // Icono según el tipo
    let icono = '';
    switch (tipo) {
        case 'exito':
            icono = '<i class="fa-solid fa-check-circle"></i>';
            break;
        case 'neutral':
            icono = '<i class="fa-solid fa-info-circle"></i>';
            break;
        default:
            icono = '<i class="fa-solid fa-exclamation-triangle"></i>';
    }

    alertaModal.innerHTML = `
        <div class="alerta-modal-content">
            <div class="alerta-modal-icon">
                ${icono}
            </div>
            <div class="alerta-modal-message">
                ${mensaje}
            </div>
            <button class="alerta-modal-close" onclick="cerrarAlertaModal(this)">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    `;

    modalContainer.appendChild(alertaModal);
    modalContainer.style.display = 'flex';

    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        cerrarAlertaModal(alertaModal);
    }, 5000);

    // Cerrar al hacer clic en el fondoa
    modalContainer.addEventListener('click', (e) => {
        if (e.target === modalContainer) {
            cerrarAlertaModal();
        }
    });
}

window.cerrarAlertaModal = function (elemento = null) {
    const modalContainer = document.getElementById('modal-alertas-container');
    if (!modalContainer) return;

    if (elemento && elemento.closest) {
        // Cerrar alerta específica
        const alertaModal = elemento.closest('.alerta-modal');
        if (alertaModal) {
            alertaModal.style.animation = 'desaparecer 0.3s ease-out forwards';
            setTimeout(() => {
                alertaModal.remove();
                // Si no quedan más alertas, ocultar el contenedor
                if (modalContainer.children.length === 0) {
                    modalContainer.style.display = 'none';
                }
            }, 300);
        }
    } else {
        // Cerrar todas las alertas
        modalContainer.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            modalContainer.innerHTML = '';
            modalContainer.style.display = 'none';
            modalContainer.style.animation = '';
        }, 300);
    }
};

function mostrarErrorCampo(input, mensaje) {
    input.classList.add('input-error');
    const campo = input.closest('.campo');

    if (campo && !campo.querySelector('.mensaje-error-campo')) {
        const div = document.createElement('div');
        div.classList.add('mensaje-error-campo');
        div.innerHTML = `
            <i class="fa-solid fa-exclamation-circle"></i>
            ${mensaje}
        `;
        campo.appendChild(div);
    }
}


// Marca en rojo cada campo con su error. Devuelve los errores que no corresponden a ningún campo.
function mostrarErroresEspecificos(formulario, errores) {
    limpiarErroresCampos(formulario);
    const sinCampo = [];

    errores.forEach((error) => {
        const mapeoErrores = {
            // ==========================================
            // USUARIOS - FORMULARIO DE REGISTRO (PRIORIDAD ALTA)
            // ==========================================
            'nombre': [
                'El nombre es obligatorio',
                'El nombre debe tener al menos 2 caracteres',
                'El nombre no puede tener más de 50 caracteres',
                'El nombre solo puede contener letras y espacios'
            ],

            'apellido': [
                'El apellido es obligatorio',
                'El apellido debe tener al menos 3 caracteres',
                'El apellido no puede tener más de 50 caracteres',
                'El apellido solo puede contener letras y espacios'
            ],

            'sexo': [
                'Debe seleccionar un sexo',
                'El valor de sexo no es válido',
                'El sexo seleccionado no es válido'
            ],

            'password': [
                'La contraseña debe tener al menos 6 caracteres',
                'La contraseña no puede tener más de 100 caracteres',
                'La contraseña debe contener al menos una letra',
                'La contraseña debe contener al menos un número',
                'La contraseña es muy común, elige una más segura',
                'Las contraseñas no coinciden'
            ],

            'confirm_password': [
                'Las contraseñas no coinciden'
            ],

            // ==========================================
            // USUARIOS - PANEL ADMIN (BACKEND)
            // ==========================================
            'nombre_persona': [
                'El nombre es obligatorio',
                'El nombre debe tener al menos 2 caracteres',
                'El nombre no puede tener más de 50 caracteres',
                'El nombre solo puede contener letras y espacios'
            ],

            'apellido_persona': [
                'El apellido es obligatorio',
                'El apellido debe tener al menos 2 caracteres',
                'El apellido no puede tener más de 50 caracteres',
                'El apellido solo puede contener letras y espacios'
            ],

            'rela_sexo': [
                'Debe seleccionar un sexo',
                'El valor de sexo no es válido',
                'El sexo seleccionado no es válido'
            ],

            'nombre_usuario': [
                'El nombre de usuario es obligatorio',
                'El nombre de usuario debe tener al menos 3 caracteres',
                'El nombre de usuario no puede tener más de 20 caracteres',
                'El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos',
                'El nombre de usuario no puede empezar con un número',
                'El nombre de usuario no está permitido',
                'El nombre de usuario ya está en uso',
                'El nombre de usuario ya está en uso por otro usuario',
                'Usuario o Gmail no encontrado',
                'Todos los campos son obligatorios'
            ],

            'email': [
                'El email es obligatorio',
                'El formato del email no es válido',
                'El email no puede tener más de 100 caracteres',
                'Solo se permiten correos de: gmail.com, hotmail.com, outlook.com, yahoo.com',
                'El email ya está registrado',
                'El email ya está en uso por otro usuario',
                'Por favor, confirmá tu cuenta desde el correo para iniciar sesión',
                'Este correo no está registrado',
                'Este correo aún no fue verificado',
                'Debes ingresar un Gmail válido',
                'El campo email es obligatorio',
                'El correo electrónico o nombre de usuario ya están registrados'
            ],

            'clave_usuario': [
                'La contraseña debe tener al menos 6 caracteres',
                'La contraseña no puede tener más de 100 caracteres',
                'La contraseña debe contener al menos una letra',
                'La contraseña debe contener al menos un número',
                'La contraseña es muy común, elige una más segura',
                'La contraseña es incorrecta',
                'Las contraseñas no coinciden',
                'Todos los campos son obligatorios'
            ],

            'fecha_nacimiento': [
                'La fecha de nacimiento es obligatoria',
                'El formato de fecha de nacimiento no es válido (YYYY-MM-DD)',
                'Debe ser mayor de 13 años para registrarse',
                'La fecha de nacimiento no es válida',
                'La fecha de nacimiento no puede ser futura'
            ],

            'rela_perfil': [
                'Debe seleccionar un perfil',
                'El perfil seleccionado no es válido',
                'El perfil seleccionado no existe'
            ],

            // ==========================================
            // SALAS DE CINE
            // ==========================================
            'capacidad_sala': [
                'La capacidad es obligatoria',
                'La capacidad debe ser un número válido',
                'La capacidad debe ser mayor a 0',
                'La capacidad mínima para una sala de cine es de 50 personas',
                'La capacidad máxima permitida es de 500 personas'
            ],

            'filas_sala': [
                'Las filas son obligatorias',
                'Las filas deben ser un número válido',
                'Las filas deben ser mayor a 0',
                'Una sala de cine debe tener mínimo 5 filas',
                'El máximo de filas permitido es 30'
            ],

            'columnas_sala': [
                'Las columnas son obligatorias',
                'Las columnas deben ser un número válido',
                'Las columnas deben ser mayor a 0',
                'Una sala de cine debe tener mínimo 6 columnas',
                'El máximo de columnas permitido es 25',
                'Se recomienda usar un número par de columnas para mejor distribución'
            ],

            // ==========================================
            // PELÍCULAS
            // ==========================================
            'titulo_pelicula': [
                'El título es obligatorio',
                'El título debe tener al menos 2 caracteres',
                'El título no puede exceder 50 caracteres',
                'Ya existe una película con ese título'
            ],

            'sinopsis_pelicula': [
                'La sinopsis es obligatoria',
                'la sinopsis es obligatoria',
                'sinopsis es obligatoria'
            ],

            'anyo_pelicula': [
                'El año es obligatorio',
                'el año es obligatorio',
                'año es obligatorio'
            ],

            'duracion_pelicula': [
                'La duración es obligatoria',
                'La duración debe ser un número válido',
                'La duración debe ser mayor a 0',
                'La duración mínima para una película es de 30 minutos',
                'La duración máxima permitida es de 300 minutos (5 horas)'
            ],

            'rela_tipo_clasificacion': [
                'Seleccione una clasificación',
                'seleccione una clasificación',
                'clasificación es obligatoria'
            ],

            'rela_idioma_pelicula': [
                'Seleccione un idioma',
                'seleccione un idioma',
                'idioma es obligatorio'
            ],

            'rela_estado_pelicula': [
                'Seleccione un estado',
                'seleccione un estado',
                'estado es obligatorio'
            ],

            'generos': [
                'Seleccione al menos un género',
                'No puede seleccionar más de 5 géneros',
                'Los géneros seleccionados no son válidos'
            ],

            'imagen_pelicula': [
                'Error al subir la imagen',
                'error al subir la imagen',
                'imagen es obligatoria'
            ],

            'trailer_url': [
                'La URL del tráiler no tiene un formato válido',
                'La URL del tráiler debe ser una URL válida de YouTube'
            ],

            // ==========================================
            // FUNCIONES
            // ==========================================
            'fecha_hora': [
                'La fecha de inicio es obligatoria',
                'La fecha de inicio debe tener un formato válido (YYYY-MM-DD)',
                'La fecha de inicio debe ser posterior a la fecha actual',
                'No se pueden programar funciones con más de 1 mes de anticipación'
            ],

            'fecha_finalizacion': [
                'La fecha de finalización es obligatoria',
                'La fecha de finalización debe tener un formato válido (YYYY-MM-DD)',
                'La fecha de finalización no puede ser anterior a la fecha de inicio',
                'La función no puede durar más de 1 mes'
            ],

            'rela_salas': [
                'La sala es obligatoria',
                'La sala seleccionada no es válida',
                'Ya hay una función programada en esa sala y turno en las mismas fechas'
            ],

            'rela_peliculas': [
                'La película es obligatoria',
                'La película seleccionada no es válida'
            ],

            'rela_turnos': [
                'El turno es obligatorio',
                'El turno seleccionado no es válido'
            ],

            'rela_tipo_entrada': [
                'El tipo de entrada es obligatorio',
                'El tipo de entrada seleccionado no es válido'
            ],

            'rela_idioma': [
                'Debe seleccionar un idioma para la función'
            ],

            // ==========================================
            // TURNOS Y ENTRADAS
            // ==========================================
            'turno_horario': [
                'El horario es obligatorio',
                'El horario debe tener un formato válido (HH:MM)',
                'El horario no puede ser anterior a las 08:00',
                'El horario no puede ser posterior a las 23:59',
                'Ya existe un turno con ese horario',
                'El horario debe ser en intervalos de 15 minutos (00, 15, 30, 45)',
                'Se recomienda no programar funciones antes de las 10:00',
                'Se recomienda no programar funciones después de las 22:00'
            ],

            'tipo_entrada_desc': [
                'La descripción es obligatoria',
                'La descripción no puede exceder 10 caracteres',
                'La descripción debe tener al menos 2 caracteres',
                'La descripción solo puede contener letras, números y espacios',
                'Ya existe un tipo de entrada con esa descripción'
            ],

            'precio_entrada': [
                'El precio es obligatorio',
                'El precio no puede contener la letra "e"',
                'El precio debe ser un número válido',
                'El precio debe ser mayor a 0',
                'El precio no puede exceder $10,000',
                'El precio no puede tener más de 2 decimales'
            ],

            'numero_ticket_entrada': [
                'El número de ticket es obligatorio',
                'El ticket ya existe'
            ],

            'rela_funcion': [
                'La función es obligatoria',
                'La función seleccionada no es válida'
            ],

            // ==========================================
            // CANTINA - PRODUCTOS
            // ==========================================
            'nombre_producto_cantina': [
                'El nombre es obligatorio',
                'El nombre debe tener al menos 2 caracteres',
                'El nombre no puede exceder 80 caracteres',
                'El nombre solo puede contener letras, números, espacios, guiones y puntos',
                'Ya existe un producto con ese nombre'
            ],

            'precio_producto': [
                'El precio es obligatorio',
                'El precio no puede contener la letra "e"',
                'El precio debe ser un número válido',
                'El precio debe ser mayor a 0',
                'El precio no puede exceder $10,000',
                'El precio no puede tener más de 2 decimales'
            ],

            'rela_estado_producto': [
                'el estado es obligatorio',
                'estado es obligatorio',
                'El estado debe ser válido (0 o 1)'
            ],

            // ==========================================
            // CANTINA - STOCK
            // ==========================================
            'stock_cantina': [
                'El stock es obligatorio',
                'El stock debe ser un número válido',
                'El stock debe ser un número entero (sin decimales)',
                'El stock no puede ser negativo',
                'El stock no puede ser mayor a 20,000 unidades'
            ],

            'rela_producto_cantina': [
                'Debe seleccionar un producto válido',
                'Ya existe stock registrado para este producto en esta cantina'
            ],

            'rela_cantina': [
                'Debe seleccionar una cantina válida'
            ],

            'nombre_cantina': [
                'El nombre de la cantina es obligatorio',
                'El nombre de la cantina debe tener al menos 3 caracteres'
            ],

            // ==========================================
            // MÁQUINAS Y FICHAS
            // ==========================================
            'maquinas_nombre': [
                'El nombre de la máquina es obligatorio',
                'El nombre debe tener al menos 3 caracteres',
                'El nombre no puede exceder 50 caracteres',
                'El nombre solo puede contener letras, números, espacios, guiones y guiones bajos',
                'Ya existe una máquina con ese nombre'
            ],

            'maquina_descripcion': [
                'La descripción es obligatoria',
                'La descripción debe tener al menos 10 caracteres',
                'La descripción no puede exceder 200 caracteres'
            ],

            'rela_fichas': [
                'Debe seleccionar un tipo de ficha válido'
            ],

            'precio_ficha': [
                'El precio es obligatorio',
                'El precio no puede contener la letra "e"',
                'El precio debe ser un número válido',
                'El precio debe ser mayor a 0',
                'El precio no puede exceder $10,000',
                'El precio no puede tener más de 2 decimales'
            ],

            'cantidad_ficha': [
                'La cantidad es obligatoria',
                'La cantidad debe ser un número válido',
                'La cantidad debe ser un número entero',
                'La cantidad debe ser mayor a 0',
                'La cantidad no puede exceder 50,000 fichas'
            ],

            // ==========================================
            // OTROS CATÁLOGOS
            // ==========================================
            'nombre_sexo': [
                'El nombre del sexo es obligatorio',
                'El nombre del sexo debe tener al menos 2 caracteres',
                'El nombre del sexo no puede exceder 20 caracteres',
                'El nombre del sexo solo puede contener letras y espacios',
                'El nombre del sexo no puede tener espacios consecutivos',
                'El nombre del sexo no puede empezar o terminar con espacios',
                'El nombre del sexo contiene palabras no permitidas'
            ],

            'genero_pelicula': [
                'El género de película es obligatorio',
                'El género debe tener al menos 2 caracteres'
            ],

            'nombre_estado_pelicula': [
                'El nombre del estado es obligatorio',
                'El nombre del estado no puede exceder 35 caracteres'
            ],

            'estado': [
                'El estado debe ser válido (0 o 1)'
            ]
        };

        // Un mismo mensaje puede figurar en varios campos (ej. "password" en crear cuenta y
        // "clave_usuario" en editar usuario): solo se consideran los campos que existen en
        // ESTE formulario. Gana la coincidencia exacta; si no hay, la parcial más larga.
        const errorLower = error.toLowerCase();
        let mejor = null; // { input, exacta, longitud }

        for (const [nombreCampo, palabrasClave] of Object.entries(mapeoErrores)) {
            for (const palabra of palabrasClave) {
                const palabraLower = palabra.toLowerCase();
                const exacta = errorLower === palabraLower;
                if (!exacta && !errorLower.includes(palabraLower)) continue;

                const esMejor = !mejor
                    || (exacta && !mejor.exacta)
                    || (exacta === mejor.exacta && palabraLower.length > mejor.longitud);
                if (!esMejor) continue;

                const input = buscarInputDeCampo(formulario, nombreCampo);
                if (input) mejor = { input, exacta, longitud: palabraLower.length };
            }
        }

        if (mejor) {
            mostrarErrorCampo(mejor.input, error);
        } else {
            sinCampo.push(error);
        }
    });

    // Los errores que no corresponden a ningún campo los muestra quien llama (en un solo modal)
    return sinCampo;
}

// Busca en el formulario el input correspondiente a un nombre de campo del mapeo de errores
function buscarInputDeCampo(formulario, campo) {
    const estrategias = [
        () => formulario.querySelector(`[name="${campo}"]`),
        () => formulario.querySelector(`#${campo}`),
        () => formulario.querySelector(`[name*="${campo}"]`),
        () => formulario.querySelector(`[id*="${campo}"]`),
        () => campo === 'generos' ? formulario.querySelector('[name="generos[]"]') : null,
        () => campo === 'generos' ? formulario.querySelector('#rela_genero_pelicula') : null
    ];

    for (const estrategia of estrategias) {
        try {
            const input = estrategia();
            if (input) return input;
        } catch (e) {
            // selector inválido para este nombre de campo: probar la siguiente estrategia
        }
    }
    return null;
}
function limpiarErroresCampos(formulario) {
    formulario.querySelectorAll('.mensaje-error-campo').forEach(el => el.remove());
    formulario.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}

// ======================
// CONFIRMACIÓN MODAL MEJORADA
// ======================
export async function mostrarConfirmacionModal(mensaje, tipo = 'warning') {
    return new Promise((resolve) => {
        const modalContainer = crearContenedorModal();

        const confirmacionModal = document.createElement('div');
        confirmacionModal.className = `alerta-modal confirmacion ${tipo}`;

        let icono = '<i class="fa-solid fa-question-circle"></i>';
        if (tipo === 'warning') icono = '<i class="fa-solid fa-exclamation-triangle"></i>';
        if (tipo === 'danger') icono = '<i class="fa-solid fa-trash-alt"></i>';
        if (tipo === 'neutral') icono = '<i class="fa-solid fa-info-circle"></i>'; // AGREGAR ESTA LÍNEA

        confirmacionModal.innerHTML = `
            <div class="alerta-modal-content confirmacion-content">
                <div class="alerta-modal-icon">
                    ${icono}
                </div>
                <div class="alerta-modal-message">
                    ${mensaje}
                </div>
                <div class="confirmacion-botones">
                    <button class="boton-modal cancelar">
                        <i class="fa-solid fa-times"></i> NO
                    </button>
                    <button class="boton-modal confirmar">
                        <i class="fa-solid fa-check"></i> SÍ
                    </button>
                </div>
            </div>
        `;

        modalContainer.appendChild(confirmacionModal);
        modalContainer.style.display = 'flex';

        const btnCancelar = confirmacionModal.querySelector('.cancelar');
        const btnConfirmar = confirmacionModal.querySelector('.confirmar');

        btnCancelar.addEventListener('click', () => {
            cerrarAlertaModal(confirmacionModal);
            resolve(false);
        });

        btnConfirmar.addEventListener('click', () => {
            cerrarAlertaModal(confirmacionModal);
            resolve(true);
        });

        const handleKeydown = (e) => {
            if (e.key === 'Escape') {
                cerrarAlertaModal(confirmacionModal);
                document.removeEventListener('keydown', handleKeydown);
                resolve(false);
            }
        };
        document.addEventListener('keydown', handleKeydown);
    });
}

// ======================
// Validar formulario (mantener validaciones básicas del frontend)
// ======================
function validarFormulario(formulario) {
    limpiarErroresCampos(formulario);
    let hayErrores = false;

    const datos = new FormData(formulario);

    for (let [key, value] of datos.entries()) {
        const input = formulario.querySelector(`[name="${key}"]`);
        if (!input) continue;


        if (input.type === 'file') {
            const esActualizacion = formulario.action.includes('actualizar');
            const esRequerido = input.hasAttribute('required');

            // Solo validar si el archivo ES REQUERIDO y NO es actualización
            if (!esActualizacion && esRequerido && input.files.length === 0) {
                mostrarErrorCampo(input, 'Debes subir un archivo');
                hayErrores = true;
            }
            continue;
        }

        const esSelect = input.tagName === 'SELECT';
        const esTexto = typeof value === 'string';
        const valorVacio = (esTexto && value.trim() === '') || (esSelect && input.value === '');

        if (valorVacio && input.hasAttribute('required')) {
            mostrarErrorCampo(input, 'Este campo es obligatorio');
            hayErrores = true;
        }
    }

    return !hayErrores;
}

// ======================
// Enviar datos con fetch (sin cambios)
// ======================
export async function enviarFetch(url, datos, metodo = 'POST', esMultipart = false) {
    try {
        let opcionesFetch;
        if (esMultipart) {
            opcionesFetch = {
                method: metodo,
                body: datos
            };
        } else {
            const json = {};
            const generosSeleccionados = datos.getAll('generos[]');
            if (generosSeleccionados.length > 0) json['generos'] = generosSeleccionados;
            datos.forEach((valor, clave) => {
                if (clave !== 'generos[]') json[clave] = valor;
            });
            opcionesFetch = {
                method: metodo,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(json)
            };
        }

        const respuesta = await fetch(url, opcionesFetch);
        const textoPlano = await respuesta.text();
        return JSON.parse(textoPlano);
    } catch (error) {
        console.error('💥 Error en fetch:', error);
        return { error: 'Error de conexión con el servidor' };
    }
}

// ======================
// Confirmación actualización MODAL
// ======================
async function enviarFormularioConConfirmacion(formulario, action, metodo, contenedor, redirigirHTML) {
    const confirmacion = await mostrarConfirmacionModal(
        '¿Estás seguro que deseas actualizar este registro?',
        'warning'
    );

    if (!confirmacion) return false;

    const datos = new FormData(formulario);
    const tieneArchivo = formulario.querySelector('input[type="file"]') !== null;
    const respuesta = await enviarFetch(action, datos, metodo, tieneArchivo);
    return respuesta;
}

// ======================
// VALIDACIÓN + ENVÍO MEJORADO con alertas modales
// ======================
document.addEventListener('DOMContentLoaded', () => {
    const formularios = document.querySelectorAll('form[data-fetch="true"]');
    formularios.forEach(formulario => {
        formulario.addEventListener('submit', async e => {
            e.preventDefault();
            const action = formulario.getAttribute('action') || window.location.href;
            const metodo = formulario.getAttribute('method') || 'POST';
            const contenedor = formulario.getAttribute('data-alerta') || '.form-container';
            const redirigirHTML = formulario.getAttribute('data-redirigir') || '';

            limpiarErroresCampos(formulario);

            const esValido = validarFormulario(formulario);
            if (!esValido) return;

            let respuesta;
            const sinConfirmacion = formulario.dataset.sinConfirmacion === 'true';

            if (action.includes('actualizar') && !sinConfirmacion) {
                respuesta = await enviarFormularioConConfirmacion(formulario, action, metodo, contenedor, redirigirHTML);
                if (respuesta === false) return;
            } else {
                const datos = new FormData(formulario);
                const tieneArchivo = formulario.querySelector('input[type="file"]') !== null;
                respuesta = await enviarFetch(action, datos, metodo, tieneArchivo);
            }


            // MANEJO MEJORADO DE RESPUESTAS CON ALERTAS MODALES
            if (respuesta.ok === true || respuesta.ok === "true") {
                mostrarAlerta(respuesta.mensaje || 'Operación exitosa', 'exito', contenedor);
                const destino = respuesta.redirigir || redirigirHTML;
                if (destino) setTimeout(() => window.location.href = destino, 2000);

            } else if (respuesta.errores) {
                let erroresArray = [];

                if (Array.isArray(respuesta.errores)) {
                    erroresArray = respuesta.errores;
                } else if (respuesta.errores.error && Array.isArray(respuesta.errores.error)) {
                    erroresArray = respuesta.errores.error;
                } else if (typeof respuesta.errores === 'object') {
                    erroresArray = Object.values(respuesta.errores).flat();
                }


                if (erroresArray.length > 0) {
                    mostrarErroresEspecificos(formulario, erroresArray);
                    // Un único modal con todos los errores (además de marcarlos en sus campos)
                    mostrarAlerta(erroresArray.join('<br>'), 'error', contenedor, false);
                }

            } else if (respuesta.alertas && typeof respuesta.alertas === 'object') {
                let erroresArray = [];

                if (respuesta.alertas.error && Array.isArray(respuesta.alertas.error)) {
                    erroresArray = respuesta.alertas.error;
                    const sinCampo = mostrarErroresEspecificos(formulario, erroresArray);
                    // Los que no tienen campo se muestran juntos en un único modal
                    if (sinCampo.length > 0) {
                        mostrarAlerta(sinCampo.join('<br>'), 'error', contenedor, false);
                    }
                } else {
                    Object.entries(respuesta.alertas).forEach(([tipo, mensajes]) => {
                        if (Array.isArray(mensajes)) mensajes.forEach(mensaje => {
                            mostrarAlerta(mensaje, tipo, contenedor, false);
                        });
                    });
                }

            } else if (respuesta.error) {
                mostrarAlerta(respuesta.error, 'error', contenedor);

            } else if (respuesta.mensaje) {
                mostrarAlerta(respuesta.mensaje, 'error', contenedor);
            }
        });
    });
});

// ======================
// FUNCIÓN DE CONFIRMACIÓN PARA ELIMINACIONES
// ======================
window.confirmarAccionModal = async function ({
    id,
    nombre,
    mensajeConfirmacion,
    url,
    dataKey = 'id',
    mensajeExito = 'Acción realizada con éxito',
    mensajeError = 'Ocurrió un error'
}) {
    const mensaje = mensajeConfirmacion.replace('{nombre}', `<strong>${nombre}</strong>`);

    const confirmacion = await mostrarConfirmacionModal(mensaje, 'danger');

    if (!confirmacion) return;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ [dataKey]: id })
        });

        const data = await response.json();

        mostrarAlerta(data.mensaje || mensajeExito, 'exito');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        mostrarAlerta(mensajeError, 'error');
        console.error(err);
    }
};

// Eventos para botones de eliminación con confirmación modal
document.body.addEventListener('click', function (e) {
    if (e.target.classList.contains('eliminar-usuario')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja al usuario {nombre}?',
            url: '/administrador/usuarios/eliminar',
            dataKey: 'id',
            mensajeExito: 'Usuario eliminado correctamente',
            mensajeError: 'Error al eliminar el usuario'
        });
    }

    if (e.target.classList.contains('eliminar-pelicula')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas marcar como finalizada la película {nombre}?',
            url: '/administrador/peliculas/eliminar',
            dataKey: 'id',
            mensajeExito: 'Película actualizada correctamente',
            mensajeError: 'Error al actualizar película'
        });
    }

    if (e.target.classList.contains('eliminar-sala')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja la sala con capacidad {nombre}?',
            url: '/administrador/salas/eliminar',
            dataKey: 'id_sala',
            mensajeExito: 'Sala dada de baja correctamente',
            mensajeError: 'Error al dar de baja la sala'
        });
    }

    if (e.target.classList.contains('eliminar-funcion')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja la función con la fecha {nombre}?',
            url: '/administrador/funciones/eliminar',
            dataKey: 'id_funcion',
            mensajeExito: 'Función dada de baja correctamente',
            mensajeError: 'Error al dar de baja la función'
        });
    }

    if (e.target.classList.contains('eliminar-turno')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja el turno con la hora {nombre}?',
            url: '/administrador/turnos/eliminar',
            dataKey: 'id_turnos',
            mensajeExito: 'Turno dado de baja correctamente',
            mensajeError: 'Error al dar de baja el turno'
        });
    }

    if (e.target.classList.contains('eliminar-tipoentrada')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja el tipo de entrada {nombre}?',
            url: '/administrador/tipos_entradas/eliminar',
            dataKey: 'id_tipo_entrada',
            mensajeExito: 'Tipo de entrada dado de baja correctamente',
            mensajeError: 'Error al dar de baja el tipo de entrada'
        });
    }

    if (e.target.classList.contains('eliminar-producto')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas suspender el producto {nombre}?',
            url: '/administrador/productos/eliminar',
            dataKey: 'id_producto_cantina',
            mensajeExito: 'El producto fue Suspendido corectamente',
            mensajeError: 'Error al Suspender el producto'
        });
    }
    if (e.target.classList.contains('eliminar-stock')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas Suspender el Stock del producto {nombre}?',
            url: '/administrador/stock/eliminar',
            dataKey: 'id_stock_cantina',
            mensajeExito: 'El Stock  del Producto fue Suspendido corectamente',
            mensajeError: 'Error al Suspender el Stock del producto'
        });
    }
    if (e.target.classList.contains('eliminar-maquina')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas Dar de baja la  {nombre}?',
            url: '/administrador/maquinas/eliminar',
            dataKey: 'id_maquinas',
            mensajeExito: 'La maquina fue dada de baja Correctamente',
            mensajeError: 'Error al dar de baja la maquina'
        });
    }
    if (e.target.classList.contains('eliminar-genero')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas Dar de baja el Genero  {nombre}?',
            url: '/administrador/generos/eliminar',
            dataKey: 'id_genero_pelicula',
            mensajeExito: 'El Genero fue dado de baja Correctamente',
            mensajeError: 'Error al dar de baja El Genero'
        });
    }
    if (e.target.classList.contains('eliminar-estado-pelicula')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas Dar de baja el estado  {nombre}?',
            url: '/administrador/estados_peliculas/eliminar',
            dataKey: 'id_estado_pelicula',
            mensajeExito: 'El estado fue dado de baja Correctamente',
            mensajeError: 'Error al dar de baja El estado'
        });
    }
    if (e.target.classList.contains('eliminar-perfil')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja el perfil {nombre}?',
            url: '/administrador/perfiles/eliminar',
            dataKey: 'id',
            mensajeExito: 'Perfil dado de baja correctamente',
            mensajeError: 'Error al dar de baja el perfil'
        });
    }
    if (e.target.classList.contains('eliminar-proveedor')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja al proveedor {nombre}?',
            url: '/administrador/proveedores/eliminar',
            dataKey: 'id',
            mensajeExito: 'Proveedor dado de baja correctamente',
            mensajeError: 'Error al dar de baja el proveedor'
        });
    }
    if (e.target.classList.contains('eliminar-servicio')) {
        e.preventDefault();
        confirmarAccionModal({
            id: e.target.dataset.id,
            nombre: e.target.dataset.nombre,
            mensajeConfirmacion: '¿Deseas dar de baja el servicio {nombre}?',
            url: '/administrador/servicios/eliminar',
            dataKey: 'id',
            mensajeExito: 'Servicio dado de baja correctamente',
            mensajeError: 'Error al dar de baja el servicio'
        });
    }

});
window.mostrarAlerta = mostrarAlerta;
window.mostrarConfirmacionModal = mostrarConfirmacionModal;