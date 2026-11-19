param([string]$Scenario = 'all')
$ErrorActionPreference = 'Stop'
$runtime = Join-Path $env:TEMP 'bovisoft-browser-tests'
$python = Join-Path $runtime 'Scripts/python.exe'
if (-not (Test-Path -LiteralPath $python)) { throw 'Ejecuta primero tests/install-browser.ps1' }
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $runtime 'browsers'
$env:PYTHONIOENCODING = 'utf-8'
& rtk proxy $python -u tests/browser/automatic.py --scenario $Scenario
exit $LASTEXITCODE
