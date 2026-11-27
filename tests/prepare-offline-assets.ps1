$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$root = Join-Path $PSScriptRoot '../public/vendor'
foreach ($folder in @('fontawesome/css', 'fontawesome/webfonts', 'inter')) {
    New-Item -ItemType Directory -Path (Join-Path $root $folder) -Force | Out-Null
}
Invoke-WebRequest -UseBasicParsing 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' -OutFile (Join-Path $root 'fontawesome/css/all.min.css')
Invoke-WebRequest -UseBasicParsing 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2' -OutFile (Join-Path $root 'fontawesome/webfonts/fa-solid-900.woff2')
Invoke-WebRequest -UseBasicParsing 'https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.4.0/LICENSE.txt' -OutFile (Join-Path $root 'fontawesome/LICENSE.txt')
$css = (Invoke-WebRequest -UseBasicParsing -UserAgent 'Mozilla/5.0 Chrome/120.0.0.0 Safari/537.36' 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap').Content
foreach ($match in [regex]::Matches($css, 'https://fonts\.gstatic\.com/[^)\s]+') | Select-Object -ExpandProperty Value -Unique) {
    $name = [IO.Path]::GetFileName(([Uri]$match).AbsolutePath)
    Invoke-WebRequest -UseBasicParsing $match -OutFile (Join-Path $root "inter/$name")
    $css = $css.Replace($match, "/vendor/inter/$name")
}
[IO.File]::WriteAllText((Join-Path $root 'inter/inter.css'), $css, [Text.UTF8Encoding]::new($false))
Invoke-WebRequest -UseBasicParsing 'https://raw.githubusercontent.com/rsms/inter/master/LICENSE.txt' -OutFile (Join-Path $root 'inter/LICENSE.txt')
Write-Output 'Recursos visuales del piloto guardados localmente.'
