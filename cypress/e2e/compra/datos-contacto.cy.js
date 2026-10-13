// Teléfono y DNI del checkout: se validan, se guardan con la orden y el administrador los ve en
// el detalle de la compra web. La próxima compra los trae ya completados.
// El servidor de pruebas no usa Mercado Pago: la orden queda guardada y la preferencia falla.
// Base de prueba: cliente = usuario 4; función 1 (sala 1).

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);

const agregarButaca = () =>
  consultar('SELECT id_butaca FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1 ORDER BY id_butaca LIMIT 1')
    .then(([{ id_butaca }]) => cy.postJson('/api/butacas/reservar', { butacas: [Number(id_butaca)], id_funcion: 1 }));

const pagar = (telefono, dni) =>
  cy.postJson('/pago/crear-orden', { nombre: 'Cliente', email: 'cliente@cinfsa.test', telefono, dni }).its('body').then(json);

describe('Datos de contacto del checkout', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('cliente');
    agregarButaca();
  });

  it('rechaza un teléfono o un DNI inválidos sin crear la orden', () => {
    pagar('hola', '30123456').its('mensaje').should('contain', 'teléfono válido');
    pagar('11 3456-7890', '30A12345').its('mensaje').should('contain', 'DNI válido');
    pagar('11 3456-7890', '123').its('mensaje').should('contain', 'DNI válido');
    pagar('11 3456-7890', '').its('mensaje').should('contain', 'DNI válido');
    consultar('SELECT COUNT(*) AS n FROM ordenes').its('0.n').should('eq', '0');
  });

  it('guarda teléfono y DNI con la orden (el DNI con puntos se acepta) y el admin los ve en el detalle', () => {
    pagar('+54 9 11 3456-7890', '30.123.456');
    consultar('SELECT id_orden, telefono_contacto, dni_contacto FROM ordenes WHERE id_usuario = 4').then(([orden]) => {
      expect(orden.telefono_contacto).to.eq('+54 9 11 3456-7890');
      expect(orden.dni_contacto).to.eq('30123456');

      cy.loginComo('admin');
      cy.visit(`/administrador/movimientos-web/detalle?id=${orden.id_orden}`);
      cy.contains('.info-row', 'Teléfono:').should('contain.text', '+54 9 11 3456-7890');
      cy.contains('.info-row', 'DNI:').should('contain.text', '30123456');
    });
  });

  it('la próxima compra trae el teléfono y el DNI ya completados', () => {
    pagar('11 3456-7890', '30123456');
    cy.visit('/carrito/checkout');
    cy.get('input[name="telefono"]').should('have.value', '11 3456-7890');
    cy.get('input[name="dni"]').should('have.value', '30123456');
  });

  it('una orden sin datos de contacto (anterior al cambio) muestra "No especificado"', () => {
    cy.task('crearPedidoWeb', { idUsuario: 4 }).then(({ id_orden }) => {
      cy.loginComo('admin');
      cy.visit(`/administrador/movimientos-web/detalle?id=${id_orden}`);
      cy.contains('.info-row', 'Teléfono:').should('contain.text', 'No especificado');
      cy.contains('.info-row', 'DNI:').should('contain.text', 'No especificado');
    });
  });
});
