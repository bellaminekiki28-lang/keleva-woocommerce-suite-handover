<?php
/**
 * Product schema regression in a booted WordPress/WooCommerce instance.
 *
 * @package KelevaWooDev
 */

defined('ABSPATH') || exit;

$products = wc_get_products([
    'status' => 'publish',
    'limit' => 1,
    'return' => 'objects',
]);

if (!$products || !$products[0] instanceof WC_Product) {
    fwrite(STDERR, "No published WooCommerce product is available for schema validation.\n");
    exit(1);
}

$product = $products[0];
global $wp_query;
$previous_query = $wp_query;
$wp_query = new WP_Query([
    'post_type' => 'product',
    'p' => $product->get_id(),
]);
$wp_query->the_post();

ob_start();
keleva_woo_render_product_schema();
$output = ob_get_clean();
wp_reset_postdata();
$wp_query = $previous_query;

preg_match('/<script type="application\/ld\+json">(.*)<\/script>/', $output, $match);
$schema = isset($match[1]) ? json_decode($match[1], true) : null;
$pass = is_array($schema)
    && 'Product' === ($schema['@type'] ?? '')
    && $product->get_name() === ($schema['name'] ?? '')
    && $product->get_permalink() === ($schema['url'] ?? '')
    && ('' === $product->get_price() || (isset($schema['offers']['priceCurrency'], $schema['offers']['price'], $schema['offers']['availability']) && get_woocommerce_currency() === $schema['offers']['priceCurrency']));

echo wp_json_encode([
    'product_schema_ssr' => $pass,
    'product_id' => $product->get_id(),
    'pass' => $pass,
], JSON_PRETTY_PRINT) . PHP_EOL;
exit($pass ? 0 : 1);
