#!/usr/bin/env bash
set -Eeuo pipefail

BASE="${KELEVA_BASE_URL:-https://127.0.0.1:8443}"
API="${BASE%/}/wp-json/keleva-dashboard/v1"
EMAIL="${KELEVA_MERCHANT_EMAIL:-merchant@keleva.test}"
PASSWORD="${KELEVA_MERCHANT_PASSWORD:-Merchant_Local_2026!Secure}"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

fail() { printf 'ÉCHEC: %s\n' "$1" >&2; exit 1; }
status() {
  local output="${!#}"
  local count=$#
  local args=("${@:1:$((count - 1))}")
  curl -ksS -o "$output" -w '%{http_code}' "${args[@]}"
}

LOGIN="$(curl -ksS -c "$JAR" -H 'Content-Type: application/json' -X POST "$API/session/login" --data "$(printf '{"email":"%s","password":"%s"}' "$EMAIL" "$PASSWORD")")"
CSRF="$(awk '$6 == "keleva_merchant_csrf" {print $7}' "$JAR")"
[ -n "$CSRF" ] || fail 'cookie CSRF absent'
HEAD=(-H "X-Keleva-CSRF: $CSRF" -H "Origin: $BASE" -H 'Content-Type: application/json' -b "$JAR")

UNAUTH="$(curl -ksS -o /tmp/keleva-safety-unauth.json -w '%{http_code}' "$API/summary")"
[ "$UNAUTH" = 403 ] || fail "summary non authentifié: $UNAUTH"

CATEGORY_NAME="Safety Contract $(date +%s)"
CATEGORY_STATUS="$(status "${HEAD[@]}" -X POST "$API/categories" --data "$(printf '{"name":"%s","visible":true}' "$CATEGORY_NAME")" /tmp/keleva-safety-category.json)"
[ "$CATEGORY_STATUS" = 200 ] || fail "création catégorie: $CATEGORY_STATUS"
CATEGORY_ID="$(grep -oE '"id":[0-9]+' /tmp/keleva-safety-category.json | head -1 | cut -d: -f2)"
[ -n "$CATEGORY_ID" ] || fail 'identifiant catégorie absent'

MOVE_STATUS="$(status "${HEAD[@]}" -X POST "$API/categories/$CATEGORY_ID/products" --data '{"product_ids":[11],"mode":"replace"}' /tmp/keleva-safety-move.json)"
[ "$MOVE_STATUS" = 200 ] || fail "déplacement produit: $MOVE_STATUS"
DELETE_STATUS="$(status "${HEAD[@]}" -X DELETE "$API/categories/$CATEGORY_ID" /tmp/keleva-safety-delete.json)"
[ "$DELETE_STATUS" = 409 ] || fail "suppression catégorie non vide acceptée: $DELETE_STATUS"
PRODUCT_STATE="$(cd "${KELEVA_SITE_ROOT:-/home/ubuntu/keleva-local-wordpress/site}" && /home/ubuntu/bin/wp eval 'echo wp_json_encode(wc_get_product(11)->get_category_ids());')"
printf '%s\n' "$PRODUCT_STATE" | grep -q "$CATEGORY_ID" || fail 'produit sorti de sa catégorie après refus de suppression'

OPTION_STATUS="$(status "${HEAD[@]}" -X POST "$API/products/11/configuration" --data '{"type":"simple","option_groups":[{"id":"multi-buttons","label":"Contrat","display":"buttons","max":2,"options":[{"id":"a","label":"A","price":0},{"id":"b","label":"B","price":1}]}]}' /tmp/keleva-safety-options.json)"
[ "$OPTION_STATUS" = 200 ] || fail "configuration option: $OPTION_STATUS"
grep -q '"display":"checkbox","max":2' /tmp/keleva-safety-options.json || fail 'buttons max=2 non normalisé en checkbox'

PALETTE_STATUS="$(status "${HEAD[@]}" "$API/appearance/palettes" /tmp/keleva-safety-palettes.json)"
[ "$PALETTE_STATUS" = 200 ] || fail "liste palettes: $PALETTE_STATUS"
for id in velora onyx-gold sienne sauge azur; do grep -q "\"id\":\"$id\"" /tmp/keleva-safety-palettes.json || fail "palette absente: $id"; done
for token in surface_card surface_media accent_deep warning_wash danger_wash shadow_tint; do grep -q "\"$token\":" /tmp/keleva-safety-palettes.json || fail "token absent: $token"; done

printf 'Sécurité/API : OK — non-auth=%s, catégorie non vide=%s, options=%s, palettes=%s\n' "$UNAUTH" "$DELETE_STATUS" "$OPTION_STATUS" "$PALETTE_STATUS"
