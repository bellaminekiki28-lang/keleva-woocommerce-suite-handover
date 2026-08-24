<?php
/**
 * Public user enumeration and XML-RPC hardening regression in booted WordPress.
 *
 * @package KelevaWooDev
 */

defined('ABSPATH') || exit;

$original_user_id = get_current_user_id();
wp_set_current_user(0);
$anonymous_endpoints = apply_filters('rest_endpoints', rest_get_server()->get_routes());
$anonymous_user_routes_hidden = !isset(
    $anonymous_endpoints['/wp/v2/users'],
    $anonymous_endpoints['/wp/v2/users/(?P<id>[\d]+)']
);

$original_get = $_GET;
$_GET['author'] = '1';
$numeric_author_probe_detected = keleva_woo_is_author_enumeration_request();
$_GET['author'] = 'marketplacewoo-com';
$slug_author_probe_rejected = !keleva_woo_is_author_enumeration_request();
$_GET = $original_get;

$xmlrpc_disabled = !apply_filters('xmlrpc_enabled', true);
$xmlrpc_request_block_registered = has_action('init', 'keleva_woo_block_xmlrpc_request') === 0;
$headers = apply_filters('wp_headers', ['X-Pingback' => home_url('/xmlrpc.php')]);
$pingback_header_removed = !isset($headers['X-Pingback']);
wp_set_current_user($original_user_id);

$pass = $anonymous_user_routes_hidden
    && $numeric_author_probe_detected
    && $slug_author_probe_rejected
    && $xmlrpc_disabled
    && $xmlrpc_request_block_registered
    && $pingback_header_removed;

echo wp_json_encode([
    'anonymous_user_routes_hidden' => $anonymous_user_routes_hidden,
    'numeric_author_probe_detected' => $numeric_author_probe_detected,
    'slug_author_probe_rejected' => $slug_author_probe_rejected,
    'xmlrpc_disabled' => $xmlrpc_disabled,
    'xmlrpc_request_block_registered' => $xmlrpc_request_block_registered,
    'pingback_header_removed' => $pingback_header_removed,
    'pass' => $pass,
], JSON_PRETTY_PRINT) . PHP_EOL;

exit($pass ? 0 : 1);
