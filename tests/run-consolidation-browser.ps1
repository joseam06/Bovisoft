param([ValidateSet('all','fidelity','restart','mobile')][string]$Scenario = 'all')
$ErrorActionPreference = 'Stop'
$runtime = Join-Path $env:TEMP 'bovisoft-browser-tests'
$python = Join-Path $runtime 'Scripts/python.exe'
if (-not (Test-Path -LiteralPath $python)) { throw 'Ejecuta tests/install-browser.ps1 para preparar las herramientas de pruebas, no la aplicación.' }
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $runtime 'browsers'
$env:PYTHONIOENCODING = 'utf-8'
& rtk proxy $python -u tests/browser/consolidation.py --scenario $Scenario
exit $LASTEXITCODE
