<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
iupyRequireAdminAccess();
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$tipos = [
    'pelotero' => 'Peloteros', 'salon' => 'Salón', 'barra' => 'Barras',
    'animacion' => 'Animación', 'carpita' => 'Carpitas', 'juego' => 'Juegos',
    'souvenir' => 'Souvenirs', 'extra' => 'Extras / Otros', 'otro' => 'Extras / Otros'
];
$edades = ['0 a 3 años','4 a 6 años','7 a 10 años','11 años o más','Todas las edades','Consultar'];
$tamanos = ['pequeno'=>'Pequeño','mediano'=>'Mediano','grande'=>'Grande'];
$caracteristicas = ['agua'=>'De agua','electrico'=>'Eléctrico/mecánico','tradicional'=>'Tradicional'];
$mensaje = '';
$error = '';
$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;

iupyStartSession();
if (empty($_SESSION['iupy_admin_csrf'])) $_SESSION['iupy_admin_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['iupy_admin_csrf'];

function campoTexto(string $nombre, int $max): string {
    $valor = trim((string)($_POST[$nombre] ?? ''));
    if (mb_strlen($valor, 'UTF-8') > $max) throw new RuntimeException("El campo $nombre es demasiado largo.");
    return $valor;
}

function guardarImagenes(PDO $pdo, int $productoId, string $lineas): void {
    $rutas = preg_split('/\r\n|\r|\n/', trim($lineas)) ?: [];
    $rutas = array_values(array_unique(array_filter(array_map('trim', $rutas), static fn($ruta) => $ruta !== '')));
    foreach ($rutas as $ruta) {
        if (strlen($ruta) > 255 || str_contains($ruta, '..') || preg_match('~^(?:https?:)?//~i', $ruta)) {
            throw new RuntimeException('Usá rutas locales dentro de img/, una por línea.');
        }
    }
    $pdo->prepare('DELETE FROM producto_imagenes WHERE producto_id=?')->execute([$productoId]);
    $stmt = $pdo->prepare('INSERT INTO producto_imagenes (producto_id,ruta,orden) VALUES (?,?,?)');
    foreach ($rutas as $orden => $ruta) $stmt->execute([$productoId, $ruta, $orden]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = (string)($_POST['csrf'] ?? '');
        if (!hash_equals($csrf, $token)) throw new RuntimeException('La sesión venció. Actualizá la página e intentá de nuevo.');
        $accion = (string)($_POST['accion'] ?? '');

        if (in_array($accion, ['crear_producto','actualizar_producto'], true)) {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = campoTexto('nombre', 150);
            $tipo = (string)($_POST['tipo_catalogo'] ?? 'pelotero');
            $categoria = campoTexto('categoria', 80) ?: 'sin-categoria';
            $tamanoEtiqueta = campoTexto('tamano_etiqueta', 80);
            $descripcion = campoTexto('descripcion', 5000);
            $duracion = campoTexto('duracion', 80);
            $edad = campoTexto('edad_recomendada', 60);
            $filtroTamano = (string)($_POST['filtro_tamano'] ?? '');
            $caracteristica = (string)($_POST['caracteristica'] ?? '');
            $ancho = trim((string)($_POST['ancho'] ?? '')) !== '' ? (float)$_POST['ancho'] : null;
            $largo = trim((string)($_POST['largo'] ?? '')) !== '' ? (float)$_POST['largo'] : null;
            $precio = (float)($_POST['precio_base'] ?? 0);
            $stock = filter_var($_POST['stock'] ?? 1, FILTER_VALIDATE_INT);
            $externalId = trim((string)($_POST['external_item_id'] ?? '')) !== '' ? filter_var($_POST['external_item_id'], FILTER_VALIDATE_INT) : null;
            if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
            if (!isset($tipos[$tipo])) throw new RuntimeException('Elegí una categoría principal válida.');
            if ($precio < 0 || $stock === false || $stock < 0 || ($externalId !== null && ($externalId === false || $externalId < 1))) throw new RuntimeException('Revisá precio, stock e ID externo.');
            if ($filtroTamano !== '' && !isset($tamanos[$filtroTamano])) throw new RuntimeException('Elegí un tamaño de filtro válido.');
            if ($caracteristica !== '' && !isset($caracteristicas[$caracteristica])) throw new RuntimeException('Elegí una característica válida.');
            if ($tipo !== 'pelotero') { $edad = ''; $filtroTamano = ''; $caracteristica = ''; }

            $values = [$nombre,$tipo,$categoria,$tamanoEtiqueta ?: null,$ancho,$largo,$precio,$duracion ?: null,(int)$stock,$descripcion ?: null,$edad ?: null,$filtroTamano ?: null,$caracteristica ?: null,$externalId ?: null];
            $pdo->beginTransaction();
            if ($accion === 'crear_producto') {
                $sql = 'INSERT INTO productos (nombre,tipo_catalogo,categoria,tamano_etiqueta,ancho,largo,precio_base,duracion,stock,descripcion,edad_recomendada,filtro_tamano,caracteristica,external_item_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                $pdo->prepare($sql)->execute($values);
                $id = (int)$pdo->lastInsertId();
                $mensaje = 'Producto agregado al catálogo.';
            } else {
                if ($id < 1) throw new RuntimeException('No se encontró el producto que querés editar.');
                $sql = 'UPDATE productos SET nombre=?,tipo_catalogo=?,categoria=?,tamano_etiqueta=?,ancho=?,largo=?,precio_base=?,duracion=?,stock=?,descripcion=?,edad_recomendada=?,filtro_tamano=?,caracteristica=?,external_item_id=? WHERE id=?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([...$values,$id]);
                if ($stmt->rowCount() === 0) {
                    $check = $pdo->prepare('SELECT id FROM productos WHERE id=?');
                    $check->execute([$id]);
                    if (!$check->fetch()) throw new RuntimeException('No se encontró el producto que querés editar.');
                }
                $mensaje = 'Producto actualizado.';
            }
            guardarImagenes($pdo, $id, (string)($_POST['imagenes'] ?? ''));
            $pdo->commit();
            $editarId = 0;
        } elseif ($accion === 'actualizar_precios_peloteros') {
            $precio = filter_var($_POST['precio_masivo'] ?? null, FILTER_VALIDATE_FLOAT);
            $tamano = (string)($_POST['tamano_precio'] ?? 'todos');
            $caracteristica = (string)($_POST['caracteristica_precio'] ?? 'todos');
            if ($precio === false || $precio < 0) throw new RuntimeException('Ingresá un precio válido.');
            if ($tamano !== 'todos' && !isset($tamanos[$tamano])) throw new RuntimeException('Elegí un tamaño válido.');
            if ($caracteristica !== 'todos' && !isset($caracteristicas[$caracteristica])) throw new RuntimeException('Elegí una característica válida.');
            $where = ["tipo_catalogo='pelotero'"];
            $params = [$precio];
            if ($tamano !== 'todos') { $where[] = 'filtro_tamano=?'; $params[] = $tamano; }
            if ($caracteristica !== 'todos') { $where[] = 'caracteristica=?'; $params[] = $caracteristica; }
            $stmt = $pdo->prepare('UPDATE productos SET precio_base=? WHERE '.implode(' AND ', $where));
            $stmt->execute($params);
            $mensaje = 'Precio aplicado a '.$stmt->rowCount().' pelotero(s) que coinciden con esos filtros.';
        } elseif ($accion === 'cambiar_estado') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) throw new RuntimeException('No se encontró el producto.');
            $stmt = $pdo->prepare('UPDATE productos SET activo=IF(activo=1,0,1) WHERE id=?');
            $stmt->execute([$id]);
            $mensaje = 'Estado del producto actualizado.';
        } else {
            throw new RuntimeException('La acción solicitada no es válida.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar. Revisá los datos y la base.';
    }
}

$productos = $pdo->query('SELECT * FROM productos ORDER BY tipo_catalogo,nombre')->fetchAll();
$productoEnEdicion = null;
$imagenesEnEdicion = [];
if ($editarId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id=?');
    $stmt->execute([$editarId]);
    $productoEnEdicion = $stmt->fetch() ?: null;
    if ($productoEnEdicion) $imagenesEnEdicion = imagenesProducto($pdo, $editarId);
    else $error = 'No se encontró el producto seleccionado.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administración IUPY</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#fff1f7;font-family:Arial,sans-serif;color:#292929}.wrap{max-width:1200px;margin:auto;padding:20px}h1,h2{color:#ed1e79}.panel{background:white;padding:20px;margin:18px 0;border-radius:14px;box-shadow:0 2px 10px #0001}.formgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}label{display:flex;flex-direction:column;gap:6px;font-weight:bold;font-size:14px}input,select,textarea{padding:10px;border:1px solid #bbb;border-radius:7px;font:inherit;width:100%}textarea{min-height:80px}button,.link{display:inline-block;background:#ed1e79;color:white;border:0;border-radius:8px;padding:11px 16px;cursor:pointer;text-decoration:none;margin-top:12px}.notice{padding:12px;border-radius:8px;background:#dcfce7}.error{background:#fee2e2}.tablewrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:850px}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;vertical-align:top}th{background:#ffe0ef}.hint{color:#765f6a;font-size:14px}.actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.actions form{margin:0}
</style></head><body><div class="wrap">
<h1>Administración de productos IUPY</h1><p><a class="link" href="catalogo.php" target="_blank" rel="noopener">Ver catálogo</a></p>
<?php if ($mensaje): ?><p class="notice"><?= h($mensaje) ?></p><?php endif; ?>
<?php if ($error): ?><p class="notice error"><?= h($error) ?></p><?php endif; ?>

<?php if ($productoEnEdicion): ?>
<section class="panel" id="editar-producto"><h2>Editar producto #<?= (int)$productoEnEdicion['id'] ?></h2>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="actualizar_producto"><input type="hidden" name="id" value="<?= (int)$productoEnEdicion['id'] ?>">
<div class="formgrid">
<label>Nombre *<input name="nombre" required value="<?= h($productoEnEdicion['nombre']) ?>"></label>
<label>Categoría principal *<select name="tipo_catalogo" required><?php foreach($tipos as $k=>$v): ?><option value="<?= h($k) ?>" <?= $productoEnEdicion['tipo_catalogo']===$k?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Subcategoría o detalle<input name="categoria" value="<?= h($productoEnEdicion['categoria']) ?>"></label>
<label>Tamaño visible<input name="tamano_etiqueta" value="<?= h($productoEnEdicion['tamano_etiqueta'] ?? '') ?>"></label>
<label>Ancho en metros<input type="number" name="ancho" min="0" step="0.01" value="<?= h($productoEnEdicion['ancho'] ?? '') ?>"></label>
<label>Largo en metros<input type="number" name="largo" min="0" step="0.01" value="<?= h($productoEnEdicion['largo'] ?? '') ?>"></label>
<label>Precio base ($)<input type="number" name="precio_base" min="0" step="1" required value="<?= h($productoEnEdicion['precio_base']) ?>"></label>
<label>Duración<input name="duracion" value="<?= h($productoEnEdicion['duracion'] ?? '') ?>"></label>
<label>Stock/unidades<input type="number" name="stock" min="0" step="1" required value="<?= (int)$productoEnEdicion['stock'] ?>"></label>
<label>Edad recomendada<input name="edad_recomendada" list="edades-recomendadas" value="<?= h($productoEnEdicion['edad_recomendada'] ?? '') ?>" placeholder="Ej. 4 a 8 años"></label>
<label>Tamaño para el subfiltro<select name="filtro_tamano"><option value="">Sin filtro</option><?php foreach($tamanos as $k=>$v): ?><option value="<?= h($k) ?>" <?= ($productoEnEdicion['filtro_tamano']??'')===$k?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Tipo de pelotero<select name="caracteristica"><option value="">Sin filtro</option><?php foreach($caracteristicas as $k=>$v): ?><option value="<?= h($k) ?>" <?= ($productoEnEdicion['caracteristica']??'')===$k?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>ID de alquiler para consultar disponibilidad<input type="number" name="external_item_id" min="1" step="1" value="<?= h($productoEnEdicion['external_item_id'] ?? '') ?>" placeholder="Dejalo vacío hasta tener el código"></label>
</div>
<datalist id="edades-recomendadas"><?php foreach($edades as $edad): ?><option value="<?= h($edad) ?>"><?php endforeach; ?></datalist>
<p><label>Descripción<textarea name="descripcion"><?= h($productoEnEdicion['descripcion'] ?? '') ?></textarea></label></p>
<p><label>Rutas de imágenes locales (una por línea)<textarea name="imagenes"><?= h(implode("\n", $imagenesEnEdicion)) ?></textarea></label></p>
<button type="submit">Guardar cambios</button> <a class="link" href="admin.php">Cancelar edición</a>
</form></section>
<?php endif; ?>

<section class="panel"><h2><?= $productoEnEdicion ? 'Agregar otro producto' : 'Agregar un producto' ?></h2>
<p class="hint">Elegí la categoría principal. Los filtros de tamaño, tipo y edad se usan en Peloteros; si todavía no tenés un código externo, dejá ese campo vacío y completalo luego.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="crear_producto">
<div class="formgrid">
<label>Nombre *<input name="nombre" required></label>
<label>Categoría principal *<select name="tipo_catalogo" required><?php foreach($tipos as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Subcategoría o detalle<input name="categoria" value="sin-categoria"></label>
<label>Tamaño visible<input name="tamano_etiqueta" placeholder="Ej. 3x5m"></label>
<label>Ancho en metros<input type="number" name="ancho" min="0" step="0.01"></label>
<label>Largo en metros<input type="number" name="largo" min="0" step="0.01"></label>
<label>Precio base ($)<input type="number" name="precio_base" min="0" step="1" required value="0"></label>
<label>Duración<input name="duracion" value="3 horas"></label>
<label>Stock/unidades<input type="number" name="stock" min="0" step="1" required value="1"></label>
<label>Edad recomendada<input name="edad_recomendada" list="edades-recomendadas" placeholder="Ej. 4 a 8 años"></label>
<label>Tamaño para subfiltro<select name="filtro_tamano"><option value="">Sin filtro</option><?php foreach($tamanos as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Tipo de pelotero<select name="caracteristica"><option value="">Sin filtro</option><?php foreach($caracteristicas as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>ID de alquiler para disponibilidad<input type="number" name="external_item_id" min="1" step="1" placeholder="Opcional"></label>
</div>
<datalist id="edades-recomendadas"><?php foreach($edades as $edad): ?><option value="<?= h($edad) ?>"><?php endforeach; ?></datalist>
<p><label>Descripción<textarea name="descripcion" placeholder="Detalles del producto"></textarea></label></p>
<p><label>Rutas de imágenes locales, una por línea<textarea name="imagenes" placeholder="img/castillo-1.jpg&#10;img/castillo-2.jpg"></textarea></label></p>
<button type="submit">Guardar producto</button></form></section>

<section class="panel"><h2>Actualizar precios de peloteros por grupo</h2>
<p class="hint">El cambio se aplica a todos los peloteros que coincidan con los filtros. Dejá “Todos” para no filtrar por ese dato. Las consultas de disponibilidad también actualizarán automáticamente el precio del producto consultado cuando el sistema externo lo informe.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="actualizar_precios_peloteros"><div class="formgrid">
<label>Tamaño<select name="tamano_precio"><option value="todos">Todos los tamaños</option><?php foreach($tamanos as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Tipo<select name="caracteristica_precio"><option value="todos">Todos los tipos</option><?php foreach($caracteristicas as $k=>$v): ?><option value="<?= h($k) ?>"><?= h($v) ?></option><?php endforeach; ?></select></label>
<label>Nuevo precio ($)<input type="number" name="precio_masivo" min="0" step="1" required></label>
</div><button type="submit">Aplicar a los peloteros seleccionados</button></form></section>

<section class="panel"><h2>Productos cargados</h2><div class="tablewrap"><table><thead><tr><th>Nombre</th><th>Categoría</th><th>Edad/tipo</th><th>ID alquiler</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($productos as $p): ?><tr>
<td><?= h($p['nombre']) ?><br><small><?= h($p['tamano_etiqueta'] ?: '-') ?></small></td>
<td><?= h($tipos[$p['tipo_catalogo']] ?? 'Extras / Otros') ?><?= $p['categoria'] && $p['categoria']!=='sin-categoria' ? ' · '.h($p['categoria']) : '' ?></td>
<td><?= h($p['edad_recomendada'] ?: '-') ?><br><?= h($tamanos[$p['filtro_tamano'] ?? ''] ?? '') ?> <?= h($caracteristicas[$p['caracteristica'] ?? ''] ?? '') ?></td>
<td><?= h($p['external_item_id'] ?: 'Pendiente') ?></td>
<td>$<?= number_format((float)$p['precio_base'],0,',','.') ?></td>
<td><?= $p['activo'] ? 'Activo' : 'Oculto' ?></td>
<td><div class="actions"><a class="link" href="admin.php?editar=<?= (int)$p['id'] ?>#editar-producto">Editar</a><form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="accion" value="cambiar_estado"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit"><?= $p['activo'] ? 'Ocultar' : 'Activar' ?></button></form></div></td>
</tr><?php endforeach; ?>
</tbody></table></div></section>
</div></body></html>

