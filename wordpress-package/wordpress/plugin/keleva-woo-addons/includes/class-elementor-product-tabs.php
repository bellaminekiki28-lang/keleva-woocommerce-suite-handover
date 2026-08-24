<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Tabs extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-tabs'; }
    public function get_title(): string { return __('Keleva Product Tabs', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-tabs'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'product', 'description', 'attributes', 'shipping', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Contenu produit', 'keleva-woo-addons')]);
        $this->add_control('product_id', ['label' => __('ID produit hors template', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'default' => 0]);
        $this->add_control('show_description', ['label' => __('Afficher la description', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_attributes', ['label' => __('Afficher les informations complémentaires', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('shipping_title', ['label' => __('Titre livraison', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Livraison', 'keleva-woo-addons')]);
        $this->add_control('shipping_copy', ['label' => __('Texte livraison', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Les options, délais et tarifs sont calculés au checkout selon votre adresse et les règles WooCommerce actives.', 'keleva-woo-addons')]);
        $this->end_controls_section();
    }

    protected function render(): void {
        global $product;
        $settings = $this->get_settings_for_display();
        $context_product = $product instanceof WC_Product ? $product : wc_get_product((int) ($settings['product_id'] ?? 0));
        if (!$context_product instanceof WC_Product) {
            echo '<p class="keleva-widget-empty" role="status">' . esc_html__('À placer dans un template produit.', 'keleva-woo-addons') . '</p>';
            return;
        }
        $description = trim((string) $context_product->get_description());
        $has_attributes = (bool) $context_product->has_attributes();
        if ('yes' !== ($settings['show_description'] ?? 'yes') && 'yes' !== ($settings['show_attributes'] ?? 'yes') && empty($settings['shipping_copy'])) {
            echo '<p class="keleva-widget-empty" role="status">' . esc_html__('Aucun panneau produit n’est activé.', 'keleva-woo-addons') . '</p>';
            return;
        }

        echo '<section class="keleva-product-tabs" aria-label="' . esc_attr__('Informations produit', 'keleva-woo-addons') . '">';
        if ('yes' === ($settings['show_description'] ?? 'yes') && $description) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- the_content is the documented core formatter for stored product content.
            echo '<details class="keleva-product-tabs__panel" open><summary>' . esc_html__('Description', 'keleva-woo-addons') . '</summary><div class="keleva-product-tabs__content">' . wp_kses_post(apply_filters('the_content', $description)) . '</div></details>';
        }
        if ('yes' === ($settings['show_attributes'] ?? 'yes') && $has_attributes) {
            ob_start();
            wc_display_product_attributes($context_product);
            $attributes = (string) ob_get_clean();
            if ($attributes) echo '<details class="keleva-product-tabs__panel"><summary>' . esc_html__('Informations complémentaires', 'keleva-woo-addons') . '</summary><div class="keleva-product-tabs__content">' . $attributes . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        if (!empty($settings['shipping_copy'])) {
            echo '<details class="keleva-product-tabs__panel"><summary>' . esc_html($settings['shipping_title'] ?: __('Livraison', 'keleva-woo-addons')) . '</summary><div class="keleva-product-tabs__content"><p>' . esc_html($settings['shipping_copy']) . '</p></div></details>';
        }
        echo '</section>';
    }
}
