<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
require_login();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = (string)($_POST['old'] ?? ''); $new = (string)($_POST['new'] ?? '');
    if (!check_password($old)) $err = 'La contraseña actual no es correcta.';
    elseif (mb_strlen($new) < 10) $err = 'La nueva contraseña debe tener al menos 10 caracteres.';
    elseif ($new !== (string)($_POST['new2'] ?? '')) $err = 'Las contraseñas nuevas no coinciden.';
    elseif (!set_password($new)) $err = 'No se pudo guardar.';
    else { flash('Contraseña actualizada.'); header('Location: index.php'); exit; }
}
admin_head('Contraseña'); ?>
<h1>Cambiar contraseña</h1>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
<label>Contraseña actual<input type="password" name="old" required autocomplete="current-password"></label>
<label>Nueva contraseña<input type="password" name="new" required autocomplete="new-password"></label>
<label>Repetir nueva contraseña<input type="password" name="new2" required autocomplete="new-password"></label>
<button class="btn" type="submit">Guardar</button></form>
<?php admin_foot();
