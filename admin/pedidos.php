<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready()) { header('Location: setup.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $o = order_by_code((string)($_POST['code'] ?? ''));
    if ($o && $o['status'] === 'paid' && ($_POST['act'] ?? '') === 'renew') {
        $d = max(1, (int)cfg('download.days', 7));
        db()->prepare("UPDATE orders SET downloads=0, expires_at=DATE_ADD(NOW(), INTERVAL $d DAY) WHERE code=?")->execute([$o['code']]);
        flash('Enlace renovado por ' . $d . ' días.');
    }
    header('Location: pedidos.php'); exit;
}
$rows = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 200')->fetchAll();
$tot = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid'")->fetchColumn();
$lbl = ['paid'=>'Pagado','pending'=>'Pendiente','rejected'=>'Rechazado','refunded'=>'Devuelto'];
admin_head('Pedidos'); ?>
<div class="head"><div><h1>Pedidos</h1><p class="muted">Últimos <?= count($rows) ?> · Total cobrado: <b><?= h(format_price($tot)) ?></b></p></div></div>
<?php show_flash(); ?>
<?php if (!$rows): ?><div class="card empty">Todavía no hay pedidos.</div><?php endif; ?>
<div class="plist">
<?php foreach ($rows as $o): ?>
<div class="pitem<?= $o['status'] === 'paid' ? '' : ' off' ?>">
  <div class="pinfo"><b><?= h($o['product_title']) ?></b>
    <span class="muted"><?= h($o['buyer_email']) ?> · <?= h(format_price((float)$o['amount'])) ?> · <?= h($o['created_at']) ?></span>
    <span class="tags"><em class="<?= $o['status'] === 'paid' ? 't-ok' : 't-warn' ?>"><?= h($lbl[$o['status']] ?? $o['status']) ?></em><?php if ($o['status'] === 'paid'): ?><em class="t-ok"><?= (int)$o['downloads'] ?> descargas · vence <?= h((string)$o['expires_at']) ?></em><?php endif; ?></span>
    <?php if ($o['status'] === 'paid'): ?><span class="muted"><small>Enlace: <?= h(download_link($o)) ?></small></span><?php endif; ?></div>
  <?php if ($o['status'] === 'paid'): ?><div class="pact"><form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= h($o['code']) ?>"><button class="btn sm" name="act" value="renew">Renovar enlace</button></form></div><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php admin_foot();
