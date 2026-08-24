<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Compare extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-compare'; }
    public function get_title(): string { return __('Keleva Compare', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-table'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['compare', 'comparison', 'woocommerce', 'keleva']; }
    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Comparaison', 'keleva-woo-addons')]);
        $this->add_control('mode', ['label' => __('Affichage', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'both', 'options' => ['toggle' => __('Bouton produit', 'keleva-woo-addons'), 'table' => __('Tableau', 'keleva-woo-addons'), 'both' => __('Les deux', 'keleva-woo-addons')]]);
        $this->add_control('product_id', ['label' => __('ID produit pour le bouton', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'default' => 0]);
        $this->end_controls_section();
    }
    protected function render(): void {
        global $product;
        $settings = $this->get_settings_for_display();
        $mode = (string) ($settings['mode'] ?? 'both');
        $context_product = $product instanceof WC_Product ? $product : wc_get_product((int) ($settings['product_id'] ?? 0));
        echo '<section class="keleva-compare" aria-label="' . esc_attr__('Comparer les produits', 'keleva-woo-addons') . '">';
        if (in_array($mode, ['toggle', 'both'], true) && $context_product instanceof WC_Product) echo Keleva_Saved_Product_Lists::toggle_form('compare', $context_product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        if (in_array($mode, ['table', 'both'], true)) {
            $items = array_filter(array_map('wc_get_product', Keleva_Saved_Product_Lists::ids('compare')));
            if (!$items) echo '<p class="keleva-widget-empty" role="status">' . esc_html__('Ajoutez jusqu’à quatre produits pour les comparer.', 'keleva-woo-addons') . '</p>';
            else { echo '<div class="keleva-compare__scroll"><table><caption>' . esc_html__('Comparaison enregistrée pour cette session', 'keleva-woo-addons') . '</caption><thead><tr><th scope="col">' . esc_html__('Critère', 'keleva-woo-addons') . '</th>'; foreach ($items as $item) echo '<th scope="col"><a href="' . esc_url(get_permalink($item->get_id())) . '">' . esc_html($item->get_name()) . '</a></th>'; echo '</tr></thead><tbody><tr><th scope="row">' . esc_html__('Prix', 'keleva-woo-addons') . '</th>'; foreach ($items as $item) echo '<td>' . wp_kses_post($item->get_price_html()) . '</td>'; echo '</tr><tr><th scope="row">' . esc_html__('Disponibilité', 'keleva-woo-addons') . '</th>'; foreach ($items as $item) echo '<td>' . esc_html($item->is_in_stock() ? __('En stock', 'keleva-woo-addons') : __('Indisponible', 'keleva-woo-addons')) . '</td>'; echo '</tr></tbody></table></div>'; }
        }
        echo '</section>';
    }
}
