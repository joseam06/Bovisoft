$ErrorActionPreference = 'Stop'
$target = Join-Path $env:TEMP 'bovisoft-responsive-assets'
New-Item -ItemType Directory -Force -Path $target | Out-Null
# Test-only copies of resources already referenced by the application, not new dependencies.
$files = @{
    'chart.js' = 'https://cdn.jsdelivr.net/npm/chart.js'
    'leaflet.js' = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    'leaflet.css' = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
}
foreach ($file in $files.Keys) {
    if (Test-Path -LiteralPath (Join-Path $target $file)) { continue }
    & rtk proxy curl.exe -fLsS --max-time 30 $files[$file] -o (Join-Path $target $file)
    if ($LASTEXITCODE -ne 0) { throw "No se pudo preparar $file para las pruebas locales" }
}
Write-Output "Recursos existentes disponibles en $target"
