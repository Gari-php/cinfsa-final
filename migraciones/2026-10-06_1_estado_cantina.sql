-- Baja lógica de cantinas: en vez de borrarlas se marcan inactivas (estado = 0).
-- Las cantinas que ya existen quedan activas (estado = 1).
--
-- Aplicar UNA vez en cada base (local y servidor):
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-06_1_estado_cantina.sql

ALTER TABLE `cantina`
  ADD COLUMN `estado` TINYINT(1) NOT NULL DEFAULT 1 AFTER `nombre_cantina`;
