<?php
defined('ABSPATH') || exit;

/**
 * Applies a small, server-side REST budget to Keleva's own exposed routes.
 *
 * The bucket identifier stores only an HMAC digest of a validated remote
 * address (or a WordPress user ID), never the address itself. Host/CDN-level
 * rate limiting remains necessary for distributed abuse protection.
 */
final class Keleva_Rest_Rate_Limiter {
    private const WINDOW_SECONDS = 60;

    public static function boot(): void {
        add_filter('rest_pre_dispatch', [self::class, 'enforce'], 9, 3);
        add_filter('rest_post_dispatch', [self::class, 'append_headers'], 10, 3);
    }

    /**
     * @param mixed          $result Existing REST response, if any.
     * @param WP_REST_Server $server REST server instance.
     */
    public static function enforce($result, WP_REST_Server $server, WP_REST_Request $request) {
        if (null !== $result) {
            return $result;
        }

        $policy = self::policy_for_route($request->get_route());
        if (!$policy) {
            return $result;
        }

        [$scope, $limit] = $policy;
        $key = 'keleva_rl_' . hash_hmac('sha256', $scope . '|' . self::identity(), wp_salt('nonce'));
        $now = time();
        $bucket = get_transient($key);
        $bucket = is_array($bucket) ? $bucket : ['started_at' => $now, 'count' => 0];

        if (!isset($bucket['started_at'], $bucket['count']) || $now - (int) $bucket['started_at'] >= self::WINDOW_SECONDS) {
            $bucket = ['started_at' => $now, 'count' => 0];
        }

        $bucket['count'] = (int) $bucket['count'] + 1;
        $remaining_seconds = max(1, self::WINDOW_SECONDS - ($now - (int) $bucket['started_at']));
        set_transient($key, $bucket, $remaining_seconds);

        if ((int) $bucket['count'] <= $limit) {
            return $result;
        }

        $response = new WP_REST_Response([
            'code' => 'keleva_rate_limited',
            'message' => __('Trop de requêtes. Réessayez dans un instant.', 'keleva-woo-addons'),
        ], 429);
        $response->header('Retry-After', (string) $remaining_seconds);
        $response->header('Cache-Control', 'no-store');
        $response->header('X-RateLimit-Limit', (string) $limit);
        $response->header('X-RateLimit-Remaining', '0');

        return $response;
    }

    /**
     * @param WP_REST_Response|WP_Error|mixed $response Existing REST response.
     * @param WP_REST_Server                   $server REST server instance.
     */
    public static function append_headers($response, WP_REST_Server $server, WP_REST_Request $request) {
        $policy = self::policy_for_route($request->get_route());
        if (!$policy || !$response instanceof WP_REST_Response) {
            return $response;
        }

        [, $limit] = $policy;
        $headers = $response->get_headers();
        if (!isset($headers['X-RateLimit-Limit'])) {
            $response->header('X-RateLimit-Limit', (string) $limit);
        }

        return $response;
    }

    /**
     * @return array{string, int}|null
     */
    private static function policy_for_route(string $route): ?array {
        if ('/keleva-dashboard/v1/session/login' === $route) {
            return ['dashboard-login', 5];
        }

        if (0 === strpos($route, '/keleva-dashboard/v1/')) {
            return ['dashboard-api', 120];
        }

        if ('/keleva/v1/quick-view' === $route) {
            return ['quick-view', 60];
        }

        return null;
    }

    private static function identity(): string {
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            return 'user:' . $user_id;
        }

        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!filter_var($remote_addr, FILTER_VALIDATE_IP)) {
            $remote_addr = 'unknown';
        }

        return 'client:' . hash_hmac('sha256', $remote_addr, wp_salt('auth'));
    }
}
