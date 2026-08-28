# 構造調査 C — HELIX-WP-THEME（旧 AGENT NEO）

- 調査日: 2026-08-26 / 対象: `~/dev/HELIX-WP-THEME`（read-only reference）
- 構成: FSE テーマ `agent-neo-theme` + プラグイン `agent-neo-core` / `agent-neo-embed`

## 1. 物理構造（themes/agent-neo-theme）

| 項目 | 値 |
|---|---|
| ファイル内訳 | php 35・**html 14**・json 10・pot 1・css 1・js 1 |
| theme.json | **v3**（settings: appearanceTools/layout/color/typography/spacing/blocks/border/custom、styles: color/typography/spacing/elements/blocks） |
| templates/ | 10 HTML（`index` `single` `page` `archive` `search` `404` `front-page` `blank` `no-header` `page-lp-sample`） |
| parts/ | 4 HTML（`header` `footer` `post-header` `post-footer`） |
| styles/ | 2 スタイルバリエーション（`light.json` `dark.json`） |
| patterns/ | **24 PHP パターン**（`agent-neo/*`） |
| config/ | 7 JSON（`section-registry` `theme-manifest` `asset-policy` `third-party-tags` `schema-reference` `i18n-profile` `web-vitals-budget`） |
| inc/ | 10 PHP（bootstrap / config-loader / related-query / setup(theme-setup・boundary-guard) / assets(third-party-manager) / seo(head-meta・structured-data・oembed-lazy)） |

## 2. パーツ機構

### 2.1 パターン（24・名前空間 `agent-neo/`）
- サイト面: `hero` `home-hero` `home-gateway` `home-overview` `home-cases` `home-resources` `home-trust` `home-final-cta` `footer-credit`
- LP 面: `lp-hero` `lp-problem` `lp-agitation` `lp-solution` `lp-benefit` `lp-feature` `lp-proof` `lp-use-case` `lp-comparison` `lp-pricing` `lp-faq` `lp-final-cta`
- 記事面: `article-cta` `author-profile` `share-buttons`

### 2.2 セクション・レジストリ（`config/section-registry.json`）
`section_id` → `type` → `template` → `pattern` の対応表（v0.1.0、8 セクション、`required_sections: [agent_neo_hero]`）。
= **パターンを機械可読な「セクション」として台帳化**する機構。テーマA / テーマB に対応物なし。

### 2.3 ブロック
- テーマ側: **カスタムブロック 0**（コアブロック + パターンで構成）
- `agent-neo-embed` プラグイン: **`agent-neo/embed` 1 種のみ**（AI 生成 HTML の隔離差込。DSD shadowroot SSR）

### 2.4 ウィジェット / メニュー / ショートコード
**すべて 0**。`add_theme_support` は `post-thumbnails` `editor-styles` `wp-block-styles` の 3 つのみ。

## 3. API・契約面（agent-neo-core）

| 項目 | 実測 |
|---|---|
| REST コントローラ | **34 クラス**（`inc/rest/`）。名前空間 `agent-neo/v1`（コード内参照 54 箇所） |
| 主要コントローラ | sections / sections-read / pages / pages-read / posts / blocks / elements / ad-zones / ad-tags / ctas / affiliate / ab-test / tracking(+export) / design-tokens / blueprint / actions / jobs / migration / risks / seo / llmo-summary / features / settings / status / health / logs / media / license / auth / blog-card / automation-seo / public |
| スキーマ | `schema/` に JSON Schema 6 本 + `openapi.yaml`（`/status` ほか） |
| 特徴的モジュール | `inc/mcp/`（MCP）・`inc/json/`（中間 JSON）・`inc/catalog/`・`inc/design/`・`inc/tracking/`・`inc/lifecycle/`・`inc/cli/` |
| 構造化データ（テーマ側） | `BlogPosting` `WebPage` `WebSite` `BreadcrumbList` `Organization` `Person` `ImageObject` `ListItem` |

### `ad-zone.schema.json` の既存記述（重要）
> 広告ゾーン定義スキーマ。**CARRY-A2-001: テーマA の 4 ゾーン（h2 前挿入 / 記事終 / 関連上 / カテゴリ別上書き）に対応する静的管理**。REQ-NF-025 厳守。

→ **テーマA のゾーン構造を取り込む意図が既にスキーマに刻まれている**。本調査はその前提を実測で裏取りした形。

## 4. 構造的性格（要約）

1. **FSE / theme.json v3 の宣言的テーマ**。設定正本は JSON（theme.json + config/*.json）で、PHP は薄い。
2. **パーツ＝パターン**。ブロックを自作せず、コアブロックの組み合わせをパターンとして台帳化する方針。
3. **エージェント接点が最初から一級**（34 REST コントローラ + MCP + CLI + 中間 JSON）。テーマA（REST 0）・テーマB（wp/v2 相乗り 14）とは設計思想が別物。
4. **弱点は運用面の面積**: ウィジェットエリア 0・メニュー 0・ショートコード 0・CPT 0。
   実運用 2 サイトが依存している「記事内広告ゾーン」「再利用パーツ」「追尾サイドバー」に対応する受け皿が無い。
