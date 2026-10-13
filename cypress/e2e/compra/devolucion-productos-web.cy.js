// Devolución parcial de productos y fichas de una compra web (admin → Ventas web → detalle):
// solo se devuelve lo que el cliente todavía no retiró, vuelve al stock, deja de estar para retirar
// y, si después se cancela la orden entera, no se repone dos veces.
// Base de prueba: el pedido web de prueba trae 10 Pochoclo Chico (producto 1, cantina 1, $2000) y
// 6 fichas de la máquina 1; cliente = usuario 4; entrega = usuario de retiros.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);
const numero = (sql) => consultar(sql).its(0).then((fila) => Number(Object.values(fila)[0]));
const stockPochoclo = () => numero('SELECT stock_cantina FROM stock_cantina WHERE rela_producto_cantina = 1 AND rela_cantina = 1');
const stockFichas = () => numero('SELECT f.cantidad_ficha FROM fichas f JOIN maquinas m ON m.rela_fichas = f.id_fichas WHERE m.id_maquinas = 1');

const pedido = () =>
  cy.task('crearPedidoWeb', { idUsuario: 4 }).then((p) =>
    consultar(`SELECT id_detalle, tipo_producto FROM detalle_orden WHERE id_orden = ${p.id_orden}`).then((filas) => ({
      ...p,
      qr: `CINFSA-O-${p.codigo}`,
      cantina: Number(filas.find((f) => f.tipo_producto === 'cantina').id_detalle),
      fichas: Number(filas.find((f) => f.tipo_producto === 'fichas').id_detalle),
    })));

const entregar = (qr, items) => {
  cy.loginComo('entrega');
  return cy.postJson('/control/retiros/entregar', { codigo: qr, items }).its('body').then(json);
};
const devolver = (idDetalle, cantidad) =>
  cy.postJson('/administrador/movimientos-web/devolver-producto', { id_detalle: idDetalle, cantidad }).its('body').then(json);

describe('Devolución de productos de compras web', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('devuelve solo lo que no se retiró: vuelve al stock y ya no se puede retirar', () => {
    pedido().then((p) => {
      entregar(p.qr, { [p.cantina]: 3 }).then((r) => expect(r.error).to.be.undefined);

      stockPochoclo().then((antes) => {
        cy.loginComo('admin');
        // Quedan 7 sin retirar: no se pueden devolver 8
        devolver(p.cantina, 8).then((r) => {
          expect(r.ok).to.eq(false);
          expect(r.mensaje).to.contain('Solo se pueden devolver 7');
        });
        devolver(p.cantina, 2).then((r) => {
          expect(r.ok).to.eq(true);
          expect(r.mensaje).to.contain('Recordá hacer el reembolso de $4.000');
        });
        stockPochoclo().should('eq', antes + 2);
      });

      numero(`SELECT cantidad_devuelta FROM detalle_orden WHERE id_detalle = ${p.cantina}`).should('eq', 2);
      numero(`SELECT COUNT(*) FROM auditoria WHERE accion = 'orden.devolver_producto' AND id_entidad = ${p.id_orden}`).should('eq', 1);

      // En la cantina quedan 5 (10 − 3 retirados − 2 devueltos) y no se pueden entregar más
      cy.loginComo('entrega');
      cy.postJson('/control/retiros/buscar', { codigo: p.qr }).its('body').then(json).then((r) => {
        const item = r.pedido.items.find((i) => i.id_detalle === p.cantina);
        expect(item).to.include({ comprado: 10, entregado: 3, devuelto: 2, quedan: 5 });
      });
      entregar(p.qr, { [p.cantina]: 6 }).its('error').should('contain', 'quedan 5');
    });
  });

  it('desde el detalle de la orden se elige cuántos devolver y avisa que el dinero va por Mercado Pago', () => {
    pedido().then((p) => {
      cy.loginComo('admin');
      cy.visit(`/administrador/movimientos-web/detalle?id=${p.id_orden}`);

      cy.get(`.btn-devolver-producto-web[data-id-detalle="${p.fichas}"]`).click();
      cy.get('#modal-devolver-producto').should('be.visible').and('contain.text', 'NO devuelve el dinero');
      cy.get('#devolver-cantidad').should('have.value', '6').clear().type('2');
      cy.get('#devolver-monto').should('have.text', '$2.000');
      cy.contains('#modal-devolver-producto button', 'Devolver').click();

      cy.get('.alerta-modal.exito').should('be.visible').and('contain.text', 'Recordá hacer el reembolso de $2.000');
      numero(`SELECT cantidad_devuelta FROM detalle_orden WHERE id_detalle = ${p.fichas}`).should('eq', 2);
      cy.contains('.producto-detalles', 'Devuelto: 2').should('exist');
    });
  });

  it('al cancelar la orden entera no se repone dos veces lo que ya se devolvió', () => {
    pedido().then((p) => {
      entregar(p.qr, { [p.cantina]: 3 });

      stockPochoclo().then((pochoclosAntes) => {
        stockFichas().then((fichasAntes) => {
          cy.loginComo('admin');
          devolver(p.cantina, 2).its('ok').should('eq', true);
          cy.postJson('/administrador/movimientos-web/cancelar', { id_orden: p.id_orden }).its('body').then(json).its('ok')
            .should('eq', true);

          // Pochoclos: 2 devueltos + 5 que quedaban = 7 (los 3 retirados no vuelven); fichas: las 6
          stockPochoclo().should('eq', pochoclosAntes + 7);
          stockFichas().should('eq', fichasAntes + 6);
        });
      });
    });
  });
});
