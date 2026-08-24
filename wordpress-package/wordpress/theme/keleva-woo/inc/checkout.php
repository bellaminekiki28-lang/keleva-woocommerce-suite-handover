<?php
defined('ABSPATH') || exit;

/** Checkout invité : le compte reste toujours facultatif et le panier de session est conservé sept jours. */
add_filter('pre_option_woocommerce_enable_guest_checkout', static fn (): string => 'yes');
add_filter('pre_option_woocommerce_enable_checkout_login_reminder', static fn (): string => 'no');
add_filter('wc_session_expiring', static fn (): int => 6 * DAY_IN_SECONDS + 12 * HOUR_IN_SECONDS);
add_filter('wc_session_expiration', static fn (): int => 7 * DAY_IN_SECONDS);

add_filter('woocommerce_checkout_fields', static function (array $fields): array {
    $autocomplete = [
        'billing_first_name' => 'given-name', 'billing_last_name' => 'family-name', 'billing_company' => 'organization',
        'billing_country' => 'country-name', 'billing_address_1' => 'address-line1', 'billing_address_2' => 'address-line2',
        'billing_city' => 'address-level2', 'billing_state' => 'address-level1', 'billing_postcode' => 'postal-code',
        'billing_phone' => 'tel', 'billing_email' => 'email',
    ];
    foreach ($autocomplete as $field => $value) {
        if (isset($fields['billing'][$field])) {
            $fields['billing'][$field]['custom_attributes']['autocomplete'] = $value;
        }
    }
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['required'] = false;
        $fields['order']['order_comments']['placeholder'] = __('Un détail utile pour la livraison ? (facultatif)', 'keleva-woo');
    }
    return $fields;
}, 20);

function keleva_woo_checkout_intro_markup(): string {
    $gateways = WC()->payment_gateways()?->get_available_payment_gateways() ?? [];
    return '<section class="keleva-checkout-intro" aria-labelledby="keleva-checkout-intro-title"><span class="keleva-eyebrow">' . esc_html__('Finaliser simplement', 'keleva-woo') . '</span><h1 id="keleva-checkout-intro-title">' . esc_html__('Votre commande, sans compte obligatoire.', 'keleva-woo') . '</h1><p>' . esc_html__('Vos coordonnées servent uniquement à préparer la livraison. Votre panier reste disponible pendant sept jours sur cet appareil.', 'keleva-woo') . '</p><ul><li>' . esc_html__('Paiement et montant vérifiés avant confirmation.', 'keleva-woo') . '</li><li>' . esc_html__('Coordonnées suggérées par votre navigateur quand elles sont disponibles.', 'keleva-woo') . '</li><li>' . esc_html($gateways ? __('Moyens de paiement configurés affichés à l’étape suivante.', 'keleva-woo') : __('Aucun moyen de paiement n’est encore configuré pour cette boutique.', 'keleva-woo')) . '</li></ul></section>';
}

add_action('woocommerce_before_checkout_form', static function (): void {
    if (!is_checkout() || is_order_received_page()) return;
    echo wp_kses_post(keleva_woo_checkout_intro_markup());
}, 5);

add_filter('render_block_woocommerce/checkout', static function (string $content): string {
    if (is_admin() || !is_checkout() || is_order_received_page() || str_contains($content, 'keleva-checkout-intro')) return $content;
    $intro = keleva_woo_checkout_intro_markup();
    if (str_contains($content, 'wc-block-checkout') || str_contains($content, 'wc-block-components-checkout')) {
        return $intro . $content;
    }
    return $intro . do_shortcode('[woocommerce_checkout]');
}, 10);

add_filter('render_block_woocommerce/cart', static function (string $content): string {
    if (is_admin() || !is_cart() || !str_contains($content, 'wc-block-cart__empty-cart')) return $content;
    $replacement = '<p class="velora-eyebrow">' . esc_html__('Votre sélection', 'keleva-woo') . '</p><h2$1>' . esc_html__('Le panier est encore léger.', 'keleva-woo') . '</h2><p class="keleva-cart-empty-copy">' . esc_html__('Parcourez la sélection : vos futurs coups de cœur resteront disponibles dans le tiroir panier.', 'keleva-woo') . '</p>';
    return preg_replace('~<h2([^>]*wc-block-cart__empty-cart__title[^>]*)>.*?</h2>~s', $replacement, $content, 1) ?: $content;
}, 10);

add_action('woocommerce_before_thankyou', static function (int $order_id): void {
    $order = wc_get_order($order_id);
    if (!$order) return;
    /* translators: %s: customer billing first name. */
    echo '<section class="keleva-thankyou-intro" aria-labelledby="keleva-thankyou-title"><p class="velora-eyebrow">' . esc_html__('Confirmation', 'keleva-woo') . '</p><h1 id="keleva-thankyou-title">' . esc_html__('Votre commande est bien enregistrée.', 'keleva-woo') . '</h1><p>' . esc_html(sprintf(__('Merci %s. Nous préparons votre sélection et vous tiendrons informé·e par e-mail.', 'keleva-woo'), $order->get_billing_first_name() ?: __('à vous', 'keleva-woo'))) . '</p></section>';
}, 5);
