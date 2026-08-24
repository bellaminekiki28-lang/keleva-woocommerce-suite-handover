<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Meta extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-meta'; }
    public function get_title(): string { return __('Keleva Product Meta', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-product-info'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'single product', 'stock', 'meta', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Informations produit', 'keleva-woo-addons')]);
        $this->add_control('show_stock', [
            'label' => __('Afficher le stock', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        global $product;
        if (!$product instanceof WC_Product) {
            echo '<p>' . esc_html__('À placer dans un template produit.', 'keleva-woo-addons') . '</p>';
            return;
        }
        $settings = $this->get_settings_for_display();
        echo '<div class="keleva-product-meta-widget">';
        echo '<p class="keleva-product-card__meta">' . wp_kses_post(wc_get_product_category_list($product->get_id(), ', ')) . '</p>';
        if ('yes' === $settings['show_stock']) {
            echo '<p>' . wp_kses_post(wc_get_stock_html($product)) . '</p>';
        }
        echo '</div>';
    }
}
