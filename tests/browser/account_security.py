"""Focused 6.1 profile/credential/offline journey; fictitious router, fake mail only."""
import json,tempfile,uuid
from pathlib import Path
from playwright.sync_api import sync_playwright,expect
import automatic,consolidation,responsive,remaining_integrated

BASE='http://127.0.0.1:8096'
for module in [automatic,consolidation,responsive,remaining_integrated]:module.BASE=BASE
OUT=Path(tempfile.gettempdir())/'bovisoft-account-security-evidence'

def change_email(page,email):
    page.wait_for_function('document.body.dataset.offlineReady==="true"')
    form=page.locator('form[action$="/perfil/seguridad/correo"]')
    form.locator('[name=new_email]').fill(email)
    form.locator('[name=current_password]').fill(automatic.test_password())
    captured=[]
    def delivery(route):
        response=route.fetch(max_redirects=0)
        assert response.status==302 and 'no-store' in response.headers['cache-control']
        captured.append(response.headers.get('x-bovi-test-confirmation'))
        route.fulfill(response=response)
    page.context.route('**/perfil/seguridad/correo',delivery)
    try:
        with page.expect_navigation(wait_until='networkidle'):
            form.locator('button[type=submit]').tap()
    finally:page.context.unroute('**/perfil/seguridad/correo',delivery)
    assert captured and captured[0],'El transporte ficticio no recibió el correo'
    link=captured[0]
    assert page.locator('[data-account-security-email]').inner_text()!=email
    page.goto(link,wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineReady==="true"')
    expect(page.locator('[data-account-security-email]')).to_have_text(email)
    assert page.url==BASE+'/perfil'

def run(p):
    OUT.mkdir(exist_ok=True)
    directory=Path(tempfile.mkdtemp(prefix='bovisoft-p61-browser-'))
    ctx=remaining_integrated.open_context(p,directory);page=ctx.new_page();failures=[]
    ctx.on('response',lambda r:failures.append(r.status) if r.status==419 else None)
    automatic.login(page);consolidation.ready(page)
    page.goto(BASE+'/perfil',wait_until='networkidle')
    page.wait_for_function('document.body.dataset.offlineReady==="true"')
    assert 'no-store' in page.request.get(BASE+'/perfil').headers['cache-control']
    owner=page.evaluate('BoviOfflineStore.security().then(s=>s.accountId)')
    vault=page.evaluate('BoviOfflineStore.automaticWorkspace('+json.dumps(owner)+').then(v=>v.generation)')
    for width in [390,1440]:
        page.set_viewport_size({'width':width,'height':820});responsive.measure(page,'profile-security-'+str(width))
        page.locator('#seguridad').scroll_into_view_if_needed();page.screenshot(path=str(OUT/f'profile-{width}.png'))
    page.set_viewport_size({'width':390,'height':820})
    assert not responsive.issues,responsive.issues
    ctx.set_offline(True)
    page.locator('[name=name]').fill('Pendiente seguridad '+uuid.uuid4().hex[:8])
    page.locator('main form').filter(has=page.locator('[name=name]')).locator('button[type=submit]').tap()
    consolidation.wait_until(page,lambda:len(consolidation.local_data(page)['queue'])==1,'No se guardó el nombre')
    operation=consolidation.local_data(page)['queue'][0]['operation_uuid']
    page.reload(wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"')
    expect(page.locator('[data-account-security-verified]')).to_have_text('Verificado')
    expect(page.locator('[data-account-security-google]')).to_have_text('Sin vincular')
    assert page.locator('[data-online-sensitive]:visible').count()==0
    # Hold the queue while online credential forms complete. It must survive
    # the session rotation without acquiring a new owner or offline vault.
    ctx.route('**/api/offline/v1/workspace/operations',lambda r:r.fulfill(status=503,json={'message':'Interrupción ficticia'}))
    ctx.set_offline(False);page.goto(BASE+'/perfil',wait_until='networkidle')
    changed='seguridad-'+uuid.uuid4().hex[:8]+'@example.test'
    changed_ok=False
    try:
        change_email(page,changed)
        changed_ok=True
        assert page.evaluate('BoviOfflineStore.security().then(s=>s.accountId)')==owner
        assert page.evaluate('BoviOfflineStore.automaticWorkspace('+json.dumps(owner)+').then(v=>v.generation)')==vault
        data=consolidation.local_data(page)
        assert any(op['operation_uuid']==operation for op in data['queue'])
        serialized=json.dumps({'views':data['views'],'pages':data.get('pages',{})})
        for forbidden in ['name="nonce"','name="current_password"','/perfil/seguridad/correo/','signature=','pending_email_token_hash']:
            assert forbidden not in serialized,forbidden
        # The established first-password flow and alternate guards are covered
        # on PostgreSQL; here preserve a queued name across an ordinary change.
        form=page.locator('form[action$="/perfil/password"]')
        form.locator('[name=current_password]').fill(automatic.test_password())
        form.locator('[name=password]').fill(automatic.test_password())
        form.locator('[name=password_confirmation]').fill(automatic.test_password())
        with page.expect_navigation(wait_until='networkidle'):
            form.locator('button[type=submit]').tap()
        expect(page.locator('main')).to_contain_text('Contraseña actualizada correctamente.')
        page.reload(wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"');expect(page.locator('#seguridad')).to_be_visible()
        assert any(op['operation_uuid']==operation for op in consolidation.local_data(page)['queue'])
    except Exception as failure:
        page.screenshot(path=str(OUT/'failure.png'))
        print('Diagnóstico: ruta',page.url.split('?')[0],'acciones',page.locator('[data-online-sensitive]').count(),'error',type(failure).__name__,flush=True)
        raise
    finally:
        if changed_ok and page.url.startswith(BASE):
            page.goto(BASE+'/perfil',wait_until='networkidle');change_email(page,'offline-a@example.test')
    ctx.unroute('**/api/offline/v1/workspace/operations')
    page.evaluate("dispatchEvent(new Event('online'))")
    automatic.wait_synced(page)
    assert next(r for r in automatic.catalog(page) if r['module']=='perfil')['name'].startswith('Pendiente seguridad ')
    assert not failures,'Se recibió un error 419'
    print('PASS Chromium: perfil 390/1440, nombre pendiente tras recarga, cambio de correo y contraseña sin perder propietario/bóveda/cola, sincronización y enlaces sensibles fuera de las vistas locales.',flush=True)
    ctx.close()
    # New accounts can correct their address but cannot enter record routes.
    browser=p.chromium.launch(headless=True);ctx=browser.new_context(viewport={'width':390,'height':820},has_touch=True)
    ctx.route('**/*',lambda r:r.continue_() if r.request.url.startswith(BASE) else r.abort())
    page=ctx.new_page();page.goto(BASE+'/register',wait_until='networkidle')
    for name,value in [('name','Productor nuevo ficticio'),('email','nuevo-'+uuid.uuid4().hex[:8]+'@example.test'),('password',automatic.test_password()),('password_confirmation',automatic.test_password())]:page.locator('[name='+name+']').fill(value)
    page.locator('form[action$="/register"] button[type=submit]').tap();page.wait_for_url('**/email/verify')
    responsive.measure(page,'verify-email-390');page.screenshot(path=str(OUT/'verification-390.png'))
    page.locator('a[href$="/perfil#seguridad"]').tap();expect(page.locator('[data-account-security-verified]')).to_have_text('Sin verificar')
    page.goto(BASE+'/fincas',wait_until='networkidle');assert page.url.endswith('/email/verify')
    assert not responsive.issues,responsive.issues
    browser.close();print('PASS Chromium: registro nuevo restringido y acceso a corrección de correo; navegación y acciones táctiles nativas.',flush=True)

if __name__=='__main__':
    with sync_playwright() as p:run(p)
