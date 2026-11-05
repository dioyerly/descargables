(function(){
'use strict';
const $=id=>document.getElementById(id),$$=(q,r)=>[...(r||document).querySelectorAll(q)];
const cl=(x,a=0,b=1)=>Math.min(b,Math.max(a,x));const ss=(a,b,x)=>{const t=cl((x-a)/(b-a));return t*t*(3-2*t)};const lerp=(a,b,t)=>a+(b-a)*t;
const RM=matchMedia('(prefers-reduced-motion:reduce)').matches;

/* hero: piezas sueltas que vuelan a su dispositivo (tablet, notebook, navegador, celular) */
const ds=$('dstage');let SETS={};try{SETS=JSON.parse(ds.dataset.sets||'{}')}catch(e){}
const CAT=['agendas','apps','landings','micro'],SCAT={agendas:190,apps:280,landings:100,micro:260};
const R=(s=>()=>(s=(s*16807)%2147483647)/2147483647)(11);
const devs=$$('.dev',ds),scrs=devs.map(d=>d.querySelector('.scr'));
const bars=n=>Array.from({length:n},(_,i)=>`<i style="height:${30+((i*37)%60)}%"></i>`).join('');
const MOCK={
 agendas:j=>`<div class="mk mk-ag v${j}"><b>${j?'Semana':'Enero'}</b><div class="cal">${'<i></i>'.repeat(28)}</div></div>`,
 apps:j=>`<div class="mk mk-app v${j}"><i class="sb"></i><div class="mm"><b>${j?'Ventas':'Panel'}</b><div class="kp"><i></i><i></i><i></i></div><div class="ch">${bars(9)}</div></div></div>`,
 landings:j=>`<div class="mk mk-land v${j}"><i class="nv"></i><div class="hx"><b>${j?'Tu estudio,<br>online.':'Café de<br>barrio.'}</b><i class="bt"></i></div><i class="ph"></i></div>`,
 micro:j=>`<div class="mk mk-mic v${j}"><b>${j?'Pedidos':'Turnos'}</b>${'<p><i></i><span></span></p>'.repeat(4)}<i class="fab"></i></div>`};
const PS=[];const layer=$('pieces');
const SEEN=new Set();CAT.forEach((c,ci)=>{let l=(SETS[c]||[]).map(x=>typeof x==='string'?{u:x,g:x}:x).filter(x=>!SEEN.has(x.u)&&SEEN.add(x.u));
  const vv=c==='landings'?ds.dataset.vland:c==='micro'?ds.dataset.vmicro:'';if(vv)l=l.concat([{u:'vid:'+vv,g:'vid-'+c}]);
  const mock=!l.length;if(mock)l=[{m:0,g:c+'m0'},{m:1,g:c+'m1'}];
  l.forEach((it,j)=>{const e=document.createElement('div');e.className='piece p-'+c+(mock?' mock':'');const u=it.u||'';
    if(mock)e.innerHTML=MOCK[c](j);else if(u.startsWith('vid:')){e.classList.add('vid');e.innerHTML=`<video src="${u.slice(4)}" poster="${u.slice(4).replace('.mp4','.jpg')}" muted loop playsinline autoplay preload="auto"></video>`}else e.style.backgroundImage=`url('${u}')`;
    layer.appendChild(e);PS.push({e,c,ci,j,n:l.length,g:c+':'+it.g,r:0,d:R(),w:0,h:0,cx:0,cy:0})})});
PS.forEach((s,i)=>s.z=i);const SF=Math.min(1,Math.sqrt(15/Math.max(1,PS.length)));
/* reparto ordenado-desordenado: grilla con variación, sin dos piezas del mismo producto juntas */
let LK='';
function layout(W,H,M){const k=W+'x'+H;if(k===LK)return;LK=k;const Rl=(s=>()=>(s=(s*16807)%2147483647)/2147483647)(7);
  const x0=M?10:W*.5,x1=M?W-10:W*.95,y0=M?90:H*.17,y1=M?H*.32:H*.8;const N=PS.length,a=(x1-x0)/(y1-y0);
  let cols=Math.max(2,Math.round(Math.sqrt(N*a))),rows=Math.ceil(N/cols);const cw=(x1-x0)/cols,rh=(y1-y0)/rows;
  const groups={};PS.forEach(s=>(groups[s.g]=groups[s.g]||[]).push(s));const gk=Object.keys(groups).sort(()=>Rl()-.5);
  const q=[];let more=true;while(more){more=false;gk.forEach(g=>{if(groups[g].length){q.push(groups[g].shift());more=true}})}
  const cells=[];for(let r=0;r<rows;r++)for(let c=0;c<cols;c++)cells.push([r,c]);
  while(cells.length>N)cells.splice(Math.floor(Rl()*cells.length),1);
  const at={};cells.forEach(([r,c])=>{const nb=[[r,c-1],[r-1,c],[r-1,c-1],[r-1,c+1],[r,c-2]].map(([a,b])=>at[a+','+b]).filter(Boolean);
    let i=q.findIndex(s=>!nb.includes(s.g));if(i<0)i=0;const s=q.splice(i,1)[0];at[r+','+c]=s.g;
    const off=(r%2?.25:-.25)*cw;s.cx=x0+(c+.5)*cw+off+(Rl()-.5)*cw*.3;s.cy=y0+(r+.5)*rh+(Rl()-.5)*rh*.3;s.r=(Rl()-.5)*28;s.z=Math.floor(Rl()*N)});
  GRP=groups}
let GRP={};
const rot=$('rot'),words=rot?$$('b',rot):[],w2=$$('#rot2 b');let wi=0,w2i=0;
function setWord(i){if(i===wi)return;wi=i;words.forEach((w,k)=>w.classList.toggle('on',k===i))}
function setW2(i){if(i===w2i)return;w2i=i;w2.forEach((w,k)=>w.classList.toggle('on',k===i))}
if(words.length&&!RM)setInterval(()=>{if(scrollY<innerHeight*.15)setWord((wi+1)%words.length)},2200);
const GL=['106,79,196','47,143,131','224,113,79','201,150,43'];
let cyc=0;setInterval(()=>cyc++,2600);
function hero(){const r=$('desk').getBoundingClientRect();if(r.bottom<0)return;
  const p=RM?1:cl(-r.top/(r.height-innerHeight));const W=innerWidth,H=innerHeight,M=W<=860;const sr=ds.getBoundingClientRect();
  const st=ci=>.2+ci*.1;
  const ht=ss(.06,.16,p);$('htxt').style.opacity=1-ht;$('htxt').style.translate=`0 ${-40*ht}px`;$('htxt').style.pointerEvents=ht>.5?'none':'auto';
  const dt=ss(.15,.27,p);$('dtitle').style.opacity=dt;$('dtitle').style.translate=`0 ${(1-dt)*30}px`;
  $('stamp').style.opacity=1-ss(0,.08,p);
  const act=p<.2?-1:p>=.66?4:Math.min(3,Math.floor((p-.2)/.1));
  setW2(act<0?0:act);
  devs.forEach((d,i)=>{const v=ss(.17+i*.03,.27+i*.03,p);d.style.opacity=v;d.style.transform=`translateY(${(1-v)*50}px) scale(${.92+.08*v})`;
    d.classList.toggle('lit',act===i);d.style.pointerEvents=v>.6?'auto':'none';
    const cv=ss(st(i)+.16,st(i)+.24,p);const cap=d.querySelector('.cap');cap.style.opacity=cv;cap.style.translate=`0 ${(1-cv)*14}px`});
  const g=$('glowbg');const gi=act<0?0:act>3?0:act;g.style.setProperty('--g',GL[gi]);g.style.opacity=act<0||act>3?.5:1;
  const rects=scrs.map(e=>{const q=e.getBoundingClientRect();return {x:q.left-sr.left,y:q.top-sr.top,w:q.width,h:q.height}});
  layout(W,H,M);PS.forEach(s=>{const t=rects[s.ci];if(!t.w)return;
    if(Math.abs(s.w-t.w)>.5||Math.abs(s.h-t.h)>.5){s.w=t.w;s.h=t.h;s.e.style.width=t.w+'px';s.e.style.height=t.h+'px'}
    const a=st(s.ci)+(s.j/s.n)*.07+s.d*.02;const k=ss(a,a+.13,p);
    const s0=(SCAT[s.c]*(M?.46:1)*SF)/t.w,dw=t.w*s0,dh=t.h*s0;
    const x0=cl(s.cx,dw*.3,W-dw*.3),y0=cl(s.cy,dh*.35+50,H-dh*.45),x1=t.x+t.w/2,y1=t.y+t.h/2;
    const x=lerp(x0,x1,k),y=lerp(y0,y1,k)-Math.sin(Math.PI*k)*(M?40:90),sc=lerp(s0,1,k),rr=lerp(s.r,0,k);
    s.e.style.transform=`translate(${x-t.w/2}px,${y-t.h/2}px) rotate(${rr}deg) scale(${sc})`;
    const landed=k>.985;s.e.classList.toggle('in',landed);s.e.style.zIndex=landed?100+s.ci*10+s.j:(k>0?60+s.z:s.z);
    const last=s.n-1,cur=p>.72?(last+cyc)%s.n:last;s.e.style.opacity=(!landed||s.j===cur||p<=.72)?1:0;
    if(landed&&p>.72)s.e.style.zIndex=s.j===cur?150:100+s.ci*10});
  ds.classList.toggle('done',p>.66);
}
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
function frame(){hero();fans();$('hdr').classList.toggle('solid',scrollY>40);requestAnimationFrame(frame)}
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
const IDS=['apps','landings','agendas','micro','proximamente'],tabsEl=$('tabs'),tl=$$('a',tabsEl);
function hdrH(){return $('hdr').offsetHeight||64}
function goTop(el,inner){const base=inner&&el?el.getBoundingClientRect().top+scrollY-tabsEl.offsetHeight-hdrH()-8:$('desk').offsetTop+$('desk').offsetHeight-hdrH()+1;scrollTo({top:Math.max(0,base),behavior:'instant'})}
function activate(id,jump){if(!IDS.includes(id))id='apps';IDS.forEach(k=>{const e=$(k);const on=k===id;if(on&&e.hidden){e.hidden=false;e.classList.remove('enter');void e.offsetWidth;if(!RM)e.classList.add('enter')}else if(!on)e.hidden=true});
  tl.forEach(a=>a.classList.toggle('on',a.getAttribute('href')==='#'+id));
  if(jump)goTop(null)}
document.addEventListener('click',e=>{const a=e.target.closest('a[href^="#"]');if(!a)return;const id=a.getAttribute('href').slice(1);if(!id)return;e.preventDefault();
  if(IDS.includes(id))activate(id,true);else if(id==='top')scrollTo({top:0,behavior:'instant'});else{const t=$(id);if(t)scrollTo({top:t.getBoundingClientRect().top+scrollY-hdrH(),behavior:'instant'})}});
/* Próximamente: deslizar las fotos de cada tarjeta */
document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.soon-media .sn');if(!b)return;var m=b.closest('.soon-media'),t=m.querySelector('.soon-track'),w=t.clientWidth,dir=b.classList.contains('sn-n')?1:-1;t.scrollBy({left:dir*w,behavior:'smooth'})});
document.addEventListener('scroll',function(e){var t=e.target;if(!t||!t.classList||!t.classList.contains('soon-track'))return;var m=t.closest('.soon-media'),c=m&&m.querySelector('.sn-count b');if(c)c.textContent=Math.round(t.scrollLeft/Math.max(1,t.clientWidth))+1},true);
/* “Avisame cuando esté”: guarda el mail y muestra el mensaje sin recargar la página */
document.addEventListener('submit',function(e){var f=e.target.closest&&e.target.closest('form[data-aviso]');if(!f)return;e.preventDefault();
  var m=f.querySelector('.msg'),b=f.querySelector('button'),em=f.querySelector('input[type=email]');
  if(!em.value||!em.checkValidity()){m.textContent='Escribí un mail válido.';m.className='msg err';em.focus();return}
  b.disabled=true;m.textContent='Guardando…';m.className='msg';
  fetch('aviso.php',{method:'POST',body:new FormData(f)}).then(function(r){return r.json()}).then(function(j){
    m.textContent=j.msg;m.className='msg '+(j.ok?'ok':'err');if(j.ok){em.value='';b.textContent='¡Anotado!'}else{b.disabled=false}
  }).catch(function(){m.textContent='No pudimos guardar tu mail. Probá de nuevo.';m.className='msg err';b.disabled=false})});
/* siempre empezar arriba: si la URL trae #agendas etc. se elige esa pestaña pero no se salta */
try{history.scrollRestoration='manual'}catch(e){}
if(IDS.includes(location.hash.slice(1)))activate(location.hash.slice(1),false);
if(location.hash){try{history.replaceState(null,'',location.pathname+location.search)}catch(e){}}
scrollTo(0,0);addEventListener('load',()=>setTimeout(()=>scrollTo(0,0),0));addEventListener('pageshow',e=>{if(e.persisted)scrollTo(0,0)});
})();
