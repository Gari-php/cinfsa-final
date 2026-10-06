// Una venta del vendedor de productos que deja el stock en 15 o menos (Stock::UMBRAL_STOCK_BAJO)
// avisa al administrador, una sola vez: cuando cruza el umbral, no en cada venta posterior.
// Base de prueba: Agua 500 ML (producto 7), stock 13 en la cantina 1 con 47 unidades; las cajas de
// productos están en la cantina 1. Ver tests/db/datos_prueba.sql.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const valor = (sql) => cy.task('consultarBase', sql).its(0).then((fila) => Number(Object.values(fila)[0]));
const avisosDeAgua = () =>
  valor("SELECT COUNT(*) FROM notificaciones WHERE tipo = 'stock_bajo' AND descripcion LIKE '%Agua 500 ML%'");

const vender = (cantidad) =>
  cy.postJson('/vendedorproductos/completar-venta', {
    productos: [{ id_producto_cantina: 7, id_stock: 13, cantidad, precio: 1000, total: 1000 * cantidad }],
    fichas: [],
    metodo_pago: 1,
    tipo_comprobante: 'TICKET',
    observaciones: '',
    total: 1000 * cantidad,
  }).its('body').then(json).its('ok').should('eq', true);

describe('Aviso de stock bajo al vender', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('avisa al administrador cuando una venta deja el stock en 15', () => {
    cy.loginComo('vproductos');
    cy.postJson('/vendedorproductos/caja/abrir', { rela_caja: 3, monto_inicial: 0 })
      .its('body').then(json).its('ok').should('eq', true);

    vender(31); // 47 → 16: todavía no es bajo
    avisosDeAgua().should('eq', 0);

    vender(1); // 16 → 15: cruza el umbral
    avisosDeAgua().should('be.greaterThan', 0).then((avisos) => {
      vender(1); // 15 → 14: ya estaba bajo, no se repite
      avisosDeAgua().should('eq', avisos);
    });

    // El administrador la ve en su campana
    cy.loginComo('admin');
    cy.request('/api/notificaciones/obtener?limite=10').its('body').then(json).then((r) => {
      const textos = JSON.stringify(r);
      expect(textos).to.contain("El producto 'Agua 500 ML' en Cantina 1 tiene solo 15 unidades");
    });
  });
});
