"""One bounded 500→2000 journey, native UI, interrupted immutable snapshot."""
import os,json,time,tempfile,subprocess,uuid,sys,re
from pathlib import Path
from playwright.sync_api import sync_playwright,expect
import automatic,consolidation
BASE='http://127.0.0.1:8097';automatic.BASE=consolidation.BASE=BASE
def run(p):
    browser=p.chromium.launch(headless=True);ctx=browser.new_context(viewport={'width':390,'height':820},is_mobile=True,has_touch=True,service_workers='allow')
    ctx.route(re.compile(r'^https?://(?!127\.0\.0\.1:8097(?:/|$))'),lambda r:r.abort())
    # Inject failures at the page fetch boundary: SW-owned network requests are
    # not reliably intercepted by BrowserContext.route in all Chromium versions.
    ctx.add_init_script('''(()=>{window.snapshotPackets=[];window.snapshotRequests=[];window.sentOperations=[];window.pauseOperations=false;const original=window.fetch;window.fetch=async(...args)=>{
      const path=String(typeof args[0]==='string'?args[0]:args[0]?.url||'');
      if(path.includes('/workspace/changes?')&&window.expireCursor){window.expireCursor=false;return new Response(JSON.stringify({message:'Cursor vencido ficticio'}),{status:410,headers:{'Content-Type':'application/json'}});}
      if(path.includes('/workspace/snapshots/')){const n=Number(new URL(path,location.origin).searchParams.get('page'));window.snapshotRequests.push(n);if(n===2&&window.interruptSnapshot){window.interruptSnapshot=false;window.snapshotInterrupted=true;throw new TypeError('Descarga interrumpida en la prueba');}}
      if(path.endsWith('/workspace/operations')){if(window.pauseOperations)return new Response(JSON.stringify({message:'Envío pausado en la prueba'}),{status:503,headers:{'Content-Type':'application/json'}});window.sentOperations.push(JSON.parse(args[1].body).operation.operation_uuid);}
      const r=await original(...args);if(path.includes('/workspace/snapshots')&&r.ok)r.clone().text().then(text=>{const p=JSON.parse(text);if(p.format==='catalog-chunks-v1')window.snapshotPackets.push({id:p.snapshot_uuid,part:p.part,bytes:new TextEncoder().encode(text).length});}).catch(()=>{});return r;};})();''')
    page=ctx.new_page();page.set_default_timeout(20000);page.on('pageerror',lambda e:print('JS:',str(e),flush=True));cdp=ctx.new_cdp_session(page)
    cdp.send('Network.enable');cdp.send('Emulation.setCPUThrottlingRate',{'rate':4});cdp.send('Network.emulateNetworkConditions',{'offline':False,'latency':100,'downloadThroughput':200000,'uploadThroughput':100000})
    packets=[]
    page.goto(BASE+'/login',wait_until='networkidle',timeout=60000);page.locator('[name=email]').fill('performance@example.test');page.locator('[name=password]').fill(os.environ['BOVISOFT_BROWSER_TEST_PASSWORD'])
    start=time.perf_counter();page.locator('form[action$="/login"] button[type=submit]').tap();page.wait_for_url('**/dashboard');print('Login correcto; preparando 500.',flush=True)
    page.wait_for_timeout(15000);print('Progreso:',page.evaluate('({ready:document.body.dataset.offlineReady,warm:document.body.dataset.offlineWarm,parts:window.snapshotPackets?.map(p=>p.part),error:document.getElementById("offline-error")?.textContent})'),flush=True)
    page.wait_for_function('document.body.dataset.offlineWarm==="true"',timeout=150000)
    print('Interfaz guardada; leyendo catálogo local.',flush=True)
    initial=consolidation.local_data(page);assert len(initial['base']['animales'])==500
    print('Catálogo local de 500 confirmado.',flush=True)
    results={'animals500_preparation_seconds':round(time.perf_counter()-start,3),'initial_records':sum(map(len,initial['base'].values()))}
    ctx.set_offline(True)
    print('Red desconectada; navegando.',flush=True)
    start=time.perf_counter();automatic.offline_link(page,'/animales')
    print('Ruta de animales abierta.',flush=True)
    expect(page.locator('#tabla-animales tbody tr')).to_have_count(50,timeout=30000);results['animals500_navigation_seconds']=round(time.perf_counter()-start,3)
    search=page.locator('#busqueda,#buscar-animal,#buscar').first;search.fill('PERF-A-100');expect(page.locator('#tabla-animales tbody tr')).to_have_count(1);expect(page.locator('#tabla-animales tbody')).to_contain_text('PERF-A-100');search.fill('');expect(page.locator('#tabla-animales tbody tr')).to_have_count(50)
    page.locator('[data-record-pagination] button').last.tap();expect(page.locator('[data-record-pagination]')).to_contain_text('51–100')
    print('500 verificados:',json.dumps(results),flush=True)
    (Path(tempfile.gettempdir())/'bovisoft-browser631-partial.json').write_text(json.dumps(results,indent=2))
    subprocess.run(['php','tests/performance/local.php','grow','2000'],check=True)
    page.evaluate('window.expireCursor=true;window.interruptSnapshot=true;window.pauseOperations=true');ctx.set_offline(False)
    consolidation.wait_until(page,lambda:page.evaluate('!!window.snapshotInterrupted'),'No se interrumpió la segunda página',seconds=150);ctx.set_offline(True)
    print('Descarga interrumpida; comprobando copia anterior y pendiente.',flush=True)
    assert len(consolidation.local_data(page)['base']['animales'])==500
    marker='Pendiente 631 '+uuid.uuid4().hex[:8];automatic.offline_link(page,'/animales/1/edit');page.locator('[name=observaciones]').fill(marker);page.get_by_role('button',name='Guardar Cambios').tap()
    consolidation.wait_until(page,lambda:len(consolidation.local_data(page)['queue'])==1,'No se conservó el pendiente')
    operation=consolidation.local_data(page)['queue'][0]['operation_uuid'];before_requests=page.evaluate('window.snapshotRequests.length');start=time.perf_counter();ctx.set_offline(False)
    consolidation.wait_until(page,lambda:len(consolidation.local_data(page)['base']['animales'])==2000,'No terminó la preparación de 2000',seconds=180)
    print('Copia de 2000 confirmada.',flush=True)
    results['animals2000_resume_seconds']=round(time.perf_counter()-start,3)
    assert any(op['operation_uuid']==operation for op in consolidation.local_data(page)['queue'])
    assert 1 not in page.evaluate('window.snapshotRequests')[before_requests:],'Se descargó de nuevo una parte válida'
    packets=page.evaluate('window.snapshotPackets');assert packets and max(x['bytes'] for x in packets)<262144
    ctx.set_offline(True);automatic.offline_link(page,'/animales');start=time.perf_counter();page.reload(wait_until='networkidle');page.wait_for_function('document.body.dataset.offlineReady==="true"');expect(page.locator('#tabla-animales tbody tr')).to_have_count(50,timeout=30000);results['animals2000_reload_seconds']=round(time.perf_counter()-start,3)
    search=page.locator('#busqueda,#buscar-animal,#buscar').first;search.fill('PERF-A-500');expect(page.locator('#tabla-animales tbody tr')).to_have_count(1);expect(page.locator('#tabla-animales tbody')).to_contain_text('PERF-A-500')
    assert any(op['operation_uuid']==operation for op in consolidation.local_data(page)['queue'])
    results['encrypted_bytes']=page.evaluate('BoviOfflineStore.security().then(s=>BoviOfflineStore.automaticWorkspace(s.accountId)).then(v=>v.ciphertext.byteLength)')
    page.evaluate('window.pauseOperations=false');ctx.set_offline(False);automatic.wait_synced(page)
    assert page.evaluate('window.sentOperations').count(operation)==1
    actual=automatic.catalog(page);assert sum(r['module']=='animales' for r in actual)==2000;assert next(r for r in actual if r['module']=='animales' and r['id']==1)['observaciones']==marker
    assert sum(r['animales_actuales'] for r in actual if r['module']=='potreros')==2000
    results.update(max_packet_bytes=max(x['bytes'] for x in packets),snapshot_packets=len(packets),download_bytes=sum(x['bytes'] for x in packets),pending_preserved=True,filter_all_records=True,one_server_write=True)
    assert not page.evaluate('document.documentElement.scrollWidth>innerWidth+2')
    (Path(tempfile.gettempdir())/'bovisoft-browser631.json').write_text(json.dumps(results,indent=2));print(json.dumps(results),flush=True);browser.close()
if __name__=='__main__':
    if os.environ.get('BOVISOFT_BROWSER_TRACE'):
        def trace(frame,event,arg):
            if event=='line' and frame.f_code.co_filename==__file__ and frame.f_code.co_name=='run':print('STEP',frame.f_lineno,flush=True)
            return trace
        sys.settrace(trace)
    with sync_playwright() as p:run(p)
