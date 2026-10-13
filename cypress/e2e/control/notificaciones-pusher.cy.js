// Notificaciones en vivo (Pusher): cada usuario escucha solo su canal privado, que autoriza
// nuestro servidor, y por Pusher viaja solo el id de la notificación (nunca datos de clientes).
// El servidor de pruebas no usa Pusher: guarda los avisos y la tarea pusherEnviados los devuelve.
// Base de prueba: admin = usuario 1, cliente = usuario 4.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);

const pedirPermiso = (canal, socketId = '123.456') =>
  cy.csrfToken().then((token) =>
    cy.request({
      method: 'POST',
      url: '/api/notificaciones/pusher-auth',
      form: true,
      headers: { 'X-CSRF-Token': token },
      failOnStatusCode: false,
      followRedirect: false,
      body: { socket_id: socketId, channel_name: canal },
    }));

describe('Notificaciones en vivo (Pusher)', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('el admin solo puede escuchar su propio canal privado', () => {
    cy.loginComo('admin');

    pedirPermiso('private-usuario-1').then((r) => {
      expect(r.status).to.eq(200);
      expect(json(r.body).auth).to.match(/^[^:]+:[0-9a-f]{64}$/); // "clave:firma"
    });

    // Canal de otro usuario, el canal general viejo y un socket inválido: rechazados
    pedirPermiso('private-usuario-5').its('status').should('eq', 403);
    pedirPermiso('admin-notifications').its('status').should('eq', 403);
    pedirPermiso('private-usuario-1', 'cualquier-cosa').its('status').should('eq', 403);
  });

  it('un cliente no puede pedir permiso para el canal de un admin', () => {
    cy.loginComo('cliente');
    // La ruta es solo del admin: el router lo saca (redirección) sin llegar a firmar nada
    pedirPermiso('private-usuario-1').then((r) => {
      expect(r.status).to.be.oneOf([302, 403]);
      expect(JSON.stringify(r.body)).to.not.match(/"auth"/);
    });
  });

  it('un mensaje de contacto avisa al admin por su canal privado, solo con el id', () => {
    cy.loginComo('cliente');
    cy.postJson('/contacto/enviar', {
      nombre: 'Persona Dummy', email: 'dummy.contacto@gmail.com', asunto: 'consulta', mensaje: 'Mensaje de prueba secreto',
    }).its('body').then(json).its('ok').should('eq', true);

    cy.task('pusherEnviados').then(({ crudo, avisos }) => {
      expect(avisos.length).to.be.greaterThan(0);
      avisos.forEach((aviso) => {
        expect(aviso.canal).to.match(/^private-usuario-\d+$/);
        expect(Object.keys(aviso.datos)).to.deep.eq(['id']);
      });
      expect(avisos.map((a) => a.canal)).to.include('private-usuario-1');
      // Ningún dato del cliente pasa por Pusher
      ['Persona Dummy', 'dummy.contacto', 'secreto'].forEach((dato) => expect(crudo).to.not.contain(dato));
    });

    // El admin ve el contenido completo pidiéndolo al servidor con su sesión
    cy.loginComo('admin');
    cy.request('/api/notificaciones/obtener').its('body').then(json).then((r) => {
      expect(JSON.stringify(r.notificaciones)).to.contain('Persona Dummy');
    });
  });

  it('el panel del admin ya no tiene la clave de Pusher escrita en el JS', () => {
    cy.request('/assets/js/notificaciones.js').its('body').should('not.contain', '195cfd5239428846d64e')
      .and('contain', 'private-usuario-');
  });
});
