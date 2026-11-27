import {cents,decimal,sum,format} from './money';
import {localDate,addDays,bind,relatedForm} from './module-views';
export function finance(model,params=new URLSearchParams()){
    const today=localDate(),period=params.get('periodo')||'mes',farm=params.get('finca_id');let start=null,end=today;
    if(period==='dia')start=today;
    else if(period==='semana'){const day=new Date(today+'T12:00Z').getUTCDay();start=addDays(today,-((day+6)%7));end=addDays(start,6);}
    else if(period==='mes'){start=today.slice(0,7)+'-01';end=addDays(addDays(start,32).slice(0,7)+'-01',-1);}
    else if(period==='anio'){start=today.slice(0,4)+'-01-01';end=today.slice(0,4)+'-12-31';}
    else if(period==='custom'){start=params.get('fecha_inicio')||null;end=params.get('fecha_fin')||null;}
    else end=null;
    const selected=farm&&Object.values(model.fincas).find(f=>String(f.id||f.record_uuid)===farm);
    if(farm&&!selected)throw new Error('La finca seleccionada no está disponible en esta cuenta.');
    const filtered=m=>Object.values(model[m]||{}).filter(r=>!r.deleted&&(!start||r.fecha>=start)&&(!end||r.fecha<=end)&&(!farm||r.finca_uuid===selected.record_uuid)).sort((a,b)=>b.fecha.localeCompare(a.fecha));
    const ingresos=filtered('ingresos'),egresos=filtered('egresos');
    const totalIngresos=sum(ingresos),totalEgresos=sum(egresos);
    const health=Object.values(model.salud).filter(r=>!r.deleted&&(!start||r.fecha_aplicacion>=start)&&(!end||r.fecha_aplicacion<=end)&&(!farm||r.finca_uuid===selected.record_uuid));
    if(start&&(!/^\d{4}-\d{2}-\d{2}$/.test(start)||end&&start>end)||end&&!/^\d{4}-\d{2}-\d{2}$/.test(end))throw new Error('Revisa el rango de fechas.');
    return {ingresos,egresos,totalIngresos,totalEgresos,balance:totalIngresos-totalEgresos,costosSalud:sum(health,'costo'),start,end,farm:selected,periodo:start?start+' al '+(end||'sin fecha final'):'Todos los registros'};
}
export function validateFinancial(module,values,model){
    if(cents(values.monto)<0n)throw new Error('El importe no puede ser negativo.');
    const farm=model.fincas[values.finca_uuid],animal=values.animal_uuid&&model.animales[values.animal_uuid];
    if(!farm||farm.deleted||values.animal_uuid&&(!animal||animal.deleted||animal.finca_uuid!==farm.record_uuid))throw new Error('El animal debe pertenecer a la finca seleccionada y a tu cuenta.');
    if(module==='ingresos'&&values.tipo==='venta_animal'&&animal&&(animal.venta_registrada||animal.estado==='vendido'))throw new Error('Este animal ya tiene una venta registrada o pendiente.');
    if(values.salud_uuid){const health=model.salud[values.salud_uuid];if(!health||health.deleted||health.finca_uuid!==farm.record_uuid||animal&&health.animal_uuid!==animal.record_uuid)throw new Error('El evento sanitario no corresponde a la finca y animal seleccionados.');}
}
export function financialForm(form,row,module,model){
    for(const [field,target] of [['finca_id','fincas'],['animal_id','animales']]){
        const input=form.querySelector('[name="'+field+'"]');if(!input)continue;
        const ref=field.replace('_id','_uuid'),prior=row?.[ref],value=prior?(model[target][prior]?.id||prior):input.value;
        input.replaceChildren(new Option(field==='animal_id'?'Sin animal':'Selecciona una finca',''));
        for(const item of Object.values(model[target]).filter(r=>!r.deleted)){const option=new Option(item.nombre||item.codigo||'Pendiente',String(item.id||item.record_uuid));option.dataset.version=item.version;input.add(option);}
        input.value=value||new URLSearchParams(location.search).get(field)||'';
    }
    if(!form.querySelector('[name=fecha]')?.value){const date=form.querySelector('[name=fecha]');if(date)date.value=localDate();}
    const animalInput=form.querySelector('[name=animal_id]'),farmInput=form.querySelector('[name=finca_id]'),type=form.querySelector('[name=tipo]');
    const show=()=>{const sale=type?.value==='venta_animal';for(const [id,visible]of [['seccion-animal',sale],['seccion-comprador',['venta_animal','venta_leche'].includes(type?.value)]])form.querySelector('#'+id)?.classList.toggle('hidden',!visible);if(animalInput)animalInput.required=sale;};
    if(type){type.onchange=show;show();}
    const choose=()=>{const animal=Object.values(model.animales).find(a=>String(a.id||a.record_uuid)===animalInput?.value);if(animal&&farmInput)farmInput.value=String(model.fincas[animal.finca_uuid]?.id||animal.finca_uuid);const version=form.querySelector('[name=animal_base_version]');if(version)version.value=animal?.version||'';for(const field of ['codigo','tipo','raza','peso']){const node=form.querySelector('#ficha-'+field);if(node)node.textContent=animal?.[field==='peso'?'peso_actual':field]||'—';}form.querySelector('#ficha-animal')?.classList.toggle('hidden',!animal);};
    if(animalInput){animalInput.onchange=choose;choose();}
    const params=new URLSearchParams(location.search),health=params.get('salud_id')&&Object.values(model.salud).find(r=>String(r.id||r.record_uuid)===params.get('salud_id'));
    if(module==='egresos'&&health&&!form.querySelector('[name=salud_id]')){const input=document.createElement('input');input.type='hidden';input.name='salud_id';input.value=health.id||health.record_uuid;form.append(input);form.querySelector('[name=monto]').value=health.costo||'';form.querySelector('[name=fecha]').value=health.fecha_aplicacion;form.querySelector('[name=descripcion]').value=health.nombre_producto||'Evento sanitario';animalInput.value=model.animales[health.animal_uuid]?.id||health.animal_uuid;choose();}
}
export function hydrateFinance(main,model,data,path){
    const route=new URL(path,location.origin),summary=finance(model,route.searchParams);
    for(const node of main.querySelectorAll('[data-finance-stat]')){
        const field=node.dataset.financeStat;
        if(field in summary)node.textContent=typeof summary[field]==='bigint'?format(summary[field]):summary[field];
        if(field==='totalAnimales')node.textContent=Object.values(model.animales).filter(r=>!r.deleted).length;
        if(field==='totalSalud')node.textContent=Object.values(model.salud).filter(r=>!r.deleted).length;
    }
    for(const node of main.querySelectorAll('[data-finance-sign]'))node.textContent=summary.balance>=0n?'+':'';
    for(const form of main.querySelectorAll('form[method=GET],form[method=get]')){
        form.action=location.pathname;
        const select=form.querySelector('[name=finca_id]');if(select){select.replaceChildren(new Option('Todas las fincas',''));for(const farm of Object.values(model.fincas).filter(r=>!r.deleted))select.add(new Option(farm.nombre,String(farm.id||farm.record_uuid)));select.value=route.searchParams.get('finca_id')||'';select.onchange=()=>form.requestSubmit();}
        for(const a of form.querySelectorAll('a[href]')){const url=new URL(a.href);if(route.searchParams.get('finca_id'))url.searchParams.set('finca_id',route.searchParams.get('finca_id'));a.href=url.pathname+url.search;}
    }
    for(const list of main.querySelectorAll('[data-finance-list]')){
        const module=list.dataset.financeList,pattern=list.firstElementChild?.cloneNode(true);if(!pattern)continue;list.replaceChildren();
        for(const row of summary[module]){const node=pattern.cloneNode(true);bind(node,row,module,model);node.querySelectorAll('[data-offline-field=monto]').forEach(n=>n.textContent=format(row.monto));node.querySelectorAll('a[href]').forEach(a=>{if(a.getAttribute('href').includes('/'+module+'/'))a.href='/finanzas/'+module+'/'+(row.id||row.record_uuid);});list.append(node);}
    }
    const balance=main.querySelector('[data-finance-balances]'),pattern=balance?.querySelector('tr')?.cloneNode(true);
    if(balance&&pattern){const params=new URLSearchParams(route.search);params.delete('finca_id');const all=finance(model,params);balance.replaceChildren();for(const farm of Object.values(model.fincas).filter(r=>!r.deleted)){
        const inc=sum(all.ingresos.filter(r=>r.finca_uuid===farm.record_uuid)),out=sum(all.egresos.filter(r=>r.finca_uuid===farm.record_uuid)),node=pattern.cloneNode(true),cells=node.querySelectorAll('td');
        [farm.nombre,format(inc),format(out),format(inc-out)].forEach((value,i)=>cells[i].textContent=value);balance.append(node);
    }}
    for(const list of main.querySelectorAll('[data-finance-breakdown]')){
        const module=list.dataset.financeBreakdown,pattern=list.firstElementChild?.cloneNode(true);if(!pattern)continue;list.replaceChildren();const groups=new Map();
        for(const row of summary[module]){const key=module==='ingresos'?row.tipo:row.categoria;groups.set(key,(groups.get(key)||0n)+cents(row.monto));}
        for(const [key,amount] of groups){const node=pattern.cloneNode(true),spans=node.querySelectorAll('span');spans[0].textContent=(module==='ingresos'?data.references.financial_types:data.references.financial_categories)?.[key]||key;spans[1].textContent='$'+format(amount);list.append(node);}
    }
    const canvas=main.querySelector('#flujoCajaChart');
    if(canvas&&window.Chart){Chart.getChart(canvas)?.destroy();const months=Array.from({length:6},(_,i)=>{const date=new Date(localDate()+'T12:00Z');date.setUTCDate(1);date.setUTCMonth(date.getUTCMonth()-5+i);return date.toISOString().slice(0,7);});
        const amounts=module=>months.map(month=>sum(Object.values(model[module]).filter(r=>!r.deleted&&r.fecha.startsWith(month)&&(!summary.farm||r.finca_uuid===summary.farm.record_uuid))));const income=amounts('ingresos'),expense=amounts('egresos');
        new Chart(canvas,{type:'bar',data:{labels:months,datasets:[{label:'Ingresos',data:income.map(n=>Number(n)/100),backgroundColor:'#16a34a'},{label:'Egresos',data:expense.map(n=>Number(n)/100),backgroundColor:'#dc2626'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{tooltip:{callbacks:{label:c=>c.dataset.label+': $'+format((c.datasetIndex?expense:income)[c.dataIndex])}}}}});
    }
    return summary;
}
