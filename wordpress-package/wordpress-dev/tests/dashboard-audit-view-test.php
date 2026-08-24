<?php
/**
 * Admin audit view regression for the protected WordPress dashboard page.
 *
 * @package KelevaWooDev
 */

defined('ABSPATH') || exit;

$original_user_id = get_current_user_id();
$users = get_users(['number' => 1, 'fields' => 'ids']);
if ($users) {
    wp_set_current_user((int) $users[0]);
}

Keleva_Dashboard_Audit_Log::record('theme_switch', [
    'to' => ['stylesheet' => 'keleva-woo'],
    'from' => ['stylesheet' => 'restocommerce'],
    'execution_context' => 'admin',
], 'wp-user-' . get_current_user_id());

ob_start();
Keleva_Dashboard_Settings::render();
$html = (string) ob_get_clean();
wp_set_current_user($original_user_id);

$theme_switch_row = '';
preg_match('~<tr>.*?theme_switch.*?</tr>~s', $html, $matches);
if (!empty($matches[0])) {
    $theme_switch_row = $matches[0];
}

$pass = str_contains($html, 'Journal d’audit récent')
    && str_contains($html, 'theme_switch')
    && str_contains($html, 'keleva-woo')
    && '' !== $theme_switch_row
    && !str_contains($theme_switch_row, '127.0.0.1');

echo wp_json_encode([
    'audit_heading' => str_contains($html, 'Journal d’audit récent'),
    'theme_switch_visible' => str_contains($html, 'theme_switch'),
    'theme_context_visible' => str_contains($html, 'keleva-woo'),
    'no_ip_leak' => !str_contains($theme_switch_row, '127.0.0.1'),
    'pass' => $pass,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit($pass ? 0 : 1);
