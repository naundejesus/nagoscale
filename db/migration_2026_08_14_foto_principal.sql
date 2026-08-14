-- Migración: elegir cuál foto es la principal (portada) de cada apartamento.
-- Ejecutar UNA sola vez. Marca como principal la foto más antigua de cada
-- apartamento que ya tenga fotos, para que no quede ninguno sin portada.

ALTER TABLE apartamento_fotos ADD COLUMN es_principal TINYINT(1) NOT NULL DEFAULT 0;

UPDATE apartamento_fotos f
JOIN (
  SELECT apartamento_id, MIN(id) AS primera_foto_id
  FROM apartamento_fotos
  GROUP BY apartamento_id
) primeras ON primeras.primera_foto_id = f.id
SET f.es_principal = 1;
