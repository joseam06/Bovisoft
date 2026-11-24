import {localDate,addDays} from './module-views';
import {alerts} from './alerts';

function chart(canvas,labels,values,type){
    if(!canvas||!window.Chart)return;
    const old=Chart.getChart(canvas);
    if(old){old.data.labels=labels;old.data.datasets[0].data=values;old.update('none');return;}
    const colors=['rgba(220,38,38,.8)','rgba(59,130,246,.8)','rgba(147,51,234,.8)','rgba(234,179,8,.8)'];
    const background=type==='doughnut'?colors:context=>{const gradient=context.chart.ctx.createLinearGradient(0,0,0,400);gradient.addColorStop(0,canvas.id==='chartProduccion'?'#9333ea':'#dc2626');gradient.addColorStop(1,canvas.id==='chartProduccion'?'#dc2626':'#991b1b');return gradient;};
    new Chart(canvas,{type,data:{labels,datasets:[{label:type==='doughnut'?'Animales':'Litros',data:values,backgroundColor:background,borderColor:type==='doughnut'?colors:'#b91c1c',borderWidth:2,borderRadius:type==='bar'?8:0}]},options:{responsive:true,maintainAspectRatio:false,animation:false}});
}
export function updateDashboard(main,model,data){
    const complete=data.schema>=3&&['fincas','animales','potreros','produccion','salud'].every(m=>data.references.complete_modules?.includes(m));
    if(!complete)return;
    const rows=module=>Object.values(model[module]).filter(r=>!r.deleted),animals=rows('animales'),farms=rows('fincas'),paddocks=rows('potreros'),production=rows('produccion'),health=rows('salud'),today=localDate();
    const sum=items=>items.reduce((n,r)=>n+Math.round(Number(r.litros)*100),0)/100;
    const counts={animales:animals.length,fincas:farms.length,potreros:paddocks.length,ocupacion:paddocks.reduce((n,p)=>n+p.animales_actuales,0),salud:health.length,produccion:sum(production.filter(r=>r.fecha.startsWith(today.slice(0,7))))};
    counts.carencia=health.filter(r=>r.en_carencia).length;counts.proximas=health.filter(r=>r.proxima_aplicacion>=today&&r.proxima_aplicacion<=addDays(today,7)).length;
    const notifications=alerts(model,data.references.alert_rules),list=main.querySelector('[data-dashboard-alerts]'),pattern=main.querySelector('[data-dashboard-alert-pattern]')?.content.firstElementChild;
    main.querySelectorAll('[data-dashboard-alert-total]').forEach(n=>n.textContent=notifications.length);
    if(list&&pattern){list.replaceChildren();for(const item of notifications.slice(0,5)){const node=pattern.cloneNode(true);node.querySelectorAll('[data-alert-field]').forEach(n=>n.textContent=item[n.dataset.alertField]);node.querySelector('a').href=item.url||'/salud/'+item.salud_id;list.append(node);}if(!notifications.length){const note=document.createElement('p');note.className='text-sm text-gray-600 text-center py-10';note.textContent='Todo en orden. Sin alertas pendientes';list.append(note);}}
    for(const node of main.querySelectorAll('[data-dashboard-stat]'))if(node.dataset.dashboardStat in counts)node.textContent=counts[node.dataset.dashboardStat];
    const types=['vaca','ternero','toro','novilla'],labels=['Vacas','Terneros','Toros','Novillas'];
    for(const node of main.querySelectorAll('[data-dashboard-type]')){
        const count=animals.filter(a=>a.tipo===node.dataset.dashboardType).length;node.textContent=node.dataset.percent!==undefined?(animals.length?Math.round(count/animals.length*1000)/10:0)+'%':count;
    }
    chart(main.querySelector('#distribucionChart'),labels,types.map(type=>animals.filter(a=>a.tipo===type).length),'doughnut');
    const canvas=main.querySelector('#produccionChart,#chartProduccion');
    const period=main.dataset.chartDays||'7',days=period==='anio'?0:Number(period),dates=Array.from({length:days},(_,i)=>addDays(today,i-days+1));
    const months=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    chart(canvas,period==='anio'?months:dates.map(d=>d.slice(5).split('-').reverse().join('/')),period==='anio'?months.map((_,i)=>sum(production.filter(r=>r.fecha.startsWith(today.slice(0,4)+'-'+String(i+1).padStart(2,'0'))))):dates.map(d=>sum(production.filter(r=>r.fecha===d))),'bar');
    const caption=main.querySelector('[data-chart-caption]');if(caption)caption.textContent=period==='anio'?'Este año':'Últimos '+days+' días';
    for(const button of main.querySelectorAll('#btn-7,#btn-30,#btn-7dias,#btn-30dias,#btn-anio,[data-chart-days]')){
        const range=button.id==='btn-anio'?'anio':button.dataset.chartDays||(button.id.includes('30')?'30':'7'),selected=range===period;
        button.setAttribute('aria-pressed',String(selected));
        for(const name of ['bg-gradient-to-r','from-red-600','to-red-700','text-white'])button.classList.toggle(name,selected);
        for(const name of ['bg-white','text-red-700'])button.classList.toggle(name,!selected);
        button.onclick=()=>{main.dataset.chartDays=range;updateDashboard(main,model,data);};
    }
    const map=main.querySelector('#map');
    if(map&&!navigator.onLine&&!main.querySelector('[data-map-offline-note]')){const note=document.createElement('p');note.dataset.mapOfflineNote='';note.className='text-sm text-amber-900 bg-amber-50 rounded-xl p-3 mb-3';note.textContent='El mapa base necesita Internet. Las coordenadas guardadas se consultan en las fichas de las fincas.';map.before(note);}
    for(const node of main.querySelectorAll('[data-offline-last-valid]')){
        node.title='Última información recibida del servidor; no se recalcula sin sus datos completos.';
        if(!node.querySelector('[data-last-valid-label]')){const label=document.createElement('small');label.dataset.lastValidLabel='';label.className='block text-xs font-normal';label.textContent='Última información confirmada';node.append(label);}
    }
}
export {chart as updateLocalChart};
