// Las exportaciones del administrador: Excel es un .xlsx real (con anchos de columna y encabezados)
// y PDF un documento válido, en todas las pantallas que exportan.

const MODULOS = [
  'sexo', 'usuarios', 'peliculas', 'salas', 'funciones', 'turnos', 'tipos_entradas',
  'entradas', 'productos', 'stock', 'maquinas', 'gastos', 'movimientos-web',
];

const descargar = (url) => cy.request({ url, encoding: 'binary' });

describe('Exportaciones a Excel y PDF', () => {
  before(() => {
    cy.task('prepararBase', null, { timeout: 60000 });
  });

  beforeEach(() => {
    cy.loginComo('admin');
  });

  it('cada pantalla exporta un .xlsx y un .pdf válidos', () => {
    MODULOS.forEach((modulo) => {
      descargar(`/administrador/${modulo}/exportar?tipo=excel`).then((r) => {
        expect(r.status, `${modulo} excel`).to.eq(200);
        expect(r.headers['content-type'], `${modulo} excel`).to.contain('spreadsheetml.sheet');
        expect(r.headers['content-disposition'], `${modulo} excel`).to.contain('.xlsx');
        expect(r.body.slice(0, 2), `${modulo} excel es un zip`).to.eq('PK');
      });
      descargar(`/administrador/${modulo}/exportar?tipo=pdf`).then((r) => {
        expect(r.status, `${modulo} pdf`).to.eq(200);
        // TCPDF manda varios Content-Type para forzar la descarga: se mira el nombre del archivo
        expect(r.headers['content-disposition'], `${modulo} pdf`).to.contain('.pdf');
        expect(r.body.slice(0, 5), `${modulo} pdf`).to.eq('%PDF-');
      });
    });
  });

  it('el Excel trae título, encabezados, los datos y columnas con ancho según el contenido', () => {
    descargar('/administrador/usuarios/exportar?tipo=excel').then((r) => {
      cy.writeFile('cypress/downloads/usuarios.xlsx', r.body, 'binary');
    });
    cy.task('leerXlsx', 'cypress/downloads/usuarios.xlsx').then(({ filas, anchos }) => {
      expect(filas[0][0]).to.contain('Usuarios');                       // título
      expect(filas[1][0]).to.contain('Generado el');                    // fecha
      const encabezados = filas[2];
      expect(encabezados).to.include.members(['ID', 'Usuario', 'Email']);

      const iEmail = encabezados.indexOf('Email');
      const emails = filas.slice(3).map((f) => f[iEmail]);
      expect(emails).to.include('admin@cinfsa.test');

      // La columna de email es más ancha que la de ID, y ninguna queda en el ancho mínimo de Excel
      expect(anchos[iEmail]).to.be.greaterThan(anchos[encabezados.indexOf('ID')]);
      expect(anchos[iEmail]).to.be.at.least('vproductos@cinfsa.test'.length);
    });
  });

  it('un vendedor no puede exportar', () => {
    cy.loginComo('vfunciones');
    cy.request({ url: '/administrador/usuarios/exportar?tipo=excel', followRedirect: false, failOnStatusCode: false })
      .its('status').should('eq', 302);
  });
});
