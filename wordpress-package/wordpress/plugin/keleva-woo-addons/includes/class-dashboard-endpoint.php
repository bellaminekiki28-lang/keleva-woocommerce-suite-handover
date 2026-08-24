<?php
defined('ABSPATH') || exit;

final class Keleva_Dashboard_Endpoint {
    private const TOKEN_HEADER = 'x_keleva_dashboard_key';
    private const LEGACY_TOKEN_HEADER = 'x_keleva_sandbox_key';
    private const SESSION_COOKIE = 'keleva_merchant_session';
    private const CSRF_COOKIE = 'keleva_merchant_csrf';
    private const SESSION_PREFIX = 'keleva_dashboard_session_';
    private const SESSION_TTL = 28800;

    public static function register_routes(): void {
        register_rest_route('keleva-dashboard/v1', '/session/login', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'session_login'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('keleva-dashboard/v1', '/session', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'session'],
            'permission_callback' => [self::class, 'authorize_session'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/session/logout', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'session_logout'],
            'permission_callback' => [self::class, 'authorize_session'],
        ]);

        register_rest_route('keleva-dashboard/v1', '/summary', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'summary'],
            'permission_callback' => [self::class, 'authorize'],
        ]);

        register_rest_route('keleva-dashboard/v1', '/audit', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'audit'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/orders', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'orders'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/orders/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'order'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/orders/(?P<id>\d+)/status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_order_status'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/coupons', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'coupons'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/coupons', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'create_coupon'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/coupons/(?P<id>\d+)', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_coupon'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/coupons/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [self::class, 'delete_coupon'],
            'permission_callback' => [self::class, 'authorize'],
        ]);

        register_rest_route('keleva-dashboard/v1', '/categories', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'categories'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'create_category'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'category'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_category'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [self::class, 'delete_category'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories/order', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'order_categories'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories/(?P<id>\d+)/products', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'move_category_products'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/categories/(?P<id>\d+)/image', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'upload_category_cover'],
            'permission_callback' => [self::class, 'authorize'],
        ]);

        register_rest_route('keleva-dashboard/v1', '/products', ['methods' => WP_REST_Server::CREATABLE, 'callback' => [self::class, 'create_product'], 'permission_callback' => [self::class, 'authorize']]);
        register_rest_route('keleva-dashboard/v1', '/products/(?P<id>\d+)', ['methods' => WP_REST_Server::READABLE, 'callback' => [self::class, 'product'], 'permission_callback' => [self::class, 'authorize']]);
        register_rest_route('keleva-dashboard/v1', '/products/(?P<id>\d+)', ['methods' => WP_REST_Server::CREATABLE, 'callback' => [self::class, 'update_product'], 'permission_callback' => [self::class, 'authorize']]);

        register_rest_route('keleva-dashboard/v1', '/products/(?P<id>\d+)/status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_product_status'],
            'permission_callback' => [self::class, 'authorize'],
            'args' => [
                'status' => [
                    'required' => true,
                    'validate_callback' => static fn ($status): bool => in_array($status, ['publish', 'draft'], true),
                ],
            ],
        ]);

        register_rest_route('keleva-dashboard/v1', '/products/(?P<id>\d+)/image', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'upload_product_image'],
            'permission_callback' => [self::class, 'authorize'],
        ]);

        register_rest_route('keleva-dashboard/v1', '/products/(?P<id>\d+)/configuration', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'configuration'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/products/(?P<id>\d+)/configuration', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_configuration'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/appearance/palettes', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [self::class, 'appearance_palettes'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/appearance/palette', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'update_appearance_palette'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
        register_rest_route('keleva-dashboard/v1', '/appearance/palette', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [self::class, 'reset_appearance_palette'],
            'permission_callback' => [self::class, 'authorize'],
        ]);
    }

    private static function config_value(string $name): string {
        if (defined($name)) {
            $value = constant($name);
            if (is_string($value) && '' !== $value) {
                return $value;
            }
        }

        $value = getenv($name);
        if (is_string($value) && '' !== $value) {
            return $value;
        }

        return class_exists('Keleva_Dashboard_Settings') ? Keleva_Dashboard_Settings::get($name) : '';
    }

    private static function valid_tokens(): array {
        return array_values(array_unique(array_filter([
            self::config_value('KELEVA_DASHBOARD_TOKEN'),
            self::config_value('KELEVA_DASHBOARD_PREVIOUS_TOKEN'),
        ], static fn (string $token): bool => '' !== $token)));
    }

    public static function authorize(WP_REST_Request $request): bool|WP_Error {
        if (true === self::authorize_session($request)) {
            return true;
        }

        $provided = (string) $request->get_header(self::TOKEN_HEADER);
        if ('' === $provided) {
            $provided = (string) $request->get_header(self::LEGACY_TOKEN_HEADER);
        }
        foreach (self::valid_tokens() as $token) {
            if ($provided && hash_equals($token, $provided)) {
                return true;
            }
        }

        return new WP_Error('keleva_dashboard_forbidden', __('Accès dashboard refusé.', 'keleva-woo-addons'), ['status' => 403]);
    }

    /**
     * Validates the native console session. A browser never needs to retain the
     * dashboard integration key after login: only this opaque, HTTP-only cookie
     * is sent on same-origin requests.
     */
    public static function authorize_session(WP_REST_Request $request): bool|WP_Error {
        $session = isset($_COOKIE[self::SESSION_COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::SESSION_COOKIE])) : '';
        if ('' === $session) {
            return new WP_Error('keleva_dashboard_session_required', __('Session marchande requise.', 'keleva-woo-addons'), ['status' => 401]);
        }

        $data = get_transient(self::SESSION_PREFIX . hash('sha256', $session));
        if (!is_array($data) || empty($data['csrf_hash']) || empty($data['expires']) || (int) $data['expires'] < time()) {
            self::expire_session_cookies();
            return new WP_Error('keleva_dashboard_session_expired', __('Votre session a expiré. Connectez-vous à nouveau.', 'keleva-woo-addons'), ['status' => 401]);
        }

        if (!in_array($request->get_method(), [WP_REST_Server::READABLE, 'HEAD'], true)) {
            $origin = rtrim((string) $request->get_header('origin'), '/');
            $site_origin = rtrim(home_url(), '/');
            if ('' !== $origin && !hash_equals($site_origin, $origin)) {
                return new WP_Error('keleva_dashboard_origin_invalid', __('Origine de requête refusée.', 'keleva-woo-addons'), ['status' => 403]);
            }

            $csrf = (string) $request->get_header('x_keleva_csrf');
            if ('' === $csrf || !hash_equals((string) $data['csrf_hash'], hash('sha256', $csrf))) {
                return new WP_Error('keleva_dashboard_csrf_invalid', __('Vérification de sécurité expirée. Rechargez la console.', 'keleva-woo-addons'), ['status' => 403]);
            }
        }

        return true;
    }

    public static function session_login(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $email = sanitize_email((string) $request->get_param('email'));
        $password = trim((string) $request->get_param('password'));
        $valid = false;

        $merchant_email = sanitize_email(self::config_value('KELEVA_DASHBOARD_MERCHANT_EMAIL'));
        $merchant_password_hash = self::config_value('KELEVA_DASHBOARD_MERCHANT_PASSWORD_HASH');
        if ('' !== $merchant_email && '' !== $merchant_password_hash) {
            $valid = '' !== $email
                && '' !== $password
                && hash_equals($merchant_email, $email)
                && password_verify($password, $merchant_password_hash);
        } else {
            foreach (self::valid_tokens() as $token) {
                if ('' !== $password && hash_equals($token, $password)) {
                    $valid = true;
                    break;
                }
            }
        }
        if (!$valid) {
            return new WP_Error('keleva_dashboard_login_failed', __('Identifiants marchand incorrects.', 'keleva-woo-addons'), ['status' => 401]);
        }

        $session = bin2hex(random_bytes(32));
        $csrf = bin2hex(random_bytes(24));
        $expires = time() + self::SESSION_TTL;
        set_transient(self::SESSION_PREFIX . hash('sha256', $session), [
            'csrf_hash' => hash('sha256', $csrf),
            'expires' => $expires,
        ], self::SESSION_TTL);
        self::set_session_cookies($session, $csrf, $expires);
        self::record_audit('merchant_session_started', ['source' => 'native_console']);

        return self::private_response(['authenticated' => true, 'expires_at' => gmdate(DATE_ATOM, $expires)]);
    }

    public static function session(WP_REST_Request $request): WP_REST_Response {
        return self::private_response(['authenticated' => true, 'expires_at' => gmdate(DATE_ATOM, time() + self::SESSION_TTL)]);
    }

    public static function session_logout(WP_REST_Request $request): WP_REST_Response {
        $session = isset($_COOKIE[self::SESSION_COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::SESSION_COOKIE])) : '';
        if ('' !== $session) {
            delete_transient(self::SESSION_PREFIX . hash('sha256', $session));
        }
        self::expire_session_cookies();
        self::record_audit('merchant_session_ended', ['source' => 'native_console']);
        return self::private_response(['logged_out' => true]);
    }

    private static function set_session_cookies(string $session, string $csrf, int $expires): void {
        $options = [
            'expires' => $expires,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'samesite' => 'Lax',
        ];
        setcookie(self::SESSION_COOKIE, $session, $options + ['httponly' => true]);
        setcookie(self::CSRF_COOKIE, $csrf, $options + ['httponly' => false]);
        $_COOKIE[self::SESSION_COOKIE] = $session;
        $_COOKIE[self::CSRF_COOKIE] = $csrf;
    }

    private static function expire_session_cookies(): void {
        $options = [
            'expires' => time() - HOUR_IN_SECONDS,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'samesite' => 'Lax',
        ];
        setcookie(self::SESSION_COOKIE, '', $options + ['httponly' => true]);
        setcookie(self::CSRF_COOKIE, '', $options + ['httponly' => false]);
        unset($_COOKIE[self::SESSION_COOKIE], $_COOKIE[self::CSRF_COOKIE]);
    }

    private static function private_response(array $payload): WP_REST_Response {
        $response = new WP_REST_Response($payload);
        $response->header('Cache-Control', 'no-store');
        $response->header('Vary', 'Cookie');
        return $response;
    }

    private static function record_audit(string $event, array $context): void {
        Keleva_Dashboard_Audit_Log::record($event, $context);
    }

    private static function dispatch_webhook(string $event, array $payload): void {
        $url = self::config_value('KELEVA_DASHBOARD_WEBHOOK_URL');
        $secret = self::config_value('KELEVA_DASHBOARD_WEBHOOK_SECRET');
        if ('' === $url || '' === $secret || 'https' !== wp_parse_url($url, PHP_URL_SCHEME) || !wp_http_validate_url($url)) {
            return;
        }

        $occurred_at = gmdate(DATE_ATOM);
        $body = wp_json_encode(['event' => $event, 'occurred_at' => $occurred_at, 'data' => $payload]);
        wp_remote_post($url, [
            'timeout' => 5,
            'blocking' => false,
            'redirection' => 0,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Keleva-Signature' => 'sha256=' . hash_hmac('sha256', $body, $secret),
                'X-Keleva-Event' => $event,
                'X-Keleva-Occurred-At' => $occurred_at,
            ],
            'body' => $body,
        ]);
    }

    public static function summary(WP_REST_Request $request): WP_REST_Response {
        $page = max(1, absint($request->get_param('page') ?: 1));
        $per_page = min(48, max(12, absint($request->get_param('per_page') ?: 24)));
        $search = sanitize_text_field((string) $request->get_param('search'));
        $status = sanitize_key((string) $request->get_param('status'));
        $statuses = in_array($status, ['publish', 'draft'], true) ? [$status] : ['publish', 'draft'];
        $product_query = [
            'status' => $statuses,
            'limit' => $per_page,
            'page' => $page,
            'orderby' => 'modified',
            'order' => 'DESC',
            'return' => 'objects',
        ];
        if ('' !== $search) {
            $product_query['s'] = $search;
        }
        $products = wc_get_products($product_query);
        $count_query = ['status' => $statuses, 'limit' => -1, 'return' => 'ids'];
        if ('' !== $search) {
            $count_query['s'] = $search;
        }
        $matching_product_ids = wc_get_products($count_query);

        $rows = array_map(static function (WC_Product $product): array {
            $image_id = $product->get_image_id();
            return [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'status' => $product->get_status(),
                'type' => $product->is_type('variable') ? 'variable' : 'simple',
                'stock_status' => $product->get_stock_status(),
                'stock_quantity' => $product->managing_stock() ? $product->get_stock_quantity() : null,
                'price' => $product->get_price(),
                'currency' => get_woocommerce_currency(),
                'category' => wp_strip_all_tags(wc_get_product_category_list($product->get_id(), ', ')),
                'image' => $image_id ? wp_get_attachment_image_url(absint($image_id), 'thumbnail') : null,
                'modified' => $product->get_date_modified()?->date(DATE_ATOM),
            ];
        }, $products);

        $product_counts = wp_count_posts('product');
        $published = (int) ($product_counts->publish ?? 0);
        $drafts = (int) ($product_counts->draft ?? 0);
        $out_of_stock = count(wc_get_products(['status' => ['publish', 'draft'], 'stock_status' => 'outofstock', 'limit' => -1, 'return' => 'ids']));
        $orders = wc_get_orders(['limit' => 100, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects']);
        $paid_statuses = ['processing', 'completed'];
        $paid_orders = array_filter($orders, static fn (WC_Order $order): bool => in_array($order->get_status(), $paid_statuses, true));
        $revenue = array_reduce($paid_orders, static fn (float $total, WC_Order $order): float => $total + (float) $order->get_total(), 0.0);
        $today = wp_date('Y-m-d');
        $orders_today = count(array_filter($orders, static fn (WC_Order $order): bool => $order->get_date_created() && $order->get_date_created()->date('Y-m-d') === $today));
        $week_start = wp_date('Y-m-d', strtotime('-6 days'));
        $revenue_week = array_reduce(array_filter($paid_orders, static fn (WC_Order $order): bool => $order->get_date_created() && $order->get_date_created()->date('Y-m-d') >= $week_start), static fn (float $total, WC_Order $order): float => $total + (float) $order->get_total(), 0.0);
        $awaiting_orders = count(array_filter($orders, static fn (WC_Order $order): bool => in_array($order->get_status(), ['pending', 'on-hold'], true)));
        $top_products = [];
        foreach ($paid_orders as $paid_order) {
            foreach ($paid_order->get_items('line_item') as $item) {
                if (!$item instanceof WC_Order_Item_Product) continue;
                $name = $item->get_name();
                $top_products[$name] = ($top_products[$name] ?? 0) + $item->get_quantity();
            }
        }
        arsort($top_products);

        return self::private_response([
            'mode' => self::config_value('KELEVA_DASHBOARD_TOKEN') ? 'production-configured' : 'configuration-required',
            'metrics' => [
                'products_total' => $published + $drafts,
                'products_published' => $published,
                'products_draft' => $drafts,
                'out_of_stock' => $out_of_stock,
                'orders_total' => count($orders),
                'orders_today' => $orders_today,
                'revenue_paid' => wc_format_decimal($revenue, wc_get_price_decimals()),
                'revenue_week' => wc_format_decimal($revenue_week, wc_get_price_decimals()),
                'orders_awaiting' => $awaiting_orders,
                'top_products' => array_map(static fn (string $name, int $quantity): array => ['name' => $name, 'quantity' => $quantity], array_keys(array_slice($top_products, 0, 3, true)), array_values(array_slice($top_products, 0, 3, true))),
                'currency' => get_woocommerce_currency(),
            ],
            'products' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => count($matching_product_ids),
                'pages' => max(1, (int) ceil(count($matching_product_ids) / $per_page)),
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    private static function order_payload(WC_Order $order): array {
        $items = array_values(array_map(static fn (WC_Order_Item_Product $item): array => [
            'name' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'total' => $item->get_total(),
            'options' => array_values(array_filter(array_map(static fn ($meta): array => ['label' => wp_strip_all_tags($meta->display_key ?? ''), 'value' => wp_strip_all_tags($meta->display_value ?? '')], $item->get_formatted_meta_data('')), static fn (array $meta): bool => '' !== $meta['label'] && '' !== $meta['value'])),
        ], array_filter($order->get_items('line_item'), static fn ($item): bool => $item instanceof WC_Order_Item_Product)));
        return [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'status_label' => wc_get_order_status_name('wc-' . $order->get_status()),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'items_count' => $order->get_item_count(),
            'items' => $items,
            'customer' => trim($order->get_formatted_billing_full_name()) ?: __('Client invité', 'keleva-woo-addons'),
            'shipping_address' => wp_strip_all_tags($order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address()),
            'shipping_method' => $order->get_shipping_method(),
            'customer_note' => $order->get_customer_note(),
            'created_at' => $order->get_date_created()?->date(DATE_ATOM),
            'payment_method' => $order->get_payment_method_title(),
        ];
    }

    public static function orders(WP_REST_Request $request): WP_REST_Response {
        $limit = min(100, max(1, absint($request->get_param('limit') ?: 30)));
        $status = sanitize_key((string) $request->get_param('status'));
        $args = ['limit' => $limit, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects'];
        if ('' !== $status && 'all' !== $status) {
            $args['status'] = str_starts_with($status, 'wc-') ? substr($status, 3) : $status;
        }
        $orders = wc_get_orders($args);
        return self::private_response(['orders' => array_map([self::class, 'order_payload'], $orders), 'statuses' => wc_get_order_statuses()]);
    }

    public static function order(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $order = wc_get_order((int) $request['id']);
        if (!$order) {
            return new WP_Error('keleva_order_not_found', __('Commande introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        return self::private_response(['order' => self::order_payload($order)]);
    }

    public static function update_order_status(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $order = wc_get_order((int) $request['id']);
        if (!$order) {
            return new WP_Error('keleva_order_not_found', __('Commande introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        $body = $request->get_json_params();
        $status = sanitize_key((string) (is_array($body) ? ($body['status'] ?? '') : $request->get_param('status')));
        $status = str_starts_with($status, 'wc-') ? substr($status, 3) : $status;
        if (!array_key_exists('wc-' . $status, wc_get_order_statuses())) {
            return new WP_Error('keleva_order_status_invalid', __('Statut de commande invalide.', 'keleva-woo-addons'), ['status' => 422]);
        }
        $from = $order->get_status();
        $order->update_status($status, __('Statut mis à jour depuis Keleva Merchant.', 'keleva-woo-addons'), true);
        self::record_audit('order_status_updated', ['order_id' => $order->get_id(), 'from' => $from, 'to' => $status]);
        self::dispatch_webhook('order.status_updated', ['order_id' => $order->get_id(), 'from' => $from, 'to' => $status]);
        return self::private_response(['order' => self::order_payload($order)]);
    }

    private static function coupon_payload(WC_Coupon $coupon): array {
        return [
            'id' => $coupon->get_id(),
            'code' => $coupon->get_code(),
            'discount_type' => $coupon->get_discount_type(),
            'amount' => $coupon->get_amount(),
            'free_shipping' => $coupon->get_free_shipping(),
            'individual_use' => $coupon->get_individual_use(),
            'usage_count' => $coupon->get_usage_count(),
            'usage_limit' => $coupon->get_usage_limit(),
            'date_expires' => $coupon->get_date_expires()?->date(DATE_ATOM),
        ];
    }

    private static function coupon_input(WP_REST_Request $request, bool $creating): array|WP_Error {
        $body = $request->get_json_params();
        if (!is_array($body)) $body = $request->get_params();
        $input = [];
        if (array_key_exists('code', $body)) {
            $input['code'] = wc_format_coupon_code((string) $body['code']);
            if ('' === $input['code']) return new WP_Error('keleva_coupon_code_invalid', __('Le code promotionnel est requis.', 'keleva-woo-addons'), ['status' => 422]);
        }
        if ($creating && empty($input['code'])) return new WP_Error('keleva_coupon_code_required', __('Le code promotionnel est requis.', 'keleva-woo-addons'), ['status' => 422]);
        if (array_key_exists('discount_type', $body)) {
            $input['discount_type'] = sanitize_key((string) $body['discount_type']);
            if (!in_array($input['discount_type'], ['percent', 'fixed_cart', 'fixed_product'], true)) return new WP_Error('keleva_coupon_type_invalid', __('Type de remise invalide.', 'keleva-woo-addons'), ['status' => 422]);
        }
        if (array_key_exists('amount', $body)) {
            $input['amount'] = wc_format_decimal((string) $body['amount']);
            if ((float) $input['amount'] <= 0) return new WP_Error('keleva_coupon_amount_invalid', __('Le montant doit être supérieur à zéro.', 'keleva-woo-addons'), ['status' => 422]);
        }
        foreach (['free_shipping', 'individual_use'] as $field) if (array_key_exists($field, $body)) $input[$field] = rest_sanitize_boolean($body[$field]);
        if (array_key_exists('usage_limit', $body)) $input['usage_limit'] = max(0, absint($body['usage_limit']));
        if (array_key_exists('date_expires', $body)) {
            $date = wc_string_to_datetime((string) $body['date_expires']);
            if (!$date) return new WP_Error('keleva_coupon_expiry_invalid', __('Date d’expiration invalide.', 'keleva-woo-addons'), ['status' => 422]);
            $input['date_expires'] = $date;
        }
        return $input;
    }

    private static function apply_coupon(WC_Coupon $coupon, array $input): void {
        if (isset($input['code'])) $coupon->set_code($input['code']);
        if (isset($input['discount_type'])) $coupon->set_discount_type($input['discount_type']);
        if (isset($input['amount'])) $coupon->set_amount($input['amount']);
        if (array_key_exists('free_shipping', $input)) $coupon->set_free_shipping($input['free_shipping']);
        if (array_key_exists('individual_use', $input)) $coupon->set_individual_use($input['individual_use']);
        if (array_key_exists('usage_limit', $input)) $coupon->set_usage_limit($input['usage_limit'] ?: null);
        if (array_key_exists('date_expires', $input)) $coupon->set_date_expires($input['date_expires']);
    }

    public static function coupons(): WP_REST_Response {
        $query = new WP_Query([
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);
        $coupons = array_values(array_filter(array_map(static fn (int $id): WC_Coupon => new WC_Coupon($id), $query->posts), static fn (WC_Coupon $coupon): bool => (bool) $coupon->get_id()));
        return self::private_response(['coupons' => array_map([self::class, 'coupon_payload'], $coupons)]);
    }

    public static function create_coupon(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $input = self::coupon_input($request, true);
        if (is_wp_error($input)) return $input;
        $coupon = new WC_Coupon();
        self::apply_coupon($coupon, $input + ['discount_type' => 'percent']);
        try { $coupon->save(); } catch (Throwable $exception) { return new WP_Error('keleva_coupon_create_failed', $exception->getMessage(), ['status' => 422]); }
        self::record_audit('coupon_created', ['coupon_id' => $coupon->get_id(), 'code' => $coupon->get_code()]);
        return self::private_response(['coupon' => self::coupon_payload($coupon)]);
    }

    public static function update_coupon(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $coupon = new WC_Coupon((int) $request['id']);
        if (!$coupon->get_id()) return new WP_Error('keleva_coupon_not_found', __('Code de réduction introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        $input = self::coupon_input($request, false);
        if (is_wp_error($input)) return $input;
        if (!$input) return new WP_Error('keleva_coupon_empty_update', __('Aucune modification reçue.', 'keleva-woo-addons'), ['status' => 422]);
        try { self::apply_coupon($coupon, $input); $coupon->save(); } catch (Throwable $exception) { return new WP_Error('keleva_coupon_update_failed', $exception->getMessage(), ['status' => 422]); }
        self::record_audit('coupon_updated', ['coupon_id' => $coupon->get_id(), 'code' => $coupon->get_code()]);
        return self::private_response(['coupon' => self::coupon_payload($coupon)]);
    }

    public static function delete_coupon(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $coupon = new WC_Coupon((int) $request['id']);
        if (!$coupon->get_id()) return new WP_Error('keleva_coupon_not_found', __('Code de réduction introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        $payload = self::coupon_payload($coupon);
        wp_delete_post($coupon->get_id(), true);
        self::record_audit('coupon_deleted', ['coupon_id' => $payload['id'], 'code' => $payload['code']]);
        return self::private_response(['deleted' => true, 'id' => $payload['id']]);
    }

    public static function audit(): WP_REST_Response {
        return self::private_response(['events' => Keleva_Dashboard_Audit_Log::recent()]);
    }

    public static function categories(): WP_REST_Response {
        return self::private_response(['categories' => Keleva_Category_Service::list()]);
    }

    private static function category_input(WP_REST_Request $request, bool $creating): array|WP_Error {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            $body = $request->get_params();
        }
        $input = [];
        if (array_key_exists('name', $body)) {
            $input['name'] = sanitize_text_field((string) $body['name']);
            if ('' === $input['name']) {
                return new WP_Error('keleva_category_name_invalid', __('Le nom de catégorie est requis.', 'keleva-woo-addons'), ['status' => 422]);
            }
        }
        if ($creating && empty($input['name'])) {
            return new WP_Error('keleva_category_name_required', __('Le nom de catégorie est requis.', 'keleva-woo-addons'), ['status' => 422]);
        }
        if (array_key_exists('slug', $body)) {
            $input['slug'] = sanitize_title((string) $body['slug']);
        }
        if (array_key_exists('description', $body)) {
            $input['description'] = wp_kses_post((string) $body['description']);
        }
        if (array_key_exists('visible', $body)) {
            $input['visible'] = rest_sanitize_boolean($body['visible']);
        }
        if (array_key_exists('order', $body)) {
            if (!is_numeric($body['order'])) {
                return new WP_Error('keleva_category_order_invalid', __('L’ordre doit être un entier positif.', 'keleva-woo-addons'), ['status' => 422]);
            }
            $input['order'] = max(0, (int) $body['order']);
        }
        if (array_key_exists('cover_id', $body)) {
            $input['cover_id'] = absint($body['cover_id']);
        }
        if (array_key_exists('option_templates', $body)) {
            if (!is_array($body['option_templates']) || count($body['option_templates']) > 8) {
                return new WP_Error('keleva_category_templates_invalid', __('Les modèles d’options sont invalides.', 'keleva-woo-addons'), ['status' => 422]);
            }
            $input['option_templates'] = $body['option_templates'];
        }
        return $input;
    }

    public static function create_category(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $input = self::category_input($request, true);
        if (is_wp_error($input)) {
            return $input;
        }
        $term = Keleva_Category_Service::create($input);
        if (is_wp_error($term)) {
            return new WP_Error('keleva_category_create_failed', $term->get_error_message(), ['status' => 422]);
        }
        self::record_audit('category_created', ['category_id' => $term->term_id]);
        return self::private_response(Keleva_Category_Service::payload($term));
    }

    public static function category(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $term = Keleva_Category_Service::find((int) $request['id']);
        if (!$term) {
            return new WP_Error('keleva_category_not_found', __('Catégorie introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        return self::private_response(Keleva_Category_Service::payload($term));
    }

    public static function update_category(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $term = Keleva_Category_Service::find((int) $request['id']);
        if (!$term) {
            return new WP_Error('keleva_category_not_found', __('Catégorie introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        $input = self::category_input($request, false);
        if (is_wp_error($input)) {
            return $input;
        }
        if (!$input) {
            return new WP_Error('keleva_category_empty_update', __('Aucune modification de catégorie reçue.', 'keleva-woo-addons'), ['status' => 422]);
        }
        $updated = Keleva_Category_Service::save($term, $input);
        if (is_wp_error($updated)) {
            return new WP_Error('keleva_category_update_failed', $updated->get_error_message(), ['status' => 422]);
        }
        self::record_audit('category_updated', ['category_id' => $term->term_id]);
        return self::private_response(Keleva_Category_Service::payload($updated));
    }

    public static function delete_category(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $term = Keleva_Category_Service::find((int) $request['id']);
        if (!$term) {
            return new WP_Error('keleva_category_not_found', __('Catégorie introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        if ((int) get_option('default_product_cat') === $term->term_id) {
            return new WP_Error('keleva_category_default_protected', __('La catégorie produit par défaut ne peut pas être supprimée.', 'keleva-woo-addons'), ['status' => 422]);
        }
        $assigned_product_ids = get_objects_in_term($term->term_id, 'product_cat');
        if (is_wp_error($assigned_product_ids)) {
            return new WP_Error('keleva_category_products_lookup_failed', __('Les produits de cette catégorie ne peuvent pas être vérifiés.', 'keleva-woo-addons'), ['status' => 500]);
        }
        $assigned_product_ids = array_values(array_filter(array_map('absint', (array) $assigned_product_ids)));
        if ($assigned_product_ids) {
            return new WP_Error(
                'keleva_category_not_empty',
                sprintf(
                    /* translators: %d is the number of products assigned to the category. */
                    _n('Cette catégorie contient %d produit. Déplacez-le avant de supprimer la catégorie.', 'Cette catégorie contient %d produits. Déplacez-les avant de supprimer la catégorie.', count($assigned_product_ids), 'keleva-woo-addons'),
                    count($assigned_product_ids)
                ),
                ['status' => 409, 'product_ids' => $assigned_product_ids]
            );
        }
        $payload = Keleva_Category_Service::payload($term);
        $cover_id = absint($payload['cover']['id'] ?? 0);
        $deleted = wp_delete_term($term->term_id, 'product_cat');
        if (!$deleted || is_wp_error($deleted)) {
            return new WP_Error('keleva_category_delete_failed', __('La catégorie ne peut pas être supprimée.', 'keleva-woo-addons'), ['status' => 422]);
        }
        if ($cover_id && (int) get_post_meta($cover_id, '_keleva_category_cover_owner', true) === $term->term_id) {
            wp_delete_attachment($cover_id, true);
        }
        self::record_audit('category_deleted', ['category_id' => $term->term_id, 'name' => $term->name]);
        return self::private_response(['deleted' => true, 'id' => $term->term_id]);
    }

    public static function order_categories(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $body = $request->get_json_params();
        $ids = is_array($body) && isset($body['ids']) && is_array($body['ids']) ? array_slice(array_unique(array_map('absint', $body['ids'])), 0, 50) : [];
        if (!$ids) {
            return new WP_Error('keleva_category_order_empty', __('Indiquez au moins une catégorie à ordonner.', 'keleva-woo-addons'), ['status' => 422]);
        }
        foreach ($ids as $position => $term_id) {
            $term = Keleva_Category_Service::find($term_id);
            if (!$term) {
                return new WP_Error('keleva_category_not_found', __('Une catégorie à ordonner est introuvable.', 'keleva-woo-addons'), ['status' => 404]);
            }
            Keleva_Category_Service::save($term, ['order' => $position]);
        }
        self::record_audit('categories_reordered', ['category_ids' => $ids]);
        return self::private_response(['categories' => Keleva_Category_Service::list()]);
    }

    public static function move_category_products(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $term = Keleva_Category_Service::find((int) $request['id']);
        if (!$term) {
            return new WP_Error('keleva_category_not_found', __('Catégorie introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }
        $body = $request->get_json_params();
        $product_ids = is_array($body) && isset($body['product_ids']) && is_array($body['product_ids']) ? $body['product_ids'] : [];
        $mode = is_array($body) && 'append' === ($body['mode'] ?? '') ? 'append' : 'replace';
        if (!$product_ids) {
            return new WP_Error('keleva_category_products_empty', __('Sélectionnez au moins un produit à déplacer.', 'keleva-woo-addons'), ['status' => 422]);
        }
        $moved = Keleva_Category_Service::move_products($term, $product_ids, $mode);
        self::record_audit('category_products_moved', ['category_id' => $term->term_id, 'product_ids' => $moved, 'mode' => $mode]);
        return self::private_response(['category' => Keleva_Category_Service::payload($term), 'moved_product_ids' => $moved]);
    }

    public static function update_product_status(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $product = wc_get_product((int) $request['id']);
        if (!$product) {
            return new WP_Error('keleva_dashboard_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        }

        $before = $product->get_status();
        $after = (string) $request['status'];
        $product->set_status($after);
        $product->save();

        $event = ['product_id' => $product->get_id(), 'from' => $before, 'to' => $product->get_status()];
        self::record_audit('product_status_changed', $event);
        self::dispatch_webhook('product.status_changed', $event);

        return self::private_response(['id' => $product->get_id(), 'status' => $product->get_status()]);
    }

    public static function product(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $product = wc_get_product((int) $request['id']);
        if (!$product) return new WP_Error('keleva_dashboard_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        return self::private_response(self::product_detail($product));
    }

    private static function product_detail(WC_Product $product): array {
        $image_id = $product->get_image_id();
        return ['id' => $product->get_id(), 'name' => $product->get_name(), 'status' => $product->get_status(), 'type' => $product->is_type('variable') ? 'variable' : 'simple', 'description' => $product->get_description(), 'short_description' => $product->get_short_description(), 'regular_price' => $product->get_regular_price(), 'stock_status' => $product->get_stock_status(), 'stock_quantity' => $product->managing_stock() ? $product->get_stock_quantity() : null, 'category' => wp_strip_all_tags(wc_get_product_category_list($product->get_id(), ', ')), 'image' => $image_id ? wp_get_attachment_image_url(absint($image_id), 'thumbnail') : null, 'modified' => $product->get_date_modified()?->date(DATE_ATOM)];
    }

    private static function input(WP_REST_Request $request, bool $creating): array|WP_Error {
        $body = $request->get_json_params(); if (!is_array($body)) $body = []; $input = [];
        if (array_key_exists('name', $body)) { $input['name'] = sanitize_text_field((string) $body['name']); if ('' === $input['name']) return new WP_Error('keleva_dashboard_invalid_name', __('Le nom produit est requis.', 'keleva-woo-addons'), ['status' => 422]); }
        if (array_key_exists('description', $body)) $input['description'] = wp_kses_post((string) $body['description']);
        if (array_key_exists('short_description', $body)) $input['short_description'] = wp_kses_post((string) $body['short_description']);
        if (array_key_exists('category', $body)) $input['category'] = sanitize_text_field((string) $body['category']);
        if (array_key_exists('status', $body)) { $input['status'] = (string) $body['status']; if (!in_array($input['status'], ['publish', 'draft'], true)) return new WP_Error('keleva_dashboard_invalid_status', __('Statut produit invalide.', 'keleva-woo-addons'), ['status' => 422]); }
        if (array_key_exists('regular_price', $body)) { $price = wc_format_decimal((string) $body['regular_price']); if ('' === $price || !is_numeric($price) || (float) $price < 0) return new WP_Error('keleva_dashboard_invalid_price', __('Le prix doit être positif ou nul.', 'keleva-woo-addons'), ['status' => 422]); $input['regular_price'] = $price; }
        if (array_key_exists('stock_quantity', $body)) { if (!is_numeric($body['stock_quantity']) || (int) $body['stock_quantity'] < 0 || (float) $body['stock_quantity'] !== (float) (int) $body['stock_quantity']) return new WP_Error('keleva_dashboard_invalid_stock', __('Le stock doit être un entier positif ou nul.', 'keleva-woo-addons'), ['status' => 422]); $input['stock_quantity'] = (int) $body['stock_quantity']; }
        if ($creating && (!isset($input['name']) || !isset($input['regular_price']) || !isset($input['stock_quantity']))) return new WP_Error('keleva_dashboard_missing_fields', __('Nom, prix et stock sont requis.', 'keleva-woo-addons'), ['status' => 422]); return $input;
    }

    private static function apply(WC_Product $product, array $input): WP_Error|null {
        if (isset($input['name'])) $product->set_name($input['name']); if (array_key_exists('description', $input)) $product->set_description($input['description']); if (array_key_exists('short_description', $input)) $product->set_short_description($input['short_description']); if (isset($input['status'])) $product->set_status($input['status']);
        if (array_key_exists('regular_price', $input) || array_key_exists('stock_quantity', $input)) { if (!$product->is_type('simple')) return new WP_Error('keleva_dashboard_variable_restricted', __('Le prix et le stock d’un produit variable se gèrent depuis ses variations WooCommerce.', 'keleva-woo-addons'), ['status' => 422]); if (isset($input['regular_price'])) { $product->set_regular_price($input['regular_price']); $product->set_price($input['regular_price']); } if (array_key_exists('stock_quantity', $input)) { $product->set_manage_stock(true); $product->set_stock_quantity($input['stock_quantity']); $product->set_stock_status($input['stock_quantity'] > 0 ? 'instock' : 'outofstock'); } }
        if (array_key_exists('category', $input)) { $term = '' === $input['category'] ? 0 : term_exists($input['category'], 'product_cat'); if (!$term && '' !== $input['category']) $term = wp_insert_term($input['category'], 'product_cat'); if (is_wp_error($term)) return new WP_Error('keleva_dashboard_category_failed', __('La catégorie ne peut pas être enregistrée.', 'keleva-woo-addons'), ['status' => 422]); $product->set_category_ids($term ? [is_array($term) ? (int) $term['term_id'] : (int) $term] : []); } return null;
    }

    public static function create_product(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $input = self::input($request, true); if (is_wp_error($input)) return $input; $product = new WC_Product_Simple(); $error = self::apply($product, $input); if ($error) return $error; $id = $product->save(); $fresh = wc_get_product($id); if ($fresh instanceof WC_Product && array_key_exists('category', $input)) Keleva_Category_Service::inherit_template($fresh); $event = ['product_id' => $id, 'to' => $product->get_status(), 'source' => 'merchant_dashboard']; self::record_audit('product_created', $event); self::dispatch_webhook('product.created', $event); return self::private_response(self::product_detail(wc_get_product($id)));
    }

    public static function update_product(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $product = wc_get_product((int) $request['id']); if (!$product) return new WP_Error('keleva_dashboard_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]); $input = self::input($request, false); if (is_wp_error($input)) return $input; if ([] === $input) return new WP_Error('keleva_dashboard_empty_update', __('Aucune modification reçue.', 'keleva-woo-addons'), ['status' => 422]); $error = self::apply($product, $input); if ($error) return $error; $product->save(); $fresh = wc_get_product($product->get_id()); if ($fresh instanceof WC_Product && array_key_exists('category', $input) && 'custom' !== Keleva_Category_Service::source_for_product($fresh)['source']) Keleva_Category_Service::inherit_template($fresh); $event = ['product_id' => $product->get_id(), 'source' => 'merchant_dashboard']; self::record_audit('product_updated', $event); self::dispatch_webhook('product.updated', $event); return self::private_response(self::product_detail(wc_get_product($product->get_id())));
    }

    public static function upload_product_image(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $product = wc_get_product((int) $request['id']);
        if (!$product) return new WP_Error('keleva_dashboard_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        $files = $request->get_file_params();
        $file = $files['image'] ?? null;
        if (!is_array($file) || empty($file['tmp_name']) || !empty($file['error'])) return new WP_Error('keleva_dashboard_image_required', __('Choisissez une photo valide.', 'keleva-woo-addons'), ['status' => 422]);
        if ((int) ($file['size'] ?? 0) > 5 * MB_IN_BYTES) return new WP_Error('keleva_dashboard_image_too_large', __('La photo ne doit pas dépasser 5 Mo.', 'keleva-woo-addons'), ['status' => 422]);
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => ['jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif']]);
        if (!empty($upload['error']) || empty($upload['file']) || empty($upload['type'])) return new WP_Error('keleva_dashboard_image_failed', __('La photo ne peut pas être importée.', 'keleva-woo-addons'), ['status' => 422]);
        $attachment_id = wp_insert_attachment(['post_mime_type' => $upload['type'], 'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)), 'post_status' => 'inherit'], $upload['file'], $product->get_id());
        if (is_wp_error($attachment_id)) return new WP_Error('keleva_dashboard_image_failed', __('La photo ne peut pas être enregistrée.', 'keleva-woo-addons'), ['status' => 422]);
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if (is_array($metadata)) wp_update_attachment_metadata($attachment_id, $metadata);
        $product->set_image_id((int) $attachment_id);
        $product->save();
        $event = ['product_id' => $product->get_id(), 'attachment_id' => (int) $attachment_id, 'source' => 'merchant_dashboard'];
        self::record_audit('product_image_updated', $event);
        self::dispatch_webhook('product.image_updated', $event);
        return self::private_response(self::product_detail(wc_get_product($product->get_id())));
    }

    public static function upload_category_cover(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $term = Keleva_Category_Service::find((int) $request['id']);
        if (!$term) return new WP_Error('keleva_category_not_found', __('Catégorie introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        $previous = Keleva_Category_Service::payload($term);
        $previous_cover_id = absint($previous['cover']['id'] ?? 0);
        $files = $request->get_file_params();
        $file = $files['image'] ?? null;
        if (!is_array($file) || empty($file['tmp_name']) || !empty($file['error'])) return new WP_Error('keleva_category_cover_required', __('Choisissez une image de couverture valide.', 'keleva-woo-addons'), ['status' => 422]);
        if ((int) ($file['size'] ?? 0) > 5 * MB_IN_BYTES) return new WP_Error('keleva_category_cover_too_large', __('La couverture ne doit pas dépasser 5 Mo.', 'keleva-woo-addons'), ['status' => 422]);
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => ['jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif']]);
        if (!empty($upload['error']) || empty($upload['file']) || empty($upload['type'])) return new WP_Error('keleva_category_cover_failed', __('La couverture ne peut pas être importée.', 'keleva-woo-addons'), ['status' => 422]);
        $attachment_id = wp_insert_attachment(['post_mime_type' => $upload['type'], 'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)), 'post_status' => 'inherit'], $upload['file']);
        if (is_wp_error($attachment_id)) return new WP_Error('keleva_category_cover_failed', __('La couverture ne peut pas être enregistrée.', 'keleva-woo-addons'), ['status' => 422]);
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if (is_array($metadata)) wp_update_attachment_metadata($attachment_id, $metadata);
        update_post_meta($attachment_id, '_keleva_category_cover_owner', $term->term_id);
        $saved = Keleva_Category_Service::save($term, ['cover_id' => (int) $attachment_id]);
        if (is_wp_error($saved)) return new WP_Error('keleva_category_cover_failed', $saved->get_error_message(), ['status' => 422]);
        if ($previous_cover_id && (int) get_post_meta($previous_cover_id, '_keleva_category_cover_owner', true) === $term->term_id) {
            wp_delete_attachment($previous_cover_id, true);
        }
        self::record_audit('category_cover_updated', ['category_id' => $term->term_id, 'attachment_id' => (int) $attachment_id]);
        return self::private_response(Keleva_Category_Service::payload($saved));
    }

    public static function configuration(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $product = wc_get_product((int) $request['id']);
        if (!$product) return new WP_Error('keleva_dashboard_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        return self::private_response(self::configuration_payload($product));
    }

    private static function configuration_payload(WC_Product $product): array {
        $attributes = [];
        foreach ($product->get_attributes() as $attribute) {
            if (!$attribute instanceof WC_Product_Attribute) continue;
            $attributes[] = [
                'name' => $attribute->get_name(),
                'values' => array_values(array_map('strval', $attribute->get_options())),
                'variation' => $attribute->get_variation(),
            ];
        }
        $variations = [];
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation instanceof WC_Product_Variation) continue;
                $variations[] = [
                    'id' => $variation->get_id(),
                    'attributes' => $variation->get_attributes(),
                    'regular_price' => $variation->get_regular_price(),
                    'stock_quantity' => $variation->managing_stock() ? $variation->get_stock_quantity() : null,
                    'status' => $variation->get_status(),
                ];
            }
        }
        $source = Keleva_Category_Service::source_for_product($product);
        $source['source'] = match ($source['source'] ?? 'none') {
            'category' => 'category_default',
            'custom' => 'customized',
            default => 'none',
        };
        return ['product' => self::product_detail($product), 'attributes' => $attributes, 'variations' => $variations, 'option_groups' => Keleva_Product_Options::groups_for($product), 'options_source' => $source];
    }

    public static function update_configuration(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $product_id = (int) $request['id'];
        $product = wc_get_product($product_id);
        if (!$product) return new WP_Error('keleva_dashboard_product_not_found', __('Produit introuvable.', 'keleva-woo-addons'), ['status' => 404]);
        $body = $request->get_json_params();
        if (!is_array($body)) $body = [];
        $type = isset($body['type']) ? sanitize_key((string) $body['type']) : ($product->is_type('variable') ? 'variable' : 'simple');
        if (!in_array($type, ['simple', 'variable'], true)) return new WP_Error('keleva_dashboard_invalid_type', __('Type produit invalide.', 'keleva-woo-addons'), ['status' => 422]);
        if ('variable' === $type && !$product->is_type('variable')) {
            wp_set_object_terms($product_id, 'variable', 'product_type', false);
            wc_delete_product_transients($product_id);
            clean_post_cache($product_id);
            $product = new WC_Product_Variable($product_id);
        }
        if (isset($body['attributes'])) {
            if (!is_array($body['attributes']) || count($body['attributes']) > 4) return new WP_Error('keleva_dashboard_invalid_attributes', __('Attributs produit invalides.', 'keleva-woo-addons'), ['status' => 422]);
            $attributes = [];
            foreach ($body['attributes'] as $position => $item) {
                if (!is_array($item)) continue;
                $name = sanitize_text_field((string) ($item['name'] ?? ''));
                $values = array_values(array_filter(array_map('sanitize_text_field', (array) ($item['values'] ?? []))));
                if ('' === $name || !$values || count($values) > 12) continue;
                $attribute = new WC_Product_Attribute();
                $attribute->set_id(0);
                $attribute->set_name($name);
                $attribute->set_options($values);
                $attribute->set_position((int) $position);
                $attribute->set_visible(true);
                $attribute->set_variation(true);
                $attributes[sanitize_title($name)] = $attribute;
            }
            $product->set_attributes($attributes);
            $product->save();
        }
        if (isset($body['variations'])) {
            if (!$product->is_type('variable') || !is_array($body['variations']) || count($body['variations']) > 48) return new WP_Error('keleva_dashboard_invalid_variations', __('Variations produit invalides.', 'keleva-woo-addons'), ['status' => 422]);
            $existing_variation_ids = array_map('absint', $product->get_children());
            $kept_variation_ids = [];
            foreach ($body['variations'] as $item) {
                if (!is_array($item)) continue;
                $variation = !empty($item['id']) ? wc_get_product((int) $item['id']) : new WC_Product_Variation();
                if (!$variation instanceof WC_Product_Variation || ($variation->get_id() && $variation->get_parent_id() && $variation->get_parent_id() !== $product_id)) continue;
                $variation->set_parent_id($product_id);
                $attrs = [];
                foreach ((array) ($item['attributes'] ?? []) as $name => $value) {
                    $key = sanitize_title((string) $name);
                    $clean_value = sanitize_text_field((string) $value);
                    if ('' !== $key && '' !== $clean_value) $attrs[$key] = $clean_value;
                }
                if (!$attrs) continue;
                $variation->set_attributes($attrs);
                if (array_key_exists('regular_price', $item)) $variation->set_regular_price(wc_format_decimal((string) $item['regular_price']));
                if (array_key_exists('stock_quantity', $item) && is_numeric($item['stock_quantity'])) { $variation->set_manage_stock(true); $variation->set_stock_quantity(max(0, (int) $item['stock_quantity'])); $variation->set_stock_status((int) $item['stock_quantity'] > 0 ? 'instock' : 'outofstock'); }
                $variation->set_status('publish');
                $variation->save();
                $kept_variation_ids[] = (int) $variation->get_id();
            }
            if (!empty($body['replace_variations'])) {
                foreach (array_diff($existing_variation_ids, $kept_variation_ids) as $variation_id) {
                    wp_delete_post((int) $variation_id, true);
                }
            }
            $product = wc_get_product($product_id);
            if ($product instanceof WC_Product_Variable) { $product->sync($product); }
        }
        $inherit_category_templates = isset($body['options_source']) && 'category' === sanitize_key((string) $body['options_source']);
        if ($inherit_category_templates) {
            Keleva_Category_Service::inherit_template($product);
        } elseif (isset($body['option_groups'])) {
            if (!is_array($body['option_groups']) || count($body['option_groups']) > 8) return new WP_Error('keleva_dashboard_invalid_options', __('Groupes d’options invalides.', 'keleva-woo-addons'), ['status' => 422]);
            $groups = [];
            foreach ($body['option_groups'] as $group_index => $group) {
                if (!is_array($group)) continue;
                $label = sanitize_text_field((string) ($group['label'] ?? ''));
                $display = (string) ($group['display'] ?? 'buttons');
                if ('' === $label || !in_array($display, ['buttons', 'radio', 'checkbox'], true)) continue;
                $options = [];
                foreach (array_slice((array) ($group['options'] ?? []), 0, 12) as $option_index => $option) {
                    if (!is_array($option)) continue;
                    $option_label = sanitize_text_field((string) ($option['label'] ?? ''));
                    if ('' === $option_label) continue;
                    $option_id = sanitize_key((string) ($option['id'] ?? ('option-' . $option_index)));
                    if ('' === $option_id) continue;
                    $options[] = ['id' => $option_id, 'label' => $option_label, 'price' => max(0, (float) ($option['price'] ?? 0))];
                }
                if (!$options) continue;
                $max = min(4, min(count($options), max(1, (int) ($group['max'] ?? 1))));
                if ('radio' === $display) $max = 1;
                if ('buttons' === $display && $max > 1) $display = 'checkbox';
                $groups[] = ['id' => sanitize_key((string) ($group['id'] ?? ('group-' . $group_index))), 'label' => $label, 'display' => $display, 'max' => $max, 'required' => !empty($group['required']), 'options' => $options];
            }
            update_post_meta($product_id, '_keleva_product_option_groups', wp_slash(wp_json_encode($groups)));
            Keleva_Category_Service::mark_custom($product);
        }
        $event = ['product_id' => $product_id, 'source' => 'merchant_dashboard', 'configuration' => true];
        self::record_audit('product_configuration_updated', $event);
        self::dispatch_webhook('product.configuration_updated', $event);
        $fresh = wc_get_product($product_id);
        return self::private_response(self::configuration_payload($fresh));
    }

    /**
     * Expose only the declared theme palette tokens to the authenticated merchant.
     * The theme remains the source of truth; the dashboard never accepts raw colors.
     */
    public static function appearance_palettes(): WP_REST_Response|WP_Error {
        if (!function_exists('keleva_woo_palettes') || !function_exists('keleva_woo_active_palette_id')) {
            return new WP_Error('keleva_appearance_unavailable', __('Les palettes du thème Keleva ne sont pas disponibles.', 'keleva-woo-addons'), ['status' => 503]);
        }
        $palettes = [];
        foreach (keleva_woo_palettes() as $id => $palette) {
            $palettes[] = [
                'id' => $id,
                'label' => (string) ($palette['label'] ?? $id),
                'colors' => array_intersect_key($palette, array_flip(['bg', 'surface', 'surface_card', 'surface_media', 'ink', 'muted', 'subtle', 'line', 'accent', 'accent_strong', 'accent_deep', 'on_accent', 'on_ink', 'success', 'success_wash', 'warning', 'warning_wash', 'danger', 'danger_wash', 'media', 'benefit', 'shadow_tint'])),
            ];
        }
        return self::private_response(['active' => keleva_woo_active_palette_id(), 'palettes' => $palettes]);
    }

    public static function update_appearance_palette(WP_REST_Request $request): WP_REST_Response|WP_Error {
        if (!function_exists('keleva_woo_palettes') || !function_exists('keleva_woo_active_palette_id')) {
            return new WP_Error('keleva_appearance_unavailable', __('Les palettes du thème Keleva ne sont pas disponibles.', 'keleva-woo-addons'), ['status' => 503]);
        }
        $body = $request->get_json_params();
        $palette_id = sanitize_key((string) (is_array($body) ? ($body['palette'] ?? '') : $request->get_param('palette')));
        if (!array_key_exists($palette_id, keleva_woo_palettes())) {
            return new WP_Error('keleva_palette_invalid', __('Cette palette n’existe pas.', 'keleva-woo-addons'), ['status' => 422]);
        }
        $before = keleva_woo_active_palette_id();
        set_theme_mod('keleva_palette', $palette_id);
        self::record_audit('appearance_palette_updated', ['from' => $before, 'to' => $palette_id]);
        return self::appearance_palettes();
    }

    public static function reset_appearance_palette(): WP_REST_Response|WP_Error {
        if (!function_exists('keleva_woo_active_palette_id')) {
            return new WP_Error('keleva_appearance_unavailable', __('Les palettes du thème Keleva ne sont pas disponibles.', 'keleva-woo-addons'), ['status' => 503]);
        }
        $before = keleva_woo_active_palette_id();
        remove_theme_mod('keleva_palette');
        self::record_audit('appearance_palette_reset', ['from' => $before, 'to' => 'velora']);
        return self::appearance_palettes();
    }

}
