$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'scripts\local\common.ps1')
Initialize-LocalDirectories

function Get-ManagedDescendants([int] $ParentId) {
    $children = @(Get-CimInstance Win32_Process -Filter "ParentProcessId = $ParentId" -ErrorAction SilentlyContinue)
    $ids = @()
    foreach ($child in $children) {
        $ids += Get-ManagedDescendants ([int] $child.ProcessId)
        $ids += [int] $child.ProcessId
    }
    return $ids
}

foreach ($name in @('scheduler','websocket','ai','ollama-model','ollama','apache','mysql')) {
    $recordPath = Join-Path $script:RuntimeDir "$name.json"
    if (-not (Test-Path -LiteralPath $recordPath)) { continue }
    try {
        $record = Get-Content -LiteralPath $recordPath -Raw | ConvertFrom-Json
        $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($record.pid)" -ErrorAction SilentlyContinue
        if ($process -and (($process.CommandLine -like "*$($record.expected)*") -or ($process.ExecutablePath -like "*$($record.expected)*"))) {
            foreach ($childId in (Get-ManagedDescendants ([int] $record.pid))) {
                Stop-Process -Id $childId -Force -ErrorAction SilentlyContinue
            }
            Stop-Process -Id ([int] $record.pid) -Force
            Write-Host ("[OK] Da dung {0} (PID {1})." -f $name, $record.pid) -ForegroundColor Green
        } elseif ($process) {
            Write-Warning "Bo qua PID $($record.pid): tien trinh khong con thuoc $name."
        }
    } catch {
        Write-Warning "Khong the dung ${name}: $($_.Exception.Message)"
    } finally {
        Remove-Item -LiteralPath $recordPath -Force -ErrorAction SilentlyContinue
    }
}
Write-Host 'Chi dung cac tien trinh co PID do run-local.ps1 da tao.' -ForegroundColor Yellow
