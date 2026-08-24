<?php
defined('ABSPATH') || exit;

const KELEVA_LAYOUT_POST_TYPE = 'keleva_layout';

function keleva_woo_layout_locations(): array {
    return [
        'shop_archive' => __('Archive boutique', 'keleva-woo'),
        'single_product' => __('Fiche produit', 'keleva-woo'),
        'cart' => __('Panier', 'keleva-woo'),
        'checkout' => __('Checkout', 'keleva-woo'),
    ];
}

add_action('init', static function (): void {
    register_post_type(KELEVA_LAYOUT_POST_TYPE, [
        'labels' => [
            'name' => __('Keleva Layouts', 'keleva-woo'),
            'singular_name' => __('Keleva Layout', 'keleva-woo'),
            'add_new_item' => __('Créer un layout Keleva', 'keleva-woo'),
            'edit_item' => __('Modifier le layout Keleva', 'keleva-woo'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'themes.php',
        'supports' => ['title', 'elementor'],
        'capability_type' => 'page',
        'map_meta_cap' => true,
    ]);
});

add_action('add_meta_boxes', static function (): void {
    add_meta_box('keleva-layout-location', __('Emplacement Woo Builder', 'keleva-woo'), static function (WP_Post $post): void {
        wp_nonce_field('keleva_layout_location', 'keleva_layout_location_nonce');
        $value = (string) get_post_meta($post->ID, '_keleva_layout_location', true);
        echo '<select name="keleva_layout_location" class="widefat">';
        foreach (keleva_woo_layout_locations() as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select><p class="description">' . esc_html__('Ce layout remplace le fallback Keleva pour cet emplacement. Il est éditable avec Elementor.', 'keleva-woo') . '</p>';
    }, KELEVA_LAYOUT_POST_TYPE, 'side');
});

add_action('save_post_' . KELEVA_LAYOUT_POST_TYPE, static function (int $post_id): void {
    if (!isset($_POST['keleva_layout_location_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['keleva_layout_location_nonce'])), 'keleva_layout_location')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $location = sanitize_key(wp_unslash($_POST['keleva_layout_location'] ?? ''));
    if (array_key_exists($location, keleva_woo_layout_locations())) {
        update_post_meta($post_id, '_keleva_layout_location', $location);
    }
});

function keleva_woo_layout_id(string $location): int {
    $layouts = get_posts([
        'post_type' => KELEVA_LAYOUT_POST_TYPE,
        'post_status' => 'publish',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Meta key is the indexed layout-location discriminator.
        'meta_key' => '_keleva_layout_location',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Exact lookup is required to select one published layout.
        'meta_value' => $location,
        'posts_per_page' => 1,
        'orderby' => 'modified',
        'order' => 'DESC',
        'fields' => 'ids',
    ]);
    return $layouts ? (int) $layouts[0] : 0;
}

function keleva_woo_render_layout(string $location): bool {
    if (is_admin() || !did_action('elementor/loaded') || is_singular(KELEVA_LAYOUT_POST_TYPE)) {
        return false;
    }
    $layout_id = keleva_woo_layout_id($location);
    if (!$layout_id) {
        return false;
    }
    $content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($layout_id, true);
    if (!$content) {
        return false;
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor rend un template publié par un utilisateur autorisé ; wp_kses_post retire notamment les formulaires et leurs attributs requis par les widgets commerce.
    echo '<div class="keleva-elementor-layout keleva-elementor-layout--' . esc_attr($location) . '">' . $content . '</div>';
    return true;
}

function keleva_woo_layout_contains_widget(string $location, string $widget_type): bool {
    $layout_id = keleva_woo_layout_id($location);
    $data = $layout_id ? get_post_meta($layout_id, '_elementor_data', true) : '';
    return is_string($data) && str_contains(wp_unslash($data), $widget_type);
}

function keleva_woo_archive_shortcode(): string {
    if (!woocommerce_product_loop()) {
        return '<p>' . esc_html__('Aucun produit à afficher.', 'keleva-woo') . '</p>';
    }
    ob_start();
    $catalog_categories = [
        ['label' => __('Nouveautés', 'keleva-woo'), 'slug' => 'nouveautes', 'count' => '03'],
        ['label' => __('Maison', 'keleva-woo'), 'slug' => 'maison', 'count' => '03'],
        ['label' => __('Équipement', 'keleva-woo'), 'slug' => 'equipement', 'count' => '03'],
        ['label' => __('Coffrets', 'keleva-woo'), 'slug' => 'coffrets', 'count' => '02'],
    ];
    $shop_url = wc_get_page_permalink('shop');
    echo '<section class="velora-shop velora-elementor-archive"><div class="velora-shop__catalog">';
    if (!class_exists('Keleva_Elementor_Product_Archive_Header') || !keleva_woo_layout_contains_widget('shop_archive', 'keleva-product-archive-header')) {
        echo '<header class="velora-section-heading velora-listing__heading"><div><p class="velora-eyebrow">' . esc_html__('Le catalogue, sans détour', 'keleva-woo') . '</p><h1>' . esc_html__('Objets qui trouvent', 'keleva-woo') . '<br><em>' . esc_html__('leur place.', 'keleva-woo') . '</em></h1></div><p>' . esc_html__('Petites séries, belles matières, aucune distraction inutile.', 'keleva-woo') . '</p></header>';
    }
    echo '<div class="velora-toolbar"><nav class="velora-category-list" aria-label="' . esc_attr__('Catégories', 'keleva-woo') . '"><a href="' . esc_url($shop_url) . '" aria-current="page">' . esc_html__('Tout', 'keleva-woo') . '<small>08</small></a>';
    foreach ($catalog_categories as $catalog_category) {
        $term = get_term_by('slug', $catalog_category['slug'], 'product_cat');
        if (!$term || is_wp_error($term)) {
            continue;
        }
        echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($catalog_category['label']) . '<small>' . esc_html($catalog_category['count']) . '</small></a>';
    }
    /* translators: %d: number of products in the selection. */
    echo '</nav><span class="velora-sort-link">' . esc_html__('Trier : pertinence', 'keleva-woo') . ' <b aria-hidden="true">⌄</b></span></div><p class="velora-result-line">' . esc_html(sprintf(_n('%d pièce dans la sélection', '%d pièces dans la sélection', (int) $GLOBALS['wp_query']->post_count, 'keleva-woo'), (int) $GLOBALS['wp_query']->post_count)) . '</p><ul class="keleva-product-grid velora-product-grid">';
    while (have_posts()) {
        the_post();
        wc_get_template_part('content', 'product');
    }
    echo '</ul></div>';
    echo '<aside class="velora-cart-rail keleva-side-cart" aria-label="' . esc_attr__('Panier persistant', 'keleva-woo') . '">';
    echo '<p class="velora-eyebrow">' . esc_html__('Votre sélection', 'keleva-woo') . '</p><h2>' . esc_html__('Le panier', 'keleva-woo') . '</h2>';
    echo '<span class="velora-cart-rail__count"><b data-keleva-cart-count>0</b> ' . esc_html__('article(s)', 'keleva-woo') . '</span>';
    echo '<div class="velora-cart-rail__progress"><p data-keleva-cart-message>' . esc_html__('Votre panier est prêt à accueillir une bonne idée.', 'keleva-woo') . '</p><span><i data-velora-cart-progress></i></span></div>';
    echo '<div class="velora-cart-rail__lines" data-velora-cart-lines><div class="velora-cart-rail__empty">▢<p>' . esc_html__('Votre panier est prêt à accueillir une bonne idée.', 'keleva-woo') . '</p></div></div>';
    echo '<div class="velora-cart-rail__summary"><p><span>' . esc_html__('Sous-total', 'keleva-woo') . '</span><b data-velora-cart-subtotal>—</b></p><p><span>' . esc_html__('Livraison', 'keleva-woo') . '</span><b data-velora-cart-delivery>' . esc_html__('Calculée au checkout', 'keleva-woo') . '</b></p><p><span>' . esc_html__('Total estimé', 'keleva-woo') . '</span><b data-velora-cart-total>—</b></p></div>';
    echo '<a class="velora-primary velora-cart-rail__cta" href="' . esc_url(wc_get_cart_url()) . '">' . esc_html__('Passer au checkout', 'keleva-woo') . '<b aria-hidden="true">→</b></a>';
    echo '<p class="velora-cart-rail__secure">◈ ' . esc_html__('Checkout en une seule séquence, optimisé mobile.', 'keleva-woo') . '</p>';
    echo '</aside></section>';
    return (string) ob_get_clean();
}

add_shortcode('keleva_product_archive', 'keleva_woo_archive_shortcode');
add_shortcode('keleva_product_single', static function (): string {
    ob_start();
    while (have_posts()) {
        the_post();
        wc_get_template_part('content', 'single-product');
    }
    return (string) ob_get_clean();
});
add_shortcode('keleva_cart', static fn (): string => do_shortcode('[woocommerce_cart]'));
add_shortcode('keleva_checkout', static fn (): string => do_shortcode('[woocommerce_checkout]'));

function keleva_woo_seed_elementor_layouts(): void {
    if (!did_action('elementor/loaded') || get_option('keleva_seeded_elementor_layouts')) {
        return;
    }
    $layouts = [
        'shop_archive' => ['Keleva — Archive boutique', '[keleva_product_archive]'],
        'single_product' => ['Keleva — Fiche produit', '[keleva_product_single]'],
        'cart' => ['Keleva — Panier', '[keleva_cart]'],
        'checkout' => ['Keleva — Checkout', '[keleva_checkout]'],
    ];
    foreach ($layouts as $location => [$title, $shortcode]) {
        if (keleva_woo_layout_id($location)) {
            continue;
        }
        $id = wp_insert_post([
            'post_title' => $title,
            'post_type' => KELEVA_LAYOUT_POST_TYPE,
            'post_status' => 'publish',
        ]);
        if (!$id || is_wp_error($id)) {
            continue;
        }
        $data = [[
            'id' => 'keleva' . wp_rand(1000, 9999),
            'elType' => 'container',
            'settings' => [],
            'elements' => [[
                'id' => 'keleva' . wp_rand(1000, 9999),
                'elType' => 'widget',
                'widgetType' => 'shortcode',
                'settings' => ['shortcode' => $shortcode],
                'elements' => [],
            ]],
        ]];
        update_post_meta($id, '_keleva_layout_location', $location);
        update_post_meta($id, '_elementor_edit_mode', 'builder');
        update_post_meta($id, '_elementor_template_type', 'wp-page');
        update_post_meta($id, '_elementor_version', ELEMENTOR_VERSION);
        update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    }
    update_option('keleva_seeded_elementor_layouts', 1, false);
}
add_action('wp_loaded', 'keleva_woo_seed_elementor_layouts', 20);

/**
 * Met à niveau uniquement le layout Keleva généré automatiquement dans les
 * anciens laboratoires. Un layout modifié par un commerçant n’est jamais remplacé.
 */
function keleva_woo_upgrade_seeded_shop_layout(): void {
    if (!did_action('elementor/loaded') || (int) get_option('keleva_seeded_layout_schema', 0) >= 2) {
        return;
    }

    $layout_id = keleva_woo_layout_id('shop_archive');
    $raw_data = $layout_id ? get_post_meta($layout_id, '_elementor_data', true) : '';
    $data = is_string($raw_data) ? json_decode(wp_unslash($raw_data), true) : null;
    $seed_widget = is_array($data) && 1 === count($data) ? ($data[0]['elements'][0] ?? null) : null;

    if (!is_array($seed_widget) || 'shortcode' !== ($seed_widget['widgetType'] ?? '') || '[keleva_product_archive]' !== ($seed_widget['settings']['shortcode'] ?? '')) {
        return;
    }

    $data[0]['elements'] = [
        [
            'id' => 'keleva' . wp_rand(1000, 9999),
            'elType' => 'widget',
            'widgetType' => 'keleva-product-filters',
            'settings' => [],
            'elements' => [],
        ],
        $seed_widget,
    ];
    update_post_meta($layout_id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_option('keleva_seeded_layout_schema', 2, false);
}
add_action('wp_loaded', 'keleva_woo_upgrade_seeded_shop_layout', 21);

/**
 * Ajoute l’en-tête Elementor uniquement au layout généré par Keleva à la
 * révision précédente. Toute personnalisation manuelle reste intouchée.
 */
function keleva_woo_upgrade_seeded_shop_layout_header(): void {
    if (!did_action('elementor/loaded') || (int) get_option('keleva_seeded_layout_schema', 0) >= 3) {
        return;
    }

    $layout_id = keleva_woo_layout_id('shop_archive');
    $raw_data = $layout_id ? get_post_meta($layout_id, '_elementor_data', true) : '';
    $data = is_string($raw_data) ? json_decode(wp_unslash($raw_data), true) : null;
    $elements = is_array($data) ? ($data[0]['elements'] ?? null) : null;
    $filter_widget = is_array($elements) ? ($elements[0] ?? null) : null;
    $archive_widget = is_array($elements) ? ($elements[1] ?? null) : null;

    if (!is_array($elements) || 2 !== count($elements) || !is_array($filter_widget) || !is_array($archive_widget) || 'keleva-product-filters' !== ($filter_widget['widgetType'] ?? '') || 'shortcode' !== ($archive_widget['widgetType'] ?? '') || '[keleva_product_archive]' !== ($archive_widget['settings']['shortcode'] ?? '')) {
        return;
    }

    array_unshift($data[0]['elements'], [
        'id' => 'keleva' . wp_rand(1000, 9999),
        'elType' => 'widget',
        'widgetType' => 'keleva-product-archive-header',
        'settings' => ['show_description' => 'yes', 'show_sorting' => ''],
        'elements' => [],
    ]);
    update_post_meta($layout_id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_option('keleva_seeded_layout_schema', 3, false);
}
add_action('wp_loaded', 'keleva_woo_upgrade_seeded_shop_layout_header', 22);
