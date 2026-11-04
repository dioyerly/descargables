<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/images.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready() || !showcase_ready()) { header('Location: setup.php'); exit; }
$secs = showcase_sections();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = showcase_find((string)($_POST['id'] ?? '')); $act = (string)($_POST['act'] ?? '');
    if ($s) {
        $same = showcase_load($s['section']); $idx = null; foreach ($same as $i => $x) if ($x['id'] === $s['id']) $idx = $i;
        if ($act === 'toggle') { $s['visible'] = !$s['visible']; showcase_save($s, false); flash($s['visible'] ? 'Visible en el sitio.' : 'Oculto.'); }
        if (($act === 'up' && $idx > 0) || ($act === 'down' && $idx < count($same) - 1)) {
            $o = $same[$act === 'up' ? $idx - 1 : $idx + 1]; [$a, $b] = [$s['order'], $o['order']];
            if ($a === $b) $b = $a + ($act === 'up' ? -1 : 1);
            $s['order'] = $b; $o['order'] = $a; showcase_save($s, false); showcase_save($o, false);
        }
        if ($act === 'delete') { showcase_delete($s); flash('Eliminado.'); }
    }
    header('Location: muestras.php'); exit;
}
admin_head('Muestras'); ?>
<div class="head"><div><h1>Muestras del inicio</h1><p class="muted">Apps web, landings y micro apps que se ven en el inicio. Las agendas se cargan en “Productos”.</p></div></div>
<?php show_flash(); ?>
<?php foreach ($secs as $key => $sec): $items = showcase_load($key); ?>
<div class="head" style="margin-top:28px"><div><h2><?= h($sec['label']) ?></h2><p class="muted"><?= h($sec['hint']) ?></p></div><a class="btn" href="muestra.php?s=<?= h($key) ?>">+ Agregar</a></div>
<?php if (!$items): ?><div class="card empty">Todavía no hay nada acá. <a href="muestra.php?s=<?= h($key) ?>">Agregá el primero</a>.</div><?php endif; ?>
<div class="plist">
<?php foreach ($items as $i => $s): $img = $s['images'][0] ?? ''; ?>
<div class="pitem<?= $s['visible'] ? '' : ' off' ?>">
  <div class="thumb"><?php if ($img): ?><img src="../uploads/<?= h(rawurlencode($img)) ?>" alt=""><?php endif; ?></div>
  <div class="pinfo"><b><?= h($s['title']) ?></b>
    <span class="muted"><?= count($s['images']) ?> fotos<?= $key === 'landings' ? ($s['html'] !== '' ? ' · HTML subido ✓' : ' · falta el HTML') : '' ?><?= $s['link'] !== '' ? ' · ' . h($s['link']) : '' ?></span>
    <span class="tags"><?php if (!$s['visible']): ?><em class="t-off">Oculto</em><?php endif; ?><?php if ($key === 'landings' && $s['html'] === ''): ?><em class="t-warn">Falta el HTML</em><?php endif; ?><?php if ($key !== 'landings' && !$s['images']): ?><em class="t-warn">Faltan fotos</em><?php endif; ?></span></div>
  <div class="pact">
    <a class="btn sm" href="muestra.php?id=<?= h(rawurlencode($s['id'])) ?>">Editar</a>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($s['id']) ?>">
      <button class="ic" name="act" value="up" title="Subir" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
      <button class="ic" name="act" value="down" title="Bajar" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>↓</button>
      <button class="ic" name="act" value="toggle" title="<?= $s['visible'] ? 'Ocultar' : 'Mostrar' ?>"><?= $s['visible'] ? '👁' : '👁‍🗨' ?></button>
      <button class="ic del" name="act" value="delete" title="Eliminar" onclick="return confirm('¿Eliminar esta muestra con sus fotos y su HTML? No se puede deshacer.')">🗑</button></form>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php admin_foot();
