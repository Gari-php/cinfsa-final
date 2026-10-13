// Baja lógica de Sexo y Fichas (antes el botón "Eliminar" de esos listados no hacía nada).
// Quedan inactivos, se reactivan desde Editar, se registran en auditoría y dejan de ofrecerse
// (sexo al crear usuarios; ficha al asignar máquinas). Una ficha que usa una máquina activa
// no se puede dar de baja. Base de prueba: sexos 1-4, fichas 1 ($1000, la usa la máquina 1) y 3 ($10000).

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const numero = (sql) => cy.task('consultarBase', sql).its(0).then((fila) => Number(Object.values(fila)[0]));

describe('Baja lógica de sexo y fichas', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
    cy.loginComo('admin');
  });

  it('sexo: se da de baja desde el listado, no se ofrece al crear usuarios y se reactiva desde Editar', () => {
    cy.visit('/administrador/sexo/listado');
    cy.get('.eliminar-sexo[data-id="3"]').click();
    cy.get('.alerta-modal.confirmacion').should('contain.text', 'NO BINARIO');
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.exito').should('contain.text', 'Dado de baja');

    numero('SELECT estado FROM sexo WHERE id_sexo = 3').should('eq', 0);
    numero("SELECT COUNT(*) FROM auditoria WHERE accion = 'sexo.baja' AND id_entidad = 3").should('eq', 1);
    cy.get('.eliminar-sexo[data-id="3"]').should('not.exist');

    cy.visit('/administrador/usuarios/crear');
    cy.get('#rela_sexo option[value="3"]').should('not.exist');
    cy.get('#rela_sexo option[value="1"]').should('exist');

    cy.visit('/administrador/sexo/editar?id=3');
    cy.get('#estado').should('have.value', '0').select('1');
    cy.get('form[data-fetch="true"] [type="submit"]').click();
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.exito').should('be.visible');
    numero('SELECT estado FROM sexo WHERE id_sexo = 3').should('eq', 1);
  });

  it('sexo: no se puede dejar sin ninguno activo', () => {
    [2, 3].forEach((id) => cy.postJson('/administrador/sexo/eliminar', { id_sexo: id }).its('body').then(json).its('ok').should('eq', true));
    cy.postJson('/administrador/sexo/eliminar', { id_sexo: 4 }).its('body').then(json).its('ok').should('eq', true);
    cy.postJson('/administrador/sexo/eliminar', { id_sexo: 1 }).its('body').then(json).then((r) => {
      expect(r.ok).to.eq(false);
      expect(r.mensaje).to.contain('único sexo activo');
    });
    numero('SELECT estado FROM sexo WHERE id_sexo = 1').should('eq', 1);
  });

  it('fichas: la que usa una máquina activa no se da de baja; la libre sí y deja de ofrecerse a las máquinas', () => {
    cy.visit('/administrador/fichas/listado');
    cy.get('.eliminar-ficha[data-id="1"]').click();
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.error').should('contain.text', 'Maquina de Prueba');
    numero('SELECT estado FROM fichas WHERE id_fichas = 1').should('eq', 1);

    cy.visit('/administrador/fichas/listado');
    cy.get('.eliminar-ficha[data-id="3"]').click();
    cy.get('.alerta-modal.confirmacion').should('contain.text', '$10.000');
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.exito').should('contain.text', 'Ficha dada de baja');
    numero('SELECT estado FROM fichas WHERE id_fichas = 3').should('eq', 0);
    numero("SELECT COUNT(*) FROM auditoria WHERE accion = 'ficha.baja' AND id_entidad = 3").should('eq', 1);

    cy.visit('/administrador/maquinas/crear');
    cy.get('[name="rela_fichas"] option[value="3"]').should('not.exist');
    cy.get('[name="rela_fichas"] option[value="1"]').should('exist');

    // Desactivarla desde Editar tiene la misma regla que la baja
    cy.visit('/administrador/fichas/editar?id=1');
    cy.get('#estado').select('0');
    cy.get('form[data-fetch="true"] [type="submit"]').click();
    cy.get('.alerta-modal.confirmacion .boton-modal.confirmar').click();
    cy.get('.alerta-modal.error').should('contain.text', 'máquinas activas');
    numero('SELECT estado FROM fichas WHERE id_fichas = 1').should('eq', 1);
  });
});
