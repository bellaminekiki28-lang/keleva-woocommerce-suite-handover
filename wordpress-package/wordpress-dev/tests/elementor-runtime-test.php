<?php


$expected = [
    'keleva-product-grid' => 'Keleva_Elementor_Product_Grid',
    'keleva-product-carousel' => 'Keleva_Elementor_Product_Carousel',
    'keleva-side-cart' => 'Keleva_Elementor_Side_Cart',
    'keleva-product-meta' => 'Keleva_Elementor_Product_Meta',
    'keleva-product-card' => 'Keleva_Elementor_Product_Card',
    'keleva-quick-view' => 'Keleva_Elementor_Quick_View',
    'keleva-mobile-cart-bar' => 'Keleva_Elementor_Mobile_Cart_Bar',
    'keleva-mini-cart' => 'Keleva_Elementor_Mini_Cart',
    'keleva-add-to-cart' => 'Keleva_Elementor_Add_To_Cart',
    'keleva-product-search' => 'Keleva_Elementor_Product_Search',
    'keleva-product-filters' => 'Keleva_Elementor_Product_Filters',
    'keleva-product-media' => 'Keleva_Elementor_Product_Media',
    'keleva-product-badges' => 'Keleva_Elementor_Product_Badges',
    'keleva-product-archive-header' => 'Keleva_Elementor_Product_Archive_Header',
    'keleva-checkout-shell' => 'Keleva_Elementor_Checkout_Shell',
    'keleva-product-tabs' => 'Keleva_Elementor_Product_Tabs',
    'keleva-wishlist' => 'Keleva_Elementor_Wishlist',
    'keleva-compare' => 'Keleva_Elementor_Compare',
    'keleva-mega-menu' => 'Keleva_Elementor_Mega_Menu',
    'keleva-analytics-cards' => 'Keleva_Elementor_Analytics_Cards',
];

$result = [
    'elementor_loaded' => did_action('elementor/loaded') > 0,
    'woocommerce_loaded' => class_exists('WooCommerce'),
    'widgets' => [],
    'missing' => [],
];

if ($result['elementor_loaded'] && class_exists('\Elementor\Plugin')) {
    $registered = \Elementor\Plugin::$instance->widgets_manager->get_widget_types();
    foreach ($expected as $name => $class) {
        $widget = $registered[$name] ?? null;
        $result['widgets'][$name] = [
            'registered' => is_object($widget),
            'class' => is_object($widget) ? get_class($widget) : null,
            'category' => is_object($widget) && in_array('keleva-woo', $widget->get_categories(), true),
        ];
        if (!is_object($widget) || get_class($widget) !== $class || !in_array('keleva-woo', $widget->get_categories(), true)) {
            $result['missing'][] = $name;
        }
    }
}

$result['pass'] = $result['elementor_loaded'] && $result['woocommerce_loaded'] && !$result['missing'];
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$result['pass']) {
    exit(1);
}
