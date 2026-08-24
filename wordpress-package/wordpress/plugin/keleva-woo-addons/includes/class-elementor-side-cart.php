<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Side_Cart extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-side-cart'; }
    public function get_title(): string { return __('Keleva Side Cart', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-cart-solid'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'cart', 'side cart', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Panier latéral', 'keleva-woo-addons')]);
        $this->add_control('title', [
            'label' => __('Titre', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Votre sélection', 'keleva-woo-addons'),
        ]);
        $this->add_control('cta', [
            'label' => __('Libellé de commande', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Passer commande', 'keleva-woo-addons'),
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        echo '<aside class="keleva-side-cart" aria-label="' . esc_attr__('Panier', 'keleva-woo-addons') . '">';
        echo '<h2>' . esc_html($settings['title']) . '</h2>';
        /* translators: %d is the number of products currently in the cart. */
        echo '<p data-keleva-cart-message>' . esc_html($count ? sprintf(_n('%d article dans votre sélection.', '%d articles dans votre sélection.', $count, 'keleva-woo-addons'), $count) : __('Votre sélection est prête à accueillir un produit.', 'keleva-woo-addons')) . '</p>';
        echo '<a class="keleva-primary-button keleva-side-cart__cta" href="' . esc_url(wc_get_checkout_url()) . '">' . esc_html($settings['cta']) . '</a>';
        echo '</aside>';
    }
}
