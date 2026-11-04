<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/images.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready()) { header('Location: setup.php'); exit; }
$list = load_products();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string)($_POST['id'] ?? ''); $act = (string)($_POST['act'] ?? '');
    $idx = null; foreach ($list as $i => $p) if ($p['id'] === $id) $idx = $i;
    if ($idx !== null) {
        if ($act === 'toggle') { $list[$idx]['visible'] = empty($list[$idx]['visible']); product_update($list[$idx]); flash($list[$idx]['visible'] ? 'Producto visible en el sitio.' : 'Producto oculto.'); }
        if ($act === 'up' && $idx > 0) { [$list[$idx - 1], $list[$idx]] = [$list[$idx], $list[$idx - 1]]; products_resequence(array_column($list, 'id')); }
        if ($act === 'down' && $idx < count($list) - 1) { [$list[$idx + 1], $list[$idx]] = [$list[$idx], $list[$idx + 1]]; products_resequence(array_column($list, 'id')); }
        if ($act === 'delete') {
            foreach ($list[$idx]['images'] ?? [] as $im) delete_upload($im);
            if (($list[$idx]['file'] ?? '') !== '') @unlink(PRIVATE_DIR . '/' . basename($list[$idx]['file']));
            product_delete($id); flash('Producto eliminado.');
        }
    }
    header('Location: index.php'); exit;
}
$themes = themes(); $types = types();
admin_head('Productos'); ?>
<div class="head"><div><h1>Productos</h1><p class="muted"><?= count($list) ?> en total · <?= count(array_filter($list, fn($p) => !empty($p['visible']))) ?> visibles. El orden de esta lista es el orden del sitio.</p></div><a class="btn" href="edit.php">+ Nuevo producto</a></div>
<?php show_flash(); ?>
<?php if (!$list): ?><div class="card empty">Todavía no hay productos. <a href="edit.php">Cargá el primero</a>.</div><?php endif; ?>
<div class="plist">
<?php foreach ($list as $i => $p): $img = $p['images'][0] ?? ''; $sale = product_for_sale($p); ?>
<div class="pitem<?= empty($p['visible']) ? ' off' : '' ?>">
  <div class="thumb"><?php if ($img): ?><img src="../uploads/<?= h(rawurlencode($img)) ?>" alt=""><?php endif; ?></div>
  <div class="pinfo"><b><?= h(str_replace('*', '', (string)$p['title'])) ?></b>
    <span class="muted"><?= h($types[$p['type'] ?? 'print'] ?? '') ?> · <?= count($p['images'] ?? []) ?> fotos · tema <?= h($themes[$p['theme'] ?? '']['label'] ?? '-') ?></span>
    <span class="tags"><?php if (empty($p['visible'])): ?><em class="t-off">Oculto</em><?php endif; ?><?php if ($p['price'] <= 0): ?><em class="t-warn">Falta el precio</em><?php else: ?><em class="t-ok"><?= h(format_price($p['price'])) ?></em><?php endif; ?><?php if ($p['file'] === '' && $p['ext_url'] === ''): ?><em class="t-warn">Falta el archivo</em><?php endif; ?></span></div>
  <div class="pact">
    <a class="btn sm" href="edit.php?id=<?= h(rawurlencode($p['id'])) ?>">Editar</a>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>">
      <button class="ic" name="act" value="up" title="Subir" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
      <button class="ic" name="act" value="down" title="Bajar" <?= $i === count($list) - 1 ? 'disabled' : '' ?>>↓</button>
      <button class="ic" name="act" value="toggle" title="<?= empty($p['visible']) ? 'Mostrar' : 'Ocultar' ?>"><?= empty($p['visible']) ? '👁‍🗨' : '👁' ?></button>
      <button class="ic del" name="act" value="delete" title="Eliminar" onclick="return confirm('¿Eliminar este producto y sus fotos? No se puede deshacer.')">🗑</button></form>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php admin_foot();
