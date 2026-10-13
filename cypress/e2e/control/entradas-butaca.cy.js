// Gestionar entradas (admin): el listado, el aviso de cancelar y "Ver" muestran la fila y el
// asiento, para estar seguro de qué butaca se cancela.
// Base de prueba: función 1 (sala 1); cliente = usuario 4.

const consultar = (sql) => cy.task('consultarBase', sql);

describe('Gestionar entradas: fila y asiento', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('el listado y el aviso de cancelar dicen la butaca exacta, y "Ver" también', () => {
    consultar('SELECT id_butaca, fila_butaca, numero_butaca FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1 ORDER BY id_butaca LIMIT 1')
      .then(([butaca]) => {
        const ubicacion = `Sala 1 · Fila ${butaca.fila_butaca} · Asiento ${butaca.numero_butaca}`;

        cy.task('venderButacaWeb', { idButaca: Number(butaca.id_butaca), idFuncion: 1, idUsuario: 4 }).then((idEntrada) => {
          cy.loginComo('admin');
          cy.visit('/administrador/entradas/listado');

          // En el listado, la columna de la sala trae la butaca
          cy.get(`button.eliminar-entrada[data-id="${idEntrada}"]`).closest('tr').should('contain.text', ubicacion);

          // El aviso de cancelar nombra la entrada, la película y la butaca; "Cancelar" no hace nada
          cy.get(`button.eliminar-entrada[data-id="${idEntrada}"]`).click();
          cy.get('#modalConfirmacionMensaje').should('be.visible')
            .and('contain.text', `entrada #${idEntrada}`)
            .and('contain.text', ubicacion)
            .and('contain.text', 'NO devuelve el dinero');
          cy.get('#modalConfirmacionCancelar').click();
          consultar(`SELECT estado FROM entradas WHERE id_entrada = ${idEntrada}`).its('0.estado').should('eq', '1');

          // Confirmando, se cancela esa entrada
          cy.get(`button.eliminar-entrada[data-id="${idEntrada}"]`).click();
          cy.get('#modalConfirmacionAceptar').click();
          consultar(`SELECT estado FROM entradas WHERE id_entrada = ${idEntrada}`).its('0.estado').should('eq', '-1');

          // "Ver" muestra la butaca
          cy.visit(`/administrador/entradas/detalle?id=${idEntrada}`);
          cy.contains('.dato', 'Butaca').should('contain.text', `Fila ${butaca.fila_butaca} · Asiento ${butaca.numero_butaca}`);
        });
      });
  });
});
