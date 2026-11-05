<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/images.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready() || !showcase_ready()) { header('Location: setup.php'); exit; }
$secs = showcase_sections();
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$s = $id !== '' ? showcase_find($id) : null;
$isNew = $s === null;
$secKey = (string)($_GET['s'] ?? $_POST['section'] ?? ($s['section'] ?? 'apps'));
if (!isset($secs[$secKey])) $secKey = 'apps';
$s = $s ?? ['id' => '', 'section' => $secKey, 'order' => 0, 'visible' => true, 'title' => '', 'desc' => '', 'link' => '', 'tags' => [], 'images' => [], 'html' => '', 'price' => ''];
$errors = []; $warns = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s['title'] = trim((string)($_POST['title'] ?? ''));
    $s['desc'] = trim((string)($_POST['desc'] ?? ''));
    $s['visible'] = !empty($_POST['visible']);
    $s['price'] = mb_substr(trim((string)($_POST['price'] ?? '')), 0, 60);
    $usdIn = str_replace(',', '.', trim(str_replace(['USD', 'usd', 'US$', 'U$S', '$', ' '], '', (string)($_POST['price_usd'] ?? ''))));
    if ($usdIn !== '') {
        if (!is_numeric($usdIn) || (float)$usdIn <= 0 || (float)$usdIn > 1000000) $errors[] = 'El precio en dólares debe ser un número (por ejemplo 150).';
        else $s['price'] = usd_label(round((float)$usdIn, 2));
    }
    $s['tags'] = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r?\n|,/', (string)($_POST['tags'] ?? '')) ?: []), fn($x) => $x !== '')), 0, 5);
    $lu = trim((string)($_POST['link'] ?? '')); $s['link'] = safe_url($lu);
    if ($lu !== '' && $s['link'] === '') $errors[] = 'El link debe empezar con https://';
    if ($s['title'] === '') $errors[] = 'Falta el título.';
    if (mb_strlen($s['title']) > 80) $errors[] = 'El título es muy largo (máximo 80 caracteres).';
    if (mb_strlen($s['desc']) > 600) $errors[] = 'La descripción es muy larga (máximo 600 caracteres).';
    $base = $isNew ? slugify($s['title']) : $s['id'];
    // fotos
    $keep = [];
    foreach ($s['images'] as $im) if (empty($_POST['rm'][$im])) $keep[] = $im; else delete_upload($im);
    $cover = (string)($_POST['cover'] ?? '');
    if ($cover !== '' && in_array($cover, $keep, true)) { $keep = array_values(array_diff($keep, [$cover])); array_unshift($keep, $cover); }
    $max = $secKey === 'landings' ? 1 : SHOWCASE_MAX_IMAGES;
    if (!empty($_FILES['imgs']['name'][0])) foreach ($_FILES['imgs']['name'] as $i => $n) {
        if (count($keep) >= $max) { $warns[] = 'Máximo ' . $max . ' foto' . ($max > 1 ? 's' : '') . '; algunas no se agregaron.'; break; }
        $f = ['name' => $n, 'type' => $_FILES['imgs']['type'][$i], 'tmp_name' => $_FILES['imgs']['tmp_name'][$i], 'error' => $_FILES['imgs']['error'][$i], 'size' => $_FILES['imgs']['size'][$i]];
        if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
        try { $keep[] = store_upload($f, 'muestra-' . $base); } catch (RuntimeException $e) { $warns[] = $n . ': ' . $e->getMessage(); }
    }
    $s['images'] = $keep;
    // HTML de la landing
    $oldHtml = $s['html'];
    if (!empty($_POST['rm_html'])) $s['html'] = '';
    if (!empty($_FILES['html']['name']) && ($_FILES['html']['error'] ?? 0) !== UPLOAD_ERR_NO_FILE) {
        try { $s['html'] = showcase_store_html($_FILES['html'], $base); } catch (RuntimeException $e) { $errors[] = 'El HTML ' . $e->getMessage(); }
    }
    if ($secKey === 'micro' && !$s['images'] && $s['html'] === '') $errors[] = 'Subí el HTML de la demo o al menos una foto.';
    if ($secKey === 'apps' && !$s['images'] && $s['html'] === '') $errors[] = 'Subí el HTML de la demo o al menos una foto de la app.';
    if ($secKey === 'landings' && $s['html'] === '' && !$s['images']) $warns[] = 'Todavía no subiste el HTML de la landing.';
    $s['section'] = $secKey;
    if (!$errors) {
        try {
            if ($isNew) { $slug = $base; $n = 2; while (showcase_find($slug)) $slug = $base . '-' . $n++; $s['id'] = $slug; $s['order'] = showcase_next_order($secKey); }
            showcase_save($s, $isNew);
            if ($oldHtml !== '' && $oldHtml !== $s['html']) @unlink(SHOWCASE_DIR . '/' . basename($oldHtml));
            flash(($isNew ? 'Creado.' : 'Cambios guardados.') . ($warns ? ' Aviso: ' . implode(' ', $warns) : ''), $warns ? 'warn' : 'ok');
            header('Location: muestras.php'); exit;
        } catch (Throwable $e) { error_log('muestra save: ' . $e->getMessage()); $errors[] = 'No se pudo guardar en la base de datos.'; }
    }
}
$sec = $secs[$secKey];
admin_head($isNew ? 'Nueva muestra' : 'Editar muestra'); ?>
<div class="head"><div><a class="back" href="muestras.php">← Muestras</a><h1><?= $isNew ? 'Nueva: ' . h($sec['single']) : 'Editar: ' . h($s['title']) ?></h1><p class="muted"><?= h($sec['hint']) ?></p></div></div>
<?php foreach (array_merge($errors, $warns) as $e): ?><div class="flash err"><?= h($e) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($s['id']) ?>"><input type="hidden" name="section" value="<?= h($secKey) ?>">
<div class="card">
  <h2><?= h($sec['single']) ?></h2>
  <label>Título<input name="title" maxlength="80" required value="<?= h($s['title']) ?>" placeholder="<?= $secKey === 'landings' ? 'Landing de un estudio contable' : ($secKey === 'apps' ? 'Gestión de tienda de ropa' : 'Control de stock') ?>"></label>
  <?php if (true): ?><label>Descripción<textarea name="desc" rows="4" maxlength="600" placeholder="Qué es y para quién, en 2 o 3 frases."><?= h($s['desc']) ?></textarea></label>
  <label>Etiquetas (hasta 5, separadas por coma)<input name="tags" value="<?= h(implode(', ', $s['tags'])) ?>" placeholder="PHP, MySQL, Mercado Pago"></label><?php else: ?>
  <label>Texto corto (opcional)<textarea name="desc" rows="2" maxlength="600" placeholder="Una frase que acompaña las fotos."><?= h($s['desc']) ?></textarea></label><?php endif; ?>
  <?php if ($secKey === 'apps'): ?><label>Link de la app<input name="link" type="url" value="<?= h($s['link']) ?>" placeholder="https://miapp.estrategia.site"><small>El botón “Ver app” lleva acá. Dejalo vacío si todavía no tiene link.</small></label>
  <?php elseif ($secKey === 'landings'): $dr = dolar_rate(); $rv = (float)($dr['valor'] ?? 0); $uv = usd_amount((string)$s['price']); ?>
  <div class="two">
    <label>Precio en dólares (USD)<input name="price_usd" id="lusd" inputmode="decimal" value="<?= $uv > 0 ? h(rtrim(rtrim(number_format($uv, 2, '.', ''), '0'), '.')) : '' ?>" placeholder="150" data-rate="<?= h((string)$rv) ?>">
      <small id="lusdh"><?= $rv > 0 ? 'Dólar ' . h(strtolower(explode(' (', dolar_types()[$dr['tipo']] ?? 'oficial')[0])) . ' hoy: <b>' . h(format_price($rv)) . '</b>. En el sitio se muestra en USD y en pesos, recalculado cada día.' : 'No se pudo leer la cotización ahora; en el sitio se muestra solo en USD hasta que se actualice.' ?></small></label>
    <label>O escribilo como quieras<input name="price" id="ltxt" maxlength="60" value="<?= $uv > 0 ? '' : h($s['price']) ?>" placeholder="$185.000"><small>Solo si no usás el campo en dólares. Vacíos los dos = no muestra precio.</small></label>
  </div>
  <script>(function(){var u=document.getElementById('lusd'),t=document.getElementById('ltxt'),hh=document.getElementById('lusdh');if(!u)return;var r=parseFloat(u.dataset.rate)||0,base=hh.innerHTML;
  function up(){var v=parseFloat((u.value||'').replace(',','.'));if(v>0){t.value='';t.disabled=true;if(r>0)hh.innerHTML='En el sitio se verá: <b>USD '+v.toLocaleString('es-AR')+'</b> ≈ <b>$ '+(Math.ceil(v*r/100)*100).toLocaleString('es-AR')+'</b> (dólar de hoy $ '+r.toLocaleString('es-AR')+'). Se recalcula solo cada día.'}else{t.disabled=false;hh.innerHTML=base}}
  u.addEventListener('input',up);up()})();</script>
  <label>Link para pedir una igual (opcional)<input name="link" type="url" value="<?= h($s['link']) ?>" placeholder="https://wa.me/..."><small>Si lo dejás vacío, el botón usa tu WhatsApp.</small></label><?php endif; ?>
  <label class="chk"><input type="checkbox" name="visible" value="1"<?= $s['visible'] ? ' checked' : '' ?>> Visible en el sitio</label>
</div>
<div class="card">
  <?php if ($secKey !== '' && $secKey !== 'soon'): ?>
  <h2><?= $secKey === 'landings' ? 'HTML de la landing' : 'HTML de la demo (opcional)' ?></h2>
  <?php if ($s['html'] !== ''): ?><p class="muted" style="margin:6px 0">📄 <b><?= h($s['html']) ?></b> <a href="../demo.php?f=<?= h(rawurlencode($s['html'])) ?>" target="_blank" rel="noopener">Ver ↗</a>
    <label class="mini rm"><input type="checkbox" name="rm_html" value="1"> Quitar</label></p><?php else: ?><p class="muted">Todavía no hay HTML.</p><?php endif; ?>
  <label class="drop"><?= $s['html'] !== '' ? 'Reemplazar el HTML' : 'Subir el HTML' ?><input type="file" name="html" accept=".html,.htm,text/html">
    <small>Un solo archivo .html (hasta 12 MB). Conviene que tenga todo adentro: estilos, imágenes y fuentes. Se muestra en vivo dentro del visor del inicio, sin poder tocar el resto del sitio. Límite del hosting ahora: <?= h(ini_get('upload_max_filesize')) ?>.</small></label>
  <?php endif; ?>
  <h2 style="margin-top:22px"><?= $secKey === 'landings' ? 'Foto de portada (opcional)' : 'Fotos (opcionales si subís la demo)' ?> <small class="muted">(máximo <?= $secKey === 'landings' ? 1 : SHOWCASE_MAX_IMAGES ?>)</small></h2>
  <?php if ($s['images']): ?><div class="imgs"><?php foreach ($s['images'] as $i => $im): ?>
    <div class="im"><img src="../uploads/<?= h(rawurlencode($im)) ?>" alt="">
      <label class="mini"><input type="radio" name="cover" value="<?= h($im) ?>"<?= $i === 0 ? ' checked' : '' ?>> Portada</label>
      <label class="mini rm"><input type="checkbox" name="rm[<?= h($im) ?>]" value="1"> Quitar</label></div>
  <?php endforeach; ?></div><?php endif; ?>
  <label class="drop">Agregar fotos<input type="file" name="imgs[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>JPG, PNG o WebP de hasta 8 MB. Se achican y optimizan solas. La primera es la portada.</small></label>
  <div class="save"><button class="btn" type="submit"><?= $isNew ? 'Crear' : 'Guardar cambios' ?></button><a href="muestras.php" class="muted">Cancelar</a></div>
</div>
</form>
<?php admin_foot();
