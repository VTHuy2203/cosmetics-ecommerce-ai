$script:ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$script:RuntimeDir = Join-Path $script:ProjectRoot '.runtime'
$script:LogDir = Join-Path $script:ProjectRoot 'logs\local'
$script:PhpExe = 'C:\laragon\bin\php\php-8.4.1-Win32-vs17-x64\php.exe'
$script:LaragonExe = 'C:\laragon\laragon.exe'
$script:OllamaExe = Join-Path $env:LOCALAPPDATA 'Programs\Ollama\ollama.exe'
$script:AiDir = Join-Path $script:ProjectRoot 'TTTT\WebBanHangMyPham\src\ML'
$script:AiPython = Join-Path $script:ProjectRoot 'venv\Scripts\python.exe'
$script:WebSocketDir = Join-Path $script:ProjectRoot 'TTTT\WebSocketServer'
$script:ApacheExe = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe'
$script:MySqlExe = 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe'
$script:MySqlIni = 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\my.ini'

function Initialize-LocalDirectories {
    New-Item -ItemType Directory -Force -Path $script:RuntimeDir, $script:LogDir | Out-Null
}

function Initialize-LocalRealtimeSecret {
    $envPath = Join-Path $script:ProjectRoot '.env'
    $lines = if (Test-Path -LiteralPath $envPath) { @(Get-Content -LiteralPath $envPath) } else { @() }
    $index = -1
    $configured = $false
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match '^REALTIME_TICKET_SECRET=(.*)$') {
            $index = $i
            $configured = $Matches[1].Trim().Length -ge 32
        }
    }
    if ($configured) { return }

    $bytes = New-Object byte[] 32
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    $secretLine = 'REALTIME_TICKET_SECRET=' + ([BitConverter]::ToString($bytes) -replace '-', '').ToLowerInvariant()
    if ($index -ge 0) { $lines[$index] = $secretLine } else { $lines += $secretLine }
    [IO.File]::WriteAllLines($envPath, [string[]] $lines, (New-Object Text.UTF8Encoding($false)))
    Write-Host '[OK] Da tao REALTIME_TICKET_SECRET local trong .env.' -ForegroundColor Green
}

function Test-LocalPort([int] $Port) {
    try {
        $client = [System.Net.Sockets.TcpClient]::new()
        $task = $client.ConnectAsync('127.0.0.1', $Port)
        if (-not $task.Wait(700)) { $client.Dispose(); return $false }
        $connected = $client.Connected
        $client.Dispose()
        return $connected
    } catch { return $false }
}

function Save-ManagedProcess([string] $Name, [System.Diagnostics.Process] $Process, [string] $Expected) {
    @{
        name = $Name
        pid = $Process.Id
        expected = $Expected
        started_at = (Get-Date).ToString('o')
    } | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $script:RuntimeDir "$Name.json") -Encoding UTF8
}

function Test-ManagedProcess([string] $Name) {
    $recordPath = Join-Path $script:RuntimeDir "$Name.json"
    if (-not (Test-Path -LiteralPath $recordPath)) { return $false }
    try {
        $record = Get-Content -LiteralPath $recordPath -Raw | ConvertFrom-Json
        $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($record.pid)" -ErrorAction SilentlyContinue
        if ($process -and (($process.CommandLine -like "*$($record.expected)*") -or ($process.ExecutablePath -like "*$($record.expected)*"))) {
            return $true
        }
    } catch { }
    Remove-Item -LiteralPath $recordPath -Force -ErrorAction SilentlyContinue
    return $false
}

function Start-ManagedProcess {
    param(
        [string] $Name,
        [string] $Executable,
        [string[]] $Arguments,
        [string] $WorkingDirectory,
        [int] $Port,
        [string] $Expected
    )
    if (Test-LocalPort $Port) {
        Write-Host ("[OK] {0} dang chay tren cong {1}." -f $Name, $Port) -ForegroundColor Green
        return
    }
    if (-not (Test-Path -LiteralPath $Executable)) {
        throw "Khong tim thay $Name tai: $Executable"
    }
    $stdout = Join-Path $script:LogDir "$Name.out.log"
    $stderr = Join-Path $script:LogDir "$Name.error.log"
    $process = Start-Process -FilePath $Executable -ArgumentList $Arguments -WorkingDirectory $WorkingDirectory `
        -WindowStyle Hidden -RedirectStandardOutput $stdout -RedirectStandardError $stderr -PassThru
    Save-ManagedProcess $Name $process $Expected
    $deadline = (Get-Date).AddSeconds(20)
    while ((Get-Date) -lt $deadline -and -not (Test-LocalPort $Port) -and -not $process.HasExited) {
        Start-Sleep -Milliseconds 350
    }
    if (Test-LocalPort $Port) {
        Write-Host ("[OK] Da bat {0} (PID {1}, cong {2})." -f $Name, $process.Id, $Port) -ForegroundColor Green
    } else {
        Write-Warning "$Name khong mo cong $Port. Xem log: $stderr"
    }
}
