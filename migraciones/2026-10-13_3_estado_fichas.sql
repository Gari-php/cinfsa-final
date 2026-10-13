-- Baja lógica de fichas (Administrador → Fichas → "Dar de baja"): la ficha queda inactiva y se
-- reactiva desde Editar. Una ficha inactiva no se ofrece para vender ni para asignar a máquinas.
--
-- Aplicar UNA vez en cada base (local y servidor), después de 2026-10-13_2_cantidad_devuelta_detalle_orden.sql:
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-13_3_estado_fichas.sql

ALTER TABLE `fichas`
  ADD COLUMN `estado` TINYINT(1) NOT NULL DEFAULT 1 AFTER `nombre_ficha`;
