-- Migración: solicitudes de reserva de clientes finales + correo de notificación.
-- Ejecutar UNA sola vez.

ALTER TABLE users ADD COLUMN email VARCHAR(120) NULL AFTER username;

CREATE TABLE IF NOT EXISTS solicitudes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  nombre_cliente VARCHAR(120) NOT NULL,
  telefono VARCHAR(50) NOT NULL,
  correo VARCHAR(120) NULL,
  mensaje VARCHAR(500) NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  reserva_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_solicitudes_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_solicitudes_reserva FOREIGN KEY (reserva_id)
    REFERENCES reservas(id) ON DELETE SET NULL,
  INDEX idx_solicitudes_apartamento (apartamento_id),
  INDEX idx_solicitudes_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
