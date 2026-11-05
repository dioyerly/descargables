<?php
declare(strict_types=1);
// Anotarse para que le avisen cuando un producto de “Próximamente” esté disponible. Devuelve JSON.
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$out = fn(bool $ok, string $msg) => exit(json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); $out(false, 'Método no permitido.'); }
// trampa para robots: si el campo oculto viene con texto, decimos que está bien pero no guardamos nada
if (trim((string)($_POST['web'] ?? '')) !== '') $out(true, '¡Listo! Te avisamos cuando esté disponible.');
$slug = (string)($_POST['slug'] ?? '');
$email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
if (!preg_match('/^[a-z0-9-]{1,120}$/', $slug)) $out(false, 'No encontramos ese producto.');
if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $out(false, 'Escribí un mail válido.');
if (!showcase_ready() || !avisos_ready()) $out(false, 'Estamos actualizando el sitio. Probá de nuevo en un rato.');
$s = showcase_find($slug);
if (!$s || $s['section'] !== 'soon' || !$s['visible']) $out(false, 'Ese producto ya no está en “Próximamente”.');
try {
    db()->prepare('INSERT IGNORE INTO avisos (slug, email) VALUES (?, ?)')->execute([$slug, $email]);
} catch (Throwable $e) { error_log('aviso: ' . $e->getMessage()); $out(false, 'No pudimos guardar tu mail. Probá de nuevo.'); }
$out(true, '¡Listo! Te avisamos cuando “' . $s['title'] . '” esté disponible.');
