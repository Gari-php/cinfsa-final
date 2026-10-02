<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/app.php';




use Controllers\LoginController;
use Controllers\SexoController;
use Controllers\UsuarioController;
use Controllers\AdministradorController;
use Controllers\FuncionController;
use Controllers\SalaController;
use Controllers\PeliculaController;
use Controllers\RegistroController;
use Controllers\TurnoController;
use Controllers\TipoEntradaController;
use Controllers\ProductoController;
use Controllers\EntradaController;
use Controllers\StockController;
use Controllers\FichaController;
use Controllers\MaquinaController;
use Controllers\GeneroPeliculaController;
use Controllers\CantinaController;
use Controllers\EstadoPeliculaController;
use Controllers\CarritoController;
use Controllers\ClienteController;
use Controllers\NotificacionesController;
use Controllers\ButacaController;
use Controllers\PagoController;
use Controllers\CajaController;
use Controllers\VentaFuncionesController;
use Controllers\MovimientosController;
use Controllers\VentasConsultaController;
use Controllers\VentasConsultaControllerP;
use Controllers\ActividadCajasController;
use Controllers\ModulosController;
use Controllers\VendedorProductosController;
use Controllers\MovimientosWebController;
use Controllers\PerfilController;
use Controllers\ProveedorController;
use Controllers\ServicioController;
USE Controllers\SoporteController;
use Controllers\GastosController;
use Controllers\ContactoController;
use Controllers\AuditoriaController;
use MVC\Router;

$router= new Router();

//agregado provisoriamente para probar, SECCION DE ENTRADAS
$router->post('/administrador/entradas/expirar-automaticamente', [EntradaController::class, 'expirarAutomaticamente']);

// rutas de notificaciones
$router->get('/api/notificaciones/obtener', [NotificacionesController::class, 'obtener']);
$router->get('/api/notificaciones/contar', [NotificacionesController::class, 'contarNoLeidas']);
$router->post('/api/notificaciones/marcar-leida', [NotificacionesController::class, 'marcarLeida']);
$router->post('/api/notificaciones/marcar-todas-leidas', [NotificacionesController::class, 'marcarTodasLeidas']);
$router->post('/api/notificaciones/verificar-funciones-vencidas', [NotificacionesController::class, 'verificarFuncionesVencidas']);


// RUTAS DE CONTACTO
$router->post('/contacto/enviar', [ContactoController::class, 'enviar']);


// API Carrito
$router->get('/api/carrito/contar', [CarritoController::class, 'contarItems']);
$router->get('/api/carrito/items', [CarritoController::class, 'obtenerItems']); // NUEVA

$router->get('/butacas', [ClienteController::class, 'verButacasFuncion']);
$router->get('/api/butacas/funcion', [ClienteController::class, 'apiObtenerButacasFuncion']);
$router->post('/api/butacas/reservar', [ClienteController::class, 'apiReservarButacas']);


// rutas de api funciones 
$router->get('/api/funciones/por-dia', [ClienteController::class, 'apiObtenerPorDia']);
$router->post('/api/funciones/por-dia', [ClienteController::class, 'apiObtenerPorDia']);

$router->get('/administrador/notificaciones', [NotificacionesController::class, 'index']);
// inicio de secion
$router->get('/', [LoginController::class, 'login']);
$router->post('/', [LoginController::class, 'login']);
$router->get('/logaut', [LoginController::class, 'logaut']);
// Rutas existentes del menu (actualizar si es necesario):
$router->get('/menu', [ClienteController::class, 'menu']);
$router->post('/menu', [ClienteController::class, 'menu']);


// Secciones específicas del cliente
$router->post('/api/obtener-trailer', [ClienteController::class, 'obtenerTrailer']);
$router->get('/funciones', [ClienteController::class, 'funciones']);
$router->post('/funciones', [ClienteController::class, 'funciones']);
$router->get('/funciones/dia', [ClienteController::class, 'funcionesPorDia']);
$router->get('/cantina', [ClienteController::class, 'cantina']);
$router->post('/cantina', [ClienteController::class, 'cantina']);
$router->get('/juegos', [ClienteController::class, 'salaJuegos']);
$router->post('/juegos', [ClienteController::class, 'salaJuegos']);
$router->get('/peliculas', [ClienteController::class, 'peliculas']);
$router->post('/peliculas', [ClienteController::class, 'peliculas']);
$router->get('/perfil', [ClienteController::class, 'mi_perfil']);
$router->post('/perfil/actualizar', [ClienteController::class, 'actualizar_perfil']);
$router->post('/perfil/cambiar-password', [ClienteController::class, 'cambiarPassword']);
$router->get('/perfil/mis-compras', [ClienteController::class, 'misCompras']);

//calificar las peliculas
$router->get('/calificar-peliculas', [ClienteController::class, 'calificarPeliculas']);
$router->post('/calificar-peliculas/guardar', [ClienteController::class, 'guardarResena']);
$router->get('/calificacion-peliculas', [ClienteController::class, 'verCalificaciones']);



// Si también quieres que los administradores puedan obtener tráilers
$router->post('/administrador/api/obtener-trailer', [PeliculaController::class, 'obtenerTrailer']);
// Nuevas rutas del carrito:
$router->post('/carrito/agregar', [CarritoController::class, 'agregar']);
$router->post('/carrito/actualizar', [CarritoController::class, 'actualizar']);
$router->post('/carrito/eliminar', [CarritoController::class, 'eliminar']);
$router->post('/carrito/vaciar', [CarritoController::class, 'vaciar']);
$router->get('/carrito/checkout', [CarritoController::class, 'checkout']); // NUEVA LÍNEA
$router->get('/carrito/retorno', [CarritoController::class, 'retorno']);

// Cliente - Pago
$router->post('/pago/crear-orden', [PagoController::class, 'crearOrden']);
$router->get('/pago/exitoso', [PagoController::class, 'exitoso']);
$router->get('/pago/fallido', [PagoController::class, 'fallido']);
$router->get('/pago/pendiente', [PagoController::class, 'pendiente']);

// Recuperar Password
$router->get('/olvide',[LoginController::class, 'olvide']);
$router->post('/olvide',[LoginController::class, 'olvide']);

//Crear Cuenta
$router->get('/crear-cuenta',[RegistroController::class, 'crear']);
$router->post('/crear-cuenta',[RegistroController::class, 'crear']);

//Confirmar cuenta
$router->get( '/confirmar-cuenta',[LoginController::class,'confirmar']);
// REStablebecr contraseña 
$router->get('/restablecer', [LoginController::class, 'restablecer']);
$router->post('/restablecer', [LoginController::class, 'restablecer']);


// ==============================================
// RUTAS DEL ADMINISTRADOR - MODULARIZADAS
// ==============================================

$router->get('/administrador', [AdministradorController::class, 'index']);
$router->post('/administrador/perfil/actualizar-foto', [AdministradorController::class, 'actualizarFoto']);

// ==============================================
// RUTAS DE tabla sexo
// ==============================================
$router->get('/administrador/sexo/listado', [SexoController::class, 'index']);
$router->get('/administrador/sexo/crear', [SexoController::class, 'crear']);
$router->post('/administrador/sexo/guardar', [SexoController::class, 'guardar']);
$router->get('/administrador/sexo/editar', [SexoController::class, 'editar']);
$router->post('/administrador/sexo/actualizar', [SexoController::class, 'actualizar']);
$router->post('/administrador/sexo/buscar', [SexoController::class, 'buscar']);
$router->get('/administrador/sexo/exportar', [SexoController::class, 'exportar']);


// ==============================================
// RUTAS DE USUARIOS
// ==============================================
$router->get('/administrador/usuarios/listado', [UsuarioController::class, 'index']);
$router->get('/administrador/usuarios/crear', [UsuarioController::class, 'crear']);
$router->post('/administrador/usuarios/guardar', [UsuarioController::class, 'guardar']);
$router->get('/administrador/usuarios/editar', [UsuarioController::class, 'editar']);
$router->post('/administrador/usuarios/actualizar', [UsuarioController::class, 'actualizar']);
$router->post('/administrador/usuarios/eliminar', [UsuarioController::class, 'eliminar']);
$router->post('/administrador/usuarios/buscar', [UsuarioController::class, 'buscar']);
$router->get('/administrador/usuarios/exportar', [UsuarioController::class, 'exportar']);
$router->get('/administrador/usuarios/reportes', [UsuarioController::class, 'reportes']);
$router->get('/administrador/usuarios/reportes/api-sexo', [UsuarioController::class, 'apiDatosSexo']);

// ==============================================
// RUTAS DE PELÍCULAS
// ==============================================
$router->get('/administrador/peliculas/listado', [PeliculaController::class, 'index']);
$router->get('/administrador/peliculas/crear', [PeliculaController::class, 'crear']);
$router->post('/administrador/peliculas/guardar', [PeliculaController::class, 'guardar']);
$router->get('/administrador/peliculas/editar', [PeliculaController::class, 'editar']);
$router->post('/administrador/peliculas/actualizar', [PeliculaController::class, 'actualizar']);
$router->post('/administrador/peliculas/eliminar', [PeliculaController::class, 'eliminar']);
$router->post('/administrador/peliculas/buscar', [PeliculaController::class, 'buscar']);
$router->post('/administrador/peliculas/obtener-imagen', [PeliculaController::class, 'obtenerImagen']);
$router->get('/administrador/peliculas/exportar', [PeliculaController::class, 'exportar']);


// ==============================================
// RUTAS DE SALAS
// ==============================================
$router->get('/administrador/salas/listado', [SalaController::class, 'index']);
$router->get('/administrador/salas/crear', [SalaController::class, 'crear']);
$router->post('/administrador/salas/guardar', [SalaController::class, 'guardar']);
$router->get('/administrador/salas/editar', [SalaController::class, 'editar']);
$router->post('/administrador/salas/actualizar', [SalaController::class, 'actualizar']);
$router->post('/administrador/salas/eliminar', [SalaController::class, 'eliminar']);
$router->post('/administrador/salas/buscar', [SalaController::class, 'buscar']);
$router->get('/administrador/salas/exportar', [SalaController::class, 'exportar']);
$router->get('/administrador/salas/reportes', [SalaController::class, 'reportes']);
$router->get('/administrador/salas/reportes/api-datos', [SalaController::class, 'apiDatosReportes']);


// ==============================================
// RUTAS DE FUNCIONES
// ==============================================
$router->get('/administrador/funciones/exportar', [FuncionController::class, 'exportar']);
$router->get('/administrador/funciones/listado', [FuncionController::class, 'index']);
$router->get('/administrador/funciones/crear', [FuncionController::class, 'crear']);
$router->post('/administrador/funciones/guardar', [FuncionController::class, 'guardar']);
$router->get('/administrador/funciones/editar', [FuncionController::class, 'editar']);
$router->post('/administrador/funciones/actualizar', [FuncionController::class, 'actualizar']);
$router->post('/administrador/funciones/eliminar', [FuncionController::class, 'eliminar']);
$router->post('/administrador/funciones/buscar', [FuncionController::class, 'buscar']);
$router->post('/administrador/funciones/info-pelicula', [FuncionController::class, 'obtenerInfoPelicula']);
$router->get('/administrador/funciones/reportes', [FuncionController::class, 'reportes']);
$router->get('/administrador/funciones/reportes/api-datos', [FuncionController::class, 'apiDatosReportes']);
$router->post('/administrador/funciones/guardar-masivo', [FuncionController::class, 'guardarMasivo']);


// ==============================================
// RUTAS DE Turnos
// ==============================================
$router->get('/administrador/turnos/listado', [TurnoController::class, 'index']);
$router->get('/administrador/turnos/crear', [TurnoController::class, 'crear']);
$router->post('/administrador/turnos/guardar', [TurnoController::class, 'guardar']);
$router->get('/administrador/turnos/editar', [TurnoController::class, 'editar']);
$router->post('/administrador/turnos/actualizar', [TurnoController::class, 'actualizar']);
$router->post('/administrador/turnos/eliminar', [TurnoController::class, 'eliminar']);
$router->post('/administrador/turnos/buscar', [TurnoController::class, 'buscar']);
$router->get('/administrador/turnos/exportar', [TurnoController::class, 'exportar']);
$router->get('/administrador/turnos/reportes', [TurnoController::class, 'reportes']);
$router->get('/administrador/turnos/reportes/api-datos', [TurnoController::class, 'apiDatosReportes']);


// ==============================================
// RUTAS DE TIPO ENTRADA
// ==============================================
$router->get('/administrador/tipos_entradas/listado', [TipoEntradaController::class, 'index']);
$router->get('/administrador/tipos_entradas/crear', [TipoEntradaController::class, 'crear']);
$router->post('/administrador/tipos_entradas/guardar', [TipoEntradaController::class, 'guardar']);
$router->get('/administrador/tipos_entradas/editar', [TipoEntradaController::class, 'editar']);
$router->post('/administrador/tipos_entradas/actualizar', [TipoEntradaController::class, 'actualizar']);
$router->post('/administrador/tipos_entradas/eliminar', [TipoEntradaController::class, 'eliminar']);
$router->post('/administrador/tipos_entradas/buscar', [TipoEntradaController::class, 'buscar']);
$router->get('/administrador/tipos_entradas/exportar', [TipoEntradaController::class, 'exportar']);
$router->get('/administrador/entradas/detalle', [EntradaController::class, 'detalle']);

// ==============================================
// RUTAS DE TIPO ENTRADA
// ==============================================
$router->get('/administrador/entradas/exportar', [EntradaController::class, 'exportar']);
$router->get('/administrador/entradas/listado', [EntradaController::class, 'index']);
$router->post('/administrador/entradas/guardar', [EntradaController::class, 'guardar']);
$router->post('/administrador/entradas/eliminar', [EntradaController::class, 'eliminar']);
$router->post('/administrador/entradas/buscar', [EntradaController::class, 'buscar']);
$router->post('/administrador/entradas/usar', [EntradaController::class, 'usar']);
$router->post('/administrador/entradas/restaurar', [EntradaController::class, 'restaurar']);
$router->post('/administrador/entradas/expirar', [EntradaController::class, 'expirarAutomaticamente']);
// Reportes de entradas (ADMINISTRADOR)
$router->get('/administrador/entradas/reportes', [EntradaController::class, 'reportes']);
$router->get('/administrador/entradas/datos-grafico', [EntradaController::class, 'obtenerDatosGrafico']);
//$router->post('/api/validar-qr', [EntradaController::class, 'validarQR']);
// ==============================================
// RUTAS DE Productos
// ==============================================
$router->get('/administrador/productos/listado', [ProductoController::class, 'index']);
$router->get('/administrador/productos/crear', [ProductoController::class, 'crear']);
$router->post('/administrador/productos/guardar', [ProductoController::class, 'guardar']);
$router->get('/administrador/productos/editar', [ProductoController::class, 'editar']);
$router->post('/administrador/productos/actualizar', [ProductoController::class, 'actualizar']);
$router->post('/administrador/productos/eliminar', [ProductoController::class, 'eliminar']);
$router->post('/administrador/productos/buscar', [ProductoController::class, 'buscar']);
$router->get('/administrador/productos/reportes', [ProductoController::class, 'reportes']);
$router->get('/administrador/productos/exportar', [ProductoController::class, 'exportar']);

// ==============================================
// RUTAS DE Stock
// ==============================================
$router->get('/administrador/stock/listado', [StockController::class, 'index']);
$router->get('/administrador/stock/crear', [StockController::class, 'crear']);
$router->post('/administrador/stock/guardar', [StockController::class, 'guardar']);
$router->get('/administrador/stock/editar', [StockController::class, 'editar']);
$router->post('/administrador/stock/actualizar', [StockController::class, 'actualizar']);
$router->post('/administrador/stock/eliminar', [StockController::class, 'eliminar']);
$router->post('/administrador/stock/buscar', [StockController::class, 'buscar']);
$router->get('/administrador/stock/exportar', [StockController::class, 'exportar']);
$router->get('/administrador/stock/productos-por-cantina', [StockController::class, 'apiProductosPorCantina']);

// ==============================================
// RUTAS DE fichas
// ==============================================
$router->get('/administrador/fichas/listado', [FichaController::class, 'index']);
$router->get('/administrador/fichas/crear', [FichaController::class, 'crear']);
$router->post('/administrador/fichas/guardar', [FichaController::class, 'guardar']);
$router->get('/administrador/fichas/editar', [FichaController::class, 'editar']);
$router->post('/administrador/fichas/actualizar', [FichaController::class, 'actualizar']);
$router->post('/administrador/fichas/buscar', [FichaController::class, 'buscar']);

// ==============================================
// RUTAS De maquinas
// ==============================================
$router->get('/administrador/maquinas/listado', [MaquinaController::class, 'index']);
$router->get('/administrador/maquinas/crear', [MaquinaController::class, 'crear']);
$router->post('/administrador/maquinas/guardar', [MaquinaController::class, 'guardar']);
$router->get('/administrador/maquinas/editar', [MaquinaController::class, 'editar']);
$router->post('/administrador/maquinas/actualizar', [MaquinaController::class, 'actualizar']);
$router->post('/administrador/maquinas/eliminar', [MaquinaController::class, 'eliminar']);
$router->post('/administrador/maquinas/buscar', [MaquinaController::class, 'buscar']);
$router->get('/administrador/maquinas/exportar', [MaquinaController::class, 'exportar']);

// ==============================================
// RUTAS DE Stock
// ==============================================
$router->get('/administrador/generos/listado', [GeneroPeliculaController::class, 'index']);
$router->get('/administrador/generos/crear', [GeneroPeliculaController::class, 'crear']);
$router->post('/administrador/generos/guardar', [GeneroPeliculaController::class, 'guardar']);
$router->get('/administrador/generos/editar', [GeneroPeliculaController::class, 'editar']);
$router->post('/administrador/generos/actualizar', [GeneroPeliculaController::class, 'actualizar']);
$router->post('/administrador/generos/eliminar', [GeneroPeliculaController::class, 'eliminar']);
$router->post('/administrador/generos/buscar', [GeneroPeliculaController::class, 'buscar']);

// ==============================================
// RUTAS DE GASTOS
// ==============================================
$router->get('/administrador/gastos/listado', [GastosController::class, 'index']);
$router->get('/administrador/gastos/crear', [GastosController::class, 'crear']);
$router->post('/administrador/gastos/guardar', [GastosController::class, 'guardar']);
$router->get('/administrador/gastos/editar', [GastosController::class, 'editar']);
$router->post('/administrador/gastos/actualizar', [GastosController::class, 'actualizar']);
$router->post('/administrador/gastos/eliminar', [GastosController::class, 'eliminar']);
$router->post('/administrador/gastos/buscar', [GastosController::class, 'buscar']);
$router->get('/administrador/gastos/exportar', [GastosController::class, 'exportar']);

// ==============================================
// RUTAS DE la Cantina
// ==============================================
$router->get('/administrador/cantina/listado', [CantinaController::class, 'index']);
$router->get('/administrador/cantina/crear', [CantinaController::class, 'crear']);
$router->post('/administrador/cantina/guardar', [CantinaController::class, 'guardar']);
$router->get('/administrador/cantina/editar', [CantinaController::class, 'editar']);
$router->post('/administrador/cantina/actualizar', [CantinaController::class, 'actualizar']);
$router->post('/administrador/cantina/buscar', [CantinaController::class, 'buscar']);
$router->get('/administrador/cantina/contenido', [CantinaController::class, 'verContenido']);
//reportes graficos de la cantina
$router->get('/administrador/cantina/reportes', [ProductoController::class, 'reportes']);
$router->get('/administrador/cantina/datos-grafico', [ProductoController::class, 'obtenerDatosGrafico']);

// ==============================================
// RUTAS DE Estados de Películas
// ==============================================
$router->get('/administrador/estados_peliculas/listado', [EstadoPeliculaController::class, 'index']);
$router->get('/administrador/estados_peliculas/crear', [EstadoPeliculaController::class, 'crear']);
$router->post('/administrador/estados_peliculas/guardar', [EstadoPeliculaController::class, 'guardar']);
$router->get('/administrador/estados_peliculas/editar', [EstadoPeliculaController::class, 'editar']);
$router->post('/administrador/estados_peliculas/actualizar', [EstadoPeliculaController::class, 'actualizar']);
$router->post('/administrador/estados_peliculas/eliminar', [EstadoPeliculaController::class, 'eliminar']);
$router->post('/administrador/estados_peliculas/buscar', [EstadoPeliculaController::class, 'buscar']);

// ==============================================
// RUTAS DE ACTIVIDAD DE CAJA
// ==============================================
$router->get('/administrador/actividadcajas/monitor', [ActividadCajasController::class, 'monitor']);
$router->post('/administrador/actividadcajas/consultar', [ActividadCajasController::class, 'consultarHistorial']);
$router->get('/administrador/actividadcajas/detalle', [ActividadCajasController::class, 'verDetalle']);
$router->post('/administrador/actividadcajas/forzar-cierre', [ActividadCajasController::class, 'forzarCierre']);

// ==============================================
// RUTAS DE BUTACAS  
// ==============================================
$router->get('/administrador/butacas/gestion', [ButacaController::class, 'gestionSalas']);
$router->get('/administrador/butacas/sala', [ButacaController::class, 'verMapaSala']);
$router->post('/administrador/butacas/cambiar-estado', [ButacaController::class, 'cambiarEstadoButaca']);
$router->post('/administrador/butacas/generar', [ButacaController::class, 'generarButacas']);
$router->get('/administrador/butacas/layout-funcion', [ButacaController::class, 'layoutPorFuncion']);


// Gestión de módulos
$router->get('/administrador/modulos/asignar', [ModulosController::class, 'asignar']);
$router->post('/administrador/modulos/asignar', [ModulosController::class, 'asignar']);
$router->get('/administrador/modulos/modulosPerfil', [ModulosController::class, 'modulosPerfil']);


// RUTAS CAJAS
$router->get('/vendedor/caja', [CajaController::class, 'index']);
$router->get('/vendedor/caja/abrir', [CajaController::class, 'vistaAbrir']);
$router->post('/vendedor/caja/abrir', [CajaController::class, 'abrirCaja']);
$router->get('/vendedor/caja/estado', [CajaController::class, 'estado']);
$router->post('/vendedor/caja/cerrar', [CajaController::class, 'cerrarCaja']);
$router->get('/api/caja/verificar', [CajaController::class, 'apiVerificarEstado']);
$router->get('/api/caja/resumen-cierre', [CajaController::class, 'apiResumenCierre']);


// Vista principal de consulta
$router->get('/vendedor/ventas/consulta', [VentasConsultaController::class, 'index']);
$router->get('/api/ventas/vendedores', [VentasConsultaController::class, 'apiObtenerVendedores']);
$router->post('/api/ventas/consultar', [VentasConsultaController::class, 'apiConsultarVentas']);

//consulta de ventas de productos
// Ventas////////////////////////////////////////////////////////////////////
////////////////////
$router->get('/ventas/consulta/api/vendedores', [VentasConsultaControllerP::class, 'apiObtenerVendedores']);
$router->post('/ventas/consulta/api/consultar', [VentasConsultaControllerP::class, 'apiConsultarVentas']);
//devoluciones
$router->post('/ventas/consulta/api/devolucion', [VentasConsultaControllerP::class, 'apiProcesarDevolucion']);


// Productos
$router->post('/vendedorproductos/buscar-ficha', [VendedorProductosController::class, 'buscarFicha']);


//  GESTIÓN DE INGRESOS/EGRESOS 
$router->get('/api/movimientos/proveedores', [MovimientosController::class, 'apiObtenerProveedores']);
$router->get('/api/movimientos/servicios', [MovimientosController::class, 'apiObtenerServicios']);
$router->get('/vendedor/movimientos', [MovimientosController::class, 'index']);
$router->post('/api/movimientos/ingreso', [MovimientosController::class, 'apiRegistrarIngreso']);
$router->post('/api/movimientos/egreso', [MovimientosController::class, 'apiRegistrarEgreso']);
$router->post('/api/movimientos/eliminar', [MovimientosController::class, 'apiEliminarMovimiento']);
$router->get('/api/movimientos/formas-pago', [MovimientosController::class, 'apiObtenerFormasPago']);

// ==============================================
// RUTAS DE VENTAS DE FUNCIONES (Vendedor Interno)
// ==============================================

$router->get('/vendedor/funciones/listado', [VentaFuncionesController::class, 'index']);
$router->post('/vendedor/funciones/buscar', [VentaFuncionesController::class, 'buscar']);
$router->get('/vendedor/funciones/butacas', [VentaFuncionesController::class, 'verButacas']);
$router->get('/api/vendedor/butacas/funcion', [VentaFuncionesController::class, 'apiObtenerButacasFuncion']);
$router->post('/vendedor/funciones/procesar-venta', [VentaFuncionesController::class, 'procesarVenta']);
$router->get('/vendedor/funciones/ticket', [VentaFuncionesController::class, 'verTicket']);
$router->get('/vendedor/obtener-tipos-comprobante', [VentaFuncionesController::class, 'obtenerTiposComprobante']);
$router->post('/vendedor/obtener-tipos-comprobante', [VentaFuncionesController::class, 'obtenerTiposComprobante']);
$router->get('/vendedor/entradas/datos-devolucion', [VentaFuncionesController::class, 'apiDatosEntradaDevolucion']);
$router->post('/vendedor/entradas/procesar-devolucion', [VentaFuncionesController::class, 'procesarDevolucion']);

$router->get('/vendedor/caja/resumen-cierre', [CajaController::class, 'apiResumenCierre']);
$router->get('/vendedor/caja/pdf-arqueo', [CajaController::class, 'generarPDFArqueo']);


// ==============================================
// RUTAS DE CAJA PARA VENDEDOR DE PRODUCTOS
// ==============================================// ===== RUTAS VENDEDOR PRODUCTOS =====
$router->get('/vendedorproductos', [VendedorProductosController::class, 'index']);
$router->get('/vendedorproductos/caja/abrir', [VendedorProductosController::class, 'vistaAbrir']);
$router->post('/vendedorproductos/caja/abrir', [VendedorProductosController::class, 'abrirCaja']);
$router->get('/vendedorproductos/caja/estado', [VendedorProductosController::class, 'estado']);
$router->post('/vendedorproductos/caja/cerrar', [VendedorProductosController::class, 'cerrarCaja']);

// API DE CAJA
$router->get('/vendedorproductos/api/resumen-cierre', [VendedorProductosController::class, 'apiResumenCierre']);
$router->get('/vendedorproductos/caja/pdf-arqueo', [VendedorProductosController::class, 'generarPDFArqueo']);

// Productos
$router->get('/vendedorproductos/productos/listado', [VendedorProductosController::class, 'listadoProductos']);
$router->post('/vendedorproductos/buscar-producto', [VendedorProductosController::class, 'buscarProducto']);

// Métodos de pago - IMPORTANTE: Esta debe estar aquí
$router->post('/vendedorproductos/obtener-metodos-pago', [VendedorProductosController::class, 'obtenerMetodosPago']);
$router->get('/vendedorproductos/obtener-metodos-pago', [VendedorProductosController::class, 'obtenerMetodosPago']);

// Ventas
$router->post('/vendedorproductos/completar-venta', [VendedorProductosController::class, 'completarVenta']);
$router->get('/vendedorproductos/ventas/ticket', [VendedorProductosController::class, 'verTicket']);
$router->get('/vendedorproductos/ventas/consulta', [VendedorProductosController::class, 'consultaVentas']);

// Movimientos
$router->get('/vendedorproductos/movimientos', [VendedorProductosController::class, 'gestionMovimientos']);
$router->post('/vendedorproductos/movimientos/registrar-ingreso', [VendedorProductosController::class, 'registrarIngreso']);
$router->post('/vendedorproductos/movimientos/registrar-egreso', [VendedorProductosController::class, 'registrarEgreso']);
$router->get('/vendedorproductos/obtener-movimientos', [VendedorProductosController::class, 'obtenerMovimientos']);

// API Vendedores
$router->get('/vendedorproductos/api/vendedores', [VendedorProductosController::class, 'apiObtenerVendedores']);
$router->post('/vendedorproductos/api/consultar-ventas', [VendedorProductosController::class, 'apiConsultarVentas']);
$router->get('/vendedorproductos/obtener-tipos-comprobante', [VendedorProductosController::class, 'obtenerTiposComprobante']);
$router->post('/vendedorproductos/obtener-tipos-comprobante', [VendedorProductosController::class, 'obtenerTiposComprobante']);

// ==========================================
// MOVIMIENTOS WEB - ADMINISTRADOR
// ==========================================
$router->get('/administrador/movimientos-web/exportar', [MovimientosWebController::class, 'exportar']);
$router->get('/administrador/movimientos-web/listado', [MovimientosWebController::class, 'listado']);
$router->get('/administrador/movimientos-web/reportes', [MovimientosWebController::class, 'reportes']);
$router->get('/administrador/movimientos-web/datos-grafico', [MovimientosWebController::class, 'datosGrafico']);
$router->get('/administrador/movimientos-web/detalle', [MovimientosWebController::class, 'detalle']);
$router->post('/administrador/movimientos-web/cancelar', [MovimientosWebController::class, 'cancelar']);
$router->post('/administrador/movimientos-web/buscar', [MovimientosWebController::class, 'buscar']);

// ==========================================
// CONTROL - REGISTRO DE AUDITORÍA (solo lectura)
// ==========================================
$router->get('/administrador/auditoria/listado', [AuditoriaController::class, 'listado']);
$router->get('/administrador/auditoria/exportar', [AuditoriaController::class, 'exportar']);

//RUTAS DE PERFILES
$router->get('/administrador/perfiles/listado', [PerfilController::class, 'index']);
$router->get('/administrador/perfiles/crear', [PerfilController::class, 'crear']);
$router->post('/administrador/perfiles/guardar', [PerfilController::class, 'guardar']);
$router->get('/administrador/perfiles/editar', [PerfilController::class, 'editar']);
$router->post('/administrador/perfiles/actualizar', [PerfilController::class, 'actualizar']);
$router->post('/administrador/perfiles/eliminar', [PerfilController::class, 'eliminar']);
$router->post('/administrador/perfiles/buscar', [PerfilController::class, 'buscar']);
// RUTAS DE PROVEEDORES
$router->get('/administrador/proveedores/listado', [ProveedorController::class, 'index']);
$router->get('/administrador/proveedores/crear', [ProveedorController::class, 'crear']);
$router->post('/administrador/proveedores/guardar', [ProveedorController::class, 'guardar']);
$router->get('/administrador/proveedores/editar', [ProveedorController::class, 'editar']);
$router->post('/administrador/proveedores/actualizar', [ProveedorController::class, 'actualizar']);
$router->post('/administrador/proveedores/eliminar', [ProveedorController::class, 'eliminar']);
$router->post('/administrador/proveedores/buscar', [ProveedorController::class, 'buscar']);

// RUTAS DE SERVICIOS
$router->get('/administrador/servicios/listado', [ServicioController::class, 'index']);
$router->get('/administrador/servicios/crear', [ServicioController::class, 'crear']);
$router->post('/administrador/servicios/guardar', [ServicioController::class, 'guardar']);
$router->get('/administrador/servicios/editar', [ServicioController::class, 'editar']);
$router->post('/administrador/servicios/actualizar', [ServicioController::class, 'actualizar']);
$router->post('/administrador/servicios/eliminar', [ServicioController::class, 'eliminar']);
$router->post('/administrador/servicios/buscar', [ServicioController::class, 'buscar']);

// RUTAS DE SOPORTE 
$router->get('/soporte', [SoporteController::class, 'formulario']);
$router->post('/soporte/enviar', [SoporteController::class, 'enviar']);




// Comprueba y valida las rutas, que existan y les asigna las funciones del Controlador
$router->comprobarRutas();