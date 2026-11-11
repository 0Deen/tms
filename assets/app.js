document.addEventListener('DOMContentLoaded',()=>{
  const nav=document.getElementById('topNav'),toggle=document.getElementById('menuToggle');
  if(toggle&&nav)toggle.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));});
  const more=document.querySelector('.nav-more'),moreButton=document.querySelector('.nav-more-trigger');
  if(more&&moreButton)moreButton.addEventListener('click',()=>{const open=more.classList.toggle('open');moreButton.setAttribute('aria-expanded',String(open));});
  document.addEventListener('click',e=>{if(more&&!more.contains(e.target)){more.classList.remove('open');if(moreButton)moreButton.setAttribute('aria-expanded','false');}});
  document.querySelectorAll('form[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm))e.preventDefault();}));
  document.querySelectorAll('[data-chart]').forEach(el=>{
    if(!window.Chart)return;let cfg;try{cfg=JSON.parse(el.dataset.chart)}catch(e){return;}
    const isBar=cfg.type==='bar';
    new Chart(el,{type:cfg.type||'line',data:{labels:cfg.labels,datasets:cfg.datasets.map(d=>({...d,borderWidth:2,tension:.38,fill:d.fill??true,pointRadius:0,pointHoverRadius:4,borderCapStyle:'round',...(isBar?{borderRadius:7,barPercentage:.58,categoryPercentage:.65}:{})}))},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:!!cfg.legend,labels:{usePointStyle:true,boxWidth:7,font:{family:'DM Sans',size:9},color:'#8f8882'}},tooltip:{backgroundColor:'#29262b',padding:10,titleFont:{family:'DM Sans',size:9},bodyFont:{family:'DM Sans',size:9},displayColors:false,cornerRadius:8}},scales:{x:{grid:{display:false},border:{display:false},ticks:{font:{family:'DM Sans',size:8},color:'#a69e98'}},y:{beginAtZero:true,grid:{color:'rgba(80,70,60,.07)',drawTicks:false},border:{display:false,dash:[3,4]},ticks:{font:{family:'DM Sans',size:8},color:'#a69e98',padding:8}}}}});
  });
});
