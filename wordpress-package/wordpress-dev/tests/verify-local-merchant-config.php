<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$testEmail = sanitize_email((string) getenv('KELEVA_LOCAL_MERCHANT_EMAIL'));
$testPassword = (string) getenv('KELEVA_LOCAL_MERCHANT_PASSWORD');
$configuredEmail = defined('KELEVA_DASHBOARD_MERCHANT_EMAIL') ? sanitize_email((string) KELEVA_DASHBOARD_MERCHANT_EMAIL) : '';
$configuredHash = defined('KELEVA_DASHBOARD_MERCHANT_PASSWORD_HASH') ? (string) KELEVA_DASHBOARD_MERCHANT_PASSWORD_HASH : '';

echo wp_json_encode([
    'emailMatch' => '' !== $testEmail && '' !== $configuredEmail && hash_equals($configuredEmail, $testEmail),
    'passwordMatch' => '' !== $testPassword && '' !== $configuredHash && password_verify($testPassword, $configuredHash),
    'configured' => '' !== $configuredEmail && '' !== $configuredHash,
    'storedPalette' => sanitize_key((string) get_theme_mod('keleva_palette', 'velora')),
], JSON_UNESCAPED_SLASHES) . "\n";
