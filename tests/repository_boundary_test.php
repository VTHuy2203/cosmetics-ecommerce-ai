<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$command = 'git -C ' . escapeshellarg($root) . ' ls-files -z';
$trackedOutput = shell_exec($command);
if (!is_string($trackedOutput)) {
    throw new RuntimeException('Cannot inspect tracked repository files.');
}
$tracked = array_values(array_filter(explode("\0", $trackedOutput), 'strlen'));
$violations = [];
foreach ($tracked as $path) {
    $normalized = str_replace('\\', '/', $path);
    if (in_array($normalized, ['webmipham1.sql','Vietnamese-Administrative-Units-Dataset.sql','TTTT/sanpham.csv'], true)
        || preg_match('~^TTTT/AdminManage/src/ReturnRefundAttachments/(?!\.htaccess$).+~i', $normalized)
        || preg_match('~(^|/)(?:\.env(?:\..+)?|chroma[^/]*|storage/private)(?:/|$)~i', $normalized)
        || preg_match('~\.(?:sqlite3?|db)$~i', $normalized)) {
        $violations[] = $normalized;
    }
}
if ($violations !== []) {
    fwrite(STDERR, "Private/generated files are still tracked:\n" . implode("\n", $violations) . "\n");
    exit(1);
}

$rootRules = file_get_contents($root . '/TTTT/.htaccess');
$workspaceRules = file_get_contents($root . '/.htaccess');
$apacheRules = file_get_contents($root . '/deploy/apache/000-default.conf');
if (!is_string($workspaceRules) || !str_contains($workspaceRules, 'deploy|docs|logs|scripts|shortcuts|tests|venv')) {
    throw new RuntimeException('Workspace document-root deny rules are missing.');
}
foreach (['docs','tests','scripts','deploy','logs','shortcuts','venv'] as $privateDirectory) {
    $denyFile = file_get_contents($root . '/' . $privateDirectory . '/.htaccess');
    if (!is_string($denyFile) || !str_contains($denyFile, 'Require all denied')) {
        throw new RuntimeException('Directory deny rule is missing for ' . $privateDirectory);
    }
}
foreach (['storage', 'WebSocketServer', 'WebBanHangMyPham/src/(?:ML|RAG|uploads)'] as $rule) {
    if (!is_string($rootRules) || !str_contains($rootRules, $rule)) {
        throw new RuntimeException('Public-root deny rule is missing for ' . $rule);
    }
}
foreach (['storage', 'WebSocketServer', 'WebBanHangMyPham/src/(ML|RAG|uploads)'] as $rule) {
    if (!is_string($apacheRules) || !str_contains($apacheRules, $rule)) {
        throw new RuntimeException('Production Apache deny rule is missing for ' . $rule);
    }
}
$dockerIgnore = file_get_contents($root . '/.dockerignore');
foreach (['.env', '.runtime', 'TTTT/AdminManage/src/ReturnRefundAttachments/*', 'TTTT/storage/private/*', 'TTTT/WebBanHangMyPham/src/uploads/*'] as $rule) {
    if (!is_string($dockerIgnore) || !str_contains($dockerIgnore, $rule)) {
        throw new RuntimeException('Docker build-context exclusion is missing for ' . $rule);
    }
}
echo "repository_boundary=ok\n";
