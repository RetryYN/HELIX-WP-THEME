# AGENT NEO Core Plugin

AGENT NEO Core Plugin scaffold for `agent-neo/v1`.

## Scope

- Registers the `agent-neo/v1` REST namespace.
- Implements `GET /wp-json/agent-neo/v1/status`.
- Implements JSON operation endpoints for L4 Sprint `.1b`:
  - `POST /wp-json/agent-neo/v1/actions/dry-run`
  - `POST /wp-json/agent-neo/v1/actions/apply`
  - `PATCH /wp-json/agent-neo/v1/posts/{id}/blocks/{block_id}`
  - `POST /wp-json/agent-neo/v1/posts/{id}/sections/{section_id}/edit`
- Adds write-route auth helpers for future `nonce + capability` checks.
- Adds schema loader foundation for `openapi.yaml` and JSON Schema files.
- Registers the private audit CPT `agent_action`.
- Adds activation, deactivation, and uninstall cleanup hooks.
- Places the catalog-update producer skeleton without outbound send logic.

Tracking, license validate, catalog-update send, pages apply, and rollback API endpoints are intentionally not implemented in this sprint.

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

Additional verification on 2026-06-21 for L4 Sprint `.1b` JSON operation API:

- `php -l` passed for all PHP files under `plugins/agent-neo-core`.
- `bin/check-prefix.sh` passed.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, or variant/statistical decision logic in PHP files.
- Route discovery:
  - `curl -s http://localhost:8086/wp-json/agent-neo/v1`
  - Confirmed routes: `/actions/dry-run`, `/actions/apply`, `/posts/(?P<id>\d+)/blocks/(?P<block_id>[A-Za-z0-9_-]+)`, `/posts/(?P<id>\d+)/sections/(?P<section_id>[a-z0-9-]+)/edit`.
- Standalone PHP verification with ABSPATH and WP stubs:
  - `json_patch=pass`
  - `diff_hash_deterministic=pass`
  - `idempotency_noop=pass`
  - `rollback_snapshot_version=pass`
- WP-CLI REST dry-run/apply flow:
  - `dry_status=200`, `dry_success=true`, `diff_hash_present=true`
  - `db_unchanged_after_dry=true`
  - `apply_status=200`, `apply_success=true`, `applied=true`
  - `rollback_point_present=true`, `db_changed_after_apply=true`
  - replay with the same `idempotency_key`: `replay_status=200`, `replay_applied=false`
- `agent_action` CPT audit verification:
  - `wp post list --post_type=agent_action --fields=ID,post_title,post_status --format=table --allow-root`
  - Result included `patch_post <request_id>` with `publish` status.
- Block update verification:
  - `block_status=200`, `block_success=true`, `block_history_present=true`
  - `block_target_changed=true`, `block_other_preserved=true`
- Section update verification:
  - `section_status=200`, `section_success=true`
  - `section_target_changed=true`, `section_other_preserved=true`

Additional verification on 2026-06-21 for L4 Sprint `.1b` TL review fixes:

- `php -l plugins/agent-neo-core/inc/rest/class-actions-controller.php` passed.
- `php -l plugins/agent-neo-core/inc/rest/class-sections-controller.php` passed.
- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files.
- `bin/check-prefix.sh` passed.
- `bin/check-impl-coverage.sh` kept coverage at `5/57` with no orphan routes.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, or variant/statistical decision logic in PHP files.
- P1 object-level apply authorization:
  - Admin dry-run created a `patch_post` apply token for an admin-owned post.
  - Author role context (`edit_posts` yes, `edit_post` for the admin-owned post no) called `/actions/apply`.
  - Result: `p1_denied_status=403`, `p1_denied_code=FORBIDDEN`.
  - Admin context with the same dry-run metadata succeeded: `p1_allowed_status=200`.
- P2 dry-run metadata binding:
  - `apply_page` dry-run replayed as `patch_post` was rejected.
  - Result: `p2_action_mismatch_status=412`, `p2_action_mismatch_code=PRECONDITION_FAILED`.
  - Correct `apply_page` request with `from_preview_token` succeeded: `p2_action_correct_status=200`.
- P2 section marker preservation:
  - Section edit payload omitted the original `section_id` marker.
  - Two consecutive calls to `/posts/{id}/sections/{section_id}/edit` both succeeded.
  - Result: `section_first_status=200`, `section_second_status=200`, `section_resolves_after_second=yes`.
- Regression flow:
  - `dry-run -> apply -> idempotent replay -> rollback point -> audit` stayed green.
  - Result: `reg_dry_status=200`, `reg_db_unchanged_after_dry=yes`, `reg_apply_status=200`, `reg_apply_applied=true`, `reg_rollback_point_present=yes`, `reg_replay_status=200`, `reg_replay_applied=false`, `reg_audit_present=yes`.
