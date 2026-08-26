-- Esquema de base de datos: Inmuebles por Días (NagoScale)
-- Importar este archivo en phpMyAdmin sobre la base de datos vacía que creaste en hPanel.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  whatsapp VARCHAR(20) NULL,
  nequi_numero VARCHAR(20) NULL,
  bancolombia_tipo_cuenta VARCHAR(20) NULL,
  bancolombia_numero VARCHAR(30) NULL,
  bancolombia_titular VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS apartamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  propietario VARCHAR(120) NULL,
  direccion VARCHAR(255) NULL,
  habitaciones SMALLINT UNSIGNED NULL,
  cocinas SMALLINT UNSIGNED NULL,
  banos SMALLINT UNSIGNED NULL,
  capacidad_huespedes SMALLINT UNSIGNED NULL,
  precio_noche DECIMAL(12,2) NULL,
  precio_fin_semana DECIMAL(12,2) NULL,
  ciudad VARCHAR(100) NULL,
  zona VARCHAR(100) NULL,
  descripcion TEXT NULL,
  amenidades TEXT NULL,
  latitud DECIMAL(10,7) NULL,
  longitud DECIMAL(10,7) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_apartamentos_user FOREIGN KEY (user_id)
    REFERENCES users(id),
  INDEX idx_apartamentos_user (user_id),
  INDEX idx_apartamentos_ciudad (ciudad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS apartamento_fotos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  archivo VARCHAR(255) NOT NULL,
  es_principal TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fotos_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE CASCADE,
  INDEX idx_fotos_apartamento (apartamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  plataforma ENUM('airbnb','booking','web') NOT NULL,
  valor_total DECIMAL(12,2) NOT NULL,
  valor_propietario DECIMAL(12,2) NOT NULL,
  valor_comision DECIMAL(12,2) NOT NULL,
  notas VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_apartamento FOREIGN KEY (apartamento_id)
    REFERENCES apartamentos(id) ON DELETE RESTRICT,
  INDEX idx_reservas_fecha_inicio (fecha_inicio),
  INDEX idx_reservas_fecha_fin (fecha_fin),
  INDEX idx_reservas_plataforma (plataforma),
  INDEX idx_reservas_apartamento (apartamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS solicitudes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apartamento_id INT UNSIGNED NOT NULL,
  nombre_cliente VARCHAR(120) NOT NULL,
  telefono VARCHAR(50) NOT NULL,
  correo VARCHAR(120) NULL,
  mensaje VARCHAR(500) NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  huespedes SMALLINT UNSIGNED NULL,
  valor_estimado DECIMAL(12,2) NULL,
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

CREATE TABLE IF NOT EXISTS login_intentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  actualizado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_login_intentos_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
