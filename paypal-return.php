<?php
declare(strict_types=1);
// Vuelta desde PayPal: si la persona aprobó, confirma el cobro; si canceló, vuelve a la ficha
require __DIR__ . '/lib/bootstrap.php';
$order = (db_ready() && pp_configured()) ? order_by_code((string)($_GET['o'] ?? '')) : null;
if (!$order || $order['method'] !== 'pp') { header('Location: ' . base_path() . '/'); exit; }
if (!empty($_GET['cancel'])) {
    $st = db()->prepare('SELECT slug FROM products WHERE id=?'); $st->execute([$order['product_id']]);
    $slug = (string)$st->fetchColumn();
    header('Location: ' . ($slug !== '' ? product_url($slug) . '?err=' . rawurlencode('Cancelaste el pago en PayPal. Cuando quieras podés intentarlo de nuevo.') . '#comprar' : base_path() . '/')); exit;
}
if ($order['status'] !== 'paid') { try { pp_capture_order($order); } catch (Throwable $e) { error_log('pp return: ' . $e->getMessage()); } }
header('Location: ' . site_url('gracias.php?o=' . $order['code'])); exit;
