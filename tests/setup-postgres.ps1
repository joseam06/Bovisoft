param([switch]$Stop)
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$installRoot = Join-Path $env:TEMP 'bovisoft-postgres-17.11-3'
$bin = Join-Path $installRoot 'pgsql/bin'
$cluster = Join-Path $installRoot 'data'
$control = Join-Path $bin 'pg_ctl.exe'
if ($Stop) {
    & rtk proxy $control -D $cluster -m fast -w stop
    exit $LASTEXITCODE
}
if (-not (Test-Path -LiteralPath $control)) {
    New-Item -ItemType Directory -Path $installRoot -Force | Out-Null
    $archivePath = Join-Path $installRoot 'postgresql.zip'
    Invoke-WebRequest -UseBasicParsing -Uri 'https://get.enterprisedb.com/postgresql/postgresql-17.11-3-windows-x64-binaries.zip' -OutFile $archivePath
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [IO.Compression.ZipFile]::OpenRead($archivePath)
    try {
        foreach ($entry in $archive.Entries) {
            if ($entry.FullName -notmatch '^pgsql/(bin|lib|share)/' -or -not $entry.Name) { continue }
            $destination = [IO.Path]::GetFullPath((Join-Path $installRoot $entry.FullName))
            if (-not $destination.StartsWith([IO.Path]::GetFullPath($installRoot) + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) { throw 'Ruta de archivo no permitida' }
            New-Item -ItemType Directory -Path ([IO.Path]::GetDirectoryName($destination)) -Force | Out-Null
            [IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $destination, $true)
        }
    } finally { $archive.Dispose() }
}
if (-not (Test-Path -LiteralPath (Join-Path $cluster 'PG_VERSION'))) {
    & rtk proxy (Join-Path $bin 'initdb.exe') -D $cluster -U postgres --auth=trust --encoding=UTF8 --locale=C
    if ($LASTEXITCODE -ne 0) { throw 'initdb no pudo preparar el cluster de pruebas' }
}
& rtk proxy $control -D $cluster status
if ($LASTEXITCODE -ne 0) {
    $log = Join-Path $installRoot 'postgres.log'
    $started = Start-Process -FilePath $control -ArgumentList "-D `"$cluster`" -l `"$log`" -o `"-h 127.0.0.1 -p 55432`" -w start" -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $installRoot 'startup.out') -RedirectStandardError (Join-Path $installRoot 'startup.err')
    $started.WaitForExit()
    # Windows PowerShell can expose a null ExitCode after WaitForExit even
    # when pg_ctl succeeded. Verify the actual isolated server instead.
    & rtk proxy $control -D $cluster status
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo iniciar PostgreSQL local de pruebas' }
}
Write-Output "PostgreSQL de pruebas en 127.0.0.1:55432; archivos: $installRoot"
