<?php
declare(strict_types=1);
// PayPal (Orders v2) — para compradores con tarjetas del exterior. Cobra en dólares.

function pp_configured(): bool {
    $id = (string)cfg('paypal.client_id', ''); $sec = (string)cfg('paypal.secret', '');
    return $id !== '' && $sec !== '' && $id !== 'PONER_AQUI' && $sec !== 'PONER_AQUI';
}
function pp_base(): string {
    $b = (string)cfg('paypal.api_base', '');
    if ($b !== '') return rtrim($b, '/');
    return cfg('paypal.mode', 'live') === 'sandbox' ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
}

/** Monto en dólares a cobrar por PayPal: el precio en USD del producto, o el de pesos convertido. 0 si no se puede. */
function pp_usd_amount(array $p): float {
    $usd = (float)($p['price_usd'] ?? 0);
    if ($usd > 0) return round($usd, 2);
    $rate = (float)(dolar_rate()['valor'] ?? 0);
    return ($rate > 0 && (float)$p['price'] > 0) ? round((float)$p['price'] / $rate, 2) : 0.0;
}
function pp_available(array $p): bool { return pp_configured() && pp_usd_amount($p) >= 1; }

function pp_token(): string {
    static $tok = null, $exp = 0;
    if ($tok !== null && time() < $exp) return $tok;
    $ch = curl_init(pp_base() . '/v1/oauth2/token');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => cfg('paypal.client_id', '') . ':' . cfg('paypal.secret', ''), CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US']]);
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($res === false) throw new RuntimeException('PayPal no respondió: ' . $err);
    $j = json_decode((string)$res, true);
    if ($code >= 400 || empty($j['access_token'])) { error_log("PayPal token -> $code $res"); throw new RuntimeException('PayPal rechazó las claves (' . $code . '). Revisá Client ID y Secret en config.php.'); }
    $exp = time() + max(60, (int)($j['expires_in'] ?? 300) - 60);
    return $tok = (string)$j['access_token'];
}

function pp_api(string $method, string $path, ?array $body = null, ?string $reqId = null): array {
    $ch = curl_init(pp_base() . $path);
    $hd = ['Authorization: Bearer ' . pp_token(), 'Content-Type: application/json', 'Accept: application/json'];
    if ($reqId) $hd[] = 'PayPal-Request-Id: ' . $reqId;
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $hd, CURLOPT_TIMEOUT => 30]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body === [] ? '{}' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($res === false) throw new RuntimeException('PayPal no respondió: ' . $err);
    $j = json_decode((string)$res, true);
    if ($code >= 400) {
        error_log("PayPal $method $path -> $code $res");
        $issue = (string)($j['details'][0]['issue'] ?? $j['name'] ?? '');
        throw new RuntimeException('PayPal ' . $code . ($issue !== '' ? ': ' . $issue : ''));
    }
    return is_array($j) ? $j : [];
}

/** Crea el pedido pendiente y la orden de PayPal. Devuelve la URL de PayPal donde la persona paga. */
function pp_start_checkout(array $p, string $email): string {
    $usd = pp_usd_amount($p);
    if ($usd < 1) throw new RuntimeException('Sin precio en dólares.');
    $pdo = db();
    $code = bin2hex(random_bytes(16));
    $title = strip_tags(str_replace('*', '', $p['title']));
    $pdo->prepare("INSERT INTO orders (code, product_id, product_title, amount, currency, buyer_email, status, ip, method) VALUES (?,?,?,?,?,?,?,?,'pp')")
        ->execute([$code, $p['pk'], $title, $usd, 'USD', $email, 'pending', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)]);
    $o = pp_api('POST', '/v2/checkout/orders', [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'D' . substr($code, 0, 12), 'custom_id' => $code, 'invoice_id' => 'DESC-' . substr($code, 0, 16),
            'description' => mb_substr($title, 0, 120),
            'amount' => ['currency_code' => 'USD', 'value' => number_format($usd, 2, '.', '')],
        ]],
        'payment_source' => ['paypal' => ['email_address' => $email, 'experience_context' => [
            'brand_name' => 'EstrategIA Descargables', 'landing_page' => 'GUEST_CHECKOUT', 'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW', 'payment_method_preference' => 'UNRESTRICTED',
            'return_url' => site_url('paypal-return.php?o=' . $code), 'cancel_url' => site_url('paypal-return.php?o=' . $code . '&cancel=1'),
        ]]],
    ], $code);
    $url = '';
    foreach (($o['links'] ?? []) as $l) if (in_array($l['rel'] ?? '', ['payer-action', 'approve'], true)) { $url = (string)$l['href']; break; }
    if ($url === '' || empty($o['id'])) throw new RuntimeException('PayPal no devolvió el enlace de pago.');
    $pdo->prepare('UPDATE orders SET pp_order_id=? WHERE code=?')->execute([(string)$o['id'], $code]);
    return $url;
}

/** Aplica el estado de una orden de PayPal (ya capturada o consultada) al pedido. Idempotente. */
function pp_apply_order(array $ppo): ?array {
    $pu = $ppo['purchase_units'][0] ?? [];
    $code = (string)($pu['custom_id'] ?? '');
    $order = order_by_code($code);
    if (!$order || $order['method'] !== 'pp') return null;
    $cap = $pu['payments']['captures'][0] ?? null;
    if (!$cap) return $order;
    $st = (string)($cap['status'] ?? '');
    $capId = (string)($cap['id'] ?? '');
    if ($st === 'COMPLETED') {
        if (($cap['amount']['currency_code'] ?? '') !== $order['currency'] || (float)($cap['amount']['value'] ?? 0) + 0.001 < (float)$order['amount']) {
            error_log("PayPal $capId: monto/moneda no coincide con pedido $code"); return $order;
        }
        return order_mark_paid($code, $capId);
    }
    if ($st === 'DECLINED' || $st === 'FAILED') return order_fail($code, 'PayPal: ' . ($cap['status_details']['reason'] ?? 'pago rechazado'), $capId);
    if (in_array($st, ['REFUNDED', 'PARTIALLY_REFUNDED'], true) || ($ppo['_event'] ?? '') === 'refund') return order_refund($code, 'PayPal: devolución');
    return $order; // PENDING: espera el aviso de PayPal
}

/** Vuelta del comprador: confirma (captura) el pago aprobado en PayPal. */
function pp_capture_order(array $order): array {
    $id = (string)$order['pp_order_id'];
    if ($id === '') return $order;
    try {
        $ppo = pp_api('GET', '/v2/checkout/orders/' . rawurlencode($id));
        if (($ppo['status'] ?? '') === 'APPROVED') $ppo = pp_api('POST', '/v2/checkout/orders/' . rawurlencode($id) . '/capture', [], 'cap-' . $order['code']);
        return pp_apply_order($ppo) ?? $order;
    } catch (Throwable $e) {
        error_log('pp capture: ' . $e->getMessage());
        if (str_contains($e->getMessage(), 'INSTRUMENT_DECLINED') || str_contains($e->getMessage(), 'PAYER_ACTION_REQUIRED') || str_contains($e->getMessage(), 'TRANSACTION_REFUSED')) {
            return order_fail($order['code'], $e->getMessage(), '') ?? $order;
        }
        return $order;
    }
}

/** Verifica la firma de un aviso (webhook) de PayPal. Sin webhook_id configurado, no se acepta (se confirma consultando la orden). */
function pp_webhook_ok(string $raw): bool {
    $wid = (string)cfg('paypal.webhook_id', '');
    if ($wid === '') return false;
    $h = fn(string $k) => (string)($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $k))] ?? '');
    try {
        $r = pp_api('POST', '/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $h('PAYPAL-AUTH-ALGO'), 'cert_url' => $h('PAYPAL-CERT-URL'), 'transmission_id' => $h('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $h('PAYPAL-TRANSMISSION-SIG'), 'transmission_time' => $h('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $wid, 'webhook_event' => json_decode($raw, true),
        ]);
        return ($r['verification_status'] ?? '') === 'SUCCESS';
    } catch (Throwable $e) { error_log('pp webhook verify: ' . $e->getMessage()); return false; }
}
