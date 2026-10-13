// Un cliente no puede ponerse como nombre de usuario el email de otra cuenta (ni nada con "@"):
// el perfil aplica las mismas reglas que el registro, y el login busca por email solo si lo
// escrito tiene "@" y por nombre de usuario si no.
// Base de prueba: cliente = usuario 4 (cliente_test), cliente2 = usuario 5 (cliente2@cinfsa.test).

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);

const actualizarPerfil = (campos) =>
  cy.csrfToken().then((token) =>
    cy.request({
      method: 'POST',
      url: '/perfil/actualizar',
      form: true,
      headers: { 'X-CSRF-Token': token },
      failOnStatusCode: false,
      body: {
        id_usuario: 4, nombre_usuario: 'cliente_test', nombre_persona: 'Cliente', apellido_persona: 'Uno',
        email: 'cliente@cinfsa.test', sexo: 1, ...campos,
      },
    }).its('body').then(json));

describe('Nombre de usuario y login', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('el perfil no acepta como nombre de usuario el email de otra cuenta', () => {
    cy.loginComo('cliente');
    actualizarPerfil({ nombre_usuario: 'cliente2@cinfsa.test' }).then((r) => {
      expect(r.success).to.not.eq(true);
      expect(r.message).to.contain('solo puede contener letras, números');
    });
    consultar('SELECT nombre_usuario FROM usuarios WHERE id_usuario = 4').its('0.nombre_usuario').should('eq', 'cliente_test');

    // Las demás reglas del registro también valen al cambiarlo
    actualizarPerfil({ nombre_usuario: '9cliente' }).its('message').should('contain', 'no puede empezar con un número');
    actualizarPerfil({ nombre_usuario: 'un_nombre_demasiado_largo' }).its('message').should('contain', 'más de 20 caracteres');
  });

  it('se puede cambiar a un nombre válido, y editar el perfil sin cambiar el nombre sigue funcionando', () => {
    cy.loginComo('cliente');
    actualizarPerfil({ nombre_persona: 'Clienta' }).its('success').should('eq', true);
    actualizarPerfil({ nombre_usuario: 'cliente.nuevo' }).its('success').should('eq', true);
    consultar('SELECT nombre_usuario FROM usuarios WHERE id_usuario = 4').its('0.nombre_usuario').should('eq', 'cliente.nuevo');
  });

  it('el login funciona con el email y con el nombre de usuario', () => {
    cy.clearCookies();
    cy.postJson('/', { nombre_usuario: 'cliente2@cinfsa.test', clave_usuario: 'Prueba123!' }).its('body.ok').should('eq', true);
    cy.clearCookies();
    cy.postJson('/', { nombre_usuario: 'cliente2_test', clave_usuario: 'Prueba123!' }).its('body.ok').should('eq', true);
    cy.clearCookies();
    cy.postJson('/', { nombre_usuario: 'cliente2_test', clave_usuario: 'otra' }).its('body.error')
      .should('eq', 'Usuario o contraseña incorrectos');
  });
});
