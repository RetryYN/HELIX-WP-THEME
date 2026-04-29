# 自動化/Cron/API契約設計

## 結論

自動化、WP-Cron、REST API、AJAX、外部HTTP連携は参照テーマから逆引きできる。AGENT NEOでは、参照テーマの実装をコピーせず、`agent-neo-core-plugin` が契約ファーストで所有する設計にする。

重要な判断は次の通り。

| 判断 | 方針 |
|---|---|
| Theme Core | `theme.json`、templates、parts、patterns、style variations、表示CSSに限定 |
| Core Plugin | REST/MCP/WP CLI、ジョブ、Cron、CPT、SEO保存、計測、A/B、Blueprint、契約テストを所有 |
| WP-Cron | 低重要度の定期処理に使う。重要ジョブはWP CLI server cron、外部cron、手動fallbackも用意 |
| API | `agent-neo/v1` 名前空間、OpenAPI、JSON Schema、標準レスポンス、エラーコードカタログを必須化 |
| AI操作 | `dry-run` と `apply` を分離し、idempotency、diff、rollback、audit logを必須化 |

## R0: 証拠収集

| テーマ | ファイル | 観測内容 | AGENT NEO判断 |
|---|---|---|---|
| SWELL | `lib/rest_api.php` | settings、tracking、lazyload、cache reset、settings reset、term listのREST route | 機能分類は参考。`wp/v2`独自route、混在response、公開trackingの弱さは改善 |
| SWELL | `lib/rest_api/balloon_api.php` | balloon CRUD、copy、sort、recoverのREST route | UI部品管理APIの参考。Core Plugin側で契約化 |
| SWELL | `lib/update/Puc/v4p5/Scheduler.php` | `wp_schedule_event`、custom schedule、admin hook fallbackで更新チェック | WP-Cron + 管理画面fallbackの発想は採用 |
| SWELL | `classes/License.php` | license serverへ `wp_remote_post`、transient cache | license refresh設計の参考。ただし `sslverify=false` は不採用 |
| SWELL | `classes/Utility/Others.php` | nonce helper、外部URL取得 | nonce集中化は参考。URL fetchはSSRF guard必須 |
| SWELL | `lib/gutenberg/block/rss.php` | RSS取得で `wp_remote_get` | 外部取得jobのtimeout/cache/size制限設計に反映 |
| JIN:R | `include/custom-functions.php` | `wp_ajax_loadmore` / `wp_ajax_nopriv_loadmore`、post list loadmore | AJAXよりRESTへ寄せる。公開受付はnonce/schema/rate limit必須 |
| JIN:R | `include/custom-functions.php` | `wp_schedule_event(1451574000, '1hours', 'set_hours_event')` | 固定timestamp、未確認custom scheduleは不採用 |
| JIN:R | `include/custom-functions.php` | `/jinr/post_by_url`、`/jinr/external_url` public REST | 内部URL解決のUXは参考。外部URL取得はSSRF対策なしでは禁止 |
| JIN:R | `theme-update-checker.php` | update metadata取得 | update checkの責務は参考。契約とhealth checkへ昇格 |

## R1: 観測契約

### REST/API

| 分類 | 参照テーマで見えるもの | AGENT NEOの契約化 |
|---|---|---|
| Status | 更新状態、ライセンス、設定 | `GET /wp-json/agent-neo/v1/status` |
| Contracts | block/layout/component/settings契約 | `GET /wp-json/agent-neo/v1/contracts` |
| Actions | settings更新、cache reset、update action | `POST /actions/dry-run`、`POST /actions/apply` |
| Tracking | PV、button、ad計測 | `POST /tracking/event` |
| Blueprint | lazyload contents、term list、post list | `POST /pages/blueprint`、`GET /catalog/*` |
| Jobs | update check、migration、sync | `POST /jobs`、`GET /jobs/{job_id}` |
| Health | cron、REST、loopback、external API | `GET /health` |

### AJAX

JIN:Rは公開AJAXで記事追加読み込みを実装している。AGENT NEOでは、公開AJAXは原則使わず、REST route + schema + cacheable responseへ寄せる。WordPress admin UI内部でAJAXが必要な場合も、`check_ajax_referer`、capability、schema validation、標準エラーを必須にする。

### Cron/Automation

| Job | 用途 | 推奨runner | 重要度 |
|---|---|---|---|
| `seo_sync` | Automation SEO/seo-tool-connector同期 | WP CLI server cron / external cron | 高 |
| `tracking_flush` | ローカル計測の集約/送信 | WP-Cron + WP CLI fallback | 中 |
| `ab_rollup` | A/Bテスト集計 | WP-Cron / WP CLI | 中 |
| `cache_warm` | LP/主要記事のcache warm | WP-Cron / manual | 低 |
| `contract_validate` | JSON Schema/OpenAPI整合確認 | WP CLI / CI | 高 |
| `health_check` | REST/cron/loopback/external API診断 | WP-Cron / admin manual | 中 |
| `license_refresh` | ライセンス状態更新 | WP-Cron + admin fallback | 中 |
| `migration_preview` | 移行プレビュー作成 | WP CLI / async job | 高 |
| `blueprint_rebuild` | LP/HP blueprint再構築 | WP CLI / external queue | 高 |

## R2: As-Isからの設計判断

### 取り込む設計

| 取り込み | 理由 |
|---|---|
| RESTで設定/計測/管理操作を分離 | AI操作面に変換しやすい |
| WP-Cron + admin fallback | WP-Cronの不確実性を緩和できる |
| transient cache | 外部API・license・OGP取得の負荷を下げられる |
| nonce/capability helper | 権限境界を集中化できる |
| UI部品管理API | reusable partsやsection registryへ応用できる |

### 不採用または改良

| 反面教師 | 問題 | AGENT NEOの対策 |
|---|---|---|
| `wp/v2`名前空間に独自route | WP標準routeとの境界が曖昧 | `agent-neo/v1` に統一 |
| `json_encode`文字列、配列、`wp_die`混在 | clientが安定処理できない | 標準response envelope |
| public tracking write | 悪用・ノイズ・DoSリスク | site token、signature、rate limit、bot filter |
| `file_get_contents($url)` | SSRF、timeout、redirect、size制限なし | WP HTTP API + allowlist/deny private IP/timeout/size cap |
| 直接 `$_POST` を読むAJAX | nonce/schema/sanitize不足 | REST schemaかAJAX contractで検証 |
| WP-Cronのみの重要処理 | アクセスがないと遅延する | WP CLI server cron / external cron / manual fallback |
| 固定timestamp Cron | 環境依存で意図が読めない | activation時刻 + named schedule + reschedule policy |

## R3: AGENT NEO API契約

### 名前空間とバージョン

| 項目 | 方針 |
|---|---|
| REST namespace | `/wp-json/agent-neo/v1` |
| MCP tool namespace | `agent_neo.*` |
| WP CLI namespace | `wp agent-neo` |
| Contract version | `contract_version` を全write requestに含める |
| 破壊的変更 | `/v2` へ分離。`v1`はdeprecation期間を設ける |

### 標準レスポンス

```json
{
  "success": true,
  "data": {},
  "meta": {
    "request_id": "req_...",
    "contract_version": "1.0.0"
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Request does not match schema.",
    "details": []
  },
  "meta": {
    "request_id": "req_...",
    "contract_version": "1.0.0"
  }
}
```

### 必須契約ファイル

| ファイル | 役割 |
|---|---|
| `openapi.yaml` | REST APIの正本 |
| `agent-actions.schema.json` | AI操作のrequest/response正本 |
| `job-contract.schema.json` | job作成、状態、retry、cancel、resultの正本 |
| `event-contract.schema.json` | tracking/event、section engagement、A/B eventの正本 |
| `webhook-contract.schema.json` | Automation SEO、外部cron、license callbackの正本 |
| `error-catalog.json` | エラーコード、HTTP status、復旧方法の正本 |
| `contract-version-policy.md` | バージョニング、deprecation、互換性規則 |
| `mcp-tools.schema.json` | MCP tool定義、input/output、権限の正本 |
| `wp-cli-contract.json` | WP CLI command、引数、exit codeの正本 |
| `automation-schedule.schema.json` | job schedule、runner、retry、DLQの正本 |

### エラーコード

| Code | 用途 |
|---|---|
| `VALIDATION_ERROR` | schema不一致 |
| `UNAUTHORIZED` | 認証なし |
| `FORBIDDEN` | capability/package不足 |
| `RATE_LIMITED` | public route制限 |
| `CONFLICT` | version競合、lock競合 |
| `LOCKED` | 同一対象が処理中 |
| `JOB_RUNNING` | 既存jobと重複 |
| `EXTERNAL_SERVICE_UNAVAILABLE` | Automation SEO等の外部障害 |
| `CONTRACT_VERSION_UNSUPPORTED` | contract version非対応 |
| `DRY_RUN_REQUIRED` | apply前のdryRun不足 |
| `SSRF_BLOCKED` | 外部URL取得が安全条件を満たさない |

## R4: 自動化基盤の設計

### Runner

| Runner | 用途 | 制約 |
|---|---|---|
| WP-Cron | 低〜中重要度の定期処理 | ページロード依存で時刻保証なし |
| WP CLI server cron | 高重要度・長時間job | サーバーcron設定が必要 |
| External cron/webhook | managed環境、SaaS連携 | 認証、署名、replay防止が必要 |
| Manual admin trigger | 復旧、検証、サポート | 操作ログとcapabilityが必要 |

### Job状態

```text
queued -> running -> succeeded
queued -> running -> failed -> retrying -> running
failed -> dead_letter
queued/running -> cancelled
```

### Job必須属性

| 属性 | 理由 |
|---|---|
| `job_id` | 追跡可能性 |
| `job_type` | handler routing |
| `idempotency_key` | 二重実行防止 |
| `target_type` / `target_id` | 対象範囲明確化 |
| `actor` | human / ai / cli / cronの識別 |
| `dry_run` | 破壊操作の事前検証 |
| `status` | UI/API/CLI共通表示 |
| `retry_policy` | 再試行上限とbackoff |
| `lock_key` | 同一対象の競合防止 |
| `result` | 成功結果、diff、warnings |
| `error` | error catalogに紐付け |

## Security/Availability Guard

| Guard | 必須条件 |
|---|---|
| Write API | nonce/application password、capability、package scope、schema validation |
| Public tracking | site token、signature、rate limit、PII最小化、bot filter |
| External URL fetch | private IP deny、redirect上限、timeout、content length上限、content-type allowlist |
| Cron/job | idempotency key、lock、retry/backoff、dead letter、manual replay |
| AI apply | dryRun済みdiff hash、rollback point、audit log |
| Webhook | HMAC署名、timestamp、replay防止 |
| Logs | request_id、job_id、actor、target、error code。secret/PIIはmask |

## Package反映

| パッケージ | 追加すべき価値 |
|---|---|
| Core | OpenAPI、JSON Schema、Job/Event/Webhook contract、MCP/WP CLI contract、contract tests |
| 個人版 | affiliate CTA、ranking、comparisonのtracking eventをevent contractへ統一 |
| 法人版 | LP改善job、A/B rollup、lead tracking、webhook/CRM adapterを契約化 |
| 移行プラグイン | migration preview/rebuildをjob contractで管理 |
| Automation SEO | `tracking/context`、`pages/sync`、改善提案webhookを契約化 |

## 検証計画

| 検証 | 合格条件 |
|---|---|
| OpenAPI lint | 全endpoint、schema、example、errorが定義済み |
| OpenAPI diff | 破壊的変更0件。破壊的変更はv2へ分離 |
| JSON Schema validation | request/response/job/event/webhookがschemaに一致 |
| REST contract test | 正常系、主要エラー、権限、rate limitが通る |
| MCP tool contract test | tool input/outputがschemaに一致 |
| WP CLI contract test | command引数、exit code、JSON出力が契約に一致 |
| Cron reliability test | WP-Cron遅延時もWP CLI/manual fallbackで復旧可能 |
| Job idempotency test | 同一idempotency keyで二重実行されない |
| Retry/DLQ test | 外部API失敗時にretry後dead letterへ入る |
| SSRF test | private IP、localhost、metadata IP、redirect悪用が拒否される |

## 公式ドキュメント確認

| 参照 | 設計への反映 |
|---|---|
| WordPress Cron | WP-Cronはページロードで実行確認され、常時実行ではないため、重要jobはWP CLI/external/manual fallbackを持つ |
| `register_rest_route()` | REST routeは `rest_api_init` 上で登録し、permission callbackを明示する |
| REST API Schema | request/response schemaを定義し、client/AIが機械的に検証できる形にする |
| WordPress HTTP API | 外部HTTPはWP HTTP APIを使い、timeout、redirect、SSL、response validationを制御する |

## Gate判定

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | SWELL/JIN:RのREST/AJAX/Cron/HTTP evidenceを収集 |
| RG1 | passed | 観測API、AJAX、Cron、外部HTTP契約を分類 |
| RG2 | passed_with_caution | 参照実装の有効パターンと危険パターンを分離 |
| R4 | passed | AGENT NEOのL1/L2/L3契約化対象へrouting |
| G3準備 | passed_with_draft | OpenAPI/JSON Schema/Job contractをL3凍結対象として定義 |
