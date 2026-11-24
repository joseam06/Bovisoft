import {finance} from './finance';
import {bind,localDate,addDays} from './module-views';
import {sum,format,cents} from './money';

export function reportData(model,params,kind){
    const financial=finance(model,params),today=localDate(),animalKey=params.get('animal_id');
    const selected=kind==='historial'&&animalKey&&Object.values(model.animales).find(r=>String(r.id||r.record_uuid)===animalKey&&!r.deleted);
    if(kind==='historial'&&!selected)throw new Error('Selecciona un animal disponible de tu cuenta.');
    const animals=Object.values(model.animales).filter(r=>!r.deleted&&(!financial.farm||r.finca_uuid===financial.farm.record_uuid));
    const allHealth=Object.values(model.salud).filter(r=>!r.deleted&&(!financial.farm||r.finca_uuid===financial.farm.record_uuid));
    let records=allHealth.filter(r=>(!financial.start||r.fecha_aplicacion>=financial.start)&&(!financial.end||r.fecha_aplicacion<=financial.end)&&(!selected||r.animal_uuid===selected.record_uuid));
    if(kind==='vacunacion')records=records.filter(r=>['vacunacion','desparasitacion'].includes(r.tipo));
    if(kind==='tratamientos')records=records.filter(r=>['tratamiento','cirugia','revision','enfermedad','diagnostico'].includes(r.tipo));
    const withdrawal=allHealth.filter(r=>r.fin_carencia>=today),active=records.filter(r=>r.estado==='en_tratamiento'),upcoming=allHealth.filter(r=>['vacunacion','desparasitacion'].includes(r.tipo)&&r.proxima_aplicacion&&r.proxima_aplicacion<=addDays(today,60));
    const groups=new Map();for(const a of animals)groups.set(a.tipo,(groups.get(a.tipo)||0)+1);
    const weights=animals.filter(r=>r.peso_actual!==null&&r.peso_actual!==undefined&&r.peso_actual!=='');
    const metrics={...financial,totalAnimales:animals.length,vacasActivas:animals.filter(a=>a.tipo==='vaca'&&a.estado==='activo').length,vendidos:animals.filter(a=>a.estado==='vendido').length,
        enCarencia:kind==='inventario'?new Set(withdrawal.map(r=>r.animal_uuid)).size:withdrawal.length,enCarenciaCount:records.filter(r=>r.en_carencia).length,
        totalRegistros:records.length,vacunaciones:records.filter(r=>r.tipo==='vacunacion').length,desparasitaciones:records.filter(r=>r.tipo==='desparasitacion').length,
        totalVacunaciones:records.filter(r=>r.tipo==='vacunacion').length,totalDesparasitaciones:records.filter(r=>r.tipo==='desparasitacion').length,
        costoTotal:sum(records,'costo'),tratamientosActivos:active.length,totalActivos:active.length,totalCompletados:records.filter(r=>r.estado==='completado').length,
        tratamientos:records.filter(r=>['tratamiento','cirugia','revision'].includes(r.tipo)).length,proximasSiete:upcoming.filter(r=>r.proxima_aplicacion>=today&&r.proxima_aplicacion<=addDays(today,7)).length,
        pesoPromedio:weights.length?(weights.reduce((n,a)=>n+Number(a.peso_actual),0)/weights.length).toFixed(2):'Sin datos',fincaNombre:financial.farm?.nombre||'Todas las fincas',generadoEl:new Date().toLocaleString('es-CO')};
    return {metrics,selected,collections:{animales:animals,registros:records,historial:records,activos:active,registrosTratamientos:active,enCarencia:withdrawal,
        alertasVencidas:allHealth.filter(r=>r.proxima_aplicacion&&r.proxima_aplicacion<today),alertasProximas:allHealth.filter(r=>r.proxima_aplicacion>=today&&r.proxima_aplicacion<=addDays(today,7)),
        proximasDosis:upcoming,ingresos:financial.ingresos,egresos:financial.egresos,conteoTipos:[...groups].map(([tipo,cantidad])=>({tipo,cantidad}))}};
}
export function renderReport(main,model,data,path){
    const url=new URL(path,location.origin),kind=url.pathname.split('/').at(-1),html=data.views.reportes?.['print_'+kind];
    const needed=kind==='financiero'?['fincas','ingresos','egresos','salud']:['fincas','animales','salud'];
    if(!html||needed.some(m=>!data.references.complete_modules?.includes(m)))throw new Error('Faltan datos o la plantilla de este reporte. Reconecta para actualizar la copia; no se generará un total incompleto.');
    const {metrics,collections,selected}=reportData(model,url.searchParams,kind),doc=new DOMParser().parseFromString(html,'text/html');
    const name=Object.values(model.perfil)[0]?.name||data.profile.name;metrics.propietario=name;metrics.usuarioNombre=name;metrics.titulo={inventario:'Inventario Animal',salud:'Reporte Sanitario',vacunacion:'Vacunación y Desparasitación',tratamientos:'Tratamientos',historial:'Historial Clínico',financiero:'Reporte Financiero'}[kind];
    for(const node of doc.querySelectorAll('[data-report-stat]')){const field=node.dataset.reportStat;if(field in metrics)node.textContent=typeof metrics[field]==='bigint'?format(metrics[field]):metrics[field];}
    for(const node of doc.querySelectorAll('[data-report-sign]'))node.textContent=metrics.balance>=0n?'+':'';
    for(const body of doc.querySelectorAll('[data-report-collection]')){
        const key=body.dataset.reportCollection,pattern=body.querySelector('tr')?.cloneNode(true);if(!pattern)continue;body.replaceChildren();
        for(const row of collections[key]||[]){const node=pattern.cloneNode(true),module=key==='animales'?'animales':['ingresos','egresos'].includes(key)?key:'salud';
            if(key==='conteoTipos'){const cells=node.querySelectorAll('td');cells[0].textContent=row.tipo;cells[1].textContent=row.cantidad;if(cells[2])cells[2].textContent=(animalsCount(collections)?(row.cantidad/animalsCount(collections)*100).toFixed(1):'0')+'%';}
            else{bind(node,row,module,model);node.querySelectorAll('[data-offline-field=monto],[data-offline-field=costo]').forEach(n=>{const value=row[n.dataset.offlineField];n.textContent=value===null||value===undefined||value===''?'Sin registrar':(n.dataset.offlineField==='costo'?'$':'')+format(value);});if(['ingresos','egresos'].includes(module)){const amounts=[...collections.ingresos,...collections.egresos].map(r=>cents(r.monto)),max=amounts.reduce((a,b)=>a>b?a:b,0n);node.querySelectorAll('.bar-fill-green,.bar-fill-red').forEach(n=>n.style.width=(max?Number(cents(row.monto)*10000n/max)/100:0)+'%');}}
            body.append(node);
        }
        if(!body.children.length){const tr=doc.createElement('tr'),td=doc.createElement('td');td.colSpan=pattern.querySelectorAll('td').length;td.className='text-center';td.textContent='Sin registros para estos filtros.';tr.append(td);body.append(tr);}
    }
    if(selected)doc.querySelectorAll('[data-offline-owner=animales]').forEach(n=>n.textContent=n.dataset.offlineField==='finca_nombre'?model.fincas[selected.finca_uuid]?.nombre:selected[n.dataset.offlineField]??'Sin datos');
    const note=doc.createElement('p');note.className='section-title';note.textContent='Vista local de '+name+'. '+data.queue.length+' operaciones pendientes/no confirmadas incluidas. '+(data.queue.some(op=>['error','conflict'].includes(op.status))?'Contiene cambios que requieren revisión. ':'')+'Período: '+metrics.periodo;
    doc.body.prepend(note);
    main.replaceChildren();const back=document.createElement('a');back.href='/reportes';back.className='inline-block mb-4 text-white font-semibold underline';back.textContent='Volver a Reportes';
    const frame=document.createElement('iframe');frame.title='Reporte '+kind;frame.className='w-full rounded-xl bg-white';frame.style.height='80vh';frame.setAttribute('sandbox','allow-same-origin allow-modals');frame.srcdoc=doc.documentElement.outerHTML;
    main.append(back,frame);
    const print=document.createElement('button');print.type='button';print.className='px-5 py-3 bg-red-700 text-white rounded-xl mb-4 ml-4';print.textContent='Imprimir / Guardar como PDF';print.onclick=()=>frame.contentWindow.print();main.insertBefore(print,frame);
    if(url.pathname.includes('/descargar/')){const notice=document.createElement('p');notice.className='bg-amber-50 text-amber-900 rounded-xl p-4 mb-4';notice.textContent='Sin conexión utiliza la impresión del navegador para guardar como PDF. La descarga PDF habitual se conserva con conexión.';main.insertBefore(notice,frame);}
}
const animalsCount=collections=>collections.animales.length;

export function hydrateReports(main,model,navigate){
    for(const [id,module,label]of [['sel-finca','fincas','Todas las fincas'],['sel-animal','animales','Selecciona un animal']]){const select=main.querySelector('#'+id);if(!select)continue;const prior=select.value;select.replaceChildren(new Option(label,''));for(const row of Object.values(model[module]).filter(r=>!r.deleted))select.add(new Option(row.nombre||row.codigo,String(row.id||row.record_uuid)));select.value=prior;}
    const period=main.querySelector('#sel-periodo');if(period)period.onchange=()=>{for(const id of ['div-fecha-inicio','div-fecha-fin'])main.querySelector('#'+id)?.classList.toggle('hidden',period.value!=='custom');};
    for(const button of main.querySelectorAll('[data-report-kind]'))button.onclick=()=>{const params=new URLSearchParams({periodo:period?.value||'mes'});for(const [id,key]of [['sel-finca','finca_id'],['sel-animal','animal_id'],['inp-fecha-inicio','fecha_inicio'],['inp-fecha-fin','fecha_fin']]){const value=main.querySelector('#'+id)?.value;if(value)params.set(key,value);}navigate('/reportes/'+(button.dataset.reportPreview==='true'?'preview':'descargar')+'/'+button.dataset.reportKind+'?'+params);};
}
