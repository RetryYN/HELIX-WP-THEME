#!/usr/bin/env bash
# AGENT NEO dev environment initializer
# Usage: bash scripts/dev-init.sh

set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Starting Docker services..."
docker compose up -d db wordpress

echo "==> Waiting for WordPress to respond..."
until curl -fsS -o /dev/null http://localhost:8086; do
  printf "."
  sleep 2
done
echo " ready."

echo "==> Running WP CLI core install (idempotent)..."
docker compose run --rm wpcli core install \
  --url=http://localhost:8086 \
  --title="AGENT NEO Dev" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=dev@agent-neo.local \
  --skip-email \
  || echo "(already installed)"

echo "==> Activating seo-tool-connector plugin (if available)..."
docker compose run --rm wpcli plugin activate seo-tool-connector || true

echo "==> Setting permalinks to /%postname%/ ..."
docker compose run --rm wpcli rewrite structure '/%postname%/' --hard

echo "==> Done."
echo
echo "WordPress:    http://localhost:8086"
echo "Admin:        http://localhost:8086/wp-admin/"
echo "  user/pass:  admin / admin"
echo "DB port:      localhost:3308"
echo
echo "WP CLI usage: docker compose run --rm wpcli <command>"
