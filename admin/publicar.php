<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/images.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready() || !showcase_ready() || !avisos_ready()) { header('Location: setup.php'); exit; }
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$s = $id !== '' ? showcase_find($id) : null;
if (!$s || $s['section'] !== 'soon') { flash('Ese producto ya no está en Próximamente.', 'warn'); header('Location: muestras.php'); exit; }
$dest = ['apps' => 'Apps web', 'landings' => 'Landings a medida', 'agendas' => 'Agendas e imprimibles (producto)', 'micro' => 'Micro apps a medida'];
$anchor = ['apps' => 'apps', 'landings' => 'landings', 'agendas' => 'agendas', 'micro' => 'micro'];
$toProduct = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $to = (string)($_POST['section'] ?? '');
    $lu = trim((string)($_POST['link'] ?? ''));
    $link = $lu === '' ? '' : safe_url($lu);
    if (!isset($dest[$to])) $errors[] = 'Elegí a qué sección lo pasás.';
    if ($lu !== '' && $link === '') $errors[] = 'El link debe empezar con https://';
    if (!$errors) {
        $site = 'https://' . biz()['site'];
        // 1) lo pasamos a la sección elegida y lo mostramos
        $newSlug = '';
        if ($to === 'agendas') {
            // pasa a la tabla de productos (agendas): se conservan las fotos y se borra de Próximamente sin tocar los archivos
            $list = load_products();
            $base = slugify($s['title']); $newSlug = $base; $k = 2;
            while (find_product($list, $newSlug)) $newSlug = $base . '-' . $k++;
            $imgs = array_values($s['images']);
            $prod = ['id' => $newSlug, 'order' => product_next_order(), 'visible' => true, 'type' => 'print', 'kicker' => '', 'title' => $s['title'],
                'theme' => 'lavanda', 'pages' => 0, 'ar' => $imgs ? image_ratio($imgs[0]) : '3/4', 'desc' => $s['desc'], 'long' => '', 'meta' => [], 'feats' => [],
                'images' => $imgs, 'price' => 0, 'price_usd' => 0, 'files' => [], 'ext_url' => '', 'landing' => $link];
            try { product_insert($prod); }
            catch (Throwable $e) { error_log('publicar agenda: ' . $e->getMessage()); flash('No se pudo crear el producto.', 'err'); header('Location: muestras.php'); exit; }
            db()->prepare('DELETE FROM showcase WHERE slug = ?')->execute([$s['id']]);
            $mailSlug = $s['id'];
        } else {
            $s['section'] = $to; $s['visible'] = true; $s['link'] = $link;
            $s['order'] = showcase_next_order($to);
            showcase_save($s, false);
            $mailSlug = $s['id'];
        }
        // 2) mails a cada persona que pidió aviso y todavía no lo recibió
        $url = $link !== '' ? $link : $site . '/#' . $anchor[$to];
        $from = (string)cfg('mail_from', 'no-responder@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $headers = "From: EstrategIA Descargables <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\nMIME-Version: 1.0";
        $body = "¡Buenas noticias!\r\n\r\n“{$s['title']}” ya está disponible.\r\n\r\nVerlo acá:\r\n$url\r\n\r\nGracias por esperarlo.\r\n\r\nEstrategIA · Productos digitales";
        $st = db()->prepare('SELECT id, email FROM avisos WHERE slug = ? AND sent_at IS NULL');
        $st->execute([$mailSlug]);
        $ok = 0; $fail = 0;
        foreach ($st->fetchAll() as $r) {
            $sent = @mail((string)$r['email'], '=?UTF-8?B?' . base64_encode('Ya está disponible: ' . $s['title']) . '?=', $body, $headers);
            if ($sent) { $ok++; db()->prepare('UPDATE avisos SET sent_at = NOW() WHERE id = ?')->execute([(int)$r['id']]); }
            else $fail++;
        }
        flash('Publicado en ' . $dest[$to] . '. Mails de aviso enviados: ' . $ok . ($fail ? ' · no se pudieron enviar: ' . $fail . ' (quedan pendientes para la próxima vez).' : '.'), $fail ? 'warn' : 'ok');
        header('Location: ' . ($newSlug !== '' ? 'edit.php?id=' . rawurlencode($newSlug) : 'muestras.php')); exit;
    }
}
$pend = db()->prepare('SELECT COUNT(*) FROM avisos WHERE slug = ? AND sent_at IS NULL'); $pend->execute([$s['id']]);
$pendN = (int)$pend->fetchColumn();
admin_head('Publicar producto'); ?>
<div class="head"><div><h1>Publicar “<?= h($s['title']) ?>”</h1><p class="muted">Lo pasamos del apartado Próximamente a la categoría que elijas (si elegís Agendas queda creado como producto y después completás precio y archivos). Le mandamos un mail a las <?= $pendN ?> personas que pidieron aviso.</p></div></div>
<?php show_flash(); ?>
<?php foreach ($errors as $e): ?><div class="flash warn"><?= h($e) ?></div><?php endforeach; ?>
<form method="post" class="grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($s['id']) ?>">
<div class="card">
  <h2>¿Dónde lo mostramos?</h2>
  <label>Sección<select name="section" required>
    <?php foreach ($dest as $k => $l): ?><option value="<?= h($k) ?>"<?= (($_POST['section'] ?? '') === $k) ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
  </select></label>
  <label>Link del producto (opcional)<input name="link" type="url" value="<?= h((string)($_POST['link'] ?? '')) ?>" placeholder="https://miapp.estrategia.site"><small>Si lo dejás vacío, el mail lleva a la sección del sitio.</small></label>
  <div class="save"><button class="btn" type="submit" onclick="return confirm('¿Publicar y mandar el mail a las <?= $pendN ?> personas que pidieron aviso?')">Publicar y avisar</button><a href="muestras.php" class="muted">Cancelar</a></div>
</div>
</form>
<?php admin_foot();
