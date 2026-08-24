<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$products = wc_get_products([
    'status' => 'publish',
    'limit' => 50,
    'orderby' => 'ID',
    'order' => 'ASC',
    'return' => 'objects',
]);

$result = [];
foreach ($products as $product) {
    if (!$product instanceof WC_Product) {
        continue;
    }
    $result[] = [
        'id' => $product->get_id(),
        'slug' => $product->get_slug(),
        'crossSellIds' => array_map('intval', $product->get_cross_sell_ids()),
        'categoryIds' => array_map('intval', $product->get_category_ids()),
    ];
}

echo wp_json_encode($result, JSON_UNESCAPED_SLASHES) . "\n";
