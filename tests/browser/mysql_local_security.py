"""Focused artisan-serve/MySQL check; requires explicitly seeded fictitious users."""
import os,uuid
from playwright.sync_api import sync_playwright,expect
import automatic,consolidation
BASE='http://127.0.0.1:8098'
automatic.BASE=consolidation.BASE=BASE
def run(p):
    browser=p.chromium.launch(headless=True);ctx=browser.new_context(service_workers='allow');ctx.route('**/*',lambda r:r.continue_() if r.request.url.startswith(BASE) else r.abort())
    page=ctx.new_page();failures=[];ctx.on('response',lambda r:failures.append(r.status) if r.status>=500 or r.status==419 else None)
    def login():
        page.goto(BASE+'/login',wait_until='networkidle');page.locator('[name=email]').fill('mysql-legacy@example.test');page.locator('[name=password]').fill(os.environ['BOVISOFT_BROWSER_TEST_PASSWORD']);page.locator('form[action$="/login"] button[type=submit]').click();page.wait_for_url('**/dashboard');consolidation.ready(page)
    login();page.goto(BASE+'/perfil',wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"');expect(page.locator('[data-account-security-verified]')).to_have_text('Sin verificar')
    ctx.set_offline(True);name='Pendiente MySQL '+uuid.uuid4().hex[:8];page.locator('[name=name]').fill(name);page.locator('main form').filter(has=page.locator('[name=name]')).locator('button[type=submit]').click();consolidation.wait_until(page,lambda:len(consolidation.local_data(page)['queue'])==1,'No se conservó el pendiente')
    page.reload(wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"');expect(page.locator('[name=name]')).to_have_value(name)
    page.locator('#user-menu-btn').click();page.locator('form[action$="/logout"] button[type=submit]').click();page.wait_for_url('**/dashboard');ctx.set_offline(False);page.wait_for_function('BoviOfflineStore.security().then(s=>!s.pendingLogout&&s.accountId===null)');login();automatic.wait_synced(page)
    assert next(r for r in automatic.catalog(page) if r['module']=='perfil')['name']==name
    page.locator('#user-menu-btn').click();page.locator('form[action$="/logout"] button[type=submit]').click();page.wait_for_url(BASE+'/')
    page.goto(BASE+'/register',wait_until='networkidle');email='mysql-new-'+uuid.uuid4().hex[:8]+'@example.test'
    for key,value in [('name','Registro ficticio MySQL'),('email',email),('password',os.environ['BOVISOFT_BROWSER_TEST_PASSWORD']),('password_confirmation',os.environ['BOVISOFT_BROWSER_TEST_PASSWORD'])]:page.locator('[name='+key+']').fill(value)
    page.locator('form[action$="/register"] button[type=submit]').click();page.wait_for_url('**/email/verify');page.goto(BASE+'/perfil',wait_until='networkidle');expect(page.locator('[data-account-security-verified]')).to_have_text('Sin verificar')
    page.goto(BASE+'/fincas',wait_until='networkidle');assert page.url.endswith('/email/verify')
    session=page.request.get(BASE+'/offline/session').json();headers={'Accept':'application/json','X-CSRF-TOKEN':session['csrf_token']}
    # Registration is guest-only; check duplicates with a guest session below.
    assert page.request.post(BASE+'/logout',headers=headers).status==204
    session=page.request.get(BASE+'/offline/session').json();headers['X-CSRF-TOKEN']=session['csrf_token']
    assert page.request.post(BASE+'/register',headers=headers,data={'name':'Duplicado','email':email,'password':os.environ['BOVISOFT_BROWSER_TEST_PASSWORD'],'password_confirmation':os.environ['BOVISOFT_BROWSER_TEST_PASSWORD']}).status==422
    assert page.request.post(BASE+'/forgot-password',headers=headers,data={'email':'mysql-recovery@example.test'}).status==200
    assert not failures,failures
    browser.close();print('PASS MySQL/artisan serve: login heredado sin verificar, perfil, registro y duplicados, restricción de verificación, recuperación array y pendiente offline tras recarga/logout/relogin sin 419 ni 500.',flush=True)
if __name__=='__main__':
    with sync_playwright() as p:run(p)
