<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Quick_View extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-quick-view'; }
    public function get_title(): string { return __('Keleva Quick View', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-zoom-in-bold'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'quick view', 'product', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Aperçu produit', 'keleva-woo-addons')]);
        $this->add_control('product_id', ['label' => __('ID produit', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'default' => 0]);
        $this->add_control('label', ['label' => __('Libellé', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Voir le produit', 'keleva-woo-addons')]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product = wc_get_product((int) $settings['product_id']);
        if (!$product || 'publish' !== $product->get_status()) {
            echo '<p class="keleva-widget-empty">' . esc_html__('Aucun aperçu produit disponible.', 'keleva-woo-addons') . '</p>';
            return;
        }
        echo '<section class="keleva-quick-view" aria-label="' . esc_attr($product->get_name()) . '">';
        echo '<h2 class="keleva-quick-view__title">' . esc_html($product->get_name()) . '</h2>';
        echo '<p class="keleva-quick-view__price">' . wp_kses_post($product->get_price_html()) . '</p>';
        echo '<p class="keleva-quick-view__stock">' . esc_html($product->is_in_stock() ? __('En stock', 'keleva-woo-addons') : __('Indisponible', 'keleva-woo-addons')) . '</p>';
        echo '<a class="keleva-primary-button" href="' . esc_url($product->get_permalink()) . '">' . esc_html($settings['label']) . '</a>';
        echo '</section>';
    }
}
