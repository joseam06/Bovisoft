"""Automatic offline operation through the normal Laravel screens, without setup UI."""
import argparse
import os
import json
import tempfile
import uuid
import time
from pathlib import Path
from datetime import datetime,timezone
from playwright.sync_api import sync_playwright, expect

BASE = 'http://127.0.0.1:8094'
EVIDENCE = Path(tempfile.gettempdir())/'bovisoft-automatic-evidence'


def test_password():
    value=os.environ.get('BOVISOFT_BROWSER_TEST_PASSWORD')
    if not value:raise RuntimeError('Define BOVISOFT_BROWSER_TEST_PASSWORD con la clave de tus cuentas ficticias locales.')
    return value


def context(browser):
    ctx=browser.new_context(service_workers='allow',viewport={'width':1365,'height':950})
    ctx.route('**/*',lambda route:route.continue_() if route.request.url.startswith(BASE) else route.abort())
    return ctx


def login(page,account='a',wait_ready=True):
    page.goto(BASE+'/login',wait_until='networkidle')
    page.locator('[name=email]').fill(f'offline-{account}@example.test')
    page.locator('[name=password]').fill(test_password())
    page.locator('form[action$="/login"] button[type=submit]').click()
    page.wait_for_url('**/dashboard')
    if wait_ready:page.wait_for_function('document.body.dataset.offlineReady === "true"',timeout=120000)
    expect(page.locator('#offline-indicator')).to_be_hidden()
    assert page.locator('#workspace-controls,#workspace-toggle,#workspace-unlock,#workspace-prepare').count()==0


def catalog(page):
    return page.evaluate('''async()=>{
        const s=await(await fetch('/offline/session',{cache:'no-store'})).json();
        let p=await(await fetch('/api/offline/v1/workspace/snapshots',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':s.csrf_token},body:'{}'})).json();
        const rows=[...p.records];while(!p.complete){p=await(await fetch(`/api/offline/v1/workspace/snapshots/${p.snapshot_uuid}?page=${p.next_page}`,{headers:{Accept:'application/json'}})).json();rows.push(...p.records);}return rows;
    }''')


def state(page):
    return page.evaluate('''async()=>{
        const s=await BoviOfflineStore.security(),v=await BoviOfflineStore.automaticWorkspace(s.accountId);
        if(!v)return null;
        const aad=new TextEncoder().encode(`bovisoft:automatic:1:${v.accountId}:${v.generation}`);
        const data=JSON.parse(new TextDecoder().decode(await crypto.subtle.decrypt({name:'AES-GCM',iv:v.iv,additionalData:aad},v.automaticKey,v.ciphertext)));
        return {queue:data.queue,extractable:v.automaticKey.extractable,hasExpiry:'expiresAt'in v,hasPassword:'wrappedKey'in v,pages:Object.keys(data.pages||{}),shellVersion:v.shellVersion};
    }''')


def wait_synced(page):
    deadline=time.monotonic()+120
    while state(page)['queue'] and time.monotonic()<deadline:page.wait_for_timeout(100)
    assert not state(page)['queue'],'No se confirmó la cola pendiente'
    expect(page.locator('#offline-indicator')).to_be_hidden(timeout=30000)


def offline_link(page,path):
    page.evaluate('''path=>{const a=document.createElement('a');a.href=path;document.body.append(a);a.click();a.remove();}''',path)
    expect(page).to_have_url(BASE+path)


def relationships(browser):
    ctx=context(browser);page=ctx.new_page();login(page)
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True)
    suffix=uuid.uuid4().hex[:8]
    offline_link(page,'/fincas/create')
    page.locator('main [name=nombre]').fill('Finca automática '+suffix)
    page.locator('main [name=latitud]').fill('4.551')
    page.locator('main [name=longitud]').fill('-74.123')
    page.locator('main form button[type=submit]').click()
    expect(page.locator('main')).to_contain_text('Finca automática '+suffix)
    farm=page.url.split('/')[-1]
    offline_link(page,'/animales/create?finca='+farm)
    page.locator('main [name=nombre]').fill('Animal automático '+suffix)
    page.locator('main [name=tipo]').select_option('vaca')
    page.locator('main [name=sexo]').select_option('hembra')
    page.locator('main form button[type=submit]').click()
    expect(page.locator('main')).to_contain_text('Animal automático '+suffix)
    animal=page.url.split('/')[-1]
    page.locator('main').get_by_role('button',name='Eliminar',exact=True).click()
    expect(page.locator('main')).not_to_contain_text('Animal automático '+suffix)
    page.get_by_role('button',name='Deshacer eliminación',exact=True).click()
    expect(page.locator('main')).to_contain_text('Animal automático '+suffix)
    page.reload(wait_until='networkidle')
    expect(page.locator('main')).to_contain_text('Animal automático '+suffix,timeout=30000)
    sent=[]
    def track(route):sent.append(route.request.post_data_json['operation']);route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',track)
    ctx.set_offline(False);wait_synced(page)
    records=catalog(page)
    parent=next(r for r in records if r['record_uuid']==farm)
    child=next(r for r in records if r['record_uuid']==animal)
    assert child['finca_uuid']==parent['record_uuid']
    assert [op['module'] for op in sent]==['fincas','animales']
    ctx.set_offline(True)
    offline_link(page,'/animales/'+str(child['id']))
    page.locator('main').get_by_role('button',name='Eliminar',exact=True).click()
    expect(page.get_by_role('button',name='Deshacer eliminación',exact=True)).to_be_visible()
    ctx.set_offline(False);wait_synced(page)
    assert not any(r['record_uuid']==animal for r in catalog(page))
    ctx.close()
    print('PASS: formularios habituales, finca nueva y animal dependiente, deshacer eliminación y borrado confirmado.')


def logout(page):
    page.locator('#user-menu-btn').click()
    page.locator('form[action$="/logout"] button[type=submit]').click()


def accounts(browser):
    ctx=context(browser);page=ctx.new_page();login(page)
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True);suffix=uuid.uuid4().hex[:8]
    offline_link(page,'/fincas/create')
    page.locator('main [name=nombre]').fill('Pendiente exclusivo A '+suffix)
    page.locator('main [name=latitud]').fill('4.51');page.locator('main [name=longitud]').fill('-74.1')
    page.locator('main form button[type=submit]').click()
    expect(page.locator('main')).to_contain_text('Pendiente exclusivo A '+suffix)
    farm=page.url.split('/')[-1]
    other=ctx.new_page();other.goto(BASE+'/dashboard',wait_until='networkidle')
    page.wait_for_timeout(300)
    logout(page)
    expect(other.locator('body')).not_to_contain_text('Pendiente exclusivo A '+suffix)
    expect(other.locator('body')).not_to_contain_text('Finca El Roble')
    page.wait_for_url(BASE+'/dashboard')
    page.wait_for_load_state('networkidle')
    page.wait_for_function('!!window.BoviOfflineStore',timeout=30000)
    ctx.set_offline(False)
    deadline=time.monotonic()+30
    while page.evaluate('BoviOfflineStore.security().then(s=>s.pendingLogout)') and time.monotonic()<deadline:page.wait_for_timeout(100)
    login(page,'b')
    offline_link(page,'/fincas') if not page.evaluate('navigator.onLine') else page.goto(BASE+'/fincas',wait_until='networkidle')
    expect(page.locator('main')).to_contain_text('Finca Los Cedros')
    expect(page.locator('body')).not_to_contain_text('Pendiente exclusivo A '+suffix)
    assert not any(r['record_uuid']==farm for r in catalog(page))
    logout(page);page.wait_for_url(BASE+'/')
    login(page);wait_synced(page)
    assert len([r for r in catalog(page) if r['record_uuid']==farm])==1
    ctx.close();print('PASS: cierre de sesión entre pestañas, aislamiento A/B y recuperación automática de los pendientes de A.')


def no_deadline(browser):
    ctx=context(browser);page=ctx.new_page();page.clock.install(time=datetime.now(timezone.utc));login(page)
    records=catalog(page);row=next(r for r in records if r['module']=='animales' and r['codigo']=='OFF-LIST-1')
    path='/animales/'+str(row['id']);page.goto(BASE+path,wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True);page.clock.fast_forward(40*24*60*60*1000)
    page.reload(wait_until='networkidle');expect(page.locator('main')).to_contain_text(row['nombre'],timeout=30000)
    assert not state(page)['hasExpiry']
    assert page.locator('#workspace-unlock,#workspace-prepare').count()==0
    ctx.close();print('PASS: la copia sigue disponible después de cuarenta días, sin clave adicional ni límite de siete días.')


def normal_animal(browser):
    ctx=context(browser);page=ctx.new_page();errors=[]
    page.on('pageerror',lambda e:errors.append(str(e)))
    login(page)
    record=next(r for r in catalog(page) if r['module']=='animales' and r['codigo']=='OFF-LIST-0')
    path='/animales/'+str(record['id'])
    page.goto(BASE+path,wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    expect(page.locator('main')).to_contain_text('Información General')
    assert path+'/edit' in state(page)['pages']
    original=page.locator('main').inner_html()
    ctx.set_offline(True)
    expect(page.locator('#offline-indicator-text')).to_have_text('Estás usando BoviSoft sin conexión')
    expect(page.locator('#offline-indicator')).to_be_visible()
    assert page.locator('#offline-indicator').evaluate('(e)=>e.tagName')=='SPAN'
    assert page.locator('main').inner_html()==original,'Cortar la conexión cambió la ficha actual'
    page.locator(f'main a[href$="{path}/edit"]').first.click()
    expect(page).to_have_url(BASE+path+'/edit')
    expect(page.locator('main')).to_contain_text('Editar Animal')
    value='Cambio desde la ficha normal '+uuid.uuid4().hex[:10]
    page.locator('main textarea[name=observaciones]').fill(value)
    page.locator('main [name=peso_actual]').fill('321.75')
    form=page.locator('main form').filter(has=page.locator('input[name="_method"][value="PUT"]'))
    form.locator('button[type=submit]').click()
    expect(page).to_have_url(BASE+path)
    expect(page.locator('main')).to_contain_text(value)
    expect(page.locator('main [data-offline-field=peso_actual]')).to_contain_text('321.75')
    expect(page.locator('main')).to_contain_text('Información General')
    page.screenshot(path=str(EVIDENCE/'animal-edited-offline.png'),full_page=True)
    page.reload(wait_until='networkidle')
    expect(page.locator('main')).to_contain_text(value,timeout=30000)
    expect(page.locator('main')).to_contain_text('Información General')
    assert not state(page)['extractable'] and not state(page)['hasExpiry'] and not state(page)['hasPassword']
    assert len(state(page)['queue'])==1
    page.screenshot(path=str(EVIDENCE/'animal-reloaded-offline.png'),full_page=True)
    page.evaluate('''()=>{window.indicatorTrace=[];new MutationObserver(()=>{if(!document.querySelector('#offline-indicator').hidden)window.indicatorTrace.push(document.querySelector('#offline-indicator-text').textContent);}).observe(document.querySelector('#offline-indicator'),{attributes:true,childList:true,subtree:true});}''')
    operations=[]
    def track(route):
        operations.append(route.request.post_data_json['operation']);route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',track)
    ctx.set_offline(False)
    deadline=time.monotonic()+120
    while state(page)['queue'] and time.monotonic()<deadline:
        page.wait_for_timeout(100)
    assert not state(page)['queue'],'No se confirmó la operación pendiente'
    page.wait_for_function('window.indicatorTrace.includes("Sincronizando cambios…")',timeout=30000)
    expect(page.locator('#offline-indicator')).to_be_hidden(timeout=30000)
    rows=[r for r in catalog(page) if r['record_uuid']==record['record_uuid']]
    assert len(rows)==1 and rows[0]['version']==record['version']+1 and rows[0]['observaciones']==value
    assert len(operations)==1 and operations[0]['type']=='update'
    assert 'Sincronizando cambios…' in page.evaluate('window.indicatorTrace')
    assert not any('workspace' in url or '/offline/' in url for url in [page.url])
    page.screenshot(path=str(EVIDENCE/'animal-reconnected.png'),full_page=True)
    print('PASS: ficha normal, desconexión sin cambiar pantalla, edición, recarga sin Internet y una sola escritura al reconectar.')
    ctx.close()


def resilience(browser):
    ctx=context(browser);page=ctx.new_page();login(page)
    row=next(r for r in catalog(page) if r['module']=='animales' and r['codigo']=='OFF-LIST-1')
    path='/animales/'+str(row['id']);page.goto(BASE+path,wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True);page.locator(f'main a[href$="{path}/edit"]').first.click()
    value='Respuesta perdida '+uuid.uuid4().hex[:8]
    page.locator('main textarea[name=observaciones]').fill(value)
    page.get_by_role('button',name='Guardar Cambios').click()
    expect(page.locator('main')).to_contain_text(value)
    sent=[]
    def lose_response(route):
        sent.append(route.request.post_data_json)
        if len(sent)==1:
            response=route.fetch();assert response.status==200
            route.abort('connectionfailed')
        else:route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',lose_response)
    ctx.set_offline(False);wait_synced(page)
    saved=next(r for r in catalog(page) if r['record_uuid']==row['record_uuid'])
    assert len(sent)==2 and sent[0]==sent[1]
    assert saved['version']==row['version']+1 and saved['observaciones']==value
    ctx.close()
    print('PASS: respuesta perdida tras confirmar el servidor, reintento automático sin evento online adicional y una sola escritura.')


def conflicts(browser):
    ctx=context(browser);page=ctx.new_page();login(page)
    row=next(r for r in catalog(page) if r['module']=='animales' and r['codigo']=='OFF-LIST-2')
    path='/animales/'+str(row['id']);page.goto(BASE+path,wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True);page.locator(f'main a[href$="{path}/edit"]').first.click()
    draft='Borrador local '+uuid.uuid4().hex[:8]
    page.locator('main textarea[name=observaciones]').fill(draft)
    page.get_by_role('button',name='Guardar Cambios').click()
    expect(page.locator('main')).to_contain_text(draft)
    remote=context(browser);other=remote.new_page();login(other)
    result=other.evaluate('''async row=>{
        const session=await(await fetch('/offline/session',{cache:'no-store'})).json();
        const response=await fetch('/api/offline/v1/workspace/operations',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':session.csrf_token},body:JSON.stringify({account_id:session.account_id,device_uuid:crypto.randomUUID(),operation:{operation_uuid:crypto.randomUUID(),record_uuid:row.record_uuid,module:'animales',type:'update',base_version:row.version,data:{...row,observaciones:'Edición confirmada desde otro dispositivo'}}})});
        return {status:response.status,data:await response.json()};
    }''',row)
    assert result['status']==200,result
    ctx.set_offline(False)
    expect(page.locator('#offline-changes')).to_contain_text('El registro cambió en otro dispositivo.',timeout=120000)
    assert state(page)['queue'][0]['status']=='conflict'
    assert state(page)['queue'][0]['data']['observaciones']==draft
    page.reload(wait_until='networkidle')
    expect(page.locator('#offline-changes')).to_contain_text('El registro cambió en otro dispositivo.',timeout=120000)
    saved=next(r for r in catalog(page) if r['record_uuid']==row['record_uuid'])
    assert saved['version']==row['version']+1 and saved['observaciones']=='Edición confirmada desde otro dispositivo'
    page.get_by_text('Revisar conflicto',exact=True).click()
    page.get_by_role('button',name='Usar versión del servidor',exact=True).click();wait_synced(page)
    ctx.close();remote.close()
    print('PASS: conflicto visible entre dispositivos, borrador conservado al recargar y ninguna sobrescritura silenciosa.')


def interrupted_download(browser):
    ctx=context(browser);page=ctx.new_page();interrupted=[]
    def interrupt(route):
        if not interrupted and 'page=2' in route.request.url:
            interrupted.append(True);route.abort('connectionfailed');ctx.set_offline(True)
        else:route.continue_()
    ctx.route('**/api/offline/v1/workspace/snapshots/*',interrupt)
    login(page,wait_ready=False)
    deadline=time.monotonic()+120
    while not interrupted and time.monotonic()<deadline:page.wait_for_timeout(100)
    assert interrupted,'No se alcanzó la segunda página de la descarga automática'
    assert state(page) is None,'Se publicó una copia parcial'
    expect(page.locator('main')).to_contain_text('Panel de Control')
    ctx.unroute('**/api/offline/v1/workspace/snapshots/*',interrupt)
    ctx.set_offline(False)
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True);offline_link(page,'/animales');page.reload(wait_until='networkidle')
    expect(page.locator('main')).to_contain_text('OFF-LIST-0',timeout=30000)
    ctx.close();print('PASS: descarga automática interrumpida sin publicar datos parciales y recuperación automática sin controles manuales.')


def interface_update(browser):
    ctx=context(browser);page=ctx.new_page();login(page)
    page.wait_for_function('document.body.dataset.offlineWarm === "true"',timeout=120000)
    ctx.set_offline(True);suffix=uuid.uuid4().hex[:8]
    offline_link(page,'/fincas/create');page.locator('main [name=nombre]').fill('Interfaz actualizada '+suffix)
    page.locator('main [name=latitud]').fill('4.51');page.locator('main [name=longitud]').fill('-74.1')
    page.locator('main form button[type=submit]').click();expect(page.locator('main')).to_contain_text('Interfaz actualizada '+suffix)
    farm=page.url.split('/')[-1]
    page.evaluate('''async()=>{for(const name of await caches.keys()){if(name.startsWith('bovisoft-offline-shell-'))await(await caches.open(name)).put('/workspace-store.js',new Response('/* earlier helper without automatic storage */',{headers:{'Content-Type':'application/javascript'}}));}}''')
    def manifest(route):
        response=route.fetch();body=response.json();body['version']='f'*64
        route.fulfill(response=response,json=body)
    def pause(route):route.abort('connectionfailed')
    ctx.route('**/offline/asset-manifest',manifest)
    ctx.route('**/api/offline/v1/workspace/operations',pause)
    ctx.set_offline(False);page.goto(BASE+'/fincas',wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineReady === "true"',timeout=120000)
    deadline=time.monotonic()+120
    while state(page)['shellVersion']!='f'*64 and time.monotonic()<deadline:page.wait_for_timeout(100)
    assert state(page)['shellVersion']=='f'*64 and state(page)['queue']
    ctx.set_offline(True);page.reload(wait_until='networkidle')
    expect(page.locator('main')).to_contain_text('Interfaz actualizada '+suffix,timeout=30000)
    ctx.unroute('**/api/offline/v1/workspace/operations',pause)
    ctx.set_offline(False);wait_synced(page)
    assert len([r for r in catalog(page) if r['record_uuid']==farm])==1
    ctx.close();print('PASS: actualización automática de interfaz y auxiliares antiguos sin borrar pendientes, con recarga offline y envío único posterior.')


def main():
    parser=argparse.ArgumentParser();parser.add_argument('--scenario',default='all');args=parser.parse_args()
    EVIDENCE.mkdir(exist_ok=True)
    with sync_playwright() as p:
        browser=p.chromium.launch(headless=True)
        try:
            if args.scenario in ['all','normal']:normal_animal(browser)
            if args.scenario in ['all','relationships']:relationships(browser)
            if args.scenario in ['all','accounts']:accounts(browser)
            if args.scenario in ['all','no-deadline']:no_deadline(browser)
            if args.scenario in ['all','resilience']:resilience(browser)
            if args.scenario in ['all','conflicts']:conflicts(browser)
            if args.scenario in ['all','interrupted']:interrupted_download(browser)
            if args.scenario in ['all','interface-update']:interface_update(browser)
        except Exception as exc:
            for ctx in browser.contexts:
                for page in ctx.pages:
                    print(json.dumps({'url':page.url,'body':page.locator('body').inner_text()[-2600:]},ensure_ascii=True))
                    page.screenshot(path=str(EVIDENCE/'failure.png'),full_page=True)
                    try:print(json.dumps({'queue':[{k:o.get(k) for k in ['module','type','record_uuid','status','started']} for o in state(page)['queue']]},ensure_ascii=True))
                    except Exception:pass
            print(str(exc)[:2000]);raise SystemExit(1)
        finally:browser.close()


if __name__=='__main__':main()
