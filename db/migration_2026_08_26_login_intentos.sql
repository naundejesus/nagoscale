-- Migración: bloqueo temporal tras varios intentos de login fallidos (fuerza bruta).
-- Ejecutar UNA sola vez.

CREATE TABLE IF NOT EXISTS login_intentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  actualizado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_login_intentos_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
