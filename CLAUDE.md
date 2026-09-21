# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

CINFSA — a cinema management system (PHP, custom MVC, no framework). Handles movie listings/showtimes, seat maps, cart & checkout (MercadoPago), tickets/entries with QR, concession stand (cantina) sales, cash-register (caja) operations for two vendor roles, expense/income tracking, and an admin back office. Served from XAMPP; DB is MySQL (`cinfsa1`).

## Commands

There is no build step (PHP is served directly by Apache/XAMPP) and no PHP test suite or linter configured.

- Install PHP deps: `composer install`
- Install JS deps (Cypress only): `npm install`
- Run Cypress E2E tests (interactive): `npx cypress open`
- Run Cypress E2E tests (headless): `npx cypress run`
- Run a single spec: `npx cypress run --spec "cypress/e2e/turnos/crear-turnos.cy.js"`
- `cypress.config.js` sets `baseUrl: 'http://localhost:3000'` — point a PHP server at the repo root on port 3000 before running tests (e.g. `php -S localhost:3000 -t public`), or via XAMPP with a vhost matching that base URL.
- Cron-style maintenance scripts (run manually or scheduled): `php scripts/expirar_entradas_cron.php`, `php scripts/limpiar_reservas_expiradas.php`.

Note: `cypress/e2e/` currently only has real specs under `turnos/` (the `usuarios/` folder is empty and there's a leftover scaffold at `cypress/cypress/` from `cypress open`'s default project — not real tests).

## Architecture

Custom front-controller MVC, autoloaded via Composer PSR-4 (see `composer.json`): `MVC\` → `./`, `Controllers\` → `./controllers`, `Models\` → `./models`, `Classes\` → `./classes`, `Middlewares\` → `./middlewares`.

**Request flow**: `public/index.php` loads the Composer autoloader and `includes/app.php` (which pulls in `includes/funciones.php`, `includes/database.php`, sets the timezone, and wires the mysqli connection into `Models\ActiveRecord`), then `Router.php` registers every route with `$router->get(...)` / `$router->post(...)` and finally calls `$router->comprobarRutas()`. All routes live in one flat list in `Router.php` — there is no per-controller route file.

**Router (`MVC\Router`, at repo root)**:
- `comprobarRutas()` starts the session, resolves `$_SERVER['REQUEST_URI']`, checks access via `validarAccesoConModulos()`, then dispatches to the matching `getRoutes`/`postRoutes` callback or a 404.
- Access control is two-layered: a hardcoded `$rutasPublicas` allowlist (no login needed), then a `$mapaRutasModulos` table mapping URL prefixes to named modules (`ADMINISTRADOR`, `VENTA_FUNCIONES`, `GESTION_CAJA`, `VENTA_PRODUCTOS`). Module access itself is checked against the DB by `Middlewares\ValidarModulo::tiene()`, driven by `$_SESSION['perfil']` (profile 3 = admin, always allowed) and the `modulo_x_tipos_de_usuarios` / `modulos` tables. When adding a new admin/vendor route, add it both to `Router.php`'s route list and to the relevant prefix array in `obtenerMapaRutasModulos()`, or it will be treated as "login required, no specific module."
- `render($view, $datos)` extracts `$datos` into variables, buffers the view (`views/$view.php`), then wraps it in a layout selected by the view's path prefix: `cliente/` → `layout_cliente.php`, `administrador/` → `layout_administrador.php`, `vendedor/` and `vendedorproductos/` → `layout_vendedor.php`, else → `layout.php`.

**Models (`models/`)**: all extend `Models\ActiveRecord`, a lightweight ActiveRecord implementation (`models/ActiveRecord.php`) built directly on `mysqli`. Key conventions:
- Each model declares `protected static $tabla` (table name) and `protected static $columnasDB` (column list, **first entry must be the PK**).
- `crear()`/`actualizar()`/`guardar()`/`eliminar()` build raw SQL strings using `real_escape_string()` — not prepared statements. `find($id)` and `where($col, $val)` return hydrated model instances via `consultarSQL()`.
- Controllers frequently bypass the ActiveRecord helpers entirely and run raw `mysqli` queries/prepared statements directly via `Models\ActiveRecord::getDB()` for anything beyond simple CRUD (joins, aggregates, reports).

**Controllers (`controllers/`)**: static-method classes (namespace `Controllers`), one per resource, matching the CRUD naming used in the routes: `index`, `crear`, `guardar`, `editar`, `actualizar`, `eliminar`, `buscar`, `exportar`, and often `reportes` / `datos-grafico` / `obtenerDatosGrafico` for chart data. Authorization inside a controller is typically a local `verificarAdmin()`/session check at the top of each method (in addition to the router-level module gate) — follow this per-method guard pattern for admin-only actions rather than relying on the router alone.

**Views (`views/`)**: plain PHP templates, organized by role (`administrador/`, `cliente/`, `vendedor/`, `vendedorproductos/`, `auth/`, `carrito/`) plus the four `layout*.php` files described above. Escape all dynamic output with the global `s()` helper (`includes/funciones.php`) — it wraps `htmlspecialchars`.

**Classes (`classes/`)**: cross-cutting services — `Email.php` (SendGrid), `Notificaciones.php`, `ExportadorDatos.php`, `GeneradorArqueoPDF.php` (TCPDF cash-register reports), `GeneradorGraficos.php`, `Paginador.php` (pagination helper used across admin `index()` listers).

**Middlewares (`middlewares/`)**: currently just `ValidarModulo.php`, described above.

**Config/secrets**: `includes/config/mercadopago.php` and `api_keys/llave_cinfsa.php` hold hardcoded API keys/tokens checked into the repo (not `.env`-based) — treat any value in those files as already-committed and not fit for reuse; `.gitignore` only excludes `/vendor/` and `.env`. Some newer classes (`Email.php`, `Notificaciones.php`) read `$_ENV`/`getenv()` (via `vlucas/phpdotenv`) with fallbacks to the hardcoded XAMPP defaults (`root`/no password/`cinfsa1`) — there's no committed `.env.example`.

**DB schema**: `cinfsa1.sql` is the current full dump/schema reference for the MySQL database.
