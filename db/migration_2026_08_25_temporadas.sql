-- Migración: precios de temporada especial por rango de fechas (ej. temporada alta).
-- Ejecutar UNA sola vez.

CREATE TABLE IF NOT EXISTS temporadas_precio (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(80) NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  precio_noche DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_temporadas_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE CASCADE,
  INDEX idx_temporadas_apartamento (apartamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
