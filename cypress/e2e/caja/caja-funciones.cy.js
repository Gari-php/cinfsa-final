// Ciclo completo de la caja de funciones: apertura, venta de entradas en boletería y cierre con arqueo.
// Base de prueba: función 2 (sala 2, entrada 2D a $3000). Ver tests/db/datos_prueba.sql.

const FUNCION_BOLETERIA = 2;
const PRECIO_ENTRADA = 3000;

const consultar = (sql) => cy.task('consultarBase', sql);

const abrirCaja = (montoInicial) => {
  cy.visit('/vendedor/caja/abrir');
  cy.get('#rela_caja').select('1');
  cy.get('#monto_inicial').clear().type(String(montoInicial));
  cy.get('#btn-abrir').click();
  cy.get('.alerta-modal.exito').should('be.visible');
  cy.location('pathname', { timeout: 10000 }).should('eq', '/vendedor/caja/estado');
};

const cerrarCaja = (montoDeclarado) => {
  // El cierre abre el PDF del arqueo en otra pestaña: se intercepta window.open
  cy.visit('/vendedor/caja/estado', {
    onBeforeLoad(win) {
      cy.stub(win, 'open').as('abrirPdf');
    },
  });
  cy.get('.btn-cerrar-caja').click();
  cy.get('#monto_final').should('be.visible').clear().type(String(montoDeclarado));
  cy.get('#btn-confirmar-cierre').click();
  cy.get('.boton-modal.confirmar').click();
  cy.get('.alerta-modal.exito').should('be.visible');
};

describe('Caja de funciones', () => {
  beforeEach(() => {
    // Cada test arranca con la base limpia (sin cajas abiertas ni ventas previas)
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('vfunciones');
  });

  it('abre caja, vende 2 entradas en efectivo y cierra con el arqueo cuadrado', () => {
    abrirCaja(10000);

    // ── Venta en boletería ─────────────────────────────────────
    cy.visit(`/vendedor/funciones/butacas?id_funcion=${FUNCION_BOLETERIA}`);
    // Se toman los ids primero: al seleccionar, la pantalla vuelve a dibujar el mapa
    cy.get('.butaca[onclick]').should('have.length.greaterThan', 2).then(($disponibles) => {
      const [id1, id2] = [$disponibles.eq(0).attr('data-id'), $disponibles.eq(1).attr('data-id')];
      cy.wrap(id1).as('butaca1');
      cy.wrap(id2).as('butaca2');
      cy.get(`.butaca[data-id="${id1}"]`).click();
      cy.get(`.butaca[data-id="${id2}"]`).click();
    });

    cy.get('#cantidad-total').should('have.text', '2');
    cy.get('#monto-total').should('contain.text', '6.000');

    cy.get('#tipo_pago').select('1'); // Efectivo
    cy.get('#tipo_comprobante option[value="TICKET"]').should('exist');
    cy.get('#tipo_comprobante').select('TICKET');
    cy.get('#btn-procesar-venta').should('not.be.disabled').click();
    cy.get('.boton-modal.confirmar').click();

    cy.get('.alerta-modal.exito').should('be.visible');
    cy.location('pathname', { timeout: 10000 }).should('eq', '/vendedor/funciones/ticket');

    // Las butacas quedaron vendidas y ya no se pueden elegir
    cy.get('@butaca1').then((id1) => {
      cy.get('@butaca2').then((id2) => {
        consultar(`SELECT id_butaca FROM butacas_vendidas WHERE id_funcion = ${FUNCION_BOLETERIA} ORDER BY id_butaca`)
          .then((filas) => expect(filas.map((f) => f.id_butaca)).to.deep.eq([id1, id2].sort((a, b) => a - b)));

        cy.visit(`/vendedor/funciones/butacas?id_funcion=${FUNCION_BOLETERIA}`);
        [id1, id2].forEach((id) => {
          cy.get(`.butaca[data-id="${id}"]`).should('have.attr', 'data-estado-num', '3').and('not.have.attr', 'onclick');
        });
      });
    });

    // Se registró el ingreso en la caja
    consultar(`SELECT monto, forma_pago FROM movimientos_caja WHERE concepto_movimiento LIKE 'Venta de 2 entrada%'`)
      .then((filas) => {
        expect(filas).to.have.length(1);
        expect(Number(filas[0].monto)).to.eq(2 * PRECIO_ENTRADA);
        expect(filas[0].forma_pago.toLowerCase()).to.eq('efectivo');
      });

    // ── Cierre: 10000 inicial + 6000 vendidos = 16000 esperados ──
    cerrarCaja(16000);
    cy.get('@abrirPdf').should('have.been.calledWithMatch', /\/vendedor\/caja\/pdf-arqueo\?id=\d+/);

    consultar(`SELECT estado_arqueo, monto_inicial, total_ventas, monto_final, diferencia FROM arqueo_cajas ORDER BY id_arqueo_caja DESC LIMIT 1`)
      .then(([arqueo]) => {
        expect(arqueo.estado_arqueo).to.eq('cerrado');
        expect(Number(arqueo.monto_inicial)).to.eq(10000);
        expect(Number(arqueo.total_ventas)).to.eq(6000);
        expect(Number(arqueo.monto_final)).to.eq(16000);
        expect(Number(arqueo.diferencia)).to.eq(0);
      });
  });

  it('registra el faltante cuando el efectivo contado es menor al esperado', () => {
    abrirCaja(5000);
    cerrarCaja(4500);

    consultar(`SELECT estado_arqueo, diferencia FROM arqueo_cajas ORDER BY id_arqueo_caja DESC LIMIT 1`)
      .then(([arqueo]) => {
        expect(arqueo.estado_arqueo).to.eq('cerrado');
        expect(Number(arqueo.diferencia)).to.eq(-500);
      });
  });

  it('no permite vender entradas sin caja abierta', () => {
    cy.postJson('/vendedor/funciones/procesar-venta', {
      id_funcion: FUNCION_BOLETERIA, butacas: [1], tipo_pago: 1, tipo_comprobante: 'TICKET',
    }).its('body').then((body) => {
      const datos = typeof body === 'string' ? JSON.parse(body) : body;
      expect(datos.ok).to.eq(false);
      expect(datos.mensaje).to.eq('No tienes caja abierta');
    });
  });
});
