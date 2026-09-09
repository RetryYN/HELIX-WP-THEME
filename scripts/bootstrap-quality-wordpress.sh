#!/usr/bin/env bash
set -euo pipefail

# GitHub Actionsの専用service containerを明示指定する。
container_id="${1:?WordPress service container ID is required}"
[[ "$container_id" =~ ^[a-f0-9]{12,64}$ ]] || { echo 'Invalid service container ID' >&2; exit 1; }
network_name="$(docker inspect "$container_id" --format '{{range $name, $_ := .NetworkSettings.Networks}}{{$name}}{{end}}')"
[[ "$network_name" == github_network_* ]] || { echo 'Dedicated GitHub service network required' >&2; exit 1; }

wp_ci() {
  docker run --rm --network "$network_name" --volumes-from "$container_id" \
    --user 33:33 -e WORDPRESS_DB_HOST=db:3306 -e WORDPRESS_DB_USER=wp \
    -e WORDPRESS_DB_PASSWORD=wp -e WORDPRESS_DB_NAME=wordpress \
    wordpress:cli-php8.3 wp "$@"
}

if ! wp_ci core is-installed; then
  wp_ci core install --url=http://localhost:8086 --title='Theme compatibility fixture' \
    --admin_user=ci_admin --admin_password="$(openssl rand -hex 24)" \
    --admin_email=ci@example.invalid --skip-email
fi
[[ "$(wp_ci core version)" == '7.1' ]] || { echo 'Unexpected WordPress version' >&2; exit 1; }
theme_source='docs/research/2026-09-05-design-prototype-03/theme/helix-wt'
theme_slug='helix-wt'
[[ -f "$theme_source/theme.json" && -f "$theme_source/style.css" ]] || {
  echo "Current theme source is incomplete: $theme_source" >&2
  exit 1
}
docker cp "$theme_source/." "$container_id:/var/www/html/wp-content/themes/$theme_slug"
wp_ci theme activate "$theme_slug"
[[ "$(wp_ci option get stylesheet)" == "$theme_slug" ]] || { echo 'Current theme activation not verified' >&2; exit 1; }
