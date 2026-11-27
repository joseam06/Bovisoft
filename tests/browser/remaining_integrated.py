"""One integrated localhost journey: dependencies, restart, lost reply, tabs and account isolation."""
import tempfile,uuid
from pathlib import Path
from collections import Counter
from playwright.sync_api import sync_playwright,expect
from automatic import BASE,login,catalog,wait_synced,offline_link
from consolidation import ready,local_data,wait_until
from responsive import measure

def go(page,path):offline_link(page,path)
def save(page):
    page.locator('main form').filter(has=page.locator('button[type=submit]')).first.locator('button[type=submit]').tap()
    page.wait_for_function("!location.pathname.endsWith('/create')&&!location.pathname.endsWith('/edit')&&!location.pathname.endsWith('/nuevo')",timeout=15000)
def open_context(p,directory,offline=False):
    ctx=p.chromium.launch_persistent_context(str(directory),headless=True,viewport={'width':390,'height':820},is_mobile=True,has_touch=True,reduced_motion='reduce',service_workers='allow')
    ctx.route('**/*',lambda r:r.continue_() if r.request.url.startswith(BASE) else r.abort())
    ctx.set_offline(offline);return ctx
def run(p):
    directory=Path(tempfile.mkdtemp(prefix='bovisoft-p53-browser-'));ctx=open_context(p,directory);page=ctx.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    login(page);ready(page);suffix=uuid.uuid4().hex[:8];ctx.set_offline(True)
    go(page,'/fincas/create');page.locator('main [name=nombre]').fill('Integrada '+suffix);page.locator('main [name=area]').fill('10');page.locator('main [name=latitud]').fill('4.5');page.locator('main [name=longitud]').fill('-74');save(page);farm=page.url.split('/')[-1]
    go(page,'/potreros/create');page.locator('main [name=nombre]').fill('Potrero '+suffix);page.locator('main [name=finca_id]').select_option(farm);page.locator('main [name=capacidad_animales]').fill('2');save(page);paddock=page.url.split('/')[-1]
    go(page,'/animales/create?finca='+farm);page.locator('main [name=nombre]').fill('Animal '+suffix);page.locator('main [name=tipo]').select_option('vaca');page.locator('main [name=sexo]').select_option('hembra');page.locator('main [name=potrero_id]').select_option(paddock);save(page);animal=page.url.split('/')[-1]
    go(page,'/produccion/create?animal_id='+animal);page.locator('main [name=animal_id]').select_option(animal);page.locator('main [name=litros]').fill('12.35');save(page);production=page.url.split('/')[-1]
    go(page,'/salud/create?animal_id='+animal);page.locator('main [name=animal_id]').select_option(animal);page.locator('main [name=tipo]').select_option('tratamiento');page.locator('main [name=estado]').select_option('en_tratamiento');page.locator('main [name=nombre_producto]').fill('Producto '+suffix);page.locator('main [name=costo]').fill('15.20');page.locator('main [name=dias_carencia]').fill('3');save(page);health=page.url.split('/')[-1]
    go(page,'/finanzas/egresos/nuevo?salud_id='+health);page.locator('main [name=categoria]').select_option('salud_animal');save(page);expense=page.url.split('/')[-1]
    go(page,'/salud/'+health+'/edit');expect(page.locator('main [name=costo]')).to_have_attribute('readonly','')
    go(page,'/finanzas/ingresos/nuevo');page.locator('main [name=finca_id]').select_option(farm);page.locator('main [name=tipo]').select_option('venta_animal');page.locator('main [name=animal_id]').select_option(animal);page.locator('main [name=monto]').fill('120.55');save(page);income=page.url.split('/')[-1]
    go(page,'/finanzas/egresos/'+expense);page.locator('main form').filter(has=page.locator('[name=_method][value=DELETE]')).locator('button[type=submit]').tap();page.get_by_role('button',name='Deshacer eliminación',exact=True).tap()
    go(page,'/finanzas?finca_id='+farm);expect(page.locator('[data-finance-stat=balance]').first).to_have_text('105,35');measure(page,'Finanzas integrada móvil')
    go(page,'/dashboard');expect(page.locator('[data-dashboard-alerts]')).to_contain_text('Animal '+suffix);measure(page,'Dashboard integrado móvil')
    go(page,'/alertas');expect(page.locator('main')).to_contain_text('Animal '+suffix);measure(page,'Alertas integradas móvil')
    for kind in ['inventario','salud','vacunacion','tratamientos','historial','financiero']:
        go(page,'/reportes/preview/'+kind+'?periodo=todos&finca_id='+farm+'&animal_id='+animal)
        frame=page.frame_locator('iframe');expect(frame.locator('body')).to_contain_text('Integrada '+suffix)
        assert 'Sin registros' in frame.locator('body').inner_text() or frame.locator('[data-report-collection] tr').count()>0
        if kind=='financiero':expect(frame.locator('[data-report-stat=balance]').first).to_have_text('105,35')
        if kind=='historial':expect(frame.locator('[data-report-collection=registros]')).to_contain_text('Producto '+suffix)
    go(page,'/perfil');page.locator('[name=name]').fill('Nombre integrado '+suffix);page.locator('main form').filter(has=page.locator('[name=name]')).locator('button[type=submit]').tap()
    wait_until(page,lambda:any(op['module']=='perfil' for op in local_data(page)['queue']),'Nombre no persistido')
    before=local_data(page);expected={op['operation_uuid'] for op in before['queue']};assert len(expected)==8
    ctx.close();ctx=open_context(p,directory,True);page=ctx.new_page();page.on('pageerror',lambda e:errors.append(str(e)))
    page.goto(BASE+'/finanzas?finca_id='+farm,wait_until='networkidle');expect(page.locator('[data-finance-stat=balance]').first).to_have_text('105,35',timeout=30000)
    assert {op['operation_uuid'] for op in local_data(page)['queue']}==expected
    print('PASS integrado offline: ocho operaciones relacionadas, seis reportes, alertas/Dashboard, importes, deshacer y cierre/reapertura real de Chromium.',flush=True)
    second=ctx.new_page();second.goto(BASE+'/dashboard',wait_until='networkidle');second.wait_for_function('document.body.dataset.offlineReady==="true"')
    sent=[];lost=[False]
    def transport(route):
        op=route.request.post_data_json['operation'];sent.append(op)
        if op['module']=='ingresos' and not lost[0]:
            result=route.fetch();assert result.ok;lost[0]=True;route.abort('failed')
        else:route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',transport)
    ctx.set_offline(False);wait_synced(page);wait_synced(second);rows=catalog(page)
    for key in [farm,paddock,animal,production,health,expense,income]:assert sum(r['record_uuid']==key for r in rows)==1
    assert next(r for r in rows if r['record_uuid']==animal)['estado']=='vendido'
    assert next(r for r in rows if r['record_uuid']==paddock)['animales_actuales']==0
    counts=Counter(op['operation_uuid'] for op in sent);assert set(counts)==expected;assert sum(n-1 for n in counts.values())==1,counts
    assert lost[0]
    # A second device updates the profile; the open local route must receive it.
    remote=p.chromium.launch(headless=True);rctx=remote.new_context();rctx.route('**/*',lambda r:r.continue_() if r.request.url.startswith(BASE) else r.abort());rp=rctx.new_page();login(rp,wait_ready=False)
    rp.goto(BASE+'/perfil',wait_until='networkidle');rp.locator('[name=name]').fill('Nombre remoto '+suffix);rp.locator('main form').filter(has=rp.locator('[name=name]')).locator('button[type=submit]').click();rp.wait_for_load_state('networkidle')
    # Remain on a local route while connected: read the downloaded model rather
    # than accepting a fresh server-rendered value as proof of incremental sync.
    wait_until(page,lambda:any(r.get('name')=='Nombre remoto '+suffix for r in local_data(page)['base']['perfil'].values()),'No se recibió el perfil remoto')
    remote.close();second.close()
    print('PASS sincronización: dependencias, dos pestañas, respuesta perdida/reintento único, efectos financieros y actualización remota.',flush=True)
    assert not errors,errors
    ctx.close()
    accounts(p)

def accounts(p):
    directory=Path(tempfile.mkdtemp(prefix='bovisoft-p53-accounts-'));ctx=open_context(p,directory);page=ctx.new_page();suffix=uuid.uuid4().hex[:8]
    failures=[];posts=[]
    ctx.on('response',lambda response:failures.append(response.status) if response.status==419 and '/login' in response.url else None)
    ctx.route('**/api/offline/v1/workspace/operations',lambda route:(posts.append(route.request.post_data_json['operation']),route.continue_())[-1])
    login(page);ready(page);page.goto(BASE+'/perfil',wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"')
    owner=page.evaluate('async()=>(await BoviOfflineStore.security()).accountId')
    ctx.set_offline(True);page.locator('[name=name]').fill('Pendiente A '+suffix);page.locator('main form').filter(has=page.locator('[name=name]')).locator('button[type=submit]').tap()
    wait_until(page,lambda:len(local_data(page)['queue'])==1,'Pendiente A no conservado')
    operation=local_data(page)['queue'][0]['operation_uuid']
    second=ctx.new_page();second.goto(BASE+'/perfil',wait_until='networkidle');expect(second.locator('[name=name]')).to_have_value('Pendiente A '+suffix,timeout=30000)
    page.locator('#user-menu-btn').tap();page.locator('form[action$="/logout"] button[type=submit]').tap()
    page.wait_for_url('**/dashboard');page.wait_for_function('window.BoviOfflineStore && BoviOfflineStore.security().then(s=>s.accountId===null&&s.pendingLogout)')
    expect(second.locator('main')).not_to_contain_text('Pendiente A '+suffix)
    ctx.set_offline(False);page.wait_for_function('BoviOfflineStore.security().then(s=>!s.pendingLogout&&s.accountId===null)',timeout=30000)
    login(page,'b') # A single login: no reload or recovery from 419 is allowed.
    ready(page);ctx.set_offline(True);go(page,'/fincas');expect(page.locator('main')).not_to_contain_text('Integrada ')
    second.reload(wait_until='networkidle');second.wait_for_function('document.body.dataset.offlineReady==="true"');expect(second.locator('main')).not_to_contain_text('Pendiente A '+suffix)
    security=page.evaluate('async()=>await BoviOfflineStore.security()');assert security['accountId']!=owner
    pending=page.evaluate('''async account=>{const v=await BoviOfflineStore.automaticWorkspace(account);const aad=new TextEncoder().encode(`bovisoft:automatic:1:${v.accountId}:${v.generation}`);return JSON.parse(new TextDecoder().decode(await crypto.subtle.decrypt({name:'AES-GCM',iv:v.iv,additionalData:aad},v.automaticKey,v.ciphertext))).queue;}''',owner)
    assert len(pending)==1 and pending[0]['data']['name']=='Pendiente A '+suffix
    print('PASS seguridad: cambio A/B bloquea los datos anteriores en ambas pestañas; las operaciones sensibles no forman parte de la cola.',flush=True)
    ctx.set_offline(False);page.locator('#user-menu-btn').tap();page.locator('form[action$="/logout"] button[type=submit]').tap();page.wait_for_url(BASE+'/')
    # Keep the SAME login document while the server invalidates its session.
    # This models an expired form and deterministically exercises token renewal.
    page.goto(BASE+'/login',wait_until='networkidle')
    assert 'no-store' in page.request.get(BASE+'/login').headers['cache-control']
    identity=page.request.get(BASE+'/offline/session').json()
    rejected=page.request.post(BASE+'/logout',headers={'Accept':'application/json','X-CSRF-TOKEN':'invalid-test-token'})
    assert rejected.status==419,'La protección CSRF debe seguir activa'
    expired=page.request.post(BASE+'/logout',headers={'Accept':'application/json','X-CSRF-TOKEN':identity['csrf_token']})
    assert expired.status==204
    token=page.locator('[name=_token]').input_value()
    from automatic import test_password
    page.locator('[name=email]').fill('offline-a@example.test');page.locator('[name=password]').fill(test_password())
    page.locator('form[action$="/login"] button[type=submit]').click();page.wait_for_url('**/dashboard');ready(page);wait_synced(page)
    assert sum(op['operation_uuid']==operation for op in posts)==1
    assert next(r for r in catalog(page) if r['module']=='perfil')['name']=='Pendiente A '+suffix
    assert not failures,'Un login recibió 419'
    print('PASS acceso: formulario vencido renovado sin recarga, logout/login online y pendiente A confirmado una sola vez; CSRF inválido rechazado.',flush=True)
    # Expire an authenticated session with a second pending change and two tabs.
    ctx.set_offline(True);go(page,'/perfil');page.locator('[name=name]').fill('Sesión vencida '+suffix);page.locator('main form').filter(has=page.locator('[name=name]')).locator('button[type=submit]').tap()
    wait_until(page,lambda:len(local_data(page)['queue'])==1,'Pendiente de sesión vencida no guardado')
    expiredOperation=local_data(page)['queue'][0]['operation_uuid']
    identity=page.request.get(BASE+'/offline/session').json();assert identity['account_id'] is not None
    assert page.request.post(BASE+'/logout',headers={'Accept':'application/json','X-CSRF-TOKEN':identity['csrf_token']}).status==204
    ctx.set_offline(False)
    wait_until(page,lambda:page.evaluate('BoviOfflineStore.security().then(s=>s.accountId===null)'),'La sesión vencida no bloqueó el acceso')
    expect(second.locator('body')).not_to_contain_text('Pendiente A '+suffix)
    login(page);ready(page);wait_synced(page)
    assert sum(op['operation_uuid']==expiredOperation for op in posts)==1
    assert next(r for r in catalog(page) if r['module']=='perfil')['name']=='Sesión vencida '+suffix
    assert not failures
    assert page.evaluate('''async()=>{for(const key of await caches.keys())for(const r of await(await caches.open(key)).keys())if(/^\\/(login|logout|register|forgot-password|reset-password|two-factor-challenge|offline\\/session)(\\/|$)/.test(new URL(r.url).pathname))return false;return true;}''')
    print('PASS sesión vencida: bloqueo entre pestañas, recuperación y una sola escritura; autenticación ausente de Cache API.',flush=True)
    ctx.close()

if __name__=='__main__':
    import sys
    with sync_playwright() as p:
        if '--accounts-only' in sys.argv:accounts(p)
        else:run(p)
