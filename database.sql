SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS iupy_catalogo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iupy_catalogo;

CREATE TABLE IF NOT EXISTS productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  tipo_catalogo ENUM('pelotero','extra','barra','carpita','juego','salon','otro') NOT NULL DEFAULT 'pelotero',
  categoria VARCHAR(80) NOT NULL DEFAULT 'sin-categoria',
  tamano_etiqueta VARCHAR(80) DEFAULT NULL,
  ancho DECIMAL(7,2) DEFAULT NULL,
  largo DECIMAL(7,2) DEFAULT NULL,
  precio_base DECIMAL(12,2) NOT NULL DEFAULT 0,
  duracion VARCHAR(80) DEFAULT '3 horas',
  stock INT UNSIGNED NOT NULL DEFAULT 1,
  descripcion TEXT DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tipo_activo (tipo_catalogo, activo),
  INDEX idx_medidas (ancho, largo)
);

CREATE TABLE IF NOT EXISTS producto_imagenes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  ruta VARCHAR(255) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_imagen_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reglas_precios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_catalogo ENUM('todos','pelotero','extra','barra','carpita','juego','salon','otro') NOT NULL DEFAULT 'todos',
  ancho DECIMAL(7,2) DEFAULT NULL,
  largo DECIMAL(7,2) DEFAULT NULL,
  precio DECIMAL(12,2) NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_regla_medidas (tipo_catalogo, ancho, largo, activa)
);

-- Productos tomados de las páginas estáticas originales. Las inserciones son
-- idempotentes: importar este archivo más de una vez no duplica los nombres.
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Super Gigante','pelotero','grande','6x5m',6,5,48000,'3 horas',1,'Pelotero inflable grande'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Super Gigante');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Castillo 3x5','pelotero','chico','3x5m',3,5,35000,'3 horas',1,'Castillo inflable'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Castillo 3x5');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Pelotero 3x3','pelotero','chico','3x3m',3,3,35000,'3 horas',1,'Pelotero compacto'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Pelotero 3x3');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Reloj 5x5','juego','mecanico','5x5m',5,5,85000,'2 horas',1,'Juego mecánico inflable'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Reloj 5x5');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Castillo 3x4','pelotero','chico','3x4m',3,4,35000,'3 horas',1,'Castillo inflable'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Castillo 3x4');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Escalador Pileta','pelotero','agua','2x5m',2,5,54000,'3 horas',1,'Inflable para pileta'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Escalador Pileta');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Deslizador Común','pelotero','agua','2x7m',2,7,54000,'3 horas',1,'Deslizador inflable'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Deslizador Común');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Deslizador Pileta','pelotero','agua','2x7m',2,7,54000,'3 horas',1,'Deslizador para pileta'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Deslizador Pileta');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Desafío 6x6','pelotero','grande','6x6m',6,6,48000,'3 horas',1,'Inflable de desafío'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Desafío 6x6');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Aventura 4 6x5','pelotero','grande','6x5m',6,5,48000,'3 horas',1,'Inflable Aventura 4'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Aventura 4 6x5');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Canchita 8x4','pelotero','grande','8x4m',8,4,48000,'3 horas',1,'Canchita inflable'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Canchita 8x4');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Canchita Box 8x4','pelotero','grande','8x4m',8,4,50000,'3 horas',1,'Canchita inflable con formato box'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Canchita Box 8x4');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Jirafa 4x5','pelotero','mediano','4x5m',4,5,42000,'3 horas',1,'Inflable Jirafa'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Jirafa 4x5');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Selvita 4x4','pelotero','mediano','4x4m',4,4,42000,'3 horas',1,'Inflable Selvita'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Selvita 4x4');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Mini Pelotero 2x2','pelotero','chico','2x2m',2,2,28000,'3 horas',1,'Pelotero compacto'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Mini Pelotero 2x2');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Yenga Gigante','extra','juego',NULL,NULL,NULL,6000,'3 horas',1,'Juego extra'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Yenga Gigante');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Tejo','extra','juego',NULL,NULL,NULL,27000,'3 horas',1,'Juego extra'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Tejo');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT '4 en Ralla','extra','juego',NULL,NULL,NULL,6000,'3 horas',1,'Juego extra'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre IN ('4 en Ralla','4 en Línea Gigante'));
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Metegol','extra','juego',NULL,NULL,NULL,27000,'3 horas',1,'Juego extra'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Metegol');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Sala Blanda 8 unidades','extra','juego',NULL,NULL,NULL,35000,'3 horas',1,'Sala blanda de 8 unidades'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Sala Blanda 8 unidades');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Cama Elástica 3m','extra','juego','3m diámetro',NULL,NULL,38000,'3 horas',1,'Cama elástica de 3 metros de diámetro'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Cama Elástica 3m');
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion)
SELECT 'Super Desafío','extra','animaciones',NULL,NULL,NULL,75000,'1 hora',1,'Super desafío'
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE nombre='Super Desafío');

-- Artículos visibles en el catálogo de WhatsApp (se excluyen expresamente los fletes).
-- 0 en precio_base significa que el precio no se alcanza a leer o aparece contradictorio.
INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,precio_base,duracion,stock,descripcion)
SELECT v.nombre,v.tipo_catalogo,v.categoria,v.tamano,v.precio,v.duracion,1,v.descripcion
FROM (
  SELECT 'Aventura 3' nombre,'pelotero' tipo_catalogo,'grande' categoria,'5x4m' tamano,60000 precio,'3 horas' duracion,'Inflable Aventura 3. Base 5x4 metros.' descripcion UNION ALL
  SELECT 'Pelotero Mickey y Minnie','pelotero','mediano','6x5m',53000,'3 horas','Pelotero temático de Mickey y Minnie, medidas aproximadas 6x5 metros.' UNION ALL
  SELECT 'Pelotero Simba','pelotero','mediano','4x5m',70000,'3 horas','Pelotero Simba 4x5 con tobogán y rampa.' UNION ALL
  SELECT 'Pelotero Tuerca','pelotero','mediano','4x5m',53000,'3 horas','Base 4x5 metros. Altura aproximada 3 metros.' UNION ALL
  SELECT 'Islita Inflable','pelotero','agua','2.5x2.5m',43000,'3 horas','Base 2,5x2,5 metros. Altura 2,5 metros.' UNION ALL
  SELECT 'Pelotero Tobogán Doble','pelotero','mediano','6x6m',53000,'3 horas','Pelotero con tobogán doble. Medidas 6x6 metros.' UNION ALL
  SELECT 'Deslizador de Agua','pelotero','agua','2.5x7m',70000,'3 horas','Deslizador de agua de 2,5 metros de ancho por 7 de largo.' UNION ALL
  SELECT 'Deslizador Pileta','pelotero','agua','2x7m',70000,'3 horas','Deslizador de 7 metros de largo por 2 de ancho, termina en pileta de 2 metros.' UNION ALL
  SELECT 'Pelotero con Tobogán y Pileta','pelotero','agua',NULL,70000,'3 horas','Pelotero con tobogán y pileta.' UNION ALL
  SELECT 'Pelotero Grande y Cama','extra','combos',NULL,105000,'3 horas','Combo de pelotero grande y cama elástica.' UNION ALL
  SELECT 'Combo Pelotero Chico, Metegol y Cama','extra','combos',NULL,110000,'3 horas','Combo de pelotero chico, metegol y cama elástica.' UNION ALL
  SELECT 'Combo Mediano, Cama y Metegol','extra','combos',NULL,120000,'3 horas','Combo de pelotero mediano, cama elástica y metegol.' UNION ALL
  SELECT 'Pelotero y Metegol con Cama','extra','combos',NULL,130000,'3 horas','Combo de pelotero grande, metegol y cama elástica.' UNION ALL
  SELECT 'Pelotero Mediano y Metegol','extra','combos',NULL,80000,'3 horas','Combo de pelotero mediano y metegol.' UNION ALL
  SELECT 'Cancha Rugby','pelotero','deportes',NULL,60000,'3 horas','Cancha inflable de rugby.' UNION ALL
  SELECT 'Animación Acción Rápida','extra','animaciones',NULL,75000,'1 hora','Animación para 20 niños con stand de glitter y actividades.' UNION ALL
  SELECT 'Animación Gran Aventura','extra','animaciones',NULL,130000,'3 horas','Animación para 20 chicos con stand de glitter y juegos.' UNION ALL
  SELECT 'Animación Súper Desafío','extra','animaciones',NULL,95000,'2 horas','Animación para 20 niños con stand de glitter y actividades.' UNION ALL
  SELECT 'Stand de Glitter','extra','animaciones',NULL,60000,'1 hora','Stand de glitter para hasta 20 niños.' UNION ALL
  SELECT 'Stand de Maquillaje y Glitter','extra','animaciones',NULL,60000,'','Stand artístico de maquillaje y glitter.' UNION ALL
  SELECT 'Barra de Tragos y Pochoclo','barra','barras',NULL,85000,'','Barra de tragos frutales y pochoclos.' UNION ALL
  SELECT 'Barra de IUPY','barra','barras',NULL,110000,'','Barra para 20 chicos. Incluye pochoclos y tragos.' UNION ALL
  SELECT 'Barra Simple','barra','barras',NULL,60000,'1 hora','Barra simple de una hora.' UNION ALL
  SELECT 'Carpitas Lupy','carpita','pijamadas',NULL,120000,'','Propuesta de pijamada IUPY. Consultar detalles.' UNION ALL
  SELECT 'IUPY Pijamadas','carpita','pijamadas',NULL,120000,'','Propuesta de pijamada IUPY. Consultar detalles.' UNION ALL
  SELECT 'Deco Cumple','extra','decoración',NULL,0,'','Decoración para cumpleaños. Consultar opciones y precio.' UNION ALL
  SELECT 'Deco Personalizada','extra','decoración',NULL,60000,'','Decoración personalizada con globos y telas.' UNION ALL
  SELECT 'Banderín 8 Letras','extra','decoración',NULL,10000,'','Banderín personalizado de 8 letras.' UNION ALL
  SELECT 'Roseta de Papel 40 cm','extra','decoración',NULL,2000,'','Roseta de papel de 40 centímetros de diámetro.' UNION ALL
  SELECT 'Globo de Papel de Seda 40 cm','extra','decoración',NULL,2000,'','Globo de papel de seda de 40 centímetros de diámetro.' UNION ALL
  SELECT 'Centro de Mesa con Chupetines','extra','souvenirs',NULL,8000,'','Centro de mesa con chupetines.' UNION ALL
  SELECT 'Centro de Mesa Golosinera','extra','souvenirs',NULL,5000,'','Centro de mesa golosinera personalizado con el personaje y color que elijas.' UNION ALL
  SELECT 'Bolsitas Triángulo para Souvenirs','extra','souvenirs',NULL,6000,'','Pack de 8 bolsitas triangulares para souvenirs o golosinas.' UNION ALL
  SELECT 'Bolsitas de Golosinas x20','extra','souvenirs',NULL,3000,'','Pack de 20 bolsitas para golosinas.' UNION ALL
  SELECT 'Tarjetitas Digitales','extra','invitaciones',NULL,3000,'','Tarjetitas digitales personalizadas.' UNION ALL
  SELECT 'Sticker para Cumpleaños A3','extra','personalizados',NULL,5000,'','Impresión autoadhesiva en tamaño A3.' UNION ALL
  SELECT 'Vasitos Personalizados x20','extra','personalizados',NULL,5000,'','Pack de 20 vasitos personalizados.' UNION ALL
  SELECT 'Alquiler de Piñatas','extra','juegos',NULL,10000,'','Alquiler de piñata. Consultar modelos disponibles.' UNION ALL
  SELECT 'Piñatas de Personajes','extra','personalizados',NULL,35000,'','Piñata personalizada de personajes.' UNION ALL
  SELECT 'Tatuajes Temporales x40','extra','personalizados',NULL,10000,'','Pack de 40 tatuajes temporales para cumpleaños.' UNION ALL
  SELECT 'Cumple Spa','salon','eventos',NULL,120000,'2 horas 30 minutos','Experiencia Cumple Spa. Duración aproximada: 2 horas y 30 minutos.' UNION ALL
  SELECT 'Salón para Cumples Infantiles','salon','eventos',NULL,0,'','Consultar disponibilidad y precio según fecha y agregados.' UNION ALL
  SELECT 'Quincho','salon','eventos',NULL,0,'','Consultar disponibilidad y precio según fecha.' UNION ALL
  SELECT 'Eventos en IUPY','salon','eventos',NULL,0,'','Precios según fecha y tipo de evento. Consultar.' UNION ALL
  SELECT 'Cumple Infantil en IUPY','salon','eventos',NULL,0,'','Precios según fecha y agregados. Consultar.' UNION ALL
  SELECT 'Juntadas en IUPY','salon','eventos',NULL,0,'','Precios según fecha. Consultar.' UNION ALL
  SELECT 'Taller de Arte','extra','actividades',NULL,70000,'','Taller de arte para cumpleaños. Consultar detalles.' UNION ALL
  SELECT 'Disfraces Inflables x3','extra','disfraces',NULL,100000,'','Pack de tres disfraces inflables de aproximadamente 2 metros de altura.' UNION ALL
  SELECT 'Disfraz Dino Inflable','extra','disfraces',NULL,35000,'','Disfraz inflable de dinosaurio, aproximadamente 2 metros de altura.' UNION ALL
  SELECT 'Disfraz Tiburón Inflable','extra','disfraces',NULL,35000,'','Disfraz inflable de tiburón.' UNION ALL
  SELECT 'Disfraz Extraterrestre','extra','disfraces',NULL,35000,'','Disfraz inflable de extraterrestre.' UNION ALL
  SELECT 'Yenga Gigante','juego','juegos',NULL,10000,'3 horas','Juego gigante de Yenga.' UNION ALL
  SELECT 'Reloj Mecánico 2 hs','juego','mecanico','5x5m',100000,'2 horas','Reloj mecánico de 5x5 metros.' UNION ALL
  SELECT 'Metegol','juego','juegos',NULL,35000,'3 horas','Alquiler de metegol.' UNION ALL
  SELECT 'Sala Blanda 8 unidades','juego','juegos',NULL,45000,'3 horas','Ocho juegos variados. No incluye piso de goma.' UNION ALL
  SELECT 'Cama Elástica 3m','juego','juegos','3m diámetro',46000,'3 horas','Cama elástica de 3 metros de diámetro con red protectora.'
) AS v
WHERE NOT EXISTS (SELECT 1 FROM productos p WHERE p.nombre=v.nombre);

-- Actualización de los artículos existentes cuyos precios actuales aparecen en WhatsApp.
UPDATE productos SET precio_base=60000, tamano_etiqueta='9x5m', ancho=9, largo=5,
 descripcion='Super Gigante, medidas aproximadas 9x5 metros.' WHERE nombre='Super Gigante';
UPDATE productos SET precio_base=45000, descripcion='Pelotero 3x3, altura aproximada 2,5 metros.' WHERE nombre='Pelotero 3x3';
UPDATE productos SET precio_base=45000 WHERE nombre='Castillo 3x5';
UPDATE productos SET precio_base=45000 WHERE nombre='Castillo 3x4';
UPDATE productos SET precio_base=100000, duracion='2 horas', descripcion='Reloj mecánico de 5x5 metros.' WHERE nombre='Reloj 5x5';
UPDATE productos SET precio_base=60000, descripcion='Canchita inflable, base 8x4 metros.' WHERE nombre='Canchita 8x4';
UPDATE productos SET precio_base=60000, descripcion='Pelotero Aventura 4, base aproximada 6x5 metros.' WHERE nombre='Aventura 4 6x5';
UPDATE productos SET precio_base=60000 WHERE nombre='Desafío 6x6';
UPDATE productos SET precio_base=46000, descripcion='Cama elástica de 3 metros de diámetro con red protectora.' WHERE nombre='Cama Elástica 3m';
UPDATE productos SET precio_base=35000 WHERE nombre='Metegol';
UPDATE productos SET precio_base=45000, descripcion='Ocho juegos variados. No incluye piso de goma.' WHERE nombre='Sala Blanda 8 unidades';
UPDATE productos SET precio_base=10000, descripcion='Juego gigante de Yenga.' WHERE nombre='Yenga Gigante';
UPDATE productos SET nombre='4 en Línea Gigante', tipo_catalogo='juego', categoria='juegos', precio_base=0,
 descripcion='Juego gigante. El catálogo muestra precios distintos; consultar.' WHERE nombre='4 en Ralla';
UPDATE productos SET precio_base=70000 WHERE nombre='Deslizador Pileta';
UPDATE productos SET precio_base=70000, descripcion='Escalador inflable con pileta.' WHERE nombre='Escalador Pileta';
UPDATE productos SET precio_base=53000 WHERE nombre='Jirafa 4x5';
-- Repara texto que hubiera quedado guardado como UTF-8 interpretado como latin1.
UPDATE productos SET
  nombre=CONVERT(CAST(CONVERT(nombre USING latin1) AS BINARY) USING utf8mb4),
  categoria=CONVERT(CAST(CONVERT(categoria USING latin1) AS BINARY) USING utf8mb4),
  tamano_etiqueta=IF(tamano_etiqueta IS NULL,NULL,CONVERT(CAST(CONVERT(tamano_etiqueta USING latin1) AS BINARY) USING utf8mb4)),
  duracion=IF(duracion IS NULL,NULL,CONVERT(CAST(CONVERT(duracion USING latin1) AS BINARY) USING utf8mb4)),
  descripcion=IF(descripcion IS NULL,NULL,CONVERT(CAST(CONVERT(descripcion USING latin1) AS BINARY) USING utf8mb4))
WHERE nombre LIKE '%Ã%' OR nombre LIKE '%Â%' OR categoria LIKE '%Ã%' OR categoria LIKE '%Â%'
   OR tamano_etiqueta LIKE '%Ã%' OR tamano_etiqueta LIKE '%Â%'
   OR duracion LIKE '%Ã%' OR duracion LIKE '%Â%'
   OR descripcion LIKE '%Ã%' OR descripcion LIKE '%Â%';
DELETE p1 FROM productos p1 JOIN productos p2
  ON p1.nombre=p2.nombre AND p1.id>p2.id
  WHERE p1.nombre='4 en Línea Gigante';
DELETE FROM productos WHERE nombre IN ('Pelotero Grande y Metegol','Pelotero y Metegol y Cama (combo)');

-- Fotos seleccionadas de la carpeta original pagina-iupy/img.
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/super-gigante.jpg',1 FROM productos p
WHERE p.nombre='Super Gigante' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/super-gigante.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/castillo-3x5.jpg',1 FROM productos p
WHERE p.nombre='Castillo 3x5' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/castillo-3x5.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/castillo-3x4.jpg',1 FROM productos p
WHERE p.nombre='Castillo 3x4' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/castillo-3x4.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/escalador-pileta.jpg',1 FROM productos p
WHERE p.nombre='Escalador Pileta' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/escalador-pileta.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/desafio-6x6.jpg',1 FROM productos p
WHERE p.nombre='Desafío 6x6' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/desafio-6x6.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/aventura-4.jpg',1 FROM productos p
WHERE p.nombre='Aventura 4 6x5' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/aventura-4.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/canchita-8x4.jpg',1 FROM productos p
WHERE p.nombre='Canchita 8x4' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/canchita-8x4.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/jirafa-4x5.jpg',1 FROM productos p
WHERE p.nombre='Jirafa 4x5' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/jirafa-4x5.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/selvita-4x4.jpg',1 FROM productos p
WHERE p.nombre='Selvita 4x4' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/selvita-4x4.jpg');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/cama-elastica.png',1 FROM productos p
WHERE p.nombre='Cama Elástica 3m' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/cama-elastica.png');
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,'img/metegol.jpg',1 FROM productos p
WHERE p.nombre='Metegol' AND NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta='img/metegol.jpg');

-- Fotos originales adicionales para las nuevas tarjetas.
INSERT INTO producto_imagenes (producto_id,ruta,orden)
SELECT p.id,v.ruta,1 FROM (
  SELECT 'Pelotero Tuerca' nombre,'img/pelotero-tuerca.jpg' ruta UNION ALL
  SELECT 'Pelotero Tuerca','img/pelotero-tuerca-2.jpg' UNION ALL
  SELECT 'Pelotero Tuerca','img/pelotero-tuerca-3.jpg' UNION ALL
  SELECT 'Pelotero Mickey y Minnie','img/pelotero-mickey-minnie.jpg' UNION ALL
  SELECT 'Pelotero Mickey y Minnie','img/pelotero-mickey-minnie-2.jpg' UNION ALL
  SELECT 'Pelotero Mickey y Minnie','img/pelotero-mickey-minnie-3.jpg' UNION ALL
  SELECT 'Castillo 3x4','img/pelotero-castillo-3x4-galeria.jpg' UNION ALL
  SELECT 'Pelotero Tobogán Doble','img/pelotero-tobogan-doble.jpg' UNION ALL
  SELECT 'Pelotero Grande y Cama','img/combo-pelotero-cama.jpg' UNION ALL
  SELECT 'Combo Pelotero Chico, Metegol y Cama','img/combo-pelotero-cama.jpg' UNION ALL
  SELECT 'Pelotero y Metegol con Cama','img/combo-pelotero-cama.jpg' UNION ALL
  SELECT 'Carpitas Lupy','img/pijamadas-carpitas.jpg' UNION ALL
  SELECT 'IUPY Pijamadas','img/pijamadas-carpitas.jpg' UNION ALL
  SELECT 'Barra de IUPY','img/barra-iupy.jpg' UNION ALL
  SELECT 'Canchita 8x4','img/pelotero-canchita-galeria.jpg' UNION ALL
  SELECT 'Canchita 8x4','img/pelotero-canchita-2.jpg' UNION ALL
  SELECT 'Canchita 8x4','img/pelotero-canchita-3.jpg' UNION ALL
  SELECT 'Super Gigante','img/super-gigante-galeria.jpg' UNION ALL
  SELECT 'Super Gigante','img/super-gigante-2.jpg' UNION ALL
  SELECT 'Super Gigante','img/super-gigante-3.jpg' UNION ALL
  SELECT 'Aventura 4 6x5','img/pelotero-aventura-4.jpg'
) v JOIN productos p ON p.nombre=v.nombre
WHERE NOT EXISTS (SELECT 1 FROM producto_imagenes i WHERE i.producto_id=p.id AND i.ruta=v.ruta);
