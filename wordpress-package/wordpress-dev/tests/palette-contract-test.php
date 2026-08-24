<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: (defined('ABSPATH') ? untrailingslashit(ABSPATH) : dirname(__DIR__) . '/site');
require $siteRoot . '/wp-load.php';

function keleva_test_luminance(string $hex): float {
    $hex = ltrim($hex, '#');
    $channels = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $linear = array_map(static function (int $value): float {
        $value /= 255;
        return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, $channels);
    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

function keleva_test_ratio(string $first, string $second): float {
    $a = keleva_test_luminance($first);
    $b = keleva_test_luminance($second);
    return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
}

$palettes = keleva_woo_palettes();
$requiredTokens = ['bg', 'surface', 'surface_card', 'surface_media', 'ink', 'muted', 'subtle', 'line', 'accent', 'accent_strong', 'accent_deep', 'on_accent', 'on_ink', 'success', 'success_wash', 'warning', 'warning_wash', 'danger', 'danger_wash', 'media', 'benefit', 'shadow_tint'];
foreach ($palettes as $id => $palette) {
    foreach ($requiredTokens as $token) {
        if (empty($palette[$token]) || !preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $palette[$token])) {
            throw new RuntimeException(sprintf('Token palette manquant ou invalide pour %s : %s.', $id, $token));
        }
    }
    $contentRatio = keleva_test_ratio($palette['ink'], $palette['bg']);
    $ctaRatio = keleva_test_ratio($palette['on_accent'], $palette['accent']);
    if ($contentRatio < 4.5 || $ctaRatio < 4.5) {
        throw new RuntimeException(sprintf('Contraste insuffisant pour %s : contenu %.2f / CTA %.2f.', $id, $contentRatio, $ctaRatio));
    }
    set_theme_mod('keleva_palette', $id);
    $css = keleva_woo_palette_css();
    if (!str_contains($css, '--accent:' . $palette['accent']) || !str_contains($css, '--bg:' . $palette['bg'])) {
        throw new RuntimeException('Variables storefront absentes pour ' . $id);
    }
    $email = apply_filters('woocommerce_email_styles', '');
    if (!str_contains($email, $palette['accent']) || !str_contains($email, $palette['bg'])) {
        throw new RuntimeException('Variables email absentes pour ' . $id);
    }
    printf("Palette %s : contenu %.2f, CTA %.2f — valide\n", $id, $contentRatio, $ctaRatio);
}

set_theme_mod('keleva_palette', 'velora');
echo "Palette de laboratoire restaurée : velora\n";
