-- Esquema de base de datos: Inmuebles por Días (NagoScale)
-- Importar este archivo en phpMyAdmin sobre la base de datos vacía que creaste en hPanel.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS apartamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  direccion VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  fecha_reserva DATE NOT NULL,
  plataforma ENUM('airbnb','booking','web') NOT NULL,
  valor_total DECIMAL(12,2) NOT NULL,
  valor_propietario DECIMAL(12,2) NOT NULL,
  valor_comision DECIMAL(12,2) NOT NULL,
  notas VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE RESTRICT,
  INDEX idx_reservas_fecha (fecha_reserva),
  INDEX idx_reservas_plataforma (plataforma),
  INDEX idx_reservas_apartamento (apartamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
