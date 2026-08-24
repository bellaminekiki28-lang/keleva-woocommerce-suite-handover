<?php

defined('ABSPATH') || exit;

$zones = WC_Shipping_Zones::get_zones();
$morocco = null;
foreach ($zones as $zone) {
    if ('Keleva Maroc' === $zone['zone_name']) {
        $morocco = $zone;
        break;
    }
}

$methods = $morocco ? array_values(wp_list_pluck($morocco['shipping_methods'], 'id')) : [];
$locations = $morocco ? array_values(wp_list_pluck($morocco['zone_locations'], 'code')) : [];
$stripe_active = is_plugin_active('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php');
$stripe_settings = get_option('woocommerce_stripe_settings', []);
$stripe_test_mode = !empty($stripe_settings['testmode']) && 'yes' === $stripe_settings['testmode'];
$stripe_connected = !empty($stripe_settings['test_secret_key']) && !empty($stripe_settings['test_publishable_key']);

$result = [
    'country' => get_option('woocommerce_default_country'),
    'currency' => get_woocommerce_currency(),
    'morocco_zone' => (bool) $morocco,
    'locations' => $locations,
    'shipping_methods' => $methods,
    'stripe_plugin_active' => $stripe_active,
    'stripe_test_mode' => $stripe_test_mode,
    'stripe_test_credentials_configured' => $stripe_connected,
];
$result['pass'] = 'MA' === $result['country']
    && 'MAD' === $result['currency']
    && $result['morocco_zone']
    && in_array('MA', $locations, true)
    && !array_diff(['flat_rate', 'free_shipping', 'local_pickup'], $methods);

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$result['pass']) {
    exit(1);
}
