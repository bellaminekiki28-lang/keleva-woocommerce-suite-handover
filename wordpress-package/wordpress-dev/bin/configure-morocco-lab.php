<?php
/**
 * Configure a WooCommerce lab for Morocco / MAD test scenarios.
 * Run with: wp eval-file wordpress-dev/bin/configure-morocco-lab.php
 */

defined('ABSPATH') || exit;

update_option('woocommerce_default_country', 'MA');
update_option('woocommerce_currency', 'MAD');
update_option('woocommerce_currency_pos', 'right_space');
update_option('woocommerce_price_num_decimals', 2);

$zone_id = 0;
foreach (WC_Shipping_Zones::get_zones() as $zone) {
    if ('Keleva Maroc' === $zone['zone_name']) {
        $zone_id = (int) $zone['zone_id'];
        break;
    }
}
if (!$zone_id) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('Keleva Maroc');
    $zone_id = (int) $zone->save();
    $zone = new WC_Shipping_Zone($zone_id);
    $zone->add_location('MA', 'country');
}

$zone = new WC_Shipping_Zone($zone_id);
$locations = $zone->get_zone_locations();
if (!in_array('MA', wp_list_pluck($locations, 'code'), true)) {
    $zone->add_location('MA', 'country');
    $zone->save();
    $zone = new WC_Shipping_Zone($zone_id);
}
$methods = $zone->get_shipping_methods(true);
$method_ids = wp_list_pluck($methods, 'id');
if (!in_array('flat_rate', $method_ids, true)) {
    $zone->add_shipping_method('flat_rate');
}
if (!in_array('free_shipping', $method_ids, true)) {
    $zone->add_shipping_method('free_shipping');
}
if (!in_array('local_pickup', $method_ids, true)) {
    $zone->add_shipping_method('local_pickup');
}

foreach ($zone->get_shipping_methods(true) as $instance_id => $method) {
    if ('flat_rate' === $method->id) {
        update_option('woocommerce_flat_rate_' . $instance_id . '_settings', ['title' => 'Livraison Maroc', 'cost' => '35', 'tax_status' => 'taxable']);
    }
    if ('free_shipping' === $method->id) {
        update_option('woocommerce_free_shipping_' . $instance_id . '_settings', ['title' => 'Livraison offerte', 'requires' => 'min_amount', 'min_amount' => '500']);
    }
    if ('local_pickup' === $method->id) {
        update_option('woocommerce_local_pickup_' . $instance_id . '_settings', ['title' => 'Retrait local', 'cost' => '0', 'tax_status' => 'none']);
    }
}

WC_Cache_Helper::get_transient_version('shipping', true);
echo wp_json_encode([
    'country' => get_option('woocommerce_default_country'),
    'currency' => get_woocommerce_currency(),
    'zone_id' => $zone_id,
    'methods' => array_values(wp_list_pluck($zone->get_shipping_methods(true), 'id')),
], JSON_UNESCAPED_SLASHES) . PHP_EOL;
