"""Two bounded fault-injection journeys for the shared sync core; fictitious local data only."""
import json
import tempfile
import time
import uuid
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
from automatic import BASE, context, login, logout, catalog, state, wait_synced, offline_link, test_password
from consolidation import ready, wait_until, local_data


def create_farm(page, name):
    offline_link(page,'/fincas/create')
    page.locator('main [name=nombre]').fill(name)
    page.locator('main [name=latitud]').fill('4.551')
    page.locator('main [name=longitud]').fill('-74.123')
    page.locator('main form button[type=submit]').click()
    expect(page.locator('main')).to_contain_text(name)
    return page.url.split('/')[-1]


def fulfill(route,response,body=None):
    route.fulfill(response=response,json=body or response.json())


def leases_and_confirmations(browser):
    ctx=context(browser)
    ctx.add_init_script("Object.defineProperty(Navigator.prototype,'locks',{get:()=>undefined,configurable:true})")
    page=ctx.new_page();login(page);ready(page)
    other=ctx.new_page();other.goto(BASE+'/dashboard',wait_until='networkidle');ready(other)
    ctx.set_offline(True)
    suffix=uuid.uuid4().hex[:8]
    farm=create_farm(page,'F4 lease '+suffix)
    offline_link(page,'/animales/create?finca='+farm)
    page.locator('main [name=nombre]').fill('F4 child '+suffix)
    page.locator('main [name=tipo]').select_option('vaca')
    page.locator('main [name=sexo]').select_option('hembra')
    page.locator('main form button[type=submit]').click()
    expect(page.locator('main')).to_contain_text('F4 child '+suffix)
    animal=page.url.split('/')[-1]
    sent=[];held=[];mode={'hold':True}
    def transport(route):
        wire=route.request.post_data_json['operation'];sent.append(wire)
        if len(sent)==1:
            route.fulfill(status=429,headers={'Retry-After':'2'},json={'message':'Límite ficticio'});return
        response=route.fetch()
        if mode['hold'] and wire['module']=='fincas':held.append((route,response));return
        fulfill(route,response)
    ctx.route('**/api/offline/v1/workspace/operations',transport)
    ctx.set_offline(False)
    wait_until(page,lambda:len(sent)==1,'No se intentó enviar')
    page.wait_for_timeout(1100)
    assert len(sent)==1,'Otra pestaña ignoró Retry-After'
    wait_until(page,lambda:len(held)==1,'No se reintentó la operación')
    page.wait_for_timeout(1500)
    assert len(sent)==2,'Hubo emisores simultáneos con una reserva vigente'
    route,response=held[0];invalid=response.json();invalid['operation_uuid']=str(uuid.uuid4())
    fulfill(route,response,invalid)
    wait_until(page,lambda:len(held)==2,'No se conservó/reintentó la confirmación inválida')
    assert len(state(page)['queue'])==2 and all(op['module']=='fincas' for op in sent)
    old_fence=page.evaluate("async()=>{const db=await BoviOfflineStore.open();return new Promise((resolve,reject)=>{const tx=db.transaction('state','readwrite'),s=tx.objectStore('state'),r=s.get('sync-lease');let fence;r.onsuccess=()=>{fence=r.result.fence;s.put({...r.result,expiresAt:0},'sync-lease')};tx.oncomplete=()=>resolve(fence);tx.onerror=()=>reject(tx.error)})}")
    wait_until(page,lambda:len(held)>=3,'La otra pestaña no recuperó la reserva vencida')
    new_fence=page.evaluate("async()=>{const db=await BoviOfflineStore.open();return new Promise(r=>{const q=db.transaction('state').objectStore('state').get('sync-lease');q.onsuccess=()=>r(q.result.fence)})}")
    assert new_fence>old_fence
    try:fulfill(*held[1])
    except Exception:pass  # The old request may already have been aborted by the heartbeat.
    page.wait_for_timeout(300)
    assert len(state(page)['queue'])==2,'Una confirmación de un emisor vencido consumió la cola'
    mode['hold']=False;fulfill(*held[2]);wait_synced(page)
    rows=catalog(page)
    parents=[r for r in rows if r['record_uuid']==farm];children=[r for r in rows if r['record_uuid']==animal]
    assert len(parents)==len(children)==1
    assert parents[0]['version']==children[0]['version']==1 and children[0]['finca_uuid']==farm
    parent_posts=[op for op in sent if op['module']=='fincas']
    assert all(op==parent_posts[0] for op in parent_posts),'El reintento cambió el UUID o el payload'
    assert sent[-1]['module']=='animales'
    ctx.close();print('PASS: sin Web Locks, dos pestañas, Retry-After compartido, ACK inválido, reserva vencida y finca→animal sin duplicados.',flush=True)


def delayed_account_response(p):
    profile=Path(tempfile.mkdtemp(prefix='bovisoft-f4-browser-'))
    def launch():
        ctx=p.chromium.launch_persistent_context(str(profile),headless=True,service_workers='allow',viewport={'width':1365,'height':950})
        ctx.route('**/*',lambda route:route.continue_() if route.request.url.startswith(BASE) else route.abort())
        return ctx
    ctx=launch();page=ctx.pages[0];login(page);ready(page)
    other=ctx.new_page();other.goto(BASE+'/dashboard',wait_until='networkidle');ready(other)
    account=page.evaluate('BoviOfflineStore.security().then(s=>s.accountId)')
    ctx.set_offline(True);name='F4 cuenta A '+uuid.uuid4().hex[:8];farm=create_farm(page,name)
    sent=[];held=[]
    def hold(route):
        sent.append(route.request.post_data_json['operation']);held.append((route,route.fetch()))
    ctx.route('**/api/offline/v1/workspace/operations',hold)
    ctx.set_offline(False);wait_until(page,lambda:len(held)==1,'No llegó la escritura de A')
    revision=page.evaluate('id=>BoviOfflineStore.automaticWorkspace(id).then(v=>v.revision)',account)
    ctx.clear_cookies();other.goto(BASE+'/login',wait_until='networkidle')
    login(other,'b');ready(other)
    assert not state(other)['queue'] and name not in json.dumps(local_data(other))
    try:fulfill(*held[0])
    except Exception:pass
    other.wait_for_timeout(500)
    assert not state(other)['queue'] and name not in json.dumps(local_data(other))
    assert other.evaluate('id=>BoviOfflineStore.automaticWorkspace(id).then(v=>v.revision)',account)==revision
    logout(other);other.wait_for_url(BASE+'/');ctx.close()
    ctx=launch();page=ctx.pages[0]
    retries=[]
    def track(route):retries.append(route.request.post_data_json['operation']);route.continue_()
    ctx.route('**/api/offline/v1/workspace/operations',track)
    page.goto(BASE+'/login',wait_until='networkidle')
    page.locator('[name=email]').fill('offline-a@example.test');page.locator('[name=password]').fill(test_password())
    page.locator('form[action$="/login"] button[type=submit]').click();page.wait_for_url('**/dashboard')
    ready(page);wait_synced(page)
    rows=[r for r in catalog(page) if r['record_uuid']==farm]
    assert len(rows)==1 and rows[0]['version']==1
    assert retries and all(op==sent[0] for op in retries)
    ctx.close();print('PASS: sesión vencida, respuesta tardía de A bloqueada en B y recuperación tras cerrar/reabrir Chromium con un único efecto.',flush=True)


if __name__=='__main__':
    with sync_playwright() as p:
        browser=p.chromium.launch(headless=True)
        leases_and_confirmations(browser);browser.close()
        delayed_account_response(p)
