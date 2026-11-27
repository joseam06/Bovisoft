import {localDate,addDays} from './module-views';
export function alerts(model,catalog,today=localDate()){
    if(!catalog?.rules)return [];
    const found=new Map(),dateLabel=d=>d.split('-').reverse().join('/');
    for(const r of Object.values(model.salud||{})){
        if(r.deleted||r.estado==='cancelado')continue;
        for(const rule of catalog.rules){
            const date=r[rule.field];if(!date)continue;
            const days=Math.round((Date.parse(date)-Date.parse(today))/86400000);
            if(rule.state&&r.estado!==rule.state||rule.skip_next&&r.proxima_aplicacion&&r.proxima_aplicacion<=addDays(today,rule.skip_next))continue;
            if(rule.min!==undefined&&days<rule.min||rule.max!==undefined&&days>rule.max)continue;
            const n=Math.abs(days),animal=model.animales[r.animal_uuid],farm=model.fincas[r.finca_uuid],fields={tipo:catalog.types[r.tipo]||r.tipo,animal:animal?.nombre||animal?.codigo||'Animal',n,plural:n===1?'':'s',fecha:dateLabel(date)};
            const text=template=>template.replace(/\{(\w+)\}/g,(_,key)=>String(fields[key]??''));
            const row={id:'sal-'+rule.key+'-'+r.record_uuid,tipo:rule.key,nivel:rule.level,titulo:text(rule.title),descripcion:text(rule.description),animal:fields.animal,finca:farm?.nombre||'Sin finca',fecha:fields.fecha,salud_id:r.id||r.record_uuid,categoria:r.categoria,icono:rule.icon,color:rule.color};
            row.categoria_label=catalog.categories?.[r.categoria]||r.categoria||'';found.set(row.id,row);
        }
    }
    for(const p of Object.values(model.potreros||{})){
        if(p.deleted||!p.fecha_ultima_rotacion||!p.dias_descanso||!catalog.rotation)continue;
        const date=addDays(p.fecha_ultima_rotacion,p.dias_descanso),days=Math.round((Date.parse(date)-Date.parse(today))/86400000);if(days>catalog.rotation.max)continue;
        const fields={nombre:p.nombre,fecha:dateLabel(date)},text=t=>t.replace(/\{(\w+)\}/g,(_,k)=>fields[k]||'');
        const row={id:'pot-rotacion-'+p.record_uuid,tipo:'rotacion',nivel:catalog.rotation.level,titulo:text(catalog.rotation.title),descripcion:text(catalog.rotation.description),animal:p.nombre,finca:model.fincas[p.finca_uuid]?.nombre||'Sin finca',fecha:fields.fecha,url:'/potreros/'+(p.id||p.record_uuid),categoria:null,icono:'fa-arrows-rotate',color:'yellow'};found.set(row.id,row);
    }
    const rank={critico:0,urgente:1,advertencia:2,info:3};return [...found.values()].sort((a,b)=>rank[a.nivel]-rank[b.nivel]||a.id.localeCompare(b.id));
}
export function hydrateAlerts(main,rows){
    for(const node of main.querySelectorAll('[data-alert-stat]'))node.textContent=node.dataset.alertStat==='total'?rows.length:rows.filter(r=>r.nivel===node.dataset.alertStat).length;
    for(const list of main.querySelectorAll('[data-alert-list]')){
        const pattern=list.querySelector('[data-alert-row]')?.cloneNode(true);if(!pattern)continue;
        const items=rows.filter(r=>r.nivel===list.dataset.alertList);list.replaceChildren();
        for(const row of items){const node=pattern.cloneNode(true);node.dataset.alertId=row.id;node.querySelectorAll('[data-alert-field]').forEach(n=>{n.textContent=row[n.dataset.alertField]??'';if(n.dataset.alertField==='categoria_label')n.parentElement.hidden=!row.categoria;});const icon=node.firstElementChild?.querySelector('i');if(icon)icon.className='fa-solid '+row.icono+' text-'+row.color+'-600';node.querySelectorAll('a').forEach(a=>a.href=row.url||'/salud/'+row.salud_id);list.append(node);}
        list.closest('[data-alert-section]')?.toggleAttribute('hidden',!items.length);
    }
}

export function hydrateBell(rows){
    const list=document.getElementById('lista-alertas-campanita'),badge=document.getElementById('badge-dropdown');if(!list)return;
    if(badge)badge.textContent=rows.length+' alertas';list.replaceChildren();
    for(const row of rows.slice(0,8)){
        const a=document.createElement('a');a.href=row.url||'/salud/'+row.salud_id;a.className='flex items-center gap-3 px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-all';
        const icon=document.createElement('i');icon.className='fa-solid '+row.icono+' text-yellow-600';const content=document.createElement('div');content.className='flex-1 min-w-0';
        const title=document.createElement('p');title.className='text-xs font-bold text-gray-800 truncate';title.textContent=row.titulo;
        const detail=document.createElement('p');detail.className='text-xs text-gray-500 truncate';detail.textContent=row.animal+' · '+row.finca;content.append(title,detail);a.append(icon,content);list.append(a);
    }
    if(!rows.length){const note=document.createElement('p');note.className='p-4 text-center text-sm text-gray-500';note.textContent='Sin alertas activas';list.append(note);}
}
