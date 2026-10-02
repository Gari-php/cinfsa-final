// Login por rol y control de acceso entre módulos.
// Usuarios: cypress/fixtures/usuarios.json (base de prueba cinfsa1_test).

// Pide una URL sin seguir redirecciones y devuelve { status, destino }
const pedirSinSeguir = (url) =>
  cy.request({ url, followRedirect: false, failOnStatusCode: false }).then((r) => ({
    status: r.status,
    destino: r.redirectedToUrl || '',
  }));
describe('Login por rol', () => {
  beforeEach(() => {
    cy.clearCookies();
  });

  ['admin', 'vfunciones', 'vproductos', 'cliente'].forEach((rol) => {
    it(`${rol} inicia sesión y llega a su pantalla`, () => {
      cy.fixture('usuarios').then((usuarios) => {
        const u = usuarios[rol];
        cy.visit('/');
        cy.get('#nombre_usuario').type(u.usuario);
        cy.get('#clave_usuario').type(u.clave);
        cy.get('input[type="submit"]').click();

        cy.get('.alerta-modal.exito').should('be.visible');
        // los vendedores sin caja abierta pasan a /caja/abrir: se acepta el prefijo
        cy.location('pathname', { timeout: 10000 }).should('match', new RegExp('^' + u.inicio));
      });
    });
  });

  it('rechaza una contraseña incorrecta sin revelar si el usuario existe', () => {
    cy.visit('/');
    cy.get('#nombre_usuario').type('cliente_test');
    cy.get('#clave_usuario').type('incorrecta');
    cy.get('input[type="submit"]').click();

    cy.get('.alerta-modal.error').should('contain.text', 'Usuario o contraseña incorrectos');
    cy.location('pathname').should('eq', '/');
  });

  it('rechaza un usuario inexistente con el mismo mensaje', () => {
    cy.visit('/');
    cy.get('#nombre_usuario').type('no_existe_test');
    cy.get('#clave_usuario').type('Prueba123!');
    cy.get('input[type="submit"]').click();

    cy.get('.alerta-modal.error').should('contain.text', 'Usuario o contraseña incorrectos');
  });
});

describe('Control de acceso', () => {
  it('sin sesión no se puede entrar al panel de administración', () => {
    cy.clearCookies();
    pedirSinSeguir('/administrador').then(({ status, destino }) => {
      expect(status).to.eq(302);
      expect(destino).to.match(/\/$/);
    });
  });

  it('un cliente no puede entrar a administración ni a las cajas', () => {
    cy.loginComo('cliente');
    ['/administrador', '/administrador/usuarios/listado', '/vendedor/caja/abrir', '/vendedorproductos/caja/abrir']
      .forEach((url) => {
        pedirSinSeguir(url).then(({ status, destino }) => {
          expect(status, url).to.eq(302);
          expect(destino, url).to.match(/\/$/);
        });
      });
  });

  it('el vendedor de funciones abre su caja pero no la de productos', () => {
    cy.loginComo('vfunciones');
    pedirSinSeguir('/vendedor/caja/abrir').its('status').should('eq', 200);
    pedirSinSeguir('/vendedorproductos/caja/abrir').then(({ status, destino }) => {
      expect(status).to.eq(302);
      expect(destino).to.match(/\/$/);
    });
    // la API responde con Content-Type JSON, así Cypress la interpreta como objeto
    cy.request('/vendedorproductos/api/resumen-cierre').its('body.mensaje').should('eq', 'No autorizado');
  });

  it('el vendedor de productos abre su caja pero no la de funciones', () => {
    cy.loginComo('vproductos');
    pedirSinSeguir('/vendedorproductos/caja/abrir').its('status').should('eq', 200);
    pedirSinSeguir('/vendedor/caja/abrir').then(({ status, destino }) => {
      expect(status).to.eq(302);
      expect(destino).to.match(/\/$/);
    });
    cy.request('/api/caja/verificar').its('body.mensaje').should('eq', 'No autorizado');
  });

  it('el administrador puede ver ambas cajas', () => {
    cy.loginComo('admin');
    pedirSinSeguir('/vendedor/caja/abrir').its('status').should('eq', 200);
    pedirSinSeguir('/vendedorproductos/caja/abrir').its('status').should('eq', 200);
  });
});
