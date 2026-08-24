<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Search extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-search'; }
    public function get_title(): string { return __('Keleva Product Search', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-search'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'search', 'product', 'keleva']; }
    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Recherche', 'keleva-woo-addons')]);
        $this->add_control('placeholder', ['label' => __('Placeholder', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Rechercher un produit', 'keleva-woo-addons')]);
        $this->end_controls_section();
    }
    protected function render(): void {
        $settings = $this->get_settings_for_display();
        echo '<form class="keleva-product-search" role="search" method="get" action="' . esc_url(home_url('/')) . '">';
        echo '<label class="screen-reader-text" for="keleva-product-search-' . esc_attr($this->get_id()) . '">' . esc_html__('Rechercher un produit', 'keleva-woo-addons') . '</label>';
        echo '<input id="keleva-product-search-' . esc_attr($this->get_id()) . '" type="search" name="s" value="' . esc_attr(get_search_query()) . '" placeholder="' . esc_attr($settings['placeholder']) . '" />';
        echo '<input type="hidden" name="post_type" value="product" />';
        echo '<button class="keleva-primary-button" type="submit">' . esc_html__('Rechercher', 'keleva-woo-addons') . '</button></form>';
    }
}
