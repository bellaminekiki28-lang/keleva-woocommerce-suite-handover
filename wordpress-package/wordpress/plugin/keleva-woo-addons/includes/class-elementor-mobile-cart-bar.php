<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Mobile_Cart_Bar extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-mobile-cart-bar'; }
    public function get_title(): string { return __('Keleva Mobile Cart Bar', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-cart'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'mobile', 'cart', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Barre panier mobile', 'keleva-woo-addons')]);
        $this->add_control('label', ['label' => __('Libellé', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Voir le panier', 'keleva-woo-addons')]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $count = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
        echo '<nav class="keleva-mobile-cart-bar" aria-label="' . esc_attr__('Panier mobile', 'keleva-woo-addons') . '">';
        /* translators: %d is the number of products currently in the cart. */
        echo '<a class="keleva-primary-button" href="' . esc_url(wc_get_cart_url()) . '"><span>' . esc_html($settings['label']) . '</span><span class="keleva-mobile-cart-bar__count" aria-label="' . esc_attr(sprintf(_n('%d article', '%d articles', $count, 'keleva-woo-addons'), $count)) . '">' . esc_html((string) $count) . '</span></a>';
        echo '</nav>';
    }
}
