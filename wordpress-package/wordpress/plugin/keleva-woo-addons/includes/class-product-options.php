<?php
defined('ABSPATH') || exit;

/**
 * Groupes d’options configurables par produit : boutons, radios ou cases à cocher.
 * Les règles de sélection et les prix sont toujours contrôlés côté serveur.
 */
final class Keleva_Product_Options {
    private const META_KEY = '_keleva_product_option_groups';
    private const ADMIN_NONCE = 'keleva_save_product_option_groups';
    private const MAX_GROUPS = 8;
    private const MAX_OPTIONS_PER_GROUP = 16;

    public static function boot(): void {
        add_action('woocommerce_before_add_to_cart_button', [self::class, 'render']);
        add_action('woocommerce_before_variations_form', [self::class, 'render']);
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'validate'], 20, 6);
        add_filter('woocommerce_add_cart_item_data', [self::class, 'add_cart_item_data'], 20, 4);
        add_action('woocommerce_before_calculate_totals', [self::class, 'apply_surcharge'], 20);
        add_filter('woocommerce_get_item_data', [self::class, 'display_cart_item_data'], 20, 2);
        add_action('add_meta_boxes_product', [self::class, 'add_meta_box']);
        add_action('save_post_product', [self::class, 'save_admin_options']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_storefront_assets']);
    }

    public static function groups_for(WC_Product $product): array {
        $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $source = (string) get_post_meta($product_id, '_keleva_options_source', true);
        if (class_exists('Keleva_Category_Service') && 'custom' !== $source) {
            $inherited = Keleva_Category_Service::template_for_product($product);
            if (!empty($inherited['groups'])) {
                return self::normalize_groups($inherited['groups']);
            }
        }
        $raw = get_post_meta($product_id, self::META_KEY, true);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        return self::normalize_groups($decoded);
    }

    public static function public_groups_for(WC_Product $product): array {
        return array_map(static function (array $group): array {
            return [
                'id' => $group['id'],
                'label' => $group['label'],
                'display' => $group['display'],
                'max' => $group['max'],
                'required' => $group['required'],
                'options' => array_map(static function (array $option): array {
                    return [
                        'id' => $option['id'],
                        'label' => $option['label'],
                        'price' => $option['price'],
                        'price_html' => $option['price'] > 0
                            ? wp_strip_all_tags(wc_price($option['price']))
                            : __('incluse', 'keleva-woo-addons'),
                    ];
                }, $group['options']),
            ];
        }, self::groups_for($product));
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, surcharge: float}|WP_Error
     */
    public static function selection_for(WC_Product $product, mixed $requested): array|WP_Error {
        $groups = self::groups_for($product);
        if (!$groups) {
            return ['items' => [], 'surcharge' => 0.0];
        }

        $requested = is_array($requested) ? $requested : [];
        $normalized = [];
        foreach ($requested as $group_id => $values) {
            $id = sanitize_key((string) $group_id);
            if ($id === '') {
                continue;
            }
            $values = is_array($values) ? $values : [$values];
            $normalized[$id] = array_values(array_unique(array_filter(array_map('sanitize_key', $values))));
        }

        $allowed_group_ids = array_column($groups, 'id');
        foreach ($normalized as $group_id => $values) {
            if ($values && !in_array($group_id, $allowed_group_ids, true)) {
                return new WP_Error('keleva_unknown_option_group', __('Un groupe d’options sélectionné est invalide.', 'keleva-woo-addons'), ['status' => 400]);
            }
        }

        $items = [];
        foreach ($groups as $group) {
            $selected_ids = $normalized[$group['id']] ?? [];
            if ($group['required'] && !$selected_ids) {
                /* translators: %s is the product option group label. */
                return new WP_Error('keleva_required_product_option', sprintf(__('Choisissez au moins une option pour « %s ».', 'keleva-woo-addons'), $group['label']), ['status' => 400]);
            }
            if (count($selected_ids) > $group['max']) {
                /* translators: %1$d is the maximum number of choices; %2$s is the option group label. */
                return new WP_Error('keleva_option_limit_reached', sprintf(__('Choisissez au maximum %1$d option(s) pour « %2$s ».', 'keleva-woo-addons'), $group['max'], $group['label']), ['status' => 400]);
            }

            $allowed_options = [];
            foreach ($group['options'] as $option) {
                $allowed_options[$option['id']] = $option;
            }
            foreach ($selected_ids as $option_id) {
                if (!isset($allowed_options[$option_id])) {
                    return new WP_Error('keleva_invalid_product_option', __('Une option sélectionnée est invalide.', 'keleva-woo-addons'), ['status' => 400]);
                }
                $option = $allowed_options[$option_id];
                $items[] = [
                    'group_id' => $group['id'],
                    'group_label' => $group['label'],
                    'option_id' => $option['id'],
                    'label' => $option['label'],
                    'price' => $option['price'],
                ];
            }
        }

        return [
            'items' => $items,
            'surcharge' => (float) array_sum(array_column($items, 'price')),
        ];
    }

    public static function cart_item_data_for(WC_Product $product, array $selection): array {
        if (empty($selection['items'])) {
            return [];
        }
        return [
            'keleva_product_options' => $selection['items'],
            'keleva_product_options_surcharge' => (float) ($selection['surcharge'] ?? 0),
            'keleva_product_options_base_price' => (float) $product->get_price('edit'),
        ];
    }

    public static function normalize_groups(mixed $decoded): array {
        if (!is_array($decoded)) {
            return [];
        }

        $groups = [];
        foreach (array_slice($decoded, 0, self::MAX_GROUPS) as $group_key => $group) {
            if (!is_array($group) || empty($group['label'])) {
                continue;
            }
            $id = sanitize_key((string) ($group['id'] ?? $group_key));
            if ($id === '') {
                continue;
            }
            $display = (string) ($group['display'] ?? 'buttons');
            if (!in_array($display, ['buttons', 'radio', 'checkbox'], true)) {
                $display = 'buttons';
            }
            $options = [];
            foreach (array_slice((array) ($group['options'] ?? []), 0, self::MAX_OPTIONS_PER_GROUP) as $option_key => $option) {
                if (!is_array($option) || empty($option['label'])) {
                    continue;
                }
                $option_id = sanitize_key((string) ($option['id'] ?? $option_key));
                if ($option_id === '') {
                    continue;
                }
                $options[$option_id] = [
                    'id' => $option_id,
                    'label' => sanitize_text_field((string) $option['label']),
                    'price' => max(0, (float) ($option['price'] ?? 0)),
                ];
            }
            if (!$options) {
                continue;
            }
            $max = min(count($options), max(1, (int) ($group['max'] ?? 1)));
            if ($display === 'radio') {
                $max = 1;
            }
            if ($display === 'buttons' && $max > 1) {
                $display = 'checkbox';
            }
            $groups[] = [
                'id' => $id,
                'label' => sanitize_text_field((string) $group['label']),
                'display' => $display,
                'max' => min(4, $max),
                'required' => !empty($group['required']),
                'options' => array_values($options),
            ];
        }
        return $groups;
    }

    public static function add_meta_box(): void {
        add_meta_box(
            'keleva-product-option-groups',
            __('Keleva — groupes d’options', 'keleva-woo-addons'),
            [self::class, 'render_admin_options'],
            'product',
            'normal',
            'high'
        );
    }

    public static function render_admin_options(WP_Post $post): void {
        wp_nonce_field(self::ADMIN_NONCE, 'keleva_product_option_groups_nonce');
        $product = wc_get_product($post->ID);
        $groups = $product ? self::groups_for($product) : [];
        ?>
        <div id="keleva-product-options-admin" data-groups="<?php echo esc_attr(wp_json_encode($groups)); ?>">
            <p><?php esc_html_e('Créez des groupes comme « Couleur », « Extras » ou « Finition ». Chaque groupe peut s’afficher en boutons, radios ou cases à cocher et accepter jusqu’à quatre choix simultanés.', 'keleva-woo-addons'); ?></p>
            <div class="keleva-product-options-admin__groups" data-keleva-option-groups></div>
            <p><button type="button" class="button button-secondary" data-keleva-add-option-group><?php esc_html_e('Ajouter un groupe d’options', 'keleva-woo-addons'); ?></button></p>
            <input type="hidden" name="keleva_product_option_groups_json" value="<?php echo esc_attr(wp_json_encode($groups)); ?>" data-keleva-option-groups-json>
            <p class="description"><?php esc_html_e('Pour chaque option, définissez un libellé et, si nécessaire, un supplément de prix. Les limites sont appliquées par le storefront et vérifiées de nouveau par WooCommerce avant l’ajout panier.', 'keleva-woo-addons'); ?></p>
        </div>
        <?php
    }

    public static function save_admin_options(int $post_id): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $nonce = isset($_POST['keleva_product_option_groups_nonce']) ? sanitize_text_field(wp_unslash($_POST['keleva_product_option_groups_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, self::ADMIN_NONCE) || !current_user_can('edit_post', $post_id)) {
            return;
        }

        $raw = isset($_POST['keleva_product_option_groups_json']) ? trim((string) sanitize_textarea_field(wp_unslash($_POST['keleva_product_option_groups_json']))) : '';
        if ($raw === '') {
            delete_post_meta($post_id, self::META_KEY);
            return;
        }
        $decoded = json_decode($raw, true);
        $groups = self::normalize_groups($decoded);
        if (json_last_error() !== JSON_ERROR_NONE || !$groups) {
            return;
        }
        update_post_meta($post_id, self::META_KEY, wp_slash(wp_json_encode($groups)));
    }

    private static function requested_from_post(int $product_id): array|WP_Error {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('keleva_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'));
        }
        $requested = isset($_POST['keleva_product_options']) && is_array($_POST['keleva_product_options']) ? map_deep(wp_unslash($_POST['keleva_product_options']), 'sanitize_text_field') : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce validates the add-to-cart request with its own nonce.
        return self::selection_for($product, $requested);
    }

    public static function render(): void {
        global $product;
        if (!$product instanceof WC_Product || !$product->is_purchasable()) {
            return;
        }
        $groups = self::public_groups_for($product);
        if (!$groups) {
            return;
        }
        echo '<section class="keleva-product-options" data-keleva-product-options>';
        foreach ($groups as $group) {
            $input_type = ($group['display'] === 'radio' || $group['max'] === 1) ? 'radio' : 'checkbox';
            $group_id = $product->get_id() . '-' . $group['id'];
            echo '<fieldset class="keleva-product-options__group keleva-product-options__group--' . esc_attr($group['display']) . '" data-keleva-option-group data-option-group-id="' . esc_attr($group['id']) . '" data-option-max="' . esc_attr((string) $group['max']) . '" data-option-required="' . esc_attr($group['required'] ? 'true' : 'false') . '">';
            echo '<legend>' . esc_html($group['label']) . ($group['required'] ? ' <em aria-hidden="true">*</em>' : '') . '</legend>';
            echo '<p class="keleva-product-options__hint">' . esc_html($group['max'] === 1 ? __('Choisissez une option.', 'keleva-woo-addons') : sprintf(/* translators: %d is the maximum number of choices. */ __('Jusqu’à %d options.', 'keleva-woo-addons'), $group['max'])) . '</p>';
            echo '<div class="keleva-product-options__choices">';
            foreach ($group['options'] as $option) {
                $id = 'keleva-option-' . sanitize_html_class($group_id . '-' . $option['id']);
                $price = $option['price'] > 0 ? ' +' . wp_strip_all_tags(wc_price($option['price'])) : ' ' . __('incluse', 'keleva-woo-addons');
                echo '<label class="keleva-product-options__option" for="' . esc_attr($id) . '">';
                echo '<input id="' . esc_attr($id) . '" type="' . esc_attr($input_type) . '" name="keleva_product_options[' . esc_attr($group['id']) . '][]" value="' . esc_attr($option['id']) . '" data-keleva-product-option>';
                echo '<span>' . esc_html($option['label']) . '</span><small>' . wp_kses_post($price) . '</small></label>';
            }
            echo '</div><p class="keleva-product-options__status" data-keleva-option-status aria-live="polite"></p></fieldset>';
        }
        echo '</section>';
    }

    public static function validate(bool $passed, int $product_id, int $quantity, int $variation_id = 0, array $variations = [], array $cart_item_data = []): bool {
        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product) {
            return false;
        }
        $selection = self::requested_from_post($product->get_id());
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
        $selection = self::requested_from_post($product->get_id());
        if (is_wp_error($selection)) {
            return $cart_item_data;
        }
        return array_merge($cart_item_data, self::cart_item_data_for($product, $selection));
    }

    public static function apply_surcharge(WC_Cart $cart): void {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        foreach ($cart->get_cart() as $item) {
            if (!isset($item['keleva_product_options_surcharge'], $item['keleva_product_options_base_price'])) {
                continue;
            }
            $sauce_surcharge = isset($item['keleva_sauce_surcharge']) ? (float) $item['keleva_sauce_surcharge'] : 0.0;
            $item['data']->set_price((float) $item['keleva_product_options_base_price'] + (float) $item['keleva_product_options_surcharge'] + $sauce_surcharge);
        }
    }

    public static function display_cart_item_data(array $item_data, array $cart_item): array {
        if (empty($cart_item['keleva_product_options']) || !is_array($cart_item['keleva_product_options'])) {
            return $item_data;
        }
        $by_group = [];
        foreach ($cart_item['keleva_product_options'] as $option) {
            if (!is_array($option) || empty($option['group_label']) || empty($option['label'])) {
                continue;
            }
            $by_group[$option['group_label']][] = $option['label'];
        }
        foreach ($by_group as $group_label => $labels) {
            $item_data[] = ['key' => $group_label, 'value' => implode(', ', $labels)];
        }
        return $item_data;
    }

    public static function enqueue_admin_assets(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }
        $script_path = KELEVA_WOO_ADDONS_PATH . 'assets/js/product-options-admin.js';
        wp_enqueue_script('keleva-product-options-admin', plugins_url('../assets/js/product-options-admin.js', __FILE__), [], file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0', true);
        $style_path = KELEVA_WOO_ADDONS_PATH . 'assets/css/product-options-admin.css';
        wp_enqueue_style('keleva-product-options-admin', plugins_url('../assets/css/product-options-admin.css', __FILE__), [], file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0');
    }

    public static function enqueue_storefront_assets(): void {
        if (!function_exists('is_woocommerce') || (!is_woocommerce() && !is_product())) {
            return;
        }
        $style_path = KELEVA_WOO_ADDONS_PATH . 'assets/css/product-options.css';
        wp_enqueue_style('keleva-product-options', plugins_url('../assets/css/product-options.css', __FILE__), [], file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0');
    }
}
