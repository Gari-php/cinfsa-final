-- Control de entradas en la puerta (etapa 3 del QR)
-- Módulo CONTROL_ENTRADAS y perfil CONTROL_ACCESO (el empleado que escanea en la puerta).
-- El módulo también se le puede dar a VENDEDOR_FUNCIONES desde Usuarios → Permisos.
-- Se insertan por nombre (no por id) y solo si no existen, así se puede correr en cualquier base.
--
-- Aplicar UNA vez en cada base (local y servidor):
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-04_control_acceso.sql

INSERT INTO `modulos` (`modulo_nombre`)
SELECT 'CONTROL_ENTRADAS' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `modulos` WHERE `modulo_nombre` = 'CONTROL_ENTRADAS');

INSERT INTO `perfiles` (`nombre_perfil`, `permiso_perfil`, `estado`)
SELECT 'CONTROL_ACCESO', 'Controla el ingreso a las salas escaneando el QR de las entradas', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `perfiles` WHERE `nombre_perfil` = 'CONTROL_ACCESO');

INSERT INTO `modulo_x_tipos_de_usuarios` (`estado`, `rela_tipos_de_usuarios`, `rela_modulo`)
SELECT 1, p.`id_perfiles`, m.`id_modulo`
FROM `perfiles` p, `modulos` m
WHERE p.`nombre_perfil` = 'CONTROL_ACCESO' AND m.`modulo_nombre` = 'CONTROL_ENTRADAS'
  AND NOT EXISTS (
      SELECT 1 FROM `modulo_x_tipos_de_usuarios` x
      WHERE x.`rela_tipos_de_usuarios` = p.`id_perfiles` AND x.`rela_modulo` = m.`id_modulo`
  );
