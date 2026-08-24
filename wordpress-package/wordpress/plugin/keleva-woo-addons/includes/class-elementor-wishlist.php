<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Wishlist extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-wishlist'; }
    public function get_title(): string { return __('Keleva Wishlist', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-heart-o'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['wishlist', 'favorites', 'woocommerce', 'keleva']; }
    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Favoris', 'keleva-woo-addons')]);
        $this->add_control('mode', ['label' => __('Affichage', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'both', 'options' => ['toggle' => __('Bouton produit', 'keleva-woo-addons'), 'list' => __('Liste', 'keleva-woo-addons'), 'both' => __('Les deux', 'keleva-woo-addons')]]);
        $this->add_control('product_id', ['label' => __('ID produit pour le bouton', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'default' => 0]);
        $this->end_controls_section();
    }
    protected function render(): void {
        global $product;
        $settings = $this->get_settings_for_display();
        $mode = (string) ($settings['mode'] ?? 'both');
        $context_product = $product instanceof WC_Product ? $product : wc_get_product((int) ($settings['product_id'] ?? 0));
        echo '<section class="keleva-wishlist" aria-label="' . esc_attr__('Favoris', 'keleva-woo-addons') . '">';
        if (in_array($mode, ['toggle', 'both'], true) && $context_product instanceof WC_Product) echo Keleva_Saved_Product_Lists::toggle_form('wishlist', $context_product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        if (in_array($mode, ['list', 'both'], true)) {
            $ids = Keleva_Saved_Product_Lists::ids('wishlist');
            if (!$ids) echo '<p class="keleva-widget-empty" role="status">' . esc_html__('Aucun favori enregistré dans cette session.', 'keleva-woo-addons') . '</p>';
            else { echo '<ul class="keleva-saved-list">'; foreach ($ids as $id) { $item = wc_get_product($id); if ($item) echo '<li><a href="' . esc_url(get_permalink($id)) . '">' . esc_html($item->get_name()) . '</a></li>'; } echo '</ul>'; }
        }
        echo '</section>';
    }
}
