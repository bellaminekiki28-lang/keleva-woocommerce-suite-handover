<?php
defined('ABSPATH') || exit;

function keleva_woo_cart_cross_sells(WP_REST_Request $request): WP_REST_Response {
    if (!function_exists('WC') || !function_exists('wc_load_cart')) {
        return rest_ensure_response(['products' => []]);
    }

    if (null === WC()->cart) {
        wc_load_cart();
    }

    if (!WC()->cart) {
        return rest_ensure_response(['products' => []]);
    }

    $cart_product_ids = [];
    $cross_sell_ids = [];
    foreach (WC()->cart->get_cart() as $item) {
        $product_id = absint($item['product_id'] ?? 0);
        if (!$product_id) {
            continue;
        }
        $cart_product_ids[] = $product_id;
        $product = wc_get_product($product_id);
        if ($product instanceof WC_Product) {
            $cross_sell_ids = array_merge($cross_sell_ids, array_map('absint', $product->get_cross_sell_ids()));
        }
    }

    $cross_sell_ids = array_values(array_diff(array_unique($cross_sell_ids), array_unique($cart_product_ids)));
    if (!$cross_sell_ids) {
        return rest_ensure_response(['products' => []]);
    }

    $products = wc_get_products([
        'include' => $cross_sell_ids,
        'status' => 'publish',
        'stock_status' => 'instock',
        'orderby' => 'include',
        'limit' => 3,
        'return' => 'objects',
    ]);

    $payload = [];
    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_purchasable()) {
            continue;
        }
        $image_id = $product->get_image_id();
        $payload[] = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => html_entity_decode(wp_strip_all_tags($product->get_price_html()), ENT_QUOTES, get_bloginfo('charset')),
            'image' => $image_id ? wp_get_attachment_image_url(absint($image_id), 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail'),
        ];
    }

    return rest_ensure_response(['products' => $payload]);
}

add_action('rest_api_init', static function (): void {
    register_rest_route('keleva/v1', '/cart/cross-sells', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'keleva_woo_cart_cross_sells',
        'permission_callback' => '__return_true',
    ]);
});
