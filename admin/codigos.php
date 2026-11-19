<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready()) { header('Location: setup.php'); exit; }
gratis_ready();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['act'] ?? '');
    $db = db();
    if ($act === 'crear') {
        $pk = (int)($_POST['product_id'] ?? 0);
        $code = strtoupper(trim((string)($_POST['codigo'] ?? '')));
        if ($code === '') $code = strtoupper(bin2hex(random_bytes(3)));
        $max = max(1, (int)($_POST['max_usos'] ?? 300));
        $dias = (int)($_POST['dias'] ?? 7);
        $vence = $dias > 0 ? date('Y-m-d 23:59:59', strtotime("+$dias days")) : null;
        if (!preg_match('/^[A-Za-z0-9_-]{4,40}$/', $code)) {
            flash('El código debe tener de 4 a 40 letras, números, guiones o guiones bajos.', 'err');
        } elseif ($pk <= 0) {
            flash('Elegí un producto.', 'err');
        } else {
            try {
                $db->prepare('INSERT INTO codigos_gratis (codigo, product_id, max_usos, vence, activo) VALUES (?,?,?,?,1)')
                   ->execute([$code, $pk, $max, $vence]);
                $db->prepare('UPDATE products SET gratis=1 WHERE id=?')->execute([$pk]);
                flash('Código «' . $code . '» creado. El producto ya aparece en la pestaña Gratis.');
            } catch (PDOException $e) {
                flash('Ese código ya existe. Probá con otro.', 'err');
            }
        }
    } elseif ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('UPDATE codigos_gratis SET activo = 1 - activo WHERE id=?')->execute([$id]);
        flash('Estado del código actualizado.');
    } elseif ($act === 'quitar') {
        $pk = (int)($_POST['product_id'] ?? 0);
        $db->prepare('UPDATE products SET gratis=0 WHERE id=?')->execute([$pk]);
        flash('El producto ya no está en la pestaña Gratis. Sus códigos siguen guardados.');
    }
    header('Location: codigos.php'); exit;
}

$products = array_values(array_filter(load_products(), fn($p) => !empty($p['pk'])));
$codes = db()->query("SELECT c.*, p.title AS ptitle FROM codigos_gratis c LEFT JOIN products p ON p.id = c.product_id ORDER BY c.id DESC LIMIT 200")->fetchAll();
$usos = [];
if ($codes) {
    $ids = array_map(fn($c) => (int)$c['id'], $codes);
    $in = implode(',', $ids);
    foreach (db()->query("SELECT codigo_id, email, created_at FROM codigos_usos WHERE codigo_id IN ($in) ORDER BY id DESC")->fetchAll() as $u) {
        $usos[(int)$u['codigo_id']][] = $u;
    }
}
$gratisProds = array_values(array_filter($products, fn($p) => !empty($p['gratis'])));

admin_head('Gratis'); ?>
<div class="head"><div><h1>Gratis</h1><p class="muted">Códigos para regalar productos. Cada email puede descargar una sola vez cada producto.</p></div></div>
<?php show_flash(); ?>

<div class="card">
  <h2>Crear código</h2>
  <p class="muted">¿Todavía no subiste el producto gratis? <a class="btn sm" href="edit.php?gratis=1">+ Subir producto gratis</a> (ahí sube el HTML y queda marcado como gratis).</p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="crear">
    <p><label>Producto<br>
      <select name="product_id" required>
        <option value="">— elegí —</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['pk'] ?>"><?= h(strip_tags(str_replace('*', '', (string)$p['title']))) ?></option>
        <?php endforeach; ?>
      </select></label></p>
    <p><label>Código (dejalo vacío para generar uno)<br>
      <input type="text" name="codigo" maxlength="40" placeholder="Ej: GRATIS2027"></label></p>
    <p><label>Máximo de descargas<br><input type="number" name="max_usos" value="300" min="1"></label>
       <label>Vence en (días, 0 = nunca)<br><input type="number" name="dias" value="7" min="0"></label></p>
    <p><button class="btn" type="submit">Crear código</button></p>
  </form>
</div>

<div class="card">
  <h2>Productos en Gratis</h2>
  <?php if (!$gratisProds): ?><p class="muted">Ninguno todavía. Al crear un código, el producto se agrega solo.</p><?php endif; ?>
  <div class="plist">
  <?php foreach ($gratisProds as $p): ?>
    <div class="pitem">
      <div class="pinfo"><b><?= h(strip_tags(str_replace('*', '', (string)$p['title']))) ?></b></div>
      <div class="pact"><form method="post"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int)$p['pk'] ?>"><button class="btn sm o" name="act" value="quitar">Quitar de Gratis</button></form></div>
    </div>
  <?php endforeach; ?>
  </div>
</div>

<h2>Códigos</h2>
<?php if (!$codes): ?><div class="card empty">Todavía no creaste códigos.</div><?php endif; ?>
<div class="plist">
<?php foreach ($codes as $c):
    $vencido = $c['vence'] !== null && strtotime((string)$c['vence']) < time();
    $agotado = (int)$c['usos'] >= (int)$c['max_usos'];
    $estado = !$c['activo'] ? 'Desactivado' : ($vencido ? 'Vencido' : ($agotado ? 'Agotado' : 'Activo'));
    $lista = $usos[(int)$c['id']] ?? [];
?>
  <div class="pitem<?= $estado === 'Activo' ? '' : ' off' ?>">
    <div class="pinfo"><b><?= h($c['codigo']) ?></b> <span class="muted">· <?= h(strip_tags(str_replace('*', '', (string)$c['ptitle']))) ?></span>
      <span class="tags"><em class="<?= $estado === 'Activo' ? 't-ok' : 't-warn' ?>"><?= h($estado) ?></em><em class="t-ok"><?= (int)$c['usos'] ?> / <?= (int)$c['max_usos'] ?> usos</em><?php if ($c['vence']): ?><em class="t-warn">vence <?= h((string)$c['vence']) ?></em><?php endif; ?></span>
      <?php if ($lista): ?>
      <details><summary class="muted">Emails que descargaron (<?= count($lista) ?>)</summary>
        <ul><?php foreach ($lista as $u): ?><li><?= h($u['email']) ?> <span class="muted">· <?= h((string)$u['created_at']) ?></span></li><?php endforeach; ?></ul>
      </details>
      <?php endif; ?>
    </div>
    <div class="pact"><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn sm o" name="act" value="toggle"><?= $c['activo'] ? 'Desactivar' : 'Activar' ?></button></form></div>
  </div>
<?php endforeach; ?>
</div>
<?php admin_foot();
