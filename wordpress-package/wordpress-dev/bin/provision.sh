#!/usr/bin/env bash
set -Eeuo pipefail

# Provisionnement reproductible appelé par wp-env après le démarrage du site.
# Le script ne contient aucun secret et peut être rejoué sans créer de doublons.
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
WP_PATH="${WP_PATH:-/var/www/html}"
WP_BIN="${WP_BIN:-wp}"
WP=("${WP_BIN}" --allow-root --path="${WP_PATH}")
export KELEVA_SITE_ROOT="${KELEVA_SITE_ROOT:-${WP_PATH}}"
export KELEVA_CONSOLE_SOURCE="${KELEVA_CONSOLE_SOURCE:-${ROOT_DIR}/console/keleva-native-console.html}"

if ! command -v "${WP_BIN}" >/dev/null 2>&1; then
  printf '%s\n' 'WP-CLI est requis pour le provisioning wp-env.' >&2
  exit 127
fi

"${WP[@]}" core is-installed >/dev/null

if ! "${WP[@]}" plugin is-installed woocommerce >/dev/null 2>&1; then
  "${WP[@]}" plugin install woocommerce --version=11.0.1 --activate --force
else
  "${WP[@]}" plugin activate woocommerce >/dev/null
fi

"${WP[@]}" plugin activate keleva-woo-addons/keleva-woo-addons.php >/dev/null
"${WP[@]}" eval-file "${ROOT_DIR}/wordpress-dev/bin/provision-core.php"
"${WP[@]}" eval-file "${ROOT_DIR}/wordpress-dev/bin/provision-catalog.php"
"${WP[@]}" eval-file "${ROOT_DIR}/wordpress-dev/bin/provision-merchant-console.php"
"${WP[@]}" rewrite flush --hard >/dev/null

printf '%s\n' 'Provisionnement Keleva terminé.'
"${WP[@]}" option get woocommerce_shop_page_id
"${WP[@]}" option get woocommerce_cart_page_id
"${WP[@]}" option get woocommerce_checkout_page_id
"${WP[@]}" option get woocommerce_myaccount_page_id
