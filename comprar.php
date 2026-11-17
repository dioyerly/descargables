<?php
declare(strict_types=1);
// Recibe el formulario de compra → crea el pedido → manda a Mercado Pago o a PayPal
require __DIR__ . '/lib/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . base_path() . '/'); exit; }
$slug = (string)($_POST['slug'] ?? '');
$back = product_url($slug);
$fail = function (string $m) use ($back) { header('Location: ' . $back . '?err=' . rawurlencode($m) . '#comprar'); exit; };
if (!empty($_POST['web'])) exit; // trampa para bots
if (!db_ready() || !(mp_configured() || pp_configured())) $fail('La compra no está disponible por ahora.');
$p = find_product(load_products(), $slug);
if (!$p || !$p['visible'] || !product_for_sale($p)) $fail('Este producto todavía no está a la venta.');
$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $fail('Escribí un email válido: ahí te mandamos la descarga.');
$st = db()->prepare("SELECT COUNT(*) FROM orders WHERE ip=? AND created_at > (NOW() - INTERVAL 1 HOUR)");
$st->execute([substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)]);
if ((int)$st->fetchColumn() >= 15) $fail('Demasiados intentos. Probá de nuevo en un rato.');
$pp = ($_POST['metodo'] ?? '') === 'paypal';
if ($pp ? !pp_available($p) : !mp_configured()) $fail('Ese medio de pago no está disponible para este producto.');
try { $url = $pp ? pp_start_checkout($p, $email) : mp_start_checkout($p, $email); }
catch (Throwable $e) { error_log('checkout: ' . $e->getMessage()); try { setting_set('last_checkout_error', date('d/m/Y H:i') . ' · ' . ($pp ? 'PayPal' : 'Mercado Pago') . ' · ' . mb_substr($e->getMessage(), 0, 300)); } catch (Throwable $e2) {} $fail('No pudimos iniciar el pago. Probá de nuevo en unos minutos.'); }
header('Location: ' . $url); exit;
