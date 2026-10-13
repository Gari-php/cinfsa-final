// Soporte y reclamos: después de enviar un reclamo el formulario queda en blanco
// (tipo de reclamo, número de orden, mensaje y comprobante), listo para cargar otro.
// El servidor de pruebas no manda mails: el reclamo queda en mailsEnviados.

describe('Soporte y reclamos', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('cliente');
    cy.visit('/soporte');
  });

  it('al enviar el reclamo el formulario se limpia', () => {
    cy.get('#asunto').select('Error en la orden');
    cy.get('#numero_orden').type('CINFSA-20261013-ABCD');
    cy.get('#mensaje').type('Me cobraron dos veces el mismo pochoclo, adjunto el ticket.');
    cy.get('#comprobante').selectFile(
      { contents: Cypress.Buffer.from('%PDF-1.4 comprobante de prueba'), fileName: 'ticket.pdf', mimeType: 'application/pdf' },
      { force: true },
    );
    cy.get('#file-name').should('contain.text', 'ticket.pdf');

    cy.contains('button', 'Enviar Reclamo').click();
    cy.get('.alerta-modal.exito').should('be.visible').and('contain.text', 'Reclamo');

    // Todo vuelve a como estaba al entrar
    cy.get('#asunto').should('have.value', '');
    cy.get('#numero_orden').should('have.value', '');
    cy.get('#mensaje').should('have.value', '');
    cy.get('#comprobante').should(($input) => expect($input[0].files.length).to.eq(0));
    cy.get('#file-name').should('have.text', 'Seleccionar archivo');

    // El reclamo se mandó a soporte con el comprobante
    cy.task('mailsEnviados').then((mails) => {
      const reclamo = mails.find((m) => m.asunto.includes('Nuevo Reclamo'));
      expect(reclamo, 'mail del reclamo').to.exist;
      expect(reclamo.adjuntos).to.eq(1);
    });
  });

  it('si falta un dato, no se envía y lo cargado se mantiene', () => {
    cy.get('#numero_orden').type('CINFSA-20261013-ABCD');
    cy.get('#mensaje').type('Mensaje sin elegir el tipo de reclamo.');
    cy.contains('button', 'Enviar Reclamo').click();

    cy.get('#numero_orden').should('have.value', 'CINFSA-20261013-ABCD');
    cy.get('#mensaje').should('have.value', 'Mensaje sin elegir el tipo de reclamo.');
    cy.task('mailsEnviados').should('have.length', 0);
  });
});
