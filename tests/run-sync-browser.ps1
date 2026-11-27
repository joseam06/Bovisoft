$ErrorActionPreference = 'Stop'
$runtime = Join-Path $env:TEMP 'bovisoft-browser-tests'
$python = Join-Path $runtime 'Scripts/python.exe'
if (-not (Test-Path -LiteralPath $python)) { throw 'Faltan las herramientas locales de Playwright.' }
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $runtime 'browsers'
$env:PYTHONIOENCODING = 'utf-8'
& rtk proxy $python -u tests/browser/sync_integrity.py
exit $LASTEXITCODE
