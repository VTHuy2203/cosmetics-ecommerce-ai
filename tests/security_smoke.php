<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/TTTT/config/security.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SESSION = [];
$token = csrf_token();
check(strlen($token) === 64, 'CSRF token length');
check(csrf_is_valid($token), 'CSRF valid token');
check(!csrf_is_valid(str_repeat('0', 64)), 'CSRF invalid token');

$hash = password_hash('a-production-password', PASSWORD_DEFAULT);
check(password_verify('a-production-password', $hash), 'password verification');
check(!password_verify('wrong-password', $hash), 'password rejection');

$body = '{"id":1,"transferAmount":100000}';
$secret = 'test-secret-that-is-long-enough';
$timestamp = 1_700_000_000;
$signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
check(sepay_signature_is_valid($body, $secret, $signature, $timestamp, $timestamp), 'SePay valid signature');
check(!sepay_signature_is_valid($body . 'x', $secret, $signature, $timestamp, $timestamp), 'SePay modified body');
check(!sepay_signature_is_valid($body, $secret, $signature, $timestamp, $timestamp + 301), 'SePay replay window');

echo "security smoke tests passed\n";
