#!/usr/bin/env bash
set -euo pipefail

: "${KELEVA_LOCAL_MERCHANT_PASSWORD:?Définissez KELEVA_LOCAL_MERCHANT_PASSWORD avant la recette.}"
: "${KELEVA_LOCAL_SERVER_TOKEN:?Définissez KELEVA_LOCAL_SERVER_TOKEN pour la compatibilité serveur.}"
: "${KELEVA_LOCAL_PREVIOUS_SERVER_TOKEN:?Définissez KELEVA_LOCAL_PREVIOUS_SERVER_TOKEN pour la rotation de clé.}"
: "${KELEVA_LOCAL_MERCHANT_EMAIL:?Définissez KELEVA_LOCAL_MERCHANT_EMAIL avant la recette.}"
MERCHANT_EMAIL="$KELEVA_LOCAL_MERCHANT_EMAIL"

BASE_URL="${KELEVA_LOCAL_BASE_URL:-http://127.0.0.1:8088}"
ROUTE="$BASE_URL/wp-json/keleva-dashboard/v1"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
JAR="$TMP_DIR/cookies.txt"
CURL_OPTIONS=()
if [[ "${KELEVA_LOCAL_ALLOW_SELF_SIGNED:-0}" = '1' ]]; then
  CURL_OPTIONS+=(-k)
fi

assert_status() {
  local expected="$1"; shift
  local actual
  actual="$(curl "${CURL_OPTIONS[@]}" -sS "$@" -o "$TMP_DIR/body.json" -w '%{http_code}')"
  test "$actual" = "$expected"
}

curl "${CURL_OPTIONS[@]}" -fsS "$BASE_URL/wp-json/" -o "$TMP_DIR/index.json"
grep -q 'keleva-dashboard\\/v1\\/session\\/login' "$TMP_DIR/index.json"

assert_status 401 "$ROUTE/session"
grep -q 'keleva_dashboard_session_required' "$TMP_DIR/body.json"

failed_login_status="$(curl "${CURL_OPTIONS[@]}" -sS -o "$TMP_DIR/failed-login.json" -w '%{http_code}' \
  -X POST "$ROUTE/session/login" --data-urlencode 'email=wrong@keleva.local' --data-urlencode 'password=incorrect-password')"
test "$failed_login_status" = '401'
grep -q 'keleva_dashboard_login_failed' "$TMP_DIR/failed-login.json"

login_status="$(curl "${CURL_OPTIONS[@]}" -sS -D "$TMP_DIR/login.headers" -c "$JAR" -o "$TMP_DIR/login.json" -w '%{http_code}' \
  -X POST "$ROUTE/session/login" --data-urlencode "email=$MERCHANT_EMAIL" --data-urlencode "password=$KELEVA_LOCAL_MERCHANT_PASSWORD")"
test "$login_status" = '200'
grep -q '"authenticated":true' "$TMP_DIR/login.json"
grep -qi '^Set-Cookie: keleva_merchant_session=.*HttpOnly.*SameSite=Lax' "$TMP_DIR/login.headers"
grep -qi '^Set-Cookie: keleva_merchant_csrf=.*SameSite=Lax' "$TMP_DIR/login.headers"
if [[ "$BASE_URL" == https://* ]]; then
  grep -qi '^Set-Cookie: keleva_merchant_session=.*Secure' "$TMP_DIR/login.headers"
  grep -qi '^Set-Cookie: keleva_merchant_csrf=.*Secure' "$TMP_DIR/login.headers"
fi
csrf="$(awk '$6 == "keleva_merchant_csrf" {print $7}' "$JAR")"
test -n "$csrf"

assert_status 200 -b "$JAR" "$ROUTE/session"
grep -q '"authenticated":true' "$TMP_DIR/body.json"

summary_status="$(curl "${CURL_OPTIONS[@]}" -sS -D "$TMP_DIR/summary.headers" -b "$JAR" -o "$TMP_DIR/summary.json" -w '%{http_code}' "$ROUTE/summary")"
test "$summary_status" = '200'
grep -q '"products_total":15' "$TMP_DIR/summary.json"
grep -qi '^Cache-Control: no-store' "$TMP_DIR/summary.headers"
grep -qi '^Vary: Cookie' "$TMP_DIR/summary.headers"

audit_status="$(curl "${CURL_OPTIONS[@]}" -sS -b "$JAR" -o "$TMP_DIR/audit.json" -w '%{http_code}' "$ROUTE/audit")"
test "$audit_status" = '200'
grep -q 'merchant_session_started' "$TMP_DIR/audit.json"

token_summary_status="$(curl "${CURL_OPTIONS[@]}" -sS -H "X-Keleva-Dashboard-Key: $KELEVA_LOCAL_SERVER_TOKEN" -o "$TMP_DIR/token-summary.json" -w '%{http_code}' "$ROUTE/summary")"
test "$token_summary_status" = '200'
grep -q '"products_total":15' "$TMP_DIR/token-summary.json"

previous_token_summary_status="$(curl "${CURL_OPTIONS[@]}" -sS -H "X-Keleva-Dashboard-Key: $KELEVA_LOCAL_PREVIOUS_SERVER_TOKEN" -o "$TMP_DIR/previous-token-summary.json" -w '%{http_code}' "$ROUTE/summary")"
test "$previous_token_summary_status" = '200'
grep -q '"products_total":15' "$TMP_DIR/previous-token-summary.json"

assert_status 403 -b "$JAR" -X POST "$ROUTE/session/logout"
grep -q 'keleva_dashboard_csrf_invalid' "$TMP_DIR/body.json"

assert_status 403 -b "$JAR" -H "X-Keleva-CSRF: $csrf" -H 'Origin: https://attacker.invalid' -X POST "$ROUTE/session/logout"
grep -q 'keleva_dashboard_origin_invalid' "$TMP_DIR/body.json"

logout_status="$(curl "${CURL_OPTIONS[@]}" -sS -D "$TMP_DIR/logout.headers" -b "$JAR" -c "$JAR" -H "X-Keleva-CSRF: $csrf" -H "Origin: $BASE_URL" -o "$TMP_DIR/logout.json" -w '%{http_code}' -X POST "$ROUTE/session/logout")"
test "$logout_status" = '200'
grep -q '"logged_out":true' "$TMP_DIR/logout.json"

assert_status 401 -b "$JAR" "$ROUTE/session"
grep -q 'keleva_dashboard_session_required' "$TMP_DIR/body.json"

printf 'Session HTTP locale : mauvais-login=%s login=%s session=200 summary=%s audit=%s clé-courante=%s clé-précédente=%s csrf=403 origin=403 logout=%s post_logout=401\n' "$failed_login_status" "$login_status" "$summary_status" "$audit_status" "$token_summary_status" "$previous_token_summary_status" "$logout_status"
