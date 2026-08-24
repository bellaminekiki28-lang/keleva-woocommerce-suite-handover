<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$action = $argv[1] ?? 'create';
if ('cleanup' === $action) {
    $products = wc_get_products([
        'limit' => -1,
        'return' => 'objects',
        'meta_key' => '_keleva_local_catalog_recipe',
        'meta_value' => '1',
        'status' => ['publish', 'draft', 'private'],
    ]);
    foreach ($products as $product) {
        $product->delete(true);
    }
    echo count($products) . " produit(s) de catalogue supprimé(s).\n";
    exit(0);
}

for ($index = 1; $index <= 26; $index++) {
    $sku = sprintf('KELEVA-LOCAL-CATALOG-%02d', $index);
    $id = wc_get_product_id_by_sku($sku);
    $product = $id ? wc_get_product($id) : new WC_Product_Simple();
    $product->set_name(sprintf('Référence catalogue locale %02d', $index));
    $product->set_sku($sku);
    $product->set_status('draft');
    $product->set_regular_price('1.00');
    $product->set_catalog_visibility('hidden');
    $product->update_meta_data('_keleva_local_catalog_recipe', '1');
    $product->save();
}
echo "26 produits de catalogue créés.\n";
