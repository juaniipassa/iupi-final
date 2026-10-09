<?php
require __DIR__ . '/includes/auth.php';
iupyRequireAdminAccess();
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';
$mensaje = '';
$error = '';
$editarId = (int)($_GET['editar'] ?? 0);
$tipos = ['pelotero'=>'Pelotero','extra'=>'Extra','barra'=>'Barra','carpita'=>'Carpita','juego'=>'Juego','salon'=>'Salón','otro'=>'Otro'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear_producto') {
            $nombre = trim($_POST['nombre'] ?? '');
            $tipo = $_POST['tipo_catalogo'] ?? 'pelotero';
            $categoria = trim($_POST['categoria'] ?? 'sin-categoria');
            $tamano = trim($_POST['tamano_etiqueta'] ?? '');
            $ancho = ($_POST['ancho'] ?? '') !== '' ? (float)$_POST['ancho'] : null;
            $largo = ($_POST['largo'] ?? '') !== '' ? (float)$_POST['largo'] : null;
            $precio = (float)($_POST['precio_base'] ?? 0);
            $duracion = trim($_POST['duracion'] ?? '3 horas');
            $stock = max(0, (int)($_POST['stock'] ?? 1));
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
            if (!isset($tipos[$tipo])) throw new RuntimeException('Elegí un tipo de catálogo válido.');
            if ($precio < 0) throw new RuntimeException('El precio no puede ser negativo.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$nombre,$tipo,$categoria,$tamano ?: null,$ancho,$largo,$precio,$duracion,$stock,$descripcion]);
            $id = (int)$pdo->lastInsertId();
            $rutas = preg_split('/\r\n|\r|\n/', trim($_POST['imagenes'] ?? ''));
            $imgStmt = $pdo->prepare('INSERT INTO producto_imagenes (producto_id,ruta,orden) VALUES (?,?,?)');
            $orden = 0;
            foreach ($rutas as $ruta) {
                $ruta = trim($ruta);
                if ($ruta !== '') $imgStmt->execute([$id,$ruta,$orden++]);
            }
            $pdo->commit();
            $mensaje = 'Producto agregado correctamente. Ya aparece en el catálogo que elegiste.';
        } elseif ($accion === 'actualizar_producto') {
            $id = (int)($_POST['id'] ?? 0);
            $editarId = $id;
            $nombre = trim($_POST['nombre'] ?? '');
            $tipo = $_POST['tipo_catalogo'] ?? 'pelotero';
            $categoria = trim($_POST['categoria'] ?? 'sin-categoria');
            $tamano = trim($_POST['tamano_etiqueta'] ?? '');
            $ancho = ($_POST['ancho'] ?? '') !== '' ? (float)$_POST['ancho'] : null;
            $largo = ($_POST['largo'] ?? '') !== '' ? (float)$_POST['largo'] : null;
            $precio = (float)($_POST['precio_base'] ?? 0);
            $duracion = trim($_POST['duracion'] ?? '3 horas');
            $stock = max(0, (int)($_POST['stock'] ?? 1));
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($id <= 0) throw new RuntimeException('No se encontró el producto que querés editar.');
            if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
            if (!isset($tipos[$tipo])) throw new RuntimeException('Elegí un tipo de catálogo válido.');
            if ($precio < 0) throw new RuntimeException('El precio no puede ser negativo.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE productos SET nombre=?,tipo_catalogo=?,categoria=?,tamano_etiqueta=?,ancho=?,largo=?,precio_base=?,duracion=?,stock=?,descripcion=? WHERE id=?');
            $stmt->execute([$nombre,$tipo,$categoria,$tamano ?: null,$ancho,$largo,$precio,$duracion,$stock,$descripcion,$id]);
            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare('SELECT id FROM productos WHERE id=?');
                $check->execute([$id]);
                if (!$check->fetch()) throw new RuntimeException('No se encontró el producto que querés editar.');
            }
            $pdo->prepare('DELETE FROM producto_imagenes WHERE producto_id=?')->execute([$id]);
            $rutas = preg_split('/\r\n|\r|\n/', trim($_POST['imagenes'] ?? ''));
            $imgStmt = $pdo->prepare('INSERT INTO producto_imagenes (producto_id,ruta,orden) VALUES (?,?,?)');
            $orden = 0;
            foreach ($rutas as $ruta) {
                $ruta = trim($ruta);
                if ($ruta !== '') $imgStmt->execute([$id,$ruta,$orden++]);
            }
            $pdo->commit();
            $mensaje = 'Producto actualizado correctamente.';
            $editarId = 0;
        } elseif ($accion === 'regla_precio') {
            $tipo = $_POST['regla_tipo'] ?? 'todos';
            $ancho = (float)($_POST['regla_ancho'] ?? 0);
            $largo = (float)($_POST['regla_largo'] ?? 0);
            $precio = (float)($_POST['regla_precio'] ?? 0);
            if (!in_array($tipo, array_merge(['todos'],array_keys($tipos)), true) || $ancho <= 0 || $largo <= 0 || $precio < 0) {
                throw new RuntimeException('Completá tipo, ancho, largo y precio correctamente.');
            }
            $stmt = $pdo->prepare('INSERT INTO reglas_precios (tipo_catalogo,ancho,largo,precio,activa) VALUES (?,?,?,?,1)');
            $stmt->execute([$tipo,$ancho,$largo,$precio]);
            $mensaje = 'Regla de precio guardada. Los productos con esas medidas usarán el nuevo precio.';
        } elseif ($accion === 'cambiar_estado') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE productos SET activo = IF(activo=1,0,1) WHERE id=?');
            $stmt->execute([$id]);
            $mensaje = 'Estado del producto actualizado.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar. Revisá los datos y la base de datos.';
    }
}
$productos = $pdo->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll();
$reglas = $pdo->query('SELECT * FROM reglas_precios ORDER BY actualizado_en DESC, id DESC')->fetchAll();
$productoEnEdicion = null;
$imagenesEnEdicion = [];
if ($editarId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id=?');
    $stmt->execute([$editarId]);
    $productoEnEdicion = $stmt->fetch();
    if ($productoEnEdicion) $imagenesEnEdicion = imagenesProducto($pdo, $editarId);
    else $error = 'No se encontró el producto seleccionado.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administración IUPY</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#fff1f7;font-family:Arial,sans-serif;color:#292929}.wrap{max-width:1100px;margin:auto;padding:20px}h1,h2{color:#ed1e79}.panel{background:white;padding:20px;margin:18px 0;border-radius:14px;box-shadow:0 2px 10px #0001}.formgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}label{display:flex;flex-direction:column;gap:6px;font-weight:bold;font-size:14px}input,select,textarea{padding:10px;border:1px solid #bbb;border-radius:7px;font:inherit;width:100%}textarea{min-height:80px}button,.link{display:inline-block;background:#ed1e79;color:white;border:0;border-radius:8px;padding:11px 16px;cursor:pointer;text-decoration:none;margin-top:12px}.notice{padding:12px;border-radius:8px;background:#dcfce7}.error{background:#fee2e2}.tablewrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:650px}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}th{background:#ffe0ef}
</style></head><body><div class="wrap">
<h1>Administración de productos IUPY</h1><p><a class="link" href="catalogo.php" target="_blank">Ver catálogo</a></p>
<?php if ($mensaje): ?><p class="notice"><?= h($mensaje) ?></p><?php endif; ?>
<?php if ($error): ?><p class="notice error"><?= h($error) ?></p><?php endif; ?>
<?php if ($productoEnEdicion): ?>
<section class="panel" id="editar-producto"><h2>Editar producto #<?= (int)$productoEnEdicion['id'] ?></h2>
<form method="post"><input type="hidden" name="accion" value="actualizar_producto"><input type="hidden" name="id" value="<?= (int)$productoEnEdicion['id'] ?>">
<div class="formgrid">
<label>Nombre *<input name="nombre" required value="<?= h($productoEnEdicion['nombre']) ?>"></label>
<label>¿En qué catálogo aparece? *<select name="tipo_catalogo" required><?php foreach($tipos as $k=>$v): ?><option value="<?= h($k) ?>" <?= $productoEnEdicion['tipo_catalogo'] === $k ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Filtro/categoría<input name="categoria" value="<?= h($productoEnEdicion['categoria']) ?>"></label>
<label>Etiqueta de tamaño<input name="tamano_etiqueta" value="<?= h($productoEnEdicion['tamano_etiqueta'] ?? '') ?>"></label>
<label>Ancho en metros<input type="number" name="ancho" min="0" step="0.01" value="<?= h($productoEnEdicion['ancho'] ?? '') ?>"></label>
<label>Largo en metros<input type="number" name="largo" min="0" step="0.01" value="<?= h($productoEnEdicion['largo'] ?? '') ?>"></label>
<label>Precio base ($)<input type="number" name="precio_base" min="0" step="1" required value="<?= h($productoEnEdicion['precio_base']) ?>"></label>
<label>Duración<input name="duracion" value="<?= h($productoEnEdicion['duracion'] ?? '') ?>"></label>
<label>Stock/unidades<input type="number" name="stock" min="0" step="1" value="<?= (int)$productoEnEdicion['stock'] ?>"></label>
</div>
<p><label>Descripción<textarea name="descripcion"><?= h($productoEnEdicion['descripcion'] ?? '') ?></textarea></label></p>
<p><label>Rutas de imágenes (una por línea)<textarea name="imagenes"><?= h(implode("\n", $imagenesEnEdicion)) ?></textarea></label></p>
<button type="submit">Guardar cambios</button> <a class="link" href="admin.php">Cancelar</a>
</form></section>
<?php endif; ?>
<section class="panel"><h2>1. Agregar una tarjeta/producto</h2>
<p>Elegí el tipo de catálogo. La tarjeta aparecerá automáticamente en esa sección; el modal también se genera solo.</p>
<form method="post"><input type="hidden" name="accion" value="crear_producto">
<div class="formgrid">
<label>Nombre *<input name="nombre" required placeholder="Ej. Castillo 3x5"></label>
<label>¿En qué catálogo aparece? *
<select name="tipo_catalogo" required><?php foreach($tipos as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Filtro/categoría<input name="categoria" placeholder="chico, mediano, grande, agua, mecanico..."></label>
<label>Etiqueta de tamaño<input name="tamano_etiqueta" placeholder="Ej. 3x5m"></label>
<label>Ancho en metros<input type="number" name="ancho" min="0" step="0.01" placeholder="Ej. 3"></label>
<label>Largo en metros<input type="number" name="largo" min="0" step="0.01" placeholder="Ej. 5"></label>
<label>Precio base ($)<input type="number" name="precio_base" min="0" step="1" required value="0"></label>
<label>Duración<input name="duracion" value="3 horas"></label>
<label>Stock/unidades<input type="number" name="stock" min="0" step="1" value="1"></label>
</div>
<p><label>Descripción<textarea name="descripcion" placeholder="Detalles del producto"></textarea></label></p>
<p><label>Rutas de imágenes (una por línea)<textarea name="imagenes" placeholder="img/castillo-1.jpg&#10;img/castillo-2.jpg"></textarea></label></p>
<button type="submit">Guardar producto</button></form></section>
<section class="panel"><h2>2. Actualizar precios por tamaño</h2>
<p>Esta regla modifica el precio mostrado por todos los productos que coincidan con tipo y medidas. Si elegís “Todos los tipos”, afecta a cualquier producto de esas medidas. La regla específica de un tipo tiene prioridad sobre “Todos los tipos”.</p>
<form method="post"><input type="hidden" name="accion" value="regla_precio"><div class="formgrid">
<label>Tipo de catálogo<select name="regla_tipo"><option value="todos">Todos los tipos</option><?php foreach($tipos as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Ancho (m)<input type="number" name="regla_ancho" min="0.01" step="0.01" required placeholder="6"></label>
<label>Largo (m)<input type="number" name="regla_largo" min="0.01" step="0.01" required placeholder="5"></label>
<label>Nuevo precio ($)<input type="number" name="regla_precio" min="0" step="1" required placeholder="52000"></label>
</div><button type="submit">Guardar regla de precio</button></form>
<h3>Reglas guardadas</h3><div class="tablewrap"><table><thead><tr><th>Tipo</th><th>Medidas</th><th>Precio</th><th>Estado</th></tr></thead><tbody>
<?php foreach($reglas as $r): ?><tr><td><?= h($r['tipo_catalogo']) ?></td><td><?= h($r['ancho'].' x '.$r['largo'].' m') ?></td><td>$<?= number_format((float)$r['precio'],0,',','.') ?></td><td><?= $r['activa'] ? 'Activa' : 'Inactiva' ?></td></tr><?php endforeach; ?>
<?php if (!$reglas): ?><tr><td colspan="4">Todavía no hay reglas.</td></tr><?php endif; ?>
</tbody></table></div></section>
<section class="panel"><h2>3. Productos cargados</h2><div class="tablewrap"><table><thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Medidas</th><th>Precio base</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach($productos as $p): ?><tr><td><?= (int)$p['id'] ?></td><td><?= h($p['nombre']) ?></td><td><?= h($p['tipo_catalogo']) ?></td><td><?= h($p['tamano_etiqueta'] ?: '-') ?></td><td>$<?= number_format((float)$p['precio_base'],0,',','.') ?></td><td><?= $p['activo'] ? 'Activo' : 'Oculto' ?></td><td><a class="link" href="?editar=<?= (int)$p['id'] ?>#editar-producto">Editar</a><form method="post" style="margin:0"><input type="hidden" name="accion" value="cambiar_estado"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit"><?= $p['activo'] ? 'Ocultar' : 'Activar' ?></button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</div></body></html>
