これは Haiku 調査の生資料。誤りを含み docs/reviews/WP7-THEME-COMPLETENESS-AUDIT.md が正本(§是正参照)

# WordPress 7.0 FSE テーマ 完全対応要件チェックリスト

**対象プロジェクト**: AGENT-NEO（WP7.0 カスタム FSE テーマ）  
**調査完了日**: 2026-06-21  
**出典**: WP公式ハンドブック + AGENT-NEO メモリ + 実WP検証

**凡例**:
- 🔴 **Critical** — 実装前の絶対要件（wp.org Theme Review必須または機能不全）
- 🟡 **Important** — ローンチ前に確認（品質/運用/a11y）
- 🟢 **Nice-to-have** — 運用後推奨（拡張機能）
- ❌ **Not Started** / ⚠️ **In Progress** / ✅ **Complete**

---

## セクション 1: 必須ファイル/ディレクトリ構成

### 1.1 ルートレベル必須ファイル

| ファイル | 優先度 | WP7.0で変更 | 説明 | チェック |
|---------|--------|-----------|------|---------|
| `style.css` | 🔴 Critical | No | テーマヘッダメタデータ（Theme Name / URI / Description / Author / License等） | ❌ |
| `theme.json` | 🔴 Critical | **Yes** | FSEテーマの設定基盤。v3必須（`$schema: https://schemas.wp.org/wp/7.0/theme.json`） | ❌ |
| `templates/index.html` | 🔴 Critical | No | 全ページのfallbackテンプレート | ❌ |
| `functions.php` | 🟢 Nice-to-have | Yes | テーマ初期化（WP7.0では必須でない。AGENT-NEO非採用予定 / ADR-024） | ❌ |
| `screenshot.png` | 🟡 Important | No | wp.org提出時必須。サイズ: 1200x900px / PNG形式 | ❌ |
| `readme.txt` | 🟡 Important | No | テーマ説明文 / Changelog / Install / License セクション | ❌ |
| `license.txt` | 🟡 Important | No | GPL v2ライセンス本文 | ❌ |

**出典**: `project_agent_neo_theme.md §2026-06-20(part2)` / WP Handbook Block Themes

**実装上の注意**:
- style.css ヘッダ例:
  ```css
  /*!
   * Theme Name: Agent Neo
   * Theme URI: https://automation-seo.solobiz-lab.com
   * Description: WP7.0 FSE Theme for AI Agent Platform
   * Author: Automation SEO Team
   * Author URI: https://automation-seo.solobiz-lab.com
   * License: GPL v2 or later
   * License URI: https://www.gnu.org/licenses/gpl-2.0.html
   * Text Domain: agent-neo
   * Domain Path: /languages
   * Requires at least: 6.7
   * Requires PHP: 7.4
   * Tested up to: 7.0
   * Version: 1.0.0
   */
  ```
  
- theme.json ヘッダ例:
  ```json
  {
    "$schema": "https://schemas.wp.org/wp/7.0/theme.json",
    "version": 3,
    "settings": { ... },
    "styles": { ... },
    "customTemplates": [ ... ],
    "templateParts": [ ... ],
    "patterns": [ ... ]
  }
  ```

---

### 1.2 ディレクトリ構成

| ディレクトリ | 必須/推奨/任意 | 用途 | チェック |
|-----------|-----------|------|---------|
| `templates/` | 🔴 必須 | FSEテンプレート群（index.html / home.html / singular.html / archive.html / 404.html / search.html 等） | ❌ |
| `parts/` | 🟡 推奨 | Template Parts（header.html / footer.html / sidebar.html 等の再利用パーツ） | ❌ |
| `patterns/` | 🟡 推奨 | Block Patterns（ユーザーが再利用可能なブロック組成） | ❌ |
| `assets/` | 🟢 任意 | CSS / JS / 画像 等の静的ファイル（functions.phpで管理 or theme.json) | ❌ |
| `languages/` | 🟢 任意 | i18n翻訳ファイル（.mo / .po）/ AGENT-NEO英語ファースト予定 | ❌ |
| `block-styles/` | 🟢 任意 | カスタムブロック定義（block.json / スタイルバリエーション） | ❌ |

**出典**: `project_agent_neo_theme.md L3 design docs` / WP Handbook Templates & Parts

---

## セクション 2: theme.json スキーマ v3 仕様と WP7.0 新機能

### 2.1 Schema Version & $schema URL

| 項目 | 値 | 説明 | チェック |
|------|-----|------|---------|
| **version** | `3` | WP6.6以降・WP7.0GA対応。v2は後方互換ながら新機能利用不可 | ❌ |
| **$schema** | `https://schemas.wp.org/wp/7.0/theme.json` | IDE自動補完 + 検証用（VSCode等） | ❌ |
| **後方互換性** | YES (v2は WP6.5以降動作継続) | v4予定なし（v3永続予定） | ✅ |

**出典**: `reference_wp_ecosystem_20260620.md §3` / GitHub Twenty Twenty-Four theme.json

**実装上の注意**:
- IDE検証: VSCode + JSON Schema拡張で WP7.0 スキーマ自動検証
- テスト: `wp_theme_json_validate()` で構文・型チェック

---

### 2.2 settings セクション（WP7.0での拡張）

| 設定項目 | 従来/新規 | 説明 | チェック |
|---------|---------|------|---------|
| **color** | 従来 | パレット / gradients / custom colors | ❌ |
| **typography** | 従来 (拡張) | fontFamilies / fontSizes / lineHeight / letterSpacing / typography presets | ❌ |
| **spacing** | 従来 | padding / margin / gap presets（CSS custom properties生成） | ❌ |
| **dimensions** | 従来 (拡張) | width / height / minHeight / maxWidth presets（WP7.0で拡張） | ❌ |
| **border** | 従来 | borderColor / borderRadius / borderStyle / borderWidth presets | ❌ |
| **shadow** | 従来 | boxShadow presets（テキストシャドウは未対応） | ❌ |
| **layout** | 従来 | grid / flex 等のレイアウトエンジン設定 | ❌ |
| **appearanceTools** | **新規** (WP7.0) | 疑似要素（:hover / :focus / :active 等）のスタイル編集許可 | ❌ |
| **aspectRatio** | (推測) | アスペクト比プリセット | ❌ |
| **fluid** | **新規** (WP6.7+) | 流動的タイポグラフィ（min/max size で自動スケーリング） | ❌ |

**出典**: `reference_wp_ecosystem_20260620.md §3-2` / WP6.5+ GA機能

**実装例**:
```json
{
  "settings": {
    "typography": {
      "fontFamilies": [
        {
          "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto",
          "name": "System",
          "slug": "system"
        }
      ],
      "fontSizes": [
        { "name": "Small", "slug": "small", "size": "0.875rem" },
        { "name": "Normal", "slug": "normal", "size": "1rem" },
        { "name": "Large", "slug": "large", "size": "1.5rem", "fluid": { "min": "1.25rem", "max": "1.75rem" } }
      ]
    },
    "color": {
      "palette": [
        { "color": "#ffffff", "name": "White", "slug": "white" },
        { "color": "#000000", "name": "Black", "slug": "black" }
      ]
    }
  }
}
```

---

### 2.3 styles セクション（WP7.0での新機能）

| スタイルエリア | 説明 | WP7.0新規 | チェック |
|-------------|------|---------|---------|
| **root** | グローバル CSS変数 + ファイナルレイアウト | No | ❌ |
| **block** | コアブロック・カスタムブロック毎のスタイル | No | ❌ |
| **elements** | button / heading / link 等の汎用要素スタイル | No | ❌ |
| **pseudoClasses** | `:hover` / `:focus` / `:active` / `:focus-visible` 等の疑似要素スタイル（ブロック単位） | **Yes** | ❌ |
| **sectionStyles** | セクション単位でのスタイル適用（`section-1` / `section-2` 等） | **Yes** (experimental) | ❌ |
| **blockBindings** | ブロック属性を post-meta / カスタムソースへ動的束縛 | **Yes** | ❌ |

**出典**: `reference_wp_ecosystem_20260620.md §3-3` / `project_agent_neo_theme.md §2026-06-20(part2)`

**実装例 - 疑似クラス**:
```json
{
  "styles": {
    "blocks": {
      "core/button": {
        "color": { "background": "#0073aa", "text": "#ffffff" },
        ":hover": { "color": { "background": "#005a87" } },
        ":focus": { "outline": { "color": "#ffb900", "style": "dashed", "width": "2px" } }
      }
    }
  }
}
```

**実装例 - Section Styles**:
```json
{
  "styles": {
    "sections": {
      "section-1": {
        "color": { "background": "#f5f5f5" },
        "spacing": { "padding": "2rem" }
      }
    }
  }
}
```

---

### 2.4 customTemplates セクション

| フィールド | 必須/推奨 | 説明 | チェック |
|-----------|---------|------|---------|
| **path** | 必須 | `templates/` から相対パス（例: `single-product.html`） | ❌ |
| **title** | 必須 | 管理画面に表示される名前（例: "Product Template"） | ❌ |
| **description** | 推奨 | テンプレートの説明 | ❌ |
| **postTypes** | 必須 | このテンプレートを使用できるポストタイプ配列（`["post"]` / `["page", "post"]` 等） | ❌ |

**出典**: `project_agent_neo_theme.md §2026-06-20(part2)` / WP Handbook Custom Templates

**実装例**:
```json
{
  "customTemplates": [
    {
      "path": "templates/single-post.html",
      "title": "Blog Post",
      "description": "Template for individual blog posts",
      "postTypes": ["post"]
    },
    {
      "path": "templates/page-landing.html",
      "title": "Landing Page",
      "postTypes": ["page"]
    }
  ]
}
```

---

### 2.5 templateParts セクション

| フィールド | 説明 | チェック |
|-----------|------|---------|
| **path** | `parts/` からの相対パス（例: `header.html`） | ❌ |
| **title** | 管理画面名 | ❌ |
| **type** | `header` / `footer` / `uncategorized` 等（WP7.0で新型別？） | ❌ |
| **area** | パート配置エリア（オプション） | ❌ |

**出典**: WP Handbook Template Parts / `project_agent_neo_theme.md L3`

**標準パーツ一覧（推奨）**:
```json
{
  "templateParts": [
    {
      "path": "parts/header.html",
      "title": "Header",
      "type": "header"
    },
    {
      "path": "parts/footer.html",
      "title": "Footer",
      "type": "footer"
    },
    {
      "path": "parts/sidebar.html",
      "title": "Sidebar",
      "type": "uncategorized"
    }
  ]
}
```

---

### 2.6 patterns セクション

| フィールド | 説明 | チェック |
|-----------|------|---------|
| **name** | パターン内部識別子（例: `agent-neo/testimonial`） | ❌ |
| **title** | ユーザー向け表示名 | ❌ |
| **description** | パターン説明 | ❌ |
| **categories** | カテゴリ配列（`["buttons", "cards", "testimonials"]` 等） | ❌ |
| **content** | HTML ブロック マークアップ | ❌ |
| **blockTypes** | このパターンが挿入可能なブロックタイプ | ❌ |

**出典**: WP Handbook Block Patterns / `project_agent_neo_theme.md`

**実装例**:
```json
{
  "patterns": [
    {
      "name": "agent-neo/testimonial-card",
      "title": "Testimonial Card",
      "categories": ["cards", "testimonials"],
      "content": "<!-- wp:group {\"layout\":{\"type\":\"flex\",\"flexWrap\":\"wrap\"}} -->\n<div class=\"wp-block-group\"><!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Amazing service!</h3>\n<!-- /wp:heading --><!-- wp:paragraph -->\n<p>Lorem ipsum dolor sit amet...</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->"
    }
  ]
}
```

---

## セクション 3: WP7.0 新機能とテーマ対応の要否

### 3.1 Block Bindings API

| 項目 | 対応要否 | 説明 | 出典 |
|------|---------|------|------|
| **テーマ対応** | 🟡 推奨 | ブロック属性を post-meta / term-data / カスタムソースへ動的束縛 | `reference_wp_ecosystem_20260620.md §3-4` |
| **実装方法** | — | `register_block_bindings_source()` + `permission_callback` + `get_callback` | `project_agent_neo_theme.md §2026-06-20(part2)` |
| **セキュリティ** | 推奨 | Permission callback + Transient キャッシュで安全化 | `project_agent_neo_theme.md` |
| **AGENT-NEO採用** | ❌ 非採用予定 | L4実装時に判断 / 現状ADR-024/025未採用 | `project_agent_neo_theme.md §2026-06-21` |

**実装例**（参考）:
```php
register_block_bindings_source(
  'my-plugin/site-title',
  array(
    'label' => __( 'Site Title', 'agent-neo' ),
    'get_callback' => function() {
      return get_bloginfo( 'name' );
    },
    'permission_callback' => function() {
      return current_user_can( 'edit_posts' );
    }
  )
);
```

---

### 3.2 Interactivity API

| 項目 | 対応要否 | 説明 | 出典 |
|------|---------|------|------|
| **テーマ対応** | 🟡 推奨 | React / Vue 不要の軽量フロント実装。watch() / Preact signals ベース | `reference_wp_ecosystem_20260620.md §3-5` |
| **WP7.0での変更** | — | React19 compatibility 向上（upgrade → revert 中） | `project_agent_neo_theme.md §2026-06-20(part2)` |
| **実装例** | — | ボタンクリック / アコーディオン / モーダル 等の対話性 | WP 6.5+ GA |
| **AGENT-NEO採用** | ⚠️ 未定 | L4実装時に判断 / 複雑な対話性は Automation SEO JS に集約予定 | `project_agent_neo_theme.md` |

**実装例**（参考）:
```html
<!-- wp:core/button { "interactiveButton": true, "onClick": "toggleAccordion" } -->
<button class="wp-block-button">Toggle</button>
<!-- /wp:core/button -->
```

---

### 3.3 Abilities API

| 項目 | 対応要否 | 説明 | 出典 |
|------|---------|------|------|
| **テーマ対応** | ❌ **非採用** (テーマは0実績) | WP6.9+ 標準。読取り機能9 / 書き込み機能18 / plugin19個のみ採用 | `reference_wp_ecosystem_20260620.md §3-1` |
| **実装実態** | — | ~8割は read-only。Write は Automation SEO 集約が正規 | `reference_wp_ecosystem_20260620.md §最重要` |
| **AGENT-NEO戦略** | ❌ 非採用 | テーマは **Abilities 公開を明示禁止**。READ公開は Automation SEO 親エージェント責務（ADR-024） | `project_agent_neo_theme.md §2026-06-21 ADR-024/025` |

**出典**: `reference_wp_ecosystem_20260620.md §3-1 + §含意` / WP 6.9.4 / 7.0 実機検証

---

### 3.4 Font Library & fontFace

| 項目 | 対応要否 | 説明 | チェック |
|------|---------|------|---------|
| **Google Fonts廃止** | 🟡 推奨 | fontFace で Google Fonts / 自社ホスティング フォント統合 | ❌ |
| **theme.json定義** | 🟡 推奨 | `settings.typography.fontFamilies[].fontFace` で宣言 | ❌ |
| **WP7.0での対応** | — | 既存 v6.5+ と変更なし | ✅ |

**実装例**:
```json
{
  "settings": {
    "typography": {
      "fontFamilies": [
        {
          "fontFamily": "'Inter', sans-serif",
          "name": "Inter",
          "slug": "inter",
          "fontFace": [
            {
              "fontFamily": "Inter",
              "fontWeight": "400",
              "fontStyle": "normal",
              "src": ["file:./assets/fonts/inter-regular.woff2"]
            },
            {
              "fontFamily": "Inter",
              "fontWeight": "700",
              "fontStyle": "normal",
              "src": ["file:./assets/fonts/inter-bold.woff2"]
            }
          ]
        }
      ]
    }
  }
}
```

---

### 3.5 Speculative Loading（プリロード / プリコネクト）

| 項目 | 対応要否 | 説明 | チェック |
|------|---------|------|---------|
| **自動生成** | 🟢 自動 | `<link rel="prefetch">` / `<link rel="preconnect">` が WP 6.8+ で自動生成 | ✅ |
| **テーマ明示宣言** | 🟢 任意 | where 句で除外除外可能（パフォーマンス最適化） | ❌ |
| **AGENT-NEO** | — | デフォルト動作で十分。ADR検討待ち | — |

**出典**: `reference_wp_ecosystem_20260620.md §3-6` / WP 6.8+ GA

---

### 3.6 HTML API（非推奨化への対応）

| 項目 | 対応要否 | 説明 | チェック |
|------|---------|------|---------|
| **XSS対策** | 🔴 必須 | `wp_kses()` で HTML エスケープ（WP7.0でも継続） | ❌ |
| **タグ除去** | 🔴 必須 | `<script>` タグ自動除去（WP6.2+ HTML API） | ✅ |
| **AGENT-NEO** | — | ADR-026 で Sandbox iframe + CSP 実装予定 | — |

**出典**: `project_agent_neo_theme.md §2026-06-21` / WP 6.2+ GA

---

### 3.7 新ブロック（WP7.0追加）

| ブロック名 | 説明 | テーマ対応 | チェック |
|-----------|------|---------|---------|
| Headings | 見出し統一（h1〜h6一括管理） | 🟢 任意 | ❌ |
| Breadcrumbs | ナビゲーション軌跡表示 | 🟢 任意 | ❌ |
| Icons | アイコンライブラリ統合 | 🟢 任意 | ❌ |
| (その他) | (調査未取得) | — | — |

**出典**: `reference_wp_ecosystem_20260620.md §3（新規ブロック詳細未取得）`

---

## セクション 4: Theme Check / Theme Review 最新要件（WP7.0）

### 4.1 WordPress Theme Check プラグイン検査項目

| チェック項目 | 必須/推奨 | WP7.0での変更 | チェック |
|-----------|---------|-----------|---------|
| **License（GPL v2以上）** | 🔴 必須 | No | ❌ |
| **Text Domain** | 🔴 必須 | No（style.css + functions.php で宣言） | ❌ |
| **Deprecated Functions** | 🔴 必須 | (詳細未取得) | ❌ |
| **Security（esc_html / wp_kses）** | 🔴 必須 | No（継続） | ❌ |
| **wp_enqueue_style / wp_enqueue_script** | 🟡 重要 | (変更内容未取得) | ❌ |
| **Screenshot（1200x900 PNG）** | 🟡 推奨 | No | ❌ |
| **PHP Version（8.0+）** | 🔴 必須 | **Yes**（7.x廃止） | ❌ |
| **WordPress Version（6.7+）** | 🔴 必須 | **Yes**（テスト対象v7.0） | ❌ |

**出典**: WP公式 Theme Check Plugin / `project_agent_neo_theme.md`

---

### 4.2 WP.org Theme Review Handbook（WP7.0更新）

| 要件カテゴリ | 内容 | チェック |
|-------------|------|---------|
| **Escaping** | 全 HTML 出力に `esc_html()` / `esc_attr()` / `wp_kses()` 必須 | ❌ |
| **Prefix** | `theme_` または独自プレフィックス（例: `agent_neo_`）を options に付与 | ❌ |
| **i18n** | `load_theme_textdomain()` の実装（任意） / block.json `textDomain` 指定 | ❌ |
| **Custom Logo / Header / Background** | `add_theme_support()` で宣言（WP7.0で推奨/必須？） | ❌ |
| **PHP Compatibility** | PHP 8.0+ / 8.3推奨。WP 6.7+ テスト済み | ❌ |
| **Accessibility** | WCAG 2.2 AA 達成目標（新要件） | ❌ |

**出典**: WP.org Theme Review Guidelines / `reference_wp_ecosystem_20260620.md §3-7`

---

### 4.3 WP7.0で新規チェック項目

| チェック項目 | 内容 | 優先度 | チェック |
|-----------|------|--------|---------|
| **Block Bindings対応宣言** | (新規チェック内容未取得) | 🟢 任意 | ❌ |
| **Interactivity API サポート** | (新規チェック内容未取得) | 🟢 任意 | ❌ |
| **Abilities API非採用宣言** | テーマは Abilities API を明示的に採用しないことを宣言（推奨） | 🟡 推奨 | ❌ |
| **WCAG 2.2 AA対応** | accessibility-ready tag + 5新要件（下述） | 🔴 必須 | ❌ |

---

## セクション 5: WP7.0での Deprecation / 非推奨化 / 後方互換性

### 5.1 PHP版本要件

| 項目 | 値 | チェック |
|------|-----|---------|
| **WP7.0最小PHP** | PHP 7.4+ (JSON / hash extension 必須) | ❌ |
| **推奨** | PHP 8.3+ | ❌ |
| **PHP 7.x サポート** | 終了予定（WP 6.9〜7.0で廃止段階的） | ✅ |

**出典**: WP公式 `wp-includes/version.php` / `reference_wp_ecosystem_20260620.md`

---

### 5.2 削除・非推奨化された機能

| 機能 | 状態 | 代替手段 | チェック |
|------|------|--------|---------|
| **add_theme_support( 'custom-X' )** | (WP7.0での廃止予定 = 調査未取得) | — | ❌ |
| **Classic Widgets** | 継続対応（`is_wp_version_compatible()` で条件分岐） | Block themes も `add_theme_support('widgets')` で有効化可能 | ✅ |
| **WP Connectors API（AI機能）** | **テーマ非採用が正規**（生成=Automation SEO集約） | — | ✅ |
| **PHP AI Client SDK** | **テーマ採用禁止**（REQ-NF-025） | Automation SEO エージェント責務 | ✅ |
| **wp_enqueue 従来形式** | (統一内容未取得) | — | ❌ |

**出典**: `reference_wp_ecosystem_20260620.md§3-1/4-1` / `project_agent_neo_theme.md ADR-024/025`

---

### 5.3 theme.json version downgrade対応

| 項目 | 対応 | 説明 | チェック |
|------|------|------|---------|
| **version: 2 互換性** | YES | WP6.5以降で動作継続（新機能利用不可） | ✅ |
| **version: 3の使用** | 🔴 必須 | WP7.0では v3 推奨。v2は非推奨化傾向 | ❌ |

---

## セクション 6: アクセシビリティ（accessibility-ready タグ）

### 6.1 WCAG 2.2 AA準拠要件（WP7.0で新基準）

🔴 **Critical** — 2026-06-30期限（EU EAA施行 / ADA訴訟増加）

| 要件 | WCAG基準 | テーマ実装 | チェック |
|------|---------|---------|---------|
| **1.4.3 Contrast Minimum** | 4.5:1（本文） / 3:1（見出し・UI） | CSS color 検証（Axe DevTools） | ❌ |
| **2.1.1 Keyboard** | 全機能キーボード操作可能 | Tab / Enter / Space / Arrow キー | ❌ |
| **2.1.2 No Keyboard Trap** | フォーカストラップなし | 全ボタンは Escape で脱出可能 | ❌ |
| **2.4.1 Bypass Blocks** | Skip link 必須 | `<a href="#main">Skip to content</a>` | ❌ |
| **2.4.3 Focus Order** | フォーカス順序が論理的 | Tab順 = 視覚順 = DOM順 | ❌ |
| **2.4.7 Focus Visible** | フォーカス表示が見えやすい | `:focus-visible { outline: ... }` | ❌ |
| **3.2.4 Consistent Identification** | UI用語一貫性 | 同じ機能は同じ名前・アイコン使用 | ❌ |
| **4.1.3 Status Messages** | aria-live で通知 | 形式変更・エラーメッセージ | ❌ |

**出典**: `reference_wp_ecosystem_20260620.md §3-7` / WCAG 2.2 仕様 / WP Accessibility Handbook

---

### 6.2 テーマ実装での WCAG チェック項目

#### 6.2.1 Skip Link（全ページ必須）

```html
<a href="#main" class="skip-link">
  <?php esc_html_e( 'Skip to content', 'agent-neo' ); ?>
</a>

<style>
  .skip-link {
    position: absolute;
    left: -9999px;
    z-index: 999;
  }
  .skip-link:focus {
    left: 6px;
    top: 7px;
    background: #000;
    color: #fff;
    padding: 0.5rem;
  }
</style>

<main id="main">
  <!-- ページ本体 -->
</main>
```

**チェック**: ❌

#### 6.2.2 Color Contrast

| テスト対象 | 最小比率 | ツール | チェック |
|----------|--------|-------|---------|
| 本文テキスト | 4.5:1 | Axe DevTools / WebAIM | ❌ |
| 見出し（18pt以上） | 3:1 | 同上 | ❌ |
| UI 要素（ボタン・アイコン） | 3:1 | 同上 | ❌ |

#### 6.2.3 Keyboard Navigation

```html
<!-- すべてのボタン/リンクが Tab キー到達可能 -->
<button type="button">Toggle Menu</button>
<a href="/about">About Us</a>

<!-- フォーカス visible（必須） -->
<style>
  button:focus-visible,
  a:focus-visible {
    outline: 2px solid #ffb900;
    outline-offset: 2px;
  }
</style>

<!-- フォーカストラップ防止 -->
<button type="button" onclick="closeModal(); document.querySelector('#main').focus();">
  Close
</button>
```

**チェック**: ❌

#### 6.2.4 Semantic HTML

```html
<!-- ✅ Good -->
<nav aria-label="Main Navigation">
  <ul>
    <li><a href="/">Home</a></li>
  </ul>
</nav>

<main id="main">
  <h1>Page Title</h1>
  <section>
    <h2>Section</h2>
    <p>Content</p>
  </section>
</main>

<footer>
  <p>&copy; 2026 Agent Neo</p>
</footer>

<!-- ❌ Bad -->
<div id="nav"><!-- Divで構造化 --></div>
<div id="main">
  <div class="h1">Page Title</div><!-- divで見出し化 -->
</div>
```

**チェック**: ❌

#### 6.2.5 ARIA Attributes

```html
<!-- アイコンボタン（テキストなし場合は aria-label必須） -->
<button aria-label="Close Menu" class="close-icon">✕</button>

<!-- 詳細説明 -->
<input type="text" id="username" aria-describedby="username-hint">
<small id="username-hint">3-20文字の英数字</small>

<!-- ナビゲーション現在地 -->
<nav>
  <a href="/blog" aria-current="page">Blog</a>
  <a href="/about">About</a>
</nav>

<!-- 展開・折畳み -->
<button aria-expanded="false" aria-controls="menu">Menu</button>
<div id="menu" hidden><!-- aria-expanded="false" 時は hidden属性 --></div>

<!-- 隠し要素（スクリーンリーダー無視） -->
<div aria-hidden="true">装飾画像</div>
```

**チェック**: ❌

#### 6.2.6 Image Alt Text

```html
<!-- wp:image で alt 属性必須 -->
<!-- wp:image { "id": 123, "alt": "Product screenshot showing main dashboard" } -->
<figure class="wp-block-image">
  <img src="screenshot.png" alt="Product screenshot showing main dashboard">
</figure>
<!-- /wp:image -->

<!-- 装飾画像 = alt="" (empty) -->
<!-- wp:image { "id": 456, "alt": "" } -->
<figure class="wp-block-image">
  <img src="decorative-line.png" alt="">
</figure>
<!-- /wp:image -->
```

**チェック**: ❌

---

### 6.3 WP7.0で新規アクセシビリティ機能

| 機能 | 説明 | AGENT-NEO対応 | チェック |
|------|------|-----------|---------|
| **Font Sizing Optimization** | フォント読みやすさ自動調整 | (実装内容未定) | ❌ |
| **Reduced Motion Respect** | `prefers-reduced-motion` に対応 | CSS media query で animation 無効化 | ❌ |
| **Color Blind Friendly Palette** | 色覚異常者向けパレット | (任意) | ❌ |

---

## セクション 7: ブロック関連（Block API v3 / Core Block Changes）

### 7.1 Block.json スキーマ v3

| フィールド | 説明 | WP7.0新規 | チェック |
|-----------|------|---------|---------|
| **apiVersion** | `3` | No（v2 互換性あり） | ❌ |
| **name** | `vendor/block-name` | No | ❌ |
| **textDomain** | i18n ドメイン（例: `agent-neo`） | 推奨（WP6.2+） | ❌ |
| **supports.interactivity** | Interactivity API対応宣言 | **Yes**（WP6.5+） | ❌ |
| **supports.blockBindings** | Block Bindings API対応宣言 | **Yes**（WP6.5+） | ❌ |
| その他従来フィールド | edit / save / attributes 等 | No | ❌ |

**実装例**:
```json
{
  "apiVersion": 3,
  "name": "agent-neo/custom-block",
  "title": "Custom Block",
  "description": "A custom block for Agent Neo",
  "category": "widgets",
  "icon": "smiley",
  "textDomain": "agent-neo",
  "supports": {
    "interactivity": true,
    "blockBindings": true,
    "align": ["wide", "full"]
  },
  "attributes": {
    "content": { "type": "string" }
  },
  "editorScript": "file:./index.js"
}
```

**チェック**: ❌

---

### 7.2 Core Block の WP7.0での変更

| ブロック | 変更内容 | テーマ対応 | チェック |
|---------|--------|---------|---------|
| **Paragraph / Heading** | (詳細未取得) | — | ❌ |
| **Form** | 新規 widget 機能 | (詳細未取得) | ❌ |
| **Navigation** | AI自動生成対応 | (詳細未取得) | ❌ |
| その他 | (調査未取得) | — | — |

**出典**: `reference_wp_ecosystem_20260620.md §3-6` (詳細未取得)

---

### 7.3 テーマが対応すべき Core Block の動き

| チェック項目 | 説明 | チェック |
|-----------|------|---------|
| **Iframed Editor** | ブロックエディタが iframe 内で動作（Block API v3） | ❌ |
| **Custom Block Styles** | block-style-variants.html でスタイルバリエーション提供可 | ❌ |
| **Block Variations** | カスタムバリエーション定義（例: ボタン色の追加オプション） | ❌ |

---

## セクション 8: wp.org Theme Directory 提出時の要件（WP7.0対応テーマ）

### 8.1 チェックリスト（提出前必須）

| 要件 | 状態 | チェック |
|------|------|---------|
| 🔴 **theme.json** | WP6.2+、ブロックテーマは必須 | ❌ |
| 🔴 **style.css ヘッダ** | Theme Name / License / Text Domain / Requires PHP 8.0+ / Tested up to 7.0 | ❌ |
| 🔴 **screenshot.png** | 1200x900 PNG | ❌ |
| 🔴 **license.txt** | GPL v2以上 | ❌ |
| 🔴 **Security** | 全 HTML エスケープ（esc_html / wp_kses） | ❌ |
| 🔴 **Accessibility** | WCAG 2.2 AA + skip link + Color Contrast + キーボード操作 | ❌ |
| 🟡 **readme.txt** | Description / Install / Changelog 等（マークダウン非対応） | ❌ |
| 🟡 **PHP 8.0+ テスト** | 実環境テスト必須 | ❌ |
| 🟡 **WP 6.7+ テスト** | WP 7.0 テスト済み記載 | ❌ |

**出典**: WP.org Theme Review Guidelines / `project_agent_neo_theme.md`

---

## セクション 9: AGENT-NEO テーマ向け実装前監査チェックリスト

### 9.1 フェーズ別チェック

#### Phase L1 — 要件定義・テーマ戦略確定

| 項目 | 状態 | 期限 |
|------|------|------|
| ✅ ADR-023（テーマ基盤） | PASS | 2026-06-20 |
| ✅ ADR-024（テーマ機能スコープ） | PASS | 2026-06-20 |
| ✅ ADR-025（AI機能不採用宣言） | PASS | 2026-06-20 |
| ✅ ADR-026（Sandbox Embed実装） | PASS | 2026-06-20 |
| ⚠️ L1 PO承認 | **待機中** | — |

**出典**: `project_agent_neo_theme.md §2026-06-20 part22`

#### Phase L2 — ビジュアル・アーキテクチャ設計

| チェック項目 | 説明 | 状態 | 期限 |
|------------|------|------|------|
| 🔴 theme.json schema確定 | v3 / $schema / settings / styles 全体 | ❌ | TBD |
| 🔴 テンプレート構成設計 | templates / parts / patterns の標準セット定義 | ❌ | TBD |
| 🔴 Block Bindings戦略 | 採用 or 非採用の判断 | ❌ | TBD |
| 🔴 Interactivity API戦略 | 採用 or 非採用の判断 | ❌ | TBD |
| 🟡 WP7.0新機能対応マップ | 実装予定機能の洗出し | ❌ | TBD |
| 🟡 a11y デザイン仕様 | WCAG 2.2 AA の具体的実装方針 | ❌ | TBD |

#### Phase L3 — 詳細設計

| チェック項目 | 説明 | 状態 | 期限 |
|------------|------|------|------|
| 🔴 theme.json 詳細仕様書 | JSON全文 + コメント | ❌ | TBD |
| 🔴 テンプレート詳細仕様 | 各テンプレートの HTML マークアップ | ❌ | TBD |
| 🔴 functions.php 実装仕様 | 最小限 or 完全版の判断 | ❌ | TBD |
| 🔴 セキュリティ対策リスト | XSS / CSP / Sandbox iframe 対応 | ❌ | TBD |
| 🟡 a11y 検証チェックリスト | 各要件の実装完了確認リスト | ❌ | TBD |
| 🟡 Theme Check対応票 | deprecated 関数リスト | ❌ | TBD |

#### Phase L4 — 実装 (Sprint単位)

| チェック項目 | 説明 | 状態 | 期限 |
|------------|------|------|------|
| 🔴 必須ファイル作成 | style.css / theme.json / templates/index.html | ❌ | TBD |
| 🔴 theme.json 実装 | L3仕様に基づき実装 + VSCode検証 | ❌ | TBD |
| 🔴 テンプレート実装 | templates / parts / patterns マークアップ | ❌ | TBD |
| 🔴 ブロックスタイル | CSS / セクションスタイル / 疑似クラス対応 | ❌ | TBD |
| 🟡 functions.php実装 | 初期化フック / エンキュー処理 | ❌ | TBD |
| 🟡 i18n対応 | textDomain / load_theme_textdomain() | ❌ | TBD |

#### Phase L5 — Visual Refinement

| チェック項目 | 説明 | 状態 | 期限 |
|------------|------|------|------|
| 🟡 実ブラウザ確認 | Chrome / Firefox / Safari での表示確認 | ❌ | TBD |
| 🟡 レスポンシブテスト | モバイル / タブレット / デスクトップ | ❌ | TBD |
| 🟡 Block Editor プレビュー | WP管理画面でのブロック編集画面 | ❌ | TBD |
| 🟡 ダークモード対応 | `prefers-color-scheme` テスト | ❌ | TBD |

#### Phase L6 — 統合検証

| チェック項目 | 説明 | 状態 | 期限 |
|------------|------|------|------|
| 🔴 WordPress Theme Check | プラグイン実行 → PASS | ❌ | TBD |
| 🔴 WCAG 2.2 AA監査 | Axe DevTools / WebAIM コントラスト検証 | ❌ | TBD |
| 🔴 Security監査 | XSS / CSP / Sandbox検証 | ❌ | TBD |
| 🟡 Core Block互換性 | 標準ブロック（Paragraph / Heading / Button等）正常動作 | ❌ | TBD |
| 🟡 Custom Block互換性 | (実装予定 block.json の検証) | ❌ | TBD |

#### Phase L8 — 受入試験

| チェック項目 | 説明 | 状態 | 期限 |
|------------|------|------|------|
| 🔴 wp.org提出要件 | theme.json / screenshot.png / license.txt 最終確認 | ❌ | TBD |
| 🔴 本番環境テスト | XVPS + WordPress 7.0 での動作確認 | ❌ | TBD |
| 🟡 ドキュメント整備 | README / セットアップガイド / カスタマイズ手順 | ❌ | TBD |

---

### 9.2 重要な警告 ⚠️

| 警告 | 対応方法 | チェック |
|------|--------|---------|
| **functions.php作成は後回し** | テーマ機能の9割は theme.json で実装可。L2でスコープ判定 | ✅ |
| **block.json検証** | VSCode + JSON Schema 拡張で自動検証 / wp_theme_json_validate() | ❌ |
| **Sandbox iframe CSP** | ADR-026で設計済。実装時は postMessage nonce検証必須 | ✅ |
| **WCAG期限** | **2026-06-30** — EU EAA施行 + ADA訴訟増加で対応必須 | ⚠️ 急! |
| **wp.org提出** | L8 before — 変更が多いため早期提出は非推奨 | — |

---

## 参考資料・出典URL

### 公式ドキュメント

- **WordPress Developer Blog**: https://developer.wordpress.org/blog/
- **WordPress Handbook — Themes**: https://developer.wordpress.org/themes/
- **WordPress Handbook — Block Themes**: https://developer.wordpress.org/themes/block-themes/
- **WordPress Handbook — theme.json**: https://developer.wordpress.org/themes/global-settings-and-styles/the-settings-and-styles-apis/
- **WordPress Handbook — Templates**: https://developer.wordpress.org/themes/templates/
- **WordPress Handbook — Template Parts**: https://developer.wordpress.org/themes/template-parts/
- **WordPress Handbook — Block Patterns**: https://developer.wordpress.org/themes/block-patterns/
- **WordPress Handbook — i18n**: https://developer.wordpress.org/plugins/internationalization/
- **WP.org Theme Review Guidelines**: https://make.wordpress.org/themes/handbook/review/required/
- **WP.org Theme Check Plugin**: https://wordpress.org/plugins/theme-check/
- **Block API v3 Reference**: https://developer.wordpress.org/blocks/block.json/

### アクセシビリティ関連

- **WCAG 2.2**: https://www.w3.org/WAI/WCAG22/quickref/
- **Axe DevTools**: https://www.deque.com/axe/devtools/
- **WebAIM Contrast Checker**: https://webaim.org/resources/contrastchecker/
- **WordPress Accessibility Handbook**: https://developer.wordpress.org/plugins/accessibility/

### AGENT-NEO プロジェクト内メモリ

- `/root/.claude/projects/-opt-seo-tool/memory/reference_wp_ecosystem_20260620.md` — WP7.0 AI infra調査
- `/root/.claude/projects/-opt-seo-tool/memory/project_agent_neo_theme.md` — テーマ設計ドキュメント（L1〜L3）

### 参考テーマ実装例

- **WordPress Twenty Twenty-Four**: https://github.com/WordPress/wordpress-develop/tree/trunk/src/wp-content/themes/twentytwentyfour （WP6.5+ ブロックテーマ）
- **WordPress Twenty Twenty-Three**: https://github.com/WordPress/wordpress-develop/tree/trunk/src/wp-content/themes/twentytwentythree

---

## 今後の手順

1. **L1 PO承認取得** — 現在ステータス（WAITING）
2. **L2 設計開始** — ADR-023/024/025に基づく theme.json 骨組み
3. **L3 詳細設計** — 各セクション（2.x / 3.x等）の仕様書化
4. **L4 実装Sprint開始** — セクション9.1 Phase L4 チェック実施
5. **L6 検証ゲート** — Theme Check + WCAG 2.2 AA + Security audit
6. **L8 受入** — wp.org提出準備

---

**最終更新**: 2026-06-21 / **次アクション**: L1 PO承認待ち
