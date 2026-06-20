# Phase1 Implementation Matrix (L1-L4 SSOT)

## Summary

- **Sprint/Task progress**: VERIFIED **16** / IMPL **2** / SCAFFOLD **0** / NONE **11** *(total 29)*
- **Endpoint coverage**: `Y` **12** / `N` **45** *(total 57)*
- **Phase1 launch F-ID**: **完了 2** / 残 **23** *(total 25)*
- **残タスク（next）**:
  - `.3〜.5`: **T-017〜T-026**
- **運用ルール（更新時）**:
  - 各 sprint 完了時に本表を更新
  - `bin/check-impl-coverage.sh` で `coverage = 12/57` を確認済み（`/aseo/v1/agent-neo/catalog-update` は受け口/外部連携枠）
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
| T-017 | F-011 | SEO 入力検証・共存 | .3 | theme/core-plugin | NONE | `plugins/agent-neo-core/inc/rest/`（scaffold） | SEO 関連 endpoint 全未実装（`status` 限定） | 04154e1 |
| T-018 | F-011 | 計測/SEO 監査ログ保存（agent_action CPT） | .3 | core-plugin | NONE | `plugins/agent-neo-core/inc/cpt/class-agent-action-cpt.php`（CPT 定義のみ） | CPT 定義あり。ログ運用 API route は未実装 | 04154e1 |
| T-019 | F-010/F-016 | 個人版 package 境界 | .3 | core-plugin | NONE | `plugins/agent-neo-core/inc`（scaffold） | package boundary endpoint 未実装（`status` の license_mode は取得のみ） | 04154e1 |
| T-020 | F-004/F-030 | 個人版 CV module 最小限表示 | .3 | core-plugin | NONE | `plugins/agent-neo-core/inc`（scaffold） | 付随 API 未実装。core scaffold のみ確認 | 04154e1 |
| T-021 | F-005/F-012 | LP/HP blueprint API とページ apply 接続 | .3 | core-plugin | NONE | `plugins/agent-neo-core/inc/rest/`（scaffold） | `/pages/{id}/apply`, `/pages/blueprint`, `/lp/sections` 未実装 | 04154e1 |
| T-022 | F-013/F-031 | 法人版リード寄与権限制御 | .4 | core-plugin | NONE | `plugins/agent-neo-core/inc/rest/`（scaffold） | 法人向け権限制御 API 未実装 | 04154e1 |
| T-023 | F-011/F-023/F-024 | Performance + a11y + i18n/RTL gate パイプライン | .4 | theme | NONE | `themes/agent-neo-theme`, `plugins/agent-neo-core`（scaffold） | 性能/a11y/RTL の CI gate 実装未確認（`TC-016` 以降未着手） | 04154e1 |
| T-024 | F-006/F-007/F-026/F-027 | 連携契約（tracking-context / webhook / catalog cache） | .5 | core-plugin | NONE | `plugins/agent-neo-core/inc`（scaffold） | 連携契約/契約テストは未実装。catalog-update scaffold も未実装 | 04154e1 |
| T-025 | F-020/F-021/F-023 | SBOM 生成・検証 | .5 | core-plugin | NONE | `plugins/agent-neo-core`（scaffold） | `sbom.cdx.json` 生成/Release Gate 未検出 | 04154e1 |
| T-026 | F-003 | 操作面（REST/MCP/WP CLI/React UI）統合検証 | .1a〜.2 | core-plugin | NONE | `plugins/agent-neo-core`, `themes/agent-neo-theme` | `dry-run/apply` 統合導線は未実装 | - |
| T-027 | REQ-F-038 / ADR-026 | embed static mode 実装 | .4 | embed-plugin | IMPL | `plugins/agent-neo-embed/agent-neo-embed.php`, `plugins/agent-neo-embed/src/embed/block.json`, `plugins/agent-neo-embed/src/embed/render.php`, `plugins/agent-neo-embed/assets/embed-reset.css`, `plugins/agent-neo-embed/src/embed/view.js` | shadow DOM + リセット CSS 由来の静的 embed 実装あり（`mode=static`） | - |
| T-028 | REQ-F-038 / ADR-026 | embed interactive mode 実装 | .4 | embed-plugin | IMPL | `plugins/agent-neo-embed/agent-neo-embed.php`, `plugins/agent-neo-embed/src/embed/edit.js`, `plugins/agent-neo-embed/src/embed/view.js`, `plugins/agent-neo-embed/src/embed/block.json` | iframe/sandbox-origin 由来ロジックは実装。実運用（実ホスト接続）側の検証は未完了 | - |
| T-029 | CARRY-EMBED-006 / ADR-026 | embed CI gate 自動化 | .4 | embed-plugin | NONE | `poc/embed-isolation/verify.py`, `plugins/agent-neo-embed`（未連携） | `poc/embed-isolation/verify.py` は存在するが CI 統合ワークフロー未実装 | - |

---

## Table 2: API endpoint coverage（agent-neo/v1 + aseo/v1）

| method | path | 対応 T-ID | sprint | status | register_rest_route 実在(Y/N) | 実装ファイル |
|---|---|---|---|---|---|---|
| GET | /status | T-005 | .1a | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-status-controller.php` |
| GET | /health | - | - | NONE | N | - |
| GET | /features | - | - | NONE | N | - |
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
| POST | /pages/blueprint | - | - | NONE | N | - |
| POST | /lp/sections | - | - | NONE | N | - |
| POST | /pages/{id}/preview | - | - | NONE | N | - |
| POST | /pages/{id}/apply | - | - | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-pages-controller.php` |
| POST | /pages/{id}/rollback | - | - | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-pages-controller.php` |
| POST | /rollback/{rollback_id} | - | - | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-pages-controller.php` |
| GET | /ctas | - | - | NONE | N | - |
| GET | /ctas/{cta_id} | - | - | NONE | N | - |
| POST | /ctas/{cta_id}/apply | - | - | NONE | N | - |
| POST | /elements/swap | - | - | NONE | N | - |
| POST | /actions/dry-run | T-006 | .1b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-actions-controller.php` |
| POST | /actions/apply | T-007 | .1b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-actions-controller.php` |
| PATCH | /batch | - | - | NONE | N | - |
| POST | /design-tokens/apply | - | - | NONE | N | - |
| GET | /design-tokens | - | - | NONE | N | - |
| GET | /seo/{post_id} | - | - | NONE | N | - |
| POST | /seo/{post_id}/apply | - | - | NONE | N | - |
| POST | /seo/meta | - | - | NONE | N | - |
| POST | /media/upload | - | - | NONE | N | - |
| POST | /migration/jobs | - | - | NONE | N | - |
| POST | /tracking/event | T-012 | .2b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-tracking-controller.php` |
| POST | /tracking/context | - | - | NONE | N | - |
| GET | /tracking/llmo-summary | - | - | NONE | N | - |
| POST | /license/validate | T-013 | .2b | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-license-controller.php` |
| POST | /settings/export | T-016 | .2b/.2c | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-settings-controller.php` |
| POST | /settings/import | T-016 | .2b/.2c | VERIFIED | Y | `plugins/agent-neo-core/inc/rest/class-settings-controller.php` |
| POST | /jobs | - | - | NONE | N | - |
| GET | /jobs/{job_id} | - | - | NONE | N | - |
| POST | /jobs/{job_id}/cancel | - | - | NONE | N | - |
| GET | /logs | - | - | NONE | N | - |
| GET | /public/pages/{id}/snapshot | - | - | NONE | N | - |
| GET | /public/crawl-map | - | - | NONE | N | - |
| GET | /public/llmo/answers | - | - | NONE | N | - |
| GET | /automation-seo/fit | - | - | NONE | N | - |
| POST | /automation-seo/fit | - | - | NONE | N | - |
| GET | /automation-seo/bridge-profile | - | - | NONE | N | - |
| GET | /risks/hazards | - | - | NONE | N | - |
| GET | /crawler-policy | - | - | NONE | N | - |
| POST | /crawler-policy | - | - | NONE | N | - |
| POST | /affiliate/block | - | - | NONE | N | - |
| POST | /aseo/v1/agent-neo/catalog-update | T-014 / T-015 | .2 | NONE | N | external receiver / out of agent-neo scope（coverage 除外対象） |

---

## Table 3: Phase1 launch set F-ID coverage（F-001〜F-025）

| F-ID | 対応 T-ID | status | 現状 |
|---|---|---|---|
| F-001 | T-001, T-002, T-003, T-004 | VERIFIED | launch 必達として完了 | 
| F-002 | T-006, T-007, T-010, T-011 | VERIFIED | dry-run/apply/pages が実機検証済み。`POST /pages/{id}/apply` / `POST /pages/{id}/rollback` / `POST /rollback/{rollback_id}` は `18d982f`（fix 含む）で検証済み |
| F-003 | T-026 | NONE | 操作面統合（dry-run/apply を含む）未実装 | 
| F-004 | T-027 | IMPL | static embed 機能は存在。Interactive 統合は T-028 | 
| F-005 | T-020 | NONE | API 契約・blueprint系未実装 | 
| F-006 | T-012, T-024 | PARTIAL | `/tracking/event` は実機検証済み（T-012）。`tracking-context/webhook/cache` は継続実装待ち（T-024） | 
| F-007 | T-024 | NONE | webhook 連携未実装 | 
| F-008 | T-024 | NONE | tracking/webhook 連携未実装 | 
| F-009 | T-021 | NONE | 設定 import/export endpoint 未実装 | 
| F-010 | T-013, T-019 | PARTIAL | `/license/validate` は実機検証済み（T-013）。package境界/権限制御の追加検証は T-019 が未完了 | 
| F-011 | T-017, T-018, T-023 | NONE | SEO監査・計測監査の API/API連携未実装 | 
| F-012 | T-021 | NONE | blueprint 生成/更新未実装 | 
| F-013 | T-022 | NONE | 法人リード寄与 API 未実装 | 
| F-014 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-015 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-016 | T-019 | NONE | テンプレ固定構成境界の強制は未実装 | 
| F-017 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-018 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-019 | - | NONE | 該当 T-ID が未割当（Phase1表では未着手） | 
| F-020 | T-025 | NONE | SNS API 設定・機能未実装 | 
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
  - endpoint: 57 件（api-catalog `endpoint 総数サマリ` を採用。実装実態は `12/57`）。ただし `/aseo/v1/agent-neo/catalog-update` は automation SEO 側受け口で agent-neo 実装範囲外として注記
  - F-ID: 25 件（F-001〜F-025）を抽出
- TODO 残存:
  - `Table 1`: T-018〜T-029 は NONE（T-006〜T-013、T-016 は VERIFIED）
  - `Table 2`: Y が 12 / N が 45。`/health`, `/features`, `/contracts` 等未実装が残存
  - `Table 3`: F-021/F-022 は partial、F-003〜F-024 ほぼ未完了。F-025 は完了
