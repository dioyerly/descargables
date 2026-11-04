<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/images.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
if (!db_ready()) { header('Location: setup.php'); exit; }
$list = load_products();
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$p = $id !== '' ? find_product($list, $id) : null;
$isNew = $p === null;
$p = $p ?? ['id'=>'','visible'=>true,'type'=>'print','kicker'=>'','title'=>'','desc'=>'','long'=>'','price'=>0,'price_usd'=>0,'file'=>'','file_orig'=>'','files'=>[],'ext_url'=>'','landing'=>'','meta'=>[],'feats'=>[],'theme'=>'lavanda','pages'=>0,'images'=>[],'ar'=>'3/4'];
$errors = []; $warns = [];
$lines = fn(string $s, int $max) => array_slice(array_values(array_filter(array_map('trim', preg_split('/\r?\n|,/', $s) ?: []), fn($x) => $x !== '')), 0, $max);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p['title'] = trim((string)($_POST['title'] ?? ''));
    // Palabra en cursiva: se escribe aparte y se marca sola dentro del nombre
    $ital = trim(str_replace('*', '', (string)($_POST['italic'] ?? '')));
    if ($ital !== '') {
        $plain = str_replace('*', '', $p['title']);
        $pos = mb_stripos($plain, $ital);
        if ($pos !== false) { $len = mb_strlen($ital); $p['title'] = mb_substr($plain, 0, $pos) . '*' . mb_substr($plain, $pos, $len) . '*' . mb_substr($plain, $pos + $len); }
        else { $errors[] = 'La palabra en cursiva tiene que estar escrita dentro del nombre.'; }
    }
    $p['kicker'] = trim((string)($_POST['kicker'] ?? ''));
    $p['desc'] = trim((string)($_POST['desc'] ?? ''));
    $p['type'] = array_key_exists($_POST['type'] ?? '', types()) ? $_POST['type'] : 'print';
    $p['theme'] = array_key_exists($_POST['theme'] ?? '', themes()) ? $_POST['theme'] : 'lavanda';
    $p['pages'] = max(0, (int)($_POST['pages'] ?? 0));
    $p['visible'] = !empty($_POST['visible']);
    $p['meta'] = $lines((string)($_POST['meta'] ?? ''), 4);
    $p['feats'] = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST['feats'] ?? '')) ?: []))), 0, 5);
    $lu = trim((string)($_POST['landing'] ?? ''));
    $p['landing'] = safe_url($lu);
    if ($lu !== '' && $p['landing'] === '') $errors[] = 'El link de la página del producto debe empezar con https://';
    $p['long'] = trim((string)($_POST['long'] ?? ''));
    $priceRaw = str_replace(['$', ' '], '', (string)($_POST['price'] ?? '0'));
    $priceRaw = preg_match('/,\d{1,2}$/', $priceRaw) ? str_replace(',', '.', str_replace('.', '', $priceRaw)) : str_replace(['.', ','], '', $priceRaw);
    $p['price'] = is_numeric($priceRaw) ? max(0, (float)$priceRaw) : 0;
    if (!is_numeric($priceRaw)) $errors[] = 'El precio debe ser un número (por ejemplo 4500).';
    if ($p['price'] > 99999999) $errors[] = 'El precio es demasiado alto.';
    $usdRaw = str_replace(['US$', 'USD', 'u$s', '$', ' '], '', (string)($_POST['price_usd'] ?? ''));
    $usdRaw = str_replace(',', '.', $usdRaw);
    if ($usdRaw !== '' && (!is_numeric($usdRaw) || (float)$usdRaw < 0 || (float)$usdRaw > 100000)) $errors[] = 'El precio en dólares debe ser un número (por ejemplo 9.99).';
    $p['price_usd'] = is_numeric($usdRaw) ? round(max(0, (float)$usdRaw), 2) : 0;
    if ($p['price_usd'] > 0) {
        $ars = usd_to_ars($p['price_usd']);
        if ($ars > 0) $p['price'] = $ars;
        elseif ($p['price'] <= 0) $errors[] = 'No pude consultar la cotización del dólar ahora. Cargá también el precio en pesos (se usa mientras tanto).';
    }
    $rawUrl = trim((string)($_POST['ext_url'] ?? ''));
    $p['ext_url'] = safe_url($rawUrl);
    if ($rawUrl !== '' && $p['ext_url'] === '') $errors[] = 'El link de descarga debe empezar con https://';
    if (mb_strlen($p['long']) > 8000) $errors[] = 'El detalle es muy largo (máximo 8000 caracteres).';
    // archivos descargables (privados): se pueden subir varios (ej. HTML + manual PDF)
    $files = $p['files']; $toDelete = [];
    foreach ($files as $i => $fl) if (!empty($_POST['rm_files'][$fl['stored']])) { $toDelete[] = $fl['stored']; unset($files[$i]); }
    $files = array_values($files);
    $uf = $_FILES['dl'] ?? null;
    if ($uf && is_array($uf['name'])) foreach ($uf['name'] as $i => $n) {
        $er = $uf['error'][$i];
        if ($er === UPLOAD_ERR_NO_FILE) continue;
        $ext = strtolower(pathinfo((string)$n, PATHINFO_EXTENSION));
        if ($er !== UPLOAD_ERR_OK) { $errors[] = $n . ': no se pudo subir (' . ($er === UPLOAD_ERR_INI_SIZE || $er === UPLOAD_ERR_FORM_SIZE ? 'supera el límite de tu hosting; usá el link de descarga' : 'error ' . $er) . ').'; continue; }
        if (!in_array($ext, ['pdf','zip','html','epub','xlsx','docx'], true)) { $errors[] = $n . ': archivo no permitido (PDF, ZIP, HTML, EPUB, XLSX o DOCX).'; continue; }
        if (count($files) >= 6) { $errors[] = 'Máximo 6 archivos por producto.'; break; }
        ensure_dirs(); $stored = bin2hex(random_bytes(12)) . '.dat';
        if (move_uploaded_file($uf['tmp_name'][$i], PRIVATE_DIR . '/' . $stored)) $files[] = ['stored' => $stored, 'orig' => mb_substr(basename((string)$n), 0, 180), 'size' => (int)$uf['size'][$i]];
        else $errors[] = $n . ': no se pudo guardar en el servidor.';
    }
    $p['files'] = $files;
    if ($p['title'] === '') $errors[] = 'Falta el nombre del producto.';
    if (mb_strlen($p['title']) > 80) $errors[] = 'El nombre es muy largo (máximo 80 caracteres).';
    if (mb_strlen($p['desc']) > 400) $errors[] = 'La descripción es muy larga (máximo 400 caracteres).';

    // fotos: quitar, reordenar (portada), agregar
    $keep = [];
    foreach ($p['images'] as $im) if (empty($_POST['rm'][$im])) $keep[] = $im; else delete_upload($im);
    $cover = (string)($_POST['cover'] ?? '');
    if ($cover !== '' && in_array($cover, $keep, true)) { $keep = array_values(array_diff($keep, [$cover])); array_unshift($keep, $cover); }
    $base = $isNew ? slugify($p['title']) : $p['id'];
    if (!empty($_FILES['imgs']['name'][0])) {
        foreach ($_FILES['imgs']['name'] as $i => $n) {
            if (count($keep) >= MAX_IMAGES) { $warns[] = 'Máximo ' . MAX_IMAGES . ' fotos por producto; algunas no se agregaron.'; break; }
            $f = ['name'=>$n,'type'=>$_FILES['imgs']['type'][$i],'tmp_name'=>$_FILES['imgs']['tmp_name'][$i],'error'=>$_FILES['imgs']['error'][$i],'size'=>$_FILES['imgs']['size'][$i]];
            if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
            try { $keep[] = store_upload($f, $base); } catch (RuntimeException $e) { $warns[] = $n . ': ' . $e->getMessage(); }
        }
    }
    $p['images'] = $keep;
    if (!$p['images']) $errors[] = 'Subí al menos una foto (lo ideal son 3 o 4).';
    if ($p['images']) $p['ar'] = image_ratio($p['images'][0]);

    if (!$errors) {
        try {
            if ($isNew) {
                $slug = $base; $n = 2; while (find_product($list, $slug)) $slug = $base . '-' . $n++;
                $p['id'] = $slug; $p['order'] = product_next_order(); product_insert($p);
            } else product_update($p);
            foreach ($toDelete as $d) @unlink(PRIVATE_DIR . '/' . basename($d));
            flash(($isNew ? 'Producto creado.' : 'Cambios guardados.') . ($warns ? ' Aviso: ' . implode(' ', $warns) : ''), $warns ? 'warn' : 'ok');
            header('Location: index.php'); exit;
        } catch (Throwable $e) { error_log('admin save: ' . $e->getMessage()); $errors[] = 'No se pudo guardar en la base de datos.'; }
    }
}
$themes = themes();
admin_head($isNew ? 'Nuevo producto' : 'Editar producto'); ?>
<div class="head"><div><a class="back" href="index.php">← Productos</a><h1><?= $isNew ? 'Nuevo producto' : 'Editar: ' . h(str_replace('*', '', $p['title'])) ?></h1></div></div>
<?php foreach (array_merge($errors, $warns) as $e): ?><div class="flash err"><?= h($e) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($p['id']) ?>">
<div class="card">
  <h2>Datos del producto</h2>
  <label>Nombre<input name="title" maxlength="80" required value="<?= h(str_replace('*', '', $p['title'])) ?>" placeholder="Agenda Michis 2027"></label>
  <label>Palabra en cursiva (opcional)<input name="italic" maxlength="40" value="<?= h(preg_match('/\*(.+?)\*/u', (string)$p['title'], $mi) ? $mi[1] : '') ?>" placeholder="Michis">
    <small>Escribí una palabra del nombre y se muestra en cursiva y de color, como en “Agenda <i>Michis</i> 2027”. Dejalo vacío si no querés cursiva. No hace falta poner asteriscos.</small></label>
  <label>Línea superior (chiquita)<input name="kicker" maxlength="60" value="<?= h($p['kicker']) ?>" placeholder="Imprimible · PDF · A4"></label>
  <label>Descripción<textarea name="desc" rows="4" maxlength="400" placeholder="Para quién es y qué tiene, en 2 o 3 frases."><?= h($p['desc']) ?></textarea></label>
  <label>Link de la página propia del producto (opcional)<input name="landing" type="url" value="<?= h($p['landing']) ?>" placeholder="https://miapp.estrategia.site">
    <small>Si lo completás, el botón “Ver detalle” lleva a ese link (por ejemplo una app que ya tiene su propia página) y no se usa la página de detalle ni la compra de acá. Dejalo vacío para vender con detalle y Mercado Pago.</small></label>
  <label>Detalle completo (se ve en la página del producto)<textarea name="long" rows="8" maxlength="8000" placeholder="Contá todo: para quién es, cómo se usa, qué hojas trae. Separá los párrafos con una línea en blanco."><?= h($p['long']) ?></textarea></label>
  <div class="two">
<?php $dr = dolar_rate(); $rv = (float)($dr['valor'] ?? 0); ?>
    <label>Precio en dólares (USD, opcional)<input name="price_usd" id="pusd" inputmode="decimal" value="<?= ($p['price_usd'] ?? 0) > 0 ? h(rtrim(rtrim(number_format((float)$p['price_usd'], 2, '.', ''), '0'), '.')) : '' ?>" placeholder="9.99" data-rate="<?= h((string)$rv) ?>">
      <small><?php if ($rv > 0): ?>Dólar <?= h(strtolower(explode(' (', dolar_types()[$dr['tipo']] ?? 'oficial')[0])) ?> hoy: <b><?= h(format_price($rv)) ?></b><?= empty($dr['ok']) ? ' (última cotización guardada)' : '' ?>. El precio en pesos se recalcula solo cada día. <a href="ajustes.php">Cambiar tipo de dólar</a><?php else: ?>No se pudo leer la cotización ahora; cargá el precio en pesos.<?php endif; ?></small></label>
        <label>Precio en pesos (ARS)<input name="price" id="pars" inputmode="decimal" value="<?= $p['price'] > 0 ? h((string)(fmod((float)$p['price'], 1) == 0.0 ? (int)$p['price'] : $p['price'])) : '' ?>" placeholder="4500"><small id="parsh">Sin precio, el botón de compra no aparece. Si cargás USD, este se calcula solo.</small></label>
  </div>
  <div class="two">
    <label>Tipo<select name="type"><?php foreach (types() as $k => $v): ?><option value="<?= h($k) ?>"<?= $p['type'] === $k ? ' selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select></label>
    <label>Cantidad de páginas<input name="pages" type="number" min="0" value="<?= (int)$p['pages'] ?>"><small>Suma al contador del inicio.</small></label>
  </div>
  <label>Etiquetas (hasta 4, separadas por coma)<input name="meta" value="<?= h(implode(', ', $p['meta'])) ?>" placeholder="387 páginas, A4, 365 días fechados"></label>
  <label>Qué incluye (una por línea, hasta 5)<textarea name="feats" rows="4" placeholder="Metas y 12 meses&#10;Planificador semanal&#10;Hábitos y finanzas"><?= h(implode("\n", $p['feats'])) ?></textarea></label>
  <label class="chk"><input type="checkbox" name="visible" value="1"<?= !empty($p['visible']) ? ' checked' : '' ?>> Visible en el sitio</label>
</div>
<div class="card">
  <h2>Colores</h2>
  <div class="themes"><?php foreach ($themes as $k => $t): ?>
    <label class="th"><input type="radio" name="theme" value="<?= h($k) ?>"<?= $p['theme'] === $k ? ' checked' : '' ?>><span style="background:<?= h($t['bg']) ?>;color:<?= h($t['fg']) ?>"><i style="background:<?= h($t['tag']) ?>"></i><?= h($t['label']) ?></span></label>
  <?php endforeach; ?></div>
  <h2 style="margin-top:22px">Fotos <small class="muted">(3 o 4 recomendadas, máximo <?= MAX_IMAGES ?>)</small></h2>
  <?php if ($p['images']): ?><div class="imgs"><?php foreach ($p['images'] as $i => $im): ?>
    <div class="im"><img src="../uploads/<?= h(rawurlencode($im)) ?>" alt="">
      <label class="mini"><input type="radio" name="cover" value="<?= h($im) ?>"<?= $i === 0 ? ' checked' : '' ?>> Portada</label>
      <label class="mini rm"><input type="checkbox" name="rm[<?= h($im) ?>]" value="1"> Quitar</label></div>
  <?php endforeach; ?></div><?php endif; ?>
  <label class="drop">Agregar fotos<input type="file" name="imgs[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>JPG, PNG o WebP de hasta 8 MB. Se achican y optimizan solas. La primera es la portada.</small></label>
  <h2 style="margin-top:22px">Archivos que recibe el comprador</h2>
  <?php if ($p['files']): ?><div class="imgs" style="display:block"><?php foreach ($p['files'] as $fl): ?>
    <p class="muted" style="margin:6px 0">📄 <b><?= h($fl['orig']) ?></b> · <?= h(file_kind($fl['orig'])) ?> · <?= h(human_size((int)($fl['size'] ?? 0))) ?>
      <label class="mini rm"><input type="checkbox" name="rm_files[<?= h($fl['stored']) ?>]" value="1"> Quitar</label></p>
  <?php endforeach; ?></div><?php else: ?><p class="muted">Todavía no hay archivos subidos.</p><?php endif; ?>
  <label class="drop"><?= $p['files'] ? 'Agregar más archivos' : 'Subir archivos' ?> (PDF, ZIP, HTML, EPUB, XLSX, DOCX)<input type="file" name="dl[]" multiple>
    <small>Podés elegir varios a la vez (por ejemplo la agenda en HTML y el manual en PDF). Quedan en una carpeta privada: solo se entregan a quien paga. “Ningún archivo seleccionado” es normal: los ya subidos están en la lista de arriba.</small></label>
  <label>…o link de descarga (Drive, Dropbox, etc.)<input name="ext_url" type="url" value="<?= h($p['ext_url']) ?>" placeholder="https://..."><small>Úsalo si el archivo es muy pesado para subirlo. Se entrega solo después del pago. Si hay archivo subido, se usa el archivo.</small></label>
  <div class="save"><button class="btn" type="submit"><?= $isNew ? 'Crear producto' : 'Guardar cambios' ?></button><a href="index.php" class="muted">Cancelar</a></div>
</div>
</form>
<script>(function(){var u=document.getElementById('pusd'),a=document.getElementById('pars'),hh=document.getElementById('parsh');if(!u||!a)return;var r=parseFloat(u.dataset.rate)||0;
function up(){var v=parseFloat((u.value||'').replace(',','.'));if(v>0&&r>0){var ars=Math.ceil(v*r/100)*100;a.value=ars;a.readOnly=true;a.style.background='#f3efe7';hh.textContent='Calculado: US$ '+v+' × $'+r.toLocaleString('es-AR')+' = $'+ars.toLocaleString('es-AR')+' (redondeado a la centena).'}else{a.readOnly=false;a.style.background='';hh.textContent='Sin precio, el botón de compra no aparece. Si cargás USD, este se calcula solo.'}}
u.addEventListener('input',up);up()})();</script>
<?php admin_foot();
