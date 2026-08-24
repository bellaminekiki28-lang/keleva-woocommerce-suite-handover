<?php
/**
 * Contract regression for the Keleva REST rate limiting filter.
 *
 * @package KelevaWooDev
 */

defined('ABSPATH') || exit;

$original_user_id = get_current_user_id();
$original_remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
wp_set_current_user(0);
$_SERVER['REMOTE_ADDR'] = '198.51.100.' . wp_rand(10, 240);

$request = new WP_REST_Request('POST', '/keleva-dashboard/v1/session/login');
$allowed = 0;
$limited_response = null;

for ($attempt = 1; $attempt <= 6; $attempt++) {
    $response = rest_do_request($request);
    if (429 !== $response->get_status()) {
        $allowed++;
    } else {
        $limited_response = $response;
    }
}

if (null === $original_remote_addr) {
    unset($_SERVER['REMOTE_ADDR']);
} else {
    $_SERVER['REMOTE_ADDR'] = $original_remote_addr;
}
wp_set_current_user($original_user_id);

$headers = $limited_response instanceof WP_REST_Response ? $limited_response->get_headers() : [];

$pass = 5 === $allowed
    && $limited_response instanceof WP_REST_Response
    && 429 === $limited_response->get_status()
    && '' !== (string) ($headers['Retry-After'] ?? '')
    && 'no-store' === ($headers['Cache-Control'] ?? '')
    && '5' === ($headers['X-RateLimit-Limit'] ?? '');

echo wp_json_encode([
    'allowed_attempts' => $allowed,
    'limited_status' => $limited_response instanceof WP_REST_Response ? $limited_response->get_status() : null,
    'retry_after_present' => '' !== (string) ($headers['Retry-After'] ?? ''),
    'cache_control' => $headers['Cache-Control'] ?? null,
    'pass' => $pass,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit($pass ? 0 : 1);
