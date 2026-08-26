<?php
/**
 * Plugin Name: Keleva Woo Addons
 * Description: Quick view REST endpoint and Elementor commerce widgets for Keleva Woo.
 * Version: 0.6.16
 * Author: Keleva
 * Text Domain: keleva-woo-addons
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('KELEVA_WOO_ADDONS_PATH', plugin_dir_path(__FILE__));
define('KELEVA_WOO_ADDONS_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('keleva-woo-addons', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 1);

require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-quick-view-endpoint.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-dashboard-audit-log.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-theme-switch-audit.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-dashboard-settings.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-manager-admin.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-native-merchant-portal.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-portal-public-entry.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-rest-rate-limiter.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-product-options.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-category-service.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-dashboard-endpoint.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-restaurant-extras.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-saved-product-lists.php';
require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-whatsapp-order.php';

register_activation_hook(__FILE__, ['Keleva_Dashboard_Audit_Log', 'install']);
add_action('plugins_loaded', ['Keleva_Dashboard_Audit_Log', 'maybe_install']);

add_action('rest_api_init', ['Keleva_Quick_View_Endpoint', 'register_routes']);
add_action('rest_api_init', ['Keleva_Dashboard_Endpoint', 'register_routes']);
Keleva_Rest_Rate_Limiter::boot();
Keleva_Dashboard_Settings::boot();
Keleva_Manager_Admin::boot();
Keleva_Native_Merchant_Portal::boot();
Keleva_Portal_Public_Entry::boot();
Keleva_Restaurant_Extras::boot();
Keleva_Product_Options::boot();
Keleva_Saved_Product_Lists::boot();
Keleva_WhatsApp_Order::boot();

add_action('elementor/elements/categories_registered', static function ($elements_manager): void {
    $elements_manager->add_category('keleva-woo', [
        'title' => __('Keleva Woo', 'keleva-woo-addons'),
        'icon' => 'eicon-products',
    ]);
});

add_action('elementor/widgets/register', static function ($widgets_manager): void {
    if (!did_action('elementor/loaded') || !class_exists('WooCommerce')) {
        return;
    }

    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-grid.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-carousel.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-side-cart.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-meta.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-card.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-quick-view.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-mobile-cart-bar.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-mini-cart.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-add-to-cart.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-search.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-filters.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-media.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-badges.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-archive-header.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-checkout-shell.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-product-tabs.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-wishlist.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-compare.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-mega-menu.php';
    require_once KELEVA_WOO_ADDONS_PATH . 'includes/class-elementor-analytics-cards.php';
    $widgets_manager->register(new Keleva_Elementor_Product_Grid());
    $widgets_manager->register(new Keleva_Elementor_Product_Carousel());
    $widgets_manager->register(new Keleva_Elementor_Side_Cart());
    $widgets_manager->register(new Keleva_Elementor_Product_Meta());
    $widgets_manager->register(new Keleva_Elementor_Product_Card());
    $widgets_manager->register(new Keleva_Elementor_Quick_View());
    $widgets_manager->register(new Keleva_Elementor_Mobile_Cart_Bar());
    $widgets_manager->register(new Keleva_Elementor_Mini_Cart());
    $widgets_manager->register(new Keleva_Elementor_Add_To_Cart());
    $widgets_manager->register(new Keleva_Elementor_Product_Search());
    $widgets_manager->register(new Keleva_Elementor_Product_Filters());
    $widgets_manager->register(new Keleva_Elementor_Product_Media());
    $widgets_manager->register(new Keleva_Elementor_Product_Badges());
    $widgets_manager->register(new Keleva_Elementor_Product_Archive_Header());
    $widgets_manager->register(new Keleva_Elementor_Checkout_Shell());
    $widgets_manager->register(new Keleva_Elementor_Product_Tabs());
    $widgets_manager->register(new Keleva_Elementor_Wishlist());
    $widgets_manager->register(new Keleva_Elementor_Compare());
    $widgets_manager->register(new Keleva_Elementor_Mega_Menu());
    $widgets_manager->register(new Keleva_Elementor_Analytics_Cards());
});

add_action('wp_enqueue_scripts', static function (): void {
    if (did_action('elementor/loaded')) {
        wp_enqueue_style('keleva-woo-elementor', plugins_url('assets/css/elementor.css', __FILE__), [], '0.4.9');
        wp_enqueue_style('keleva-woo-product-media', plugins_url('assets/css/product-media.css', __FILE__), ['keleva-woo-elementor'], '0.4.9');
        wp_enqueue_style('keleva-woo-saved-lists', plugins_url('assets/css/saved-lists.css', __FILE__), ['keleva-woo-elementor'], '0.4.9');
    }
});

add_action('elementor/theme/register_locations', static function ($manager): void {
    foreach (['header', 'footer', 'single', 'archive'] as $location) {
        $manager->register_location($location);
    }
});
