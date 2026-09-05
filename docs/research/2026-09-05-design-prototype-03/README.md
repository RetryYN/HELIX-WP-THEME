# デザイン試作 03 — 比較・アフィリエイト媒体の記事面・404・カテゴリ面・footer（選べる型として実装）

- 実施日: 2026-09-05。計画は `PLAN.md`（段 1 = 記事面 + 404 が本書の範囲）
- 位置づけ: PoC 証跡。要求の確定・設計の決定ではない。PO 決定 2026-09-05「フロント先行」（試作 02 は殺風景・変化が少ないという PO 判定を受け、1 サイトパターン = 比較・アフィリエイト媒体に絞る）に基づく。段3ではカテゴリ ミニ HOME・footer・記事末尾 slot の型を選択可能な状態にする。
- 方針: 見た目の差はすべて**テーマの実コード**（theme.json / block style / pattern / template part / CSS / 小さな JS）で、選択軸は body class・block style・pattern として実際に切り替えられる（DOM 注入のモックではない）。
- 入力: 型の棚卸し `../2026-09-05-parts-pattern-taxonomy/by-purpose.md` §1「比較・アフィリエイト媒体」と §2（比較媒体・全用途共通）、語彙 `../2026-09-05-parts-pattern-taxonomy/PARTS-VOCAB.md`、既定値 `../2026-09-05-cro-usability-evidence/README.md` §2 P01–P33、パーツ一覧 `../2026-09-04-parts-inventory/README.md`（#98 / #107 / #110 / #122 / #126 / #127 / #130 / #132 / #134）。
- テーマ本体（`themes/`）・`plugins/` は未編集。`theme/helix-wt/` は試作 02 の複製に上書きした試作テーマ。

## 1. 選択軸の仕組み

選択は `functions.php` の `wt_axes()` に列挙した 27 軸。解決順は **プレビュー引数 `?wt=key:value,...`（PoC 用）→ 記事の post meta `wt_<key>`（eyecatch / toc / pr / share のみ、「この記事では目次を隠す」等）→ `theme_mod` `wt_<key>`（サイト既定）→ 既定値**。結果は `body.wt-<key>-<value>` の class になり、段3の `_` を含むキーには互換用の正規化 class（例: `wt-cat_header-hero` と `wt-cat-header-hero`）も付く。CSS がその class を切り替える。ヘッダーだけは `render_block_data` で template part の slug を `header-<variant>` に差し替える。実装時はプレビュー引数を管理者限定にし、サイトエディター / 記事サイドバーの選択 UI を付ける。

| 軸 | 値（既定を太字） | 切替の実体 |
|---|---|---|
| header | **search** / nav / cta / announce | template part `parts/header{,-nav,-cta,-announce}.html` |
| sp（SP ヘッダー） | **search** / right / left | `body.wt-sp-*` + flex order |
| eyecatch | **title-image** / image-title / hero / side / none | `body.wt-eyecatch-*`（`.wt-posthead` の grid） |
| toc | **box** / float / collapsible / none | サーバ生成 `nav.wt-toc.wt-toc--*` |
| related | **grid** / list / rank / carousel | `body.wt-related-*`（Query Loop の post-template） |
| share | **topbottom** / float / none | `body.wt-share-*` |
| motion | **off** / on | `body.wt-motion-on` + `html.wt-js` |
| depth | **0** / 1 / 2 | `body.wt-depth-*` |
| density | airy / **normal** / compact | `body.wt-density-*`（spacing preset の差し替え） |
| detext | **off** / on | `body.wt-detext-on` |
| nf（404） | **popular** / cta / suggest | `body.wt-nf-*` |
| pr | **on** / off | 本文先頭に PR 表記を自動挿入 |
| cat_header | **name-only** / name-desc / hero | `templates/category.html` / `.wt-cat-head`（`core/term-description` を name-desc / hero で表示） |
| cat_children | none / **chips** / cards / steps | `helix-wt/category-children`（chips / card-grid / numbered-steps） |
| cat_list | **grid** / thumb-list / featured-grid | `.wt-cat-list`（PC grid、SP は画像左の thumb-list へ自動適用） |
| cat_pagination | **numbers** / load-more / prev-next | `.wt-cat-pagination` + `category.js`（load-more は no-JS で numbers） |
| cat_ranking | **none** / sidebar / bottom | `helix-wt/category-ranking`（sidebar は PC、SP では下部） |
| cat_minihome | **off** / on | `helix-wt/category-minihome`（子カテゴリごとの4件、読む順番、ランキング） |
| footer_layout | **sitemap** / single-row / columns-3 | `parts/footer.html` の3 layout slot。sitemap の `details` は SP アコーディオン |
| footer_above | **none** / cta-band / banner-row / newsletter | `.wt-footer__above-slot--*` |
| footer_legal | **copyright-links** / copyright-only | `.wt-footer__legal--links` / `--only` |
| footer_extra | **sns** / none / sites / badges / address / 組み合わせ / all | SNS・関連サイト・認証バッジ・住所を slot ごとに body class で表示 |
| footer_totop | **off** / button | `.wt-totop`（`footer.js`、JS無効時も `href="#"` が上部へ戻る） |
| tail_order | **related-author-share-cta** / cta-related-author-share / related-cta-author | `.wt-tail__slot` の CSS order |
| tail_share | **none** / icons-row | `.wt-tail__slot--share`（既存の share 軸とは別の末尾 slot） |
| tail_author | **none** / avatar-bio / avatar-bio-sns / supervisor | `.wt-author-variant--*` |
| tail_prevnext | **off** / thumb | `helix-wt/tail-prevnext` / `.wt-tail__prevnext` |

## 2. 作ったもの（変種ごとのセレクタ・型名・根拠）

型名は PARTS-VOCAB の語彙、根拠は R 番号（`../2026-09-05-cro-usability-evidence/README.md` §1）/ P 番号（同 §2）/ 観察（by-purpose §1 compare 表）。

### 2.1 ヘッダー（PC 4 + SP 3 + 帯）

| 変種 | セレクタ / ファイル | 型名 | 根拠 |
|---|---|---|---|
| 検索付き（既定） | `parts/header.html`、`.wt-header__search`（PC インライン）/ `.wt-header__search--sp`（SP アイコン、core/search button-only） | header.layout: with-search | 観察 compare PC 26% / SP 27%、R61 |
| ロゴ左・ナビ右 | `parts/header-nav.html` | logo-left-nav-right | 観察 23%、P09 |
| ロゴ左・CTA 右（文字ナビなし） | `parts/header-cta.html`、`.wt-header--cta` | logo-left-cta-right(no text nav) | 観察 12% |
| お知らせ帯つき | `parts/header-announce.html`、`.wt-announce`（1 行・`role=status`・閉ボタン 44px・閉状態は `localStorage` `wt-announce-closed:<id>`、初期描画前に `html.wt-announce-closed`） | with-announce-bar / fixed: announce-bar | 観察 13%（SP 固定 31%）、P18、R69 |
| SP: ハンバーガー + 検索（既定） | `body.wt-sp-search` | header.sp: hamburger+search | 観察 31% |
| SP: ハンバーガー右 | `body.wt-sp-right` | hamburger-right | 観察 19% |
| SP: ハンバーガー左 | `body.wt-sp-left` | hamburger-left | 観察 19% |
| 固定方式 | `.wt-header`（sticky・不透明 #fff）+ `assets/js/header.js`（下スクロールで `.is-hidden`、上スクロールで再表示） | fixed: sticky-header（部分固定） | R08、P10 |

### 2.2 アイキャッチ 位置 × 有無（5）

| 変種 | セレクタ | 型名 | 根拠 |
|---|---|---|---|
| タイトル → 画像（既定） | `body.wt-eyecatch-title-image`（`.wt-posthead__text` → `.wt-posthead__img`） | title.area: title-then-image | 観察 53–57%、#126 案 A |
| 画像 → タイトル | `body.wt-eyecatch-image-title`（`order:-1`） | image-then-title | 観察 9–14%、案 B |
| ヒーロー重ね（白抜き） | `body.wt-eyecatch-hero`（同一 grid cell に重ね、`::after` スクリム、`data-wt-lum` で強度） | hero-overlay-title | 観察 10–12%、案 C、R75 |
| 横サムネ | `body.wt-eyecatch-side`（600px 以上で 2 列 220px） | side-thumb | 観察 portal 6%、案 D |
| なし | `body.wt-eyecatch-none` | no-image | 観察 10–12%、案 E |

記事単位の上書きは post meta `wt_eyecatch`（例 `wp post meta set <ID> wt_eyecatch none`）。

### 2.3 目次（4 + 機能）

| 変種 | セレクタ | 型名 | 根拠 |
|---|---|---|---|
| ボックス埋め込み（既定、SP は開閉・既定閉） | `nav.wt-toc.wt-toc--box`（`<details>`、SP は `article.js` が `open` を外す。JS 無効時は開いたまま） | toc.type: box-inline | 観察 19–29%、P19、R44 |
| フロート（PC のみ） | `.wt-toc--float`（1200px 以上で `position:fixed` 左レール 240px、それ未満は box と同じ） | float-side | 観察 PC 28%、R44 |
| 開閉（全幅で既定閉） | `.wt-toc--collapsible` | collapsible | 観察 19–29% |
| なし | `body.wt-toc-none` または post meta `wt_toc=none`（「この記事では隠す」hook） | none | 観察 23–29% |

機能（第三者の目次プラグイン相当を超える点）: h2/h3 の 2 階層を `the_content` で機械導出（見出しに `id="h-n"` を付与）、**h2 ≥ 3 のしきい値**（記事は h2 5 / h3 7 → 目次 5 / 7 で一致）、現在位置強調 `.wt-toc a.is-current`（IO + scroll）、`scroll-margin-top` 76px（ヘッダー高 + 1rem）、章数バッジ、記事単位の非表示 hook、テーマ内完結（外部スクリプトなし）。

### 2.4 見出し（h2 6 型・h3 3 型）

| 変種 | block style class | 型名 | 根拠 |
|---|---|---|---|
| 無装飾太字 | `.is-style-wt-plain` | heading.h2: plain-bold | 観察 20–21% |
| 下線 2 色 | `.is-style-wt-2tone`（記事の既定として使用） | bottom-border-2tone | 観察 13–14% |
| アイコン前置 | `.is-style-wt-icon`（SVG mask、`data-wt-icon` で差し替え。render_block で既定 `check-circle`） | icon-prefix | 観察 13–14%、§2「控えめな装飾 + アイコン前置」 |
| 左バー | `.is-style-wt-bar` | bar-left | 観察 7% |
| 下線 | `.is-style-wt-underline` | underline | 観察 SP 20% |
| 帯（塗り） | `.is-style-wt-band` | band-fill | 語彙 |
| h3 細い左バー | `.is-style-wt-bar-thin` | heading.h3: bar-left | 語彙 |
| h3 点線下線 | `.is-style-wt-dotted` | dotted | 語彙 |
| h3 番号前置 | `.is-style-wt-num`（CSS counter） | number-prefix 相当 | 語彙 |

見出しサイズ: h2 は fluid 18–24px、letter-spacing 0、SP 390 の本文列 358px（gutter 16px）で **18.5px × 18 字が 1 行**（19.3 字まで収まる。実測 `results/verify.json` `headline`）。補助文は `.wt-sub`（mute 色 #5c6875、5.69:1）。

### 2.5 囲み（7 型 × 色 4）

| 変種 | block style class（group） | 型名 | 根拠 |
|---|---|---|---|
| 罫線 | `.is-style-wt-plain-border` | box.types: plain-border | 観察 26–30% |
| 淡塗り | `.is-style-wt-tinted` | tinted | 観察 21–23% |
| 帯タイトル | `.is-style-wt-band-title`（先頭子要素がタイトル） | band-title | 観察 14–21% |
| タブタイトル | `.is-style-wt-tab-title` | tab-title | 観察 9% |
| ラベルタイトル | `.is-style-wt-label-title` | label-title | 観察 personal 11% |
| 影カード | `.is-style-wt-card-shadow` | shadow-card | 観察 12% |
| チェックリスト | `.is-style-wt-check`（list） | check-list | 観察 7% |
| 色 | 併用 class `.wt-c-warn` / `.wt-c-point` / `.wt-c-note` / `.wt-c-ok`（`--wt-box-accent` / `--wt-box-soft` を差し替え） | warn / point / note | §2「帯タイトル / 注意 / ポイント」 |

試作 02 の `.is-style-wt-note / -point / -warn / -card` は互換のため残置。

### 2.6 記事内 CTA・比較表・周辺

| 変種 | セレクタ / pattern | 型名 | 根拠 |
|---|---|---|---|
| 商品カード束（既定） | `helix-wt/product-bundle`、`.is-style-wt-product`（画像・名前・星 + 実数・価格・CTA ×2・PR バッジ） | cta.inpost: product-card-bundle | 観察 33–37%、P25、SELL |
| バナー画像 | `helix-wt/cta-banner`、`.wp-block-image.is-style-wt-banner` | banner-image | 観察 27–33% |
| ボタンのみ | `helix-wt/cta-button`、`.wt-cta-btn`（幅広 56px） | button-only | 観察 20–22% |
| コピー付きボックス | `helix-wt/cta-box`、`.is-style-wt-cta-box` | box-with-copy | 観察 7–10% |
| 比較表 | `.wp-block-table.is-style-wt-compare`（PC: 先頭列 sticky + 横スクロール、SP: 行ごとのカード縦積み。render_block で先頭列を `<th scope="row">` に、他列に `data-th` を付与、thead の `th` に `scope="col"`、`caption` あり、数値は `.wt-num` 右揃え、◎○△ は凡例で文字代替） | table: compare-sticky-first-col | R39–R41、P24 |
| 比較表（SP も横スクロール） | `.is-style-wt-compare-scroll` | 同 | R39 |
| メリデメ | `columns.wt-prosc` + `.is-style-wt-label-title.wt-c-ok/.wt-c-warn` + `.is-style-wt-pros/-cons` | pros-cons | §2 |
| 評価バー | `.wt-rate`（試作 02 の CSS） | review bar | P25 |
| リンクカード（内部） | `helix-wt/linkcard`、`.is-style-wt-linkcard`（タイトルの `a::after` で全面クリック） | link.card: internal-thumb-left | R52、P22 |
| PR 表記 | 本文先頭に自動挿入 `p.wt-pr`（1 行・xs・mute）、post meta `wt_pr=off` で抑止 | PR notice | §2 |
| 引用符 | `.wp-block-quote.is-style-wt-quote-mark` | quote-style | 語彙 |

### 2.7 記事末・共有

| 変種 | セレクタ | 型名 | 根拠 |
|---|---|---|---|
| 次に読む 1 件 | `parts/article-tail.html` の Query Loop `queryId:901`（同カテゴリ優先・現在記事除外、`query_loop_block_query_vars`） | series / related | R53、P20 |
| 関連 3–6: グリッド（既定） | `body.wt-related-grid`（PC 3 列 / SP 2 列、`.wt-rcard` 全面クリック・高さ統一） | related.layout: grid-cards | 観察 25–26%、R52 |
| 横サムネ 1 行 | `body.wt-related-list` | thumb-list-1line | 観察 SP 37% |
| ランキング番号 | `body.wt-related-rank`（1–3 位は金銀銅色） | ranking-numbers | 観察 12–13% |
| カルーセル（自動送りなし） | `body.wt-related-carousel`（scroll-snap、前後ボタンは `article.js` が生成） | carousel | 観察 13–25%、R14 |
| → CTA → 著者 | `.wt-tail__cta`（`helix-wt/cta-box`）→ `.wt-author-box`（avatar + 名前 + 紹介） | author.box: avatar+bio | P20 |
| 共有 記事上下（既定） | `.wt-share--top` / `.wt-share--bottom`（Web Share API + リンクコピー。SNS 名を出さない） | share: top-and-bottom | 語彙 |
| 共有 フロート | `body.wt-share-float`（SP 右下 48px、PC は本文右のレール） | float | 語彙 |

### 2.8 4 軸（opt-in）

| 軸 | セレクタ | 内容 | 根拠 |
|---|---|---|---|
| motion | `body.wt-motion-on`（+ `html.wt-js`） | `.wt-reveal` の fade-up（375ms、transform/opacity のみ）、`.wt-count[data-to]` の count-up。`prefers-reduced-motion` で両方停止（初期状態表示・最終値表示） | #132、P29、R76–R82 |
| depth | `body.wt-depth-1` / `.wt-depth-2`、`.is-style-wt-raised`（ボタン） | 影 3 段（theme.json shadow `depth-1/2/3`）、カード hover の浮遊、CTA ボタンの立体（depth-2 で自動、または block style） | #122 |
| spatial | `body.wt-density-airy` / `-compact` | spacing preset `--wp--preset--spacing--40〜80` と本文段落・見出し余白の差し替え | #134 |
| de-text | `body.wt-detext-on`、`.is-style-wt-badge-list`、`.is-style-wt-icon-list`、`.is-style-wt-quote-mark` | 無装飾の ol を番号バッジ化、h2 前の丸マーク、引用符、目次番号の丸バッジ | #127（メディア枠 D 番号） |

### 2.9 自動コントラスト guard

- `assets/js/contrast.js`: 写真の上に文字を置く要素（hero アイキャッチ `.wt-posthead__img`、`cover.is-style-wt-scrim`、`[data-wt-scrim]`）の画像を 32×32 の canvas に描き、**下部 55%（文字が載る領域）の相対輝度 L** から `data-wt-lum="dark|mid|light"` を付ける（L < 0.12 / < 0.35 / それ以上）。`data-wt-lum-value` に L を残す。
- CSS: `.wt-posthead__img[data-wt-lum]::after` と `.wp-block-cover.is-style-wt-scrim[data-wt-lum]::before`（登録 block style のクラス）で、`data-wt-lum` ごとにスクリム（黒の linear-gradient、to top）の不透明度を切替。下端 / 55–70% / 上端の alpha は dark .55/.40/0、mid .80/.70/.15、light .95/.93/.70、未計測（既定）.90/.84/.30。**同一オリジンでない・読めない画像は属性を付けず既定（強）に倒す**。初回計測で light 画像の h3 位置が 3.49:1 だったため、文字位置（下部 0〜60%）の最小 alpha を引き上げた（§4）。
- ゲートの検査方法（`scripts/verify.mjs` §6、実描画から算出）: 文字要素の boundingRect を取り、(a) スクリム擬似要素の `background-image` の gradient stop を解析して文字矩形の上端・下端位置の alpha を線形補間し小さい方を採る、(b) 画像を canvas に描き object-fit: cover の写像で文字矩形に当たる画素の平均輝度 L と最大輝度を測る、(c) 文字要素ごとに `getComputedStyle().color` の実色の輝度 Lt を取り、合成輝度 `Lc = L × (1 − α)`（黒スクリム）との比 `(Lt + 0.05) / (Lc + 0.05)` を本文 4.5:1・大文字（≥18.67px bold / ≥24px）3:1 で判定。hero では h1・パンくず 2 リンク・日付を個別に測る（メタは CSS で白に上書き）。gradient の補間は線形近似で、ブラウザの実際の補間（sRGB / premultiplied）と僅差があるため**概算**。最大輝度画素での比も `ratioWorstPixel` として併記。

### 2.10 404

| 要素 | セレクタ | 根拠 |
|---|---|---|
| 謝意・原因・検索（ボタン付き）・カテゴリ・ホーム | `templates/404.html`（`.wt-404__why`、`.wt-404__search`、`.wt-404__cats`、`.wt-404__home`）+ `<meta name=robots content=noindex>` | R60、R61、P33 |
| 変種: 人気記事（既定） | `body.wt-nf-popular` → `.wt-404__variant--popular`（Query Loop `queryId:903`、PoC では新着順で代替） | TPL-01 |
| 変種: CTA | `body.wt-nf-cta` → `helix-wt/cta-box` | TPL-01 |
| 変種: 検索語提案 | `body.wt-nf-suggest` → `.wt-suggest`（`notfound.js` が URL パスから語を切り出し検索リンク化、検索欄にも入れる） | TPL-01 |
| CV slot レーン | `parts/cv-slot.html`（`.wt-cv`: 比較記事リンク / LP リンク（CTA 色）/ 問い合わせボタン） | PO 指示 2026-09-05、§2 全用途共通 |

### 2.11 その他の既定（P01–P33 の反映）

本文 17px 固定（P01）、lh SP 1.6 / PC 1.7、contentSize 680px（= 17px で 40 字、P02）、gutter `clamp(16px,4vw,24px)`、ボタン高 48px（P05）、リンクは下線 + アクセント色（P21）、`:focus-visible` 2px アクセント + 白 4px（P31）、CTA 色 #c2410c は CTA 以外に使わない（P07。ランクバッジと注意色のみ同系色を使用 = 要検討）、画像 `alt` 未設定は `alt=""` を保証（P27 の一部）、自動送りなし（P11）。

## 2.12 段3 — カテゴリ面・footer・記事末尾 slot

段3は `category.html` をカテゴリ面の専用テンプレートとして追加し、`archive.html` にも同じ構造を反映した。カテゴリの子カテゴリ・ミニ HOME・ランキング・記事末尾の前後記事は、WordPress の投稿・タームを取得して表示する dynamic block であり、AI判定・外部API・人気度の推定は行わない。`cat_ranking` とミニ HOME のランキングは、既存404/関連記事と同じくPoCでは日付順をランキング表示へ投影する。

観察根拠は台帳 v2 の `aggregate-v2.md` / 親 `README.md §1b` / 親 `by-purpose.md §2b`。値は `cat/pc` / `cat/sp` または `top/pc` / `top/sp` の n 比をそのまま記載した。`tail_order` は台帳が要素の出現頻度のみを集計して順序を集計していないため、既定順は `by-purpose §2b` の案であることを明記する。

### カテゴリ面

| 軸 / variant | セレクタ・実体 | 型名 | 台帳 v2 の根拠（PC / SP） |
|---|---|---|---|
| `cat_header: name-only` | `body.wt-cat-header-name-only .wt-cat-head` | category.header: name-only | 42% / 46%（多数派） |
| `cat_header: name-desc` | `.wt-cat-head__desc` | category.header: name+description | 26% / 24% |
| `cat_header: hero` | `body.wt-cat-header-hero .wt-cat-head` + `::before` | category.header: hero-style | 5% / 5%（少数派） |
| `cat_children: none` | `.wt-cat-children--none` | category.children: none | 56% / 55%（最多） |
| `cat_children: chips` | `.wt-cat-children--chips` | category.children: chips | 29% / 25%（既定として選択） |
| `cat_children: cards` | `.wt-cat-children--cards` | category.children: cards | 2% / 2%（少数派） |
| `cat_children: steps` | `.wt-cat-children--steps` | category.children: numbered-steps | 画像バナー 1% / 1% や cards 2% / 2% の稀な導線を、比較媒体の「読む順番」用に選択肢化（#129） |
| `cat_list: grid` | `.wt-cat-list` / `body.wt-cat-list-grid` | list.layout: grid | 44% / 14% |
| `cat_list: thumb-list` | `body.wt-cat-list-thumb-list` | list.layout: thumb-list | 14% / 35% |
| `cat_list: featured-grid` | `body.wt-cat-list-featured-grid` | list.layout: featured+grid | 4% / 8%（少数派） |
| `cat_pagination: numbers` | `.wt-cat-pagination` | pagination: numbers | 27% / 19%（既定） |
| `cat_pagination: load-more` | `.wt-load-more` + `assets/js/category.js` | pagination: load-more | 9% / 6% |
| `cat_pagination: prev-next` | `.wp-block-query-pagination-previous/next` | pagination: prev-next | 3% / 3% |
| `cat_ranking: none` | `body.wt-cat-ranking-none` | ranking.in-category: none | 85% / 88%（多数派） |
| `cat_ranking: sidebar` | `.wt-cat-ranking`（PC側欄、SP下部） | ranking.in-category: sidebar+bottom | 8% / 1% |
| `cat_ranking: bottom` | `.wt-cat-ranking`（下部） | ranking.in-category: bottom | 1% / 4% |
| `cat_minihome: off` | `.wt-cat-primary` | category.mini-home: none | 86% / 87%（多数派） |
| `cat_minihome: on` | `.wt-cat-minihome`（子別4件 + 一覧へ + 読む順番 + ランキング） | category.mini-home: sections per child | 8% / 7%（少数派。比較媒体の回遊用） |
| カード共通 | `.wt-cat-card` | list.card: image-top + date + category-chip | image-top 25% / 25%、date 24% / 23%、category-chip 16% / 17%。抜粋は12% / 12%のため `thumb-list` と先頭featuredで表示 |

既定の `grid` は PC では3列、SPではCSSで画像左の1行サムネリストへ畳む。カテゴリデータは親 `topic-index` と子 `topic-one` / `topic-two` / `topic-three`、ダミー記事14件以上を想定し、タイトル・説明は固有名を使わない。

### footer

| 軸 / variant | セレクタ・実体 | 型名 | 台帳 v2 の根拠（PC / SP） |
|---|---|---|---|
| `footer_layout: sitemap` | `.wt-footer__layout--sitemap` / `.wt-footer__sitemap details` | footer.layout: mega(sitemap) + accordion(sp) | cat 40% / 28%（多数派寄り） |
| `footer_layout: single-row` | `.wt-footer__layout--single-row` | footer.layout: single-row | cat 23% / 25% |
| `footer_layout: columns-3` | `.wt-footer__layout--columns-3` | footer.layout: columns-3 / stacked-centered(sp) | cat 12% / SPは中央積みへ畳む |
| `footer_above: none` | `.wt-footer__above-slot--none` | footer.above: none | cat 47% / 49%（最多） |
| `footer_above: cta-band` | `.wt-footer__above-slot--cta-band` | footer.above: cta-band | cat 19% / 16% |
| `footer_above: banner-row` | `.wt-footer__above-slot--banner-row` | footer.above: banner-row | cat 14% / 17%。比較媒体PCでは48%で用途依存の多数派 |
| `footer_above: newsletter` | `.wt-footer__above-slot--newsletter` | footer.above: newsletter | cat 8% / 6%（少数派） |
| `footer_legal: copyright-links` | `.wt-footer__legal--links` | footer.legal: copyright+links | cat 47% / 42% |
| `footer_legal: copyright-only` | `.wt-footer__legal--only` | footer.legal: copyright-only | cat 36% / 40% |
| `footer_extra` | `.wt-footer-extra-slot--sns/sites/badges/address` | footer.extra: slot ON/OFF | SNS 38% / 36%、関連サイト5% / 10%、認証バッジ8% / 7%、住所4% / 6%。既定はSNSのみON、値 `sns-sites` 等で組み合わせる |
| `footer_totop: off` | `.wt-totop` 非表示 | footer.back-to-top: none | cat 61% / 65%（多数派） |
| `footer_totop: button` | `.wt-totop` | footer.back-to-top: button-fixed | cat 22% / 12% |

SPのsitemapはHTML側で全 `details` に `open` を付け、`footer.js` がJS有効時だけ閉じる。したがってJS無効時は全項目を読める。SNSの丸アイコンは数字の汎用記号だけで、第三者ロゴ画像・固有名・外部URLを置かない。

### 記事末尾

| 軸 / variant | セレクタ・実体 | 型名 | 台帳 v2 の根拠（PC / SP） |
|---|---|---|---|
| `tail_order: related-author-share-cta` | `.wt-tail__slot--related/author/share/cta` のCSS order | related → author → share → CTA | 台帳は順序を未集計。by-purpose §2b の既定案。要素頻度は related 29% / 22%、author 12% / 14%、share 12% / 9%、CTA 9% / 11% |
| `tail_order: cta-related-author-share` | 同上 | CTA → related → author → share | slot順入替 |
| `tail_order: related-cta-author` | 同上 | related → CTA → author（shareは末尾） | slot順入替 |
| `tail_share: none` | `.wt-tail__slot--share` 非表示 | tail.share: none | 60% / 71%（多数派） |
| `tail_share: icons-row` | `.wt-tail__slot--share` | tail.share: icons-row | 30% / 13% |
| `tail_author: none` | `.wt-author-variant` 非表示 | tail.author: none | 60% / 65%（多数派） |
| `tail_author: avatar-bio` | `.wt-author-variant--avatar-bio` | avatar+bio | 14% / 9% |
| `tail_author: avatar-bio-sns` | `.wt-author-variant--avatar-bio-sns` | avatar+bio+sns | 14% / 12% |
| `tail_author: supervisor` | `.wt-author-variant--supervisor` | supervisor-separate | SP 1%（少数派。PCは集計値なし） |
| `tail_prevnext: off` | `.wt-tail__prevnext` 非表示 | tail.prev-next: none | 78% / 89%（多数派） |
| `tail_prevnext: thumb` | `helix-wt/tail-prevnext` / `.wt-tail__prevnext` | tail.prev-next: with-thumb | 11% / 4% |

既存の関連記事Query Loop、CTA pattern、共有軸は流用し、段3では末尾側のslot順と表示選択だけを追加した。新しい動的ブロックも投稿・ターム・前後記事・著者情報の表示に限定している。著者ボックス 3 型は `helix-wt/tail-author`（PHP）で描く: template part 内では core の `post-author-name` / `post-author-biography` / `avatar` が postId context を持たず空になる（実機で確認）ため。アバターは外部サービスへ問い合わせず、表示名の頭文字を丸く出す `.wt-author-box__initial`。

### 段3のguardと証跡

`verify.mjs` §8 は既存のSP監査に加えて、カテゴリ面とfooterのSP/PC 44px・24px監査、footerの表示色、hero見出しコントラスト、footer sitemap の no-JS 全展開、load-more の no-JS numbers退避、カテゴリページ送りリンクのHTTP 200、カテゴリ/footerの reduced-motion を記録する。`shots.mjs --stage3 true` は既存151枚を保持し、カテゴリ18 variant×SP/PC、footer27 variant×SP/PC、記事末尾11 variant×SP/PCの計112枚を追加する（実機実行済み。`CATALOG-INDEX.json` は 151 → 263 エントリ、`results/*.jpg` 263 枚）。実機結果（`results/verify.json`、WP 7.1 ローカル）: `summary` **pass 14 / fail 0**。段3分の内訳 — タップ監査 カテゴリ面 SP 34/34・PC 34/34、footer（`footer_extra:all,footer_totop:button`）SP 26/26・PC 26/26（44px・24px とも未達 0、inline 除外 0）。footer 表示色 34 要素すべて 4.5:1 以上（最小 5.25:1 = 法的表記・extra 見出しの mute 色。丸アイコン・to-top は自前の背景（contrast 色）との比 17.13:1 で判定。初回実行では footer 背景と比べて 1.08:1 と誤判定したため、guard を「要素自身の実効背景（最も近い不透明な祖先）」で測る形に直した）。hero 見出し: スクリム alpha 0.88、白見出しとの比 5.84:1。footer sitemap no-JS: `details` 4/4 が open・内容可視。load-more no-JS: ボタン非表示・numbers 2 件表示。ページ送りリンク 2 件とも HTTP 200（`/page/2/`）。reduced-motion: カード・to-top・footer の transition `none`。

撮影スクリプトの修正: footer / 記事末尾は viewport 外に位置するため、`save()` の clip をページ座標 + `fullPage` で切り出す形に直した（初回は「Clipped area is outside」で footer の 1 枚目に失敗）。あわせて、要素までスクロールして lazy 画像を eager 化し読込を待つこと、`fullPage` 撮影で srcset 候補が切り替わり再読込が走るため 1 回目を捨てて撮り直すこと、を追加した（それ以前の撮影では関連カードの画像が空だった）。記事末尾の author / share / prevnext は該当 slot（`.wt-tail__slot--*`）だけを切り出し、order 3 型と none / off は末尾全体 `.wt-tail` を撮る。実機で見つけて直した表示不具合: footer SP で `.wt-footer__legal` の両型が同時表示（SP の `display:block` が型別の非表示を打ち消していた）、記事末尾 author slot の core ブロックが空描画（→ `helix-wt/tail-author`）、記事 554 の `post_author` が 0（→ 1 を設定）。

## 3. 実測（`results/metrics.json`、調査スクリプト `../2026-09-04-site-survey/scripts/measure.mjs`）

| | 本文 | lh | h1 | h2 | h3 | ヘッダー高 | ボタン高 | 本文列幅 | 小タップ率 |
|---|---|---|---|---|---|---|---|---|---|
| 試作 03 記事 SP 390 | 17 | 1.6 | 22.53 | 18.52 | 17.27 | 61 | 48 | 358 | 0.04 |
| 試作 03 記事 PC 1280 | 17 | 1.7 | 28 | 24 | 20 | 61 | 48 | 680（`cont` 1232 は alignfull 外枠） | 0.16 |
| 試作 02 記事 SP（参考） | 15.09 | 1.8 | 22.53 | 20.35 | 18.18 | 61 | 46 | 350 | 0.72 |
| 調査 記事 中央値 SP（参考） | 16 | 1.8 | 22.2 | 20 | 18 | 64 | 40 | 351 | – |

小タップ率（`smallTapRate`）は調査スクリプトの定義（44px 未満の a/button の割合。本文中のインラインリンクを含む）。

## 4. 検証結果（`results/verify.json`、`scripts/verify.mjs`。Astra レビュー PR #139 の指摘 8 件を反映して再実行）

下表は段1/2の検査項目。段3で追加した 9 項目（categoryTapSp / categoryTapPc / footerTapSp / footerTapPc / footerContrast / footerNoJs / loadMoreNoJs / categoryPagination / categoryHeroContrast）の結果は §2.12「段3のguardと証跡」に記載し、`summary` は 14 項目 pass 14 / fail 0。

| 項目 | 結果 |
|---|---|
| JS 無効描画（SP、`motion:on,header:announce`） | `html.wt-js` なし、お知らせ帯 存在・可視、目次 表示・開・リンク 12、`.wt-reveal` 10 件すべて opacity 1、商品カード・比較表・関連カード 7 件 表示、本文 3,052 字表示。`pass: true` |
| reduced-motion（SP、`motion:on`） | `.wt-reveal` 10/10 が初期表示、ヘッダー・ボタンの transition `none`、count-up は最終値「1,284」。比較: 通常設定では読み込み直後 10/10 が非表示 → スクロールで出現 |
| コントラスト（PC、算出） | CTA ボタン 白/#c2410c **5.18:1**、本文段落内リンク（`.wp-block-post-content > p > a`、#1d4ed8/白）6.7:1、補助文字 mute 5.69:1、PR 表記 5.69:1、目次リンク 6.19:1、帯タイトル 6.7:1、ランクバッジ 5.18:1、アウトラインボタン 6.7:1、価格単位 5.69:1、リンクカードラベル 5.69:1、カード日付 5.69:1。11 項目すべて 4.5:1 以上 |
| 自動コントラスト guard（実描画、§2.9 の算式、文字は実色 #fff） | dark 画像（`dark`、文字位置の画像 L 0.025 / α .371）本文 15.98:1・h3 16.74:1 / mid（`mid`、L 0.246 / α .66）本文 7.86:1・h3 6.17:1 / light（`light`、L 0.882 / α .933）本文 9.62:1・h3 5.48:1。スクリムなしでは mid 3.55、light 1.13 で不合格。記事 hero アイキャッチ（生成写真、`mid`）h1 11.21:1（最大輝度画素 3.38、要 3:1）、パンくず「ホーム」10.39:1、カテゴリ 7.38:1、日付 17.35:1（いずれも実色 rgb(255,255,255)。著者名・更新日はこの記事では非描画）。10 判定すべて合格 |
| 比較表（記事 554 の描画 HTML） | `<thead>` 無傷、`th` 4 / `scope="col"` 4、行 8 / 行見出し `tbody th[scope=row]` 8（`td[scope=row]` 0）、`data-th` 24（データセル td のみ。行見出し th の `data-th` 0）、`tfoot` 行は変換対象外（本記事に tfoot なし、変換件数 0）、`caption` あり。`pass: true` |
| 404 | 素の URL・3 変種すべて **HTTP 404**、robots meta 全件 `["max-image-preview:large","noindex"]` → noindex あり、謝意・原因・検索（ボタン付き）・人気 3 件・カテゴリ・ホーム・CV slot 3 枠・検索語提案 4 件 |
| タップ領域監査（SP） | 除外は p / li 直下の display:inline リンクだけ（記事 2・カタログ 1・404 0）。core の skip-link（1×1、フォーカス時のみ表示）は SR 用として別掲。**44px（P05）**: 記事 50/50、帯 + カルーセル + float 共有 54/54、404 23/23、カタログ 13/13。**24px（WCAG 2.5.8）**: 同じく全件。サイト名・パンくず・カテゴリターム・カードタイトルは inline-flex + 負マージンで 44px 化（行送りは据え置き） |
| 見出し 1 行 | h2 18.5px、18 字、358px、1 行（19.3 字まで） |
| 目次しきい値 | h2 5 / h3 7 → 目次 5 / 7、`scroll-margin-top` 76px、SP は JS で閉 |
| 動作（別スクリプト） | お知らせ帯: 閉 → 再読込後も非表示（localStorage キー 1 件）。ヘッダー: 下スクロールで隠れ、上で再表示、背景不透明。目次: `#h-3` 到達で「3 製品の比較表」が current。カルーセル前後ボタン 2 組。比較表 SP: 行が block（カード）。横スクロールなし |

修正した点（初回検証・Astra レビューで判明）: PR 表記の重複判定が `is-style-wt-product` に誤一致して未挿入だった（`class="wt-pr "` 判定へ）、次に読むカードが関連グリッドの列幅を継承していた、目次リンク・FAQ summary・フッターナビが 44px 未満だった。レビュー指摘: `<th` の正規表現が `<thead>` に一致し最後の列見出しに scope が付かなかった（thead 内限定・タグ境界限定・件数制限なしへ）、post meta 4 件に sanitize_callback（allowlist 外は既定値）と REST schema enum を追加、監査の除外条件を狭め 44 / 24 を分離、guard の検査を固定 alpha から実描画へ（その結果 light 画像が未達と判明 → スクリム強化）、robots meta 全件判定、JS 無効テストで帯を検査、本文リンクのセレクタを段落内に限定（記事本文に内部リンクを 1 つ追加）。再レビュー（head cc9fa8b）: post meta の空文字はサイト設定継承として保持、比較表の先頭列を `<th scope="row">` に（CSS・検査も th 前提へ）、輝度別スクリムのセレクタを登録 block style クラス `.is-style-wt-scrim` に統一、guard は文字要素ごとの実色で判定し hero 上のメタを白に上書き、既存 scope 検出を空白・大文字対応に。

## 5. 描画証跡

`results/` には既存151枚（JPEG q75、長辺 1600 以下。`CATALOG-INDEX.json` に {file, face, part, variant, dev}）を保持する。内訳: 記事全長 SP/PC 2 + 画面単位 20、ヘッダー 8（PC 4・SP 3・帯）、アイキャッチ 10、目次 9、h2 12、h3 6、囲み 16、CTA 8、比較表 2、メリデメ 2、評価バー 2、リンクカード 2、PR 2、de-text 部品 2、関連 8、共有 4、4 軸 on/off 24（depth 8・density 4・detext 8・motion 4）、コントラスト guard 6、404 6（計 151）。段3で 112 枚を追加（カテゴリ面 18 variant × SP/PC = 36、footer 27 variant × SP/PC = 54、記事末尾 11 variant × SP/PC = 22。`category-*`, `footer-*`, `tail-*`）、計 263。既存 151 枚のファイル名・内容は変更していない。全長画像は縮小で判読しにくいため `article-screen-NN-*.jpg` を併用する。

## 6. 手順（再現）

1. `docker cp theme/helix-wt/. agent-neo-wp:/var/www/html/wp-content/themes/helix-wt/`（helix-wt は試作 02 から有効のまま）
2. 記事: `wp post create --post_type=post --post_content='<!-- wp:pattern {"slug":"helix-wt/compare-article"} /-->' ...`、アイキャッチは `wp media import <theme>/assets/img/media-pickup-1.jpg` → `_thumbnail_id`。関連一覧用に短い架空記事 5 件 + カタログ固定ページ（`helix-wt/catalog-03`、テンプレ page-canvas）
3. 段3データ: WP-CLIで親カテゴリ `topic-index` を作成し、子カテゴリ `topic-one` / `topic-two` / `topic-three` を `--parent=<親term_id>` で作成する。各子へダミー記事を5件ずつ、親へ横断記事を2件以上 `wp post create --post_type=post --post_status=publish --post_category=<term_id> --post_content='<!-- wp:paragraph --><p>ダミー本文です。</p><!-- /wp:paragraph -->' --post_title='読みもの <連番>'` で登録し、合計14件以上にする。カテゴリ説明は `wp term update category <term_id> --description='カテゴリの説明文です。'` で設定する。固有名・実在URL・第三者ロゴは使わない。
4. 変種の確認: 既存軸は `?wt=header:cta,sp:left,eyecatch:hero,toc:float,related:rank,share:float,motion:on,depth:2,density:airy,detext:on,nf:suggest`、段3は `?wt=cat_header:hero,cat_children:steps,cat_list:featured-grid,cat_pagination:load-more,cat_ranking:sidebar,cat_minihome:on,footer_layout:columns-3,footer_above:banner-row,footer_legal:copyright-only,footer_extra:sns-sites,footer_totop:button,tail_order:cta-related-author-share,tail_share:icons-row,tail_author:avatar-bio-sns,tail_prevnext:thumb` のように付ける。サイト既定は `wp theme mod set wt_<key> <value>`、記事単位は `wp post meta set <ID> wt_eyecatch|wt_toc|wt_pr|wt_share <value>`
5. 撮影は既存を上書きせず、`NODE_PATH=<playwright の node_modules> node scripts/shots.mjs --stage3 true --base <site> --out results`。検証は `node scripts/verify.mjs --base <site> --out results/verify.json`、計測は `node ../2026-09-04-site-survey/scripts/measure.mjs --url <記事 URL> --out <dir> --playwright <playwright パス>`
6. 輝度テスト画像 3 枚（`assets/img/lum-{dark,mid,light}.jpg`）は PHP GD で生成した無文字のグラデーション（手順はコンテナ内 eval、リポには成果物のみ）

## 7. 終了時状態（意図的に残置）

- テーマ `helix-wt`（試作 03 版）が有効。投稿: 記事 **554**（`/standing-desk-compare/`）、関連用 555–559、カタログ固定ページ 560（`/catalog-03/`）、添付 548–553、カテゴリ term 7（desk）。段3: カテゴリ term 8 `topic-index`（説明あり）、子 9 `topic-one` / 10 `topic-two` / 11 `topic-three`（各 5 件）、ダミー記事 561–577（17 件、親直下 2 件、アイキャッチは 548–553 を再利用、日付 2026-08-01〜17）。554–559 と 561–577 の `post_author` を 1 に設定（WP-CLI 作成時は 0 で、著者ボックスが空になる）。試作 02 の 518 / 519 / 520 / 533 と `wp_global_styles` 525 はそのまま。519 にアイキャッチ（添付 551）を付与。
- サイト名・キャッチフレーズ・ユーザー 1 の表示名と紹介文を架空値へ変更（試作 02 時の値から）。
- 撤去: `wp post delete 554 555 556 557 558 559 560 --force`、`wp post delete 548 549 550 551 552 553 --force`、`wp term delete category 7`、段3: `wp post delete $(seq 561 577) --force`、`wp term delete category 9 10 11 8`、試作 02 の README §5 の手順。

## 8. 未実装・次タスク

- LP: 段4で実装する。
- 人気記事の集計方式（404 の人気・関連の「人気」は新着順で代替、#110）。
- 選択 UI（サイトエディターの variation / 記事サイドバー）。現状は theme_mod・post meta・プレビュー引数。
- 目次の float は 1200px 以上のみ（本文 680 + レール 240 + 余白）。1024–1199px では box にフォールバック。
- コントラスト guard の canvas 標本化はクライアント側。実装ではアップロード時（サーバ）に輝度を事前計算して attachment meta に持ち、`data-wt-lum` をサーバで出す（R75「輝度事前計算」）。
- PR 表記の自動挿入は投稿タイプ post 全件（比較媒体前提）。実装ではカテゴリ / 記事 meta で対象を絞る。
- 4 軸のうち depth-2 の CTA 立体化と `.is-style-wt-raised` の重複、`.wt-c-*` 色 modifier の block style 化（現状は追加 CSS class）は設計で整理。
- 44px 監査: カード全面クリックの実効領域を数える監査ロジック（`a::after` の矩形を含める）。

## 9. 公開安全

サイト名・URL・実在の製品名・ブランド名なし（製品・価格・数値・引用は架空）。参照テーマは テーマA / テーマB 表記、第三者プラグイン名・SNS 名なし（共有は Web Share API + リンクコピー）。画像は生成画像と GD 生成のグラデーション（文字・ロゴなし）。パスはリポ相対。スクリプトの既定 URL はローカル docker のもの。
