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
5. Copiá `.env.example` a `.env` y completá tus credenciales:
   ```bash
   cp .env.example .env
   ```
   Variables necesarias: conexión a MySQL, API key de SendGrid (envío de emails),
   credenciales de MercadoPago (cobros) y de Pusher (notificaciones en tiempo real
   dentro del panel de administrador). El sistema funciona sin estas tres últimas,
   pero esas funcionalidades puntuales quedarán deshabilitadas hasta configurarlas.
6. Levantá el proyecto con XAMPP (Apache apuntando a la carpeta del repo) o con
   el servidor embebido de PHP:
   ```bash
   php -S localhost:8000 -t public
   ```

## Tests (Cypress)

```bash
npx cypress open        # modo interactivo
npx cypress run         # modo headless
```

`cypress.config.js` usa `baseUrl: http://localhost:3000`, así que el servidor debe
estar corriendo en ese puerto al ejecutar los tests (por ejemplo `php -S localhost:3000 -t public`).

## Estructura del proyecto

```
Router.php              Registro de todas las rutas + despachador (front controller)
controllers/             Un controlador estático por recurso (Controllers\*)
models/                  Modelos ActiveRecord sobre mysqli (Models\*)
classes/                 Servicios transversales: email, PDF, gráficos, notificaciones, paginación
middlewares/              Control de acceso por módulo (Middlewares\ValidarModulo)
views/                   Vistas PHP planas, organizadas por rol (administrador/cliente/vendedor/...)
includes/                Bootstrap de la app (app.php, database.php, funciones.php, config/)
public/                  Document root: index.php, assets (css/js/img), uploads
scripts/                 Scripts de mantenimiento (cron): expirar entradas, limpiar reservas
cypress/                 Tests E2E
cinfsa1_schema.sql       Esquema de la base de datos (sin datos)
```

Para el detalle de la arquitectura (flujo de request, control de acceso por
módulos, convenciones de controladores/modelos/vistas), ver [`CLAUDE.md`](./CLAUDE.md).

## Scripts de mantenimiento

Pensados para correrse por cron / tarea programada:

```bash
php scripts/expirar_entradas_cron.php
php scripts/limpiar_reservas_expiradas.php
```
