(function(){
'use strict';
const $=id=>document.getElementById(id),$$=(q,r)=>[...(r||document).querySelectorAll(q)];
const cl=(x,a=0,b=1)=>Math.min(b,Math.max(a,x));const ss=(a,b,x)=>{const t=cl((x-a)/(b-a));return t*t*(3-2*t)};const lerp=(a,b,t)=>a+(b-a)*t;
const RM=matchMedia('(prefers-reduced-motion:reduce)').matches;

/* hero: hojas sueltas que se apilan */
const ds=$('dstage');let sheets=[];try{sheets=JSON.parse(ds.dataset.sheets||'[]')}catch(e){}
const R=(s=>()=>(s=(s*16807)%2147483647)/2147483647)(11);
const list=sheets.length?sheets.concat(sheets.slice(0,Math.min(6,sheets.length))).slice(0,24):[];
const SH=list.map(u=>{const e=document.createElement('div');e.className='sheet';e.style.backgroundImage=`url('${u}')`;ds.appendChild(e);return {e,x:R(),y:R(),r:(R()-.5)*50,d:R()}});
const cnt=$('cnt'),pn=$('pn'),total=cnt?+cnt.dataset.total:0;
function hero(){const r=$('desk').getBoundingClientRect();const p=cl(-r.top/(r.height-innerHeight));const W=innerWidth,H=innerHeight,M=W<=860;
  if(SH.length){const sw=SH[0].e.offsetWidth,sh=sw*4/3;
  SH.forEach((s,i)=>{const k=ss(.05+s.d*.25,.55+s.d*.3,p);
    const sx=M?(-sw*.3+s.x*(W-sw*.4)):(W*.5+s.x*(W*.5-sw*.55)), sy=M?(-sh*.45+s.y*(H*.42-sh*.4)):(70+s.y*(H-sh*.55-70));
    const tx=M?W/2-sw/2:W*.62-sw/2, ty=M?H*.12:H/2-sh/2+(i-SH.length/2)*-.6;
    s.e.style.transform=`translate(${lerp(sx,tx,k)}px,${lerp(sy,ty,k)}px) rotate(${lerp(s.r,(i%2?1:-1)*(i%5)*.8,k)}deg)`;s.e.style.zIndex=i})}
  $('htxt').style.opacity=1-ss(.55,.85,p);$('htxt').style.translate=`0 ${-40*ss(.55,.85,p)}px`;
  $('stamp').style.opacity=1-ss(0,.1,p);
  if(cnt){cnt.style.opacity=ss(.55,.8,p)*(1-ss(.93,1,p));pn.textContent=Math.round(total*ss(.55,.85,p)).toLocaleString('es-AR')}
}
/* productos: las páginas se reparten con el scroll */
function fans(){const M=innerWidth<=860;
  $$('.prod').forEach(sec=>{if(sec.classList.contains('hide'))return;const r=sec.getBoundingClientRect();if(r.bottom<-50||r.top>innerHeight*1.1)return;
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
  const t=$('catalogo');scrollTo({top:t.getBoundingClientRect().top+scrollY-10,behavior:RM?'auto':'smooth'})}));

/* visor de páginas */
let imgs=[],title='',ci=0;const lb=$('lb');
function show(){$('lbi').src=imgs[ci];$('lbi').alt=`Página ${ci+1} de ${title}`;$('lbc').textContent=`${title} · ${ci+1}/${imgs.length}`}
function open(id,i){const sec=$('p-'+id);if(!sec)return;try{imgs=JSON.parse(sec.dataset.imgs)}catch(e){return}title=sec.dataset.title||'';ci=i;show();lb.classList.add('open');$('lbx').focus()}
document.addEventListener('click',e=>{const g=e.target.closest('.pg');if(g)open(g.dataset.p,+g.dataset.i);const z=e.target.closest('[data-zoom]');if(z)open(z.dataset.zoom,0)});
document.addEventListener('keydown',e=>{if(e.key==='Enter'&&e.target.classList&&e.target.classList.contains('pg'))open(e.target.dataset.p,+e.target.dataset.i);if(!lb.classList.contains('open'))return;if(e.key==='Escape')lb.classList.remove('open');if(e.key==='ArrowRight')$('lbn').click();if(e.key==='ArrowLeft')$('lbp').click()});
$('lbx').onclick=()=>lb.classList.remove('open');lb.addEventListener('click',e=>{if(e.target===lb)lb.classList.remove('open')});
$('lbn').onclick=()=>{ci=(ci+1)%imgs.length;show()};$('lbp').onclick=()=>{ci=(ci+imgs.length-1)%imgs.length;show()};
$$('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const id=a.getAttribute('href');if(id==='#'||id==='#top')return;const t=document.querySelector(id);if(!t)return;e.preventDefault();scrollTo({top:t.getBoundingClientRect().top+scrollY,behavior:RM?'auto':'smooth'})}));
})();
