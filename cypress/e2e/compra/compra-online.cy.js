// Compra online del cliente: selección de butacas, carrito y checkout.
// El pago con MercadoPago se simula (no se llama a la API real ni se cobra nada).
// Base de prueba: función 1 (sala 1, entrada 2D a $3000). Ver tests/db/datos_prueba.sql.

const FUNCION_ONLINE = 1;

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

describe('Compra online', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('el cliente elige 2 butacas, las agrega al carrito y llega al pago', () => {
    cy.loginComo('cliente');
    cy.intercept('POST', '/api/butacas/reservar').as('agregarAlCarrito');

    cy.visit(`/butacas?id_funcion=${FUNCION_ONLINE}`);
    cy.get('.butaca[onclick]').should('have.length.greaterThan', 2).then(($disponibles) => {
      const ids = [$disponibles.eq(0).attr('data-id'), $disponibles.eq(1).attr('data-id')];
      ids.forEach((id) => cy.get(`.butaca[data-id="${id}"]`).click());
    });

    cy.get('#total-precio').invoke('text').should('match', /^6[.,]?000$/);
    cy.get('.btn-agregar-carrito').should('not.be.disabled').click();

    cy.wait('@agregarAlCarrito').its('response.body').then((body) => {
      expect(json(body).ok).to.eq(true);
    });
    cy.request('/api/carrito/contar').its('body').then((body) => expect(json(body).total).to.eq(2));

    // ── Checkout ───────────────────────────────────────────────
    cy.visit('/carrito/checkout');
    cy.get('.checkout-item').should('have.length', 2);
    cy.get('.total-amount').should('contain.text', '6.000');

    // MercadoPago simulado: se responde como si hubiera creado la preferencia
    cy.intercept('POST', '/pago/crear-orden', {
      statusCode: 200,
      body: { ok: true, init_point: '/pago/pendiente?orden=CINFSA-TEST' },
    }).as('crearOrden');

    cy.get('input[name="telefono"]').type('3704000000');
    cy.get('input[name="dni"]').type('30111222');
    cy.get('.btn-pagar').click();

    cy.wait('@crearOrden').its('request.body').should('include', { telefono: '3704000000', dni: '30111222' });
    cy.location('pathname').should('eq', '/pago/pendiente');
  });

  it('una butaca vendida en boletería aparece ocupada y no se puede agregar al carrito', () => {
    // Un vendedor vende una butaca de la función online
    consultar('SELECT MIN(id_butaca) AS id FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1')
      .then(([{ id }]) => {
        cy.loginComo('vfunciones');
        cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 })
          .its('body').then((b) => expect(json(b).ok).to.eq(true));
        cy.postJson('/vendedor/funciones/procesar-venta', {
          id_funcion: FUNCION_ONLINE, butacas: [Number(id)], tipo_pago: 1, tipo_comprobante: 'TICKET',
        }).its('body').then((b) => expect(json(b).ok, json(b).mensaje).to.eq(true));

        // Otro cliente la ve ocupada en el mapa
        cy.loginComo('cliente2');
        cy.visit(`/butacas?id_funcion=${FUNCION_ONLINE}`);
        cy.get(`.butaca[data-id="${id}"]`)
          .should('have.attr', 'data-estado-num', '3')
          .and('not.have.attr', 'onclick');

        // Y aunque la pida directo a la API, se rechaza
        cy.postJson('/api/butacas/reservar', { butacas: [Number(id)], id_funcion: FUNCION_ONLINE })
          .its('body').then((b) => {
            expect(json(b).ok).to.eq(false);
            expect(json(b).mensaje).to.contain('ya fueron vendidas');
          });
        cy.request('/api/carrito/contar').its('body').then((b) => expect(json(b).total).to.eq(0));
      });
  });

  it('el checkout con el carrito vacío vuelve al inicio', () => {
    cy.loginComo('cliente');
    cy.visit('/carrito/checkout');
    cy.location('pathname').should('eq', '/menu');
  });
});
