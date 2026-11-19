param([ValidateSet('all','360','768','1440','offline')][string]$Group = 'all')
$ErrorActionPreference = 'Stop'
$runtime = Join-Path $env:TEMP 'bovisoft-browser-tests'
$python = Join-Path $runtime 'Scripts/python.exe'
if (-not (Test-Path -LiteralPath $python)) { throw 'Prepara las herramientas de desarrollo con tests/install-browser.ps1' }
if (-not (Test-Path -LiteralPath (Join-Path $env:TEMP 'bovisoft-responsive-assets/chart.js'))) { throw 'Ejecuta tests/prepare-responsive-assets.ps1 para comprobar las gráficas con sus recursos existentes' }
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $runtime 'browsers'
$env:PYTHONIOENCODING = 'utf-8'
& rtk proxy $python -u tests/browser/responsive.py --group $Group
exit $LASTEXITCODE
