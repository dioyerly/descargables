<?php
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/admin_ui.php';
start_secure_session();
$setup = !has_password();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pass = (string)($_POST['pass'] ?? '');
    if ($setup) {
        if (mb_strlen($pass) < 10) $err = 'La contraseña debe tener al menos 10 caracteres.';
        elseif ($pass !== (string)($_POST['pass2'] ?? '')) $err = 'Las contraseñas no coinciden.';
        elseif (!set_password($pass)) $err = 'No se pudo guardar. Revisá que la carpeta data/ tenga permisos de escritura.';
        else { session_regenerate_id(true); $_SESSION['eia_ok'] = 1; header('Location: index.php'); exit; }
    } else {
        $wait = login_blocked();
        if ($wait > 0) $err = 'Demasiados intentos. Esperá ' . ceil($wait / 60) . ' minutos.';
        elseif (check_password($pass)) { login_attempt(true); session_regenerate_id(true); $_SESSION['eia_ok'] = 1; header('Location: index.php'); exit; }
        else { login_attempt(false); usleep(600000); $err = 'Contraseña incorrecta.'; }
    }
}
admin_head($setup ? 'Crear contraseña' : 'Ingresar', false); ?>
<div class="login"><div class="brand big"><i>e</i>EstrategIA</div>
<h1><?= $setup ? 'Creá tu contraseña' : 'Panel de descargables' ?></h1>
<p class="muted"><?= $setup ? 'Es la primera vez que entrás. Elegí una contraseña de al menos 10 caracteres: la vas a usar siempre para entrar al panel.' : 'Ingresá tu contraseña para administrar los productos.' ?></p>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
<label>Contraseña<input type="password" name="pass" required autofocus autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>"></label>
<?php if ($setup): ?><label>Repetir contraseña<input type="password" name="pass2" required autocomplete="new-password"></label><?php endif; ?>
<button class="btn" type="submit"><?= $setup ? 'Guardar y entrar' : 'Entrar' ?></button></form></div>
<?php admin_foot();
