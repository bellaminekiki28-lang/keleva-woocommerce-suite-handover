<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Mini_Cart extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-mini-cart'; }
    public function get_title(): string { return __('Keleva Mini Cart', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-cart-medium'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'mini cart', 'header', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Mini panier', 'keleva-woo-addons')]);
        $this->add_control('title', ['label' => __('Titre', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Votre panier', 'keleva-woo-addons')]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        if (!function_exists('woocommerce_mini_cart')) {
            echo '<p class="keleva-widget-empty">' . esc_html__('WooCommerce est requis pour le mini panier.', 'keleva-woo-addons') . '</p>';
            return;
        }
        echo '<section class="keleva-mini-cart" aria-label="' . esc_attr($settings['title']) . '">';
        echo '<h2 class="keleva-mini-cart__title">' . esc_html($settings['title']) . '</h2>';
        woocommerce_mini_cart();
        echo '</section>';
    }
}
