"""Local MariaDB/Artisan authentication destination regression."""
import os,subprocess
from playwright.sync_api import sync_playwright,expect
BASE='http://127.0.0.1:8000'
def run(p):
    browser=p.chromium.launch(headless=True);ctx=browser.new_context(service_workers='allow');ctx.route('**/*',lambda r:r.continue_() if r.request.url.startswith(BASE) else r.abort());page=ctx.new_page()
    old_worker=subprocess.check_output(['git','show','HEAD:public/sw.js']).decode('utf-8')
    ctx.route('**/sw.js',lambda r:r.fulfill(body=old_worker,content_type='application/javascript; charset=utf-8'))
    ctx.on('request',lambda r:print('POST',r.url.split('?')[0],'referer',r.headers.get('referer'),flush=True) if r.method=='POST' and r.url.endswith('/login') else None)
    ctx.on('response',lambda r:print('LOGIN response',r.status,r.headers.get('location'),flush=True) if r.url.endswith('/login') and r.request.method=='POST' else None)
    page.goto(BASE+'/login',wait_until='networkidle');page.wait_for_function('navigator.serviceWorker.controller!==null')
    identity=page.request.get(BASE+'/offline/session').json()
    expired=page.request.post(BASE+'/fincas',headers={'X-CSRF-TOKEN':identity['csrf_token'],'Accept':'text/html','Referer':BASE+'/sw.js'},data={})
    print('Solicitud protegida expirada:',expired.status,flush=True)
    page.locator('[name=email]').fill('mysql-legacy@example.test');page.locator('[name=password]').fill(os.environ['BOVISOFT_BROWSER_TEST_PASSWORD']);page.locator('form[action$="/login"] button[type=submit]').click();page.wait_for_load_state('networkidle');page.wait_for_timeout(2500)
    expect(page).to_have_url(BASE+'/dashboard');expect(page.locator('main')).to_be_visible();assert 'importScripts' not in page.locator('body').inner_text()
    print('PASS worker anterior: destino Dashboard y contenido HTML correcto; sesión contaminada reparada.',flush=True)
    page.locator('#user-menu-btn').click();page.locator('form[action$="/logout"] button[type=submit]').click();page.wait_for_url(BASE+'/')
    page.goto(BASE+'/login',wait_until='networkidle');page.locator('[name=email]').fill('mysql-legacy@example.test');page.locator('[name=password]').fill('incorrecta');page.locator('form[action$="/login"] button[type=submit]').click();expect(page.locator('body')).to_contain_text('Credenciales incorrectas');expect(page).to_have_url(BASE+'/login')
    ctx.unroute('**/sw.js');page.reload(wait_until='networkidle');page.wait_for_function('navigator.serviceWorker.controller!==null')
    page.locator('[name=email]').fill('mysql-legacy@example.test');page.locator('[name=password]').fill(os.environ['BOVISOFT_BROWSER_TEST_PASSWORD']);page.locator('form[action$="/login"] button[type=submit]').click();expect(page).to_have_url(BASE+'/dashboard');expect(page.locator('main')).to_be_visible()
    print('PASS credenciales incorrectas y actualización automática del worker sin borrar almacenamiento.',flush=True)
    browser.close()
    import mysql_local_security,automatic,consolidation
    mysql_local_security.BASE=automatic.BASE=consolidation.BASE=BASE
    mysql_local_security.run(p)
if __name__=='__main__':
    with sync_playwright() as p:run(p)
