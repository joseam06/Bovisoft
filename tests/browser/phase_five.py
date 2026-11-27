"""Four related local journeys; native Blade forms, touch input, no external requests."""
import uuid
import argparse
import tempfile
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
from automatic import BASE, login, catalog, state, wait_synced, offline_link
from consolidation import ready, local_data, wait_until
from responsive import context

def go(page,path):
    offline_link(page,path)

def save(page):
    page.locator('main form').filter(has=page.locator('button[type=submit]')).first.locator('button[type=submit]').tap()
    page.wait_for_function("!location.pathname.endsWith('/edit')&&!location.pathname.endsWith('/create')",timeout=15000)

def paddock(page,name,farm):
    go(page,'/potreros/create')
    page.locator('main [name=nombre]').fill(name)
    page.locator('main [name=finca_id]').select_option(farm)
    page.locator('main [name=capacidad_animales]').fill('2')
    page.locator('main [name=estado]').select_option('activo')
    save(page);expect(page.locator('main')).to_contain_text(name)
    return page.url.split('/')[-1]

def run(p):
    browser=p.chromium.launch(headless=True);ctx=context(browser,390);page=ctx.new_page()
    errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    login(page);ready(page)
    initial=catalog(page);farm=next(r for r in initial if r['module']=='fincas' and r['activa'])
    ctx.set_offline(True);suffix=uuid.uuid4().hex[:8]
    first=paddock(page,'P51 primero '+suffix,str(farm['id']))
    go(page,'/potreros/'+first+'/edit');page.locator('main [name=observaciones]').fill('Rotación ficticia '+suffix);save(page)
    second=paddock(page,'P51 segundo '+suffix,str(farm['id']))
    page.locator('main form').filter(has=page.locator('[name=_method][value=DELETE]')).locator('button').tap()
    page.get_by_role('button',name='Deshacer eliminación',exact=True).tap()
    go(page,'/animales/create?finca='+str(farm['id']))
    page.locator('main [name=nombre]').fill('P51 animal '+suffix)
    expect(page.locator('main [name=nombre]')).to_have_value('P51 animal '+suffix)
    page.locator('main [name=tipo]').select_option('vaca');page.locator('main [name=sexo]').select_option('hembra')
    expect(page.locator('main [name=nombre]')).to_have_value('P51 animal '+suffix)
    page.locator('main [name=potrero_id]').select_option(first)
    expect(page.locator('main [name=nombre]')).to_have_value('P51 animal '+suffix)
    save(page)
    animal=page.url.split('/')[-1]
    go(page,'/animales/'+animal+'/edit');page.locator('main [name=potrero_id]').select_option(second);save(page)
    go(page,'/potreros/'+second)
    expect(page.locator('main [data-offline-collection=animales]')).to_contain_text('P51 animal '+suffix)
    assert local_data(page)['queue'],'No se persistieron operaciones'
    print('PASS parcial Potreros: formularios táctiles, CRUD con deshacer, dependencias, asignación y traslado local.',flush=True)

    go(page,'/produccion/create?animal_id='+animal)
    page.locator('main [name=animal_id]').select_option(animal)
    page.locator('main [name=litros]').fill('12.35');save(page)
    production=page.url.split('/')[-1]
    go(page,'/produccion/'+production+'/edit');page.locator('main [name=litros]').fill('15.25');save(page)
    expect(page.locator('main [data-offline-field=litros]').first).to_have_text('15.25')
    page.reload(wait_until='networkidle');expect(page.locator('main [data-offline-field=litros]').first).to_have_text('15.25',timeout=30000)
    go(page,'/produccion/animal/'+animal)
    expect(page.locator('main [data-offline-collection=produccion]')).to_contain_text('15.25')
    print('PASS parcial Producción: creación, corrección, historial, persistencia y cantidades.',flush=True)

    go(page,'/salud/create?animal_id='+animal)
    page.locator('main [name=animal_id]').select_option(animal)
    page.locator('main [name=nombre_producto]').fill('Vacuna ficticia '+suffix)
    page.locator('main [name=tipo]').select_option('vacunacion');page.locator('main [name=estado]').select_option('completado')
    page.locator('main [name=costo]').fill('120.50');page.locator('main [name=dias_carencia]').fill('3');save(page)
    health=page.url.split('/')[-1]
    go(page,'/salud/'+health+'/edit');page.locator('main [name=observaciones]').fill('Seguimiento '+suffix);save(page)
    go(page,'/salud/preventivo');expect(page.locator('main [data-offline-collection=salud]')).to_contain_text('Vacuna ficticia '+suffix)
    print('PASS parcial Salud: vacunación, costo, carencia, edición y consulta por categoría.',flush=True)

    go(page,'/dashboard')
    projected=page.evaluate('''async()=>{const s=await BoviOfflineStore.security(),v=await BoviOfflineStore.automaticWorkspace(s.accountId);return v.revision}''')
    expect(page.locator('[data-dashboard-stat=potreros]')).to_have_text(str(len([r for r in initial if r['module']=='potreros'])+2))
    expect(page.locator('[data-dashboard-stat=salud]')).to_have_text(str(len([r for r in initial if r['module']=='salud'])+1))
    assert page.evaluate('!!Chart.getChart(document.querySelector("#distribucionChart"))'),'No se creó la gráfica local'
    assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+2'),'Desbordamiento móvil'
    sent=[]
    def transport(route):
        op=route.request.post_data_json['operation'];sent.append(op)
        if op['module']=='salud' and op['type']=='create' and sum(x['operation_uuid']==op['operation_uuid'] for x in sent)==1:
            response=route.fetch();assert response.ok;route.abort('connectionfailed')
        else:route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',transport)
    ctx.set_offline(False);wait_synced(page)
    rows=catalog(page)
    for key in [first,second,animal,production,health]:assert len([r for r in rows if r['record_uuid']==key])==1
    actual={r['record_uuid']:r for r in rows}
    assert actual[first]['animales_actuales']==0 and actual[second]['animales_actuales']==1
    assert actual[production]['litros']=='15.25' and actual[health]['costo']=='120.50'
    health_posts=[op for op in sent if op['module']=='salud' and op['type']=='create'];assert len(health_posts)>=2 and all(op==health_posts[0] for op in health_posts)
    print('PASS sincronización agrupada: orden, ocupación y respuesta perdida de Salud con un solo efecto.',flush=True)

    ctx.set_offline(True);go(page,'/produccion/'+str(actual[production]['id']))
    page.locator('main form').filter(has=page.locator('[name=_method][value=DELETE]')).locator('button').tap()
    wait_until(page,lambda:any(op['module']=='produccion' and op['type']=='delete' for op in state(page)['queue']),'No se guardó la baja productiva')
    go(page,'/potreros/'+str(actual[first]['id']))
    page.locator('main form').filter(has=page.locator('[name=_method][value=DELETE]')).locator('button').tap()
    wait_until(page,lambda:any(op['module']=='potreros' and op['type']=='delete' for op in state(page)['queue']),'No se guardó la baja del potrero vacío')
    go(page,'/salud/'+str(actual[health]['id']))
    page.locator('main form').filter(has=page.locator('[name=_method][value=DELETE]')).locator('button').tap()
    wait_until(page,lambda:any(op['module']=='salud' and op['type']=='delete' for op in state(page)['queue']),'No se guardó la baja sanitaria')
    go(page,'/dashboard');ctx.set_offline(False);wait_synced(page)
    remaining=catalog(page);assert not any(r['record_uuid'] in [production,health] for r in remaining)
    expect(page.locator('[data-dashboard-stat=salud]')).to_have_text(str(len([r for r in initial if r['module']=='salud'])))
    page.set_viewport_size({'width':1440,'height':900});assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+2')
    # A separate authenticated browser changes a record through its normal form.
    remote=context(browser,768);other=remote.new_page();login(other);ready(other)
    other.goto(BASE+'/produccion/create?animal_id='+str(actual[animal]['id']),wait_until='networkidle')
    other.locator('main [name=animal_id]').select_option(str(actual[animal]['id']))
    other.locator('main [name=litros]').fill('21.75');save(other)
    new_row=next(r for r in catalog(other) if r['module']=='produccion' and r['animal_uuid']==animal)
    wait_until(page,lambda:new_row['record_uuid'] in local_data(page)['base']['produccion'],'No llegó el alta remota')
    expected_month=sum(round(float(r['litros'])*100) for r in catalog(other) if r['module']=='produccion' and r['fecha'].startswith(new_row['fecha'][:7]))/100
    expect(page.locator('[data-dashboard-stat=produccion]')).to_have_text(str(expected_month).removesuffix('.0'),timeout=30000)
    ctx.set_offline(True);go(page,'/produccion/'+str(new_row['id'])+'/edit');page.locator('main [name=litros]').fill('25.00');save(page)
    other.goto(BASE+'/produccion/'+str(new_row['id'])+'/edit',wait_until='networkidle');other.locator('main [name=litros]').fill('30.00');save(other)
    ctx.set_offline(False)
    expect(page.locator('#offline-changes')).to_contain_text('El registro cambió en otro dispositivo.',timeout=45000)
    assert state(page)['queue'][0]['data']['litros']=='25.00'
    page.get_by_text('Revisar conflicto',exact=True).tap()
    page.get_by_role('button',name='Conservar mis cambios revisados',exact=True).tap();wait_synced(page)
    result=next(r for r in catalog(page) if r['record_uuid']==new_row['record_uuid']);assert result['litros']=='25.00' and result['version']==3
    remote.close()
    assert not errors,errors
    print('PASS Dashboard: indicadores y Chart.js locales/remotos, bajas, conflicto revisado y 390/768/1440 px; sin errores JavaScript.',flush=True)
    ctx.close();browser.close()

def upgrade_and_chart(p):
    browser=p.chromium.launch(headless=True);ctx=context(browser,390);page=ctx.new_page();login(page);ready(page)
    ctx.set_offline(True);suffix=uuid.uuid4().hex[:8];go(page,'/fincas/create')
    page.locator('main [name=nombre]').fill('P51 actualización '+suffix)
    page.locator('main [name=latitud]').fill('4.55');page.locator('main [name=longitud]').fill('-74.12');save(page)
    pending=state(page)['queue'][0]
    # Fault injection: recreate the shape of a Phase 4 encrypted copy. Keep its queue.
    page.evaluate('''async()=>{
        const security=await BoviOfflineStore.security(),vault=await BoviOfflineStore.automaticWorkspace(security.accountId);
        const additionalData=new TextEncoder().encode(`bovisoft:automatic:1:${vault.accountId}:${vault.generation}`);
        const data=JSON.parse(new TextDecoder().decode(await crypto.subtle.decrypt({name:'AES-GCM',iv:vault.iv,additionalData},vault.automaticKey,vault.ciphertext)));
        data.schema=2;for(const module of ['potreros','produccion','salud']){delete data.base[module];delete data.views[module];delete data.removals[module];}
        delete data.references.complete_modules;delete data.references.workspace_schema;
        const iv=crypto.getRandomValues(new Uint8Array(12)),ciphertext=await crypto.subtle.encrypt({name:'AES-GCM',iv,additionalData},vault.automaticKey,new TextEncoder().encode(JSON.stringify(data)));
        await BoviOfflineStore.commitAutomaticWorkspace({...vault,iv,ciphertext,revision:crypto.randomUUID()},security.epoch,vault.revision);
    }''')
    page.reload(wait_until='networkidle');expect(page.locator('main')).to_contain_text('P51 actualización '+suffix,timeout=30000)
    assert state(page)['queue'][0]['operation_uuid']==pending['operation_uuid']
    limited=[]
    def limit_page(route):
        limited.append(route.request.url)
        if len(limited)==1:route.fulfill(status=429,headers={'Retry-After':'1'},json={'message':'Espera ficticia'})
        else:route.continue_()
    ctx.route('**/api/offline/v1/workspace/snapshots/*?page=2',limit_page)
    ctx.set_offline(False)
    wait_until(page,lambda:local_data(page)['schema']==3,'No se actualizó automáticamente la copia anterior')
    wait_synced(page);assert len([r for r in catalog(page) if r['record_uuid']==pending['record_uuid']])==1
    assert len(limited)>=2 and limited[0]==limited[1],'La descarga no retomó la misma página tras Retry-After'
    ctx.set_offline(True);go(page,'/dashboard')
    assert page.evaluate('Chart.getChart(document.querySelector("#produccionChart")).config.type')=='bar'
    page.locator('#btn-30dias').tap()
    assert page.evaluate('Chart.getChart(document.querySelector("#produccionChart")).data.labels.length')==30
    expect(page.locator('[data-chart-caption]')).to_have_text('Últimos 30 días')
    page.locator('#btn-anio').tap()
    assert page.evaluate('Chart.getChart(document.querySelector("#produccionChart")).data.labels.length')==12
    expect(page.locator('#btn-anio')).to_have_attribute('aria-pressed','true')
    assert page.evaluate('document.documentElement.scrollWidth<=innerWidth+2')
    page.screenshot(path=str(Path(tempfile.gettempdir())/'bovisoft-phase51-dashboard.png'),full_page=True)
    ctx.close();browser.close()
    print('PASS: copia Fase 4 actualizada automáticamente sin perder el pendiente; gráficas originales de barras, selector de 30 días y diseño de 390 px.',flush=True)

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--smoke',action='store_true');args=parser.parse_args()
    with sync_playwright() as p:
        (upgrade_and_chart if args.smoke else run)(p)
