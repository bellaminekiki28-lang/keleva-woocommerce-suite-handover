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

login_status="$(curl -sS -c "$JAR" -o "$TMP_DIR/login.json" -w '%{http_code}' -X POST "$ROUTE/session/login" --data-urlencode "email=$MERCHANT_EMAIL" --data-urlencode "password=$KELEVA_LOCAL_MERCHANT_PASSWORD")"
test "$login_status" = '200'
csrf="$(awk '$6 == "keleva_merchant_csrf" {print $7}' "$JAR")"
test -n "$csrf"

list_status="$(curl -sS -b "$JAR" -o "$TMP_DIR/list.json" -w '%{http_code}' "$ROUTE/appearance/palettes")"
test "$list_status" = '200'
for palette in velora onyx-gold sienne sauge azur; do grep -q "\"id\":\"$palette\"" "$TMP_DIR/list.json"; done
grep -q '"active":"velora"' "$TMP_DIR/list.json"

apply_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/apply.json" -w '%{http_code}' -X POST "$ROUTE/appearance/palette" --data '{"palette":"onyx-gold"}')"
test "$apply_status" = '200'
grep -q '"active":"onyx-gold"' "$TMP_DIR/apply.json"

reset_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -o "$TMP_DIR/reset.json" -w '%{http_code}' -X DELETE "$ROUTE/appearance/palette")"
test "$reset_status" = '200'
grep -q '"active":"velora"' "$TMP_DIR/reset.json"

printf 'Apparence API locale : palettes=200 application=%s réinitialisation=%s\n' "$apply_status" "$reset_status"
