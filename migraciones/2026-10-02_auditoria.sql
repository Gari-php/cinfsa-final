-- Registro de auditoría (Control → Registro de auditoría)
-- Guarda quién hizo cada acción sensible, cuándo y desde qué IP. Lo escribe Classes\Auditoria.
--
-- Aplicar UNA vez en cada base (local y servidor):
--   mysql -u USUARIO -p NOMBRE_BASE < migraciones/2026-10-02_auditoria.sql

CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,               -- NULL en un inicio de sesión fallido
  `nombre_usuario` varchar(45) DEFAULT NULL,       -- copia: sigue legible aunque el usuario se borre
  `perfil` varchar(45) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,                   -- ej. entrada.cancelar, precio.tipo_entrada
  `entidad` varchar(50) DEFAULT NULL,              -- ej. entradas, usuarios
  `id_entidad` int(11) DEFAULT NULL,
  `descripcion` varchar(500) NOT NULL,
  `datos_antes` text DEFAULT NULL,                 -- JSON
  `datos_despues` text DEFAULT NULL,               -- JSON
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_fecha` (`fecha`),
  KEY `idx_auditoria_usuario` (`id_usuario`),
  KEY `idx_auditoria_accion` (`accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- `accesos` nunca se usó (ningún código la lee ni la escribe): la reemplaza `auditoria`
DROP TABLE IF EXISTS `accesos`;
