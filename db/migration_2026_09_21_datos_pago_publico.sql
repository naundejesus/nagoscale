-- Migración: consentimiento explícito para mostrar datos de pago (Nequi/Bancolombia)
-- en la página pública de reservas. WhatsApp sigue siendo siempre público
-- (Fase 3 de remediación de seguridad).
-- Ejecutar UNA sola vez.

ALTER TABLE users
  ADD COLUMN mostrar_datos_pago_publico TINYINT(1) NOT NULL DEFAULT 0;

-- Política de migración: preservar el comportamiento actual para cuentas que
-- YA tenían datos de pago cargados (ya dependían de que se mostraran para
-- cobrar); las cuentas nuevas quedan en 0 (opt-in) hasta que el propietario
-- lo active a propósito desde su perfil.
UPDATE users
SET mostrar_datos_pago_publico = 1
WHERE (nequi_numero IS NOT NULL AND nequi_numero <> '')
   OR (bancolombia_numero IS NOT NULL AND bancolombia_numero <> '');
