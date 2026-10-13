-- Migración: fotos de apartamento, propietario, y rango de fechas en reservas.
-- Ejecutar UNA sola vez sobre una base de datos que ya tiene el esquema original
-- (la que se creó con db/schema.sql antes de este cambio).

ALTER TABLE apartamentos ADD COLUMN propietario VARCHAR(120) NULL AFTER nombre;

CREATE TABLE IF NOT EXISTS apartamento_fotos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  archivo VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fotos_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE CASCADE,
  INDEX idx_fotos_apartamento (apartamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE reservas ADD COLUMN fecha_fin DATE NULL AFTER fecha_reserva;
UPDATE reservas SET fecha_fin = fecha_reserva WHERE fecha_fin IS NULL;
ALTER TABLE reservas MODIFY fecha_fin DATE NOT NULL;
ALTER TABLE reservas CHANGE fecha_reserva fecha_inicio DATE NOT NULL;

ALTER TABLE reservas DROP INDEX idx_reservas_fecha;
ALTER TABLE reservas ADD INDEX idx_reservas_fecha_inicio (fecha_inicio);
ALTER TABLE reservas ADD INDEX idx_reservas_fecha_fin (fecha_fin);
