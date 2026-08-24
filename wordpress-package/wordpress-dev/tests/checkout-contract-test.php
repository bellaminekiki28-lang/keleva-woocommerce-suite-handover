<?php
declare(strict_types=1);
$siteRoot = getenv('KELEVA_SITE_ROOT') ?: dirname(__DIR__) . '/site';
require $siteRoot . '/wp-load.php';

if (apply_filters('pre_option_woocommerce_enable_guest_checkout', false) !== 'yes') throw new RuntimeException('Le checkout invité doit être actif.');
if ((int) apply_filters('wc_session_expiration', 0) < 7 * DAY_IN_SECONDS) throw new RuntimeException('La session panier doit couvrir sept jours.');
$fields = WC()->checkout()->get_checkout_fields();
if (($fields['billing']['billing_email']['custom_attributes']['autocomplete'] ?? '') !== 'email') throw new RuntimeException('Autocomplete e-mail manquant.');
if (($fields['billing']['billing_postcode']['custom_attributes']['autocomplete'] ?? '') !== 'postal-code') throw new RuntimeException('Autocomplete code postal manquant.');
echo "Contrat checkout local : invité=oui session=7j autocomplete=email+postal-code\n";
