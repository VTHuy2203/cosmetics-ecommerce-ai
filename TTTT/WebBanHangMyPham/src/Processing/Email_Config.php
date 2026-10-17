<?php
/**
 * Email Configuration for Mơ Shop
 * Gmail SMTP Settings
 */
return [
    'smtp_host' => app_env('SMTP_HOST', 'smtp.gmail.com'),
    'port' => (int) app_env('SMTP_PORT', '587'),
    'smtp_secure' => app_env('SMTP_SECURE', 'tls'),
    'smtp_username' => app_env('SMTP_USERNAME', ''),
    'smtp_password' => app_env('SMTP_PASSWORD', ''),
    'from_email' => app_env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'from_name' => app_env('MAIL_FROM_NAME', 'Mơ Shop')
];
?>
