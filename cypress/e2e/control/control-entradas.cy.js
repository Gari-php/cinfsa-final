// Control de entradas en la puerta (etapa 3 del QR): el controlador escanea el QR o escribe el
// código corto, y la entrada se marca usada. Ventana: desde 60 min antes hasta que termina la función.
// Base de prueba: usuario control_test (perfil CONTROL_ACCESO = 7, id 6), módulo CONTROL_ENTRADAS = 9.
// Película de prueba: 120 minutos.

const ID_CLIENTE = 4;
const ID_CONTROL = 6;
const PERFIL_VFUNCIONES = 4;
const MODULO_CONTROL = 9;

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

// Entrada vendida (con código) para una función que empieza dentro de N minutos
const entradaEn = (minutos) =>
  cy.task('crearFuncion', minutos).then((idFuncion) =>
    consultar('SELECT MIN(id_butaca) AS id FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1')
      .then(([{ id }]) => cy.task('venderButacaWeb', { idButaca: Number(id), idFuncion, idUsuario: ID_CLIENTE, conCodigo: true }))
      .then((idEntrada) => consultar(`SELECT id_entrada, codigo_acceso FROM entradas WHERE id_entrada = ${idEntrada}`))
      .then(([e]) => ({ id: Number(e.id_entrada), codigo: e.codigo_acceso, qr: `CINFSA-E-${e.codigo_acceso}` })));

const escanear = (codigo, accion = 'validar') =>
  cy.postJson(`/control/entradas/${accion}`, { codigo }).its('body').then(json);

const estadoDe = (id) =>
  consultar(`SELECT estado, usada_por, usada_en FROM entradas WHERE id_entrada = ${id}`).then(([e]) => e);

const auditoriaDe = (accion, id) =>
  consultar(`SELECT * FROM auditoria WHERE accion = '${accion}' AND id_entidad = ${id}`);

describe('Control de entradas en la puerta', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('dentro de horario: verde, queda usada por el controlador y no puede volver a entrar', () => {
    entradaEn(30).then((e) => {
      cy.loginComo('control');
      escanear(e.qr).then((r) => {
        expect(r.resultado).to.eq('verde');
        expect(r.titulo).to.eq('Puede pasar');
        expect(r.puede_deshacer).to.eq(true);
        expect(r.entrada.pelicula).to.eq('Pelicula de Prueba');
        expect(r.entrada.codigo).to.eq(e.codigo.slice(0, 8).toUpperCase());
      });
      estadoDe(e.id).then((x) => {
        expect(Number(x.estado)).to.eq(2);
        expect(Number(x.usada_por)).to.eq(ID_CONTROL);
        expect(x.usada_en).to.not.eq(null);
      });

      escanear(e.qr).then((r) => {
        expect(r.resultado).to.eq('rojo');
        expect(r.titulo).to.eq('Ya ingresó');
        expect(r.mensaje).to.contain('control_test');
      });
    });
  });

  it('deshacer devuelve la entrada a activa y queda en la auditoría', () => {
    entradaEn(30).then((e) => {
      cy.loginComo('control');
      escanear(e.qr).its('resultado').should('eq', 'verde');

      escanear(e.qr, 'deshacer').its('ok').should('eq', true);
      estadoDe(e.id).then((x) => {
        expect(Number(x.estado)).to.eq(1);
        expect(x.usada_por).to.eq(null);
      });
      auditoriaDe('entrada.deshacer_ingreso', e.id).should('have.length', 1);

      // Ya activa de nuevo: se puede volver a escanear, y deshacer dos veces no hace nada
      escanear(e.qr, 'deshacer').its('ok').should('eq', false);
      escanear(e.qr).its('resultado').should('eq', 'verde');
    });
  });

  it('otro controlador no puede deshacer lo que marcó uno', () => {
    entradaEn(30).then((e) => {
      cy.loginComo('control');
      escanear(e.qr).its('resultado').should('eq', 'verde');
      cy.loginComo('admin');
      escanear(e.qr, 'deshacer').its('ok').should('eq', false);
      estadoDe(e.id).its('estado').then(Number).should('eq', 2);
    });
  });

  it('antes de horario: amarillo; "dejar pasar igual" la marca usada y queda en la auditoría', () => {
    entradaEn(180).then((e) => {
      cy.loginComo('control');
      escanear(e.qr).then((r) => {
        expect(r.resultado).to.eq('amarillo');
        expect(r.titulo).to.eq('Todavía no');
        expect(r.mensaje).to.match(/^Es para el \S+ \d{2}\/\d{2} \d{2}:\d{2}$/);
        expect(r.puede_forzar).to.eq(true);
      });
      estadoDe(e.id).its('estado').then(Number).should('eq', 1); // el amarillo no la marca

      escanear(e.qr, 'forzar').then((r) => {
        expect(r.resultado).to.eq('verde');
        expect(r.mensaje).to.eq('Ingreso fuera de horario autorizado');
      });
      estadoDe(e.id).its('estado').then(Number).should('eq', 2);
      auditoriaDe('entrada.fuera_horario', e.id).then((filas) => {
        expect(filas).to.have.length(1);
        expect(filas[0].nombre_usuario).to.eq('control_test');
        expect(filas[0].descripcion).to.contain('Pelicula de Prueba').and.contain('Sala 1');
      });

      // Forzar no sirve para una entrada ya usada
      escanear(e.qr, 'forzar').its('titulo').should('eq', 'Ya ingresó');
    });
  });

  it('a 50 minutos del inicio ya puede pasar (ventana de 60)', () => {
    entradaEn(50).then((e) => {
      cy.loginComo('control');
      escanear(e.qr).its('resultado').should('eq', 'verde');
    });
  });

  it('función terminada: amarillo', () => {
    entradaEn(-200).then((e) => {
      cy.loginComo('control');
      escanear(e.qr).then((r) => {
        expect(r.resultado).to.eq('amarillo');
        expect(r.titulo).to.eq('La función ya terminó');
      });
    });
  });

  it('cancelada, inexistente o un QR ajeno: rojo', () => {
    entradaEn(30).then((e) => {
      cy.loginComo('admin');
      cy.postJson('/administrador/entradas/eliminar', { id_entrada: e.id });

      cy.loginComo('control');
      escanear(e.qr).then((r) => {
        expect(r.resultado).to.eq('rojo');
        expect(r.titulo).to.eq('Entrada cancelada');
      });
      escanear(e.qr, 'forzar').its('titulo').should('eq', 'Entrada cancelada');
      escanear(`CINFSA-E-${'0'.repeat(32)}`).its('titulo').should('eq', 'Entrada inexistente');
      escanear('https://www.google.com').its('titulo').should('eq', 'QR no válido');
    });
  });

  it('pantalla: se valida con el código corto escrito a mano', () => {
    entradaEn(30).then((e) => {
      cy.loginComo('control');
      cy.visit('/control/entradas');
      cy.get('h1').should('contain.text', 'Control de entradas');

      cy.get('#codigo-manual').type(e.codigo.slice(0, 8).toUpperCase());
      cy.get('#form-manual').submit();

      cy.get('#resultado').should('be.visible').and('have.class', 'verde');
      cy.get('#resultado-titulo').should('have.text', 'Puede pasar');
      cy.get('#resultado-entrada').should('contain.text', 'Pelicula de Prueba').and('contain.text', 'Sala 1');
      cy.get('#btn-deshacer').should('be.visible');
      cy.get('#historial li.verde').should('have.length', 1);

      // Deshacer desde la pantalla
      cy.get('#btn-deshacer').click();
      cy.get('#resultado-titulo').should('have.text', 'Ingreso deshecho');
      estadoDe(e.id).its('estado').then(Number).should('eq', 1);
    });
  });

  it('pantalla: un amarillo ofrece "Dejar pasar igual"', () => {
    entradaEn(180).then((e) => {
      cy.loginComo('control');
      cy.visit('/control/entradas');
      cy.get('#codigo-manual').type(e.codigo.slice(0, 8));
      cy.get('#form-manual').submit();

      cy.get('#resultado').should('have.class', 'amarillo');
      cy.get('#btn-forzar').should('be.visible').click();
      cy.get('#resultado').should('have.class', 'verde');
      cy.get('#resultado-mensaje').should('have.text', 'Ingreso fuera de horario autorizado');
    });
  });

  it('el vendedor de funciones accede solo si le dan el módulo, y ve el botón en su panel', () => {
    cy.loginComo('vfunciones');
    cy.request('/control/entradas').its('redirects').should('have.length.greaterThan', 0);
    cy.postJson('/control/entradas/validar', { codigo: 'CINFSA-E-' + '0'.repeat(32) }).its('body')
      .should((b) => expect(typeof b === 'string' ? b : JSON.stringify(b)).not.to.contain('"resultado"'));

    cy.loginComo('admin');
    cy.postJson('/administrador/modulos/asignar', { id_perfil: PERFIL_VFUNCIONES, id_modulo: MODULO_CONTROL, estado: 1 })
      .its('body').then(json).its('ok').should('eq', true);

    cy.loginComo('vfunciones');
    cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
    cy.visit('/vendedor/caja/estado');
    cy.get('a.btn-control-entradas').should('have.attr', 'href', '/control/entradas').click();
    cy.location('pathname').should('eq', '/control/entradas');
    cy.get('a.control-enlace').first().should('have.attr', 'href', '/vendedor/caja'); // "Volver"

    // Se lo quitan: el botón desaparece en la próxima página
    cy.loginComo('admin');
    cy.postJson('/administrador/modulos/asignar', { id_perfil: PERFIL_VFUNCIONES, id_modulo: MODULO_CONTROL, estado: 0 });
    cy.loginComo('vfunciones');
    cy.visit('/vendedor/caja/estado');
    cy.get('a.btn-control-entradas').should('not.exist');
  });
});
