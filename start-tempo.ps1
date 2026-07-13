$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $MyInvocation.MyCommand.Path
$logDirectory = Join-Path $project 'storage\logs'
$outputLog = Join-Path $logDirectory 'tempo-server.log'
$errorLog = Join-Path $logDirectory 'tempo-server-error.log'

Set-Location $project

try {
    Get-Command php -ErrorAction Stop | Out-Null
    $php = (& php -r "echo PHP_BINARY;").Trim()
} catch {
    Write-Host 'Tempo could not find PHP.' -ForegroundColor Red
    Write-Host 'Install Laravel Herd or PHP, then try again.'
    exit 1
}

try {
    & $php artisan optimize:clear | Out-Null
    & $php artisan migrate --force | Out-Null

    $port = 8765
    $url = "http://127.0.0.1:$port"
    $alreadyRunning = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    if (-not $alreadyRunning) {
        Start-Process -FilePath $php -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$port") -WorkingDirectory $project -WindowStyle Hidden -RedirectStandardOutput $outputLog -RedirectStandardError $errorLog | Out-Null
    }

    $ready = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        try {
            $response = Invoke-WebRequest -Uri "$url/up" -UseBasicParsing -TimeoutSec 1
            if ($response.StatusCode -eq 200) { $ready = $true; break }
        } catch { Start-Sleep -Milliseconds 500 }
    }

    if (-not $ready) { throw "The Tempo server did not start. See $errorLog" }

    Start-Process $url
    Write-Host "Tempo is running at $url" -ForegroundColor Green
    Start-Sleep -Seconds 2
} catch {
    Write-Host 'Tempo could not start:' -ForegroundColor Red
    Write-Host $_.Exception.Message
    if (Test-Path $errorLog) { Get-Content $errorLog -Tail 20 }
    exit 1
}
