"""Bounded independent processes: a large PDF cannot terminate other checks."""
import os,subprocess,tempfile,json
from pathlib import Path
results=[]
for kind in ['inventario','salud','vacunacion','tratamientos','historial','financiero']:
    r=subprocess.run(['php','tests/run-pilot.php','mysql','PerformanceReports631Test'],env=dict(os.environ,BOVISOFT_PDF_KIND=kind),capture_output=True,text=True,encoding='utf-8',errors='replace')
    path=Path(tempfile.gettempdir())/('bovisoft-pdf631-mysql-'+kind+'.json')
    result=json.loads(path.read_text()) if path.exists() else {'report':kind,'complete':False}
    result['exit_code']=r.returncode
    if not result['complete']:result['reason']='memory_budget' if 'Allowed memory size' in r.stdout+r.stderr else 'time_budget' if 'execution time' in r.stdout+r.stderr else 'failed'
    results.append(result);print(json.dumps(result),flush=True)
(Path(tempfile.gettempdir())/'bovisoft-pdf631-summary.json').write_text(json.dumps(results,indent=2))
