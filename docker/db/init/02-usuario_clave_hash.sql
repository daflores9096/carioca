-- Hash moderno (bcrypt/argon). Si está lleno, la API lo usa; si no, valida MD5 en usuario_clave.
ALTER TABLE `usuarios`
  ADD COLUMN `usuario_clave_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `usuario_clave`;
