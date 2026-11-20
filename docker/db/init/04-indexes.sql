-- Índices para listados/orden (no cambian datos ni columnas de negocio)
ALTER TABLE `cliente`
  ADD INDEX `idx_cliente_nom` (`nom_cli`),
  ADD INDEX `idx_cliente_ciudad` (`ciudad_cli`);

ALTER TABLE `proveedor`
  ADD INDEX `idx_proveedor_nom` (`nom_pro`),
  ADD INDEX `idx_proveedor_ciudad` (`ciudad_pro`);

-- item no trae PK en el dump; índice por código y nombre
ALTER TABLE `item`
  ADD INDEX `idx_item_cod` (`cod_item`),
  ADD INDEX `idx_item_nom` (`nom_item`);
