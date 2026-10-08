// La sesión se controla contra la base en cada pedido: una cuenta dada de baja o con la
// contraseña cambiada queda afuera, un cambio de perfil rige enseguida y tras 30 minutos
// sin actividad hay que volver a iniciar sesión.
// Base de prueba: vfunciones = usuario 2 (persona 2, perfil 4), cliente = usuario 4.

const ID_VENDEDOR = 2;
const CLAVE = 'Prueba123!';

const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

// Código de estado de una página sin seguir la redirección (200 = entra, 302 = lo saca)
const estadoDe = (url) => cy.request({ url, followRedirect: false, failOnStatusCode: false }).its('status');

const sesionActual = () => cy.getCookie('PHPSESSID').its('value');
const volverASesion = (id) => {
  cy.clearCookies();
  cy.setCookie('PHPSESSID', id);
};

// El admin edita al vendedor de prueba con el perfil (y la contraseña) que se indique
const editarVendedor = ({ perfil, clave = '' }) => {
  cy.loginComo('admin');
  cy.postJson('/administrador/usuarios/actualizar', {
    id_usuario: ID_VENDEDOR, id_persona: 2,
    nombre_persona: 'Vendedor', apellido_persona: 'Funciones', fecha_nacimiento: '1990-01-01', rela_sexo: 4,
    nombre_usuario: 'vfunciones_test', email: 'vfunciones.test@gmail.com', rela_perfil: perfil, clave_usuario: clave,
  }).its('body').then((b) => expect(json(b).ok, JSON.stringify(json(b))).to.eq(true));
};

describe('Sesión revalidada en cada pedido', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('un usuario dado de baja no puede iniciar sesión y su sesión abierta se cierra', () => {
    cy.loginComo('vfunciones');
    estadoDe('/vendedor/caja/abrir').should('eq', 200);
    sesionActual().then((sesionVendedor) => {
      cy.loginComo('admin');
      cy.postJson('/administrador/usuarios/eliminar', { id: ID_VENDEDOR }).its('body.ok').should('eq', true);

      // La sesión que tenía abierta ya no sirve
      volverASesion(sesionVendedor);
      estadoDe('/vendedor/caja/abrir').should('eq', 302);
      cy.visit('/');
      cy.get('.aviso-sesion').should('contain.text', 'Tu cuenta está desactivada');

      // Y no puede volver a entrar
      cy.clearCookies();
      cy.postJson('/', { nombre_usuario: 'vfunciones_test', clave_usuario: CLAVE }).its('body').then((b) => {
        expect(json(b).ok).to.not.eq(true);
        expect(json(b).error).to.contain('desactivada');
      });
    });
  });

  it('a un admin degradado le rige el perfil nuevo en el siguiente pedido', () => {
    editarVendedor({ perfil: 3 });
    cy.loginComo('vfunciones');
    estadoDe('/administrador/usuarios/listado').should('eq', 200);

    sesionActual().then((sesionVendedor) => {
      editarVendedor({ perfil: 4 });

      volverASesion(sesionVendedor);
      estadoDe('/administrador/usuarios/listado').should('eq', 302);
      // Sigue logueado con su perfil nuevo
      estadoDe('/vendedor/caja/abrir').should('eq', 200);
    });
  });

  it('si el admin le cambia la contraseña, la sesión abierta del usuario se cierra', () => {
    cy.loginComo('vfunciones');
    sesionActual().then((sesionVendedor) => {
      editarVendedor({ perfil: 4, clave: 'OtraClave123!' });

      volverASesion(sesionVendedor);
      estadoDe('/vendedor/caja/abrir').should('eq', 302);
      cy.visit('/');
      cy.get('.aviso-sesion').should('contain.text', 'Tu contraseña cambió');
    });
  });

  it('el cliente que cambia su propia contraseña sigue logueado', () => {
    cy.loginComo('cliente');
    cy.postJson('/perfil/cambiar-password', {
      password_actual: CLAVE, password_nueva: 'OtraClave123!', password_confirmar: 'OtraClave123!',
    }).its('body').then((b) => expect(json(b).ok, JSON.stringify(json(b))).to.eq(true));

    estadoDe('/perfil').should('eq', 200);
  });

  it('cada clic renueva los 30 minutos; pasados 30 sin actividad hay que volver a iniciar sesión', () => {
    const atrasar = (minutos) => sesionActual().then((idSesion) => cy.task('envejecerSesion', { idSesion, minutos }));
    cy.loginComo('cliente');

    // A los 29 minutos todavía sirve, y ese clic renueva el plazo
    atrasar(29);
    estadoDe('/perfil').should('eq', 200);
    atrasar(2);
    estadoDe('/perfil').should('eq', 200);

    // 31 minutos sin actividad: se cierra. Un pedido de datos recibe JSON; la página lleva al login con el aviso
    atrasar(31);
    cy.request({ url: '/api/carrito/contar', failOnStatusCode: false }).then((r) => {
      expect(r.status).to.eq(401);
      expect(json(r.body).sesion_cerrada).to.eq(true);
    });
    estadoDe('/perfil').should('eq', 302);
    cy.visit('/');
    cy.get('.aviso-sesion').should('contain.text', 'venció por inactividad');
  });

  it('las consultas automáticas (contador del carrito) no renuevan el plazo', () => {
    const atrasar = (minutos) => sesionActual().then((idSesion) => cy.task('envejecerSesion', { idSesion, minutos }));
    cy.loginComo('cliente');

    atrasar(29);
    cy.request('/api/carrito/contar').its('status').should('eq', 200);
    // Si la consulta automática hubiera renovado, 2 minutos más no alcanzarían para vencer
    atrasar(2);
    estadoDe('/perfil').should('eq', 302);
  });
});
