// Bind the existing Blade templates. Storage, queue, authentication and transport
// stay in workspace.js; these adapters only project downloaded/local records.
const names={manana:'Mañana',tarde:'Tarde',noche:'Noche',en_descanso:'En descanso',en_mantenimiento:'En mantenimiento',en_tratamiento:'En tratamiento',vacunacion:'Vacunación',desparasitacion:'Desparasitación',vitaminizacion:'Vitaminización',revision:'Revisión',diagnostico:'Diagnóstico',cirugia:'Cirugía'};
const title=v=>names[v]||String(v??'').replaceAll('_',' ').replace(/^./,c=>c.toUpperCase());
const dateFormatter=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Bogota',year:'numeric',month:'2-digit',day:'2-digit'});
export const localDate=()=>dateFormatter.format(new Date());
export const addDays=(date,count)=>{const d=new Date(date+'T12:00:00Z');d.setUTCDate(d.getUTCDate()+Number(count));return d.toISOString().slice(0,10);};
export function category(type){return ['vacunacion','vitaminizacion','desparasitacion','bioseguridad'].includes(type)?'preventivo':['enfermedad','tratamiento','diagnostico','cirugia','revision'].includes(type)?'clinico':['sincronizacion','hormonas','protocolo','preparacion_iatf'].includes(type)?'reproductivo':['alerta','control','carencia'].includes(type)?'seguimiento':null;}
export function enrich(model){
    const today=localDate(),todayMs=Date.parse(today),nextWeek=addDays(today,7);
    for(const animal of Object.values(model.animales)){
        const born=animal.fecha_nacimiento;
        const months=born?Math.max(0,(Number(today.slice(0,4))-Number(born.slice(0,4)))*12+Number(today.slice(5,7))-Number(born.slice(5,7))):null;
        animal.edad=months===null?'N/A':months>=12?Math.floor(months/12)+' años':months+' meses';
    }
    const active=Object.values(model.animales).filter(a=>!a.deleted&&a.estado==='activo');
    for(const p of Object.values(model.potreros||{})){
        p.animales_actuales=active.filter(a=>a.potrero_uuid===p.record_uuid||(!a.potrero_uuid&&a.potrero_id&&a.potrero_id===p.id)).length;
        p.porcentaje_ocupacion=p.capacidad_animales?Math.round(p.animales_actuales/Number(p.capacidad_animales)*1000)/10:0;
        p.disponibilidad=p.capacidad_animales?Math.max(0,p.capacidad_animales-p.animales_actuales):'Sin límite';
        const next=p.fecha_ultima_rotacion&&p.dias_descanso?addDays(p.fecha_ultima_rotacion,p.dias_descanso):null;
        const days=next?Math.round((Date.parse(next)-todayMs)/86400000):null;
        p.requiere_rotacion=days!==null&&days<=0;
        p.dias_desde_ultima_rotacion=p.fecha_ultima_rotacion?Math.round((todayMs-Date.parse(p.fecha_ultima_rotacion))/86400000):null;
        p.proxima_rotacion=days===null?'No programada':days<0?'Requiere rotación':days===0?'Hoy':days===1?'Mañana':'En '+days+' días';
    }
    for(const r of Object.values(model.salud||{})){
        r.categoria=category(r.tipo);r.fin_carencia=r.dias_carencia?addDays(r.fecha_aplicacion,r.dias_carencia):null;
        r.en_carencia=!!r.fin_carencia&&r.fin_carencia>=today;
        r.dias_carencia_restantes=r.en_carencia?Math.round((Date.parse(r.fin_carencia)-todayMs)/86400000):0;
        r.alerta_proxima=!!r.proxima_aplicacion&&r.proxima_aplicacion<=nextWeek;
        const days=r.proxima_aplicacion?Math.round((Date.parse(r.proxima_aplicacion)-todayMs)/86400000):null;
        r.dias_proxima=days===null?null:Math.abs(days);
        r.dias_para_proxima=days===null?null:days<0?'Vencida':days===0?'Hoy':'En '+days+' días';
    }
    return model;
}
export function bind(root,row,module,model){
    if(!row)return;
    for(const node of root.querySelectorAll('[data-offline-if]'))if(!node.closest('tbody')||root===node.closest('tr'))node.hidden=!row[node.dataset.offlineIf];
    for(const node of root.querySelectorAll('[data-offline-occupancy-bar]')){const value=Math.min(100,Number(row.porcentaje_ocupacion)||0);node.style.width=value+'%';node.style.setProperty('--w',value+'%');}
    for(const node of root.querySelectorAll('[data-offline-occupancy-ring]'))node.setAttribute('stroke-dashoffset',2*Math.PI*56*(1-Math.min(100,Number(row.porcentaje_ocupacion)||0)/100));
    for(const node of root.querySelectorAll('[data-offline-field]')){
        if(node.dataset.offlineOwner&&node.dataset.offlineOwner!==module)continue;
        if(node.closest('[data-offline-collection]')&&root!==node.closest('tr'))continue;
        const field=node.dataset.offlineField;
        let value=field==='finca_nombre'?model.fincas[row.finca_uuid]?.nombre:field==='animal_nombre'?model.animales[row.animal_uuid]?.nombre:row[field];
        if(['sesion_formateada','calidad_formateada','estado_formateado','tipo_formateado','categoria_formateada'].includes(field))value=title(row[field.replace('_formateada','').replace('_formateado','')]);
        if(['tipo','estado','sesion','calidad'].includes(field))value=title(value);
        const badge=node.parentElement?.matches('span.rounded-full')?node.parentElement:null;
        if(badge&&['estado','estado_formateado'].includes(field)&&['salud','potreros'].includes(module)){
            const color=module==='potreros'?({activo:'green',en_descanso:'orange',en_mantenimiento:'gray'}[row.estado]||'gray'):({completado:'green',en_tratamiento:'blue',pendiente:'yellow',cancelado:'gray'}[row.estado]||'gray');
            badge.className=badge.className.replace(/\b(?:bg|text)-(?:red|green|blue|yellow|orange|gray)-\d+\b|\btext-white\b/g,'')+(module==='potreros'?` bg-${color}-500 text-white`:` bg-${color}-100 text-${color}-800`);
        }
        if(['fecha','fecha_aplicacion','fecha_ultima_rotacion','proxima_aplicacion','fin_carencia','fecha_nacimiento'].includes(field)&&value)value=value.split('-').reverse().join('/');
        if(field==='created_at'||field==='updated_at')value=value?new Date(value).toLocaleString('es-CO'):'Guardado en este dispositivo';
        node.textContent=value===null||value===undefined||value===''?'Sin registrar':String(value);
    }
    for(const form of root.querySelectorAll('form[action]')){
        if(form.method.toUpperCase()==='GET')continue;
        form.action=(['ingresos','egresos'].includes(module)?'/finanzas/':'/')+module+'/'+(row.id||row.record_uuid);form.removeAttribute('onsubmit');
        form.querySelectorAll('[name=base_version]').forEach(n=>n.value=row.version);
    }
    for(const link of root.querySelectorAll('a[href]')){
        const url=new URL(link.href,location.origin);
        if(url.origin!==location.origin)continue;
        for(const [target,related] of [[module,row],['fincas',model.fincas[row.finca_uuid]],['animales',model.animales[row.animal_uuid]]]){
            if(related)url.pathname=url.pathname.replace(new RegExp('^/'+target+'/(?:[0-9]+|[a-f0-9-]{36})(?=/|$)'),'/'+target+'/'+(related.id||related.record_uuid));
        }
        const animal=model.animales[row.animal_uuid]||(module==='animales'?row:null);
        if(animal){
            url.pathname=url.pathname.replace(/^\/(produccion|salud)\/animal\/[^/]+$/,'/$1/animal/'+(animal.id||animal.record_uuid));
            if(url.searchParams.has('animal_id'))url.searchParams.set('animal_id',animal.id||animal.record_uuid);
        }
        link.href=url.pathname+url.search;
    }
    root.querySelectorAll('button').forEach(button=>{if(button.querySelector('.fa-trash,.fa-trash-can')&&!button.closest('form')){button.dataset.delete=row.record_uuid;button.dataset.module=module;}});
}
export function hydrateModule(main,module,row,model,views,path){
    const url=new URL(path,location.origin),parts=url.pathname.split('/').filter(Boolean),params=url.searchParams;
    if(parts[0]==='fincas'&&parts[2]==='potreros')params.set('finca_id',parts[1]);
    for(const form of main.querySelectorAll('form[method=GET],form[method=get]')){
        form.action=location.pathname;
        for(const [field,target] of [['finca_id','fincas'],['animal_id','animales']]){
            const select=form.querySelector('select[name="'+field+'"]');if(!select)continue;
            select.replaceChildren(new Option('Todos',''));
            for(const item of Object.values(model[target]).filter(r=>!r.deleted))select.add(new Option(item.nombre||item.codigo||'Pendiente',String(item.id||item.record_uuid)));
            select.value=params.get(field)||'';
        }
    }
    const all=Object.values(model[module]||{}).filter(r=>!r.deleted);
    const animalKey=parts[1]==='animal'?parts[2]:params.get('animal_id');
    const animal=Object.values(model.animales).find(a=>String(a.id||a.record_uuid)===animalKey);
    if(animal)for(const node of main.querySelectorAll('[data-offline-owner=animales][data-offline-field]'))if(!node.closest('tbody'))node.textContent=node.dataset.offlineField==='finca_nombre'?model.fincas[animal.finca_uuid]?.nombre||'Finca original':animal[node.dataset.offlineField]??'Sin registrar';
    let rows=all.filter(r=>!animalKey||r.animal_uuid===animal?.record_uuid);
    if(module==='salud'&&['preventivo','clinico','reproductivo','seguimiento'].includes(parts[1]))rows=rows.filter(r=>r.categoria===parts[1]);
    for(const [field,value] of params){
        if(!value)continue;
        if(['finca_id','animal_id'].includes(field)){
            const target=field==='finca_id'?'fincas':'animales',ref=Object.values(model[target]).find(r=>String(r.id||r.record_uuid)===value);
            rows=rows.filter(r=>r[field.replace('_id','_uuid')]===ref?.record_uuid);
        }else if(['estado','tipo','sesion','calidad'].includes(field))rows=rows.filter(r=>r[field]===value);
        else if(['search','buscar'].includes(field))rows=rows.filter(r=>JSON.stringify([r.nombre,r.codigo,r.nombre_producto,r.veterinario,model.animales[r.animal_uuid]?.nombre]).toLocaleLowerCase('es').includes(value.toLocaleLowerCase('es')));
        else if(field==='fecha_desde')rows=rows.filter(r=>r.fecha>=value);
        else if(field==='fecha_hasta')rows=rows.filter(r=>r.fecha<=value);
    }
    rows.sort((a,b)=>(b.fecha||b.fecha_aplicacion||b.created_at||'').localeCompare(a.fecha||a.fecha_aplicacion||a.created_at||''));
    bind(main,row,module,model);
    for(const body of main.querySelectorAll('[data-offline-collection]')){
        const target=body.dataset.offlineCollection,pattern=body.querySelector('tr')?.cloneNode(true);
        if(!pattern)continue;
        let items=target===module?rows:Object.values(model.animales).filter(a=>!a.deleted&&(a.potrero_uuid===row?.record_uuid||a.potrero_id&&a.potrero_id===row?.id));
        if(body.hasAttribute('data-offline-history'))items=all.filter(r=>r.animal_uuid===row?.animal_uuid&&r.record_uuid!==row?.record_uuid);
        body.replaceChildren();
        for(const item of items){const node=pattern.cloneNode(true);node.dataset.offlineRow=item.record_uuid;bind(node,item,target,model);body.append(node);}
        if(!items.length){const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=pattern.querySelectorAll('td').length;td.className='p-6 text-center text-gray-500';td.textContent='No hay registros con estos filtros.';tr.append(td);body.append(tr);}
    }
    main.querySelectorAll('nav[role=navigation]').forEach(n=>n.hidden=true);
    const today=localDate(),month=today.slice(0,7),day=new Date(today+'T12:00:00Z').getUTCDay(),week=addDays(today,-((day+6)%7));
    const cents=items=>items.reduce((n,r)=>n+Math.round(Number(r.litros||0)*100),0)/100;
    const stats={total:all.length,totalRegistros:rows.length,activos:all.filter(r=>r.estado==='activo').length,en_descanso:all.filter(r=>r.estado==='en_descanso').length,
        area_total:all.reduce((n,r)=>n+Number(r.area||0),0),totalHoy:cents(all.filter(r=>r.fecha===today)),totalSemana:cents(all.filter(r=>r.fecha>=week&&r.fecha<=today)),totalMes:cents(all.filter(r=>r.fecha?.startsWith(month))),
        totalAnimal:cents(rows),promedioAnimal:rows.length?cents(rows)/rows.length:0};
    Object.assign(stats,{pendientes:rows.filter(r=>r.estado==='pendiente').length,proximas:rows.filter(r=>r.alerta_proxima).length,proximasVencer:rows.filter(r=>r.alerta_proxima).length,vacunaciones:rows.filter(r=>r.tipo==='vacunacion').length,enCarencia:rows.filter(r=>r.en_carencia).length});
    for(const n of main.querySelectorAll('[data-offline-stat]'))if(n.dataset.offlineStat in stats)n.textContent=stats[n.dataset.offlineStat];
    for(const n of main.querySelectorAll('[data-offline-health-category]'))n.textContent=all.filter(r=>r.categoria===n.dataset.offlineHealthCategory).length;
    const top=main.querySelector('[data-offline-top]'),pattern=top?.querySelector('[data-offline-top-row]')?.cloneNode(true);
    if(top&&pattern){
        const totals=new Map();
        for(const r of all.filter(r=>r.fecha?.startsWith(month))){const item=totals.get(r.animal_uuid)||{animal:r.animal_uuid,litros:0,count:0};item.litros+=Math.round(Number(r.litros)*100);item.count++;totals.set(r.animal_uuid,item);}
        top.replaceChildren();
        const ranking=[...totals.values()].sort((a,b)=>b.litros-a.litros).slice(0,5);
        ranking.forEach((item,index)=>{const node=pattern.cloneNode(true);node.querySelector('[data-top-rank]').textContent=index+1;node.querySelector('[data-top-animal]').textContent=model.animales[item.animal]?.nombre||model.animales[item.animal]?.codigo||'Animal original';node.querySelector('[data-top-count]').textContent=item.count;node.querySelector('[data-top-litros]').textContent=(item.litros/100).toFixed(2);top.append(node);});
        if(!ranking.length){const empty=document.createElement('p');empty.className='text-sm text-gray-500';empty.textContent='Sin registros este mes';top.append(empty);}
    }
    for(const n of main.querySelectorAll('[data-offline-health-stat]')){
        const type=n.dataset.offlineHealthStat;
        n.textContent=type==='total'?all.length:type==='vacunaciones'?all.filter(r=>r.tipo==='vacunacion').length:type==='tratamientos'?all.filter(r=>r.tipo==='tratamiento').length:type==='en_carencia'?all.filter(r=>r.en_carencia).length:all.filter(r=>r.proxima_aplicacion>=today&&r.proxima_aplicacion<=addDays(today,7)).length;
    }
    return {rows,all};
}

export function validateLocal(module,row,values,model){
    if(module==='fincas')return;
    const farm=model.fincas[values.finca_uuid];
    if(!farm||farm.deleted)throw new Error('Selecciona una finca autorizada disponible.');
    if(module==='animales'&&values.potrero_uuid){
        const p=model.potreros[values.potrero_uuid];
        if(!p||p.deleted||p.finca_uuid!==farm.record_uuid)throw new Error('El potrero no pertenece a la finca seleccionada.');
        if(row?.potrero_uuid!==p.record_uuid&&(p.estado!=='activo'||p.capacidad_animales&&p.animales_actuales>=p.capacidad_animales))throw new Error('El potrero no está activo o no tiene capacidad disponible.');
    }
    if(module==='potreros'){
        const count=row?.animales_actuales||0;
        if(Number(values.capacidad_animales)>0&&Number(values.capacidad_animales)<count)throw new Error('La capacidad no puede ser menor que la ocupación actual.');
        if(row&&row.finca_uuid!==farm.record_uuid&&Object.values(model.animales).some(a=>!a.deleted&&a.potrero_uuid===row.record_uuid))throw new Error('Reasigna primero los animales antes de trasladar el potrero.');
    }
    if(['produccion','salud'].includes(module)){
        const animal=model.animales[values.animal_uuid];
        if(!animal||animal.deleted)throw new Error('Selecciona un animal autorizado disponible.');
        const historical=row&&row.animal_uuid===animal.record_uuid&&row.finca_uuid===farm.record_uuid;
        if(module==='produccion'&&row&&!historical)throw new Error('Conserva el animal y la finca originales de la producción.');
        if(!historical&&(animal.estado!=='activo'||animal.finca_uuid!==farm.record_uuid||module==='produccion'&&(!farm.activa||!['vaca','novilla'].includes(animal.tipo))))throw new Error('El animal debe estar activo, ser elegible y pertenecer a la finca seleccionada.');
        if(module==='produccion'&&row&&values.fecha!==row.fecha&&(animal.estado!=='activo'||animal.finca_uuid!==farm.record_uuid))throw new Error('Cambiar la fecha requiere revisión online porque el animal fue trasladado o está inactivo. Conserva la fecha original.');
        if(module==='salud'&&row?.egreso_vinculado&&['animal_uuid','finca_uuid','fecha_aplicacion','nombre_producto','costo'].some(f=>f==='costo'?Number(values[f]||0)!==Number(row[f]||0):values[f]!==row[f]))throw new Error('El evento tiene un egreso vinculado. Revisa ese cambio en Finanzas con conexión.');
    }
}

export function relatedForm(form,row,module,model){
    if(!['animales','potreros','produccion','salud'].includes(module))return;
    const creating=!row,params=new URLSearchParams(location.search);
    for(const [field,target] of [['finca_id','fincas'],['animal_id','animales']]){
        const input=form.querySelector('[name="'+field+'"]');if(!input)continue;
        const reference=field.replace('_id','_uuid'),original=row&&model[target][row[reference]];
        const previous=original?.id||original?.record_uuid||input.value||params.get(field)||params.get('finca');
        if(input.tagName==='SELECT'){
            input.replaceChildren(new Option('Selecciona '+(field==='finca_id'?'una finca':'un animal'),''));
            for(const item of Object.values(model[target]).filter(r=>!r.deleted&&(!creating||target==='fincas'?r.activa||!creating:r.estado==='activo'&&(module!=='produccion'||['vaca','novilla'].includes(r.tipo))))){
                const option=new Option(item.nombre||item.codigo||'Pendiente',String(item.id||item.record_uuid));
                if(target==='animales')option.dataset.finca=String(model.fincas[item.finca_uuid]?.id||item.finca_uuid);
                input.add(option);
            }
        }
        input.value=previous??'';
    }
    const animalInput=form.querySelector('[name=animal_id]'),farmInput=form.querySelector('[name=finca_id]');
    if(animalInput&&farmInput){
        const change=()=>{const selected=Object.values(model.animales).find(a=>String(a.id||a.record_uuid)===animalInput.value),farm=model.fincas[selected?.finca_uuid];if(farm&&!row)farmInput.value=String(farm.id||farm.record_uuid);};
        animalInput.addEventListener('change',change);
        if(!row){if(params.get('animal_id'))animalInput.value=params.get('animal_id');change();}
        if(module==='produccion'&&row){
            const inputs=form.querySelectorAll('input[readonly][type=text]');
            if(inputs[1])inputs[1].value=model.animales[row.animal_uuid]?.nombre||'Animal original';
            if(inputs[2])inputs[2].value=model.fincas[row.finca_uuid]?.nombre||'Finca original';
            const animal=model.animales[row.animal_uuid],date=form.querySelector('[name=fecha]');
            if(date)date.readOnly=!animal||animal.estado!=='activo'||animal.finca_uuid!==row.finca_uuid;
        }
    }
    for(const input of form.querySelectorAll('input[type=date]'))if(['fecha','fecha_aplicacion','fecha_ultima_rotacion'].includes(input.name)){input.max=localDate();if(!row&&!input.value&&input.name!=='fecha_ultima_rotacion')input.value=localDate();}
    if(row?.egreso_vinculado)for(const field of ['costo','fecha_aplicacion','nombre_producto','animal_id','finca_id']){const input=form.querySelector('[name="'+field+'"]');if(input){if(input.tagName==='SELECT'){const value=input.value;input.addEventListener('change',()=>input.value=value);}else input.readOnly=true;input.title='Egreso vinculado: requiere revisión en Finanzas con conexión.';}}
}
