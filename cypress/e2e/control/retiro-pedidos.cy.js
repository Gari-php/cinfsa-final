// Retiro en la cantina de productos y fichas comprados por la web (etapa 4 del QR).
// Cada pedido tiene un QR de retiro (CINFSA-O-<código>); se puede retirar todo o de a poco.
// Base de prueba: entrega_test (perfil ENTREGA_PRODUCTOS = 8, id 7), módulo ENTREGA_PRODUCTOS = 10,
// vproductos = perfil 5. El pedido de prueba trae 10 Pochoclo Chico y 6 fichas.

const ID_CLIENTE = 4;
const PERFIL_VPRODUCTOS = 5;
const MODULO_ENTREGA = 10;

const consultar = (sql) => cy.task('consultarBase', sql);
const json = (body) => (typeof body === 'string' ? JSON.parse(body) : body);

const pedido = (estado = 'pagado') =>
  cy.task('crearPedidoWeb', { idUsuario: ID_CLIENTE, estado }).then((p) => {
    const qr = `CINFSA-O-${p.codigo}`;
    return consultar(`SELECT id_detalle, tipo_producto FROM detalle_orden WHERE id_orden = ${p.id_orden}`).then((filas) => ({
      ...p,
      qr,
      cantina: Number(filas.find((f) => f.tipo_producto === 'cantina').id_detalle),
      fichas: Number(filas.find((f) => f.tipo_producto === 'fichas').id_detalle),
    }));
  });

const pedir = (accion, cuerpo) => cy.postJson(`/control/retiros/${accion}`, cuerpo).its('body').then(json);
const item = (r, idDetalle) => r.pedido.items.find((i) => i.id_detalle === idDetalle);

describe('Retiro de pedidos web en la cantina', () => {
  beforeEach(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  it('el usuario de entregas inicia sesión directo en la pantalla de retiros', () => {
    cy.fixture('usuarios').then((u) => {
      cy.clearCookies();
      cy.postJson('/', { nombre_usuario: u.entrega.usuario, clave_usuario: u.entrega.clave })
        .its('body').then(json).its('redirigir').should('eq', '/control/retiros');
    });
  });

  it('retiro de a poco: entrega parcial, no deja pasarse y termina en "ya retiró todo"', () => {
    pedido().then((p) => {
      cy.loginComo('entrega');

      pedir('buscar', { codigo: p.qr }).then((r) => {
        expect(r.resultado).to.eq('pendiente');
        expect(r.pedido.numero).to.eq(p.numero_orden);
        expect(r.pedido.cliente).to.eq('cliente_test');
        expect(item(r, p.cantina)).to.include({ comprado: 10, entregado: 0, quedan: 10 });
        expect(item(r, p.fichas)).to.include({ comprado: 6, entregado: 0, quedan: 6, tipo: 'Fichas' });
      });

      // Primera vez: 2 pochoclos y 2 fichas
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 2, [p.fichas]: 2 } }).then((r) => {
        expect(r.resultado).to.eq('verde');
        expect(r.mensaje).to.contain('2 × Pochoclo Chico');
        expect(item(r, p.cantina).quedan).to.eq(8);
        expect(item(r, p.fichas).quedan).to.eq(4);
        expect(r.pedido.historial).to.have.length(2);
        expect(r.pedido.historial[0].usuario).to.eq('entrega_test');
      });

      // No se puede entregar más de lo que queda (y no se registra nada)
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 9 } }).then((r) => {
        expect(r.error).to.eq('De Pochoclo Chico quedan 8');
        expect(item(r, p.cantina).quedan).to.eq(8);
      });
      pedir('entregar', { codigo: p.qr, items: {} }).its('error').should('eq', 'Elegí qué entregar');

      // El resto
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 8, [p.fichas]: 4 } }).then((r) => {
        expect(r.resultado).to.eq('verde');
        expect(r.mensaje).to.contain('Pedido completo');
        expect(r.puede_entregar).to.eq(false);
      });

      pedir('buscar', { codigo: p.qr }).then((r) => {
        expect(r.resultado).to.eq('rojo');
        expect(r.titulo).to.eq('Ya retiró todo');
        expect(r.pedido.historial).to.have.length(4);
      });
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 1 } }).its('error').should('eq', 'De Pochoclo Chico quedan 0');

      consultar(`SELECT SUM(eo.cantidad) AS total FROM entregas_orden eo JOIN detalle_orden d ON d.id_detalle = eo.id_detalle
                 WHERE d.id_orden = ${p.id_orden}`).its('0.total').then(Number).should('eq', 16);
    });
  });

  it('deshacer anula la última entrega completa, y solo la puede deshacer quien la hizo', () => {
    pedido().then((p) => {
      cy.loginComo('entrega');
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 3, [p.fichas]: 1 } });

      cy.loginComo('admin');
      pedir('deshacer', { codigo: p.qr }).its('error').should('contain', 'Ya no se puede deshacer');

      cy.loginComo('entrega');
      pedir('deshacer', { codigo: p.qr }).then((r) => {
        expect(r.mensaje).to.contain('Entrega deshecha');
        expect(item(r, p.cantina).quedan).to.eq(10);
        expect(item(r, p.fichas).quedan).to.eq(6);
        expect(r.pedido.historial).to.have.length(0);
      });
    });
  });

  it('pedido cancelado o sin pagar, una entrada u otro QR: rojo', () => {
    pedido('cancelado').then((p) => {
      cy.loginComo('entrega');
      pedir('buscar', { codigo: p.qr }).its('titulo').should('eq', 'Pedido cancelado');
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 1 } }).its('puede_entregar').should('eq', false);
      consultar(`SELECT COUNT(*) AS n FROM entregas_orden eo JOIN detalle_orden d ON d.id_detalle = eo.id_detalle
                 WHERE d.id_orden = ${p.id_orden}`).its('0.n').then(Number).should('eq', 0);
    });
    pedido('pendiente').then((p) => {
      pedir('buscar', { codigo: p.qr }).its('titulo').should('eq', 'Pedido sin pagar');
    });
    pedir('buscar', { codigo: `CINFSA-E-${'a'.repeat(32)}` }).its('titulo').should('eq', 'Es una entrada');
    pedir('buscar', { codigo: `CINFSA-O-${'0'.repeat(32)}` }).its('titulo').should('eq', 'Pedido inexistente');
    pedir('buscar', { codigo: 'hola' }).its('titulo').should('eq', 'QR no válido');
  });

  it('pantalla: con el código corto se elige cuánto entregar y se registra', () => {
    pedido().then((p) => {
      cy.loginComo('entrega');
      cy.visit('/control/retiros');
      cy.get('#codigo-manual').type(p.codigo.slice(0, 8).toUpperCase());
      cy.get('#form-manual').submit();

      cy.get('#resultado').should('be.visible').and('have.class', 'pendiente');
      cy.get('#pedido-items .retiro-item').should('have.length', 2);
      // Por defecto se entrega todo lo que queda (10 + 6); se baja el pochoclo a 2 y las fichas a 0
      cy.get('#entregar-total').should('have.text', '(16)');
      cy.get(`[data-id-detalle="${p.cantina}"] button[aria-label="Una menos"]`).as('menosPochoclo');
      for (let i = 0; i < 8; i++) cy.get('@menosPochoclo').click();
      cy.get(`[data-id-detalle="${p.fichas}"] button[aria-label="Una menos"]`).as('menosFichas');
      for (let i = 0; i < 6; i++) cy.get('@menosFichas').click();
      cy.get('#entregar-total').should('have.text', '(2)');
      cy.get('#btn-entregar').click();

      cy.get('#resultado').should('have.class', 'verde');
      cy.get('#resultado-mensaje').should('contain.text', '2 × Pochoclo Chico');
      cy.get(`[data-id-detalle="${p.cantina}"]`).should('contain.text', 'retiró 2 · quedan 8');
      cy.get('#pedido-historial').should('contain.text', '2 × Pochoclo Chico');
      cy.get('#btn-deshacer').should('be.visible');
    });
  });

  it('Mis compras: pestaña "Cantina y fichas" con QR de retiro y lo que queda; al completar, retirado y sin QR', () => {
    pedido().then((p) => {
      cy.loginComo('entrega');
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 4 } });

      cy.loginComo('cliente');
      cy.request('/perfil/mis-compras').its('body').then(json).then((r) => {
        const pe = r.pedidos.find((x) => x.id_orden === p.id_orden);
        expect(pe.retirado).to.eq(false);
        expect(pe.qr).to.match(/^data:image\/png;base64,/);
        expect(pe.codigo_corto).to.eq(p.codigo.slice(0, 8).toUpperCase());
        expect(pe.total).to.eq(26000);
        expect(pe.productos.find((x) => x.nombre === 'Pochoclo Chico')).to.include({ comprado: 10, entregado: 4, quedan: 6 });
      });

      cy.visit('/perfil');
      cy.window().then((w) => w.mostrarMisCompras());
      cy.get('#tabBtnPedidos').should('contain.text', 'Cantina y fichas').click();
      cy.get('#tabPedidos').should('contain.text', 'Para retirar').and('contain.text', 'quedan 6');
      cy.get(`[aria-controls="qr-pedido-${p.id_orden}"]`).click();
      cy.get(`#qr-pedido-${p.id_orden}`).should('be.visible').and('contain.text', p.codigo.slice(0, 8).toUpperCase());

      // Se retira el resto: el pedido pasa a "Retirados" sin QR
      cy.loginComo('entrega');
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 6, [p.fichas]: 6 } });
      cy.loginComo('cliente');
      cy.request('/perfil/mis-compras').its('body').then(json).then((r) => {
        const pe = r.pedidos.find((x) => x.id_orden === p.id_orden);
        expect(pe.retirado).to.eq(true);
        expect(pe.qr).to.eq(null);
      });
    });
  });

  it('cancelar una compra con entregas devuelve al stock solo lo que no se retiró', () => {
    const stockPochoclo = () =>
      consultar('SELECT stock_cantina AS n FROM stock_cantina WHERE rela_producto_cantina = 1 AND rela_cantina = 1').its('0.n').then(Number);
    const stockFichas = () =>
      consultar('SELECT f.cantidad_ficha AS n FROM fichas f JOIN maquinas m ON m.rela_fichas = f.id_fichas WHERE m.id_maquinas = 1').its('0.n').then(Number);

    pedido().then((p) => {
      cy.loginComo('entrega');
      pedir('entregar', { codigo: p.qr, items: { [p.cantina]: 4, [p.fichas]: 6 } });

      stockPochoclo().then((pochocloAntes) => stockFichas().then((fichasAntes) => {
        cy.loginComo('admin');
        cy.postJson('/administrador/movimientos-web/cancelar', { id_orden: p.id_orden }).its('body').then(json).then((r) => {
          expect(r.ok).to.eq(true);
          expect(r.mensaje).to.contain('no vuelve al stock').and.contain('4 × Pochoclo Chico').and.contain('6 × Fichas - Maquina de Prueba');
        });

        // De 10 pochoclos se retiraron 4: vuelven 6. Las 6 fichas se retiraron todas: no vuelve ninguna
        stockPochoclo().should('eq', pochocloAntes + 6);
        stockFichas().should('eq', fichasAntes);

        consultar(`SELECT descripcion FROM auditoria WHERE accion = 'orden.cancelar' AND id_entidad = ${p.id_orden}`)
          .its('0.descripcion').should('contain', 'ya se había entregado: 4 × Pochoclo Chico');

        // Y el pedido ya no se entrega
        cy.loginComo('entrega');
        pedir('buscar', { codigo: p.qr }).its('titulo').should('eq', 'Pedido cancelado');
      }));
    });
  });

  it('cancelar una compra con entradas libera la butaca de esa función pero respeta el bloqueo del administrador', () => {
    consultar('SELECT MIN(id_butaca) AS id FROM butacas WHERE rela_salas = 1 AND rela_estado_butaca = 1').then(([{ id }]) => {
      const butaca = Number(id);
      cy.task('venderButacaWeb', { idButaca: butaca, idFuncion: 1, idUsuario: ID_CLIENTE }).then((idEntrada) => {
        consultar(`SELECT id_orden FROM butacas_vendidas WHERE id_entrada = ${idEntrada}`).then(([{ id_orden: idOrden }]) => {
          cy.loginComo('admin');
          // El administrador la bloquea por mantenimiento (estado 2 = No disponible)
          cy.postJson('/administrador/butacas/cambiar-estado', { id_butaca: butaca, estado: 2 });
          cy.postJson('/administrador/movimientos-web/cancelar', { id_orden: Number(idOrden) }).its('body').then(json).its('ok').should('eq', true);

          consultar(`SELECT COUNT(*) AS n FROM butacas_vendidas WHERE id_butaca = ${butaca} AND id_funcion = 1`).its('0.n').then(Number).should('eq', 0);
          consultar(`SELECT estado FROM entradas WHERE id_entrada = ${idEntrada}`).its('0.estado').then(Number).should('eq', -1);
          consultar(`SELECT rela_estado_butaca AS e FROM butacas WHERE id_butaca = ${butaca}`).its('0.e').then(Number).should('eq', 2);
        });
      });
    });
  });

  it('el vendedor de productos accede solo si le dan el módulo, y ve el botón en su panel', () => {
    cy.loginComo('vproductos');
    cy.request('/control/retiros').its('redirects').should('have.length.greaterThan', 0);

    cy.loginComo('admin');
    cy.postJson('/administrador/modulos/asignar', { id_perfil: PERFIL_VPRODUCTOS, id_modulo: MODULO_ENTREGA, estado: 1 })
      .its('body').then(json).its('ok').should('eq', true);

    cy.loginComo('vproductos');
    cy.postJson('/vendedorproductos/caja/abrir', { rela_caja: 3, monto_inicial: 0 });
    cy.visit('/vendedorproductos/caja/estado');
    cy.get('a.btn-retiros').should('have.attr', 'href', '/control/retiros').click();
    cy.location('pathname').should('eq', '/control/retiros');

    cy.loginComo('admin');
    cy.postJson('/administrador/modulos/asignar', { id_perfil: PERFIL_VPRODUCTOS, id_modulo: MODULO_ENTREGA, estado: 0 });
    cy.loginComo('vproductos');
    cy.visit('/vendedorproductos/caja/estado');
    cy.get('a.btn-retiros').should('not.exist');
  });
});
