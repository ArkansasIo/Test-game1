const KEY='rmg-save-v1';
const base={cycle:1,population:10,housing:20,credits:500,food:250,wood:250,metal:100,energy:50,science:0,land:1,buildings:{farm:0,sawmill:0,mine:0,power:0,lab:0,house:0},research:{agriculture:0,metallurgy:0,energy:0,automation:0}};
let state=load();
const defs={
 farm:{name:'Hydroponic Farm',cost:{wood:80,metal:20,credits:60},desc:'+35 food/cycle',apply:s=>s.food+=35},
 sawmill:{name:'Lumber Mill',cost:{wood:120,metal:30,credits:80},desc:'+28 wood/cycle',apply:s=>s.wood+=28},
 mine:{name:'Metal Mine',cost:{wood:100,metal:40,credits:100},desc:'+20 metal/cycle',apply:s=>s.metal+=20},
 power:{name:'Power Plant',cost:{wood:90,metal:70,credits:140},desc:'+15 energy/cycle',apply:s=>s.energy+=15},
 lab:{name:'Research Lab',cost:{wood:150,metal:80,credits:180},desc:'+12 science/cycle',apply:s=>s.science+=12},
 house:{name:'Habitat Block',cost:{wood:100,metal:50,credits:100},desc:'+10 housing',apply:s=>s.housing+=10}
};
const research={agriculture:{name:'Advanced Agriculture',cost:100,desc:'Food production +25% per level'},metallurgy:{name:'Metallurgy',cost:120,desc:'Metal production +25% per level'},energy:{name:'Energy Systems',cost:140,desc:'Energy production +25% per level'},automation:{name:'Automation',cost:200,desc:'All building output +10% per level'}};
const expansions=[{name:'Outpost',cost:800,land:1,desc:'Claim a new sector and increase population capacity.'},{name:'Trade Hub',cost:1800,land:2,desc:'Adds 120 credits every cycle.'},{name:'Orbital Station',cost:4000,land:3,desc:'Adds 40 science and 25 energy every cycle.'}];
function load(){try{return {...base,...JSON.parse(localStorage.getItem(KEY))}}catch{return structuredClone(base)}}
function save(){localStorage.setItem(KEY,JSON.stringify(state))}
function fmt(n){return Math.floor(n).toLocaleString()}
function can(cost){return Object.entries(cost).every(([k,v])=>state[k]>=v)}
function pay(cost){Object.entries(cost).forEach(([k,v])=>state[k]-=v)}
function costText(c){return Object.entries(c).map(([k,v])=>`${fmt(v)} ${k}`).join(' · ')}
function tick(){
 state.cycle++; let mult=1+state.research.automation*.1;
 const ag=1+state.research.agriculture*.25, me=1+state.research.metallurgy*.25, en=1+state.research.energy*.25;
 state.food=Math.max(0,state.food+(18+state.buildings.farm*35)*ag*mult-state.population*1.2);
 state.wood+=12+state.buildings.sawmill*28*mult;
 state.metal+=6+state.buildings.mine*20*me*mult;
 state.energy+=state.buildings.power*15*en*mult- state.buildings.lab*2;
 state.science+=state.buildings.lab*12*mult;
 state.credits+=30+state.land*10+(state.land>=2?120:0);
 if(state.land>=3) {state.science+=40;state.energy+=25}
 if(state.food>50 && state.population<state.housing) state.population++;
 save();render();
}
function build(id){const d=defs[id];if(!can(d.cost))return;pay(d.cost);state.buildings[id]++;save();render()}
function researchBuy(id){const d=research[id],level=state.research[id],cost=Math.floor(d.cost*Math.pow(1.8,level));if(state.science<cost)return;state.science-=cost;state.research[id]++;save();render()}
function expand(i){const d=expansions[i],cost=d.cost*(state.land);if(state.credits<cost)return;state.credits-=cost;state.land+=d.land;state.housing+=20*d.land;save();render()}
function render(){
 const res=[['credits','Credits'],['food','Food'],['wood','Wood'],['metal','Metal'],['energy','Energy'],['science','Science']];
 document.querySelector('#resources').innerHTML=res.map(([k,n])=>`<div class="resource"><span>${n}</span><b>${fmt(state[k])}</b></div>`).join('');
 document.querySelector('#cycle').textContent=state.cycle;document.querySelector('#population').textContent=state.population;document.querySelector('#popCard').textContent=state.population;document.querySelector('#housing').textContent=state.housing;document.querySelector('#popbar').style.width=Math.min(100,state.population/state.housing*100)+'%';
 const income=30+state.land*10+(state.land>=2?120:0);document.querySelector('#income').textContent=`+${income} credits / cycle`;
 document.querySelector('#productionList').innerHTML=Object.entries(defs).map(([id,d])=>`<article class="action"><h3>${d.name}</h3><p>${d.desc}<br>Owned: ${state.buildings[id]}</p><small>${costText(d.cost)}</small><button onclick="build('${id}')" ${can(d.cost)?'':'disabled'}>Construct</button></article>`).join('');
 document.querySelector('#researchList').innerHTML=Object.entries(research).map(([id,d])=>{let c=Math.floor(d.cost*Math.pow(1.8,state.research[id]));return `<article class="action"><h3>${d.name}</h3><p>${d.desc}<br>Level: ${state.research[id]}</p><small>${fmt(c)} science</small><button onclick="researchBuy('${id}')" ${state.science>=c?'':'disabled'}>Research</button></article>`}).join('');
 document.querySelector('#expansionList').innerHTML=expansions.map((d,i)=>{let c=d.cost*state.land;return `<article class="action"><h3>${d.name}</h3><p>${d.desc}<br>Sector level: ${state.land}</p><small>${fmt(c)} credits</small><button onclick="expand(${i})" ${state.credits>=c?'':'disabled'}>Expand</button></article>`}).join('');
 document.querySelector('#objectives').innerHTML=[['Build 3 farms',state.buildings.farm>=3],['Reach 25 population',state.population>=25],['Research 3 technologies',Object.values(state.research).reduce((a,b)=>a+b,0)>=3],['Claim sector 2',state.land>=2]].map(([x,ok])=>`<li class="${ok?'good':''}">${ok?'✓':'○'} ${x}</li>`).join('');
}
document.querySelectorAll('.tab').forEach(b=>b.onclick=()=>{document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.panel').forEach(x=>x.classList.remove('active'));b.classList.add('active');document.getElementById(b.dataset.tab).classList.add('active')});
document.querySelector('#reset').onclick=()=>{if(confirm('Reset your colony?')){state=structuredClone(base);save();render()}};
render();setInterval(tick,10000);
