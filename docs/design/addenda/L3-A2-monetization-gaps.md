# L3 Addendum A2 — 広告・アフィリエイト収益化 設計補完

> **起票日**: 2026-06-18  
> **担当 GAP-RT**: GAP-RT-010 / GAP-RT-011 / GAP-RT-012 / GAP-RT-013 / GAP-RT-014  
> **カテゴリ**: A-2 広告・収益化  
> **参照先**: `docs/reviews/L3-real-theme-gap-register.md` §GAP-RT-010〜014  
> **共有ファイル変更禁止**: 本ファイルは addendum 専用。`L3-detailed-design.md` / `event-contract.schema.json` 等の既存ファイルは読み取り参照のみ。スキーマ定義案は本 addendum 内に記述し、実 schema ファイル生成は L4 とする。  
> **設計前提**: REQ-NF-025（AIロジック完全分離）を全節で厳守。テーマは宣言的ゾーン定義・計測ID発火のみ提供し、配置最適化判断は Automation SEO 側。

---

## 目次

1. [GAP-RT-010 — 広告ゾーン管理（ad-zone.schema.json 定義案）](#1-gap-rt-010--広告ゾーン管理)
2. [GAP-RT-011 — ad_tag CPT 詳細スキーマ（5分岐・ランキング構造）](#2-gap-rt-011--ad_tag-cpt-詳細スキーマ)
3. [GAP-RT-012 — Tracking event 細粒度拡張](#3-gap-rt-012--tracking-event-細粒度拡張)
4. [GAP-RT-013 — 外部アフィリエイト CSS adapter](#4-gap-rt-013--外部アフィリエイト-css-adapter)
5. [GAP-RT-014 — Disclosure（PR表記）ブロック](#5-gap-rt-014--disclosurepr表記ブロック)
6. [L4 Carry エントリ一覧](#6-l4-carry-エントリ一覧)

---

## 1. GAP-RT-010 — 広告ゾーン管理

**関連 REQ-F**: REQ-F-004, REQ-F-006  
**関連 REQ-NF**: REQ-NF-008, REQ-NF-025  
**実コード根拠**: テーマA `template-parts/ad-finish.php`, `ad-related.php`, `functions.php`（category override filter）/ 解析レポート `07-計測と連携性分析.md`

### 1.1 設計方針

テーマA は PHP フィルターでH2前・記事終・関連上・ユニット等の広告挿入点を実装している。AGENT-NEO（FSE / Block Theme）ではこれを PHP フィルターで直接移植せず、以下の方針で再設計する。

- **テーマ側の役割**: 宣言的な広告スロット（`ad_zone_id`）を named slot として定義し、計測 ID（`zone_id`）を DOM 属性として発火する。
- **Automation SEO 側の役割**: どのゾーンに何の広告タグを割り当てるか（= 配置最適化）の判断をすべて担う（REQ-NF-025）。
- **カテゴリ別上書き**: テーマ側は category override ルールを「宣言的な優先度付きルール配列」として JSON で保持する。ルールの評価・選択判断は Automation SEO 側。

### 1.2 広告ゾーン一覧（個人版スコープ）

| zone_id | 配置面 | 発火タイミング | 許可 ad_type |
|---|---|---|---|
| `zone_before_h2_{n}` | H2 見出し直前（n=1,2,3...） | the_content フィルター処理後 | all |
| `zone_after_content` | 記事本文終了直後 | the_content フィルター末尾 | all |
| `zone_above_related` | 関連記事セクション直上 | after_entry_content フック | all |
| `zone_sidebar_widget` | サイドバーウィジェットエリア | sidebar widget フック | all |
| `zone_in_content_{n}` | 本文中インライン（n番目テキストブロック後） | the_content パーサー | `normal` / `text` / `affiliate` |

> **個人版テンプレ固定構成（REQ-F-016）**: 個人版はテンプレ構造を変更できないため、zone_id はすべて固定配置スロットとして実装する。ゾーン追加・削除は Companion Plugin 管理画面から AI（Automation SEO）が指示する。

### 1.3 スキーマ定義案（`ad-zone.schema.json`、実体は L4 で生成）

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "agent-neo/ad-zone.schema.json",
  "title": "AdZone",
  "description": "広告スロット宣言スキーマ。テーマが配置面と計測IDを宣言し、割り当てはAutomation SEO側が決定する。",
  "type": "object",
  "required": ["zone_id", "placement", "enabled"],
  "properties": {
    "zone_id": {
      "type": "string",
      "pattern": "^[a-z0-9_]+$",
      "description": "一意の広告ゾーン識別子"
    },
    "placement": {
      "type": "string",
      "enum": [
        "before_h2",
        "after_content",
        "above_related",
        "sidebar_widget",
        "in_content"
      ],
      "description": "配置面の種別"
    },
    "placement_params": {
      "type": "object",
      "properties": {
        "h2_index": { "type": "integer", "minimum": 1 },
        "content_block_index": { "type": "integer", "minimum": 1 }
      },
      "description": "配置面に依存するパラメータ（before_h2 の場合はh2_index等）"
    },
    "enabled": {
      "type": "boolean",
      "description": "ゾーンが有効かどうか（Automation SEO側がオフにできる）"
    },
    "allowed_ad_types": {
      "type": "array",
      "items": {
        "type": "string",
        "enum": ["amazon", "affiliate", "ranking", "normal", "text"]
      },
      "description": "このゾーンで許可するad_type一覧"
    },
    "category_overrides": {
      "type": "array",
      "description": "カテゴリ別上書きルール配列。評価・選択はAutomation SEO側。",
      "items": {
        "type": "object",
        "required": ["category_slug", "override_zone_id", "priority"],
        "properties": {
          "category_slug": { "type": "string" },
          "override_zone_id": {
            "type": "string",
            "description": "このカテゴリでは別のzone_idに差し替えて使用するゾーン"
          },
          "ad_tag_id": {
            "type": ["string", "null"],
            "description": "割り当てるad_tag CPT投稿ID（nullは無効化）"
          },
          "priority": {
            "type": "integer",
            "minimum": 0,
            "description": "複数ルール競合時の優先度（高いほど優先）"
          }
        }
      }
    },
    "dom_anchor": {
      "type": "string",
      "description": "DOM上のanchor属性値（data-agent-zone-id）。計測JSが参照する。"
    },
    "performance_budget_kb": {
      "type": "number",
      "description": "このゾーンが読み込むアセットのKBバジェット上限"
    }
  }
}
```

### 1.4 DOM 出力契約

テーマ側は広告ゾーンを以下の形式で DOM に出力する。実コンテンツの挿入は Automation SEO が `POST /agent-neo/v1/ad-zones/{zone_id}/assign` で行う（L4 endpoint 定義）。

```html
<!-- 例: H2前ゾーン -->
<div
  data-agent-zone-id="zone_before_h2_1"
  data-zone-placement="before_h2"
  data-zone-enabled="true"
  class="agent-neo-ad-zone"
  aria-hidden="true"
>
  <!-- Automation SEO が割り当てた ad_tag コンテンツをここに注入 -->
</div>
```

### 1.5 AI 操作契約（Automation SEO → AGENT-NEO）

| 操作 | Endpoint | 説明 |
|---|---|---|
| ゾーン状態取得 | `GET /agent-neo/v1/ad-zones` | 全ゾーン定義と現在の割り当て状態を返す |
| ゾーン有効/無効 | `PATCH /agent-neo/v1/ad-zones/{zone_id}` | `enabled` フィールドのみ更新可 |
| ad_tag 割り当て | `PATCH /agent-neo/v1/ad-zones/{zone_id}/assign` | `ad_tag_id`（CPT投稿ID）を割り当て |
| カテゴリ別ルール更新 | `PUT /agent-neo/v1/ad-zones/{zone_id}/category-overrides` | `category_overrides` 配列を全量置換 |

> **禁止**: 配置最適化ロジック（どのゾーンに何を割り当てるか）をテーマ側 PHP に持つこと（REQ-NF-025）。テーマ側は「宣言されたゾーンに割り当てられた ad_tag を表示するだけ」に限定する。

### 1.6 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 |
|---|---|---|
| TC-A2-010-1 | `enabled=true` の `zone_before_h2_1` が存在する記事を表示 | H2 直前に `data-agent-zone-id="zone_before_h2_1"` の div が出力される |
| TC-A2-010-2 | `PATCH /ad-zones/zone_before_h2_1 { "enabled": false }` を実行 | 当該ゾーンの DOM 出力が消える（aria-hidden のみ残存も可） |
| TC-A2-010-3 | カテゴリ「レビュー」の category_override に `ad_tag_id=10` を設定 | カテゴリ「レビュー」の記事でのみ ad_tag_id=10 の内容が zone に注入される |
| TC-A2-010-4 | category_override の priority が競合（同カテゴリに2件） | 高い priority のルールが採用される |
| TC-A2-010-5 | 個人版で `zone_sidebar_widget` の allowed_ad_types に `lead_cta`（法人専用）を指定試行 | 422 バリデーションエラーが返る |
| TC-A2-010-6 | `GET /ad-zones` を管理画面から呼ぶ | `manage_options` 権限がなければ 403、あれば全ゾーン一覧が返る |

---

## 2. GAP-RT-011 — ad_tag CPT 詳細スキーマ

**関連 REQ-F**: REQ-F-004, REQ-NF-008  
**実コード根拠**: ThemeB `inc/cpt/ad_tag/`（`ad_type`, `ad_border`, `ad_rank`, `ad_name`, `ad_price`, `ad_desc`, `ad_star`, `ad_btn1_text/url`, `ad_btn2_text/url` のpost meta）/ REST 計測 `wp/v2/themeB-ct-ad-data`（`adid`, `ct_name`）/ 解析レポート `30-ThemeB-block-cpt-rest-deep-extract.md`

### 2.1 設計方針

ThemeB の ad_tag CPT は `public=false, show_in_rest=false`（管理画面のみ・REST 非対応）であったが、AGENT-NEO では Companion Plugin 側 CPT として REST 対応（read: `edit_posts`、write: `manage_options`）で再定義する。ThemeB では単一 CPT に5種の ad_type を post meta で表現していたが、AGENT-NEO では JSON 統一データモデル（REQ-F-025）に従い CPT post_meta の `_agent_neo_ad_tag_data` に型付き JSON として保持する。

### 2.2 CPT 定義

```
CPT slug: agent_neo_ad_tag
表示名:   広告タグ
public:   false
show_in_rest: true
権限: 閲覧=edit_posts / 作成・更新・削除=manage_options
supports: title, custom-fields
```

### 2.3 ad_type 5分岐スキーマ定義案

#### 共通フィールド（全 ad_type 共通）

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `ad_type` | enum | YES | `amazon` / `affiliate` / `ranking` / `normal` / `text` |
| `ad_tag_id` | string | YES | CPT 投稿 ID（slug、整数 WP post_id ではなく stable slug 推奨） |
| `label` | string | YES | 管理画面表示名 |
| `enabled` | boolean | YES | 有効フラグ |
| `tracking_id` | string | NO | 計測集計用 ID（ad_tag_id と同値でもよい。A/B で差し替え可能） |
| `disclosure_required` | boolean | YES | PR 表記が必要かどうか（後述 Disclosure との連携） |

#### `amazon` タイプ

Amazon アソシエイト商品カード。PA-API 連携または手動入力の2モード。

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `asin` | string | YES | Amazon 商品 ASIN |
| `product_name` | string | YES | 商品名（PA-API から取得 or 手動） |
| `image_url` | string | NO | 商品画像 URL（PA-API から取得 or 手動） |
| `price_display` | string | NO | 表示価格テキスト（「¥3,980」等） |
| `rating` | number | NO | 評価値 0.0〜5.0 |
| `review_count` | integer | NO | レビュー件数 |
| `associate_tag` | string | YES | アソシエイトタグ（`?tag=xxx-22`） |
| `affiliate_url` | string | YES | 最終アフィリエイトURL（PA-API 生成 or 手動） |
| `btn_text` | string | NO | ボタンテキスト（デフォルト: 「Amazonで見る」） |
| `pa_api_enabled` | boolean | NO | PA-API 自動取得を使うか（false=手動入力） |

#### `affiliate` タイプ

もしも・a8.net 等汎用ASP アフィリエイトリンクブロック。

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `asp_name` | string | YES | ASP 名（`moshimo` / `a8net` / `valuecommerce` / `other`） |
| `product_name` | string | YES | 商品・サービス名 |
| `affiliate_url` | string | YES | アフィリエイト URL |
| `image_url` | string | NO | バナー・商品画像 URL |
| `btn_text` | string | NO | ボタンテキスト |
| `btn_text_2` | string | NO | サブボタンテキスト（2ボタン対応） |
| `btn_url_2` | string | NO | サブボタン URL |
| `description` | string | NO | 説明テキスト |
| `price_display` | string | NO | 表示価格（任意） |
| `has_border` | boolean | NO | 枠線表示フラグ |

#### `ranking` タイプ

ランキング形式の複合広告ブロック（1位〜N位の商品リスト）。

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `ranking_title` | string | YES | ランキングタイトル（「おすすめXXXランキング」等） |
| `items` | array | YES | ランキングアイテムの配列（下記参照） |

**ランキングアイテム（`items[n]`）**

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `rank` | integer | YES | 順位（1〜） |
| `product_name` | string | YES | 商品名 |
| `image_url` | string | NO | 商品画像 |
| `description` | string | NO | 説明テキスト |
| `price_display` | string | NO | 表示価格 |
| `rating` | number | NO | 評価値 0.0〜5.0 |
| `affiliate_url` | string | YES | アフィリエイトURL |
| `btn_text` | string | NO | ボタンテキスト |
| `badge_label` | string | NO | バッジテキスト（「1位」「人気No.1」等） |
| `tracking_id` | string | NO | このアイテム固有の計測 ID（ランク別 CTR 集計用） |

#### `normal` タイプ

Google AdSense 等の広告タグ・バナー広告（HTML 埋め込み）。

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `ad_html` | string | YES | 広告タグ HTML（AdSense ins タグ等。sanitize 済み保存） |
| `ad_network` | string | NO | `adsense` / `gdn` / `other` |
| `ad_size` | string | NO | `responsive` / `728x90` / `300x250` / `300x600` 等 |
| `lazy_load` | boolean | NO | IntersectionObserver 遅延ロードを適用するか |

> **セキュリティ注記**: `ad_html` は保存時に wp_kses_post 相当の sanitize を通す。`<script>` タグは AdSense 公式 `ins` タグのみ例外ホワイトリスト方式で許可（L4 sanitize 仕様に詳述）。

#### `text` タイプ

テキストリンク形式のアフィリエイト・プロモーションリンク。

| フィールド | 型 | 必須 | 説明 |
|---|---|---|---|
| `link_text` | string | YES | 表示テキスト |
| `affiliate_url` | string | YES | アフィリエイト URL |
| `rel_nofollow` | boolean | NO | `rel="nofollow"` 付与フラグ（デフォルト: true） |
| `rel_sponsored` | boolean | NO | `rel="sponsored"` 付与フラグ（PR表記連動推奨） |
| `open_new_tab` | boolean | NO | `target="_blank"` フラグ |
| `description` | string | NO | リンク周辺の補足テキスト |

### 2.4 post_meta 保存形式

```json
{
  "_agent_neo_ad_tag_data": {
    "ad_type": "ranking",
    "label": "おすすめ脱毛器ランキング",
    "enabled": true,
    "tracking_id": "ad-ranking-dassmou-001",
    "disclosure_required": true,
    "ranking_title": "2026年 おすすめ家庭用脱毛器 TOP3",
    "items": [
      {
        "rank": 1,
        "product_name": "XXX Pro",
        "affiliate_url": "https://...",
        "rating": 4.5,
        "tracking_id": "ad-ranking-dassmou-001-rank1"
      }
    ]
  }
}
```

### 2.5 REST エンドポイント設計案

| Method | Path | 認証 | 説明 |
|---|---|---|---|
| GET | `/agent-neo/v1/ad-tags` | `edit_posts` | 全ad_tag一覧（ページネーション対応） |
| GET | `/agent-neo/v1/ad-tags/{id}` | `edit_posts` | 単一 ad_tag 取得 |
| POST | `/agent-neo/v1/ad-tags` | `manage_options` + nonce | ad_tag 新規作成 |
| PATCH | `/agent-neo/v1/ad-tags/{id}` | `manage_options` + nonce | 部分更新 |
| DELETE | `/agent-neo/v1/ad-tags/{id}` | `manage_options` + nonce | 削除（計測履歴は保持） |
| POST | `/agent-neo/v1/ad-tags/{id}/impression` | 公開（署名+rate limit） | impression 計測 |
| POST | `/agent-neo/v1/ad-tags/{id}/click` | 公開（署名+rate limit） | click 計測 |

> **ThemeB との差異**: ThemeB の `wp/v2/themeB-ct-ad-data` は `__return_true`（認証なし）で計測を受け付けていた。AGENT-NEO では site_token + HMAC 署名 + nonce の3点検証（A-007 tracking endpoint と同方式）を採用してなりすましを防ぐ。

### 2.6 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 |
|---|---|---|
| TC-A2-011-1 | `ad_type=amazon` でad_tag を作成し GET で取得 | `asin`, `associate_tag`, `affiliate_url` が揃った JSON が返る |
| TC-A2-011-2 | `ad_type=ranking` で3商品を持つad_tag を作成 | `items[].rank` が 1/2/3 で順序正しく返る |
| TC-A2-011-3 | `ad_type=normal` で AdSense ins タグを保存 | sanitize 後に正当な ins タグのみ保持（script直書きは除去） |
| TC-A2-011-4 | ad_tag の `/impression` エンドポイントに不正 site_token で POST | 401 SIGNATURE_INVALID が返る |
| TC-A2-011-5 | `disclosure_required=true` の ad_tag が配置されたゾーンを表示 | Disclosure ブロックが自動付与される（GAP-RT-014 連携） |
| TC-A2-011-6 | `enabled=false` の ad_tag が割り当てられたゾーンを表示 | 当該ゾーンには何も出力されない |
| TC-A2-011-7 | `ad_type=text` で `rel_sponsored=true` を設定 | 出力 HTML に `rel="nofollow sponsored"` が含まれる |

---

## 3. GAP-RT-012 — Tracking event 細粒度拡張

**関連 REQ-F**: REQ-F-006, REQ-F-004  
**関連 REQ-NF**: REQ-NF-004, REQ-NF-014  
**実コード根拠**: ThemeB `rest-api/tracking.php` / JS `tracking.js`（scroll_depth 25/50/75/100% threshold, view_time 秒計測）/ 解析レポート `07-計測と連携性分析.md`

### 3.1 現状の問題

`L3-detailed-design.md §A-007` では `event_type` の enum が `impression` / `click` / `conversion` の3値のみ。ThemeB 実装および解析レポート07の設計では `ad_impression` / `affiliate_click` / `scroll_depth` / `view_time` 等が個別計測されており、設計と実態に乖離がある。

### 3.2 event_type 拡張 enum 定義案

以下を `event-contract.schema.json` への **追記案** として示す（実ファイル変更は L4）。

#### 広告・収益化イベント（個人版）

| event_type 値 | トリガー | 計測対象 | 個人版 | 法人版 |
|---|---|---|---|---|
| `ad_impression` | IntersectionObserver（閾値50%以上・0.5秒以上） | 広告ゾーンの表示 | YES | NO |
| `ad_click` | click / mousedown | 広告ゾーン内のリンク・ボタン | YES | NO |
| `affiliate_click` | click / mousedown | ASP アフィリエイトリンク | YES | NO |
| `affiliate_impression` | IntersectionObserver（閾値50%以上・1秒以上） | 商品カード・ランキングブロックの表示 | YES | NO |

#### エンゲージメントイベント（個人版・法人版共通）

| event_type 値 | トリガー | 閾値・パラメータ | 個人版 | 法人版 |
|---|---|---|---|---|
| `scroll_depth` | scroll イベント（throttle 500ms） | `depth`: 25 / 50 / 75 / 100（%）の4段階 | YES | YES |
| `view_time` | visibilitychange + Page Visibility API | `seconds`: 30 / 60 / 120 / 300 の4段階 | YES | YES |
| `cta_view` | IntersectionObserver（閾値50%以上・1秒以上） | CTA ブロック固有 `cta_id` | YES | YES |

#### CTA イベント（既存 impression/click との整合）

| event_type 値 | 説明 | 既存との関係 |
|---|---|---|
| `cta_impression` | CTA 表示（解析レポート07に既出） | 既存 `impression` を CTA 専用に rename する扱い |
| `cta_click` | CTA クリック（解析レポート07に既出） | 既存 `click` を CTA 専用に rename する扱い |

> **後方互換方針**: 既存の `impression` / `click` / `conversion` は **非推奨（deprecated）** とするが削除はしない。`event-contract.schema.json` に `deprecated: true` フラグを追加し、新規実装は細粒度 enum を使用する。移行期間: L4 実装から 6 ヶ月間（ADR-028 のバージョニングポリシーに準拠）。

### 3.3 拡張フィールド（event ごとの追加 payload）

`A-007` の Request Body に以下のオプションフィールドを追加する。

```json
{
  "event_type": "scroll_depth",
  "section_id": "article-main",
  "cta_id": null,
  "variant_id": null,
  "site_token": "...",
  "signature": "...",
  "nonce": "...",
  "payload": {
    "depth": 75,
    "scroll_px": 2400,
    "page_height_px": 3200
  }
}
```

```json
{
  "event_type": "ad_impression",
  "section_id": "zone_before_h2_1",
  "cta_id": null,
  "variant_id": null,
  "site_token": "...",
  "signature": "...",
  "nonce": "...",
  "payload": {
    "ad_tag_id": "ad-amazon-001",
    "ad_type": "amazon",
    "zone_id": "zone_before_h2_1",
    "visible_ratio": 0.82,
    "visible_duration_ms": 1200
  }
}
```

```json
{
  "event_type": "view_time",
  "section_id": "article-main",
  "site_token": "...",
  "signature": "...",
  "nonce": "...",
  "payload": {
    "seconds": 60,
    "active_seconds": 45
  }
}
```

### 3.4 JS 実装契約（フロント側）

```
window.AgentNeo.tracking.emit({
  event_type: "scroll_depth",
  section_id: document.body.dataset.agentSectionId || null,
  payload: { depth: 50 }
});
```

- `window.AgentNeo.tracking.emit()` を統一インターフェースとする
- 重複排除: scroll_depth の各閾値（25/50/75/100）はセッション内で1回のみ送信
- bot 判定: `navigator.webdriver` が true の場合はすべての送信をスキップ
- オフライン: `navigator.onLine=false` の場合は localStorage にキューイングし復帰時に再送（最大 10 件）
- rate limit: 同一 `event_type` + `section_id` を 1 秒以内に重複送信しない

### 3.5 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 |
|---|---|---|
| TC-A2-012-1 | 記事を 75% スクロール | `scroll_depth` + `depth:75` のイベントが A-007 に送信される |
| TC-A2-012-2 | 同じ記事で 75% スクロールを同セッションで2回目実施 | 2回目は送信されない（重複排除） |
| TC-A2-012-3 | 記事を 60 秒閲覧 | `view_time` + `seconds:60` が送信される |
| TC-A2-012-4 | ad_tag（amazon タイプ）が 1.2 秒以上 50% 以上表示 | `ad_impression` + `ad_tag_id` が payload に含まれて送信される |
| TC-A2-012-5 | アフィリエイトリンクをクリック | `affiliate_click` が送信され、`event_id` が返る |
| TC-A2-012-6 | `navigator.webdriver=true` 環境でスクロール | 計測イベントが一切送信されない |
| TC-A2-012-7 | 既存の `event_type=impression` で POST | 200 で受け付けられ、レスポンスに `deprecated_event_type: true` フラグが含まれる |
| TC-A2-012-8 | 不明な `event_type` 値（例: `foo_bar`）で POST | 400 VALIDATION_ERROR が返る |

### 3.6 REQ-NF-004（個人情報非収集）との整合

`scroll_depth` / `view_time` のペイロードに IP アドレス・ユーザーエージェント・メールアドレス等の個人情報を含めない。session_token は疑似ランダム UUID（ページセッション単位、ブラウザ永続なし）とし、ユーザー追跡に使用しない設計を明示する。

---

## 4. GAP-RT-013 — 外部アフィリエイト CSS adapter

**関連 REQ-F**: REQ-F-004, REQ-NF-010  
**関連 REQ-NF**: REQ-NF-001a（JS/CSS予算）, REQ-NF-011  
**実コード根拠**: テーマA `assets/css/others/appreach.css` / `amazon.css` 等 / 解析レポート `33-plugin-compat-style-variations-deep-extract.md`

### 4.1 設計方針

テーマA は外部アフィリエイトプラグイン（かえるべあ / appreach / Amazon アソシエイト等）が出力するHTMLのスタイリングを `assets/css/others/` 配下の個別 CSS ファイルで対応していた。AGENT-NEO ではこれを以下の方針で再設計する。

- **FSE 原則**: `style.css` への全量バンドルは行わない。`block.json` の `editorStyle` / `style` フィールドで条件付きロードする。
- **adapter 方式**: 各外部プラグインに対応する CSS adapter を独立した登録可能モジュールとして管理する。
- **CSS 予算遵守**: adapter CSS は合算でも記事ページ JS < 15KB / CSS 全体予算に収まるよう設計する。
- **オプトイン**: adapter は Companion Plugin の管理画面でプラグイン検出に基づき自動有効化するが、無効化も可能。

### 4.2 対応 adapter 一覧（初版スコープ）

| adapter_id | 対象プラグイン | CSS ソース | ロード条件 | 推定サイズ |
|---|---|---|---|---|
| `appreach` | AppReach（アプリ紹介カード） | `adapters/appreach.css` | `is_plugin_active('appreach/appreach.php')` | ~3KB |
| `kaerubear` | かえるべあ（ASP 商品紹介カード） | `adapters/kaerubear.css` | `is_plugin_active('kaerubear/...')` | ~4KB |
| `amazon_asso` | Amazon アソシエイト iFrame/バナー | `adapters/amazon-asso.css` | アフィリエイトリンクが本文に存在 | ~2KB |
| `moshimo` | もしもアフィリエイト | `adapters/moshimo.css` | `is_plugin_active('moshimo-af/...')` | ~2KB |
| `a8net` | a8.net | `adapters/a8net.css` | a8.net ドメインリンク検出 | ~1KB |

> **Phase 1 スコープ**: appreach / kaerubear / amazon_asso の3件を優先実装。moshimo / a8net は Phase 2 以降。

### 4.3 adapter 管理スキーマ定義案（`affiliate-css-adapter.schema.json`）

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "agent-neo/affiliate-css-adapter.schema.json",
  "title": "AffiliateCssAdapter",
  "type": "object",
  "required": ["adapter_id", "label", "enabled", "load_condition"],
  "properties": {
    "adapter_id": {
      "type": "string",
      "pattern": "^[a-z0-9_]+$"
    },
    "label": {
      "type": "string",
      "description": "管理画面表示名"
    },
    "enabled": {
      "type": "boolean"
    },
    "css_file": {
      "type": "string",
      "description": "Companion Plugin 内の CSS ファイルパス（adapters/ 配下）"
    },
    "load_condition": {
      "type": "string",
      "enum": [
        "plugin_active",
        "link_detected",
        "always",
        "manual"
      ],
      "description": "ロード条件種別"
    },
    "load_condition_params": {
      "type": "object",
      "properties": {
        "plugin_slug": {
          "type": "string",
          "description": "is_plugin_active() に渡すプラグインスラッグ（load_condition=plugin_activeの場合）"
        },
        "link_domain_pattern": {
          "type": "string",
          "description": "正規表現パターン（load_condition=link_detectedの場合）"
        }
      }
    },
    "estimated_size_kb": {
      "type": "number",
      "description": "概算 CSS サイズ（予算管理用）"
    },
    "page_types": {
      "type": "array",
      "items": {
        "type": "string",
        "enum": ["single", "page", "archive", "home", "lp", "all"]
      },
      "description": "ロード対象ページタイプ（REQ-F-029 ページタイプ別アセット振り分けと連携）"
    },
    "scope_prefix": {
      "type": "string",
      "description": "CSS スコーププレフィックス。セレクタに自動付与（例: .agent-neo-adapter-appreach）"
    }
  }
}
```

### 4.4 CSS スコープ化方針

外部 adapter CSS はすべて `.agent-neo-adapter-{adapter_id}` プレフィックスでスコープ化する。これにより AGENT-NEO テーマ全体のスタイルへの漏洩を防ぐ。

```css
/* appreach.css のコンパイル後イメージ */
.agent-neo-adapter-appreach .appreach { /* ... */ }
.agent-neo-adapter-appreach .appreach__icon { /* ... */ }
```

外部プラグインが出力する HTML のルート要素に `agent-neo-adapter-{adapter_id}` クラスを付与するための PHP フィルターを Companion Plugin 側で提供する（`the_content` フィルター + DOM パーサー）。

### 4.5 プラグイン検出・自動有効化フロー

```
1. プラグイン有効化 / 投稿保存時に adapter_id を走査
2. load_condition=plugin_active: is_plugin_active() で検出
3. load_condition=link_detected: 本文 HTML を正規表現スキャン
4. 検出時: options に adapter_enabled[adapter_id]=true を保存
5. フロント出力時: wp_enqueue_style() で条件付きロード
6. 管理画面 S-xxx（新規: アダプター管理画面）で一覧表示・手動 ON/OFF
```

### 4.6 AI 操作契約

| 操作 | Endpoint | 説明 |
|---|---|---|
| adapter 一覧取得 | `GET /agent-neo/v1/affiliate-adapters` | 全 adapter の状態を返す |
| adapter 有効/無効 | `PATCH /agent-neo/v1/affiliate-adapters/{adapter_id}` | `enabled` フィールドのみ更新可 |

### 4.7 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 |
|---|---|---|
| TC-A2-013-1 | AppReach プラグインを有効化した状態で記事を表示 | `appreach` adapter CSS がフロントで読み込まれる |
| TC-A2-013-2 | AppReach を無効化 | adapter CSS が読み込まれなくなる |
| TC-A2-013-3 | adapter CSS が読み込まれた記事でクラスを確認 | `.agent-neo-adapter-appreach` でスコープ化されている |
| TC-A2-013-4 | 全 adapter 有効時の記事 CSS 合計サイズを計測 | adapter CSS 合計 < 15KB（記事ページ予算内） |
| TC-A2-013-5 | `PATCH /affiliate-adapters/appreach { "enabled": false }` | 次のページ読み込みから appreach CSS が出力されなくなる |
| TC-A2-013-6 | `load_condition=link_detected` adapter で該当ドメインのリンクが本文にない記事を表示 | 当該 adapter CSS は読み込まれない |

---

## 5. GAP-RT-014 — Disclosure（PR表記）ブロック

**関連 REQ-F**: REQ-F-004, REQ-NF-009  
**関連 REQ-NF**: REQ-NF-009（景表法・ステマ規制対応）, REQ-NF-025  
**実コード根拠**: 解析レポート `20-運用セキュリティ可用性更新性分析.md` / 設計 `docs/requirements/` REQ-NF-009

### 5.1 法的背景

2023年10月施行の景品表示法ステルスマーケティング規制（以下「ステマ規制」）により、事業者による広告・PR 表示の明示が義務付けられた。アフィリエイトブログで収益化している個人の場合も「事業者」に該当し得るため、AGENT-NEO は PR 表記の仕組みを製品仕様として提供する（REQ-NF-009）。

### 5.2 設計方針と方式選択

PR 表記の付与方式として以下の3方式を検討し、**方式B（ブロック＋自動付与ハイブリッド）** を採用する。

| 方式 | 説明 | 採否 |
|---|---|---|
| A. 全記事共通ヘッダー | サイト全体に「本サイトはアフィリエイトを含みます」的な表示 | 参考のみ（記事個別制御不可） |
| **B. ブロック＋自動付与ハイブリッド** | 専用 Disclosure ブロックを提供しつつ、`disclosure_required=true` の ad_tag が含まれる記事に自動付与トリガーも提供 | **採用** |
| C. アフィリエイトリンク検出時自動挿入 | 本文中のリンクをスキャンし自動挿入 | L4 オプション（Phase 2） |

### 5.3 Disclosure ブロック仕様

#### ブロック識別子

```
block.json: agent-neo/disclosure
ブロック名: PR・広告表記
カテゴリ: text
```

#### block.json 属性定義案

```json
{
  "name": "agent-neo/disclosure",
  "title": "PR・広告表記",
  "category": "text",
  "attributes": {
    "disclosureType": {
      "type": "string",
      "enum": ["affiliate", "pr", "ad", "sponsor", "custom"],
      "default": "affiliate",
      "description": "表記種別"
    },
    "displayText": {
      "type": "string",
      "default": "本記事にはアフィリエイト広告が含まれています。",
      "description": "表示するPR文言"
    },
    "placement": {
      "type": "string",
      "enum": ["top", "bottom", "manual"],
      "default": "top",
      "description": "記事内での配置位置（自動付与の場合）"
    },
    "isAutoInserted": {
      "type": "boolean",
      "default": false,
      "description": "Automation SEOによる自動挿入フラグ（手動ブロックはfalse）"
    },
    "linkedAdTagIds": {
      "type": "array",
      "items": { "type": "string" },
      "description": "このDisclosureが対応するad_tag IDのリスト（traceability）"
    },
    "visibilityCondition": {
      "type": "string",
      "enum": ["always", "has_affiliate_link", "specific_category"],
      "default": "always",
      "description": "表示条件"
    },
    "specificCategories": {
      "type": "array",
      "items": { "type": "string" },
      "description": "visibilityCondition=specific_categoryの場合の対象カテゴリslug"
    }
  }
}
```

#### 表示文言プリセット（デフォルト提供）

| disclosureType | デフォルト文言 |
|---|---|
| `affiliate` | 本記事にはアフィリエイト広告が含まれています。 |
| `pr` | 本記事はPR広告を含みます。 |
| `ad` | 本記事は広告を含みます。 |
| `sponsor` | 本記事はスポンサーの提供でお送りしています。 |
| `custom` | （ユーザー任意の文言） |

> ユーザーはカスタムテキストに変更可能。ただし意図的な PR 表記の削除・隠蔽を補助する機能は提供しない。

### 5.4 自動付与フロー

`disclosure_required=true` の ad_tag が記事に含まれると検出された場合、以下のフローで Disclosure ブロックの自動付与を促す。**自動付与の実行判断は Automation SEO 側**（REQ-NF-025）であり、テーマ側は「自動付与 API エンドポイント」と「フラグチェック」のみ提供する。

```
1. Automation SEO 側: 記事内の ad_tag を解析
   → disclosure_required=true を含む場合
   → POST /agent-neo/v1/posts/{id}/disclosure-check を実行
   → AGENT NEO 側: 当該記事の本文を確認し、
     既に agent-neo/disclosure ブロックが存在すれば "has_disclosure=true"
     存在しなければ "has_disclosure=false" を返す

2. Automation SEO 側: has_disclosure=false の場合
   → PATCH /agent-neo/v1/posts/{id}/blocks にて
     placement=top に agent-neo/disclosure ブロックを挿入

3. AGENT NEO 側: 挿入は REQ-F-021 (部分更新性) の PATCH で処理
   → isAutoInserted=true, linkedAdTagIds=[...] を設定
```

### 5.5 表示条件の実装規約

| visibilityCondition | テーマ側の動作 |
|---|---|
| `always` | 条件なしで常に表示 |
| `has_affiliate_link` | 記事本文に ASP/アフィリエイトドメインのリンクが存在する場合のみ表示 |
| `specific_category` | `specificCategories` に含まれるカテゴリの記事のみ表示 |

> **制約（REQ-NF-025）**: リンクの ASP 判定ロジック（どのドメインがアフィリエイトかの判断）は `affiliate_domain_allowlist.json` として宣言的に保持し、判定計算は PHP で行う。ただし「この記事に PR 表記が必要かどうか」の判断（コンテキスト解析）は Automation SEO 側。テーマ側は「存在する Disclosure ブロックの表示条件評価」のみ実施する。

### 5.6 `affiliate_domain_allowlist.json` 定義案

```json
{
  "version": "1.0.0",
  "description": "アフィリエイトドメイン判定のallowlist。Disclosure表示条件hasAffiliateLinkの評価に使用。",
  "domains": [
    { "domain": "amzn.to", "asp": "amazon", "label": "Amazonアソシエイト" },
    { "domain": "amazon.co.jp", "asp": "amazon", "label": "Amazon" },
    { "domain": "af.moshimo.com", "asp": "moshimo", "label": "もしもアフィリエイト" },
    { "domain": "px.a8.net", "asp": "a8net", "label": "a8.net" },
    { "domain": "ck.jp.ap.valueclick.com", "asp": "valuecommerce", "label": "バリューコマース" },
    { "domain": "h.accesstrade.net", "asp": "accesstrade", "label": "アクセストレード" },
    { "domain": "api.ad.smaad.net", "asp": "smaad", "label": "SmAd" }
  ],
  "user_additions": {
    "description": "ユーザーが追加したドメイン一覧（管理画面から登録可能）",
    "items": []
  }
}
```

### 5.7 フロント出力 HTML 契約

```html
<!-- agent-neo/disclosure ブロック出力例 -->
<div
  class="agent-neo-disclosure"
  data-disclosure-type="affiliate"
  data-auto-inserted="false"
  role="note"
  aria-label="広告表記"
>
  <p class="agent-neo-disclosure__text">
    本記事にはアフィリエイト広告が含まれています。
  </p>
</div>
```

- `role="note"` でスクリーンリーダーに補足情報として認識させる（REQ-NF-005 / WCAG 2.2 AA）
- `data-disclosure-type` を DOM 属性で公開し、Automation SEO が監査時に機械読み取り可能にする（REQ-NF-015）

### 5.8 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 |
|---|---|---|
| TC-A2-014-1 | `agent-neo/disclosure` ブロックを記事先頭に手動挿入 | `data-disclosure-type="affiliate"` の div が記事先頭に出力される |
| TC-A2-014-2 | `disclosureType=pr` を選択 | 出力テキストが「本記事はPR広告を含みます。」になる |
| TC-A2-014-3 | `disclosureType=custom`, `displayText=【PR】本記事は提供品レビューです。` | カスタム文言が出力される |
| TC-A2-014-4 | `visibilityCondition=has_affiliate_link` で amzn.to リンクなし記事を表示 | Disclosure ブロックが出力されない |
| TC-A2-014-5 | `visibilityCondition=has_affiliate_link` で amzn.to リンクあり記事を表示 | Disclosure ブロックが出力される |
| TC-A2-014-6 | `disclosure_required=true` の ad_tag がある記事で `GET /disclosure-check` | `has_disclosure=false` が返る（Disclosure ブロック未挿入の場合） |
| TC-A2-014-7 | Disclosure ブロックを出力した HTML を axe-core で検証 | `role="note"` が適切に機能し a11y 違反なし |
| TC-A2-014-8 | Disclosure ブロックの出力に `aria-label` が存在することを確認 | `aria-label="広告表記"` が含まれる |

---

## 6. L4 Carry エントリ一覧

以下は本 addendum で設計を示したが、実装・実ファイル生成・スキーマ確定を L4 に委ねる項目。

### CARRY-A2-001（GAP-RT-010 | P0 | L4 必須）

```yaml
id: CARRY-A2-001
title: ad-zone.schema.json 実ファイル生成 + REST endpoint 実装
gap_rt: GAP-RT-010
priority: P0
description: >
  本 addendum §1.3 の定義案を元に docs/design/schemas/ad-zone.schema.json を生成する。
  また §1.5 の REST endpoint 4本（GET/PATCH/assign/category-overrides）を
  Companion Plugin に実装する。
acceptance: TC-A2-010-1〜6 全通過
dependencies: GAP-RT-011 CPT 定義（CARRY-A2-002）
assignee: be-api
```

### CARRY-A2-002（GAP-RT-011 | P0 | L4 必須）

```yaml
id: CARRY-A2-002
title: agent_neo_ad_tag CPT 実装 + 5分岐スキーマ確定 + REST 5本実装
gap_rt: GAP-RT-011
priority: P0
description: >
  本 addendum §2.2〜2.5 を元に Companion Plugin で agent_neo_ad_tag CPT を登録し、
  post_meta _agent_neo_ad_tag_data に5分岐 JSON を保存する実装を行う。
  REST endpoint 5本（GET×2 / POST / PATCH / DELETE）を実装し、
  impression / click 計測 endpoint 2本も実装する。
  ad_type=normal の ad_html sanitize 仕様（AdSense ins タグ ホワイトリスト）を L4 で詳述する。
acceptance: TC-A2-011-1〜7 全通過
dependencies: なし
assignee: be-logic, be-api
```

### CARRY-A2-003（GAP-RT-012 | P0 | L4 必須 + test-plan）

```yaml
id: CARRY-A2-003
title: event_type 拡張 enum + event-contract.schema.json 追記 + フロント JS 実装
gap_rt: GAP-RT-012
priority: P0
description: >
  本 addendum §3.2 の拡張 event_type enum を L3-detailed-design.md §A-007 の
  正本（event-contract.schema.json）に追記する（共有ファイル変更は L4 着手時に実施）。
  既存 impression/click/conversion を deprecated にする移行フラグを追加する。
  フロント JS（window.AgentNeo.tracking.emit）の重複排除・bot 判定・オフラインキューを実装する。
  解析レポート07の scroll_depth (25/50/75/100%) / view_time (30/60/120/300s) 閾値を実装する。
acceptance: TC-A2-012-1〜8 全通過 + test-plan TC として L3-test-plan.md に追記
dependencies: A-007 endpoint（既存実装を拡張）
assignee: be-api, fe-component
```

### CARRY-A2-004（GAP-RT-013 | P1 | L4）

```yaml
id: CARRY-A2-004
title: affiliate-css-adapter.schema.json 生成 + appreach/kaerubear/amazon-asso 3 adapter 実装
gap_rt: GAP-RT-013
priority: P1
description: >
  本 addendum §4.3 のスキーマ定義案を元にスキーマファイルを生成する。
  appreach / kaerubear / amazon_asso の3 adapter CSS を実装し、
  is_plugin_active() / リンク検出によるオプトイン有効化フローを Companion Plugin に実装する。
  CSS スコープ化（.agent-neo-adapter-{id} プレフィックス付与）の PHP フィルターを実装する。
acceptance: TC-A2-013-1〜6 全通過 + 記事ページ adapter CSS 合計 < 15KB
dependencies: REQ-F-029 ページタイプ別アセット振り分けの実装
assignee: fe-style, be-logic
```

### CARRY-A2-005（GAP-RT-014 | P0 | L4 必須）

```yaml
id: CARRY-A2-005
title: agent-neo/disclosure ブロック実装 + disclosure-check endpoint + affiliate_domain_allowlist.json
gap_rt: GAP-RT-014
priority: P0
description: >
  本 addendum §5.3〜5.7 を元に agent-neo/disclosure ブロックを block.json + PHP render で実装する。
  POST /agent-neo/v1/posts/{id}/disclosure-check endpoint を実装する。
  affiliate_domain_allowlist.json を生成し、Companion Plugin 管理画面から
  ユーザー追加ドメインを登録できる UI を提供する。
  WCAG 2.2 AA 対応（role=note / aria-label）を必須とする。
acceptance: TC-A2-014-1〜8 全通過 + ACC-NF-003 compliance review 通過
dependencies: REQ-F-021 (部分更新性) の実装（自動挿入フローで使用）
assignee: be-logic, fe-component
```

### CARRY-A2-006（全 GAP-RT | P1 | L4 後半）

```yaml
id: CARRY-A2-006
title: 広告収益化ダッシュボード画面（S-xxx）設計・実装
gap_rt: GAP-RT-010, GAP-RT-011, GAP-RT-012
priority: P1
description: >
  ad-zone 割り当て状態・ad_tag 一覧・event_type 別集計（ad_impression/affiliate_click等）を
  管理画面ダッシュボードで可視化する画面を新規追加する（S-xxx: 収益化管理）。
  affiliate CSS adapter 管理画面（ON/OFF 切り替え）も本画面に含める。
  ASP 別 CTR / 収益サマリは Automation SEO 側から集計結果を受信して表示（REQ-NF-025）。
acceptance: 管理画面で全ゾーン状態・全ad_tag・収益イベント集計が確認できる
dependencies: CARRY-A2-001〜003 の実装完了
assignee: fe-component
```

---

*作成: 2026-06-18 / be-logic 寄りドラフタ / 担当 GAP: A-2 広告・収益化 5件（GAP-RT-010〜014）*  
*共有ファイル変更: 禁止（本 addendum 内スキーマ定義案 = L4 で実ファイル生成する際の原案）*  
*次アクション: L4 entry 時に CARRY-A2-001〜006 を工程表に組み込む。event-contract.schema.json の拡張は CARRY-A2-003 の L4 着手時に共有ファイル変更として実施する。*
