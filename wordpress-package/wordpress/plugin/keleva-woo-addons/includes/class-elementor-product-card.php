<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Card extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-card'; }
    public function get_title(): string { return __('Keleva Product Card', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-product-info'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'product', 'card', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Produit', 'keleva-woo-addons')]);
        $this->add_control('product_id', [
            'label' => __('ID produit', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 0,
            'default' => 0,
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product = wc_get_product((int) $settings['product_id']);
        if (!$product || 'publish' !== $product->get_status()) {
            echo '<p class="keleva-widget-empty">' . esc_html__('Sélectionnez un produit publié dans les réglages du widget.', 'keleva-woo-addons') . '</p>';
            return;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce attend le global product pour son template de carte.
        $GLOBALS['product'] = $product;
        echo '<div class="keleva-product-card">';
        wc_get_template_part('content', 'product');
        echo '</div>';
        wp_reset_postdata();
    }
}
