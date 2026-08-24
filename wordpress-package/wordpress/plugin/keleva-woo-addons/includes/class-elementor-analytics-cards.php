<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Analytics_Cards extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-analytics-cards'; }
    public function get_title(): string { return __('Keleva Analytics Cards', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-dashboard'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['analytics', 'orders', 'revenue', 'merchant', 'keleva']; }
    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Indicateurs', 'keleva-woo-addons')]);
        $this->add_control('days', ['label' => __('Période (jours)', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 30, 'min' => 1, 'max' => 90]);
        $this->end_controls_section();
    }
    protected function render(): void {
        if (!current_user_can('manage_woocommerce')) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this merchant administration capability.
            echo '<p class="keleva-widget-empty" role="status">' . esc_html__('Les indicateurs marchand sont réservés aux comptes autorisés.', 'keleva-woo-addons') . '</p>';
            return;
        }
        $days = max(1, min(90, (int) ($this->get_settings_for_display()['days'] ?? 30)));
        $orders = wc_get_orders(['limit' => 200, 'return' => 'objects', 'date_created' => '>=' . gmdate('Y-m-d', time() - ($days * DAY_IN_SECONDS)), 'status' => array_keys(wc_get_order_statuses())]);
        $revenue = array_reduce($orders, static fn (float $total, WC_Order $order): float => $total + (float) $order->get_total(), 0.0);
        /* translators: %d: number of calendar days included in the merchant data source. */
        echo '<section class="keleva-analytics-cards" aria-label="' . esc_attr__('Indicateurs marchand', 'keleva-woo-addons') . '"><p>' . esc_html(sprintf(__('Source : commandes WooCommerce des %d derniers jours, plafonnées à 200 lignes.', 'keleva-woo-addons'), $days)) . '</p><div><article><span>' . esc_html__('Commandes', 'keleva-woo-addons') . '</span><strong>' . esc_html((string) count($orders)) . '</strong></article><article><span>' . esc_html__('Chiffre d’affaires', 'keleva-woo-addons') . '</span><strong>' . wp_kses_post(wc_price($revenue)) . '</strong></article></div></section>';
    }
}
