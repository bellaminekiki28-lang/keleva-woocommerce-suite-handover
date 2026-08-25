<?php
defined('ABSPATH') || exit;

/**
 * Stores optional Keleva dashboard settings encrypted at rest in the WordPress database.
 * Environment variables and wp-config constants retain priority over these settings.
 */
final class Keleva_Dashboard_Settings {
    private const OPTION = 'keleva_dashboard_integration';
    private const CIPHER = 'aes-256-gcm';
    private const FIELDS = [
        'KELEVA_DASHBOARD_TOKEN' => 'Jeton dashboard actif',
        'KELEVA_DASHBOARD_PREVIOUS_TOKEN' => 'Jeton dashboard précédent',
        'KELEVA_DASHBOARD_WEBHOOK_URL' => 'URL HTTPS du webhook',
        'KELEVA_DASHBOARD_WEBHOOK_SECRET' => 'Secret de signature webhook dashboard',
        'KELEVA_WHATSAPP_NUMBER' => 'Numéro WhatsApp Business',
        'KELEVA_WHATSAPP_WEBHOOK_URL' => 'URL HTTPS du webhook WhatsApp / n8n',
        'KELEVA_WHATSAPP_WEBHOOK_SECRET' => 'Secret de signature webhook WhatsApp',
    ];

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'register_page']);
        add_action('admin_post_keleva_save_dashboard_settings', [self::class, 'save']);
        add_action('current_screen', [self::class, 'suppress_unrelated_admin_notices']);
    }

    /**
     * Keeps the Keleva integration screen focused on its own controls.
     * Third-party promotions and template notices remain available on their
     * respective WordPress screens and are not rendered on this page.
     *
     * @param WP_Screen $screen Current WordPress admin screen.
     */
    public static function suppress_unrelated_admin_notices($screen): void {
        if (!is_object($screen) || 'woocommerce_page_keleva-dashboard-settings' !== ($screen->id ?? '')) {
            return;
        }

        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }

    public static function get(string $field): string {
        if (!array_key_exists($field, self::FIELDS)) {
            return '';
        }

        $settings = get_option(self::OPTION, []);
        return is_array($settings) && isset($settings[$field]) ? self::decrypt((string) $settings[$field]) : '';
    }

    public static function register_page(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        add_submenu_page(
            'woocommerce',
            __('Keleva Dashboard', 'keleva-woo-addons'),
            __('Keleva Dashboard', 'keleva-woo-addons'),
            // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
            'manage_woocommerce',
            'keleva-dashboard-settings',
            [self::class, 'render']
        );
    }

    public static function render(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Accès refusé.', 'keleva-woo-addons'));
        }

        $configured = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $configured[$field] = '' !== self::get($field);
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Keleva Dashboard — intégration sécurisée', 'keleva-woo-addons'); ?></h1>
            <p><?php esc_html_e('Utilisez uniquement des secrets de test en préproduction. Les valeurs sont chiffrées dans WordPress et ne s’affichent jamais après enregistrement.', 'keleva-woo-addons'); ?></p>
            <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=keleva-manager')); ?>"><?php esc_html_e('Ouvrir Keleva Manager', 'keleva-woo-addons'); ?></a> <span class="description"><?php esc_html_e('Les actions marchandes guidées se font depuis le portail Keleva natif de ce site. Cette page reste réservée aux réglages d’intégration avancés.', 'keleva-woo-addons'); ?></span></p>
            <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- simple flag de redirection assaini, sans mutation d’état.
            if (isset($_GET['updated']) && '1' === sanitize_text_field(wp_unslash($_GET['updated']))) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Configuration Keleva enregistrée.', 'keleva-woo-addons'); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('keleva_save_dashboard_settings'); ?>
                <input type="hidden" name="action" value="keleva_save_dashboard_settings">
                <table class="form-table" role="presentation">
                    <tbody>
                    <?php foreach (self::FIELDS as $field => $label) : ?>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <input name="<?php echo esc_attr($field); ?>" id="<?php echo esc_attr($field); ?>" type="<?php echo in_array($field, ['KELEVA_DASHBOARD_WEBHOOK_URL', 'KELEVA_WHATSAPP_WEBHOOK_URL'], true) ? 'url' : 'password'; ?>" class="regular-text" autocomplete="new-password" value="" placeholder="<?php echo $configured[$field] ? esc_attr__('Déjà configuré — laisser vide pour conserver', 'keleva-woo-addons') : ''; ?>">
                                <?php if (in_array($field, ['KELEVA_DASHBOARD_WEBHOOK_URL', 'KELEVA_WHATSAPP_WEBHOOK_URL'], true)) : ?>
                                    <p class="description"><?php esc_html_e('Seules les URL HTTPS sont acceptées.', 'keleva-woo-addons'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <label>
                        <input name="keleva_generate_test_secrets" type="checkbox" value="1">
                        <?php esc_html_e('Générer côté serveur de nouveaux secrets temporaires de test. Cette action remplace les jetons et le secret webhook existants, sans jamais les afficher.', 'keleva-woo-addons'); ?>
                    </label>
                </p>
                <?php submit_button(__('Enregistrer la configuration chiffrée', 'keleva-woo-addons')); ?>
            </form>
            <h2><?php esc_html_e('Journal d’audit récent', 'keleva-woo-addons'); ?></h2>
            <p><?php esc_html_e('Les événements sont réservés aux gestionnaires WooCommerce. Les bascules de thème ne conservent ni adresse IP ni secret.', 'keleva-woo-addons'); ?></p>
            <?php $events = Keleva_Dashboard_Audit_Log::recent(25); ?>
            <?php if (!$events) : ?>
                <p><?php esc_html_e('Aucun événement d’audit disponible.', 'keleva-woo-addons'); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('UTC', 'keleva-woo-addons'); ?></th>
                            <th scope="col"><?php esc_html_e('Événement', 'keleva-woo-addons'); ?></th>
                            <th scope="col"><?php esc_html_e('Acteur', 'keleva-woo-addons'); ?></th>
                            <th scope="col"><?php esc_html_e('Contexte', 'keleva-woo-addons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $event) : ?>
                        <tr>
                            <td><?php echo esc_html((string) ($event['at'] ?? '')); ?></td>
                            <td><code><?php echo esc_html((string) ($event['event'] ?? '')); ?></code></td>
                            <td><?php echo esc_html((string) ($event['actor'] ?? '')); ?></td>
                            <td><code><?php echo esc_html((string) wp_json_encode($event['context'] ?? [])); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function save(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Accès refusé.', 'keleva-woo-addons'));
        }
        check_admin_referer('keleva_save_dashboard_settings');

        $settings = get_option(self::OPTION, []);
        $settings = is_array($settings) ? $settings : [];
        if (!empty($_POST['keleva_generate_test_secrets'])) {
            foreach (['KELEVA_DASHBOARD_TOKEN', 'KELEVA_DASHBOARD_PREVIOUS_TOKEN', 'KELEVA_DASHBOARD_WEBHOOK_SECRET', 'KELEVA_WHATSAPP_WEBHOOK_SECRET'] as $field) {
                $settings[$field] = self::encrypt(bin2hex(random_bytes(32)));
            }
        }
        foreach (self::FIELDS as $field => $_label) {
            $value = isset($_POST[$field]) ? trim((string) sanitize_text_field(wp_unslash($_POST[$field]))) : '';
            if ('' === $value) {
                continue;
            }
            if (in_array($field, ['KELEVA_DASHBOARD_WEBHOOK_URL', 'KELEVA_WHATSAPP_WEBHOOK_URL'], true) && ('https' !== wp_parse_url($value, PHP_URL_SCHEME) || !wp_http_validate_url($value))) {
                wp_die(esc_html__('L’URL du webhook doit être une URL HTTPS valide.', 'keleva-woo-addons'));
            }
            $encrypted = self::encrypt($value);
            if ('' === $encrypted) {
                wp_die(esc_html__('Impossible de chiffrer la configuration. Vérifiez que l’extension OpenSSL PHP est active.', 'keleva-woo-addons'));
            }
            $settings[$field] = $encrypted;
        }

        update_option(self::OPTION, $settings, false);
        wp_safe_redirect(add_query_arg(['page' => 'keleva-dashboard-settings', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    private static function key(): string {
        return hash('sha256', wp_salt('auth') . wp_salt('secure_auth'), true);
    }

    private static function encrypt(string $value): string {
        if (!function_exists('openssl_encrypt')) {
            return '';
        }
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($value, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($ciphertext)) {
            return '';
        }
        return base64_encode(wp_json_encode(['iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'ciphertext' => base64_encode($ciphertext)]));
    }

    private static function decrypt(string $stored): string {
        if (!function_exists('openssl_decrypt') || '' === $stored) {
            return '';
        }
        $payload = json_decode((string) base64_decode($stored, true), true);
        if (!is_array($payload) || !isset($payload['iv'], $payload['tag'], $payload['ciphertext'])) {
            return '';
        }
        $value = openssl_decrypt(
            (string) base64_decode((string) $payload['ciphertext'], true),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            (string) base64_decode((string) $payload['iv'], true),
            (string) base64_decode((string) $payload['tag'], true)
        );
        return is_string($value) ? $value : '';
    }
}
