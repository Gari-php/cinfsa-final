// Textos que escribe un cliente o un vendedor y después se muestran en otras pantallas
// (sobre todo en las del admin) tienen que verse como texto, nunca ejecutarse como HTML.
// Base de prueba: caja 1 de funciones; el cliente es el usuario 4.

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

const CARGA = '<img src=x onerror="window.__xss=1">';

// Al cargar la página, si el texto se ejecutó como HTML aparece window.__xss
const sinXss = () => cy.window().its('__xss').should('be.undefined');

describe('Protección contra XSS', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('/pago/pendiente muestra el número de orden como texto', () => {
    cy.loginComo('cliente');
    cy.visit(`/pago/pendiente?orden=${encodeURIComponent(CARGA)}`);
    cy.get('.resultado-pago strong').should('have.text', CARGA);
    cy.get('.resultado-pago strong img').should('not.exist');
    sinXss();
  });

  it('el concepto de un movimiento del vendedor se ve como texto en el detalle de caja del admin', () => {
    cy.loginComo('vfunciones');
    cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
    cy.postJson('/api/movimientos/ingreso', { concepto: CARGA, monto: 1, id_tipo_pago: 1 })
      .its('body').then((b) => expect(json(b).ok, json(b).mensaje).to.eq(true));

    consultar("SELECT id_arqueo_caja FROM arqueo_cajas WHERE estado_arqueo = 'abierto' ORDER BY id_arqueo_caja DESC LIMIT 1")
      .then(([{ id_arqueo_caja }]) => {
        // El propio vendedor también lo ve como texto
        cy.visit('/vendedor/caja');
        sinXss();

        cy.loginComo('admin');
        cy.visit(`/administrador/actividadcajas/detalle?id=${id_arqueo_caja}`);
        cy.contains('td', CARGA).should('exist').find('img').should('not.exist');
        sinXss();
      });
  });

  it('no se puede guardar un email con caracteres de HTML', () => {
    cy.loginComo('cliente');
    cy.csrfToken().then((token) => {
      cy.request({
        method: 'POST',
        url: '/perfil/actualizar',
        form: true,
        headers: { 'X-CSRF-Token': token },
        failOnStatusCode: false,
        body: {
          id_usuario: 4, nombre_usuario: 'cliente_test', nombre_persona: 'Cliente',
          apellido_persona: 'Uno', sexo: 1, email: '"<svg/onload=alert(1)>"@gmail.com',
        },
      }).its('body').then((b) => {
        expect(json(b).success).to.not.eq(true);
        expect(JSON.stringify(json(b))).to.contain('El formato del email no es válido');
      });
    });
    consultar('SELECT email FROM usuarios WHERE id_usuario = 4').its('0.email').should('eq', 'cliente@cinfsa.test');
  });
});
