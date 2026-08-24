<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Grid extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-grid'; }
    public function get_title(): string { return __('Keleva Product Grid', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-products'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'product', 'keleva', 'catalogue', 'quick view']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Catalogue', 'keleva-woo-addons')]);
        $this->add_control('limit', [
            'label' => __('Nombre de produits', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min' => 1,
            'max' => 24,
        ]);
        $this->add_control('category', [
            'label' => __('Catégorie', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'multiple' => false,
            'options' => $this->get_category_options(),
        ]);
        $this->add_control('order_by', [
            'label' => __('Trier par', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date' => __('Nouveauté', 'keleva-woo-addons'),
                'price' => __('Prix', 'keleva-woo-addons'),
                'title' => __('Titre', 'keleva-woo-addons'),
                'menu_order' => __('Ordre boutique', 'keleva-woo-addons'),
            ],
        ]);
        $this->add_control('order', [
            'label' => __('Sens', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'DESC',
            'options' => ['DESC' => __('Décroissant', 'keleva-woo-addons'), 'ASC' => __('Croissant', 'keleva-woo-addons')],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('layout', ['label' => __('Mise en page', 'keleva-woo-addons')]);
        $this->add_responsive_control('columns', [
            'label' => __('Colonnes', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'tablet_default' => '2',
            'mobile_default' => '2',
            'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
            'selectors' => [
                '{{WRAPPER}} .keleva-product-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
            ],
        ]);
        $this->end_controls_section();
    }

    private function get_category_options(): array {
        $options = ['' => __('Toutes les catégories', 'keleva-woo-addons')];
        foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) as $term) {
            $options[(string) $term->term_id] = $term->name;
        }
        return $options;
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $args = [
            'status' => 'publish',
            'limit' => max(1, min(24, (int) $settings['limit'])),
            'orderby' => in_array($settings['order_by'], ['date', 'price', 'title', 'menu_order'], true) ? $settings['order_by'] : 'date',
            'order' => 'ASC' === $settings['order'] ? 'ASC' : 'DESC',
            'return' => 'objects',
        ];
        if (!empty($settings['category'])) {
            $args['category'] = [(int) $settings['category']];
        }

        $products = wc_get_products($args);
        if (!$products) {
            echo '<p>' . esc_html__('Aucun produit à afficher.', 'keleva-woo-addons') . '</p>';
            return;
        }

        echo '<ul class="keleva-product-grid">';
        foreach ($products as $keleva_product) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce attend explicitement le global product lors du rendu d’un template.
            $GLOBALS['product'] = $keleva_product;
            wc_get_template_part('content', 'product');
        }
        wp_reset_postdata();
        echo '</ul>';
    }
}
