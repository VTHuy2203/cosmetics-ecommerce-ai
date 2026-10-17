$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'scripts\local\common.ps1')

$services = @(
    @{ Name='Apache/Web'; Port=80; Url='http://localhost/TTTT/' },
    @{ Name='MySQL'; Port=3306; Url=$null },
    @{ Name='Ollama'; Port=11434; Url='http://127.0.0.1:11434/api/tags' },
    @{ Name='AI'; Port=8010; Url='http://127.0.0.1:8010/ping' },
    @{ Name='WebSocket'; Port=8080; Url=$null }
)

Write-Host 'Trang thai Mo Shop local' -ForegroundColor Cyan
foreach ($service in $services) {
    $running = Test-LocalPort $service.Port
    $detail = if ($running) { 'RUNNING' } else { 'STOPPED' }
    if ($running -and $service.Url) {
        try {
            $response = Invoke-WebRequest -Uri $service.Url -UseBasicParsing -TimeoutSec 3
            $detail += " HTTP $($response.StatusCode)"
        } catch { $detail += ' (cong mo, HTTP chua san sang)' }
    }
    $color = if ($running) { 'Green' } else { 'Red' }
    Write-Host ("{0,-12} {1,-5} {2}" -f $service.Name, $service.Port, $detail) -ForegroundColor $color
}

$schedulerRunning = Test-ManagedProcess 'scheduler'
$schedulerColor = if ($schedulerRunning) { 'Green' } else { 'Red' }
$schedulerState = if ($schedulerRunning) { 'RUNNING' } else { 'STOPPED' }
Write-Host ("{0,-12} {1,-5} {2}" -f 'Scheduler', '-', $schedulerState) -ForegroundColor $schedulerColor

$modelLine = Get-Content -LiteralPath (Join-Path $script:ProjectRoot '.env') -ErrorAction SilentlyContinue |
    Where-Object { $_ -match '^OLLAMA_MODEL=' } | Select-Object -Last 1
$ollamaModel = if ($modelLine) { ($modelLine -split '=', 2)[1].Trim() } else { 'llama3.2:3b' }
try {
    $tags = Invoke-RestMethod -Uri 'http://127.0.0.1:11434/api/tags' -TimeoutSec 3
    $modelReady = @($tags.models.name) -contains $ollamaModel
    $modelState = if ($modelReady) { 'READY' } else { 'DOWNLOADING / MISSING' }
    $modelColor = if ($modelReady) { 'Green' } else { 'Yellow' }
    Write-Host ("AI model     {0} {1}" -f $ollamaModel, $modelState) -ForegroundColor $modelColor
} catch {
    Write-Host "AI model     $ollamaModel UNKNOWN" -ForegroundColor Yellow
}

if (Test-Path -LiteralPath $script:PhpExe) {
    $version = & $script:PhpExe -r "echo PHP_VERSION . ' ' . (PHP_ZTS ? 'TS' : 'NTS');" 2>$null
    Write-Host "PHP CLI      $version"
}
Write-Host "Logs: $script:LogDir"
