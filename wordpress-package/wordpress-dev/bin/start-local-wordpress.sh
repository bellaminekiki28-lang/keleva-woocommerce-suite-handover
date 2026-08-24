#!/usr/bin/env bash
set -euo pipefail

ROOT="/home/ubuntu/keleva-local-wordpress"
LOG="$ROOT/logs/php-server.log"
TLS_LOG="$ROOT/logs/tls-proxy.log"
CERT_DIR="$ROOT/certs"
CERT="$CERT_DIR/local-cert.pem"
KEY="$CERT_DIR/local-key.pem"

# Les identifiants du laboratoire viennent exclusivement de l’environnement.
# Les variables d’intégration déjà injectées servent uniquement de compatibilité locale.
if [[ -f /home/ubuntu/.user_env ]]; then
  set -a
  source /home/ubuntu/.user_env
  set +a
fi
export KELEVA_LOCAL_MERCHANT_EMAIL="${KELEVA_LOCAL_MERCHANT_EMAIL:-${KELEVA_MERCHANT_EMAIL:-}}"
export KELEVA_LOCAL_MERCHANT_PASSWORD="${KELEVA_LOCAL_MERCHANT_PASSWORD:-${KELEVA_MERCHANT_PASSWORD:-}}"
export KELEVA_LOCAL_SERVER_TOKEN="${KELEVA_LOCAL_SERVER_TOKEN:-${KELEVA_PREPROD_DASHBOARD_KEY:-}}"
export KELEVA_LOCAL_PREVIOUS_SERVER_TOKEN="${KELEVA_LOCAL_PREVIOUS_SERVER_TOKEN:-${KELEVA_PREPROD_DASHBOARD_KEY:-}}"
mkdir -p "$ROOT/logs"

if ! pgrep -x mariadbd >/dev/null 2>&1; then
  sudo install -d -o mysql -g mysql /run/mysqld
  sudo nohup mariadbd --user=mysql --bind-address=127.0.0.1 --socket=/run/mysqld/mysqld.sock --pid-file=/run/mysqld/mysqld.pid >/tmp/keleva-mariadb.log 2>&1 &
  sleep 2
fi

if ! fuser 8088/tcp >/dev/null 2>&1; then
  nohup php -S 0.0.0.0:8088 -t "$ROOT/site" >"$LOG" 2>&1 &
fi

if ! fuser 8443/tcp >/dev/null 2>&1; then
  mkdir -p "$CERT_DIR"
  if [[ ! -f "$CERT" || ! -f "$KEY" ]]; then
    openssl req -x509 -newkey rsa:2048 -sha256 -days 7 -nodes \
      -keyout "$KEY" -out "$CERT" -subj '/CN=127.0.0.1' >/dev/null 2>&1
  fi
  nohup socat OPENSSL-LISTEN:8443,reuseaddr,fork,cert="$CERT",key="$KEY",verify=0 TCP:127.0.0.1:8088 >"$TLS_LOG" 2>&1 &
fi

echo 'WordPress local HTTP : http://127.0.0.1:8088'
echo 'WordPress local HTTPS (certificat de test) : https://127.0.0.1:8443'
