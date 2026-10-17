[CmdletBinding()]
param([switch] $NoBrowser, [switch] $SkipLaragon)

$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'scripts\local\common.ps1')
Initialize-LocalDirectories
Initialize-LocalRealtimeSecret

Write-Host 'Khoi dong Mo Shop local...' -ForegroundColor Cyan
if (-not $SkipLaragon -and -not (Test-LocalPort 80)) {
    if (Test-Path -LiteralPath $script:LaragonExe) {
        Start-Process -FilePath $script:LaragonExe -ArgumentList 'start' -WindowStyle Hidden | Out-Null
        Start-Sleep -Seconds 3
    } else {
        Write-Warning 'Khong tim thay C:\laragon\laragon.exe.'
    }
}

if (-not $SkipLaragon -and -not (Test-LocalPort 3306)) {
    Start-ManagedProcess -Name 'mysql' -Executable $script:MySqlExe `
        -Arguments @('--defaults-file=' + $script:MySqlIni) -WorkingDirectory (Split-Path $script:MySqlExe) `
        -Port 3306 -Expected 'mysqld.exe'
}
if (-not $SkipLaragon -and -not (Test-LocalPort 80)) {
    Start-ManagedProcess -Name 'apache' -Executable $script:ApacheExe -Arguments @('-D','FOREGROUND') `
        -WorkingDirectory (Split-Path $script:ApacheExe) -Port 80 -Expected 'httpd.exe'
}

Start-ManagedProcess -Name 'ollama' -Executable $script:OllamaExe -Arguments @('serve') `
    -WorkingDirectory $script:ProjectRoot -Port 11434 -Expected 'ollama.exe'

$modelLine = Get-Content -LiteralPath (Join-Path $script:ProjectRoot '.env') -ErrorAction SilentlyContinue |
    Where-Object { $_ -match '^OLLAMA_MODEL=' } | Select-Object -Last 1
$ollamaModel = if ($modelLine) { ($modelLine -split '=', 2)[1].Trim() } else { 'llama3.2:3b' }
$modelInstalled = $false
try {
    $modelResponse = Invoke-RestMethod -Uri 'http://127.0.0.1:11434/api/tags' -TimeoutSec 3
    $modelInstalled = @($modelResponse.models.name) -contains $ollamaModel
} catch { }
if (-not $modelInstalled -and -not (Get-CimInstance Win32_Process -Filter "Name='ollama.exe'" -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine -like "*pull $ollamaModel*" })) {
    $modelProcess = Start-Process -FilePath $script:OllamaExe -ArgumentList @('pull', $ollamaModel) `
        -WorkingDirectory $script:ProjectRoot -WindowStyle Hidden `
        -RedirectStandardOutput (Join-Path $script:LogDir 'ollama-model.out.log') `
        -RedirectStandardError (Join-Path $script:LogDir 'ollama-model.error.log') -PassThru
    Save-ManagedProcess 'ollama-model' $modelProcess ("pull " + $ollamaModel)
    Write-Warning "Dang tai model $ollamaModel trong nen. AI chat se san sang sau khi tai xong."
} elseif ($modelInstalled) {
    Write-Host "[OK] Ollama model $ollamaModel da san sang." -ForegroundColor Green
}

Start-ManagedProcess -Name 'ai' -Executable $script:AiPython `
    -Arguments @('-m','uvicorn','api_rag_ollama:app','--host','127.0.0.1','--port','8010','--log-level','info') `
    -WorkingDirectory $script:AiDir -Port 8010 -Expected 'api_rag_ollama'
Start-ManagedProcess -Name 'websocket' -Executable $script:PhpExe -Arguments @('server.php') `
    -WorkingDirectory $script:WebSocketDir -Port 8080 -Expected 'server.php'

$schedulerFile = Join-Path $script:RuntimeDir 'scheduler.json'
if (-not (Test-ManagedProcess 'scheduler')) {
    $schedulerScript = Join-Path $script:ProjectRoot 'scripts\local\scheduler-loop.ps1'
    $schedulerArgs = @('-NoProfile','-ExecutionPolicy','Bypass','-File',('"' + $schedulerScript + '"'),'-ProjectRoot',('"' + $script:ProjectRoot + '"'),'-PhpExe',('"' + $script:PhpExe + '"'))
    $scheduler = Start-Process -FilePath 'powershell.exe' -ArgumentList $schedulerArgs -WorkingDirectory $script:ProjectRoot `
        -WindowStyle Hidden -RedirectStandardOutput (Join-Path $script:LogDir 'scheduler.out.log') `
        -RedirectStandardError (Join-Path $script:LogDir 'scheduler.error.log') -PassThru
    Save-ManagedProcess 'scheduler' $scheduler 'scheduler-loop.ps1'
    Write-Host ("[OK] Da bat scheduler (PID {0})." -f $scheduler.Id) -ForegroundColor Green
}

if (Test-LocalPort 80) {
    Write-Host '[OK] Web: http://localhost/TTTT/' -ForegroundColor Green
    Write-Host '[OK] Admin: http://localhost/TTTT/admin' -ForegroundColor Green
    if (-not $NoBrowser) { Start-Process 'http://localhost/TTTT/' }
} else {
    Write-Warning 'Apache chua mo cong 80. Xem logs\local\apache.error.log.'
}

& (Join-Path $script:ProjectRoot 'status-local.ps1')
