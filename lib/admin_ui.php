<?php
declare(strict_types=1);
function admin_head(string $title, bool $nav = true): void { ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title><?= h($title) ?> · Panel EstrategIA</title>
<link rel="stylesheet" href="../assets/css/admin.css?v=1"></head><body>
<?php if ($nav): ?><header class="top"><a class="brand" href="index.php"><i>e</i>EstrategIA <small>Panel de descargables</small></a>
<nav><a href="index.php">Productos</a><a href="edit.php" class="b">+ Nuevo producto</a><a href="muestras.php">Muestras</a><a href="pedidos.php">Pedidos</a><a href="setup.php">Base de datos</a><a href="password.php">Contraseña</a><a href="../" target="_blank" rel="noopener">Ver sitio ↗</a><a href="logout.php">Salir</a></nav></header><?php endif; ?>
<main class="wrap">
<?php }
function admin_foot(): void { ?></main></body></html><?php }
function flash(?string $msg = null, string $kind = 'ok'): ?array {
    start_secure_session();
    if ($msg !== null) { $_SESSION['flash'] = [$kind, $msg]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function show_flash(): void { $f = flash(); if ($f) echo '<div class="flash ' . h($f[0]) . '">' . h($f[1]) . '</div>'; }
