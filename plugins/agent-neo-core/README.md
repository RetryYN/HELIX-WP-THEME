# AGENT NEO Core Plugin

AGENT NEO Core Plugin scaffold for `agent-neo/v1`.

## Scope

- Registers the `agent-neo/v1` REST namespace.
- Implements only `GET /wp-json/agent-neo/v1/status`.
- Adds write-route auth helpers for future `nonce + capability` checks.
- Adds schema loader foundation for `openapi.yaml` and JSON Schema files.
- Registers the private audit CPT `agent_action`.
- Adds activation, deactivation, and uninstall cleanup hooks.
- Places the catalog-update producer skeleton without outbound send logic.

Dry-run, apply, PATCH, section edit, rollback, tracking, license validate, and catalog-update send endpoints are intentionally not implemented in this scaffold sprint.

## Verification Commands

```bash
find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;
rg -n "wp/v2|openai|anthropic|xai|risk_score|score\\s*>" plugins/agent-neo-core --glob '*.php'
rg -n "register_rest_route\\(" plugins/agent-neo-core --glob '*.php'
```

Docker/WP-CLI smoke test:

```bash
docker exec agent-neo-wp ln -sfn agent-neo-plugins/agent-neo-core /var/www/html/wp-content/plugins/agent-neo-core
wp rewrite structure '/%postname%/' --hard
wp plugin activate agent-neo-core
curl -s -o /dev/null -w "%{http_code}" http://localhost:8086/wp-json/agent-neo/v1/status
wp eval 'echo post_type_exists("agent_action") ? "agent_action:yes\n" : "agent_action:no\n";'
```

## Verification Results

Verified in this workspace:

- `php -l` passed for all PHP files under `plugins/agent-neo-core`.
- Static grep found no `wp/v2` route registration, AI SDK import, prompt construction, or local risk scoring in PHP files.
- `register_rest_route()` is centralized in `inc/rest/class-rest-controller-base.php` with the `agent-neo/v1` namespace.
- Prior Docker smoke test on WP 6.9.4 / PHP 8.3 before `/status` auth hardening:
  - Created the local compose symlink `wp-content/plugins/agent-neo-core -> agent-neo-plugins/agent-neo-core`.
  - Set the local permalink structure to `/%postname%/` so `/wp-json/...` pretty REST routes resolve in this container.
  - `wp plugin activate agent-neo-core --allow-root` succeeded.
  - `GET http://localhost:8086/wp-json/agent-neo/v1/status/` returned 200 JSON with `license_mode`, `package`, `integration_health`, `theme`, and `core_plugin_version`.
  - `wp eval 'echo post_type_exists("agent_action") ? "agent_action:yes\n" : "agent_action:no\n";' --allow-root` returned `agent_action:yes`.

Additional verification on 2026-06-21 for `/status` authentication:

- `php -l plugins/agent-neo-core/inc/rest/class-status-controller.php` passed.
- Unauthenticated HTTP:
  - `curl -s -o /tmp/agent-neo-status-body.json -w "%{http_code}" http://localhost:8086/wp-json/agent-neo/v1/status`
  - Result: `401` with `UNAUTHORIZED`.
- Authenticated admin context:
  - `docker compose run --rm wpcli eval 'wp_set_current_user( 1 ); $request = new WP_REST_Request( "GET", "/agent-neo/v1/status" ); $response = rest_do_request( $request ); $data = $response->get_data(); echo "status=" . $response->get_status() . "\n"; echo "success=" . ( isset( $data["success"] ) && $data["success"] ? "true" : "false" ) . "\n"; echo "has_loaded_modules=" . ( isset( $data["data"]["loaded_modules"] ) ? "true" : "false" ) . "\n"; echo "has_license_mode=" . ( isset( $data["data"]["license_mode"] ) ? "true" : "false" ) . "\n"; echo "has_theme=" . ( isset( $data["data"]["theme"] ) ? "true" : "false" ) . "\n";'`
  - Result: `status=200`, `success=true`, `has_loaded_modules=true`, `has_license_mode=true`, `has_theme=true`.
- Logged-in context without `edit_posts`:
  - `docker compose run --rm wpcli eval 'wp_set_current_user( 1 ); add_filter( "user_has_cap", function( $allcaps ) { unset( $allcaps["edit_posts"] ); return $allcaps; }, 999 ); $request = new WP_REST_Request( "GET", "/agent-neo/v1/status" ); $response = rest_do_request( $request ); $data = $response->get_data(); echo "status=" . $response->get_status() . "\n"; echo "code=" . ( $data["code"] ?? "" ) . "\n";'`
  - Result: `status=403`, `code=FORBIDDEN`.
