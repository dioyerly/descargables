<?php
// Pie común con legales + botón "volver arriba". Usa $B (base) si existe.
$__B = isset($B) ? (string)$B : base_path(); $__b = biz(); $__L = $__B . '/legales.php';
?>
<footer class="sfoot">
  <div class="sf-row"><span>© <?= date('Y') ?> <?= h($__b['name']) ?> · Productos digitales · <?= h($__b['country']) ?></span><span>Productos digitales: no se envía nada físico.</span></div>
  <nav class="sf-links" aria-label="Legales"><a href="<?= h($__L) ?>#terminos">Términos y condiciones</a><a href="<?= h($__L) ?>#privacidad">Privacidad</a><a href="<?= h($__L) ?>#devoluciones">Devoluciones</a><a href="<?= h($__L) ?>#arrepentimiento">Botón de arrepentimiento</a><a href="mailto:<?= h($__b['email']) ?>"><?= h($__b['email']) ?></a><?php if (!empty($footBack)): ?><a href="<?= h($__B) ?>/">Ver más productos</a><?php endif; ?></nav>
</footer>
<button class="totop" id="totop" type="button" aria-label="Volver al inicio"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Inicio</span></button>
<script>(function(){var b=document.getElementById('totop');if(!b)return;var on=false;function f(){var v=scrollY>innerHeight*.9;if(v!==on){on=v;b.classList.toggle('on',v)}}addEventListener('scroll',f,{passive:true});f();
b.addEventListener('click',function(){try{scrollTo({top:0,behavior:'instant'})}catch(e){scrollTo(0,0)}if(window.trk)trk('click','Volver al inicio')})})();</script>
