(function(){
'use strict';
const $=id=>document.getElementById(id),$$=(q,r)=>[...(r||document).querySelectorAll(q)];
const cl=(x,a=0,b=1)=>Math.min(b,Math.max(a,x));const ss=(a,b,x)=>{const t=cl((x-a)/(b-a));return t*t*(3-2*t)};const lerp=(a,b,t)=>a+(b-a)*t;
const RM=matchMedia('(prefers-reduced-motion:reduce)').matches;

/* inicio: el universo con el núcleo y las portadas en órbita vive en universo.js */
const rot=$('rot'),words=rot?$$('b',rot):[],w2=$$('#rot2 b');let wi=0,w2i=0;
function setWord(i){if(i===wi)return;wi=i;words.forEach((w,k)=>w.classList.toggle('on',k===i))}
function setW2(i){if(i===w2i)return;w2i=i;w2.forEach((w,k)=>w.classList.toggle('on',k===i))}
if(words.length&&!RM)setInterval(()=>{if(scrollY<innerHeight*.15)setWord((wi+1)%words.length)},2200);
/* productos: las páginas se reparten con el scroll */
function fans(){const M=innerWidth<=860;
  $$('.prod').forEach(sec=>{if(sec.classList.contains('hide')||!sec.offsetParent)return;const r=sec.getBoundingClientRect();if(r.bottom<-50||r.top>innerHeight*1.1)return;
    const p=cl(-r.top/(r.height-innerHeight));const pgs=$$('.pg',sec);const n=pgs.length;const fan=sec.querySelector('.fan');const fw=fan.clientWidth;
    const open=ss(0,.3,p);const act=Math.min(n-1,Math.floor(ss(.3,.95,p)*n*.999));
    pgs.forEach((e,i)=>{const spread=M?fw*.24:fw*.28;const off=i-act;
      const o2=off<0?-.45+off*.06:off*.62;const x=lerp((i-1.5)*3,o2*spread,open),y=lerp(i*-4,(off<0?10:Math.abs(off)*18),open),rot=lerp((i-1.5)*3,off<0?-8+off:off*7,open),s=lerp(1,off===0?1.1:(off<0?.86:.9),open);
      e.style.transform=`translate(${x}px,calc(-50% + ${y}px)) rotate(${rot}deg) scale(${s})`;e.style.zIndex=off<0?5+i:10-off;e.style.filter=`brightness(${1-.08*Math.abs(off)*open})`});
    $$('.dots i',sec).forEach((d,i)=>d.classList.toggle('on',i===act));
    const bg=sec.querySelector('.pbg');const vh=innerHeight;const ein=ss(vh*.95,vh*.35,r.top),eout=ss(vh*.05,vh*.6,r.bottom),ev=ein*eout;bg.style.setProperty('--ft',(vh*.24*ss(0,vh*.35,r.top)).toFixed(1)+'px');bg.style.setProperty('--fb',(vh*.24*ss(0,vh*.35,vh-r.bottom)).toFixed(1)+'px');const info=sec.querySelector('.info');const iv=ss(vh*.55,0,r.top)*eout;info.style.opacity=iv;info.style.translate=`0 ${(1-iv)*30}px`;
  })}
function frame(){fans();$('hdr').classList.toggle('solid',scrollY>40);requestAnimationFrame(frame)}
requestAnimationFrame(frame);

/* filtros */
$$('.chips button').forEach(b=>b.addEventListener('click',()=>{$$('.chips button').forEach(x=>x.classList.toggle('on',x===b));const f=b.dataset.f;
  $$('.prod').forEach(s=>{const t=s.dataset.type;s.classList.toggle('hide',!(f==='all'||t===f||t==='both'))});
  goTop($('catalogo'),true)}));

/* visor de páginas */
let imgs=[],title='',ci=0;const lb=$('lb');
function show(){$('lbi').src=imgs[ci];$('lbi').alt=`${title} ${ci+1}`;$('lbc').textContent=`${title} · ${ci+1}/${imgs.length}`}
function openList(list,t,i){imgs=list;title=t;ci=i;show();lb.classList.add('open');$('lbx').focus()}
function open(id,i){const sec=$('p-'+id);if(!sec)return;try{openList(JSON.parse(sec.dataset.imgs),sec.dataset.title||'',i)}catch(e){}}
document.addEventListener('click',e=>{const g=e.target.closest('.pg');if(g)open(g.dataset.p,+g.dataset.i);const z=e.target.closest('[data-zoom]');if(z)open(z.dataset.zoom,0);
  const o=e.target.closest('[data-open]');if(o){const host=o.closest('[data-imgs]');if(host){try{const l=JSON.parse(host.dataset.imgs);let t=host.dataset.title||'';const caps=host.dataset.caps?JSON.parse(host.dataset.caps):null;if(caps)t=caps[+o.dataset.open]||'';openList(l,t,+o.dataset.open)}catch(x){}}}});
document.addEventListener('keydown',e=>{if(e.key==='Enter'&&e.target.classList&&e.target.classList.contains('pg'))open(e.target.dataset.p,+e.target.dataset.i);if(!lb.classList.contains('open'))return;if(e.key==='Escape')lb.classList.remove('open');if(e.key==='ArrowRight')$('lbn').click();if(e.key==='ArrowLeft')$('lbp').click()});
$('lbx').onclick=()=>lb.classList.remove('open');lb.addEventListener('click',e=>{if(e.target===lb)lb.classList.remove('open')});
$('lbn').onclick=()=>{ci=(ci+1)%imgs.length;show()};$('lbp').onclick=()=>{ci=(ci+imgs.length-1)%imgs.length;show()};
/* visores de landings y demos de apps */
$$('[data-viewer]').forEach(vw=>{const q=c=>vw.querySelector(c),tabs=$$('.vtabs button',vw),fr=q('.vfr'),vp=q('.vp');let loaded=false;
  function pick(b){tabs.forEach(x=>x.classList.toggle('on',x===b));const raw=b.dataset.src,src=raw?'demo.php?f='+encodeURIComponent(raw.split('/').pop()):'';let im=[];try{im=JSON.parse(b.dataset.imgs||'[]')}catch(e){}
    q('.vname').textContent=b.dataset.title;q('.vdesc').textContent=b.dataset.desc||'';const ask=q('.vask');ask.href=b.dataset.link;ask.textContent=b.dataset.label;
    const pr=q('.vprice');if(pr){pr.hidden=!b.dataset.price;if(b.dataset.price)pr.querySelector('b').textContent=b.dataset.price}q('.vtags').innerHTML='';(b.dataset.tags||'').split('|').filter(Boolean).forEach(t=>{const s=document.createElement('span');s.textContent=t;q('.vtags').appendChild(s)});
    q('.vurl').textContent=b.dataset.title.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')+'.com';
    const old=fr.querySelector('iframe');if(old)old.remove();const ph=q('.vph');const op=q('.vopen');
    if(src){ph.style.display='none';const f=document.createElement('iframe');f.src=src;f.title='Demo: '+b.dataset.title;f.setAttribute('sandbox','allow-scripts allow-forms allow-popups allow-modals');fr.appendChild(f);op.href=src;op.style.display=''}
    else{ph.style.display='';ph.style.backgroundImage=im[0]?`url('${im[0]}')`:'';ph.dataset.open='0';ph.dataset.imgs=JSON.stringify(im);ph.dataset.title=b.dataset.title;ph.style.cursor=im.length?'zoom-in':'';op.style.display='none'}}
  tabs.forEach(b=>b.addEventListener('click',()=>pick(b)));
  $$('.vdev button',vw).forEach(b=>b.addEventListener('click',()=>{$$('.vdev button',vw).forEach(x=>x.classList.toggle('on',x===b));vp.classList.toggle('mob',b.dataset.dev==='m')}));
  const load=()=>{if(!loaded&&!vw.closest('[hidden]')){loaded=true;pick(tabs[0])}};
  new IntersectionObserver(en=>{if(en[0].isIntersecting)load()},{rootMargin:'300px'}).observe(vw);
  new MutationObserver(load).observe(vw.closest('.panel')||vw,{attributes:true,attributeFilter:['hidden']})});
/* demos de apps (a todo el ancho, como agendas) */
$$('[data-app]').forEach(ap=>{const fr=ap.querySelector('.vfr'),vp=ap.querySelector('.vp'),src=ap.dataset.src;let on=false;
  const load=()=>{if(on||!src||ap.closest('[hidden]'))return;on=true;const ph=ap.querySelector('.vph');if(ph)ph.style.display='none';const f=document.createElement('iframe');f.src='demo.php?f='+encodeURIComponent(src.split('/').pop());f.title='Demo: '+ap.dataset.title;f.setAttribute('sandbox','allow-scripts allow-forms allow-popups allow-modals');fr.appendChild(f)};
  new IntersectionObserver(en=>{if(en[0].isIntersecting)load()},{rootMargin:'400px'}).observe(ap);
  new MutationObserver(load).observe(ap.closest('.panel'),{attributes:true,attributeFilter:['hidden']});
  $$('.vdev button',ap).forEach(b=>b.addEventListener('click',()=>{$$('.vdev button',ap).forEach(x=>x.classList.toggle('on',x===b));vp.classList.toggle('mob',b.dataset.dev==='m')}))});
/* categorías: se cambia el panel en el lugar, sin recorrer la página */
const IDS=['apps','landings','agendas','micro','gratis','proximamente'],tabsEl=$('tabs'),tl=$$('a',tabsEl);
function hdrH(){return $('hdr').offsetHeight||64}
function goTop(el,inner){const base=inner&&el?el.getBoundingClientRect().top+scrollY-tabsEl.offsetHeight-hdrH()-8:$('desk').offsetTop+$('desk').offsetHeight-hdrH()+1;scrollTo({top:Math.max(0,base),behavior:'instant'})}
function activate(id,jump){if(!IDS.includes(id))id='apps';IDS.forEach(k=>{const e=$(k);const on=k===id;if(on&&e.hidden){e.hidden=false;e.classList.remove('enter');void e.offsetWidth;if(!RM)e.classList.add('enter')}else if(!on)e.hidden=true});
  tl.forEach(a=>a.classList.toggle('on',a.getAttribute('href')==='#'+id));
  if(jump)goTop(null)}
document.addEventListener('click',e=>{const a=e.target.closest('a[href^="#"]');if(!a)return;const id=a.getAttribute('href').slice(1);if(!id)return;e.preventDefault();
  if(IDS.includes(id))activate(id,true);else if(id==='top')scrollTo({top:0,behavior:'instant'});else{const t=$(id);if(t)scrollTo({top:t.getBoundingClientRect().top+scrollY-hdrH(),behavior:'instant'})}});
/* “Avisame cuando esté”: guarda el mail y muestra el mensaje sin recargar la página */
document.addEventListener('submit',function(e){var f=e.target.closest&&e.target.closest('form[data-aviso]');if(!f)return;e.preventDefault();
  var m=f.querySelector('.msg'),b=f.querySelector('button'),em=f.querySelector('input[type=email]');
  if(!em.value||!em.checkValidity()){m.textContent='Escribí un mail válido.';m.className='msg err';em.focus();return}
  b.disabled=true;m.textContent='Guardando…';m.className='msg';
  fetch('aviso.php',{method:'POST',body:new FormData(f)}).then(function(r){return r.json()}).then(function(j){
    m.textContent=j.msg;m.className='msg '+(j.ok?'ok':'err');if(j.ok){if(window.ttEvent){var ar=f.closest('[data-title]');ttEvent('Lead',{contents:[{content_id:(f.dataset.aviso||''),content_type:'product',content_name:(ar&&ar.dataset.title)||''}]})}em.value='';b.textContent='¡Anotado!'}else{b.disabled=false}
  }).catch(function(){m.textContent='No pudimos guardar tu mail. Probá de nuevo.';m.className='msg err';b.disabled=false})});
/* siempre empezar arriba: si la URL trae #agendas etc. se elige esa pestaña pero no se salta */
try{history.scrollRestoration='manual'}catch(e){}
if(IDS.includes(location.hash.slice(1)))activate(location.hash.slice(1),false);
if(location.hash){try{history.replaceState(null,'',location.pathname+location.search)}catch(e){}}
scrollTo(0,0);addEventListener('load',()=>setTimeout(()=>scrollTo(0,0),0));addEventListener('pageshow',e=>{if(e.persisted)scrollTo(0,0)});
})();

/* Próximamente: pasar fotos con flechas, en la misma tarjeta */
(function(){
  document.querySelectorAll('.soonp [data-app], .gratis-p [data-app]').forEach(function(card){
    var imgs=[];try{imgs=JSON.parse(card.dataset.imgs||'[]')}catch(e){}
    var ph=card.querySelector('.vph'),dots=card.querySelectorAll('.sdots i');
    var isG=!!card.closest('.gratis-p');
    /* GRATIS: el visor toma la proporción de cada foto (no se estira ni se recorta) */
    function ratio(u){if(!isG)return;var im=new Image();im.onload=function(){if(im.naturalHeight)card.style.setProperty('--ar',(im.naturalWidth/im.naturalHeight).toFixed(4))};im.src=u}
    if(isG&&imgs.length===1)ratio(imgs[0]);
    if(!ph||imgs.length<2)return;
    var i=0;
    function go(n){i=(n+imgs.length)%imgs.length;ph.style.backgroundImage="url('"+imgs[i]+"')";ratio(imgs[i]);if(ph.hasAttribute('data-open'))ph.dataset.open=String(i);dots.forEach(function(d,k){d.classList.toggle('on',k===i)})}
    go(0);
    var pv=card.querySelector('.sprev'),nx=card.querySelector('.snext');
    if(pv)pv.addEventListener('click',function(){go(i-1)});
    if(nx)nx.addEventListener('click',function(){go(i+1)});
    var x0=null;ph.addEventListener('touchstart',function(e){x0=e.touches[0].clientX},{passive:true});
    ph.addEventListener('touchend',function(e){if(x0===null)return;var d=e.changedTouches[0].clientX-x0;if(Math.abs(d)>40)go(i+(d<0?1:-1));x0=null});
  });
})();
