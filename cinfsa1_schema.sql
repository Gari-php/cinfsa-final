-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cinfsa1
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `cinfsa1`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `cinfsa1` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `cinfsa1`;

--
-- Table structure for table `arqueo_cajas`
--

DROP TABLE IF EXISTS `arqueo_cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `arqueo_cajas` (
  `id_arqueo_caja` int(11) NOT NULL AUTO_INCREMENT,
  `rela_usuario` int(11) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) DEFAULT 0.00,
  `total_ventas` decimal(10,2) DEFAULT 0.00,
  `estado_arqueo` enum('abierto','cerrado') DEFAULT 'abierto',
  `monto_final` decimal(10,2) DEFAULT 0.00,
  `diferencia` decimal(10,2) DEFAULT 0.00,
  `observaciones_cierre` text DEFAULT NULL,
  `rela_caja` int(11) NOT NULL,
  PRIMARY KEY (`id_arqueo_caja`),
  KEY `rela_usuario` (`rela_usuario`),
  KEY `rela_caja` (`rela_caja`),
  CONSTRAINT `arqueo_cajas_ibfk_1` FOREIGN KEY (`rela_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `arqueo_cajas_ibfk_2` FOREIGN KEY (`rela_caja`) REFERENCES `cajas` (`id_caja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `nombre_usuario` varchar(45) DEFAULT NULL,
  `perfil` varchar(45) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `entidad` varchar(50) DEFAULT NULL,
  `id_entidad` int(11) DEFAULT NULL,
  `descripcion` varchar(500) NOT NULL,
  `datos_antes` text DEFAULT NULL,
  `datos_despues` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_fecha` (`fecha`),
  KEY `idx_auditoria_usuario` (`id_usuario`),
  KEY `idx_auditoria_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `butacas`
--

DROP TABLE IF EXISTS `butacas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `butacas` (
  `id_butaca` int(11) NOT NULL AUTO_INCREMENT,
  `fila_butaca` int(11) DEFAULT NULL,
  `numero_butaca` int(11) DEFAULT NULL,
  `rela_salas` int(11) NOT NULL,
  `rela_estado_butaca` int(11) NOT NULL,
  PRIMARY KEY (`id_butaca`),
  KEY `fk_butacas_salas1_idx` (`rela_salas`),
  KEY `fk_butacas_estados_butacas1_idx` (`rela_estado_butaca`),
  CONSTRAINT `fk_butacas_estados_butacas1` FOREIGN KEY (`rela_estado_butaca`) REFERENCES `estados_butacas` (`id_estado_butaca`),
  CONSTRAINT `fk_butacas_salas1` FOREIGN KEY (`rela_salas`) REFERENCES `salas` (`id_sala`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `butacas_vendidas`
--

DROP TABLE IF EXISTS `butacas_vendidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `butacas_vendidas` (
  `id_venta_butaca` int(11) NOT NULL AUTO_INCREMENT,
  `id_butaca` int(11) NOT NULL,
  `id_funcion` int(11) NOT NULL,
  `id_entrada` int(11) DEFAULT NULL,
  `id_orden` int(11) DEFAULT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_venta_butaca`),
  UNIQUE KEY `unique_butaca_funcion` (`id_butaca`,`id_funcion`),
  KEY `id_funcion` (`id_funcion`),
  KEY `id_entrada` (`id_entrada`),
  KEY `fk_butacas_vendidas_orden` (`id_orden`),
  CONSTRAINT `butacas_vendidas_ibfk_1` FOREIGN KEY (`id_butaca`) REFERENCES `butacas` (`id_butaca`) ON DELETE CASCADE,
  CONSTRAINT `butacas_vendidas_ibfk_2` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id_funcion`) ON DELETE CASCADE,
  CONSTRAINT `butacas_vendidas_ibfk_3` FOREIGN KEY (`id_entrada`) REFERENCES `entradas` (`id_entrada`) ON DELETE CASCADE,
  CONSTRAINT `fk_butacas_vendidas_orden` FOREIGN KEY (`id_orden`) REFERENCES `ordenes` (`id_orden`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cabecera_fact_cantina`
--

DROP TABLE IF EXISTS `cabecera_fact_cantina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cabecera_fact_cantina` (
  `id_cabecera_fact_cantin` int(11) NOT NULL AUTO_INCREMENT,
  `rela_cantina` int(11) NOT NULL,
  `rela_arqueo_caja` int(11) DEFAULT NULL,
  `tipo_comprobante` varchar(10) DEFAULT 'TICKET',
  `punto_venta` int(11) DEFAULT 1,
  `numero_ticket` int(11) DEFAULT NULL,
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `rela_tipo_de_pagos` int(11) NOT NULL,
  `monto_total_cantina` float DEFAULT NULL,
  `fecha_hora_pago_cantina` datetime NOT NULL,
  `rela_datos_facturacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_cabecera_fact_cantin`),
  KEY `fk_cabecera_fact_cantina_cantina1_idx` (`rela_cantina`),
  KEY `fk_cabecera_fact_cantina_tipos_de_pagos1_idx` (`rela_tipo_de_pagos`),
  KEY `idx_cabecera_cantina_arqueo` (`rela_arqueo_caja`),
  KEY `idx_cabecera_cantina_comprobante` (`tipo_comprobante`,`punto_venta`,`numero_ticket`),
  KEY `rela_datos_facturacion` (`rela_datos_facturacion`),
  CONSTRAINT `cabecera_fact_cantina_ibfk_1` FOREIGN KEY (`rela_datos_facturacion`) REFERENCES `datos_facturacion` (`id_dato_facturacion`),
  CONSTRAINT `fk_cabecera_cantina_arqueo` FOREIGN KEY (`rela_arqueo_caja`) REFERENCES `arqueo_cajas` (`id_arqueo_caja`),
  CONSTRAINT `fk_cabecera_fact_cantina_cantina1` FOREIGN KEY (`rela_cantina`) REFERENCES `cantina` (`id_cantina`),
  CONSTRAINT `fk_cabecera_fact_cantina_tipos_de_pagos1` FOREIGN KEY (`rela_tipo_de_pagos`) REFERENCES `tipos_de_pagos` (`id_tipo_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cabecera_fact_cine`
--

DROP TABLE IF EXISTS `cabecera_fact_cine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cabecera_fact_cine` (
  `id_pagos` int(11) NOT NULL AUTO_INCREMENT,
  `pago_fecha_hora` datetime DEFAULT NULL,
  `monto_total` float DEFAULT NULL,
  `rela_tipos_de_pagos` int(11) NOT NULL,
  `rela_arqueo_caja` int(11) DEFAULT NULL,
  `rela_usuario_vendedor` int(11) NOT NULL,
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `tipo_comprobante` varchar(10) DEFAULT 'B',
  `punto_venta` int(11) DEFAULT 1,
  `numero_ticket` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_pagos`),
  UNIQUE KEY `unique_ticket` (`tipo_comprobante`,`punto_venta`,`numero_ticket`),
  KEY `fk_pagos_tipos_de_pagos1_idx` (`rela_tipos_de_pagos`),
  KEY `idx_fecha_hora` (`pago_fecha_hora`),
  KEY `idx_arqueo` (`rela_arqueo_caja`),
  KEY `idx_vendedor` (`rela_usuario_vendedor`),
  CONSTRAINT `fk_cabecera_fact_arqueo` FOREIGN KEY (`rela_arqueo_caja`) REFERENCES `arqueo_cajas` (`id_arqueo_caja`),
  CONSTRAINT `fk_cabecera_vendedor` FOREIGN KEY (`rela_usuario_vendedor`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_pagos_tipos_de_pagos1` FOREIGN KEY (`rela_tipos_de_pagos`) REFERENCES `tipos_de_pagos` (`id_tipo_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cajas`
--

DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cajas` (
  `id_caja` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_caja` varchar(20) DEFAULT NULL,
  `numero_caja` varchar(20) NOT NULL,
  `nombre_caja` varchar(50) NOT NULL,
  `folio` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_caja`),
  UNIQUE KEY `uk_numero_caja` (`numero_caja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cantina`
--

DROP TABLE IF EXISTS `cantina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cantina` (
  `id_cantina` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cantina` varchar(45) NOT NULL,
  PRIMARY KEY (`id_cantina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carrito_temporal`
--

DROP TABLE IF EXISTS `carrito_temporal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carrito_temporal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `tipo_producto` varchar(50) NOT NULL,
  `id_butaca` int(11) DEFAULT NULL,
  `id_funcion` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `fecha_agregado` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_tipo` (`tipo_producto`),
  KEY `idx_butaca` (`id_butaca`),
  KEY `idx_funcion` (`id_funcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contactos`
--

DROP TABLE IF EXISTS `contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contactos` (
  `id_contactos` int(11) NOT NULL AUTO_INCREMENT,
  `rela_persona` int(11) NOT NULL,
  `rela_tipo_contacto` int(11) NOT NULL,
  `valor` varchar(95) DEFAULT NULL,
  PRIMARY KEY (`id_contactos`),
  KEY `fk_contactos_personas1_idx` (`rela_persona`),
  KEY `fk_contactos_tipos_contactos1_idx` (`rela_tipo_contacto`),
  CONSTRAINT `fk_contactos_personas1` FOREIGN KEY (`rela_persona`) REFERENCES `personas` (`id_persona`),
  CONSTRAINT `fk_contactos_tipos_contactos1` FOREIGN KEY (`rela_tipo_contacto`) REFERENCES `tipos_contactos` (`id_tipo_contacto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `control_numeracion_tickets`
--

DROP TABLE IF EXISTS `control_numeracion_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `control_numeracion_tickets` (
  `id_control` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_comprobante` varchar(10) NOT NULL DEFAULT 'B',
  `punto_venta` int(11) NOT NULL DEFAULT 1,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_control`),
  UNIQUE KEY `unique_tipo_punto` (`tipo_comprobante`,`punto_venta`),
  KEY `idx_tipo_punto` (`tipo_comprobante`,`punto_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `datos_facturacion`
--

DROP TABLE IF EXISTS `datos_facturacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `datos_facturacion` (
  `id_dato_facturacion` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento` enum('DNI','CUIL','CUIT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_documento` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `domicilio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `localidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provincia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo_postal` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_facturacion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono_facturacion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_creacion_facturacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion_facturacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id_dato_facturacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detalle_fact_cantina`
--

DROP TABLE IF EXISTS `detalle_fact_cantina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_fact_cantina` (
  `id_detalle_fact_cantina` int(11) NOT NULL AUTO_INCREMENT,
  `rela_cabecera_fact_cantin` int(11) NOT NULL,
  `fecha_reserva_cantina` datetime NOT NULL,
  `rela_producto_cantina` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio_unitario` float DEFAULT NULL,
  `rela_fichas` int(11) DEFAULT NULL,
  `estado` enum('vendido','devuelto') DEFAULT 'vendido',
  PRIMARY KEY (`id_detalle_fact_cantina`),
  KEY `fk_detalle_fact_cantina_cabecera_fact_cantina1_idx` (`rela_cabecera_fact_cantin`),
  KEY `fk_detalle_fact_cantina_productos_cantina1_idx` (`rela_producto_cantina`),
  KEY `rela_fichas` (`rela_fichas`),
  CONSTRAINT `detalle_fact_cantina_ibfk_1` FOREIGN KEY (`rela_fichas`) REFERENCES `fichas` (`id_fichas`),
  CONSTRAINT `fk_detalle_fact_cantina_cabecera_fact_cantina1` FOREIGN KEY (`rela_cabecera_fact_cantin`) REFERENCES `cabecera_fact_cantina` (`id_cabecera_fact_cantin`),
  CONSTRAINT `fk_detalle_fact_cantina_productos_cantina1` FOREIGN KEY (`rela_producto_cantina`) REFERENCES `productos_cantina` (`id_producto_cantina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detalle_fact_cine`
--

DROP TABLE IF EXISTS `detalle_fact_cine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_fact_cine` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `rela_cabecera_fact` int(11) NOT NULL,
  `fecha_venta` datetime NOT NULL,
  `rela_entrada` int(11) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  UNIQUE KEY `uk_entrada_unica` (`rela_entrada`),
  KEY `fk_reservas_entradas1_idx` (`rela_entrada`),
  KEY `idx_cabecera` (`rela_cabecera_fact`),
  CONSTRAINT `fk_detalle_cabecera` FOREIGN KEY (`rela_cabecera_fact`) REFERENCES `cabecera_fact_cine` (`id_pagos`),
  CONSTRAINT `fk_detalle_entrada` FOREIGN KEY (`rela_entrada`) REFERENCES `entradas` (`id_entrada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `detalle_orden`
--

DROP TABLE IF EXISTS `detalle_orden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_orden` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_orden` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `tipo_producto` varchar(50) NOT NULL,
  `id_butaca` int(11) DEFAULT NULL,
  `id_funcion` int(11) DEFAULT NULL,
  `nombre_producto` varchar(255) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_orden` (`id_orden`),
  CONSTRAINT `detalle_orden_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `ordenes` (`id_orden`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `egresos`
--

DROP TABLE IF EXISTS `egresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `egresos` (
  `id_egreso` int(11) NOT NULL AUTO_INCREMENT,
  `rela_arqueo_caja` int(11) DEFAULT NULL,
  `rela_proveedor` int(11) DEFAULT NULL,
  `rela_servicio` int(11) DEFAULT NULL,
  `rela_usuario` int(11) NOT NULL,
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `concepto` text NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `forma_pago` enum('efectivo','tarjeta','transferencia','cheque') DEFAULT 'efectivo',
  `tipo_egreso` enum('operativo','administrativo','comercial') DEFAULT 'operativo',
  `fecha_egreso` datetime DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `estado_egreso` enum('registrado','aprobado','pagado','anulado','devolucion_pendiente') DEFAULT 'registrado',
  `observaciones` text DEFAULT NULL,
  `archivo_comprobante` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_egreso`),
  KEY `idx_arqueo` (`rela_arqueo_caja`),
  KEY `idx_proveedor` (`rela_proveedor`),
  KEY `idx_servicio` (`rela_servicio`),
  KEY `idx_usuario` (`rela_usuario`),
  KEY `idx_fecha` (`fecha_egreso`),
  KEY `idx_estado` (`estado_egreso`),
  KEY `idx_egresos_fecha_estado` (`fecha_egreso`,`estado_egreso`),
  KEY `idx_egresos_proveedor_fecha` (`rela_proveedor`,`fecha_egreso`),
  CONSTRAINT `egresos_ibfk_1` FOREIGN KEY (`rela_arqueo_caja`) REFERENCES `arqueo_cajas` (`id_arqueo_caja`),
  CONSTRAINT `egresos_ibfk_2` FOREIGN KEY (`rela_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `egresos_ibfk_3` FOREIGN KEY (`rela_servicio`) REFERENCES `servicios_proveedor` (`id_servicio`) ON DELETE SET NULL,
  CONSTRAINT `egresos_ibfk_4` FOREIGN KEY (`rela_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entradas`
--

DROP TABLE IF EXISTS `entradas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entradas` (
  `id_entrada` int(11) NOT NULL AUTO_INCREMENT,
  `numero_ticket_entrada` int(11) DEFAULT NULL,
  `rela_tipo_entrada` int(11) NOT NULL,
  `rela_funcion` int(11) DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 0 COMMENT '0=reservada, 1=vendida',
  `codigo_acceso` char(32) DEFAULT NULL COMMENT 'Codigo aleatorio del QR (hex)',
  `usada_en` datetime DEFAULT NULL COMMENT 'Cuando se escaneo en la puerta',
  `usada_por` int(11) DEFAULT NULL COMMENT 'Usuario que la marco como usada',
  PRIMARY KEY (`id_entrada`),
  UNIQUE KEY `uq_entradas_codigo_acceso` (`codigo_acceso`),
  KEY `fk_entradas_tipo_entradas1_idx` (`rela_tipo_entrada`),
  KEY `idx_rela_funcion` (`rela_funcion`),
  CONSTRAINT `fk_entradas_funciones` FOREIGN KEY (`rela_funcion`) REFERENCES `funciones` (`id_funcion`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_entradas_tipo_entradas1` FOREIGN KEY (`rela_tipo_entrada`) REFERENCES `tipo_entradas` (`id_tipo_entrada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entregas_orden`
--

DROP TABLE IF EXISTS `entregas_orden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entregas_orden` (
  `id_entrega` int(11) NOT NULL AUTO_INCREMENT,
  `id_detalle` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id_entrega`),
  KEY `idx_entregas_detalle` (`id_detalle`),
  CONSTRAINT `fk_entregas_detalle` FOREIGN KEY (`id_detalle`) REFERENCES `detalle_orden` (`id_detalle`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estados_butacas`
--

DROP TABLE IF EXISTS `estados_butacas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_butacas` (
  `id_estado_butaca` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado_butaca` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_estado_butaca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estados_peliculas`
--

DROP TABLE IF EXISTS `estados_peliculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_peliculas` (
  `id_estado_pelicula` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado_pelicula` varchar(45) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_estado_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estados_productos`
--

DROP TABLE IF EXISTS `estados_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_productos` (
  `id_estado_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado_producto` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_estado_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fichas`
--

DROP TABLE IF EXISTS `fichas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fichas` (
  `id_fichas` int(11) NOT NULL AUTO_INCREMENT,
  `precio_ficha` float DEFAULT NULL,
  `cantidad_ficha` int(11) DEFAULT NULL,
  `nombre_ficha` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_fichas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `funciones`
--

DROP TABLE IF EXISTS `funciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funciones` (
  `id_funcion` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_hora` date DEFAULT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `rela_salas` int(11) NOT NULL,
  `rela_peliculas` int(11) NOT NULL,
  `rela_turnos` int(11) NOT NULL,
  `rela_tipo_entrada` int(11) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `rela_idioma` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_funcion`),
  KEY `fk_funciones_salas1_idx` (`rela_salas`),
  KEY `fk_funciones_peliculas1_idx` (`rela_peliculas`),
  KEY `fk_funciones_turnos1_idx` (`rela_turnos`),
  KEY `fk_funciones_tipo_entrada` (`rela_tipo_entrada`),
  KEY `rela_idioma` (`rela_idioma`),
  CONSTRAINT `fk_funciones_peliculas1` FOREIGN KEY (`rela_peliculas`) REFERENCES `peliculas` (`id_pelicula`),
  CONSTRAINT `fk_funciones_salas1` FOREIGN KEY (`rela_salas`) REFERENCES `salas` (`id_sala`),
  CONSTRAINT `fk_funciones_tipo_entrada` FOREIGN KEY (`rela_tipo_entrada`) REFERENCES `tipo_entradas` (`id_tipo_entrada`),
  CONSTRAINT `fk_funciones_turnos1` FOREIGN KEY (`rela_turnos`) REFERENCES `turnos` (`id_turnos`),
  CONSTRAINT `funciones_ibfk_1` FOREIGN KEY (`rela_idioma`) REFERENCES `idiomas_peliculas` (`id_idioma_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generos_peliculas`
--

DROP TABLE IF EXISTS `generos_peliculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generos_peliculas` (
  `id_genero_pelicula` int(11) NOT NULL AUTO_INCREMENT,
  `genero_pelicula` varchar(45) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_genero_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generos_por_peliculas`
--

DROP TABLE IF EXISTS `generos_por_peliculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generos_por_peliculas` (
  `id_genero_por_pelicula` int(11) NOT NULL AUTO_INCREMENT,
  `rela_genero_pelicula` int(11) NOT NULL,
  `rela_pelicula` int(11) NOT NULL,
  PRIMARY KEY (`id_genero_por_pelicula`),
  KEY `fk_generos_x_peliculas_generos_peliculas1_idx` (`rela_genero_pelicula`),
  KEY `fk_generos_x_peliculas_peliculas1_idx` (`rela_pelicula`),
  CONSTRAINT `fk_generos_x_peliculas_generos_peliculas1` FOREIGN KEY (`rela_genero_pelicula`) REFERENCES `generos_peliculas` (`id_genero_pelicula`),
  CONSTRAINT `fk_generos_x_peliculas_peliculas1` FOREIGN KEY (`rela_pelicula`) REFERENCES `peliculas` (`id_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `idiomas_peliculas`
--

DROP TABLE IF EXISTS `idiomas_peliculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idiomas_peliculas` (
  `id_idioma_pelicula` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_idioma_pelicula` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_idioma_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `idiomas_por_peliculas`
--

DROP TABLE IF EXISTS `idiomas_por_peliculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idiomas_por_peliculas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rela_pelicula` int(11) NOT NULL,
  `rela_idioma` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rela_pelicula` (`rela_pelicula`),
  KEY `rela_idioma` (`rela_idioma`),
  CONSTRAINT `idiomas_por_peliculas_ibfk_1` FOREIGN KEY (`rela_pelicula`) REFERENCES `peliculas` (`id_pelicula`),
  CONSTRAINT `idiomas_por_peliculas_ibfk_2` FOREIGN KEY (`rela_idioma`) REFERENCES `idiomas_peliculas` (`id_idioma_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapa`
--

DROP TABLE IF EXISTS `mapa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapa` (
  `id_mapa` int(11) NOT NULL,
  `mapa_descripcion` varchar(900) DEFAULT NULL,
  `mapa_ubicacion` varchar(900) DEFAULT NULL,
  PRIMARY KEY (`id_mapa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maquinas`
--

DROP TABLE IF EXISTS `maquinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquinas` (
  `id_maquinas` int(11) NOT NULL AUTO_INCREMENT,
  `maquinas_nombre` varchar(45) DEFAULT NULL,
  `maquina_descripcion` varchar(900) DEFAULT NULL,
  `imagen_maquina` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `rela_fichas` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_maquinas`),
  KEY `fk_maquinas_fichas` (`rela_fichas`),
  CONSTRAINT `fk_maquinas_fichas` FOREIGN KEY (`rela_fichas`) REFERENCES `fichas` (`id_fichas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marcas`
--

DROP TABLE IF EXISTS `marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marcas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `categorias_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_marcas_categorias_idx` (`categorias_id`),
  CONSTRAINT `fk_marcas_categorias` FOREIGN KEY (`categorias_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo_x_tipos_de_usuarios`
--

DROP TABLE IF EXISTS `modulo_x_tipos_de_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulo_x_tipos_de_usuarios` (
  `id_mod_x_tipo` int(11) NOT NULL AUTO_INCREMENT,
  `estado` tinyint(4) DEFAULT 1,
  `rela_tipos_de_usuarios` int(11) NOT NULL,
  `rela_modulo` int(11) NOT NULL,
  PRIMARY KEY (`id_mod_x_tipo`),
  KEY `fk_modulo_x_tipos_de_usuarios_tipos_de_usuarios1_idx` (`rela_tipos_de_usuarios`),
  KEY `fk_modulo_x_tipos_de_usuarios_servicios1_idx` (`rela_modulo`),
  CONSTRAINT `fk_modulo_x_tipos_de_usuarios_servicios1` FOREIGN KEY (`rela_modulo`) REFERENCES `modulos` (`id_modulo`),
  CONSTRAINT `fk_modulo_x_tipos_de_usuarios_tipos_de_usuarios1` FOREIGN KEY (`rela_tipos_de_usuarios`) REFERENCES `perfiles` (`id_perfiles`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulos`
--

DROP TABLE IF EXISTS `modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulos` (
  `id_modulo` int(11) NOT NULL AUTO_INCREMENT,
  `modulo_nombre` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `movimientos_caja`
--

DROP TABLE IF EXISTS `movimientos_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_caja` (
  `id_movimiento_caja` int(11) NOT NULL AUTO_INCREMENT,
  `rela_arqueo_caja` int(11) NOT NULL,
  `tipo_movimiento` enum('ingreso','egreso') NOT NULL,
  `concepto_movimiento` varchar(100) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_movimiento` datetime NOT NULL,
  `forma_pago` enum('efectivo','tarjeta','transferencia','otro') NOT NULL,
  `rela_egreso` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_movimiento_caja`),
  KEY `rela_arqueo_caja` (`rela_arqueo_caja`),
  KEY `idx_fecha_movimiento` (`fecha_movimiento`),
  KEY `rela_egreso` (`rela_egreso`),
  CONSTRAINT `movimientos_caja_ibfk_1` FOREIGN KEY (`rela_arqueo_caja`) REFERENCES `arqueo_cajas` (`id_arqueo_caja`),
  CONSTRAINT `movimientos_caja_ibfk_2` FOREIGN KEY (`rela_egreso`) REFERENCES `egresos` (`id_egreso`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `movimientos_stock`
--

DROP TABLE IF EXISTS `movimientos_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_stock` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_producto` varchar(50) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida','venta','ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_orden` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `movimientos_stock_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `rela_usuario` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `tipo` enum('registro_usuario','nueva_venta','stock_bajo','sistema') DEFAULT 'sistema',
  `leido` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_lectura` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_notificacion`),
  KEY `idx_usuario_leido` (`rela_usuario`,`leido`),
  KEY `idx_fecha_creacion` (`fecha_creacion`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`rela_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ordenes`
--

DROP TABLE IF EXISTS `ordenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordenes` (
  `id_orden` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `numero_orden` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','cancelado','fallido') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `preference_id` varchar(100) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_pago` datetime DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `codigo_retiro` char(32) DEFAULT NULL COMMENT 'Codigo aleatorio del QR de retiro (hex)',
  PRIMARY KEY (`id_orden`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  UNIQUE KEY `uq_ordenes_codigo_retiro` (`codigo_retiro`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`),
  KEY `idx_payment` (`payment_id`),
  CONSTRAINT `ordenes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `peliculas`
--

DROP TABLE IF EXISTS `peliculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peliculas` (
  `id_pelicula` int(11) NOT NULL AUTO_INCREMENT,
  `titulo_pelicula` varchar(45) DEFAULT NULL,
  `sinopsis_pelicula` varchar(995) DEFAULT NULL,
  `anyo_pelicula` int(11) DEFAULT NULL,
  `duracion_pelicula` int(11) DEFAULT NULL,
  `imagen_pelicula` varchar(255) DEFAULT NULL,
  `trailer_url` varchar(500) DEFAULT NULL,
  `rela_estado_pelicula` int(11) NOT NULL,
  `rela_tipo_clasificacion` int(11) DEFAULT NULL,
  `rela_idioma_pelicula` int(11) DEFAULT NULL,
  `creado` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pelicula`),
  KEY `fk_peliculas_estados_peliculas1_idx` (`rela_estado_pelicula`),
  KEY `rela_tipo_clasificacion` (`rela_tipo_clasificacion`),
  KEY `rela_idioma_pelicula` (`rela_idioma_pelicula`),
  CONSTRAINT `fk_peliculas_estados_peliculas1` FOREIGN KEY (`rela_estado_pelicula`) REFERENCES `estados_peliculas` (`id_estado_pelicula`),
  CONSTRAINT `peliculas_ibfk_1` FOREIGN KEY (`rela_tipo_clasificacion`) REFERENCES `tipos_clasificaciones` (`id_tipo_clasificacion`),
  CONSTRAINT `peliculas_ibfk_2` FOREIGN KEY (`rela_idioma_pelicula`) REFERENCES `idiomas_peliculas` (`id_idioma_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `perfiles`
--

DROP TABLE IF EXISTS `perfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perfiles` (
  `id_perfiles` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_perfil` varchar(45) DEFAULT NULL,
  `permiso_perfil` varchar(345) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_perfiles`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personas`
--

DROP TABLE IF EXISTS `personas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personas` (
  `id_persona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_persona` varchar(95) DEFAULT NULL,
  `apellido_persona` varchar(45) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_alta` datetime DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 1,
  `fecha_baja` datetime DEFAULT NULL,
  `rela_sexo` int(11) NOT NULL,
  PRIMARY KEY (`id_persona`),
  KEY `fk_personas_sexo1_idx` (`rela_sexo`),
  CONSTRAINT `fk_personas_sexo1` FOREIGN KEY (`rela_sexo`) REFERENCES `sexo` (`id_sexo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(85) NOT NULL,
  `descripcion` varchar(85) NOT NULL,
  `codigo` varchar(65) NOT NULL,
  `precio_venta` decimal(12,2) NOT NULL,
  `precio_compra` decimal(12,2) NOT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 1,
  `stock_maximo` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 2,
  `marcas_id` int(11) NOT NULL,
  `categorias_id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_productos_marcas1_idx` (`marcas_id`),
  KEY `fk_productos_categorias1_idx` (`categorias_id`),
  CONSTRAINT `fk_productos_categorias1` FOREIGN KEY (`categorias_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `fk_productos_marcas1` FOREIGN KEY (`marcas_id`) REFERENCES `marcas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productos_cantina`
--

DROP TABLE IF EXISTS `productos_cantina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos_cantina` (
  `id_producto_cantina` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_producto_cantina` varchar(90) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `rela_estado_producto` int(11) NOT NULL,
  `precio_producto` int(11) DEFAULT NULL,
  `imagen_producto` varchar(255) DEFAULT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  PRIMARY KEY (`id_producto_cantina`),
  KEY `fk_productos_cantina_estados_productos1_idx` (`rela_estado_producto`),
  CONSTRAINT `fk_productos_cantina_estados_productos1` FOREIGN KEY (`rela_estado_producto`) REFERENCES `estados_productos` (`id_estado_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(200) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `rut` varchar(20) DEFAULT NULL,
  `tipo_proveedor` enum('peliculas','servicios','productos','otros') NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `pais` varchar(50) DEFAULT 'Argentina',
  `sitio_web` varchar(200) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_alta` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `rut` (`rut`),
  KEY `idx_tipo` (`tipo_proveedor`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resenas`
--

DROP TABLE IF EXISTS `resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resenas` (
  `id_resena` int(11) NOT NULL AUTO_INCREMENT,
  `rela_usuario` int(11) NOT NULL,
  `rela_pelicula` int(11) NOT NULL,
  `puntuacion` decimal(3,1) NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_resena`),
  UNIQUE KEY `uniq_usuario_pelicula` (`rela_usuario`,`rela_pelicula`),
  KEY `rela_pelicula` (`rela_pelicula`),
  CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`rela_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`rela_pelicula`) REFERENCES `peliculas` (`id_pelicula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_butacas`
--

DROP TABLE IF EXISTS `reservas_butacas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_butacas` (
  `id_reserva` int(11) NOT NULL AUTO_INCREMENT,
  `id_butaca` int(11) NOT NULL,
  `id_funcion` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `sesion_id` varchar(100) DEFAULT NULL,
  `estado_reserva` enum('temporal','confirmada','pagada','cancelada') NOT NULL DEFAULT 'temporal',
  `fecha_reserva` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  UNIQUE KEY `unique_butaca_funcion` (`id_butaca`,`id_funcion`),
  KEY `idx_funcion` (`id_funcion`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_session` (`sesion_id`),
  KEY `idx_estado` (`estado_reserva`),
  CONSTRAINT `reservas_butacas_ibfk_1` FOREIGN KEY (`id_butaca`) REFERENCES `butacas` (`id_butaca`) ON DELETE CASCADE,
  CONSTRAINT `reservas_butacas_ibfk_2` FOREIGN KEY (`id_funcion`) REFERENCES `funciones` (`id_funcion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sala_de_juegos`
--

DROP TABLE IF EXISTS `sala_de_juegos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sala_de_juegos` (
  `id_sala_de_juegos` int(11) NOT NULL AUTO_INCREMENT,
  `rela_maquinas` int(11) NOT NULL,
  PRIMARY KEY (`id_sala_de_juegos`),
  KEY `fk_sala_de_juegos_maquinas1_idx` (`rela_maquinas`),
  CONSTRAINT `fk_sala_de_juegos_maquinas1` FOREIGN KEY (`rela_maquinas`) REFERENCES `maquinas` (`id_maquinas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salas`
--

DROP TABLE IF EXISTS `salas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salas` (
  `id_sala` int(11) NOT NULL AUTO_INCREMENT,
  `capacidad_sala` int(11) DEFAULT NULL,
  `filas_sala` int(11) DEFAULT NULL,
  `columnas_sala` int(11) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_sala`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `servicios_proveedor`
--

DROP TABLE IF EXISTS `servicios_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios_proveedor` (
  `id_servicio` int(11) NOT NULL AUTO_INCREMENT,
  `rela_proveedor` int(11) NOT NULL,
  `nombre_servicio` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `codigo_servicio` varchar(50) DEFAULT NULL,
  `categoria_servicio` enum('licencia_pelicula','distribucion','agua','luz','gas','internet','telefonia','limpieza','mantenimiento','seguridad','alquiler','impuestos','otros') NOT NULL,
  `monto_base` decimal(10,2) DEFAULT 0.00,
  `tiene_monto_variable` tinyint(1) DEFAULT 0,
  `frecuencia_pago` enum('mensual','bimestral','trimestral','unico','variable') DEFAULT 'unico',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_alta` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_servicio`),
  KEY `idx_proveedor` (`rela_proveedor`),
  KEY `idx_categoria` (`categoria_servicio`),
  KEY `idx_activo` (`activo`),
  KEY `idx_servicios_proveedor_categoria` (`rela_proveedor`,`categoria_servicio`),
  CONSTRAINT `servicios_proveedor_ibfk_1` FOREIGN KEY (`rela_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sexo`
--

DROP TABLE IF EXISTS `sexo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sexo` (
  `id_sexo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_sexo` varchar(45) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_sexo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_cantina`
--

DROP TABLE IF EXISTS `stock_cantina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_cantina` (
  `id_stock_cantina` int(11) NOT NULL AUTO_INCREMENT,
  `stock_cantina` int(11) DEFAULT NULL,
  `rela_producto_cantina` int(11) NOT NULL,
  `rela_cantina` int(11) NOT NULL,
  PRIMARY KEY (`id_stock_cantina`),
  KEY `fk_stock_cantina_productos_cantina1_idx` (`rela_producto_cantina`),
  KEY `fk_stock_cantina_cantina1_idx` (`rela_cantina`),
  CONSTRAINT `fk_stock_cantina_cantina1` FOREIGN KEY (`rela_cantina`) REFERENCES `cantina` (`id_cantina`),
  CONSTRAINT `fk_stock_cantina_productos_cantina1` FOREIGN KEY (`rela_producto_cantina`) REFERENCES `productos_cantina` (`id_producto_cantina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipo_entradas`
--

DROP TABLE IF EXISTS `tipo_entradas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_entradas` (
  `id_tipo_entrada` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_entrada_desc` varchar(45) NOT NULL,
  `precio_entrada` int(11) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo_entrada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipos_clasificaciones`
--

DROP TABLE IF EXISTS `tipos_clasificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_clasificaciones` (
  `id_tipo_clasificacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tipo_clasificacion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_tipo_clasificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipos_comprobantes`
--

DROP TABLE IF EXISTS `tipos_comprobantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_comprobantes` (
  `id_tipo_comprobante` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `descripcion_corta` varchar(50) NOT NULL,
  `valido_afip` tinyint(1) DEFAULT 0,
  `requiere_cuit` tinyint(1) DEFAULT 0,
  `requiere_datos_facturacion` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tipo_comprobante`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipos_contactos`
--

DROP TABLE IF EXISTS `tipos_contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_contactos` (
  `id_tipo_contacto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tipo_contacto` varchar(95) DEFAULT NULL,
  PRIMARY KEY (`id_tipo_contacto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipos_de_pagos`
--

DROP TABLE IF EXISTS `tipos_de_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_de_pagos` (
  `id_tipo_pago` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion_tipo_pago` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turnos` (
  `id_turnos` int(11) NOT NULL AUTO_INCREMENT,
  `turno_horario` time DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_turnos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(45) DEFAULT NULL,
  `clave_usuario` varchar(300) DEFAULT NULL,
  `rela_perfil` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `token_verificacion` varchar(255) DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0,
  `email` varchar(45) DEFAULT NULL,
  `token_recuperacion` varchar(60) DEFAULT NULL,
  `token_recuperacion_expira` datetime DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `foto_perfil` longtext DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  KEY `fk_usuarios_tipos_de_usuarios_idx` (`rela_perfil`),
  KEY `fk_usuarios_personas1_idx` (`id_persona`),
  CONSTRAINT `fk_usuarios_personas1` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`),
  CONSTRAINT `fk_usuarios_tipos_de_usuarios` FOREIGN KEY (`rela_perfil`) REFERENCES `perfiles` (`id_perfiles`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `vista_egresos_completos`
--

DROP TABLE IF EXISTS `vista_egresos_completos`;
/*!50001 DROP VIEW IF EXISTS `vista_egresos_completos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_egresos_completos` AS SELECT
 1 AS `id_egreso`,
  1 AS `numero_comprobante`,
  1 AS `concepto`,
  1 AS `monto`,
  1 AS `forma_pago`,
  1 AS `tipo_egreso`,
  1 AS `fecha_egreso`,
  1 AS `fecha_vencimiento`,
  1 AS `estado_egreso`,
  1 AS `observaciones`,
  1 AS `archivo_comprobante`,
  1 AS `proveedor`,
  1 AS `nombre_proveedor`,
  1 AS `tipo_proveedor`,
  1 AS `nombre_servicio`,
  1 AS `categoria_servicio`,
  1 AS `usuario_registro`,
  1 AS `id_arqueo_caja`,
  1 AS `nombre_caja` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vista_servicios_activos`
--

DROP TABLE IF EXISTS `vista_servicios_activos`;
/*!50001 DROP VIEW IF EXISTS `vista_servicios_activos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_servicios_activos` AS SELECT
 1 AS `id_servicio`,
  1 AS `nombre_servicio`,
  1 AS `descripcion`,
  1 AS `categoria_servicio`,
  1 AS `monto_base`,
  1 AS `tiene_monto_variable`,
  1 AS `frecuencia_pago`,
  1 AS `id_proveedor`,
  1 AS `razon_social`,
  1 AS `nombre_comercial`,
  1 AS `tipo_proveedor` */;
SET character_set_client = @saved_cs_client;

--
-- Current Database: `cinfsa1`
--

USE `cinfsa1`;

--
-- Final view structure for view `vista_egresos_completos`
--

/*!50001 DROP VIEW IF EXISTS `vista_egresos_completos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_egresos_completos` AS select `e`.`id_egreso` AS `id_egreso`,`e`.`numero_comprobante` AS `numero_comprobante`,`e`.`concepto` AS `concepto`,`e`.`monto` AS `monto`,`e`.`forma_pago` AS `forma_pago`,`e`.`tipo_egreso` AS `tipo_egreso`,`e`.`fecha_egreso` AS `fecha_egreso`,`e`.`fecha_vencimiento` AS `fecha_vencimiento`,`e`.`estado_egreso` AS `estado_egreso`,`e`.`observaciones` AS `observaciones`,`e`.`archivo_comprobante` AS `archivo_comprobante`,`p`.`id_proveedor` AS `proveedor`,`p`.`razon_social` AS `nombre_proveedor`,`p`.`tipo_proveedor` AS `tipo_proveedor`,`s`.`nombre_servicio` AS `nombre_servicio`,`s`.`categoria_servicio` AS `categoria_servicio`,concat(`per`.`nombre_persona`,' ',`per`.`apellido_persona`) AS `usuario_registro`,`ac`.`id_arqueo_caja` AS `id_arqueo_caja`,`c`.`nombre_caja` AS `nombre_caja` from ((((((`egresos` `e` join `proveedores` `p` on(`e`.`rela_proveedor` = `p`.`id_proveedor`)) left join `servicios_proveedor` `s` on(`e`.`rela_servicio` = `s`.`id_servicio`)) join `usuarios` `u` on(`e`.`rela_usuario` = `u`.`id_usuario`)) join `personas` `per` on(`u`.`id_persona` = `per`.`id_persona`)) join `arqueo_cajas` `ac` on(`e`.`rela_arqueo_caja` = `ac`.`id_arqueo_caja`)) join `cajas` `c` on(`ac`.`rela_caja` = `c`.`id_caja`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vista_servicios_activos`
--

/*!50001 DROP VIEW IF EXISTS `vista_servicios_activos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_servicios_activos` AS select `s`.`id_servicio` AS `id_servicio`,`s`.`nombre_servicio` AS `nombre_servicio`,`s`.`descripcion` AS `descripcion`,`s`.`categoria_servicio` AS `categoria_servicio`,`s`.`monto_base` AS `monto_base`,`s`.`tiene_monto_variable` AS `tiene_monto_variable`,`s`.`frecuencia_pago` AS `frecuencia_pago`,`p`.`id_proveedor` AS `id_proveedor`,`p`.`razon_social` AS `razon_social`,`p`.`nombre_comercial` AS `nombre_comercial`,`p`.`tipo_proveedor` AS `tipo_proveedor` from (`servicios_proveedor` `s` join `proveedores` `p` on(`s`.`rela_proveedor` = `p`.`id_proveedor`)) where `s`.`activo` = 1 and `p`.`activo` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
