<?php
defined('ABSPATH') || exit;

final class Keleva_Restaurant_Extras {
    private const META_KEY = '_keleva_restaurant_sauces';
    private const MAX_SAUCES = 2;

    public static function boot(): void {
        add_action('woocommerce_before_add_to_cart_button', [self::class, 'render']);
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'validate'], 10, 6);
        add_filter('woocommerce_add_cart_item_data', [self::class, 'add_cart_item_data'], 10, 4);
        add_action('woocommerce_before_calculate_totals', [self::class, 'apply_surcharge']);
        add_filter('woocommerce_get_item_data', [self::class, 'display_cart_item_data'], 10, 2);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('add_meta_boxes_product', [self::class, 'add_meta_box']);
        add_action('save_post_product', [self::class, 'save_admin_options']);
    }

    public static function options_for(WC_Product $product): array {
        $raw = get_post_meta($product->get_id(), self::META_KEY, true);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        return self::sanitize_options($decoded);
    }

    public static function max_sauces(): int {
        return self::MAX_SAUCES;
    }

    /**
     * Valide une sélection venue d’un formulaire classique ou du quick view REST.
     * Le plafond et la liste blanche restent donc imposés côté serveur.
     *
     * @return array|WP_Error
     */
    public static function selection_for(WC_Product $product, mixed $requested): array|WP_Error {
        $requested = is_array($requested) ? $requested : [];
        $requested = array_values(array_unique(array_map('sanitize_key', $requested)));
        if (count($requested) > self::MAX_SAUCES) {
            /* translators: %d is the maximum number of sauces allowed. */
            return new WP_Error('keleva_too_many_sauces', sprintf(__('Veuillez choisir au maximum %d sauces.', 'keleva-woo-addons'), self::MAX_SAUCES), ['status' => 400]);
        }

        $allowed = [];
        foreach (self::options_for($product) as $option) {
            $allowed[$option['id']] = $option;
        }

        $selected = [];
        foreach ($requested as $id) {
            if (!isset($allowed[$id])) {
                return new WP_Error('keleva_invalid_sauce', __('Une sauce sélectionnée est invalide.', 'keleva-woo-addons'), ['status' => 400]);
            }
            $selected[] = $allowed[$id];
        }
        return $selected;
    }

    public static function cart_item_data_for(WC_Product $product, array $options): array {
        if (!$options) {
            return [];
        }
        return [
            'keleva_sauces' => $options,
            'keleva_sauce_surcharge' => array_sum(array_column($options, 'price')),
            'keleva_base_price' => (float) $product->get_price('edit'),
        ];
    }

    private static function sanitize_options(mixed $decoded): array {
        if (!is_array($decoded)) {
            return [];
        }
        $options = [];
        foreach (array_slice($decoded, 0, 12) as $key => $option) {
            if (!is_array($option) || empty($option['label'])) {
                continue;
            }
            $id = sanitize_key((string) ($option['id'] ?? $key));
            if ($id === '') {
                continue;
            }
            $options[] = [
                'id' => $id,
                'label' => sanitize_text_field((string) $option['label']),
                'price' => max(0, (float) ($option['price'] ?? 0)),
            ];
        }
        return $options;
    }

    public static function add_meta_box(): void {
        add_meta_box(
            'keleva-restaurant-sauces',
            __('Keleva — suppléments restaurant', 'keleva-woo-addons'),
            [self::class, 'render_admin_options'],
            'product',
            'normal',
            'high'
        );
    }

    public static function render_admin_options(WP_Post $post): void {
        wp_nonce_field('keleva_save_restaurant_options', 'keleva_restaurant_options_nonce');
        $product = wc_get_product($post->ID);
        $options = $product ? self::options_for($product) : [];
        $json = wp_json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo '<p>' . esc_html__('Configuration visible uniquement pour les produits de restauration. Le storefront limite strictement la sélection à deux sauces.', 'keleva-woo-addons') . '</p>';
        echo '<label for="keleva-restaurant-sauces-json"><strong>' . esc_html__('Sauces au format JSON', 'keleva-woo-addons') . '</strong></label>';
        echo '<textarea id="keleva-restaurant-sauces-json" class="widefat code" rows="10" name="keleva_restaurant_sauces_json" placeholder="[{&quot;id&quot;:&quot;sauce-maison&quot;,&quot;label&quot;:&quot;Sauce maison&quot;,&quot;price&quot;:0}]">' . esc_textarea((string) $json) . '</textarea>';
        echo '<p class="description">' . esc_html__('Chaque option nécessite un identifiant, un libellé et un prix positif ou nul. Laissez le champ vide pour désactiver les suppléments sur ce produit.', 'keleva-woo-addons') . '</p>';
    }

    public static function save_admin_options(int $post_id): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['keleva_restaurant_options_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['keleva_restaurant_options_nonce'])), 'keleva_save_restaurant_options')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $raw = isset($_POST['keleva_restaurant_sauces_json']) ? trim((string) sanitize_textarea_field(wp_unslash($_POST['keleva_restaurant_sauces_json']))) : '';
        if ($raw === '') {
            delete_post_meta($post_id, self::META_KEY);
            return;
        }
        $decoded = json_decode($raw, true);
        $options = self::sanitize_options($decoded);
        if (json_last_error() !== JSON_ERROR_NONE || !$options) {
            return;
        }
        update_post_meta($post_id, self::META_KEY, wp_json_encode($options));
    }

    private static function requested_options(int $product_id): array {
        $product = wc_get_product($product_id);
        if (!$product) {
            return [];
        }
        $requested = isset($_POST['keleva_sauces']) && is_array($_POST['keleva_sauces']) ? map_deep(wp_unslash($_POST['keleva_sauces']), 'sanitize_key') : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce validates the add-to-cart request with its own nonce.
        $selection = self::selection_for($product, $requested);
        return is_wp_error($selection) ? [] : $selection;
    }

    public static function render(): void {
        global $product;
        if (!$product instanceof WC_Product || !$product->is_purchasable()) {
            return;
        }
        $options = self::options_for($product);
        if (!$options) {
            return;
        }
        echo '<fieldset class="keleva-sauce-picker" data-keleva-sauce-picker data-max-sauces="' . esc_attr((string) self::MAX_SAUCES) . '">';
        echo '<legend>' . esc_html__('Personnalisez votre commande', 'keleva-woo-addons') . '</legend>';
        echo '<p class="keleva-sauce-picker__hint">' . esc_html__('Choisissez jusqu’à deux sauces. Les autres choix se désactivent automatiquement.', 'keleva-woo-addons') . '</p>';
        echo '<p class="keleva-sauce-picker__status" aria-live="polite"></p>';
        foreach ($options as $option) {
            $id = 'keleva-sauce-' . $product->get_id() . '-' . $option['id'];
            $price = $option['price'] > 0 ? ' +' . wp_strip_all_tags(wc_price($option['price'])) : ' ' . __('incluse', 'keleva-woo-addons');
            echo '<label class="keleva-sauce-picker__option" for="' . esc_attr($id) . '">';
            echo '<input id="' . esc_attr($id) . '" type="checkbox" name="keleva_sauces[]" value="' . esc_attr($option['id']) . '">';
            echo '<span>' . esc_html($option['label']) . '</span><small>' . wp_kses_post($price) . '</small></label>';
        }
        echo '</fieldset>';
    }

    public static function validate(bool $passed, int $product_id, int $quantity, int $variation_id = 0, array $variations = [], array $cart_item_data = []): bool {
        $requested = isset($_POST['keleva_sauces']) && is_array($_POST['keleva_sauces']) ? map_deep(wp_unslash($_POST['keleva_sauces']), 'sanitize_key') : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce validates the add-to-cart request with its own nonce.
        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product) {
            return false;
        }
        $selection = self::selection_for($product, $requested);
        if (is_wp_error($selection)) {
            wc_add_notice($selection->get_error_message(), 'error');
            return false;
        }
        return $passed;
    }

    public static function add_cart_item_data(array $cart_item_data, int $product_id, int $variation_id, int $quantity): array {
        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product) {
            return $cart_item_data;
        }
        $options = self::requested_options($product_id);
        return array_merge($cart_item_data, self::cart_item_data_for($product, $options));
    }

    public static function apply_surcharge(WC_Cart $cart): void {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        foreach ($cart->get_cart() as $item) {
            if (!isset($item['keleva_sauce_surcharge'], $item['keleva_base_price'])) {
                continue;
            }
            $item['data']->set_price((float) $item['keleva_base_price'] + (float) $item['keleva_sauce_surcharge']);
        }
    }

    public static function display_cart_item_data(array $item_data, array $cart_item): array {
        if (empty($cart_item['keleva_sauces']) || !is_array($cart_item['keleva_sauces'])) {
            return $item_data;
        }
        $labels = array_map(static fn (array $option): string => (string) $option['label'], $cart_item['keleva_sauces']);
        $item_data[] = ['key' => __('Sauces', 'keleva-woo-addons'), 'value' => implode(', ', $labels)];
        return $item_data;
    }

    public static function enqueue_assets(): void {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        $style_path = KELEVA_WOO_ADDONS_PATH . 'assets/css/restaurant-extras.css';
        $style_version = file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0';
        wp_enqueue_style('keleva-restaurant-extras', plugins_url('../assets/css/restaurant-extras.css', __FILE__), [], $style_version);
        wp_enqueue_script('keleva-restaurant-extras', plugins_url('../assets/js/restaurant-extras.js', __FILE__), [], '1.0.0', ['strategy' => 'defer', 'in_footer' => true]);
    }
}
