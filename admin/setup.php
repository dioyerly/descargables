<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    if (!$pdo) $err = 'No hay conexión con MySQL: ' . ($GLOBALS['db_error'] ?? '');
    else try { db_install($pdo); $n = db_import_seed($pdo); $msg = 'Tablas listas.' . ($n ? " Se importaron $n productos." : ''); }
    catch (Throwable $e) { $err = 'Error: ' . $e->getMessage(); }
}
$pdo = db(); $ready = db_ready();
admin_head('Base de datos'); ?>
<div class="head"><div><h1>Base de datos y pagos</h1><p class="muted">Estado de la conexión y de Mercado Pago.</p></div></div>
<?php if ($msg): ?><div class="flash ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<div class="card">
  <h2>Estado</h2>
  <p><?= config_exists() ? '✅' : '❌' ?> Archivo <b>config.php</b> <?= config_exists() ? 'encontrado' : 'no encontrado: copiá config.example.php como config.php y completalo' ?></p>
  <p><?= $pdo ? '✅' : '❌' ?> Conexión a MySQL <?= $pdo ? 'correcta' : '— ' . h((string)($GLOBALS['db_error'] ?? '')) ?></p>
  <p><?= $ready ? '✅' : '❌' ?> Tablas <?= $ready ? 'creadas' : 'sin crear' ?></p>
  <p><?= mp_configured() ? '✅' : '❌' ?> Access Token de Mercado Pago <?= mp_configured() ? 'cargado' : 'sin cargar (en config.php)' ?></p>
  <p class="muted">Aviso automático (webhook) de Mercado Pago: <code><?= h(site_url('mp-webhook.php')) ?></code></p>
  <?php if ($pdo): ?><form method="post" style="margin-top:16px"><?= csrf_field() ?><button class="btn" type="submit"><?= $ready ? 'Revisar tablas' : 'Crear tablas e importar mis productos' ?></button></form>
  <p class="muted"><small>Es seguro repetirlo: no borra nada.</small></p><?php endif; ?>
</div>
<?php admin_foot();
