<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Mega_Menu extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-mega-menu'; }
    public function get_title(): string { return __('Keleva Mega Menu', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-nav-menu'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['menu', 'navigation', 'categories', 'keleva']; }
    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Navigation', 'keleva-woo-addons')]);
        $this->add_control('menu_location', ['label' => __('Emplacement du menu', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'primary']);
        $this->add_control('fallback_categories', ['label' => __('Fallback catégories produit', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();
    }
    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $location = sanitize_key((string) ($settings['menu_location'] ?? 'primary'));
        $menu = wp_nav_menu(['theme_location' => $location, 'container' => '', 'menu_class' => 'keleva-mega-menu__items', 'fallback_cb' => false, 'echo' => false]);
        echo '<nav class="keleva-mega-menu" aria-label="' . esc_attr__('Navigation catalogue', 'keleva-woo-addons') . '">';
        if ($menu) echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        elseif ('yes' === ($settings['fallback_categories'] ?? 'yes')) wp_list_categories(['taxonomy' => 'product_cat', 'title_li' => '', 'depth' => 1, 'show_count' => false]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        else echo '<p class="keleva-widget-empty" role="status">' . esc_html__('Aucun menu n’est configuré pour cet emplacement.', 'keleva-woo-addons') . '</p>';
        echo '</nav>';
    }
}
