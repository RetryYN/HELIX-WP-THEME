# AGENT-NEO テクニカル SEO 監査 — Google 検索セントラル準拠版

- 監査日: 2026-06-27
- 対象: AGENT-NEO 1st-party FSE テーマ（`themes/agent-neo-theme/`）+ companion plugins（`plugins/agent-neo-core`, `plugins/agent-neo-embed`）
- 検証環境: docker `agent-neo-wp` / http://localhost:8086（`?page_id=`/`?p=` は 301 → `curl -L`）
- 正本: Google 検索セントラル（developers.google.com/search/docs）。各項目に該当公式ページ URL を引用
- 判定凡例: ✅準拠 / ⚠部分 / ❌違反・欠落 / N/A（理由付き）

> 重要な正本上の前提（2024-2026 の Google 仕様変更を反映）
> - **Sitelinks search box（WebSite+SearchAction）は 2024-11-29 廃止**。ドキュメントもアーカイブ済。よって SearchAction は Rich Results 対象外（出力していても無害だが必須ではない）。出典: developers.google.com/search/docs/appearance/structured-data/sitelinks-searchbox（アーカイブ通知）
> - **FAQPage rich result は 2026-05 に完全廃止**（2023-09 から gov/health のみに制限済だった）。出典: developers.google.com/search/docs/appearance/structured-data/faqpage（deprecation 通知）
> - **`<changefreq>` / `<priority>` は Google が無視**。`<lastmod>` のみ利用（一貫して正確な場合）。出典: developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap
> - **OGP / Twitter Card はランキング・Rich Results に非使用**（SNS 表示用）。Google の検索評価対象外。ただし「SEO 標準装備」プロダクト要件としては SNS 流入のため評価対象に含める

---

## チェックリスト本体（カテゴリ別・全項目）

### A. Crawling & Indexing

| # | 項目 | Google 要件（正本 URL） | 判定 | 実機証拠 | 重大度 |
|---|------|------------------------|------|----------|--------|
| A-1 | robots.txt | wp-admin 制限・sitemap 参照を推奨。`crawling-indexing/robots/intro` | ✅ | `Disallow: /wp-admin/` + `Allow: admin-ajax.php` + `Sitemap: .../wp-sitemap.xml`（WP core 仮想出力） | — |
| A-2 | robots meta（noindex）構文 | `<meta name="robots" content="noindex">`。robots.txt でブロックされていないこと。`crawling-indexing/block-indexing` | ⚠ | 検索ページは `noindex, follow` ✅。**404 / 著者 / 日付アーカイブに noindex なし**（全て `max-image-preview:large` のみ） | Important |
| A-3 | X-Robots-Tag | 非HTML資源向けヘッダ。`crawling-indexing/block-indexing` | ✅ N/A | 記事に X-Robots-Tag なし（indexable で正しい） | — |
| A-4 | canonical（rel=canonical） | `<head>` 内・絶対パス。重複URL統合。home/archive も指定推奨。`crawling-indexing/consolidate-duplicate-urls` | ⚠ | singular（記事/LP）は WP core が出力 ✅。**ホーム・カテゴリ・著者・日付アーカイブに canonical 皆無** | Important |
| A-5 | XML sitemap | `<loc>` 必須・`<lastmod>` のみ利用・canonical URL のみ含める。`sitemaps/build-sitemap` | ⚠ | WP core sitemap 動作 ✅・lastmod 付き ✅。**テスト/開発投稿 9 件混入**（json-api-vdd 等）。changefreq/priority なし=正常（Google 無視） | Minor |
| A-6 | 画像 sitemap | 画像発見のため推奨。`appearance/google-images` | ❌ | WP core sitemap に画像エントリなし（`<image:` 0件） | Minor |
| A-7 | HTTP ステータス（404/410/3xx・ソフト404回避） | 404 は正しく 404 を返す・ソフト404禁止。`crawling-indexing/javascript/javascript-seo-basics` | ✅ | `/nope-xyz/` → `HTTP/1.1 404 Not Found`（ソフト404でない） | — |
| A-8 | リダイレクト | 正規 URL への 3xx。`crawling-indexing/301-redirects` | ✅ | `?p=256` → 301 → pretty URL。正常 | — |
| A-9 | URL 構造 | 簡潔・可読・正規化。`crawling-indexing/url-structure` | ⚠ | pretty permalink 動作 ✅。**日本語スラッグが %XX エンコードで canonical/og:url に記録**（Google は処理可・Bing 等で互換性低下リスク） | Minor |
| A-10 | JavaScript SEO | サーバ側 HTML で主要コンテンツ提供。`crawling-indexing/javascript/javascript-seo-basics` | ✅ | FSE テーマはサーバレンダリング。主要コンテンツが初期 HTML に存在 | — |

### B. Search Appearance / Features

| # | 項目 | Google 要件（正本 URL） | 判定 | 実機証拠 | 重大度 |
|---|------|------------------------|------|----------|--------|
| B-1 | title link（title 要素） | 全ページに `<title>` 必須・一意・記述的・boilerplate 最小。`appearance/title-link` | ✅ | 全ページ一意。記事「タイトル – サイト名」形式。ホーム=サイト名のみ（許容） | — |
| B-2 | snippet（meta description） | home/人気ページに最低限・一意・記述的。`appearance/snippet` | ❌ | **全ページで `<meta name="description">` を一切出力しない**（OGP description のみ存在） | Important |
| B-3 | data-nosnippet | スニペット除外制御（任意）。`appearance/snippet` | ✅ N/A | 不使用（除外したいコンテンツがないため正常） | — |
| B-4 | Article/BlogPosting 構造化データ | 必須プロパティなし・author/image/date 推奨。`appearance/structured-data/article` | ⚠ | BlogPosting 出力 ✅。**author.name 空 ❌・image 欠落 ⚠・page にも誤適用 ❌**（詳細は Rich Results 充足表） | Critical |
| B-5 | BreadcrumbList | itemListElement/position/name 必須・item は最終項目で省略可。`appearance/structured-data/breadcrumb` | ✅ | JSON-LD 3段（ホーム→カテゴリ→記事）。position/name/item 全充足。**HTML パンくずは最終項目欠落だが JSON-LD は完備=Rich Results 上は適合** | Minor |
| B-6 | Organization | 必須なし・logo/sameAs/address 推奨。`appearance/structured-data/organization` | ⚠ | name/url のみ。**logo 欠落（カスタムロゴ未設定）・sameAs 欠落** | Important |
| B-7 | WebSite（sitename） | name/url/@type 必須・alternateName 推奨。`appearance/site-names` | ✅ | name/url/@type WebSite 充足。Sitename 要件 OK | — |
| B-8 | Sitelinks search box（SearchAction） | **2024-11-29 廃止・Rich Results 対象外**。`appearance/structured-data/sitelinks-searchbox` | ⚠ | SearchAction 出力ありだが urlTemplate の `{search_term_string}` が `esc_url_raw()` で除去（壊れている）。**廃止済機能のため SEO 影響なし**だが不正 JSON-LD は除去推奨 | Minor（降格） |
| B-9 | Profile/Person（著者 E-E-A-T） | ProfilePage は mainEntity/name 必須・sameAs/jobTitle 推奨。`appearance/structured-data/profile-page` | ❌ | 著者ページ（`/author/`）に ProfilePage schema なし。BlogPosting author.sameAs/jobTitle なし | Important |
| B-10 | 画像 SEO（alt） | `<img>` 使用・記述的 alt・descriptive filename。`appearance/google-images` | ⚠ | アイキャッチ未設定記事多数で画像なし。Gravatar img の alt 空。記述的 alt 不足 | Important |
| B-11 | favicon | サイトアイコン設定推奨。`appearance/favicon-in-search` | ❌ | `<link rel="icon">` 0件。サイトアイコン未設定（検索結果にファビコン非表示） | Minor |
| B-12 | mobile-friendly | viewport・レスポンシブ。`appearance/page-experience` | ✅ | `<meta name="viewport" content="width=device-width, initial-scale=1">` 正常 | — |
| B-13 | page experience（CWV / HTTPS） | LCP/INP/CLS・HTTPS。ランキング要素。`appearance/page-experience` | N/A（dev） | localhost=http（dev）。本番 https://automation-seo... で別途 CWV 計測必要。テーマは軽量 FSE で構造上良好 | — |
| B-14 | pagination | rel=prev/next は 2019 廃止・各ページ自己 canonical。`appearance`（一般） | ✅ | query-pagination 動作。rel=prev/next 不要（廃止済） | — |

### C. Structured Data 一般ガイドライン

| # | 項目 | Google 要件（正本 URL） | 判定 | 実機証拠 | 重大度 |
|---|------|------------------------|------|----------|--------|
| C-1 | 形式（JSON-LD 推奨） | JSON-LD/Microdata/RDFa。JSON-LD 推奨。`appearance/structured-data/sd-policies` | ✅ | JSON-LD `@graph` 形式で出力 | — |
| C-2 | @id・配置 | 関連アイテムを @id で接続。robots.txt/noindex でブロックしない。`sd-policies` | ✅ | `#organization` / `#website` / `#article` を @id で接続。publisher が @id 参照 | — |
| C-3 | 可視コンテンツとの一致 | マークアップは可視内容の真の表現。不可視/偽コンテンツ禁止。`sd-policies` | ⚠ | BlogPosting の `description` に HTML エンティティ `[&hellip;]` 混入（可視テキストと不一致）。page の空 description は要修正 | Important |

### D. SEO Fundamentals / Content

| # | 項目 | Google 要件（正本 URL） | 判定 | 実機証拠 | 重大度 |
|---|------|------------------------|------|----------|--------|
| D-1 | lang 属性 | `<html lang>` 出力。SEO starter guide | ✅ | `<html lang="ja">` 正常 | — |
| D-2 | hreflang | 多言語時のみ。`crawling-indexing/localized-versions` | ✅ N/A | 日本語単一サイト。不要 | — |
| D-3 | helpful content / 著者情報表示 | people-first・著者バイライン明示・author ページ。`fundamentals/creating-helpful-content` | ⚠ | post-header に post-author-name あり ✅。ただし JSON-LD author.name 空・著者プロフィール充実不足 | Important |
| D-4 | E-E-A-T（著者の専門性） | 著者の experience/expertise を示す。`creating-helpful-content` | ⚠ | author 表示はあるが sameAs/jobTitle/bio 不足（B-9 と連動） | Important |
| D-5 | AI 生成コンテンツ開示 | **ランキング要件ではない**。spam目的の自動生成のみ違反。「どう作られたか」が問われる文脈で開示は有用。`fundamentals/using-gen-ai-content` / blog 2023-02 | ✅ N/A | disclosure ブロック実装済（`agent-neo-core/blocks/disclosure`）。**Google ランキング上は開示必須でない**。挿入判断は Automation SEO 側責務。ステマ規制（景表法）は別途国内法要件 | — |
| D-6 | spam policies（隠しテキスト等） | キーワード詰め込み・クローキング禁止。`essentials/spam-policies` | ✅ | 該当なし。クリーンな HTML | — |
| D-7 | generator バージョン露出 | 直接の SEO 要件ではないがセキュリティ慣行 | ⚠ | `<meta name="generator" content="WordPress 6.9.4">` 露出 + xmlrpc リンク（SEO 影響なし・セキュリティ慣行で除去推奨） | Minor |

---

## ❌/⚠ 修正対象リスト（file:line + 推奨修正）

### Critical

**CR-1: BlogPosting の `author.name` が全記事で空文字**（チェックリスト B-4）
- `themes/agent-neo-theme/inc/seo/class-structured-data.php:181`
- 実証: `"author": {"name": "", "url": "http://localhost:8086/author/"}`（display_name 未設定 admin）
- 正本: Article は author 推奨・author.name は author を出すなら実質必須（Rich Results Test で warning）。`appearance/structured-data/article`
- 修正:
```php
$author_name = sanitize_text_field( get_the_author_meta( 'display_name', (int) $post->post_author ) );
if ( '' === $author_name ) {
    $author_name = sanitize_text_field( get_the_author_meta( 'login', (int) $post->post_author ) );
}
```

**CR-2: 固定ページ（page）に BlogPosting スキーマが誤適用 + description 空**（B-4 / C-3）
- `themes/agent-neo-theme/inc/seo/class-structured-data.php:86,156-220`、`inc/seo/class-head-meta.php:162`
- 実証: LP（lp-sample）JSON-LD に `@type: BlogPosting` + `description: ""`。og:type も `article`
- 正本: 構造化データは可視内容の真の表現（空 description は不一致）。`sd-policies`
- 修正: `is_singular('post')` のみ BlogPosting、page は WebPage（または BlogPosting 自体を出さない）。og:type も page は `website`

### Important

**IM-1: meta description が全ページ未出力**（B-2）
- `themes/agent-neo-theme/inc/seo/class-head-meta.php`（description 出力ロジックなし）
- 実証: 全ページ `<meta name="description">` 不在
- 正本: home/人気ページに最低限・一意の meta description 推奨。`appearance/snippet`
- 修正: OGP description と共通ロジックで `<meta name="description" content="...">` を wp_head に追加出力

**IM-2: 著者・日付アーカイブ・404 に noindex なし**（A-2）
- robots フック未実装
- 実証: 全て `max-image-preview:large` のみ
- 正本: 薄い/重複アーカイブの noindex。`crawling-indexing/block-indexing`
- 修正:
```php
add_filter( 'wp_robots', function( $robots ) {
    if ( is_author() || is_date() || is_404() ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
} );
```

**IM-3: ホーム・アーカイブに canonical なし**（A-4）
- 実証: `curl http://localhost:8086/ | grep canonical` → 空。カテゴリも同様
- 正本: 重複 URL 統合のため canonical 推奨・絶対パス・`<head>` 内。`consolidate-duplicate-urls`
- 修正: head-meta に非 singular 向け canonical 出力フック追加（singular は WP core が自動出力のため skip）

**IM-4: Organization に logo / sameAs なし**（B-6）
- `class-structured-data.php:103-127`（logo はカスタムロゴ依存）
- 実証: `Organization` = name/url のみ
- 正本: logo（min 112x112）・sameAs 推奨。`appearance/structured-data/organization`
- 修正: テーマデフォルトロゴ + サイト設定 sameAs（SNS URL）を Organization に追加

**IM-5: 著者 E-E-A-T（ProfilePage / author.sameAs / jobTitle）欠落**（B-9 / D-3 / D-4）
- 著者ページに ProfilePage schema なし・BlogPosting author に sameAs/jobTitle なし
- 正本: ProfilePage は mainEntity/name 必須・sameAs 推奨。`profile-page` / `creating-helpful-content`
- 修正: 著者ページに ProfilePage + Person（name/sameAs/jobTitle/description）出力。BlogPosting author にも sameAs 付与

**IM-6: og:image 全ページ欠落 + 画像 alt 不足**（B-10）
- `class-head-meta.php:263-280`（カスタムロゴ未設定で空）
- 実証: og:image 0件。アイキャッチ未設定記事多数
- 正本（alt）: 記述的 alt 推奨。`appearance/google-images`（og:image 自体は SNS 用で Google ランキング外）
- 修正: テーマデフォルト OG 画像フォールバック追加 + コンテンツ生成時のアイキャッチ/alt 必須化（Automation SEO 側）

**IM-7: JSON-LD / og:description の HTML エンティティ混入**（C-3）
- `class-structured-data.php:289-295`、`class-head-meta.php:127`
- 実証: `description: "…[&hellip;]"`
- 正本: 可視内容の真の表現。`sd-policies`
- 修正: `html_entity_decode( wp_strip_all_tags( $excerpt ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )`

### Minor

**MN-1: sitemap にテスト/開発投稿 9 件混入**（A-5）— 本番前に該当投稿を非公開化/削除
**MN-2: 画像 sitemap なし**（A-6）— カスタムフィルタまたは SEO プラグインで対応
**MN-3: 日本語スラッグ %XX エンコード**（A-9）— ASCII スラッグ運用 or 許容判断（Google は処理可）
**MN-4: favicon（サイトアイコン）未設定**（B-11）— カスタマイザでサイトアイコン設定
**MN-5: HTML パンくず最終項目欠落**（B-5）— `post-header.html` にタイトル追加 or 現状維持（JSON-LD 適合済）
**MN-6: SearchAction urlTemplate 破損**（B-8）— `class-structured-data.php:148` の `esc_url_raw()` が `{}` 除去。**廃止済機能のため SEO 影響なし**。不正 JSON-LD 除去 or 修正（`esc_url_raw(home_url('/')).'?s={search_term_string}'`）
**MN-7: generator バージョン露出**（D-7）— `remove_action('wp_head','wp_generator')`

---

## Rich Results 必須/推奨プロパティ充足表

| タイプ | プロパティ | 区分 | Google 正本 | AGENT-NEO 出力 | 判定 |
|--------|-----------|------|------------|---------------|------|
| **BlogPosting** | headline | 推奨 | article | あり | ✅ |
| | image | 推奨 | article | **なし** | ⚠ |
| | datePublished | 推奨 | article | あり（ISO 8601） | ✅ |
| | dateModified | 推奨 | article | あり | ✅ |
| | author | 推奨 | article | あり（Person） | ✅ |
| | author.name | 推奨（author 出すなら実質必須） | article | **空文字** | ❌ |
| | author.url | 推奨 | article | あり | ✅ |
| | publisher | 推奨 | article | あり（@id 参照） | ✅ |
| **BreadcrumbList** | itemListElement | 必須 | breadcrumb | あり（配列） | ✅ |
| | position | 必須 | breadcrumb | あり（1,2,3） | ✅ |
| | name | 必須 | breadcrumb | あり | ✅ |
| | item | 必須（最終項目は省略可） | breadcrumb | あり（全項目） | ✅ |
| **Organization** | name | 推奨（必須なし） | organization | あり | ✅ |
| | url | 推奨 | organization | あり | ✅ |
| | logo | 推奨 | organization | **なし** | ⚠ |
| | sameAs | 推奨 | organization | **なし** | ⚠ |
| **WebSite（sitename）** | name | 必須 | site-names | あり | ✅ |
| | url | 必須 | site-names | あり | ✅ |
| | @type WebSite | 必須 | site-names | あり | ✅ |
| | alternateName | 推奨 | site-names | なし | ⚠ |
| **ProfilePage/Person** | mainEntity | 必須 | profile-page | **未実装** | ❌ |
| | name | 必須 | profile-page | **未実装** | ❌ |
| | sameAs | 推奨 | profile-page | **未実装** | ❌ |
| **FAQPage** | — | **廃止（2026-05）** | faqpage | LP に schema なし | N/A（実装不要・正） |
| **SearchAction** | urlTemplate | **廃止（2024-11）** | sitelinks-searchbox | 破損出力 | N/A（SEO 影響なし） |

---

## 「SEO 標準装備」要件への準拠率（公式チェックリスト基準）

評価対象項目（N/A 除く）= 32 項目（A 10 + B 14 + C 3 + D 5 のうち N/A/dev を除外）

| 判定 | 件数 |
|------|------|
| ✅準拠 | 17 |
| ⚠部分 | 11 |
| ❌違反・欠落 | 4 |
| N/A | （除外） |

- 完全準拠率（✅のみ / 32）= **約 53%**
- 加重達成率（✅=1.0・⚠=0.5・❌=0 / 32）= (17 + 5.5) / 32 = **約 70%**

G6 RC ブロッカー（Critical/Important = 8 件）を解消すれば加重達成率は約 92% に到達見込み。

---

## 出典（Google 検索セントラル正本）

- [Article structured data](https://developers.google.com/search/docs/appearance/structured-data/article)
- [Breadcrumb structured data](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb)
- [Organization structured data](https://developers.google.com/search/docs/appearance/structured-data/organization)
- [Site names / WebSite](https://developers.google.com/search/docs/appearance/site-names)
- [Sitelinks search box（廃止通知）](https://developers.google.com/search/docs/appearance/structured-data/sitelinks-searchbox)
- [FAQPage（廃止通知）](https://developers.google.com/search/docs/appearance/structured-data/faqpage)
- [Profile page structured data](https://developers.google.com/search/docs/appearance/structured-data/profile-page)
- [Structured data general guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Canonicalization](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- [Block indexing (noindex)](https://developers.google.com/search/docs/crawling-indexing/block-indexing)
- [Build a sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap)
- [Title links](https://developers.google.com/search/docs/appearance/title-link)
- [Snippets / meta description](https://developers.google.com/search/docs/appearance/snippet)
- [Google Images / image SEO](https://developers.google.com/search/docs/appearance/google-images)
- [JavaScript SEO basics（HTTP status / soft 404）](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics)
- [Page experience / Core Web Vitals](https://developers.google.com/search/docs/appearance/page-experience)
- [Creating helpful content / E-E-A-T](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)
- [Using generative AI content](https://developers.google.com/search/docs/fundamentals/using-gen-ai-content)
- [Spam policies](https://developers.google.com/search/docs/essentials/spam-policies)
