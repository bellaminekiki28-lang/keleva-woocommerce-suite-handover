<?php
/**
 * Theme switch audit regression without changing the active local theme.
 *
 * @package KelevaWooDev
 */

defined('ABSPATH') || exit;

Keleva_Dashboard_Audit_Log::install();
$original_user_id = get_current_user_id();
$test_users = get_users(['number' => 1, 'fields' => 'ids']);
if ($test_users) {
    wp_set_current_user((int) $test_users[0]);
}
$new_theme = wp_get_theme('keleva-woo');
$old_theme = wp_get_theme('restocommerce');
if (!$old_theme->exists()) {
    $old_theme = wp_get_theme();
}
$old_stylesheet = $old_theme->get_stylesheet();
keleva_woo_addons_record_theme_switch('Keleva Woo', $new_theme, $old_theme);
$latest = Keleva_Dashboard_Audit_Log::recent(1)[0] ?? [];

$pass = 'theme_switch' === ($latest['event'] ?? '')
    && 'keleva-woo' === ($latest['context']['to']['stylesheet'] ?? '')
    && $old_stylesheet === ($latest['context']['from']['stylesheet'] ?? '')
    && isset($latest['context']['execution_context'])
    && str_starts_with((string) ($latest['actor'] ?? ''), 'wp-user-');

wp_set_current_user($original_user_id);

echo wp_json_encode([
    'event' => $latest['event'] ?? null,
    'actor' => $latest['actor'] ?? null,
    'to' => $latest['context']['to'] ?? null,
    'from' => $latest['context']['from'] ?? null,
    'execution_context' => $latest['context']['execution_context'] ?? null,
    'pass' => $pass,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit($pass ? 0 : 1);
