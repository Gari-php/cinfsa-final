// QR de las entradas (etapa 1 del control de acceso): cada entrada tiene un código aleatorio
// propio y su QR aparece en el ticket de boletería y en "Mis compras" del cliente.
// Base de prueba: función 1 (sala 1). Usuarios: cliente = 4.

const FUNCION = 1;
const ID_CLIENTE = 4;

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

const butacasLibres = (cantidad) =>
  consultar(`SELECT b.id_butaca AS id FROM butacas b
             WHERE b.rela_salas = 1 AND b.rela_estado_butaca = 1
               AND b.id_butaca NOT IN (SELECT id_butaca FROM butacas_vendidas WHERE id_funcion = ${FUNCION})
             ORDER BY b.id_butaca LIMIT ${cantidad}`)
    .then((filas) => filas.map((f) => Number(f.id)));

describe('QR de las entradas', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('una venta de boletería genera un código por entrada y un QR por butaca en el ticket', () => {
    butacasLibres(2).then((butacas) => {
      cy.loginComo('vfunciones');
      cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
      cy.postJson('/vendedor/funciones/procesar-venta', {
        id_funcion: FUNCION, butacas, tipo_pago: 1, tipo_comprobante: 'TICKET',
      }).its('body').then(json).then((r) => {
        expect(r.ok, r.mensaje).to.eq(true);

        // Cada entrada nueva tiene su código: 32 caracteres hex, distintos entre sí
        consultar(`SELECT e.codigo_acceso FROM detalle_fact_cine df JOIN entradas e ON e.id_entrada = df.rela_entrada
                   WHERE df.rela_cabecera_fact = ${Number(r.id_venta)}`).then((filas) => {
          expect(filas).to.have.length(2);
          filas.forEach((f) => expect(f.codigo_acceso).to.match(/^[0-9a-f]{32}$/));
          expect(filas[0].codigo_acceso).not.to.eq(filas[1].codigo_acceso);
        });

        cy.visit(`/vendedor/funciones/ticket?id=${r.id_venta}`);
        cy.get('.entradas-qr').should('contain.text', 'MOSTRAR EN LA ENTRADA DE LA SALA');
        cy.get('.entrada-qr').should('have.length', 2).each(($e) => {
          cy.wrap($e).find('img').should('have.attr', 'src').and('match', /^data:image\/png;base64,/);
          cy.wrap($e).find('p').invoke('text').should('match', /^FILA \d{2} - COL \d{2}$/);
        });
      });
    });
  });

  it('una entrada web vieja (sin código) recibe uno y muestra su QR en Mis compras', () => {
    butacasLibres(1).then(([butaca]) => {
      cy.task('venderButacaWeb', { idButaca: butaca, idFuncion: FUNCION, idUsuario: ID_CLIENTE }).then((idEntrada) => {
        consultar(`SELECT codigo_acceso FROM entradas WHERE id_entrada = ${idEntrada}`)
          .its('0.codigo_acceso').should('be.null');

        cy.loginComo('cliente');
        cy.request('/perfil/mis-compras').its('body').then(json).then((r) => {
          const entrada = r.entradas.find((e) => Number(e.id_entrada) === idEntrada);
          expect(entrada.estado_vigencia).to.eq('vigente');
          expect(entrada.qr).to.match(/^data:image\/png;base64,/);
        });

        // Se le asignó el código, y no cambia al volver a mostrarla
        consultar(`SELECT codigo_acceso FROM entradas WHERE id_entrada = ${idEntrada}`).then(([{ codigo_acceso: codigo }]) => {
          expect(codigo).to.match(/^[0-9a-f]{32}$/);
          cy.request('/perfil/mis-compras');
          consultar(`SELECT codigo_acceso FROM entradas WHERE id_entrada = ${idEntrada}`)
            .its('0.codigo_acceso').should('eq', codigo);
        });

        // En pantalla: "Ver QR" despliega el código debajo de la tarjeta
        cy.visit('/perfil');
        cy.window().then((w) => w.mostrarMisCompras());
        cy.get(`#qr-entrada-${idEntrada}`).should('not.be.visible');
        cy.get(`[aria-controls="qr-entrada-${idEntrada}"]`).click();
        cy.get(`#qr-entrada-${idEntrada}`).should('be.visible').find('img')
          .should('have.attr', 'src').and('match', /^data:image\/png;base64,/);
        cy.get(`[aria-controls="qr-entrada-${idEntrada}"]`).should('contain.text', 'Ocultar QR').click();
        cy.get(`#qr-entrada-${idEntrada}`).should('not.be.visible');
      });
    });
  });

  it('una entrada cancelada no muestra QR', () => {
    butacasLibres(1).then(([butaca]) => {
      cy.task('venderButacaWeb', { idButaca: butaca, idFuncion: FUNCION, idUsuario: ID_CLIENTE }).then((idEntrada) => {
        cy.loginComo('admin');
        cy.postJson('/administrador/entradas/eliminar', { id_entrada: idEntrada });

        cy.loginComo('cliente');
        cy.request('/perfil/mis-compras').its('body').then(json).then((r) => {
          const entrada = r.entradas.find((e) => Number(e.id_entrada) === idEntrada);
          expect(entrada.estado_vigencia).to.eq('cancelada');
          expect(entrada.qr).to.eq(null);
        });
      });
    });
  });
});
