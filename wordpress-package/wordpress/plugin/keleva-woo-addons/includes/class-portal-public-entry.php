<?php
defined('ABSPATH') || exit;

/**
 * Public entry for the WordPress-native Keleva merchant portal.
 *
 * It only maps a Hostinger path to the local portal renderer. No remote URL,
 * proxy, WordPress user, wp-admin cookie, or administrator credential is used.
 */
final class Keleva_Portal_Public_Entry {
    private const QUERY_VAR = 'keleva_portal';
    private const REWRITE_VERSION = '0.6.15';

    public static function boot(): void {
        add_filter('query_vars', [self::class, 'register_query_var']);
        add_action('init', [self::class, 'register_rewrite_rule']);
        add_action('init', [self::class, 'maybe_flush_rewrite_rules'], 99);
        add_action('template_redirect', [self::class, 'serve_portal'], 0);
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public static function register_query_var(array $vars): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function register_rewrite_rule(): void {
        add_rewrite_rule(
            '^' . Keleva_Native_Merchant_Portal::PATH . '/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );

        if (!function_exists('pll_languages_list')) {
            return;
        }

        $languages = pll_languages_list(['fields' => 'slug']);
        if (!is_array($languages)) {
            return;
        }

        foreach ($languages as $language) {
            if (!is_string($language) || !preg_match('/^[a-z]{2,8}(?:-[a-z0-9]{2,8})?$/i', $language)) {
                continue;
            }

            add_rewrite_rule(
                '^' . preg_quote($language, '/') . '/' . Keleva_Native_Merchant_Portal::PATH . '/?$',
                'index.php?' . self::QUERY_VAR . '=1&lang=' . rawurlencode($language),
                'top'
            );
        }
    }

    public static function maybe_flush_rewrite_rules(): void {
        if (self::REWRITE_VERSION === get_option('keleva_native_portal_rewrite_version')) {
            return;
        }
        flush_rewrite_rules(false);
        update_option('keleva_native_portal_rewrite_version', self::REWRITE_VERSION, false);
    }

    public static function serve_portal(): void {
        $path = trim((string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        $native_path = trim((string) wp_parse_url(Keleva_Native_Merchant_Portal::url(), PHP_URL_PATH), '/');
        $language_prefix = '';
        if (function_exists('pll_current_language')) {
            $current_language = pll_current_language('slug');
            if (is_string($current_language) && preg_match('/^[a-z]{2,8}(?:-[a-z0-9]{2,8})?$/i', $current_language)) {
                $language_prefix = trim($current_language, '/') . '/';
            }
        }
        $localized_native_path = $language_prefix . $native_path;

        if ('1' === (string) wp_unslash($_GET[self::QUERY_VAR] ?? '') && !in_array($path, [$native_path, $localized_native_path], true)) {
            wp_safe_redirect(Keleva_Native_Merchant_Portal::url(), 302, 'Keleva Native Merchant Portal');
            exit;
        }

        if ('1' === (string) get_query_var(self::QUERY_VAR) || in_array($path, [$native_path, $localized_native_path], true)) {
            Keleva_Native_Merchant_Portal::render();
            exit;
        }
    }
}
