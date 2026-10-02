// Control → Registro de auditoría: cada acción sensible queda registrada con su usuario,
// sin guardar nunca contraseñas, y solo el administrador puede consultarlo.
// Base de prueba: funciones 1 y 2, tipo de entrada 1 (2D, $3000), película 1. Ver tests/db/datos_prueba.sql.

const json = (b) => (typeof b === 'string' ? JSON.parse(b) : b);
const consultar = (sql) => cy.task('consultarBase', sql);

// Último registro de una acción
const ultimo = (accion) =>
  consultar(`SELECT * FROM auditoria WHERE accion = '${accion}' ORDER BY id_auditoria DESC LIMIT 1`).its(0);

const pedirSinSeguir = (url) =>
  cy.request({ url, followRedirect: false, failOnStatusCode: false });

describe('Registro de auditoría', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  describe('Acceso', () => {
    it('solo el administrador puede ver y exportar el registro', () => {
      ['cliente', 'vfunciones', 'vproductos'].forEach((rol) => {
        cy.loginComo(rol);
        ['/administrador/auditoria/listado', '/administrador/auditoria/exportar'].forEach((url) => {
          pedirSinSeguir(url).then((r) => {
            expect(r.status, `${rol} ${url}`).to.eq(302);
            expect(r.redirectedToUrl, `${rol} ${url}`).to.match(/\/$/);
          });
        });
      });
      cy.loginComo('admin');
      pedirSinSeguir('/administrador/auditoria/listado').its('status').should('eq', 200);
    });
  });

  describe('Qué se registra', () => {
    it('inicio y cierre de sesión', () => {
      cy.loginComo('vfunciones');
      ultimo('sesion.login').then((r) => {
        expect(r.nombre_usuario).to.eq('vfunciones_test');
        expect(r.perfil).to.eq('VENDEDOR_FUNCIONES');
        expect(r.ip).to.not.be.empty;
      });
      cy.request('/logaut');
      ultimo('sesion.logout').its('nombre_usuario').should('eq', 'vfunciones_test');
    });

    it('login fallido: queda a nombre de la cuenta atacada, sin guardar lo que se escribió', () => {
      cy.clearCookies();
      cy.postJson('/', { nombre_usuario: 'admin_test', clave_usuario: 'ClaveEquivocada77' });
      ultimo('sesion.login_fallido').then((r) => {
        expect(r.nombre_usuario).to.eq('admin_test');
        expect(r.descripcion).to.contain('Contraseña incorrecta');
      });

      // Usuario inexistente: puede ser una contraseña tipeada en el campo equivocado
      cy.postJson('/', { nombre_usuario: 'MiClaveSecreta99', clave_usuario: 'x' });
      ultimo('sesion.login_fallido').then((r) => {
        expect(r.id_usuario).to.be.null;
        expect(r.descripcion).to.eq('Intento con un usuario inexistente');
      });
      consultar(`SELECT COUNT(*) AS n FROM auditoria WHERE CONCAT_WS(' ', descripcion, datos_antes, datos_despues, nombre_usuario)
                 LIKE '%MiClaveSecreta99%' OR CONCAT_WS(' ', descripcion, datos_antes, datos_despues) LIKE '%ClaveEquivocada77%'`)
        .its('0.n').should('eq', '0');
    });

    it('cambio de precio de una entrada, con el antes y el después', () => {
      cy.loginComo('admin');
      cy.postJson('/administrador/tipos_entradas/actualizar', { id_tipo_entrada: 1, tipo_entrada_desc: '2D', precio_entrada: 3500, estado: 1 })
        .its('body').then((b) => expect(json(b).ok).to.eq(true));
      ultimo('precio.tipo_entrada').then((r) => {
        expect(r.nombre_usuario).to.eq('admin_test');
        expect(JSON.parse(r.datos_antes)).to.deep.eq({ precio: 3000 });
        expect(JSON.parse(r.datos_despues)).to.deep.eq({ precio: 3500 });
      });

      // Guardar sin cambiar el precio no genera registro
      cy.postJson('/administrador/tipos_entradas/actualizar', { id_tipo_entrada: 1, tipo_entrada_desc: '2D', precio_entrada: 3500, estado: 1 });
      consultar("SELECT COUNT(*) AS n FROM auditoria WHERE accion = 'precio.tipo_entrada'").its('0.n').should('eq', '1');
    });

    it('editar un usuario: registra qué cambió y solo que cambió la contraseña, nunca su valor', () => {
      cy.loginComo('admin');
      cy.visit('/administrador/usuarios/editar?id=4');
      cy.get('#email').clear().type('cliente.prueba@gmail.com');
      cy.get('#clave_usuario').type('NuevaClave123');
      cy.get('form[data-fetch="true"] [type="submit"]').click();
      cy.get('.boton-modal.confirmar').click();
      cy.get('.alerta-modal.exito').should('be.visible');

      ultimo('usuario.modificar').then((r) => {
        expect(r.descripcion).to.contain('cliente_test').and.contain('email').and.contain('contraseña');
        expect(JSON.parse(r.datos_despues)).to.include({ email: 'cliente.prueba@gmail.com', cambio_credenciales: 'sí' });
      });
      consultar("SELECT COUNT(*) AS n FROM auditoria WHERE CONCAT_WS(' ', descripcion, datos_antes, datos_despues) LIKE '%NuevaClave123%' OR CONCAT_WS(' ', datos_antes, datos_despues) LIKE '%$2y$%'")
        .its('0.n').should('eq', '0');
    });

    it('cierre de caja con faltante y cancelación de una entrada', () => {
      cy.loginComo('vfunciones');
      cy.postJson('/vendedor/caja/abrir', { rela_caja: 1, monto_inicial: 10000 });
      consultar('SELECT MIN(id_butaca) AS id FROM butacas WHERE rela_salas = 1').then(([{ id }]) => {
        cy.postJson('/vendedor/funciones/procesar-venta', { id_funcion: 1, butacas: [Number(id)], tipo_pago: 1, tipo_comprobante: 'TICKET' });
      });
      cy.postJson('/vendedor/caja/cerrar', { monto_final: 12500, observaciones: '' });
      ultimo('caja.cerrar').then((r) => {
        expect(r.nombre_usuario).to.eq('vfunciones_test');
        expect(r.descripcion).to.contain('diferencia -$500');
        expect(JSON.parse(r.datos_despues)).to.include({ esperado_efectivo: 13000, declarado: 12500, diferencia: -500 });
      });

      consultar('SELECT MAX(id_entrada) AS id FROM entradas').then(([{ id }]) => {
        cy.loginComo('admin');
        cy.postJson('/administrador/entradas/eliminar', { id_entrada: Number(id) });
        ultimo('entrada.cancelar').then((r) => {
          expect(Number(r.id_entidad)).to.eq(Number(id));
          expect(r.descripcion).to.contain('Pelicula de Prueba').and.contain('butaca F');
        });
      });
    });

    it('alta y baja de una función, y baja de una película', () => {
      cy.loginComo('admin');
      const manana = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
      cy.postJson('/administrador/funciones/guardar', {
        fecha_hora: manana, fecha_finalizacion: manana, rela_salas: 12, rela_peliculas: 1,
        rela_turnos: 1, rela_tipo_entrada: 1, rela_idioma: 1, estado: 1,
      }).its('body').then((b) => expect(json(b).ok, JSON.stringify(json(b))).to.eq(true));
      ultimo('funcion.crear').then((r) => {
        expect(r.descripcion).to.contain('Pelicula de Prueba').and.contain('Sala 12');
        cy.postJson('/administrador/funciones/eliminar', { id_funcion: Number(r.id_entidad) });
        ultimo('funcion.baja').its('id_entidad').should('eq', r.id_entidad);
      });

      cy.postJson('/administrador/peliculas/eliminar', { id: 1 });
      ultimo('pelicula.baja').its('descripcion').should('eq', 'Dio de baja la película Pelicula de Prueba');
    });

    it('alta, modificación y baja de un producto (un solo registro por guardado)', () => {
      cy.loginComo('admin');
      cy.visit('/administrador/productos/crear');
      cy.get('#nombre_producto_cantina').type('Gaseosa de Prueba');
      cy.get('#precio_producto').type('1500');
      cy.get('#imagen_producto').selectFile('cypress/fixtures/producto-prueba.png');
      cy.get('form[data-fetch="true"] [type="submit"]').click();
      cy.get('.alerta-modal.exito').should('be.visible');

      ultimo('producto.crear').then((alta) => {
        expect(alta.descripcion).to.eq('Creó el producto Gaseosa de Prueba ($1.500)');
        const id = Number(alta.id_entidad);
        expect(id).to.be.greaterThan(0);

        const editar = (cambios) => {
          cy.visit(`/administrador/productos/editar?id=${id}`);
          Object.entries(cambios).forEach(([campo, valor]) => cy.get(`#${campo}`).clear().type(valor));
          cy.get('form[data-fetch="true"] [type="submit"]').click();
          cy.get('.boton-modal.confirmar').click();
          cy.get('.alerta-modal.exito').should('be.visible');
        };

        // Solo el nombre: "modificación de producto"
        editar({ nombre_producto_cantina: 'Gaseosa Grande' });
        ultimo('producto.modificar').then((r) => {
          expect(r.descripcion).to.eq('Modificó el producto Gaseosa Grande (nombre)');
          expect(JSON.parse(r.datos_antes)).to.deep.eq({ nombre: 'Gaseosa de Prueba' });
        });

        // Precio y nombre a la vez: un único registro, como "cambio de precio"
        editar({ nombre_producto_cantina: 'Gaseosa XL', precio_producto: '1800' });
        ultimo('precio.producto').then((r) => {
          expect(r.descripcion).to.eq('Cambió el precio de Gaseosa XL: $1.500 → $1.800 (también: nombre)');
          expect(JSON.parse(r.datos_despues)).to.deep.eq({ nombre: 'Gaseosa XL', precio: 1800 });
        });
        consultar(`SELECT COUNT(*) AS n FROM auditoria WHERE entidad = 'productos_cantina' AND id_entidad = ${id}`)
          .its('0.n').should('eq', '3'); // alta + 2 guardados

        cy.postJson('/administrador/productos/eliminar', { id_producto_cantina: id });
        ultimo('producto.baja').its('descripcion').should('eq', 'Dio de baja (suspendió) el producto Gaseosa XL');
      });
    });

    it('cambios de stock: alta, modificación de la cantidad y baja', () => {
      cy.loginComo('admin');
      // Pochoclo Chico (producto 1) todavía no tiene stock en la cantina 2
      cy.postJson('/administrador/stock/guardar', { stock_cantina: 20, rela_producto_cantina: 1, rela_cantina: 2 })
        .its('body').then((b) => expect(json(b).ok, JSON.stringify(json(b))).to.eq(true));
      ultimo('stock.crear').then((alta) => {
        expect(alta.descripcion).to.eq('Cargó stock de Pochoclo Chico en cantina 2: 20 unidad(es)');
        const id = Number(alta.id_entidad);
        expect(id).to.be.greaterThan(0);

        cy.postJson('/administrador/stock/actualizar', { id_stock_cantina: id, stock_cantina: 35, rela_producto_cantina: 1, rela_cantina: 2 });
        ultimo('stock.modificar').then((r) => {
          expect(r.descripcion).to.eq('Modificó el stock de Pochoclo Chico en cantina 2: 20 → 35 unidad(es)');
          expect(JSON.parse(r.datos_antes)).to.deep.eq({ cantidad: 20 });
          expect(JSON.parse(r.datos_despues)).to.deep.eq({ cantidad: 35 });
        });

        cy.postJson('/administrador/stock/eliminar', { id_stock_cantina: id });
        ultimo('stock.baja').its('descripcion').should('eq', 'Dio de baja el stock de Pochoclo Chico en cantina 2 (35 unidad(es))');
      });
    });
  });

  describe('Pantalla Control → Registro de auditoría', () => {
    it('lista, filtra, muestra el detalle y exporta lo filtrado', () => {
      cy.clearCookies();
      cy.postJson('/', { nombre_usuario: 'admin_test', clave_usuario: 'mal' });
      cy.loginComo('admin');
      cy.postJson('/administrador/tipos_entradas/actualizar', { id_tipo_entrada: 1, tipo_entrada_desc: '2D', precio_entrada: 3500, estado: 1 });

      cy.visit('/administrador/auditoria/listado');
      cy.get('.tabla-auditoria tbody tr').should('have.length', 3); // login fallido, login, cambio de precio
      cy.contains('.tabla-auditoria tr', 'Inicio de sesión fallido').should('have.class', 'fila-destacada');

      // Detalle con antes → después
      cy.contains('.tabla-auditoria tr', 'Cambio de precio de entrada').find('.btn-ver-detalle').click();
      cy.get('#auditoria-modal').should('be.visible').within(() => {
        cy.contains('td', 'Precio').siblings('.valor-antes').should('have.text', '3000');
        cy.contains('td', 'Precio').siblings('.valor-despues').should('have.text', '3500');
      });
      cy.get('.auditoria-modal-cerrar').click();
      cy.get('#auditoria-modal').should('not.be.visible');

      // Filtro por acción
      cy.get('#filtro-accion').select('precio.tipo_entrada');
      cy.get('.auditoria-filtros [type="submit"]').click();
      cy.get('.tabla-auditoria tbody tr').should('have.length', 1);

      // La exportación respeta el filtro
      cy.get('.auditoria-exportar').invoke('attr', 'href').should('contain', 'accion=precio.tipo_entrada').then((href) => {
        cy.request(href).then((r) => {
          expect(r.headers['content-type']).to.contain('text/csv');
          expect(r.body).to.contain('Cambio de precio de entrada').and.not.contain('Inicio de sesión fallido');
        });
      });
    });

    it('la pestaña Control está en la barra y en el menú lateral', () => {
      cy.loginComo('admin');
      cy.visit('/administrador');
      cy.contains('.nav-modular .dropdown-toggle', 'Control').click();
      cy.contains('.nav-modular a', 'Registro de auditoría').should('be.visible')
        .and('have.attr', 'href', '/administrador/auditoria/listado');
      // Menú lateral (celular): ahora también trae Ventas Web y Proveedores
      ['lateral-ventas-web', 'lateral-proveedores', 'lateral-control'].forEach((id) => {
        cy.get(`#${id}`).should('exist');
      });
      cy.get('#lateral-control a').should('have.attr', 'href', '/administrador/auditoria/listado');
    });
  });
});
