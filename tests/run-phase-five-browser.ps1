param([switch]$Smoke)
$ErrorActionPreference = 'Stop'
$runtime = Join-Path $env:TEMP 'bovisoft-browser-tests'
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $runtime 'browsers'
$env:PYTHONIOENCODING = 'utf-8'
if (-not $env:BOVISOFT_BROWSER_TEST_PASSWORD) { throw 'Define la contraseña de las cuentas ficticias existentes en BOVISOFT_BROWSER_TEST_PASSWORD.' }
$arguments = @('-u', 'tests/browser/phase_five.py')
if ($Smoke) { $arguments += '--smoke' }
& rtk proxy (Join-Path $runtime 'Scripts/python.exe') @arguments
exit $LASTEXITCODE
