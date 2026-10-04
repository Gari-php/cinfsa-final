-- Retiro de productos y fichas comprados por la web (etapa 4 del QR)
-- Cada orden tiene un código aleatorio para su QR de retiro (CINFSA-O-<código>) y cada entrega
-- (total o parcial) queda registrada por ítem en entregas_orden: lo que queda por retirar es
-- lo comprado (detalle_orden.cantidad) menos lo entregado.
-- Módulo ENTREGA_PRODUCTOS y perfil ENTREGA_PRODUCTOS (también se le puede dar a VENDEDOR_PRODUCTOS).
--
-- Aplicar UNA vez en cada base (local y servidor):
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-04_retiro_productos.sql

ALTER TABLE `ordenes`
  ADD COLUMN `codigo_retiro` char(32) DEFAULT NULL COMMENT 'Codigo aleatorio del QR de retiro (hex)',
  ADD UNIQUE KEY `uq_ordenes_codigo_retiro` (`codigo_retiro`);

CREATE TABLE IF NOT EXISTS `entregas_orden` (
  `id_entrega` int(11) NOT NULL AUTO_INCREMENT,
  `id_detalle` int(11) NOT NULL,                 -- ítem de detalle_orden (producto o fichas)
  `cantidad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,                 -- quién lo entregó
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id_entrega`),
  KEY `idx_entregas_detalle` (`id_detalle`),
  CONSTRAINT `fk_entregas_detalle` FOREIGN KEY (`id_detalle`) REFERENCES `detalle_orden` (`id_detalle`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modulos` (`modulo_nombre`)
SELECT 'ENTREGA_PRODUCTOS' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `modulos` WHERE `modulo_nombre` = 'ENTREGA_PRODUCTOS');

INSERT INTO `perfiles` (`nombre_perfil`, `permiso_perfil`, `estado`)
SELECT 'ENTREGA_PRODUCTOS', 'Entrega en la cantina los productos y fichas comprados por la web', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `perfiles` WHERE `nombre_perfil` = 'ENTREGA_PRODUCTOS');

INSERT INTO `modulo_x_tipos_de_usuarios` (`estado`, `rela_tipos_de_usuarios`, `rela_modulo`)
SELECT 1, p.`id_perfiles`, m.`id_modulo`
FROM `perfiles` p, `modulos` m
WHERE p.`nombre_perfil` = 'ENTREGA_PRODUCTOS' AND m.`modulo_nombre` = 'ENTREGA_PRODUCTOS'
  AND NOT EXISTS (
      SELECT 1 FROM `modulo_x_tipos_de_usuarios` x
      WHERE x.`rela_tipos_de_usuarios` = p.`id_perfiles` AND x.`rela_modulo` = m.`id_modulo`
  );
