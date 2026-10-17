$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$settings = @{
    APP_ENV='testing'; APP_URL='http://127.0.0.1:18089/TTTT'
    REALTIME_TICKET_SECRET='realtime-test-secret-at-least-32-characters'
    REALTIME_WS_HOST='127.0.0.1'; REALTIME_WS_PORT='18080'
    REALTIME_QUEUE_FILE=(Join-Path $projectRoot '.runtime/realtime-socket-test-queue.json')
}
$previous = @{}
try {
    foreach ($key in $settings.Keys) {
        $previous[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
        [Environment]::SetEnvironmentVariable($key, $settings[$key], 'Process')
    }
    $server = Start-Process -FilePath (Get-Command php).Source -ArgumentList @('server.php') `
        -WorkingDirectory (Join-Path $projectRoot 'TTTT/WebSocketServer') -WindowStyle Hidden -PassThru `
        -RedirectStandardOutput (Join-Path $projectRoot '.runtime/realtime-socket.out.log') `
        -RedirectStandardError (Join-Path $projectRoot '.runtime/realtime-socket.err.log')
    $server.Id | Set-Content -LiteralPath (Join-Path $projectRoot '.runtime/realtime-socket.pid')
    $deadline = (Get-Date).AddSeconds(10)
    do {
        Start-Sleep -Milliseconds 200
        $ready = Test-NetConnection -ComputerName 127.0.0.1 -Port 18080 -InformationLevel Quiet -WarningAction SilentlyContinue
    } while (-not $ready -and (Get-Date) -lt $deadline -and -not $server.HasExited)
    if (-not $ready) { throw 'Isolated realtime server did not start.' }
    Write-Output "Isolated realtime PID: $($server.Id)"
} finally {
    foreach ($key in $settings.Keys) { [Environment]::SetEnvironmentVariable($key, $previous[$key], 'Process') }
}
