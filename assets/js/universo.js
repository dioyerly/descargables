/* Inicio: el núcleo (imagen nítida, sin deformar) con energía en los colores de la marca
   y las portadas de los productos orbitando, todas del mismo tamaño y más chicas que el núcleo. */
(function(){
'use strict';
const stage=document.getElementById('uni');if(!stage)return;
let CARDS=[];try{CARDS=JSON.parse(stage.dataset.cards||'[]')}catch(e){}
const bc=stage.querySelector('.uni-back'),fc=stage.querySelector('.uni-front'),core=stage.querySelector('.uni-core'),b=bc.getContext('2d'),f=fc.getContext('2d');
const RM=matchMedia('(prefers-reduced-motion: reduce)').matches;
const KC={agenda:'#8f74ff',app:'#2f8f83',landing:'#ff8a63',micro:'#e8b54a'},HREF={agenda:'#agendas',app:'#apps',landing:'#landings',micro:'#micro'};
const RGB={v:[106,79,196],t:[47,143,131],c:[224,113,79],g:[201,150,43],w:[140,110,230]},PAL=[RGB.v,RGB.v,RGB.t,RGB.c,RGB.g,RGB.w];
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const cards=CARDS.map((c,i)=>{const e=document.createElement('a');e.className='uni-card';e.href=HREF[c.k]||'#agendas';e.style.setProperty('--c',KC[c.k]||KC.agenda);
  e.setAttribute('aria-label',c.t||c.l);e.innerHTML='<div class="ph"><img alt="" loading="lazy" src="'+esc(c.u)+'"></div><span><i></i>'+esc(c.l)+'</span>';
  stage.appendChild(e);return{e,ph:i*Math.PI*2/Math.max(1,CARDS.length),x:0,y:0,near:0,h:0}});
let W=0,H=0,cx=0,cy=0,R=0,cw=0,paused=false,t=0,orb=0,last=performance.now(),dt=0,waves=[],nextWave=0;
cards.forEach(c=>{c.e.addEventListener('mouseenter',()=>paused=true);c.e.addEventListener('mouseleave',()=>paused=false);c.e.addEventListener('focus',()=>paused=true);c.e.addEventListener('blur',()=>paused=false)});
const rnd=(a,z)=>a+Math.random()*(z-a);
const dust=Array.from({length:240},()=>({r:rnd(.5,1),a:rnd(0,7),s:rnd(.4,1.5),tw:rnd(0,7),col:PAL[(Math.random()*PAL.length)|0]}));
const spawn=any=>({r:any?rnd(.35,1):rnd(.9,1.05),a:rnd(0,7),v:rnd(.05,.16),col:PAL[(Math.random()*5)|0],w:rnd(.6,1.8)});
const flow=Array.from({length:150},()=>spawn(true));
function size(){const r=stage.getBoundingClientRect();W=r.width;H=r.height;const D=Math.min(2,devicePixelRatio||1);
  [bc,fc].forEach(c=>{c.width=Math.round(W*D);c.height=Math.round(H*D)});b.setTransform(D,0,0,D,0,0);f.setTransform(D,0,0,D,0,0);
  cx=W/2;cy=H/2;R=Math.min(W,H)*.245;core.style.width=(R*2/.56)+'px';cw=Math.round(R*2*.26);
  cards.forEach(c=>{c.e.style.width=cw+'px';c.h=c.e.offsetHeight})}
size();addEventListener('resize',size);if(window.ResizeObserver)new ResizeObserver(size).observe(stage);
function back(){const P=Math.min(W,H)*.5;b.clearRect(0,0,W,H);b.lineCap='round';
  flow.forEach((p,i)=>{const r0=p.r*P,a0=p.a;p.r-=p.v*dt*(1.4-p.r);p.a+=dt*(.45+(1-p.r)*2);if(p.r<R/P*1.02){flow[i]=spawn(false);return}
    const r1=p.r*P,x0=cx+Math.cos(a0)*r0,y0=cy+Math.sin(a0)*r0*.62,x1=cx+Math.cos(p.a)*r1,y1=cy+Math.sin(p.a)*r1*.62,al=Math.min(1,(1-p.r)*2.2)*Math.min(1,(p.r*P-R)/(R*.4))*.55;
    if(al<=0)return;b.strokeStyle='rgba('+p.col+','+al.toFixed(2)+')';b.lineWidth=p.w;b.beginPath();b.moveTo(x0,y0);b.lineTo(x1,y1);b.stroke()});
  dust.forEach(s=>{const rr=s.r*P*.98,a=s.a+t*.03,x=cx+Math.cos(a)*rr,y=cy+Math.sin(a)*rr*.62,al=(.15+.45*Math.abs(Math.sin(t*1.1+s.tw)))*(1.1-s.r);
    b.fillStyle='rgba('+s.col+','+al.toFixed(2)+')';b.beginPath();b.arc(x,y,s.s*.9,0,7);b.fill()});
  if(t>nextWave){waves.push(0);nextWave=t+4}
  waves=waves.filter(v=>v<1);waves.forEach((v,i)=>{const rr=R*1.05+v*(P*.95-R);b.strokeStyle='rgba(106,79,196,'+((1-v)*.28).toFixed(2)+')';b.lineWidth=1.2;b.beginPath();b.ellipse(cx,cy,rr,rr*.62,0,0,7);b.stroke();waves[i]=v+dt*.28})}
const tilt=-.18;
function place(){const rx=Math.min(W,H)*.46,ry=rx*.34,ct=Math.cos(tilt),st=Math.sin(tilt);
  cards.forEach(c=>{const a=orb+c.ph,x=Math.cos(a)*rx,y=Math.sin(a)*ry,depth=Math.sin(a),near=(depth+1)/2;c.near=near;
    c.x=cx+x*ct-y*st;c.y=cy+x*st+y*ct;const sc=.62+near*.38;
    c.e.style.transform='translate3d('+(c.x-cw/2).toFixed(1)+'px,'+(c.y-c.h/2).toFixed(1)+'px,0) scale('+sc.toFixed(3)+')';
    c.e.style.opacity=(.55+near*.45).toFixed(2);c.e.style.filter='blur('+((1-near)*1.2).toFixed(1)+'px)';c.e.style.zIndex=depth>0?6:2})}
function front(){const rx=Math.min(W,H)*.46,ry=rx*.34;f.clearRect(0,0,W,H);
  f.save();f.translate(cx,cy);f.rotate(tilt);const gr=f.createLinearGradient(-rx,0,rx,0);gr.addColorStop(0,'rgba(106,79,196,0)');gr.addColorStop(.5,'rgba(106,79,196,.35)');gr.addColorStop(1,'rgba(106,79,196,0)');
  f.strokeStyle=gr;f.lineWidth=1.2;f.beginPath();f.ellipse(0,0,rx,ry,0,0,Math.PI);f.stroke();f.restore();
  cards.forEach((c,i)=>{if(c.near<.55)return;const k=(c.near-.55)/.45,dx=c.x-cx,dy=c.y-cy,L=Math.hypot(dx,dy)||1,ux=dx/L,uy=dy/L,sx=cx+ux*R*1.02,sy=cy+uy*R*1.02;
    const g=f.createLinearGradient(sx,sy,c.x,c.y);g.addColorStop(0,'rgba(106,79,196,'+(.45*k).toFixed(2)+')');g.addColorStop(1,'rgba(106,79,196,0)');f.strokeStyle=g;f.lineWidth=1.1;f.beginPath();f.moveTo(sx,sy);f.lineTo(c.x,c.y);f.stroke();
    for(let j=0;j<2;j++){const p=((t*.55+i*.37+j*.5)%1),px=sx+(c.x-sx)*p,py=sy+(c.y-sy)*p,rg=f.createRadialGradient(px,py,0,px,py,6);
      rg.addColorStop(0,'rgba(201,150,43,'+(k*(1-p)).toFixed(2)+')');rg.addColorStop(1,'rgba(201,150,43,0)');f.fillStyle=rg;f.beginPath();f.arc(px,py,6,0,7);f.fill()}})}
let vis=true;if(window.IntersectionObserver)new IntersectionObserver(e=>{vis=e[0].isIntersecting}).observe(stage);
function frame(now){dt=Math.min((now-last)/1000,.05);last=now;
  if(vis&&!document.hidden){if(!RM){t+=dt;if(!paused)orb+=dt*(Math.PI*2/40)}back();place();front()}
  requestAnimationFrame(frame)}
requestAnimationFrame(frame);
})();
