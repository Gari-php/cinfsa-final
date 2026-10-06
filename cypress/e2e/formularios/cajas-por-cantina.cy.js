// Cada caja de productos pertenece a una cantina (Administrador → Cantina → Editar): el vendedor que
// la abre solo ve y vende el stock de esa cantina, y una devolución repone el stock solo ahí.
// Base de prueba: cajas 3 ("Caja Principal Ventas Productos") y 4 ("Caja Secundaria Ventas Productos"),
// ambas en la cantina 1. Agua 500 ML (producto 7): stock 13 en la cantina 1 (47 u.) y stock 12 en la
// cantina 2 (50 u.). Los pochoclos solo tienen stock en la cantina 1. Ver tests/db/datos_prueba.sql.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);
const valor = (sql) => consultar(sql).its(0).then((fila) => Number(Object.values(fila)[0]));
const stock = (id) => valor(`SELECT stock_cantina FROM stock_cantina WHERE id_stock_cantina = ${id}`);
const cantinaDeCaja = (id) =>
  consultar(`SELECT rela_cantina FROM cajas WHERE id_caja = ${id}`).its(0)
    .then((fila) => (fila.rela_cantina === null ? null : Number(fila.rela_cantina)));

// Deja las cajas marcadas en la cantina indicada (las demás de esa cantina quedan sin asignar)
const asignarCajas = (idCantina, nombre, cajas) => {
  const body = { id_cantina: idCantina, nombre_cantina: nombre, estado: 1, cajas_enviadas: 1 };
  cajas.forEach((id) => { body[`caja_${id}`] = 1; });
  return cy.postJson('/administrador/cantina/actualizar', body).its('body').then(json);
};

const abrirCaja = (idCaja) =>
  cy.postJson('/vendedorproductos/caja/abrir', { rela_caja: idCaja, monto_inicial: 0 }).its('body').then(json);

const vender = (idStock, cantidad, precio = 1000) =>
  cy.postJson('/vendedorproductos/completar-venta', {
    productos: [{ id_producto_cantina: 7, id_stock: idStock, cantidad, precio, total: precio * cantidad }],
    fichas: [],
    metodo_pago: 1,
    tipo_comprobante: 'TICKET',
    observaciones: '',
    total: precio * cantidad,
  }).its('body').then(json);

describe('Cajas asignadas a una cantina', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('admin');
  });

  it('el administrador pasa una caja a otra cantina desde Editar', () => {
    cy.visit('/administrador/cantina/editar?id=2');
    cy.contains('label.caja-cantina', 'Caja Secundaria Ventas Productos')
      .should('contain', 'Hoy está en Cantina 1')
      .find('input[type="checkbox"]').should('not.be.checked').check();
    cy.get('input[type="submit"]').click();
    cy.get('.confirmacion .confirmar').click();
    cy.location('pathname', { timeout: 6000 }).should('eq', '/administrador/cantina/listado');

    cy.contains('tr', 'cantina 2').should('contain', 'Caja Secundaria Ventas Productos');
    cy.contains('tr', 'Cantina 1').should('contain', 'Caja Principal Ventas Productos')
      .and('not.contain', 'Caja Secundaria');
    cantinaDeCaja(4).should('eq', 2);

    consultar(`SELECT * FROM auditoria WHERE accion = 'cantina.modificar' ORDER BY id_auditoria DESC LIMIT 1`)
      .its(0).then((r) => {
        expect(json(r.datos_antes)).to.deep.eq({ cajas: 'Ninguna' });
        expect(json(r.datos_despues)).to.deep.eq({ cajas: 'Caja Secundaria Ventas Productos' });
      });
  });

  it('el vendedor solo ve y vende el stock de la cantina de su caja; la devolución vuelve ahí', () => {
    asignarCajas(2, 'cantina 2', [4]).its('ok').should('eq', true);

    cy.loginComo('vproductos');
    abrirCaja(4).its('ok').should('eq', true);
    cy.visit('/vendedorproductos/productos/listado');
    cy.contains('.numero-caja', 'cantina 2');

    // Agua 500 ML aparece una sola vez, con el stock de la cantina 2; los pochoclos (cantina 1) no
    cy.postJson('/vendedorproductos/buscar-producto', { busqueda: 'Agua' }).its('body').then(json).then((r) => {
      expect(r.productos.map((p) => Number(p.id_stock_cantina))).to.deep.eq([12]);
    });
    cy.postJson('/vendedorproductos/buscar-producto', { busqueda: 'Pochoclo' }).its('body').then(json)
      .its('productos').should('have.length', 0);

    // No se puede vender stock de otra cantina aunque se mande su id
    vender(13, 1).then((r) => {
      expect(r.ok).to.eq(false);
      expect(r.mensaje).to.contain('no tiene stock en la cantina de esta caja');
    });
    stock(13).should('eq', 47);

    vender(12, 2).its('ok').should('eq', true);
    stock(12).should('eq', 48);
    stock(13).should('eq', 47);
    valor('SELECT rela_cantina FROM cabecera_fact_cantina ORDER BY id_cabecera_fact_cantin DESC LIMIT 1')
      .should('eq', 2);

    // Devolución: repone solo en la cantina 2 (antes sumaba en todas las cantinas del producto)
    cy.loginComo('admin');
    valor('SELECT MAX(id_cabecera_fact_cantin) FROM cabecera_fact_cantina').then((idVenta) => {
      cy.postJson('/ventas/consulta/api/devolucion', {
        id_venta: idVenta,
        items: [{ index: 0, tipo_item: 'producto', producto: 'Agua 500 ML' }],
        motivo: 'Prueba',
      }).its('body').then(json).its('ok').should('eq', true);
    });
    stock(12).should('eq', 50);
    stock(13).should('eq', 47);
  });

  it('una caja sin cantina no se puede abrir', () => {
    asignarCajas(1, 'Cantina 1', [4]).its('ok').should('eq', true); // la caja 3 queda sin cantina
    cantinaDeCaja(3).should('be.null');

    cy.loginComo('vproductos');
    cy.visit('/vendedorproductos/caja/abrir');
    cy.contains('#rela_caja option', 'SIN CANTINA ASIGNADA').should('be.disabled');
    abrirCaja(3).then((r) => {
      expect(r.ok).to.eq(false);
      expect(r.mensaje).to.contain('no tiene una cantina activa asignada');
    });
    valor("SELECT COUNT(*) FROM arqueo_cajas WHERE estado_arqueo = 'abierto'").should('eq', 0);
  });

  it('no deja mover una caja abierta ni dar de baja una cantina con cajas', () => {
    cy.loginComo('vproductos');
    abrirCaja(3).its('ok').should('eq', true);

    cy.loginComo('admin');
    asignarCajas(2, 'cantina 2', [3]).its('errores').should('deep.eq', [
      'La Caja Principal Ventas Productos está abierta: cerrala antes de cambiarla de cantina',
    ]);
    cantinaDeCaja(3).should('eq', 1);

    // En Editar, la caja abierta se ve bloqueada y guardar la cantina no la desasigna
    cy.visit('/administrador/cantina/editar?id=1');
    cy.contains('label.caja-cantina', 'Caja Principal Ventas Productos')
      .should('contain', 'Abierta ahora')
      .find('input[type="checkbox"]').should('be.disabled');
    cy.get('input[type="submit"]').click();
    cy.get('.confirmacion .confirmar').click();
    cy.location('pathname', { timeout: 6000 }).should('eq', '/administrador/cantina/listado');
    cantinaDeCaja(3).should('eq', 1);

    // La cantina 1 tiene cajas: no se puede dar de baja ni desactivar
    cy.postJson('/administrador/cantina/eliminar', { id_cantina: 1 }).its('body').then(json).then((r) => {
      expect(r.ok).to.eq(false);
      expect(r.mensaje).to.contain('tiene cajas asignadas');
    });
    cy.postJson('/administrador/cantina/actualizar', { id_cantina: 1, nombre_cantina: 'Cantina 1', estado: 0 })
      .its('body').then(json).its('errores')
      .should('deep.eq', ['Una cantina inactiva no puede tener cajas: pasalas a otra cantina antes de desactivarla']);
    valor('SELECT estado FROM cantina WHERE id_cantina = 1').should('eq', 1);
  });
});
