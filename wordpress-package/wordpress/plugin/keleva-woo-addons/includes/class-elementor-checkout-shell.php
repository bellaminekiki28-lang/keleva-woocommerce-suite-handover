<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Checkout_Shell extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-checkout-shell'; }
    public function get_title(): string { return __('Keleva Checkout Shell', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-checkout'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'checkout', 'payment', 'shipping', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Checkout', 'keleva-woo-addons')]);
        $this->add_control('eyebrow', ['label' => __('Surtitre', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Finaliser simplement', 'keleva-woo-addons')]);
        $this->add_control('title', ['label' => __('Titre', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Votre commande, sans compte obligatoire.', 'keleva-woo-addons')]);
        $this->add_control('description', ['label' => __('Description', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Vos coordonnées servent uniquement à préparer la livraison. Les modes de paiement et de livraison proviennent de WooCommerce.', 'keleva-woo-addons')]);
        $this->add_control('render_checkout', ['label' => __('Rendre le checkout classique', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes']);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $title_id = wp_unique_id('keleva-checkout-shell-title-');
        echo '<section class="keleva-checkout-shell" aria-labelledby="' . esc_attr($title_id) . '">';
        if (!empty($settings['eyebrow'])) echo '<p class="keleva-checkout-shell__eyebrow">' . esc_html($settings['eyebrow']) . '</p>';
        if (!empty($settings['title'])) echo '<h2 id="' . esc_attr($title_id) . '">' . esc_html($settings['title']) . '</h2>';
        if (!empty($settings['description'])) echo '<p class="keleva-checkout-shell__description">' . esc_html($settings['description']) . '</p>';

        if ('yes' !== ($settings['render_checkout'] ?? 'yes')) {
            echo '<p class="keleva-widget-empty" role="status">' . esc_html__('Le shell est rendu sans formulaire WooCommerce par ce réglage.', 'keleva-woo-addons') . '</p>';
        } elseif (!function_exists('WC')) {
            echo '<p class="keleva-widget-empty" role="status">' . esc_html__('WooCommerce doit être actif pour afficher le checkout.', 'keleva-woo-addons') . '</p>';
        } else {
            // Shortcode WooCommerce classique : utilisable sans JavaScript et compatible avec ses passerelles.
            echo do_shortcode('[woocommerce_checkout]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</section>';
    }
}
