<?php
defined('ABSPATH') || exit;

/**
 * Maintient les listes visiteur dans la session WooCommerce plutôt que dans le
 * navigateur seul. Les formulaires restent entièrement utilisables sans JS.
 */
final class Keleva_Saved_Product_Lists {
    private const WISHLIST_KEY = 'keleva_wishlist_products';
    private const COMPARE_KEY = 'keleva_compare_products';

    public static function boot(): void {
        add_action('template_redirect', [self::class, 'handle_request']);
    }

    /** @return int[] */
    public static function ids(string $list): array {
        if (!function_exists('WC') || !WC()->session) return [];
        $key = 'wishlist' === $list ? self::WISHLIST_KEY : self::COMPARE_KEY;
        $ids = WC()->session->get($key, []);
        return is_array($ids) ? array_values(array_unique(array_filter(array_map('absint', $ids)))) : [];
    }

    public static function contains(string $list, int $product_id): bool {
        return in_array($product_id, self::ids($list), true);
    }

    public static function toggle(string $list, int $product_id): bool {
        if (!in_array($list, ['wishlist', 'compare'], true) || !$product_id || !wc_get_product($product_id) || !function_exists('WC') || !WC()->session) return false;
        $key = 'wishlist' === $list ? self::WISHLIST_KEY : self::COMPARE_KEY;
        $ids = self::ids($list);
        if (in_array($product_id, $ids, true)) {
            $ids = array_values(array_diff($ids, [$product_id]));
        } else {
            $ids[] = $product_id;
            $ids = array_slice($ids, -('compare' === $list ? 4 : 50));
        }
        WC()->session->set($key, $ids);
        return true;
    }

    public static function handle_request(): void {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if ('POST' !== $request_method || empty($_POST['keleva_saved_list'])) return;
        $list = sanitize_key(wp_unslash($_POST['keleva_saved_list']));
        $product_id = isset($_POST['keleva_product_id']) ? absint($_POST['keleva_product_id']) : 0;
        if (!in_array($list, ['wishlist', 'compare'], true) || !$product_id || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'keleva_saved_products')) return;
        if (!wc_get_product($product_id) || !function_exists('WC') || !WC()->session) return;

        if (!self::toggle($list, $product_id)) return;
        if (method_exists(WC()->session, 'set_customer_session_cookie')) WC()->session->set_customer_session_cookie(true);
        if (method_exists(WC()->session, 'save_data')) WC()->session->save_data();
        $posted_return = isset($_POST['keleva_saved_return']) ? esc_url_raw(wp_unslash($_POST['keleva_saved_return'])) : '';
        $redirect = wp_validate_redirect($posted_return, wp_get_referer() ?: get_permalink($product_id));
        wp_safe_redirect($redirect);
        exit;
    }

    public static function toggle_form(string $list, WC_Product $product, string $context = ''): string {
        $selected = self::contains($list, $product->get_id());
        $verb = $selected ? __('Retirer', 'keleva-woo-addons') : __('Ajouter', 'keleva-woo-addons');
        $label = 'wishlist' === $list ? __('favoris', 'keleva-woo-addons') : __('comparaison', 'keleva-woo-addons');
        $is_card = 'card' === $context;
        $form_class = $is_card ? 'keleva-saved-list-toggle keleva-product-card__favorite-toggle' : 'keleva-saved-list-toggle';
        $button_class = $is_card ? 'keleva-product-card__favorite' : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        $return_url = home_url($request_uri);
        /* translators: 1: action verb, 2: saved list name. */
        $button_label = sprintf(__('%1$s aux %2$s', 'keleva-woo-addons'), $verb, $label);
        ob_start();
        echo '<form class="' . esc_attr($form_class) . '" method="post">';
        wp_nonce_field('keleva_saved_products');
        echo '<input type="hidden" name="keleva_saved_list" value="' . esc_attr($list) . '"><input type="hidden" name="keleva_product_id" value="' . esc_attr((string) $product->get_id()) . '"><input type="hidden" name="keleva_saved_return" value="' . esc_url($return_url) . '">';
        echo '<button' . ($button_class ? ' class="' . esc_attr($button_class) . '"' : '') . ' type="submit" aria-pressed="' . ($selected ? 'true' : 'false') . '" aria-label="' . esc_attr($button_label) . '">';
        if ($is_card) echo '<span aria-hidden="true">♡</span><span class="screen-reader-text">' . esc_html($button_label) . '</span>';
        else echo esc_html($button_label);
        echo '</button></form>';
        return (string) ob_get_clean();
    }
}
