// Entradas compradas por la web: solo el administrador las cancela (desde Ventas web, una por una
// o la orden entera) y el reembolso se hace por Mercado Pago. En boletería no se pueden devolver:
// esa plata nunca pasó por la caja.
// Base de prueba: función 1 (sala 1); cliente = usuario 4; vfunciones tiene DEVOLUCION_ENTRADAS.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);

// Vende por la web una butaca libre de la sala 1 y devuelve { idEntrada, idOrden, butaca }
const ventaWeb = (indice = 0) =>
  consultar(`SELECT id_butaca, fila_butaca, numero_butaca FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1 ORDER BY id_butaca LIMIT 1 OFFSET ${indice}`)
    .then(([butaca]) =>
      cy.task('venderButacaWeb', { idButaca: Number(butaca.id_butaca), idFuncion: 1, idUsuario: 4 }).then((idEntrada) =>
        consultar(`SELECT id_orden FROM butacas_vendidas WHERE id_entrada = ${idEntrada}`).then(([{ id_orden }]) => ({
          idEntrada, idOrden: Number(id_orden), butaca,
        }))));

describe('Devolución de entradas compradas por la web', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('el admin cancela una entrada web desde el detalle de la orden, avisando que el dinero va por Mercado Pago', () => {
    ventaWeb().then(({ idEntrada, idOrden, butaca }) => {
      cy.loginComo('admin');
      cy.visit(`/administrador/movimientos-web/detalle?id=${idOrden}`);

      cy.get('.btn-cancelar-entrada-web').should('have.length', 1).click();
      cy.contains('NO devuelve el dinero').should('be.visible');
      cy.contains(`Fila ${butaca.fila_butaca} · Asiento ${butaca.numero_butaca}`).should('be.visible');
      cy.contains('$3.000').should('be.visible');
      cy.get('.boton-modal.confirmar').filter(':visible').first().click();

      cy.contains('Recordá hacer el reembolso').should('be.visible');
      consultar(`SELECT estado FROM entradas WHERE id_entrada = ${idEntrada}`).its('0.estado').should('eq', '-1');
      // La butaca quedó libre y ninguna caja registró plata
      consultar(`SELECT COUNT(*) AS n FROM butacas_vendidas WHERE id_entrada = ${idEntrada}`).its('0.n').should('eq', '0');
      consultar(`SELECT COUNT(*) AS n FROM egresos WHERE concepto LIKE 'Devolución entrada #${idEntrada} %'`).its('0.n').should('eq', '0');
      consultar(`SELECT COUNT(*) AS n FROM auditoria WHERE accion = 'orden.cancelar_entrada' AND id_entidad = ${idEntrada}`)
        .its('0.n').should('eq', '1');

      // Al volver al detalle, la entrada figura cancelada y ya no tiene botón
      cy.visit(`/administrador/movimientos-web/detalle?id=${idOrden}`);
      cy.get('.btn-cancelar-entrada-web').should('not.exist');
      cy.contains('.producto-detalles', 'Cancelada').should('exist');
    });
  });

  it('una entrada web no se puede devolver en boletería', () => {
    ventaWeb().then(({ idEntrada }) => {
      cy.loginComo('vfunciones');
      cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });

      cy.request(`/vendedor/entradas/datos-devolucion?id=${idEntrada}`).its('body').then(json).then((r) => {
        expect(r.ok).to.eq(false);
        expect(r.mensaje).to.contain('se compró por la web');
      });
      cy.postJson('/vendedor/entradas/procesar-devolucion', { id_entrada: idEntrada }).its('body').then(json).then((r) => {
        expect(r.ok).to.eq(false);
        expect(r.mensaje).to.contain('se compró por la web');
      });

      consultar(`SELECT estado FROM entradas WHERE id_entrada = ${idEntrada}`).its('0.estado').should('eq', '1');
      consultar(`SELECT COUNT(*) AS n FROM egresos WHERE concepto LIKE 'Devolución entrada #${idEntrada} %'`).its('0.n').should('eq', '0');
      consultar(`SELECT COUNT(*) AS n FROM movimientos_caja WHERE concepto_movimiento LIKE 'Devolución entrada #${idEntrada} %'`).its('0.n').should('eq', '0');
    });
  });

  it('el aviso de cancelar la orden completa aclara que no devuelve el dinero', () => {
    ventaWeb().then(({ idOrden }) => {
      cy.loginComo('admin');
      cy.visit('/administrador/movimientos-web/listado');
      cy.get(`[data-accion="cancelar"][data-id="${idOrden}"]`).click();
      cy.get('#modalConfirmacionMensaje').should('be.visible')
        .and('contain.text', 'Se cancelan sus entradas')
        .and('contain.text', 'NO devuelve el dinero');
    });
  });
});
