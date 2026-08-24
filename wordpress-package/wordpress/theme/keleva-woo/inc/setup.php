<?php
defined('ABSPATH') || exit;

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('keleva-woo', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 640,
        'single_image_width' => 1200,
        'product_grid' => [
            'default_rows' => 3,
            'min_rows' => 2,
            'max_rows' => 6,
            'default_columns' => 3,
            'min_columns' => 2,
            'max_columns' => 4,
        ],
    ]);
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
	register_nav_menus(['primary' => __('Navigation principale', 'keleva-woo')]);
	add_image_size('keleva-card', 640, 720, true);
	add_image_size('keleva-quick-view', 900, 1080, true);
});

add_action('after_switch_theme', static function (): void {
	flush_rewrite_rules(false);
});

add_filter('wp_get_attachment_image_attributes', static function (array $attributes): array {
    if (isset($attributes['loading']) && 'eager' !== $attributes['loading']) {
        $attributes['decoding'] = 'async';
    }

    return $attributes;
});

add_filter('woocommerce_product_tabs', static function (array $tabs): array {
    unset($tabs['reviews']);
    return $tabs;
}, 98);

add_filter('comments_open', static function (bool $open, int $post_id): bool {
    return 'product' === get_post_type($post_id) ? false : $open;
}, 10, 2);

/**
 * La préproduction Velora expose uniquement son catalogue d’objets. Les produits
 * restaurant restent administrativement disponibles pour les essais métier mais
 * sortent de toutes les boucles publiques WooCommerce.
 */
add_action('pre_get_posts', static function (WP_Query $query): void {
    if (is_admin() || !$query->is_main_query() || !($query->is_post_type_archive('product') || $query->is_tax('product_cat') || $query->is_search())) {
        return;
    }

    $tax_query = (array) $query->get('tax_query');
    $tax_query[] = [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => ['restauration-keleva'],
        'operator' => 'NOT IN',
    ];
    $query->set('tax_query', $tax_query);
});

add_action('woocommerce_product_query', static function (WP_Query $query): void {
    if (is_admin()) {
        return;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Les filtres GET sont non mutatifs et doivent rester partageables/crawlables.
    $category = sanitize_title(wp_unslash($_GET['keleva_category'] ?? ''));
    $stock = sanitize_key(wp_unslash($_GET['keleva_stock'] ?? ''));
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
    if ($category && get_term_by('slug', $category, 'product_cat')) {
        $tax_query = (array) $query->get('tax_query');
        $tax_query[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$category]];
        $query->set('tax_query', $tax_query);
    }
    if ('instock' === $stock) {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = ['key' => '_stock_status', 'value' => 'instock'];
        $query->set('meta_query', $meta_query);
    }
});
