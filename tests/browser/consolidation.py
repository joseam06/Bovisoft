"""Three grouped local checks for the Phase 3 consolidation. Fictitious data only."""
import argparse
import json
import tempfile
import time
import uuid
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
from automatic import BASE, context, login, catalog, state, wait_synced, offline_link

EVIDENCE=Path(tempfile.gettempdir())/'bovisoft-phase3-consolidation'


def wait_until(page,predicate,message,seconds=100):
    deadline=time.monotonic()+seconds
    while time.monotonic()<deadline:
        if predicate():return
        page.wait_for_timeout(150)
    raise AssertionError(message)


def local_data(page):
    return page.evaluate('''async()=>{
        const s=await BoviOfflineStore.security(),v=await BoviOfflineStore.automaticWorkspace(s.accountId);
        const aad=new TextEncoder().encode(`bovisoft:automatic:1:${v.accountId}:${v.generation}`);
        return JSON.parse(new TextDecoder().decode(await crypto.subtle.decrypt({name:'AES-GCM',iv:v.iv,additionalData:aad},v.automaticKey,v.ciphertext)));
    }''')


def ready(page):
    page.wait_for_function('document.body.dataset.offlineWarm==="true"',timeout=120000)
    assert local_data(page).get('views'),'No se guardaron las vistas Blade habituales'
    assert '_token' not in json.dumps(local_data(page)['views']),'Se persistió un CSRF en las vistas locales'


def update_remote(page,row,observations):
    result=page.evaluate('''async({row,observations})=>{
        const s=await(await fetch('/offline/session',{cache:'no-store'})).json();
        const response=await fetch('/api/offline/v1/workspace/operations',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':s.csrf_token},body:JSON.stringify({account_id:s.account_id,device_uuid:crypto.randomUUID(),operation:{operation_uuid:crypto.randomUUID(),record_uuid:row.record_uuid,module:'animales',type:'update',base_version:row.version,data:{...row,observaciones:observations}}})});
        return response.json();
    }''',{'row':row,'observations':observations})
    assert result['status']==200,result
    return result['record']


def save_animal(page,value):
    page.locator('main [name=observaciones]').fill(value)
    page.get_by_role('button',name='Guardar Cambios').click()
    expect(page.locator('main')).to_contain_text(value)


def fidelity_and_sync(browser):
    ctx=context(browser);page=ctx.new_page();login(page);ready(page)
    def fail_download(route):route.fulfill(status=503,json={'message':'Fallo ficticio de descarga incremental'})
    ctx.route('**/api/offline/v1/workspace/changes**',fail_download)
    page.evaluate("document.dispatchEvent(new Event('visibilitychange'))")
    expect(page.locator('#offline-indicator-text')).to_have_text('No se pudo completar la sincronización',timeout=30000)
    expect(page.locator('#offline-indicator')).to_be_visible()
    assert not state(page)['queue']
    ctx.unroute('**/api/offline/v1/workspace/changes**',fail_download)
    page.evaluate("window.dispatchEvent(new Event('online'))")
    expect(page.locator('#offline-indicator')).to_be_hidden(timeout=30000)
    rows=catalog(page);record=next(r for r in rows if r['module']=='animales' and r['codigo']=='OFF-LIST-8')
    untouched=next(r for r in rows if r['module']=='animales' and r['codigo']=='OFF-LIST-9')
    path='/animales/'+str(record['id']);page.goto(BASE+path,wait_until='networkidle');ready(page)
    account_name=page.locator('[data-account-field=name]').first.inner_text()
    remote=context(browser);other=remote.new_page();login(other)
    changed=update_remote(other,record,'Actualización recibida '+uuid.uuid4().hex[:8])
    expect(page.locator('main')).to_contain_text(changed['observaciones'],timeout=45000)
    page.goto(BASE+path+'/edit',wait_until='networkidle');ready(page)
    changed=update_remote(other,changed,'Actualización de formulario limpio '+uuid.uuid4().hex[:8])
    expect(page.locator('main [name=observaciones]')).to_have_value(changed['observaciones'],timeout=45000)
    page.locator('main [name=observaciones]').fill('Texto todavía sin guardar')
    changed=update_remote(other,changed,'Segunda actualización remota '+uuid.uuid4().hex[:8])
    wait_until(page,lambda:local_data(page)['base']['animales'][record['record_uuid']]['version']==changed['version'],'No llegó la descarga incremental')
    expect(page.locator('main [name=observaciones]')).to_have_value('Texto todavía sin guardar')
    # Fetch only the normal edit form's structure, without opening this animal.
    normal=page.evaluate('''async id=>{const doc=new DOMParser().parseFromString(await(await fetch('/animales/'+id+'/edit')).text(),'text/html');return [...doc.querySelectorAll('main form')][0].className;}''',untouched['id'])
    ctx.set_offline(True)
    offline_link(page,'/animales/'+str(untouched['id']))
    expect(page.locator('main')).to_contain_text('Información General')
    assert page.locator('#workspace-record-form').count()==0
    page.locator('main a[href$="/edit"]').first.click()
    assert (page.locator('main form').first.get_attribute('class') or '')==normal,'El formulario dejó de usar la estructura normal'
    value='Ficha habitual consolidada '+uuid.uuid4().hex[:8];save_animal(page,value)
    page.reload(wait_until='networkidle')
    expect(page.locator('main')).to_contain_text(value,timeout=30000)
    expect(page.locator('[data-account-field=name]').first).to_have_text(account_name)
    expect(page.locator('body')).not_to_contain_text('Cuenta del dispositivo')
    page.screenshot(path=str(EVIDENCE/'desktop-offline.png'),full_page=True)
    sent=[]
    def lose_response(route):
        sent.append(route.request.post_data_json)
        if len(sent)==1:
            response=route.fetch();assert response.ok;route.abort('connectionfailed')
        else:route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',lose_response)
    ctx.set_offline(False);wait_synced(page)
    saved=next(r for r in catalog(page) if r['record_uuid']==untouched['record_uuid'])
    assert saved['version']==untouched['version']+1 and saved['observaciones']==value
    assert len(sent)==2 and sent[0]==sent[1]
    # Conflict remains explicit, including the top status, rather than implying completion.
    ctx.unroute('**/api/offline/v1/workspace/operations',lose_response)
    ctx.set_offline(True);page.locator('main a[href$="/edit"]').first.click()
    save_animal(page,'Borrador que debe conservarse')
    update_remote(other,saved,'Cambio paralelo confirmado')
    ctx.set_offline(False)
    expect(page.locator('#offline-changes')).to_contain_text('El registro cambió en otro dispositivo.',timeout=45000)
    expect(page.locator('#offline-indicator-text')).to_have_text('Hay cambios pendientes de revisión')
    assert state(page)['queue'][0]['data']['observaciones']=='Borrador que debe conservarse'
    page.get_by_text('Revisar conflicto',exact=True).click()
    page.get_by_role('button',name='Usar versión del servidor',exact=True).click();wait_synced(page)
    ctx.close();remote.close()


def restart(playwright):
    profile=Path(tempfile.mkdtemp(prefix='bovisoft-phase3-profile-'))
    assert Path(tempfile.gettempdir()).resolve() in profile.resolve().parents
    def launch():
        ctx=playwright.chromium.launch_persistent_context(str(profile),headless=True,service_workers='allow',viewport={'width':1365,'height':950})
        ctx.route('**/*',lambda route:route.continue_() if route.request.url.startswith(BASE) else route.abort())
        return ctx
    ctx=launch();page=ctx.pages[0];login(page);ready(page)
    row=next(r for r in catalog(page) if r['module']=='animales' and r['codigo']=='OFF-LIST-10')
    path='/animales/'+str(row['id']);name=page.locator('[data-account-field=name]').first.inner_text()
    ctx.set_offline(True);offline_link(page,path+'/edit')
    value='Pendiente tras cerrar el navegador '+uuid.uuid4().hex[:8];save_animal(page,value)
    operation=state(page)['queue'][0]['operation_uuid'];ctx.close()
    # This starts a NEW browser process with the previous disk profile, offline before navigation.
    ctx=launch();ctx.set_offline(True);page=ctx.pages[0];page.goto(BASE+path,wait_until='networkidle')
    expect(page.locator('main')).to_contain_text(value,timeout=30000)
    expect(page.locator('[data-account-field=name]').first).to_have_text(name)
    assert state(page)['queue'][0]['operation_uuid']==operation
    page.screenshot(path=str(EVIDENCE/'reopened-offline.png'),full_page=True)
    ctx.set_offline(False);wait_synced(page)
    saved=next(r for r in catalog(page) if r['record_uuid']==row['record_uuid'])
    assert saved['version']==row['version']+1 and saved['observaciones']==value
    ctx.close()


def mobile_without_locks(browser,playwright):
    ctx=browser.new_context(**playwright.devices['iPhone 13'],service_workers='allow')
    ctx.add_init_script("Object.defineProperty(Navigator.prototype,'locks',{configurable:true,get:()=>undefined})")
    ctx.route('**/*',lambda route:route.continue_() if route.request.url.startswith(BASE) else route.abort())
    first=ctx.new_page();login(first);ready(first)
    second=ctx.new_page();second.goto(BASE+'/fincas',wait_until='networkidle');ready(second)
    assert not first.evaluate('!!navigator.locks') and not second.evaluate('!!navigator.locks')
    ctx.set_offline(True);suffix=uuid.uuid4().hex[:8]
    for page in [first,second]:offline_link(page,'/fincas/create')
    for index,page in enumerate([first,second]):
        page.locator('main [name=nombre]').fill(f'Finca concurrente {index} '+suffix)
        page.locator('main [name=latitud]').fill('4.51');page.locator('main [name=longitud]').fill('-74.12')
    first.locator('main form button[type=submit]').click()
    second.locator('main form button[type=submit]').click()
    wait_until(first,lambda:len(state(first)['queue'])==2,'Se perdió una escritura concurrente sin Web Locks')
    farm=next(op['record_uuid'] for op in state(first)['queue'] if op['data']['nombre']=='Finca concurrente 0 '+suffix)
    other_farm=next(op['record_uuid'] for op in state(first)['queue'] if op['data']['nombre']=='Finca concurrente 1 '+suffix)
    offline_link(first,'/fincas/'+other_farm)
    # Exercise the actual farm control without claiming that the overlapping
    # mobile toolbar is touch-usable before Phase 3.1.
    first.locator('#btn-toggle-estado').evaluate('(button)=>button.click()')
    expect(first.locator('[data-offline-field=operativa]')).to_have_text('Inactiva')
    expect(first.locator('#texto-estado')).to_have_text('Activar')
    offline_link(second,'/animales/create?finca='+farm)
    second.locator('main [name=nombre]').fill('Animal móvil '+suffix)
    second.locator('main [name=tipo]').select_option('vaca');second.locator('main [name=sexo]').select_option('hembra')
    second.locator('main form button[type=submit]').click();expect(second.locator('main')).to_contain_text('Animal móvil '+suffix)
    animal=second.url.split('/')[-1]
    # Existing mobile layout can overlap this toolbar (Phase 3.1). Submit the
    # actual native form to verify storage/sync without claiming touch usability.
    second.locator('main form').filter(has=second.locator('input[name=_method][value=DELETE]')).evaluate('(form)=>form.requestSubmit()')
    second.get_by_role('button',name='Deshacer eliminación',exact=True).evaluate('(button)=>button.click()')
    wait_until(second,lambda:not any(op['type']=='delete' for op in state(second)['queue']),'No se confirmó el deshacer antes de recargar')
    second.reload(wait_until='networkidle');expect(second.locator('main')).to_contain_text('Animal móvil '+suffix,timeout=30000)
    second.screenshot(path=str(EVIDENCE/'mobile-emulation-offline.png'),full_page=True)
    ctx.set_offline(False);wait_synced(first);wait_synced(second)
    rows=catalog(second);parents=[r for r in rows if r['record_uuid']==farm];children=[r for r in rows if r['record_uuid']==animal]
    assert len(parents)==len(children)==1 and children[0]['finca_uuid']==farm and children[0]['version']==1
    assert next(r for r in rows if r['record_uuid']==other_farm)['activa'] is False
    assert len([r for r in rows if r.get('nombre','').startswith('Finca concurrente') and suffix in r['nombre']])==2
    ctx.close()


def main():
    parser=argparse.ArgumentParser();parser.add_argument('--scenario',default='all',choices=['all','fidelity','restart','mobile']);args=parser.parse_args()
    EVIDENCE.mkdir(exist_ok=True);results=[]
    with sync_playwright() as p:
        browser=p.chromium.launch(headless=True)
        try:
            for name,run in [('fidelity',lambda:fidelity_and_sync(browser)),('restart',lambda:restart(p)),('mobile',lambda:mobile_without_locks(browser,p))]:
                if args.scenario in ['all',name]:
                    run();results.append(name);print('PASS: '+name,flush=True)
                    (EVIDENCE/'results.json').write_text(json.dumps({'passed':results,'mobile':'Chromium emulation, not a physical iPhone'}),encoding='utf-8')
        except Exception as exc:
            for context_ in browser.contexts:
                for page in context_.pages:
                    try:
                        print(json.dumps({'url':page.url,'body':page.locator('body').inner_text()[-3000:]},ensure_ascii=True))
                        page.screenshot(path=str(EVIDENCE/'failure.png'),full_page=True)
                    except Exception:pass
            print(str(exc)[:2600]);raise SystemExit(1)
        finally:browser.close()


if __name__=='__main__':main()
