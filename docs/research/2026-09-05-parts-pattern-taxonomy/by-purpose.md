# 用途別パーツ整理（数の目標ではなく「用途に必要な型」で選ぶ）

- 作成: 2026-09-05。PO 指示「パーツは目標数ではなく必要なだけ作る。用途別にしっかり分けて整理し直す」
- 用途 = サイトパターン（企業 HP / サービス LP / 比較媒体 / ポータル / ブランド / 個人 / 大手）× 面（トップ・LP・記事・カテゴリ・固定・404）× 目的（認知 / 信頼 / 理解 / 行動 / 回遊 / 読了）。
- 本書の §2〜§4 は Claude 案（PO 判断待ち）。L2 探索証跡であり要求・設計の決定ではない。
- 型を入れる条件（案）: (a) その用途の実サイトで一定以上観察される、または (b) 根拠つきルール（PR #133 の R 番号）が要求する、または (c) PO 指示。どれにも当たらない型は作らない、という運用を提案する。テーマ A/B の型数は参考であって目標ではない。
- 観察値は `aggregate.json` の pattern 別集計。分母は na（切り出し範囲外・未描画）を除いたタグ出現数で、1 shot に複数の型があれば各々 1 と数える（README.md と同じ定義。n は shot 数ではない）。**v2 行（footer（v2）/ カテゴリ面（v2）/ 記事末尾（v2）と §2b）の出典は `recapture-v2/aggregate-v2.json` で、分母は na を含むタグ出現数（README §1b と同じ）。v1 と v2 で分母の扱いが異なる。**

## 1. 用途（サイトパターン）ごとに実際に観察された型

### 企業 HP（corporate）

| パーツ | PC | SP |
|---|---|---|
| header | logo-left-nav-right 28%, transparent-over-hero 22%, logo-left-cta-right(no text nav) 15%, with-search 10%（n=66） | transparent-over-hero 30%, logo-left-cta-right(no text nav) 21%, logo-center-only 19%, logo-left-nav-right 10%（n=46） |
| SP header | – | hamburger-right 45%, hamburger+search 19%, hamburger+cta 12%, hamburger-left 12%（n=31） |
| hero | fullbleed-photo-overlay 26%, illustration 16%, video 15%, text-only 10%（n=60） | fullbleed-photo-overlay 30%, text-only 15%, illustration 15%, video 15%（n=52） |
| トップのセクション | news-list 17%, features-3col 14%, banner-row 9%, cases-cards 6%（n=63） | news-list 12%, features-3col 12%, banner-row 9%, features-icon-list 5%（n=54） |
| カード | flat-no-border 27%, image-top 27%, photo-full-overlay 16%, shadow 11%（n=43） | image-top 28%, flat-no-border 26%, photo-full-overlay 13%, shadow 13%（n=38） |
| 固定パーツ | cookie-consent 26%, none 23%, sticky-header 20%, float-cta 6%（n=30） | none 36%, cookie-consent 26%, float-cta 10%, announce-bar 10%（n=19） |
| footer（v2）| single-row 25%, mega(sitemap) 22%, columns-4 18%（n=27） / 直上 cta-band 33%, banner-row 23%, contact-block 16%（n=30） | single-row 30%, columns-2 15%, mega(sitemap) 15%（n=33） |
| カテゴリ面（v2）| 見出し name-only 52%, hero-style 14%（n=21） / 子 none 61%, chips 23%（n=21） / 一覧 text-list 38%, grid 38%（n=21） / ミニ HOME none 95%, yes(sections per child) 4%（n=21） | 一覧 text-list 44%, thumb-list 36%（n=25） |

### サービス / SaaS LP（service）

| パーツ | PC | SP |
|---|---|---|
| header | logo-left-nav-right 34%, logo-left-cta-right(no text nav) 34%, two-rows 12%, with-announce-bar 10%（n=79） | logo-left-cta-right(no text nav) 74%, with-announce-bar 13%, transparent-over-hero 4%, with-tel 2%（n=43） |
| SP header | – | hamburger+cta 47%, hamburger-right 47%, hamburger+search 5%（n=38） |
| hero | split-text-image 35%, product-shot 27%, text-only 13%, illustration 7%（n=68） | text-only 35%, product-shot 29%, split-text-image 14%, illustration 10%（n=57） |
| トップのセクション | logos-row 19%, features-3col 14%, numbers 12%, badges-awards 9%（n=128） | features-icon-list 14%, logos-row 13%, features-3col 9%, badges-awards 9%（n=111） |
| カード | shadow 25%, flat-no-border 25%, icon-top 13%, image-left 9%（n=51） | shadow 28%, border 15%, icon-top 13%, flat-no-border 13%（n=53） |
| 固定パーツ | sticky-header 21%, cookie-consent 17%, announce-bar 17%, float-chat 17%（n=41） | announce-bar 37%, none 31%, cookie-consent 12%, other:lead-modal 6%（n=16） |
| footer（v2）| mega(sitemap) 91%, columns-3 5%（n=34） / 直上 cta-band 37%, banner-row 32%, none 27%（n=40） | accordion(sp) 50%, mega(sitemap) 35%, single-row 8%（n=34） |
| カテゴリ面（v2）| 見出し name-only 32%, name+description 25%（n=31） / 子 chips 51%, none 41%（n=31） / 一覧 grid 54%, text-list 12%（n=31） / ミニ HOME none 87%, yes(sections per child) 9%（n=31） | 一覧 thumb-list 35%, featured+grid 19%（n=31） |

### 比較・アフィリエイト媒体（compare）

| パーツ | PC | SP |
|---|---|---|
| header | with-search 26%, logo-left-nav-right 23%, with-announce-bar 13%, logo-left-cta-right(no text nav) 12%（n=72） | with-search 27%, logo-left-cta-right(no text nav) 23%, logo-left-nav-right 12%, logo-center-only 12%（n=55） |
| SP header | – | hamburger+search 31%, hamburger-right 19%, hamburger-left 19%, no-hamburger(text nav) 17%（n=41） |
| hero | slider 18%, fullbleed-photo-overlay 15%, text-only 15%, article-grid(media) 15%（n=44） | fullbleed-photo-overlay 18%, slider 18%, article-grid(media) 16%, text-only 13%（n=43） |
| トップのセクション | article-grid 13%, category-cards 12%, banner-row 11%, news-list 10%（n=102） | category-cards 14%, article-grid 14%, banner-row 10%, news-list 10%（n=88） |
| カード | flat-no-border 23%, image-top 16%, border 16%, image-left 14%（n=71） | image-top 21%, flat-no-border 21%, image-left 15%, border 15%（n=69） |
| 固定パーツ | none 26%, announce-bar 23%, float-cta 15%, sticky-header 15%（n=26） | none 50%, announce-bar 31%, cookie-consent 12%, other:popup-banner 6%（n=16） |
| 記事: 記事タイトル部 | title-then-image 53%, hero-overlay-title 12%, no-image 12%, image-then-title 9%（n=32） | title-then-image 57%, image-then-title 14%, hero-overlay-title 10%, no-image 10%（n=28） |
| 記事: 目次 | float-side 28%, none 23%, box-inline 19%, collapsible 19%（n=21） | collapsible 29%, box-inline 29%, none 29%, other:tab-nav 5%（n=17） |
| 記事: h2 | plain-bold 21%, bottom-border-2tone 14%, icon-prefix 14%, bar-left 7%（n=14） | plain-bold 20%, underline 20%, icon-prefix 13%, bottom-border-2tone 13%（n=15） |
| 記事: 囲み | plain-border 26%, tinted 21%, band-title 21%, shadow-card 12%（n=41） | plain-border 30%, tinted 23%, band-title 14%, tab-title 9%（n=42） |
| 記事: 記事内 CTA | product-card-bundle 37%, banner-image 27%, button-only 20%, box-with-copy 10%（n=29） | banner-image 33%, product-card-bundle 33%, button-only 22%, box-with-copy 7%（n=27） |
| 記事: 関連 | sidebar-widget-list 40%, grid-cards 26%, ranking-numbers 13%, carousel 13%（n=15） | thumb-list-1line 37%, carousel 25%, grid-cards 25%, ranking-numbers 12%（n=8） |
| footer（v2）| mega(sitemap) 40%, single-row 37%, columns-4 7%（n=27） / 直上 banner-row 48%, none 24%, cta-band 20%（n=29） | single-row 33%, mega(sitemap) 26%, columns-2 13%（n=30） |
| カテゴリ面（v2）| 見出し name-only 42%, name+description 31%（n=19） / 子 none 63%, chips 26%（n=19） / 一覧 thumb-list 36%, grid 31%（n=19） / ミニ HOME none 84%, yes(sections per child) 15%（n=19） | 一覧 thumb-list 47%, grid 19%（n=21） |
| 記事末尾（v2）| 並び related 34%, tags 8%, cta 8%, share 8%（n=35） / 関連 grid-cards 40%, none 35%, text-numbered 10%（n=20） | 関連 none 33%, thumb-list-1line 20%, grid-cards 20%（n=15） |

### ポータル・ニュース（portal）

| パーツ | PC | SP |
|---|---|---|
| header | with-search 32%, logo-left-nav-right 25%, logo-left-cta-right(no text nav) 14%, two-rows 7%（n=71） | logo-left-cta-right(no text nav) 34%, with-search 23%, logo-center-only 17%, logo-left-nav-right 8%（n=46） |
| SP header | – | hamburger-right 37%, hamburger+search 27%, hamburger+cta 9%, no-hamburger(text nav) 9%（n=43） |
| hero | article-grid(media) 50%, split-text-image 16%, slider 10%, fullbleed-photo-overlay 6%（n=50） | article-grid(media) 50%, slider 13%, fullbleed-photo-overlay 11%, split-text-image 9%（n=44） |
| トップのセクション | article-grid 28%, banner-row 13%, news-list 11%, ranking 9%（n=98） | article-grid 34%, banner-row 9%, news-list 9%, category-cards 8%（n=72） |
| カード | flat-no-border 29%, image-top 21%, image-left 20%, shadow 7%（n=79） | image-top 34%, flat-no-border 31%, shadow 7%, border 5%（n=70） |
| 固定パーツ | none 50%, sticky-header 19%, cookie-consent 11%, announce-bar 11%（n=26） | none 73%, announce-bar 26%（n=15） |
| 記事: 記事タイトル部 | title-then-image 57%, no-image 15%, image-then-title 12%, side-thumb 6%（n=33） | title-then-image 53%, no-image 21%, image-then-title 15%, hero-overlay-title 9%（n=32） |
| 記事: 目次 | none 62%, float-side 17%, box-inline 13%, collapsible 6%（n=29） | none 73%, box-inline 19%, collapsible 7%（n=26） |
| 記事: h2 | plain-bold 45%, underline 13%, other:centered 9%, icon-prefix 9%（n=22） | plain-bold 56%, underline 13%, icon-prefix 8%, other:centered 4%（n=23） |
| 記事: 囲み | none 50%, tinted 25%, plain-border 8%, other:quick-summary 4%（n=24） | none 44%, tinted 24%, plain-border 12%, shadow-card 8%（n=25） |
| 記事: 記事内 CTA | none 66%, banner-image 16%, button-only 8%, product-card-bundle 8%（n=12） | none 41%, banner-image 17%, product-card-bundle 17%, box-with-copy 11%（n=17） |
| 記事: 関連 | sidebar-widget-list 77%, ranking-numbers 22%（n=9） | grid-cards 40%, text-numbered 20%, other:テキスト罫線 20%, thumb-list-1line 20%（n=5） |
| footer（v2）| mega(sitemap) 54%, single-row 16%, stacked-centered 9%（n=31） / 直上 none 51%, banner-row 18%, newsletter 12%（n=33） | single-row 45%, mega(sitemap) 41%, columns-2 3%（n=31） |
| カテゴリ面（v2）| 見出し name-only 44%, name+description 25%（n=27） / 子 none 70%, chips 22%（n=27） / 一覧 grid 40%, thumb-list 33%（n=27） / ミニ HOME none 85%, yes(sections per child) 14%（n=27） | 一覧 thumb-list 42%, text-list 19%（n=26） |
| 記事末尾（v2）| 並び related 33%, share 12%, author 11%, cta 11%（n=71） / 関連 grid-cards 35%, none 29%, thumb-list-1line 19%（n=31） | 関連 none 50%, thumb-list-1line 23%, ranking-numbers 10%（n=30） |

### ブランド・EC（brand）

| パーツ | PC | SP |
|---|---|---|
| header | with-search 20%, transparent-over-hero 17%, with-announce-bar 17%, logo-left-nav-right 11%（n=81） | logo-center-only 22%, transparent-over-hero 20%, with-announce-bar 20%, with-search 20%（n=63） |
| SP header | – | hamburger-left 35%, hamburger+search 31%, hamburger-right 16%, no-hamburger(text nav) 4%（n=48） |
| hero | fullbleed-photo-overlay 38%, slider 14%, product-shot 12%, video 12%（n=54） | fullbleed-photo-overlay 41%, product-shot 15%, text-only 13%, slider 10%（n=46） |
| トップのセクション | banner-row 21%, category-cards 16%, article-grid 10%, news-list 10%（n=60） | category-cards 22%, banner-row 14%, other:product-carousel 10%, article-grid 8%（n=57） |
| カード | flat-no-border 39%, image-top 39%, photo-full-overlay 14%, image-left 4%（n=41） | image-top 42%, flat-no-border 34%, photo-full-overlay 14%, border 6%（n=47） |
| 固定パーツ | announce-bar 30%, sticky-header 17%, cookie-consent 17%, float-cta 6%（n=46） | announce-bar 46%, none 10%, cookie-consent 10%, other:region-modal 7%（n=28） |
| footer（v2）| columns-4 23%, single-row 20%, columns-3 20%（n=34） / 直上 banner-row 50%, newsletter 15%, none 13%（n=38） | accordion(sp) 25%, single-row 20%, stacked-centered 17%（n=35） |
| カテゴリ面（v2）| 見出し name-only 50%, name+description 15%（n=20） / 子 none 65%, chips 25%（n=20） / 一覧 grid 60%, text-list 20%（n=20） / ミニ HOME none 95%（n=20） | 一覧 grid 31%, thumb-list 27%（n=22） |

### 個人ブログ・ポートフォリオ（personal）

| パーツ | PC | SP |
|---|---|---|
| header | with-search 20%, logo-left-nav-right 16%, logo-center-nav-below 11%, with-announce-bar 10%（n=67） | logo-center-only 25%, with-search 24%, with-announce-bar 9%, none 8%（n=62） |
| SP header | – | hamburger+search 26%, no-hamburger(text nav) 24%, hamburger-left 22%, hamburger-right 17%（n=45） |
| hero | text-only 29%, fullbleed-photo-overlay 18%, none 14%, illustration 12%（n=55） | text-only 34%, fullbleed-photo-overlay 18%, none 16%, illustration 12%（n=49） |
| トップのセクション | blog-cards 16%, article-grid 14%, category-cards 11%, banner-row 10%（n=92） | blog-cards 20%, article-grid 15%, category-cards 11%, banner-row 8%（n=80） |
| カード | flat-no-border 32%, image-top 32%, border 8%, image-left 5%（n=68） | image-top 34%, flat-no-border 32%, image-left 7%, border 6%（n=64） |
| 固定パーツ | none 45%, announce-bar 13%, float-cta 10%, sticky-header 8%（n=37） | none 64%, announce-bar 17%, float-cta 3%, other:sns-band 3%（n=28） |
| 記事: 記事タイトル部 | title-then-image 62%, no-image 21%, hero-overlay-title 9%, other:tinted-hero 3%（n=32） | title-then-image 64%, no-image 22%, hero-overlay-title 9%, other:tinted-hero 3%（n=31） |
| 記事: 目次 | none 42%, float-side 30%, box-inline 23%, collapsible 3%（n=26） | none 47%, box-inline 39%, collapsible 8%, float-side 4%（n=23） |
| 記事: h2 | plain-bold 62%, icon-prefix 12%, bar-left 12%, other:wave-under 6%（n=16） | plain-bold 52%, bottom-border-2tone 15%, icon-prefix 10%, underline 5%（n=19） |
| 記事: 囲み | tinted 25%, none 13%, label-title 11%, shadow-card 9%（n=43） | tinted 28%, none 15%, plain-border 12%, check-list 7%（n=39） |
| 記事: 記事内 CTA | none 29%, banner-image 23%, button-only 17%, product-card-bundle 17%（n=17） | button-only 30%, none 25%, product-card-bundle 15%, box-with-copy 15%（n=20） |
| 記事: 関連 | sidebar-widget-list 53%, grid-cards 23%, ranking-numbers 7%, series-prev-next 7%（n=13） | grid-cards 50%, series-prev-next 25%, thumb-list-1line 25%（n=4） |
| footer（v2）| single-row 41%, columns-3 20%, mega(sitemap) 10%（n=39） / 直上 none 51%, banner-row 20%, contact-block 5%（n=39） | single-row 32%, stacked-centered 32%, columns-2 7%（n=40） |
| カテゴリ面（v2）| 見出し name+description 46%, name-only 32%（n=28） / 子 none 57%, chips 25%（n=28） / 一覧 grid 60%, text-list 14%（n=28） / ミニ HOME none 82%, yes(sections per child) 7%（n=28） | 一覧 thumb-list 46%, text-list 21%（n=28） |
| 記事末尾（v2）| 並び related 23%, author 16%, share 14%, prev-next 12%（n=71） / 関連 grid-cards 35%, none 25%, thumb-list-1line 14%（n=28） | 関連 none 50%, thumb-list-1line 17%, grid-cards 10%（n=28） |

### 大手メディア（major）

| パーツ | PC | SP |
|---|---|---|
| header | with-search 26%, logo-left-nav-right 22%, with-announce-bar 16%, two-rows 14%（n=68） | logo-center-only 21%, logo-left-cta-right(no text nav) 21%, with-search 19%, with-announce-bar 16%（n=42） |
| SP header | – | hamburger-right 36%, hamburger+search 21%, hamburger+cta 15%, hamburger-left 13%（n=38） |
| hero | slider 33%, split-text-image 12%, fullbleed-photo-overlay 12%, product-shot 10%（n=48） | slider 28%, fullbleed-photo-overlay 15%, product-shot 13%, text-only 13%（n=46） |
| トップのセクション | banner-row 18%, category-cards 14%, news-list 11%, tabs 8%（n=85） | banner-row 16%, category-cards 14%, news-list 9%, cta-band 9%（n=84） |
| カード | flat-no-border 30%, image-top 22%, shadow 12%, icon-top 12%（n=50） | image-top 30%, flat-no-border 28%, shadow 9%, image-left 7%（n=52） |
| 固定パーツ | announce-bar 26%, cookie-consent 23%, float-cta 17%, float-chat 11%（n=34） | announce-bar 35%, none 29%, cookie-consent 17%, float-cta 11%（n=17） |
| footer（v2）| mega(sitemap) 55%, single-row 18%, columns-4 14%（n=27） / 直上 banner-row 62%, none 22%, cta-band 11%（n=27） | accordion(sp) 33%, mega(sitemap) 29%, single-row 14%（n=27） |
| カテゴリ面（v2）| 見出し name-only 50%, name+description 27%（n=22） / 子 chips 36%, none 36%（n=22） / 一覧 text-list 36%, grid 22%（n=22） / ミニ HOME none 86%, yes(sections per child) 13%（n=22） | 一覧 text-list 45%, thumb-list 18%（n=22） |

### 表現重視（参考のみ）（motion）

| パーツ | PC | SP |
|---|---|---|
| header | logo-left-cta-right(no text nav) 25%, transparent-over-hero 25%, logo-left-nav-right 13%, two-rows 6%（n=43） | transparent-over-hero 36%, logo-left-cta-right(no text nav) 21%, logo-center-only 12%, logo-left-nav-right 12%（n=33） |
| SP header | – | hamburger-right 42%, hamburger+cta 14%, no-hamburger(text nav) 14%, hamburger-left 9%（n=21） |
| hero | text-only 22%, fullbleed-photo-overlay 18%, video 16%, illustration 14%（n=54） | text-only 25%, video 22%, fullbleed-photo-overlay 15%, illustration 13%（n=44） |
| トップのセクション | news-list 18%, video 9%, banner-row 9%, tabs 9%（n=32） | news-list 17%, article-grid 8%, video 5%, banner-row 5%（n=34） |
| カード | image-top 38%, flat-no-border 33%, border 11%, photo-full-overlay 11%（n=18） | image-top 34%, flat-no-border 34%, border 13%, shadow 4%（n=23） |
| 固定パーツ | none 21%, cookie-consent 17%, float-cta 17%, sticky-header 14%（n=28） | none 46%, cookie-consent 13%, sticky-header 13%, other:loader 6%（n=15） |
| footer（v2）| single-row 19%, logo-only-legal 12%, stacked-centered 6%（n=31） / 直上 banner-row 21%, none 12%, cta-band 9%（n=32） | single-row 24%, columns-2 9%, stacked-centered 9%（n=33） |
| カテゴリ面（v2）| 見出し name-only 30%, name+description 15%（n=13） / 子 none 53%, chips 15%（n=13） / 一覧 grid 38%, thumb-list 15%（n=13） / ミニ HOME none 76%（n=13） | 一覧 thumb-list 25%, grid 25%（n=12） |

## 2. 用途 × 目的 → 必要な型（Claude 案、PO 判断待ち）

各行は「この用途・この目的のために、この型が要る」の対応。根拠列は PR #133 の R 番号または本台帳の観察。ここに無い型は初期版では作らない、という案。

| 用途 | 目的 | 面 | 必要な型 | 根拠 |
|---|---|---|---|---|
| 企業 HP | 認知・信頼 | トップ | header: ロゴ左ナビ右 + 電話 / 資料 DL（2 段は項目 6 以上のみ）。hero: 静止写真 + 見出し + CTA 1（split または全面写真）。セクション: 特長 3 列 / 実績ロゴ列 / 事例カード / 流れ / FAQ / CTA 帯 | 観察 corporate 上位、R01 R11 R19 |
| 企業 HP | 行動 | 全面 | CTA 帯（footer 上）+ SP 下部固定（項目数は R63・R64 の範囲）。フォームは 1 カラム（項目数は R27–R32 の範囲） | R24 R63 R27–R32 |
| 企業 HP | 理解 | 固定 | 会社概要 / 採用 / 問い合わせ / プライバシー / 特商法 / 外部送信先 / アクセシビリティ のパターン群（PAGE-01 が列挙する固定ページ） | PAGE-01, LEGAL-02 |
| サービス LP | 行動 | LP | 最小ヘッダー（ロゴ + CTA）。hero: 商品画像 or イラスト + 見出し + CTA 1 + 補助文。数字訴求 / 特長 / 導線 3 か所の CTA slot / 料金表（おすすめ 1 つ、R34）/ 声（件数は R35 の範囲） / FAQ / 最終 CTA。動き: 出現 fade-up・count-up のみ既定 ON | 観察 service 上位、R12 R34–R38 #132 |
| サービス LP | 信頼 | LP | ロゴ列 / 受賞バッジ / 数字（出典付き）/ 打消し表示 | R42 R43 LEGAL-03 |
| 比較媒体 | 理解・行動 | 記事 | タイトル→画像、目次（非固定、開閉）、h2 は控えめな装飾 + アイコン前置（型は #122 の用途由来一覧から）、囲み: 帯タイトル / 注意 / ポイント、比較表（先頭列固定・SP カード）、商品カード + 星 + CTA 束、レビュー評価バー、pros-cons、PR 表記、リンクカード内部 | 観察 compare 上位、R39–R41 R44 #116 #117 #108 #112 |
| 比較媒体 | 回遊 | カテゴリ・記事末 | カテゴリ ミニ HOME（子カテゴリ・読む順番・ランキング）、記事末: 次に読む + 関連（件数は R53 の範囲）→ CTA → 著者。ランキング番号大 / 横サムネ 1 行 | R53 R58 #129 #110 |
| ポータル・大手メディア | 回遊 | トップ・記事 | header: 検索付き + ロゴ中央（SP）。hero: 記事グリッド / スライダー（自動回転なし）。セクション: 新着 / カテゴリカード / タブ（新着・人気）/ ランキング / お知らせ帯。記事: サイドバー人気・目次フロート（PC） | 観察 portal・major 上位、R11 R55 |
| ブランド・EC | 認知 | トップ | header: 透過 or ロゴ中央。hero: 全面写真 / 動画（既定 OFF、有効時は LCP ゲート）/ 商品カルーセル。セクション: 商品グリッド / ブランドメッセージ / 写真 + 文の交互。密度 airy、明朝または display 系書体 | 観察 brand 上位、#130 #134 |
| 個人ブログ | 読了・回遊 | 記事 | シンプル header、タイトル→画像、h2 は無装飾 or 下線、囲み淡塗り、吹き出し、リンクカード、記事末: 関連グリッド + 著者。SP 下部バーなし | 観察 personal 上位、R46–R49 |
| 全用途共通 | 守り | 全面 | 自動コントラスト guard、reduced-motion、44px タップ、LCP 2.5s、同意バー（既定 OFF、位置 2 択）、404 の変種（TPL-01）+ CV slot、パンくず、alt 必須 | R75 R83–R93 #103 #98 #130 |

### 2b. 再取得 v2 から加える行（Claude 案、PO 判断待ち）

| 用途 | 目的 | 面 | 必要な型 | 根拠 |
|---|---|---|---|---|
| 全用途共通 | 回遊・信頼 | footer | PC: サイトマップ型（列数は項目数から自動）/ 1 行型 の 2 型。SP: 1 行 / アコーディオン / 中央積み の 3 型（PC の列型は SP で畳む）。法定行は copyright + リンク列 / copyright のみ の 2 型。付属: SNS アイコン・関連サイト・認証バッジ・住所 / 電話 を slot で ON/OFF。back-to-top は既定 OFF | §1b footer.* |
| 企業 HP / サービス LP | 行動 | footer 直上 | CTA 帯（企業 HP 33%、サービス 37% で最多）。問い合わせブロックは企業 HP のみ | §1b footer.above |
| ブランド / 大手 / 比較 | 回遊 | footer 直上 | バナー列（ブランド 50%、大手 62%、比較 48%） | §1b footer.above |
| ポータル | 継続 | footer 直上 | newsletter（ポータルの cat / article 面で 2 割） | §1b footer.above |
| 比較媒体 / ポータル / 個人 | 回遊 | 記事末尾 | 並び順は「関連 → 著者 → シェア → CTA」を既定とする案（v2 は要素の頻度のみ集計し、順序は未集計。既定順は観察事実ではなく Claude 案）。slot 順は入れ替え可。関連は PC グリッド / SP サムネ 1 行リスト。前後記事・コメント欄は既定 OFF | §1b tail.* |
| 全用途共通 | 回遊 | カテゴリ面 | 既定は「名前（+ 説明）→ PC グリッド / SP サムネリスト（画像上 + 日付 + カテゴリチップ）→ 番号ページ送り」。子カテゴリは chips。ミニ HOME（子カテゴリ別セクション + 読む順番 + ランキング）は比較媒体 / ポータル向けの選択肢として残し、既定にしない | §1b category.* / list.* |

## 3. 用途に入らなかった型（初期版では作らない案）

- hero の bento / WebGL / 粒子、marquee、parallax・pin（表現重視サイト以外で観察 1〜2% 以下、性能・前庭リスク）
- 囲みの黒板風・角括弧風などテーマ A 固有の装飾（観察されず）
- 無限スクロール（R58）、自動回転カルーセル（R14。条件付き許容のため全面不採用は Claude 案）、全画面モーダル（R66）、疑似ライブ通知（R42）
- 会員制限・リッチメニュー・定義リスト（VOCAB-01 の枠は残すが用途が出るまで実装しない）

## 4. 型を増やすときの手順

1. どの用途 × 目的 × 面のために要るかを 1 行で書く。2. 観察（台帳）か根拠（ルール集 R 番号）か PO 指示のどれに当たるかを示す。3. 既存の型で代替できないことを示す。4. G-E1 で SP / PC の証跡を取る。これを満たさない型は Issue に「保留」で置く（運用案）。