/* Eventos del píxel de TikTok (solo si el píxel está cargado; el dueño no lo carga) */
(function(){'use strict';
function tt(n,p,id){try{if(window.ttq&&ttq.track)ttq.track(n,p||{},id?{event_id:id}:undefined)}catch(e){}}
window.ttEvent=tt;
var B=document.body;
function item(name,id){return {contents:[{content_id:String(id||name||''),content_type:'product',content_name:String(name||'')}]}}
/* Contacto: botones que llevan a WhatsApp */
document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('a[href]');if(!a)return;
  if(/wa\.me\//.test(a.getAttribute('href')||'')){var h=a.closest('[data-title]');tt('Contact',item((h&&h.dataset.title)||B.dataset.title||'WhatsApp'))}},true);
/* Inicio de compra: botón Comprar de la página del producto */
document.addEventListener('submit',function(e){var f=e.target;
  if(/comprar\.php/.test((f.getAttribute&&f.getAttribute('action'))||'')){var p=item(B.dataset.title||'',B.dataset.pid||B.dataset.title);
    if(+B.dataset.price>0){p.value=+B.dataset.price;p.currency='ARS'}tt('InitiateCheckout',p)}},true);
})();
