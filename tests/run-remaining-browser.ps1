param([switch]$AccountsOnly, [string]$RuntimeRoot = (Join-Path $env:TEMP 'bovisoft-browser-tests'))
$ErrorActionPreference = 'Stop'
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $RuntimeRoot 'browsers'
$env:PYTHONIOENCODING = 'utf-8'
if (-not $env:BOVISOFT_BROWSER_TEST_PASSWORD) { throw 'Define la contraseña elegida para las cuentas ficticias en BOVISOFT_BROWSER_TEST_PASSWORD.' }
$python = Join-Path $RuntimeRoot 'Scripts/python.exe'
if (-not (Test-Path -LiteralPath $python)) { throw 'Prepara las herramientas con tests/install-browser.ps1 y el mismo RuntimeRoot.' }
$arguments = @('-u', 'tests/browser/remaining_integrated.py')
if ($AccountsOnly) { $arguments += '--accounts-only' }
& rtk proxy $python @arguments
exit $LASTEXITCODE
