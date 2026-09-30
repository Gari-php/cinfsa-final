# CINFSA

Sistema de gestión para cine: cartelera y venta de entradas online (con selección
de butacas y pago por MercadoPago), venta presencial en boletería y cantina,
control de caja, stock, proveedores/gastos, y un panel de administración
completo con reportes.

> ⚠️ Todos los derechos reservados. Ver [`LICENSE`](./LICENSE).

## Stack

- **Backend:** PHP (MVC propio, sin framework) + MySQL
- **Frontend:** PHP + JS vanilla (sin build step)
- **Dependencias PHP:** Composer (PHPMailer, SendGrid, TCPDF, Pusher, MercadoPago SDK, phpdotenv)
- **Tests E2E:** Cypress
- **Entorno local recomendado:** XAMPP (Apache + MySQL + PHP)

## Requisitos

- PHP 8+
- MySQL / MariaDB
- Composer
- Node.js (solo para correr los tests de Cypress)

## Instalación

1. Cloná el repo dentro de tu carpeta de XAMPP (ej. `C:\xampp\htdocs\CINFSA1`).
2. Instalá las dependencias de PHP:
   ```bash
   composer install
   ```
3. Instalá las dependencias de Node (solo necesarias para Cypress):
   ```bash
   npm install
   ```
4. Creá la base de datos e importá el esquema:
   ```bash
   mysql -u root -e "CREATE DATABASE cinfsa1"
   mysql -u root cinfsa1 < cinfsa1_schema.sql
   ```
   > `cinfsa1_schema.sql` trae solo la estructura de las tablas (sin datos). Cargá
   > tus propios datos de prueba desde el panel de administrador una vez levantado el sistema.
   >
   > ⚠️ El archivo incluye `DROP TABLE` antes de cada tabla: no lo importes sobre una
   > base que ya tenga datos que quieras conservar.
5. Copiá `.env.example` a `.env` y completá tus credenciales:
   ```bash
   cp .env.example .env
   ```
   Variables necesarias: conexión a MySQL, API key de SendGrid (envío de emails),
   credenciales de MercadoPago (cobros) y de Pusher (notificaciones en tiempo real
   dentro del panel de administrador). El sistema funciona sin estas tres últimas,
   pero esas funcionalidades puntuales quedarán deshabilitadas hasta configurarlas.

   Además:
   - `APP_URL`: URL pública del sitio. Se usa en los enlaces de los emails y como
     dirección de retorno de MercadoPago, así que tiene que ser el dominio donde corre el sitio.
   - `APP_ENV`: `development` muestra los errores en pantalla; `production` los oculta
     y los guarda en `logs/php_errors.log`. **En un servidor público usá `production`.**
6. Levantá el proyecto con XAMPP (Apache apuntando a la carpeta del repo) o con
   el servidor embebido de PHP:
   ```bash
   php -S localhost:8000 -t public
   ```

## Tests

Los tests **nunca usan la base real**: trabajan sobre `cinfsa1_test`, que se crea
desde cero con `cinfsa1_schema.sql` + `tests/db/datos_prueba.sql` (catálogos y usuarios de
prueba, sin datos personales). Los scripts se niegan a correr contra una base cuyo
nombre no termine en `_test`.

### Prueba rápida (antes de cada commit, ~1 minuto)

```bash
npm run test:rapido     # = php scripts/prueba_rapida.php
```

Verifica que cada ruta apunte a un método existente, levanta un servidor propio,
inicia sesión con cada rol y pide todas las páginas GET. Informa cualquier error de
PHP (fatal, warning, notice, deprecated) y termina con código 1 si encontró alguno.

### Tests E2E (Cypress)

En una terminal, el servidor apuntando a la base de prueba:

```bash
npm run test:servidor   # = php -S localhost:3000 -t public tests/servidor_pruebas.php
```

En otra:

```bash
npm run test:e2e        # headless
npx cypress open        # modo interactivo
```

Cada archivo de tests recrea `cinfsa1_test` antes de empezar. Cubren:

| Spec | Qué verifica |
|---|---|
| `auth/login-roles.cy.js` | Login de cada rol, credenciales inválidas y acceso entre módulos |
| `caja/caja-funciones.cy.js` | Apertura de caja, venta en boletería, cierre y arqueo (con y sin faltante) |
| `compra/compra-online.cy.js` | Butacas → carrito → checkout (MercadoPago simulado) y butacas vendidas bloqueadas |
| `turnos/crear-turnos.cy.js` | Formulario de alta de turnos |

Usuarios de prueba (contraseña `Prueba123!`): `admin_test`, `vfunciones_test`,
`vproductos_test`, `cliente_test`, `cliente2_test` (ver `cypress/fixtures/usuarios.json`).

Si `php` no está en el PATH, definí `PHP_BIN` (ej. `C:\xampp\php\php.exe`) antes de
correr Cypress.

## Estructura del proyecto

```
Router.php              Despachador y control de acceso (las rutas se registran en public/index.php)
controllers/             Un controlador estático por recurso (Controllers\*)
models/                  Modelos ActiveRecord sobre mysqli (Models\*)
classes/                 Servicios transversales: email, PDF, gráficos, notificaciones, paginación
middlewares/              Control de acceso por módulo (Middlewares\ValidarModulo)
views/                   Vistas PHP planas, organizadas por rol (administrador/cliente/vendedor/...)
includes/                Bootstrap de la app (app.php, database.php, funciones.php, config/)
public/                  Document root: index.php, assets (css/js/img), uploads
scripts/                 Mantenimiento (cron), prueba rápida e instalador de tareas de Windows
tests/                   Base de prueba: preparador, servidor de pruebas y datos (tests/db/)
cypress/                 Tests E2E
logs/                    Logs locales (cron, errores en producción); no se versionan
cinfsa1_schema.sql       Esquema de la base de datos (sin datos)
```

Para el detalle de la arquitectura (flujo de request, control de acceso por
módulos, convenciones de controladores/modelos/vistas), ver [`CLAUDE.md`](./CLAUDE.md).

## Scripts de mantenimiento

Tienen que correr cada 15 minutos:

| Script | Qué hace |
|---|---|
| `scripts/expirar_entradas_cron.php` | Marca como expiradas las entradas de funciones que ya terminaron |
| `scripts/limpiar_reservas_expiradas.php` | Borra reservas temporales de butacas vencidas |

**Windows (XAMPP):** en PowerShell abierto **como Administrador**:

```powershell
powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\CINFSA1\scripts\instalar_tareas_windows.ps1
```

Registra las dos tareas ejecutándolas con `php.exe` (Windows no sabe abrir un `.php`
por sí solo) y guarda la salida en `logs/cron.log`, donde se puede confirmar que corren.

**Linux (cron):**

```cron
*/15 * * * * php /ruta/al/proyecto/scripts/expirar_entradas_cron.php >> /ruta/al/proyecto/logs/cron.log 2>&1
*/15 * * * * php /ruta/al/proyecto/scripts/limpiar_reservas_expiradas.php >> /ruta/al/proyecto/logs/cron.log 2>&1
```
