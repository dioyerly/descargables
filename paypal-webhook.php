<?php
declare(strict_types=1);
// Aviso automático (webhook) de PayPal: cobros que se completan después, rechazos y devoluciones
require __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
if (!db_ready() || !pp_configured()) { http_response_code(503); exit('no configurado'); }
$raw = (string)file_get_contents('php://input');
if (!pp_webhook_ok($raw)) { http_response_code(401); exit('firma inválida'); }
$j = json_decode($raw, true) ?: [];
$ev = (string)($j['event_type'] ?? '');
$r = $j['resource'] ?? [];
try {
    if (str_starts_with($ev, 'PAYMENT.CAPTURE.')) {
        $code = (string)($r['custom_id'] ?? '');
        $order = order_by_code($code);
        if ($order && $order['method'] === 'pp') {
            $ppo = ['purchase_units' => [['custom_id' => $code, 'payments' => ['captures' => [$r]]]]];
            if (in_array($ev, ['PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED'], true)) $ppo['_event'] = 'refund';
            pp_apply_order($ppo);
        }
    }
    http_response_code(200); echo 'ok';
} catch (Throwable $e) { error_log('pp webhook: ' . $e->getMessage()); http_response_code(500); echo 'reintentar'; }
