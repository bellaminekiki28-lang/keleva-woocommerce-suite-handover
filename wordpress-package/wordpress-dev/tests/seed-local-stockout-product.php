<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$action = $argv[1] ?? 'create';
if ('cleanup' === $action) {
    $products = wc_get_products([
        'limit' => -1,
        'return' => 'objects',
        'meta_key' => '_keleva_local_stockout_recipe',
        'meta_value' => '1',
        'status' => ['publish', 'draft', 'private'],
    ]);
    foreach ($products as $product) {
        $product->delete(true);
    }
    echo count($products) . " produit(s) de rupture supprimé(s).\n";
    exit(0);
}

$product = new WC_Product_Simple();
$product->set_name('Rupture locale Keleva');
$product->set_status('draft');
$product->set_regular_price('1');
$product->set_price('1');
$product->set_manage_stock(true);
$product->set_stock_quantity(0);
$product->set_stock_status('outofstock');
$product->update_meta_data('_keleva_local_stockout_recipe', '1');
$id = $product->save();

echo $id . "\n";
