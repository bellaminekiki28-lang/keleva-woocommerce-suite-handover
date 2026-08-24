<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Filters extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-filters'; }
    public function get_title(): string { return __('Keleva Product Filters', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-filter'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'filter', 'category', 'stock', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Filtres', 'keleva-woo-addons')]);
        $this->add_control('show_stock', ['label' => __('Afficher le filtre de stock', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filtres GET non mutatifs, volontairement partageables et crawlables.
        $selected_category = sanitize_title(wp_unslash($_GET['keleva_category'] ?? ''));
        $in_stock = isset($_GET['keleva_stock']) && 'instock' === sanitize_key(wp_unslash($_GET['keleva_stock']));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        echo '<form class="keleva-product-filters" method="get" action="' . esc_url(get_post_type_archive_link('product') ?: home_url('/')) . '">';
        echo '<fieldset><legend>' . esc_html__('Filtrer les produits', 'keleva-woo-addons') . '</legend>';
        echo '<label for="keleva-product-category-' . esc_attr($this->get_id()) . '">' . esc_html__('Catégorie', 'keleva-woo-addons') . '</label>';
        echo '<select id="keleva-product-category-' . esc_attr($this->get_id()) . '" name="keleva_category"><option value="">' . esc_html__('Toutes les catégories', 'keleva-woo-addons') . '</option>';
        foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]) as $term) {
            echo '<option value="' . esc_attr($term->slug) . '"' . selected($selected_category, $term->slug, false) . '>' . esc_html($term->name) . '</option>';
        }
        echo '</select>';
        if ('yes' === $settings['show_stock']) {
            echo '<label><input type="checkbox" name="keleva_stock" value="instock"' . checked($in_stock, true, false) . ' /> ' . esc_html__('En stock uniquement', 'keleva-woo-addons') . '</label>';
        }
        echo '<button class="keleva-primary-button" type="submit">' . esc_html__('Appliquer', 'keleva-woo-addons') . '</button>';
        echo '<a href="' . esc_url(get_post_type_archive_link('product') ?: home_url('/')) . '">' . esc_html__('Réinitialiser', 'keleva-woo-addons') . '</a></fieldset></form>';
    }
}
