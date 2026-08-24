<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$action = $argv[1] ?? 'create';
$initialStatus = isset($argv[2]) && in_array($argv[2], ['pending', 'processing', 'completed'], true) ? $argv[2] : 'processing';
if ('delete' === $action) {
    $orderId = isset($argv[2]) ? absint($argv[2]) : 0;
    $order = $orderId ? wc_get_order($orderId) : false;
    if ($order) {
        $order->delete(true);
    }
    echo "deleted\n";
    exit(0);
}

$product = wc_get_product(11);
if (!$product) {
    fwrite(STDERR, "Produit de recette local introuvable.\n");
    exit(1);
}

$order = wc_create_order();
$order->add_product($product, 1);
$order->set_billing_first_name('Recette');
$order->set_billing_last_name('Locale');
$order->set_billing_email('recette-locale@example.test');
$order->set_payment_method('bacs');
$order->set_payment_method_title('Recette locale');
$order->update_meta_data('_keleva_local_recipe_order', '1');
$order->calculate_totals();
$order->update_status($initialStatus, 'Commande temporaire pour la recette locale Keleva.', true);
$order->save();

if ('create-json' === $action) {
    echo wp_json_encode(['id' => $order->get_id(), 'key' => $order->get_order_key()], JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo $order->get_id() . "\n";
