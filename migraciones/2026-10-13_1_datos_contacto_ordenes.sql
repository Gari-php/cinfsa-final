-- Teléfono y DNI que el cliente deja en el checkout, guardados con cada compra web, para que
-- el administrador pueda contactarlo si hay un problema (por ejemplo, con el retiro).
-- Se ven en Administrador → Compras web → Detalle. Las órdenes anteriores quedan en NULL.
--
-- Aplicar UNA vez en cada base (local y servidor):
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-13_1_datos_contacto_ordenes.sql

ALTER TABLE `ordenes`
  ADD COLUMN `telefono_contacto` VARCHAR(30) DEFAULT NULL AFTER `codigo_retiro`,
  ADD COLUMN `dni_contacto` VARCHAR(15) DEFAULT NULL AFTER `telefono_contacto`;
