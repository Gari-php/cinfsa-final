// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

// cypress/support/commands.js

/**
 * Comando para hacer login como administrador
 * Uso: cy.loginAsAdmin()
 */
Cypress.Commands.add('loginAsAdmin', () => {
  cy.fixture('usuarios').then(({ admin }) => {
    cy.visit('/');

    // Verificar que estamos en la página de login
    cy.get('h2').should('contain.text', 'Iniciar Sesión');

    // Realizar login (usuario de la base de prueba, ver tests/db/datos_prueba.sql)
    cy.get('#nombre_usuario').type(admin.usuario);
    cy.get('#clave_usuario').type(admin.clave);
    cy.get('input[type="submit"]').click();

    // Verificar que el login fue exitoso
    cy.url().should('include', '/administrador');
  });
});

/**
 * Obtiene el token CSRF de la sesión actual (lo expone cada layout en window.CSRF_TOKEN)
 * Uso: cy.csrfToken().then(token => ...)
 */
Cypress.Commands.add('csrfToken', () => {
  return cy.request('/').its('body').then((html) => {
    const coincidencia = html.match(/CSRF_TOKEN = "([a-f0-9]+)"/);
    expect(coincidencia, 'token CSRF en la página').to.not.be.null;
    return coincidencia[1];
  });
});

/**
 * POST con JSON y token CSRF, igual que hace el frontend con fetch()
 * Uso: cy.postJson('/api/butacas/reservar', { ... })
 */
Cypress.Commands.add('postJson', (url, body) => {
  return cy.csrfToken().then((token) =>
    cy.request({
      method: 'POST',
      url,
      body,
      headers: { 'X-CSRF-Token': token },
      failOnStatusCode: false,
    })
  );
});

/**
 * Inicia sesión por API con un usuario del fixture (sin pasar por la pantalla de login)
 * Uso: cy.loginComo('cliente')  → roles: admin, vfunciones, vproductos, cliente, cliente2, control, entrega
 */
Cypress.Commands.add('loginComo', (rol) => {
  cy.clearCookies();
  cy.fixture('usuarios').then((usuarios) => {
    const u = usuarios[rol];
    cy.postJson('/', { nombre_usuario: u.usuario, clave_usuario: u.clave })
      .its('body.ok').should('eq', true);
  });
});

/**
 * Comando para hacer login con credenciales específicas
 * Uso: cy.loginAs('usuario', 'contraseña')
 */
Cypress.Commands.add('loginAs', (usuario, contraseña) => {
  cy.visit('/');
  cy.get('h2').should('contain.text', 'Iniciar Sesión');
  cy.get('#nombre_usuario').type(usuario);
  cy.get('#clave_usuario').type(contraseña);
  cy.get('input[type="submit"]').click();
});

/**
 * Comando para hacer logout
 * Uso: cy.logout()
 */
Cypress.Commands.add('logout', () => {
  // Ajusta según como sea tu logout
  cy.get('[data-cy="logout"]').click(); // o el selector correcto
  cy.url().should('eq', Cypress.config().baseUrl + '/');
});

/**
 * Comando para navegar a módulos de administrador
 * Uso: cy.navigateToAdmin('turnos/crear')
 */
Cypress.Commands.add('navigateToAdmin', (modulo) => {
  cy.visit(`/administrador/${modulo}`);
  
  // Verificar que no fuimos redirigidos al login
  cy.url().should('include', `/administrador/${modulo}`);
  cy.get('h2').should('not.contain.text', 'Iniciar Sesión');
});

/**
 * Comando para interceptar peticiones comunes
 * Uso: cy.interceptCrud('turnos')
 */
Cypress.Commands.add('interceptCrud', (modulo) => {
  cy.intercept('POST', `/administrador/${modulo}/guardar`).as(`guardar${modulo}`);
  cy.intercept('POST', `/administrador/${modulo}/actualizar`).as(`actualizar${modulo}`);
  cy.intercept('POST', `/administrador/${modulo}/eliminar`).as(`eliminar${modulo}`);
  cy.intercept('POST', `/administrador/${modulo}/buscar`).as(`buscar${modulo}`);
});

/**
 * Comando para limpiar datos de prueba
 * Uso: cy.cleanTestData()
 */
Cypress.Commands.add('cleanTestData', () => {
  // Limpiar localStorage, sessionStorage, cookies
  cy.clearLocalStorage();
  cy.clearCookies();
  cy.window().then((win) => {
    win.sessionStorage.clear();
  });
});