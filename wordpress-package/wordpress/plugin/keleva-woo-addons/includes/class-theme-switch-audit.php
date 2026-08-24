<?php
/**
 * Journalise toute activation de thème pour diagnostiquer les bascules non sollicitées.
 *
 * @package KelevaWooAddons
 */

defined('ABSPATH') || exit;

/**
 * Retourne l’acteur WordPress disponible sans stocker d’adresse IP personnelle.
 */
function keleva_woo_addons_theme_switch_actor(): string {
    $user = wp_get_current_user();
    if ($user instanceof WP_User && $user->exists()) {
        return 'wp-user-' . (int) $user->ID;
    }

    return 'wordpress-runtime';
}

/**
 * Normalise les champs publics d’un thème dans le contexte d’audit.
 *
 * @return array{name: string, stylesheet: string, version: string}
 */
function keleva_woo_addons_theme_audit_context($theme, string $fallback_name): array {
    if ($theme instanceof WP_Theme) {
        return [
            'name' => sanitize_text_field($theme->get('Name')),
            'stylesheet' => sanitize_key($theme->get_stylesheet()),
            'version' => sanitize_text_field($theme->get('Version')),
        ];
    }

    return [
        'name' => sanitize_text_field($fallback_name),
        'stylesheet' => sanitize_key($fallback_name),
        'version' => '',
    ];
}

/**
 * Trace les changements au hook natif WordPress switch_theme.
 *
 * Les données enregistrées sont volontairement minimales : source d’exécution,
 * utilisateur si identifié, thèmes avant/après et instant UTC. Aucune IP n’est
 * conservée par ce mécanisme.
 *
 * @param string   $new_name Nouveau nom de thème.
 * @param WP_Theme $new_theme Nouveau thème.
 * @param WP_Theme $old_theme Ancien thème.
 */
function keleva_woo_addons_record_theme_switch(string $new_name, $new_theme, $old_theme): void {
    Keleva_Dashboard_Audit_Log::record('theme_switch', [
        'to' => keleva_woo_addons_theme_audit_context($new_theme, $new_name),
        'from' => keleva_woo_addons_theme_audit_context($old_theme, ''),
        'execution_context' => wp_doing_ajax() ? 'ajax' : (is_admin() ? 'admin' : 'runtime'),
    ], keleva_woo_addons_theme_switch_actor());
}
add_action('switch_theme', 'keleva_woo_addons_record_theme_switch', 10, 3);
