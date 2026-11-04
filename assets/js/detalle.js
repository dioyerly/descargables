(function(){'use strict';
const $=id=>document.getElementById(id);const imgs=window.__IMGS||[],title=window.__TITLE||'';let ci=0;const lb=$('lb');
function setMain(i){ci=i;$('gimg').src=imgs[i];$('gimg').alt=title+' — página '+(i+1);document.querySelectorAll('.thumbs .th').forEach((t,k)=>t.classList.toggle('on',k===i))}
function show(){$('lbi').src=imgs[ci];$('lbi').alt='Página '+(ci+1)+' de '+title;$('lbc').textContent=title+' · '+(ci+1)+'/'+imgs.length}
document.querySelectorAll('.thumbs .th').forEach(t=>t.addEventListener('click',()=>setMain(+t.dataset.i)));
$('gmain').addEventListener('click',()=>{show();lb.classList.add('open');$('lbx').focus()});
$('lbx').onclick=()=>lb.classList.remove('open');lb.addEventListener('click',e=>{if(e.target===lb)lb.classList.remove('open')});
$('lbn').onclick=()=>{ci=(ci+1)%imgs.length;show();setMain(ci)};$('lbp').onclick=()=>{ci=(ci+imgs.length-1)%imgs.length;show();setMain(ci)};
document.addEventListener('keydown',e=>{if(!lb.classList.contains('open'))return;if(e.key==='Escape')lb.classList.remove('open');if(e.key==='ArrowRight')$('lbn').click();if(e.key==='ArrowLeft')$('lbp').click()});
if(imgs.length<2){$('lbn').style.display='none';$('lbp').style.display='none'}
})();
