<?php
declare(strict_types=1);
$siteRoot = getenv('KELEVA_SITE_ROOT') ?: dirname(__DIR__) . '/site';
require $siteRoot . '/wp-load.php';

$previous = get_theme_mod('keleva_palette', 'velora');
foreach (array_keys(keleva_woo_palettes()) as $paletteId) {
    set_theme_mod('keleva_palette', $paletteId);
    $palette = keleva_woo_active_palette();
    $styles = apply_filters('woocommerce_email_styles', '');
    foreach ([$palette['bg'], $palette['ink'], $palette['accent'], $palette['on_accent'], $palette['muted'], $palette['line']] as $token) {
        if (!str_contains($styles, $token)) throw new RuntimeException("Token email absent pour {$paletteId}: {$token}");
    }
    echo "Email {$paletteId}: styles palette injectés\n";
}
set_theme_mod('keleva_palette', $previous);
echo "Palette email restaurée : {$previous}\n";
