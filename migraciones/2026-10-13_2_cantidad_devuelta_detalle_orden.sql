-- Devolución parcial de productos y fichas de una compra web (Administrador → Ventas web → detalle):
-- cuántas unidades de cada línea se devolvieron. Lo pendiente de retirar en la cantina pasa a ser
-- comprado − entregado − devuelto, y al cancelar la orden completa no se repone dos veces el stock.
--
-- Aplicar UNA vez en cada base (local y servidor), después de 2026-10-13_1_datos_contacto_ordenes.sql:
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-13_2_cantidad_devuelta_detalle_orden.sql

ALTER TABLE `detalle_orden`
  ADD COLUMN `cantidad_devuelta` INT(11) NOT NULL DEFAULT 0 AFTER `cantidad`;
