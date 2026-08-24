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
suffix="$(date +%s%N)"
coupon_code="LOCAL-$suffix"
coupon_code_normalized="${coupon_code,,}"

login_status="$(curl -sS -c "$JAR" -o "$TMP_DIR/login.json" -w '%{http_code}' -X POST "$ROUTE/session/login" --data-urlencode "email=$MERCHANT_EMAIL" --data-urlencode "password=$KELEVA_LOCAL_MERCHANT_PASSWORD")"
test "$login_status" = 200
csrf="$(awk '$6 == "keleva_merchant_csrf" {print $7}' "$JAR")"
test -n "$csrf"

summary_status="$(curl -sS -b "$JAR" -o "$TMP_DIR/summary.json" -w '%{http_code}' "$ROUTE/summary")"
test "$summary_status" = 200
grep -q '"orders_total"' "$TMP_DIR/summary.json"
grep -q '"revenue_paid"' "$TMP_DIR/summary.json"

orders_status="$(curl -sS -b "$JAR" -o "$TMP_DIR/orders.json" -w '%{http_code}' "$ROUTE/orders?limit=10")"
test "$orders_status" = 200
grep -q '"orders"' "$TMP_DIR/orders.json"

expiry_date="$(date -d '+30 days' +%F)"
create_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/coupon-create.json" -w '%{http_code}' -X POST "$ROUTE/coupons" --data "{\"code\":\"$coupon_code\",\"discount_type\":\"percent\",\"amount\":15,\"usage_limit\":2,\"date_expires\":\"$expiry_date\"}")"
test "$create_status" = 200
grep -q '"amount":"15"' "$TMP_DIR/coupon-create.json"
grep -q "\"date_expires\":\"$expiry_date" "$TMP_DIR/coupon-create.json"
coupon_id="$(grep -oE '"id":[0-9]+' "$TMP_DIR/coupon-create.json" | head -1 | cut -d: -f2)"
test -n "$coupon_id"

update_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -H 'Content-Type: application/json' -o "$TMP_DIR/coupon-update.json" -w '%{http_code}' -X POST "$ROUTE/coupons/$coupon_id" --data '{"amount":20,"individual_use":true}')"
test "$update_status" = 200
grep -q '"amount":"20"' "$TMP_DIR/coupon-update.json"
grep -q '"individual_use":true' "$TMP_DIR/coupon-update.json"

list_status="$(curl -sS -b "$JAR" -o "$TMP_DIR/coupons.json" -w '%{http_code}' "$ROUTE/coupons")"
test "$list_status" = 200
grep -q "$coupon_code_normalized" "$TMP_DIR/coupons.json"

delete_status="$(curl -sS -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -o "$TMP_DIR/coupon-delete.json" -w '%{http_code}' -X DELETE "$ROUTE/coupons/$coupon_id")"
test "$delete_status" = 200
grep -q '"deleted":true' "$TMP_DIR/coupon-delete.json"

printf 'Commandes/coupons API locales : KPI=%s commandes=%s création=%s mise-à-jour=%s liste=%s suppression=%s\n' "$summary_status" "$orders_status" "$create_status" "$update_status" "$list_status" "$delete_status"
