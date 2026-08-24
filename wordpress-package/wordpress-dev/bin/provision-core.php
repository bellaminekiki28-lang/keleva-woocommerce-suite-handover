<?php
/**
 * Provisionnement exclusif au laboratoire local Keleva.
 * Ne pas copier ce fichier dans une archive distribuée ou une instance publique.
 */
$siteRoot = getenv('KELEVA_SITE_ROOT') ?: (defined('ABSPATH') ? untrailingslashit(ABSPATH) : dirname(__DIR__) . '/site');
if (!defined('ABSPATH')) {
    require_once $siteRoot . '/wp-load.php';
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

$plugins = [
    'woocommerce/woocommerce.php',
    'keleva-woo-addons/keleva-woo-addons.php',
];

foreach ($plugins as $plugin) {
    $result = activate_plugin($plugin, '', false, true);
    if (is_wp_error($result)) {
        fwrite(STDERR, sprintf("Échec activation %s : %s\n", $plugin, $result->get_error_message()));
        exit(1);
    }
}

if (class_exists('WC_Install')) {
    WC_Install::create_pages();
}

update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');

switch_theme('keleva-woo');
update_option('permalink_structure', '/%postname%/');
flush_rewrite_rules();

foreach (['shop', 'cart', 'checkout', 'myaccount'] as $pageKey) {
    if (empty(get_option('woocommerce_' . $pageKey . '_page_id'))) {
        fwrite(STDERR, "Page WooCommerce absente : {$pageKey}\n");
        exit(1);
    }
}

printf("WooCommerce : %s\n", defined('WC_VERSION') ? WC_VERSION : 'indisponible');
printf("Thème actif : %s\n", wp_get_theme()->get('Name'));
printf("Extension Keleva active : %s\n", is_plugin_active('keleva-woo-addons/keleva-woo-addons.php') ? 'oui' : 'non');
printf("Accueil : %s\n", home_url('/'));
