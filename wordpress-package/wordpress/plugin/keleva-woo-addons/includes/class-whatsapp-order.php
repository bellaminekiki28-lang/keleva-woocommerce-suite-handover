<?php
defined('ABSPATH') || exit;

/**
 * Creates a WooCommerce order from the cart before WhatsApp collects customer
 * details, then exposes a signed endpoint for n8n to complete that order.
 */
final class Keleva_WhatsApp_Order {
    private const NONCE_ACTION = 'wp_rest';
    private const IDEMPOTENCY_TTL = 15 * MINUTE_IN_SECONDS;
    private const WEBHOOK_MAX_SKEW = 600;
    private const WEBHOOK_EVENT_META = '_keleva_whatsapp_event_ids';

    public static function boot(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
        add_action('template_redirect', [self::class, 'handle_whatsapp_link'], 1);
        add_filter('render_block_woocommerce/cart', [self::class, 'inject_cart_button'], 30, 1);
        add_action('woocommerce_after_cart', [self::class, 'render_classic_cart_button'], 30);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets'], 30);
    }

    public static function handle_whatsapp_link(): void {
        if (!isset($_GET['keleva_whatsapp_order'])) {
            return;
        }
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if ('' === $nonce || !wp_verify_nonce($nonce, 'keleva_whatsapp_order')) {
            wp_die(esc_html__('Lien WhatsApp expiré. Rechargez le panier puis réessayez.', 'keleva-woo-addons'), '', ['response' => 403]);
        }
        self::ensure_cart();
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }
        $order = self::create_order_from_cart();
        if (is_wp_error($order)) {
            wp_die(esc_html($order->get_error_message()), '', ['response' => 422]);
        }
        $response = self::order_response($order);
        self::send_webhook_once('keleva.whatsapp.order.created', $response, $order);
        wp_redirect(esc_url_raw($response['whatsapp_url']), 303);
        exit;
    }

    public static function register_routes(): void {
        register_rest_route('keleva/v1', '/whatsapp/order', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'create_order'],
            'permission_callback' => [self::class, 'cart_permission'],
        ]);
        register_rest_route('keleva/v1', '/whatsapp/order/(?P<id>\d+)', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_order'],
            'permission_callback' => [self::class, 'webhook_permission'],
            'args' => [
                'id' => ['validate_callback' => static fn ($value): bool => (int) $value > 0],
            ],
        ]);
    }

    public static function cart_permission(WP_REST_Request $request): bool|WP_Error {
        self::ensure_cart();
        $nonce = (string) $request->get_header('X-WP-Nonce');
        if ('' === $nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return new WP_Error('keleva_whatsapp_nonce', __('Session de panier expirée. Rechargez la page puis réessayez.', 'keleva-woo-addons'), ['status' => 403]);
        }
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return new WP_Error('keleva_whatsapp_empty_cart', __('Le panier est vide.', 'keleva-woo-addons'), ['status' => 422]);
        }
        return true;
    }

    public static function webhook_permission(WP_REST_Request $request): bool|WP_Error {
        $configured = defined('KELEVA_WHATSAPP_WEBHOOK_SECRET') ? (string) KELEVA_WHATSAPP_WEBHOOK_SECRET : (class_exists('Keleva_Dashboard_Settings') ? Keleva_Dashboard_Settings::get('KELEVA_WHATSAPP_WEBHOOK_SECRET') : (string) get_option('keleva_whatsapp_webhook_secret', ''));
        $timestamp = sanitize_text_field((string) $request->get_header('X-Keleva-Webhook-Timestamp'));
        $event_id = sanitize_text_field((string) $request->get_header('X-Keleva-Webhook-Event-Id'));
        $provided = (string) $request->get_header('X-Keleva-Webhook-Signature');
        if ('' === $configured || '' === $timestamp || '' === $event_id || '' === $provided || !ctype_digit($timestamp)) {
            return new WP_Error('keleva_whatsapp_webhook_forbidden', __('Signature webhook invalide.', 'keleva-woo-addons'), ['status' => 401]);
        }
        if (abs(time() - (int) $timestamp) > self::WEBHOOK_MAX_SKEW) {
            return new WP_Error('keleva_whatsapp_webhook_expired', __('Événement webhook expiré.', 'keleva-woo-addons'), ['status' => 401]);
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{7,127}$/', $event_id)) {
            return new WP_Error('keleva_whatsapp_webhook_event_invalid', __('Identifiant d’événement webhook invalide.', 'keleva-woo-addons'), ['status' => 401]);
        }
        $signed_payload = $timestamp . '.' . $event_id . '.' . $request->get_body();
        $expected = 'sha256=' . hash_hmac('sha256', $signed_payload, $configured);
        if (!hash_equals($expected, $provided)) {
            return new WP_Error('keleva_whatsapp_webhook_forbidden', __('Signature webhook invalide.', 'keleva-woo-addons'), ['status' => 401]);
        }
        return true;
    }

    public static function create_order(WP_REST_Request $request): WP_REST_Response|WP_Error {
        self::ensure_cart();
        if (!function_exists('wc_create_order') || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return new WP_Error('keleva_whatsapp_empty_cart', __('Le panier est vide.', 'keleva-woo-addons'), ['status' => 422]);
        }

        $idempotency = sanitize_key((string) $request->get_header('X-Keleva-Idempotency-Key'));
        $order = self::create_order_from_cart($idempotency);
        if (is_wp_error($order)) {
            return $order;
        }
        $response = self::order_response($order);
        self::send_webhook_once('keleva.whatsapp.order.created', $response, $order);

        return new WP_REST_Response($response, 201);
    }

    private static function create_order_from_cart(string $idempotency = ''): WC_Order|WP_Error {
        $fingerprint = self::cart_fingerprint();
        if ('' === $idempotency) {
            $idempotency = hash('sha256', (string) wp_get_session_token() . '|' . $fingerprint . '|' . gmdate('Y-m-d'));
        }
        $transient_key = 'keleva_wa_idem_' . md5($idempotency . '|' . $fingerprint);
        $existing_id = (int) get_transient($transient_key);
        if ($existing_id > 0) {
            $existing = wc_get_order($existing_id);
            if ($existing instanceof WC_Order) {
                return $existing;
            }
        }

        $order = wc_create_order();
        if (is_wp_error($order)) {
            return $order;
        }
        $order->set_created_via('keleva_whatsapp');
        $order->set_payment_method('cod');
        $order->set_payment_method_title(__('Paiement à la livraison — WhatsApp', 'keleva-woo-addons'));
        $order->add_meta_data('_keleva_whatsapp_state', 'awaiting_customer_details', true);
        $order->add_meta_data('_keleva_whatsapp_phone', self::phone(), true);
        $order->add_meta_data('_keleva_whatsapp_cart_fingerprint', $fingerprint, true);

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;
            $quantity = max(1, (int) ($cart_item['quantity'] ?? 1));
            if (!$product instanceof WC_Product || !$product->exists()) {
                continue;
            }
            $item_id = $order->add_product($product, $quantity, [
                'variation_id' => (int) ($cart_item['variation_id'] ?? 0),
                'variation' => (array) ($cart_item['variation'] ?? []),
            ]);
            $item = $item_id ? $order->get_item($item_id) : false;
            if (!$item) {
                continue;
            }
            foreach (self::safe_cart_meta($cart_item) as $label => $value) {
                $item->add_meta_data($label, $value, true);
            }
            $item->save();
        }

        $order->calculate_totals();
        $order->add_order_note(__('Commande créée depuis le panier avant collecte WhatsApp. Les coordonnées, la localisation et la date de livraison seront complétées par l’automatisation.', 'keleva-woo-addons'));
        $order->update_status('pending', __('En attente des informations client WhatsApp.', 'keleva-woo-addons'));
        $order->save();
        set_transient($transient_key, $order->get_id(), self::IDEMPOTENCY_TTL);
        return $order;
    }

    public static function update_order(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $order = wc_get_order((int) $request['id']);
        if (!$order) {
            return new WP_Error('keleva_whatsapp_order_not_found', __('Commande introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        $event_id = sanitize_text_field((string) $request->get_header('X-Keleva-Webhook-Event-Id'));
        $seen_event_ids = (array) $order->get_meta(self::WEBHOOK_EVENT_META, true);
        if (in_array($event_id, $seen_event_ids, true)) {
            $response = self::order_response($order);
            $response['deduplicated'] = true;
            return new WP_REST_Response($response, 200);
        }

        $body = $request->get_json_params();
        $body = is_array($body) ? $body : [];
        $name = sanitize_text_field((string) ($body['name'] ?? ''));
        $location = sanitize_text_field((string) ($body['location'] ?? ''));
        $delivery_date = sanitize_text_field((string) ($body['delivery_date'] ?? ''));
        $phone = sanitize_text_field((string) ($body['phone'] ?? ''));
        if ('' === $name || '' === $location || '' === $delivery_date) {
            return new WP_Error('keleva_whatsapp_customer_data_missing', __('Le nom, la localisation et la date de livraison sont obligatoires.', 'keleva-woo-addons'), ['status' => 422]);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $delivery_date)) {
            return new WP_Error('keleva_whatsapp_delivery_date_invalid', __('La date de livraison doit être au format AAAA-MM-JJ.', 'keleva-woo-addons'), ['status' => 422]);
        }

        $name_parts = preg_split('/\s+/', $name, 2) ?: [$name];
        $order->set_billing_first_name((string) ($name_parts[0] ?? $name));
        $order->set_billing_last_name((string) ($name_parts[1] ?? ''));
        $order->set_billing_phone($phone);
        $order->set_billing_address_1($location);
        $order->set_shipping_first_name((string) ($name_parts[0] ?? $name));
        $order->set_shipping_last_name((string) ($name_parts[1] ?? ''));
        $order->set_shipping_address_1($location);
        $order->update_meta_data('_keleva_customer_name', $name);
        $order->update_meta_data('_keleva_delivery_location', $location);
        $order->update_meta_data('_keleva_delivery_date', $delivery_date);
        $order->update_meta_data('_keleva_whatsapp_state', 'customer_details_received');
        $seen_event_ids[] = $event_id;
        $seen_event_ids = array_values(array_unique(array_filter(array_map('sanitize_text_field', $seen_event_ids))));
        $order->update_meta_data(self::WEBHOOK_EVENT_META, array_slice($seen_event_ids, -20));
        $order->update_meta_data('_keleva_whatsapp_last_event_id', $event_id);
        $order->add_order_note(sprintf(__('Informations reçues par WhatsApp : %1$s — %2$s — livraison le %3$s.', 'keleva-woo-addons'), $name, $location, $delivery_date));
        $order->update_status('on-hold', __('Informations WhatsApp reçues — paiement à la livraison à confirmer.', 'keleva-woo-addons'));
        $order->save();

        return new WP_REST_Response(self::order_response($order), 200);
    }

    public static function inject_cart_button(string $content): string {
        if (is_admin() || !is_cart() || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty() || str_contains($content, 'keleva-whatsapp-order-button')) {
            return $content;
        }
        return $content . self::button_markup();
    }

    public static function render_classic_cart_button(): void {
        if (!is_cart() || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }
        echo wp_kses_post(self::button_markup());
    }

    public static function enqueue_assets(): void {
        if (!is_cart()) {
            return;
        }
        $handle = 'keleva-whatsapp-order';
        wp_enqueue_script($handle, plugins_url('../assets/js/whatsapp-order.js', __FILE__), [], '0.5.9', true);
        wp_localize_script($handle, 'KelevaWhatsAppOrder', [
            'endpoint' => esc_url_raw(rest_url('keleva/v1/whatsapp/order')),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'phone' => self::phone(),
            'creating' => __('Préparation de votre commande…', 'keleva-woo-addons'),
            'error' => __('Impossible de préparer la commande. Réessayez.', 'keleva-woo-addons'),
        ]);
        wp_add_inline_style($handle, '.keleva-whatsapp-order-button{display:inline-flex;align-items:center;justify-content:center;gap:.6rem;margin-top:12px;min-height:48px;padding:0 18px;border:1px solid #1b5e4b;background:#1b5e4b;color:#fff;font-weight:700;cursor:pointer}.keleva-whatsapp-order-button:hover{background:#124737}.keleva-whatsapp-order-button[aria-busy="true"]{opacity:.65;cursor:wait}.keleva-whatsapp-order-status{margin:10px 0;font-size:.9rem}.keleva-whatsapp-order-error{color:#9b2f2f}');
    }

    private static function button_markup(): string {
        if ('' === self::phone()) {
            return '';
        }
        $url = add_query_arg(['keleva_whatsapp_order' => '1', '_wpnonce' => wp_create_nonce('keleva_whatsapp_order')], home_url('/'));
        return '<div class="keleva-whatsapp-order-wrap"><a class="keleva-whatsapp-order-button" data-keleva-whatsapp-order href="' . esc_url($url) . '">Commander sur WhatsApp — Paiement à la livraison <span aria-hidden="true">→</span></a><p class="keleva-whatsapp-order-status" data-keleva-whatsapp-status role="status"></p></div>';
    }

    private static function phone(): string {
        $configured = class_exists('Keleva_Dashboard_Settings') ? Keleva_Dashboard_Settings::get('KELEVA_WHATSAPP_NUMBER') : '';
        return (string) apply_filters('keleva_whatsapp_phone', preg_replace('/\\D+/', '', $configured));
    }

    private static function send_webhook_once(string $event, array $payload, ?WC_Order $order = null): void {
        if ($order instanceof WC_Order && $order->get_meta('_keleva_whatsapp_webhook_dispatched', true)) {
            return;
        }
        $sent = self::send_webhook($event, $payload);
        if ($sent && $order instanceof WC_Order) {
            $order->update_meta_data('_keleva_whatsapp_webhook_dispatched', gmdate('c'));
            $order->save();
        }
    }

    private static function send_webhook(string $event, array $payload): bool {
        $url = class_exists('Keleva_Dashboard_Settings') ? Keleva_Dashboard_Settings::get('KELEVA_WHATSAPP_WEBHOOK_URL') : '';
        $secret = class_exists('Keleva_Dashboard_Settings') ? Keleva_Dashboard_Settings::get('KELEVA_WHATSAPP_WEBHOOK_SECRET') : '';
        if ('' === $url || '' === $secret || !function_exists('wp_remote_post')) {
            return false;
        }
        $body = wp_json_encode(['event' => $event, 'sent_at' => gmdate('c'), 'data' => $payload]);
        $signature = hash_hmac('sha256', (string) $body, $secret);
        $result = wp_remote_post($url, [
            'timeout' => 1,
            'blocking' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Keleva-Event' => $event,
                'X-Keleva-Signature' => 'sha256=' . $signature,
            ],
            'body' => $body,
        ]);
        return !is_wp_error($result);
    }

    private static function ensure_cart(): void {
        if (function_exists('wc_load_cart')) {
            wc_load_cart();
        }
        if (!function_exists('WC') || !WC()) {
            return;
        }
        if (!WC()->session && method_exists(WC(), 'initialize_session')) {
            WC()->initialize_session();
        }
        if (!WC()->cart && method_exists(WC(), 'initialize_cart')) {
            WC()->initialize_cart();
        }
        if (WC()->cart && method_exists(WC()->cart, 'get_cart_from_session')) {
            WC()->cart->get_cart_from_session();
        }
    }

    private static function cart_fingerprint(): string {
        $parts = [];
        foreach (WC()->cart?->get_cart() ?? [] as $item) {
            $parts[] = implode(':', [(int) ($item['product_id'] ?? 0), (int) ($item['variation_id'] ?? 0), (int) ($item['quantity'] ?? 0), md5(wp_json_encode(self::safe_cart_meta($item)))]);
        }
        return hash('sha256', implode('|', $parts));
    }

    private static function safe_cart_meta(array $cart_item): array {
        $meta = [];
        foreach ($cart_item as $key => $value) {
            if (in_array($key, ['data', 'product_id', 'variation_id', 'variation', 'quantity', 'key', 'line_tax', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax_data'], true)) {
                continue;
            }
            if (is_scalar($value) && '' !== (string) $value) {
                $meta[sanitize_text_field((string) $key)] = sanitize_text_field((string) $value);
            } elseif (is_array($value) && $value) {
                $meta[sanitize_text_field((string) $key)] = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }
        return $meta;
    }

    private static function order_response(WC_Order $order): array {
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => (float) $item->get_total(),
            ];
        }
        $total_label = number_format_i18n((float) $order->get_total(), 2) . ' ' . $order->get_currency();
        $message = sprintf("Bonjour, je souhaite confirmer la commande WooCommerce #%d.\n\n%s\nTotal : %s\nPaiement : à la livraison.\n\nMerci de me demander mon nom, ma localisation et ma date de livraison.", $order->get_id(), self::format_items($items), $total_label);
        return [
            'order_id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => (float) $order->get_total(),
            'currency' => $order->get_currency(),
            'items' => $items,
            'whatsapp_url' => 'https://wa.me/' . rawurlencode(self::phone()) . '?text=' . rawurlencode($message),
            'webhook_event' => 'keleva.whatsapp.order.created',
        ];
    }

    private static function format_items(array $items): string {
        $lines = [];
        foreach ($items as $item) {
            $lines[] = sprintf('- %s × %d', $item['name'], $item['quantity']);
        }
        return implode("\n", $lines);
    }
}
