// Una butaca que está en el carrito puede venderse o reservarse para otro antes de que la pague.
// Verifica que el checkout lo avise, que el pago se rechace y que nadie pueda venderla dos veces.
// Base de prueba: función 1 (sala 1, $3000). Usuarios: cliente = 4, cliente2 = 5.

const FUNCION = 1;
const ID_CLIENTE2 = 5;

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

// Dos butacas libres de la sala 1
const dosButacas = () =>
  consultar('SELECT id_butaca FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1 ORDER BY id_butaca LIMIT 2')
    .then((filas) => filas.map((f) => Number(f.id_butaca)));

const agregarAlCarrito = (ids) =>
  cy.postJson('/api/butacas/reservar', { butacas: ids, id_funcion: FUNCION })
    .its('body').then((b) => expect(json(b).ok, json(b).mensaje).to.eq(true));

const venderEnBoleteria = (ids) => {
  cy.loginComo('vfunciones');
  cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
  return cy.postJson('/vendedor/funciones/procesar-venta', {
    id_funcion: FUNCION, butacas: ids, tipo_pago: 1, tipo_comprobante: 'TICKET',
  }).its('body').then(json);
};

describe('Butaca que deja de estar disponible', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('si otro la compra mientras está en mi carrito, al pagar me avisa que ya se vendió', () => {
    dosButacas().then(([butaca]) => {
      cy.loginComo('cliente');
      agregarAlCarrito([butaca]);

      // Otra persona la compra en boletería
      venderEnBoleteria([butaca]).its('ok').should('eq', true);

      // Vuelvo a mi carrito para pagar
      cy.loginComo('cliente');
      cy.visit('/carrito/checkout');
      cy.get('.aviso-no-disponibles').should('be.visible');
      cy.get('.checkout-item.no-disponible').should('have.length', 1)
        .and('contain.text', 'Esta butaca ya fue vendida');
      cy.get('.total-amount').should('contain.text', '$0');
      cy.get('.btn-pagar').should('be.disabled');

      // Aunque se fuerce el pago, el servidor lo rechaza y no crea la orden
      cy.postJson('/pago/crear-orden', { nombre: 'x', email: 'x@x.com', telefono: '1', dni: '1' })
        .its('body').then((b) => {
          const datos = json(b);
          expect(datos.ok).to.eq(false);
          expect(datos.mensaje).to.contain('ya fue vendida');
          expect(datos.no_disponibles).to.have.length(1);
          expect(datos.no_disponibles[0].motivo).to.eq('vendida');
        });
      consultar('SELECT COUNT(*) AS n FROM ordenes').its('0.n').should('eq', '0');

      // La quito del carrito: queda vacío y vuelve al inicio
      cy.get('.btn-quitar-item').click();
      cy.location('pathname').should('eq', '/menu');
      cy.request('/api/carrito/contar').its('body').then((b) => expect(json(b).total).to.eq(0));
    });
  });

  it('si otro cliente la está pagando, me avisa, la cobra aparte y no me deja pagarla', () => {
    dosButacas().then(([ocupada, libre]) => {
      cy.loginComo('cliente');
      agregarAlCarrito([ocupada, libre]);

      // cliente2 empezó a pagar `ocupada` (reserva de 15 minutos)
      cy.task('reservarButaca', { idButaca: ocupada, idFuncion: FUNCION, idUsuario: ID_CLIENTE2 });

      cy.visit('/carrito/checkout');
      cy.get('.checkout-item').should('have.length', 2);
      cy.get('.checkout-item.no-disponible').should('have.length', 1)
        .and('contain.text', 'Otro cliente está pagando esta butaca');
      cy.get('.total-amount').should('contain.text', '3.000'); // solo cuenta la libre
      cy.get('.btn-pagar').should('be.disabled');

      cy.postJson('/pago/crear-orden', {}).its('body').then((b) => {
        const datos = json(b);
        expect(datos.ok).to.eq(false);
        expect(datos.no_disponibles.map((p) => p.motivo)).to.deep.eq(['reservada']);
      });

      // En el mapa la veo ocupada y no la puedo volver a agregar
      cy.visit(`/butacas?id_funcion=${FUNCION}`);
      cy.get(`.butaca[data-id="${ocupada}"]`).should('have.attr', 'data-estado-num', '3').and('not.have.attr', 'onclick');
      cy.postJson('/api/butacas/reservar', { butacas: [ocupada], id_funcion: FUNCION })
        .its('body').then((b) => {
          expect(json(b).ok).to.eq(false);
          expect(json(b).mensaje).to.contain('otro cliente');
        });
    });
  });

  it('en boletería no se puede vender una butaca que un cliente está pagando online', () => {
    dosButacas().then(([butaca]) => {
      cy.task('reservarButaca', { idButaca: butaca, idFuncion: FUNCION, idUsuario: ID_CLIENTE2 });

      venderEnBoleteria([butaca]).then((datos) => {
        expect(datos.ok).to.eq(false);
        expect(datos.mensaje).to.contain('pagando un cliente online');
      });
      consultar(`SELECT COUNT(*) AS n FROM butacas_vendidas WHERE id_butaca = ${butaca}`).its('0.n').should('eq', '0');

      // El vendedor la ve ocupada en su mapa
      cy.request(`/api/vendedor/butacas/funcion?id_funcion=${FUNCION}`).its('body').then((b) => {
        const datos = json(b);
        const butacas = (datos.layout && datos.layout.butacas) || datos.butacas;
        expect(butacas.find((x) => x.id === butaca).estado).to.eq(3);
      });
    });
  });

  it('mi propia reserva de pago no me bloquea (por ejemplo, si reintento el pago)', () => {
    dosButacas().then(([butaca]) => {
      cy.loginComo('cliente');
      agregarAlCarrito([butaca]);
      cy.task('reservarButaca', { idButaca: butaca, idFuncion: FUNCION, idUsuario: 4 });

      cy.visit('/carrito/checkout');
      cy.get('.checkout-item.no-disponible').should('not.exist');
      cy.get('.aviso-no-disponibles').should('not.exist');
      cy.get('.btn-pagar').should('not.be.disabled');
      cy.get('.total-amount').should('contain.text', '3.000');
    });
  });
});
