<?php
defined('ABSPATH') || exit;

/**
 * Guided Keleva Manager inside wp-admin.
 *
 * The page deliberately delegates product, order and palette mutations to the
 * already authenticated dashboard endpoint so that validation, audit and
 * webhook behavior stay identical across the REST and wp-admin surfaces.
 */
final class Keleva_Manager_Admin {
    private const SLUG = 'keleva-manager';
    private const NONCE = 'keleva_manager_action';

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'register_menu'], 20);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_post_keleva_manager_action', [self::class, 'handle_action']);
    }

    public static function register_menu(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        add_menu_page(
            __('Keleva Manager', 'keleva-woo-addons'),
            __('Keleva Manager', 'keleva-woo-addons'),
            'manage_woocommerce',
            self::SLUG,
            [self::class, 'render'],
            'dashicons-store',
            56
        );
    }

    public static function enqueue_assets(string $hook): void {
        if ('toplevel_page_' . self::SLUG !== $hook) {
            return;
        }
        wp_enqueue_style('keleva-manager-admin', plugins_url('../assets/css/manager-admin.css', __FILE__), [], '0.5.12');
        wp_enqueue_script('keleva-manager-admin', plugins_url('../assets/js/manager-admin.js', __FILE__), [], '0.5.12', true);
    }

    public static function render(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Accès refusé.', 'keleva-woo-addons'));
        }
        if (!function_exists('wc_get_products')) {
            self::render_shell();
            echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce doit être actif pour utiliser Keleva Manager.', 'keleva-woo-addons') . '</p></div></div>';
            return;
        }

        $summary = self::response_data(Keleva_Dashboard_Endpoint::summary(new WP_REST_Request('GET', '/keleva-dashboard/v1/summary')));
        $orders = self::response_data(Keleva_Dashboard_Endpoint::orders(new WP_REST_Request('GET', '/keleva-dashboard/v1/orders')));
        $appearance = self::response_data(Keleva_Dashboard_Endpoint::appearance_palettes());
        $notice = isset($_GET['keleva_notice']) ? sanitize_key(wp_unslash($_GET['keleva_notice'])) : '';
        $message = isset($_GET['keleva_message']) ? sanitize_text_field(wp_unslash($_GET['keleva_message'])) : '';

        self::render_shell();
        if ('success' === $notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message ?: __('Modification enregistrée.', 'keleva-woo-addons')) . '</p></div>';
        } elseif ('error' === $notice) {
            echo '<div class="notice notice-error"><p>' . esc_html($message ?: __('La modification n’a pas été enregistrée.', 'keleva-woo-addons')) . '</p></div>';
        }
        if (!is_array($summary) || !is_array($orders) || !is_array($appearance)) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Les données du tableau de bord ne sont pas disponibles pour le moment.', 'keleva-woo-addons') . '</p></div></div>';
            return;
        }

        $metrics = is_array($summary['metrics'] ?? null) ? $summary['metrics'] : [];
        $products = is_array($summary['products'] ?? null) ? $summary['products'] : [];
        $order_rows = is_array($orders['orders'] ?? null) ? array_slice($orders['orders'], 0, 8) : [];
        $palettes = is_array($appearance['palettes'] ?? null) ? $appearance['palettes'] : [];
        $active_palette = sanitize_key((string) ($appearance['active'] ?? 'velora'));
        ?>
        <section class="keleva-manager-hero">
            <div>
                <p class="keleva-eyebrow"><?php esc_html_e('Votre espace de gestion', 'keleva-woo-addons'); ?></p>
                <h1><?php esc_html_e('Bonjour, que voulez-vous faire aujourd’hui ?', 'keleva-woo-addons'); ?></h1>
                <p><?php esc_html_e('Les cinq actions essentielles sont ici. Les réglages techniques restent séparés pour éviter les erreurs.', 'keleva-woo-addons'); ?></p>
            </div>
            <div class="keleva-manager-hero-actions">
                <span class="keleva-manager-badge"><?php esc_html_e('WooCommerce reste la source de vérité', 'keleva-woo-addons'); ?></span>
                <a class="button keleva-portal-link" href="<?php echo esc_url(Keleva_Native_Merchant_Portal::url()); ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons dashicons-external" aria-hidden="true"></span><?php esc_html_e('Ouvrir le portail marchand Keleva', 'keleva-woo-addons'); ?><span class="screen-reader-text"> <?php esc_html_e('(nouvelle fenêtre)', 'keleva-woo-addons'); ?></span>
                </a>
                <p class="keleva-portal-note"><?php esc_html_e('Utilisez les identifiants Keleva dédiés : ce portail est séparé de l’administration.', 'keleva-woo-addons'); ?></p>
            </div>
        </section>

        <section class="keleva-manager-cards" aria-label="Résumé du magasin">
            <?php self::metric_card(__('Produits publiés', 'keleva-woo-addons'), (string) ($metrics['products_published'] ?? 0), 'dashicons-products'); ?>
            <?php self::metric_card(__('Commandes à traiter', 'keleva-woo-addons'), (string) ($metrics['orders_awaiting'] ?? 0), 'dashicons-clipboard'); ?>
            <?php self::metric_card(__('Ruptures', 'keleva-woo-addons'), (string) ($metrics['out_of_stock'] ?? 0), 'dashicons-warning'); ?>
            <?php self::metric_card(__('Chiffre payé cette semaine', 'keleva-woo-addons'), self::format_amount($metrics['revenue_week'] ?? '0', $metrics['currency'] ?? ''), 'dashicons-chart-area'); ?>
        </section>

        <div class="keleva-manager-grid">
            <section class="keleva-manager-panel" id="ajouter-un-plat">
                <div class="keleva-panel-heading"><span class="keleva-icon keleva-icon-orange"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span></span><div><h2><?php esc_html_e('Ajouter un plat', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Commencez par les informations essentielles. Le brouillon est le choix par défaut.', 'keleva-woo-addons'); ?></p></div></div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="keleva-form">
                    <?php self::action_fields('create_product'); ?>
                    <label><?php esc_html_e('Nom du plat', 'keleva-woo-addons'); ?><input name="name" required maxlength="180" placeholder="Ex. Brunch du dimanche"></label>
                    <div class="keleva-form-two"><label><?php esc_html_e('Prix', 'keleva-woo-addons'); ?><input name="regular_price" required inputmode="decimal" placeholder="Ex. 49.00"></label><label><?php esc_html_e('Stock initial', 'keleva-woo-addons'); ?><input name="stock_quantity" required type="number" min="0" step="1" value="0"></label></div>
                    <label class="keleva-check"><input type="checkbox" name="publish" value="1"><span><strong><?php esc_html_e('Publier immédiatement', 'keleva-woo-addons'); ?></strong><small><?php esc_html_e('Sinon, le produit reste en brouillon afin de vérifier la photo et le résultat.', 'keleva-woo-addons'); ?></small></span></label>
                    <button class="button button-primary keleva-primary" type="submit"><?php esc_html_e('Créer le plat', 'keleva-woo-addons'); ?></button>
                </form>
            </section>

            <section class="keleva-manager-panel keleva-manager-panel-dark" id="modifier-apparence">
                <div class="keleva-panel-heading"><span class="keleva-icon keleva-icon-gold"><span class="dashicons dashicons-art" aria-hidden="true"></span></span><div><h2><?php esc_html_e('Modifier l’apparence', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Choisissez une palette préparée. Les couleurs brutes ne sont jamais demandées.', 'keleva-woo-addons'); ?></p></div></div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="keleva-palette-form">
                    <?php self::action_fields('update_appearance_palette'); ?>
                    <div class="keleva-palette-grid">
                        <?php foreach ($palettes as $palette) : $palette_id = sanitize_key((string) ($palette['id'] ?? '')); $colors = is_array($palette['colors'] ?? null) ? $palette['colors'] : []; ?>
                            <label class="keleva-palette-option"><input type="radio" name="palette" value="<?php echo esc_attr($palette_id); ?>" <?php checked($palette_id, $active_palette); ?>><span class="keleva-palette-swatch" style="--keleva-bg:<?php echo esc_attr((string) ($colors['bg'] ?? '#f4f0e8')); ?>;--keleva-accent:<?php echo esc_attr((string) ($colors['accent'] ?? '#eb5f2a')); ?>;--keleva-ink:<?php echo esc_attr((string) ($colors['ink'] ?? '#1d1d1b')); ?>"></span><span><?php echo esc_html((string) ($palette['label'] ?? $palette_id)); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <button class="button button-primary keleva-primary" type="submit"><?php esc_html_e('Prévisualiser et appliquer', 'keleva-woo-addons'); ?></button>
                    <p class="keleva-help"><?php esc_html_e('La palette active est signalée. Le changement est réversible.', 'keleva-woo-addons'); ?></p>
                </form>
            </section>
        </div>

        <section class="keleva-manager-panel" id="produits-et-stock">
            <div class="keleva-panel-heading"><span class="keleva-icon keleva-icon-blue"><span class="dashicons dashicons-products" aria-hidden="true"></span></span><div><h2><?php esc_html_e('Produits et stock', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Modifiez un prix ou une quantité. Chaque action est validée puis journalisée.', 'keleva-woo-addons'); ?></p></div></div>
            <?php if (!$products) : ?><p class="keleva-empty"><?php esc_html_e('Aucun produit publié ou brouillon à afficher.', 'keleva-woo-addons'); ?></p><?php else : ?>
                <div class="keleva-table-scroll" tabindex="0" role="region" aria-label="Produits et stock défilable horizontalement"><table class="widefat striped keleva-table"><thead><tr><th scope="col"><?php esc_html_e('Produit', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Prix', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Stock', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Statut', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Action', 'keleva-woo-addons'); ?></th></tr></thead><tbody>
                <?php foreach ($products as $product) : $product_id = absint($product['id'] ?? 0); if (!$product_id) continue; ?>
                    <tr><td><div class="keleva-product-cell"><?php if (!empty($product['image'])) : ?><img src="<?php echo esc_url((string) $product['image']); ?>" alt="" width="48" height="48"><?php endif; ?><strong><?php echo esc_html((string) ($product['name'] ?? '')); ?></strong></div></td><td><?php echo esc_html(self::format_amount($product['price'] ?? '', $product['currency'] ?? '')); ?></td><td><?php echo esc_html(null === ($product['stock_quantity'] ?? null) ? __('Géré par WooCommerce', 'keleva-woo-addons') : (string) $product['stock_quantity']); ?></td><td><span class="keleva-status"><?php echo esc_html((string) ($product['status'] ?? '')); ?></span></td><td><details class="keleva-inline-edit"><summary><?php esc_html_e('Modifier', 'keleva-woo-addons'); ?></summary><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <?php self::action_fields('update_product'); ?><input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product_id); ?>"><label><?php esc_html_e('Prix', 'keleva-woo-addons'); ?><input name="regular_price" inputmode="decimal" value="<?php echo esc_attr((string) ($product['price'] ?? '')); ?>"></label><label><?php esc_html_e('Stock', 'keleva-woo-addons'); ?><input name="stock_quantity" type="number" min="0" step="1" value="<?php echo esc_attr((string) ($product['stock_quantity'] ?? 0)); ?>"></label><label><?php esc_html_e('Nouvelle photo (facultatif)', 'keleva-woo-addons'); ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif"></label><button class="button button-primary" type="submit"><?php esc_html_e('Enregistrer', 'keleva-woo-addons'); ?></button></form></details></td></tr>
                <?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
        </section>

        <section class="keleva-manager-panel" id="commandes"><div class="keleva-panel-heading"><span class="keleva-icon keleva-icon-green"><span class="dashicons dashicons-clipboard" aria-hidden="true"></span></span><div><h2><?php esc_html_e('Commandes à traiter', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Changez le statut avec une action explicite. Aucun paiement ou message n’est envoyé par cette page.', 'keleva-woo-addons'); ?></p></div></div>
            <?php if (!$order_rows) : ?><p class="keleva-empty"><?php esc_html_e('Aucune commande récente.', 'keleva-woo-addons'); ?></p><?php else : ?><div class="keleva-table-scroll" tabindex="0" role="region" aria-label="Commandes défilables horizontalement"><table class="widefat striped keleva-table"><thead><tr><th scope="col"><?php esc_html_e('Commande', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Client', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Statut', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Total', 'keleva-woo-addons'); ?></th><th scope="col"><?php esc_html_e('Action', 'keleva-woo-addons'); ?></th></tr></thead><tbody><?php foreach ($order_rows as $order) : $order_id = absint($order['id'] ?? 0); if (!$order_id) continue; ?><tr><td><strong>#<?php echo esc_html((string) ($order['number'] ?? $order_id)); ?></strong><small class="keleva-muted"><?php echo esc_html((string) ($order['created_at'] ?? '')); ?></small></td><td><?php echo esc_html((string) ($order['customer'] ?? __('Client invité', 'keleva-woo-addons'))); ?></td><td><span class="keleva-status"><?php echo esc_html((string) ($order['status_label'] ?? $order['status'] ?? '')); ?></span></td><td><?php echo esc_html(self::format_amount($order['total'] ?? '', $order['currency'] ?? '')); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="keleva-order-action"><?php self::action_fields('update_order_status'); ?><input type="hidden" name="order_id" value="<?php echo esc_attr((string) $order_id); ?>"><label class="screen-reader-text" for="keleva-order-status-<?php echo esc_attr((string) $order_id); ?>"><?php esc_html_e('Nouveau statut', 'keleva-woo-addons'); ?></label><select id="keleva-order-status-<?php echo esc_attr((string) $order_id); ?>" name="status"><option value="pending"><?php esc_html_e('En attente', 'keleva-woo-addons'); ?></option><option value="on-hold"><?php esc_html_e('En pause', 'keleva-woo-addons'); ?></option><option value="processing"><?php esc_html_e('En préparation', 'keleva-woo-addons'); ?></option><option value="completed"><?php esc_html_e('Terminée', 'keleva-woo-addons'); ?></option><option value="cancelled"><?php esc_html_e('Annulée', 'keleva-woo-addons'); ?></option></select><button class="button" type="submit"><?php esc_html_e('Valider', 'keleva-woo-addons'); ?></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </section>

        <details class="keleva-advanced"><summary><?php esc_html_e('Réglages avancés et sécurité', 'keleva-woo-addons'); ?></summary><p><?php esc_html_e('Les secrets dashboard, WhatsApp/n8n et les réglages techniques restent dans la page de configuration sécurisée.', 'keleva-woo-addons'); ?></p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=keleva-dashboard-settings')); ?>"><?php esc_html_e('Ouvrir les réglages avancés', 'keleva-woo-addons'); ?></a></details>
        </div>
        <?php
    }

    private static function render_shell(): void {
        echo '<div class="wrap keleva-manager-wrap">';
    }

    private static function metric_card(string $label, string $value, string $icon): void {
        echo '<article class="keleva-metric-card"><span class="dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span><div><p>' . esc_html($label) . '</p><strong>' . esc_html($value) . '</strong></div></article>';
    }

    private static function action_fields(string $action): void {
        wp_nonce_field(self::NONCE, '_keleva_nonce');
        echo '<input type="hidden" name="action" value="keleva_manager_action"><input type="hidden" name="keleva_task" value="' . esc_attr($action) . '">';
    }

    private static function response_data($response): array|false {
        if (is_wp_error($response) || !$response instanceof WP_REST_Response) {
            return false;
        }
        $data = $response->get_data();
        return is_array($data) ? $data : false;
    }

    private static function format_amount($amount, $currency): string {
        $value = is_numeric($amount) ? (float) $amount : 0.0;
        $formatted = number_format_i18n($value, 2);
        return trim($formatted . ' ' . sanitize_text_field((string) $currency));
    }

    public static function handle_action(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Accès refusé.', 'keleva-woo-addons'));
        }
        check_admin_referer(self::NONCE, '_keleva_nonce');
        $task = isset($_POST['keleva_task']) ? sanitize_key(wp_unslash($_POST['keleva_task'])) : '';
        $result = match ($task) {
            'create_product' => self::create_product(),
            'update_product' => self::update_product(),
            'update_order_status' => self::update_order_status(),
            'update_appearance_palette' => self::update_appearance_palette(),
            default => new WP_Error('keleva_manager_unknown_action', __('Action inconnue.', 'keleva-woo-addons')),
        };
        if (is_wp_error($result)) {
            self::redirect('error', $result->get_error_message());
        }
        self::redirect('success', __('Modification enregistrée.', 'keleva-woo-addons'));
    }

    private static function create_product(): true|WP_Error {
        $request = self::json_request('/keleva-dashboard/v1/products', [
            'name' => isset($_POST['name']) ? wp_unslash($_POST['name']) : '',
            'regular_price' => isset($_POST['regular_price']) ? wp_unslash($_POST['regular_price']) : '',
            'stock_quantity' => isset($_POST['stock_quantity']) ? wp_unslash($_POST['stock_quantity']) : '',
            'status' => !empty($_POST['publish']) ? 'publish' : 'draft',
        ]);
        $response = Keleva_Dashboard_Endpoint::create_product($request);
        return is_wp_error($response) ? $response : true;
    }

    private static function update_product(): true|WP_Error {
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        if (!$product_id) return new WP_Error('keleva_manager_product_required', __('Produit introuvable.', 'keleva-woo-addons'));
        $request = self::json_request('/keleva-dashboard/v1/products/' . $product_id, [
            'regular_price' => isset($_POST['regular_price']) ? wp_unslash($_POST['regular_price']) : '',
            'stock_quantity' => isset($_POST['stock_quantity']) ? wp_unslash($_POST['stock_quantity']) : '',
        ]);
        $request['id'] = $product_id;
        $response = Keleva_Dashboard_Endpoint::update_product($request);
        if (is_wp_error($response)) return $response;
        if (!empty($_FILES['image']) && is_array($_FILES['image']) && empty($_FILES['image']['error'])) {
            $image_request = new WP_REST_Request('POST', '/keleva-dashboard/v1/products/' . $product_id . '/image');
            $image_request['id'] = $product_id;
            $image_request->set_file_params(['image' => $_FILES['image']]);
            $image_response = Keleva_Dashboard_Endpoint::upload_product_image($image_request);
            if (is_wp_error($image_response)) return $image_response;
        }
        return true;
    }

    private static function update_order_status(): true|WP_Error {
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
        $request = new WP_REST_Request('POST', '/keleva-dashboard/v1/orders/' . $order_id . '/status');
        $request['id'] = $order_id;
        $request->set_param('status', $status);
        $response = Keleva_Dashboard_Endpoint::update_order_status($request);
        return is_wp_error($response) ? $response : true;
    }

    private static function update_appearance_palette(): true|WP_Error {
        $palette = isset($_POST['palette']) ? sanitize_key(wp_unslash($_POST['palette'])) : '';
        $request = new WP_REST_Request('POST', '/keleva-dashboard/v1/appearance/palette');
        $request->set_param('palette', $palette);
        $response = Keleva_Dashboard_Endpoint::update_appearance_palette($request);
        return is_wp_error($response) ? $response : true;
    }

    private static function json_request(string $route, array $payload): WP_REST_Request {
        $request = new WP_REST_Request('POST', $route);
        $request->set_body(wp_json_encode($payload));
        $request->set_header('Content-Type', 'application/json');
        return $request;
    }

    private static function redirect(string $notice, string $message): never {
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'keleva_notice' => $notice, 'keleva_message' => rawurlencode($message)], admin_url('admin.php')));
        exit;
    }
}
