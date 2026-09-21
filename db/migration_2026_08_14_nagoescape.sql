-- Migración: rediseño Nago Escape — ciudad/zona/descripción/amenidades/mapa
-- en apartamentos, y tabla de reseñas. Ejecutar UNA sola vez.

ALTER TABLE apartamentos
  ADD COLUMN ciudad VARCHAR(100) NULL,
  ADD COLUMN zona VARCHAR(100) NULL,
  ADD COLUMN descripcion TEXT NULL,
  ADD COLUMN amenidades TEXT NULL,
  ADD COLUMN latitud DECIMAL(10,7) NULL,
  ADD COLUMN longitud DECIMAL(10,7) NULL,
  ADD INDEX idx_apartamentos_ciudad (ciudad);

ALTER TABLE solicitudes
  ADD COLUMN huespedes SMALLINT UNSIGNED NULL AFTER fecha_fin;

CREATE TABLE IF NOT EXISTS resenas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  nombre_cliente VARCHAR(120) NOT NULL,
  calificacion TINYINT UNSIGNED NOT NULL,
  comentario TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resenas_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE CASCADE,
  INDEX idx_resenas_apartamento (apartamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
