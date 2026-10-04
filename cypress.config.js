const { defineConfig } = require("cypress");
const { execFileSync } = require("child_process");
const path = require("path");

// Los tests corren contra la base de prueba (cinfsa1_test), nunca contra la real.
// Servidor: `npm run test:servidor` (usa tests/servidor_pruebas.php).
module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    setupNodeEvents(on, config) {
      on('task', {
        // Recrea cinfsa1_test desde cero (estructura + datos de prueba)
        prepararBase() {
          const php = process.env.PHP_BIN || 'php';
          const script = path.join(__dirname, 'tests', 'preparar_base_pruebas.php');
          return execFileSync(php, [script], { encoding: 'utf8' });
        },
        // SELECT de solo lectura sobre la base de prueba; devuelve un array de filas
        consultarBase(sql) {
          const php = process.env.PHP_BIN || 'php';
          const script = path.join(__dirname, 'tests', 'consultar_base_pruebas.php');
          return JSON.parse(execFileSync(php, [script, sql], { encoding: 'utf8' }));
        },
        // Simula que un cliente está pagando una butaca (reserva temporal de 15 minutos)
        reservarButaca({ idButaca, idFuncion, idUsuario }) {
          const php = process.env.PHP_BIN || 'php';
          const script = path.join(__dirname, 'tests', 'reservar_butaca_prueba.php');
          return execFileSync(php, [script, String(idButaca), String(idFuncion), String(idUsuario)], { encoding: 'utf8' });
        },
        // Simula una compra web ya pagada de una butaca; devuelve el id de la entrada
        // (sin código QR, salvo que se pida conCodigo)
        venderButacaWeb({ idButaca, idFuncion, idUsuario, conCodigo = false }) {
          const php = process.env.PHP_BIN || 'php';
          const script = path.join(__dirname, 'tests', 'venta_web_prueba.php');
          return Number(execFileSync(php, [script, String(idButaca), String(idFuncion), String(idUsuario), conCodigo ? '1' : '0'], { encoding: 'utf8' }));
        },
        // Crea una función que empieza dentro de N minutos (negativo = ya empezó); devuelve su id
        crearFuncion(minutosDesdeAhora) {
          const php = process.env.PHP_BIN || 'php';
          const script = path.join(__dirname, 'tests', 'funcion_prueba.php');
          return Number(execFileSync(php, [script, String(minutosDesdeAhora)], { encoding: 'utf8' }));
        },
        // Crea un pedido web con 10 productos y 6 fichas; devuelve { id_orden, numero_orden, codigo }
        crearPedidoWeb({ idUsuario, estado = 'pagado' }) {
          const php = process.env.PHP_BIN || 'php';
          const script = path.join(__dirname, 'tests', 'pedido_web_prueba.php');
          return JSON.parse(execFileSync(php, [script, String(idUsuario), estado], { encoding: 'utf8' }));
        },
      });
    },
    viewportWidth: 1280,
    viewportHeight: 720,
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
  },
});
