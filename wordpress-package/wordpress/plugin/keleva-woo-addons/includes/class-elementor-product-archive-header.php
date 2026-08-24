<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Archive_Header extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-archive-header'; }
    public function get_title(): string { return __('Keleva Product Archive Header', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-archive-title'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'archive', 'title', 'sorting', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Archive produit', 'keleva-woo-addons')]);
        $this->add_control('show_description', ['label' => __('Afficher la description', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_sorting', ['label' => __('Afficher tri et compteur', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        echo '<header class="keleva-product-archive-header">';
        echo '<h1>' . esc_html(wp_strip_all_tags(woocommerce_page_title(false))) . '</h1>';
        if ('yes' === $settings['show_description']) {
            $description = is_product_category() ? term_description() : '';
            if ($description) echo '<div class="keleva-product-archive-header__description">' . wp_kses_post($description) . '</div>';
        }
        if ('yes' === $settings['show_sorting'] && woocommerce_product_loop()) {
            echo '<div class="keleva-product-archive-header__tools">';
            woocommerce_result_count();
            woocommerce_catalog_ordering();
            echo '</div>';
        }
        echo '</header>';
    }
}
