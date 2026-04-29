# PM 観点統合考察 — Codex 解析の実コード根拠化

> 解析日: 2026-04-30 / 作成者: PM Opus
> 目的: Codex が抽象論で止めた箇所を実コード根拠に接続し、L2 設計の起点にする

## 0. このレポートの位置づけ

Codex は 29 本の解析レポート + 1,792 行の `analysis-summary.json` で**戦略・思想**を充実させたが、**実コード由来の具体的データ・スキーマ・テーブル構造**は意図的に省略していた（コード引用禁止のため）。

本レポートでは Explore サブエージェントによる追加抽出（30〜33 番）を踏まえ、AGENT NEO の以下 5 ファイルが **実コード根拠付きで** 設計可能になった点を整理する:

1. `block-registry.json` — レポート 30 がベース
2. `seo-meta.schema.json` — レポート 31 がベース
3. `design-tokens.json` — レポート 31 + 32 がベース
4. `asset-policy.schema.json` — レポート 32 がベース
5. `agent-actions.schema.json` — レポート 30 + 33 がベース

---

## 1. SWELL ブロック総括（32 ブロック）→ AGENT NEO への取捨選択

### 採用（個人版コア）

| SWELL ブロック | 採用判断 | AGENT NEO 名前空間案 | 用途 |
|---|---|---|---|
| `loos/faq` | ✅ 採用 | `agent-neo/faq` | FAQ + JSON-LD（個人/法人共通） |
| `loos/step` | ✅ 採用 | `agent-neo/step` | 手順説明 |
| `loos/button` | ✅ 採用 | `agent-neo/button` | CTA（cta_id 必須化） |
| `loos/banner-link` | ✅ 採用 | `agent-neo/banner` | バナー導線 |
| `loos/box-menu` | ✅ 採用 | `agent-neo/gateway-grid` | HP の入口（gateway_id 必須化） |
| `loos/full-wide` | ✅ 採用 | `agent-neo/section` | フルワイドセクション（section_id 必須化） |
| `loos/post-list` | ✅ 採用 | `agent-neo/post-list` | 関連記事・回遊 |
| `loos/balloon` | ✅ 採用 | `agent-neo/speech-balloon` | ブログ的演出（個人版優先） |
| `loos/cap-block` | ✅ 採用 | `agent-neo/note-box` | 補足ボックス |
| `loos/dl/dt/dd` | ✅ 採用 | `agent-neo/definition-list` | 構造化用語リスト |

### 採用（個人版アフィリ拡張）

| SWELL ブロック | 採用判断 | AGENT NEO 強化 |
|---|---|---|
| `loos/review` | ✅ 採用 | `agent-neo/review`（Review schema.org 強化、PR 表記必須化、Amazon API メタ連携） |
| `loos/ad-tag` | ✅ 採用 | `agent-neo/ad-tag`（cta_id / tracking_id / pr_disclosure / placement_rules メタ追加。**REST 公開**で AI 操作可能に） |

### 採用（法人版拡張）

| SWELL ブロック | AGENT NEO 強化 |
|---|---|
| `loos/blog-parts` | `agent-neo/reusable-part`（service_id でサービス別フィルタ、parts_use taxonomy で拡張） |
| `loos/restricted-area` | `agent-neo/restricted-area`（権限分離 4 ロール対応） |
| `loos/ab-test` / `ab-test-a` / `ab-test-b` | `agent-neo/variant` / `variant-a` / `variant-b`（variant_id を seo-tool-connector スキーマに準拠） |

### 再設計

| SWELL ブロック | 改善点 |
|---|---|
| `loos/columns` / `column` | FSE の `core/columns` を活用、独自 columns は不要 |
| `loos/tab` / `tab-body` | アクセシビリティ強化（`role="tablist"`, `aria-controls` 必須） |
| `loos/accordion` / `accordion-item` | `<details>/<summary>` ベースに刷新（a11y + AI 機械読みやすさ） |
| `loos/link-list` | `core/list` + Pattern で代替検討 |

### 見送り

| SWELL ブロック | 理由 |
|---|---|
| 各種ショートコード | block.json 単一ソース原則のため shortcode 後方互換は持たない |
| balloon の旧 CPT (speech_balloon) | カスタムテーブル直書きは AI 操作面で逆効果 |

### 重要な設計差分

**SWELL は `block.json` に `styles` フィールドを持たず、属性で variant 制御している**。AGENT NEO は **AI 機械可読性重視**で標準的な `styles` フィールド + `register_block_style()` パターンを採用する。AI が JSON 1 行で variation を切り替えられるように。

---

## 2. SWELL CPT 構造 → AGENT NEO の CPT 設計

### SWELL の CPT（lib/post_type.php）

| CPT | public | show_in_rest | 用途 |
|---|---|---|---|
| `lp` | true | true | LP 専用、single-lp.php で個別テンプレート |
| `blog_parts` | false（管理画面のみ） | true | 再利用パーツ |
| `ad_tag` | false（管理画面のみ） | **false** | 広告タグ |

### AGENT NEO の CPT 設計（実コード根拠あり）

| AGENT NEO CPT | public | show_in_rest | 改善点 |
|---|---|---|---|
| `agent-neo/lp` | true | true | offer_id / service_id 必須メタ追加。法人版限定 |
| `agent-neo/reusable-part` | false | true | service_id でサービス別フィルタ、parts_use 強化 |
| `agent-neo/ad-tag` | false | **true**（SWELL から改善） | tracking_id / cta_id / placement_rules / pr_disclosure メタ。AI 操作可能に |
| `agent-neo/blueprint`（新設）| false | true | 法人版の HP/LP/BLP の JSON 契約を保管。version 管理付き |

`agent-neo/ad-tag` の REST 公開が **個人版 AI 自動運用の鍵**。SWELL は管理画面 UI 専用に閉じていたため AI 操作できなかった点を改善する。

---

## 3. SWELL REST エンドポイント（14 route, 16 method）→ AGENT NEO API への含意

### SWELL の REST 構造（観察）

| カテゴリ | route 数 | 備考 |
|---|---|---|
| ブロックエディタ設定 | 1 | GET/POST |
| 計測系 | 4 | POST（PV, button counter, ad counter, reset） |
| キャッシュ・設定管理 | 2 | POST |
| 更新実行 | 1 | POST（License-coupled） |
| コンテンツ遅延読み込み | 1 | GET（pagination 補助） |
| タクソノミー | 1 | GET |
| ふきだし管理 | 7 | GET/POST/DELETE/PATCH + copy/sort/recover |

### AGENT NEO API 設計（実コード根拠 + 改善）

```
agent-neo/v1
├─ posts/                  # 記事 CRUD（個人/法人共通）
├─ media/                  # メディア CRUD（共通）
├─ taxonomies/             # タクソノミー（共通）
├─ ad-tags/                # 広告タグ CRUD（個人版・SWELL の改善）
├─ blueprints/             # HP/LP/BLP ブループリント（法人版限定）
├─ design-tokens/          # design-tokens.json 操作（法人版限定）
├─ reusable-parts/         # 再利用パーツ（法人版限定）
├─ tracking/               # 計測（seo-tool-connector 互換）
│   ├─ event
│   ├─ section-engagement
│   └─ context
├─ ab-variants/            # A/B variant 管理（法人版限定）
├─ leads/                  # リード管理（法人版限定）
├─ migration/              # 移行プラン A/B（移行プラグイン経由）
└─ settings/               # 設定 export/import / license / health
```

### 重要設計判断

- **計測系は seo-tool-connector の API スキーマと整合**（POST /v1/tracking/event, /context, /section-engagement）。ゼロから作らない
- **dryRun フラグ**を全書き込み API に必須化（SWELL は未実装、AGENT NEO の独自価値）
- **idempotency-key ヘッダ**を全書き込み API でサポート（同上）
- **個人版 / 法人版でルート公開範囲を切替**: `package-matrix` の feature flag で動的に `register_rest_route` を呼び分け

---

## 4. JIN:R SEO 構造 → AGENT NEO seo-meta.schema.json の起点

### JIN:R の Post Meta（`_jinr_*` 系 10 個）

| Post Meta Key | 型 | AGENT NEO への写像 |
|---|---|---|
| `_jinr_seotitle_display` | string | `agent_neo_seo_title` |
| `_jinr_description_display` | text | `agent_neo_seo_description` |
| `_jinr_keyword_display` | string | （SEO 観点で削除：keyword は GG 評価対象外） |
| `_jinr_canonical_display` | url | `agent_neo_seo_canonical` |
| `_jinr_noindex_display` | bool | `agent_neo_seo_noindex` |
| `_jinr_category` | int | （AGENT NEO は taxonomy に集約） |
| `_jinr_thumb_youtube` | url | `agent_neo_hero_video_url` |

### JIN:R の Customizer（500+ settings）→ AGENT NEO design-tokens

JIN:R が 14 セクションで 500+ settings を管理しているのは過多。AGENT NEO は **40 トークン以下** を目標に絞り込む:

```json
{
  "colors": {
    "primary": "#04384c",
    "accent": "#1176d4",
    "text": "#333",
    "background": "#fff",
    "marker": { "blue": "#b7e3ff", "yellow": "#ffeb70", "red": "#ffadad" }
  },
  "typography": {
    "fontFamily": "yugo",
    "fontSize": { "base": "16px", "sp": "14px" },
    "lineHeight": { "base": 1.7, "sp": 1.6 }
  },
  "spacing": { "container": 1200, "article": 800 },
  "radius": { "sm": 4, "md": 8, "lg": 16 }
}
```

これを `theme.json` に変換するビルダーで FSE 標準に乗せる。

### JSON-LD 出力タイプ

JIN:R が 7 タイプ（Article, WebPage, WebSite, CollectionPage, BreadcrumbList, Person, Organization）を出力している。AGENT NEO は + 3 タイプ（Review, FAQPage, Offer）を追加して 10 タイプ対応。

---

## 5. SWELL アセット読み込みパイプライン → AGENT NEO への直接転用

SWELL の 2 層判定が秀逸:
- **第 1 層**: hook 基準（`is_admin`, `is_customize_preview`, `$post_type === 'ad_tag'`）
- **第 2 層**: ブロック検知基準（`Pre_Parse_Blocks::init()` で `wp_head` Pri:0 で発火 → 全ブロック検出 → CSS separate）

AGENT NEO の `asset-policy.schema.json`:

```json
{
  "$schema": "...",
  "policies": [
    {
      "id": "agent-neo-block-detection",
      "trigger": "wp_head",
      "priority": 0,
      "rules": [
        {"if": "block_used", "block": "agent-neo/cta-button", "load": ["assets/blocks/cta-button.css"]},
        {"if": "post_type", "value": "agent-neo/lp", "load": ["assets/lp-base.css", "assets/lp-sections.css"]},
        {"if": "is_customize_preview", "skip_cache": true}
      ]
    }
  ],
  "cache": {
    "transient_prefix": "agent_neo_assets_",
    "ttl_seconds": 2592000,
    "invalidate_on": ["customizer_save", "design_tokens_update"]
  }
}
```

SWELL の `Pre_Parse_Blocks` は静的配列（`SWELL::$used_blocks`）に蓄積する単純設計。AGENT NEO もこれを採用。**ただしサイドバー separate のキャッシュは SWELL では未完成（草案コメントのみ）→ AGENT NEO は完成形で実装**。

---

## 6. ad_tag CPT — 個人版アフィリエイト機能の核心

### SWELL の ad_tag が持つ 5 タイプ
- normal（バナー画像）
- text（テキスト広告）
- affiliate（商品レビュー型）
- amazon（Amazon 特化）
- ranking（ランキング型）

### AGENT NEO で追加すべきタイプと拡張

| タイプ | 用途 | 追加メタ |
|---|---|---|
| `mercari_card` | メルカリ商品カード | mercari_item_id, mercari_url, image_url（自動取得） |
| `adsense_in_article` | AdSense 記事内広告 | adsense_slot_id, adsense_ad_format, position_rules |
| `adsense_auto` | AdSense 自動広告 | enabled flag のみ |
| `affiliate_with_amazon_api` | Amazon API 連携 | amazon_asin, auto_refresh_interval, fallback_image |

### 必須メタ追加（SWELL からの改善）

| メタキー | 目的 |
|---|---|
| `cta_id` | クリック計測の主キー（seo-tool-connector 互換） |
| `tracking_id` | 計測スクリプト紐付け |
| `pr_disclosure` | 景表法 PR 表記の自動付与フラグ（**ON がデフォルト**） |
| `placement_rules` | 記事内自動差し込みルール（位置・条件） |
| `ab_variant_group` | A/B テスト連携 |
| `affiliate_network` | 広告ネットワーク種別（amazon/rakuten/asp/adsense） |
| `revenue_estimate` | 収益推定値（管理画面ダッシュボード用） |

### REST 公開（最重要）

SWELL の ad_tag は `show_in_rest: false` で AI から触れない。AGENT NEO は **必ず `show_in_rest: true`** にして agent-api 経由で:
- AI が新商品の ad_tag を作成
- AI が CTR の悪い ad_tag を非アクティブ化
- AI が記事に最適な ad_tag を自動配置

これらを完結できるようにする。これが個人版の独自価値の中核。

---

## 7. ブロックパターン → AGENT NEO のパターンライブラリ戦略

### SWELL の革新的設計

`blog_parts` CPT で保存したコンテンツが **自動的に block_pattern として登録される**（lib/gutenberg/block_patterns.php L31-64）。

### AGENT NEO の同等機能

```
agent-neo/reusable-part (CPT, show_in_rest=true)
  ├─ taxonomy: parts_use { pattern, cta, section, hero, blp_block }
  ├─ taxonomy: service_assoc { service_id でサービス別 }（法人版）
  └─ init hook で register_block_pattern() 自動実行
```

法人版で**サービス別パターンライブラリ**が運用しながら充実する仕組み。AI が「サービス A 用の Hero」を作って保存 → 即パターンとして他ページで再利用可能。

---

## 8. プラグイン互換戦略の確定案

SWELL（SSP に深い統合）と JIN:R（独立志向）の両方の弱点を回避する **3 ティア戦略**:

| Tier | 対象 | AGENT NEO の振る舞い |
|---|---|---|
| 1（深い統合）| seo-tool-connector | first-party。前提として深く統合、API スキーマ準拠 |
| 2（adapter 経由） | Yoast / RankMath / Schema Pro / All in One SEO | 検出時に重複出力を抑制（duplicate guard）。AGENT NEO の SEO Core はそのままだが、JSON-LD は片方のみ出力 |
| 3（フォールバック）| なし（プレーン WP） | AGENT NEO 自前で全機能完結。プラグイン不要で動作 |

検出は `is_plugin_active()` + `class_exists()` の二重チェック。`adapter-capability-map.json` で対応プラグインと振る舞いを一元管理。

---

## 9. L2 全体設計への引き継ぎ事項（Action Items）

### 高優先（L2 凍結時に必須）

1. ✅ `block-registry.json` v0.1: 32 SWELL ブロックの取捨選択をベースに 25-30 ブロックの初版を凍結
2. ✅ `seo-meta.schema.json` v0.1: JIN:R の 10 post_meta + 7 JSON-LD タイプ + 3 追加タイプで凍結
3. ✅ `design-tokens.json` v0.1: 40 トークン以下に絞った初版を凍結。theme.json 変換ビルダー設計
4. ✅ `asset-policy.schema.json` v0.1: SWELL の 2 層判定をモデルに凍結
5. ✅ `package-matrix.json` v0.1: 個人版（記事 CRUD のみ）/ 法人版（構造変更込み）の operation 許可リスト

### 中優先（L3 開始前に確定）

6. `agent-actions.schema.json` v0.1: REST + MCP + WP CLI の操作許可リスト統合
7. `adapter-capability-map.json` v0.1: 3 ティアプラグイン互換戦略
8. `lp-blueprint.schema.json` / `home-blueprint.schema.json` / `blp-blueprint.schema.json`（法人版）

### 低優先（L4 で確定可）

9. ad_tag の 4 種拡張（mercari_card, adsense_in_article, adsense_auto, affiliate_with_amazon_api）
10. reusable-part CPT の service_assoc taxonomy

---

## 10. 結論

Codex 解析の戦略・思想は十分に深い。本レポートで実コード根拠を接続したことで、L2 全体設計が **「実装可能な JSON 契約」** レベルで凍結できる状態になった。

特に重要な認識:
- **SWELL は性能・ブロック・パターン設計の参照源**として優秀（採用 60% / 改善 30% / 見送り 10%）
- **JIN:R は SEO 統合・Hero variant 設計の参照源**として優秀（採用 40% / 改善 50% / 見送り 10%）
- **両テーマの欠点（AI 操作前提なし、計測ID 不在、ad_tag REST 非公開、独自 CSS 重め）を AGENT NEO で改善**
- AGENT NEO の独自価値は **「AI 第一級ユーザー前提」+「dryRun/diff/rollback 必須」+「seo-tool-connector ネイティブ統合」** の 3 点に集中

次は L2 全体設計に進み、上記 Action Items 1〜5 を ADR としてドキュメント化する。

---

**作成**: 2026-04-30 / PM Opus
**入力レポート**: 30, 31, 32, 33（深堀り抽出）+ Codex 1〜29（戦略解析）
