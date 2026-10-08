// Montos y stock de las ventas y devoluciones los decide el servidor, no la pantalla:
// precios de la base, devoluciones que no se repiten y movimientos de caja que no se borran.
// Base de prueba: Pochoclo Chico (producto 1, $2000), stock 13 (id_stock 1) en la cantina 1;
// caja 3 = productos (cantina 1); caja 1 = funciones; función 1 (sala 1, $3000).

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);
const numero = (sql) => consultar(sql).its(0).then((fila) => Number(Object.values(fila)[0]));
const stockPochoclo = () => numero('SELECT stock_cantina FROM stock_cantina WHERE id_stock_cantina = 1');

const venderPochoclo = ({ cantidad = 1, precio = 2000, total = 2000 } = {}) =>
  cy.postJson('/vendedorproductos/completar-venta', {
    productos: [{ id_producto_cantina: 1, id_stock: 1, cantidad, precio, total }],
    fichas: [], metodo_pago: 1, tipo_comprobante: 'TICKET', observaciones: '', total,
  }).its('body').then(json);

const abrirCajaProductos = () => {
  cy.loginComo('vproductos');
  cy.postJson('/vendedorproductos/caja/abrir', { rela_caja: 3, monto_inicial: 0 }).its('body').then(json).its('ok').should('eq', true);
};

describe('Integridad de plata y stock', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('la venta de cantina usa el precio de la base aunque la pantalla mande otro', () => {
    abrirCajaProductos();
    venderPochoclo({ precio: 1, total: 1 }).then((r) => {
      expect(r.ok, r.mensaje).to.eq(true);
      expect(Number(r.monto_total)).to.eq(2000);

      numero(`SELECT monto_total_cantina FROM cabecera_fact_cantina WHERE id_cabecera_fact_cantin = ${r.id_venta}`).should('eq', 2000);
      numero(`SELECT precio_unitario FROM detalle_fact_cantina WHERE rela_cabecera_fact_cantin = ${r.id_venta}`).should('eq', 2000);
      numero("SELECT monto FROM movimientos_caja WHERE concepto_movimiento LIKE 'Venta:%' ORDER BY id_movimiento_caja DESC LIMIT 1")
        .should('eq', 2000);
    });
    stockPochoclo().should('eq', 12);
  });

  it('rechaza cantidades negativas o no enteras sin tocar el stock', () => {
    abrirCajaProductos();
    [-5, 0, 1.5, '2abc'].forEach((cantidad) => {
      venderPochoclo({ cantidad }).then((r) => expect(r.ok, `cantidad ${cantidad}`).to.eq(false));
    });
    stockPochoclo().should('eq', 13);
    numero('SELECT COUNT(*) FROM cabecera_fact_cantina').should('eq', 0);
  });

  it('una devolución con el mismo ítem repetido lo devuelve una sola vez', () => {
    // El vendedor de productos puede devolver (módulo DEVOLUCION_PRODUCTOS, apagado en la base de prueba)
    cy.loginComo('admin');
    cy.postJson('/administrador/modulos/asignar', { id_perfil: 5, id_modulo: 8, estado: 1 });

    abrirCajaProductos();
    venderPochoclo().then((r) => {
      stockPochoclo().should('eq', 12);
      cy.postJson('/ventas/consulta/api/devolucion', {
        id_venta: r.id_venta, items: [{ index: 0 }, { index: 0 }, { index: 0 }], motivo: 'prueba',
      }).its('body').then(json).its('ok').should('eq', true);

      stockPochoclo().should('eq', 13);
      numero("SELECT SUM(monto) FROM egresos WHERE concepto LIKE 'DEVOLUCIÓN%'").should('eq', 2000);
      numero("SELECT SUM(monto) FROM movimientos_caja WHERE tipo_movimiento = 'egreso' AND concepto_movimiento LIKE 'DEVOLUCIÓN%'")
        .should('eq', 2000);

      // Repetir el pedido completo tampoco devuelve nada más
      cy.postJson('/ventas/consulta/api/devolucion', { id_venta: r.id_venta, items: [{ index: 0 }], motivo: 'prueba' })
        .its('body').then(json).its('ok').should('eq', false);
      stockPochoclo().should('eq', 13);
    });
  });

  it('una entrada no se puede devolver dos veces', () => {
    cy.loginComo('vfunciones');
    cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
    consultar('SELECT id_butaca FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1 ORDER BY id_butaca LIMIT 1')
      .then(([{ id_butaca }]) => {
        cy.postJson('/vendedor/funciones/procesar-venta', {
          id_funcion: 1, butacas: [Number(id_butaca)], tipo_pago: 1, tipo_comprobante: 'TICKET',
        }).its('body').then(json).its('ok').should('eq', true);
      });

    numero('SELECT MAX(id_entrada) FROM entradas').then((idEntrada) => {
      const devolver = () => cy.postJson('/vendedor/entradas/procesar-devolucion', { id_entrada: idEntrada }).its('body').then(json);
      devolver().then((r) => {
        expect(r.ok, r.mensaje).to.eq(true);
        expect(Number(r.monto)).to.eq(3000);
      });
      devolver().its('ok').should('eq', false);

      numero(`SELECT COUNT(*) FROM egresos WHERE concepto LIKE 'Devolución entrada #${idEntrada} %'`).should('eq', 1);
      numero(`SELECT COUNT(*) FROM movimientos_caja WHERE concepto_movimiento LIKE 'Devolución entrada #${idEntrada} %'`).should('eq', 1);
    });
  });

  it('el vendedor ya no puede borrar movimientos de su caja (por ejemplo, el ingreso de una venta)', () => {
    abrirCajaProductos();
    venderPochoclo().its('ok').should('eq', true);
    numero("SELECT id_movimiento_caja FROM movimientos_caja WHERE concepto_movimiento LIKE 'Venta:%' LIMIT 1").then((idMov) => {
      cy.postJson('/api/movimientos/eliminar', { id_movimiento: idMov }).its('status').should('eq', 404);
      numero(`SELECT COUNT(*) FROM movimientos_caja WHERE id_movimiento_caja = ${idMov}`).should('eq', 1);
    });
  });
});
