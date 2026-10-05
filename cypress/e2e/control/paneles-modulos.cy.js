// Paneles del vendedor (etapa 5): cada botón aparece solo si el perfil tiene su módulo, y el cambio
// se ve en la próxima página sin volver a iniciar sesión.
// Perfiles: VENDEDOR_FUNCIONES = 4, VENDEDOR_PRODUCTOS = 5.
// Módulos: 2 DEVOLUCION_ENTRADAS, 3 CONSULTA_VENTAS_FUNCIONES, 5 CONSULTA_VENTAS_PRODUCTOS,
//          7 MOVIMIENTOS_CAJA, 8 DEVOLUCION_PRODUCTOS, 9 CONTROL_ENTRADAS.

const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

const permiso = (idPerfil, idModulo, activo) => {
  cy.loginComo('admin');
  cy.postJson('/administrador/modulos/asignar', { id_perfil: idPerfil, id_modulo: idModulo, estado: activo ? 1 : 0 })
    .its('body').then(json).its('ok').should('eq', true);
};

describe('Paneles del vendedor según sus módulos', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('vendedor de funciones: los botones siguen a sus módulos', () => {
    cy.loginComo('vfunciones');
    cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
    cy.visit('/vendedor/caja/estado');
    cy.get('a.btn-vender').should('exist');
    cy.get('a.btn-consultar').should('exist');
    cy.get('a.btn-ingresos-egresos').should('exist');
    cy.get('a.btn-control-entradas').should('not.exist');

    permiso(4, 7, false); // sin Ingresos/Egresos
    permiso(4, 3, false); // sin Consultar Ventas
    cy.loginComo('vfunciones');
    cy.visit('/vendedor/caja/estado');
    cy.get('a.btn-ingresos-egresos').should('not.exist');
    cy.get('a.btn-consultar').should('not.exist');
    cy.get('a.btn-vender').should('exist');
    cy.get('.btn-cerrar-caja').should('exist'); // la caja siempre se puede cerrar
  });

  it('vendedor de productos: los botones siguen a sus módulos', () => {
    cy.loginComo('vproductos');
    cy.postJson('/vendedorproductos/caja/abrir', { rela_caja: 3, monto_inicial: 0 });
    cy.visit('/vendedorproductos/caja/estado');
    cy.get('a.btn-vender').should('exist');
    cy.get('a.btn-consultar').should('exist');
    cy.get('a.btn-ingresos-egresos').should('exist');

    permiso(5, 5, false); // sin Consultar Ventas
    cy.loginComo('vproductos');
    cy.visit('/vendedorproductos/caja/estado');
    cy.get('a.btn-consultar').should('not.exist');
    cy.get('a.btn-vender').should('exist');
  });

  it('el botón de devolución en Consultar Ventas depende del módulo de devoluciones', () => {
    // Funciones: DEVOLUCION_ENTRADAS viene activo
    cy.loginComo('vfunciones');
    cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 0 });
    cy.request('/vendedor/ventas/consulta').its('body').should('contain', 'const PUEDE_DEVOLVER = true;');
    permiso(4, 2, false);
    cy.loginComo('vfunciones');
    cy.request('/vendedor/ventas/consulta').its('body').should('contain', 'const PUEDE_DEVOLVER = false;');

    // Productos: DEVOLUCION_PRODUCTOS viene desactivado en la base de prueba
    cy.loginComo('vproductos');
    cy.postJson('/vendedorproductos/caja/abrir', { rela_caja: 3, monto_inicial: 0 });
    cy.request('/vendedorproductos/ventas/consulta').its('body').should('contain', 'const PUEDE_DEVOLVER = false;');
    permiso(5, 8, true);
    cy.loginComo('vproductos');
    cy.request('/vendedorproductos/ventas/consulta').its('body').should('contain', 'const PUEDE_DEVOLVER = true;');
  });

  it('sin caja abierta, el vendedor llega igual al control de entradas si tiene el módulo', () => {
    cy.loginComo('vfunciones');
    cy.visit('/vendedor/caja/abrir');
    cy.get('.tareas-sin-caja').should('not.exist');

    permiso(4, 9, true);
    cy.loginComo('vfunciones');
    cy.visit('/vendedor/caja/abrir');
    cy.get('.tareas-sin-caja a.tarea-sin-caja').should('have.length', 1)
      .and('have.attr', 'href', '/control/entradas')
      .click();
    cy.location('pathname').should('eq', '/control/entradas');
  });
});
