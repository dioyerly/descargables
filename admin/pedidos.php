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
$f = (string)($_GET['f'] ?? '');
// «Sin cobrar»: rechazados + pendientes de hace más de 30 minutos (la persona se fue sin pagar o el pago falló)
$failCond = "(status='rejected' OR (status='pending' AND created_at < (NOW() - INTERVAL 30 MINUTE)))";
$where = $f === 'fail' ? "WHERE $failCond" : ($f === 'paid' ? "WHERE status='paid'" : '');
$rows = db()->query("SELECT * FROM orders $where ORDER BY id DESC LIMIT 200")->fetchAll();
$nFail = (int)db()->query("SELECT COUNT(*) FROM orders WHERE $failCond AND created_at > (NOW() - INTERVAL 30 DAY)")->fetchColumn();
$nPaid = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status='paid' AND created_at > (NOW() - INTERVAL 30 DAY)")->fetchColumn();
$tot = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid' AND currency='ARS'")->fetchColumn();
$totUsd = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid' AND currency='USD'")->fetchColumn();
$lbl = ['paid'=>'Pagado','pending'=>'Pendiente','rejected'=>'Rechazado','refunded'=>'Devuelto'];
admin_head('Pedidos'); ?>
<div class="head"><div><h1>Pedidos</h1><p class="muted">Últimos <?= count($rows) ?> · Total cobrado: <b><?= h(format_price($tot)) ?></b><?= $totUsd > 0 ? ' + <b>USD ' . h(number_format($totUsd, 2)) . '</b>' : '' ?></p></div></div>
<p class="muted" style="margin:0 0 12px">Últimos 30 días: <b><?= $nPaid ?></b> cobrados · <b><?= $nFail ?></b> sin cobrar.
  <?php foreach (['' => 'Todos', 'fail' => 'Sin cobrar', 'paid' => 'Cobrados'] as $k => $t): ?> <a class="btn sm<?= $f === $k ? '' : ' o' ?>" href="pedidos.php<?= $k ? '?f=' . $k : '' ?>"><?= $t ?></a><?php endforeach; ?></p>
<?php show_flash(); ?>
<?php if (!$rows): ?><div class="card empty">Todavía no hay pedidos.</div><?php endif; ?>
<div class="plist">
<?php foreach ($rows as $o): ?>
<div class="pitem<?= $o['status'] === 'paid' ? '' : ' off' ?>">
  <div class="pinfo"><b><?= h($o['product_title']) ?></b>
    <span class="muted"><?= h($o['buyer_email']) ?> · <?= h(($o['currency'] === 'USD' ? 'USD ' . number_format((float)$o['amount'], 2) : format_price((float)$o['amount']))) ?> · <?= $o['method'] === 'pp' ? 'PayPal' : ($o['method'] === 'gratis' ? 'Código gratis' : 'Mercado Pago') ?> · <?= h($o['created_at']) ?></span>
    <span class="tags"><em class="<?= $o['status'] === 'paid' ? 't-ok' : 't-warn' ?>"><?= h($lbl[$o['status']] ?? $o['status']) ?></em><?php if ($o['status'] === 'pending' && strtotime((string)$o['created_at']) < time() - 1800): ?><em class="t-warn">Sin completar</em><?php endif; ?><?php if ($o['fail_reason'] !== ''): ?><em class="t-warn"><?= h($o['fail_reason']) ?></em><?php endif; ?><?php if ($o['status'] === 'paid'): ?><em class="t-ok"><?= (int)$o['downloads'] ?> descargas · vence <?= h((string)$o['expires_at']) ?></em><?php endif; ?></span>
    <?php if ($o['status'] === 'paid'): ?><span class="muted"><small>Enlace: <?= h(download_link($o)) ?></small></span><?php endif; ?></div>
  <?php if ($o['status'] === 'paid'): ?><div class="pact"><form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= h($o['code']) ?>"><button class="btn sm" name="act" value="renew">Renovar enlace</button></form></div><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php admin_foot();
