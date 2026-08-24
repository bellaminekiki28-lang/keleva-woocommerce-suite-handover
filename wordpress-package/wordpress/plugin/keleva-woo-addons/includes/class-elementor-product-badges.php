<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Badges extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-badges'; }
    public function get_title(): string { return __('Keleva Product Badges', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-info-circle-o'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'product', 'badge', 'sale', 'stock', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Badges produit', 'keleva-woo-addons')]);
        $this->add_control('product_id', ['label' => __('ID produit', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'min' => 0]);
        $this->add_control('custom_label', ['label' => __('Label administrable', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('show_sale', ['label' => __('Afficher promotion', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_new', ['label' => __('Afficher nouveauté (30 jours)', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('low_stock_threshold', ['label' => __('Seuil stock bas', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 100]);
        $this->end_controls_section();
    }

    public static function get_labels(WC_Product $product, string $custom_label = '', bool $show_sale = true, bool $show_new = true, int $low_stock_threshold = 3): array {
        $labels = [];
        if ($custom_label !== '') $labels[] = $custom_label;
        if ($show_sale && $product->is_on_sale()) $labels[] = __('Promotion', 'keleva-woo-addons');
        $created_at = $product->get_date_created();
        if ($show_new && $created_at && $created_at->getTimestamp() >= (time() - (30 * DAY_IN_SECONDS))) $labels[] = __('Nouveau', 'keleva-woo-addons');
        $quantity = $product->get_stock_quantity();
        if ($product->managing_stock() && null !== $quantity && $quantity > 0 && $quantity <= max(1, $low_stock_threshold)) $labels[] = __('Stock bas', 'keleva-woo-addons');
        return array_values(array_unique($labels));
    }

    public static function render_product_badges(WC_Product $product, string $custom_label = '', bool $show_sale = true, bool $show_new = true, int $low_stock_threshold = 3): string {
        $labels = self::get_labels($product, $custom_label, $show_sale, $show_new, $low_stock_threshold);
        if (!$labels) return '';
        $output = '<ul class="keleva-product-badges" aria-label="' . esc_attr__('Informations produit', 'keleva-woo-addons') . '">';
        foreach ($labels as $label) $output .= '<li>' . esc_html($label) . '</li>';
        return $output . '</ul>';
    }

    protected function render(): void {
        global $product;
        $settings = $this->get_settings_for_display();
        $badge_product = $product instanceof WC_Product ? $product : wc_get_product((int) $settings['product_id']);
        if (!$badge_product instanceof WC_Product) {
            echo '<p class="keleva-widget-empty">' . esc_html__('À placer dans un template produit ou configurez un produit.', 'keleva-woo-addons') . '</p>';
            return;
        }

        echo self::render_product_badges($badge_product, (string) ($settings['custom_label'] ?? ''), 'yes' === ($settings['show_sale'] ?? 'yes'), 'yes' === ($settings['show_new'] ?? 'yes'), (int) ($settings['low_stock_threshold'] ?? 3)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
