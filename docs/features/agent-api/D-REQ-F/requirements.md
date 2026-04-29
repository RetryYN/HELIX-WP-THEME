# agent-api — D-REQ-F（機能要件）

## 概要

`agent-api` は AGENT NEO の REST API 操作面を定義する feature である。名前空間 `agent-neo/v1` のもと、AIエージェント・人間管理者・外部システムが安全にサイト構造、ブロック、セクション、CTA、SEOメタ、計測設定を取得・更新できる契約層を提供する。

全エンドポイントは OpenAPI 仕様を正本とし、`dryRun` と `apply` を分離した2フェーズ操作を必須とする。書き込み系は常に schema validation → dryRun diff → apply の順序を強制し、AI による意図しない破壊操作を防ぐ。Companion Plugin が全 REST ルートを所有し、Theme Core は REST ルートを持たない。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `AF-` | agent-api 機能要件 |
| エンドポイント接頭辞 | `/wp-json/agent-neo/v1/` |

## 詳細要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| AF-001 | 名前空間統一 | 全 REST ルートを `/wp-json/agent-neo/v1/` に統一し、`wp/v2` との混在を禁止する | P0 | REQ-F-002, REQ-NF-014 |
| AF-002 | 標準レスポンス envelope | 全レスポンスに `success`, `data`, `meta.request_id`, `meta.contract_version` を必須化する | P0 | REQ-F-002, REQ-NF-014 |
| AF-003 | dryRun/apply 2フェーズ | 書き込み系エンドポイントに `dry_run: true` フラグを実装し、差分・影響範囲・リスクスコアを返す。apply は dryRun 済み diff hash を必須とする | P0 | REQ-F-002, REQ-NF-014 |
| AF-004 | 認証方式 | Application Password（WP標準）と APIキー（Companion Plugin 発行）の2方式をサポートし、未認証書き込みは拒否する | P0 | REQ-NF-002, REQ-NF-014 |
| AF-005 | capability スコープ | `manage_options`（管理者）, `edit_posts`（編集者）, `agent_readonly`（読み取り専用）の3ロールでエンドポイント権限を制御する | P0 | REQ-F-003, REQ-NF-002 |
| AF-006 | rate limit | 公開エンドポイント: 60 req/min、認証済み: 300 req/min、AI apply: 30 req/min。超過時 429 を返す | P0 | REQ-NF-002, REQ-NF-014 |
| AF-007 | OpenAPI 仕様 | `openapi.yaml` を正本とし、全エンドポイント・スキーマ・エラーコード・例を定義する。CI で lint/diff を必須とする | P0 | REQ-NF-014 |
| AF-008 | ページ操作 API | `GET /pages`, `GET /pages/{id}`, `POST /pages/{id}/apply` でページ構造取得と更新を提供する。blueprint schema 準拠必須 | P0 | REQ-F-002, REQ-F-012 |
| AF-009 | セクション操作 API | `GET /sections`, `GET /sections/{section_id}`, `POST /sections/{section_id}/apply` でセクション単位の取得・更新を提供する | P0 | REQ-F-002, REQ-F-006 |
| AF-010 | CTA 操作 API | `GET /ctas`, `GET /ctas/{cta_id}`, `POST /ctas/{cta_id}/apply` でCTA設定を管理する。`offer_id` との紐付け必須 | P0 | REQ-F-002, REQ-F-006 |
| AF-011 | SEO メタ API | `GET /seo/{post_id}`, `POST /seo/{post_id}/apply` でcanonical/noindex/OGP/JSON-LD を管理する。SEO risk diff を必須化する | P0 | REQ-F-011, REQ-NF-015 |
| AF-012 | デザイントークン API | `GET /design-tokens`, `POST /design-tokens/apply` でカラー/フォント/間隔トークンを JSON で操作する | P1 | REQ-F-009 |
| AF-013 | ジョブ API | `POST /jobs`, `GET /jobs/{job_id}`, `DELETE /jobs/{job_id}` で非同期ジョブを管理する。idempotency_key 必須 | P0 | REQ-NF-014 |
| AF-014 | batch 操作 | `POST /batch` で最大20件の操作を1リクエストに束ねる。部分失敗時は成功分と失敗分を分離して返す | P1 | REQ-F-002, REQ-NF-014 |
| AF-015 | エラーコードカタログ | `error-catalog.json` に全エラーコード・HTTP status・復旧方法を定義する | P0 | REQ-NF-014 |
| AF-016 | 操作ログ API | `GET /logs` で操作履歴を返す。actor/request_id/target/diff/status フィールド必須 | P1 | REQ-NF-007 |
| AF-017 | health エンドポイント | `GET /health` で REST, cron, DB, 外部連携の状態を返す。認証不要（読み取り専用） | P0 | REQ-NF-013 |
| AF-018 | contract_version | 全書き込みリクエストに `contract_version` を必須化し、サポート外バージョンは `CONTRACT_VERSION_UNSUPPORTED` を返す | P0 | REQ-NF-014 |

## 補足・設計指針

**2フェーズ操作の詳細**: dryRun は副作用なしで実行可能な差分シミュレーションである。apply 時は dryRun の `diff_hash` を必須パラメータとし、diff 生成から一定時間（デフォルト10分）以内の apply のみ受け付ける。これにより「確認後に内容が変わっている状態での apply」を防ぐ。

**SSRF 対策**: 外部 URL を扱うエンドポイント（RSS取得、OGP取得等）は private IP レンジ・ループバック・メタデータ IP への接続を拒否し、リダイレクト上限・タイムアウト・レスポンスサイズ上限を設定する。

**破壊的変更ポリシー**: `v1` は廃止告知6ヶ月後まで維持し、破壊的変更は `/v2` へ分離する。フィールド追加・任意フィールド変更は非破壊変更として `v1` で実施可能。

**エンドポイント操作許可リスト**: 全 apply エンドポイントは操作対象パスのアローリストを保持し、リスト外パスへの書き込みは `FORBIDDEN` を返す。AI エージェントが scope 外を書き換えるリスクを排除する。

## エンドポイント一覧（MVP）

| メソッド | パス | 説明 |
|---|---|---|
| GET | `/status` | テーマ・プラグイン・ライセンスの状態 |
| GET | `/health` | REST/DB/cron/外部連携の健全性 |
| GET | `/features` | パッケージ別機能フラグ |
| GET | `/pages` | ページ一覧（ページネーション） |
| GET | `/pages/{id}` | ページ構造 + blueprint |
| POST | `/pages/{id}/apply` | ページ更新（dryRun/apply） |
| GET | `/sections` | セクション一覧 |
| GET | `/sections/{section_id}` | セクション詳細 |
| POST | `/sections/{section_id}/apply` | セクション更新 |
| GET | `/ctas` | CTA 一覧 |
| POST | `/ctas/{cta_id}/apply` | CTA 更新 |
| GET | `/seo/{post_id}` | SEO メタ取得 |
| POST | `/seo/{post_id}/apply` | SEO メタ更新（risk diff 付き） |
| GET | `/design-tokens` | デザイントークン取得 |
| POST | `/design-tokens/apply` | デザイントークン更新 |
| POST | `/jobs` | ジョブ作成 |
| GET | `/jobs/{job_id}` | ジョブ状態取得 |
| DELETE | `/jobs/{job_id}` | ジョブキャンセル |
| POST | `/batch` | バッチ操作（最大20件） |
| GET | `/logs` | 操作ログ取得 |
| POST | `/tracking/event` | 計測イベント送信（公開・署名必須） |

## 参照

- L1: REQ-F-002, REQ-F-003, REQ-F-006, REQ-F-007, REQ-F-011, REQ-NF-002, REQ-NF-014
- 解析レポート: 21-自動化CronAPI契約設計（R3 §AGENT NEO API契約, §エラーコード）
- 解析レポート: 22-AIエージェント運用性（§不都合な真実 #4, #12）
