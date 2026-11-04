<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready()) { header('Location: setup.php'); exit; }
extras_ready();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['act'] ?? '');
    if ($act === 'dolar') {
        $t = (string)($_POST['tipo'] ?? 'oficial');
        if (isset(dolar_types()[$t])) setting_set('dolar_tipo', $t);
        $r = dolar_rate(true);
        flash(!empty($r['ok']) ? 'Listo. Dólar ' . $t . ': ' . format_price((float)$r['valor']) . '. Los precios en USD ya usan esta cotización.' : 'Guardado, pero no se pudo consultar la cotización ahora. Se reintenta solo.', !empty($r['ok']) ? 'ok' : 'err');
    } elseif ($act === 'owner_on') { mark_owner_device(true); flash('Este dispositivo ya no se cuenta en las estadísticas.'); }
    elseif ($act === 'owner_off') { mark_owner_device(false); flash('Este dispositivo vuelve a contarse en las estadísticas.'); }
    header('Location: ajustes.php'); exit;
}
$dr = dolar_rate(); $tipo = dolar_type(); $b = biz();
admin_head('Ajustes'); ?>
<div class="head"><div><h1>Ajustes</h1><p class="muted">Cotización del dólar y estadísticas.</p></div></div>
<?php show_flash(); ?>
<div class="card">
  <h2>Dólar para los precios en USD</h2>
  <p class="muted" style="margin-bottom:14px">Si a un producto le cargás precio en dólares, el precio en pesos se calcula con esta cotización (se actualiza sola cada 3 horas y se redondea a la centena). Mercado Pago siempre cobra en pesos.</p>
  <form method="post" class="two" style="align-items:end"><?= csrf_field() ?><input type="hidden" name="act" value="dolar">
    <label>Tipo de dólar<select name="tipo"><?php foreach (dolar_types() as $k => $v): ?><option value="<?= h($k) ?>"<?= $k === $tipo ? ' selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
    <div><button class="btn" type="submit">Guardar y actualizar</button></div>
  </form>
  <p style="margin-top:12px"><?php if ((float)($dr['valor'] ?? 0) > 0): ?>Hoy: <b><?= h(format_price((float)$dr['valor'])) ?></b> por dólar<?= $dr['fecha'] ? ' · actualizado ' . h(date('d/m H:i', strtotime((string)$dr['fecha']) ?: time())) : '' ?><?= empty($dr['ok']) ? ' · <span class="muted">(última guardada, no se pudo actualizar ahora)</span>' : '' ?><?php else: ?><span class="muted">Todavía no se pudo leer la cotización.</span><?php endif; ?></p>
</div>
<div class="card">
  <h2>No contarme en las estadísticas</h2>
  <p class="muted" style="margin-bottom:14px">Cuando entrás al panel no se cuentan tus visitas: ni desde este navegador ni desde otros aparatos conectados a la misma red (tu wifi) durante 45 días. Si igual aparece una visita tuya (por ejemplo desde datos móviles), en Estadísticas → Últimas visitas tocá “Era yo”.</p>
  <?php if (is_owner_device()): ?>
    <p style="margin-bottom:12px">✅ Este dispositivo <b>no se cuenta</b>.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="owner_off"><button class="btn o" type="submit">Volver a contar este dispositivo</button></form>
  <?php else: ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="owner_on"><button class="btn" type="submit">No contar este dispositivo</button></form>
  <?php endif; ?>
</div>
<div class="card">
  <h2>Datos que aparecen en los legales</h2>
  <p>Titular: <b><?= h($b['name']) ?></b> · Email: <b><?= h($b['email']) ?></b> · WhatsApp: <b><?= h($b['whatsapp']) ?></b> · País: <?= h($b['country']) ?></p>
  <p class="muted" style="margin-top:8px">Se ven en <a href="../legales.php" target="_blank" rel="noopener">Términos, privacidad y devoluciones</a>.</p>
</div>
<?php admin_foot();
