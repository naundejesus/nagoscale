-- Migración: soporte multi-usuario (cada usuario ve solo sus propios apartamentos/reservas).
-- Ejecutar UNA sola vez. Los apartamentos existentes quedan asignados al primer
-- usuario creado en la cuenta (el único que existe hasta ahora).

ALTER TABLE apartamentos ADD COLUMN user_id INT UNSIGNED NULL AFTER id;
UPDATE apartamentos SET user_id = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE user_id IS NULL;
ALTER TABLE apartamentos MODIFY user_id INT UNSIGNED NOT NULL;
ALTER TABLE apartamentos ADD CONSTRAINT fk_apartamentos_user FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE apartamentos ADD INDEX idx_apartamentos_user (user_id);
