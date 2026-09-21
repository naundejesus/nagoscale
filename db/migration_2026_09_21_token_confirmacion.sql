-- Migración: token de confirmación de alta entropía para solicitudes públicas.
-- Corrige un IDOR: reservar_confirmacion.php ya no expone datos de cualquier
-- solicitud por sólo conocer/enumerar el ID (Fase 3 de remediación de seguridad).
-- Ejecutar UNA sola vez.

ALTER TABLE solicitudes
  ADD COLUMN token_confirmacion CHAR(64) NULL AFTER estado,
  ADD COLUMN token_expira_at DATETIME NULL AFTER token_confirmacion,
  ADD UNIQUE KEY idx_solicitudes_token (token_confirmacion);
