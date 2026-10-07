-- Cada caja de venta de productos pertenece a una cantina: el vendedor solo ve y vende el stock
-- de esa cantina. Se asigna en Administrador → Cantina → Editar.
-- Las cajas de productos (3 y 4) quedan en la primera cantina activa, que es la que usaba la venta
-- hasta ahora, así nada cambia hasta que se reasignen.
--
-- Aplicar UNA vez en cada base (local y servidor), después de 2026-10-06_1_estado_cantina.sql:
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-06_2_cajas_por_cantina.sql

ALTER TABLE `cajas`
  ADD COLUMN `rela_cantina` INT(11) DEFAULT NULL AFTER `activo`,
  ADD KEY `fk_cajas_cantina_idx` (`rela_cantina`),
  ADD CONSTRAINT `fk_cajas_cantina` FOREIGN KEY (`rela_cantina`) REFERENCES `cantina` (`id_cantina`);

UPDATE `cajas`
SET `rela_cantina` = (SELECT MIN(`id_cantina`) FROM `cantina` WHERE `estado` = 1)
WHERE `id_caja` IN (3, 4) AND `rela_cantina` IS NULL;
