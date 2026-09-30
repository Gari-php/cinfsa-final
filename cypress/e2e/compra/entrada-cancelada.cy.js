// Cancelar una entrada desde el administrador libera la butaca SOLO en esa función,
// y restaurarla solo es posible si nadie la compró ni la está pagando.
// Base de prueba: funciones 1 y 2 (sala 1 y sala 2). Usuarios: cliente2 = 5.

const FUNCION = 1;
const ID_CLIENTE2 = 5;

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

const butacaLibre = () =>
  consultar('SELECT MIN(id_butaca) AS id FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1')
    .then(([{ id }]) => Number(id));

// Vende la butaca en boletería y devuelve el id de la entrada creada
const venderEnBoleteria = (butaca) => {
  cy.loginComo('vfunciones');
  // Abre la caja solo si todavía no está abierta
  cy.request('/api/caja/verificar').its('body').then((b) => {
    if (!json(b).tiene_caja_abierta) cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
  });
  return cy.postJson('/vendedor/funciones/procesar-venta', {
    id_funcion: FUNCION, butacas: [butaca], tipo_pago: 1, tipo_comprobante: 'TICKET',
  }).its('body').then(json);
};

const entradaActivaDe = (butaca) =>
  consultar(`SELECT bv.id_entrada FROM butacas_vendidas bv JOIN entradas e ON e.id_entrada = bv.id_entrada
             WHERE bv.id_butaca = ${butaca} AND bv.id_funcion = ${FUNCION} AND e.estado = 1`)
    .then((filas) => (filas[0] ? Number(filas[0].id_entrada) : null));

const accionAdmin = (url, idEntrada) => {
  cy.loginComo('admin');
  return cy.postJson(url, { id_entrada: idEntrada }).its('body').then(json);
};

const estadoEnMapaCliente = (butaca) => {
  cy.loginComo('cliente');
  return cy.request(`/api/butacas/funcion?id_funcion=${FUNCION}`).its('body')
    .then((b) => json(b).layout.butacas.find((x) => x.id === butaca).estado);
};

const estadoEnMapaBoleteria = (butaca) => {
  cy.loginComo('vfunciones');
  return cy.request(`/api/vendedor/butacas/funcion?id_funcion=${FUNCION}`).its('body').then((b) => {
    const d = json(b);
    return ((d.layout && d.layout.butacas) || d.butacas).find((x) => x.id === butaca).estado;
  });
};

describe('Entrada cancelada por el administrador', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('libera la butaca en esa función y se puede volver a vender', () => {
    butacaLibre().then((butaca) => {
      venderEnBoleteria(butaca).its('ok').should('eq', true);
      entradaActivaDe(butaca).then((idEntrada) => {
        accionAdmin('/administrador/entradas/eliminar', idEntrada).its('ok').should('eq', true);
      });

      // Libre para el cliente y para boletería
      estadoEnMapaCliente(butaca).should('eq', 1);
      estadoEnMapaBoleteria(butaca).should('eq', 1);

      // Se puede volver a vender en boletería
      venderEnBoleteria(butaca).then((r) => expect(r.ok, r.mensaje).to.eq(true));
      consultar(`SELECT COUNT(*) AS n FROM butacas_vendidas WHERE id_butaca = ${butaca} AND id_funcion = ${FUNCION}`)
        .its('0.n').should('eq', '1');
      estadoEnMapaBoleteria(butaca).should('eq', 3);
    });
  });

  it('un cliente puede comprarla online: aparece disponible en su checkout', () => {
    butacaLibre().then((butaca) => {
      venderEnBoleteria(butaca);
      entradaActivaDe(butaca).then((idEntrada) => accionAdmin('/administrador/entradas/eliminar', idEntrada));

      cy.loginComo('cliente');
      cy.postJson('/api/butacas/reservar', { butacas: [butaca], id_funcion: FUNCION })
        .its('body').then((b) => expect(json(b).ok).to.eq(true));
      cy.visit('/carrito/checkout');
      cy.get('.checkout-item.no-disponible').should('not.exist');
      cy.get('.btn-pagar').should('not.be.disabled');
    });
  });

  it('no desbloquea una butaca que el administrador bloqueó por mantenimiento', () => {
    butacaLibre().then((butaca) => {
      venderEnBoleteria(butaca);
      cy.loginComo('admin');
      cy.postJson('/administrador/butacas/cambiar-estado', { id_butaca: butaca, estado: 2 })
        .its('body').then((b) => expect(json(b).ok).to.eq(true));

      entradaActivaDe(butaca).then((idEntrada) => accionAdmin('/administrador/entradas/eliminar', idEntrada));
      consultar(`SELECT rela_estado_butaca FROM butacas WHERE id_butaca = ${butaca}`)
        .its('0.rela_estado_butaca').should('eq', '2');
    });
  });

  it('se puede restaurar si nadie la compró, y vuelve a quedar ocupada', () => {
    butacaLibre().then((butaca) => {
      venderEnBoleteria(butaca);
      entradaActivaDe(butaca).then((idEntrada) => {
        accionAdmin('/administrador/entradas/eliminar', idEntrada);
        accionAdmin('/administrador/entradas/restaurar', idEntrada).then((r) => expect(r.ok, r.mensaje).to.eq(true));
      });
      estadoEnMapaCliente(butaca).should('eq', 3);
      estadoEnMapaBoleteria(butaca).should('eq', 3);
      consultar(`SELECT rela_estado_butaca FROM butacas WHERE id_butaca = ${butaca}`)
        .its('0.rela_estado_butaca').should('eq', '1'); // no se bloquea en las demás funciones
    });
  });

  it('no se puede restaurar si la butaca se vendió a otra persona', () => {
    butacaLibre().then((butaca) => {
      venderEnBoleteria(butaca);
      entradaActivaDe(butaca).then((idCancelada) => {
        accionAdmin('/administrador/entradas/eliminar', idCancelada);
        venderEnBoleteria(butaca).its('ok').should('eq', true);
        accionAdmin('/administrador/entradas/restaurar', idCancelada).then((r) => {
          expect(r.ok).to.eq(false);
          expect(r.mensaje).to.contain('se vendió a otra persona');
        });
      });
    });
  });

  it('no se puede restaurar mientras un cliente la está pagando', () => {
    butacaLibre().then((butaca) => {
      venderEnBoleteria(butaca);
      entradaActivaDe(butaca).then((idEntrada) => {
        accionAdmin('/administrador/entradas/eliminar', idEntrada);
        cy.task('reservarButaca', { idButaca: butaca, idFuncion: FUNCION, idUsuario: ID_CLIENTE2 });
        accionAdmin('/administrador/entradas/restaurar', idEntrada).then((r) => {
          expect(r.ok).to.eq(false);
          expect(r.mensaje).to.contain('está pagando');
        });
      });
    });
  });
});
