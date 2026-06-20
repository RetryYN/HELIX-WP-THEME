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
- Implements page apply / rollback endpoints for L4 Sprint `.2a`:
  - `POST /wp-json/agent-neo/v1/pages/{id}/apply`
  - `POST /wp-json/agent-neo/v1/pages/{id}/rollback`
  - `POST /wp-json/agent-neo/v1/rollback/{rollback_id}`
- Adds write-route auth helpers for future `nonce + capability` checks.
- Adds schema loader foundation for `openapi.yaml` and JSON Schema files.
- Registers the private audit CPT `agent_action`.
- Adds activation, deactivation, and uninstall cleanup hooks.
- Implements the catalog-update producer outbox without adding a public `agent-neo/v1` route.

catalog-update remains a producer-only outbound integration; no new public receive endpoint is registered in this plugin.

## REST Controller Registration

New REST controllers must live under `inc/rest/` as `class-{name}-controller.php`. The bootstrap glob-loads `inc/rest/*-controller.php`, so adding a controller only requires self-registration at the end of that controller file with `add_action( 'agent_neo_core_register_rest', ... )`, pulling constructor dependencies from `Agent_Neo_Core_Container` and calling `$container->register_module( 'rest-{name}' )` for health reporting. Do not edit `inc/bootstrap.php` or `inc/class-agent-neo-core.php` when adding a controller; the kernel fires the shared registration hook once after shared modules are ready.

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

Additional verification on 2026-06-21 for L4 Sprint `.2a` pages apply / rollback API:

- `php -l` passed for all PHP files under `plugins/agent-neo-core`.
- `bin/check-prefix.sh` passed.
- `bin/check-impl-coverage.sh --strict-orphan` reported coverage `8/57` and `ORPHAN (0)`.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, or variant/statistical decision logic in PHP files.
- Route discovery:
  - `curl -s http://localhost:8086/wp-json/agent-neo/v1`
  - Confirmed routes: `/pages/(?P<id>\d+)/apply`, `/pages/(?P<id>\d+)/rollback`, `/rollback/(?P<rollback_id>[A-Za-z0-9_-]+)`.
- Unauthenticated HTTP:
  - `curl -s -o /tmp/agent-neo-pages-unauth.json -w "%{http_code}" -X POST http://localhost:8086/wp-json/agent-neo/v1/pages/1/apply ...`
  - Result: `401`.
- WP-CLI REST page dry-run/apply/rollback flow:
  - `dry_status=200`, `dry_success=true`, `diff_hash_present=true`
  - `/pages/{id}/apply`: `apply_status=200`, `apply_success=true`, `rollback_point_present=true`, `applied_blocks=1`, `content_after_apply=after`
  - idempotent replay: `replay_status=200`, `replay_applied=false`
  - `/pages/{id}/rollback`: `rollback_status=200`, `restored_version_present=true`, `content_after_rollback=before`
  - expired rollback point: `expired_status=410`, `expired_code=GONE`
  - missing generic rollback point: `missing_status=404`, `missing_code=NOT_FOUND`
  - personal package with LP/template apply: `package_status=403`, `package_code=FORBIDDEN`
- Generic rollback success:
  - `/rollback/{rollback_id}` returned `generic_rollback_status=200`, `generic_restored_version_present=true`, `generic_content_after=before`.

Additional verification on 2026-06-21 for L4 Sprint `.2a` TL review fixes:

- `php -l plugins/agent-neo-core/inc/rest/class-pages-controller.php` passed.
- `php -l plugins/agent-neo-core/inc/json/class-rollback-store.php` passed.
- `bin/check-prefix.sh` passed.
- `bin/check-impl-coverage.sh --strict-orphan` kept coverage at `8/57` with `ORPHAN (0)`.
- WP-CLI REST verification:
  - A-005 compliant apply without `request_id`: `page_dry_status=200`, `page_apply_no_request_status=200`, `page_apply_success=true`, `page_content_after_apply=after`.
  - `diff_hash` missing: `missing_diff_status=400`, `missing_diff_code=VALIDATION_ERROR`.
  - Dry-run action mismatch for page apply: `mismatch_status=412`, `mismatch_code=PRECONDITION_FAILED`.
  - Existing page rollback regression: `page_rollback_status=200`, `page_rollback_success=true`, `page_content_after_rollback=before`.
  - Package boundary regression: `package_status=403`, `package_code=FORBIDDEN`.
  - Generic rollback restored a `post` rollback point from `/actions/apply`: `post_apply_status=200`, `post_rollback_point_present=yes`, `generic_post_rollback_status=200`, `generic_post_rollback_post_type=post`, `post_content_after_rollback=before`.
  - Generic rollback errors stayed explicit: `missing_rollback_status=404`, `missing_rollback_code=NOT_FOUND`, `expired_rollback_status=410`, `expired_rollback_code=GONE`.

Additional verification on 2026-06-21 for REST controller self-registration refactor:

- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files.
- `bin/check-prefix.sh` passed.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, variant generation, statistical decision logic, or prompt construction in PHP files.
- `docker exec agent-neo-wp ln -sfn agent-neo-plugins/agent-neo-core /var/www/html/wp-content/plugins/agent-neo-core && docker compose run --rm wpcli plugin activate agent-neo-core` completed without fatal; plugin was already active.
- Route discovery via `curl -s http://localhost:8086/wp-json/agent-neo/v1` confirmed the same implemented route set:
  - `/agent-neo/v1/status`
  - `/agent-neo/v1/actions/dry-run`
  - `/agent-neo/v1/actions/apply`
  - `/agent-neo/v1/posts/(?P<id>\d+)/blocks/(?P<block_id>[A-Za-z0-9_-]+)`
  - `/agent-neo/v1/posts/(?P<id>\d+)/sections/(?P<section_id>[a-z0-9-]+)/edit`
  - `/agent-neo/v1/pages/(?P<id>\d+)/apply`
  - `/agent-neo/v1/pages/(?P<id>\d+)/rollback`
  - `/agent-neo/v1/rollback/(?P<rollback_id>[A-Za-z0-9_-]+)`
- Authenticated status check returned `status=200`, `success=true`, and `loaded_modules=schema-loader,auth,license,agent-action-cpt,rest-status,rest-actions,rest-blocks,rest-sections,rest-pages,catalog-update-producer`.
- `bash bin/check-impl-coverage.sh --strict-orphan` kept coverage at `8/57` with `ORPHAN (0)`.
- Existing flow regression stayed green:
  - `dry_status=200`, `dry_success=true`, `diff_hash_present=yes`, `db_unchanged_after_dry=yes`
  - `apply_status=200`, `apply_success=true`, `apply_applied=true`, `rollback_point_present=yes`, `db_changed_after_apply=yes`
  - `replay_status=200`, `replay_applied=false`
  - `rollback_status=200`, `rollback_success=true`, `content_after_rollback=before`, `audit_present=yes`

Additional verification on 2026-06-21 for REST controller glob autoload:

- `php -l plugins/agent-neo-core/inc/bootstrap.php` passed.
- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files.
- `bash bin/check-impl-coverage.sh --strict-orphan` kept coverage at `8/57` with `ORPHAN (0)`.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, variant/statistical decision logic, or prompt construction in PHP files.
- WP 6.9.4 / PHP 8.3 re-activation completed without fatal:
  - `docker compose run --rm wpcli plugin activate agent-neo-core --allow-root`
  - Result: plugin already active / activated successfully.
- Authenticated status after deleting the temporary controller returned the legacy module order:
  - `status=200`, `success=true`
  - `loaded_modules=schema-loader,auth,license,agent-action-cpt,rest-status,rest-actions,rest-blocks,rest-sections,rest-pages,catalog-update-producer`
- Temporary autoload proof:
  - Added `inc/rest/class-zztest-controller.php` with `agent_neo_core_register_rest` self-registration, a test `GET /agent-neo/v1/zztest` route, and `$container->register_module( 'rest-zztest' )`.
  - Re-activation/status check loaded the new controller without editing bootstrap or kernel:
    - `loaded_modules=schema-loader,auth,license,agent-action-cpt,rest-status,rest-actions,rest-blocks,rest-sections,rest-pages,rest-zztest,catalog-update-producer`
  - `GET http://localhost:8086/wp-json/agent-neo/v1/zztest` returned `200` with `{"success":true}`.
  - Deleted `inc/rest/class-zztest-controller.php`; `test ! -e plugins/agent-neo-core/inc/rest/class-zztest-controller.php` returned `zztest_removed`.

Additional verification on 2026-06-21 for L4 Sprint `.2b-1` tracking event API:

- `php -l plugins/agent-neo-core/inc/rest/class-tracking-controller.php` passed.
- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files in the current workspace.
- `bin/check-prefix.sh` passed.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, variant generation, statistical decision logic, or prompt construction in PHP files.
- `bash bin/check-impl-coverage.sh --strict-orphan` reported `POST /tracking/event` as covered and `ORPHAN (0)`. Current workspace coverage was `12/57` because parallel license/settings branches are also present.
- Route discovery via `curl -s http://localhost:8086/wp-json/agent-neo/v1` confirmed `/agent-neo/v1/tracking/event`.
- Authenticated status check showed self-registration loaded `rest-tracking` without editing the kernel or bootstrap.
- WP-CLI REST verification with `wp_set_current_user( 0 )`:
  - Valid `site_token` + HMAC signature + `section_id` / `cta_id` / `variant_id`: `ok_status=200`, `ok_success=true`, `ok_replay=false`, `ok_event_id=evt_d401ecc3c5bc6070ed650bf2931feefd`.
  - Invalid signature: `bad_sig_status=401`, `bad_sig_code=SIGNATURE_INVALID`.
  - Missing `section_id`: `missing_status=400`, `missing_code=VALIDATION_ERROR`.
  - Same nonce replay: `replay_status=200`, `replay_flag=true`.
  - Rate limit with isolated IP/token and test filter limit `1`: `rate_first_status=200`, `rate_second_status=429`, `rate_second_code=RATE_LIMITED`, `rate_retry_status=429`.
  - Bot policy block via metadata: `bot_status=403`, `bot_code=FORBIDDEN`.
  - Missing nonce: `missing_nonce_status=401`, `missing_nonce_code=SIGNATURE_INVALID`.

Additional verification on 2026-06-21 for L4 `.2c-1` settings export/import:

- `php -l plugins/agent-neo-core/inc/rest/class-settings-controller.php` passed.
- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, prompt construction, or variant/statistical decision logic in PHP files.
- `bash bin/check-impl-coverage.sh` reported coverage `12/57` and `ORPHAN (0)`, including:
  - `POST /settings/export`
  - `POST /settings/import`
- WP 6.9.4 / PHP 8.3 REST verification via `rest_do_request()`:
  - Route registration: `route_export=yes`, `route_import=yes`.
  - Two consecutive exports: `export_status_1=200`, `export_status_2=200`, `export_bit_identical=yes`.
  - Export payload import: `import_status=200`.
  - Re-export after import: `roundtrip_bit_identical=yes`.
  - Unauthenticated export: `unauth_status=401`, `unauth_code=UNAUTHORIZED`.
  - Invalid import schema: `invalid_status=400`, `invalid_code=VALIDATION_ERROR`.

Additional verification on 2026-06-21 for L4 `.2b-2` license validate API:

- `php -l plugins/agent-neo-core/inc/license/class-license-state.php` passed.
- `php -l plugins/agent-neo-core/inc/rest/class-license-controller.php` passed.
- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files.
- `bin/check-prefix.sh` passed.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, prompt construction, or variant/statistical decision logic in PHP files.
- `bash bin/check-impl-coverage.sh --strict-orphan` reported coverage `12/57` and `ORPHAN (0)`, including `POST /license/validate`. Current workspace already includes parallel tracking/settings branches, so the aggregate coverage is above the single license delta.
- Route discovery via `curl -s http://localhost:8086/wp-json/agent-neo/v1` confirmed `/agent-neo/v1/license/validate`.
- Unauthenticated HTTP:
  - `curl -s -o /tmp/agent-neo-license-unauth.json -w "%{http_code}" -X POST http://localhost:8086/wp-json/agent-neo/v1/license/validate ...`
  - Result: `401` with `UNAUTHORIZED`.
- WP 6.9.4 / PHP 8.3 REST verification via `rest_do_request()` with `agent_neo_core_license_verification_result` filter:
  - Valid corporate entitlement: `valid_status=200`, `valid_success=true`, `valid_package=corporate`.
  - Transient verification failure after valid state: `grace_validate_status=200`, `grace_readonly=true`, `grace_reason=license_unreachable`.
  - Grace active write guard: `grace_write_status=503`, `grace_write_code=LICENSE_GRACE_PERIOD`.
  - Grace expired write guard: `expired_write_status=403`, `expired_write_code=FEATURE_DISABLED`.
  - Invalid license validate: `invalid_validate_status=403`, `invalid_validate_code=FEATURE_DISABLED`.
  - Invalid license write guard: `invalid_write_status=403`, `invalid_write_code=FEATURE_DISABLED`.
- After verification, local `agent_neo_license_state` was reset to `license_mode=readonly`, `package=personal`.

Additional verification on 2026-06-21 for L4 fan-out TL review fixes:

- `php -l plugins/agent-neo-core/inc/license/class-license-state.php` passed.
- `php -l plugins/agent-neo-core/inc/rest/class-tracking-controller.php` passed.
- `bash bin/check-impl-coverage.sh --strict-orphan` kept coverage at `12/57` with `ORPHAN (0)`.
- License cache verification with a saved valid corporate entitlement and future `next_check_at`:
  - `refresh=false` with upstream forced unreachable: `cache_status=200`, `cache_valid=true`, `cache_readonly=false`, `upstream_calls_after_cache=0`.
  - `refresh=true` with the same upstream failure: `refresh_status=200`, `refresh_readonly=true`, `refresh_reason=license_unreachable`, `upstream_calls_after_refresh=1`.
- License write guard for settings import/export:
  - Grace state `/settings/import`: `grace_import_status=503`, `grace_import_code=LICENSE_GRACE_PERIOD`.
  - Grace state `/settings/export`: `grace_export_status=200`.
  - Invalid state `/settings/import`: `invalid_import_status=403`, `invalid_import_code=FEATURE_DISABLED`.
- Tracking nonce concurrent replay simulation:
  - Pre-existing nonce value with a preserved timeout forced the `add_option( value )` conflict path.
  - Result: `race_replay=true`, `race_event_id=evt_existing_race`, `timeout_exists_after_race=true`.
- Tracking rate limit IP source verification:
  - No trusted proxy configured: changing `X-Forwarded-For` still used `REMOTE_ADDR`; second request hit `RATE_LIMITED`.
  - Trusted proxy configured through `agent_neo_trusted_proxies`: different `X-Forwarded-For` values used separate keys, while repeating the same forwarded IP hit `RATE_LIMITED`.

Additional verification on 2026-06-21 for L4 `.2c` catalog-update producer:

- Research notes used before implementation:
  - WordPress `wp_remote_post()` returns an array or `WP_Error`; `pre_http_request` can short-circuit HTTP calls for mocks.
  - WordPress `wp_after_insert_post` runs after post/template persistence; `updated_option` runs after successful option updates.
- `php -l plugins/agent-neo-core/inc/catalog/class-catalog-update-producer.php` passed.
- `find plugins/agent-neo-core -name '*.php' -print -exec php -l {} \;` passed for all plugin PHP files.
- `bin/check-prefix.sh` passed.
- Static grep found no `wp/v2` route registration, AI SDK import, local risk scoring, prompt construction, or variant/statistical decision logic in PHP files.
- `bash bin/check-impl-coverage.sh --strict-orphan` kept coverage at `12/57` with `ORPHAN (0)`; `POST /aseo/v1/agent-neo/catalog-update` remains missing by design because this task implements the producer, not an `agent-neo/v1` public route.
- WP 6.9.4 / PHP 8.3 plugin health:
  - `health=yes`
  - `catalog=implemented`
- WP-CLI request construction verification:
  - `block_registered=ok`, payload keys: `block_name`
  - `block_unregistered=ok`, payload keys: `block_name`
  - `template_updated=ok`, payload keys: `template_part_slug,diff`
  - `theme_token_updated=ok`, payload keys: `diff`
- WP-CLI `pre_http_request` success mock:
  - Configured endpoint `https://aseo.example.test/aseo/v1/agent-neo/catalog-update`, allowed host `aseo.example.test`, and a test HMAC key.
  - Mocked `200` response with `received=true`, matching `event_id`, `deduplicated=false`, and `next_action=scan-catalog`.
  - Result: `sent=1`, `outbox_count=0`, `receipt_next=scan-catalog`, `scan_hook=yes`.
  - HMAC headers were present (`has_sig=yes`).
- WP-CLI deduplicated response mock:
  - Mocked `200` response with `deduplicated=true`, `next_action=none`.
  - Result: `sent=1`, `deduplicated=true`, `next_action=none`.
- WP-CLI retry verification:
  - `500` response: `attempts=1`, `status=retrying`, `delay=1`.
  - Follow-up `429` response: `attempts2=2`, `last_status2=429`, `delay2=2`.
  - `WP_Error( http_request_failed )` timeout mock: `retrying=1`, `attempts=1`, `last_error=http_request_failed`.
- WP-CLI non-retry 4xx verification:
  - `400` response: `dead=1`, `outbox_count=0`, `dlq_status=400`, `dlq_reason=VALIDATION_ERROR`, `attempts=1`.
- WP-CLI DLQ exhaustion verification:
  - Five consecutive `500` responses moved the event to DLQ.
  - Result: `outbox_count=0`, `dlq_status=409`, `dlq_reason=RETRY_EXHAUSTED`, `attempts=5`, `producer_status=409 RETRY_EXHAUSTED`.
- WP-CLI idempotency verification:
  - Same `event_id` re-enqueue within TTL returned `second_enqueued=no`, `second_dedup=yes`, `outbox_count=1`.

Additional verification on 2026-06-21 for L4 `.2c` lifecycle/uninstall cleanup fixes:

- Research notes used before implementation:
  - WordPress `wp_clear_scheduled_hook()` unschedules all events for a hook when the same hook/args combination is supplied.
  - WordPress plugin `uninstall.php` must keep the `WP_UNINSTALL_PLUGIN` guard and should remove plugin-owned options during uninstall, not deactivation.
- Producer source of truth:
  - Cron hooks cleared on deactivation: `agent_neo_catalog_update_retry`, `agent_neo_catalog_update_process_outbox`.
  - Catalog-update options deleted on uninstall: `agent_neo_catalog_update_outbox`, `agent_neo_catalog_update_dlq`, `agent_neo_catalog_update_receipts`, `agent_neo_catalog_update_known_blocks`.
- `php -l plugins/agent-neo-core/inc/lifecycle/class-lifecycle.php` passed.
- `php -l plugins/agent-neo-core/uninstall.php` passed.
- `bash bin/check-impl-coverage.sh --strict-orphan` kept coverage at `12/57` with `ORPHAN (0)`.
- Deactivation simulation with stubbed WordPress functions confirmed both scheduled hooks were cleared and `agent_neo_catalog_update_process_outbox` would have `wp_next_scheduled(...) === false` after clear.
- Uninstall simulation with stubbed WordPress functions confirmed all four producer-owned catalog-update options were passed to `delete_option()`.
