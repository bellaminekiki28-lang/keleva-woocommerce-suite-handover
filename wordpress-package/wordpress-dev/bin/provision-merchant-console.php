<?php
/**
 * Publie uniquement dans le laboratoire local la console native Keleva.
 */
$siteRoot = getenv('KELEVA_SITE_ROOT') ?: (defined('ABSPATH') ? untrailingslashit(ABSPATH) : dirname(__DIR__) . '/site');
$consoleSource = getenv('KELEVA_CONSOLE_SOURCE') ?: dirname(__DIR__, 2) . '/console/keleva-native-console.html';
if (!defined('ABSPATH')) {
    require_once $siteRoot . '/wp-load.php';
}

// Le contenu est une page de laboratoire vérifiée, administrée par l’utilisateur local 1.
wp_set_current_user(1);

if (!is_readable($consoleSource)) {
    fwrite(STDERR, "Console native introuvable.\n");
    exit(1);
}
$content = file_get_contents($consoleSource);
if (!is_string($content) || !str_contains($content, '/session/login') || str_contains($content, 'sessionStorage')) {
    fwrite(STDERR, "La console source ne respecte pas le flux session-only attendu.\n");
    exit(1);
}
$content = str_replace('Préproduction', 'Laboratoire local', $content);
if (!is_string($content) || preg_match('/<input[^>]*id="mk-email"[^>]*\svalue="/i', $content)) {
    fwrite(STDERR, "La console source ne doit pas préremplir d’identifiant e-mail.\n");
    exit(1);
}

$page = get_page_by_path('keleva-merchant');
$data = [
    'post_title' => 'Keleva Merchant',
    'post_name' => 'keleva-merchant',
    'post_content' => $content,
    'post_status' => 'publish',
    'post_type' => 'page',
];

if ($page instanceof WP_Post) {
    $data['ID'] = $page->ID;
    $pageId = wp_update_post(wp_slash($data), true);
} else {
    $pageId = wp_insert_post(wp_slash($data), true);
}

if (is_wp_error($pageId)) {
    fwrite(STDERR, $pageId->get_error_message() . "\n");
    exit(1);
}

printf("Page console locale : %s\n", get_permalink((int) $pageId));
