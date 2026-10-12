// Cerrar sesión pide confirmación en todos los perfiles; al confirmar se cierra de verdad
// y queda registrado en la auditoría ("Cierre de sesión").
// Base de prueba: admin = 1, vfunciones = 2, vproductos = 3, cliente = 4, control = 6, entrega = 7.

const numero = (sql) => cy.task('consultarBase', sql).its(0).then((fila) => Number(Object.values(fila)[0]));
const cierresDe = (idUsuario) =>
  numero(`SELECT COUNT(*) FROM auditoria WHERE accion = 'sesion.logout' AND id_usuario = ${idUsuario}`);
const sigueLogueado = (url) =>
  cy.request({ url, followRedirect: false, failOnStatusCode: false }).its('status');

// Pantalla donde está el botón de salir de cada perfil
const PERFILES = [
  { rol: 'admin', id: 1, pantalla: '/administrador', protegida: '/administrador/usuarios/listado' },
  { rol: 'cliente', id: 4, pantalla: '/menu', protegida: '/perfil' },
  { rol: 'vfunciones', id: 2, pantalla: '/vendedor/caja/abrir', protegida: '/vendedor/caja/abrir' },
  { rol: 'vproductos', id: 3, pantalla: '/vendedorproductos/caja/abrir', protegida: '/vendedorproductos/caja/abrir' },
  { rol: 'control', id: 6, pantalla: '/control/entradas', protegida: '/control/entradas' },
  { rol: 'entrega', id: 7, pantalla: '/control/retiros', protegida: '/control/retiros' },
];

describe('Cerrar sesión', () => {
  before(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  PERFILES.forEach(({ rol, id, pantalla, protegida }) => {
    it(`${rol}: pide confirmación, cancelar no cierra y confirmar cierra y lo registra`, () => {
      cy.loginComo(rol);
      cierresDe(id).then((antes) => {
        cy.visit(pantalla);

        // Cancelar: el modal se va y la sesión sigue
        cy.get('a[data-cerrar-sesion]').first().click({ force: true });
        cy.get('#modal-cerrar-sesion').should('be.visible')
          .and('contain.text', '¿Seguro que querés cerrar sesión?');
        cy.contains('#modal-cerrar-sesion button', 'Cancelar').click();
        cy.get('#modal-cerrar-sesion').should('not.exist');
        cy.location('pathname').should('eq', pantalla);
        sigueLogueado(protegida).should('eq', 200);
        cierresDe(id).should('eq', antes);

        // Confirmar: vuelve al login, la sesión ya no sirve y queda en la auditoría
        cy.get('a[data-cerrar-sesion]').first().click({ force: true });
        cy.contains('#modal-cerrar-sesion button', 'Sí, cerrar sesión').click();
        cy.location('pathname').should('eq', '/');
        cy.get('h2').should('contain.text', 'Iniciar Sesión');
        sigueLogueado(protegida).should('eq', 302);
        cierresDe(id).should('eq', antes + 1);
      });
    });
  });

  it('con la caja abierta, el panel de la caja de cada vendedor tiene "Cerrar sesión"', () => {
    const paneles = [
      ['vfunciones', '/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 }, '/vendedor/caja'],
      ['vproductos', '/vendedorproductos/caja/abrir', { rela_caja: 3, monto_inicial: 0 }, '/vendedorproductos/caja/estado'],
    ];

    paneles.forEach(([rol, urlAbrir, datos, panel]) => {
      cy.loginComo(rol);
      cy.postJson(urlAbrir, datos);
      cy.visit(panel);
      cy.get('.info-usuario-caja .btn-salir-caja').should('be.visible').and('have.attr', 'href', '/logaut');
      cy.screenshot(`panel-caja-${rol}`, { capture: 'viewport' });
      cy.viewport(390, 844);
      cy.get('.btn-salir-caja').should('be.visible');
      cy.screenshot(`panel-caja-${rol}-celular`, { capture: 'viewport' });
      cy.viewport(1280, 720);

      cy.get('.btn-salir-caja').click();
      cy.contains('#modal-cerrar-sesion button', 'Sí, cerrar sesión').click();
      cy.location('pathname').should('eq', '/');
      sigueLogueado(panel).should('eq', 302);
    });
  });

  it('el administrador ve el cierre de sesión en la auditoría', () => {
    cy.loginComo('admin');
    cy.visit('/administrador/auditoria/listado?accion=sesion.logout');
    cy.get('.tabla-auditoria tbody').should('contain.text', 'cliente_test').and('contain.text', 'Cerró sesión');
  });
});
