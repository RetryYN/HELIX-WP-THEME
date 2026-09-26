#!/usr/bin/env bash
set -euo pipefail

# GitHub Actionsの専用service containerを明示指定する。
container_id="${1:?WordPress service container ID is required}"
[[ "$container_id" =~ ^[a-f0-9]{12,64}$ ]] || { echo 'Invalid service container ID' >&2; exit 1; }
wp_ci_url="${WP_CI_URL:-http://localhost:8086}"
network_name="$(docker inspect "$container_id" --format '{{range $name, $_ := .NetworkSettings.Networks}}{{$name}}{{end}}')"
[[ "$network_name" == github_network_* ]] || { echo 'Dedicated GitHub service network required' >&2; exit 1; }

wp_ci() {
  docker run --rm --network "$network_name" --volumes-from "$container_id" \
    --user 33:33 -e WORDPRESS_DB_HOST=db:3306 -e WORDPRESS_DB_USER=wp \
    -e WORDPRESS_DB_PASSWORD=wp -e WORDPRESS_DB_NAME=wordpress \
    wordpress:cli-php8.3 wp "$@"
}

if ! wp_ci core is-installed; then
  wp_ci core install --url="$wp_ci_url" --title='Theme compatibility fixture' \
    --admin_user=ci_admin --admin_password="$(openssl rand -hex 24)" \
    --admin_email=ci@example.invalid --skip-email
fi
wp_version="$(wp_ci core version)"
[[ "$wp_version" == '7.1' || "$wp_version" == 7.1.* ]] || {
  echo "Unexpected WordPress version: $wp_version (expected 7.1.x)" >&2
  exit 1
}
theme_source='docs/research/2026-09-05-design-prototype-03/theme/helix-wt'
theme_slug='helix-wt'
[[ -f "$theme_source/theme.json" && -f "$theme_source/style.css" ]] || {
  echo "Current theme source is incomplete: $theme_source" >&2
  exit 1
}
docker cp "$theme_source/." "$container_id:/var/www/html/wp-content/themes/$theme_slug"
wp_ci theme activate "$theme_slug"
[[ "$(wp_ci option get stylesheet)" == "$theme_slug" ]] || { echo 'Current theme activation not verified' >&2; exit 1; }

# Keep a small, deterministic set of real WordPress routes for browser gates.
# This database belongs to the disposable GitHub Actions service only.
ensure_post() {
  local slug="$1" type="$2" title="$3" content="$4"
  local id
  id="$(wp_ci post list --post_type="$type" --name="$slug" --field=ID --format=ids)"
  if [[ -z "$id" ]]; then
    id="$(wp_ci post create --post_type="$type" --post_status=publish --post_name="$slug" \
      --post_title="$title" --post_content="$content" --porcelain)"
  fi
  printf '%s' "$id"
}

front_id="$(ensure_post a11y-home page 'Accessibility gate home' \
  '<p>Public home fixture for automated accessibility checks.</p><p><a href="/a11y-page/">Read the information page</a></p>')"
ensure_post a11y-page page 'Accessibility gate information' \
  '<p>Information page fixture for automated accessibility checks.</p>' >/dev/null
article_id="$(ensure_post a11y-fixture-article post 'Accessibility gate article' \
  '<p>Article fixture for automated accessibility checks.</p>')"
ensure_page_template() {
  local slug="$1" title="$2" template="$3" content="$4" id
  id="$(ensure_post "$slug" page "$title" "$content")"
  wp_ci post meta update "$id" _wp_page_template "$template" >/dev/null
}
ensure_page_template a11y-fixture-lp 'Accessibility gate landing page' page-lp \
  '<!-- wp:heading {"level":1} --><h1>Landing page fixture</h1><!-- /wp:heading --><p>Accessibility checks for the landing page template.</p>'
ensure_page_template a11y-fixture-event 'Accessibility gate event page' page-event \
  '<!-- wp:heading {"level":1} --><h1>Event page fixture</h1><!-- /wp:heading --><p>Accessibility checks for the event template.</p>'
ensure_page_template a11y-fixture-zone-catalog 'Accessibility gate zone catalog' page-zone-catalog \
  '<!-- wp:heading {"level":1} --><h1>Zone catalog fixture</h1><!-- /wp:heading --><p>Accessibility checks for the zone catalog template.</p>'
ensure_page_template a11y-fixture-canvas 'Accessibility gate canvas page' page-canvas \
  '<!-- wp:heading {"level":1} --><h1>Canvas page fixture</h1><!-- /wp:heading --><p>Accessibility checks for the canvas template.</p>'
category_id="$(wp_ci term list category --slug=a11y-fixture --field=term_id --format=ids)"
if [[ -z "$category_id" ]]; then
  category_id="$(wp_ci term create category 'Accessibility fixture' --slug=a11y-fixture --porcelain)"
fi
wp_ci post term add "$article_id" category "$category_id" >/dev/null
wp_ci option update show_on_front page >/dev/null
wp_ci option update page_on_front "$front_id" >/dev/null
wp_ci option update permalink_structure '/%postname%/' >/dev/null
wp_ci rewrite flush >/dev/null
