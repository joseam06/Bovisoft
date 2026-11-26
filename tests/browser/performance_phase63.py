"""Bounded 100-animal mobile benchmark against the disposable performance router."""
import os,json,time,tempfile
from pathlib import Path
from playwright.sync_api import sync_playwright,expect,TimeoutError as BrowserTimeout
import automatic,consolidation
BASE='http://127.0.0.1:8097'
automatic.BASE=consolidation.BASE=BASE
def run(p):
    guards=bool(os.environ.get('BOVISOFT_PERF_GUARDS'))
    browser=p.chromium.launch(headless=True);ctx=browser.new_context(viewport={'width':390,'height':820},is_mobile=True,has_touch=True,service_workers='allow')
    ctx.route('**/*',lambda r:r.continue_() if r.request.url.startswith(BASE) else r.abort())
    helper=Path('public/workspace-store.js').read_text(encoding='utf-8')+'''
    if(typeof window!=='undefined'&&!self.BoviOfflineStore.commitAutomaticWorkspace.perf){
      const original=self.BoviOfflineStore.commitAutomaticWorkspace;
      const wrapped=function(...args){window.BoviPerfWrites=(window.BoviPerfWrites||0)+1;return original.apply(this,args);};
      wrapped.perf=true;self.BoviOfflineStore.commitAutomaticWorkspace=wrapped;
    }
    '''
    ctx.route('**/workspace-store.js*',lambda r:r.fulfill(body=helper,content_type='application/javascript; charset=utf-8'))
    page=ctx.new_page();cdp=ctx.new_cdp_session(page);cdp.send('Network.enable');cdp.send('Performance.enable');cdp.send('Emulation.setCPUThrottlingRate',{'rate':1 if guards else 4})
    if not guards:cdp.send('Network.emulateNetworkConditions',{'offline':False,'latency':100,'downloadThroughput':200000,'uploadThroughput':100000})
    requests=[];wire=[];cdp.on('Network.requestWillBeSent',lambda e:requests.append(e['request']['url']));cdp.on('Network.loadingFinished',lambda e:wire.append(e['encodedDataLength']))
    page.goto(BASE+'/login',wait_until='networkidle');page.locator('[name=email]').fill('performance@example.test');page.locator('[name=password]').fill(os.environ['BOVISOFT_BROWSER_TEST_PASSWORD'])
    start=time.perf_counter();page.locator('form[action$="/login"] button[type=submit]').tap();page.wait_for_url('**/dashboard')
    if os.environ.get('BOVISOFT_PERF_PREP_ONLY'):
        try:page.wait_for_function('document.body.dataset.offlineWarm==="true"',timeout=60000)
        except BrowserTimeout:
            result={'volume':int(os.environ['BOVISOFT_PERF_VOLUME']),'complete':False,'budget_seconds':60,'elapsed_seconds':round(time.perf_counter()-start,3)}
        else:
            data=consolidation.local_data(page);assert len(data['base']['animales'])==int(os.environ['BOVISOFT_PERF_VOLUME'])
            result={'volume':int(os.environ['BOVISOFT_PERF_VOLUME']),'complete':True,'preparation_seconds':round(time.perf_counter()-start,3),'encrypted_bytes':page.evaluate('BoviOfflineStore.security().then(s=>BoviOfflineStore.automaticWorkspace(s.accountId)).then(v=>v.ciphertext.byteLength)')}
        (Path(tempfile.gettempdir())/('bovisoft-performance-preparation-'+os.environ['BOVISOFT_PERF_VOLUME']+'.json')).write_text(json.dumps(result,indent=2));print(json.dumps(result),flush=True);browser.close();return
    consolidation.ready(page);prep=time.perf_counter()-start
    data=consolidation.local_data(page);assert len(data['base']['animales'])==100
    stored=page.evaluate('BoviOfflineStore.security().then(s=>BoviOfflineStore.automaticWorkspace(s.accountId)).then(v=>v.ciphertext.byteLength)')
    page.wait_for_timeout(3000);initial=page.evaluate('window.BoviPerfWrites||0');page.wait_for_timeout(2000 if guards else 32000);idle=page.evaluate('window.BoviPerfWrites||0')-initial
    metrics={r['name']:r['value'] for r in cdp.send('Performance.getMetrics')['metrics']}
    ctx.set_offline(True);start=time.perf_counter();automatic.offline_link(page,'/animales');expect(page.locator('main')).to_contain_text('Animal ficticio');navigation=time.perf_counter()-start
    first=next(iter(data['base']['animales'].values()));automatic.offline_link(page,'/animales/'+str(first['id'])+'/edit');page.locator('[name=observaciones]').fill('Edición ficticia de rendimiento'+(' '+str(time.time_ns()) if guards else ''));page.get_by_role('button',name='Guardar Cambios').tap();consolidation.wait_until(page,lambda:len(consolidation.local_data(page)['queue'])==1,'Cola no persistida')
    page.reload(wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"');assert len(consolidation.local_data(page)['queue'])==1
    start=time.perf_counter()
    if guards:
        second=ctx.new_page();second.goto(BASE+'/dashboard',wait_until='networkidle');second.wait_for_function('document.body.dataset.offlineReady==="true"')
        expect(second.locator('body')).to_contain_text('Rendimiento ficticio')
        identity=page.request.get(BASE+'/offline/session').json()
        assert page.request.post(BASE+'/logout',headers={'Accept':'application/json','X-CSRF-TOKEN':'invalid-test'}).status==419
        assert page.request.post(BASE+'/logout',headers={'Accept':'application/json','X-CSRF-TOKEN':identity['csrf_token']}).status==204
        ctx.set_offline(False);page.wait_for_function('BoviOfflineStore.security().then(s=>s.accountId===null)')
        expect(second.locator('body')).not_to_contain_text('Rendimiento ficticio')
        page.goto(BASE+'/login',wait_until='networkidle');page.locator('[name=email]').fill('performance@example.test');page.locator('[name=password]').fill(os.environ['BOVISOFT_BROWSER_TEST_PASSWORD']);page.locator('form[action$="/login"] button[type=submit]').tap();page.wait_for_url('**/dashboard');page.wait_for_function('document.body.dataset.offlineReady==="true"')
    else:ctx.set_offline(False)
    automatic.wait_synced(page);sync=time.perf_counter()-start
    result={'volume':100,'cpu_rate':4,'latency_ms':100,'download_bytes_sec':200000,'preparation_seconds':round(prep,3),'idle_32s_commits':idle,'encrypted_bytes':stored,'requests':len(requests),'encoded_bytes':sum(wire),'js_heap_bytes':metrics.get('JSHeapUsedSize'),'dom_nodes':metrics.get('Nodes'),'offline_navigation_seconds':round(navigation,3),'one_operation_sync_seconds':round(sync,3),'horizontal_overflow':page.evaluate('document.documentElement.scrollWidth>innerWidth+2')}
    if guards:result.update(mode='guards',cpu_rate=1,latency_ms=0,download_bytes_sec=None,idle_32s_commits=None)
    out=Path(tempfile.gettempdir())/('bovisoft-performance-browser-'+os.environ.get('BOVISOFT_PERF_LABEL','current')+'.json');out.write_text(json.dumps(result,indent=2));print(json.dumps(result),flush=True);assert not result['horizontal_overflow'];browser.close()
if __name__=='__main__':
    with sync_playwright() as p:run(p)
