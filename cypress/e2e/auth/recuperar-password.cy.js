// "¿Olvidaste tu contraseña?": como mucho un mail cada 5 minutos por cuenta, un pedido nuevo no
// anula el link que ya llegó, y se aceptan los mismos proveedores que al registrarse.
// El servidor de pruebas no manda mails: los guarda y la tarea mailsEnviados los devuelve.
// Base de prueba: vfunciones = usuario 2 (persona 2); el admin le pone un email de un proveedor aceptado.

const ID_USUARIO = 2;
const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);
const tokenActual = () => consultar(`SELECT token_recuperacion AS t FROM usuarios WHERE id_usuario = ${ID_USUARIO}`).its('0.t');
const mailsDeRecuperacion = (email) =>
  cy.task('mailsEnviados').then((mails) => mails.filter((m) => m.para === email && m.asunto.includes('Restablecer')));

const ponerEmail = (email) => {
  cy.loginComo('admin');
  cy.postJson('/administrador/usuarios/actualizar', {
    id_usuario: ID_USUARIO, id_persona: 2,
    nombre_persona: 'Vendedor', apellido_persona: 'Funciones', fecha_nacimiento: '1990-01-01', rela_sexo: 4,
    nombre_usuario: 'vfunciones_test', email, rela_perfil: 4, clave_usuario: '',
  }).its('body').then((b) => expect(json(b).ok, JSON.stringify(json(b))).to.eq(true));
  cy.clearCookies();
};

const pedirRecuperacion = (email) => cy.postJson('/olvide', { email }).its('body').then(json);

describe('Recuperar contraseña', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('dos pedidos seguidos mandan un solo mail y no cambian el link', () => {
    const email = 'vfunciones.prueba@gmail.com';
    ponerEmail(email);

    pedirRecuperacion(email).then((r) => {
      expect(r.ok).to.eq(true);
      expect(r.mensaje).to.contain('Si el correo está registrado');
    });
    tokenActual().then((primerToken) => {
      expect(primerToken).to.have.length(40);

      // Al instante: misma respuesta, pero no se manda otro mail ni cambia el token
      pedirRecuperacion(email).its('ok').should('eq', true);
      mailsDeRecuperacion(email).should('have.length', 1);
      tokenActual().should('eq', primerToken);
    });
  });

  it('pasados 5 minutos reenvía el mismo link si sigue vigente; vencido, manda uno nuevo', () => {
    const email = 'vfunciones.prueba@gmail.com';
    ponerEmail(email);
    pedirRecuperacion(email);

    tokenActual().then((primerToken) => {
      // Pasaron 6 minutos: se reenvía, con el mismo token (el link que ya llegó sigue sirviendo)
      cy.task('envejecerRecuperacion', { idUsuario: ID_USUARIO, minutos: 6 });
      pedirRecuperacion(email);
      mailsDeRecuperacion(email).should('have.length', 2).then((mails) => {
        expect(mails[1].html).to.contain(primerToken);
      });
      tokenActual().should('eq', primerToken);
      // El plazo se renovó: vuelve a vencer dentro de 30 minutos
      consultar(`SELECT TIMESTAMPDIFF(MINUTE, NOW(), token_recuperacion_expira) AS m FROM usuarios WHERE id_usuario = ${ID_USUARIO}`)
        .its('0.m').then(Number).should('be.within', 28, 30);

      // Pasó más de media hora: el token venció y se genera uno nuevo
      cy.task('envejecerRecuperacion', { idUsuario: ID_USUARIO, minutos: 31 });
      pedirRecuperacion(email);
      mailsDeRecuperacion(email).should('have.length', 3);
      tokenActual().should('not.eq', primerToken);
    });
  });

  it('acepta los mismos proveedores que el registro (por ejemplo Hotmail) y rechaza otros', () => {
    ponerEmail('vfunciones.prueba@hotmail.com');
    pedirRecuperacion('vfunciones.prueba@hotmail.com').its('ok').should('eq', true);
    mailsDeRecuperacion('vfunciones.prueba@hotmail.com').should('have.length', 1);

    pedirRecuperacion('alguien@empresa.com').then((r) => {
      expect(r.ok).to.not.eq(true);
      expect(r.error).to.contain('Solo se permiten correos de');
    });
  });

  it('un email que no está registrado recibe la misma respuesta y no se manda nada', () => {
    pedirRecuperacion('nadie.registrado@gmail.com').then((r) => {
      expect(r.ok).to.eq(true);
      expect(r.mensaje).to.contain('Si el correo está registrado');
    });
    cy.task('mailsEnviados').should('have.length', 0);
  });

  it('el link del mail sirve para poner una contraseña nueva', () => {
    const email = 'vfunciones.prueba@gmail.com';
    ponerEmail(email);
    pedirRecuperacion(email);
    tokenActual().then((token) => {
      cy.postJson('/restablecer', { token, password: 'NuevaClave123', confirm_password: 'NuevaClave123' })
        .its('body').then(json).its('ok').should('eq', true);
      cy.clearCookies();
      cy.postJson('/', { nombre_usuario: 'vfunciones_test', clave_usuario: 'NuevaClave123' }).its('body.ok').should('eq', true);
    });
  });
});
