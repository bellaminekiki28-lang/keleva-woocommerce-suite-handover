<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$orders = wc_get_orders([
    'limit' => -1,
    'return' => 'objects',
    'meta_key' => '_keleva_local_recipe_order',
    'meta_value' => '1',
]);
foreach ($orders as $order) {
    if ($order instanceof WC_Order) {
        $order->delete(true);
    }
}
echo count($orders) . " commande(s) de recette supprimée(s).\n";
