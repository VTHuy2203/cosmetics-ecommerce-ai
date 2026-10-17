$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$settings = @{
    APP_ENV='testing'; MYSQL_HOST='127.0.0.1'; MYSQL_PORT='13316'
    MYSQL_DB='moshop_test_standardization'; MYSQL_USER='moshop_test'
    MYSQL_PASSWORD='isolated-test-only-not-a-production-secret'
    REALTIME_TICKET_SECRET='realtime-test-secret-at-least-32-characters'
}
$previous = @{}
try {
    foreach ($key in $settings.Keys) {
        $previous[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
        [Environment]::SetEnvironmentVariable($key, $settings[$key], 'Process')
    }
    $modules = & php -m
    $phpOptions = @()
    foreach ($module in @('mysqli','pdo_mysql')) {
        if ($modules -notcontains $module) { $phpOptions += @('-d', "extension=$module") }
    }
    & php @phpOptions (Join-Path $PSScriptRoot 'support/prepare_comments_browser.php')
    if ($LASTEXITCODE -ne 0) { throw 'Fixture preparation failed.' }
    $sessions = Join-Path $projectRoot '.runtime/comments-sessions'
    New-Item -ItemType Directory -Path $sessions -Force | Out-Null
    $argsList = $phpOptions + @('-d', "session.save_path=$sessions", '-S','127.0.0.1:18089','-t',$projectRoot,(Join-Path $PSScriptRoot 'support/comments_router.php'))
    $server = Start-Process -FilePath (Get-Command php).Source -ArgumentList $argsList -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $projectRoot '.runtime/comments-http.out.log') -RedirectStandardError (Join-Path $projectRoot '.runtime/comments-http.err.log')
    $server.Id | Set-Content -LiteralPath (Join-Path $projectRoot '.runtime/comments-http.pid')
    Write-Output "Isolated comments HTTP PID: $($server.Id)"
} finally {
    foreach ($key in $settings.Keys) { [Environment]::SetEnvironmentVariable($key, $previous[$key], 'Process') }
}
