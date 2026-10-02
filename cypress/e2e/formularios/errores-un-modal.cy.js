// Los errores de validación de los formularios del administrador (public/assets/js/formularios.js)
// se muestran debajo de cada campo y en UN SOLO modal. Antes, 9 pantallas mostraban el mismo
// error en 2 o 3 modales, y el error de contraseña de editar usuario no se marcaba en su campo.

const MODULOS = [
  'usuarios', 'sexo', 'peliculas', 'salas', 'funciones', 'turnos', 'tipos_entradas', 'productos',
  'stock', 'fichas', 'maquinas', 'generos', 'cantina', 'estados_peliculas', 'gastos', 'perfiles',
  'proveedores', 'servicios',
];

// Modales de error abiertos (sin contar el de confirmación "¿Guardar cambios?")
const modalesDeError = ($body) =>
  [...$body.find('.alerta-modal.error')].filter((m) => !m.querySelector('.confirmacion-content'));

// Envía el formulario de la pantalla saltando la validación nativa del navegador,
// para que los datos inválidos lleguen a formularios.js y al servidor
const enviarFormulario = () => {
  cy.get('form[data-fetch="true"]').first().invoke('attr', 'novalidate', 'novalidate')
    .find('[type="submit"]').first().click({ force: true });

  // Editar pide confirmación antes de enviar: esperar a que aparezca la confirmación o un error
  cy.get('.boton-modal.confirmar, .alerta-modal.error, .mensaje-error-campo', { timeout: 10000 })
    .should('exist')
    .then(($el) => {
      const confirmar = $el.filter('.boton-modal.confirmar');
      if (confirmar.length) cy.wrap(confirmar.first()).click();
    });

  // Esperar el primer error y dar margen a que apareciera un posible duplicado
  cy.get('.alerta-modal.error, .mensaje-error-campo', { timeout: 10000 }).should('exist');
  cy.wait(800);
};

const sinMasDeUnModal = () =>
  cy.get('body').then(($b) => {
    const modales = modalesDeError($b);
    expect(modales.length, modales.map((m) => m.innerText.trim()).join(' | ')).to.be.at.most(1);
  });

// Algunas pantallas (crear función, crear gasto) usan su propio asistente en vez de formularios.js
const conFormularioEstandar = (alternativa) =>
  cy.get('body').then(($b) => {
    if ($b.find('form[data-fetch="true"]').length) alternativa();
    else cy.log('Pantalla sin formulario data-fetch: no usa formularios.js');
  });

describe('Errores de formularios: un solo modal', () => {
  beforeEach(() => {
    cy.loginComo('admin');
  });

  it('editar usuario: el error de contraseña se marca en su campo y aparece una sola vez', () => {
    cy.visit('/administrador/usuarios/editar?id=1');
    // Email válido para que el único error sea el de la contraseña (no se guarda: la contraseña es inválida)
    cy.get('#email').clear().type('usuario.prueba@gmail.com');
    cy.get('#clave_usuario').type('soloLetras');
    enviarFormulario();

    cy.get('#clave_usuario').should('have.class', 'input-error');
    cy.get('#clave_usuario').closest('.campo').find('.mensaje-error-campo')
      .should('contain.text', 'La contraseña debe contener al menos un número');
    cy.get('body').then(($b) => {
      const modales = modalesDeError($b);
      expect(modales).to.have.length(1);
      expect(modales[0].innerText).to.contain('La contraseña debe contener al menos un número');
    });
  });

  MODULOS.forEach((modulo) => {
    it(`crear ${modulo}: formulario vacío`, () => {
      cy.visit(`/administrador/${modulo}/crear`);
      conFormularioEstandar(() => {
        enviarFormulario();
        sinMasDeUnModal();
      });
    });

    it(`editar ${modulo}: campos de texto vacíos`, () => {
      cy.visit(`/administrador/${modulo}/editar?id=1`);
      conFormularioEstandar(() => {
        cy.get('form[data-fetch="true"]').first()
          .find('input[type="text"], input[type="email"], input[type="number"], input[type="time"], input[type="date"], textarea')
          .not('[readonly]').not('[disabled]')
          .each(($campo) => cy.wrap($campo).invoke('val', ''));
        enviarFormulario();
        sinMasDeUnModal();
      });
    });
  });
});
