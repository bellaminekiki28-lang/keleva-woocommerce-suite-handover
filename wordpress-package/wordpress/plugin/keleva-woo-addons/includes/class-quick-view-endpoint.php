<?php
defined('ABSPATH') || exit;

final class Keleva_Quick_View_Endpoint {
    private static function image_payload(int $image_id, WC_Product $product): array {
        return [
            'src' => $image_id ? wp_get_attachment_image_url($image_id, 'keleva-quick-view') : wc_placeholder_img_src('woocommerce_single'),
            'avif' => $image_id && function_exists('keleva_woo_get_avif_url') ? keleva_woo_get_avif_url($image_id, 'keleva-quick-view') : null,
            'webp' => $image_id && function_exists('keleva_woo_get_variant_url') ? keleva_woo_get_variant_url($image_id, 'keleva-quick-view', 'WEBP') : null,
            'alt' => $image_id ? (string) get_post_meta($image_id, '_wp_attachment_image_alt', true) : $product->get_name(),
        ];
    }

    private static function variation_payload(WC_Product_Variation $variation, WC_Product $parent): array {
        $attributes = [];
        $labels = [];

        foreach ($variation->get_attributes() as $name => $value) {
            $key = 'attribute_' . sanitize_title($name);
            $attributes[$key] = $value;
            $label = wc_attribute_label($name, $parent);
            $term = taxonomy_exists($name) ? get_term_by('slug', $value, $name) : false;
            $labels[$label] = $term instanceof WP_Term ? $term->name : $value;
        }

        $image_id = (int) ($variation->get_image_id() ?: $parent->get_image_id());
        return [
            'id' => $variation->get_id(),
            'attributes' => $attributes,
            'labels' => $labels,
            'price_html' => wp_kses_post($variation->get_price_html()),
            'price' => (string) $variation->get_price(),
            'regular_price' => (string) $variation->get_regular_price(),
            'can_add' => $variation->is_purchasable() && $variation->is_in_stock(),
            'stock_status' => $variation->get_stock_status(),
            'image' => self::image_payload($image_id, $parent),
        ];
    }

    private static function variable_attributes(WC_Product_Variable $product): array {
        $attributes = [];
        foreach ($product->get_variation_attributes() as $name => $options) {
            $attributes[] = [
                'key' => 'attribute_' . sanitize_title($name),
                'name' => $name,
                'label' => wc_attribute_label($name, $product),
                'options' => array_map(static function (string $value) use ($name): array {
                    $term = taxonomy_exists($name) ? get_term_by('slug', $value, $name) : false;
                    return [
                        'value' => $value,
                        'label' => $term instanceof WP_Term ? $term->name : $value,
                    ];
                }, $options),
            ];
        }
        return $attributes;
    }

    public static function register_routes(): void {
        register_rest_route('keleva/v1', '/products/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'get_product'],
            'permission_callback' => '__return_true',
            'args' => ['id' => ['validate_callback' => static fn ($value): bool => is_numeric($value)]],
        ]);
        register_rest_route('keleva/v1', '/products/(?P<id>\d+)/add-to-cart', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'add_configured_product_to_cart'],
            'permission_callback' => [self::class, 'can_mutate_cart'],
            'args' => [
                'id' => ['validate_callback' => static fn ($value): bool => is_numeric($value)],
                'sauces' => ['type' => 'array', 'required' => false],
                'options' => ['type' => 'object', 'required' => false],
                'variation_id' => ['validate_callback' => static fn ($value): bool => empty($value) || is_numeric($value), 'required' => false],
                'variation' => ['type' => 'array', 'required' => false],
                'quantity' => ['validate_callback' => static fn ($value): bool => empty($value) || is_numeric($value), 'required' => false],
            ],
        ]);
    }

    public static function can_mutate_cart(WP_REST_Request $request): bool|WP_Error {
        $nonce = $request->get_header('Nonce') ?: $request->get_header('X-WC-Store-API-Nonce');
        if ($nonce && wp_verify_nonce($nonce, 'wc_store_api')) {
            return true;
        }

        // Le Store API ne retourne pas systématiquement un nonce réutilisable sur
        // certaines sessions anonymes. Le quick view reste donc autorisé lorsqu’il
        // provient de la même origine que la boutique : une requête JSON cross-origin
        // est rejetée, tandis que la session WooCommerce reste portée par ses cookies.
        $origin = rtrim((string) $request->get_header('Origin'), '/');
        $site = wp_parse_url(home_url('/'));
        $site_origin = isset($site['scheme'], $site['host'])
            ? strtolower($site['scheme'] . '://' . $site['host'] . (isset($site['port']) ? ':' . $site['port'] : ''))
            : '';
        if ($origin && $site_origin && hash_equals($site_origin, strtolower($origin))) {
            return true;
        }

        return new WP_Error('keleva_cart_nonce_required', __('Session panier invalide. Actualisez la page puis réessayez.', 'keleva-woo-addons'), ['status' => 403]);
    }

    public static function get_product(WP_REST_Request $request): WP_REST_Response|WP_Error {
        if (!function_exists('wc_get_product')) {
            return new WP_Error('keleva_woocommerce_missing', __('WooCommerce est indisponible.', 'keleva-woo-addons'), ['status' => 503]);
        }

        $product = wc_get_product((int) $request['id']);
        if (!$product || 'publish' !== $product->get_status() || !$product->is_visible()) {
            return new WP_Error('keleva_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }

        $image_id = (int) $product->get_image_id();
        $is_variable = $product instanceof WC_Product_Variable;
        $variations = $is_variable
            ? array_map(static fn (WC_Product_Variation $variation): array => self::variation_payload($variation, $product), $product->get_available_variations('objects'))
            : [];
        $response = new WP_REST_Response([
            'id' => $product->get_id(),
            'type' => $product->get_type(),
            'name' => $product->get_name(),
            'permalink' => get_permalink($product->get_id()),
            'category' => wp_strip_all_tags(wc_get_product_category_list($product->get_id(), ', ')),
            'short_description' => wp_strip_all_tags($product->get_short_description()),
            'price_html' => wp_kses_post($product->get_price_html()),
            'can_add' => $product->is_purchasable() && $product->is_in_stock() && !$is_variable,
            'attributes' => $is_variable && $product instanceof WC_Product_Variable ? self::variable_attributes($product) : [],
            'variations' => $variations,
            'sauces' => class_exists('Keleva_Restaurant_Extras') ? array_map(static function (array $option): array {
                $option['price_html'] = $option['price'] > 0 ? wp_strip_all_tags(wc_price($option['price'])) : __('incluse', 'keleva-woo-addons');
                return $option;
            }, Keleva_Restaurant_Extras::options_for($product)) : [],
            'max_sauces' => class_exists('Keleva_Restaurant_Extras') ? Keleva_Restaurant_Extras::max_sauces() : 0,
            'option_groups' => class_exists('Keleva_Product_Options') ? Keleva_Product_Options::public_groups_for($product) : [],
            'image' => self::image_payload($image_id, $product),
        ]);
        $response->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=30');
        return $response;
    }

    public static function add_configured_product_to_cart(WP_REST_Request $request): WP_REST_Response|WP_Error {
        if (function_exists('wc_load_cart')) {
            wc_load_cart();
        }
        if (!function_exists('WC') || !WC()->cart) {
            return new WP_Error('keleva_cart_unavailable', __('Le panier WooCommerce est indisponible.', 'keleva-woo-addons'), ['status' => 503]);
        }

        $product = wc_get_product((int) $request['id']);
        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return new WP_Error('keleva_product_unavailable', __('Ce produit ne peut pas être ajouté au panier.', 'keleva-woo-addons'), ['status' => 400]);
        }

        $quantity = max(1, (int) ($request->get_param('quantity') ?: 1));
        $variation_id = (int) ($request->get_param('variation_id') ?: 0);
        $variation = $request->get_param('variation');
        $variation_attributes = [];
        foreach (is_array($variation) ? $variation : [] as $attribute) {
            if (!is_array($attribute) || empty($attribute['attribute']) || !isset($attribute['value'])) {
                continue;
            }
            $variation_attributes[sanitize_key((string) $attribute['attribute'])] = sanitize_text_field((string) $attribute['value']);
        }

        if ($product->is_type('variable')) {
            $selected_variation = wc_get_product($variation_id);
            if (!$selected_variation instanceof WC_Product_Variation || $selected_variation->get_parent_id() !== $product->get_id() || !$selected_variation->is_purchasable() || !$selected_variation->is_in_stock()) {
                return new WP_Error('keleva_invalid_variation', __('La variation sélectionnée est indisponible.', 'keleva-woo-addons'), ['status' => 400]);
            }
            $product_for_price = $selected_variation;
        } elseif ($variation_id) {
            return new WP_Error('keleva_unexpected_variation', __('Cette variation ne correspond pas au produit choisi.', 'keleva-woo-addons'), ['status' => 400]);
        } else {
            $product_for_price = $product;
        }

        $options = $request->get_param('options');
        $option_selection = class_exists('Keleva_Product_Options')
            ? Keleva_Product_Options::selection_for($product_for_price, $options)
            : ['items' => [], 'surcharge' => 0.0];
        if (is_wp_error($option_selection)) {
            return $option_selection;
        }

        $sauces = $request->get_param('sauces');
        $selection = class_exists('Keleva_Restaurant_Extras')
            ? Keleva_Restaurant_Extras::selection_for($product_for_price, $sauces)
            : [];
        if (is_wp_error($selection)) {
            return $selection;
        }
        if (class_exists('Keleva_Restaurant_Extras') && Keleva_Restaurant_Extras::options_for($product_for_price) && !$selection) {
            return new WP_Error('keleva_sauces_required', __('Choisissez au moins une sauce pour ce produit.', 'keleva-woo-addons'), ['status' => 400]);
        }

        $cart_item_data = class_exists('Keleva_Product_Options')
            ? Keleva_Product_Options::cart_item_data_for($product_for_price, $option_selection)
            : [];
        if ($selection && class_exists('Keleva_Restaurant_Extras')) {
            $cart_item_data = array_merge($cart_item_data, Keleva_Restaurant_Extras::cart_item_data_for($product_for_price, $selection));
        }

        $cart_key = WC()->cart->add_to_cart($product->get_id(), $quantity, $variation_id, $variation_attributes, $cart_item_data);
        if (!$cart_key) {
            return new WP_Error('keleva_cart_add_failed', __('Impossible d’ajouter ce produit au panier.', 'keleva-woo-addons'), ['status' => 400]);
        }

        return new WP_REST_Response([
            'items_count' => WC()->cart->get_cart_contents_count(),
            'sauces' => array_map(static fn (array $option): string => $option['label'], $selection),
            'options' => array_map(static fn (array $option): string => $option['label'], $option_selection['items']),
        ]);
    }
}
