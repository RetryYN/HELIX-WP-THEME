# 外部リサーチ: 現要求 97 件に対する想起漏れの洗い出し（2026-09-03）

> 位置づけ: 探索証跡。PO 決定ではない。ここに挙げた項目は「X ができる。採用するか」の問いの候補であり、
> 採否は 1 件ずつ PO に提示して discovery event に記録する（WT-AGENT-VARIETY の規律）。
> 参照日はすべて 2026-09-03。出典は公式・一次情報を優先し、二次情報のみの箇所は「未検証」と記す。
> 第三者製品名・ベンダー名は伏せ字（国内主要有料テーマ、大手 CDN、国内主要共用ホスティング 等）。

## 1. 方法

- 入力: `docs/requirements/l3/requirements-ir.json` の 97 要求（PR #86 merge 時点、head c575638）と
  `docs/research/wp-ecosystem-20260620.md`（旧調査）。
- 調査 6 系統を並行実施: (1) WordPress 公式、(2) Google 公式、(3) 他検索エンジン・AI プロバイダ公式と標準化団体、
  (4) 国内外テーマベンダー・技術ブログ・WordCamp、(5) サーバー・ホスティング・CDN、(6) 法令・アクセシビリティ・OSS 配布。
- 各系統 15〜35 件の候補を、重複を畳んで本書の判断事項に統合した。既存要求で十分と判断した項目は §7 に列挙。

## 2. 最優先: 現要求と外部正本が矛盾または食い違う項目（12 件）

要求本文が外部の公式ドキュメントと衝突しているもの。採否以前に「要求を直すか」の判断が要る。

| # | 事項 | 外部正本 | 触れる要求 | 問い |
|---|---|---|---|---|
| X-01 | FAQPage リッチリザルトは 2026-05-07 に表示停止、2026-06-15 に doc 削除。HowTo は 2023-09 終了 | developers.google.com/search/updates | WT-FR-SEO-02 が両型の自動生成を明記、SEO-01「対象外型を出さない」と矛盾 | FAQPage / HowTo の JSON-LD を既定で出さない（他エンジン向け任意 ON）にできる。採用するか |
| X-02 | Sitelinks Search Box（WebSite.potentialAction / SearchAction）は 2024-11 廃止 | /appearance/structured-data/sitelinks-searchbox | WT-FR-SEO-01 が SearchAction 追加を明記 | SearchAction を要求から外し、WebSite は site name 用 name / alternateName / url のみにできる。採用するか |
| X-03 | Google AI 最適化ガイド（2026-07-10）: llms.txt 等の AI 向けファイルは不要「Skip llms.txt」。AI Overviews の制御は nosnippet / max-snippet / data-nosnippet のみ。Google-Extended は検索と無関係。他 AI 事業者も第三者サイトの llms.txt を読む公式表明なし | /fundamentals/ai-optimization-guide、llmstxt.org | WT-FR-SEO-03（llms.txt・crawl-map・LLMO summary を既定出力）、WT-FR-CRAWL-02/03 | llms.txt を「提案段階・効果未実証・他事業者向け任意」と位置づけ直し、CRAWL-02 のアクセス計測を実証手段にできる。採用するか |
| X-04 | ステマ規制 Q&A（2023-10）は「表示内容全体から明瞭」を要件とし、文字サイズ・色・位置で実際に認識されることを求める | caa.go.jp ステルスマーケティング Q&A | WT-FR-VOCAB-03「最小限」「大々的に出さない」 | PR 表記に機械検査の下限（本文と同等サイズ・ファーストビュー内・AA コントラスト）を置き、「控えめ」はその上でのみ選べるようにできる。採用するか |
| X-05 | WP 7.1 responsive block styles（`@mobile` / `@tablet`、`settings.viewport`）は max-width の desktop-first。基準スタイル = PC | make.wordpress.org/core/2026/08/05 responsive-block-styles | WT-FR-SP-01「SP 基準、PC は派生」 | コアの responsive styles を使う（既定 = PC、`@mobile` で上書き）か、`responsiveEditingEnabled` を切って SP 基準の自前尺度を維持するか、どちらかにできる。採用するか |
| X-06 | WP 7.0 Block Visibility（viewport 別非表示）は DOM に残し CSS で隠す | make.wordpress.org/core/2026/03/15 block-visibility | WT-FR-SP-02「SP 専用広告面」、WT-FR-ZONE-01「空なら描画しない」、WT-NFR-PERF-02 | SP 専用面はサーバー側条件描画（slot）に限定し、コアの Block Visibility UI を編集者に開放しない（または開放して転送量予算で受ける）。採用するか |
| X-07 | WP 7.0 ブロック単位 custom CSS がコア入り | wordpress.org/news/2026/05/armstrong | WT-FR-VALUE-01/02（生値は警告、許容リスト）、WT-NFR-VALUE-03 | ブロック単位 / 記事単位の任意 CSS を「封じる / 許容リスト検査 / 警告域」のいずれかにできる。採用するか |
| X-08 | WP 7.1 client-side media processing（wasm-vips: リサイズ・WebP/AVIF・GIF→MP4/WebM をブラウザ側で実行、サーバー hook も二重発火） | make.wordpress.org/core/2026/07/22 | WT-FR-IMG-01/02（WP-Cron サーバー側前提） | 7.1 のクライアント側処理を主経路にし、Cron ジョブは既存画像の再生成に限定できる。採用するか |
| X-09 | コアは `wp_get_loading_optimization_attributes()` で fetchpriority / lazy を自動付与。二重指定は `_doing_it_wrong` | developer.wordpress.org reference | WT-FR-IMG-01「hero は fetchpriority=high」、WT-NFR-SEC-01「Warning 0」 | hero 指定をコアの filter 経由に限定し、それ以外はコア heuristic に委ねる。採用するか |
| X-10 | Product は product snippet と merchant listing の 2 経路。他サイトへリンクするページは merchant listing 非適格 | /structured-data/merchant-listing, /product-snippet | WT-FR-SELL-02 | 商品正本の「リンク先種別」に応じて snippet 形 / merchant listing 形を出し分けできる。採用するか |
| X-11 | Consent Mode v2 は 7 種（ad_storage / analytics_storage / ad_user_data / ad_personalization / functionality_storage / personalization_storage / security_storage）、default は gtag 読込前、basic / advanced、region 別 | developers.google.com/tag-platform/security/guides/consent | WT-FR-TAG-03（必須 / 計測 / 広告の 3 カテゴリ） | 同意カテゴリを 7 種へ写像する対応表をデータ層契約に含め、head slot の注入順（consent default 最初）を契約で固定できる。採用するか |
| X-12 | hreflang は多言語 / 多地域のみ。単一言語では不要 | /specialty/international/localized-versions | WT-FR-SEO-03/04（全ページ種別で出す） | 日本語単一サイトでは hreflang を出さない既定にし、多言語時のみ相互参照検査付きで出す。採用するか |

## 3. 分野別の判断事項

重要度: high = 現要求の前提や主用途に直結、medium = 設計時に決めないと後付けが高い、low = 明文化・境界確認のみ。
「触れる要求」が none のものは新規候補。

### 3.1 WordPress 公式（7.0 / 7.1）

| # | 事項 | 出典 | 触れる要求 | 重要度 |
|---|---|---|---|---|
| W-01 | MCP 常用パックの実装基盤を Abilities API（6.9〜、7.1 で lifecycle filter・JSON Schema 整形）+ MCP Adapter にし、MCP / REST / CLI の 3 面をコア機構で得る | developer.wordpress.org/apis/abilities-api、WordPress/mcp-adapter | WT-FR-AGENT-01、WT-TR-CLI-01、WT-TR-AGENT-05（自前名前空間と要整合） | high |
| W-02 | 7.1 `meta.public` / `show_in_rest` / `mcp.public` の露出フラグと「公開≠認可」。全 ability に permission_callback | make.wordpress.org/core/2026/08/04 | WT-NFR-SEC-01、WT-NFR-PERM-01 | high |
| W-03 | MCP annotations（readonly / destructive / idempotent / openWorld）を各操作に必須化し destructive → dry-run receipt 必須へ機械接続 | WordPress/mcp-adapter README | WT-FR-AGENT-01、WT-NFR-PERM-01 | medium |
| W-04 | 鍵管理を WP 標準 Application Passwords（専用ロールユーザー + read / write 別）の薄いラッパにする | developer.wordpress.org/news/2026/02 mcp-adapter | WT-FR-ADMIN-04 | high |
| W-05 | 7.0 AI Client / Connectors（`wp_ai_client_prevent_prompt`）を boundary guard の対象に含め、Connectors 設定有無を manifest に載せる | make.wordpress.org/core/2026/03/24 | WT-TR-CORE-03 | medium |
| W-06 | Guidelines / Knowledge は 7.1 未 merge。正本は HELIX 側、WP は読取ミラーの方針を先に決めるか静観 | make.wordpress.org/core/2026/06/22 | none | low |
| W-07 | 7.1 pseudo / custom states（`:hover` `:focus-visible` `-current`）を theme.json 宣言へ移し、値差し替えのみ許可 | make.wordpress.org/core/2026/08/05 | WT-FR-LOOK-01、WT-NFR-VALUE-03、WT-TR-CORE-01 | medium |
| W-08 | 7.0 navigation-overlay template part area でドロワーを提供 | make.wordpress.org/core/2026/03/04 | WT-FR-SP-02、WT-FR-PARTS-01 | medium |
| W-09 | dimension presets、7.1 minWidth / background.gradient を尺度（安全域）に含める | make.wordpress.org/core/2026/03/15 dimensions | WT-FR-VALUE-01/02 | medium |
| W-10 | Block Bindings（post-meta / post-data / term-data / pattern overrides）で商品カード・CTA 文言・PR 表記・著者を結線し保存 HTML に値を固定しない | developer.wordpress.org block-bindings | WT-FR-META-01、WT-FR-SELL-02、WT-FR-AGENT-04 | medium |
| W-11 | slot 挿入を Block Hooks（`hooked_block_types`、`ignoredHookedBlocks`）で表現し、自前 do_action と役割分担 | developer.wordpress.org hooked_block_types | WT-FR-ZONE-01、WT-FR-SECTION-02、WT-FR-AGENT-03 | medium |
| W-12 | 7.0 PHP-only block registration（`supports.autoRegister`）で「正本 ID を参照するだけ」のブロック（商品カード・ランキング・比較テーブル・CTA 束）を JS なしで作る | make.wordpress.org/core/2026/03/03 | WT-FR-VOCAB-01、WT-FR-SELL-02、WT-NFR-PERF-01 | medium |
| W-13 | コア Tabs（7.1 安定）+ block style（SP はアコーディオン化）で「タブ」を置き換え、新規ブロック枠を 6 に減らす | make.wordpress.org/core/2026/08/05 field guide | WT-FR-VOCAB-01 | medium |
| W-14 | 7.1 SVG Icon API（`wp_register_icon_collection`）で SNS・SP 下部タブのアイコンを共用 | make.wordpress.org/core/2026/07/24 | WT-FR-SNS-01、WT-FR-SP-02 | low |
| W-15 | Font Library（7.0 専用ページ）を無効化またはテーマ提供コレクションのみ許可。和文フォント 2〜3 系統を unicode-range 分割 woff2 で自己ホスト、palt | wp_register_font_collection、web.dev font-best-practices | WT-NFR-PERF-02 | high |
| W-16 | Interactivity API（Script Modules）を必要 JS の統一基盤にし独自バンドルを持たない | make.wordpress.org/core/2026/02/23 | WT-NFR-PERF-01 | medium |
| W-17 | Speculative Loading（6.8 コア、7.1 でキャッシュ検出時 moderate 既定）: A/B 対象・LP を除外パスに、データ層に `document.prerendering` 待ちを必須化 | wp_get_speculation_rules、developer.chrome.com prerender-pages | WT-FR-AB-02、WT-FR-TAG-02/03、WT-FR-CRAWL-01 | high |
| W-18 | Performance Lab 系（Image Placeholders / Enhanced Responsive Images / Embed Optimizer）を PLUGIN-02 の譲渡領域に追加 | wordpress.org/plugins/performance-lab（4.2.0、2026-08-26） | WT-TR-PLUGIN-02 | low |
| W-19 | `Update URI` ヘッダー必須 + 更新配信経路（Git Updater 互換 / Core プラグイン自前 / ハーネス配備）を決める | make.wordpress.org/core/2022/10/06 update-uri | WT-TR-PLUGIN-01 の「配備」実体、none | high |
| W-20 | 最低 WP / PHP を要求 ID で固定（WP 7.0/7.1 は PHP 7.4〜8.5、PHP 8.3 は security-only）、style.css ヘッダーと CI マトリクス（8.1〜8.5、MySQL 8.4 / MariaDB 11.x） | php-compatibility-and-wordpress-versions、hosting handbook server-environment | none（CLAUDE.md の PHP ≥ 8.1 に要求 ID なし） | medium |
| W-21 | 7.1 iframed 投稿エディタでブロック JS / CSS が動くことを G-E1 に追加 | 7.1 field guide | WT-NFR-GATE-01 | medium |
| W-22 | Theme Check / Plugin Check（2.1.0）を静的ゲートに追加。「plugin territory」（CPT・ショートコード）を赤にし、LP CPT・商品正本をプラグイン側へ | make.wordpress.org/themes/handbook/review/required（2026-06-09 改定） | WT-NFR-GATE-01、WT-FR-LP-01、WT-FR-SELL-01 | medium |
| W-23 | Playground Blueprint（テーマ + Core プラグイン + サンプル記事）を PoC 比較・PO レビューのブラウザ内プレビューに使う。G-E1 は docker のまま | developer.wordpress.org/playground、wp-env `--runtime=playground` | WT-NFR-COST-01 | low |
| W-24 | WP 標準 privacy tools（exporter / eraser / `wp_add_privacy_policy_content`）にリード・同意記録・操作ログを登録 | developer.wordpress.org/plugins/privacy | WT-NFR-CV-01、WT-FR-ADMIN-02、WT-FR-CRAWL-01 | high |
| W-25 | accessibility-ready 18 要件（2026-05-12 改定）のうち機械検査可能なもの（skip link・landmark・focus 可視・reflow・text-spacing・target=_blank 警告）を A11Y ゲートに追加 | themes/handbook/review/accessibility/required | WT-NFR-A11Y-01 | medium |
| W-26 | i18n は Text Domain と翻訳関数の形式準拠に留め RTL 非対象。`.pot` を CI 生成、ソース言語（日 / 英）を決める | developer.wordpress.org internationalization | none | low |
| W-27 | 自サイト oEmbed の embed テンプレ提供または discovery 停止、feed 出力有無を同じ設定に | wp_oembed_add_discovery_links | WT-FR-SEO-03、WT-TR-CORE-01 | low |
| W-28 | 同意カテゴリに「外部埋め込み（機能）」を追加し同意前はプレースホルダ | themes review required「remote resources」 | WT-FR-SNS-02、WT-FR-TAG-03 | medium |

### 3.2 Google 公式（検索・Discover・計測）

| # | 事項 | 出典 | 触れる要求 | 重要度 |
|---|---|---|---|---|
| G-01 | 廃止型リスト（2025-06 の 7 型、Practice Problems 2025-11、FAQ、HowTo、SearchAction）を JSON 正本化し、docs updates RSS を差分監視の入力に | /search/updates、search_docs_updates.rss | WT-NFR-SEO-01 | medium |
| G-02 | 編集レビューの pros / cons（positiveNotes / negativeNotes）をレビューブロックから自動出力 | /structured-data/product-snippet | WT-FR-VOCAB-01、WT-FR-SELL-02 | medium |
| G-03 | Review snippet: author（Person 実名）必須、自社への Organization / LocalBusiness レビューは出力を検査で止める | /structured-data/review-snippet | WT-FR-SELL-02 | medium |
| G-04 | Organization 拡充（logo 112px 以上・legalName・識別子・返品ポリシー）、site name は hostname root のみ、favicon は 48px 倍数 PNG（SVG 非掲載、2026-08）。7 サブドメイン構成では root ごとに出力 | /structured-data/organization、/appearance/site-names、/appearance/favicon-in-search | WT-FR-SNS-01、WT-FR-SEO-01 | medium |
| G-05 | 著者正本（名前・経歴・sameAs・画像）+ 著者アーカイブに ProfilePage + Article.author.url / sameAs + 監修者（reviewedBy）+ 代表画像 3 比率 | /structured-data/article、/profile-page、creating-helpful-content | WT-FR-SNS-01 のみ | high |
| G-06 | 公開日 / 更新日の可視ラベルと timezone 付き ISO、dateModified の機械更新条件（本文変更時のみ）、sitemap lastmod と同一ソース、priority / changefreq 不使用 | /appearance/publication-dates、/sitemaps/build-sitemap | none、WT-FR-SEO-03、WT-FR-SECTION-02 | medium |
| G-07 | snippet 系 robots meta（max-snippet / nosnippet / data-nosnippet）をページ種別・section 単位で設定（AI Overviews の唯一の公式制御） | /crawling-indexing/robots-meta-tag | WT-FR-SEO-04、WT-FR-SECTION-02 | high |
| G-08 | Google-Extended と Googlebot を robots.txt で分離設定し、Google-Extended 拒否は AI Overviews に影響しないと UI で明示 | /google-common-crawlers（2026-07-14） | WT-FR-CRAWL-03 | high |
| G-09 | 公式 IP レンジ JSON（common-crawlers.json 等）の URL・逆引きパターンを正本にし WP-Cron で定期取得、取得不能時は最終版継続 | /google-common-crawlers | WT-FR-CRAWL-01 | medium |
| G-10 | 生成 robots.txt の 200 応答保証（失敗時は前回版静的配信）と 500KiB 検査 | /robots/robots_txt | WT-FR-CRAWL-03 | low |
| G-11 | Search Console 生成 AI レポート（2026-06、8/31 全展開）・Platform properties を HELIX 側 API 入力に | /search/blog/2026/06、/2026/07 | WT-TR-API-*（テーマ外） | low |
| G-12 | サイト評判濫用: 投稿メタに「提供元種別（自社 / 外部ライター / 配布）」を持ち外部由来を分離・可視化（EEA は 2026-08 に別サイト扱い） | /essentials/spam-policies（2026-08） | WT-FR-VOCAB-03、WT-FR-SEO-05 | medium |
| G-13 | 区間 / 記事の「AI 生成・AI 補助・人」フラグと編集責任者を記事メタに持ち、任意の開示ブロックと AI 画像への IPTC DigitalSourceType を出力（EU AI Act 50 条 2026-08-02、AI 事業者ガイドライン 1.2 版 2026-03-31 とも共通） | /fundamentals/using-gen-ai-content（2025-12-10）、digital-strategy.ec.europa.eu | WT-FR-SECTION-02、WT-FR-IMG-03、WT-FR-ADMIN-02 | medium |
| G-14 | page experience: ビューポート内広告面積上限、初回表示モーダル禁止（同意バー除く）を G-E1 検査 | /appearance/page-experience | WT-FR-ZONE-03、WT-NFR-SP-01 | medium |
| G-15 | A/B は「同一 URL + cookie、cookie 無し = 既定案」に統一（UA 判定は cloaking 疑義）。URL 分割型を許すなら canonical + 302 + 期間上限必須 | /crawling-indexing/website-testing | WT-FR-AB-01、WT-FR-SEO-04 | high |
| G-16 | 一覧は各ページ自己 canonical、無限スクロール / もっと見るは ?page=N + History API | /javascript/lazy-loading | WT-FR-SEO-03、WT-FR-VOCAB-01 | low |
| G-17 | SP 語彙変換（タブ→アコーディオン・カード化・横スクロール）でも初期 HTML に全本文を含むことを G-E1 検査（主コンテンツをユーザー操作で遅延ロードしない） | /mobile/mobile-sites-mobile-first-indexing | WT-FR-VOCAB-01、WT-FR-SP-03 | medium |
| G-18 | Discover Follow の feed 宣言（優先順付き複数 feed）、2026-02 Discover core update（専門性・独自性） | /appearance/google-discover、/search/blog/2026/02 | WT-FR-IMG-03 | low |
| G-19 | Preferred sources ボタン（2026-08、deeplink 版は JS 不要） | /appearance/preferred-sources | none | low |
| G-20 | bfcache 適格（unload 0、Cache-Control: no-store 不使用、pagehide で接続 close）を CI ゲート | web.dev/articles/bfcache | WT-NFR-PERF-01/03 | medium |
| G-21 | Lighthouse 13（2025-10-10）で監査 ID 変更。CI はメトリクス + insight ID 固定、major pin | developer.chrome.com/blog/lighthouse-13-0 | WT-NFR-PERF-03、WT-NFR-SP-02 | low |
| G-22 | Interop 2026 / Baseline: 語彙ごとに「Baseline Newly available 以上のみ採用、未達は CSS フォールバック」（details、popover / dialog、anchor positioning、scroll-driven animations） | web.dev/blog/interop-2026 | WT-NFR-PERF-01、WT-FR-LOOK-03 | medium |
| G-23 | GA4 推奨イベント名（view_item_list / select_item / view_promotion / select_promotion / generate_lead / purchase）と items 配列への標準写像を version 付きで同梱 | developers.google.com/analytics ga4/reference/events | WT-FR-TAG-02 | medium |
| G-24 | Conversion Linker、server-side tagging の 1st-party サブドメイン、7 サブドメインの cookie ドメイン方針、A/B cookie の名前空間 | support.google.com/google-ads/answer/7521212、tag-manager/server-side | WT-FR-TAG-01/03、WT-FR-AB-01 | medium |
| G-25 | Merchant Center フィードはアフィリエイト媒体向け経路なし → 要求にしない明示（自社 EC 併設時のみ別提案） | support.google.com/merchants | WT-FR-SELL-01 | low |
| G-26 | ProductGroup（バリエーション）は自社 EC のみ | /structured-data/product-variants | WT-FR-SELL-01 | low |

### 3.3 他検索エンジン・AI プロバイダ・標準化

| # | 事項 | 出典 | 触れる要求 | 重要度 |
|---|---|---|---|---|
| E-01 | クローラー台帳を 4 分類（訓練 / 検索索引 / ユーザー起動 / 広告・プレビュー）で持ち、WT-UI-11 の推移・許可拒否をこの軸で表示。初期データ（各社公式 UA + 出典 URL + 参照日）を JSON 同梱 | OpenAI bots、Anthropic crawler doc、Perplexity bots、Meta web-crawlers、Apple 119829、Amazonbot、CCBot | WT-FR-CRAWL-01/02 | high |
| E-02 | 公式 IP JSON 端点一覧（OpenAI 4 本、Anthropic bots.json、Perplexity 2 本、DuckDuckBot、Applebot、Amazonbot、Google）を設定 JSON に持ち定期取得と鮮度表示。Bing は要再確認、Meta は IP 非公開 | 同上 | WT-FR-CRAWL-01 | high |
| E-03 | 制御 token（Google-Extended / Applebot-Extended）はクローラーでなく来訪 0 → UI で別枠、来訪数に混ぜない | Google common-crawlers、Apple 119829 | WT-FR-CRAWL-02/03 | high |
| E-04 | robots.txt に従わない bot（ChatGPT-User、Perplexity-User、Claude-User、meta-externalfetcher）と反映遅延 24h を UI 明示、制御不能分はサーバー / WAF 責務 | 各社 doc | WT-FR-CRAWL-03 | medium |
| E-05 | 検証不能 bot（Bytespider、UA を出さない検索エンジン、逆引きのみのエンジン）は「未検証（UA 一致のみ）」別集計 | Brave Search crawler help、Baidu robots | WT-FR-CRAWL-01 | medium |
| E-06 | Content Signals（robots.txt `Content-Signal: search / ai-input / ai-train`、大手 CDN 発、業界提案）を用途別信号として設定・出力 | contentsignals.org（2025-09-24） | WT-FR-CRAWL-03 | high |
| E-07 | IETF aipref `Content-Usage` 行 / ヘッダ（Internet-Draft 2026-08-19）、RSL 1.0（2025-12-10、`License:` 行 + link rel=license）、TDMRep を同一設定 JSON から複数形式で出力 | datatracker.ietf.org/wg/aipref、rslstandard.org、w3.org tdmrep | none | medium（TDMRep は low） |
| E-08 | robots.txt 生成器を RFC 9309 準拠・1 本に集約し、robots.txt を PLUGIN-02 の譲渡領域に追加（第三者 SEO プラグインとの衝突回避） | RFC 9309 | WT-FR-CRAWL-03、WT-TR-PLUGIN-02 | medium |
| E-09 | IndexNow（Bing / Yandex / Naver / Seznam / Amazon、Bing は AI Performance で推奨 2026-02-10）を公開 / 更新 / 削除で送信。鍵は DB、第三者 SEO プラグイン持ちなら譲る | indexnow.org、blogs.bing.com 2026-02 | none、WT-TR-PLUGIN-02 | high |
| E-10 | Bing Webmaster Tools AI Performance（Citations / Grounding Queries）を CRAWL-03 の「テーマ外突合」対象に明示 | blogs.bing.com 2026-02 | WT-FR-CRAWL-03 | medium |
| E-11 | データ層に AI 回答エンジン由来の参照元（chatgpt.com / perplexity.ai / copilot 等）を device type と同列の必須項目化 | help.openai.com chatgpt-search | WT-FR-TAG-02 | medium |
| E-12 | Yahoo! JAPAN は Google 準拠で包含（契約 2027-03 まで）、Y!J 系 UA は未検証扱い | 報道、info-search.yahoo.co.jp | WT-FR-CRAWL-01 | low |
| E-13 | Web Bot Auth（HTTP Message Signatures、IETF draft）を第 3 の判定経路に。pay-per-crawl / x402 は CDN 責務で計測に現れない限界を明記 | 大手 CDN docs、blog | WT-FR-CRAWL-01、WT-NFR-CRAWL-01 | low |
| E-14 | schema.org 版固定（30.0、2026-03-19）と非推奨型赤 | schema.org/docs/releases | WT-NFR-SEO-01 | low |
| E-15 | ACP（OpenAI Agentic Commerce）フィード export は merchant 向け、アフィリエイトは対象外明記。SELL-01 の項目名を寄せるかのみ | developers.openai.com/commerce | WT-FR-SELL-01 | low |
| E-16 | パブリッシャープログラム（AI 側の収益分配・ライセンス市場）はテーマ外明記 | 各社発表 | none | low |

### 3.4 テーマベンダー・技術ブログ（利用者が「当然」と期待する部品）

| # | 事項 | 証跡 | 触れる要求 | 重要度 |
|---|---|---|---|---|
| T-01 | パンくず（WP 7.0 コア Breadcrumbs ブロック + BreadcrumbList JSON-LD 単一出力元） | 国内テーマほぼ全部、コア 7.0 | WT-FR-SEO-01、表示部品は none | high |
| T-02 | 著者 / 監修者ボックス + 著者アーカイブテンプレ（G-05 と同一判断） | 国内主要有料テーマ複数 | WT-FR-SNS-01 | high |
| T-03 | 関連記事（同カテゴリ→タグ→手動）と人気記事の並び規則を JSON 宣言。人気の定義（自前 PV を持つか、外部集計から読み戻すか）は WT-NFR-PRIV-01 / CRAWL-01「人の閲覧を記録しない」との整合判断 | 国内無料テーマは自前 PV 集計・解析ダッシュボード内蔵 | WT-FR-VOCAB-01、WT-FR-ZONE-01、WT-NFR-PRIV-01 | high |
| T-04 | 商品正本は手動 + 価格 / 画像の取得は外部（HELIX / ハーネス）が任意で埋める。テーマ側に EC API クライアントを持たない方針を明文化（国内 EC API は販売実績要件・旧版終了で不安定） | 国内商品リンク系プラグインの解説 | WT-FR-SELL-01、WT-NFR-COST-01 | high |
| T-05 | `/go/<slug>` 型リダイレクト経路（302 + rel=sponsored nofollow + robots 除外 + サーバー側クリック記録）を Core プラグインに持ち、データ層計測と二重にならないよう選べる | 海外リンク管理プラグイン、国内主要有料テーマの広告タグ管理 | WT-FR-SELL-03、WT-FR-BANNER-02、WT-FR-TAG-02 | high |
| T-06 | 和文タイポ既定 CSS（`line-break: strict`、`overflow-wrap: anywhere`、`word-break: normal`、`text-autospace`、`text-spacing-trim`、見出し `text-wrap: balance / pretty`）を theme.json `styles.css` / block style で持ち G-T 検査 | 国内実務記事 2026、MDN | none（A11Y-01 の横スクロール 0 のみ） | high |
| T-07 | 記事単位 / ブロック単位 custom CSS の扱い（X-07 と同一） | 国内無料テーマ標準、コア 7.0 | WT-FR-VALUE-01/02 | high |
| T-08 | ページネーション方式（番号 / もっと見る / 無限）を block style で選択、分割記事でも目次を全体で出す | コア Query Pagination、国内無料テーマ | WT-FR-VOCAB-02 | medium |
| T-09 | 404 / 検索結果テンプレ変種（人気記事・CTA・検索語提案）、サイト内検索語ログ（bot 除外）、検索結果の noindex 方針 | 国内コーポレート向けブロックテーマ | none、WT-FR-CRAWL-02 | medium |
| T-10 | お知らせバーを BANNER-01 の正本（有効期間・リンク・種別）から派生、閉じた状態を localStorage 記憶 | 国内主要有料テーマ複数 | WT-FR-ZONE-03（slot のみ） | medium |
| T-11 | ダークモード（8 色スラッグのダーク値を variation 1 本、OS 追従 + 切替）、両モードで AA 検査 | 海外ブロックテーマの 2026 動向 | WT-FR-LOOK-02、WT-NFR-A11Y-01 | medium |
| T-12 | 外部送信先一覧ページ（外部送信規律の「公表」）を TAG-01 の登録タグから機械生成（L-01 と同一） | 総務省 | WT-FR-TAG-01/03 | high |
| T-13 | テーマ内 CTR ダッシュボードは持たない現方針を維持し、WT-UI-10 に HELIX 集計の「読み戻し」タブを置くか | 国内主要有料テーマは内蔵 | WT-FR-AB-02、WT-FR-BANNER-02、WT-FR-SELL-03 | medium |
| T-14 | 商品正本・本文外部リンクの定期 HEAD 検査（リンク切れ）を WP-Cron で行い WT-UI-10 と MCP に警告 | 国内定番プラグイン | WT-FR-BANNER-02（バナーのみ） | medium |
| T-15 | pros-cons・タイムラインを core + block style で受け、星評価はレビューブロック内に限定（7 ブロック上限維持） | 国内テーマ複数 | WT-FR-VOCAB-01 | medium |
| T-16 | 料金表を比較専用テーブルの variant（プラン JSON 参照、おすすめ強調、CTA）として扱う | コーポレート向けテーマ・パターン集 | WT-FR-VOCAB-01、WT-FR-LOOK-03 | medium |
| T-17 | 比較専用テーブルに先頭列固定（CSS のみ）必須、ソート / フィルタは JS 遅延の任意 | 国内主要有料テーマ | WT-FR-SELL-02、WT-FR-SP-03 | medium |
| T-18 | 会社概要 / お問い合わせ / 採用 / プライバシー / 特商法 / 外部送信先一覧 / アクセシビリティ方針の固定ページパターン群。自前フォームは持たず第三者フォームへ譲る明記 | コーポレート向けテーマ、wp.org 審査要件 | WT-FR-LP-02、WT-TR-PLUGIN-02 | medium |
| T-19 | 段落の字下げ既定（ブログ = なし / コーポレート = あり、7.0 コア設定）を variation で、見出し palt、本文の和欧 fallback スタック | コア 7.0、国内解説 | none | medium |
| T-20 | 記事メタ表示部品（日付種別・カテゴリ / NEW バッジ・読了時間）を block style / variant で | 国内外テーマ | none | low |
| T-21 | sticky（縮小あり / なし）・透過ヘッダー variant | 国内外テーマ | WT-FR-PARTS-01 | low |
| T-22 | 広告ローテーションに重み・device type・参照元 / ログイン条件 | 海外広告管理プラグイン | WT-FR-BANNER-01 | low |
| T-23 | 自動免責文のインライン挿入は不採用と明記（ページ単位方針維持） | 海外リンク管理プラグイン | WT-FR-VOCAB-03 | low |
| T-24 | キーワード自動リンク規則（dry-run 付き）は不採用候補（AI 経路で代替、過剰リンクリスク） | 同上 | none | low |
| T-25 | カウントダウン（SSR 静的 fallback + JS 遅延）を LP 語彙に足すか | 国内主要有料テーマ | WT-FR-LP-02、WT-NFR-PERF-01 | low |
| T-26 | シェアボタン: はてなブックマーク件数のみサーバー側キャッシュで表示可、対象 SNS 既定リスト（X / はてな / メッセージアプリ / Threads / Bluesky / Pocket / note）を JSON 列挙 | はてな API doc、X 件数 API 廃止 | WT-FR-SNS-01 | low |
| T-27 | feed 内容（抜粋 + アイキャッチ、広告除外、rel=sponsored 保持）と print CSS 最小仕様 | 国内テーマ一部（二次情報） | none | low |
| T-28 | HTML サイトマップページ（カテゴリ階層 + 固定ページ + LP 一覧） | 国内テーマ複数（二次情報） | WT-FR-SEO-03 | low |
| T-29 | ルビ（marks 配列の 1 種にするか）・縦書き block style を持つか対象外明記。中間 JSON 契約に影響するため可否だけ早期決定 | 国内主要テーマに標準なし | WT-TR-AGENT-05 | low |

### 3.5 サーバー・ホスティング・CDN

| # | 事項 | 出典 | 触れる要求 | 重要度 |
|---|---|---|---|---|
| S-01 | hosting capability manifest（PHP / DB 版、画像拡張、cron 駆動、キャッシュ / CDN 有無、WAF、SMTP / DMARC）を health に自己申告させ MCP パックとハーネスが同じ一覧を読む。以下 S-02〜S-12 の受け皿 | WP 6.1 Site Health の page cache 検査方式 | WT-TR-PLUGIN-04、WT-TR-CORE-02 | high |
| S-02 | GD / Imagick の WebP / AVIF 能力検出と未対応時の縮退（WebP のみ + 警告） | hosting handbook AVIF/WebP（2024-06-19） | WT-FR-IMG-01 | high |
| S-03 | A/B cookie のサーバーキャッシュ対応: vary 対応キャッシュでは variant 別キャッシュ（互換 cookie 名規約）、非対応では除外 URL 一覧を自動生成、の 2 モード | サーバーキャッシュ製品 devguide、大手 CDN cache rules | WT-FR-AB-01、WT-TR-PLUGIN-03 | high |
| S-04 | 応答ヘッダのループバック検査でプラグイン非経由のサーバー / CDN キャッシュを検出し manifest へ | make.wordpress.org/core/2022/10/06 cache health checks | WT-TR-PLUGIN-03 | high |
| S-05 | サーバー生ログ（combined、gz、ユーザー領域保存）を WP-Cron / CLI で取り込み、キャッシュ応答分もクロールログへ。取り込み時に人の行と IP を捨てる（個人情報保護委員会 Q&A: 容易照合で個人情報） | 国内主要共用ホスティング公式マニュアル、ppc.go.jp | WT-FR-CRAWL-01、WT-NFR-CRAWL-01 | high |
| S-06 | クロールログ・操作ログ・監査保持に行数 / 容量上限と日次集約（URL × bot × 日）。生行短期・集約長期（共用 DB 容量超過はサイト停止） | 国内主要共用ホスティング公式マニュアル | WT-NFR-CRAWL-01、WT-FR-ADMIN-02、WT-FR-AUDIT-01 | high |
| S-07 | wp_mail を認証 SMTP + From 整合、SPF / DKIM / DMARC 未設定を警告（2026 大手メール送信者要件）。`wp_mail_failed` を操作ログへ。第三者 SMTP プラグイン検出時は譲る | Google / Yahoo / Microsoft 送信者要件 | WT-FR-CV-02、WT-NFR-CV-01 | high |
| S-08 | WAF が REST / MCP を 403 にする自己診断（ループバック + 「WAF 除外が必要」警告）、xmlrpc 既定無効 | 国内主要共用ホスティング公式マニュアル | WT-TR-AGENT-05、WT-FR-ADMIN-04 | high |
| S-09 | 非同期ジョブを WP-Cron / システム cron / WP-CLI の 3 駆動、キャッシュで wp-cron.php が呼ばれない環境を警告 | developer.wordpress.org cron handbook | WT-FR-IMG-02 | high |
| S-10 | 「選択セット」export / import（template part・global styles・設定 JSON・商品正本・ゾーン割当をスラッグ参照・URL 非依存）でステージング dry-run → 本番 apply | `wp search-replace` 定石 | WT-TR-PLUGIN-01、WT-FR-ADMIN-01 | high |
| S-11 | セキュリティ応答ヘッダ: テーマ / Core プラグインは Referrer-Policy・Permissions-Policy・X-Content-Type-Options のみ既定出力、CSP / HSTS はホスティング責務（タグ 3 slot と衝突するため） | OWASP HTTP Headers Cheat Sheet | WT-NFR-SEC-01 | medium |
| S-12 | Hardening WordPress 項目（DISALLOW_FILE_EDIT・権限・自動更新）を health に読み取り専用表示、API key / ログインのレート制限を持つか譲るか、自動更新方針（minor のみ / 手動）とコア更新後スモーク | developer.wordpress.org hardening、rollback merge proposal | none、WT-NFR-PERM-01、WT-NFR-REC-01 | medium |
| S-13 | memory / upload / max_execution_time の下限宣言と警告、測定条件 OPcache 有効・JIT 無効 | hosting handbook | WT-FR-IMG-02、WT-NFR-PERF-03 | medium / low |
| S-14 | CDN 画像変換との二重処理回避（応答ヘッダで検出）、CDN ログ（Enterprise 限定）は CSV import か Worker 通知の 2 案 | 大手 CDN docs | WT-FR-IMG-01、WT-NFR-CRAWL-01 | low / medium |
| S-15 | rollback 到達範囲を直近 N 操作に限りそれ以前はホスティングのバックアップ復元、公開面 Warning 0 をエラーログ参照で実機ゲート | hardening doc | WT-NFR-REC-01、WT-NFR-SEC-01 | low |

### 3.6 法令・アクセシビリティ・OSS 配布

| # | 事項 | 出典 | 触れる要求 | 重要度 |
|---|---|---|---|---|
| L-01 | 外部送信規律（電気通信事業法 2023-06-16）: タグ登録に送信先事業者・送信情報・利用目的を必須メタとし、公表ページをテーマが自動生成（広告付きメディアはほぼ確実に対象） | soumu.go.jp gaibusoushin_kiritsu | WT-FR-TAG-01/03 | high |
| L-02 | No.1・ランキング根拠（調査主体・時期・対象・方法 / 編集部基準）を商品正本 / ランキング / 比較テーブルに持ち脚注自動描画（2024-09-26 実態調査、措置命令の集中領域） | caa.go.jp No.1 表示報告書 | WT-FR-VOCAB-01、WT-FR-SELL-02 | high |
| L-03 | PR 表記自動判定の根拠（affiliation ブロック / creative ID / 商品リンク）を操作ログへ残し export | caa.go.jp Q&A | WT-FR-VOCAB-03、WT-FR-ADMIN-02 | medium |
| L-04 | 打消し表示（価格条件・定期購入条件・個人の感想）を CTA と同視野・同サイズで出す block style | caa.go.jp インターネット広告留意事項、特商法 | WT-FR-SELL-01 | medium |
| L-05 | 薬機法・健康増進法の表現規制は HELIX 監査種別として列挙、テーマは表示のみ | caa.go.jp 健康食品留意事項 | WT-FR-AUDIT-02 | low |
| L-06 | 令和 8 年改正個人情報保護法（2026-07-17 公布、2027-01-17 一部施行）: 同意バーをカテゴリ別・撤回常設・事前チェックなし、同意記録（時刻・版・カテゴリ）を保持期間付き保存。GPC 信号（`Sec-GPC: 1`）検出時の広告拒否既定は日本のみなら非対象明記 | ppc.go.jp r8kaiseihogohou（Cookie 詳細は未検証） | WT-FR-TAG-03、WT-NFR-CV-01 | medium |
| L-07 | 特定電子メール法: メルマガ登録は明示オプトイン（既定オフ）・配信者表示・同意日時記録を webhook payload に必須 | soumu.go.jp ガイドライン | WT-FR-CV-01、WT-NFR-CV-01 | medium |
| L-08 | フォーム宣言 JSON に利用目的文・プライバシーポリシーリンク・同意チェックを必須、欠落は G-T 赤 | 個人情報保護法 21 条 | WT-FR-CV-02、WT-NFR-CV-01 | medium |
| L-09 | 到達目標 WCAG 2.2 AA（改正 JIS X 8341-3 は 2026 年度中見込み、未検証）、axe 相当を G-T へ。A11Y-01 は 2.0 一部相当 | w3.org/TR/WCAG22 | WT-NFR-A11Y-01 | high |
| L-10 | WAI-ARIA APG 契約（タブ・アコーディオン・ドロワー・目次・モーダルの role / aria-expanded / キーボード）を新規ブロックと SP 挙動宣言に付ける | w3.org/WAI/ARIA/apg | WT-FR-VOCAB-01、WT-FR-SP-02/03 | high |
| L-11 | ターゲットサイズ: SC 2.5.8 の 24px を全表示の下限、SP 44px は上限目標の 2 段。固定要素がフォーカス要素を隠さない（SC 2.4.11、scroll-padding 自動付与、focus ring）。スワイプ / 横スクロールにボタン代替（2.5.7）、多段フォーム引継ぎ（3.3.7）、ヘルプ導線同一位置（3.2.6） | WCAG 2.2 Understanding | WT-NFR-SP-01、WT-FR-ZONE-03、WT-FR-VOCAB-01、WT-FR-LP-02 | medium |
| L-12 | `prefers-reduced-motion` で動き・autoplay 停止、`prefers-color-scheme` を variation 対で持つか（T-11 と同一） | @wordpress/theme、WCAG 2.3.3 | WT-FR-LOOK-01/03 | medium |
| L-13 | フォント OFL 運用（サブセット派生は OFL、Reserved Font Name の扱い、OFL 全文同梱、uploads/fonts 推奨、遠隔読込不可） | wp.org themes 2022-07-28、notofonts LICENSE | WT-NFR-PERF-02、WT-NFR-LEGAL-01 | high |
| L-14 | 更新経路（W-19 と同一）+ リリース SHA-256 / Sigstore 署名、Actions SHA 固定、lockfile 必須、Dependabot | docs.github.com secure-use | WT-NFR-GATE-01、WT-NFR-CRED-01 | high / medium |
| L-15 | SECURITY.md（窓口・SLA・公開ポリシー）+ 脆弱性開示プログラム登録（外部登録は PO 承認事項） | CNA 各社 doc | WT-NFR-SEC-01 | medium |
| L-16 | readme.txt に第三者資産台帳（フォント・アイコン・画像の出所とライセンス）を JSON から機械生成、台帳外資産は静的ゲート赤。semver + CHANGELOG、子テーマ方針 | wp.org review required | WT-NFR-LEGAL-01、WT-NFR-VALUE-03、WT-FR-INTAKE-01 | medium |
| L-17 | wp.org ディレクトリ登録は対象外明記（Core プラグイン依存・CPT で不可）。審査要件は品質基準として参照のみ | wp.org review required | WT-TR-PLUGIN-01、WT-FR-LP-01 | low |
| L-18 | 画像再生成時に IPTC DigitalSourceType / XMP を引継ぎ、C2PA manifest は原本のみ保持か選択 | Google using-gen-ai-content、iptc.org | WT-FR-IMG-01 | medium |
| L-19 | 区間リライト履歴を section ID × 実行者（人 / AI パック）× 版で記事メタに残す（G-13 と同一判断の記録側） | AI 事業者ガイドライン 1.2 版 | WT-FR-SECTION-02、WT-FR-ADMIN-02 | medium |
| L-20 | 引用 block style の citation 必須（空は警告）、AI 画像の生成ツール名メタ | 著作権法 32 条 | WT-FR-VOCAB-01 | low |
| L-21 | DSA 26 条・EU AI Act 50 条・EAA は日本国内運用では非対象と明記し、記録だけ持つ（G-13 / L-19 で吸収） | EU 各 doc | none | low |

## 4. 「やらない」を明記する候補（境界の明文化）

要求として持たないことを書くだけで先掘りを防げる項目。G-25、G-26、E-15、E-16、T-23、T-24、L-05、L-17、L-21、S-14（CDN ログ）、E-13（pay-per-crawl）。

## 5. 未検証（一次情報で確認できなかった点）

- 動画リッチリザルト「動画が主コンテンツの場合のみ」の本文、Discover Follow の対応地域、Breadcrumb のモバイル表示変更。
- Bing のクローラー一覧・IP JSON の正確な URL（ヘルプ本文 3 URL の取得失敗）。Bytespider / UA を出さない検索エンジン / Y!J 系 UA は一次情報が薄い。
- 改正 JIS X 8341-3 の発行時期、改正個人情報保護法の Cookie 関連の具体内容、US GPC 義務化の州数、GitHub Actions lockfile、Google 検索への C2PA 組み込み。
- Font Library の無効化 filter 名、rtl.css の block theme 自動読込、theme.json `styles.css` / block-level custom CSS の個別 dev note。
- `word-break: auto-phrase` と `hanging-punctuation` の Safari / Firefox 対応。
- 国内主要有料テーマの公式機能ページは複数が 404 / 403 で二次情報（レビュー・ASP 比較記事）で補った。

## 6. 問い出しの順序案（claude の提案。PO 決定ではない）

1. §2 の矛盾 12 件（X-01〜X-12）。要求本文の修正判断が先。
2. 構造が決まらないと後付けできないもの: W-01/02/04（Abilities / 認可 / 鍵）、W-19（更新経路）、S-01（hosting capability）、S-03/04（キャッシュ × A/B）、W-17（prerender × 計測）、L-09/10（WCAG 2.2 / APG）、W-15 + L-13（和文フォント）。
3. 主用途の法令: L-01（外部送信）、L-02（ランキング根拠）、S-05/06（ログと IP）。
4. 利用者期待の部品: T-01〜T-06、G-05、E-01/02/03/06/09。
5. medium / low は分野ごとに一括提示し採否のみ取る。

## 7. 既存要求で十分と判断した項目（再提示しない）

目次（VOCAB-02）、吹き出し・FAQ・アコーディオン（VOCAB-01）、メッセージアプリ友だち追加 / QR（SNS-02）、同意バー本体（TAG-03）、画像最適化の方針（IMG-01〜03）、A/B の承認・停止（AB-03）、バナー期限・ローテーション（BANNER-01）、rel=sponsored（SEO-05）、CWV 閾値（PERF-03）、Discover 画像幅（IMG-03）、cloaking 回避の原則（SEO-04）、Bing の引用向け推奨（見出し・表・FAQ・最新性）は VOCAB-01 / SEO-02 で充足。
