# Phase1 Implementation Matrix (L1-L4 SSOT)

## Summary

- **Sprint/Task progress**: VERIFIED **29** / IMPL **0** / SCAFFOLD **0** / NONE **0** *(total 29)*
- **Endpoint coverage**: `Y` **25** / `N` **32** *(total 57)*
- **Phase1 launch F-ID**: **完了 2** / 残 **23** *(total 25)*
- **残タスク（next）**:
  - `.3〜.5`: **T-017〜T-026**
- **運用ルール（更新時）**:
  - 各 sprint 完了時に本表を更新
  - `bin/check-impl-coverage.sh` で `coverage = 25/57` を確認済み（`/aseo/v1/agent-neo/catalog-update` は受け口/外部連携枠）
  - 注記: endpoint の実在(Y/N)は `bin/check-impl-coverage.sh` を正本とし、sprint 完了時点で同期

---

## Table 1: WBS task coverage (T-001〜T-029)

| T-ID | 対応 F-ID | 内容(要約) | sprint | 想定実装先(theme/core-plugin/embed-plugin) | status | 実装ファイル | 検証 evidence | commit |
|---|---|---|---|---|---|---|---|---|
| T-001 | F-001 | Theme 起動トレース整備（bootstrap/health） | .1a | theme | VERIFIED | `themes/agent-neo-theme/functions.php`, `themes/agent-neo-theme/inc/bootstrap.php`, `themes/agent-neo-theme/inc/class-agent-neo-theme.php` | `agent_neo_health()` と `trace_step` の実体、`themes/agent-neo-theme/README.md`（起動時確認） | 04154e1 |
| T-002 | F-001 | Theme/Plugin 境界表の実行定義化 | .1a | theme | VERIFIED | `themes/agent-neo-theme/inc/setup/class-boundary-guard.php`, `themes/agent-neo-theme/inc/class-agent-neo-theme.php` | `Theme/Plugin` 所有者ルールの実装と README の境界検査記載 | 04154e1 |
| T-003 | F-001 | theme-manifest / section-registry スキーマ反映 | .1a | theme | VERIFIED | `themes/agent-neo-theme/config/theme-manifest.json`, `themes/agent-neo-theme/config/section-registry.json`, `themes/agent-neo-theme/inc/class-config-loader.php` | config loader の fail-fast / schema validation 実体 | 04154e1 |
| T-004 | F-001 | `agent_neo` プレフィックス規約静的検査 | .1a | theme | VERIFIED | `themes/agent-neo-theme/inc/class-config-loader.php`, `themes/agent-neo-theme/config/schema-reference.json` | schema-reference の `json_prefix: agent_neo` + naming 検査 | 04154e1 |
| T-005 | F-025 | `agent_neo` 統一 JSON 方針と schema 参照表作成 | .1a | theme | VERIFIED | `themes/agent-neo-theme/config/schema-reference.json`, `themes/agent-neo-theme/inc/class-config-loader.php` | `schema-reference` 読込と openapi/schema 参照定義 | 04154e1 |
| T-006 | F-002 | `POST /actions/dry-run` 実装 I/O 契約 | .1b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-actions-controller.php`（scaffold 実体） | dry-run200 (DB不変) / 権限403 / action412 | 5de1a57 |
| T-007 | F-002 | `POST /actions/apply` 実装 I/O 契約 | .1b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-actions-controller.php` | apply200 (DB変化) / replay applied=false / rollback_point | 5de1a57 |
| T-008 | F-021 | ブロック PATCH endpoint 契約実装（単位更新） | .1b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-blocks-controller.php`, `plugins/agent-neo-core/inc/json/class-json-patch.php`, `plugins/agent-neo-core/inc/json/class-dry-run-store.php`, `plugins/agent-neo-core/inc/json/class-idempotency-store.php`, `plugins/agent-neo-core/inc/json/class-rollback-store.php`, `plugins/agent-neo-core/inc/json/class-audit-log.php` | section2回200 / audit CPT / 権限403 | 5de1a57 |
| T-009 | F-021 | `POST /posts/{id}/sections/{section_id}/edit` 実装 | .1b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-sections-controller.php`, `plugins/agent-neo-core/inc/json/class-json-patch.php`, `plugins/agent-neo-core/inc/json/class-dry-run-store.php`, `plugins/agent-neo-core/inc/json/class-idempotency-store.php`, `plugins/agent-neo-core/inc/json/class-rollback-store.php`, `plugins/agent-neo-core/inc/json/class-audit-log.php` | section2回200 / audit CPT | 5de1a57 |
| T-010 | F-002 | `/pages/{id}/apply` + `from_preview_token` | .1b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-pages-controller.php`, `inc/json/class-rollback-store.php`（post_type 永続化） | apply(request_id無し)200 / diff_hash欠落400 / generic rollback で post 内容復元200 / TTL切れ410 / 不在404 / package境界403 | 18d982f |
| T-011 | F-002 | rollback API 実装 | .1b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-pages-controller.php`, `inc/json/class-rollback-store.php`（post_type 永続化） | apply(request_id無し)200 / diff_hash欠落400 / generic rollback で post 内容復元200 / TTL切れ410 / 不在404 / package境界403 | 18d982f |
| T-012 | F-006 | `/tracking/event` 署名/nonce/bot filter | .2b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-tracking-controller.php` | tracking 署名200/401/section欠落400/replay/429 を実機検証 | 70fbbb0 |
| T-013 | F-010 | `/license/validate` + failure 制御 | .2b | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-license-controller.php`, `plugins/agent-neo-core/inc/license/class-license-state.php` | license 2モード(grace503/invalid403)+24hキャッシュ（非refresh upstream0）を実機検証 | 70fbbb0 |
| T-014 | F-044 | catalog-update request schema 受け口と応答固定 | .2 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/catalog/class-catalog-update-producer.php` (`class-catalog-update-producer.php`), `plugins/agent-neo-core/inc/lifecycle/class-lifecycle.php`, `plugins/agent-neo-core/uninstall.php` | `event_kind`4種 enqueue、HMAC署名push、受信/重複排除/次アクション分岐検証、4xx即DLQ、`event_id` 24h冪等、cron/option cleanup 実機検証 | b1f33ad |
| T-015 | F-044 | Outbox retry / DLQ | .2 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/catalog/class-catalog-update-producer.php` (`class-catalog-update-producer.php`), `plugins/agent-neo-core/inc/lifecycle/class-lifecycle.php`, `plugins/agent-neo-core/uninstall.php` | 指数バックオフ(1s/2^n/±10%、max5)、5xx/429/timeout retry、409 RETRY_EXHAUSTED の5回DLQ、event_kind4種 enqueue、実機検証 | b1f33ad |
| T-016 | F-025 | JSON 統合入出力（settings） | .2b/.2c | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-settings-controller.php` | settings bit-identical / import guard を実機検証 | 70fbbb0 |
| T-017 | F-011 | SEO 入力検証・共存 | .3 | theme/core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-seo-controller.php` | GET(canonical/noindex/OGP/JSON-LD)/POST(canonical/noindex/apply+deprecated) / rollback + risk passthrough / risk_diff欠落400 / duplicate warning / wp_slash quote/backslash round-trip | 6b02b37 |
| T-018 | F-011 | 計測/SEO 監査ログ保存（agent_action CPT） | .3 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-logs-controller.php`, `plugins/agent-neo-core/inc/cpt/class-agent-action-cpt.php` | GET /logs の必須フィールド5件・request_idフィルタ・401/403・agent_action CPT監査 | 0cd46bb |
| T-019 | F-010/F-016 | 個人版 package 境界 | .3 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-features-controller.php` | GET /features の ACC-PF-003 package-keyed 応答（include=package=現package / include=all=personal+corporate）・flag tier 写像（personal.corporate_lp=false / corporate=全true・各16flag）・未認証401 を実機WP rest_do_request 検証 | 0ead253 |
| T-020 | F-004/F-030 | 個人版 CV module（収益化ブロック生成） | .3 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-affiliate-controller.php` | POST /affiliate/block の block_type 5種（review/ranking/comparison/affiliate_cta/product_card）静的構造組立・XSSエスケープ・enum外400・payload欠落400・review rating必須(0-5)400・未認証401 を実機WP VDD（AI生成なし=REQ-NF-025） | 3c23c08 |
| T-021 | F-005/F-012 | LP/HP blueprint API とページ apply 接続 | .3 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-blueprint-controller.php` | section_kind検証（hero…final-cta）・blueprint_id/section_id 一貫・契約外kind400・package境界403・pages apply接続・fail-before-write | 0cd46bb |
| T-022 | F-013/F-031 | 法人版リード寄与権限制御（CTA管理） | .4 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-ctas-controller.php` | GET /ctas・GET /ctas/{cta_id}・POST /ctas/{cta_id}/apply を corporate 専用化（個人→403 越境拒否）・apply diff_hash409/idempotency applied=false/license guard grace503・invalid403/route slug制約 を実機WP VDD。content-level element swap（ACC-023）は別 /elements/swap（REQ-F-023・未実装）の責務 | b47f1de |
| T-023 | F-011/F-023/F-024 | Performance + a11y + i18n/RTL gate パイプライン | .4 | theme | VERIFIED | `bin/check-theme-quality.sh`, `plugins/agent-neo-core/inc/util/class-slug.php`, `bin/verify-slug.php`, `.github/workflows/theme-quality-gate.yml` | sanitize_slug TC-017b/TC-025 単体11/0 PASS・i18n/RTL/a11y(axe critical-serious 0)/perf(web-vitals budget) 4ゲート RESULT PASS(FAIL0)・テーマ a11y serious 11→0・実LCP/INP/CLS は CI lhci | 29ee7b5 |
| T-024 | F-006/F-007/F-026/F-027 | 連携契約（tracking-context / CAT契約テスト） | .5 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/rest/class-tracking-controller.php`, `bin/verify-catalog-contract.php` | POST /tracking/context（TC-013 署名401/schema400/正常200 実機VDD）+ CAT-001〜009 全PASS（4フィールド応答/dedup/validation/backoff 1s・2^n・max5・±10%jitter/DLQ409/retry429）。catalog-update producer は T-014/T-015 実装済 | 979a1a7 |
| T-025 | F-020/F-021/F-023 | SBOM 生成・検証 | .5 | core-plugin | VERIFIED | `bin/generate-sbom.php`, `bin/check-sbom-gate.sh`, `sbom.cdx.json` | CycloneDX 1.6・5 component・外部依存ゼロ。Release/SBOM Gate（依存元/ライセンス/checksum/changelog/rollback）PASS 6/0WARN/0FAIL。embed License補完+3CHANGELOG+runbook-rollback | 6a29a55 |
| T-026 | F-003 | 操作面（REST/MCP/WP CLI/React UI）統合検証 | .1a〜.2 | core-plugin | VERIFIED | `plugins/agent-neo-core/inc/cli/class-cli-command.php`, `plugins/agent-neo-core/inc/mcp/class-abilities.php`, `plugins/agent-neo-core/inc/admin/class-admin-page.php` | 4操作面を同一 JSON 契約に集約（ADR-002/012）。WP-CLI/MCP(Abilities) は rest_do_request・React UI は apiFetch で REST 契約へ委譲。GET /status が REST/WP-CLI/MCP 完全一致+React UI 登録を実機検証（機能差異ゼロ） | eddb6de |
| T-027 | REQ-F-038 / ADR-026 | embed static mode 実装 | .4 | embed-plugin | VERIFIED | `plugins/agent-neo-embed/agent-neo-embed.php`, `plugins/agent-neo-embed/src/embed/block.json`, `plugins/agent-neo-embed/src/embed/render.php`, `plugins/agent-neo-embed/assets/embed-reset.css`, `plugins/agent-neo-embed/src/embed/view.js` | PoC verify.py 10本 all PASS（static Shadow DOM 非継承/継承 CSS隔離・Light DOM侵入なし実測）+ 実WP DSD SSR。poc/embed-isolation/RESULTS.md VERDICT PASS | 721642c |
| T-028 | REQ-F-038 / ADR-026 | embed interactive mode 実装 | .4 | embed-plugin | VERIFIED | `plugins/agent-neo-embed/agent-neo-embed.php`, `plugins/agent-neo-embed/src/embed/edit.js`, `plugins/agent-neo-embed/src/embed/view.js`, `plugins/agent-neo-embed/src/embed/block.json` | PoC verify.py PASS: iframe sandbox（allow-scripts のみ/allow-same-origin・allow-top-navigation 不在）・parent-cannot-read-iframe・egress sink0・form-action CSP・postMessage source+nonce。実運用ホスト接続は CARRY-EMBED-005（deploy 繰延） | 721642c |
| T-029 | CARRY-EMBED-006 / ADR-026 | embed CI gate 自動化 | .4 | embed-plugin | VERIFIED | `.github/workflows/embed-isolation.yml`, `poc/embed-isolation/verify.py` | verify.py（10本 all PASS・exit 0/非0 正常）を GitHub Actions CI（push/PR・fail-fast）へ統合。継続回帰ゲート確立 | 721642c |

---

## Table 2: API endpoint coverage（agent-neo/v1 + aseo/v1）

| method | path | 対応 T-ID | sprint | status | register_rest_route 実在(Y/N) | 実装ファイル |
|---|---|---|---|---|---|---|
| GET | /status | T-005 | .1a | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-status-controller.php` |
| GET | /health | - | - | NONE | N | - |
| GET | /features | T-019 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-features-controller.php` |
| GET | /contracts | - | - | NONE | N | - |
| GET | /posts | - | - | NONE | N | - |
| GET | /posts/{id} | - | - | NONE | N | - |
| GET | /posts/{id}/diff | - | - | NONE | N | - |
| GET | /posts/{id}/markdown | - | - | NONE | N | - |
| PATCH | /posts/{id}/blocks/{block_id} | T-008 | .1b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-blocks-controller.php` |
| POST | /posts/{id}/sections/{section_id}/edit | T-009 | .1b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-sections-controller.php` |
| GET | /sections | - | - | NONE | N | - |
| GET | /sections/{section_id} | - | - | NONE | N | - |
| POST | /sections/{section_id}/apply | - | - | NONE | N | - |
| GET | /pages | - | - | NONE | N | - |
| GET | /pages/{id} | - | - | NONE | N | - |
| POST | /pages/blueprint | T-021 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-blueprint-controller.php` |
| POST | /lp/sections | T-021 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-blueprint-controller.php` |
| POST | /pages/{id}/preview | - | - | NONE | N | - |
| POST | /pages/{id}/apply | - | - | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-pages-controller.php` |
| POST | /pages/{id}/rollback | - | - | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-pages-controller.php` |
| POST | /rollback/{rollback_id} | - | - | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-pages-controller.php` |
| GET | /ctas | T-022 | .4 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-ctas-controller.php` |
| GET | /ctas/{cta_id} | T-022 | .4 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-ctas-controller.php` |
| POST | /ctas/{cta_id}/apply | T-022 | .4 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-ctas-controller.php` |
| POST | /elements/swap | - | - | NONE | N | - |
| POST | /actions/dry-run | T-006 | .1b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-actions-controller.php` |
| POST | /actions/apply | T-007 | .1b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-actions-controller.php` |
| PATCH | /batch | - | - | NONE | N | - |
| POST | /design-tokens/apply | - | - | NONE | N | - |
| GET | /design-tokens | - | - | NONE | N | - |
| GET | /seo/{post_id} | T-017 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-seo-controller.php` |
| POST | /seo/{post_id}/apply | T-017 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-seo-controller.php` |
| POST | /seo/meta | T-017 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-seo-controller.php` |
| POST | /media/upload | - | - | NONE | N | - |
| POST | /migration/jobs | - | - | NONE | N | - |
| POST | /tracking/event | T-012 | .2b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-tracking-controller.php` |
| POST | /tracking/context | T-024 | .5 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-tracking-controller.php` |
| GET | /tracking/llmo-summary | - | - | NONE | N | - |
| POST | /license/validate | T-013 | .2b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-license-controller.php` |
| POST | /settings/export | T-016 | .2b/.2c | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-settings-controller.php` |
| POST | /settings/import | T-016 | .2b/.2c | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-settings-controller.php` |
| POST | /jobs | - | - | NONE | N | - |
| GET | /jobs/{job_id} | - | - | NONE | N | - |
| POST | /jobs/{job_id}/cancel | - | - | NONE | N | - |
| GET | /logs | T-018 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-logs-controller.php` |
| GET | /public/pages/{id}/snapshot | - | - | NONE | N | - |
| GET | /public/crawl-map | - | - | NONE | N | - |
| GET | /public/llmo/answers | - | - | NONE | N | - |
| GET | /automation-seo/fit | - | - | NONE | N | - |
| POST | /automation-seo/fit | - | - | NONE | N | - |
| GET | /automation-seo/bridge-profile | - | - | NONE | N | - |
| GET | /risks/hazards | - | - | NONE | N | - |
| GET | /crawler-policy | - | - | NONE | N | - |
| POST | /crawler-policy | - | - | NONE | N | - |
| POST | /affiliate/block | T-020 | .3 | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-affiliate-controller.php` |
| POST | /aseo/v1/agent-neo/catalog-update | T-014 / T-015 | .2 | NONE | N | external receiver / out of agent-neo scope（coverage 除外対象） |

---

## Table 3: Phase1 launch set F-ID coverage（F-001〜F-025）

| F-ID | 対応 T-ID | status | 現状 |
|---|---|---|---|
| F-001 | T-001, T-002, T-003, T-004 | VERIFIED | launch 必達として完了 | 
| F-002 | T-006, T-007, T-010, T-011 | VERIFIED | dry-run/apply/pages が実機検証済み。`POST /pages/{id}/apply` / `POST /pages/{id}/rollback` / `POST /rollback/{rollback_id}` は `18d982f`（fix 含む）で検証済み |
| F-003 | T-026 | VERIFIED | 4操作面（REST/MCP/WP-CLI/React UI）を同一契約で実装・GET /status 一致を実機検証 |
| F-004 | T-020 | VERIFIED | 収益化ブロック生成 API（/affiliate/block・5 block_type）実機検証済。AI生成なし（REQ-NF-025） |
| F-005 | T-021 | VERIFIED | blueprint API 実装（section kind、section_id/blueprint_id、pages apply接続）検証済み |
| F-006 | T-012, T-024 | VERIFIED | `/tracking/event`（T-012）+ `/tracking/context`（T-024）+ catalog-update CAT契約テスト 全実機検証済 |
| F-007 | T-024 | VERIFIED | Automation SEO 連携（/tracking/context A-008 + catalog-update CAT-001〜009）実機検証済 |
| F-008 | T-024 | VERIFIED | tracking/webhook 連携（context 同期 + catalog-update push 契約）実機検証済 |
| F-009 | T-021 | NONE | 設定 import/export endpoint 未実装 | 
| F-010 | T-013, T-019 | VERIFIED | `/license/validate`（T-013）+ /features package境界フラグ（T-019）とも実機検証済 | 
| F-011 | T-017, T-018, T-023 | VERIFIED | SEO Core（T-017/T-018）+ perf/a11y/i18n/RTL 品質ゲート（T-023・RESULT PASS）とも実機検証済 |
| F-012 | T-021 | VERIFIED | LP/HP blueprint API（`POST /pages/blueprint`, `POST /lp/sections`）実装済み |
| F-013 | T-022 | VERIFIED | CTA管理 API（/ctas 3種）corporate 専用・個人→403 越境拒否を実機検証 | 
| F-014 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-015 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-016 | T-019 | PARTIAL | package 境界フラグ（/features）と既存 check_package_scope による個人版 HP/LP 書換え拒否は実装。テンプレ固定構成の全 enforcement は残 | 
| F-017 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-018 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-019 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-020 | T-025, T-023 | PARTIAL | Release/SBOM Gate（T-025）実装・PASS。a11y/i18n/RTL/Theme Review gate は T-023 で残 | 
| F-021 | T-008, T-009 | PARTIAL | section編集（block/section） endpoint は実装・検証済み。全体ロールは未完了 |
| F-022 | T-009 | PARTIAL | section edit endpoint は実装済み。apply/rollback など未実装 |
| F-023 | T-023, T-025 | NONE | swap / CV関連 API 未実装 | 
| F-024 | T-023 | NONE | A/B テスト運用連携未実装 | 
| F-025 | T-016 | VERIFIED | 設定 settings import/export の JSON 統合は実機検証済み | 
| F-044 | T-014, T-015 | VERIFIED | catalog-update 受け口と Outbox/DLQ は実機検証済み |

### Phase1 status summary
- **完了**: F-001, F-025（2）
- **残**: F-002〜F-024（23）

---

## Table 4: Non-endpoint deliverables

| 領域 | 実装トラック | 実装ファイル | status | 検証 evidence |
|---|---|---|---|---|
| Theme kernel（bootstrap） | 起動・ヘルス取得 | `themes/agent-neo-theme/inc/bootstrap.php`, `themes/agent-neo-theme/functions.php` | VERIFIED | `theme_health` 呼び出しと `trace_step` 実装 |
| Theme kernel（manifest/boundary/prefix） | `theme-manifest` / `section-registry` / `schema-reference` | `themes/agent-neo-theme/config/theme-manifest.json`, `themes/agent-neo-theme/config/section-registry.json`, `themes/agent-neo-theme/config/schema-reference.json`, `themes/agent-neo-theme/inc/class-config-loader.php`, `themes/agent-neo-theme/inc/setup/class-boundary-guard.php` | VERIFIED | `config_valid` / boundary 検査ロジックが実体 |
| Core plugin（lifecycle/CPT/schema） | bootstrap/lifecycle/CPT/schema loader | `plugins/agent-neo-core/inc/bootstrap.php`, `plugins/agent-neo-core/inc/class-agent-neo-core.php`, `plugins/agent-neo-core/inc/lifecycle/class-lifecycle.php`, `plugins/agent-neo-core/inc/schema/class-schema-loader.php`, `plugins/agent-neo-core/inc/cpt/class-agent-action-cpt.php` | IMPL（scaffold） | `agent_neo_core_health()`、`schema-loader` ロード処理 |
| Core plugin（REST scaffolding） | REST 基盤（base controller / auth） | `plugins/agent-neo-core/inc/rest/class-rest-controller-base.php`, `plugins/agent-neo-core/inc/rest/class-auth.php`, `plugins/agent-neo-core/schema/openapi.yaml`, `plugins/agent-neo-core/schema/status-response.schema.json` | IMPL（scaffold） | `register_rest_route` は `/status` のみ登録 |
| CI / SBOM / gate | TC-016〜 / SBOM | - | NONE | 本表での対象 artifacts / script 未存在 | 

---

## Link & consistency checks

- 参照リンクはすべて本リポジトリ内の相対パスで統一。
- `docs/design/L3-WBS.md` と `docs/design/api-catalog.md` の行数・件数との整合:
  - WBS: T-001〜T-029 を漏れなく反映
  - endpoint: 57 件（api-catalog `endpoint 総数サマリ` を採用。実装実態は `18/57`）。ただし `/aseo/v1/agent-neo/catalog-update` は automation SEO 側受け口で agent-neo 実装範囲外として注記
  - F-ID: 25 件（F-001〜F-025）を抽出
- TODO 残存:
- `Table 1`: **全29タスク VERIFIED**（NONE/IMPL なし）。T-001〜T-029 完了
- `Table 2`: Y が 25 / N が 32（/features は 2 catalog entry で +2）。`/health`, `/contracts` 等未実装が残存
- `Table 3`: F-011 は PARTIAL、F-021/F-022 は partial、F-003〜F-024 ほぼ未完了。F-025 は完了
