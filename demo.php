<?php
declare(strict_types=1);
/* Sirve una demo/landing subida en private/muestras dentro del visor.
   Va "encerrada" (CSP sandbox): no puede tocar cookies, sesión ni el resto del sitio.
   Como el sandbox bloquea localStorage/sessionStorage, se inyecta un reemplazo en memoria
   para que las apps de demo (que guardan datos en el navegador) funcionen igual. */
$f = (string)($_GET['f'] ?? '');
$path = __DIR__ . '/private/muestras/' . $f;
if (!preg_match('~^[A-Za-z0-9._-]+\.html?$~', $f) || !is_file($path)) { http_response_code(404); header('Content-Type: text/html; charset=utf-8'); exit('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><body style="margin:0;min-height:100vh;display:grid;place-items:center;font:16px system-ui;color:#666;text-align:center;padding:20px"><p>Esta demo no está disponible por ahora.</p>'); }
$html = (string)file_get_contents($path);
$shim = '<script>(function(){function S(){var d={};return{getItem:function(k){return Object.prototype.hasOwnProperty.call(d,k)?d[k]:null},setItem:function(k,v){d[k]=String(v)},removeItem:function(k){delete d[k]},clear:function(){d={}},key:function(i){return Object.keys(d)[i]||null},get length(){return Object.keys(d).length}}}'
      . '["localStorage","sessionStorage"].forEach(function(n){var ok=true;try{void window[n]}catch(e){ok=false}if(!ok){var s=S();try{Object.defineProperty(window,n,{get:function(){return s},configurable:true})}catch(x){}}});})();</script>';
$n = 0;
$html = preg_replace('~<head[^>]*>~i', '$0' . $shim, $html, 1, $n);
if (!$n) $html = $shim . $html;
header('Content-Type: text/html; charset=utf-8');
header('Content-Security-Policy: sandbox allow-scripts allow-forms allow-popups allow-modals');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache');
echo $html;
