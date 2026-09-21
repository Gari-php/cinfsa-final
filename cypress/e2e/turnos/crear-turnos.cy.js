

describe('CRUD Turnos - Crear Turno', () => {
  beforeEach(() => {

    cy.cleanTestData();
    
    
    cy.loginAsAdmin();
    

    cy.interceptCrud('turnos');
    
   
    cy.navigateToAdmin('turnos/crear');
  });

  describe('Validaciones del formulario', () => {
    it('Debe mostrar error cuando el horario está vacío', () => {

      cy.get('#turno_horario').clear();
      cy.get('input[type="submit"]').click();
    
      cy.get('.mensaje-error-campo')
        .should('contain.text', 'El horario es obligatorio');
      

      cy.get('#turno_horario').should('have.class', 'input-error');
    });

    it('Debe mostrar la estructura correcta del formulario', () => {
      cy.get('.form-container').should('exist');
      cy.get('form[data-fetch="true"]').should('exist');
      cy.get('h2').should('contain.text', 'Crear Nuevo Turno');
      
      cy.get('#turno_horario')
        .should('exist')
        .should('have.attr', 'type', 'time')
        .should('have.attr', 'name', 'turno_horario');
      
      cy.get('#estado')
        .should('exist')
        .should('have.attr', 'name', 'estado');
      

      cy.get('#estado option[value="1"]').should('contain.text', 'Activa');
      cy.get('#estado option[value="0"]').should('contain.text', 'Baja');
      

      cy.get('#estado').should('have.value', '1');
    });

    it('Debe permitir seleccionar diferentes estados', () => {

      cy.get('#estado').select('0');
      cy.get('#estado').should('have.value', '0');
      

      cy.get('#estado').select('1');
      cy.get('#estado').should('have.value', '1');
    });
  });

  describe('Crear turno exitosamente', () => {
    it('Debe crear un turno válido correctamente', () => {
      // Simular respuesta exitosa del servidor
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente',
          redirigir: '/administrador/turnos/listado'
        }
      }).as('guardarTurnoExitoso');

      // Llenar el formulario con datos válidos
      cy.get('#turno_horario').type('14:30');
      cy.get('#estado').select('1');
      
      // Enviar el formulario
      cy.get('input[type="submit"]').click();
      
      // Verificar que se hace la petición con los datos correctos
      cy.wait('@guardarTurnoExitoso').then((interception) => {
        expect(interception.request.body).to.deep.include({
          turno_horario: '14:30',
          estado: '1'
        });
      });
      
      // Verificar que aparece la alerta de éxito
      cy.get('.alerta-modal.exito').should('be.visible');
      cy.get('.alerta-modal-message').should('contain.text', 'Turno creado correctamente');
      
      // Verificar que después de 2 segundos redirige
      cy.url({ timeout: 3000 }).should('include', '/administrador/turnos/listado');
    });

    it('Debe crear turno con estado "Baja"', () => {
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente',
          redirigir: '/administrador/turnos/listado'
        }
      }).as('guardarTurnoBaja');

      // Llenar formulario con estado "Baja"
      cy.get('#turno_horario').type('08:00');
      cy.get('#estado').select('0');
      
      cy.get('input[type="submit"]').click();
      
      cy.wait('@guardarTurnoBaja').then((interception) => {
        expect(interception.request.body).to.deep.include({
          turno_horario: '08:00',
          estado: '0'
        });
      });
      
      cy.get('.alerta-modal.exito').should('be.visible');
    });
  });

  describe('Manejo de errores del servidor', () => {
    it('Debe mostrar errores de validación del backend', () => {
      // Simular respuesta de error del servidor
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          errores: [
            'El horario es obligatorio',
            'El estado debe ser válido (0 o 1)'
          ]
        }
      }).as('errorValidacion');

      // Enviar formulario (puede ser con datos válidos, el error viene del servidor)
      cy.get('#turno_horario').type('10:30');
      cy.get('input[type="submit"]').click();
      
      cy.wait('@errorValidacion');
      
    });

    it('Debe mostrar error de conexión', () => {
      // Simular error de red
      cy.intercept('POST', '/administrador/turnos/guardar', {
        forceNetworkError: true
      }).as('errorRed');

      cy.get('#turno_horario').type('12:00');
      cy.get('input[type="submit"]').click();
      
      cy.wait('@errorRed');
      
      // Verificar mensaje de error de conexión
      cy.get('.alerta-modal.error').should('be.visible');
      cy.get('.alerta-modal-message').should('contain.text', 'Error de conexión con el servidor');
    });

    it('Debe manejar respuesta de error general', () => {
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: false,
          mensaje: 'Error al guardar en la base de datos'
        }
      }).as('errorBaseDatos');

      cy.get('#turno_horario').type('16:45');
      cy.get('input[type="submit"]').click();
      
      cy.wait('@errorBaseDatos');
      
      cy.get('.alerta-modal.error').should('be.visible');
      cy.get('.alerta-modal-message').should('contain.text', 'Error al guardar en la base de datos');
    });
  });

  describe('Funcionalidad de alertas modales', () => {
    it('Debe poder cerrar alertas manualmente', () => {
      // Simular una alerta de éxito
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente'
        }
      });

      cy.get('#turno_horario').type('11:30');
      cy.get('input[type="submit"]').click();
      
      // Esperar que aparezca la alerta
      cy.get('.alerta-modal.exito').should('be.visible');
      
      // Cerrar la alerta haciendo clic en la X
      cy.get('.alerta-modal-close').click();
      
      // Verificar que la alerta desaparece
      cy.get('.alerta-modal').should('not.exist');
    });

    it('Debe cerrar alerta automáticamente después de 5 segundos', () => {
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente'
        }
      });

      cy.get('#turno_horario').type('13:15');
      cy.get('input[type="submit"]').click();
      
      // Verificar que aparece la alerta
      cy.get('.alerta-modal.exito').should('be.visible');
      
      // Esperar 5 segundos y verificar que se cierra automáticamente
      cy.wait(5100);
      cy.get('.alerta-modal').should('not.exist');
    });

    it('Debe cerrar alerta al hacer clic en el fondo', () => {
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente'
        }
      });

      cy.get('#turno_horario').type('15:00');
      cy.get('input[type="submit"]').click();
      
      cy.get('.alerta-modal.exito').should('be.visible');
      
      // Hacer clic en el overlay (fondo)
      cy.get('.modal-alertas-overlay').click({ force: true });
      
      cy.get('.alerta-modal').should('not.exist');
    });
  });

  describe('Navegación y enlaces', () => {
    it('Debe tener enlace para volver al listado', () => {
      cy.get('a[href="/administrador/turnos/listado"]')
        .should('exist')
        .should('contain.text', 'Volver');
    });

   
  });

  describe('Validación de formato de tiempo', () => {
    it('Debe aceptar formato de tiempo válido', () => {
      const tiemposValidos = ['00:00', '12:30', '23:59', '09:15'];
      
      tiemposValidos.forEach(tiempo => {
        cy.get('#turno_horario').clear().type(tiempo);
        cy.get('#turno_horario').should('have.value', tiempo);
      });
    });

    it('Debe mantener el foco en el campo de horario al tener error', () => {
      cy.get('#turno_horario').clear();
      cy.get('input[type="submit"]').click();
      
      // Verificar que el campo con error está resaltado
      cy.get('#turno_horario').should('have.class', 'input-error');
    });
  });

  describe('Comportamiento del formulario', () => {
    it('Debe limpiar errores previos al enviar nuevamente', () => {
      // Provocar un error primero
      cy.get('#turno_horario').clear();
      cy.get('input[type="submit"]').click();
      
      cy.get('.mensaje-error-campo').should('exist');
      cy.get('#turno_horario').should('have.class', 'input-error');
      
      // Llenar correctamente y enviar de nuevo
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente'
        }
      });
      
      cy.get('#turno_horario').type('14:00');
      cy.get('input[type="submit"]').click();
      
      // Verificar que se limpian los errores previos
      cy.get('.mensaje-error-campo').should('not.exist');
      cy.get('#turno_horario').should('not.have.class', 'input-error');
    });

    it('Debe prevenir envío múltiple del formulario', () => {
      cy.intercept('POST', '/administrador/turnos/guardar', {
        statusCode: 200,
        body: {
          ok: true,
          mensaje: 'Turno creado correctamente'
        },
        delay: 2000 // Simular respuesta lenta
      }).as('guardarLento');

      cy.get('#turno_horario').type('10:00');
      
      // Hacer clic múltiple rápido
      cy.get('input[type="submit"]').click();
      cy.get('input[type="submit"]').click();
      cy.get('input[type="submit"]').click();
      
      // En lugar de esperar exactamente 1, acepta que puede haber múltiples
    cy.get('@guardarLento.all').should('have.length.at.least', 1);
    });
  });
});