"""Four bounded UI journeys, local fictitious dataset; Chromium emulation only."""
import argparse
import json
import tempfile
import time
import uuid
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
from automatic import BASE, login, catalog, state, wait_synced
from consolidation import ready, wait_until

OUT=Path(tempfile.gettempdir())/'bovisoft-responsive-evidence'
ASSETS=Path(tempfile.gettempdir())/'bovisoft-responsive-assets'
issues=[];visited=[]


def context(browser,width,touch=True):
    ctx=browser.new_context(viewport={'width':width,'height':820},has_touch=touch,is_mobile=touch,reduced_motion='reduce',service_workers='allow')
    def resources(route):
        url=route.request.url
        if url.startswith(BASE):route.continue_();return
        asset='chart.js' if url.startswith('https://cdn.jsdelivr.net/npm/chart.js') else 'leaflet.css' if url.endswith('/leaflet.css') else 'leaflet.js' if url.endswith('/leaflet.js') else None
        if asset and (ASSETS/asset).exists():route.fulfill(path=str(ASSETS/asset),content_type='text/css' if asset.endswith('css') else 'application/javascript')
        else:route.abort()
    ctx.route('**/*',resources)
    return ctx


def measure(page,label):
    page.wait_for_function('!document.fonts || document.fonts.status==="loaded"')
    result=page.evaluate('''()=>{
        const width=innerWidth;
        const offenders=[...document.querySelectorAll('main input,main select,main textarea,main button,main a')].filter(e=>{
            if(!e.getClientRects().length||e.closest('.bovi-table-scroll,[inert]'))return false;
            const r=e.getBoundingClientRect();return r.width>0&&(r.right>width+2||r.left<-2);
        }).slice(0,6).map(e=>({tag:e.tagName,name:e.name||e.textContent.trim().slice(0,45),rect:Math.round(e.getBoundingClientRect().right)}));
        return {width,scroll:document.documentElement.scrollWidth,offenders};
    }''')
    visited.append({'path':label,**result})
    if result['scroll']>result['width']+2 or result['offenders']:
        issues.append({'path':label,**result})
        page.screenshot(path=str(OUT/('overflow-'+str(len(issues))+'.png')),full_page=False)
    return result


def visit(page,path):
    response=page.goto(BASE+path,wait_until='networkidle')
    if response and response.status>=400:
        issues.append({'path':path,'http':response.status,'body':page.locator('body').inner_text()[:300]});return
    measure(page,path)


def narrow(browser):
    ctx=context(browser,360);page=ctx.new_page()
    for path in ['/','/login','/register','/forgot-password','/reset-password/fictitious-token?email=offline-a%40example.test']:
        visit(page,path)
    page.goto(BASE+'/',wait_until='networkidle')
    page.locator('#public-nav-toggle').tap();expect(page.locator('#public-nav')).to_be_visible()
    page.locator('#public-nav a[href="#contacto"]').tap()
    page.locator('[data-open=login]').first.tap()
    expect(page.locator('#authPanel')).to_be_visible();measure(page,'public-auth-modal')
    panel=page.locator('#authPanel').bounding_box();assert panel['x']>=0 and panel['x']+panel['width']<=361
    page.locator('#authClose').tap()
    login(page);ready(page)
    page.locator('#nav-toggle').tap();expect(page.locator('#nav-toggle')).to_have_attribute('aria-expanded','true')
    page.screenshot(path=str(OUT/'navigation-360.png'))
    page.locator('#sidebar').get_by_role('link',name='Ganado',exact=True).tap()
    expect(page).to_have_url(BASE+'/animales');expect(page.locator('#nav-toggle')).to_have_attribute('aria-expanded','false')
    rows=catalog(page);animal=next(r for r in rows if r['module']=='animales' and r['codigo']=='OFF-LIST-0');farm=next(r for r in rows if r['module']=='fincas')
    paths=['/dashboard','/fincas','/potreros','/animales','/salud','/produccion','/finanzas','/alertas','/reportes','/perfil',
           '/fincas/'+str(farm['id'])+'/edit','/animales/'+str(animal['id'])+'/edit','/potreros/create','/salud/create','/produccion/create','/finanzas/ingresos/nuevo','/finanzas/egresos/nuevo']
    for path in paths:
        visit(page,path)
        if path in ['/dashboard','/animales/'+str(animal['id'])+'/edit']:
            page.screenshot(path=str(OUT/('dashboard-360.png' if path=='/dashboard' else 'form-360.png')))
    page.goto(BASE+'/perfil',wait_until='networkidle')
    page.evaluate("Object.defineProperty(navigator.mediaDevices,'getUserMedia',{configurable:true,value:()=>Promise.reject(new DOMException('Fictitious denied camera','NotAllowedError'))})")
    page.get_by_role('button',name='Tomar foto',exact=False).tap()
    expect(page.locator('#modal-camara')).to_be_visible()
    modal=page.locator('#modal-camara > div').bounding_box();assert modal['x']>=0 and modal['x']+modal['width']<=361 and modal['height']<=800
    page.screenshot(path=str(OUT/'camera-modal-360.png'))
    page.get_by_role('button',name='Cerrar cámara',exact=True).tap();expect(page.locator('#modal-camara')).to_be_hidden()
    ctx.close();print('PASS: 360 - all module indexes, representative forms, auth, navigation and camera dialog',flush=True)


def tablet(browser):
    ctx=context(browser,768);page=ctx.new_page();login(page);ready(page)
    for path in ['/dashboard','/fincas','/salud/preventivo','/finanzas']:
        visit(page,path)
    page.locator('#user-menu-btn').tap();expect(page.locator('#user-dropdown')).to_be_visible()
    box=page.locator('#user-dropdown').bounding_box();assert box['x']>=0 and box['x']+box['width']<=769
    page.locator('#nav-toggle').tap();page.keyboard.press('Escape');expect(page.locator('#nav-toggle')).to_have_attribute('aria-expanded','false')
    page.set_viewport_size({'width':1024,'height':768})
    expect(page.locator('#nav-toggle')).to_be_visible()
    page.locator('#nav-toggle').tap();expect(page.locator('#nav-toggle')).to_have_attribute('aria-expanded','true')
    page.locator('#nav-close').tap();measure(page,'tablet-landscape-1024')
    page.set_viewport_size({'width':768,'height':820})
    page.goto(BASE+'/dashboard',wait_until='networkidle');page.screenshot(path=str(OUT/'dashboard-768.png'))
    ctx.close();print('PASS: 768 - grids, profile dropdown, menu and financial/chart containers',flush=True)


def desktop(browser):
    ctx=context(browser,1440,touch=False);page=ctx.new_page();login(page);ready(page)
    visit(page,'/dashboard');page.mouse.move(900,500)
    layout=page.evaluate("({sidebar:document.querySelector('#sidebar').getBoundingClientRect().width,margin:getComputedStyle(document.querySelector('#app-content')).marginLeft,header:getComputedStyle(document.querySelector('.app-header')).position})")
    assert layout=={'sidebar':80,'margin':'80px','header':'sticky'},layout
    charts=page.evaluate("[...document.querySelectorAll('canvas')].filter(e=>window.Chart&&Chart.getChart(e)).length")
    assert charts>=1,'No se comprobó una gráfica real de Chart.js'
    for node in page.locator('canvas,.leaflet-container').all():
        box=node.bounding_box()
        if box:assert box['width']<=page.locator('main').bounding_box()['width']
    page.screenshot(path=str(OUT/'dashboard-1440.png'))
    visit(page,'/animales')
    expect(page.locator('.bovi-table-scroll').first).to_have_attribute('tabindex','0')
    ctx.close();print('PASS: 1440 - desktop shell unchanged, real charts/maps and table scroll region',flush=True)


def offline_touch(browser):
    ctx=context(browser,390);page=ctx.new_page();login(page);ready(page)
    ctx.set_offline(True);suffix=uuid.uuid4().hex[:7]
    page.locator('#nav-toggle').tap();page.locator('#sidebar').get_by_role('link',name='Fincas',exact=True).tap()
    page.locator('main a[href$="/fincas/create"]').first.tap()
    page.locator('main [name=nombre]').fill('Finca táctil '+suffix)
    page.locator('main [name=latitud]').fill('4.55');page.locator('main [name=longitud]').fill('-74.1')
    page.locator('main form button[type=submit]').tap()
    expect(page.locator('main')).to_contain_text('Finca táctil '+suffix)
    farm=page.url.split('/')[-1];measure(page,'390-offline-farm')
    page.locator('#nav-toggle').tap();page.locator('#sidebar').get_by_role('link',name='Ganado',exact=True).tap()
    page.locator('main a[href$="/animales/create"]').first.tap()
    page.locator('main [name=nombre]').fill('Animal táctil '+suffix)
    page.locator('main [name=tipo]').select_option('vaca');page.locator('main [name=sexo]').select_option('hembra')
    page.locator('main [name=finca_id]').select_option(farm)
    page.locator('main form button[type=submit]').tap();expect(page.locator('main')).to_contain_text('Animal táctil '+suffix)
    animal=page.url.split('/')[-1]
    page.locator('main a[href$="/edit"]').first.tap()
    page.locator('main [name=observaciones]').fill('Guardado con interacción táctil real de Playwright')
    page.get_by_role('button',name='Guardar Cambios').tap();expect(page.locator('main')).to_contain_text('Guardado con interacción táctil')
    measure(page,'390-offline-animal');page.screenshot(path=str(OUT/'animal-offline-390.png'))
    delete=page.locator('main form').filter(has=page.locator('input[name=_method][value=DELETE]')).locator('button')
    delete.tap()
    page.get_by_role('button',name='Deshacer eliminación',exact=True).tap()
    wait_until(page,lambda:not any(op['type']=='delete' for op in state(page)['queue']),'No se confirmó el deshacer')
    page.reload(wait_until='networkidle');expect(page.locator('main')).to_contain_text('Animal táctil '+suffix,timeout=30000)
    measure(page,'390-reload-table')
    region=page.locator('.bovi-table-scroll').first
    assert region.evaluate('(e)=>e.scrollWidth>e.clientWidth'),'Falta el desplazamiento horizontal de la tabla'
    ctx.set_offline(False);wait_synced(page)
    rows=catalog(page);saved=next(r for r in rows if r['record_uuid']==animal)
    assert saved['finca_uuid']==farm and saved['version']==2
    assert len([r for r in rows if r['record_uuid']==animal])==1
    ctx.close();print('PASS: 390 - real taps only, offline farm/animal create/edit/delete/undo, reload and sync once',flush=True)


def main():
    parser=argparse.ArgumentParser();parser.add_argument('--group',default='all',choices=['all','360','768','1440','offline']);args=parser.parse_args()
    OUT.mkdir(exist_ok=True)
    with sync_playwright() as p:
        browser=p.chromium.launch(headless=True)
        try:
            for name,run in [('360',narrow),('768',tablet),('1440',desktop),('offline',offline_touch)]:
                if args.group in ['all',name]:run(browser)
        except Exception as exc:
            for ctx in browser.contexts:
                for page in ctx.pages:
                    try:
                        print(json.dumps({'url':page.url,'body':page.locator('body').inner_text()[-1800:]},ensure_ascii=True))
                        page.screenshot(path=str(OUT/'failure.png'))
                    except Exception:pass
            print(str(exc)[:2400]);raise SystemExit(1)
        finally:
            (OUT/('report-'+args.group+'.json')).write_text(json.dumps({'issues':issues,'visited':visited,'physical_devices':False},ensure_ascii=False,indent=2),encoding='utf-8')
            browser.close()
    if issues:print(json.dumps(issues,ensure_ascii=True));raise SystemExit(1)


if __name__=='__main__':main()
