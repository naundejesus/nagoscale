-- Migración: número de WhatsApp de contacto del propietario.
-- Ejecutar UNA sola vez.

ALTER TABLE users
  ADD COLUMN whatsapp VARCHAR(20) NULL AFTER password_hash;
