// Alertas de los ABM del administrador: guardar bien, dar de baja y nombres con apóstrofe muestran
// UNA sola confirmación y UN solo modal de resultado (errores-un-modal.cy.js cubre los errores).
// Los nombres con apóstrofe ("Cantina D'Ana") rompían las consultas armadas a mano: ahora se guardan
// tal cual, y donde la validación no los acepta se ve el error de validación, no un error de base.

const MODULOS = [
  'usuarios', 'sexo', 'peliculas', 'salas', 'funciones', 'turnos', 'tipos_entradas', 'productos',
  'stock', 'fichas', 'maquinas', 'generos', 'cantina', 'estados_peliculas', 'gastos', 'perfiles',
  'proveedores', 'servicios',
];

// Botón de baja de cada listado (formularios.js → confirmarAccionModal)
const BAJAS = {
  usuarios: 'eliminar-usuario', sexo: 'eliminar-sexo', peliculas: 'eliminar-pelicula', salas: 'eliminar-sala',
  funciones: 'eliminar-funcion', turnos: 'eliminar-turno', tipos_entradas: 'eliminar-tipoentrada',
  productos: 'eliminar-producto', stock: 'eliminar-stock', fichas: 'eliminar-ficha', maquinas: 'eliminar-maquina',
  generos: 'eliminar-genero', cantina: 'eliminar-cantina', estados_peliculas: 'eliminar-estado-pelicula',
  perfiles: 'eliminar-perfil', proveedores: 'eliminar-proveedor', servicios: 'eliminar-servicio',
};

const consultar = (sql) => cy.task('consultarBase', sql);

// Modales abiertos de un tipo (exito / error), sin contar las confirmaciones
const modales = ($body, tipo) =>
  [...$body.find(`.alerta-modal.${tipo}`)].filter((m) => !m.classList.contains('confirmacion'));
const confirmaciones = ($body) => [...$body.find('.alerta-modal.confirmacion')];

const textos = (lista) => lista.map((m) => m.innerText.trim()).join(' | ');

// Exactamente un modal del tipo esperado y ninguno del otro, dando margen a que aparezca un duplicado
const unSoloModal = (tipo) => {
  cy.get(`.alerta-modal.${tipo}:not(.confirmacion)`, { timeout: 10000 }).should('exist');
  cy.wait(800);
  return cy.get('body').then(($b) => {
    const otro = tipo === 'exito' ? 'error' : 'exito';
    expect(modales($b, tipo), textos(modales($b, tipo))).to.have.length(1);
    expect(modales($b, otro), textos(modales($b, otro))).to.have.length(0);
    return modales($b, tipo)[0].innerText;
  });
};

// Confirma la única confirmación abierta (si hay más de una, falla)
const confirmarUnaVez = () => {
  cy.get('.alerta-modal.confirmacion', { timeout: 10000 }).should('be.visible');
  cy.wait(300);
  cy.get('body').then(($b) => expect(confirmaciones($b), 'confirmaciones abiertas').to.have.length(1));
  cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
};

// Editar pide confirmación salvo los formularios con data-sin-confirmacion (ej. funciones)
const confirmarSiCorresponde = () =>
  formulario().then(($f) => {
    if ($f.attr('data-sin-confirmacion') !== 'true') confirmarUnaVez();
  });

// Datos de prueba que no pasan la validación actual: se completan antes de guardar
const PREPARAR_EDICION = {
  usuarios: () => cy.get('#email').clear().type('usuario.prueba@gmail.com'),
  peliculas: () => cy.get('select[name="generos[]"] option').first().then(($o) =>
    cy.get('select[name="generos[]"]').select([$o.val()])),
  salas: () => {
    cy.get('[name="capacidad_sala"]').clear().type('160');
    cy.get('[name="filas_sala"]').clear().type('10');
    cy.get('[name="columnas_sala"]').clear().type('16');
  },
  proveedores: () => {
    cy.get('[name="nombre_comercial"]').clear().type('Proveedor Prueba');
    cy.get('[name="rut"]').clear().type('20-12345678-9');
  },
};
const prepararEdicion = (modulo) => (PREPARAR_EDICION[modulo] || (() => {}))();

const formulario = () => cy.get('form[data-fetch="true"]').first();
const enviar = () => formulario().find('[type="submit"]').first().click();

// Algunas pantallas (crear función, crear gasto) usan su propio asistente en vez de formularios.js
const conFormularioEstandar = (alternativa) =>
  cy.get('body').then(($b) => {
    if ($b.find('form[data-fetch="true"]').length) alternativa();
    else cy.log('Pantalla sin formulario data-fetch: no usa formularios.js');
  });

describe('ABM del administrador: una sola alerta', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('admin');
  });

  MODULOS.forEach((modulo) => {
    it(`editar ${modulo} y guardar: una confirmación y un solo aviso de éxito`, () => {
      cy.visit(`/administrador/${modulo}/editar?id=1`);
      conFormularioEstandar(() => {
        prepararEdicion(modulo);
        enviar();
        confirmarSiCorresponde();
        unSoloModal('exito');
      });
    });
  });

  Object.entries(BAJAS).forEach(([modulo, clase]) => {
    it(`dar de baja en ${modulo}: una confirmación y un solo aviso`, () => {
      cy.visit(`/administrador/${modulo}/listado`);
      cy.get('body').then(($b) => {
        if (!$b.find(`.${clase}`).length) {
          cy.log(`El listado de ${modulo} no tiene registros para dar de baja`);
          return;
        }
        // En funciones el botón está dentro de una tarjeta plegada
        cy.get(`.${clase}`).first().click({ force: true });
        confirmarUnaVez();
        // Puede ser éxito o un rechazo del servidor (ej. "tiene funciones activas"), pero uno solo
        cy.get('.alerta-modal.exito:not(.confirmacion), .alerta-modal.error:not(.confirmacion)', { timeout: 10000 })
          .should('exist');
        cy.wait(800);
        cy.get('body').then(($body) => {
          const todos = [...modales($body, 'exito'), ...modales($body, 'error')];
          expect(todos, textos(todos)).to.have.length(1);
        });
      });
    });
  });

  describe('nombres con apóstrofe', () => {
    const ALTAS = [
      { modulo: 'cantina', campos: { nombre_cantina: "Cantina D'Ana" }, tabla: 'cantina', columna: 'nombre_cantina' },
      { modulo: 'generos', campos: { genero_pelicula: "Cine d'autor" }, tabla: 'generos_peliculas', columna: 'genero_pelicula' },
      { modulo: 'estados_peliculas', campos: { nombre_estado_pelicula: "Pre'estreno" }, tabla: 'estados_peliculas', columna: 'nombre_estado_pelicula' },
      { modulo: 'perfiles', campos: { nombre_perfil: "Supervisor O'Neil", permiso_perfil: "Ver d'todo" }, tabla: 'perfiles', columna: 'nombre_perfil' },
    ];

    ALTAS.forEach(({ modulo, campos, tabla, columna }) => {
      const nombre = Object.values(campos)[0];
      it(`crear ${modulo} "${nombre}": se guarda tal cual con un solo aviso`, () => {
        cy.visit(`/administrador/${modulo}/crear`);
        Object.entries(campos).forEach(([campo, valor]) => cy.get(`[name="${campo}"]`).clear().type(valor));
        enviar();
        unSoloModal('exito');
        consultar(`SELECT COUNT(*) AS n FROM ${tabla} WHERE ${columna} = '${nombre.replace(/'/g, "''")}'`)
          .its('0.n').then(Number).should('eq', 1);
      });
    });

    const EDICIONES = [
      { modulo: 'peliculas', campo: 'titulo_pelicula', valor: "Ocean's Eleven", tabla: 'peliculas', columna: 'titulo_pelicula', id: 'id_pelicula' },
      { modulo: 'proveedores', campo: 'razon_social', valor: "O'Brien Hnos. S.A.", tabla: 'proveedores', columna: 'razon_social', id: 'id_proveedor' },
      { modulo: 'servicios', campo: 'nombre_servicio', valor: "Service d'Or", tabla: 'servicios_proveedor', columna: 'nombre_servicio', id: 'id_servicio' },
    ];

    EDICIONES.forEach(({ modulo, campo, valor, tabla, columna, id }) => {
      it(`editar ${modulo} con "${valor}": se guarda tal cual con un solo aviso`, () => {
        cy.visit(`/administrador/${modulo}/editar?id=1`);
        prepararEdicion(modulo);
        cy.get(`[name="${campo}"]`).clear().type(valor);
        enviar();
        confirmarUnaVez();
        unSoloModal('exito');
        consultar(`SELECT ${columna} AS v FROM ${tabla} WHERE ${id} = 1`).its('0.v').should('eq', valor);
      });
    });

    // Estos nombres no aceptan apóstrofes: antes el chequeo de duplicados igual mandaba el nombre a la
    // base y fallaba la consulta; ahora solo se ve el error de validación, una vez
    const RECHAZADOS = [
      { modulo: 'maquinas', campo: 'maquinas_nombre' },
      { modulo: 'productos', campo: 'nombre_producto_cantina' },
      { modulo: 'tipos_entradas', campo: 'tipo_entrada_desc' },
    ];

    RECHAZADOS.forEach(({ modulo, campo }) => {
      it(`editar ${modulo} con apóstrofe: un solo error de validación`, () => {
        cy.visit(`/administrador/${modulo}/editar?id=1`);
        prepararEdicion(modulo);
        cy.get(`[name="${campo}"]`).clear().type("Nombre D'Ana");
        formulario().invoke('attr', 'novalidate', 'novalidate');
        enviar();
        confirmarUnaVez();
        unSoloModal('error').should('not.match', /SQL|syntax|mysqli|Fatal/i);
      });
    });
  });
});
