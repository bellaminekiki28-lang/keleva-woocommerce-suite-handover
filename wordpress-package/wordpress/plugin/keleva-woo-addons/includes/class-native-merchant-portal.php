<?php
defined('ABSPATH') || exit;

/**
 * Native merchant portal served directly by WordPress.
 *
 * This class deliberately has no remote URL, proxy, WordPress-user login, or
 * wp-admin session transfer. Merchant access uses an opaque Keleva session
 * scoped to the public portal path.
 */
final class Keleva_Native_Merchant_Portal {
    public const PATH = 'espace-marchand';

    private const ACCESS_OPTION = 'keleva_native_portal_access';
    private const COOKIE_NAME = 'keleva_native_portal_session';
    private const SESSION_PREFIX = 'keleva_native_portal_session_';
    private const SESSION_TTL = 28800;
    private const ADMIN_SLUG = 'keleva-native-portal-access';

    /**
     * @return array<string, string>
     */
    private static function permissions(): array {
        return [
            'catalog.write' => __('Créer et modifier les produits', 'keleva-woo-addons'),
            'catalog.delete' => __('Supprimer les produits de test créés dans le portail', 'keleva-woo-addons'),
            'pricing.write' => __('Modifier les prix', 'keleva-woo-addons'),
            'inventory.write' => __('Modifier le stock', 'keleva-woo-addons'),
            'orders.write' => __('Mettre à jour les statuts de commande', 'keleva-woo-addons'),
            'palettes.write' => __('Modifier les palettes validées', 'keleva-woo-addons'),
        ];
    }

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'register_admin_page'], 82);
        add_action('admin_post_keleva_native_portal_save_access', [self::class, 'save_access']);
    }

    public static function url(): string {
        return home_url('/' . self::PATH . '/');
    }

    public static function register_admin_page(): void {
        add_options_page(
            __('Accès marchand Keleva', 'keleva-woo-addons'),
            __('Accès marchand Keleva', 'keleva-woo-addons'),
            'manage_woocommerce',
            self::ADMIN_SLUG,
            [self::class, 'render_access_page']
        );
    }

    public static function render_access_page(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Accès refusé.', 'keleva-woo-addons'));
        }

        $access = self::access_config();
        $selected_permissions = is_array($access['permissions'] ?? null) ? $access['permissions'] : [];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Accès marchand Keleva', 'keleva-woo-addons'); ?></h1>
            <p><?php esc_html_e('Configurez ici le seul compte du portail marchand de staging. Ce compte ne crée jamais de compte WordPress et ne peut pas ouvrir wp-admin.', 'keleva-woo-addons'); ?></p>
            <?php if (isset($_GET['keleva_native_notice']) && 'saved' === sanitize_key(wp_unslash($_GET['keleva_native_notice']))) : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Accès Keleva enregistré.', 'keleva-woo-addons'); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('keleva_native_portal_access', '_keleva_native_access_nonce'); ?>
                <input type="hidden" name="action" value="keleva_native_portal_save_access">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="keleva-native-username"><?php esc_html_e('Identifiant Keleva', 'keleva-woo-addons'); ?></label></th>
                        <td><input id="keleva-native-username" class="regular-text" type="text" name="username" required maxlength="80" value="<?php echo esc_attr((string) ($access['username'] ?? '')); ?>"><p class="description"><?php esc_html_e('Cet identifiant est indépendant de WordPress.', 'keleva-woo-addons'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="keleva-native-password"><?php esc_html_e('Mot de passe Keleva', 'keleva-woo-addons'); ?></label></th>
                        <td><input id="keleva-native-password" class="regular-text" type="password" name="password" minlength="12" autocomplete="new-password"><p class="description"><?php esc_html_e('Renseignez-le pour créer ou modifier le mot de passe. Laissez vide pour conserver le mot de passe actuel.', 'keleva-woo-addons'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Actions autorisées', 'keleva-woo-addons'); ?></th>
                        <td>
                            <?php foreach (self::permissions() as $permission => $label) : ?>
                                <label style="display:block;margin:6px 0;"><input type="checkbox" name="permissions[]" value="<?php echo esc_attr($permission); ?>" <?php checked(in_array($permission, $selected_permissions, true)); ?>> <?php echo esc_html($label); ?></label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Enregistrer l’accès marchand', 'keleva-woo-addons')); ?>
            </form>
            <p><strong><?php esc_html_e('Lien à communiquer au marchand :', 'keleva-woo-addons'); ?></strong> <code><?php echo esc_html(self::url()); ?></code></p>
        </div>
        <?php
    }

    public static function save_access(): void {
        // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Accès refusé.', 'keleva-woo-addons'));
        }
        check_admin_referer('keleva_native_portal_access', '_keleva_native_access_nonce');

        $username = sanitize_user((string) wp_unslash($_POST['username'] ?? ''), true);
        $password = (string) wp_unslash($_POST['password'] ?? '');
        $requested_permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? array_map('sanitize_text_field', wp_unslash($_POST['permissions'])) : [];
        $allowed_permissions = array_keys(self::permissions());
        $permissions = array_values(array_intersect($allowed_permissions, $requested_permissions));
        $existing = self::access_config();

        if ('' === $username) {
            wp_die(esc_html__('Un identifiant Keleva est requis.', 'keleva-woo-addons'));
        }
        if ('' === $password && empty($existing['password_hash'])) {
            wp_die(esc_html__('Un mot de passe Keleva d’au moins 12 caractères est requis pour créer le premier accès.', 'keleva-woo-addons'));
        }
        if ('' !== $password && strlen($password) < 12) {
            wp_die(esc_html__('Le mot de passe Keleva doit contenir au moins 12 caractères.', 'keleva-woo-addons'));
        }

        $config = [
            'username' => $username,
            'password_hash' => '' !== $password ? wp_hash_password($password) : (string) ($existing['password_hash'] ?? ''),
            'permissions' => $permissions,
        ];
        update_option(self::ACCESS_OPTION, $config, false);
        Keleva_Dashboard_Audit_Log::record('merchant_native_access_configured', ['permissions' => $permissions], 'wp-owner');
        wp_safe_redirect(add_query_arg('keleva_native_notice', 'saved', admin_url('options-general.php?page=' . self::ADMIN_SLUG)));
        exit;
    }

    /**
     * Translate the white-label portal UI when WordPress is rendering an RTL route.
     * Product names, merchant names and palette names intentionally remain unchanged.
     */
    public static function arabic_portal_gettext($translation, $text, $domain) {
        if ('keleva-woo-addons' !== (string) $domain || (function_exists('is_rtl') && !is_rtl())) return (string) $translation;
        $translations = [
            'Connexion Keleva' => 'تسجيل الدخول إلى كيليفا',
            'Portail marchand Keleva' => 'بوابة التاجر كيليفا',
            'Portail marchand' => 'بوابة التاجر',
            'Ajouter un produit' => 'إضافة منتج',
            'Produits & stock' => 'المنتجات والمخزون',
            'Commandes' => 'الطلبات',
            'Apparence' => 'المظهر',
            'Catégories & options' => 'الفئات والخيارات',
            'Déconnexion' => 'تسجيل الخروج',
            'ESPACE DE STAGING' => 'بيئة الاختبار',
            'Bonjour %s, que voulez-vous faire ?' => 'مرحبًا %s، ماذا تريد أن تفعل؟',
            'Les actions essentielles sont présentées clairement. Les réglages techniques restent hors de cet espace.' => 'تظهر الإجراءات الأساسية بوضوح. تبقى الإعدادات التقنية خارج هذه المساحة.',
            'ÉTAPE 1' => 'الخطوة 1',
            'Commencez simplement. Le brouillon évite toute publication involontaire.' => 'ابدأ ببساطة. يحمي المسودّة من النشر غير المقصود.',
            'Nom du produit' => 'اسم المنتج',
            'Catégorie' => 'الفئة',
            'Sans catégorie' => 'بدون فئة',
            'Prix' => 'السعر',
            'Stock' => 'المخزون',
            'Description (facultative)' => 'الوصف (اختياري)',
            'Photo (facultative)' => 'الصورة (اختيارية)',
            'Texte de la photo (facultatif)' => 'النص البديل للصورة (اختياري)',
            'État' => 'الحالة',
            'Brouillon — vérifier avant publication' => 'مسودة — تحقّق قبل النشر',
            'Actif — visible dans le staging' => 'نشط — ظاهر في بيئة الاختبار',
            'Créer le produit de test' => 'إنشاء منتج اختباري',
            'ÉTAPE 2' => 'الخطوة 2',
            'Les 6 produits les plus récents sont affichés pour garder cette page simple. Ouvrez un produit seulement si vous voulez le modifier.' => 'تظهر أحدث 6 منتجات للحفاظ على بساطة الصفحة. افتح المنتج فقط إذا أردت تعديله.',
            'Afficher seulement les 6 derniers' => 'عرض آخر 6 منتجات فقط',
            'Voir les %d derniers produits' => 'عرض آخر %d منتجًا',
            'Rechercher un produit' => 'البحث عن منتج',
            'Rechercher' => 'بحث',
            'Effacer' => 'مسح',
            'Aucun produit à afficher.' => 'لا توجد منتجات لعرضها.',
            'Photo' => 'صورة',
            'Actif' => 'نشط',
            'Brouillon' => 'مسودة',
            'En stock' => 'متوفر في المخزون',
            'Rupture de stock' => 'غير متوفر في المخزون',
            'Modifier ce produit' => 'تعديل هذا المنتج',
            'Ne pas modifier' => 'عدم التعديل',
            'Enregistrer le produit' => 'حفظ المنتج',
            'ÉTAPE 3' => 'الخطوة 3',
            'Aucun paiement ni message n’est envoyé depuis cette page.' => 'لا يتم إرسال أي دفعة أو رسالة من هذه الصفحة.',
            'Client invité' => 'عميل زائر',
            'Statut' => 'الحالة',
            'Total' => 'الإجمالي',
            'Action' => 'الإجراء',
            'Nouveau statut' => 'الحالة الجديدة',
            'En attente' => 'قيد الانتظار',
            'En pause' => 'معلّق',
            'En préparation' => 'قيد التحضير',
            'Terminée' => 'مكتمل',
            'Annulée' => 'ملغى',
            'Valider' => 'تأكيد',
            'ÉTAPE 4' => 'الخطوة 4',
            'Choisissez une palette Keleva validée. Les couleurs techniques restent cachées.' => 'اختر لوحة ألوان كيليفا المعتمدة. تبقى الألوان التقنية مخفية.',
            'Appliquer la palette' => 'تطبيق لوحة الألوان',
            'ACTIVITÉ' => 'النشاط',
            'Dernières actions' => 'آخر الإجراءات',
            'Catégories, variantes et suppléments' => 'الفئات والمتغيرات والإضافات',
            'Catégories disponibles' => 'الفئات المتاحة',
            'Nouvelle catégorie' => 'فئة جديدة',
            'Nom de la catégorie' => 'اسم الفئة',
            'Petite description (facultative)' => 'وصف قصير (اختياري)',
            'Créer la catégorie' => 'إنشاء الفئة',
            'Choix, variantes et suppléments' => 'الخيارات والمتغيرات والإضافات',
            'Gérer les choix' => 'إدارة الخيارات',
            'groupes' => 'مجموعات',
            'variantes' => 'متغيرات',
            'suppléments' => 'إضافات',
        ];
        $text = (string) $text;
        return $translations[$text] ?? (string) $translation;
    }

    public static function render(): void {
        nocache_headers();
        $is_arabic_portal = false !== strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/ar/' . self::PATH);
        if ($is_arabic_portal) {
            ob_start([self::class, 'translate_arabic_markup']);
        }
        // Arabic labels are rendered through the route-aware UI strings; avoid a global gettext hook on Hostinger.
        // The portal is session-aware; shared page caches must never replay its login form.
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Vary: Cookie');
        status_header(200);
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));

        if ('POST' === strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))) {
            self::handle_public_action();
        }

        $session = self::current_session();
        self::render_document_start($session);
        if (null === $session) {
            self::render_login();
        } else {
            self::render_dashboard($session);
        }
        self::render_document_end();
        if ($is_arabic_portal && ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Translate visible portal labels only on the Arabic route.
     * Keep HTML attributes and product/order data untouched.
     *
     * @param string $buffer
     * @return string
     */
    public static function translate_arabic_markup($buffer) {
        return preg_replace_callback('/>([^<>]+)</u', static function ($matches) {
            $value = (string) $matches[1];
            $trimmed = trim($value);
            if ('' === $trimmed) {
                return $matches[0];
            }
            $translated = Keleva_Native_Merchant_Portal::arabic_portal_gettext($trimmed, $trimmed, 'keleva-woo-addons');
            if ($translated === $trimmed) {
                return $matches[0];
            }
            $prefix = substr($value, 0, strlen($value) - strlen(ltrim($value)));
            $suffix = substr($value, strlen(rtrim($value)));
            return '>' . $prefix . $translated . $suffix . '<';
        }, (string) $buffer) ?? (string) $buffer;
    }

    private static function handle_public_action(): void {
        $action = sanitize_key((string) wp_unslash($_POST['keleva_native_action'] ?? ''));
        if ('login' === $action) {
            self::handle_login();
            return;
        }

        $session = self::current_session();
        if (null === $session) {
            self::redirect_with_notice('error', __('Votre session Keleva a expiré. Reconnectez-vous.', 'keleva-woo-addons'));
        }
        $nonce = sanitize_text_field((string) wp_unslash($_POST['_keleva_native_nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, self::nonce_action($session))) {
            self::redirect_with_notice('error', __('La confirmation de cette action a expiré. Réessayez.', 'keleva-woo-addons'));
        }

        $result = match ($action) {
            'logout' => self::logout($session),
            'create_product' => self::create_product($session),
            'update_product' => self::update_product($session),
            'delete_product' => self::delete_product($session),
            'create_category' => self::create_category($session),
            'add_product_option_group' => self::add_product_option_group($session),
            'delete_product_option_group' => self::delete_product_option_group($session),
            'save_product_variants' => self::save_product_variants($session),
            'update_order' => self::update_order($session),
            'update_palette' => self::update_palette($session),
            default => new WP_Error('keleva_native_unknown_action', __('Action inconnue.', 'keleva-woo-addons')),
        };

        if (is_wp_error($result)) {
            self::redirect_with_notice('error', $result->get_error_message());
        }
        self::redirect_with_notice('success', self::success_message_for($action));
    }

    private static function handle_login(): void {
        $access = self::access_config();
        $username = sanitize_user((string) wp_unslash($_POST['username'] ?? ''), true);
        $password = (string) wp_unslash($_POST['password'] ?? '');

        if (empty($access['password_hash']) || empty($access['username']) || !hash_equals((string) $access['username'], $username) || !wp_check_password($password, (string) $access['password_hash'])) {
            self::redirect_with_notice('error', __('Identifiant ou mot de passe Keleva incorrect.', 'keleva-woo-addons'));
        }

        self::create_session($access);
        Keleva_Dashboard_Audit_Log::record('merchant_native_login', [], (string) $access['username']);
        self::redirect_with_notice('success', __('Connexion Keleva réussie.', 'keleva-woo-addons'));
    }

    /**
     * @param array<string, mixed> $access
     */
    private static function create_session(array $access): void {
        $record = [
            'username' => sanitize_user((string) ($access['username'] ?? ''), true),
            'permissions' => is_array($access['permissions'] ?? null) ? array_values($access['permissions']) : [],
            'expires_at' => time() + self::SESSION_TTL,
        ];
        // Use a signed, opaque cookie so the session survives Hostinger cache/server boundaries.
        $payload = rtrim(strtr(base64_encode((string) wp_json_encode($record)), '+/', '-_'), '=');
        $token = $payload . '.' . hash_hmac('sha256', $payload, (string) ($access['password_hash'] ?? ''));
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + self::SESSION_TTL,
            'path' => '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function current_session(): ?array {
        $token = sanitize_text_field((string) wp_unslash($_COOKIE[self::COOKIE_NAME] ?? ''));
        if ('' === $token || !preg_match('/^([A-Za-z0-9_-]+)\.([A-Fa-f0-9]{64})$/', $token, $matches)) {
            return null;
        }
        $payload = $matches[1];
        $signature = $matches[2];
        $access = self::access_config();
        if (!hash_equals($signature, hash_hmac('sha256', $payload, (string) ($access['password_hash'] ?? '')))) {
            self::clear_session_cookie();
            return null;
        }
        $base64 = strtr($payload, '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
        $decoded = base64_decode($base64, true);
        $record = is_string($decoded) ? json_decode($decoded, true) : null;
        if (!is_array($record) || empty($record['username']) || empty($record['expires_at']) || (int) $record['expires_at'] < time()) {
            self::clear_session_cookie();
            return null;
        }
        $record['token'] = $token;
        return $record;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function logout(array $session): true {
        self::clear_session_cookie();
        Keleva_Dashboard_Audit_Log::record('merchant_native_logout', [], (string) $session['username']);
        return true;
    }

    private static function clear_session_cookie(): void {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - HOUR_IN_SECONDS,
            'path' => '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function has_permission(array $session, string $permission): bool {
        return in_array($permission, is_array($session['permissions'] ?? null) ? $session['permissions'] : [], true);
    }

    /**
     * @param array<string, mixed> $session
     * @param array<int, string> $permissions
     */
    private static function require_permissions(array $session, array $permissions): true|WP_Error {
        foreach ($permissions as $permission) {
            if (!self::has_permission($session, $permission)) {
                return new WP_Error('keleva_native_forbidden', __('Cette action n’est pas autorisée pour ce compte Keleva.', 'keleva-woo-addons'));
            }
        }
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function create_product(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.write', 'pricing.write', 'inventory.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!class_exists('WC_Product_Simple')) {
            return new WP_Error('keleva_native_woocommerce_required', __('WooCommerce doit être actif pour créer un produit.', 'keleva-woo-addons'));
        }

        $name = sanitize_text_field((string) wp_unslash($_POST['name'] ?? ''));
        $price = wc_format_decimal((string) wp_unslash($_POST['regular_price'] ?? ''));
        $stock = max(0, absint($_POST['stock_quantity'] ?? 0));
        $description = wp_kses_post((string) wp_unslash($_POST['description'] ?? ''));
        $status = in_array(sanitize_key((string) wp_unslash($_POST['status'] ?? 'draft')), ['draft', 'publish'], true) ? sanitize_key((string) wp_unslash($_POST['status'] ?? 'draft')) : 'draft';
        if ('' === $name || '' === $price) {
            return new WP_Error('keleva_native_product_required', __('Le nom et le prix sont requis.', 'keleva-woo-addons'));
        }

        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_regular_price($price);
        $product->set_price($price);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        $product->set_description($description);
        $product->set_status($status);
        $product_id = $product->save();
        if (!$product_id) {
            return new WP_Error('keleva_native_product_create_failed', __('Le produit n’a pas pu être créé.', 'keleva-woo-addons'));
        }
        update_post_meta($product_id, '_keleva_native_test', '1');
        self::assign_category($product_id, absint($_POST['category_id'] ?? 0));
        $upload = self::save_product_image($product_id);
        if (is_wp_error($upload)) {
            return $upload;
        }
        Keleva_Dashboard_Audit_Log::record('merchant_native_product_created', ['product_id' => $product_id, 'status' => $status], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function update_product(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.write', 'pricing.write', 'inventory.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        $product_id = absint($_POST['product_id'] ?? 0);
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : false;
        if (!$product) {
            return new WP_Error('keleva_native_product_missing', __('Produit introuvable.', 'keleva-woo-addons'));
        }
        $status = in_array(sanitize_key((string) wp_unslash($_POST['status'] ?? 'draft')), ['draft', 'publish'], true) ? sanitize_key((string) wp_unslash($_POST['status'] ?? 'draft')) : 'draft';
        if (!$product->is_type('variable')) {
            $price = wc_format_decimal((string) wp_unslash($_POST['regular_price'] ?? ''));
            if ('' === $price) {
                return new WP_Error('keleva_native_product_price_required', __('Le prix est requis.', 'keleva-woo-addons'));
            }
            $stock = max(0, absint($_POST['stock_quantity'] ?? 0));
            $product->set_regular_price($price);
            $product->set_price($price);
            $product->set_manage_stock(true);
            $product->set_stock_quantity($stock);
            $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        }
        $product->set_status($status);
        $product->set_description(wp_kses_post((string) wp_unslash($_POST['description'] ?? '')));
        $product->save();
        self::assign_category($product_id, absint($_POST['category_id'] ?? 0));
        $upload = self::save_product_image($product_id);
        if (is_wp_error($upload)) {
            return $upload;
        }
        Keleva_Dashboard_Audit_Log::record('merchant_native_product_updated', ['product_id' => $product_id], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function delete_product(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.delete']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        $product_id = absint($_POST['product_id'] ?? 0);
        if ('1' !== (string) get_post_meta($product_id, '_keleva_native_test', true)) {
            return new WP_Error('keleva_native_delete_restricted', __('Seuls les produits de test créés dans ce portail peuvent être supprimés ici.', 'keleva-woo-addons'));
        }
        $deleted = wp_delete_post($product_id, true);
        if (!$deleted) {
            return new WP_Error('keleva_native_delete_failed', __('Le produit de test n’a pas pu être supprimé.', 'keleva-woo-addons'));
        }
        Keleva_Dashboard_Audit_Log::record('merchant_native_product_deleted', ['product_id' => $product_id], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function create_category(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!class_exists('Keleva_Category_Service')) {
            return new WP_Error('keleva_native_categories_unavailable', __('La gestion des catégories n’est pas disponible.', 'keleva-woo-addons'));
        }
        $name = sanitize_text_field((string) wp_unslash($_POST['category_name'] ?? ''));
        $description = sanitize_textarea_field((string) wp_unslash($_POST['category_description'] ?? ''));
        if ('' === $name) {
            return new WP_Error('keleva_native_category_name_required', __('Le nom de la catégorie est requis.', 'keleva-woo-addons'));
        }
        $category = Keleva_Category_Service::create([
            'name' => $name,
            'slug' => '',
            'description' => $description,
            'visible' => true,
            'order' => 0,
        ]);
        if (is_wp_error($category)) {
            return $category;
        }
        Keleva_Dashboard_Audit_Log::record('merchant_native_category_created', ['category_id' => $category->term_id], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function add_product_option_group(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.write', 'pricing.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!class_exists('Keleva_Product_Options')) {
            return new WP_Error('keleva_native_options_unavailable', __('La gestion des options n’est pas disponible.', 'keleva-woo-addons'));
        }
        $product_id = absint($_POST['product_id'] ?? 0);
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : false;
        if (!$product || $product->is_type('variation')) {
            return new WP_Error('keleva_native_product_missing', __('Produit introuvable.', 'keleva-woo-addons'));
        }
        $label = sanitize_text_field((string) wp_unslash($_POST['option_group_label'] ?? ''));
        if ('' === $label) {
            return new WP_Error('keleva_native_option_group_label_required', __('Donnez un nom à ce groupe de choix.', 'keleva-woo-addons'));
        }
        $display = in_array(sanitize_key((string) wp_unslash($_POST['option_group_display'] ?? 'radio')), ['radio', 'checkbox'], true) ? sanitize_key((string) wp_unslash($_POST['option_group_display'] ?? 'radio')) : 'radio';
        $labels = isset($_POST['option_label']) && is_array($_POST['option_label']) ? array_map(static fn ($value): string => sanitize_text_field((string) wp_unslash($value)), $_POST['option_label']) : [];
        $prices = isset($_POST['option_price']) && is_array($_POST['option_price']) ? $_POST['option_price'] : [];
        $options = [];
        foreach (array_slice($labels, 0, 16) as $index => $option_label) {
            if ('' === $option_label) {
                continue;
            }
            $option_id = sanitize_key(sanitize_title($option_label) . '-' . ($index + 1));
            $options[] = [
                'id' => $option_id,
                'label' => $option_label,
                'price' => max(0, (float) wc_format_decimal((string) wp_unslash($prices[$index] ?? '0'))),
            ];
        }
        if (!$options) {
            return new WP_Error('keleva_native_option_required', __('Ajoutez au moins un choix.', 'keleva-woo-addons'));
        }
        $max = 'radio' === $display ? 1 : min(count($options), max(1, min(4, absint($_POST['option_group_max'] ?? 1))));
        $group = [
            'id' => sanitize_key('groupe-' . wp_generate_uuid4()),
            'label' => $label,
            'display' => $display,
            'max' => $max,
            'required' => !empty($_POST['option_group_required']),
            'options' => $options,
        ];
        $groups = Keleva_Product_Options::normalize_groups(array_merge(Keleva_Product_Options::groups_for($product), [$group]));
        if (count($groups) > 8) {
            return new WP_Error('keleva_native_option_groups_limit', __('Un produit peut avoir au maximum 8 groupes de choix.', 'keleva-woo-addons'));
        }
        update_post_meta($product_id, '_keleva_product_option_groups', wp_slash(wp_json_encode($groups)));
        if (class_exists('Keleva_Category_Service')) {
            Keleva_Category_Service::mark_custom($product);
        }
        Keleva_Dashboard_Audit_Log::record('merchant_native_product_options_added', ['product_id' => $product_id, 'group' => $label, 'options' => count($options)], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function delete_product_option_group(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.write', 'pricing.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!class_exists('Keleva_Product_Options')) {
            return new WP_Error('keleva_native_options_unavailable', __('La gestion des options n’est pas disponible.', 'keleva-woo-addons'));
        }
        $product_id = absint($_POST['product_id'] ?? 0);
        $group_id = sanitize_key((string) wp_unslash($_POST['option_group_id'] ?? ''));
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : false;
        if (!$product || '' === $group_id) {
            return new WP_Error('keleva_native_option_group_missing', __('Groupe de choix introuvable.', 'keleva-woo-addons'));
        }
        $before = Keleva_Product_Options::groups_for($product);
        $groups = array_values(array_filter($before, static fn (array $group): bool => $group_id !== (string) ($group['id'] ?? '')));
        if (count($groups) === count($before)) {
            return new WP_Error('keleva_native_option_group_missing', __('Groupe de choix introuvable.', 'keleva-woo-addons'));
        }
        if ($groups) {
            update_post_meta($product_id, '_keleva_product_option_groups', wp_slash(wp_json_encode($groups)));
        } else {
            delete_post_meta($product_id, '_keleva_product_option_groups');
        }
        if (class_exists('Keleva_Category_Service')) {
            Keleva_Category_Service::mark_custom($product);
        }
        Keleva_Dashboard_Audit_Log::record('merchant_native_product_options_deleted', ['product_id' => $product_id, 'group_id' => $group_id], (string) $session['username']);
        return true;
    }

    /** @param array<string, mixed> $session */
    private static function save_product_variants(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['catalog.write', 'pricing.write', 'inventory.write']);
        if (is_wp_error($allowed)) return $allowed;
        $product_id = absint($_POST['product_id'] ?? 0);
        $product = function_exists('wc_get_product') ? wc_get_product($product_id) : false;
        if (!$product || $product->is_type('variation')) return new WP_Error('keleva_native_variant_product_missing', __('Produit introuvable.', 'keleva-woo-addons'));
        if ($product->is_type('variable') && '1' !== (string) get_post_meta($product_id, '_keleva_native_variants', true)) return new WP_Error('keleva_native_variant_product_protected', __('Ce produit possède déjà des variantes gérées hors du portail. Elles sont protégées ici.', 'keleva-woo-addons'));
        $attribute_name = sanitize_text_field((string) wp_unslash($_POST['variant_attribute_name'] ?? ''));
        if ('' === $attribute_name) return new WP_Error('keleva_native_variant_attribute_required', __('Indiquez le nom du choix, par exemple Taille ou Cuisson.', 'keleva-woo-addons'));
        $labels = isset($_POST['variant_label']) && is_array($_POST['variant_label']) ? array_map(static fn ($value): string => sanitize_text_field((string) wp_unslash($value)), $_POST['variant_label']) : [];
        $prices = isset($_POST['variant_price']) && is_array($_POST['variant_price']) ? $_POST['variant_price'] : [];
        $stocks = isset($_POST['variant_stock']) && is_array($_POST['variant_stock']) ? $_POST['variant_stock'] : [];
        $availability = isset($_POST['variant_available']) && is_array($_POST['variant_available']) ? $_POST['variant_available'] : [];
        $ids = isset($_POST['variant_id']) && is_array($_POST['variant_id']) ? $_POST['variant_id'] : [];
        $rows = [];
        foreach (array_slice($labels, 0, 16) as $index => $label) {
            if ('' === $label) continue;
            $raw_price = trim((string) wp_unslash($prices[$index] ?? ''));
            if ('' === $raw_price) return new WP_Error('keleva_native_variant_price_required', __('Chaque variante doit avoir un prix.', 'keleva-woo-addons'));
            $rows[] = ['id' => absint($ids[$index] ?? 0), 'label' => $label, 'price' => max(0, (float) wc_format_decimal($raw_price)), 'stock' => max(0, absint($stocks[$index] ?? 0)), 'available' => 'available' === sanitize_key((string) wp_unslash($availability[$index] ?? 'available'))];
        }
        if (!$rows) return new WP_Error('keleva_native_variant_required', __('Ajoutez au moins une variante.', 'keleva-woo-addons'));
        if (!$product->is_type('variable')) {
            wp_set_object_terms($product_id, 'variable', 'product_type', false);
            wc_delete_product_transients($product_id);
            clean_post_cache($product_id);
            $product = new WC_Product_Variable($product_id);
        }
        if (!$product instanceof WC_Product_Variable) return new WP_Error('keleva_native_variant_type_failed', __('Le produit ne peut pas devenir variable.', 'keleva-woo-addons'));
        $attribute_key = sanitize_title($attribute_name);
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0); $attribute->set_name($attribute_name); $attribute->set_options(array_values(array_unique(array_column($rows, 'label')))); $attribute->set_position(0); $attribute->set_visible(true); $attribute->set_variation(true);
        $product->set_attributes([$attribute_key => $attribute]);
        $product->save();
        $existing_ids = array_map('absint', $product->get_children());
        $kept_ids = [];
        foreach ($rows as $row) {
            $variation = $row['id'] ? wc_get_product((int) $row['id']) : new WC_Product_Variation();
            if (!$variation instanceof WC_Product_Variation || ($variation->get_id() && ($variation->get_parent_id() !== $product_id || '1' !== (string) get_post_meta($variation->get_id(), '_keleva_native_variant', true)))) $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id); $variation->set_attributes([$attribute_key => $row['label']]); $variation->set_regular_price((string) $row['price']); $variation->set_price((string) $row['price']); $variation->set_manage_stock(true); $variation->set_stock_quantity($row['stock']); $variation->set_stock_status($row['available'] && $row['stock'] > 0 ? 'instock' : 'outofstock'); $variation->set_status('publish');
            $variation_id = (int) $variation->save(); update_post_meta($variation_id, '_keleva_native_variant', '1'); $kept_ids[] = $variation_id;
        }
        foreach ($existing_ids as $variation_id) if (!in_array($variation_id, $kept_ids, true) && '1' === (string) get_post_meta($variation_id, '_keleva_native_variant', true)) wp_delete_post($variation_id, true);
        update_post_meta($product_id, '_keleva_native_variants', '1'); wc_delete_product_transients($product_id); $product = new WC_Product_Variable($product_id); $product->sync($product); $product->save();
        Keleva_Dashboard_Audit_Log::record('merchant_native_product_variants_saved', ['product_id' => $product_id, 'attribute' => $attribute_name, 'variants' => count($rows)], (string) $session['username']);
        return true;
    }

    /** @return array{attribute_name:string,rows:array<int, array{id:int,label:string,price:string,stock:int,available:bool}>} */
    private static function native_variants_for(WC_Product $product): array {
        $result = ['attribute_name' => '', 'rows' => []];
        if (!$product->is_type('variable') || '1' !== (string) get_post_meta($product->get_id(), '_keleva_native_variants', true)) return $result;
        $attributes = $product->get_attributes(); $first_attribute = $attributes ? reset($attributes) : false;
        if ($first_attribute instanceof WC_Product_Attribute) $result['attribute_name'] = (string) $first_attribute->get_name();
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation instanceof WC_Product_Variation || '1' !== (string) get_post_meta($variation_id, '_keleva_native_variant', true)) continue;
            $attributes = $variation->get_attributes(); $label = $attributes ? (string) reset($attributes) : '';
            if ('' === $label) continue;
            $result['rows'][] = ['id' => (int) $variation->get_id(), 'label' => $label, 'price' => (string) $variation->get_regular_price(), 'stock' => max(0, (int) ($variation->get_stock_quantity() ?? 0)), 'available' => $variation->is_in_stock()];
        }
        return $result;
    }

    private static function assign_category(int $product_id, int $category_id): void {
        if ($category_id > 0 && term_exists($category_id, 'product_cat')) {
            wp_set_object_terms($product_id, [$category_id], 'product_cat', false);
        }
    }

    private static function save_product_image(int $product_id): true|WP_Error {
        if (empty($_FILES['image']) || !is_array($_FILES['image']) || empty($_FILES['image']['name']) || !empty($_FILES['image']['error'])) {
            return true;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = media_handle_upload('image', $product_id);
        if (is_wp_error($attachment_id)) {
            return new WP_Error('keleva_native_image_failed', __('La photo n’a pas pu être enregistrée. Utilisez une image JPG, PNG, WebP ou AVIF valide.', 'keleva-woo-addons'));
        }
        $alt = sanitize_text_field((string) wp_unslash($_POST['image_alt'] ?? ''));
        if ('' !== $alt) {
            update_post_meta((int) $attachment_id, '_wp_attachment_image_alt', $alt);
        }
        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_image_id((int) $attachment_id);
            $product->save();
        }
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function update_order(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['orders.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        $order_id = absint($_POST['order_id'] ?? 0);
        $status = sanitize_key((string) wp_unslash($_POST['status'] ?? ''));
        if (!in_array($status, ['pending', 'on-hold', 'processing', 'completed', 'cancelled'], true)) {
            return new WP_Error('keleva_native_order_status_invalid', __('Ce statut de commande n’est pas autorisé.', 'keleva-woo-addons'));
        }
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : false;
        if (!$order) {
            return new WP_Error('keleva_native_order_missing', __('Commande introuvable.', 'keleva-woo-addons'));
        }
        $order->update_status($status, __('Statut modifié depuis le portail marchand Keleva.', 'keleva-woo-addons'), false);
        Keleva_Dashboard_Audit_Log::record('merchant_native_order_updated', ['order_id' => $order_id, 'status' => $status], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function update_palette(array $session): true|WP_Error {
        $allowed = self::require_permissions($session, ['palettes.write']);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!function_exists('keleva_woo_palettes')) {
            return new WP_Error('keleva_native_palettes_unavailable', __('Les palettes Keleva ne sont pas disponibles dans le thème actif.', 'keleva-woo-addons'));
        }
        $palette = sanitize_key((string) wp_unslash($_POST['palette'] ?? ''));
        $palettes = keleva_woo_palettes();
        if (!array_key_exists($palette, $palettes)) {
            return new WP_Error('keleva_native_palette_invalid', __('Cette palette n’existe pas.', 'keleva-woo-addons'));
        }
        $before = function_exists('keleva_woo_active_palette_id') ? keleva_woo_active_palette_id() : '';
        set_theme_mod('keleva_palette', $palette);
        Keleva_Dashboard_Audit_Log::record('merchant_native_palette_updated', ['from' => $before, 'to' => $palette], (string) $session['username']);
        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function nonce_action(array $session): string {
        return 'keleva_native_portal_' . hash('sha256', (string) $session['token']);
    }

    private static function redirect_with_notice(string $type, string $message): never {
        wp_safe_redirect(add_query_arg([
            'keleva_notice' => sanitize_key($type),
            'keleva_message' => rawurlencode($message),
        ], self::url()));
        exit;
    }

    private static function success_message_for(string $action): string {
        return match ($action) {
            'logout' => __('Déconnexion Keleva terminée.', 'keleva-woo-addons'),
            'create_product' => __('Produit de test créé.', 'keleva-woo-addons'),
            'update_product' => __('Produit mis à jour.', 'keleva-woo-addons'),
            'delete_product' => __('Produit de test supprimé.', 'keleva-woo-addons'),
            'create_category' => __('Catégorie créée. Vous pouvez maintenant la choisir pour un produit.', 'keleva-woo-addons'),
            'add_product_option_group' => __('Choix et suppléments enregistrés pour ce produit.', 'keleva-woo-addons'),
            'delete_product_option_group' => __('Groupe de choix retiré du produit.', 'keleva-woo-addons'),
            'save_product_variants' => __('Variantes et stocks enregistrés pour ce produit.', 'keleva-woo-addons'),
            'update_order' => __('Statut de commande mis à jour.', 'keleva-woo-addons'),
            'update_palette' => __('Palette appliquée.', 'keleva-woo-addons'),
            default => __('Action enregistrée.', 'keleva-woo-addons'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function access_config(): array {
        $access = get_option(self::ACCESS_OPTION, []);
        return is_array($access) ? $access : [];
    }

    /**
     * @param array<string, mixed>|null $session
     */
    private static function render_document_start(?array $session): void {
        $title = null === $session ? __('Connexion Keleva', 'keleva-woo-addons') : __('Portail marchand Keleva', 'keleva-woo-addons');
        $language = get_bloginfo('language') ?: 'fr';
        $direction = is_rtl() ? 'rtl' : 'ltr';
        $rtl_font = '';
        if (is_rtl()) {
            $arabic_font = esc_url(KELEVA_WOO_ADDONS_URL . 'assets/fonts/noto-sans-arabic-arabic.woff2');
            $latin_font = esc_url(KELEVA_WOO_ADDONS_URL . 'assets/fonts/noto-sans-arabic-latin.woff2');
            $rtl_font = '<style>@font-face{font-family:"Noto Sans Arabic";font-style:normal;font-weight:400 800;font-display:swap;src:url("' . $arabic_font . '") format("woff2");unicode-range:U+0600-06FF,U+0750-077F,U+0870-08FF,U+200C-200E,U+FB50-FDFF,U+FE70-FEFC}@font-face{font-family:"Noto Sans Arabic";font-style:normal;font-weight:400 800;font-display:swap;src:url("' . $latin_font . '") format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}</style>';
        }
        echo '<!doctype html><html lang="' . esc_attr($language) . '" dir="' . esc_attr($direction) . '"><head><meta charset="' . esc_attr(get_bloginfo('charset')) . '"><meta name="viewport" content="width=device-width,initial-scale=1">' . $rtl_font . '<title>' . esc_html($title) . '</title><style>' . self::styles() . self::rtl_styles() . '</style></head><body><main class="kp-shell">';
    }

    private static function render_document_end(): void {
        $session = self::current_session();
        if (null !== $session) {
            self::render_commerce_manager($session);
        }
        self::render_delete_confirmation_dialog();
        echo '</main></body></html>';
    }

    private static function render_delete_confirmation_dialog(): void {
        ?>
        <dialog id="kp-delete-dialog" aria-labelledby="kp-delete-title" style="width:min(92vw,460px);border:0;border-radius:20px;padding:0;box-shadow:0 24px 70px rgba(25,20,15,.30);background:#fffdf8;color:#2b2521;">
            <div style="padding:28px;">
                <p style="margin:0 0 8px;font-size:11px;font-weight:800;letter-spacing:.12em;color:#a74732;">SUPPRESSION DE TEST</p>
                <h2 id="kp-delete-title" style="margin:0;font-family:Georgia,serif;font-size:27px;">Supprimer ce produit ?</h2>
                <p style="margin:12px 0 20px;line-height:1.55;color:#655b53;">Cette action retire définitivement ce produit de test du staging. Les autres produits ne seront pas modifiés.</p>
                <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                    <button id="kp-delete-cancel" type="button" style="border:1px solid #ded7cc;border-radius:10px;background:#fffdf8;color:#332b25;padding:11px 15px;font-weight:700;cursor:pointer;">Annuler</button>
                    <button id="kp-delete-confirm" type="button" style="border:0;border-radius:10px;background:#a74732;color:#fff;padding:11px 15px;font-weight:700;cursor:pointer;">Oui, supprimer le produit test</button>
                </div>
            </div>
        </dialog>
        <script>
        (() => {
            const dialog = document.getElementById('kp-delete-dialog');
            const cancel = document.getElementById('kp-delete-cancel');
            const confirm = document.getElementById('kp-delete-confirm');
            let formToDelete = null;
            if (!dialog || !cancel || !confirm) return;
            document.querySelectorAll('.kp-danger-form .kp-danger').forEach((button) => {
                button.addEventListener('click', () => {
                    formToDelete = button.closest('form');
                    if (formToDelete) dialog.showModal();
                });
            });
            cancel.addEventListener('click', () => dialog.close());
            dialog.addEventListener('cancel', (event) => {
                event.preventDefault();
                dialog.close();
            });
            confirm.addEventListener('click', () => {
                if (!formToDelete) return;
                confirm.disabled = true;
                formToDelete.submit();
            });
        })();
        </script>
        <?php
    }

    private static function render_notice(): void {
        $type = sanitize_key((string) wp_unslash($_GET['keleva_notice'] ?? ''));
        $message = rawurldecode(sanitize_text_field((string) wp_unslash($_GET['keleva_message'] ?? '')));
        if ('' === $message || !in_array($type, ['success', 'error'], true)) {
            return;
        }
        echo '<div class="kp-notice kp-notice-' . esc_attr($type) . '" role="status">' . esc_html($message) . '</div>';
    }

    private static function render_login(): void {
        $configured = !empty(self::access_config()['password_hash']);
        ?>
        <section class="kp-login-grid">
            <div class="kp-brand-panel">
                <div class="kp-mark">K</div><p class="kp-overline">KELEVA · ESPACE MARCHAND</p>
                <h1><?php esc_html_e('Votre activité, sans le technique.', 'keleva-woo-addons'); ?></h1>
                <p><?php esc_html_e('Ajoutez un produit, adaptez le prix ou consultez une commande en quelques gestes simples.', 'keleva-woo-addons'); ?></p>
                <div class="kp-stat-row"><span><strong>01</strong><?php esc_html_e('Portail unique', 'keleva-woo-addons'); ?></span><span><strong>08h</strong><?php esc_html_e('Session Keleva', 'keleva-woo-addons'); ?></span><span><strong>0</strong><?php esc_html_e('Outil technique', 'keleva-woo-addons'); ?></span></div>
            </div>
            <div class="kp-login-card">
                <p class="kp-overline kp-warm"><?php esc_html_e('ACCÈS MARCHAND', 'keleva-woo-addons'); ?></p>
                <h2><?php esc_html_e('Bienvenue.', 'keleva-woo-addons'); ?></h2>
                <p><?php esc_html_e('Connectez-vous avec vos identifiants Keleva dédiés.', 'keleva-woo-addons'); ?></p>
                <?php self::render_notice(); ?>
                <?php if (!$configured) : ?>
                    <div class="kp-notice kp-notice-error"><strong><?php esc_html_e('Accès en préparation.', 'keleva-woo-addons'); ?></strong><br><?php esc_html_e('Le propriétaire doit créer le premier identifiant Keleva dans les réglages sécurisés du site.', 'keleva-woo-addons'); ?></div>
                <?php else : ?>
                    <form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-form">
                        <input type="hidden" name="keleva_native_action" value="login">
                        <label><?php esc_html_e('Identifiant portail', 'keleva-woo-addons'); ?><input type="text" name="username" required autocomplete="username" placeholder="ex. keleva.marchand"></label>
                        <label><?php esc_html_e('Mot de passe', 'keleva-woo-addons'); ?><input type="password" name="password" required autocomplete="current-password" placeholder="Votre mot de passe"></label>
                        <button type="submit" class="kp-primary"><?php esc_html_e('Entrer dans mon espace', 'keleva-woo-addons'); ?></button>
                    </form>
                <?php endif; ?>
                <p class="kp-security-note"><?php esc_html_e('Ce portail est hébergé dans votre site Keleva. Il ne donne jamais accès à l’administration technique.', 'keleva-woo-addons'); ?></p>
            </div>
        </section>
        <?php
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function render_dashboard(array $session): void {
        $show_all_products = 'all' === sanitize_key((string) wp_unslash($_GET['keleva_catalog'] ?? ''));
        $catalog_search = sanitize_text_field((string) wp_unslash($_GET['keleva_search'] ?? ''));
        $product_limit = ($show_all_products || '' !== $catalog_search) ? 20 : 6;
        $product_query = ['limit' => $product_limit, 'status' => ['publish', 'draft'], 'orderby' => 'date', 'order' => 'DESC'];
        if ('' !== $catalog_search) {
            $product_query['s'] = $catalog_search;
        }
        $products = function_exists('wc_get_products') ? wc_get_products($product_query) : [];
        $product_counts = wp_count_posts('product');
        $catalog_total = (int) ($product_counts->publish ?? 0) + (int) ($product_counts->draft ?? 0);
        $orders = function_exists('wc_get_orders') ? wc_get_orders(['limit' => 8, 'orderby' => 'date', 'order' => 'DESC']) : [];
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $palettes = function_exists('keleva_woo_palettes') ? keleva_woo_palettes() : [];
        $active_palette = function_exists('keleva_woo_active_palette_id') ? keleva_woo_active_palette_id() : '';
        $nonce = wp_create_nonce(self::nonce_action($session));
        ?>
        <header class="kp-header"><a href="<?php echo esc_url(self::url()); ?>" class="kp-logo"><span class="kp-mark">K</span><span><strong>Keleva</strong><small><?php esc_html_e('Portail marchand', 'keleva-woo-addons'); ?></small></span></a><nav><a href="#ajouter"><?php esc_html_e('Ajouter un produit', 'keleva-woo-addons'); ?></a><a href="#produits"><?php esc_html_e('Produits & stock', 'keleva-woo-addons'); ?></a><a href="#commandes"><?php esc_html_e('Commandes', 'keleva-woo-addons'); ?></a><a href="#apparence"><?php esc_html_e('Apparence', 'keleva-woo-addons'); ?></a></nav><form method="post" action="<?php echo esc_url(self::url()); ?>"><input type="hidden" name="keleva_native_action" value="logout"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><button class="kp-quiet" type="submit"><?php esc_html_e('Déconnexion', 'keleva-woo-addons'); ?></button></form></header>
        <section class="kp-welcome"><div><p class="kp-overline kp-warm"><?php esc_html_e('ESPACE DE STAGING', 'keleva-woo-addons'); ?></p><h1><?php printf(esc_html__('Bonjour %s, que voulez-vous faire ?', 'keleva-woo-addons'), esc_html((string) $session['username'])); ?></h1><p><?php esc_html_e('Les actions essentielles sont présentées clairement. Les réglages techniques restent hors de cet espace.', 'keleva-woo-addons'); ?></p></div><a class="kp-primary kp-link" href="#ajouter"><?php esc_html_e('Ajouter un produit', 'keleva-woo-addons'); ?></a></section>
        <?php self::render_notice(); ?>
        <section id="ajouter" class="kp-card"><div class="kp-card-head"><div><p class="kp-overline kp-warm"><?php esc_html_e('ÉTAPE 1', 'keleva-woo-addons'); ?></p><h2><?php esc_html_e('Ajouter un produit', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Commencez simplement. Le brouillon évite toute publication involontaire.', 'keleva-woo-addons'); ?></p></div></div>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(self::url()); ?>" class="kp-form kp-product-form"><input type="hidden" name="keleva_native_action" value="create_product"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><label><?php esc_html_e('Nom du produit', 'keleva-woo-addons'); ?><input name="name" required maxlength="180" placeholder="Ex. Brunch du dimanche"></label><label><?php esc_html_e('Catégorie', 'keleva-woo-addons'); ?><select name="category_id"><option value="0"><?php esc_html_e('Sans catégorie', 'keleva-woo-addons'); ?></option><?php foreach (is_array($categories) ? $categories : [] as $category) : ?><option value="<?php echo esc_attr((string) $category->term_id); ?>"><?php echo esc_html($category->name); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e('Prix', 'keleva-woo-addons'); ?><input name="regular_price" inputmode="decimal" required placeholder="Ex. 49.00"></label><label><?php esc_html_e('Stock', 'keleva-woo-addons'); ?><input name="stock_quantity" type="number" min="0" step="1" value="0" required></label><label class="kp-wide"><?php esc_html_e('Description (facultative)', 'keleva-woo-addons'); ?><textarea name="description" rows="3" placeholder="Décrivez le produit simplement."></textarea></label><label><?php esc_html_e('Photo (facultative)', 'keleva-woo-addons'); ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif"></label><label><?php esc_html_e('Texte de la photo (facultatif)', 'keleva-woo-addons'); ?><input name="image_alt" maxlength="125" placeholder="Ex. Assiette brunch"></label><fieldset class="kp-wide"><legend><?php esc_html_e('État', 'keleva-woo-addons'); ?></legend><label class="kp-radio"><input type="radio" name="status" value="draft" checked> <?php esc_html_e('Brouillon — vérifier avant publication', 'keleva-woo-addons'); ?></label><label class="kp-radio"><input type="radio" name="status" value="publish"> <?php esc_html_e('Actif — visible dans le staging', 'keleva-woo-addons'); ?></label></fieldset><button class="kp-primary kp-wide" type="submit"><?php esc_html_e('Créer le produit de test', 'keleva-woo-addons'); ?></button></form>
        </section>
        <section id="produits" class="kp-card"><div class="kp-card-head kp-product-head"><div><p class="kp-overline kp-warm"><?php esc_html_e('ÉTAPE 2', 'keleva-woo-addons'); ?></p><h2><?php esc_html_e('Produits & stock', 'keleva-woo-addons'); ?></h2><p><?php echo esc_html('' !== $catalog_search ? sprintf(__('Résultats pour « %s ».', 'keleva-woo-addons'), $catalog_search) : __('Les 6 produits les plus récents sont affichés pour garder cette page simple. Ouvrez un produit seulement si vous voulez le modifier.', 'keleva-woo-addons')); ?></p></div><?php if ($catalog_total > 6 && '' === $catalog_search) : ?><div class="kp-catalog-control"><?php if ($show_all_products) : ?><a class="kp-quiet" href="<?php echo esc_url(self::url() . '#produits'); ?>"><?php esc_html_e('Afficher seulement les 6 derniers', 'keleva-woo-addons'); ?></a><?php else : ?><a class="kp-quiet" href="<?php echo esc_url(add_query_arg('keleva_catalog', 'all', self::url()) . '#produits'); ?>"><?php printf(esc_html__('Voir les %d derniers produits', 'keleva-woo-addons'), min(20, $catalog_total)); ?></a><?php endif; ?></div><?php endif; ?></div><form method="get" action="<?php echo esc_url(self::url()); ?>" class="kp-form" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-top:18px"><input type="hidden" name="keleva_catalog" value="all"><label style="flex:1;min-width:220px"><?php esc_html_e('Rechercher un produit', 'keleva-woo-addons'); ?><input type="search" name="keleva_search" value="<?php echo esc_attr($catalog_search); ?>" placeholder="Ex. brunch, tajine, pizza"></label><button type="submit" class="kp-quiet"><?php esc_html_e('Rechercher', 'keleva-woo-addons'); ?></button><?php if ('' !== $catalog_search) : ?><a class="kp-quiet" href="<?php echo esc_url(self::url() . '#produits'); ?>"><?php esc_html_e('Effacer', 'keleva-woo-addons'); ?></a><?php endif; ?></form>
            <?php if (!$products) : ?><p class="kp-empty"><?php esc_html_e('Aucun produit à afficher.', 'keleva-woo-addons'); ?></p><?php endif; ?>
            <div class="kp-product-list"><?php foreach (is_array($products) ? $products : [] as $product) : $product_id = $product->get_id(); $image = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'); $price_label = '' === (string) $product->get_price() ? __('Prix non défini', 'keleva-woo-addons') : number_format_i18n((float) $product->get_price(), wc_get_price_decimals()) . ' ' . get_woocommerce_currency_symbol(); $stock_quantity = $product->get_stock_quantity(); $stock_label = $product->managing_stock() ? sprintf(__('Stock : %s', 'keleva-woo-addons'), number_format_i18n((int) ($stock_quantity ?? 0))) : ($product->is_in_stock() ? __('En stock', 'keleva-woo-addons') : __('Rupture de stock', 'keleva-woo-addons')); ?><article class="kp-product"><div class="kp-product-title"><?php if ($image) : ?><img src="<?php echo esc_url($image); ?>" alt=""><?php else : ?><span class="kp-image-placeholder"><?php esc_html_e('Photo', 'keleva-woo-addons'); ?></span><?php endif; ?><div><h3><?php echo esc_html($product->get_name()); ?></h3><p><?php echo esc_html('publish' === $product->get_status() ? __('Actif', 'keleva-woo-addons') : __('Brouillon', 'keleva-woo-addons')); ?> · <?php echo esc_html($price_label); ?> · <strong><?php echo esc_html($stock_label); ?></strong></p></div></div><details><summary><?php esc_html_e('Modifier ce produit', 'keleva-woo-addons'); ?></summary><form method="post" enctype="multipart/form-data" action="<?php echo esc_url(self::url()); ?>" class="kp-form kp-product-form"><input type="hidden" name="keleva_native_action" value="update_product"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product_id); ?>"><label><?php esc_html_e('Prix', 'keleva-woo-addons'); ?><input name="regular_price" inputmode="decimal" value="<?php echo esc_attr((string) $product->get_regular_price()); ?>" required></label><label><?php esc_html_e('Stock', 'keleva-woo-addons'); ?><input name="stock_quantity" type="number" min="0" step="1" value="<?php echo esc_attr((string) ($product->get_stock_quantity() ?? 0)); ?>" required></label><label><?php esc_html_e('État', 'keleva-woo-addons'); ?><select name="status"><option value="draft" <?php selected($product->get_status(), 'draft'); ?>><?php esc_html_e('Brouillon', 'keleva-woo-addons'); ?></option><option value="publish" <?php selected($product->get_status(), 'publish'); ?>><?php esc_html_e('Actif', 'keleva-woo-addons'); ?></option></select></label><label><?php esc_html_e('Catégorie', 'keleva-woo-addons'); ?><select name="category_id"><option value="0"><?php esc_html_e('Ne pas modifier', 'keleva-woo-addons'); ?></option><?php foreach (is_array($categories) ? $categories : [] as $category) : ?><option value="<?php echo esc_attr((string) $category->term_id); ?>"><?php echo esc_html($category->name); ?></option><?php endforeach; ?></select></label><label class="kp-wide"><?php esc_html_e('Description', 'keleva-woo-addons'); ?><textarea name="description" rows="3"><?php echo esc_textarea(wp_strip_all_tags($product->get_description())); ?></textarea></label><label><?php esc_html_e('Nouvelle photo (facultative)', 'keleva-woo-addons'); ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif"></label><label><?php esc_html_e('Texte de la photo', 'keleva-woo-addons'); ?><input name="image_alt" maxlength="125"></label><button class="kp-primary kp-wide" type="submit"><?php esc_html_e('Enregistrer le produit', 'keleva-woo-addons'); ?></button></form><?php if ('1' === (string) get_post_meta($product_id, '_keleva_native_test', true)) : ?><form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-danger-form"><input type="hidden" name="keleva_native_action" value="delete_product"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product_id); ?>"><button type="button" class="kp-danger"><?php esc_html_e('Supprimer le produit de test', 'keleva-woo-addons'); ?></button></form><?php endif; ?></details></article><?php endforeach; ?></div>
        </section>
        <section id="commandes" class="kp-card"><div class="kp-card-head"><div><p class="kp-overline kp-warm"><?php esc_html_e('ÉTAPE 3', 'keleva-woo-addons'); ?></p><h2><?php esc_html_e('Commandes', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Aucun paiement ni message n’est envoyé depuis cette page.', 'keleva-woo-addons'); ?></p></div></div><div class="kp-orders"><?php if (!$orders) : ?><p class="kp-empty"><?php esc_html_e('Aucune commande à afficher.', 'keleva-woo-addons'); ?></p><?php endif; ?><?php foreach (is_array($orders) ? $orders : [] as $order) : ?><form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-order"><input type="hidden" name="keleva_native_action" value="update_order"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><input type="hidden" name="order_id" value="<?php echo esc_attr((string) $order->get_id()); ?>"><div><strong>#<?php echo esc_html($order->get_order_number()); ?></strong><small><?php echo esc_html($order->get_date_created() ? $order->get_date_created()->date_i18n('d/m/Y H:i') : ''); ?></small></div><span><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span><select name="status"><option value="pending" <?php selected($order->get_status(), 'pending'); ?>><?php esc_html_e('En attente', 'keleva-woo-addons'); ?></option><option value="on-hold" <?php selected($order->get_status(), 'on-hold'); ?>><?php esc_html_e('En pause', 'keleva-woo-addons'); ?></option><option value="processing" <?php selected($order->get_status(), 'processing'); ?>><?php esc_html_e('En préparation', 'keleva-woo-addons'); ?></option><option value="completed" <?php selected($order->get_status(), 'completed'); ?>><?php esc_html_e('Terminée', 'keleva-woo-addons'); ?></option><option value="cancelled" <?php selected($order->get_status(), 'cancelled'); ?>><?php esc_html_e('Annulée', 'keleva-woo-addons'); ?></option></select><button class="kp-quiet" type="submit"><?php esc_html_e('Valider', 'keleva-woo-addons'); ?></button></form><?php endforeach; ?></div></section>
        <section id="apparence" class="kp-card"><div class="kp-card-head"><div><p class="kp-overline kp-warm"><?php esc_html_e('ÉTAPE 4', 'keleva-woo-addons'); ?></p><h2><?php esc_html_e('Apparence', 'keleva-woo-addons'); ?></h2><p><?php esc_html_e('Choisissez une palette Keleva validée. Les couleurs techniques restent cachées.', 'keleva-woo-addons'); ?></p></div></div><?php if (!$palettes) : ?><p class="kp-empty"><?php esc_html_e('Les palettes ne sont pas disponibles avec le thème actuellement actif.', 'keleva-woo-addons'); ?></p><?php else : ?><form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-palette-form"><input type="hidden" name="keleva_native_action" value="update_palette"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><?php foreach ($palettes as $palette_id => $palette) : ?><label class="kp-palette"><input type="radio" name="palette" value="<?php echo esc_attr((string) $palette_id); ?>" <?php checked((string) $palette_id, (string) $active_palette); ?>><span style="background:<?php echo esc_attr((string) ($palette['colors']['accent'] ?? '#b53d2b')); ?>"></span><?php echo esc_html((string) ($palette['label'] ?? $palette_id)); ?></label><?php endforeach; ?><button class="kp-primary" type="submit"><?php esc_html_e('Appliquer la palette', 'keleva-woo-addons'); ?></button></form><?php endif; ?></section>
        <section class="kp-card kp-audit"><div class="kp-card-head"><div><p class="kp-overline kp-warm"><?php esc_html_e('ACTIVITÉ', 'keleva-woo-addons'); ?></p><h2><?php esc_html_e('Dernières actions', 'keleva-woo-addons'); ?></h2></div></div><ul><?php foreach (array_slice(Keleva_Dashboard_Audit_Log::recent(12), 0, 8) as $event) : ?><li><strong><?php echo esc_html((string) $event['event']); ?></strong><span><?php echo esc_html((string) $event['at']); ?></span></li><?php endforeach; ?></ul></section>
        <?php
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function render_commerce_manager(array $session): void {
        $nonce = wp_create_nonce(self::nonce_action($session));
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $products = function_exists('wc_get_products') ? wc_get_products(['limit' => 6, 'status' => ['publish', 'draft'], 'orderby' => 'date', 'order' => 'DESC']) : [];
        echo '<style>' . self::commerce_styles() . '</style>';
        ?>
        <section id="commerce" class="kp-card kp-commerce-card">
            <div class="kp-card-head">
                <div>
                    <p class="kp-overline kp-warm"><?php esc_html_e('CATALOGUE ENRICHI', 'keleva-woo-addons'); ?></p>
                    <h2><?php esc_html_e('Catégories, variantes et suppléments', 'keleva-woo-addons'); ?></h2>
                    <p><?php esc_html_e('Créez une catégorie, gérez le prix et le stock de chaque variante, puis ajoutez des extras payants et des limites de sélection.', 'keleva-woo-addons'); ?></p>
                </div>
            </div>
            <div class="kp-commerce-grid">
                <form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-form kp-commerce-panel">
                    <input type="hidden" name="keleva_native_action" value="create_category">
                    <input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>">
                    <h3><?php esc_html_e('Nouvelle catégorie', 'keleva-woo-addons'); ?></h3>
                    <p><?php esc_html_e('Ex. Entrées, Burgers, Desserts ou Boissons.', 'keleva-woo-addons'); ?></p>
                    <label><?php esc_html_e('Nom de la catégorie', 'keleva-woo-addons'); ?><input name="category_name" required maxlength="80" placeholder="Ex. Desserts"></label>
                    <label><?php esc_html_e('Petite description (facultative)', 'keleva-woo-addons'); ?><textarea name="category_description" rows="2" placeholder="Ex. Gourmandises maison."></textarea></label>
                    <button class="kp-primary" type="submit"><?php esc_html_e('Créer la catégorie', 'keleva-woo-addons'); ?></button>
                </form>
                <aside class="kp-commerce-panel">
                    <h3><?php esc_html_e('Catégories disponibles', 'keleva-woo-addons'); ?></h3>
                    <p><?php esc_html_e('Elles apparaissent ensuite dans la fiche produit.', 'keleva-woo-addons'); ?></p>
                    <ul class="kp-category-list">
                        <?php foreach (array_slice(is_array($categories) ? $categories : [], 0, 12) as $category) : ?>
                            <li><span><?php echo esc_html($category->name); ?></span><small><?php printf(esc_html(_n('%d produit', '%d produits', (int) $category->count, 'keleva-woo-addons')), (int) $category->count); ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            </div>
            <div class="kp-options-intro">
                <h3><?php esc_html_e('Choix, variantes et suppléments', 'keleva-woo-addons'); ?></h3>
                <p><?php esc_html_e('Les variantes gèrent un prix, un stock et une disponibilité par option. Les groupes de choix servent aux tailles sans stock séparé, aux extras et aux suppléments.', 'keleva-woo-addons'); ?></p>
            </div>
            <div class="kp-commerce-product-list">
                <?php foreach (is_array($products) ? $products : [] as $product) : self::render_product_options_manager($product, $nonce); endforeach; ?>
            </div>
        </section>
        <?php self::render_commerce_builder_script(); ?>
        <?php
    }

    private static function render_product_options_manager(WC_Product $product, string $nonce): void {
        $groups = class_exists('Keleva_Product_Options') ? Keleva_Product_Options::groups_for($product) : [];
        $native_variants = self::native_variants_for($product);
        $external_variants = $product->is_type('variable') && '1' !== (string) get_post_meta($product->get_id(), '_keleva_native_variants', true);
        $price_label = '' === (string) $product->get_price() ? __('Prix non défini', 'keleva-woo-addons') : number_format_i18n((float) $product->get_price(), wc_get_price_decimals()) . ' ' . get_woocommerce_currency_symbol();
        ?>
        <details class="kp-commerce-product">
            <summary><span><strong><?php echo esc_html($product->get_name()); ?></strong><small><?php echo esc_html($price_label); ?> · <?php printf(esc_html(_n('%d groupe', '%d groupes', count($groups), 'keleva-woo-addons')), count($groups)); ?> · <?php printf(esc_html(_n('%d variante', '%d variantes', count($native_variants['rows']), 'keleva-woo-addons')), count($native_variants['rows'])); ?></small></span><span class="kp-summary-action"><?php esc_html_e('Gérer les choix', 'keleva-woo-addons'); ?></span></summary>
            <div class="kp-variant-manager">
                <h4><?php esc_html_e('Variantes avec stock', 'keleva-woo-addons'); ?></h4>
                <?php if ($external_variants) : ?>
                    <p class="kp-empty"><?php esc_html_e('Ce produit a déjà des variantes WooCommerce externes. Elles restent protégées dans cet espace.', 'keleva-woo-addons'); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e('Ex. Taille : Petit, Moyen, Grand. Chaque ligne possède son prix, son stock et sa disponibilité.', 'keleva-woo-addons'); ?></p>
                    <form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-form kp-variant-builder" data-kp-variant-builder>
                        <input type="hidden" name="keleva_native_action" value="save_product_variants"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product->get_id()); ?>">
                        <label><?php esc_html_e('Nom du choix', 'keleva-woo-addons'); ?><input name="variant_attribute_name" required maxlength="60" value="<?php echo esc_attr($native_variants['attribute_name']); ?>" placeholder="Ex. Taille ou Cuisson"></label>
                        <div class="kp-variant-rows" data-kp-variant-rows>
                            <?php $variant_rows = $native_variants['rows'] ?: [['id' => 0, 'label' => '', 'price' => '', 'stock' => 0, 'available' => true]]; foreach ($variant_rows as $row) : ?>
                                <div class="kp-variant-row" data-kp-variant-row><input type="hidden" name="variant_id[]" value="<?php echo esc_attr((string) $row['id']); ?>"><label><?php esc_html_e('Option', 'keleva-woo-addons'); ?><input name="variant_label[]" required maxlength="80" value="<?php echo esc_attr($row['label']); ?>" placeholder="Ex. Grand"></label><label><?php esc_html_e('Prix', 'keleva-woo-addons'); ?><input name="variant_price[]" required inputmode="decimal" value="<?php echo esc_attr($row['price']); ?>" placeholder="0.00"></label><label><?php esc_html_e('Stock', 'keleva-woo-addons'); ?><input name="variant_stock[]" required type="number" min="0" value="<?php echo esc_attr((string) $row['stock']); ?>"></label><label><?php esc_html_e('Disponibilité', 'keleva-woo-addons'); ?><select name="variant_available[]"><option value="available" <?php selected($row['available']); ?>><?php esc_html_e('Disponible', 'keleva-woo-addons'); ?></option><option value="unavailable" <?php selected(!$row['available']); ?>><?php esc_html_e('Indisponible', 'keleva-woo-addons'); ?></option></select></label><button type="button" class="kp-quiet" data-kp-remove-variant <?php disabled(count($variant_rows) === 1); ?>><?php esc_html_e('Retirer', 'keleva-woo-addons'); ?></button></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="kp-option-actions"><button type="button" class="kp-quiet" data-kp-add-variant><?php esc_html_e('Ajouter une variante', 'keleva-woo-addons'); ?></button><button class="kp-primary" type="submit"><?php esc_html_e('Enregistrer prix, stock et disponibilité', 'keleva-woo-addons'); ?></button></div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="kp-existing-groups">
                <?php if (!$groups) : ?><p class="kp-empty"><?php esc_html_e('Aucun choix supplémentaire pour le moment.', 'keleva-woo-addons'); ?></p><?php endif; ?>
                <?php foreach ($groups as $group) : ?>
                    <div class="kp-option-group-summary">
                        <div><strong><?php echo esc_html($group['label']); ?></strong><small><?php echo esc_html('radio' === $group['display'] ? __('Un choix', 'keleva-woo-addons') : sprintf(__('Jusqu’à %d choix', 'keleva-woo-addons'), (int) $group['max'])); ?><?php echo !empty($group['required']) ? esc_html__(' · obligatoire', 'keleva-woo-addons') : esc_html__(' · facultatif', 'keleva-woo-addons'); ?></small></div>
                        <ul><?php foreach ($group['options'] as $option) : ?><li><?php echo esc_html($option['label']); ?><?php if ((float) $option['price'] > 0) : ?><small>+<?php echo esc_html(number_format_i18n((float) $option['price'], wc_get_price_decimals())); ?> <?php echo esc_html(get_woocommerce_currency_symbol()); ?></small><?php else : ?><small><?php esc_html_e('Inclus', 'keleva-woo-addons'); ?></small><?php endif; ?></li><?php endforeach; ?></ul>
                        <form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-inline-form"><input type="hidden" name="keleva_native_action" value="delete_product_option_group"><input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>"><input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product->get_id()); ?>"><input type="hidden" name="option_group_id" value="<?php echo esc_attr((string) $group['id']); ?>"><button type="submit" class="kp-text-danger"><?php esc_html_e('Retirer ce groupe', 'keleva-woo-addons'); ?></button></form>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" action="<?php echo esc_url(self::url()); ?>" class="kp-form kp-option-builder" data-kp-option-builder>
                <input type="hidden" name="keleva_native_action" value="add_product_option_group">
                <input type="hidden" name="_keleva_native_nonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product->get_id()); ?>">
                <h4><?php esc_html_e('Ajouter un groupe de choix', 'keleva-woo-addons'); ?></h4>
                <div class="kp-option-builder-grid">
                    <label><?php esc_html_e('Nom du groupe', 'keleva-woo-addons'); ?><input name="option_group_label" required maxlength="80" placeholder="Ex. Taille, Sauce ou Extras"></label>
                    <label><?php esc_html_e('Type de choix', 'keleva-woo-addons'); ?><select name="option_group_display" data-kp-option-display><option value="radio"><?php esc_html_e('Un seul choix', 'keleva-woo-addons'); ?></option><option value="checkbox"><?php esc_html_e('Plusieurs choix', 'keleva-woo-addons'); ?></option></select></label>
                    <label><?php esc_html_e('Maximum', 'keleva-woo-addons'); ?><input name="option_group_max" type="number" min="1" max="4" value="1" data-kp-option-max disabled></label>
                    <label class="kp-check"><input type="checkbox" name="option_group_required" value="1"> <?php esc_html_e('Le client doit choisir', 'keleva-woo-addons'); ?></label>
                </div>
                <div class="kp-option-rows" data-kp-option-rows>
                    <div class="kp-option-row" data-kp-option-row><label><?php esc_html_e('Choix', 'keleva-woo-addons'); ?><input name="option_label[]" required maxlength="80" placeholder="Ex. Fromage supplémentaire"></label><label><?php esc_html_e('Supplément', 'keleva-woo-addons'); ?><input name="option_price[]" inputmode="decimal" value="0" placeholder="0.00"></label><button type="button" class="kp-quiet" data-kp-remove-option disabled><?php esc_html_e('Retirer', 'keleva-woo-addons'); ?></button></div>
                </div>
                <div class="kp-option-actions"><button type="button" class="kp-quiet" data-kp-add-option><?php esc_html_e('Ajouter un choix', 'keleva-woo-addons'); ?></button><button class="kp-primary" type="submit"><?php esc_html_e('Enregistrer les choix', 'keleva-woo-addons'); ?></button></div>
            </form>
        </details>
        <?php
    }

    private static function render_commerce_builder_script(): void {
        ?>
        <script>
        (() => {
            if (window.__kelevaCommerceBuilder) return;
            window.__kelevaCommerceBuilder = true;
            const optionRow = () => '<div class="kp-option-row" data-kp-option-row><label>Choix<input name="option_label[]" required maxlength="80" placeholder="Ex. Avocat"></label><label>Supplément<input name="option_price[]" inputmode="decimal" value="0" placeholder="0.00"></label><button type="button" class="kp-quiet" data-kp-remove-option>Retirer</button></div>';
            const variantRow = () => '<div class="kp-variant-row" data-kp-variant-row><input type="hidden" name="variant_id[]" value="0"><label>Option<input name="variant_label[]" required maxlength="80" placeholder="Ex. Grand"></label><label>Prix<input name="variant_price[]" required inputmode="decimal" placeholder="0.00"></label><label>Stock<input name="variant_stock[]" required type="number" min="0" value="0"></label><label>Disponibilité<select name="variant_available[]"><option value="available">Disponible</option><option value="unavailable">Indisponible</option></select></label><button type="button" class="kp-quiet" data-kp-remove-variant>Retirer</button></div>';
            document.querySelectorAll('[data-kp-option-builder]').forEach((builder) => {
                const display = builder.querySelector('[data-kp-option-display]');
                const max = builder.querySelector('[data-kp-option-max]');
                const rows = builder.querySelector('[data-kp-option-rows]');
                const sync = () => {
                    const multiple = display && display.value === 'checkbox';
                    if (max) { max.disabled = !multiple; if (!multiple) max.value = '1'; }
                };
                sync();
                display?.addEventListener('change', sync);
                builder.querySelector('[data-kp-add-option]')?.addEventListener('click', () => { if (rows && rows.children.length < 16) rows.insertAdjacentHTML('beforeend', optionRow()); });
                rows?.addEventListener('click', (event) => { const button = event.target.closest('[data-kp-remove-option]'); if (button && rows.children.length > 1) button.closest('[data-kp-option-row]')?.remove(); });
            });
            document.querySelectorAll('[data-kp-variant-builder]').forEach((builder) => {
                const rows = builder.querySelector('[data-kp-variant-rows]');
                builder.querySelector('[data-kp-add-variant]')?.addEventListener('click', () => { if (rows && rows.children.length < 16) rows.insertAdjacentHTML('beforeend', variantRow()); });
                rows?.addEventListener('click', (event) => { const button = event.target.closest('[data-kp-remove-variant]'); if (button && rows.children.length > 1) button.closest('[data-kp-variant-row]')?.remove(); });
            });
            document.querySelector('.kp-header nav')?.insertAdjacentHTML('beforeend', '<a href="#commerce">Catégories & options</a>');
        })();
        </script>
        <?php
    }

    private static function commerce_styles(): string {
        return '.kp-commerce-card{scroll-margin-top:22px}.kp-commerce-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:22px}.kp-commerce-panel{padding:20px;border:1px solid var(--line);border-radius:16px;background:#fffdfa}.kp-commerce-panel h3,.kp-options-intro h3{margin:0 0 7px;font-family:Georgia,serif;font-size:1.35rem}.kp-commerce-panel p,.kp-options-intro p{margin:0 0 16px;color:var(--muted);line-height:1.5}.kp-category-list{list-style:none;margin:0;padding:0;display:grid;gap:8px}.kp-category-list li{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--line);font-size:.9rem}.kp-category-list small,.kp-commerce-product summary small,.kp-option-group-summary small{color:var(--muted)}.kp-options-intro{margin-top:28px;padding-top:24px;border-top:1px solid var(--line)}.kp-commerce-product-list{display:grid;gap:12px;margin-top:16px}.kp-commerce-product{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:#fff}.kp-commerce-product>summary{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:15px 17px;cursor:pointer;list-style:none}.kp-commerce-product>summary::-webkit-details-marker{display:none}.kp-commerce-product summary strong,.kp-commerce-product summary small{display:block}.kp-summary-action{color:var(--accent);font-weight:800;font-size:.84rem}.kp-variant-manager{margin:16px 17px;padding:18px;border-radius:14px;background:#f1f7f3;border:1px solid #d6e7da}.kp-variant-manager h4{margin:0 0 7px;font-size:1rem}.kp-variant-manager>p{margin:0 0 14px;color:var(--muted);line-height:1.5}.kp-existing-groups{padding:0 17px 2px}.kp-option-group-summary{border-top:1px solid var(--line);padding:14px 0}.kp-option-group-summary>div{display:flex;justify-content:space-between;gap:10px}.kp-option-group-summary ul{display:flex;flex-wrap:wrap;gap:7px;margin:10px 0;padding:0;list-style:none}.kp-option-group-summary li{border-radius:999px;padding:5px 8px;background:#f6f1e8;font-size:.8rem}.kp-option-group-summary li small{margin-left:4px}.kp-inline-form{display:inline}.kp-text-danger{border:0;background:transparent;padding:0;color:var(--danger);font:700 .8rem inherit;cursor:pointer}.kp-option-builder,.kp-variant-builder{margin-top:15px;padding:18px;border-radius:14px;background:#f8f4ec}.kp-option-builder{margin:15px 17px 18px}.kp-option-builder h4{margin:0 0 15px;font-size:1rem}.kp-option-builder-grid{display:grid;grid-template-columns:1.4fr 1fr .6fr 1fr;gap:12px;align-items:end}.kp-check{display:flex!important;align-items:center;gap:8px;padding-bottom:12px}.kp-check input{width:auto!important}.kp-option-rows,.kp-variant-rows{display:grid;gap:10px;margin-top:14px}.kp-option-row{display:grid;grid-template-columns:1.3fr .7fr auto;gap:10px;align-items:end}.kp-variant-row{display:grid;grid-template-columns:1.2fr .8fr .7fr 1fr auto;gap:10px;align-items:end}.kp-option-actions{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-top:16px}@media(max-width:800px){.kp-commerce-grid,.kp-option-builder-grid,.kp-option-row,.kp-variant-row{grid-template-columns:1fr}.kp-check{padding-bottom:0}.kp-commerce-product>summary{align-items:flex-start;flex-direction:column}.kp-option-actions{align-items:stretch}.kp-option-actions .kp-primary,.kp-option-actions .kp-quiet{width:100%;text-align:center}}';
    }

    private static function rtl_styles(): string {
        if (!is_rtl()) {
            return '';
        }

        return 'html[dir="rtl"]{direction:rtl}html[dir="rtl"] body{font-family:"Noto Sans Arabic",Arial,sans-serif;text-align:start}html[dir="rtl"] :is(.kp-brand-panel h1,.kp-welcome h1,.kp-login-card h2,.kp-card-head h2,.kp-commerce-panel h3,.kp-options-intro h3){font-family:"Noto Sans Arabic",Arial,sans-serif;letter-spacing:normal}html[dir="rtl"] .kp-radio input{margin-left:6px;margin-right:0}html[dir="rtl"] .kp-option-group-summary li small{margin-right:4px;margin-left:0}html[dir="rtl"] .kp-header nav,html[dir="rtl"] .kp-stat-row,html[dir="rtl"] .kp-palette-form{direction:rtl}html[dir="rtl"] .kp-notice,html[dir="rtl"] .kp-form,html[dir="rtl"] .kp-card,html[dir="rtl"] .kp-product,html[dir="rtl"] .kp-order,html[dir="rtl"] .kp-commerce-product{text-align:start}html[dir="rtl"] #kp-delete-dialog{direction:rtl;text-align:start}html[dir="rtl"] #kp-delete-dialog>div>div{justify-content:flex-start!important}@media(max-width:800px){html[dir="rtl"] .kp-header nav{justify-content:flex-start}}';
    }

    private static function styles(): string {
        return ':root{--ink:#26211e;--paper:#f6f1e8;--card:#fffdf8;--line:#e5ddd0;--accent:#b84330;--accent-dark:#8d3022;--muted:#72695f;--success:#1f6a53;--danger:#9a2929}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.kp-shell{max-width:1240px;margin:0 auto;padding:28px 22px 64px}.kp-login-grid{min-height:calc(100vh - 56px);display:grid;grid-template-columns:1.1fr .9fr;background:var(--card);border:1px solid var(--line);border-radius:28px;overflow:hidden;box-shadow:0 24px 70px rgba(45,32,20,.08)}.kp-brand-panel{background:#25211f;color:#f8f0e6;padding:64px;display:flex;flex-direction:column;justify-content:space-between}.kp-brand-panel h1,.kp-welcome h1{font-family:Georgia,"Times New Roman",serif;font-size:clamp(2.7rem,5vw,4.8rem);line-height:.96;letter-spacing:-.05em;margin:24px 0}.kp-brand-panel p{max-width:460px;color:#d4c7b8;font-size:1.05rem;line-height:1.7}.kp-mark{display:inline-grid;place-items:center;width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#d75d42,#9f2e24);color:#fff;font-family:Georgia,serif;font-size:1.3rem}.kp-overline{margin:0;font-size:.72rem;font-weight:800;letter-spacing:.16em}.kp-warm{color:var(--accent)}.kp-stat-row{display:flex;gap:36px;border-top:1px solid rgba(255,255,255,.18);padding-top:24px}.kp-stat-row span{display:grid;gap:5px;color:#c8bbb0;font-size:.76rem}.kp-stat-row strong{font-family:Georgia,serif;font-size:1.5rem;color:#fff}.kp-login-card{padding:clamp(38px,7vw,88px);align-self:center}.kp-login-card h2{font-family:Georgia,serif;font-size:3rem;margin:10px 0}.kp-login-card>p{color:var(--muted);line-height:1.6}.kp-form{display:grid;gap:16px}.kp-form label{display:grid;gap:7px;font-weight:700;font-size:.86rem}.kp-form input,.kp-form textarea,.kp-form select,.kp-order select{width:100%;border:1px solid var(--line);background:#fff;border-radius:12px;padding:13px;font:inherit;color:var(--ink)}.kp-form textarea{resize:vertical}.kp-primary{appearance:none;border:0;border-radius:12px;background:var(--accent);color:#fff;padding:14px 18px;font:700 .95rem inherit;cursor:pointer;box-shadow:0 10px 20px rgba(184,67,48,.18)}.kp-primary:hover{background:var(--accent-dark)}.kp-security-note{margin-top:20px;padding:14px;border:1px solid var(--line);border-radius:12px;font-size:.82rem}.kp-notice{margin:18px 0;padding:13px 15px;border-radius:12px;font-size:.9rem}.kp-notice-success{background:#e9f5ef;color:var(--success)}.kp-notice-error{background:#faecea;color:var(--danger)}.kp-header{display:flex;align-items:center;gap:22px;justify-content:space-between;padding:8px 0 26px}.kp-logo{display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none}.kp-logo strong,.kp-logo small{display:block}.kp-logo small{font-size:.64rem;text-transform:uppercase;letter-spacing:.12em;color:var(--muted)}.kp-header nav{display:flex;gap:18px;flex-wrap:wrap}.kp-header nav a{color:var(--muted);font-size:.88rem;text-decoration:none}.kp-quiet{border:1px solid var(--line);background:transparent;color:var(--ink);border-radius:10px;padding:10px 13px;font:700 .83rem inherit;cursor:pointer}.kp-welcome{display:flex;align-items:end;justify-content:space-between;gap:30px;padding:48px 0}.kp-welcome h1{font-size:clamp(2.4rem,4vw,4rem);margin:10px 0}.kp-welcome p{max-width:650px;color:var(--muted);line-height:1.65}.kp-link{text-decoration:none;white-space:nowrap}.kp-card{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:30px;margin-top:22px;box-shadow:0 8px 22px rgba(45,32,20,.035)}.kp-card-head h2{font-family:Georgia,serif;font-size:2rem;margin:8px 0}.kp-card-head p{color:var(--muted);margin:0;line-height:1.55}.kp-product-form{grid-template-columns:repeat(2,minmax(0,1fr));margin-top:22px}.kp-wide{grid-column:1/-1}.kp-product-form fieldset{border:1px solid var(--line);border-radius:12px;padding:12px}.kp-radio{display:block!important;margin:9px 0}.kp-radio input{width:auto!important;margin-right:6px}.kp-product-list{display:grid;gap:14px;margin-top:20px}.kp-product{padding:16px;border:1px solid var(--line);border-radius:16px}.kp-product-title{display:flex;gap:14px;align-items:center}.kp-product-title img,.kp-image-placeholder{width:58px;height:58px;border-radius:12px;object-fit:cover;background:#eee5d9}.kp-image-placeholder{display:grid;place-items:center;color:var(--muted);font-size:.7rem}.kp-product h3{margin:0 0 4px}.kp-product p{margin:0;color:var(--muted);font-size:.9rem}.kp-product summary{margin-top:14px;cursor:pointer;color:var(--accent);font-weight:700}.kp-product details .kp-form{margin-top:16px}.kp-danger-form{margin-top:12px}.kp-danger{border:1px solid #e5b9b4;background:#fff6f5;color:var(--danger);border-radius:10px;padding:9px 12px;font:700 .82rem inherit;cursor:pointer}.kp-orders{display:grid;gap:10px;margin-top:20px}.kp-order{display:grid;grid-template-columns:1.1fr .7fr 1fr auto;gap:12px;align-items:center;padding:13px;border:1px solid var(--line);border-radius:13px}.kp-order div{display:grid;gap:3px}.kp-order small{color:var(--muted)}.kp-palette-form{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:20px}.kp-palette{display:flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:10px;padding:9px 11px;cursor:pointer;font-size:.88rem}.kp-palette span{width:18px;height:18px;border-radius:99px}.kp-audit ul{margin:16px 0 0;padding:0;list-style:none}.kp-audit li{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-top:1px solid var(--line);font-size:.88rem}.kp-audit span{color:var(--muted)}.kp-empty{color:var(--muted);padding:12px 0}@media(max-width:800px){.kp-shell{padding:14px}.kp-login-grid{grid-template-columns:1fr}.kp-brand-panel{padding:36px;min-height:420px}.kp-login-card{padding:34px}.kp-header{align-items:flex-start;flex-wrap:wrap}.kp-header nav{order:3;width:100%}.kp-welcome{display:grid;padding:30px 0}.kp-product-form{grid-template-columns:1fr}.kp-order{grid-template-columns:1fr 1fr}.kp-order button{grid-column:1/-1}.kp-stat-row{gap:18px}.kp-card{padding:22px}}';
    }
}
