# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

CINFSA — a cinema management system (PHP, custom MVC, no framework). Handles movie listings/showtimes, seat maps, cart & checkout (MercadoPago), tickets/entries with QR, concession stand (cantina) sales, cash-register (caja) operations for two vendor roles, expense/income tracking, and an admin back office. Served from XAMPP; DB is MySQL (`cinfsa1`).

## Commands

There is no build step (PHP is served directly by Apache/XAMPP) and no PHP test suite or linter configured.

- Install PHP deps: `composer install`
- Install JS deps (Cypress only): `npm install`
- Quick smoke test (run before committing): `php scripts/prueba_rapida.php` (`npm run test:rapido`) — checks every route maps to an existing method, logs in as each role against the test DB and GETs every route, failing on any PHP error/warning/deprecation.
- E2E tests: start `npm run test:servidor` (php -S on :3000 via `tests/servidor_pruebas.php`, forced to the test DB), then `npm run test:e2e` / `npx cypress open`. Single spec: `npx cypress run --spec "cypress/e2e/caja/caja-funciones.cy.js"`.
- Tests never touch the real DB: they use `cinfsa1_test`, rebuilt from `cinfsa1_schema.sql` + `tests/db/datos_prueba.sql` by `php tests/preparar_base_pruebas.php` (Cypress task `prepararBase`, run before each spec). Test users (password `Prueba123!`) live in `cypress/fixtures/usuarios.json`. `cy.task('consultarBase', sql)` runs a read-only SELECT for assertions; `cy.loginComo(rol)` / `cy.postJson(url, body)` handle login and CSRF.
- When the DB schema changes, regenerate `cinfsa1_schema.sql` (`mysqldump --no-data --skip-dump-date --databases cinfsa1`, strip `AUTO_INCREMENT=`), or the test DB will drift from the real one.
- Maintenance scripts (every 15 min): `php scripts/expirar_entradas_cron.php`, `php scripts/limpiar_reservas_expiradas.php`. On Windows register them with `scripts/instalar_tareas_windows.ps1` (as Administrator); output goes to `logs/cron.log`.

## Architecture

Custom front-controller MVC, autoloaded via Composer PSR-4 (see `composer.json`): `MVC\` → `./`, `Controllers\` → `./controllers`, `Models\` → `./models`, `Classes\` → `./classes`, `Middlewares\` → `./middlewares`.

**Request flow**: `public/index.php` loads the Composer autoloader and `includes/app.php` (which pulls in `includes/funciones.php`, `includes/database.php`, sets the timezone, and wires the mysqli connection into `Models\ActiveRecord`), then `Router.php` registers every route with `$router->get(...)` / `$router->post(...)` and finally calls `$router->comprobarRutas()`. All routes live in one flat list in `Router.php` — there is no per-controller route file.

**Router (`MVC\Router`, at repo root)**:
- `comprobarRutas()` starts the session, resolves `$_SERVER['REQUEST_URI']`, checks access via `validarAccesoConModulos()`, then dispatches to the matching `getRoutes`/`postRoutes` callback or a 404.
- Access control is two-layered: a hardcoded `$rutasPublicas` allowlist (no login needed), then a `$mapaRutasModulos` table mapping URL prefixes to named modules (`ADMINISTRADOR`, `VENTA_FUNCIONES`, `GESTION_CAJA`, `VENTA_PRODUCTOS`). Module access itself is checked against the DB by `Middlewares\ValidarModulo::tiene()`, driven by `$_SESSION['perfil']` (profile 3 = admin, always allowed) and the `modulo_x_tipos_de_usuarios` / `modulos` tables. When adding a new admin/vendor route, add it both to `Router.php`'s route list and to the relevant prefix array in `obtenerMapaRutasModulos()`, or it will be treated as "login required, no specific module."
- `comprobarRutas()` wraps the whole response in an output handler (`marcarRespuestaJson`): if the body is valid JSON and no `Content-Type` was set, it sends `application/json`. Controllers may still set the header themselves (files like PDF/Excel keep theirs), but a missing header no longer makes JSON go out as `text/html`.
- `render($view, $datos)` extracts `$datos` into variables, buffers the view (`views/$view.php`), then wraps it in a layout selected by the view's path prefix: `cliente/` → `layout_cliente.php`, `administrador/` → `layout_administrador.php`, `vendedor/` and `vendedorproductos/` → `layout_vendedor.php`, else → `layout.php`.

**Models (`models/`)**: all extend `Models\ActiveRecord`, a lightweight ActiveRecord implementation (`models/ActiveRecord.php`) built directly on `mysqli`. Key conventions:
- Each model declares `protected static $tabla` (table name) and `protected static $columnasDB` (column list, **first entry must be the PK**).
- `crear()`/`actualizar()`/`guardar()`/`eliminar()` build raw SQL strings using `real_escape_string()` — not prepared statements. `find($id)` and `where($col, $val)` return hydrated model instances via `consultarSQL()`.
- Controllers frequently bypass the ActiveRecord helpers entirely and run raw `mysqli` queries/prepared statements directly via `Models\ActiveRecord::getDB()` for anything beyond simple CRUD (joins, aggregates, reports).

**Controllers (`controllers/`)**: static-method classes (namespace `Controllers`), one per resource, matching the CRUD naming used in the routes: `index`, `crear`, `guardar`, `editar`, `actualizar`, `eliminar`, `buscar`, `exportar`, and often `reportes` / `datos-grafico` / `obtenerDatosGrafico` for chart data. Authorization inside a controller is typically a local `verificarAdmin()`/session check at the top of each method (in addition to the router-level module gate) — follow this per-method guard pattern for admin-only actions rather than relying on the router alone.

**Views (`views/`)**: plain PHP templates, organized by role (`administrador/`, `cliente/`, `vendedor/`, `vendedorproductos/`, `auth/`, `carrito/`) plus the four `layout*.php` files described above. Escape all dynamic output with the global `s()` helper (`includes/funciones.php`) — it wraps `htmlspecialchars`.

**Classes (`classes/`)**: cross-cutting services — `Email.php` (SendGrid), `Notificaciones.php`, `ExportadorDatos.php`, `GeneradorArqueoPDF.php` (TCPDF cash-register reports), `GeneradorGraficos.php`, `Paginador.php` (pagination helper used across admin `index()` listers).

**Middlewares (`middlewares/`)**: currently just `ValidarModulo.php`, described above.

**Audit log (`Classes\Auditoria`, admin tab "Control")**: sensitive actions are recorded in the `auditoria` table and listed read-only at `/administrador/auditoria/listado` (`AuditoriaController`, CSV export). When adding a sensitive action (money, prices, users/permissions, cancellations, CRUD of movies/functions), add its code to `Auditoria::ACCIONES` and call `Auditoria::registrar($accion, $descripcion, $entidad, $id, $antes, $despues)` **after** the action succeeded (after `commit()`), with human-readable values (names, not foreign-key ids). `Auditoria::cambios()` keeps only changed fields. It never throws, and it masks any key matching clave/password/contrase/token/secret — so don't name non-secret fields with those words. Never pass passwords or what a user typed in a failed login.

**DB migrations**: schema changes go in `migraciones/AAAA-MM-DD_descripcion.sql` (applied once per database). After applying one locally, regenerate `cinfsa1_schema.sql` so the test DB matches.

**Config**: read settings with the global `env('CLAVE', $defecto)` helper (`includes/funciones.php`), not `$_ENV` directly — `$_ENV` is empty under XAMPP's `variables_order`, so process-level overrides (like the test server's `DB_NAME`) would otherwise be ignored. `APP_ENV=production` hides errors and logs them to `logs/php_errors.log` (`includes/app.php`); `APP_URL` builds email links and MercadoPago back_urls. Unknown routes render `views/errores/404.php` (or JSON for AJAX/`/api/` requests).

**Config/secrets**: `includes/config/mercadopago.php` and `api_keys/llave_cinfsa.php` hold hardcoded API keys/tokens checked into the repo (not `.env`-based) — treat any value in those files as already-committed and not fit for reuse; `.gitignore` only excludes `/vendor/` and `.env`. Some newer classes (`Email.php`, `Notificaciones.php`) read `$_ENV`/`getenv()` (via `vlucas/phpdotenv`) with fallbacks to the hardcoded XAMPP defaults (`root`/no password/`cinfsa1`) — there's no committed `.env.example`.

**DB schema**: `cinfsa1.sql` is the current full dump/schema reference for the MySQL database.
