# agent-neo/v1 全 endpoint カタログ

> 根拠: L1-requirements.md（43 REQ-F）/ L0-planning.md / L2-design.md §5 API概要設計 / docs/features/agent-api/D-REQ-F/requirements.md
> 作成日: 2026-04-30
> ステータス: **L3 API 契約凍結（G3）/ §17 完全一致**（2026-06-14）

すべてのルートは `/wp-json/agent-neo/v1/` プレフィックスに統一。`wp/v2` との混在禁止（REQ-NF-014 / AF-001）。

---

## agent-neo/v1 全 endpoint カタログ

### Health / Setup

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /status | テーマ・Companion Plugin・ライセンス・連携状態 | REQ-F-001, REQ-F-010 | A-001。license_mode / package / 連携健全性を返す |
| GET | /health | REST / DB / Cron / loopback / 外部 API 診断 | REQ-NF-013 | A-017 / AF-017。認証不要（読み取り専用）。ライセンス status も含む（PF-NF） |
| GET | /features | パッケージ別機能フラグ一覧 | REQ-F-010 | PF-003。personal / corporate 別フラグを返す |
| GET | /contracts | block / layout / component 契約取得（OpenAPI 正本） | REQ-F-003 | A-004。AI がスキーマを確認する読み取り専用エンドポイント |

---

### コンテンツ操作（投稿 / ブロック / セクション）

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /posts | 投稿一覧（bulk read）| REQ-F-002, REQ-F-026 | `?since=<ts>` 差分取得・`?fields=id,title` sparse fieldset・ETag 対応 |
| GET | /posts/{id} | 投稿詳細取得 | REQ-F-002 | 全ブロック + block_id + section_id 付き構造を返す |
| GET | /posts/{id}/diff | JSON Patch 差分エクスポート | REQ-F-026 | `?from=<ts>` で差分を RFC 6902 JSON Patch 形式で返す。v2 引き出し向け |
| GET | /posts/{id}/markdown | vector-friendly markdown export | REQ-F-026 | Gutenberg JSON → plain markdown。v2 embedding 生成用 |
| PATCH | /posts/{id}/blocks/{block_id} | ブロック単位部分更新 | REQ-F-021 | idempotency-key ヘッダ必須。block-level version 履歴 N 版保持 |
| POST | /posts/{id}/sections/{section_id}/edit | H2 単位セクション編集 | REQ-F-022 | dryRun + diff preview + apply + rollback。rewrite/expand/summarize/translate/restructure の文章判定・生成は Automation SEO 側 LLM が担い、本エンドポイントは確定済みコンテンツの受信・適用のみ（REQ-NF-025） |
| GET | /sections | セクション一覧 | REQ-F-002, REQ-F-006 | AF-009 |
| GET | /sections/{section_id} | セクション詳細 | REQ-F-002, REQ-F-006 | AF-009 |
| POST | /sections/{section_id}/apply | セクション更新（dryRun/apply）| REQ-F-002, REQ-F-006 | AF-009。diff_hash 必須 |

---

### ページ / Blueprint 操作

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /pages | ページ一覧 | REQ-F-002, REQ-F-012 | AF-008 |
| GET | /pages/{id} | ページ構造 + blueprint | REQ-F-002, REQ-F-012 | AF-008。blueprint schema 準拠必須 |
| POST | /pages/{id}/apply | ページ更新（dryRun/apply / preview反映）| REQ-F-002, REQ-F-012 | AF-008。diff_hash + idempotency_key 必須。`from_preview_token` 指定時は preview → production 反映として扱う（CARRY-G2-016） |
| POST | /pages/blueprint | LP / HP blueprint 生成・更新 | REQ-F-012 | A-013。LP/HP の内容・コピー・CV 設計の AI 生成は Automation SEO 側。本 endpoint は blueprint/標準セクション定義に基づく静的構造の生成・更新のみ。section_id / cta_id / offer_id / service_id 必須 |
| POST | /lp/sections | LP セクション生成・更新 | REQ-F-005 | A-006。LP/HP の内容・コピー・CV 設計は Automation SEO 側で行う。本 endpoint は blueprint/標準セクション定義に基づく静的構造の生成・更新のみ。12 標準セクション（Hero → CTA）対応 |

---

### サンドボックス（Tier 1）

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /pages/{id}/preview | preview content 作成（Tier 1） | REQ-F-038 | `_agent_neo_preview_content` meta + token URL 発行。HP / LP / 固定ページが主対象 |
| POST | /pages/{id}/rollback | 旧 version への復元 | REQ-F-038 | rollback_id 指定で完全復元 |
| POST | /rollback/{rollback_id} | apply 前状態への汎用ロールバック | REQ-F-038, REQ-F-008 | agent-api ANF-013。apply 後 30 日保持 |

---

### 要素差し替え（Element Swap）

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /ctas | CTA 一覧 | REQ-F-002, REQ-F-023 | AF-010。offer_id との紐付け確認 |
| GET | /ctas/{cta_id} | CTA 詳細 | REQ-F-002, REQ-F-023 | AF-010 |
| POST | /ctas/{cta_id}/apply | CTA 更新（swap / 差し替え）| REQ-F-023 | AF-010。cta_id 単位 swap。AI の自律差し替えで使用 |
| POST | /elements/swap | 要素差し替え汎用 API | REQ-F-023 | link_id / banner_id / media_id / blueprint_id / reusable_part_id の swap に対応（暗黙的スコープ） |

---

### JSON 操作（dryRun / apply 共通）

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /actions/dry-run | JSON 操作の検証（副作用なし）| REQ-F-002 | A-002 / AF-003。schema validation + diff + risk score を返す。ここで返す risk score は Automation SEO 側が算出した受信値、および schema/diff の構造検証結果であり、AGENT NEO 側で risk を算出しない |
| POST | /actions/apply | JSON 操作の適用 | REQ-F-002 | A-003 / AF-003。diff_hash + idempotency_key 必須。apply 前に dryRun 必須 |
| PATCH | /batch | バッチ操作（最大 20 件）| REQ-F-026 | AF-014。v2 が一括変更に使用。部分失敗時は成功/失敗を分離返却 |
| POST | /design-tokens/apply | デザイントークン更新 | REQ-F-009 | AF-012。color / font / spacing を JSON で操作 |
| GET | /design-tokens | デザイントークン取得 | REQ-F-009 | AF-012 |

---

### SEO

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /seo/{post_id} | SEO メタ取得（canonical / noindex / OGP / JSON-LD）| REQ-F-011 | AF-011 |
| POST | /seo/{post_id}/apply | SEO メタ更新 | REQ-F-011 | AF-011。SEO risk diff 必須。重複 canonical / noindex 変更を警告 |
| POST | /seo/meta | SEO メタ・Entity Graph 更新（Deprecated）| REQ-F-011, AF-011 | **非推奨**。`/seo/{post_id}/apply` を canonical とし、移行期間中の互換維持目的のみ |

---

### メディア

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /media/upload | 画像アップロード（WebP 変換パイプライン）| REQ-F-017 | L0 §1.2 記載。5MB 超は Action Scheduler でバックグラウンド処理。既存 WebP はスキップ。GIF はアニメ保持・警告 |

---

### 移行

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /migration/jobs | 移行ジョブ作成（Plan A / Plan B）| REQ-F-008 | A-009。extract / transform / preview / apply の4ステップ非同期実行 |

---

### 計測

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /tracking/event | 計測イベント受付 | REQ-F-006 | A-007。公開受付（署名 / nonce 相当 / rate limit）。section_id / cta_id / variant_id 必須 |
| POST | /tracking/context | Automation SEO 互換 context 送信 | REQ-F-007 | A-008。site_id / article_id / section_id を同期 |

---

### ライセンス / パッケージ

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /license/validate | ライセンス検証 | REQ-F-010 | A-011。個人版 / 法人版 / アドオンの境界制御。mode: readonly 時は /health で警告 |
| GET | /features | パッケージ別機能フラグ | REQ-F-010 | PF-003。※Health/Setup 節にも掲載（共用）|

---

### 設定 I/O

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /settings/export | 設定 export（design-tokens / blueprints / package-matrix）| REQ-F-009 | A-010。JSON 統一。bit-identical で import 可能 |
| POST | /settings/import | 設定 import | REQ-F-009 | L1 では明示的に PATH 未確定。export とペアで存在が暗黙的（憶測あり） |

---

### ジョブ管理

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /jobs | 非同期ジョブ作成 | REQ-NF-014 | A-014 / AF-013。idempotency_key 必須 |
| GET | /jobs/{job_id} | ジョブ状態 / 結果取得 | REQ-NF-014 | A-015 / AF-013 |
| POST | /jobs/{job_id}/cancel | ジョブ取消 | REQ-NF-014 | A-016。`DELETE /jobs/{job_id}` は重複経路として廃止し、POST 一本化（api-catalog 冒頭 TL 指摘（2026-04-30 / L3 確定）） |

---

### ログ

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /logs | 操作ログ取得 | REQ-NF-007 | AF-016。actor / request_id / target / diff / status フィールド必須 |

---

### 公開 API（認証不要）

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /public/pages/{id}/snapshot | 公開ページの AI Snapshot | REQ-NF-015 | A-018 / SF-003。section 一覧・CTA label・JSON-LD・canonical・robots を返す。認証不要 |
| GET | /public/crawl-map | 全公開ページの crawl map | REQ-NF-015 | A-019 / SF-012。canonical・robots・更新日・section 数・content_type を返す |
| GET | /public/llmo/answers | answer unit / citation anchor / evidence graph | REQ-NF-017 | A-021。LLMO 向け公開エンドポイント。認証不要 |

---

### Automation SEO 連携

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /automation-seo/fit | theme capability / section / CTA / SEO mapping 診断 | REQ-NF-019 | A-023。GET は診断のみ |
| POST | /automation-seo/fit | safe apply readiness 同期 | REQ-NF-019 | A-023。POST は同期 / apply |
| GET | /automation-seo/bridge-profile | Theme Bridge Plugin 互換プロファイル | REQ-NF-020 | A-024。既存テーマ横断情報を source/confidence 付きで返す。`safe_apply_state` は `preview-only` / `write-ready` の2値。既存テーマは `preview-only` 固定、AGENT NEO は `write-ready`（ADR-019 / preview-only 限定）。既存テーマ（非 AGENT NEO）は `safe_apply_state=preview-only` 固定。`write-ready` は AGENT NEO Core Plugin 経由のみで、深い自動書換は禁止。 |

### External outbound contracts（AGENT NEO → automation SEO）

AGENT NEO Core Plugin（Plugin B）が発火する、外部向け catalog-update 契約。  
catalog-update 発火責務は agent-neo-core-plugin（Plugin B）が所有し、ADR-008 配布境界 / §2.4 の範囲制約に準拠する。  
**正本 = automation SEO `D-PLUGIN-CONTRACT §17`。** 本書は ADR-018 に基づくミラーで、`§17` と差分が出た場合は `§17` が優先。  

#### catalog-update Request schema（§17.2）

| 契約項目 | 仕様 |
|---|---|
| エンドポイント | `POST /aseo/v1/agent-neo/catalog-update` |
| `site_hash` | テナント識別子 |
| `agent_neo_version` | Plugin B のバージョン |
| `event_kind` | `block_registered` / `block_unregistered` / `template_updated` / `theme_token_updated` |
| `event_id` | idempotency key。24時間 TTL のイベント重複抑止キー（CARRY-G2-005 / §17.4） |
| `occurred_at` | ISO8601 |
| `payload` | `block_name` / `template_part_slug` / `diff` を含む |
| 備考 | `event_id` は別個の `idempotency_key` なし。`event_id` が冪等キー（CARRY-G2-005 / §17.2） |

#### catalog-update Response schema（§17.2）

| 契約項目 | 仕様 |
|---|---|
| `received` | `true` / `false` |
| `event_id` | 受信イベントの `event_id` echo |
| `deduplicated` | `true` / `false` |
| `next_action` | `scan-catalog` / `none`（**all responses, dedup 含む**） |
| 備考 | `event_kind` / `received_at` / `idempotency_key` 等の付加フィールドは付与しない（CARRY-G2-004） |

#### catalog-update 署名・エラー / リトライ（§17.11 / §17.12）

| 項目 | 仕様 |
|---|---|
| リトライ対象 | 5xx および 429、network timeout |
| 初回再試行待機 | 1 秒（指数 2^n、±10% jitter） |
| 最大再試行回数 | 5 回 |
| 4xx 挙動 | リトライしない。ただし 429 は retry 対象 |
| 400 | `VALIDATION_ERROR`（`event_kind` 欠落含む） |
| 401 | `PLUGIN_AUTH_FAILED` |
| 409 | `AGENT_NEO_NOT_INSTALLED`（`metadata_json.theme_slug != 'agent-neo'`） |
| 429 | `RATE_LIMITED` |
| 備考 | 5xx は §17.11 の再試行方針、4xx は即失敗。5 回失敗時は DLQ 送出し、producer status は `409 RETRY_EXHAUSTED` とする。 |

#### catalog-update 契約テスト

| TestID | 条件 | 期待値 |
|---|---|---|
| CAT-001 | `event_kind=block_registered` で payload 送信 | 200 OK、`received=true` / `deduplicated=false` / `next_action=none` |
| CAT-002 | `event_kind=block_unregistered` で payload 送信 | 200 OK、`received=true` / `deduplicated=false` / `next_action=none` |
| CAT-003 | `event_kind=template_updated` で payload 送信 | 200 OK、`received=true` / `deduplicated=false` / `next_action=none` |
| CAT-004 | `event_kind=theme_token_updated` で payload 送信 | 200 OK、`received=true` / `deduplicated=false` / `next_action=none` |
| CAT-005 | 同一 `event_id` 再送 | 200 OK、`received=true` / `deduplicated=true` / `event_id` 保持 / `next_action=none` |
| CAT-006 | `event_kind` 欠落 | 400 |

補足:
- `next_action` は dedup 含め全応答で必須（`scan-catalog` または `none`）
- `scan-catalog` 時のみ Plugin B は catalog 再取得を再実行する（CARRY-G2-023）
---

### リスク管理

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| GET | /risks/hazards | SEO / WP 運用 / AI 運用の risk-ledger と検出結果 | REQ-NF-018 | A-022。canonical / cache / cron / plugin conflict 等のハザード一覧 |
| POST | /crawler-policy | crawler access matrix 更新 | REQ-NF-015 | A-020。AI 学習 / クローラ別許可方針を設定 |

---

## Affiliate / 収益化ブロック

| Method | Path | 用途 | REQ-F | 備考 |
|--------|------|------|-------|------|
| POST | /affiliate/block | 収益化ブロック生成 | REQ-F-004 | A-005。Amazon Product API 等の提供データから静的にブロック構造を組み立てる処理。AI による内容判断・文章生成は行わない(それは Automation SEO 側)。Review / Ranking / Comparison / Affiliate CTA / 商品カード。個人版 |

---

## endpoint 総数サマリ

| カテゴリ | 件数 |
|---------|-----|
| Health / Setup | 4 |
| コンテンツ操作 | 9 |
| ページ / Blueprint | 5 |
| サンドボックス Tier 1 | 3 |
| 要素差し替え | 4 |
| JSON 操作共通 | 5 |
| SEO | 3 |
| メディア | 1 |
| 移行 | 1 |
| 計測 | 2 |
| ライセンス / パッケージ | 2 |
| 設定 I/O | 2 |
| ジョブ管理 | 3 |
| ログ | 1 |
| 公開 API | 3 |
| Automation SEO 連携 | 3 |
| リスク管理 | 2 |
| Affiliate / 収益化 | 1 |
| External outbound（AGENT NEO→Automation SEO） | 1 |
| **合計** | **55** |

---

## 整合性検証

### 同パス・複数 method の分離確認

- `/automation-seo/fit`: GET（診断）/ POST（同期）を意図的に分割。A-023 で `GET/POST` と表記されており、L3 で endpoint を分けるか合わせるかを決定する必要あり。
- `/pages/{id}/apply` は POST のみに統一し、PATCH /pages/{id}/apply は preview 昇格経路の重複として整理（CARRY-G2-016）。
- `/jobs/{job_id}/cancel`（POST）/`/jobs/{job_id}`（DELETE）は POST 一本化済み。`DELETE` は本資料で廃止（api-catalog 冒頭 TL 指摘（2026-04-30 / L3 確定））。
- `/seo/{post_id}/apply` を canonical とし、`/seo/meta` は deprecated（api-catalog 冒頭 TL 指摘（2026-04-30 / L3 確定））。

### RESTful 慣習違反の可能性

| endpoint | 懸念 | 根拠 |
|---------|------|------|
| `POST /actions/dry-run` | dryRun は副作用なしだが POST を使用 | L1 / L2 明示設計。GET より POST の方が JSON body を送れるため意図的判断と解釈 |
| `POST /settings/export` | export（read）に POST を使用 | body にフィルタ条件を指定するため POST が必要と推定。L3 で確認 |
| `POST /tracking/event` | 計測受付は公開 POST | 署名 / nonce / rate limit で保護する設計。RESTful 的には適切 |
| `POST /license/validate` | validate（検証）に POST | 外部ライセンスサーバーへの通信を含むため副作用あり。適切 |

### 命名一貫性

| 問題 | 箇所 |
|------|------|
| 複数形 vs 単数形混在 | `/posts`（複数）/ `/seo/{post_id}`（単数 seo）—— seo は `/seo-meta` または `/posts/{id}/seo` に統一を推奨 |
| apply の位置 | `/pages/{id}/apply`, `/sections/{section_id}/apply`, `/ctas/{cta_id}/apply` は一貫。`/actions/apply` だけ異なるパターン（汎用ゲートウェイとして意図的） |
| `/migration/jobs` vs `/jobs` | 移行ジョブと汎用ジョブで分離されている。責務が異なるため意図的分離だが L3 で確認 |

### agent-neo/v1 と aseo/v1 の責務境界

本 API の「生成／作成」は、LP/HP/収益化/リスク検証向けの**静的構造の組み立て**を指し、AI による内容生成・判定・最適化は一切含まれない（REQ-NF-025）。AI の内容生成・判定は Automation SEO 側（LLM / 統計判定 / variant 生成）で実施する。

| 責務 | agent-neo/v1 | aseo/v1（外部）|
|------|-------------|---------------|
| コンテンツ書き込み（apply）| owner | 経由して agent-neo/v1 へ書き込む |
| AI 自律最適化ループ | 基盤提供（計測 ID / rollback / 検証パイプライン）| オーケストレーション（LLM / 統計判定 / variant 生成）|
| Tier 1 サンドボックス | owner | 不関与 |
| Tier 2 サンドボックス | PATCH apply の受け手 | オーナー（multi-version / A/B / time-machine）|
| 外部エディタ制御 | デフォルト 403 拒否 | 唯一の許可 Write 経路の一つ |

**境界違反リスク**: `aseo/v1` が `wp/v2` を直接書き込む経路を使うと REQ-F-042 違反になる。`aseo/v1 → agent-neo/v1 PATCH` の経路強制を L3 で契約化すること。
