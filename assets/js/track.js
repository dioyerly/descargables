/* Estadísticas propias y anónimas (sin cookies de terceros). El dueño queda excluido por la cookie est_owner. */
(function(){'use strict';
var B=document.body,base=B.dataset.base||'';
if(/(^|;\s*)est_owner=1/.test(document.cookie)){window.trk=function(){};return}
function rid(k,st){try{var v=st.getItem(k);if(!v){v=(Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2)).slice(0,14);st.setItem(k,v)}return v}catch(e){return ''}}
var vid=rid('est_v',localStorage),sid=rid('est_s',sessionStorage),src='';
try{src=sessionStorage.getItem('est_src')||''}catch(e){}
if(!src){var q=new URLSearchParams(location.search),u=q.get('utm_source')||q.get('ref')||'',r='';
  try{r=document.referrer?new URL(document.referrer).hostname.replace(/^www\./,''):''}catch(e){}
  if(r===location.hostname.replace(/^www\./,''))r='';src=(u||r||'directo').toLowerCase().slice(0,60);try{sessionStorage.setItem('est_src',src)}catch(e){}}
var page=B.dataset.page||'otra',dev=matchMedia('(max-width:860px)').matches?'m':'d';
function send(t,n){var b=JSON.stringify({t:t,n:String(n||'').slice(0,140),p:page,s:src,v:vid,i:sid,d:dev});
  try{if(navigator.sendBeacon&&navigator.sendBeacon(base+'/track.php',new Blob([b],{type:'text/plain'})))return}catch(e){}
  try{fetch(base+'/track.php',{method:'POST',body:b,keepalive:true,headers:{'Content-Type':'text/plain'}})}catch(e){}}
window.trk=send;
send('pv',B.dataset.title||'');
var title=function(el){var h=el&&el.closest('[data-title]');return h?h.dataset.title:''};
document.addEventListener('click',function(e){var a=e.target.closest('a,button');if(!a)return;var h=a.getAttribute('href')||'',k=a.dataset.trk;
  if(k){var i=k.indexOf(':');send(i<0?k:k.slice(0,i),i<0?'':k.slice(i+1));return}
  var m=h.match(/^#(agendas|apps|landings|micro)$/);if(m){send('tab',m[1]);return}
  if(/wa\.me\//.test(h)){send('wa',title(a)||page);return}
  if(/demo\.php/.test(h)||a.closest('[data-app]')&&/demo/i.test(a.textContent)){send('demo',title(a));return}
  if(a.hasAttribute('data-open')||a.hasAttribute('data-zoom')||a.closest('.pg')){send('photos',title(a)||B.dataset.title||'');return}
  if(/\/producto\//.test(h)){send('click','Ver detalle · '+title(a));return}
  if(a.type!=='submit'&&a.matches('.btn,.b')&&a.textContent.trim())send('click',a.textContent.trim().slice(0,60))},true);
document.addEventListener('submit',function(e){var f=e.target;if(/comprar\.php/.test(f.getAttribute('action')||''))send('buy',B.dataset.title||'')},true);
if('IntersectionObserver' in window){var tm=new Map(),io=new IntersectionObserver(function(es){es.forEach(function(x){var el=x.target;if(x.isIntersecting){tm.set(el,setTimeout(function(){io.unobserve(el);send('seen',el.dataset.title||el.id)},800))}else{clearTimeout(tm.get(el))}})},{rootMargin:'-45% 0px -45% 0px'});
  document.querySelectorAll('.prod[data-title],.app[data-title]').forEach(function(s){io.observe(s)})}
})();
