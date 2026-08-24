<?php
defined('ABSPATH') || exit;

/**
 * Palettes centralisées : storefront, console embarquée, composants WooCommerce et emails.
 * Les clés sont volontairement stables afin que les intégrations puissent les consommer.
 */
function keleva_woo_palettes(): array {
    return [
        'velora' => [
            'label' => __('Velora Corail', 'keleva-woo'),
            'bg' => '#F7F4EE', 'surface' => '#FFFDF8', 'surface_card' => '#FFFDF8', 'surface_media' => '#E8DED0',
            'ink' => '#1E1C19', 'muted' => '#68645D', 'subtle' => '#655F58', 'line' => '#D9D1C5',
            'accent' => '#A83B2B', 'accent_strong' => '#872E22', 'accent_deep' => '#70251B', 'on_accent' => '#FFFFFF', 'on_ink' => '#FFFFFF',
            'success' => '#176B4D', 'success_wash' => '#E5F1EB', 'warning' => '#805800', 'warning_wash' => '#FFF3D6',
            'danger' => '#9B2F2F', 'danger_wash' => '#FCE8E8', 'media' => '#E8DED0', 'benefit' => '#E9E1D4',
            'shadow_tint' => '#1E1C19',
        ],
        'onyx-gold' => [
            'label' => __('Onyx Doré', 'keleva-woo'),
            'bg' => '#0A0A0B', 'surface' => '#131315', 'surface_card' => '#1C1C1F', 'surface_media' => '#242329',
            'ink' => '#F7F1E6', 'muted' => '#C8C1B5', 'subtle' => '#A69E91', 'line' => '#454149',
            'accent' => '#D3A33E', 'accent_strong' => '#E6BB62', 'accent_deep' => '#F0CB7A', 'on_accent' => '#17130A', 'on_ink' => '#F7F1E6',
            'success' => '#79C69B', 'success_wash' => '#163325', 'warning' => '#F0BE58', 'warning_wash' => '#3A2B0D',
            'danger' => '#FF8F86', 'danger_wash' => '#3B1718', 'media' => '#242329', 'benefit' => '#1B1B1E',
            'shadow_tint' => '#000000',
        ],
        'sienne' => [
            'label' => __('Sienne Atelier', 'keleva-woo'),
            'bg' => '#FAF3EA', 'surface' => '#FFFDF9', 'surface_card' => '#FFFDF9', 'surface_media' => '#E9D1B7',
            'ink' => '#33231D', 'muted' => '#6C5B51', 'subtle' => '#6A574D', 'line' => '#DECABD',
            'accent' => '#98402B', 'accent_strong' => '#762E21', 'accent_deep' => '#602319', 'on_accent' => '#FFFFFF', 'on_ink' => '#FFFFFF',
            'success' => '#37654C', 'success_wash' => '#E5F0E9', 'warning' => '#815600', 'warning_wash' => '#FFF2D4',
            'danger' => '#9B3030', 'danger_wash' => '#FCE8E5', 'media' => '#E9D1B7', 'benefit' => '#EEDCC8',
            'shadow_tint' => '#33231D',
        ],
        'sauge' => [
            'label' => __('Sauge Minérale', 'keleva-woo'),
            'bg' => '#F0F3ED', 'surface' => '#FCFDF9', 'surface_card' => '#FCFDF9', 'surface_media' => '#D9E2D5',
            'ink' => '#1E3028', 'muted' => '#5E6D65', 'subtle' => '#54665C', 'line' => '#CCD8CE',
            'accent' => '#2B604D', 'accent_strong' => '#20483A', 'accent_deep' => '#18392E', 'on_accent' => '#FFFFFF', 'on_ink' => '#FFFFFF',
            'success' => '#236047', 'success_wash' => '#DDEFE5', 'warning' => '#765300', 'warning_wash' => '#FFF2CF',
            'danger' => '#973333', 'danger_wash' => '#FBE6E5', 'media' => '#D9E2D5', 'benefit' => '#DFE7DD',
            'shadow_tint' => '#1E3028',
        ],
        'azur' => [
            'label' => __('Azur Profond', 'keleva-woo'),
            'bg' => '#F2F6FB', 'surface' => '#FEFEFF', 'surface_card' => '#FEFEFF', 'surface_media' => '#D9E6F0',
            'ink' => '#13283D', 'muted' => '#53677C', 'subtle' => '#52677B', 'line' => '#C9D7E5',
            'accent' => '#1B5D88', 'accent_strong' => '#15496C', 'accent_deep' => '#103B58', 'on_accent' => '#FFFFFF', 'on_ink' => '#FFFFFF',
            'success' => '#23735A', 'success_wash' => '#DDF1E8', 'warning' => '#755200', 'warning_wash' => '#FFF1CF',
            'danger' => '#963636', 'danger_wash' => '#FBE6E6', 'media' => '#D9E6F0', 'benefit' => '#E0EAF4',
            'shadow_tint' => '#13283D',
        ],
    ];
}

function keleva_woo_active_palette_id(): string {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only palette preview selector; it never mutates state.
    $preview_id = isset($_GET['keleva_palette_preview']) ? sanitize_key(wp_unslash((string) $_GET['keleva_palette_preview'])) : '';
    $palette_id = '' !== $preview_id ? $preview_id : sanitize_key((string) get_theme_mod('keleva_palette', 'velora'));
    return array_key_exists($palette_id, keleva_woo_palettes()) ? $palette_id : 'velora';
}

function keleva_woo_active_palette(): array {
    $palettes = keleva_woo_palettes();
    return $palettes[keleva_woo_active_palette_id()];
}

function keleva_woo_palette_css(): string {
    $palette = keleva_woo_active_palette();
    $variables = [
        '--bg' => $palette['bg'], '--surface' => $palette['surface'], '--surface-card' => $palette['surface_card'], '--surface-media' => $palette['surface_media'],
        '--ink' => $palette['ink'], '--muted' => $palette['muted'], '--subtle' => $palette['subtle'], '--line' => $palette['line'],
        '--accent' => $palette['accent'], '--accent-strong' => $palette['accent_strong'], '--accent-deep' => $palette['accent_deep'], '--on-accent' => $palette['on_accent'], '--on-ink' => $palette['on_ink'],
        '--success' => $palette['success'], '--success-wash' => $palette['success_wash'], '--warning' => $palette['warning'], '--warning-wash' => $palette['warning_wash'],
        '--danger' => $palette['danger'], '--danger-wash' => $palette['danger_wash'], '--media' => $palette['media'], '--benefit' => $palette['benefit'], '--shadow-tint' => $palette['shadow_tint'],
        '--ivory' => 'var(--bg)', '--paper' => 'var(--surface)', '--coral' => 'var(--accent)', '--coral-deep' => 'var(--accent-strong)', '--green' => 'var(--success)',
    ];
    $css = ':root{' . implode('', array_map(static fn(string $key, string $value): string => $key . ':' . $value . ';', array_keys($variables), $variables)) . '}';
    $css .= 'body{background:var(--bg);color:var(--ink)}::selection{background:var(--accent);color:var(--on-accent)}:focus-visible{outline:3px solid var(--accent);outline-offset:2px}.site-header,.velora-toolbar{background:color-mix(in srgb,var(--bg) 93%,transparent)}.site-brand__mark,.velora-category-list a[aria-current="page"]{background:var(--ink);color:var(--surface)}.site-cart b,.velora-primary,.keleva-quick-view__form button,.product .single_add_to_cart_button,.woocommerce-cart-form .button,.woocommerce-checkout .button,.velora-product-purchase .single_add_to_cart_button{background:var(--accent);border-color:var(--accent);color:var(--on-accent)}.velora-primary:hover,.keleva-quick-view__form button:hover,.velora-product-purchase .single_add_to_cart_button:hover{background:var(--accent-strong);border-color:var(--accent-strong);color:var(--on-accent)}.velora-hero__visual,.keleva-product-card__media,.keleva-product-gallery__image,.keleva-product-media-frame,.keleva-quick-view__image{background:var(--surface-media)}.velora-benefits{background:var(--benefit)}.keleva-product-card__badge,.keleva-product-options__group,.woocommerce-message,.woocommerce-info{background:var(--surface-card);border-color:var(--line);color:var(--ink)}.woocommerce-error{background:var(--danger-wash);border-color:var(--danger);color:var(--danger)}.woocommerce form .form-row input.input-text,.woocommerce form .form-row textarea,.woocommerce form .form-row select{background:var(--surface-card);border-color:var(--line);color:var(--ink)}.keleva-cart-drawer__footer p,.velora-cart-rail__secure{color:var(--muted)}.keleva-cart-drawer__actions .velora-primary{color:var(--on-accent);font-weight:700}.keleva-product-card__swatch{background:var(--accent)!important}';
    return $css;
}

add_filter('body_class', static function (array $classes): array {
    $classes[] = 'keleva-palette--' . keleva_woo_active_palette_id();
    return $classes;
});

add_filter('woocommerce_email_styles', static function (string $styles): string {
    $palette = keleva_woo_active_palette();
    return $styles . ' body,#body_content{background:' . esc_attr($palette['bg']) . ';color:' . esc_attr($palette['ink']) . '}#template_header{background:' . esc_attr($palette['accent']) . ';color:' . esc_attr($palette['on_accent']) . '}#template_header h1{color:' . esc_attr($palette['on_accent']) . '}#template_footer{color:' . esc_attr($palette['muted']) . '}a{color:' . esc_attr($palette['accent_deep']) . '}table td,table th{border-color:' . esc_attr($palette['line']) . '}td.note{background:' . esc_attr($palette['warning_wash']) . ';color:' . esc_attr($palette['ink']) . '}div.success{background:' . esc_attr($palette['success_wash']) . ';color:' . esc_attr($palette['ink']) . '}div.error{background:' . esc_attr($palette['danger_wash']) . ';color:' . esc_attr($palette['danger']) . '}';
});
