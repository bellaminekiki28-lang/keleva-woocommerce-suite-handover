#!/usr/bin/env bash
set -euo pipefail

: "${KELEVA_LOCAL_MERCHANT_PASSWORD:?Définissez KELEVA_LOCAL_MERCHANT_PASSWORD avant la recette.}"

BASE_URL="${KELEVA_LOCAL_BASE_URL:-http://127.0.0.1:8088}"
: "${KELEVA_LOCAL_MERCHANT_EMAIL:?Définissez KELEVA_LOCAL_MERCHANT_EMAIL avant la recette.}"
MERCHANT_EMAIL="$KELEVA_LOCAL_MERCHANT_EMAIL"
SITE_ROOT="${KELEVA_SITE_ROOT:-/home/ubuntu/keleva-local-wordpress/site}"
ROUTE="$BASE_URL/wp-json/keleva-dashboard/v1"
TMP_DIR="$(mktemp -d)"
ORDER_ID=''
cleanup() {
  if [[ -n "$ORDER_ID" ]]; then
    KELEVA_SITE_ROOT="$SITE_ROOT" php "$(dirname "$0")/seed-local-order.php" delete "$ORDER_ID" >/dev/null || true
  fi
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT
JAR="$TMP_DIR/cookies.txt"

ORDER_ID="$(KELEVA_SITE_ROOT="$SITE_ROOT" php "$(dirname "$0")/seed-local-order.php" create)"
test -n "$ORDER_ID"

login_status="$(curl -sS -c "$JAR" -o "$TMP_DIR/login.json" -w '%{http_code}' -X POST "$ROUTE/session/login" --data-urlencode "email=$MERCHANT_EMAIL" --data-urlencode "password=$KELEVA_LOCAL_MERCHANT_PASSWORD")"
test "$login_status" = '200'
csrf="$(awk '$6 == "keleva_merchant_csrf" {print $7}' "$JAR")"
test -n "$csrf"

orders_status="$(curl -sS -b "$JAR" -o "$TMP_DIR/orders.json" -w '%{http_code}' "$ROUTE/orders?limit=100")"
test "$orders_status" = '200'
grep -q "\"id\":$ORDER_ID" "$TMP_DIR/orders.json"

complete_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/complete.json" -w '%{http_code}' -X POST "$ROUTE/orders/$ORDER_ID/status" --data '{"status":"completed"}')"
test "$complete_status" = '200'
grep -q '"status":"completed"' "$TMP_DIR/complete.json"

restore_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/restore.json" -w '%{http_code}' -X POST "$ROUTE/orders/$ORDER_ID/status" --data '{"status":"processing"}')"
test "$restore_status" = '200'
grep -q '"status":"processing"' "$TMP_DIR/restore.json"

printf 'Commandes API locales : liste=%s terminé=%s restauration=%s suppression=prévue\n' "$orders_status" "$complete_status" "$restore_status"
