-- QR de las entradas (etapa 1 del control de acceso)
-- Cada entrada tiene un código aleatorio propio, que es lo que lleva su QR (CINFSA-E-<código>).
-- No se usa numero_ticket_entrada: es correlativo/adivinable y muchas entradas no lo tienen.
-- Las entradas viejas reciben su código la primera vez que se muestran (Entrada::asegurarCodigoAcceso).
--
-- usada_en / usada_por quedan listas para el control en la puerta (etapa 3).
--
-- Aplicar UNA vez en cada base (local y servidor):
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-04_codigo_acceso_entradas.sql

ALTER TABLE `entradas`
  ADD COLUMN `codigo_acceso` char(32) DEFAULT NULL COMMENT 'Codigo aleatorio del QR (hex)',
  ADD COLUMN `usada_en` datetime DEFAULT NULL COMMENT 'Cuando se escaneo en la puerta',
  ADD COLUMN `usada_por` int(11) DEFAULT NULL COMMENT 'Usuario que la marco como usada',
  ADD UNIQUE KEY `uq_entradas_codigo_acceso` (`codigo_acceso`);
