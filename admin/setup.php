<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo = db();
    if (($_POST['do'] ?? '') === 'fichas') {
        try { $d = db_ready() ? db_apply_fichas() : []; $msg = $d ? 'Fichas aplicadas: ' . implode(', ', $d) . '.' : 'No había fichas para aplicar.'; } catch (Throwable $e) { $err = 'Error: ' . $e->getMessage(); }
    } elseif (!$pdo) $err = 'No hay conexión con MySQL: ' . ($GLOBALS['db_error'] ?? '');
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
  <?php if ($pdo): ?><form method="post" style="margin-top:16px"><input type="hidden" name="do" value="tablas"><?= csrf_field() ?><button class="btn" type="submit"><?= $ready ? 'Revisar tablas' : 'Crear tablas e importar mis productos' ?></button></form>
  <p class="muted"><small>Es seguro repetirlo: no borra nada.</small></p>
  <?php if ($ready): ?><hr style="margin:18px 0;border:0;border-top:1px solid #ddd5c6"><h2>Fichas de productos</h2>
  <p class="muted">Carga textos, etiquetas y fotos preparados en el repositorio. No toca el precio, el archivo ni si el producto está visible.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="fichas"><button class="btn" type="submit">Aplicar fichas de productos</button></form><?php endif; ?><?php endif; ?>
</div>
<?php admin_foot();
