(function(){
'use strict';
const $=id=>document.getElementById(id),$$=(q,r)=>[...(r||document).querySelectorAll(q)];
const cl=(x,a=0,b=1)=>Math.min(b,Math.max(a,x));const ss=(a,b,x)=>{const t=cl((x-a)/(b-a));return t*t*(3-2*t)};const lerp=(a,b,t)=>a+(b-a)*t;
const RM=matchMedia('(prefers-reduced-motion:reduce)').matches;

/* hero: hojas sueltas que se apilan */
const ds=$('dstage');let sheets=[];try{sheets=JSON.parse(ds.dataset.sheets||'[]')}catch(e){}
const R=(s=>()=>(s=(s*16807)%2147483647)/2147483647)(11);
const list=sheets.length?sheets.concat(sheets.slice(0,Math.min(6,sheets.length))).slice(0,24):[];
const cover=ds.dataset.cover;if(cover)list.push(cover);
const SH=list.map((u,i)=>{const e=document.createElement('div');e.className='sheet'+(cover&&i===list.length-1?' cover':'');e.style.backgroundImage=`url('${u}')`;ds.appendChild(e);return {e,x:R(),y:R(),r:(R()-.5)*50,d:R(),c:!!(cover&&i===list.length-1)}});
const cats=$('cats'),rot=$('rot');const words=rot?$$('b',rot):[];let wi=0,wlock=-1;
function setWord(i){if(i===wi)return;wi=i;words.forEach((w,k)=>w.classList.toggle('on',k===i))}
if(words.length&&!RM)setInterval(()=>{if(scrollY<innerHeight*.25)setWord((wi+1)%words.length)},2200);
function hero(){const r=$('desk').getBoundingClientRect();const p=cl(-r.top/(r.height-innerHeight));const W=innerWidth,H=innerHeight,M=W<=860;
  if(SH.length){const sw=SH[0].e.offsetWidth,sh=sw*4/3;
  const S=Math.min(M?1.5:1.55,(H*(M?.56:.74))/sh),n=SH.length;
  SH.forEach((s,i)=>{const k=s.c?ss(.5,.88,p):ss(.05+s.d*.25,.55+s.d*.3,p);
    const sx=M?(-sw*.3+s.x*(W-sw*.4)):(W*.5+s.x*(W*.5-sw*.55)), sy=M?(-sh*.45+s.y*(H*.42-sh*.4)):(70+s.y*(H-sh*.55-70));
    const cx=M?W/2:W*.3, cy=M?H*.1+sh*S/2:H/2;
    const o=s.c?0:((i%5)-2)*3, fr=s.c?0:((i%5)-2)*1.4;
    const tx=cx-sw/2+o, ty=cy-sh/2+(s.c?0:((i%3)-1)*3);
    s.e.style.transform=`translate(${lerp(sx,tx,k)}px,${lerp(sy,ty,k)}px) rotate(${lerp(s.r,fr,k)}deg) scale(${lerp(1,S,k)})`;s.e.style.zIndex=i})}
  const ht=ss(M?.4:.55,M?.62:.85,p);$('htxt').style.opacity=1-ht;$('htxt').style.translate=`0 ${-40*ht}px`;$('htxt').style.pointerEvents=ht>.6?'none':'auto';
  $('stamp').style.opacity=1-ss(0,.1,p);
  if(words.length&&p>.04)setWord(p<.2?0:p<.36?1:2);
  if(cats){const v=ss(M?.78:.58,M?.92:.82,p);cats.style.opacity=v;cats.style.pointerEvents=v>.5?'auto':'none';cats.style.translate=`0 ${(1-v)*40}px`}
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
    const info=sec.querySelector('.info');const iv=ss(innerHeight*.55,0,r.top);info.style.opacity=iv;info.style.translate=`0 ${(1-iv)*30}px`;
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
  function pick(b){tabs.forEach(x=>x.classList.toggle('on',x===b));const src=b.dataset.src;let im=[];try{im=JSON.parse(b.dataset.imgs||'[]')}catch(e){}
    q('.vname').textContent=b.dataset.title;q('.vdesc').textContent=b.dataset.desc||'';const ask=q('.vask');ask.href=b.dataset.link;ask.textContent=b.dataset.label;
    q('.vtags').innerHTML='';(b.dataset.tags||'').split('|').filter(Boolean).forEach(t=>{const s=document.createElement('span');s.textContent=t;q('.vtags').appendChild(s)});
    q('.vurl').textContent=b.dataset.title.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')+'.com';
    const old=fr.querySelector('iframe');if(old)old.remove();const ph=q('.vph');const op=q('.vopen');
    if(src){ph.style.display='none';const f=document.createElement('iframe');f.src=src;f.title='Demo: '+b.dataset.title;f.setAttribute('sandbox','allow-scripts allow-forms allow-popups allow-modals');fr.appendChild(f);op.href=src;op.style.display=''}
    else{ph.style.display='';ph.style.backgroundImage=im[0]?`url('${im[0]}')`:'';ph.dataset.open='0';ph.dataset.imgs=JSON.stringify(im);ph.dataset.title=b.dataset.title;ph.style.cursor=im.length?'zoom-in':'';op.style.display='none'}}
  tabs.forEach(b=>b.addEventListener('click',()=>pick(b)));
  $$('.vdev button',vw).forEach(b=>b.addEventListener('click',()=>{$$('.vdev button',vw).forEach(x=>x.classList.toggle('on',x===b));vp.classList.toggle('mob',b.dataset.dev==='m')}));
  const load=()=>{if(!loaded&&!vw.closest('[hidden]')){loaded=true;pick(tabs[0])}};
  new IntersectionObserver(en=>{if(en[0].isIntersecting)load()},{rootMargin:'300px'}).observe(vw);
  new MutationObserver(load).observe(vw.closest('.panel')||vw,{attributes:true,attributeFilter:['hidden']})});
/* categorías: se cambia el panel en el lugar, sin recorrer la página */
const IDS=['agendas','apps','landings','micro'],tabsEl=$('tabs'),tl=$$('a',tabsEl);
function hdrH(){return $('hdr').offsetHeight||64}
function goTop(el,inner){const base=inner&&el?el.getBoundingClientRect().top+scrollY-tabsEl.offsetHeight-hdrH()-8:$('desk').offsetTop+$('desk').offsetHeight-hdrH()+1;scrollTo({top:Math.max(0,base),behavior:'instant'})}
function activate(id,jump){if(!IDS.includes(id))id='agendas';IDS.forEach(k=>{const e=$(k);const on=k===id;if(on&&e.hidden){e.hidden=false;e.classList.remove('enter');void e.offsetWidth;if(!RM)e.classList.add('enter')}else if(!on)e.hidden=true});
  tl.forEach(a=>a.classList.toggle('on',a.getAttribute('href')==='#'+id));
  if(jump)goTop(null);try{history.replaceState(null,'','#'+id)}catch(e){}}
$$('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const id=a.getAttribute('href').slice(1);if(!id)return;e.preventDefault();
  if(IDS.includes(id))activate(id,true);else if(id==='top')scrollTo({top:0,behavior:'instant'});else{const t=$(id);if(t)scrollTo({top:t.getBoundingClientRect().top+scrollY-hdrH(),behavior:'instant'})}}));
if(IDS.includes(location.hash.slice(1)))activate(location.hash.slice(1),true);
})();
