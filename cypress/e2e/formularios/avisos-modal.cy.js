// Los avisos y confirmaciones usan el modal del sistema, no los alert/confirm/prompt del navegador.
// Si alguna pantalla vuelve a usar uno nativo, el test falla.

const consultar = (sql) => cy.task('consultarBase', sql);

// Falla el test si aparece un alert / confirm / prompt nativo
const sinAvisosNativos = () => {
  cy.on('window:alert', (texto) => { throw new Error(`alert() nativo: ${texto}`); });
  cy.on('window:confirm', (texto) => { throw new Error(`confirm() nativo: ${texto}`); });
  cy.on('window:before:load', (win) => {
    cy.stub(win, 'prompt').callsFake((texto) => { throw new Error(`prompt() nativo: ${texto}`); });
  });
};

const unaConfirmacion = () => {
  cy.get('.alerta-modal.confirmacion', { timeout: 10000 }).should('be.visible');
  cy.get('.alerta-modal.confirmacion').should('have.length', 1);
};

describe('Avisos en el modal del sistema', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    sinAvisosNativos();
  });

  it('gastos: anular pide confirmación en el modal y avisa el resultado', () => {
    cy.loginComo('admin');
    cy.visit('/administrador/gastos/listado');
    cy.get('.eliminar-gasto[data-id="1"]').click();
    unaConfirmacion();
    cy.get('.alerta-modal.confirmacion').should('contain.text', 'Gasto de prueba');

    // "NO" no anula nada
    cy.get('.alerta-modal.confirmacion .boton-modal.cancelar').click();
    consultar('SELECT estado_egreso FROM egresos WHERE id_egreso = 1').its('0.estado_egreso').should('eq', 'registrado');

    cy.get('.eliminar-gasto[data-id="1"]').click();
    unaConfirmacion();
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.exito').should('have.length', 1);
    consultar('SELECT estado_egreso FROM egresos WHERE id_egreso = 1').its('0.estado_egreso').should('eq', 'anulado');
  });

  it('monitor de cajas: forzar el cierre pide confirmación y observaciones en un solo modal', () => {
    cy.loginComo('vfunciones');
    cy.visit('/vendedor/caja/abrir');
    cy.get('#rela_caja').select('1');
    cy.get('#monto_inicial').clear().type('10000');
    cy.get('#btn-abrir').click();
    cy.location('pathname', { timeout: 10000 }).should('eq', '/vendedor/caja/estado');

    cy.loginComo('admin');
    cy.visit('/administrador/actividadcajas/monitor');
    cy.get('.btn-forzar-cierre').first().click();
    unaConfirmacion();
    cy.get('.alerta-modal.confirmacion .modal-campo-texto').should('have.value', 'Cierre forzado por administrador')
      .clear().type('Se fue sin cerrar');
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.exito').should('have.length', 1);
    consultar("SELECT COUNT(*) AS n FROM arqueo_cajas WHERE estado_arqueo = 'abierto'").its('0.n').then(Number).should('eq', 0);
  });

  it('búsqueda vacía en un listado: aviso en el modal', () => {
    cy.loginComo('admin');
    cy.visit('/administrador/peliculas/listado');
    cy.get('#busqueda-pelicula').clear();
    cy.get('#btn-buscar-pelicula').click();
    cy.get('.alerta-modal.error').should('have.length', 1).and('contain.text', 'término de búsqueda');
  });
});
