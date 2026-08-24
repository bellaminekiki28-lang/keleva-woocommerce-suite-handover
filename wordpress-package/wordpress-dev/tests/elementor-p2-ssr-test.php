<?php

defined('ABSPATH') || exit;

$products = wc_get_products(['limit' => 20, 'status' => 'publish', 'stock_status' => 'instock']);
$test_product = null;
foreach ($products as $candidate) {
    if ($candidate->is_type('simple') && $candidate->is_purchasable() && $candidate->is_in_stock()) {
        $test_product = $candidate;
        break;
    }
}
if (!$test_product instanceof WC_Product) {
    fwrite(STDERR, "No published product is available for the Elementor SSR test.\n");
    exit(1);
}

global $post, $product;
$product = $test_product;
$post = get_post($product->get_id());
setup_postdata($post);

if (!WC()->cart) {
    fwrite(STDERR, "WooCommerce cart is unavailable for the Elementor SSR test.\n");
    exit(1);
}
WC()->cart->empty_cart();
if (!WC()->cart->add_to_cart($product->get_id())) {
    fwrite(STDERR, "Unable to add the selected product to the test cart.\n");
    exit(1);
}
WC()->session->set('keleva_wishlist_products', []);
WC()->session->set('keleva_compare_products', []);
$saved_lists_session_mutation = Keleva_Saved_Product_Lists::toggle('wishlist', $product->get_id())
    && Keleva_Saved_Product_Lists::contains('wishlist', $product->get_id())
    && Keleva_Saved_Product_Lists::toggle('compare', $product->get_id())
    && Keleva_Saved_Product_Lists::contains('compare', $product->get_id())
    && !Keleva_Saved_Product_Lists::toggle('invalid', $product->get_id());

$layout_id = wp_insert_post([
    'post_title' => 'Keleva temporary P2 SSR proof',
    'post_type' => 'page',
    'post_status' => 'draft',
]);
if (!$layout_id || is_wp_error($layout_id)) {
    fwrite(STDERR, "Unable to create temporary Elementor proof post.\n");
    exit(1);
}

$data = [[
    'id' => 'keleva-p2-proof',
    'elType' => 'container',
    'settings' => [],
    'elements' => [
        ['id' => 'keleva-media-proof', 'elType' => 'widget', 'widgetType' => 'keleva-product-media', 'settings' => [], 'elements' => []],
        ['id' => 'keleva-badges-proof', 'elType' => 'widget', 'widgetType' => 'keleva-product-badges', 'settings' => ['custom_label' => 'Preuve SSR'], 'elements' => []],
        ['id' => 'keleva-tabs-proof', 'elType' => 'widget', 'widgetType' => 'keleva-product-tabs', 'settings' => [], 'elements' => []],
        ['id' => 'keleva-checkout-proof', 'elType' => 'widget', 'widgetType' => 'keleva-checkout-shell', 'settings' => ['render_checkout' => 'yes'], 'elements' => []],
        ['id' => 'keleva-wishlist-proof', 'elType' => 'widget', 'widgetType' => 'keleva-wishlist', 'settings' => ['mode' => 'both', 'product_id' => $product->get_id()], 'elements' => []],
        ['id' => 'keleva-compare-proof', 'elType' => 'widget', 'widgetType' => 'keleva-compare', 'settings' => ['mode' => 'both', 'product_id' => $product->get_id()], 'elements' => []],
        ['id' => 'keleva-menu-proof', 'elType' => 'widget', 'widgetType' => 'keleva-mega-menu', 'settings' => ['menu_location' => 'primary', 'fallback_categories' => 'yes'], 'elements' => []],
        ['id' => 'keleva-analytics-proof', 'elType' => 'widget', 'widgetType' => 'keleva-analytics-cards', 'settings' => ['days' => 30], 'elements' => []],
    ],
]];

update_post_meta($layout_id, '_elementor_edit_mode', 'builder');
update_post_meta($layout_id, '_elementor_version', ELEMENTOR_VERSION);
update_post_meta($layout_id, '_elementor_data', wp_slash(wp_json_encode($data)));

add_filter('woocommerce_is_checkout', '__return_true');

try {
    $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($layout_id, true);
    $active_single_layout_id = keleva_woo_layout_id('single_product');
    $active_single_layout_data = $active_single_layout_id ? (string) get_post_meta($active_single_layout_id, '_elementor_data', true) : '';
    ob_start();
    wc_get_template_part('content', 'product');
    $product_card_html = (string) ob_get_clean();
    $fallback_data = [[
        'id' => 'keleva-explicit-product-proof',
        'elType' => 'container',
        'settings' => [],
        'elements' => [
            ['id' => 'keleva-wishlist-explicit-proof', 'elType' => 'widget', 'widgetType' => 'keleva-wishlist', 'settings' => ['mode' => 'toggle', 'product_id' => $test_product->get_id()], 'elements' => []],
            ['id' => 'keleva-compare-explicit-proof', 'elType' => 'widget', 'widgetType' => 'keleva-compare', 'settings' => ['mode' => 'toggle', 'product_id' => $test_product->get_id()], 'elements' => []],
            ['id' => 'keleva-tabs-explicit-proof', 'elType' => 'widget', 'widgetType' => 'keleva-product-tabs', 'settings' => ['product_id' => $test_product->get_id(), 'show_description' => 'yes', 'show_attributes' => 'yes'], 'elements' => []],
        ],
    ]];
    update_post_meta($layout_id, '_elementor_data', wp_slash(wp_json_encode($fallback_data)));
    $product = null;
    $fallback_html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($layout_id, true);
    $result = [
        'product_media_ssr' => str_contains($html, 'keleva-product-media'),
        'single_product_layout_id' => $active_single_layout_id,
        'single_product_layout_delegates_shortcode' => str_contains(wp_unslash($active_single_layout_data), '[keleva_product_single]'),
        'single_product_layout_media_renderer_available' => class_exists('Keleva_Elementor_Product_Media') && is_callable(['Keleva_Elementor_Product_Media', 'render_product_media']),
        'single_product_layout_shortcode_not_duplicated' => 1 === substr_count(wp_unslash($active_single_layout_data), '[keleva_product_single]'),
        'product_badges_ssr' => str_contains($html, 'keleva-product-badges'),
        'product_tabs_ssr' => str_contains($html, 'keleva-product-tabs'),
        'checkout_shell_ssr' => str_contains($html, 'keleva-checkout-shell'),
        'classic_checkout_ssr' => str_contains($html, 'woocommerce-checkout'),
        'wishlist_ssr' => str_contains($html, 'keleva-wishlist') && str_contains($html, $test_product->get_name()),
        'compare_ssr' => str_contains($html, 'keleva-compare') && str_contains($html, 'Comparaison enregistrée'),
        'mega_menu_ssr' => str_contains($html, 'keleva-mega-menu'),
        'analytics_permission_state_ssr' => str_contains($html, 'réservés aux comptes autorisés'),
        'saved_lists_session_mutation' => $saved_lists_session_mutation,
        'product_card_wishlist_form_ssr' => str_contains($product_card_html, 'name="keleva_saved_list"') && str_contains($product_card_html, 'value="wishlist"') && !str_contains($product_card_html, 'data-velora-favorite-toggle'),
        'wishlist_explicit_product_control_ssr' => str_contains($fallback_html, 'name="keleva_saved_list"') && str_contains($fallback_html, 'value="wishlist"') && str_contains($fallback_html, 'name="keleva_product_id" value="' . $test_product->get_id() . '"') && str_contains($fallback_html, 'name="keleva_saved_return"'),
        'compare_explicit_product_control_ssr' => str_contains($fallback_html, 'name="keleva_saved_list"') && str_contains($fallback_html, 'value="compare"') && str_contains($fallback_html, 'name="keleva_product_id" value="' . $test_product->get_id() . '"') && str_contains($fallback_html, 'name="keleva_saved_return"'),
        'product_tabs_explicit_product_control_ssr' => str_contains($fallback_html, 'keleva-product-tabs') && !str_contains($fallback_html, 'À placer dans un template produit.'),
    ];
    $result['pass'] = !in_array(false, $result, true);
    echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} finally {
    remove_filter('woocommerce_is_checkout', '__return_true');
    WC()->session->set('keleva_wishlist_products', []);
    WC()->session->set('keleva_compare_products', []);
    wp_delete_post($layout_id, true);
    wp_reset_postdata();
}

if (!$result['pass']) exit(1);
