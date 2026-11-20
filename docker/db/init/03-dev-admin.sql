-- Usuario local de desarrollo (password: admin123). No usar en producción.
DELETE FROM `usuarios` WHERE `usuario_nombre` = 'admin';
INSERT INTO `usuarios` (`usuario_nombre`, `usuario_clave`, `usuario_clave_hash`, `usuario_email`, `usuario_freg`, `nivel_acceso`)
VALUES (
  'admin',
  MD5('admin123'),
  NULL,
  'admin@local.test',
  NOW(),
  '1'
);
