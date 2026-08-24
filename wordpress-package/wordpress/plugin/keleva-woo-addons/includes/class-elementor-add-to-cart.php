<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Add_To_Cart extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-add-to-cart'; }
    public function get_title(): string { return __('Keleva Add to Cart', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-cart-solid'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'add to cart', 'variation', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Ajout panier', 'keleva-woo-addons')]);
        $this->add_control('product_id', ['label' => __('ID produit', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'default' => 0]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product = wc_get_product((int) $settings['product_id']);
        if (!$product || 'publish' !== $product->get_status()) {
            echo '<p class="keleva-widget-empty">' . esc_html__('Sélectionnez un produit publié pour afficher l’ajout au panier.', 'keleva-woo-addons') . '</p>';
            return;
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Les templates WooCommerce requièrent le global product.
        $GLOBALS['product'] = $product;
        echo '<div class="keleva-add-to-cart">';
        if ($product->is_type('variable')) {
            echo '<p class="keleva-add-to-cart__hint">' . esc_html__('Choisissez vos options sur la fiche produit avant ajout.', 'keleva-woo-addons') . '</p>';
            echo '<a class="keleva-primary-button" href="' . esc_url($product->get_permalink()) . '">' . esc_html__('Choisir une variation', 'keleva-woo-addons') . '</a>';
        } else {
            woocommerce_template_loop_add_to_cart();
        }
        echo '</div>';
        wp_reset_postdata();
    }
}
