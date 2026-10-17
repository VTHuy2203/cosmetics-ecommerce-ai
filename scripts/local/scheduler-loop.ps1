param([Parameter(Mandatory = $true)][string] $ProjectRoot, [Parameter(Mandatory = $true)][string] $PhpExe)

$ErrorActionPreference = 'Continue'
$commands = @(
    'TTTT\bin\expire-payments.php',
    'TTTT\bin\process-outbox.php',
    'TTTT\bin\publish-scheduled-marketing.php'
)
# Full customer re-scoring catches product views and recency decay; actions re-score instantly.
$scoringScript = Join-Path $ProjectRoot 'TTTT\bin\score-customers.php'
$scoringEvery = [TimeSpan]::FromMinutes(5)
$lastScoring = [DateTime]::MinValue
while ($true) {
    foreach ($relative in $commands) {
        $target = Join-Path $ProjectRoot $relative
        if (Test-Path -LiteralPath $target) {
            & $PhpExe $target
        }
    }
    if ((Test-Path -LiteralPath $scoringScript) -and ((Get-Date) - $lastScoring) -ge $scoringEvery) {
        & $PhpExe $scoringScript | Out-Null
        $lastScoring = Get-Date
    }
    $aiPython = Join-Path $ProjectRoot 'venv\Scripts\python.exe'
    $ragWorker = Join-Path $ProjectRoot 'TTTT\WebBanHangMyPham\src\ML\sync_catalog_outbox.py'
    if ((Test-Path -LiteralPath $aiPython) -and (Test-Path -LiteralPath $ragWorker)) {
        & $aiPython $ragWorker
    }
    Start-Sleep -Seconds 30
}
