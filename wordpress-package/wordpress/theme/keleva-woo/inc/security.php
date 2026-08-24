<?php
/**
 * Hardening public du storefront sans entraver l'administration WordPress.
 *
 * @package KelevaWoo
 */

defined('ABSPATH') || exit;

/**
 * Les profils ne font pas partie du contrat public de la boutique : le REST
 * WordPress utilisateur reste disponible aux administrateurs autorisés, mais
 * il n'est pas une source d'énumération pour un visiteur anonyme.
 *
 * @param array<string, mixed> $endpoints Routes REST connues.
 * @return array<string, mixed>
 */
function keleva_woo_restrict_public_user_rest_endpoints(array $endpoints): array {
    if (current_user_can('list_users')) {
        return $endpoints;
    }

    unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);

    return $endpoints;
}
add_filter('rest_endpoints', 'keleva_woo_restrict_public_user_rest_endpoints');

/**
 * Détecte la forme historique ?author=ID utilisée pour deviner un identifiant.
 */
function keleva_woo_is_author_enumeration_request(): bool {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Requête GET non mutative, uniquement bloquée avant rendu.
    $candidate = isset($_GET['author']) ? sanitize_text_field(wp_unslash($_GET['author'])) : '';
    // phpcs:enable

    return preg_match('/^[1-9][0-9]*$/', $candidate) === 1;
}

/**
 * Empêche la redirection WordPress vers /author/{login}/ pour un visiteur.
 */
function keleva_woo_block_public_author_enumeration(): void {
    if (is_admin() || current_user_can('list_users') || !keleva_woo_is_author_enumeration_request()) {
        return;
    }

    status_header(404);
    nocache_headers();
    exit;
}
add_action('template_redirect', 'keleva_woo_block_public_author_enumeration', 0);

/**
 * La boutique ne fournit aucun besoin XML-RPC : supprimer cette surface et le
 * header de découverte, sans affecter l'API REST WooCommerce utilisée au site.
 */
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', static function (array $headers): array {
    unset($headers['X-Pingback']);

    return $headers;
});

/**
 * xmlrpc_enabled bloque l'authentification XML-RPC mais ne supprime pas le
 * serveur sur toutes les versions WordPress. Le storefront n'en dépend pas :
 * refuser explicitement toute requête avant l'exposition des méthodes.
 */
function keleva_woo_block_xmlrpc_request(): void {
    if (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) {
        return;
    }

    status_header(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'XML-RPC disabled.';
    exit;
}
add_action('init', 'keleva_woo_block_xmlrpc_request', 0);
