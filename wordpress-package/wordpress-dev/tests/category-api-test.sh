#!/usr/bin/env bash
set -euo pipefail

: "${KELEVA_LOCAL_MERCHANT_PASSWORD:?Définissez KELEVA_LOCAL_MERCHANT_PASSWORD avant la recette.}"

BASE_URL="${KELEVA_LOCAL_BASE_URL:-http://127.0.0.1:8088}"
: "${KELEVA_LOCAL_MERCHANT_EMAIL:?Définissez KELEVA_LOCAL_MERCHANT_EMAIL avant la recette.}"
MERCHANT_EMAIL="$KELEVA_LOCAL_MERCHANT_EMAIL"
ROUTE="$BASE_URL/wp-json/keleva-dashboard/v1"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
JAR="$TMP_DIR/cookies.txt"

status() {
  curl -sS "$@" -o "$TMP_DIR/body.json" -w '%{http_code}'
}

login_status="$(curl -sS -c "$JAR" -o "$TMP_DIR/login.json" -w '%{http_code}' -X POST "$ROUTE/session/login" --data-urlencode "email=$MERCHANT_EMAIL" --data-urlencode "password=$KELEVA_LOCAL_MERCHANT_PASSWORD")"
test "$login_status" = '200'
csrf="$(awk '$6 == "keleva_merchant_csrf" {print $7}' "$JAR")"
test -n "$csrf"

categories_status="$(status -b "$JAR" "$ROUTE/categories")"
test "$categories_status" = '200'
maison_id="$(grep -oE '\{"id":[0-9]+,"name":"Maison"' "$TMP_DIR/body.json" | head -1 | sed -E 's/.*"id":([0-9]+).*/\1/')"
test -n "$maison_id"

suffix="$(date +%s%N)"
category_name="Atelier Recette S1 $suffix"
category_slug="atelier-recette-s1-$suffix"
create_payload="$(printf '{\"name\":\"%s\",\"slug\":\"%s\",\"visible\":false,\"order\":1,\"option_templates\":[{\"id\":\"finition\",\"label\":\"Finition atelier\",\"display\":\"buttons\",\"max\":1,\"required\":true,\"options\":[{\"id\":\"brut\",\"label\":\"Brut\",\"price\":0},{\"id\":\"emaille\",\"label\":\"Émaillé\",\"price\":5}]}]}' "$category_name" "$category_slug")"
create_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/create.json" -w '%{http_code}' -X POST "$ROUTE/categories" --data "$create_payload")"
test "$create_status" = '200'
grep -q '"visible":false' "$TMP_DIR/create.json"
grep -q 'Finition atelier' "$TMP_DIR/create.json"
category_id="$(grep -oE '"id":[0-9]+' "$TMP_DIR/create.json" | head -1 | cut -d: -f2)"
test -n "$category_id"

cover_file="/home/ubuntu/webdev-static-assets/keleva-vase-gallery-unsplash-collection.jpg"
test -f "$cover_file"
cover_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -o "$TMP_DIR/cover.json" -w '%{http_code}' -X POST "$ROUTE/categories/$category_id/image" -F "image=@$cover_file;type=image/jpeg")"
test "$cover_status" = '200'
cover_attachment_id="$(grep -oE '"cover":\{"id":[0-9]+' "$TMP_DIR/cover.json" | head -1 | sed -E 's/.*"id":([0-9]+).*/\1/')"
test -n "$cover_attachment_id"

update_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/update.json" -w '%{http_code}' -X POST "$ROUTE/categories/$category_id" --data "{\"name\":\"$category_name édité\",\"visible\":true}")"
test "$update_status" = '200'
grep -q '"visible":true' "$TMP_DIR/update.json"

order_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/order.json" -w '%{http_code}' -X POST "$ROUTE/categories/order" --data "{\"ids\":[$category_id,$maison_id]}")"
test "$order_status" = '200'

move_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/move.json" -w '%{http_code}' -X POST "$ROUTE/categories/$category_id/products" --data '{"product_ids":[12],"mode":"replace"}')"
test "$move_status" = '200'
grep -Eq '"moved_product_ids"[[:space:]]*:[[:space:]]*\[[[:space:]]*12[[:space:]]*\]' "$TMP_DIR/move.json"

config_status="$(status -b "$JAR" "$ROUTE/products/12/configuration")"
test "$config_status" = '200'
grep -q '"source":"category_default"' "$TMP_DIR/body.json"
grep -q 'Finition atelier' "$TMP_DIR/body.json"

restore_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/restore.json" -w '%{http_code}' -X POST "$ROUTE/categories/$maison_id/products" --data '{"product_ids":[12],"mode":"replace"}')"
test "$restore_status" = '200'

delete_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -o "$TMP_DIR/delete.json" -w '%{http_code}' -X DELETE "$ROUTE/categories/$category_id")"
test "$delete_status" = '200'
grep -q '"deleted":true' "$TMP_DIR/delete.json"
KELEVA_SITE_ROOT="${KELEVA_SITE_ROOT:-/home/ubuntu/keleva-local-wordpress/site}" php "$(dirname "$0")/delete-local-recipe-attachment.php" "$cover_attachment_id" >/dev/null 2>&1 || true

printf 'Catégories API locales : liste=200 création=%s couverture=%s mise-à-jour=%s ordre=%s déplacement=%s modèle=200 restauration=%s suppression=%s\n' "$create_status" "$cover_status" "$update_status" "$order_status" "$move_status" "$restore_status" "$delete_status"
