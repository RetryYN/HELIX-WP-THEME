# L3 設計 Addendum A4 — パフォーマンス契約スキーマ

> **対象 GAP**: GAP-RT-021 / GAP-RT-022 / GAP-RT-023 / GAP-RT-024 / GAP-RT-025 / GAP-RT-026
> **カテゴリ**: A-4 パフォーマンス
> **起票日**: 2026-06-18
> **起票者**: L3 drafter (be-logic)
> **振り分け元**: `docs/reviews/L3-real-theme-gap-register.md`
> **設計前提**: FSE + REQ-NF-025（AIロジック完全分離原則）に従い、テーマ側は「計測・契約・予算 enforce」を担い、改善判断は Automation SEO 側に委ねる。

---

## 設計前提の再確認

| 担当境界 | AGENT NEO テーマ側（本 addendum 対象） | Automation SEO 側（対象外） |
|---|---|---|
| critical CSS | **抽出・キャッシュ・インライン化**（CI ツール選定・`inc/assets/critical-css.php` 実装・transient 管理・サイズ予算 enforce をすべて AGENT NEO が担う。ADR-021 の critical ツール選定も AGENT NEO CI の責務。REQ-NF-025: 抽出は決定論的なレンダリング/ビルド処理であり AI 判断ではないため Automation SEO は実行を持たない） | どのページを優先最適化するかの **AI 判断材料の提供のみ**（critical CSS の抽出・キャッシュ・インライン実行は持たない） |
| 3rd party tag | 遅延ロード・同意ゲート・ページ条件制御の enforce | 同意モード設定方針の提案・GA4 設定変更 |
| font policy | family/weight 上限・preconnect/preload の宣言 | フォント品質判定・ブランドへの適合提案 |
| Web Vitals RUM | LCP/INP/CLS の計測・送信（送るところまで） | 実値の蓄積・傾向分析・改善施策の決定 |
| content 遅延 REST | FSE での遅延取得 block 識別・エンドポイント提供 | 遅延コンテンツのリライト・最適化提案 |
| 画像サイズ定義 | `add_image_size` の AGENT NEO 標準サイズ定義 | メディア生成・最適化戦略 |

---

## GAP-RT-021 — critical-css.schema.json の定義

### 背景

解析レポート 15（ページスピード設計比較）において、AGENT NEO の速度設計として `critical-css.schema.json` の必要性が明示されている（§取り込むべき設計抽象）。L2 §8.1 および ADR-006 は「条件付きアセット戦略」を採用と宣言しているが、critical CSS 専用のスキーマ契約が設計に存在しない状態である。

### スキーマ定義案: `critical-css.schema.json`

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://agent-neo.local/schemas/critical-css.schema.json",
  "title": "CriticalCssPolicy",
  "description": "page_type 別 critical CSS 抽出方針・インライン化基準・LCP 予算接続を定義する契約",
  "type": "object",
  "required": ["version", "policies"],
  "properties": {
    "version": {
      "type": "string",
      "description": "スキーマバージョン（semver）",
      "example": "1.0.0"
    },
    "globalInlineSizeKbGzip": {
      "type": "number",
      "description": "全 page_type 共通の critical CSS インラインサイズ上限（gzip 後 KB）。L2 §8.1 基準値は 20KB",
      "default": 20
    },
    "policies": {
      "type": "object",
      "description": "page_type をキーとするポリシーマップ",
      "additionalProperties": false,
      "properties": {
        "article": { "$ref": "#/$defs/PageTypePolicy" },
        "archive": { "$ref": "#/$defs/PageTypePolicy" },
        "home":    { "$ref": "#/$defs/PageTypePolicy" },
        "lp":      { "$ref": "#/$defs/PageTypePolicy" },
        "fixed":   { "$ref": "#/$defs/PageTypePolicy" },
        "search":  { "$ref": "#/$defs/PageTypePolicy" }
      }
    }
  },
  "$defs": {
    "PageTypePolicy": {
      "type": "object",
      "required": ["enabled", "extractionScope", "inlineSizeKbGzip", "lcpBudgetMs"],
      "properties": {
        "enabled": {
          "type": "boolean",
          "description": "この page_type で critical CSS インライン化を有効にするか"
        },
        "extractionScope": {
          "type": "string",
          "enum": ["first_viewport", "above_fold_hero", "lcp_image_context", "custom"],
          "description": "抽出スコープ。first_viewport = 初期ビューポート全体、above_fold_hero = Hero セクションのみ、lcp_image_context = LCP 画像を含むブロックのみ"
        },
        "inlineSizeKbGzip": {
          "type": "number",
          "description": "この page_type 用インラインサイズ上限（gzip 後 KB）",
          "minimum": 1,
          "maximum": 50
        },
        "lcpBudgetMs": {
          "type": "number",
          "description": "LCP 予算（ms）。REQ-NF-001b 基準は 2500ms。LP は 2800ms まで許容（REQ-NF-001f）",
          "minimum": 1000,
          "maximum": 4000
        },
        "fontPreloadScope": {
          "type": "array",
          "items": { "type": "string" },
          "description": "critical CSS とセットで preload する font handle の allowlist"
        },
        "regenerateTrigger": {
          "type": "string",
          "enum": ["theme_switch", "design_token_update", "template_update", "manual"],
          "description": "critical CSS 再生成トリガー条件"
        },
        "cacheStrategy": {
          "type": "object",
          "properties": {
            "transientDays": {
              "type": "integer",
              "description": "transient キャッシュ保持日数。SWELL は 30 日",
              "default": 30
            },
            "invalidateOnCustomizerPreview": {
              "type": "boolean",
              "description": "カスタマイザープレビュー時はキャッシュ無効化",
              "default": true
            }
          }
        }
      }
    }
  }
}
```

### enforce 方式

| 方式 | 実装箇所 | 詳細 |
|---|---|---|
| インライン化 | `inc/assets/critical-css.php` | `wp_head` アクションで page_type を判定し、`<style id="agent-neo-critical">` としてインライン出力 |
| サイズ検証 | CI（`wp agent-neo perf:critical-css-size`） | ビルド時に各 page_type のインライン CSS を gzip 計測し、`inlineSizeKbGzip` 超過時に CI 失敗 |
| キャッシュ | `wp_options.agent_neo_critical_css_{page_type}` | transient 保存、`design_token_update` フック受信時に flush |
| 非同期残余 CSS | `media="print" onload` + `noscript` フォールバック | SWELL `load_style_async` パターンを参照した実装（ADR-006） |

### 計測・送信フロー

```
テーマ起動
  ↓ page_type 判定（is_singular / is_page_template / is_archive）
  ↓ critical-css.schema.json の policy を参照
  ↓ キャッシュ HIT → インライン出力
  ↓ キャッシュ MISS → 抽出 → transient 保存 → インライン出力
  ↓ 残余 CSS は非同期ロード（media="print"）
Automation SEO （read-only）
  ↓ GET /agent-neo/v1/status で critical_css_policy を参照可能
```

### 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 | 測定方法 |
|---|---|---|---|
| TC-CCSS-001 | LP テンプレートを表示 | `<style id="agent-neo-critical">` が `<head>` に存在し、gzip サイズが `inlineSizeKbGzip` 以内 | CI perf:critical-css-size + HTML 検査 |
| TC-CCSS-002 | article テンプレートで `<link>` の残余 CSS を確認 | `media="print"` + `onload` パターンで非同期 | HTML 属性検査 |
| TC-CCSS-003 | design_token_update フック発火後 | transient がクリアされ次回表示で再生成 | `wp_cache_get` 確認テスト |
| TC-CCSS-004 | Lighthouse で LP を計測 | LCP < `lcpBudgetMs` (2800ms) | Lighthouse CI |
| TC-CCSS-005 | `inlineSizeKbGzip` 超過ファイルを注入 | CI が fail を返す | CI ビルドテスト |

### 関連 REQ-NF

- REQ-NF-001 / REQ-NF-001b（Core Web Vitals 必達: LCP < 2.5s）
- REQ-NF-001f（ページタイプ別性能予算: 記事 LCP < 2.0s / LP LCP < 2.8s）
- REQ-F-029（ページタイプ別アセット振り分け機構）

---

## GAP-RT-022 — third-party-tags.schema.json の定義

### 背景

L2 §8.1 は `render blocking third-party: 0` を速度予算として宣言している。一方、GAP-RT-017（任意タグ入力 UI）は UI 側の sanitize/入力機能を扱うが、**読み込みガバナンス（遅延・同意・ページ条件付き制御）** は別契約が必要であり未定義である。Google Consent Mode v2 対応は法令上の要件でもある。

### スキーマ定義案: `third-party-tags.schema.json`

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://agent-neo.local/schemas/third-party-tags.schema.json",
  "title": "ThirdPartyTagPolicy",
  "description": "GA4/GTM/広告/CRM/外部フォームの遅延ロード・同意ゲート・ページ条件付き制御を契約化する。render blocking 3rd party = 0 を保証する",
  "type": "object",
  "required": ["version", "tags"],
  "properties": {
    "version": { "type": "string" },
    "consentModeVersion": {
      "type": "string",
      "enum": ["v1", "v2"],
      "description": "Google Consent Mode バージョン。2024-03 以降の GCP 対象サイトは v2 必須",
      "default": "v2"
    },
    "defaultConsentState": {
      "type": "object",
      "description": "同意バナー表示前のデフォルト同意状態（Consent Mode v2 denied default 推奨）",
      "properties": {
        "analytics_storage": { "type": "string", "enum": ["granted", "denied"], "default": "denied" },
        "ad_storage":        { "type": "string", "enum": ["granted", "denied"], "default": "denied" },
        "ad_user_data":      { "type": "string", "enum": ["granted", "denied"], "default": "denied" },
        "ad_personalization":{ "type": "string", "enum": ["granted", "denied"], "default": "denied" }
      }
    },
    "tags": {
      "type": "array",
      "items": { "$ref": "#/$defs/TagEntry" }
    }
  },
  "$defs": {
    "TagEntry": {
      "type": "object",
      "required": ["tag_id", "category", "loadStrategy"],
      "properties": {
        "tag_id": {
          "type": "string",
          "description": "管理用 ID（例: ga4-main / gtm-head / adsense-auto）"
        },
        "label": { "type": "string", "description": "管理画面表示用ラベル" },
        "category": {
          "type": "string",
          "enum": ["analytics", "advertising", "crm", "form", "chat", "social", "other"],
          "description": "タグカテゴリ（同意ゲート分類に使用）"
        },
        "provider": {
          "type": "string",
          "description": "提供元識別子（例: google / meta / hubspot）"
        },
        "loadStrategy": {
          "type": "string",
          "enum": [
            "async_after_consent",
            "defer_after_idle",
            "lazy_on_interaction",
            "blocked_no_consent",
            "always_async"
          ],
          "description": "読み込み戦略。async_after_consent = 同意後のみ非同期実行。render-blocking は許可しない"
        },
        "consentRequired": {
          "type": "array",
          "items": {
            "type": "string",
            "enum": ["analytics_storage", "ad_storage", "ad_user_data", "ad_personalization"]
          },
          "description": "このタグを発火するために必要な同意フラグ一覧（Consent Mode v2 キー）"
        },
        "pageConditions": {
          "type": "object",
          "description": "ページ条件付き制御。指定なしは全ページ適用",
          "properties": {
            "allowedPageTypes": {
              "type": "array",
              "items": {
                "type": "string",
                "enum": ["article", "archive", "home", "lp", "fixed", "search", "all"]
              }
            },
            "blockedPageTypes": {
              "type": "array",
              "items": { "type": "string" },
              "description": "このページタイプでは絶対に発火しない（例: サンクスページの広告タグ抑止）"
            }
          }
        },
        "renderBlockingGuarantee": {
          "type": "boolean",
          "description": "この tag が render-blocking にならないことの宣言。false の場合は CI 警告対象",
          "default": true
        }
      }
    }
  }
}
```

### enforce 方式

| 方式 | 実装箇所 | 詳細 |
|---|---|---|
| 出力制御 | `inc/assets/third-party-manager.php` | `wp_head` / `wp_footer` で `tag.loadStrategy` と同意状態を照合し、発火可否を決定 |
| 同意バナー連携 | JavaScript `agent_neo_consent.updateConsent(state)` | 同意バナー（外部プラグイン or テーマ内バナー）からコールバックを受け、`gtag('consent', 'update', ...)` を実行 |
| render-blocking チェック | CI `wp agent-neo perf:third-party-audit` | Lighthouse の "Eliminate render-blocking resources" で 3rd party を検出し、0 以外は CI 失敗 |
| ページ条件 | `pageConditions` 照合 | `is_singular()` / `is_page_template()` / `is_archive()` で判定し、`blockedPageTypes` に一致すればタグ出力を完全抑止 |

### 計測・送信フロー

```
訪問者リクエスト
  ↓ テーマが同意状態を確認（Cookie or localStorage）
  ↓ defaultConsentState を gtag consent default として出力（<head> 最上位）
  ↓ 訪問者が同意バナーで選択
  ↓ agent_neo_consent.updateConsent(state) コールバック
  ↓ gtag('consent', 'update', ...) 実行
  ↓ loadStrategy=async_after_consent のタグが発火
  （render-blocking タグは存在しない = ADR-006 に準拠）
```

### 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 | 測定方法 |
|---|---|---|---|
| TC-TPT-001 | GA4 タグを `async_after_consent` で設定し、同意なしで表示 | ネットワークリクエストが GA4 へ飛ばない | ブラウザ DevTools Network + Lighthouse |
| TC-TPT-002 | 同意後に `updateConsent({ analytics_storage: 'granted' })` を呼ぶ | GA4 タグが非同期で発火（render blocking なし） | DevTools Performance タイムライン |
| TC-TPT-003 | Lighthouse を LP テンプレートで実行 | "Eliminate render-blocking resources" に 3rd party が 0 件 | Lighthouse CI |
| TC-TPT-004 | `blockedPageTypes: ["lp"]` のタグを LP で表示 | タグが HTML に出力されない | HTML ソース検査 |
| TC-TPT-005 | Cookie Consent Gate タイムライン（同意バナー表示→スクリプト発火）を計測 | バナー表示から発火まで同期実行なし（GAP-RT-038 TC 候補と連動） | Playwright E2E + DevTools |

### 関連 REQ-NF

- REQ-NF-001（性能: render blocking third-party = 0）
- REQ-NF-004（データ保護: 個人情報を直接収集しない設計基本）
- REQ-NF-009（法令/表示ガード: 外部送信同意）
- REQ-F-029（ページタイプ別アセット振り分け機構）

---

## GAP-RT-023 — font-policy.schema.json の定義

### 背景

L2 §8.1 は「フォント: 2 family / 3 weights 以内」と速度予算を宣言しているが、スキーマ化されていない。SWELL は `inc/customizer/font/` + Fonts API でフォント管理を実装しており（解析レポート 32 参照）、JIN:R は `include/font-selection.php` で preconnect を出力している。AGENT NEO では Google Fonts 選択（日本語/英語）の JSON 定義 + REST 操作を含む完全な契約が必要である。また FOIT/FOUT 制御と CLS 抑制は LCP 予算・CLS 予算に直接影響する。

### スキーマ定義案: `font-policy.schema.json`

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://agent-neo.local/schemas/font-policy.schema.json",
  "title": "FontPolicy",
  "description": "フォント family/weight 上限・preconnect/preload・FOIT/FOUT・CLS 抑制・Google Fonts 選択 UI 用 JSON 定義を契約化する",
  "type": "object",
  "required": ["version", "maxFamilies", "maxWeightsPerFamily", "fonts"],
  "properties": {
    "version": { "type": "string" },
    "maxFamilies": {
      "type": "integer",
      "description": "同時使用 font-family 上限。L2 §8.1 基準は 2",
      "maximum": 4,
      "default": 2
    },
    "maxWeightsPerFamily": {
      "type": "integer",
      "description": "1 family あたりの weight 上限。L2 §8.1 基準は 3",
      "maximum": 5,
      "default": 3
    },
    "displayStrategy": {
      "type": "string",
      "enum": ["swap", "optional", "block", "fallback"],
      "description": "font-display 戦略。FOIT 回避には swap を推奨。CLS リスクが高い場合は optional",
      "default": "swap"
    },
    "clsReserve": {
      "type": "object",
      "description": "フォント読み込み前のスペース確保（CLS 防止）",
      "properties": {
        "enabled": { "type": "boolean", "default": true },
        "sizeAdjustPercent": {
          "type": "number",
          "description": "フォールバックフォントのサイズ調整係数（%）。CSS @font-face size-adjust プロパティへのマッピング用",
          "default": 100
        }
      }
    },
    "fonts": {
      "type": "array",
      "description": "使用フォント定義。maxFamilies を超えるとスキーマ検証エラー",
      "maxItems": 4,
      "items": { "$ref": "#/$defs/FontEntry" }
    },
    "preconnectOrigins": {
      "type": "array",
      "items": { "type": "string", "format": "uri" },
      "description": "自動 preconnect する外部オリジン一覧。Google Fonts は fonts.googleapis.com + fonts.gstatic.com",
      "example": ["https://fonts.googleapis.com", "https://fonts.gstatic.com"]
    }
  },
  "$defs": {
    "FontEntry": {
      "type": "object",
      "required": ["family_id", "source", "role", "weights"],
      "properties": {
        "family_id": {
          "type": "string",
          "description": "管理用 ID（例: body-ja / heading-en）"
        },
        "source": {
          "type": "string",
          "enum": ["google_fonts", "local", "system"],
          "description": "フォント提供元。google_fonts = Google Fonts API 経由"
        },
        "googleFontsName": {
          "type": "string",
          "description": "source=google_fonts 時の Google Fonts ファミリー名（例: Noto+Sans+JP）"
        },
        "googleFontsSubsets": {
          "type": "array",
          "items": { "type": "string", "enum": ["japanese", "latin", "latin-ext", "cyrillic"] },
          "description": "使用サブセット。日本語フォントは japanese を必ず含める"
        },
        "role": {
          "type": "string",
          "enum": ["body", "heading", "code", "accent"],
          "description": "テーマ内での役割"
        },
        "weights": {
          "type": "array",
          "items": { "type": "integer", "enum": [100,200,300,400,500,600,700,800,900] },
          "maxItems": 5,
          "description": "使用 font-weight 一覧。maxWeightsPerFamily を超えるとスキーマ検証エラー"
        },
        "isVariable": {
          "type": "boolean",
          "description": "可変フォントを使用するか。可変フォントは weights 指定不要（wght 軸で制御）",
          "default": false
        },
        "preload": {
          "type": "object",
          "description": "LCP テキストに関わるフォントは preload を宣言する",
          "properties": {
            "enabled": { "type": "boolean", "default": false },
            "scope": {
              "type": "string",
              "enum": ["all_pages", "lp_only", "article_only", "home_only"],
              "description": "preload を適用するページスコープ"
            }
          }
        }
      }
    }
  }
}
```

### Google Fonts 選択 UI の JSON 定義 + REST 操作

フォント選択 UI は管理画面の Design Tokens 設定と連携し、以下の REST エンドポイントで操作する。

| Method | Path | 用途 | 認証 |
|---|---|---|---|
| GET | `/agent-neo/v1/design-tokens/fonts` | 現在の font-policy 取得 | 要（manage_options） |
| POST | `/agent-neo/v1/design-tokens/fonts/apply` | font-policy 更新（dry-run / apply） | 要（manage_options） |
| GET | `/agent-neo/v1/design-tokens/fonts/catalog` | Google Fonts カタログ取得（日本語/英語フィルタ対応） | 要 |

**注記**: `GET /design-tokens/fonts/catalog` は Google Fonts Developer API（`https://www.googleapis.com/webfonts/v1/webfonts`）をサーバーサイドでプロキシし、AGENT NEO が承認した subset のみを返す。APIキーは `wp_options.agent_neo_google_fonts_api_key` に暗号化保存。

### enforce 方式

| 方式 | 実装箇所 | 詳細 |
|---|---|---|
| family 上限 | `inc/assets/font-loader.php` | `fonts` 配列の長さが `maxFamilies` を超えた場合は load を中断し管理画面に警告 |
| preconnect 出力 | `inc/seo/head.php` の `wp_head` Pri: 1 | `preconnectOrigins` を `<link rel="preconnect">` + `<link rel="dns-prefetch">` で出力 |
| preload 出力 | `inc/assets/font-loader.php` | `preload.enabled=true` のフォントを `<link rel="preload" as="font" crossorigin>` で出力 |
| font-display | Google Fonts URL パラメータ | `&display=swap`（またはポリシーに応じた値）を URL に付加 |
| CLS 抑制 | `theme.json` の `typography.fontFamilies` | フォールバックスタックを明示し、`size-adjust` で崩れ幅を最小化 |
| CI バリデーション | `wp agent-neo perf:font-audit` | fonts 配列の weight 数・family 数・preconnect 宣言を検証 |

### 計測・送信フロー

```
管理者が fonts/apply を呼ぶ（または design-tokens/apply でフォント更新）
  ↓ スキーマ検証（maxFamilies / maxWeightsPerFamily チェック）
  ↓ wp_options.agent_neo_font_policy を更新
  ↓ theme.json のフォント設定を更新（CSS 変数反映）
  ↓ critical-css のキャッシュを flush（フォント変更は critical CSS 再生成トリガー）
  ↓ フロント: wp_head で preconnect / preload を出力
  ↓ Google Fonts URL を非同期 CSS で読み込み
Web Vitals RUM（後述 GAP-RT-024）でフォント起因の CLS が検知された場合
  ↓ Automation SEO 側が改善提案を送信（テーマ側は受信・適用のみ）
```

### 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 | 測定方法 |
|---|---|---|---|
| TC-FONT-001 | fonts に 3 family を設定（maxFamilies=2） | スキーマ検証エラーとなり apply が 422 を返す | REST テスト |
| TC-FONT-002 | Google Fonts 日本語フォントを選択して apply | `<link rel="preconnect" href="https://fonts.googleapis.com">` が出力される | HTML head 検査 |
| TC-FONT-003 | preload.enabled=true のフォントを設定 | `<link rel="preload" as="font" crossorigin>` が対象ページで出力される | HTML head 検査 |
| TC-FONT-004 | font-display=swap を設定 | Google Fonts URL に `&display=swap` が含まれる | HTML 検査 |
| TC-FONT-005 | CLS 予算で Lighthouse を実行 | CLS < 0.1（REQ-NF-001b） | Lighthouse CI |
| TC-FONT-006 | design-tokens/apply でフォント更新後に critical CSS キャッシュを確認 | transient がクリアされている | WordPress transient 検査 |

### 関連 REQ-NF

- REQ-NF-001b（CLS < 0.1）
- REQ-NF-001e（外部スクリプトは preconnect 必須）
- REQ-NF-001f（ページタイプ別性能予算）
- REQ-F-009（設定エクスポート/インポート: font policy も対象）

---

## GAP-RT-024 — Web Vitals RUM 送信経路・スキーマの定義

### 背景

解析レポート 15 および 31 は RUM（Real User Monitoring）の必要性を指摘しているが、LCP/INP/CLS の実ユーザー計測値を Automation SEO へ送信する経路とスキーマが L3 設計に存在しない。計測イベント用エンドポイント `POST /tracking/event` は既存であるが（A-007）、Web Vitals 専用のペイロード定義と section_id 別 performance marker の紐付けが欠落している。

### スキーマ定義案: `rum-metric.schema.json`

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://agent-neo.local/schemas/rum-metric.schema.json",
  "title": "RumMetricEvent",
  "description": "Web Vitals RUM 実測値を Automation SEO へ送信するイベントペイロード定義",
  "type": "object",
  "required": ["event_type", "metric_name", "metric_value", "metric_rating", "url", "page_type"],
  "properties": {
    "event_type": {
      "type": "string",
      "const": "web_vitals_rum",
      "description": "/tracking/event の event_type フィールドに使用する固定値"
    },
    "metric_name": {
      "type": "string",
      "enum": ["LCP", "INP", "CLS", "FCP", "TTFB"],
      "description": "Web Vitals メトリクス名。LCP/INP/CLS は Core Web Vitals（必須計測）"
    },
    "metric_value": {
      "type": "number",
      "description": "実測値。LCP/INP/FCP/TTFB は ms 単位、CLS はスコア（無次元）"
    },
    "metric_rating": {
      "type": "string",
      "enum": ["good", "needs-improvement", "poor"],
      "description": "web-vitals ライブラリの rating 判定結果"
    },
    "url": {
      "type": "string",
      "format": "uri",
      "description": "計測対象 URL。生の `location.href` 送信は禁止。テーマ側で必ず scrub 処理を施した URL のみ送信可（scrub ルールは後述「RUM URL scrub ルール」参照）"
    },
    "page_type": {
      "type": "string",
      "enum": ["article", "archive", "home", "lp", "fixed", "search", "unknown"],
      "description": "ページタイプ（テーマ側で付与）"
    },
    "section_id": {
      "type": "string",
      "description": "LCP/CLS 起因セクションが特定できる場合に付与する section_id。解析レポート 15 §法人版速度要件に基づく",
      "nullable": true
    },
    "lcp_element": {
      "type": "string",
      "description": "LCP メトリクス時: LCP 要素の CSS セレクタまたはブロック block_id",
      "nullable": true
    },
    "device_category": {
      "type": "string",
      "enum": ["mobile", "tablet", "desktop"],
      "description": "デバイスカテゴリ（UserAgent から判定）"
    },
    "connection_type": {
      "type": "string",
      "enum": ["4g", "3g", "2g", "slow-2g", "unknown"],
      "description": "通信種別（Network Information API から取得。未対応ブラウザは unknown）"
    },
    "page_load_id": {
      "type": "string",
      "description": "同一ページ読み込みセッションの一意 ID（複数メトリクスの紐付け用）",
      "format": "uuid"
    },
    "agent_neo_version": {
      "type": "string",
      "description": "AGENT NEO テーマバージョン（デバッグ用）"
    }
  }
}
```

### RUM URL scrub ルール（TL [P2] 指摘対応 / REQ-NF-004 最小データ原則）

RUM payload の `url` フィールドに生の `location.href` をそのまま送信することを**禁止する**。`location.href` には reset/preview token・メールアドレス・広告クリック ID（gclid / fbclid 等）・その他 PII を含む query 文字列が混入する可能性があり、Automation SEO への転送は REQ-NF-004（最小データ原則）違反に相当する。

`url` フィールドに設定する値は以下の優先順位で決定する:

1. **canonical URL 優先**: PHP 側（`wp_head` の `rel="canonical"` 出力と同じ値）を `wp_localize_script` 経由で `agentNeoData.canonicalUrl` に注入する。この値が存在する場合はそのまま使用する（query なし・正規化済みパス）。
2. **フォールバック scrub**: `canonicalUrl` が存在しない場合は `location.href` を以下のルールで scrub する。
   - query 文字列を全除去する（デフォルト動作）。
   - 許可リスト（`utm_source` / `utm_medium` / `utm_campaign` / `utm_content` / `utm_term`）に含まれるパラメータのみを再付与する。
   - PII パターン（`token` / `email` / `password` / `gclid` / `fbclid` / `msclkid` 等の広告クリック ID）は許可リスト通過後も除去する。

この scrub ルールは `rum-metric.schema.json` の `url` フィールド `description` にも反映済みである。

### 送信経路設計

RUM 送信は既存の `POST /tracking/event`（A-007）エンドポイントを使用する。専用エンドポイントは新設しない（インターフェース増加を避けるため）。

```
ブラウザ（訪問者側）
  ↓ web-vitals ライブラリ（onLCP / onINP / onCLS コールバック）
  ↓ rum-metric.schema.json に準拠したペイロードを構築
  ↓ navigator.sendBeacon('/wp-json/agent-neo/v1/tracking/event', payload)
      （同期ブロックなし / ページアンロード時も確実に送信）
AGENT NEO Core Plugin
  ↓ POST /tracking/event ハンドラが event_type="web_vitals_rum" を検知
  ↓ seo-tool-connector 経由で Automation SEO へ転送
Automation SEO（read-only 受信）
  ↓ LCP/INP/CLS 実値を蓄積・傾向分析・改善提案の生成（テーマ側は関与しない）
```

### JavaScript 実装仕様（テーマ側）

```javascript
// inc/assets/web-vitals-rum.js（フロント JS: article/lp/home のみ条件付きロード）
import { onLCP, onINP, onCLS } from 'web-vitals';

/**
 * RUM 送信用 URL を scrub する。
 * - 生の location.href（query 文字列付き）の送信は REQ-NF-004 違反のため禁止。
 * - canonical URL（agentNeoData.canonicalUrl）が利用可能な場合はそちらを優先する。
 * - canonical URL がない場合は location.href から query を全除去し、
 *   utm_source / utm_medium / utm_campaign / utm_content / utm_term のみ
 *   許可リストとして再付与する（PII・token・広告クリック ID は常に除去）。
 * @returns {string} scrub 済み URI（クエリなし、または utm_ 許可パラメータのみ付与）
 */
function scrubRumUrl() {
  // PHP 側が wp_localize_script で注入した canonical URL を最優先とする
  if (agentNeoData.canonicalUrl) {
    return agentNeoData.canonicalUrl;
  }
  const ALLOWED_QUERY_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
  const u = new URL(location.href);
  const allowed = new URLSearchParams();
  for (const key of ALLOWED_QUERY_PARAMS) {
    if (u.searchParams.has(key)) {
      allowed.set(key, u.searchParams.get(key));
    }
  }
  const qs = allowed.toString();
  return u.origin + u.pathname + (qs ? '?' + qs : '');
}

function sendVitals(metric) {
  const payload = {
    event_type: 'web_vitals_rum',
    metric_name: metric.name,
    metric_value: metric.value,
    metric_rating: metric.rating,
    url: scrubRumUrl(),             // scrub 済み URL（生の location.href 禁止）
    page_type: agentNeoData.pageType,   // wp_localize_script でテーマから注入
    section_id: agentNeoData.lcpSectionId ?? null,
    lcp_element: metric.entries?.[0]?.element?.id ?? null,
    device_category: detectDevice(),
    connection_type: navigator.connection?.effectiveType ?? 'unknown',
    page_load_id: agentNeoData.pageLoadId,  // uuid v4 を PHP 側で生成
    agent_neo_version: agentNeoData.version
  };
  navigator.sendBeacon(
    agentNeoData.trackingEndpoint,  // /wp-json/agent-neo/v1/tracking/event
    JSON.stringify(payload)
  );
}

onLCP(sendVitals);
onINP(sendVitals);
onCLS(sendVitals);
```

**ロード条件**: `block.json` の `agentNeo.allowedPageTypes` に `["article", "lp", "home", "archive", "fixed"]` を指定し、管理画面・ログインページ・プレビューページではロードしない。

### section_id 別 performance marker の紐付け

```html
<!-- テーマ側で各 FSE ブロックに data 属性を付与 -->
<section data-agent-section-id="hero-001" data-agent-block-type="hero">
  <img src="hero.webp" fetchpriority="high" loading="eager" />
</section>
```

LCP 要素の `closest('[data-agent-section-id]')` で section_id を取得し、`rum-metric.section_id` フィールドに付与する。

### 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 | 測定方法 |
|---|---|---|---|
| TC-RUM-001 | 記事ページを読み込む | 5 秒以内に `event_type=web_vitals_rum` の POST が `/tracking/event` に届く | Network DevTools + サーバーログ |
| TC-RUM-002 | payload を rum-metric.schema.json で検証 | バリデーションエラーなし | schema validation test |
| TC-RUM-003 | ページアンロード時（back ボタン）のイベント | `sendBeacon` で確実に送信される | Playwright E2E |
| TC-RUM-004 | 管理画面でページを読み込む | RUM イベントが送信されない | サーバーログ検査 |
| TC-RUM-005 | section_id 付きセクションが LCP の場合 | `section_id` フィールドに正しい値が入る | Network payload 検査 |
| TC-RUM-006 | CrUX（Field Data）との乖離確認 | PageSpeed Insights の Field Data と RUM 実測値の傾向が一致する | 手動確認（Acceptance TC） |

### 関連 REQ-NF

- REQ-NF-001b（Core Web Vitals: LCP/INP/CLS 必達を実ユーザー値で検証）
- REQ-NF-007（可観測性: 計測イベントをログ化）
- REQ-F-006（計測/A-B/CTA: section_id 単位の計測基盤）
- REQ-F-029（ページタイプ別アセット: web-vitals JS は条件付きロード）

---

## GAP-RT-025 — コンテンツ遅延 REST の仕様定義

### 背景

SWELL は `rest-api/lazyload.php` で after_article / footer エリアのコンテンツを遅延取得している（解析レポート 15 参照）。AGENT NEO は FSE（Block Theme）であるため、SWELL のような PHP テンプレートベースの遅延ロードは直接移植できない。FSE で同等の「必要箇所のみ遅延取得」を実現するための REST エンドポイントとブロック識別フラグの設計が必要である。

### 設計方針

FSE での遅延取得は「クライアントサイド JS が REST で遅延コンテンツを取得し、Intersection Observer でビューポート進入時に差し込む」方式を採用する。

```
初期 HTML（サーバーレンダリング）
  ↓ 遅延 block は <div data-agent-lazy-block="true" data-block-id="{block_id}"> プレースホルダー のみ出力
  ↓ Intersection Observer で scroll 進入を検知
  ↓ GET /agent-neo/v1/blocks/lazy/{block_id} を fetch
  ↓ 返された HTML をプレースホルダーと差し替え
```

### REST エンドポイント仕様

**新規エンドポイント追加**: `GET /agent-neo/v1/blocks/lazy/{block_id}`

| 項目 | 値 |
|---|---|
| Method | GET |
| Path | `/wp-json/agent-neo/v1/blocks/lazy/{block_id}` |
| 認証 | 原則不要（公開コンテンツのみ）。`context=edit` の場合は WordPress 認証 nonce 必須 |
| キャッシュ | `Cache-Control: public, max-age=300`（公開コンテンツ）/ `Cache-Control: private, no-store`（edit コンテキスト） |
| 対応 REQ-F | REQ-F-029 / REQ-NF-001 / REQ-NF-004 |

> **セキュリティ要件（TL [P1] 指摘対応）**: このエンドポイントは未認証でアクセス可能なため、ハンドラ側で以下の 3 段階の認可検証を必ず実施しなければならない。
>
> 1. **親投稿の可視性検証**: `block_id` から親投稿を特定し、その `post_status` が `publish` かつパスワード保護なし（`post_password` が空）であることを確認する。`draft` / `private` / `future`（未来日予約）/ `pending` / `trash` の場合は未認証アクセスを **404** で拒否する。`private` 投稿や `password` 保護投稿は、適切な capability（`read_private_posts` / パスワード認証済みセッション）を持つ認証ユーザーのみ許可する。
> 2. **block_id の allowlist 検証**: `block_id` が「遅延取得を許可されたブロックタイプ（`agentNeo.lazyLoadDefault=true` の block.json を持つブロック）」から生成された ID であることを検証する。任意の block_id による無差別 render を防ぐため、ハンドラは登録済み allowlist と照合し、未登録の block_id は **403** を返す。
> 3. **edit コンテキストの認証要求**: `context=edit` でリクエストされた場合は WordPress nonce 検証を必須とし、`edit_posts` capability を持つ認証ユーザーのみ許可する。
>
> **注記**: §8.4 の snapshot allowlist テスト（TC-SNAP-*）はこのエンドポイントを対象としない。本エンドポイントの認可検証は下記 TC-LAZY-006〜008 で別軸テストを実施する。

**リクエスト**

| パラメータ | 型 | 説明 |
|---|---|---|
| `block_id` | string | path parameter。遅延取得するブロックの安定 ID（allowlist 照合あり） |
| `context` | string（query） | `view`（デフォルト）または `edit`（edit は認証必須） |
| `post_id` | integer（query） | 親投稿の ID。パーソナライズコンテンツ生成に使用（任意）。指定時は可視性検証を `post_id` の投稿に対しても実施 |

**レスポンス**

```json
{
  "block_id": "related-posts-footer-001",
  "html": "<div class=\"wp-block-agent-neo-related-posts\">...</div>",
  "cache_ttl": 300,
  "block_type": "agent-neo/related-posts"
}
```

### block.json での遅延フラグ宣言

```json
{
  "name": "agent-neo/related-posts",
  "attributes": {
    "lazyLoad": {
      "type": "boolean",
      "default": true,
      "description": "遅延読み込みを有効にする（初期 HTML にはプレースホルダーのみ出力）"
    },
    "intersectionRootMargin": {
      "type": "string",
      "default": "200px",
      "description": "Intersection Observer の rootMargin"
    }
  }
}
```

**遅延対象ブロック候補**（block.json の `agentNeo.lazyLoadDefault` で宣言）

| ブロック | 理由 |
|---|---|
| `agent-neo/related-posts` | 記事下 / 初期表示外 |
| `agent-neo/after-article-ad` | after_article 広告（SWELL パターン相当） |
| `agent-neo/sns-feed-widget` | 外部 API 依存。初期描画をブロックしない |
| `agent-neo/comment-list` | コメント数が多いサイトで有効 |

### enforce 方式

| 方式 | 実装箇所 | 詳細 |
|---|---|---|
| server-side render | `inc/blocks/lazy-block-renderer.php` | `lazyLoad=true` のブロックは初期 HTML でプレースホルダーのみ出力。実 HTML は REST 経由 |
| クライアント JS | `inc/assets/lazy-block-loader.js` | Intersection Observer で遅延取得。web-vitals-rum と同一 JS バンドル内に含める |
| キャッシュ | `GET /blocks/lazy/{block_id}` の HTTP Cache-Control | CDN / ブラウザキャッシュを活用。個人化コンテンツは `Cache-Control: private` |

### 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 | 測定方法 |
|---|---|---|---|
| TC-LAZY-001 | lazyLoad=true の related-posts ブロックを含む記事を表示 | 初期 HTML にプレースホルダーのみ存在（実 HTML なし） | HTML ソース検査 |
| TC-LAZY-002 | スクロールで related-posts がビューポートに入る | GET /blocks/lazy/{block_id} リクエストが発生し HTML が差し込まれる | Network DevTools |
| TC-LAZY-003 | Lighthouse（モバイル）で記事ページを計測 | LCP が遅延ブロックに起因しない（LCP 要素は hero/above-fold のみ） | Lighthouse CI |
| TC-LAZY-004 | `GET /blocks/lazy/{block_id}` にキャッシュヘッダーを確認 | `Cache-Control: public, max-age=300` が返る（公開コンテンツ） | HTTP ヘッダー検査 |
| TC-LAZY-005 | block_id が不正な値の場合 | 404 を返す | REST テスト |
| TC-LAZY-006 | `draft` 状態の親投稿に属する block_id を未認証でリクエスト | **404** を返す（非公開コンテンツのバイパス防止） | REST テスト |
| TC-LAZY-007 | `private` 状態の親投稿に属する block_id を未認証でリクエスト | **404** を返す | REST テスト |
| TC-LAZY-008 | allowlist に存在しない block_id を未認証でリクエスト | **403** を返す（無差別 render 防止） | REST テスト |

### 関連 REQ-NF

- REQ-NF-001（性能: 不要 JS・CSS の抑制）
- REQ-NF-001b（LCP < 2.0s: 記事）
- REQ-F-029（ページタイプ別アセット振り分け）
- REQ-NF-025（AI ロジック完全分離: 遅延判断は Automation SEO 側、取得 API のみ AGENT NEO 側）

---

## GAP-RT-026 — カスタム画像サイズ定義

### 背景

解析レポート 32（SWELL アセットパイプライン）は、SWELL が `add_image_size` で追加した固有サイズを block.json / media エンドポイントで活用していることを示している。AGENT NEO が `block.json` / `POST /media/upload`（REQ-F-017）で一貫した画像サイズを使用するには、テーマ固有サイズの定義が L3 で必要である。

### 標準画像サイズ定義

AGENT NEO は WordPress 標準サイズ（thumbnail/medium/large）に加え、以下の固有サイズを `add_image_size` で登録する。

| サイズ名 | 幅 (px) | 高さ (px) | クロップ | 用途 | 対応ブロック |
|---|---:|---:|---|---|---|
| `an-card-s` | 360 | 240 | true（中央） | 記事カード（モバイル）/ アーカイブ | post-list, blog-card |
| `an-card-m` | 720 | 480 | true（中央） | 記事カード（タブレット〜 PC）| post-list, blog-card |
| `an-hero-pc` | 1920 | 1080 | false | LP/HP Hero（PC） | hero, home-hero |
| `an-hero-sp` | 768 | 576 | false | LP/HP Hero（スマートフォン） | hero, home-hero |
| `an-ogp` | 1200 | 630 | true（中央） | OGP / SNS シェア用 | SEO Core（OGP 出力） |
| `an-affiliate-thumb` | 240 | 240 | true（中央） | アフィリエイト商品カード | ad-tag, affiliate-cta |
| `an-author-avatar` | 96 | 96 | true（中央） | 著者アバター / E-E-A-T 表示 | author-box |

**実装場所**: `inc/setup/image-sizes.php`

```php
// inc/setup/image-sizes.php
function agent_neo_register_image_sizes(): void {
    add_image_size( 'an-card-s',          360,  240, true );
    add_image_size( 'an-card-m',          720,  480, true );
    add_image_size( 'an-hero-pc',        1920, 1080, false );
    add_image_size( 'an-hero-sp',         768,  576, false );
    add_image_size( 'an-ogp',            1200,  630, true );
    add_image_size( 'an-affiliate-thumb', 240,  240, true );
    add_image_size( 'an-author-avatar',    96,   96, true );
}
add_action( 'after_setup_theme', 'agent_neo_register_image_sizes' );
```

### `asset-policy.schema.json` との接続

各サイズは `asset-policy.schema.json` の `imageSizes` セクションに宣言し、block.json の `agentNeo.preferredImageSize` でブロックが参照できるようにする。

```json
{
  "imageSizes": {
    "an-card-s":          { "width": 360,  "height": 240,  "crop": true,  "role": "card_mobile" },
    "an-card-m":          { "width": 720,  "height": 480,  "crop": true,  "role": "card_desktop" },
    "an-hero-pc":         { "width": 1920, "height": 1080, "crop": false, "role": "hero_pc" },
    "an-hero-sp":         { "width": 768,  "height": 576,  "crop": false, "role": "hero_sp" },
    "an-ogp":             { "width": 1200, "height": 630,  "crop": true,  "role": "ogp" },
    "an-affiliate-thumb": { "width": 240,  "height": 240,  "crop": true,  "role": "affiliate" },
    "an-author-avatar":   { "width": 96,   "height": 96,   "crop": true,  "role": "avatar" }
  }
}
```

### `POST /media/upload` との接続

`POST /agent-neo/v1/media/upload`（REQ-F-017）のレスポンスに `sizes` フィールドを含め、各 AGENT NEO サイズの生成状況を返す。

```json
{
  "media_id": 1234,
  "sizes": {
    "an-card-s":   { "url": "...", "width": 360, "height": 240, "webp_generated": true },
    "an-card-m":   { "url": "...", "width": 720, "height": 480, "webp_generated": true },
    "an-ogp":      { "url": "...", "width": 1200, "height": 630, "webp_generated": true }
  }
}
```

### CLS 防止のための固定比率宣言

```html
<!-- ブロック出力時: CSS aspect-ratio を利用して CLS を防止 -->
<figure class="wp-block-agent-neo-post-list__card-image"
        style="aspect-ratio: 3/2;">
  <img src="card-image-720x480.webp"
       srcset="card-image-360x240.webp 360w, card-image-720x480.webp 720w"
       sizes="(max-width: 768px) 360px, 720px"
       width="720" height="480"
       loading="lazy" decoding="async"
       alt="..." />
</figure>
```

Hero 画像は `loading="eager" fetchpriority="high"` を付与し、LCP 対象画像として明示する。

### 受入条件（TC 候補）

| TC-ID | 条件 | 期待結果 | 測定方法 |
|---|---|---|---|
| TC-IMG-001 | JPEG をアップロード | `an-card-s` / `an-card-m` / `an-ogp` の WebP が生成される（REQ-F-017 と連動） | メディアライブラリ + ファイルシステム確認 |
| TC-IMG-002 | post-list ブロックを含む記事を表示 | `srcset` に `an-card-s` と `an-card-m` の両 WebP URL が含まれる | HTML 検査 |
| TC-IMG-003 | hero ブロックの Hero 画像を確認 | `loading="eager"` + `fetchpriority="high"` が付与されている | HTML 検査 |
| TC-IMG-004 | CLS を Lighthouse で計測 | CLS < 0.1（画像スペース確保ができているか） | Lighthouse CI |
| TC-IMG-005 | `POST /media/upload` レスポンスに sizes が含まれるか | `an-card-s` / `an-ogp` 等のキーが存在し webp_generated=true | REST テスト |
| TC-IMG-006 | 5MB 超の画像をアップロード | Action Scheduler でバックグラウンド処理（REQ-F-017 ACC-017c と連動） | ジョブキュー + 5 分以内の生成確認 |

### 関連 REQ-NF

- REQ-NF-001b（CLS < 0.1）
- REQ-NF-001d（画像メディアポリシー: WebP 自動生成 + `<picture>` 要素配信）
- REQ-F-017（画像変換パイプライン）
- REQ-F-029（ページタイプ別アセット振り分け: 画像サイズも page_type に応じて切り替え）

---

## L4 carry エントリ一覧

> L4 実装着手前に解消・確認が必要な項目を以下に整理する。

| CARRY-ID | 対象 GAP | 優先度 | 内容 | 解消条件 |
|---|---|---|---|---|
| PERF-CARRY-001 | GAP-RT-021 | P1 | critical CSS 抽出ツールの選定（Penthouse / critical / inline-critical のいずれか、または PHP 側 DOM 解析）が未決定。L4 実装前に ADR へ追記が必要 | ADR-006 への補記 or 新規 ADR-020 として採番 |
| PERF-CARRY-002 | GAP-RT-022 | P1 | Cookie Consent バナー（外部プラグイン対応 vs テーマ内蔵）の選定が未決定。外部プラグイン（CookieYes / Complianz 等）との連携方式を ADR に明記する必要がある | ADR への同意バナー連携方針の追記（PO 確認が必要な可能性あり） |
| PERF-CARRY-003 | GAP-RT-023 | P2 | Google Fonts Developer API キーの管理方式（本番: wp_options 暗号化 / 開発: 環境変数）をシークレット管理方針に追記する必要がある | 既存の認証情報管理設計（REQ-NF-002 / §7.2）への照合 |
| PERF-CARRY-004 | GAP-RT-024 | P2 | `web-vitals` npm パッケージのバージョン固定方針（v3 / v4 の API 差異に注意）が未確定。ADR-034 候補として挙がっている OSS 採用記録 GAP-RT-033/034 と連動して確認する | OSS バージョン ADR（GAP-RT-033〜035 の対象）への記載 |
| PERF-CARRY-005 | GAP-RT-025 | P2 | 遅延ブロックのサーバーキャッシュ（Object Cache / Redis）との連携設計が未確定。ホスティング環境（D8: キャッシュ/高速化系プラグイン）との共存方針を L4 前に確認する | L2 §2.5 D8「任意共存」の実装レベル定義 |
| PERF-CARRY-006 | GAP-RT-026 | P3 | `an-hero-pc` (1920×1080) の WebP 生成が 5MB 超のケースでバックグラウンド処理となる想定だが、Action Scheduler のジョブ設計（REQ-F-017）との具体的な接続仕様が L4 で詳細化が必要 | REQ-F-017 ACC-017c の実装仕様との整合確認 |

---

## 節構成サマリ

| 節 | 対象 GAP-RT | 重大度 | スキーマファイル名 | 新規 REST EP |
|---|---|---|---|---|
| §1 critical-css | GAP-RT-021 | 高 | `critical-css.schema.json` | なし（CLI コマンドのみ） |
| §2 third-party-tags | GAP-RT-022 | 高 | `third-party-tags.schema.json` | なし（既存 tracking 契約に組み込み） |
| §3 font-policy | GAP-RT-023 | 中 | `font-policy.schema.json` | GET/POST `/design-tokens/fonts`、GET `/design-tokens/fonts/catalog` |
| §4 Web Vitals RUM | GAP-RT-024 | 高 | `rum-metric.schema.json` | なし（既存 POST `/tracking/event` を使用） |
| §5 content 遅延 REST | GAP-RT-025 | 中 | なし（block.json 属性拡張） | GET `/blocks/lazy/{block_id}` |
| §6 カスタム画像サイズ | GAP-RT-026 | 中 | `asset-policy.schema.json` への追記 | なし（POST `/media/upload` レスポンス拡張） |

*作成: 2026-06-18 / 担当: L3 drafter (be-logic) / 次アクション: PERF-CARRY-001/002 の ADR 補記 (P1) → L4 実装着手*

---

## 2026-06-20 追記: 性能 enforce 機構の補強（GAP-RT-056 方針）

> **出典**: `docs/research/wp-ecosystem-20260620.md` §性能  
> **位置づけ**: 「無駄JS禁止」第一原理（REQ-NF-001a / ADR-006）を **enforce 可能** にする具体機構。具体閾値・実装詳細は L4 carry（GAP-RT-056 / PERF-CARRY-007〜009）。

### § 0 iframe payload JS の page_type 性能予算カウント（round16 P2 対応 / ADR-026 連動）

`agent-neo/embed` ブロック（GAP-RT-058 / ADR-026）の **mode=interactive** は、Automation SEO が専用サブドメイン（sandbox-origin）でホスト・配信する別オリジン sandbox iframe を用いる。この方式に関して、性能予算上の以下の原則を本 addendum に明示する。

| 原則 | 内容 |
|---|---|
| **iframe payload JS は page_type 予算にカウントする** | mode=interactive の iframe が存在するページは、当該 iframe がロードする JS（sandbox-origin から配信される payload JS）を当該ページの JS 予算消費として扱う。CI の初期 JS 計測から除外しない。 |
| **別スレッド保証には依拠しない** | 別オリジン sandbox iframe はメインスレッドを共有し得る（ブラウザが別スレッドに分離する保証はない）。iframe payload JS に起因する INP / Long Task の懸念はメインスレッドの実測（RUM: web_vitals_rum / INP 計測）で担保する。「別オリジンだから安全」という前提で性能予算を免除しない。 |
| **CI 計測対象** | page_type 別 JS 予算の CI 検証（PERF-CARRY-007）において、interactive ブロックを含むテンプレートは iframe payload JS のサイズ（gzip 後 KB）を合算して評価する。sandbox-origin 配信分を CI から除外するホワイトリスト処理は禁止。 |
| **standalone / 個人版の適用除外** | standalone・個人版では interactive は提供不可（Automation SEO 契約必須）のため、当該プランでは mode=static のみとなり iframe payload JS は発生しない（ADR-026 §mode 選択条件 参照）。 |

> **受入観点（L4）**: interactive ブロックを含む article / lp テンプレートで Lighthouse + RUM（INP 計測）を実行し、iframe payload JS を含む合計 JS バジェットが REQ-NF-001f の page_type 別閾値を超えないことを CI で検証する。

### § A ページ別アセット分離 enforce（REQ-NF-001a 系）

WordPress 6.3+ 以降、`should_load_separate_core_block_assets` フィルタにより **ページ上に存在する core ブロックの CSS のみ** を per-block に分離してロードできる。本フィルタは **core ブロック CSS の分離のみ** を制御するものであり、JS の条件ロード・抑制はしない点に注意する。AGENT NEO はこれを page-type 別アセット分離の enforce 手段として採用する方針とする。

> **役割分担**: CSS の per-block 分離は `should_load_separate_core_block_assets` フィルタで担保する。**JS の条件ロードは別機構**（block.json の `viewScript` フィールドを使い、ブロックが実際にレンダリングされたページでのみ enqueue されるよう per-block enqueue/dequeue を設計する。不要な view script は `wp_dequeue_script` で除外する）で対応する。具体的な per-block enqueue 実装は L4 carry（PERF-CARRY-007）。

| enforce 項目 | 方針 | 実装担当 | L4 carry |
|---|---|---|---|
| ブロック別 CSS 分離 | `should_load_separate_core_block_assets` を `true` として有効化し、ページ上に存在する core ブロックの CSS のみ per-block で分離ロード（**JS 分離はしない**） | `inc/assets/asset-loader.php` | PERF-CARRY-007 |
| page-type 別 JS 分離 | block.json の `viewScript` を活用してブロックが存在するページにのみ view script を enqueue。不要な JS は `wp_dequeue_script` で排除。`critical-css.schema.json` の `policies`（page_type）と連動 | 同上 | PERF-CARRY-007 |
| 不要コアブロック CSS の無効化 | AGENT NEO ブロックに含まれないコアブロック CSS ハンドルを `wp_dequeue_style` で除外する許可リストを管理 | `inc/setup/dequeue-policy.php` | PERF-CARRY-007 |

**受入観点（L4）**: page_type=lp テンプレートで初期 CSS が `<=20KB`（gzip）かつ、記事テンプレートに存在しないブロックの CSS がロードされないことを CI で検証する。具体閾値は REQ-NF-001f を参照し、L4 CI Sprint で確定する。

### § B Speculative Loading（投機的プリフェッチ / WP 6.8+）

WordPress 6.8+ の Speculation Rules API に対応した Speculative Loading が標準装備される（出典: `docs/research/wp-ecosystem-20260620.md`）。AGENT NEO は以下の方針で採用する。

| 方針 | 内容 |
|---|---|
| 採用意思 | **採用する**（訪問者のページ遷移を LCP 改善に直結させる） |
| スコープ | 記事一覧 → 記事詳細 / LP の CTA ランディング先 を投機的プリフェッチ対象とする |
| sensitive URL の明示除外 | 管理画面（`/wp-admin/`）・プレビュー（`?preview=true` 等）・ログイン（`/wp-login.php`）・認証必須パスは Speculation Rules から **明示除外**する。`eagerness: moderate` は投機の **速度を下げるだけで opt-out にはならない**（moderate でも prefetch が走る）。除外は **`where` 条件の否定ルール**（例: `"where": { "not": { "href_matches": "/wp-admin/*" } }` のような除外ルール）で表現する。**`eagerness: never` は Speculation Rules の有効値ではなく（有効値は `conservative` / `moderate` / `eager` / `immediate` のみ）、ブラウザに無視されるため除外手段にならない**。具体的なルール JSON は L4 carry（PERF-CARRY-008）。|
| 具体実装 | `wp_get_speculation_rules()` API 利用 or JSON 直出力の選択は L4 で確定 |

**受入観点（L4）**: 記事一覧ページで DevTools の "Speculative loads" パネルに記事詳細 URL がプリフェッチ候補として登録されることを Playwright で確認する。

### § C Font Library（ローカルフォント配信 / WP 6.5+）

WordPress 6.5+ で Font Library が標準装備され、Google Fonts CDN を経由せずにフォントをローカル配信できる（出典: `docs/research/wp-ecosystem-20260620.md`）。GAP-RT-023 の `font-policy.schema.json` と組み合わせ、以下の方針とする。

| 方針 | 内容 |
|---|---|
| 採用意思 | **条件付き採用**。日本語フォント（重量が大きく CDN 遅延が効きやすい）はローカル配信を優先する |
| Google Fonts CDN との使い分け | `font-policy.schema.json` の `source` フィールドで `"local"` を選択した場合は Font Library 経由でローカル配信。`"google_fonts"` は CDN 経由（preconnect 必須）|
| AVIF との組み合わせ | AVIF 画像対応（WP 6.5+ / GAP-RT-026 対応済み）と合わせ、フォント + 画像の両面で外部 CDN 依存を最小化する |
| preconnect 残存条件 | `source=google_fonts` のフォントが 1 件以上存在する場合のみ `preconnectOrigins` に `fonts.googleapis.com` / `fonts.gstatic.com` を出力し、不要時は出力しない |

**受入観点（L4）**: source=local フォント選択時に `<link rel="preconnect" href="https://fonts.googleapis.com">` が HTML に出力されないことを単体テストで検証する。具体的なフォントバンドルサイズ上限は L4 fonts Sprint で確定する。

### 節構成サマリ更新（§ A〜C 追記分）

| 節 | 対象 | 機構 | L4 carry |
|---|---|---|---|
| § A ページ別アセット分離 | REQ-NF-001a / ADR-006 | `should_load_separate_core_block_assets` + dequeue 許可リスト | PERF-CARRY-007 |
| § B Speculative Loading | REQ-NF-001 / LCP 改善 | `wp_get_speculation_rules()` / Speculation Rules API | PERF-CARRY-008 |
| § C Font Library | GAP-RT-023 / font-policy.schema.json | WP 6.5+ Font Library ローカル配信 | PERF-CARRY-009 |

---

## L4 carry エントリ追加（2026-06-20）

以下を既存の PERF-CARRY 一覧に追加する。

| CARRY-ID | 対象 | 優先度 | 内容 | 解消条件 |
|---|---|---|---|---|
| PERF-CARRY-007 | GAP-RT-056（§ A） | P2 | `should_load_separate_core_block_assets` 有効化 + dequeue 許可リスト実装 + CI 閾値確定（page_type=lp の初期 CSS ≤ 20KB gzip） | L4 asset Sprint 着手前に閾値確定 |
| PERF-CARRY-008 | GAP-RT-056（§ B） | P2 | Speculative Loading 採用 + `wp_get_speculation_rules()` vs JSON 直出力 選択 + `where` 否定ルールによる sensitive URL 除外実装（`eagerness: never` は無効値のため使用しない） | L4 performance Sprint で実装 |
| PERF-CARRY-009 | GAP-RT-056（§ C） | P2 | Font Library ローカル配信採用 + `source=local` 時の preconnect 非出力 TC + フォントバンドルサイズ上限確定 | L4 fonts Sprint（PERF-CARRY-003 と並行）|
