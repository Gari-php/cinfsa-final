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
npm run test:e2e        # headless, en Chrome (Electron, el navegador por defecto de Cypress, se colgaba)
npx cypress open        # modo interactivo
```

Cada archivo de tests recrea `cinfsa1_test` antes de empezar. Cubren:

| Spec | Qué verifica |
|---|---|
| `auth/login-roles.cy.js` | Login de cada rol, credenciales inválidas y acceso entre módulos |
| `caja/caja-funciones.cy.js` | Apertura de caja, venta en boletería, cierre y arqueo (con y sin faltante) |
| `compra/compra-online.cy.js` | Butacas → carrito → checkout (MercadoPago simulado) y butacas vendidas bloqueadas |
| `compra/butaca-no-disponible.cy.js` | Una butaca del carrito vendida o reservada por otro: aviso en el checkout y pago rechazado |
| `compra/entrada-cancelada.cy.js` | Cancelar una entrada libera la butaca en esa función; restaurar solo si sigue libre |
| `turnos/crear-turnos.cy.js` | Formulario de alta de turnos |
| `formularios/errores-un-modal.cy.js` | En todas las pantallas de crear/editar del admin, los errores se marcan en su campo y aparecen en un solo modal |
| `control/auditoria.cy.js` | Registro de auditoría: qué acciones se registran, que nunca guarde contraseñas, acceso solo del admin, filtros y exportación |

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
migraciones/             Cambios de estructura de la base, a aplicar una vez en cada base
cinfsa1_schema.sql       Esquema de la base de datos (sin datos)
```

Para el detalle de la arquitectura (flujo de request, control de acceso por
módulos, convenciones de controladores/modelos/vistas), ver [`CLAUDE.md`](./CLAUDE.md).

## Registro de auditoría (Control)

El panel de administración tiene una pestaña **Control → Registro de auditoría**
(`/administrador/auditoria/listado`) que muestra quién hizo cada acción sensible, cuándo y
desde qué IP: inicios de sesión (incluidos los fallidos), cancelaciones y devoluciones,
aperturas y cierres de caja (con la diferencia del arqueo), cambios de precios, altas/bajas/
modificaciones de usuarios, películas y funciones, cambios de permisos y bloqueos de butacas.

- Es de **solo lectura**: desde el sistema nadie puede editar ni borrar registros.
- **Nunca guarda contraseñas** (de un cambio de contraseña solo queda que cambió) ni lo que se
  escribió en un inicio de sesión fallido.
- Se puede filtrar por usuario, acción, fechas y texto, y **exportar a CSV** lo filtrado.

Para registrar una acción nueva: agregarla a `Classes\Auditoria::ACCIONES` y llamar a
`Auditoria::registrar(...)` en el controlador **después** de que la acción se completó bien.

## Migraciones de base de datos

Los cambios de estructura de la base se guardan en `migraciones/`, con fecha en el nombre.
Cada archivo se aplica **una vez** en cada base (la local y la del servidor):

```bash
mysql -u root cinfsa1 < migraciones/2026-10-02_auditoria.sql
```

Después de aplicar una migración en tu base local, regenerá `cinfsa1_schema.sql`
(ver CLAUDE.md) para que la base de prueba también la tenga.

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

## Pasar a producción

Guía para mudar el sistema desde XAMPP a un servidor (hosting) **conservando todos los datos**.

### Requisitos del servidor

- PHP 8.1 o superior, con las extensiones `mysqli`, `curl`, `openssl`, `mbstring` y `json`
  (casi todos los hostings las traen activadas)
- MySQL o MariaDB
- Composer (o subir la carpeta `vendor/` ya instalada)
- HTTPS (MercadoPago lo exige para volver al sitio después del pago)
- Poder programar tareas cada 15 minutos (cron)

### 1. Subir el código

```bash
git clone https://github.com/Gari-php/cinfsa-final.git
cd cinfsa-final
composer install --no-dev
```

**El dominio tiene que apuntar a la carpeta `public/`**, no a la raíz del proyecto. Así no
quedan accesibles desde internet `.env`, `vendor/`, los scripts ni el esquema de la base.
En cPanel se configura en "Dominios" → raíz del documento.

### 2. Llevar la base de datos

En tu PC, exportá la base completa (estructura + datos):

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root --routines --databases cinfsa1 > cinfsa1_completa.sql
```

(o desde phpMyAdmin: base `cinfsa1` → Exportar → Ejecutar).

En el servidor, creá la base y el usuario desde el panel del hosting e importá el archivo
(phpMyAdmin → Importar, o `mysql -u USUARIO -p NOMBRE_BASE < cinfsa1_completa.sql`).
Si el hosting asigna otro nombre a la base, borrá del archivo las líneas `CREATE DATABASE`
y `USE` antes de importarlo.

> ⚠️ `cinfsa1_completa.sql` tiene datos personales de clientes: **no lo subas a GitHub** y
> borralo del servidor después de importarlo.
>
> ⚠️ No importes `cinfsa1_schema.sql` sobre esta base: hace `DROP TABLE` y la deja vacía.
>
> Si exportás tu base local actual, ya incluye todas las migraciones. Si la base del servidor
> viene de una copia más vieja, aplicale los archivos de `migraciones/` que le falten.

Las contraseñas se mudan cifradas (bcrypt) y siguen funcionando. Antes de exportar, conviene
verificar que ningún usuario tenga la contraseña guardada sin cifrar (por ejemplo, editada a
mano en phpMyAdmin): esos usuarios no pueden iniciar sesión.

### 3. Copiar las imágenes subidas

No están ni en la base ni en GitHub. Copiá estas carpetas de tu PC al servidor, en la misma ruta:

```
public/assets/img/peliculas/
public/assets/img/productos/
public/assets/img/perfiles/
public/assets/img/cantina/
public/uploads/
```

El servidor web tiene que poder **escribir** en ellas (para nuevas subidas) y en `logs/`.

### 4. Crear el `.env` del servidor

Partí de `.env.example`. Diferencias con el de tu PC:

```
APP_ENV=production
APP_URL=https://www.TU-DOMINIO.com

DB_HOST=localhost
DB_USER=USUARIO_DEL_HOSTING
DB_PASSWORD=CONTRASEÑA_DE_ESA_BASE
DB_NAME=NOMBRE_DE_LA_BASE

MP_ACCESS_TOKEN=APP_USR-...   # credenciales de PRODUCCIÓN de MercadoPago, no las de prueba
MP_PUBLIC_KEY=APP_USR-...
```

SendGrid y Pusher pueden usar las mismas claves de siempre. Con `APP_ENV=production` los
errores no se muestran en pantalla y quedan en `logs/php_errors.log`.

### 5. Programar las tareas

Configurá en el cron del hosting las dos líneas de
[Scripts de mantenimiento](#scripts-de-mantenimiento), con la ruta real del proyecto.
Si `php` no es el comando correcto, el panel suele indicar la ruta (por ejemplo `/usr/local/bin/php`).

### 6. Verificar

- [ ] El sitio abre en `https://` y muestra la cartelera con imágenes
- [ ] Los administradores y un vendedor pueden iniciar sesión
- [ ] Una página inexistente muestra la página 404 (y no un error de PHP)
- [ ] Llega el mail de "¿Olvidaste tu contraseña?" y su link abre el sitio (confirma `APP_URL` y SendGrid)
- [ ] Una compra real de bajo monto: el pago vuelve al sitio, se asigna la entrada y llega el mail
- [ ] Después de 15 minutos, `logs/cron.log` tiene líneas nuevas
