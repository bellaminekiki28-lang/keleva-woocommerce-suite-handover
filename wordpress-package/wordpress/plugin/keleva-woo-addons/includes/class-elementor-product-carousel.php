<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Carousel extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-carousel'; }
    public function get_title(): string { return __('Keleva Product Carousel', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-slider-push'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'product', 'carousel', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Carrousel produits', 'keleva-woo-addons')]);
        $this->add_control('limit', [
            'label' => __('Nombre de produits', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 8,
            'min' => 2,
            'max' => 24,
        ]);
        $this->add_control('order_by', [
            'label' => __('Trier par', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => ['date' => __('Nouveauté', 'keleva-woo-addons'), 'price' => __('Prix', 'keleva-woo-addons'), 'title' => __('Titre', 'keleva-woo-addons')],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => max(2, min(24, (int) $settings['limit'])),
            'orderby' => in_array($settings['order_by'], ['date', 'price', 'title'], true) ? $settings['order_by'] : 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]);
        if (!$products) {
            return;
        }
        echo '<ul class="keleva-product-carousel" aria-label="' . esc_attr__('Produits à découvrir', 'keleva-woo-addons') . '">';
        foreach ($products as $keleva_product) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce attend explicitement le global product lors du rendu d’un template.
            $GLOBALS['product'] = $keleva_product;
            wc_get_template_part('content', 'product');
        }
        wp_reset_postdata();
        echo '</ul>';
    }
}
