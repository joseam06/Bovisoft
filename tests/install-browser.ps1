$ErrorActionPreference = 'Stop'
$runtime = Join-Path $env:TEMP 'bovisoft-browser-tests'
$python = Join-Path $runtime 'Scripts/python.exe'
if (-not (Test-Path -LiteralPath $python)) {
    & rtk proxy python -m venv $runtime
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el entorno de pruebas del navegador' }
}
& rtk proxy $python -m pip --isolated install --index-url https://pypi.org/simple playwright==1.65.2
if ($LASTEXITCODE -ne 0) { throw 'No se pudo instalar Playwright' }
$env:PLAYWRIGHT_BROWSERS_PATH = Join-Path $runtime 'browsers'
& rtk proxy $python -m playwright install chromium
if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar Chromium' }
Write-Output "Playwright preparado: $python"
