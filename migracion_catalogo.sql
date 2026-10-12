-- Ejecutar una sola vez sobre la base iupy_catalogo existente.
USE iupy_catalogo;

ALTER TABLE productos
  MODIFY tipo_catalogo ENUM('pelotero','salon','barra','animacion','carpita','juego','souvenir','extra','otro') NOT NULL DEFAULT 'pelotero',
  ADD COLUMN edad_recomendada VARCHAR(60) DEFAULT NULL AFTER descripcion,
  ADD COLUMN filtro_tamano ENUM('pequeno','mediano','grande') DEFAULT NULL AFTER edad_recomendada,
  ADD COLUMN caracteristica ENUM('agua','electrico','tradicional') DEFAULT NULL AFTER filtro_tamano,
  ADD COLUMN external_item_id INT UNSIGNED DEFAULT NULL AFTER caracteristica,
  ADD INDEX idx_external_item (external_item_id);

UPDATE productos SET tipo_catalogo='animacion' WHERE categoria='animaciones' OR nombre LIKE 'Animación %' OR nombre LIKE 'Stand de %' OR nombre='Taller de Arte';
UPDATE productos SET tipo_catalogo='souvenir' WHERE categoria IN ('souvenirs','personalizados','invitaciones');
UPDATE productos SET tipo_catalogo='juego' WHERE categoria IN ('juego','juegos') AND tipo_catalogo IN ('extra','otro');

UPDATE productos SET filtro_tamano = CASE
  WHEN LOWER(categoria) IN ('chico','pequeño','pequeno') THEN 'pequeno'
  WHEN LOWER(categoria)='mediano' THEN 'mediano'
  WHEN LOWER(categoria)='grande' THEN 'grande'
  WHEN GREATEST(COALESCE(ancho,0),COALESCE(largo,0)) > 0 AND GREATEST(COALESCE(ancho,0),COALESCE(largo,0)) <= 4 THEN 'pequeno'
  WHEN GREATEST(COALESCE(ancho,0),COALESCE(largo,0)) > 4 AND GREATEST(COALESCE(ancho,0),COALESCE(largo,0)) <= 6 THEN 'mediano'
  WHEN GREATEST(COALESCE(ancho,0),COALESCE(largo,0)) > 6 THEN 'grande'
  ELSE filtro_tamano END
WHERE tipo_catalogo='pelotero';
UPDATE productos SET caracteristica='agua'
WHERE tipo_catalogo='pelotero' AND (LOWER(categoria)='agua' OR nombre LIKE '%Pileta%' OR nombre LIKE '%Agua%' OR nombre='Islita Inflable');
UPDATE productos SET caracteristica='electrico'
WHERE tipo_catalogo='pelotero' AND (LOWER(categoria) LIKE '%mecanico%' OR LOWER(categoria) LIKE '%eléctrico%' OR LOWER(categoria) LIKE '%electrico%');
UPDATE productos SET caracteristica='tradicional'
WHERE tipo_catalogo='pelotero' AND caracteristica IS NULL;

UPDATE productos SET external_item_id=51 WHERE nombre='Super Gigante';
UPDATE productos SET external_item_id=7 WHERE nombre='Castillo 3x5';
UPDATE productos SET external_item_id=8 WHERE nombre='Pelotero 3x3';
UPDATE productos SET external_item_id=72 WHERE nombre IN ('Reloj 5x5','Reloj Mecánico 2 hs');
UPDATE productos SET external_item_id=13 WHERE nombre IN ('Castillo 3x4','Pelotero Castillo 3x4');
UPDATE productos SET external_item_id=63 WHERE nombre='Desafío 6x6';
UPDATE productos SET external_item_id=68 WHERE nombre='Aventura 4 6x5';
UPDATE productos SET external_item_id=12 WHERE nombre='Canchita 8x4';
UPDATE productos SET external_item_id=69 WHERE nombre='Canchita Box 8x4';
UPDATE productos SET external_item_id=2 WHERE nombre='Jirafa 4x5';
UPDATE productos SET external_item_id=62 WHERE nombre='Selvita 4x4';
UPDATE productos SET external_item_id=56 WHERE nombre='Tejo';
UPDATE productos SET external_item_id=24 WHERE nombre='Metegol';
UPDATE productos SET external_item_id=15 WHERE nombre='Sala Blanda 8 unidades';
UPDATE productos SET external_item_id=11 WHERE nombre='Cama Elástica 3m';
UPDATE productos SET external_item_id=3 WHERE nombre IN ('Deslizador Común','Deslizador de Agua','Deslizador Pileta');

