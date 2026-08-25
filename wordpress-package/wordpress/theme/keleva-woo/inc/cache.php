<?php
defined('ABSPATH') || exit;

function keleva_woo_has_customer_session(): bool {
    foreach (array_keys($_COOKIE) as $name) {
        if (str_starts_with($name, 'wp_woocommerce_session_')) {
            return true;
        }
    }

    return false;
}

/**
 * Les cartes produit servent un formulaire favori noncé et un état issu de la
 * session WooCommerce. Une réponse de catalogue partagée ne doit donc pas être
 * réutilisée après une mutation de liste enregistrée dans la même session.
 */
function keleva_woo_renders_saved_product_state(): bool {
    if (function_exists('is_woocommerce') && is_woocommerce()) {
        return true;
    }

    if (!function_exists('wc_get_page_id')) {
        return false;
    }

    $shop_page_id = (int) wc_get_page_id('shop');
    return $shop_page_id > 0 && is_page($shop_page_id);
}

function keleva_woo_is_validation_page(): bool {
    return is_page('keleva-validation-widgets');
}

function keleva_woo_is_private_response(): bool {
    if (is_user_logged_in() || keleva_woo_has_customer_session() || keleva_woo_renders_saved_product_state() || keleva_woo_is_validation_page()) {
        return true;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Paramètres GET non mutatifs de filtrage catalogue.
    if (isset($_GET['keleva_category']) || isset($_GET['keleva_stock'])) {
        return true;
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    return function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
}

add_action('send_headers', static function (): void {
    if (is_admin() || is_feed() || headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff', true);
    header('X-Frame-Options: SAMEORIGIN', true);
    header('Referrer-Policy: strict-origin-when-cross-origin', true);
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()', true);

    if (keleva_woo_is_private_response()) {
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0', true);
        return;
    }

    header('Cache-Control: public, max-age=300, s-maxage=900, stale-while-revalidate=60', true);
    header('X-Keleva-Cache-Policy: public-catalog', true);
});

add_action('template_redirect', static function (): void {
    if (is_admin() || headers_sent()) {
        return;
    }

    // Les conditionnels WooCommerce ne sont fiables qu’après analyse de la requête.
    if (keleva_woo_is_private_response()) {
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0', true);
        header('X-Keleva-Cache-Policy: private-customer', true);
    }
}, 0);

function keleva_woo_featured_products(int $limit = 9): array {
    // Keep the curated list when the Velora demo catalog exists, but fall back to
    // the latest published WooCommerce products on stores with different slugs.
    $key = 'keleva_featured_products_v2_' . $limit;
    $products = get_transient($key);

    if (false !== $products) {
        return $products;
    }

    if (!function_exists('wc_get_products')) {
        return [];
    }

    $velora_slugs = [
        'mug-nomade-sienna',
        'pochette-field-olive',
        'vase-forme-02',
        'lampe-halo-portable',
        'carnet-ligne-claire',
        'tote-canvas-03',
        'plateau-ondulation',
        'duo-pause-juste',
    ];
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post_name__in' => $velora_slugs,
        'orderby' => 'post_name__in',
        'order' => 'ASC',
        'fields' => 'ids',
    ]);
    if ($ids) {
        $products = wc_get_products([
            'status' => 'publish',
            'include' => $ids,
            'orderby' => 'include',
            'limit' => $limit,
            'return' => 'objects',
        ]);
    } else {
        // A fresh installation may use different product slugs. Avoid rendering
        // an empty home catalogue and derive the hero image from the first result.
        $products = wc_get_products([
            'status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'limit' => $limit,
            'return' => 'objects',
        ]);
    }

    set_transient($key, $products, 5 * MINUTE_IN_SECONDS);
    return $products;
}

function keleva_woo_flush_product_caches(): void {
    foreach ([6, 9, 12] as $limit) {
        delete_transient('keleva_featured_products_' . $limit);
        delete_transient('keleva_featured_products_v2_' . $limit);
    }
}

add_action('save_post_product', static function (): void {
    keleva_woo_flush_product_caches();
});
add_action('woocommerce_product_set_stock_status', static function (): void {
    keleva_woo_flush_product_caches();
});
add_action('woocommerce_update_product', static function (): void {
    keleva_woo_flush_product_caches();
});
add_action('woocommerce_product_set_price', static function (): void {
    keleva_woo_flush_product_caches();
});
add_action('woocommerce_variation_set_price', static function (): void {
    keleva_woo_flush_product_caches();
});
add_action('updated_post_meta', static function (int $meta_id, int $post_id, string $meta_key): void {
    if ('product' !== get_post_type($post_id)) {
        return;
    }

    if (in_array($meta_key, ['_price', '_regular_price', '_sale_price', '_stock', '_stock_status', '_thumbnail_id'], true)) {
        keleva_woo_flush_product_caches();
    }
}, 10, 3);
add_action('set_object_terms', static function (int $object_id, array $terms, array $term_taxonomy_ids, string $taxonomy): void {
    if ('product_cat' === $taxonomy && 'product' === get_post_type($object_id)) {
        keleva_woo_flush_product_caches();
    }
}, 10, 4);

// Panier, checkout et compte ne nécessitent aucune purge : ils ne sont jamais mis en cache (`private, no-store`).
