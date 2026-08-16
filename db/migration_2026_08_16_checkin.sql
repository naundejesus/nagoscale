-- Migración: registro/check-in de huéspedes por reserva.
-- Ejecutar UNA sola vez.

CREATE TABLE IF NOT EXISTS checkin_huespedes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id INT UNSIGNED NOT NULL,
  nombre_completo VARCHAR(150) NOT NULL,
  tipo_documento VARCHAR(30) NULL,
  numero_documento VARCHAR(50) NULL,
  nacionalidad VARCHAR(80) NULL,
  procedencia VARCHAR(120) NULL,
  hora_llegada TIME NULL,
  placa_vehiculo VARCHAR(20) NULL,
  foto_documento VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_checkin_reserva FOREIGN KEY (reserva_id)
    REFERENCES reservas(id) ON DELETE CASCADE,
  INDEX idx_checkin_reserva (reserva_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
