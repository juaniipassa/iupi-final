<?php
require __DIR__ . '/includes/auth.php';
iupyRequireSiteAccess();
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
header('Content-Type: text/html; charset=UTF-8');

$tiposValidos = ['todos','pelotero','extra','barra','carpita','juego','salon','otro'];
$tipo = $_GET['tipo'] ?? 'todos';
if (!in_array($tipo, $tiposValidos, true)) $tipo = 'todos';
$busqueda = trim($_GET['q'] ?? '');

 $sql = 'SELECT * FROM productos WHERE activo = 1';
 $params = [];
 if ($tipo !== 'todos') { $sql .= ' AND tipo_catalogo = ?'; $params[] = $tipo; }
 if ($busqueda !== '') { $sql .= ' AND (nombre LIKE ? OR categoria LIKE ? OR descripcion LIKE ?)'; $like = '%'.$busqueda.'%'; array_push($params, $like, $like, $like); }
 $sql .= ' ORDER BY tipo_catalogo, nombre';
 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $productos = $stmt->fetchAll();
shuffle($productos);
$etiquetas = ['todos'=>'Todos','pelotero'=>'Peloteros','extra'=>'Extras','barra'=>'Barras','carpita'=>'Carpitas','juego'=>'Juegos','salon'=>'Salón','otro'=>'Otros'];
$whatsapp = '5493434155474';
$idsDisponibilidad = [
    'Super Gigante' => 51,
    'Castillo 3x5' => 7,
    'Pelotero 3x3' => 8,
    'Reloj 5x5' => 72,
    'Castillo 3x4' => 13,
    'Desafío 6x6' => 63,
    'Aventura 4 6x5' => 68,
    'Canchita 8x4' => 12,
    'Canchita Box 8x4' => 69,
    'Jirafa 4x5' => 2,
    'Selvita 4x4' => 62,
    'Tejo' => 56,
    'Metegol' => 24,
    'Sala Blanda 8 unidades' => 15,
    'Cama Elástica 3m' => 11,
    'Deslizador Común' => 3,
];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>IUPY | Catálogo</title>
<style>
:root{--rosa:#ed1e79;--verde:#a5d608;--fondo:#fff4f8;--tinta:#30242b;--muted:#765f6a}*{box-sizing:border-box}body{margin:0;background:var(--fondo);font-family:Arial,sans-serif;color:var(--tinta)}
header{padding:34px 18px 22px;background:linear-gradient(135deg,#ffd6e7,#fff8dc);text-align:center}header>a{color:inherit;text-decoration:none}.brand{display:flex;align-items:center;justify-content:center;gap:12px}.brand img{width:68px;height:68px;object-fit:contain}.brand h1{color:var(--rosa);font-size:clamp(28px,5vw,44px);margin:0}.intro{color:var(--muted);margin:12px auto 22px;max-width:620px}.filters{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}.filters a,.btn{background:var(--rosa);color:white;text-decoration:none;border:0;padding:10px 15px;border-radius:999px;cursor:pointer;font-weight:700}.filters a:hover,.filters a.active{background:#c91464}.search{display:flex;justify-content:center;gap:8px;max-width:560px;margin:18px auto 0}.search input{min-width:0;flex:1;padding:11px 14px;border:1px solid #e6b5cb;border-radius:999px;font:inherit}.search button{padding:11px 18px;border:0;border-radius:999px;background:var(--verde);font-weight:700;cursor:pointer}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));grid-auto-rows:1fr;gap:18px;padding:24px 18px;max-width:1250px;margin:auto;align-items:stretch}.card{background:white;border-radius:18px;overflow:hidden;box-shadow:0 5px 18px #49223812;display:flex;flex-direction:column;min-height:490px;height:100%;max-width:340px;width:100%;margin:0 auto;transition:transform .18s ease,box-shadow .18s ease}.card:hover{transform:translateY(-3px);box-shadow:0 10px 26px #49223820}.card>img{width:100%;height:205px;flex:0 0 205px;object-fit:cover;background:#ffe7f0}.content{padding:16px 16px 8px;text-align:left;display:flex;flex:1;flex-direction:column}.tag{display:inline-block;background:#fff0f6;color:#a90f58;border-radius:99px;padding:5px 10px;font-size:12px;font-weight:700;text-transform:capitalize;min-height:26px;align-self:flex-start}.content h3{color:var(--rosa);margin:10px 0 7px;min-height:2.5em;max-height:2.5em;overflow:hidden}.content p{margin:7px 0;color:var(--muted);min-height:1.2em}.buttons{display:flex;justify-content:center;align-content:center;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px 16px;min-height:112px}.buttons a{background:var(--rosa);color:white;text-decoration:none;padding:9px 13px;border-radius:9px;font-weight:700}.buttons button{background:var(--verde);border:0;border-radius:9px;padding:9px 13px;cursor:pointer;font-weight:700}
.modal{display:none;position:fixed;inset:0;background:#0009;z-index:10;align-items:center;justify-content:center;padding:15px}.modal.open{display:flex}.modal-content{position:relative;background:white;border-radius:18px;padding:25px;max-width:760px;width:100%;max-height:90vh;overflow:auto;text-align:left}.close{position:absolute;right:14px;top:8px;border:0;background:transparent;font-size:30px;cursor:pointer}.imagenes{display:flex;gap:12px;overflow-x:auto;margin:15px 0}.imagenes img{height:190px;max-width:240px;object-fit:cover;border-radius:10px}.precio{font-size:20px;font-weight:bold;color:#b42318}.empty{padding:50px 20px;text-align:center;color:var(--muted);grid-column:1/-1}footer{text-align:center;color:var(--muted);padding:24px;font-size:14px}
@media(max-width:520px){header{padding-top:24px}.brand img{width:52px;height:52px}.search{flex-direction:column;padding:0 12px}.search button{width:100%}.grid{padding:16px 12px}}
.availability-check{display:flex;flex-basis:100%;gap:6px;align-items:center;flex-wrap:wrap}.availability-check input{padding:8px;border:1px solid #e6b5cb;border-radius:8px;font:inherit}.availability-check button{background:#a5d608;border:0;border-radius:9px;padding:9px 13px;cursor:pointer;font-weight:700}.availability-result{flex-basis:100%;font-size:14px;color:var(--muted);min-height:18px}.availability-result.ok{color:#167340;font-weight:700}.availability-result.no{color:#b42318;font-weight:700}.availability-link{display:none}
</style>
</head>
<body>
<header>
<a href="catalogo.php" class="brand"><img src="img/logo.png" alt="" onerror="this.style.display='none'"><h1>IUPY Fábrica de Fiestas</h1></a>
<p class="intro">Peloteros, juegos y todo para que tu fiesta sea inolvidable. Elegí una categoría o buscá lo que necesitás.</p>
<nav class="filters">
<?php foreach ($etiquetas as $clave=>$etiqueta): ?>
<a class="<?= $tipo === $clave ? 'active' : '' ?>" href="?tipo=<?= h($clave) ?>"><?= h($etiqueta) ?></a>
<?php endforeach; ?>
</nav>
<form class="search" method="get" action="catalogo.php">
<input type="hidden" name="tipo" value="<?= h($tipo) ?>">
<input type="search" name="q" value="<?= h($busqueda) ?>" placeholder="Buscar por nombre o categoría" aria-label="Buscar productos">
<button type="submit">Buscar</button>
</form>
</header>
<main class="grid">
<?php foreach ($productos as $p):
    $fotos = imagenesProducto($pdo, (int)$p['id']);
    $fotoPrincipal = $fotos[0] ?? 'img/sin-imagen.svg';
    $precio = precioEfectivo($pdo, $p);
    $medidas = $p['tamano_etiqueta'] ?: (($p['ancho'] && $p['largo']) ? $p['ancho'].'x'.$p['largo'].'m' : '');
    $textoWa = rawurlencode('Hola, vengo de la página de IUPY. Quiero consultar por '.$p['nombre'].'.');
    $idRemoto = $idsDisponibilidad[$p['nombre']] ?? null;
?>
<article class="card">
<img src="<?= h($fotoPrincipal) ?>" alt="<?= h($p['nombre']) ?>" onerror="this.onerror=null;this.src='img/sin-imagen.svg'">
<div class="content"><span class="tag"><?= h($etiquetas[$p['tipo_catalogo']] ?? 'Otros') ?><?= $p['categoria'] && $p['categoria'] !== 'sin-categoria' ? ' · '.h($p['categoria']) : '' ?></span><h3><?= h($p['nombre']) ?></h3>
<p>Tamaño: <?= $medidas ? h($medidas) : 'Consultar' ?></p>
<?php if ($precio > 0): ?><p class="precio">Precio: $<?= number_format($precio,0,',','.') ?></p><?php else: ?><p class="precio">Consultar precio</p><?php endif; ?>
<p>Duración: <?= $p['duracion'] ? h($p['duracion']) : 'Consultar' ?></p>
</div>
<div class="buttons">
<?php if ($idRemoto !== null): ?><form class="availability-check" data-producto="<?= (int)$p['id'] ?>" data-stock="<?= (int)$p['stock'] ?>">
<label>Fecha <input type="date" name="fecha" required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+3 months')) ?>"></label><button type="submit">Consultar disponibilidad</button><span class="availability-result" aria-live="polite"></span>
</form><?php endif; ?>
<a target="_blank" rel="noopener" href="https://wa.me/<?= h($whatsapp) ?>?text=<?= h($textoWa) ?>">WhatsApp</a>
<button type="button" onclick="abrirModal('producto-<?= (int)$p['id'] ?>')">Ver detalles +</button>
</div>
</article>
<div class="modal" id="producto-<?= (int)$p['id'] ?>" role="dialog" aria-modal="true" aria-label="Detalles de <?= h($p['nombre']) ?>" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal-content"><button class="close" type="button" aria-label="Cerrar" onclick="this.closest('.modal').classList.remove('open')">&times;</button>
<div class="imagenes">
<?php foreach ($fotos as $foto): ?><img src="<?= h($foto) ?>" alt="<?= h($p['nombre']) ?>" onerror="this.onerror=null;this.src='img/sin-imagen.svg'"><?php endforeach; ?>
</div>
<h2><?= h($p['nombre']) ?></h2>
<?php if ($p['descripcion']): ?><p><?= nl2br(h($p['descripcion'])) ?></p><?php endif; ?>
<?php if ($medidas): ?><p>Medidas: <?= h($medidas) ?></p><?php endif; ?>
<?php if ($precio > 0): ?><p class="precio">Precio: $<?= number_format($precio,0,',','.') ?></p><?php else: ?><p class="precio">Consultar precio</p><?php endif; ?>
<p>Duración: <?= h($p['duracion']) ?></p>
<a class="btn" target="_blank" rel="noopener" href="https://wa.me/<?= h($whatsapp) ?>?text=<?= h($textoWa) ?>">Consultar por WhatsApp</a>
</div></div>
<?php endforeach; ?>
<?php if (!$productos): ?><div class="empty">No encontramos productos para esta búsqueda.<br>Probá otra categoría o consultanos por WhatsApp.</div><?php endif; ?>
</main>
<footer>IUPY Fábrica de Fiestas · Consultas por WhatsApp</footer>
<script>
function abrirModal(id){const modal=document.getElementById(id);if(modal)modal.classList.add('open')}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal.open').forEach(m=>m.classList.remove('open'))});
document.querySelectorAll('.availability-check').forEach(form=>form.addEventListener('submit',async event=>{
 event.preventDefault();const result=form.querySelector('.availability-result');const button=form.querySelector('button');const fecha=form.elements.fecha.value;
 if(!fecha)return;result.textContent='Consultando…';result.className='availability-result';button.disabled=true;
 try{const response=await fetch('disponibilidad.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({producto:form.dataset.producto,fecha})});const data=await response.json();if(!response.ok)throw new Error(data.error||'No se pudo consultar.');result.textContent=data.mensaje;result.classList.add(data.disponible?'ok':'no');}
 catch(error){result.textContent=error.message||'No se pudo consultar la disponibilidad.';}finally{button.disabled=false;}
}));
</script>
</body></html>
