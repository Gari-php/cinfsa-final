// Administrador → Gestionar Cantina: la baja es lógica (estado = 0), la cantina se reactiva desde
// Editar y nunca puede quedar el sistema sin ninguna cantina activa (la venta de productos la necesita).
// Base de prueba: cantinas 1 ("Cantina 1") y 2 ("cantina 2"), ambas activas. Ver tests/db/datos_prueba.sql.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);
const estadoDe = (id) =>
  consultar(`SELECT estado FROM cantina WHERE id_cantina = ${id}`).its(0).its('estado').then(Number);
const ultimo = (accion) =>
  consultar(`SELECT * FROM auditoria WHERE accion = '${accion}' ORDER BY id_auditoria DESC LIMIT 1`).its(0);

describe('Baja lógica de cantinas', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('admin');
  });

  it('dar de baja desde el listado la deja INACTIVA sin borrarla', () => {
    cy.visit('/administrador/cantina/listado');
    cy.contains('tr', 'cantina 2').within(() => {
      cy.contains('ACTIVA');
      cy.contains('button', 'Dar de baja').click();
    });
    cy.get('.confirmacion .confirmar').click();
    cy.contains('.alerta-modal.exito', 'Cantina dada de baja correctamente');

    cy.contains('tr', 'cantina 2', { timeout: 6000 }).within(() => {
      cy.contains('INACTIVA');
      cy.contains('button', 'Dar de baja').should('not.exist');
      cy.contains('a', 'Editar');
    });

    estadoDe(2).should('eq', 0);
    consultar('SELECT COUNT(*) AS total FROM cantina').its(0).its('total').then(Number).should('eq', 2);
    ultimo('cantina.baja').its('descripcion').should('contain', 'cantina 2');
  });

  it('no deja dar de baja ni desactivar la única cantina activa', () => {
    cy.postJson('/administrador/cantina/eliminar', { id_cantina: 2 }).its('body').then(json)
      .its('ok').should('eq', true);

    cy.postJson('/administrador/cantina/eliminar', { id_cantina: 1 }).its('body').then(json).then((r) => {
      expect(r.ok).to.eq(false);
      expect(r.mensaje).to.contain('única cantina activa');
    });
    cy.postJson('/administrador/cantina/actualizar', { id_cantina: 1, nombre_cantina: 'Cantina 1', estado: 0 })
      .its('body').then(json).its('errores').should('deep.eq', ['No podés desactivar la única cantina activa']);
    estadoDe(1).should('eq', 1);

    // En pantalla el rechazo se ve como error y la cantina sigue activa
    cy.visit('/administrador/cantina/listado');
    cy.contains('tr', 'Cantina 1').contains('button', 'Dar de baja').click();
    cy.get('.confirmacion .confirmar').click();
    cy.contains('.alerta-modal.error', 'única cantina activa');
  });

  it('se reactiva desde Editar y queda en la auditoría', () => {
    cy.postJson('/administrador/cantina/eliminar', { id_cantina: 2 });

    cy.visit('/administrador/cantina/editar?id=2');
    cy.get('#estado').should('have.value', '0').select('Activa');
    cy.get('input[type="submit"]').click();
    cy.get('.confirmacion .confirmar').click();
    cy.location('pathname', { timeout: 6000 }).should('eq', '/administrador/cantina/listado');
    cy.contains('tr', 'cantina 2').contains('ACTIVA');

    estadoDe(2).should('eq', 1);
    ultimo('cantina.modificar').then((r) => {
      expect(json(r.datos_antes)).to.deep.eq({ estado: 'Inactiva' });
      expect(json(r.datos_despues)).to.deep.eq({ estado: 'Activa' });
    });
  });

  it('una cantina inactiva no se ofrece para cargar stock nuevo', () => {
    cy.postJson('/administrador/cantina/eliminar', { id_cantina: 2 });
    cy.visit('/administrador/stock/crear');
    cy.get('#rela_cantina option').should('contain', 'Cantina 1').and('not.contain', 'cantina 2');

    // Pero el stock que ya tenía sigue mostrando su cantina al editarlo
    cy.visit('/administrador/stock/editar?id=12');
    cy.get('#rela_cantina option:selected').should('contain', 'cantina 2 (inactiva)');
  });
});
