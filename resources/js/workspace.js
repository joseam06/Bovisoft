import {storeReady as identityStoreReady, ensureWorker, workerCall, identity} from './offline-guard';
import {createAutomaticVault, seal, readVault} from './workspace-crypto';
import {acknowledge, applyDelta as applyRemoteChanges, nextOperation, missingParents, prepareOperation, sameActor, syncError, retryAfterMs, backoffMs} from './sync/protocol';
import {leadership} from './sync/leadership';

if (document.getElementById('offline-indicator')) startWorkspace();

function startWorkspace() {
    const extensionUrl = '/workspace-store.js';
    let storePromise;
    function workspaceStore() {
        return storePromise ||= identityStoreReady.then(async store => {
            await ensureWorker();
            // Load through the current worker, after replacing any earlier manual-mode worker.
            await import(/* @vite-ignore */ extensionUrl+'?automatic='+Date.now());
            if (!store.automaticWorkspace || !store.commitAutomaticWorkspace) throw new Error('Se está actualizando el guardado automático. Tus datos anteriores se conservan.');
            return {...store,workspace:store.automaticWorkspace,commitWorkspace:store.commitAutomaticWorkspace};
        }).catch(failure=>{storePromise=null;throw failure;});
    }
    const $ = id => document.getElementById(id);
    const channel = 'BroadcastChannel' in window ? new BroadcastChannel('bovisoft-workspace-v1') : null;
    const endpoint = '/api/offline/v1/workspace';
    const main = document.querySelector('main');
    const shell = document.body.dataset.workspaceShell === 'true';
    let session, vault, data, key, epoch, editing, syncTimer, noticeTimer;
    let syncing = false, syncFailed = false, preparing = false, active = false, retry = 1000;
    let localView = shell, connectionLost = !navigator.onLine, uploading = false;
    let bootPromise, warmPromise, muted = false, productionRefreshing=false;
    const dirtyForms = new WeakSet();
    const senderId=crypto.randomUUID();
    let queueSignature = '', archiveSignature = '';
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
    const button = (label, attrs = '', secondary = false) => `<button type="button" ${attrs} class="px-4 py-2 rounded-xl font-semibold ${secondary ? 'border border-red-700 text-red-800' : 'bg-red-700 text-white'}">${label}</button>`;
    const link = (path, label) => `<a href="${esc(path)}" class="text-red-800 font-semibold underline underline-offset-4">${esc(label)}</a>`;
    const fmt = stamp => stamp ? new Date(stamp).toLocaleString('es-CO', {timeZone:'America/Bogota'}) : 'Todavía no se ha completado';
    function comparison(value) {
        if (!value || value.deleted) return '<p>Registro eliminado o no disponible.</p>';
        const names = {nombre:'Nombre',codigo:'Código',numero:'Número',area:'Área (ha)',descripcion:'Descripción',direccion:'Dirección',municipio:'Municipio',departamento:'Departamento',latitud:'Latitud',longitud:'Longitud',activa:'Activa',tipo:'Tipo',sexo:'Sexo',raza:'Raza',fecha_nacimiento:'Nacimiento',peso_actual:'Peso (kg)',color:'Color',estado:'Estado',observaciones:'Observaciones',finca_uuid:'Finca',potrero_id:'Potrero'};
        return `<p class="text-sm mb-2">Versión ${esc(value.version || 1)}</p><dl class="space-y-2">${Object.entries(names).filter(([field])=>field in value).map(([field,title])=>{
            let text = value[field];
            if (field === 'finca_uuid') text = projected().fincas[text]?.nombre || 'Finca no disponible';
            if (field === 'potrero_id') text = data.references.potreros.find(p=>String(p.id)===String(text))?.nombre || 'Sin asignar';
            if (typeof text === 'boolean') text = text ? 'Sí' : 'No';
            return `<div><dt class="font-semibold">${title}</dt><dd class="text-sm whitespace-pre-wrap break-words">${esc(text ?? 'Sin registrar')}</dd></div>`;
        }).join('')}</dl>`;
    }
    function serverRecord(op, working=data) {
        if(working.removals?.[op.module]?.[op.record_uuid])return null;
        return [op.result?.current,working.base[op.module][op.record_uuid]].filter(Boolean).sort((a,b)=>b.version-a.version)[0];
    }
    const connected = () => navigator.onLine && !connectionLost;
    const coordinate = (name, callback) => navigator.locks ? navigator.locks.request(name, callback) : callback();
    const valid = () => !!vault && !!key;
    function indicator() {
        if (!$('offline-indicator')) return;
        const offline = !connected() && !!key && !!data;
        const pending=!!data?.queue.length, blocked=data?.queue.some(op=>['error','conflict'].includes(op.status));
        $('offline-indicator').hidden = !offline && !uploading && !pending && !(syncFailed&&key&&data);
        $('offline-indicator-text').textContent = offline ? 'Estás usando BoviSoft sin conexión' : uploading ? 'Sincronizando cambios…' : blocked ? 'Hay cambios pendientes de revisión' : syncFailed ? 'No se pudo completar la sincronización' : 'Cambios pendientes de sincronizar';
        $('offline-indicator-icon').className = offline ? 'fa-solid fa-cloud-arrow-down mr-1' : uploading ? 'fa-solid fa-arrows-rotate fa-spin mr-1' : 'fa-solid fa-clock mr-1';
    }
    function feedback() {
        const root = $('offline-feedback');
        if (root) root.hidden = $('offline-notice').hidden && $('offline-error').hidden && !$('offline-changes').childElementCount;
    }
    function notice(message = '') {
        if (!$('offline-notice')) return;
        clearTimeout(noticeTimer);
        $('offline-notice').textContent = message; $('offline-notice').hidden = !message;
        if (message) noticeTimer = setTimeout(() => { if ($('offline-notice')) $('offline-notice').hidden = true; feedback(); },5000);
        feedback();
    }
    function error(message = '') {
        if (!$('offline-error')) return;
        $('offline-error').textContent = message; $('offline-error').hidden = !message; feedback();
    }
    function applyProfile(profile) {
        document.querySelectorAll('[data-account-field]').forEach(node=>node.textContent=profile?.[node.dataset.accountField]||'');
        document.querySelectorAll('[data-account-avatar]').forEach(node=>{
            if(profile?.photoData){const img=document.createElement('img');img.src=profile.photoData;img.alt='Foto';img.className='w-full h-full object-cover';node.replaceChildren(img);}
            else if(!profile || shell)node.innerHTML='<i class="fa-solid fa-user text-white text-lg"></i>';
        });
    }
    async function accountProfile() {
        const profile={...session.profile};
        if(data?.profile?.photo_url===profile.photo_url)profile.photoData=data.profile.photoData;
        if(profile.photo_url && !profile.photoData){
            try{
                const url=new URL(profile.photo_url,location.origin);
                if(url.origin===location.origin){const response=await fetch(url,{cache:'no-store'});const blob=await response.blob();
                    if(response.ok&&/^image\/(png|jpeg|webp|avif|gif)$/.test(blob.type)&&blob.size<2000000)profile.photoData=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(reader.result);reader.onerror=reject;reader.readAsDataURL(blob);});}
            }catch{/* Keep the account's text and the existing avatar if its image is unavailable. */}
        }
        return profile;
    }
    async function nativeViews() {
        const account=String(session.account_id),result=await api('/views');
        if(String(result.account_id)!==account)throw new Error('La cuenta cambió durante la actualización de las vistas.');
        for(const module of ['fincas','animales'])for(const kind of ['index','create','show','edit','index-empty','create-empty']){
            if(typeof result.views?.[module]?.[kind]!=='string')throw new Error('Faltan vistas habituales. La copia anterior se conserva.');
            const doc=new DOMParser().parseFromString(result.views[module][kind],'text/html'),page=extractPage(doc,'/'+module);
            if(!page)throw new Error('La vista habitual está incompleta. La copia anterior se conserva.');
            const root=doc.querySelector('main');root.innerHTML=page.html;
            result.views[module][kind]=root.outerHTML;
        }
        return result.views;
    }
    function lock(message = 'Inicia sesión con Internet para acceder a los datos de tu cuenta.') {
        applyProfile(null);
        key = null; data = null; editing = null; clearTimeout(syncTimer); active = false; uploading = false;
        delete document.body.dataset.offlineReady;
        if (localView || shell) main.innerHTML = '<div class="glass-effect rounded-xl p-6"><h1 class="text-3xl font-bold">BOVISOFT</h1><p class="mt-3">'+esc(message)+'</p><a href="/login" class="text-red-800 underline">Iniciar sesión</a></div>';
        if ($('offline-changes')) $('offline-changes').replaceChildren();
        notice(); error(); indicator();
    }
    async function assertAccount() {
        const current = await (await workspaceStore()).security();
        if (!vault || current.accountId !== vault.accountId || current.epoch !== epoch || current.pendingLogout) {
            lock('La sesión cambió. Los pendientes de la cuenta anterior se conservan.');
            throw new Error('Inicia sesión con la cuenta original para recuperar sus pendientes.');
        }
    }
    const actor = () => ({accountId:vault?.accountId,epoch});
    async function assertActor(expected) {
        if(!key || !sameActor(actor(),expected))throw syncError('BOVI_RUN_SUPERSEDED','La cuenta cambió. Los pendientes anteriores se conservan.');
        const current=await (await workspaceStore()).security();
        if(!sameActor(current,expected)||current.pendingLogout||!sameActor(actor(),expected))throw syncError('BOVI_RUN_SUPERSEDED','La sesión cambió. Los pendientes anteriores se conservan.');
    }
    async function restore() {
        const security = await (await workspaceStore()).security();
        const stored = security.accountId && !security.pendingLogout && await (await workspaceStore()).workspace(security.accountId);
        if (!stored?.automaticKey) return false;
        const decoded = await readVault(stored);
        const now = await (await workspaceStore()).security();
        if (now.accountId !== stored.accountId || now.epoch !== security.epoch || now.pendingLogout) return false;
        vault = stored; data = decoded; key = stored.automaticKey; epoch = now.epoch; active = true;
        applyProfile(data.profile);
        document.body.dataset.offlineReady = 'true';
        return true;
    }
    async function api(path, body = null, production = false, signal = undefined) {
        const response = await fetch((production ? '/api/offline/v1/produccion' : endpoint) + path, {
            method: body === null ? 'GET' : 'POST', credentials:'same-origin', cache:'no-store', signal,
            headers: {'Accept':'application/json', ...(body === null ? {} : {'Content-Type':'application/json', 'X-CSRF-TOKEN':session?.csrf_token || ''})},
            ...(body === null ? {} : {body: JSON.stringify(body)}),
        });
        if (!response.ok) {
            const details = await response.json().catch(() => ({}));
            const failure = new Error(details.message || 'No se pudo completar la sincronización. Los pendientes se conservan.');
            failure.status = response.status;
            failure.delay = retryAfterMs(response.headers.get('Retry-After'));
            throw failure;
        }
        return response.json();
    }
    function projected(source = data) {
        const result = structuredClone(source.base);
        for (const op of source.queue) {
            const previous = result[op.module][op.record_uuid] || op.before || {};
            result[op.module][op.record_uuid] = {...previous, ...op.data, record_uuid:op.record_uuid,
                version:op.type === 'create' ? 1 : op.base_version + 1, deleted:op.type === 'delete',
                codigo:previous.codigo || 'Código al sincronizar'};
        }
        return result;
    }
    function findRecord(module,id,source=data) {
        if(!source)return null;
        const rows=projected(source)[module];
        return rows[id] || Object.values(rows).find(row=>String(row.id)===String(id)) || null;
    }
    function canonicalPath(path) {
        const url=new URL(path,location.href), match=url.pathname.match(/^\/(fincas|animales)\/([^/]+)(\/edit)?$/);
        if(match){const row=findRecord(match[1],match[2]);if(row?.id)url.pathname='/'+match[1]+'/'+row.id+(match[3]||'');}
        return url.pathname+url.search;
    }
    async function seedCurrentRecord() {
        const script=$('offline-record-data');
        if(!script||!data||!key)return;
        let supplied;try{supplied=JSON.parse(script.textContent);}catch{return;}
        await mutate(working=>{
            for(const {module,record} of supplied){
                if(!working.base[module]||!record?.record_uuid)continue;
                const current=working.base[module][record.record_uuid];
                if(!current||current.version<record.version)working.base[module][record.record_uuid]=record;
            }
        });
    }
    function extractPage(doc,path,live=false) {
        const element=doc.querySelector('main');if(!element)return null;
        const clone=element.cloneNode(true);
        if(live){
            const originals=element.querySelectorAll('canvas');
            clone.querySelectorAll('canvas').forEach((canvas,i)=>{
                try{const img=document.createElement('img');img.src=originals[i].toDataURL();img.alt='Gráfica guardada';img.className=canvas.className;canvas.replaceWith(img);}catch{canvas.remove();}
            });
        }
        clone.querySelectorAll('script,meta,input[name="_token"],input[type="password"]').forEach(node=>node.remove());
        clone.querySelectorAll('*').forEach(node=>{
            for(const attribute of [...node.attributes])if(attribute.name.startsWith('on'))node.removeAttribute(attribute.name);
            if(node.tagName==='IMG'&&node.src&&!node.src.startsWith('data:')&&new URL(node.src,location.href).origin!==location.origin)node.remove();
        });
        return {html:clone.innerHTML,title:doc.title,path,module:element.dataset.offlineModule||'',uuid:element.dataset.offlineRecord||'',version:Number(element.dataset.offlineVersion||0)};
    }
    async function cacheCurrentPage() {
        if(!data||!key||localView||shell||!/^\/(dashboard|fincas|animales|produccion)(\/|$)/.test(location.pathname))return;
        const page=extractPage(document,location.pathname+location.search,true);
        if(!page)return;
        await mutate(working=>{working.pages||={};working.pages[page.path]=page;});
    }
    async function warmPages() {
        if(warmPromise||!connected()||!key)return warmPromise;
        warmPromise=(async()=>{
            const paths=new Set(['/dashboard','/fincas','/fincas/create','/animales','/animales/create']);
            if(/^\/(fincas|animales)\/\d+(\/edit)?$/.test(location.pathname)){
                paths.add(location.pathname.replace(/\/edit$/,''));paths.add(location.pathname.replace(/\/edit$/,'')+'/edit');
            }
            for(const path of paths){
                if(!key||!connected())break;
                if(data.pages?.[path])continue;
                try{
                    const response=await fetch(path,{cache:'no-store',headers:{Accept:'text/html'}});
                    if(!response.ok||response.headers.get('X-Bovisoft-Account')!==vault.accountId)continue;
                    const doc=new DOMParser().parseFromString(await response.text(),'text/html');
                    const page=extractPage(doc,path);if(!page)continue;
                    await mutate(working=>{working.pages||={};working.pages[path]=page;});
                }catch{break;}
            }
        })().finally(()=>{warmPromise=null;});
        return warmPromise;
    }
    function hydrateFields(row) {
        const model=projected();
        main.querySelectorAll('[data-offline-field]').forEach(node=>{
            const field=node.dataset.offlineField;let value=row[field];
            if(field==='nombre')value=value||row.codigo||'Sin nombre';
            if(field==='finca_uuid')value=model.fincas[row.finca_uuid]?.nombre;
            if(field==='finca_codigo')value=model.fincas[row.finca_uuid]?.codigo;
            if(field==='potrero_id')value=data.references?.potreros?.find(p=>String(p.id)===String(row.potrero_id))?.nombre;
            if(field==='total_animales')value=Object.values(model.animales).filter(a=>!a.deleted&&a.finca_uuid===row.record_uuid).length;
            if(field==='coordinates')value=row.latitud!=null&&row.longitud!=null?Number(row.latitud).toFixed(6)+', '+Number(row.longitud).toFixed(6):'Sin registrar';
            if(['created_at','updated_at'].includes(field))value=value?new Date(value).toLocaleString('en-GB',{timeZone:'America/Bogota',day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).replace(',',''):'Sin registrar';
            if(field==='estado'||field==='tipo'||field==='sexo')value=value?value[0].toUpperCase()+value.slice(1):value;
            if(field==='sexo'){const icon=node.parentElement.querySelector('i');if(icon){icon.classList.toggle('fa-mars',row.sexo==='macho');icon.classList.toggle('fa-venus',row.sexo!=='macho');}}
            if(field==='tipo'){
                const color={vaca:'pink',toro:'blue',ternero:'green',novilla:'purple'}[row.tipo]||'purple';
                node.className=node.className.replace(/bg-(pink|blue|green|purple)-500/g,'').trim()+' bg-'+color+'-500';
            }
            if(field==='estado'){
                const small=node.classList.contains('text-xs'),color={activo:'green',vendido:'blue',muerto:'gray',inactivo:'yellow'}[row.estado]||'yellow';
                node.className=node.className.replace(/bg-(green|blue|gray|yellow)-\d+|text-(green|blue|gray|yellow)-\d+|text-white/g,'').trim()+(small?` bg-${color}-${color==='gray'?'200':'100'} text-${color}-${color==='gray'?'700':'800'}`:` bg-${color}-${color==='gray'?'600':'500'} text-white`);
            }
            if(field==='edad'){
                if(row.fecha_nacimiento){const born=new Date(row.fecha_nacimiento+'T12:00:00'),now=new Date(),months=Math.max(0,(now.getFullYear()-born.getFullYear())*12+now.getMonth()-born.getMonth());value=months>=12?Math.floor(months/12)+' años':months+' meses';}else value='N/A';
            }
            if(field==='activa'){value=row.activa?'Activa':'Inactiva';node.classList.toggle('bg-green-500',!!row.activa);node.classList.toggle('bg-gray-500',!row.activa);}
            if(field==='operativa'){value=row.activa?'Operativa':'Inactiva';node.classList.toggle('text-green-600',!!row.activa);node.classList.toggle('text-gray-600',!row.activa);}
            if(field==='peso_actual'&&value)value+=' kg';
            if(field==='fecha_nacimiento'&&value)value=value.split('-').reverse().join('/');
            const empty={numero:'—',raza:'No especificada',peso_actual:'N/A',area:'N/A'};
            node.textContent=value===null||value===undefined||value===''?(empty[field]||'Sin registrar'):value;
        });
        const coordinates=row.latitud!=null&&row.longitud!=null;
        main.querySelectorAll('[data-offline-if]').forEach(node=>{
            const field=node.dataset.offlineIf,value=field==='has_coordinates'?coordinates:field==='has_update'?row.updated_at&&row.created_at&&Date.parse(row.updated_at)!==Date.parse(row.created_at):row[field];
            node.hidden=!value;
        });
        main.querySelectorAll('[data-offline-map]').forEach(node=>node.parentElement.hidden=!coordinates);
        const toggle=$('texto-estado');if(toggle&&'activa'in row){
            toggle.textContent=row.activa?'Desactivar':'Activar';
            const control=$('btn-toggle-estado');
            for(const style of ['bg-gray-600','hover:bg-gray-700'])control.classList.toggle(style,!!row.activa);
            for(const style of ['bg-green-600','hover:bg-green-700'])control.classList.toggle(style,!row.activa);
            const icon=control.querySelector('i');if(icon){icon.classList.toggle('fa-pause-circle',!!row.activa);icon.classList.toggle('fa-play-circle',!row.activa);}
        }
        const labels={Nombre:'nombre',Número:'numero',Raza:'raza',Color:'color',Dirección:'direccion',Municipio:'municipio',Departamento:'departamento',Latitud:'latitud',Longitud:'longitud'};
        main.querySelectorAll('p').forEach(label=>{const field=labels[label.textContent.trim()];if(field&&field in row&&label.nextElementSibling&&!label.nextElementSibling.hasAttribute('data-offline-field')&&['P','SPAN'].includes(label.nextElementSibling.tagName))label.nextElementSibling.textContent=row[field]??'Sin registrar';});
        if(row.finca_uuid){const farm=model.fincas[row.finca_uuid];if(farm)main.querySelectorAll('a[href]').forEach(anchor=>{const url=new URL(anchor.href,location.href);if(/^\/fincas\/[^/]+$/.test(url.pathname)&&anchor.origin===location.origin)anchor.href='/fincas/'+(farm.id||farm.record_uuid);});}
        for(const field of ['observaciones','descripcion'])if(row[field]&&!main.querySelector('[data-offline-field="'+field+'"]')){
            const module=row.finca_uuid?'animales':'fincas',html=data.views?.[module]?.show;
            if(html){const doc=new DOMParser().parseFromString(html,'text/html'),section=doc.querySelector('[data-offline-if="'+field+'"]');
                if(section){section.querySelector('[data-offline-field="'+field+'"]').textContent=row[field];(main.querySelector('.animate-slide-up')||main).append(section);}}
        }
        main.querySelectorAll('input[name="base_version"]').forEach(input=>input.value=row.version);
    }
    function renderCachedPage(force, supplied = null) {
        const path=location.pathname+location.search, cached=supplied || data.pages?.[path] || data.pages?.[location.pathname];
        if(!cached)return false;
        if(!force&&main.querySelector('form')&&dirtyForms.has(main.querySelector('form')))return true;
        const row=cached.module&&cached.uuid&&findRecord(cached.module,cached.uuid);
        if(cached.uuid&&(!row||row.deleted))return false;
        const controls=!force?[...main.querySelectorAll('#busqueda,#buscar-finca,#filtro-tipo,#filtro-finca,#filtro-estado,#ordenar')].map(input=>[input.id,input.value]):[];
        main.id='workspace-main';main.innerHTML=cached.html;document.title=cached.title;document.body.dataset.offlineRendered='true';
        main.dataset.offlineModule=cached.module;main.dataset.offlineRecord=cached.uuid;
        if(row)hydrateFields(row);
        if(!cached.uuid&&!hydrateCollection(location.pathname))return false;
        main.querySelectorAll('a[href]').forEach(anchor=>{const url=new URL(anchor.href,location.href);if(url.origin===location.origin)anchor.href=canonicalPath(url.pathname+url.search);});
        hydrateNormalForm(row,cached.module);
        // The normal scripts remain outside the cached main. Delegate these actions
        // to the same queue after replacing the main, avoiding stale inline handlers.
        for(const [id,value]of controls){const input=main.querySelector('#'+id);if(input){input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}));}}
        enableOfflineForm();
        return true;
    }
    function hydrateCollection(path) {
        const model=projected(), farms=Object.values(model.fincas).filter(f=>!f.deleted);
        const animals=Object.values(model.animales).filter(a=>!a.deleted&&model.fincas[a.finca_uuid]&&!model.fincas[a.finca_uuid].deleted);
        const upper=value=>value?String(value)[0].toUpperCase()+String(value).slice(1):'N/A';
        const text=(root,selector,value)=>{const node=root.querySelector(selector);if(node){const icon=node.querySelector(':scope > i');node.replaceChildren(...(icon?[icon,document.createTextNode(' ')]:[]),document.createTextNode(value??'N/A'));}};
        const age=row=>{
            if(!row.fecha_nacimiento)return'N/A';const born=new Date(row.fecha_nacimiento+'T12:00:00'),now=new Date();
            const months=Math.max(0,(now.getFullYear()-born.getFullYear())*12+now.getMonth()-born.getMonth());
            return months>=12?Math.floor(months/12)+' años':months+' meses';
        };
        const patchLinks=(root,module,row)=>{
            const key=row.id||row.record_uuid;
            root.querySelectorAll('a[href]').forEach(a=>{const url=new URL(a.href,location.href);if(new RegExp('^/'+module+'/[^/]+').test(url.pathname))a.href=url.pathname.replace(new RegExp('^/'+module+'/[^/]+'),'/'+module+'/'+key)+url.search;});
            root.querySelectorAll('form[action]').forEach(form=>{form.action='/'+module+'/'+key;form.removeAttribute('onsubmit');form.querySelectorAll('[name="base_version"]').forEach(input=>input.value=row.version);});
        };
        if(path==='/animales'){
            const farmFilter=main.querySelector('#filtro-finca');if(farmFilter){farmFilter.replaceChildren(new Option('Todas las fincas','todas'));for(const farm of farms)farmFilter.add(new Option(farm.nombre,String(farm.id||farm.record_uuid)));}
            const body=main.querySelector('#tabla-animales tbody'),template=body?.querySelector('tr');
            if(!template)return animals.length===0;
            const pattern=template.cloneNode(true);body.replaceChildren();
            for(const row of [...animals].sort((a,b)=>(b.id||Number.MAX_SAFE_INTEGER)-(a.id||Number.MAX_SAFE_INTEGER))){
                const node=pattern.cloneNode(true),cells=node.querySelectorAll('td');node.hidden=false;node.style.display='';
                node.dataset.offlineRow=row.record_uuid;node.dataset.nombre=(row.nombre||'').toLocaleLowerCase('es');node.dataset.codigo=(row.codigo||'').toLocaleLowerCase('es');node.dataset.tipo=row.tipo;node.dataset.finca=String(model.fincas[row.finca_uuid]?.id||row.finca_uuid);
                text(cells[0],'span',row.codigo||'Pendiente');text(cells[1],'span',row.nombre||'Sin nombre');text(cells[2],'span',upper(row.tipo));text(cells[3],'span',upper(row.sexo));
                cells[4].textContent=row.raza||'N/A';text(cells[5],'span',model.fincas[row.finca_uuid]?.nombre);cells[6].textContent=age(row);text(cells[7],'span',row.peso_actual?row.peso_actual+' kg':'N/A');
                const type=cells[2].querySelector('span'),color={vaca:'pink',toro:'blue',ternero:'green',novilla:'purple'}[row.tipo]||'purple';
                type.className=type.className.replace(/from-\w+-600|to-\w+-800/g,'').trim()+` from-${color}-600 to-${color}-800`;
                const typeIcon=type.querySelector('i');if(typeIcon)typeIcon.className='fas fa-'+(row.tipo==='vaca'?'venus':row.tipo==='toro'?'mars':'baby')+' mr-1';
                const sexIcon=cells[3].querySelector('i');if(sexIcon)sexIcon.className=row.sexo==='macho'?'fas fa-mars text-blue-600 mr-1':'fas fa-venus text-pink-600 mr-1';
                patchLinks(node,'animales',row);body.append(node);
            }
            const filter=()=>{
                const query=(main.querySelector('#busqueda,#buscar-animal,#buscar')?.value||'').toLocaleLowerCase('es');
                const type=main.querySelector('#filtro-tipo')?.value, farm=main.querySelector('#filtro-finca')?.value;
                body.querySelectorAll('tr').forEach(row=>row.hidden=!!(query&&!`${row.dataset.nombre} ${row.dataset.codigo}`.includes(query)||type&&type!=='todos'&&row.dataset.tipo!==type||farm&&farm!=='todas'&&row.dataset.finca!==farm));
                const noResults=main.querySelector('#sin-resultados');if(noResults)noResults.classList.toggle('hidden',[...body.children].some(row=>!row.hidden));
            };
            main.querySelectorAll('input[type="text"],input[type="search"],select').forEach(input=>{input.addEventListener('input',filter);input.addEventListener('change',filter);});
        }
        if(path==='/fincas'){
            const grid=main.querySelector('#fincas-grid'),template=grid?.querySelector('.finca-card');
            if(!template)return farms.length===0;
            const pattern=template.cloneNode(true);grid.replaceChildren();
            for(const row of [...farms].sort((a,b)=>(b.id||Number.MAX_SAFE_INTEGER)-(a.id||Number.MAX_SAFE_INTEGER))){
                const node=pattern.cloneNode(true);node.hidden=false;node.style.display='';node.dataset.offlineOrder=String(grid.childElementCount);node.dataset.offlineRow=row.record_uuid;node.dataset.nombre=(row.nombre||'').toLocaleLowerCase('es');node.dataset.codigo=(row.codigo||'').toLocaleLowerCase('es');node.dataset.ubicacion=[row.municipio,row.departamento].join(' ').toLocaleLowerCase('es');node.dataset.activa=String(!!row.activa);node.dataset.area=row.area||0;
                text(node,'h3',row.nombre);text(node,'.bg-gradient-to-r p',row.codigo||'Pendiente');text(node,'span.rounded-full',row.activa?'Activa':'Inactiva');
                const badge=node.querySelector('span.rounded-full');if(badge){badge.classList.toggle('bg-green-500',!!row.activa);badge.classList.toggle('bg-gray-500',!row.activa);const icon=badge.querySelector('i');if(icon){icon.classList.toggle('fa-check-circle',!!row.activa);icon.classList.toggle('fa-pause-circle',!row.activa);}}
                const description=node.querySelector('.line-clamp-2');if(description){description.textContent=row.descripcion||'';description.parentElement.hidden=!row.descripcion;}
                text(node,'.border-t-2 p',row.created_at?'Registrada el '+new Date(row.created_at).toLocaleDateString('es-CO',{timeZone:'America/Bogota'}):'Guardado en este dispositivo');
                for(const label of node.querySelectorAll('p')){
                    if(label.textContent.trim()==='Área'&&label.nextElementSibling)label.nextElementSibling.textContent=(row.area||0)+' hectáreas';
                    if(label.textContent.trim()==='Animales'&&label.nextElementSibling)label.nextElementSibling.textContent=animals.filter(a=>a.finca_uuid===row.record_uuid).length+' cabezas';
                    if(label.textContent.trim()==='Ubicación'&&label.nextElementSibling)label.nextElementSibling.textContent=[row.municipio,row.departamento].filter(Boolean).join(', ');
                }
                patchLinks(node,'fincas',row);grid.append(node);
            }
            const filter=()=>{
                const query=(main.querySelector('#buscar-finca')?.value||'').toLocaleLowerCase('es'),status=main.querySelector('#filtro-estado')?.value;
                grid.querySelectorAll('.finca-card').forEach(row=>row.hidden=!!(query&&!`${row.dataset.nombre} ${row.dataset.codigo} ${row.dataset.ubicacion}`.includes(query)||status==='activas'&&row.dataset.activa!=='true'||status==='inactivas'&&row.dataset.activa!=='false'));
            };
            main.querySelectorAll('input,select').forEach(input=>{input.addEventListener('input',filter);input.addEventListener('change',filter);});
            main.querySelector('#ordenar')?.addEventListener('change',event=>{
                const rows=[...grid.children];rows.sort((a,b)=>event.target.value==='nombre'?a.dataset.nombre.localeCompare(b.dataset.nombre,'es'):event.target.value==='area'?Number(b.dataset.area)-Number(a.dataset.area):Number(a.dataset.offlineOrder)-Number(b.dataset.offlineOrder));rows.forEach(row=>grid.append(row));
            });
        }
        const counts={Total:animals.length,'Total Animales':animals.length,'Total Fincas':farms.length,'Fincas Activas':farms.filter(f=>f.activa).length,'Área Total':farms.reduce((sum,f)=>sum+Number(f.area||0),0).toFixed(1),Vacas:animals.filter(a=>a.tipo==='vaca').length,Toros:animals.filter(a=>a.tipo==='toro').length,Novillas:animals.filter(a=>a.tipo==='novilla').length,Terneros:animals.filter(a=>a.tipo==='ternero').length};
        main.querySelectorAll('p').forEach(label=>{const value=counts[label.textContent.trim()];if(value!==undefined&&label.nextElementSibling?.tagName==='H3')label.nextElementSibling.textContent=value;});
        if(main.querySelector('#contador-fincas-card'))main.querySelector('#contador-fincas-card').textContent=farms.filter(f=>f.activa).length;
        return true;
    }
    function hydrateNormalForm(row,module) {
        const form=main.querySelector('form[action]');
        if(!form||form.querySelector('input[name="_method"]')?.value==='DELETE')return;
        if(row){
            for(const input of form.elements){
                if(input.name in row&&input.name!=='codigo'&&input.type!=='hidden'){
                    if(input.type==='checkbox')input.checked=!!row[input.name];else input.value=row[input.name]??'';
                }
            }
        }
        const code=form.querySelector('input[readonly]');if(code)code.value=row?.codigo||'Código al sincronizar';
        if(module==='animales'||location.pathname.startsWith('/animales')){
            const select=form.querySelector('[name="finca_id"]');
            if(select){
                const selected=row?.finca_uuid || new URLSearchParams(location.search).get('finca');
                const prior=select.value;select.replaceChildren(new Option('Selecciona una finca',''));
                for(const farm of Object.values(projected().fincas).filter(f=>!f.deleted&&(f.activa||f.record_uuid===selected)))select.add(new Option(farm.nombre,String(farm.id||farm.record_uuid)));
                const farm=selected&&findRecord('fincas',selected);select.value=farm?String(farm.id||farm.record_uuid):prior;
                populatePotreros(form,row?.potrero_id);
            }
        }
    }
    function populatePotreros(form,selected) {
        const select=form.querySelector('[name="potrero_id"]'), farmInput=form.querySelector('[name="finca_id"]');
        if(!select||!farmInput)return;
        const farm=findRecord('fincas',farmInput.value);const prior=selected??select.value;
        select.replaceChildren(new Option('Sin potrero',''));select.disabled=false;
        for(const p of data.references.potreros.filter(p=>p.finca_uuid===farm?.record_uuid))select.add(new Option(p.nombre,String(p.id)));
        select.value=prior??'';
        const message=form.querySelector('#potrero-message');if(message)message.hidden=true;
    }
    function enableOfflineForm() {
        if(connected()||!data)return;
        main.querySelectorAll('form').forEach(form=>{
            if(form.querySelector('[name="finca_id"]'))populatePotreros(form);
        });
    }
    document.addEventListener('change',event=>{
        if(!connected()&&event.target.name==='finca_id'){
            event.stopImmediatePropagation();populatePotreros(event.target.form);
        }
    },true);
    async function queueValues(module,row,values) {
        const uuid=row?.record_uuid||crypto.randomUUID();
        await mutate(working=>{
            const current=projected(working);
            if(row&&current[module][uuid]?.version!==row.version)throw new Error('El registro cambió. Revisa los datos actuales antes de guardar.');
            if(module==='animales'&&(!current.fincas[values.finca_uuid]||current.fincas[values.finca_uuid].deleted))throw new Error('Selecciona una finca disponible.');
            const dependsOn=working.queue.filter(op=>op.record_uuid===uuid||module==='animales'&&op.module==='fincas'&&op.record_uuid===values.finca_uuid).map(op=>op.operation_uuid);
            working.queue.push({operation_uuid:crypto.randomUUID(),record_uuid:uuid,module,type:row?'update':'create',...(row?{base_version:row.version}:{}),data:values,before:row&&structuredClone(row),status:'pending',started:false,dependsOn});
        });
        notice('Guardado en este dispositivo. Pendiente de sincronizar.');
        return uuid;
    }
    async function saveNormalForm(form,module,row) {
        const values=Object.fromEntries(new FormData(form));
        const version=Number(values.base_version || row?.version || 0);
        if(row&&version&&version!==row.version)throw new Error('El registro cambió desde que abriste la edición. Tus valores siguen en el formulario.');
        for(const field of ['_token','_method','base_version','codigo'])delete values[field];
        if(module==='animales'){
            const farm=findRecord('fincas',values.finca_id);
            if(!farm)throw new Error('Selecciona una finca de tu cuenta.');
            values.finca_uuid=farm.record_uuid;delete values.finca_id;
            values.potrero_id=values.potrero_id?Number(values.potrero_id):null;
        }else {
            const checkbox=form.querySelector('[name="activa"][type="checkbox"]');
            values.activa=checkbox?checkbox.checked:values.activa===undefined?(row?.activa??true):['1','true','on'].includes(String(values.activa));
        }
        for(const field of ['area','fecha_nacimiento','peso_actual'])if(values[field]==='')values[field]=null;
        const uuid=await queueValues(module,row,values);
        navigate('/'+module+'/'+uuid);schedule(250);
    }
    function label(module, uuid) {
        return data.queue.some(op => op.module === module && op.record_uuid === uuid)
            ? 'Guardado en este dispositivo · Pendiente de sincronizar' : 'Sincronizado';
    }
    async function mutate(callback, metadata = {}, expectedRevision = null, guard = null) {
        const expected=guard?.actor || actor();
        return coordinate('bovisoft-workspace-edit', async () => {
          for(let attempt=0;attempt<8;attempt++) {
            await assertActor(expected);
            const current = await (await workspaceStore()).workspace(expected.accountId);
            const encryptionKey=current.automaticKey;
            const working = await readVault(current,encryptionKey);
            await assertActor(expected);
            if(expectedRevision && current.revision!==expectedRevision){data=working;vault=current;key=encryptionKey;return false;}
            const result = await callback(working);
            const saved = await seal(working, encryptionKey, {...current,...metadata});
            try { await (await workspaceStore()).commitWorkspace(saved, expected.epoch, current.revision, guard?.lease); }
            catch(failure){if(failure.code==='BOVI_REVISION_CHANGED')continue;throw failure;}
            await assertActor(expected);
            data = working; vault = saved;key=encryptionKey;
            if(localView){const path=canonicalPath(location.pathname+location.search);if(path!==location.pathname+location.search)history.replaceState({},'',path);}
            channel?.postMessage({accountId:vault.accountId, revision:vault.revision});
            renderControls(); renderRoute(false);
            return result;
          }
          throw new Error('Otra pestaña está guardando cambios. Tus datos se conservan; vuelve a intentar.');
        });
    }
    function applyDelta(working, delta, accountId=session?.account_id) {
        applyRemoteChanges(working,delta,accountId);
        const farmIds=new Set(Object.values(working.base.fincas).map(f=>Number(f.id)));
        working.production=(working.production||[]).filter(r=>farmIds.has(Number(r.finca_id)));
    }
    async function refreshProduction() {
        if(productionRefreshing||!connected()||!key||!session?.production_enabled)return;
        productionRefreshing=true;
        const accountId=vault.accountId,expectedEpoch=epoch;
        const ids=Object.values(data.base.fincas).map(f=>Number(f.id)).sort((a,b)=>a-b);
        try{
            const batches=[];
            for(let offset=0;offset<ids.length;offset+=50){
                const group=ids.slice(offset,offset+50);
                const old=data.productionBatches?.find(batch=>JSON.stringify(batch.ids)===JSON.stringify(group));
                let records=new Map((old?.records||[]).map(r=>[r.record_uuid,r])),cursor=old?.cursor,refs=old?.references;
                if(cursor){try{await api('/changes?cursor='+encodeURIComponent(cursor),null,true);}catch(f){if(f.status===410)cursor=null;else throw f;}}
                if(!cursor){
                    let page=await api('/snapshots',{finca_ids:group,history_days:90,page_size:100},true);
                    const snapshot=page.snapshot_uuid,total=page.total_records;records=new Map();
                    while(true){
                        if(String(page.account_id)!==accountId||page.snapshot_uuid!==snapshot||page.total_records!==total)throw new Error('La cuenta cambió.');
                        for(const row of page.records)records.set(row.record_uuid,row);
                        if(page.complete)break;
                        page=await api(`/snapshots/${snapshot}?page=${page.next_page}`,null,true);
                    }
                    if(records.size!==total)throw new Error('El historial no terminó de actualizarse.');
                    cursor=page.cursor;refs=page.references;
                }
                let delta;
                do{
                    delta=await api('/changes?cursor='+encodeURIComponent(cursor),null,true);
                    if(String(delta.account_id)!==accountId)throw new Error('La cuenta cambió.');
                    for(const change of delta.changes){if(change.action==='remove')records.delete(change.record_uuid);else records.set(change.record.record_uuid,change.record);}
                    const valid=new Set(delta.valid_record_uuids);for(const uuid of records.keys())if(!valid.has(uuid))records.delete(uuid);
                    cursor=delta.cursor;refs=delta.references;
                }while(delta.has_more);
                batches.push({ids:group,cursor,records:[...records.values()],references:refs});
            }
            if(!key||vault.accountId!==accountId||epoch!==expectedEpoch)return;
            const currentIds=Object.values(data.base.fincas).map(f=>Number(f.id)).sort((a,b)=>a-b);
            if(JSON.stringify(ids)!==JSON.stringify(currentIds))return;
            await mutate(working=>{
                working.productionBatches=batches;working.production=batches.flatMap(b=>b.records);
                working.productionReferences={fincas:[...new Map(batches.flatMap(b=>b.references.fincas).map(r=>[r.id,r])).values()],animales:[...new Map(batches.flatMap(b=>b.references.animales).map(r=>[r.id,r])).values()]};
            });
        }catch(failure){if(location.pathname==='/produccion')error('No se pudo actualizar el historial de Producción. Los datos anteriores se conservan y se reintentará.');}
        finally{productionRefreshing=false;}
    }
    async function refreshShell() {
        if (!key || !connected()) return;
        const response=await fetch('/offline/asset-manifest',{cache:'no-store',headers:{Accept:'application/json'}});
        if (!response.ok) throw new Error('No se pudo actualizar la interfaz. La copia anterior y los pendientes se conservan.');
        const manifest=await response.json();
        if (manifest.version===vault.shellVersion && await workerCall({type:'CHECK_SHELL',version:manifest.version})) return;
        await workerCall({type:'PREPARE_SHELL',manifest:{...manifest,automaticMode:true}});
        const views=await nativeViews();
        await mutate(working=>{working.views=views;}, {shellVersion:manifest.version});
    }
    async function automaticDownload() {
        if (preparing || !connected() || !session?.account_id || !session.workspace_enabled) return;
        preparing = true;
        try {
            const security = await (await workspaceStore()).security();
            const accountId = String(session.account_id);
            if (security.accountId !== accountId || security.pendingLogout) return;
            const initial = await (await workspaceStore()).workspace(accountId);
            const previous = initial && await readVault(initial);
            const response = await fetch('/offline/asset-manifest',{cache:'no-store',headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error('No se pudo guardar la interfaz. Se reintentará automáticamente.');
            const manifest = await response.json();
            await workerCall({type:'PREPARE_SHELL',manifest:{...manifest,automaticMode:true}});
            let snapshot = await api('/snapshots',{});
            const id = snapshot.snapshot_uuid, total = snapshot.total_records;
            const base = {fincas:{},animales:{}}; let seen = 0;
            while (true) {
                if (String(snapshot.account_id) !== accountId || snapshot.snapshot_uuid !== id || snapshot.total_records !== total) throw new Error('La cuenta cambió durante la actualización.');
                for (const row of snapshot.records) {
                    if (!base[row.module] || base[row.module][row.record_uuid]) throw new Error('La actualización contiene páginas repetidas.');
                    base[row.module][row.record_uuid] = row; seen++;
                }
                if (snapshot.complete) break;
                snapshot = await api(`/snapshots/${id}?page=${snapshot.next_page}`);
            }
            if (seen !== total || !snapshot.cursor) throw new Error('La actualización no terminó. Se reintentará automáticamente.');
            const views=await nativeViews(),profile=await accountProfile();
            const working = {schema:2,deviceUuid:previous?.deviceUuid || crypto.randomUUID(),base,queue:previous?.queue || [],archive:previous?.archive || [],
                views,profile,
                removals:structuredClone(previous?.removals||{fincas:{},animales:{}}),
                pages:previous?.pages || {},references:snapshot.references,cursor:snapshot.cursor,production:previous?.production || [],productionReferences:previous?.productionReferences,productionBatches:previous?.productionBatches||[],productionDays:90,lastSync:previous?.lastSync || null};
            for(const module of ['fincas','animales']){
                for(const uuid of Object.keys(previous?.base[module]||{}))if(!base[module][uuid])working.removals[module][uuid]=true;
                for(const op of working.queue)if(op.module===module&&op.started&&!base[module][op.record_uuid])working.removals[module][op.record_uuid]=true;
                for(const uuid of Object.keys(base[module]))delete working.removals[module][uuid];
            }
            let delta;
            do { delta = await api('/changes?cursor='+encodeURIComponent(working.cursor));
                if (String(delta.account_id) !== accountId) throw new Error('La cuenta cambió.');
                applyDelta(working,delta);
            } while (delta.has_more);
            const confirmed = await (await workspaceStore()).security();
            if (confirmed.epoch !== security.epoch || confirmed.accountId !== accountId || confirmed.pendingLogout) return;
            const meta = {accountId,generation:initial?.generation || crypto.randomUUID(),preparedAt:Date.now(),serverOffset:0,shellVersion:manifest.version};
            const saved = initial ? await seal(working,initial.automaticKey,{...initial,...meta}) : (await createAutomaticVault(working,meta)).envelope;
            await (await workspaceStore()).commitWorkspace(saved,security.epoch,initial?.revision || null);
            await restore();
            await cacheCurrentPage();
            if (shell) renderRoute(true);
            navigator.storage?.persist?.().catch(()=>{});
        } catch (failure) {
            if (!navigator.onLine || failure instanceof TypeError) connectionLost = true;
            if (!data && !connected()) error('La conexión se interrumpió antes de completar la copia local. Los datos guardados anteriormente se conservan.');
        } finally {
            preparing = false; indicator();
            if(data)schedule(500);
            else if(navigator.onLine){clearTimeout(syncTimer);syncTimer=setTimeout(()=>{connectionLost=false;boot().catch(f=>error(f.message));},5000);}
        }
    }
    function renderControls() {
        indicator();
        if (!data || !key || !$('offline-changes')) return;
        const signature = JSON.stringify(data.queue);
        if (queueSignature !== signature) {
            $('offline-changes').innerHTML = data.queue.filter(op=>op.type==='delete'&&!op.started || ['conflict','error'].includes(op.status)).map(op=>{
                const current = serverRecord(op);
                const failed = ['conflict','error'].includes(op.status);
                const details = op.result?.errors ? Object.values(op.result.errors).flat().join(' ') : op.result?.error || '';
                return `<article class="bg-white text-gray-800 rounded-xl p-4 shadow-lg" data-operation="${esc(op.operation_uuid)}"><p class="font-semibold">${esc(op.data.nombre || op.before?.nombre || (op.module==='fincas'?'Finca':'Animal'))}: ${failed ? op.status==='conflict'?'El registro cambió en otro dispositivo.':'No se pudo sincronizar el cambio.' : 'Eliminación pendiente.'}</p>
                    ${!failed ? button('Deshacer eliminación',`data-undo="${op.operation_uuid}"`,true) : `<p class="mt-2 text-red-800">${esc(details)} Tus cambios se conservan.</p><details class="mt-3"><summary class="cursor-pointer font-semibold">Revisar conflicto</summary><div class="grid sm:grid-cols-3 gap-4 mt-3">${[['Al editar',op.before],['Tus cambios',projected()[op.module][op.record_uuid]],['Servidor',current]].map(([title,row])=>`<div class="min-w-0"><h3 class="font-bold">${title}</h3>${comparison(row)}</div>`).join('')}</div><div class="flex flex-wrap gap-3 mt-4">${button('Usar versión del servidor',`data-resolve="remote" data-id="${op.operation_uuid}"`,true)}${current&&!current.deleted&&op.type!=='create'?button('Conservar mis cambios revisados',`data-resolve="local" data-id="${op.operation_uuid}"`):button('Crear como registro nuevo',`data-resolve="copy" data-id="${op.operation_uuid}"`)}</div></details>`}</article>`;
            }).join('');
            queueSignature = signature;
        }
        feedback();
    }
    function navigate(path) { localView = true; path = canonicalPath(path); history.pushState({}, '', path); editing = null; renderRoute(true); window.scrollTo(0,0); }
    function renderRoute(force) {
        if (!data || !key || !active || !localView && !shell) return;
        const match=location.pathname.match(/^\/(fincas|animales)(?:\/([^/]+))?(?:\/(edit))?$/);
        if (match && data.views) {
            const [,module,id,edit]=match, model=projected();
            const row=id&&id!=='create'?findRecord(module,id):null;
            let kind=!id?'index':id==='create'?'create':edit?'edit':'show';
            if (['show','edit'].includes(kind)&&(!row||row.deleted)) {
                notice('El registro ya no está disponible. Los cambios pendientes se conservan.');
                history.replaceState({},'', '/'+module);return renderRoute(true);
            }
            const rows=Object.values(model[module]).filter(r=>!r.deleted);
            if(kind==='index'&&!rows.length)kind='index-empty';
            if(kind==='create'&&module==='animales'&&!Object.values(model.fincas).some(f=>!f.deleted&&f.activa))kind='create-empty';
            const html=data.views[module]?.[kind];
            if (html) {
                const doc=new DOMParser().parseFromString(html,'text/html');
                const page=extractPage(doc,location.pathname+location.search);
                if(page){
                    page.uuid=row?.record_uuid||'';page.module=module;
                    page.title=row?.nombre||row?.codigo||(module==='fincas'?'Mis Fincas':'Ganado');
                    const container=doc.querySelector('main');
                    const farm=module==='fincas'?row:model.fincas[row?.finca_uuid];
                    container.querySelectorAll('a[href],form[action]').forEach(node=>{
                        const attribute=node.tagName==='A'?'href':'action',url=new URL(node.getAttribute(attribute),location.origin);
                        if(url.origin!==location.origin)return;
                        if(row)url.pathname=url.pathname.replace('/'+module+'/0','/'+module+'/'+(row.id||row.record_uuid));
                        if(farm)url.pathname=url.pathname.replace('/fincas/0','/fincas/'+(farm.id||farm.record_uuid));
                        if(row&&url.searchParams.get('animal_id')==='0')url.searchParams.set('animal_id',row.id||row.record_uuid);
                        if(farm&&url.searchParams.get('finca')==='0')url.searchParams.set('finca',farm.id||farm.record_uuid);
                        node.setAttribute(attribute,url.pathname+url.search);
                    });
                    page.html=extractPage(doc,page.path).html;
                    if(renderCachedPage(force,page))return;
                }
            }
        }
        if(renderCachedPage(force))return;
        if(location.pathname==='/produccion'){
            const refs=data.productionReferences;
            main.innerHTML=`<h1 class="text-3xl text-white font-bold mb-5">Producción de leche</h1><div class="glass-effect p-5 rounded-2xl shadow-xl"><p class="text-gray-700 mb-4">Historial descargado de ${data.productionDays} días. Consulta solamente; la creación y edición de Producción se mantiene online.</p><div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead><tr><th class="p-2">Fecha</th><th class="p-2">Animal</th><th class="p-2">Litros</th><th class="p-2">Turno / observaciones</th></tr></thead><tbody>${data.production.map(r=>`<tr class="border-t border-gray-200"><td class="p-2">${esc(r.fecha)}</td><td class="p-2">${esc(refs?.animales.find(a=>a.id===r.animal_id)?.nombre||r.animal_id)}</td><td class="p-2">${esc(r.litros)}</td><td class="p-2">${esc(r.sesion)} · ${esc(r.calidad)} · ${esc(r.observaciones)}</td></tr>`).join('')}</tbody></table></div></div>`;
            return;
        }
        main.innerHTML='<div class="glass-effect p-6 rounded-xl"><p>Esta pantalla todavía necesita conexión. Los datos y cambios guardados se conservan.</p><a href="/dashboard" class="text-red-800 underline">Volver al dashboard</a></div>';
    }
    async function remove(module, uuid) {
        await mutate(working => {
            const current = projected(working), record = current[module][uuid];
            if (!record || record.deleted) throw new Error('Este registro ya no está disponible.');
            if (module === 'fincas' && (Object.values(current.animales).some(a => a.finca_uuid === uuid && !a.deleted) || working.references.potreros.some(p => p.finca_uuid === uuid))) throw new Error('Mueve o elimina primero los animales y potreros de esta finca.');
            const dependencies = working.queue.filter(op => op.record_uuid === uuid || module === 'fincas' && (op.data.finca_uuid === uuid || op.before?.finca_uuid === uuid)).map(op => op.operation_uuid);
            working.queue.push({operation_uuid:crypto.randomUUID(), record_uuid:uuid, module, type:'delete', base_version:record.version,
                data:{}, before:record, status:'pending', started:false, dependsOn:dependencies});
        });
        navigate('/'+module); renderControls(); schedule(250);
    }
    async function undo(id) {
        await mutate(working => {
            const op = working.queue.find(op => op.operation_uuid === id);
            if (!op || op.type !== 'delete' || op.started) throw new Error('El envío ya comenzó. No se puede deshacer esta eliminación desde la cola.');
            working.queue = working.queue.filter(op => op.operation_uuid !== id);
        });
        renderRoute(true);
    }
    async function resolve(id, choice) {
        await mutate(working => {
            const op = working.queue.find(op => op.operation_uuid === id);
            if (!op || !['conflict','error'].includes(op.status)) throw new Error('El conflicto ya se resolvió o cambió en otra pestaña.');
            const affected = working.queue.filter(other => other.module === op.module && other.record_uuid === op.record_uuid);
            if (affected.some(other => other.started && other.status === 'sending')) throw new Error('Hay un envío sin confirmar. Reinténtalo antes de resolver.');
            const draft = projected(working)[op.module][op.record_uuid];
            const current = serverRecord(op,working);
            working.archive.push({at:Date.now(), decision:choice, operations:affected, draft, remote:current});
            working.queue = working.queue.filter(other => !affected.includes(other));
            for (const other of working.queue) other.dependsOn = other.dependsOn.filter(dep => !affected.some(old => old.operation_uuid === dep));
            if (current && !current.deleted) working.base[op.module][op.record_uuid] = current;
            else delete working.base[op.module][op.record_uuid];
            if (choice !== 'remote') {
                if (choice === 'local' && (!current || current.deleted)) throw new Error('El registro fue eliminado en el servidor. Créalo como nuevo para conservar tu borrador.');
                const uuid = choice === 'copy' ? crypto.randomUUID() : op.record_uuid;
                const values = {...draft};
                for (const field of ['id','record_uuid','version','codigo','deleted','module','venta_registrada']) delete values[field];
                const type = choice === 'copy' ? 'create' : draft.deleted ? 'delete' : 'update';
                working.queue.push({operation_uuid:crypto.randomUUID(), record_uuid:uuid, module:op.module, type,
                    ...(type === 'create' ? {} : {base_version:current.version}), data:type === 'delete' ? {} : values,
                    before:current, status:'pending', started:false, dependsOn:[]});
            }
        });
        renderRoute(true); schedule(250);
    }
    function schedule(delay = 15000) {
        clearTimeout(syncTimer);
        if (!data || !key || preparing || !navigator.onLine) return;
        const scheduledActor=actor();
        syncTimer = setTimeout(async () => {
            try {
                await assertActor(scheduledActor);
                // A failed request need not generate a browser "online" event.
                // Probe the session again so an interrupted response cannot stall the queue.
                if (connectionLost) {
                    const verified = await identity();
                    await assertActor(scheduledActor);
                    if(String(verified.account_id)!==scheduledActor.accountId)throw syncError('BOVI_RUN_SUPERSEDED','La cuenta cambió.');
                    session = verified;
                    connectionLost = false;
                    indicator();
                }
                await sync();
            } catch (failure) {
                if(!sameActor(actor(),scheduledActor)||failure.code==='BOVI_RUN_SUPERSEDED')return;
                if ([401,419].includes(failure.status)) lock('La sesión venció. Inicia sesión con Internet. Los pendientes de tu cuenta se conservan.');
                else if (key) {error('No se pudo recuperar la conexión. Tus cambios se conservan.');schedule(Math.min(30000,Math.max(delay*2,2000)));}
            }
        }, delay);
    }
    async function sync() {
        if (syncing || preparing || !key || !data || !connected()) return;
        syncing = true;
        const run=actor();let leader,busy=false;
        const change=(callback,revision=null)=>mutate(callback,{},revision,{actor:run,lease:leader.token});
        try {
            await coordinate('bovisoft-workspace-sync', async () => {
                await assertActor(run);
                leader=await leadership(await workspaceStore(),run,senderId);
                if(!leader){busy=true;return;}
                const verified=await leader.request(signal=>identity(signal));
                if(verified.account_id===null)throw Object.assign(new Error('La sesión venció.'),{status:401});
                await assertActor(run);
                if(String(verified.account_id)!==run.accountId)throw syncError('BOVI_RUN_SUPERSEDED','La cuenta cambió.');
                session=verified;
                const device=data.deviceUuid;
                renderControls();
                const waitingForParents=new Set();
                while (key && connected()) {
                    await leader.check();await assertActor(run);
                    let sending;
                    await change(working => {
                        const op=nextOperation(working);
                        if(!op)return;
                        if(op.type!=='delete'&&missingParents(working,op)){waitingForParents.add(op.operation_uuid);return;}
                        sending=prepareOperation(op);
                    });
                    if(!sending)break;
                    uploading=true;indicator();
                    const result=await leader.request(signal=>api('/operations',{
                        account_id:Number(run.accountId),device_uuid:device,operation:sending.wire,
                    },false,signal));
                    await assertActor(run);
                    await change(working=>acknowledge(working,sending,result,run.accountId));
                }
                let delta;
                do {
                    await assertActor(run);
                    const revision=vault.revision,cursor=data.cursor;
                    delta=await leader.request(signal=>api('/changes?cursor='+encodeURIComponent(cursor),null,false,signal));
                    await assertActor(run);
                    const applied=await change(working=>{applyDelta(working,delta,run.accountId);return true;},revision);
                    if(!applied){delta={has_more:true};continue;}
                    if(delta.changes.length && /^\/(fincas|animales)(\/|$)/.test(location.pathname)){
                        const form=main.querySelector('form'), nativeEdit=!localView&&!shell&&/\/edit$/.test(location.pathname);
                        if(nativeEdit){
                            // Keep online form listeners and map instances alive. A dirty
                            // form retains its original version and is never silently rebased.
                            const module=location.pathname.split('/')[1],row=findRecord(module,location.pathname.split('/')[2]);
                            if(row&&!dirtyForms.has(form)){hydrateFields(row);hydrateNormalForm(row,module);}
                        }else{localView=true;renderRoute(false);}
                    }
                } while(delta.has_more);
                if(waitingForParents.size)await change(working=>{
                    for(const op of working.queue)if(waitingForParents.has(op.operation_uuid)&&missingParents(working,op)){
                        op.status='error';op.result={error:'La referencia dejó de estar disponible. Tus cambios se conservan.'};
                    }
                });
                await change(working=>{working.lastSync=Date.now()+vault.serverOffset;});
                retry=1000;syncFailed=false;error();renderControls();
            });
        } catch(failure) {
            // Never apply an older account's response or error to the current account.
            if(!sameActor(actor(),run)||['BOVI_RUN_SUPERSEDED','BOVI_SYNC_LEASE_LOST'].includes(failure.code))return;
            syncFailed=true;
            if([401,419].includes(failure.status))lock('La sesión venció. Inicia sesión con Internet. Los pendientes de tu cuenta se conservan.');
            else {
                if(!navigator.onLine||failure instanceof TypeError)connectionLost=true;
                error('No se pudieron sincronizar todos los cambios. Se conservan y se reintentará automáticamente.');
                if(key)renderControls();
                retry=backoffMs(retry,failure.delay||0);
                if(leader)await leader.defer(retry).catch(()=>{});
                if([410,422].includes(failure.status))automaticDownload();
            }
        } finally {
            if(leader)await leader.close().catch(()=>{});
            syncing=false;uploading=false;indicator();
            const current=sameActor(actor(),run);
            schedule(!current?0:syncFailed?retry:busy?2000:data?.queue.some(op=>op.status==='pending')?1000:15000);
            if(current&&key&&connected())refreshProduction();
        }
    }
    document.addEventListener('input',event=>{ if(event.target.form) dirtyForms.add(event.target.form); });
    document.addEventListener('submit',event=>{
        const form = event.target, action = new URL(form.action,location.href);
        const match = action.pathname.match(/^\/(fincas|animales)(?:\/([^/]+))?$/);
        if(!match&&!connected()&&action.pathname!=='/logout'&&form.method.toUpperCase()!=='GET'){
            event.preventDefault();event.stopImmediatePropagation();error('Este módulo todavía necesita conexión para guardar. Los valores del formulario se conservan.');return;
        }
        if (!match || (!data && connected())) return;
        const record = match[2] && findRecord(match[1],match[2]);
        if (connected() && !localView && !data?.queue.some(op=>op.module===match[1]&&op.record_uuid===record?.record_uuid)) return;
        event.preventDefault(); event.stopImmediatePropagation();
        (async()=>{
            await bootPromise;
            if (!key || !data) throw new Error('Todavía no se completó el guardado automático de estos datos. La pantalla y los valores del formulario se conservan.');
            const method = (new FormData(form).get('_method') || form.method).toUpperCase();
            if(method==='DELETE') { if(!record) throw new Error('El registro no está disponible.'); await remove(match[1],record.record_uuid); }
            else await saveNormalForm(form,match[1],record);
        })().catch(f=>error(f.message));
    },true);
    document.addEventListener('click',event=>{
        const target = event.target.closest('a,button'); if(!target) return;
        if(target.dataset.undo) undo(target.dataset.undo).catch(f=>error(f.message));
        if(target.dataset.resolve) resolve(target.dataset.id,target.dataset.resolve).catch(f=>error(f.message));
        if(target.dataset.delete) remove(target.dataset.module,target.dataset.delete).catch(f=>error(f.message));
        if(target.closest('#btn-campanita') && (!connected()||shell)) {event.preventDefault();event.stopImmediatePropagation();notice('Las alertas del sistema requieren conexión.');return;}
        if(target.id==='btn-toggle-estado'&&(!connected()||localView)) {
            event.preventDefault();event.stopImmediatePropagation();
            const row=findRecord('fincas',location.pathname.split('/')[2]);
            if(row) queueValues('fincas',row,{...row,activa:!row.activa}).then(()=>{localView=true;renderRoute(true);schedule(250);}).catch(f=>error(f.message));
            return;
        }
        if(!active||!key||target.tagName!=='A'||event.ctrlKey||event.metaKey||event.shiftKey||target.target==='_blank'||target.hasAttribute('download'))return;
        const url=new URL(target.href,location.href);
        if(url.origin!==location.origin || url.hash && url.pathname===location.pathname) return;
        if(!connected()&&url.pathname!=='/login') {event.preventDefault();event.stopImmediatePropagation();navigate(url.pathname+url.search);}
    },true);
    window.addEventListener('popstate',()=>{if(!connected()||localView){editing=null;renderRoute(true);}});
    window.addEventListener('offline',()=>{connectionLost=true;clearTimeout(syncTimer);enableOfflineForm();indicator();});
    window.addEventListener('online',()=>{connectionLost=false;indicator();boot().then(()=>schedule(0)).catch(retryBoot);});
    window.addEventListener('bovi:pagehide',()=>{key=null;data=null;clearTimeout(syncTimer);});
    window.addEventListener('bovi:security',event=>{
        if(key&&(event.detail.accountId!==vault.accountId||event.detail.epoch!==epoch))lock();
        if(event.detail.accountId!==String(session?.account_id))session=null;
        if(event.detail.accountId&&!event.detail.pendingLogout&&!key)setTimeout(()=>boot().catch(f=>error(f.message)),0);
    });
    document.addEventListener('visibilitychange',()=>{if(!document.hidden&&key)assertAccount().then(()=>schedule(0)).catch(f=>error(f.message));});
    channel?.addEventListener('message',async event=>{
        if(!key||event.data.accountId!==vault.accountId)return;
        try{await assertAccount();await restore();renderControls();if(localView)renderRoute(false);}catch{lock();}
    });
    async function boot() {
        if(bootPromise&&muted)return bootPromise;
        muted=true;
        bootPromise=(async()=>{
            await ensureWorker();
            if(navigator.onLine){try{session=await identity();connectionLost=false;}catch{connectionLost=true;}}
            const restored=await restore();
            if(restored&&connected()&&session?.workspace_enabled){
                const profile=await accountProfile();
                if(!data.views){const views=await nativeViews();await mutate(working=>{working.views=views;working.profile=profile;});}
                else if(JSON.stringify(profile)!==JSON.stringify(data.profile))await mutate(working=>{working.profile=profile;});
                applyProfile(data.profile);
            }
            if(restored){await seedCurrentRecord();await cacheCurrentPage();if(shell||data.queue.length){localView=true;renderRoute(false);}renderControls();schedule(0);}
            if(connected()&&session?.account_id&&session.workspace_enabled){
                if(!restored)await automaticDownload();
                else refreshShell().catch(f=>error(f.message));
                if(data){await seedCurrentRecord();await cacheCurrentPage();warmPages().then(()=>{document.body.dataset.offlineWarm='true';}).catch(()=>{});schedule(0);}
            }
            if(shell&&!data)lock('Aún no hay datos guardados para esta cuenta. Se guardarán automáticamente al iniciar sesión con Internet.');
            enableOfflineForm();indicator();
        })().finally(()=>{muted=false;});
        return bootPromise;
    }
    function retryBoot(failure) {
        error(failure.message);
        if (!key && navigator.onLine) {
            clearTimeout(syncTimer);
            syncTimer=setTimeout(()=>boot().catch(retryBoot),5000);
        }
    }
    indicator();boot().catch(retryBoot);
}
