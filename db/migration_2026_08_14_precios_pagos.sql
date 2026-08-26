-- Migración: características del apartamento, precio por noche/fin de semana,
-- datos de pago (Nequi/Bancolombia) y valor estimado en las solicitudes.
-- Ejecutar UNA sola vez.

ALTER TABLE users
  ADD COLUMN nequi_numero VARCHAR(20) NULL,
  ADD COLUMN bancolombia_tipo_cuenta VARCHAR(20) NULL,
  ADD COLUMN bancolombia_numero VARCHAR(30) NULL,
  ADD COLUMN bancolombia_titular VARCHAR(120) NULL;

ALTER TABLE apartamentos
  ADD COLUMN habitaciones SMALLINT UNSIGNED NULL,
  ADD COLUMN cocinas SMALLINT UNSIGNED NULL,
  ADD COLUMN banos SMALLINT UNSIGNED NULL,
  ADD COLUMN capacidad_huespedes SMALLINT UNSIGNED NULL,
  ADD COLUMN precio_noche DECIMAL(12,2) NULL,
  ADD COLUMN precio_fin_semana DECIMAL(12,2) NULL;

ALTER TABLE solicitudes
  ADD COLUMN valor_estimado DECIMAL(12,2) NULL AFTER fecha_fin;
