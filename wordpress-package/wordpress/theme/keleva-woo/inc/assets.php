<?php
defined('ABSPATH') || exit;

function keleva_woo_asset_version(string $relative_path): string {
    $path = get_template_directory() . $relative_path;
    return file_exists($path) ? (string) filemtime($path) : wp_get_theme()->get('Version');
}

function keleva_woo_font_stylesheet_url(): string {
    return 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap';
}

function keleva_woo_is_storefront_context(): bool {
    if (!function_exists('is_woocommerce')) {
        return is_front_page();
    }

    return is_front_page() || is_woocommerce() || is_cart() || is_checkout();
}

/** Réduit le délai de chargement des familles éditoriales sans bloquer le rendu. */
add_filter('wp_resource_hints', static function (array $urls, string $relation_type): array {
    if (is_admin() || !keleva_woo_is_storefront_context() || 'preconnect' !== $relation_type) {
        return $urls;
    }
    $urls[] = 'https://fonts.googleapis.com';
    $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
    return $urls;
}, 10, 2);

add_filter('wp_preload_resources', static function (array $preloads): array {
    if (is_admin() || !keleva_woo_is_storefront_context()) {
        return $preloads;
    }
    $preloads[] = [
        'href' => keleva_woo_font_stylesheet_url(),
        'as' => 'style',
        'type' => 'text/css',
    ];
    return $preloads;
});

add_action('wp_head', static function (): void {
    if (is_admin() || !keleva_woo_is_storefront_context()) {
        return;
    }

    echo '<style id="keleva-critical-layout">body{margin:0}.keleva-header{position:sticky;top:0;z-index:30;min-height:75px}.keleva-header__inner{width:min(1440px,calc(100% - 64px));min-height:74px;margin:0 auto;display:flex;align-items:center}.keleva-main{width:min(1440px,calc(100% - 64px));margin:0 auto}.elementor .e-con.e-con-boxed{padding:0 10px}.elementor .e-con.e-con-boxed>.e-con-inner{width:100%;max-width:1140px;margin:0 auto;padding:10px 0;gap:20px}.keleva-catalog__header h1{margin:0}.keleva-product-gallery{min-height:min(76vw,640px)}@media(max-width:760px){.keleva-header{min-height:67px}.keleva-header__inner,.keleva-main{width:calc(100% - 32px);min-height:66px}}</style>' . "\n";
}, 0);

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('keleva-woo-fonts', keleva_woo_font_stylesheet_url(), [], keleva_woo_asset_version('/style.css'));
    wp_enqueue_style('keleva-woo', get_stylesheet_uri(), [], keleva_woo_asset_version('/style.css'));
    wp_add_inline_style('keleva-woo', keleva_woo_palette_css());
    wp_enqueue_style('keleva-woo-velora-parity', get_template_directory_uri() . '/assets/css/velora-parity.css', ['keleva-woo'], keleva_woo_asset_version('/assets/css/velora-parity.css'));
    wp_enqueue_style('keleva-woo-states', get_template_directory_uri() . '/assets/css/velora-states.css', ['keleva-woo-velora-parity'], keleva_woo_asset_version('/assets/css/velora-states.css'));
    if (is_rtl()) {
        wp_enqueue_style('keleva-woo-rtl', get_template_directory_uri() . '/assets/css/rtl.css', ['keleva-woo-states'], keleva_woo_asset_version('/assets/css/rtl.css'));
    }
    wp_add_inline_style('keleva-woo-velora-parity', '.velora-intent>span,.velora-result-line,.keleva-product-card__meta,[data-keleva-cart-message],.velora-category-list a small,.site-footer__note,.velora-benefits article p{color:var(--muted)}.velora-category-list a{color:var(--ink)}');

    if (!keleva_woo_is_storefront_context()) {
        return;
    }

    wp_enqueue_script(
        'keleva-storefront',
        get_template_directory_uri() . '/assets/js/storefront.js',
        [],
        keleva_woo_asset_version('/assets/js/storefront.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );
    wp_enqueue_script(
        'keleva-accessibility',
        get_template_directory_uri() . '/assets/js/accessibility.js',
        [],
        keleva_woo_asset_version('/assets/js/accessibility.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );

    wp_add_inline_script(
        'keleva-storefront',
        'window.KelevaStorefront = ' . wp_json_encode([
            'quickViewRoot' => esc_url_raw(rest_url('keleva/v1/')),
            'crossSellsRoot' => esc_url_raw(rest_url('keleva/v1/cart/cross-sells')),
            'storeApiRoot' => esc_url_raw(rest_url('wc/store/v1/')),
            'productsRoot' => esc_url_raw(rest_url('wc/store/v1/products')),
            'cartUrl' => esc_url_raw(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')),
            'checkoutUrl' => esc_url_raw(function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/')),
            'i18n' => [
                'loading' => __('Chargement du produit…', 'keleva-woo'),
                'error' => __('Ce produit est indisponible pour le moment.', 'keleva-woo'),
                'added' => __('Ajouté au panier.', 'keleva-woo'),
            ],
        ]) . ';',
        'before'
    );
});

add_action('wp_enqueue_scripts', static function (): void {
    if (is_admin() || !keleva_woo_is_storefront_context()) {
        return;
    }

    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
        return;
    }

    foreach (['wc-jquery-blockui', 'wc-add-to-cart', 'wc-cart-fragments', 'jquery-migrate', 'jquery-core', 'jquery'] as $handle) {
        wp_dequeue_script($handle);
    }
    wp_deregister_script('jquery');

    foreach (['woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'wc-blocks-style', 'wc-blocks-vendors-style', 'wp-block-library', 'wp-block-library-theme', 'global-styles'] as $handle) {
        wp_dequeue_style($handle);
    }

    foreach (['wc-order-attribution', 'wc-blocks-middleware', 'wc-blocks-checkout', 'wc-blocks-cart', 'sourcebuster-js'] as $handle) {
        wp_dequeue_script($handle);
    }
}, 999);

add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    $description = '';
    if (is_front_page()) {
        $description = __('Velora : une boutique pensée pour parcourir moins, décider mieux et garder son panier dans le champ du premier regard au dernier clic.', 'keleva-woo');
    } elseif (function_exists('is_shop') && is_shop()) {
        $description = __('Découvrez la sélection Velora : objets utiles, images soignées, quick view et panier sans rupture de parcours.', 'keleva-woo');
    } elseif (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_the_ID());
        if ($product) {
            $description = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());
        }
    }

    if ($description) {
        echo '<meta name="description" content="' . esc_attr(wp_trim_words($description, 32, '')) . '">' . "\n";
    }

    if (is_front_page() || (function_exists('is_shop') && is_shop())) {
        echo '<link rel="canonical" href="' . esc_url(is_front_page() ? home_url('/') : wc_get_page_permalink('shop')) . '">' . "\n";
    }

    if (is_front_page()) {
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => home_url('/#website'),
                    'url' => home_url('/'),
                    'name' => 'Velora',
                    'inLanguage' => get_bloginfo('language'),
                ],
                [
                    '@type' => 'Organization',
                    '@id' => home_url('/#organization'),
                    'name' => 'Velora',
                    'url' => home_url('/'),
                ],
            ],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}, 1);

add_filter('pre_get_document_title', static function (string $title): string {
    if (is_front_page()) {
        return __('Velora — Choisissez vite. Gardez le contrôle.', 'keleva-woo');
    }
    if (function_exists('is_cart') && is_cart()) {
        return __('Velora — Votre sélection', 'keleva-woo');
    }
    if (function_exists('is_checkout') && is_checkout()) {
        return __('Velora — Finaliser simplement', 'keleva-woo');
    }
    return $title;
}, 20);
