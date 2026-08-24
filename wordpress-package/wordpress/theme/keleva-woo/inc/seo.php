<?php
/**
 * SEO and merchant feed integrations.
 *
 * @package KelevaWoo
 */

defined('ABSPATH') || exit;

add_filter('wp_robots', static function (array $robots): array {
    if (is_cart() || is_checkout() || is_account_page() || is_search() || is_page('keleva-validation-widgets')) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
});

add_action('init', static function (): void {
    add_feed('keleva-merchant-products', 'keleva_woo_render_merchant_feed');
});

add_action('wp_head', 'keleva_woo_render_product_schema', 30);

function keleva_woo_render_product_schema(): void {
    if (!is_product() || !function_exists('wc_get_product')) {
        return;
    }

    $product_id = (int) get_queried_object_id();
    $product = $product_id ? wc_get_product($product_id) : false;
    if (!$product instanceof WC_Product) {
        return;
    }

    $image_ids = array_filter(array_merge([(int) $product->get_image_id()], array_map('intval', $product->get_gallery_image_ids())));
    $images = array_values(array_filter(array_map(static function (int $image_id): string {
        return (string) wp_get_attachment_image_url($image_id, 'full');
    }, $image_ids)));
    $description = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'description' => $description ?: $product->get_name(),
        'sku' => $product->get_sku() ?: (string) $product->get_id(),
        'url' => $product->get_permalink(),
        'image' => $images,
    ];

    if ('' !== $product->get_price()) {
        $schema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => get_woocommerce_currency(),
            'price' => wc_format_decimal($product->get_price(), wc_get_price_decimals()),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'url' => $product->get_permalink(),
        ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

function keleva_woo_render_merchant_feed(): void {
    if (!function_exists('wc_get_products')) {
        status_header(503);
        echo esc_html__('WooCommerce est requis pour le feed marchand.', 'keleva-woo');
        return;
    }

    nocache_headers();
    header('Content-Type: application/xml; charset=' . get_option('blog_charset'));
    $products = wc_get_products([
        'status' => 'publish',
        'limit' => 500,
        'return' => 'objects',
    ]);
    echo '<?xml version="1.0" encoding="' . esc_attr(get_option('blog_charset')) . '"?>';
    echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
    echo '<title>' . esc_html(get_bloginfo('name')) . '</title>';
    echo '<link>' . esc_url(home_url('/')) . '</link>';
    echo '<description>' . esc_html(get_bloginfo('description')) . '</description>';
    foreach ($products as $keleva_product) {
        $image_id = (int) $keleva_product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
        $availability = $keleva_product->is_in_stock() ? 'in_stock' : 'out_of_stock';
        echo '<item>';
        echo '<g:id>' . esc_html((string) $keleva_product->get_id()) . '</g:id>';
        echo '<title>' . esc_html($keleva_product->get_name()) . '</title>';
        echo '<description>' . esc_html(wp_strip_all_tags($keleva_product->get_short_description() ?: $keleva_product->get_description())) . '</description>';
        echo '<link>' . esc_url($keleva_product->get_permalink()) . '</link>';
        if ($image_url) {
            echo '<g:image_link>' . esc_url($image_url) . '</g:image_link>';
        }
        echo '<g:availability>' . esc_html($availability) . '</g:availability>';
        echo '<g:price>' . esc_html(wc_format_decimal($keleva_product->get_price(), wc_get_price_decimals()) . ' ' . get_woocommerce_currency()) . '</g:price>';
        if ($keleva_product->get_sku()) {
            echo '<g:mpn>' . esc_html($keleva_product->get_sku()) . '</g:mpn>';
        }
        echo '</item>';
    }
    echo '</channel></rss>';
    exit;
}

add_action('send_headers', static function (): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Frame-Options: SAMEORIGIN');
    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    if (defined('KELEVA_CSP_REPORT_ONLY') && KELEVA_CSP_REPORT_ONLY) {
        header("Content-Security-Policy-Report-Only: default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; upgrade-insecure-requests");
    }
});
