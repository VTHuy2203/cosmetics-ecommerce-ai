param(
    [Parameter(Mandatory = $true)][string]$TestFile
)
$ErrorActionPreference = 'Stop'
$testRoot = $PSScriptRoot
$testPath = [IO.Path]::GetFullPath((Join-Path $testRoot $TestFile))
if (-not $testPath.StartsWith($testRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase) -or
    [IO.Path]::GetExtension($testPath) -ne '.php' -or -not (Test-Path -LiteralPath $testPath)) {
    throw 'TestFile must be a PHP test inside tests/.'
}
# Only reviewed tests can opt in; legacy HTTP tests still target the user's server.
$allowed = @(
    'environment_guard_test.php',
    'comments_integration.php',
    'catalog_delete_integration.php',
    'private_file_access_integration.php',
    'rate_limit_integration.php',
    'runtime_schema_integration.php',
    'refund_integration.php',
    'shipping_integration.php',
    'realtime_legacy_repro.php',
    'chat_sessions_integration.php',
    'support/prepare_comments_browser.php'
)
if ($TestFile -notin $allowed) { throw 'This test has not been approved for the isolated runner.' }
$settings = @{
    APP_ENV = 'testing'; MYSQL_HOST = '127.0.0.1'; MYSQL_PORT = '13316'
    MYSQL_DB = 'moshop_test_standardization'; MYSQL_USER = 'moshop_test'
    MYSQL_PASSWORD = 'isolated-test-only-not-a-production-secret'
}
$previous = @{}
try {
    foreach ($key in $settings.Keys) {
        $previous[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
        [Environment]::SetEnvironmentVariable($key, $settings[$key], 'Process')
    }
    $modules = & php -m
    $phpOptions = @()
    foreach ($module in @('mysqli', 'pdo_mysql', 'fileinfo')) {
        if ($modules -notcontains $module) { $phpOptions += @('-d', "extension=$module") }
    }
    & php @phpOptions $testPath
    if ($LASTEXITCODE -ne 0) { throw "Test failed with exit code $LASTEXITCODE" }
} finally {
    foreach ($key in $settings.Keys) { [Environment]::SetEnvironmentVariable($key, $previous[$key], 'Process') }
}
