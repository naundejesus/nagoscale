-- Migración: backoff progresivo por usuario + límite de intentos por IP,
-- para evitar que un tercero no autenticado pueda bloquear una cuenta ajena
-- repitiendo 5 intentos fallidos (Fase 3 de remediación de seguridad).
-- Ejecutar UNA sola vez.

ALTER TABLE login_intentos
  ADD COLUMN nivel_bloqueo TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER bloqueado_hasta;

CREATE TABLE IF NOT EXISTS login_intentos_ip (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ventana_desde DATETIME NOT NULL,
  UNIQUE KEY idx_login_intentos_ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
