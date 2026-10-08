// /pago/fallido recibe el número de orden por la URL (vuelve de Mercado Pago con un pago rechazado).
// Solo puede marcar como fallida una orden propia que siga pendiente.
// Base de prueba: cliente = usuario 4, cliente2 = usuario 5.

const ID_CLIENTE = 4;

const consultar = (sql) => cy.task('consultarBase', sql);
const estadoDe = (numero) =>
  consultar(`SELECT estado FROM ordenes WHERE numero_orden = '${numero}'`).its('0.estado');
const visitarFallido = (numero) => cy.visit(`/pago/fallido?orden=${encodeURIComponent(numero)}`);

describe('Pago fallido', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('otro cliente no puede marcar como fallida una orden ajena, ni pendiente ni pagada', () => {
    cy.task('crearPedidoWeb', { idUsuario: ID_CLIENTE, estado: 'pendiente' }).then(({ numero_orden: pendiente }) => {
      cy.task('crearPedidoWeb', { idUsuario: ID_CLIENTE, estado: 'pagado' }).then(({ numero_orden: pagada }) => {
        cy.loginComo('cliente2');
        visitarFallido(pendiente);
        visitarFallido(pagada);
        estadoDe(pendiente).should('eq', 'pendiente');
        estadoDe(pagada).should('eq', 'pagado');
      });
    });
  });

  it('el dueño marca su orden pendiente como fallida, pero una ya pagada no cambia', () => {
    cy.task('crearPedidoWeb', { idUsuario: ID_CLIENTE, estado: 'pendiente' }).then(({ numero_orden: pendiente }) => {
      cy.task('crearPedidoWeb', { idUsuario: ID_CLIENTE, estado: 'pagado' }).then(({ numero_orden: pagada }) => {
        cy.loginComo('cliente');
        visitarFallido(pendiente);
        estadoDe(pendiente).should('eq', 'fallido');

        visitarFallido(pagada);
        estadoDe(pagada).should('eq', 'pagado');
      });
    });
  });
});
