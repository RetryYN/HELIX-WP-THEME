# デザイン試作 03 — 比較・アフィリエイト媒体の記事面・404・カテゴリ面・footer・LP（選べる型として実装）

- 実施日: 2026-09-05。計画は `PLAN.md`（段 1 = 記事面 + 404 が本書の範囲）
- 位置づけ: PoC 証跡。要求の確定・設計の決定ではない。PO 決定 2026-09-05「フロント先行」（試作 02 は殺風景・変化が少ないという PO 判定を受け、1 サイトパターン = 比較・アフィリエイト媒体に絞る）に基づく。段3ではカテゴリ ミニ HOME・footer・記事末尾 slot、段4では比較媒体が出すサービス訴求 LP の型を選択可能な状態にする。
- 方針: 見た目の差はすべて**テーマの実コード**（theme.json / block style / pattern / template part / CSS / 小さな JS）で、選択軸は body class・block style・pattern として実際に切り替えられる（DOM 注入のモックではない）。LP も固有名なしのダミー商材を対象にし、AI 判定・外部 API・外部フォント / スクリプトは持たない。
- 入力: 型の棚卸し `../2026-09-05-parts-pattern-taxonomy/by-purpose.md` §1「比較・アフィリエイト媒体」と §2（比較媒体・全用途共通）、語彙 `../2026-09-05-parts-pattern-taxonomy/PARTS-VOCAB.md`、既定値 `../2026-09-05-cro-usability-evidence/README.md` §2 P01–P33、パーツ一覧 `../2026-09-04-parts-inventory/README.md`（#98 / #107 / #110 / #122 / #126 / #127 / #130 / #132 / #134）。
- テーマ本体（`themes/`）・`plugins/` は未編集。`theme/helix-wt/` は試作 02 の複製に上書きした試作テーマ。

## 1. 選択軸の仕組み

選択は `functions.php` の `wt_axes()` に列挙した 34 軸。解決順は **プレビュー引数 `?wt=key:value,...`（PoC 用）→ 記事の post meta `wt_<key>`（eyecatch / toc / pr / share のみ、「この記事では目次を隠す」等）→ `theme_mod` `wt_<key>`（サイト既定）→ 既定値**。結果は `body.wt-<key>-<value>` の class になり、段3以降の `_` を含むキーには互換用の正規化 class（例: `wt-cat_header-hero` と `wt-cat-header-hero`）も付く。CSS がその class を切り替える。ヘッダーだけは `render_block_data` で template part の slug を `header-<variant>` に差し替える。LP の `footer_layout` は、そのページ面で theme_mod 未設定のときだけ `single-row` を面別既定にする。実装時はプレビュー引数を管理者限定にし、サイトエディター / 記事サイドバーの選択 UI を付ける。

| 軸 | 値（既定を太字） | 切替の実体 |
|---|---|---|
| header | **search** / nav / cta / announce | template part `parts/header{,-nav,-cta,-announce}.html` |
| sp（SP ヘッダー） | **search** / right / left | `body.wt-sp-*` + flex order |
| eyecatch | **title-image** / image-title / hero / side / none | `body.wt-eyecatch-*`（`.wt-posthead` の grid） |
| toc | **box** / float / collapsible / none | サーバ生成 `nav.wt-toc.wt-toc--*` |
| related | **grid** / list / rank / carousel / featured / ranking-numbers | `body.wt-related-*`（Query Loop の post-template） |
| share | **topbottom** / float / none | `body.wt-share-*` |
| motion | **off** / on | `body.wt-motion-on` + `html.wt-js`。**説明（PO 反応7回目）**: 出現アニメ軸。fade-up・count-up の on/off（`prefers-reduced-motion` は常に off 相当） |
| depth | **0** / 1 / 2 | `body.wt-depth-*`。**説明（PO 反応7回目）**: 奥行き軸。影・重なり・階層の強さ（0=フラット / 1=弱い影 / 2=強い影と浮き） |
| density | airy / **normal** / compact | `body.wt-density-*`（spacing preset の差し替え）。**説明（PO 反応7回目）**: 余白密度軸。行間・見出し上マージンの疎密（airy=広い / compact=詰める） |
| detext | **off** / on | `body.wt-detext-on`。**説明（PO 反応7回目）**: 脱テキスト感の軸。見出し先頭のドット・番号付きリストの丸バッジ化など、本文の文字密度を下げ、長文比較記事の読了率を上げる装置 |
| nf（404） | **popular** / cta / suggest | `body.wt-nf-*` |
| pr（段5 反応5回目で既定変更） | **auto** / on / off | 本文先頭に PR 表記を自動挿入。auto は本文先頭 200 字以内の「PR/広告/アフィリエイト」検出で重複挿入を抑止（旧既定 on は無条件挿入のまま残置） |
| cat_header | **name-only** / name-desc / hero | `templates/category.html` / `.wt-cat-head`（`core/term-description` を name-desc / hero で表示） |
| cat_children | none / **chips** / cards / steps | `helix-wt/category-children`（chips / card-grid / numbered-steps） |
| cat_list | **grid** / thumb-list / featured-grid | `.wt-cat-list`（PC grid、SP は画像左の thumb-list へ自動適用） |
| cat_pagination | **numbers** / load-more / prev-next | `.wt-cat-pagination` + `category.js`（load-more は JS 有効時に `a.wp-block-query-pagination-next` の href を fetch して `.wt-cat-list` へ追記、no-JS では numbers） |
| cat_ranking | **none** / sidebar / bottom | `helix-wt/category-ranking`（sidebar は PC、SP では下部） |
| cat_minihome | **off** / on | `helix-wt/category-minihome`（子カテゴリごとの4件、読む順番、ランキング） |
| footer_layout | **sitemap** / single-row / columns-3 | `parts/footer.html` の3 layout slot。sitemap の `details` は SP アコーディオン |
| footer_above | **none** / cta-band / banner-row / newsletter | `.wt-footer__above-slot--*` |
| footer_legal | **copyright-links** / copyright-only | `.wt-footer__legal--links` / `--only` |
| footer_extra | **sns** / none / sites / badges / address / 組み合わせ / all | SNS・関連サイト・認証バッジ・住所を slot ごとに body class で表示 |
| footer_totop | **off** / button | `.wt-totop`（`footer.js`、JS無効時も `href="#"` が上部へ戻る） |
| tail_order | **related-author-share-cta** / cta-related-author-share / related-cta-author | `.wt-tail__slot` の CSS order |
| tail_share | **none** / icons-row | `.wt-tail__slot--share` > `.wt-tail-icons`（既存 share 軸の bottom / float 共有は `.wt-tail` の外に据え置き、両立） |
| tail_author | **none** / avatar-bio / avatar-bio-sns / supervisor | `.wt-author-variant--*` |
| tail_prevnext | **off** / thumb | `helix-wt/tail-prevnext` / `.wt-tail__prevnext` |
| lp_header | **minimal** / logo-only / none | `page-lp` の3 header slot、none は LP 内アンカーナビ |
| lp_hero | **split** / fullbleed / product / text-only | `helix-wt/lp` の4 hero slot、fullbleed は `[data-wt-scrim]` |
| lp_hero_cta | **single** / double / form-inline | hero 内 CTA slot。form-inline は `method` / `action` と label を持つ form |
| lp_sections | **full** / short / trust | `helix-wt/lp` の section slot を body class と CSS order で表示・並べ替え |
| lp_cta_style | **solid** / outline / pill | LP CTA action の実色・border-radius・大型 pill |
| lp_fixed | **none** / sp-bottom-bar / float-cta | LP 内固定 CTA。SP 下部バーは SP のみ表示 |
| lp_legal | **on** / off | 打消し脚注と PR 表記の表示 |
| width（段5 追加） | narrow / **default** / wide | `body.wt-width-*` が `--wp--style--global--content-size` / `-wide-size` と `--wt-header-max` を上書き。本文最大 640/**680**/760px、wide 最大 1040/**1120**/1240px、ヘッダー内側最大 1200/**1440**/1600px |

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
| 比較表の追加4型（Claude案） | `.is-style-wt-compare-striped/-evaluation/-price/-showdown`。縞、評価セル◎○△、価格行、2製品対決。既存 `wt-compare*` は変更せず、SPは横スクロールで比較軸を保持 | table: striped / evaluation-cells / price-highlight / two-product-showdown | PO反応6、R39–R41 を追加解釈 |
| メリデメ（既存） | `columns.wt-prosc` + `.is-style-wt-label-title.wt-c-ok/.wt-c-warn` + `.is-style-wt-pros/-cons` | pros-cons: label-title | §2 |
| メリデメの追加3型（Claude案） | `.is-style-wt-pros-contrast/-icons/-band`。2カラム対比、○×アイコン、帯タイトル箱。SPは1列。比較記事の各製品節末尾でメリット・デメリットを並べる装置 | pros-cons: contrast / icon-list / band-box | PO反応6、比較媒体の用途 |
| 評価バー（既存） | `.wt-rate`（試作02のCSS） | review-bar: item-bars | P25 |
| 評価バーの追加3型（Claude案） | `.is-style-wt-review-stars/-bars/-score`。星+数値、項目別5本、総合スコア円+コメント | review-bar: stars / five-bars / score-circle | PO反応6、P25 |
| ブログカード（既存。旧クラス互換） | `helix-wt/linkcard`、`.is-style-wt-linkcard`（タイトルの `a::after` で全面クリック） | blogcard: internal-thumb-left | R52、P22 |
| ブログカードの追加3型（Claude案） | `.is-style-wt-blogcard-top/-band/-ogp`。画像上+抜粋、テキスト帯、外部OGP風。表示名・README・block style labelだけを変更し、`wt-linkcard` のCSS/HTMLクラスとslugは維持 | blogcard: image-top / text-band / external-ogp | PO反応6、R52 |
| PR 表記（既存） | 本文先頭に自動挿入 `p.wt-pr`（1行・xs・mute）、post meta `wt_pr=off` で抑止 | PR notice: one-line | §2 |
| PR 表記の追加4型（Claude案） | `.is-style-wt-pr-intro/-inline/-double/-band`。記事上部ラベル+1文、見出し横小ラベル、上下2箇所、アイコン帯。`.wt-pr__tag` は `flex:0 0 auto` + `white-space:nowrap` | PR notice: intro / inline / double / icon-band | PO反応6、比較媒体のPRチップ観察 |
| detextの追加4型（Claude案） | `.is-style-wt-detext-takeaways/-metrics/-diagram/-quote`。要点3カード、数字強調、図解プレースホルダ帯、引用大文字。本文の文字密度を下げ、長文比較記事で読了率を上げる装置 | detext: takeaways / metrics / diagram / large-quote | PO反応6、#127 |
| 引用符 | `.wp-block-quote.is-style-wt-quote-mark` | quote-style | 語彙 |

### 2.7 記事末・共有

| 変種 | セレクタ | 型名 | 根拠 |
|---|---|---|---|
| 次に読む 1 件 | `parts/article-tail.html` の Query Loop `queryId:901`（同カテゴリ優先・現在記事除外、`query_loop_block_query_vars`） | series / related | R53、P20 |
| 関連 3–6: グリッド（既定） | `body.wt-related-grid`（PC 3 列 / SP 2 列、`.wt-rcard` 全面クリック・高さ統一） | related.layout: grid-cards | 観察 25–26%、R52 |
| 横サムネ 1 行（再設計） | `body.wt-related-list`（SPはサムネ幅20%前後、タイトル2行まで） | thumb-list-1line | 台帳 v2: PC text-numbered 10% / SP thumb-list-1line 20% |
| ランキング番号（既存値を再設計） | `body.wt-related-rank`（1–3位は金銀銅色） | ranking-numbers | 台帳 v2: PC text-numbered 10% / SP ranking-numbers 6% |
| カルーセル（自動送りなし） | `body.wt-related-carousel`（scroll-snap、前後ボタンは `article.js` が生成） | carousel | 観察 13–25%、R14 |
| featured-big+small（Claude案） | `body.wt-related-featured`（先頭カードを2行分の大カード、残りを小カード） | featured-big+small | 台帳 v2: PC 5% / SP 2% |
| ranking-numbers（新規軸値、Claude案） | `body.wt-related-ranking-numbers`（`rank` の互換値を残した新しい明示名） | ranking-numbers | 台帳 v2: SP 6%、text-numberedの観察 |
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
- 反応7追加の見せ方（Claude案）は `white-fade`、`overlay-warm`、`overlay-cool`、`overlay-brand`、`bottom-gradient`、`blur-bright`、`duotone` の7具体型（概念上は白フェード / カラーオーバーレイ / 下部グラデーション / ぼかし+明度 / デュオトーンの5型。暖色・寒色・ブランド色はオーバーレイの色差分）として登録。全型が `.is-style-wt-scrim` を併用し、既存の輝度計測・`data-wt-lum` 選択を維持した上でCSS filter / pseudo overlayを重ねる。
- ゲートの検査方法（`scripts/verify.mjs` §6、実描画から算出）: 文字要素の boundingRect を取り、(a) スクリム擬似要素の `background-image` の gradient stop を解析して文字矩形の上端・下端位置の alpha / overlay色を線形補間し小さい方を採る、(b) 画像を canvas に描き object-fit: cover の写像で文字矩形に当たる画素の平均輝度 L と最大輝度を測る（ぼかし・brightnessは概算係数）、(c) 文字要素ごとに `getComputedStyle().color` の実色の輝度 Lt を取り、合成輝度 `Lc = L × (1 − α) + Loverlay × α` との比を本文 **4.5:1**・見出し **3:1** で判定する。既存3輝度×本文/見出しに加え、追加7具体型×dark/mid/light×本文/見出しの42判定を `contrastVariants` として記録する。hero では h1・パンくず 2 リンク・日付を個別に測る（メタはCSSで白に上書き）。gradientの補間は線形近似で、ブラウザの実際の補間（sRGB / premultiplied）と僅差があるため**近似式である**。最大輝度画素での比も `ratioWorstPixel` として併記。

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
| `tail_share: icons-row` | `.wt-tail__slot--share` > `.wt-tail-icons`（汎用の丸アイコン 3 + リンクコピー、各 44px） | tail.share: icons-row | 30% / 13% |
| `tail_author: none` | `.wt-author-variant` 非表示 | tail.author: none | 60% / 65%（多数派） |
| `tail_author: avatar-bio` | `.wt-author-variant--avatar-bio` | avatar+bio | 14% / 9% |
| `tail_author: avatar-bio-sns` | `.wt-author-variant--avatar-bio-sns` | avatar+bio+sns | 14% / 12% |
| `tail_author: supervisor` | `.wt-author-variant--supervisor` | supervisor-separate | SP 1%（少数派。PCは集計値なし） |
| `tail_prevnext: off` | `.wt-tail__prevnext` 非表示 | tail.prev-next: none | 78% / 89%（多数派） |
| `tail_prevnext: thumb` | `helix-wt/tail-prevnext` / `.wt-tail__prevnext` | tail.prev-next: with-thumb | 11% / 4% |

既存の関連記事Query Loop、CTA pattern、共有軸は流用し、段3では末尾側のslot順と表示選択だけを追加した。新しい動的ブロックも投稿・ターム・前後記事・著者情報の表示に限定している。著者ボックス 3 型は `helix-wt/tail-author`（PHP）で描く: template part 内では core の `post-author-name` / `post-author-biography` / `avatar` が postId context を持たず空になる（実機で確認）ため。アバターは外部サービスへ問い合わせず、表示名の頭文字を丸く出す `.wt-author-box__initial`。

### 段3のguardと証跡

`verify.mjs` §8 は既存のSP監査に加えて、カテゴリ面とfooterのSP/PC 44px・24px監査、footerの表示色、hero見出しコントラスト、footer sitemap の no-JS 全展開、load-more の no-JS numbers退避、カテゴリページ送りリンクのHTTP 200、カテゴリ/footerの reduced-motion を記録する。`shots.mjs --stage3 true` は既存151枚を保持し、カテゴリ18 variant×SP/PC、footer27 variant×SP/PC、記事末尾11 variant×SP/PCの計112枚を追加する（実機実行済み。`CATALOG-INDEX.json` は 151 → 263 エントリ、`results/*.jpg` 263 枚）。実機結果（`results/verify.json`、WP 7.1 ローカル）: `summary` **pass 26 / fail 0**（段 1/2 の 12 項目 + 段 3 の 14 項目。総合 `pass` は全検査の AND）。段3分の内訳 — タップ監査 カテゴリ面 SP 34/34・PC 34/34、footer（`footer_extra:all,footer_totop:button`）SP 26/26・PC 26/26、著者 SNS（`tail_author:avatar-bio-sns`、`.wt-author-sns a` 44px）SP 3/3・PC 3/3（44px・24px とも未達 0、inline 除外 0）。footer 表示色 34 要素すべて 4.5:1 以上（最小 5.25:1 = 法的表記・extra 見出しの mute 色。丸アイコン・to-top は自前の背景（contrast 色）との比 17.13:1 で判定。初回実行では footer 背景と比べて 1.08:1 と誤判定したため、guard を「要素自身の実効背景（最も近い不透明な祖先）」で測る形に直した）。hero 見出し（段 1 の guard と同じ方式: 文字矩形 4 隅を 115deg の gradient 軸へ射影した実効 α の最小値、`hero.png` を cover 写像で canvas から採った文字位置の平均輝度 L、実色、合成 Lc = L×(1−α) + Lscrim×α）: h1 40px bold α .635・L .395 → **5.21:1**（要 3:1、最大輝度画素では 2.49）、説明文 17px α .627・L .438 → **4.76:1**（要 4.5:1、最大輝度画素 2.44）。スクリムなしでは 2.36 / 2.15 で不合格。最大輝度画素比は段 1 と同じく参考値として併記（判定は平均輝度）。**この値は近似式による評価であり、実描画ピクセルの直接測定ではない**（段 1 の guard と同じ方式）: 測定矩形は文字グリフではなく h1 / 説明文コンテナの矩形全体、画像輝度は矩形内の平均、α は矩形 4 隅の最小値、合成は RGB 画素の alpha 合成ではなく輝度同士の線形混合。gradient 補間も線形近似。したがって「実際の文字コントラスト」の証跡ではなく、スクリム設計が下限を満たすかの目安として読む。footer sitemap no-JS: `details` 4/4 が open・内容可視。load-more no-JS: ボタン非表示・numbers 2 件表示。load-more JS 有効（PC）: ボタン表示・番号送り非表示・`a.wp-block-query-pagination-next` あり → クリックで 10 件 → 17 件（+7、2 ページ目の全件）、次ページなし → ボタンは computed で非表示（`display: none`、矩形 0×0。`[hidden]` を `display:none!important` で明示し、JS もインライン display を落とす。以前は CSS の `display:flex` が `[hidden]` に勝って「読み込み中…」の無効ボタンが残っていた）。固定ボタンの併用（`share:float,footer_totop:button`、最下部までスクロール）: SP は to-top を float 共有の上へ退避（bottom 8.25rem 相当）し、float 共有 48×104（右下）と to-top 48×48 が交差せず、3 ボタンとも中心点で elementFromPoint が自身。PC（≥1200px）は float 共有が本文右レール上部にあり交差なし。ページ送りリンク 2 件とも HTTP 200（`/page/2/`）。reduced-motion: カード・to-top・footer の transition `none`。

撮影スクリプトの修正: footer / 記事末尾は viewport 外に位置するため、`save()` の clip をページ座標 + `fullPage` で切り出す形に直した（初回は「Clipped area is outside」で footer の 1 枚目に失敗）。あわせて、要素までスクロールして lazy 画像を eager 化し読込を待つこと、`fullPage` 撮影で srcset 候補が切り替わり再読込が走るため 1 回目を捨てて撮り直すこと、を追加した（それ以前の撮影では関連カードの画像が空だった）。記事末尾の author / share / prevnext は該当 slot（`.wt-tail__slot--*`）だけを切り出し、order 3 型と none / off は末尾全体 `.wt-tail` を撮る。実機で見つけて直した表示不具合: footer SP で `.wt-footer__legal` の両型が同時表示（SP の `display:block` が型別の非表示を打ち消していた）、記事末尾 author slot の core ブロックが空描画（→ `helix-wt/tail-author`）、記事 554 の `post_author` が 0（→ 1 を設定）。

Astra レビュー（PR #141 1 巡目）の是正: (1) `category.js` の「次のページ」セレクタが `.wp-block-query-pagination-next a` で、core は a 要素自身にそのクラスを付けるため JS 有効時も常に numbers へ退避していた → `a.wp-block-query-pagination-next` に修正し、verify に JS 有効時の実動検査 `loadMoreJs` を追加。(2) 段 1 の `share:float` 用 `.wt-share--float` が `article-tail.html` から消えていた → bottom / float 共有を `.wt-tail` の外へ復元（share 軸で制御）し、`tail_share` slot は `.wt-tail-icons` 専用に。(3) hero コントラストを固定 α・固定 RGB の概算から段 1 と同じ実描画方式へ。(4) 総合 `pass` に `contrast` / `contrastGuard` / `headline` / 段 1 タップ 4 画面 / footer transition を反映。(5) `.wt-author-sns a` 32px → 44px、監査対象に追加。(6) §4 の数値を verify.json と再同期。

Astra レビュー（PR #141 2 巡目）の是正: (1) load-more 最終ページ後にボタンが残る（`[hidden]` が CSS の `display:flex` に負ける）→ `.wt-load-more:not([hidden])` に表示を限定し `[hidden]{display:none!important}` を明示、JS もインライン display:none を設定、verify `loadMoreJs` は属性でなく computed 可視性（矩形・display）で「最終ページ後に非表示」を assert。(2) `share:float` と `footer_totop:button` が 1200px 未満で同じ右下に重なる → 併用時は to-top を float 共有の上へオフセット、verify に `fixedOverlapSp` / `fixedOverlapPc`（矩形交差なし・中心点のクリック到達）を追加。(3) hero コントラストが近似式である旨を明記（上記）。

## 2.13 段4 — 比較媒体が出すサービス訴求 LP

段4は `page-lp` 1 枚を対象に、固有名のないダミー商材へサービス訴求を行う LP の PoC である。要求・設計の決定ではなく、段3までの選択機構を LP 面へ往復させる観察記録として実装した。`patterns/lp.php` は既存の `hero-split` / `numbers` / `features` / `steps` / `pricing` / `faq` pattern と比較表 style を流用し、LP 専用のロゴ枠・声・バッジ枠・CTA 帯だけを追加している。実ロゴ、受賞名、認証名、第三者サービス名は表示しない。

### LP の軸と型

| 軸 / variant | セレクタ・実体 | 型名 | PoC 既定 |
|---|---|---|---|
| `lp_header: minimal` | `.wt-lp-header--minimal` | header: minimal logo + CTA | **既定** |
| `lp_header: logo-only` / `none` | `.wt-lp-header--logo-only` / `.wt-lp-header--none`（後者は `.wt-lp-anchor-nav` のみ） | header: logo-only / anchor-nav-only | 選択可 |
| `lp_hero: split` | `.wt-lp-hero--split`（PC は画像右・見出し左） | hero: split-text-image | **既定** |
| `lp_hero: fullbleed` / `product` / `text-only` | `.wt-lp-hero--fullbleed[data-wt-scrim]` / `.wt-lp-hero--product` / `.wt-lp-hero--text-only` | hero: fullbleed-photo-overlay / product-shot / text-only | 選択可 |
| `lp_hero_cta: single` / `double` / `form-inline` | `.wt-lp-cta--single` / `--double` / `.wt-lp-cta-form` | hero CTA: single / double / form-inline | **single** |
| `lp_sections: full` | `.wt-lp__section` 全 slot、表示順は CSS `order` | sections: full | **既定** |
| `lp_sections: short` | hero → features → pricing → FAQ → CTA 帯 1 slot | sections: short | 選択可 |
| `lp_sections: trust` | hero → logos → numbers → testimonials → badges → CTA 帯 1 slot | sections: trust | 選択可 |
| `lp_cta_style: solid` / `outline` / `pill` | `.wt-lp-cta-action` | CTA: solid / outline / large pill | **solid** |
| `lp_fixed: none` / `sp-bottom-bar` / `float-cta` | `.wt-lp-fixed--*`（SP 下部バーは SP のみ） | fixed: none / SP bottom bar / float CTA | **none** |
| `lp_legal: on` / `off` | `.wt-lp-legal`（打消し脚注 + PR 表記） | legal: disclosure + PR | **on** |
| `footer_layout` | 段3の `.wt-footer__layout--*` | footer: 既存 3 型 | LP 面の未設定時は **single-row** |

CTA 帯は最大 3 slot（one / two / three）を持ち、全構成では 3 slot、short / trust では最終 slot を表示する。`comparison-table` は段1の `.is-style-wt-compare` を使い、`numbers` は出典注記、`testimonials` は掲載件数注記、`logos-row` / `badges` は汎用枠だけを置く。form-inline は全 hero variant に `method="post"` / `action="/lp/"`、`input` の固有 `id`、対応する `label[for]` を持たせ、JavaScript が無くても HTML form として送信できる形にした。

### 観察の多数派 / 少数派（台帳の %）

LP は「サービス / SaaS LP」向けの型を主に採り、比較媒体の hero / 固定パーツと全用途共通の v2 footer 値を補助参照した。以下は `../2026-09-05-parts-pattern-taxonomy/by-purpose.md` §1「サービス / SaaS LP」、§2「サービス LP | 行動・信頼」「全用途共通 | 守り」の値を転記したもので、LP の採用決定や要求値ではない。`—` はその variant 自体の直接集計が無いことを示す。

| 軸 | 観察の多数派 | 観察の少数派 / 直接集計なし | 本 PoC の読み方 |
|---|---|---|---|
| header | サービス LP の `logo-left-cta-right` は PC 34% / SP 74% | `with-announce-bar` は PC 10% / SP 13%、`transparent-over-hero` は SP 4%。logo-only / anchor-nav-only は LP 専用値 — | minimal を既定。logo-only / none は比較媒体 SP の `logo-center-only` 12% / `no-hamburger(text nav)` 17%を参照 |
| hero | サービス LP は PC `split-text-image` 35%、SP `text-only` 35% | サービス LP の `product-shot` は PC 27% / SP 29%、`split` は SP 14%、`text-only` は PC 13%。比較媒体の `fullbleed-photo-overlay` は PC 15% / SP 18% | split を既定、product / text-only / fullbleed を比較可能にする |
| hero CTA | 実装候補内では `single` PC 17% / SP 15%、`double` PC 17% / SP 11% | `form-inline` は PC 5% / SP 5%。なお `none` は PC 58% / SP 66%で最多だが、今回の LP 軸には入れない | LP の行動導線として single を既定、double / form は観察用 |
| sections | サービス LP の `logos-row` は PC 19% / SP 13%、`features` は PC 14% / SP 9%、`numbers` は PC 12% | `badges-awards` は PC 9% / SP 9%。steps / pricing / testimonials / comparison / FAQ はこの行の型別 %なし | 行動・信頼の必要型として full / short / trust の順序差を観察 |
| CTA style | `solid` / `outline` / `pill` の直接集計 — | 型別の %は作らない | theme.json の CTA / contrast 色で3表示を比較 |
| fixed | サービス LP の PC は `sticky-header` 21%、`cookie-consent` 17%、`announce-bar` 17%。SP は `announce-bar` 37%、`none` 31% | サービス LP の `float-cta` は上位外。比較媒体 PC の `float-cta` は15%、全用途 v2 の SP 下部バーは4% | none を既定、SP bottom bar / float CTA を選択可。footer to-top / share と矩形監査 |
| legal | サービス LP の信頼行は「数字（出典付き）/ 打消し表示」を必要型として記載 | 打消し / PR の LP 専用出現率 —。近接する footer 法定表示は copyright+links PC 44% / SP 35%、copyright-only PC 30% / SP 35% | 数字・No.1の脚注と PR 表記は on を既定、off も観察 |
| footer | サービス LP の PC `mega(sitemap)` 91%。SP `accordion(sp)` 50%、`mega(sitemap)` 35% | PC `columns-3` 5%、SP `single-row` 8% | LP の面別未設定既定だけ `single-row` にし、段3の全 variant は残す |

### 段4の guard / 検査項目

`verify.mjs` §9 は段3までの検査に加えて、次の結果を同じ `results/verify.json` の `summary` へ反映する。数値結果は `verify.json` の同名フィールドを正本とし、この節に別の計算値を作らない。

- `tap.lpSp` / `tap.lpPc`: LP 全面の可視 `a` / `button` / `input` / `summary` を、44px 目標と 24px 下限で監査する。本文中の inline link と打消し脚注の inline link はインライン例外として記録する。
- `lpContrast`: `solid` / `outline` / `pill` 各 variant の computed style の実色で、header / hero / CTA 帯 / おすすめ pricing の文字と背景を計算する。CTA 帯は theme.json の `contrast` 色を背景、`base` 色を文字へ使う。
- `lpFullbleedContrast`: 段1と同じ canvas の画像輝度標本化、cover 写像、gradient stop の alpha 線形補間で h1 / lead を測る。これは文字グリフの実ピクセルを直接測るものではない**近似**であり、`ratioWorstPixel` も参考値として残す。
- `lpFormNoJs`: JS 無効コンテキストで form の `method` / `action`、input `id`、label の関連付けを確認する。
- `lpAnchorNav`: `lp_header:none` のアンカー各 `href` について、スクロール先の実在とリンク可視を確認する。
- `lpSections`: full / short / trust の可視 slot と、画面上の CSS `order` を確認する。
- `lpFixedOverlap.sp` / `.pc`: `lp_fixed` 3 variant と `footer_totop:button` / `share:float` の組み合わせで、可視固定要素の矩形交差・viewport 内・中心点のクリック到達を確認する。
- `lpReducedMotion`: `prefers-reduced-motion: reduce` で LP の出現要素が透明にならず、LP action / section の transition が停止することを確認する。
- `lpLcpHero`: split / fullbleed / product の hero 画像に `fetchpriority="high"` と `width` / `height` があり、text-only は画像なしであることを確認する。
- `lpFooterFaceDefault`（Astra 1巡目是正）: theme_mod 未設定時、LP 面の body class に `wt-face-lp` / `wt-footer-layout-single-row` が付き、非 LP 面（記事）には `wt-face-lp` が付かず `wt-footer-layout-sitemap`（既定）のままであることを確認する。
- `lpVisibleAnchors`（Astra 1巡目是正）: `lp_hero_cta:double` × `lp_sections:{full,short,trust}` の3組合せで、表示中の全アンカー（header 内アンカーナビだけでなく hero CTA を含む）の href 先が実在し可視であることを確認する（href="#" のみの placeholder リンクは対象外）。
- `lpFaceScopedTotop`（Astra 1巡目是正）: 非LP面（記事）で `lp_fixed:sp-bottom-bar` を指定しても `.wt-totop` の `bottom` が既定値から変わらないことを確認する（LP面限定のCSSが非LP面へ漏れていないか）。

### 段4の検証結果

実機での再実行結果（`results/verify.json`）: `summary` 40 項目 **pass 40 / fail 0**、総合 `pass: true`。以下は保存済みフィールドの値を丸めずに転記した PoC の観察記録であり、要求・設計の決定ではない。

タップ監査（`tap.lpSp` / `tap.lpPc`）: 44px 目標・24px 下限の結果は次のとおり。両画面とも `below44: []` / `below24: []`。`srOnly` は `["a.skip-link.screen-reader-text 'Skip to content' 1x1"]` として別掲されている。

| フィールド | total | ok44 | ok24 | inlineText | pass |
|---|---|---|---|---|---|
| `tap.lpSp` | 24 | 24 | 24 | 1 | true |
| `tap.lpPc` | 24 | 24 | 24 | 1 | true |

表示色（`lpContrast`）: 下表は各 `styles[].items[]` の記録順で、index は 0 起点。同じ label の要素も省略せず、文字の実色 `color` と実効背景 `background`、`ratio`、判定しきい値 `required` を項目ごとに記載した。比は `:1`、各 style と `lpContrast` 全体も `pass: true`。

`lpContrast` — `style: solid`（`items`）

| index / label | color | background | ratio | required | pass |
|---|---|---|---|---|---|
| 0 / header CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 1 / hero CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 2 / CTA band action | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 3 / CTA band action | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 4 / CTA band action | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 5 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 6 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 7 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 8 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 9 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 10 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 11 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 12 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 13 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 14 / featured pricing heading | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 15 / featured pricing price | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 3 | true |
| 16 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 17 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 18 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 19 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 20 / featured pricing CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |

`lpContrast` — `style: outline`（`items`）

| index / label | color | background | ratio | required | pass |
|---|---|---|---|---|---|
| 0 / header CTA | `rgb(29, 78, 216)` | `rgb(255, 255, 255)` | 6.7 | 4.5 | true |
| 1 / hero CTA | `rgb(29, 78, 216)` | `rgb(255, 255, 255)` | 6.7 | 4.5 | true |
| 2 / CTA band action | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 3 / CTA band action | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 4 / CTA band action | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 5 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 6 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 7 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 8 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 9 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 10 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 11 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 12 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 13 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 14 / featured pricing heading | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 15 / featured pricing price | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 3 | true |
| 16 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 17 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 18 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 19 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 20 / featured pricing CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |

`lpContrast` — `style: pill`（`items`）

| index / label | color | background | ratio | required | pass |
|---|---|---|---|---|---|
| 0 / header CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 1 / hero CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 2 / CTA band action | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 3 / CTA band action | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 4 / CTA band action | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |
| 5 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 6 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 7 / CTA band heading | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 3 | true |
| 8 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 9 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 10 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 11 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 12 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 13 / CTA band text | `rgb(255, 255, 255)` | `rgb(23, 28, 34)` | 17.13 | 4.5 | true |
| 14 / featured pricing heading | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 15 / featured pricing price | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 3 | true |
| 16 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 17 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 18 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 19 / featured pricing item | `rgb(23, 28, 34)` | `rgb(255, 255, 255)` | 17.13 | 4.5 | true |
| 20 / featured pricing CTA | `rgb(255, 255, 255)` | `rgb(194, 65, 12)` | 5.18 | 4.5 | true |

fullbleed hero の自動コントラスト（`lpFullbleedContrast`）: `lum: light`、`sampledL: 0.553`、`gradient` は `linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.72) 45%, rgba(0, 0, 0, 0.25) 100%)`。`approximation` は「段1と同じ canvas 輝度標本化 + linear-gradient の線形補間による概算」、全体の `pass: true`。

| label | textColor | alphaAtText | imageLAtText | imageLMaxAtText | compositeL | ratioText | ratioWorstPixel | required | pass |
|---|---|---|---|---|---|---|---|---|---|
| h1 | `rgb(255, 255, 255)` | 0.479 | 0.327 | 1 | 0.17 | 4.77 | 1.84 | 3 | true |
| lead | `rgb(255, 255, 255)` | 0.728 | 0.522 | 1 | 0.142 | 5.46 | 3.26 | 4.5 | true |

段1・段3と同様、文字グリフでなく文字コンテナ矩形内の平均画像輝度と gradient の実効 alpha を使い、輝度同士を線形混合する**近似式による評価**であり、実描画ピクセルの直接測定ではない。合否は平均輝度に基づく `ratioText` で判定し、最大輝度画素による `ratioWorstPixel` は参考値として併記する。今回の `ratioWorstPixel` は両項目とも `required` 未満であり、全画素での達成を示す記録ではない。

form-inline の no-JS（`lpFormNoJs`）: `hero: "wt-lp-hero wt-lp-hero--split"`、`method: "post"`、`action: "/lp/"`、`inputId: "lp-email-split"`、`labelFor: "lp-email-split"`、`pass: true`。JS 無効時の form 属性と label 関連付けを確認した結果であり、送信先での受付・配送の実動検証ではない。

アンカーナビ（`lpAnchorNav`）: `targets` は `["lp-hero","lp-sections","contact"]`、`pass: true`。リンクごとの記録は次のとおり。

| href | targetId | targetExists | visible |
|---|---|---|---|
| `#lp-hero` | `lp-hero` | true | true |
| `#lp-sections` | `lp-sections` | true | true |
| `#contact` | `contact` | true | true |

セクション順（`lpSections`）: `.wt-lp__sections` 内の可視 slot を表示順に記録した `visible` は下表のとおり。各 variant の `expected` と同じ配列で、全体も `pass: true`。hero はこの配列の対象外。

| variant | visible（順序を保持） | pass |
|---|---|---|
| `full` | `["numbers","features","steps","logos","testimonials","pricing","comparison","faq","badges","cta-band--one","cta-band--two","cta-band--three"]` | true |
| `short` | `["features","pricing","faq","cta-band--three"]` | true |
| `trust` | `["logos","numbers","testimonials","badges","cta-band--three"]` | true |

固定要素の併用（`lpFixedOverlap.sp` / `.pc`、`footer_totop:button,share:float`）: 可視要素の `items` を原配列順に記載。矩形は `x` / `y` / `w` / `h`（px）で、`fixed` は LP 固定 CTA、`share` は float 共有、`totop` は to-top。`intersections` の件数は空配列の 0 件、`clickable` は各ボタン中心点での到達結果。

| dev / variant | fixedVisible / expectedFixedVisible | items（可視要素の矩形） | intersections（件数） | clickable | inViewport / pass |
|---|---|---|---|---|---|
| `sp / none` | `false / false` | `share {"x":326,"y":724,"w":48,"h":104}`、`totop {"x":326,"y":664,"w":48,"h":48}` | `[]`（0 件） | `[true,true,true]` | `true / true` |
| `sp / sp-bottom-bar` | `true / true` | `fixed {"x":0,"y":787,"w":318,"h":57}`、`share {"x":326,"y":724,"w":48,"h":104}`、`totop {"x":326,"y":664,"w":48,"h":48}` | `[]`（0 件） | `[true,true,true,true,true]` | `true / true` |
| `sp / float-cta` | `true / true` | `fixed {"x":16,"y":780,"w":144,"h":48}`、`share {"x":326,"y":724,"w":48,"h":104}`、`totop {"x":326,"y":664,"w":48,"h":48}` | `[]`（0 件） | `[true,true,true,true]` | `true / true` |
| `pc / none` | `false / false` | `share {"x":1084,"y":92,"w":48,"h":104}`、`totop {"x":1376,"y":836,"w":48,"h":48}` | `[]`（0 件） | `[true,true,true]` | `true / true` |
| `pc / sp-bottom-bar` | `false / false` | `share {"x":1084,"y":92,"w":48,"h":104}`、`totop {"x":1376,"y":836,"w":48,"h":48}` | `[]`（0 件） | `[true,true,true]` | `true / true` |
| `pc / float-cta` | `true / true` | `fixed {"x":16,"y":836,"w":144,"h":48}`、`share {"x":1084,"y":92,"w":48,"h":104}`、`totop {"x":1376,"y":836,"w":48,"h":48}` | `[]`（0 件） | `[true,true,true,true]` | `true / true` |

reduced-motion（`lpReducedMotion`）: `revealHidden: 0`、`actionTransition: "none"`、`sectionTransition: "none"`、`pass: true`。

LCP の目安（`lpLcpHero`）: hero の可視性と画像の `attrs` は次のとおり（属性値の文字列と数値・真偽値を原記録のまま区別）。全体の `pass: true`。これは画像の優先度・寸法属性と読込状態の検査であり、LCP 時間の実測ではない。

| variant | heroVisible | imageExpected | attrs | pass |
|---|---|---|---|---|
| `split` | true | true | `{"fetchpriority":"high","loading":"eager","width":"720","height":"540","complete":true,"naturalWidth":1200}` | true |
| `fullbleed` | true | true | `{"fetchpriority":"high","loading":"eager","width":"1440","height":"820","complete":true,"naturalWidth":1200}` | true |
| `product` | true | true | `{"fetchpriority":"high","loading":"eager","width":"512","height":"512","complete":true,"naturalWidth":800}` | true |
| `text-only` | true | false | `null` | true |

実機検証での是正の記録: (1) SP header の site-title が flex 内で幅 15px に潰れ、44px 監査に落ちた → site-title に `flex:1 1 auto;min-width:0`、リンクに `white-space:nowrap`、header CTA に `flex:0 0 auto` と SP の `width:auto` を設定した。(2) LP 要素の `content-box` と `width:100%` に padding / border が加算され、SP の layout viewport が 433px に拡大し、全幅化した float-cta が `share:float` と交差した → LP 関連要素を `border-box` 化し、SP float-cta は `width:auto;max-width:calc(100vw - 2rem - 48px - .75rem)` として右側の float 帯を避けた。(3) `verify.mjs` の cta-band キー抽出が接頭辞を二重に付け、正しい DOM の `wt-lp-cta-band--one/two/three` を拾えなかった → `one/two/three` のクラスを先に検索し、見つかった名前へ `cta-band--` を付ける順に是正した。上記の再実行値はこれらの是正後の観察である。

`shots.mjs --stage4 true` の撮影行列は実機で実行済み。段4で 48 枚を追加した（LP 専用 7 軸の `lp-*` 42 枚 + LP 面の `footer_layout` 3 variant × SP / PC の 6 枚）、計 311。既存 263 枚のファイル名・内容は変更していない。`CATALOG-INDEX.json` の末尾へ `{file, face: "lp", part, variant, dev}` を 48 件追記し、263 → 311 エントリになった。

### Astra 1巡目是正（head e4d6a8d に対するレビュー、指摘 4 件）

- 重大: `wt_is_lp_page()` が `is_page_template('page-lp.html')` で判定しており、`theme.json` の `customTemplates` 登録名（core が保存する slug）`page-lp` と不一致だった。通常のテンプレート選択・WP-CLI の `--page_template=page-lp` は slug 表記で `_wp_page_template` を保存するため、旧コードでは常に false になり footer の LP 既定（`single-row`）が効かなかった。`is_page_template( array( 'page-lp', 'page-lp.html' ) )` に修正し、README §6 手順 5 の `--page_template=page-lp.html` も `page-lp` に修正した（README 内の他の `page-lp.html` 表記も同語へ統一）。実機で LP ページ（post 601）の `_wp_page_template` を `wp post meta get 601 _wp_page_template` で確認し `page-lp` が保存されていること、body class に `page-template-page-lp` / `wt-face-lp` / `wt-footer-layout-single-row` が付くこと、記事面には付かず `wt-footer-layout-sitemap`（既定）のままであることを確認した。`verify.mjs` に `lpFooterFaceDefault` を追加し検査化した。
- 改善: `patterns/lp.php` の double CTA 副ボタン「比較表を見る」（`#comparison`）が `lp_sections:short` / `trust` では非表示セクションを指していた。副ボタンを `full → #comparison`（比較表を見る）/ `short → #pricing`（料金を見る）/ `trust → #voices`（利用者の声を見る、testimonials セクションの実 id）の3種の `<a data-lp-cta-target="...">` に分け、`theme.css` に `body.wt-lp-sections-{short,trust}` に応じて該当 variant だけを `inline-flex` にする CSS を追加した（既定は `full`）。`verify.mjs` に `lpVisibleAnchors` を追加し、`lp_hero_cta:double × lp_sections:{full,short,trust}` の3組合せで表示中の全アンカー（header 内だけでなく hero CTA も対象）の href 先が実在し可視であることを検査する（`href="#"` のみの placeholder リンクは対象外）。
- 改善: `theme.css` の `.wt-lp-fixed-sp-bottom-bar.wt-footer-totop-button:not(.wt-share-float) .wt-totop{bottom:...}` が LP 面限定の条件を持たず、`wt_lp_fixed` は全画面へ body class が付くため非LP面でも SP の to-top が持ち上がり得た。`functions.php` の `body_class` フィルタで LP 判定時だけ `wt-face-lp` を付与し、該当 CSS ルールを `body.wt-face-lp.wt-lp-fixed-sp-bottom-bar...` に限定した。`verify.mjs` に `lpFaceScopedTotop` を追加し、記事面（非LP）で `lp_fixed:sp-bottom-bar` を指定しても `.wt-totop` の `bottom` が既定（16px）から変わらないことを検査する。
- 軽微: `patterns/lp.php:112` の比較セクション内 `.wt-lp-section-inner` の閉じ `</div>` 欠落を追加した。`php -l` 通過、レンダリング後の DOM で当該 `<div class="wp-block-group wt-lp-section-inner">` が正しく `</section>` の前で閉じることを確認した。

是正後の実機再実行（`results/verify.json`）: `summary` 40 項目 **pass 40 / fail 0**（既存 37 + 新規 3）、総合 `pass: true`。`shots.mjs --stage4` の再撮影は既存 311 枚と一致し、差分 0 枚だった（本是正は body class の追加条件と CSS セレクタ限定・PHP マークアップの閉じタグ追加のみで、既定描画・見た目には変化がないため）。

### Astra 2巡目是正（head 5d3bb62 に対するレビュー、指摘 改善2・軽微1。重大なし・merge可の判定を維持）

- 改善: `lpVisibleAnchors` は「表示中の全リンクが href 先を持つ」ことしか検査しておらず、副 CTA そのものが1本も表示されていない・別 variant の href のまま残っている、といった消失を検出できなかった（保存済みリンク集合から副 CTA だけを除いても同じ判定なら合格してしまう）。`scripts/verify.mjs` の `lpVisibleAnchors` に `.wt-lp-cta-action--secondary[data-lp-cta-target]` を直接クエリする検査を追加し、構成ごとに「可視な副 CTA が **ちょうど1本**」かつ「その `href` が期待値（full→`#comparison` / short→`#pricing` / trust→`#voices`）と一致」することを合格条件に加えた（`secondaryButtons` / `expectedHref` / `secondaryPass` を記録）。
- 改善: `lpFaceScopedTotop` は非 null・前後一致のみを見ており、両方が同じ非既定値（例 68px）でも合格し、かつ `share:float` を明示回避していなかったため、サイト既定（`share` の既定値）次第で問題の CSS 分岐を踏まずに合格できる余地があった。記事面の計測を `share:topbottom`（float ではない）に固定し、判定を「`baseline` と `withLpFixed` がともに `"16px"`（`theme.css:631` の `.wt-totop{bottom:1rem}` = 16px という既定値そのもの）と一致」に変更した。
- 軽微: README §2.13 のタップ数（`tap.lpSp` / `tap.lpPc`）が「36 / 36 / 36」のまま古く、`results/verify.json` の実データ（total 24・ok44 24・ok24 24）と不一致だった。表を実データへ修正し、他の掲載数値（`lpContrast` 各 style 21 件、`lpFullbleedContrast`、`lpAnchorNav`、`lpSections`、`lpFixedOverlap.sp`/`.pc`、`lpLcpHero`）も `results/verify.json` と全件照合し、一致を確認した（不一致はタップ数のみ）。

是正後の実機再実行（`results/verify.json`）: `summary` 40 項目 **pass 40 / fail 0**、総合 `pass: true`（項目数は前回是正から変わらず、検査条件のみ強化）。`shots.mjs --stage4` の再撮影は既存 311 枚の MD5 と一致し、差分 0 枚だった（本是正は `verify.mjs` の検査条件強化と README の数値修正のみで、テーマの PHP / CSS には変更がないため）。

## 2.14 段5 — PO 反応 1 回目（2026-09-05）の是正

PO 反応（原文）:「フルスクリーン10の3スマホのテーブルがおかしいな。ここテーブルは調整できるか？価格の文字サイズがおかしいな。ヘッダーのPCがおかしいな。これ画面に合わせたコンテンツ幅になっている？CTAど真ん中は気持ち悪いなｗおそらく全体的にPCのコンテンツ幅、サイドカラム幅この辺りの最大サイズ、最小サイズみたいな定義が変。ひとまずの指摘。」

指摘は4点。それぞれの原因と是正、証跡ファイルは以下（要求・設計の決定ではなく PoC の是正）。

1. **比較表 SP（`article-screen-03-sp.jpg` = PO 指摘「フルスクリーン10の3」）でキャプションが1文字ずつ縦積みになっていた**
   - 原因: `<table><caption>電動昇降デスク 3 製品の比較（…）</caption>…</table>` で、SP のカード化 CSS（`is-style-wt-compare table{display:block}`）が `table` を table 書式から外すと、子の `caption`（既定 `display:table-caption`）はその生成元を失い、幅が確定せず 1 文字ずつ折り返す。
   - 是正: `theme/helix-wt/assets/css/theme.css` に `caption{caption-side:top;display:block;width:100%;text-align:left;font-weight:700;font-size:var(--wp--preset--font-size--m)}` を追加し明示的にブロック化・全幅化した（PC の通常 table でも無害）。
   - 証跡: `results/article-screen-03-sp.jpg`（1 行の横書きに復帰）、`results/table-compare-sp.jpg` / `results/table-compare-pc.jpg`。`verify.json.tableCaptionSp`（`ratio` = 幅/高さ、縦積みなら概ね 1 未満、実測 7.52 → `pass: true`）。
2. **比較表の価格セルの文字サイズがおかしい**
   - 原因: `<td class="wt-num">` の `.wt-num` は数字訴求セクション（LP hero の大型価格表示など）向けの hero サイズ（`--wp--preset--font-size--hero` = 40px 級・太字）で、比較表セルにも同じクラスがそのまま当たっていた。
   - 是正: `.wp-block-table.is-style-wt-compare td.wt-num` を本文サイズ（`--wp--preset--font-size--m` = 17px）へ戻し、強調はサイズではなく太字 + アクセント色のみで担うよう限定した（`.wt-num` 本体・LP 側の hero 用途は変更していない）。
   - 証跡: `results/table-compare-sp.jpg` / `results/table-compare-pc.jpg`。`verify.json.tableNumFontSize`（`bodyFs` 17px に対し `sizes` 全 6 セル 17px、`maxDeviation` 0 → `pass: true`）。
3. **ヘッダー PC がおかしい／画面幅に合っていない／CTA がど真ん中**
   - 原因（2 つ）: (a) `parts/header*.html` の外枠 `wideSize` が `1120px` 固定で、1440px 幅での撮影ではロゴ・ナビが内側 1120px に寄って両端に不自然な余白ができていた。(b) `parts/header-cta.html` の行はロゴ／検索／CTA ボタン／ナビの4要素を `justify-content:space-between` で等間隔に並べる構造で、PC では検索欄が非表示になるため実質3要素になり、CTA が中央付近に来ていた。
   - 是正: (a) 4 種のヘッダー全部で `wideSize` を `1440px`（新規トークン `--wt-header-max`、`theme.json` `settings.custom.header-max`）に上げ、画面に合わせて広がるようにした。(b) `header-cta.html` の CTA ボタンをナビ用の `.wt-header__nav`（`flex:1 1 auto; justify-content:flex-end`）の内側へ移し、行を `justify-content:left` に変更。結果「ロゴ左／CTA + ハンバーガー右」に統一し、CTA は右端に固定される（中央に来ない）。SP は「ロゴ左／CTA + ハンバーガー右」（`order:0` でソース順を保持するよう `theme.css` の旧 order ルールを整理）。
   - 証跡: `results/header-cta-pc.jpg`（CTA が右端）、`results/header-search-pc.jpg` / `results/header-nav-pc.jpg` / `results/header-announce-pc.jpg`（いずれも 1440px 幅いっぱいに広がる）。`verify.json.headerInnerWidth`（`rowWidth` 実測が `min(viewport, --wt-header-max) − gutter×2` の期待値と誤差 4px 以内 → `pass: true`）、`verify.json.headerCtaOffCenter`（CTA 中心が viewport 中央から `offsetRatio` 0.807 離れている（≥0.25 を要求）→ `pass: true`）。
4. **PC のコンテンツ幅・サイドカラム幅の最大／最小サイズの定義がない**
   - 原因: `theme.json` の `layout.contentSize`（680px）/ `wideSize`（1120px）以外に、幅の上限・下限を表す名前付きトークンがなく、ヘッダー最大幅・サイドカラム（目次 float・共有 float）幅も個別のマジックナンバー（`340px` / `240px` 等）で書かれていた。
   - 是正: `theme.json` `settings.custom` に `content-max`（680px、既定維持）/ `wide-max`（1120px、既定維持）/ `header-max`（1440px）/ `sidebar-w-min`（280px）/ `sidebar-w-max`（336px）/ `gutter-pc`（`clamp(24px,3vw,32px)`）を追加し、`theme.css` の `:root` に `--wt-content-max` / `--wt-wide-max` / `--wt-header-max` / `--wt-sidebar-w`（`clamp(280px,22vw,336px)`）として定義した。目次 float（`.wt-toc--float`）・共有 float（`body.wt-share-float .wt-share--float`）の位置計算をこれらの変数（`calc(50% - var(--wt-content-max)/2 - …)`）へ置き換え、マジックナンバーを解消した。
   - `content-max` / `wide-max` は段1〜4のキャプチャとの回帰比較を優先し既定値を据え置いた（680px / 1120px のまま。変える場合は別 PO 判断）。代わりに `?wt=width:<preset>` 軸（`narrow` / `default` / `wide`、Claude 案の3プリセット）を新設し、本文最大 640/680/760px・wide 最大 1040/1120/1240px・ヘッダー最大 1200/1440/1600px を切り替えて比較できるようにした（`body.wt-width-*` が `--wp--style--global--content-size` 等を上書き）。
   - 証跡: `results/width-narrow-{sp,pc}.jpg` / `results/width-default-{sp,pc}.jpg` / `results/width-wide-{sp,pc}.jpg`（6 枚、`CATALOG-INDEX.json` に width 軸として追記）。

是正後の実機再実行（`results/verify.json`）: `summary` 44 項目 **pass 44 / fail 0**（既存 40 + 新規 4: `tableCaptionSp` / `tableNumFontSize` / `headerInnerWidth` / `headerCtaOffCenter`）、総合 `pass: true`。再撮影は `scripts/shots-reaction1.mjs`（`--stage` 系と同じ merge 方式で `CATALOG-INDEX.json` を更新）で、記事全長 2・画面単位 20・ヘッダー PC 4・比較表 2・404（fullPage キャプチャでヘッダーを含むため wideSize 1120→1440 の影響を受ける）6 の既存 34 枚を差し替え、新規に幅プリセット `width-{narrow,default,wide}-{sp,pc}.jpg` 6 枚を追加した（既存 311 → 317）。段1〜4の他の画面（アイキャッチ・目次・囲み・カテゴリ・footer・LP 等はいずれも該当要素だけを切り出す `selector` 指定でヘッダーを含まない）は本是正の変更が及ばないため再撮影していない。

## 2.15 段5 — PO 反応 2〜5 回目（2026-09-05）の是正

同日中に続けて 4 回の反応を受けた。反応 1 回目と同じ PR（`research/2026-09-05-design-prototype-03-reaction1`）に合流している。反応 6 回目の追加要求は本ブランチの §2.17 で往復した。

### 反応 2 回目（原文）

「H2のチェックマークの見出しはリストと被るから別のに変更を。H3の見出しの番号は見出しテキストより大きくなるべきだな。あと数字が縦に積まれるのはいただけない。見出し系は少しバリエーション強化で。」（後日「番号サイズは見出しテキストより大きく」と訂正）

1. **h2 アイコン前置とリストの被り**: 既定アイコンが `check-circle`（丸背景 + 白チェック）で、`is-style-wt-check` リスト（同じ丸背景 + 白チェック）と見分けがつかなかった。既定を `star`（星）へ変更（`theme.css` `.is-style-wt-icon::before` の base SVG、`functions.php` の `data-wt-icon` 既定値表記）。証跡: `results/h2-icon-{sp,pc}.jpg`。
2. **h3 番号（`is-style-wt-num`）が数字ごとに縦積み**: 原因は比較表 caption と同種——親が `display:flex` のとき子は既定 `flex-shrink:1` のため、行が窮屈だと自身の内容幅より縮んで折り返す（SP 390 幅で長い見出しが 2 行になると、`::before` の「01」が「0」「1」に割れて表示されていた）。`flex:0 0 auto; white-space:nowrap` を追加して防止。証跡: `results/h3-num-sp.jpg`（1 行に復帰）。
3. **番号の大きさ**: `.9em`（本文より小さい）→ `1.5em`（**見出しテキスト自身**の 1.5 倍、太字化）。verify: `headingNumberPc`（PC 実測 `numFs` 30px / `textFs` 20px、`biggerThanText: true`）、`headingNumberSp`（縦積み検知: 見出し全体の高さが行高 ×2.6 以内）。
4. **見出しバリエーション強化**: h2 は既存 6 型（plain / 2tone / icon / bar / underline / band）に **+4**（`numbox` 番号ボックス・`barbg` 左太罫+背景淡色・`doubleline` 上下二重線・`label` 英字ラベル付き）、h3 は既存 3 型（bar-thin / dotted / num）に **+2**（`marker` 左マーカー・`underline-thin` 下線細）。型数そのものは目標にせず、台帳 `../2026-09-05-parts-pattern-taxonomy/README.md` §1 の観察型（h2/h3 行）から重複しない型を選定した（既存型は変更なし、`numbox`/`barbg`/`doubleline`/`label`/`marker`/`underline-thin` は追加のみ）。

| 見出し | 旧 | 新（追加分） |
|---|---|---|
| h2 | plain / 2tone / icon / bar / underline / band | + numbox / barbg / doubleline / label |
| h3 | bar-thin / dotted / num | + marker / underline-thin |

### 反応 3 回目（原文）

「見出しのアンダーバー系は文字からちょっと低い位置にありすぎるな。」

下線系（`is-style-wt-2tone` / `is-style-wt-underline` / `is-style-wt-dotted` / 新規 `is-style-wt-underline-thin`）の `padding-bottom` を固定 rem（.5〜.6rem = 8〜9.6px）から `0.3em`（文字サイズに追従）へ変更し、文字下端〜下線の距離を概ね 4〜8px に収めた。verify: `underlineGap`（4 型すべて実測 6〜7.2px、`pass: true`）。証跡: `results/h2-underline-{sp,pc}.jpg`、`results/h2-2tone-*`・`results/h3-dotted-*`（既存ファイルを再撮影、型自体は変更なし）。

### 反応 4 回目（原文）

「ボックスは悪くないがバリエーションがさみしいから追加で。」

既存 7 型（plain-border / tinted / band-title / tab-title / label-title / card-shadow / check-list 併用）は変更せず、**+5** 型を追加した。選定は台帳 `../2026-09-05-parts-pattern-taxonomy/README.md` §1「囲み」の観察型（引用 2%・タブ 3% 等）と Claude 案（Q&A・番号手順・warn の強弱2段。PO 指示は「バリエーション追加」までで、具体型は Claude 解釈）から。

| 型名 | 用途 | 台帳での観察 |
|---|---|---|
| `wt-quote`（引用風） | 短い体験談・レビュー引用の強調 | 「引用」2% |
| `wt-dashed`（破線） | 一時的な注記・撮影時点限定の断り書き | 台帳に破線の直接観察なし（Claude 案、既存 plain-border の枠種違いとして追加） |
| `wt-steps`（番号手順） | 手順・使い方の段階説明（`wt-timeline` の縦タイムラインより軽量な箱入り版） | 手順表示は「タブ」3% 系に近い運用（台帳に手順専用の観察行はなし、Claude 案） |
| `wt-qa`（Q&A） | 記事内の一問一答（`wp:details` の FAQ とは別に、本文中で強調したい Q&A に使う） | 台帳に Q&A 専用の観察行はなし（Claude 案） |
| `wt-warn-soft`（注意・弱） | 強い警告色（既存 `wt-c-warn` 赤系）ほどではない軽い注意書き | 「注意」系全体の強弱バリエーションとして Claude 案 |

証跡: `results/box-{quote,dashed,steps,qa,warn-soft}-{sp,pc}.jpg`。記事本文（`patterns/compare-article.php`）にも `wt-dashed`（比較表直後の一時的な注記）と `wt-quote`（F3 レビュー末尾の短い引用）を各 1 箇所配置し、文脈内での見え方を確認できるようにした（`results/article-full-{sp,pc}.jpg` に反映）。

### 反応 5 回目（原文）

「CTAはいい感じバリエーションを追加。あと記事本文に入っているからPRの記載は不要。」

1. **記事内 CTA +4**（既存4型 button-only / box-with-copy / banner-image / product-card-bundle は変更なし）: `cta-triple`（比較表直下の3社横並びボタン）・`cta-rank-featured`（ランキング1位強調カード）・`cta-price-tier`（価格 + 特典の2段ボタン）・`cta-textlink`（テキストリンク型「公式サイトで確認 →」）。証跡: `results/cta-{triple,rank,price-tier,textlink}-{sp,pc}.jpg`。
2. **PR 表記の重複抑止**: `pr` 軸に `auto`（本文先頭 200 字以内に「PR」「広告」「アフィリエイト」を検出したら自動挿入を抑止）を追加し既定を `auto` に変更（旧既定 `on` は常時挿入のまま axis 値として残す、`off` も変更なし）。実機確認: 記事 561（ダミー記事）の本文を一時的に「本記事はPRを含みます。…」へ書き換え、`.wt-pr`（`is-style-wt-pr`）が描画されないことを確認（`is-style-wt-pr` / `wt-pr__tag` の出現回数 0）。対照として記事 562（本文に PR 語なし）では通常どおり `.wt-pr` が 1 回描画されることを確認（出現回数 1）。確認後、記事 561 の本文は元のダミー文へ戻した（一時的な実機確認のみで、リポジトリ・fixture には残していない）。
   - **注記**: 「本文の語を機械判定して自動挿入を抑止してよいか」自体は要求 VOCAB-03「機械判定」の解釈に関わる論点であり、本是正はその判定方式（対象語・文字数・除外条件）を正式決定するものではない。PoC の是正として `auto` を実装したのみで、正本の仕様確定は別途 PO 判断とする。
   - ついでに: `.wt-pr__tag`（「PR」の1行タグ）が `.wt-pr` の `display:flex` 内で `flex-shrink:1`（既定）により縮んで P/R に縦積みになる同種の不具合を発見し、`flex:0 0 auto; white-space:nowrap` で修正（比較表 caption・h3 番号と同じ原因）。verify: `prTagNotStacked`（実測 `ratio`＝幅/高さ 1.68、横長であることを確認）。証跡: `results/pr-notice-one-line-{sp,pc}.jpg`。

是正後の実機再実行（`results/verify.json`）: `summary` 48 項目 **pass 48 / fail 0**（反応1回目までの44 + 新規4: `headingNumberPc` / `headingNumberSp` / `underlineGap` / `prTagNotStacked`）、総合 `pass: true`。再撮影は `scripts/shots-reaction2.mjs`（同じ merge 方式）で、記事全長・画面単位（本文が伸びた分、SP は 10→12 画面）・h2 6・h3 4・囲み新規5・CTA 新規4・PR 表記 1 の計 64 枚を差し替え / 追加した（既存 317 → 349）。

### 反応 7 回目（原文、一部のみ本 PR で対応）

「contrast-guard は面白いからいろんなパターンを追加できるか？写真自体を薄くしたり、透明系統、暖色系等などのカラーバリエーションの追加だな。ようは画像に見せ方を変えるような感じに。relatedはかなりしょぼい。これはデザイン品質の問題で再調査して品質向上をしてくれ。axis-depthこれはなんだ？」

3 点の要求のうち、(3) は本 PR で対応済み、(1)(2) は本ブランチの §2.17 で追加する。

- **(3) axis-depth ほか4軸の説明**: 実装変更ではなく PO の質問への回答。README §1 の軸一覧表に `motion` / `depth` / `density` / `detext` の1行説明を追記した（`depth` = 奥行き軸〈影・重なり・階層の強さ。0=フラット / 1=弱い影 / 2=強い影と浮き〉、他3軸も同様）。
- **(1) contrast-guard の見せ方バリエーション +5〜6型**（白フェード・暖色/寒色/ブランド色オーバーレイ・下部グラデーション・ぼかし・デュオトーン風）は §2.17 で追加する。
- **(2) related の品質再調査**（テーマA・テーマBの実物参照を含む）は §2.17 で追加する。

## 2.16 段5 — Astra レビュー（head 85ae634、重大1・改善5）の是正

指摘は根拠（ファイル・行）付きの6件すべてを是正した（PO 方針: 改善も直す）。

1. **重大: `pr:auto` の重複検出が先頭200字の単純部分一致で誤検出・見逃しがある**
   - 原因: `functions.php` の旧実装は `PR|広告|アフィリエイト` の話題語だけを先頭200字に対して部分一致させており、「広告のない製品を比較します」（話題語はあるが開示ではない）や「PROモデルを紹介します」（`PR` が `PRO` の部分文字列として誤一致）で誤って自動挿入を抑止し、逆に201字目以降にある実際の開示文は見逃していた。
   - 是正: `wt_content_has_pr_disclosure()` を新設し、(a) 話題語（PR/広告/アフィリエイト/プロモーション。`PR` は前後が英字でない独立した2文字のときだけ一致＝`PRO` 等を除外）と (b) 開示の述語（含む・含みます・掲載・表記）が**同一文**（。！？・改行区切り）に共起する場合だけを開示文とみなす。走査範囲は先頭の `<p>` を最大3つ・合計600字までとし、固定200字より広く201字目以降にも対応する（見出し内・4段落目以降は対象外＝既知の限界として明記）。
   - 検証: `scripts/verify.mjs` に `prAutoFixtures`（`--wpclidir <docker-compose project dir>` 指定時に実行。wp-cli で一時記事を作成→実機で `.wt-pr` の有無を確認→削除）を追加し、陽性3例（定型開示文／「PRを含みます」／「プロモーションを含みます」）・陰性3例（「広告のない製品を比較します」／「PROモデルを紹介します」／無関係文）・境界2例（開示文が4段落目・600字超／見出し内のみ）の計8件を検証。実機確認: 8/8 期待どおり（陽性→非表示、陰性・境界→表示）。`--wpclidir` 未指定時はスキップし `pass` を集計に含めない（環境依存の docker-compose 経路を必須にしないため）。
2. **改善: サイド目次 float の left が負値で画面左へ欠ける**
   - 原因: サイドカラム幅トークンを 240→280px（min）へ上げた際、1200px 幅では `left = 600 − 340 − 16 − 280 = −36px`（wide 側 `sidebar-w-max` 336px ではさらに `−76px`）になっていた。
   - 是正: `theme.css` の `.wt-toc--float` の `left` を `max(8px, calc(...))` に変更し、本文幅・サイド幅のどのプリセット組み合わせでも画面内の左端 8px を下限にした。
   - 検証: `verify.mjs` に `tocFloatLeft`（1200/1280/1440px × narrow/default/wide の9通りで `left ≥ 0` を検査）を追加。実測はすべて `left ≥ 8px`（1440px・narrow プリセットで最大 67px）。
3. **改善: SP 向け caption 修正が PC 比較表を崩す**
   - 原因: `caption{display:block}` を無条件（メディアクエリ外）に適用していたため、PC の通常 table でも caption が table 書式の外に出て、列見出しと価格行の間に紛れ込んでいた。
   - 是正: 当該ルールを SP カード表示専用の `@media (max-width:599px)` ブロック内へ移設し、PC では `caption-side:top` の既定挙動（表の上）に戻した。
   - 検証: `verify.mjs` に `tableCaptionPcPosition`（caption の y 座標 < thead の y 座標）を追加、実測 `capY 2507 < theadY 2531` で合格。証跡: `results/table-compare-pc.jpg` 再撮影（caption が表の最上部に復帰）。
4. **改善: 新規 verify の判定が主張を保証しない**
   - `headerCtaOffCenter`: 絶対値だと左寄せでも合格していたため、符号付き判定（`(cx − center) / center ≥ 0.25`）に変更。
   - `headingNumberPc/Sp`: 見出し全体の高さだけでは「番号自身は縦積みだが本文が短く全体高さは閾値内」を見逃すため、`::before` 自身の `white-space:nowrap` かつ `flex-shrink:0`（＝縮んで折り返すことが構造的に起きないという CSS 宣言そのもの）を検査対象にした。
   - `underlineGap`: `padding-bottom` の値ではなく、`Range.selectNodeContents()` で実テキスト（疑似要素を含まない）の bounding rect を取り、要素外枠下端 − border 幅 との実距離を測るよう変更。
   - PR タグ検査: `prTagNotStackedPc` に加え `prTagNotStackedSp` を追加し SP も検査対象にした。
5. **改善: 変更済み要素の画像証跡が古い**
   - `scripts/shots-reaction2.mjs` の撮影対象に `2tone`（h2 の下線間隔を変更したのに撮り直していなかった）と `toc-float`（サイド幅トークン変更の影響を受けるのに撮り直していなかった）を追加し、`h2-2tone-{sp,pc}.jpg` / `toc-float-pc.jpg` / `table-compare-{sp,pc}.jpg` を再撮影した。
6. **改善: Claude 案を「PO 提案」と記録していた**
   - `README.md`（囲み +5型の表・本文）と `theme/helix-wt/functions.php`（コメント）の「PO 提案」を「Claude 案（PO 指示は追加のみ）」へ修正した。イベント 0245 の PO 指示は「ボックスのバリエーション追加」までで、Q&A・番号手順・warn の強弱2段という具体型は Claude の解釈であり、PO 自身の提案ではない。

是正後の実機再実行（`results/verify.json`）: `summary` 52 項目 **pass 52 / fail 0**（反応2〜5回目までの48 + 新規4: `prTagNotStackedSp`〈`prTagNotStacked` から分離〉/ `tocFloatLeft` / `tableCaptionPcPosition` / `prAutoFixtures`）、総合 `pass: true`。再撮影は `scripts/shots-reaction2.mjs`（`2tone`/`toc-float` 追加）+ 個別実行で `h2-2tone-{sp,pc}.jpg` / `toc-float-pc.jpg` / `table-compare-{sp,pc}.jpg` の計5枚を差し替えた（件数は変更なし、349枚のまま）。

### Astra 再レビュー（head 4c19ce3、重大1・改善2）の是正

1. **重大: `wt_content_has_pr_disclosure()` が否定文を開示扱いにしていた**
   - 原因: 話題語（PR/広告/アフィリエイト/プロモーション）と開示述語（含む/含みます/掲載/表記）の共起だけを見ており、「広告のない製品を掲載しています。」（話題語+「掲載」で共起するが「ない」で否定）や「本記事には広告を含みません。」（「含みません」を「含み」の部分一致で誤って開示述語と判定）も開示文として検出し、`.wt-pr` の自動挿入を誤って抑止していた。
   - 是正: 同一文内に否定語（`ない`/`なし`/`ません`/`ありません`/`ございません`）があれば、その文は開示文とみなさないよう `$negation` 判定を追加した（文単位のヒューリスティックのため、無関係な否定表現が同じ文に混在する場合は見逃す方向に倒れる＝既知の限界として明記）。
   - 検証: `prAutoFixtures` に否定形の陰性フィクスチャを2件追加（`neg-4`「広告のない製品を掲載しています。」、`neg-5`「本記事には広告を含みません。」）。両方とも `.wt-pr` が通常どおり描画される（`autoInserted: true`）ことを実機（wp-cli 一時記事）で確認。既存の陽性3件（「本記事はPRを含みます」等）は維持され、引き続き非表示（`autoInserted: false`）。
2. **改善: `prAutoFixtures.pass===null`（`--wpclidir` 未指定によるスキップ）を `true` に変換して合格件数へ加算していた**
   - 是正: `scripts/verify.mjs` の集計を変更し、スキップ時は `checkList`（分母・分子とも）から除外、`summary.skipped` に配列として別掲するようにした（`--wpclidir` 指定時は `skipped: []`、未指定時は `skipped: ["prAutoFixtures"]` で `summary.pass`/`fail` の母数は51項目になる）。
3. **改善: 陽性フィクスチャが短文のみで201字目以降の検出を実証していなかった**
   - 是正: 長文の陽性フィクスチャを2件追加。`pos-4-long-250`（フィラー1段落＋開示文、開示文の開始位置は266字目（段落間改行・1始まりを含む））、`pos-5-long-400`（フィラー2段落＋開示文、開始位置は417字目）。いずれも3段落・600字の走査範囲内に収まる（39字の開示文を含めても合計 304字 / 455字で600字を超えない）。実機確認: 両方とも `.wt-pr` が非表示（`autoInserted: false`）となり、旧実装（200字固定長）では検出できなかった位置の開示文を検出できることを確認した。

是正後の実機再実行（`results/verify.json`、`--wpclidir` 指定で `prAutoFixtures` を実行）: `summary` 52 項目 **pass 52 / fail 0**（`skipped: []`）、`prAutoFixtures.results` は陽性5・陰性5・境界2 の計12件すべて期待どおり、総合 `pass: true`。テーマ・verify スクリプトの変更のみで描画への影響はないため画像の再撮影はしていない。

## 2.17 段5 — PO反応6・7の是正（2026-09-05、Claude案の追加型）

この節は L2 プロト往復の証跡であり、要求・設計の決定ではない。原文の語彙に対し、追加型はすべて Claude 案として記録する。既存型の削除・名称変更・クラス変更は行わず、ブログカードだけは表示名を変更し、互換の `linkcard` / `wt-linkcard` は残した。

### PO反応6（原文）

「テーブルパターンを増やせるか？pros-cons 1これはよくわからんがバリエーションを増やしてみてほしい。用途があまり見えてない。レビューバーもバリエーションを追加。linkcard 1正式にはブログカードって名称だろうな。これもバリエーションを追加。PRバリエーションは（テーマA・テーマB）を参考に修正。detext はまだ用途が見えないからバリエーション追加。中間報告。」

対応は次のとおり。

| 語彙 | 旧型 → 新型（追加分はすべてClaude案） | 用途の仮説 |
|---|---|---|
| 比較表 | 既存 `compare` / `compare-scroll` → `compare-striped` / `compare-evaluation` / `compare-price` / `compare-showdown` | 縞で一覧性を上げる、◎○△で評価差を先に読む、価格行を比較判断の起点にする、最終候補2製品を対決させる |
| pros-cons | 既存 `label-title + pros/cons` → `pros-contrast` / `pros-icons` / `pros-band` | 比較記事の各製品節末尾でメリット・デメリットを並べる装置。SPは縦積み |
| review-bar | 既存 `item-bars` → `review-stars` / `review-bars` / `review-score` | 星+数値、5項目の内訳、総合点+コメントで、読者が評価粒度を選べる |
| ブログカード | 旧表示名「リンクカード（内部）」 / 既存 `wt-linkcard` → 表示名「ブログカード（内部）」 / `blogcard-top` / `blogcard-band` / `blogcard-ogp` | 画像上+抜粋、画像なしのテキスト帯、外部OGP風の出典導線。CSS/HTMLクラス名とslugは互換維持 |
| PR表記 | 既存 `one-line` → `pr-intro` / `pr-inline` / `pr-double` / `pr-band` | 記事上部、見出し横、上下開示、アイコン帯。タグは `white-space:nowrap` でP/R分割を防止 |
| detext | 既存 `badge-list` / `icon-list` / `quote-mark` / `number` → `detext-takeaways` / `detext-metrics` / `detext-diagram` / `detext-quote` | 要点3つ、数字、図解の骨子、短い引用へ要約し、本文の文字密度を下げ長文比較記事の読了率を上げる |

### PO反応7（原文）

「contrast-guardは面白いからいろんなパターンを追加できるか？写真自体を薄くしたり、透明系統、暖色系等などのカラーバリエーションの追加だな。ようは画像に見せ方を変えるような感じに。relatedはかなりしょぼい。これはデザイン品質の問題で再調査して品質向上をしてくれ。axis-depthこれはなんだ？」

`axis-depth` は質問のみのため、`depth` の軸説明へ「奥行き軸（影・重なり・階層の強さ）」を残し、実装軸は増やしていない。contrast-guardは既存の自動輝度判定の上へ、白フェード、カラーオーバーレイ（暖色・寒色・ブランド色）、下部グラデーション、ぼかし+明度調整、デュオトーン風を追加した。概念5型・具体7型で、具体型ごとに dark / mid / light の画像をカタログへ置き、本文4.5:1・見出し3:1の近似式チェックを追加した。

relatedは台帳 `recapture-v2/aggregate-v2.md` の `tail.related.layout` を再確認し、PCのgrid-cards 36%・SPのthumb-list-1line 20%・featured-big+small・ranking-numbers・carousel等の分布を入力にした。併せて **テーマA / テーマBの実物（各ベンダー自身の公開記事面）を Playwright の read-only 参照で構造だけ観察し、型として言語化した**（固有名・画像・文言・ドメイン・スクリーンショットは保存していない）。観察できた事実は次のとおり: テーマBの記事末関連は 4 件のカード、PC 4 列 / SP 2 列、サムネ 16:9（object-fit: cover）、角丸・枠・影なし、タイトル 14px 太字で最大 3 行、メタは日付のみ（カテゴリチップなし）。テーマAはサンプルした記事面に関連記事欄がなく観察できなかった。PR 表記はどちらのベンダー記事面にも開示文がなく（ベンダー自身の媒体のため）、型の追加は台帳 §1 の PR チップ観察と Claude 案に基づく。再設計後はサムネ16:9固定、角丸、枠/影、タイトル2行クランプ、カテゴリチップ+日付（チップは当たり判定 44px を保ちつつ見た目 1.35rem のピルを `::before` で描く）、gap、hover、SPのサムネ20%前後の1行リストを共通基準にした。アイキャッチ未設定の投稿は、記事末尾の関連 Query Loop（queryId 901 / 902）の `post-featured-image` ブロックが空を返したときだけ同梱の無文字グラデーション（`assets/img/lum-mid.jpg`）を figure で返し、16:9 枠を崩さない（`render_block_core/post-featured-image`、Claude案。`get_the_post_thumbnail()` やカテゴリカードには作用しない）。既存4型（grid / list / rank / carousel）を再設計し、`featured`（featured-big+small）と `ranking-numbers` を追加した。`featured` は初回実装（3 列 + 大カード 2 行スパン）を実機撮影で見ると大カード内に空白が残り 6 件目が幅広で折り返したため、**左に大カード 1 + 右に横サムネの小リスト 5**（台帳の観察形）へ作り直した。SP は大カード 1 + 1 行リストに落ちる。

### 数値的根拠と検証状態

- コントラスト: `verify.mjs` の既存近似式を拡張し、追加7具体型 × dark/mid/light × 本文/見出し = **42判定**。本文は4.5:1、見出しは3:1を要求する。画像filter・gradient色の合成も含むが、実際の文字グリフ画素を直接測るものではないため**近似式である**。
- related: `verify.mjs` でSP/PC各6型について、同一行カード高さの差 **±2px以内**、タイトル `line-clamp:2`、サムネ比率 **16:9±1%** を検証する。
- 台帳値: `tail.related.layout` のPCはgrid-cards 36%、SPはthumb-list-1line 20%、featured-big+smallはPC 5% / SP 2%、SPのranking-numbers 6%。比較媒体行はPC grid-cards 40% / text-numbered 10%、SP thumb-list-1line 20%を示す。これらは観察値であり要求値ではない。
- 撮影・実機検証（origin/main c0a719a へ rebase 後、docker `agent-neo-wp` に配置し直して再実行）: `scripts/shots-reaction3.mjs`（新規）で追加型 34 × SP/PC = **68 枚** を撮影し `CATALOG-INDEX.json` へ追記（349 → 417 エントリ、既存エントリ変更なし）。`scripts/verify.mjs`（既存を拡張）は Astra 是正前 54 項目 pass 0 fail、是正後 **57 項目 pass 0 fail**（下記小節）（`--wpclidir` 指定で prAutoFixtures も実行、skip なし）。近似コントラスト 42 判定の最小値は **5.11:1**、related 12 ケース（6 型 × SP/PC）は高さ差 0px・2 行以内・16:9 すべて pass。
- 実機で見つけて直した 3 点（Codex 初回実装からの是正）: (a) 追加 7 型は mid / light 画像で白文字の近似比が 1.83〜4.49:1 に落ちていた → `data-wt-lum` ごとに強度を上げる型別ルールを重ねた（例: light 画像の暖色オーバーレイは alpha .90〜.94 のほぼ均一な濃い暖色になる。「自動で強くなる」のが guard の意図）。(b) 関連カードのカテゴリチップが 97×25px で 44px の当たり判定を割った（記事面タップ検査 2 件が fail）→ 見た目 1.35rem のピルを `::before` で描き、アンカー箱を 44px + 負マージンにした。(c) タイトル 2 行クランプの検査が computed `display` のキーワード（`-webkit-box`）を見ていたが、近年の Chromium は `flow-root` と報告する（CSS Overflow 4 の legacy line-clamp 扱い）→ 実描画の高さ ≤ 2 行で判定する実測式に変えた。
- 参照サイト観察は Codex sandbox では Chromium が起動できず未実施だったため、Claude が read-only で構造だけ観察して上記 related 段落に言語化した（撮影・保存なし）。

### Astra レビュー（PR #151 head 8716b62、重大 2・改善 3）の是正

| # | 指摘 | 是正 |
|---|---|---|
| 重大 1 | `post_thumbnail_html` フォールバックが post type と画像有無しか見ておらず、カード外の `get_the_post_thumbnail()`（カテゴリカードの `$attr` 等）にも作用する | `post_thumbnail_html` フィルタを撤去。`render_block_core/post-featured-image` で、記事末尾の関連 Query Loop（`parts/article-tail.html` の queryId 901 / 902）の子ブロックが空を返したときだけ figure を返す。core/post-template は子を context なしで再生成し queryId が届かないため、`render_block_context` で post-template の queryId を描画中だけ退避して子へ引き継ぐ。実機: 記事面の関連カードで 2 件（投稿 475 / 1）に出現、カテゴリ面・カタログでは 0 件 |
| 重大 2 | 型別 `!important` が `data-wt-lum` 属性なし（JS 無効・別オリジン・canvas 失敗）時の強い既定スクリムを上書きし、暖色型で約 2.96:1 になる | 順序を「属性なし = 強（light 相当）→ `[data-wt-lum="mid"]` で中 → `[data-wt-lum="dark"]` で弱」に組み替えた。`[data-wt-lum]`（値不問）を default と同じ強に置き、mid / dark だけを後段の属性付きセレクタで弱める。verify に JS 無効（属性なし）状態の 42 判定を PC / SP で追加し、`lum === null` であることも確認する |
| 改善 1 | 7 型が独立 block style で、`is-style-wt-scrim` なしではガードが働かない（カタログが手書きの二重 class に依存） | 型 class 単独で成立するよう `.wp-block-cover[class*="is-style-wt-contrast-"]` に擬似要素・背景 dim 無効化を持たせ、`contrast.js` の計測対象にも追加。カタログの二重 class を外し、verify で `singleClass`（`is-style-wt-scrim` を含まない）と `::before` の存在を全 cover で確認する |
| 改善 2 | 42 判定が PC のみ、撮影が mid 画像のみ | 判定を PC / SP × JS 有効 / 無効の 4 系統（各 42）に拡張。撮影は代表 2 型（overlay-warm / white-fade）に dark / light を追加（+8 枚） |
| 改善 3 | 近似式が filter の brightness しか読まない | 下記「近似式の省略」に明記 |

**近似式の省略（verify.mjs §6）**: 合成輝度は「画像の文字矩形平均輝度 × brightness 係数 × (1 − スクリム alpha) + スクリム色輝度 × alpha」で、`filter` からは `brightness()` だけを読む。`blur()`・`saturate()`・`grayscale()`・`contrast()` と `mix-blend-mode: multiply` は計算に入れていない。影響: `blur-bright` は blur で局所の明部が平均化される分だけ実比が近似より高くも低くもなり得る（最悪画素は `ratioWorstPixel` に別掲）。`duotone` は `grayscale(1) contrast(1.12)` で明部がさらに明るくなるため、白文字の実比は近似より**低い**可能性がある一方、`multiply` は暗く倒す方向に働く。いずれも近似であり、実際の文字グリフ画素は測っていない。判定は近似値に対する 4.5:1 / 3:1 であって、実測保証ではない。

実機再検証（Astra 是正後、style.css 0.3.4）: verify **57 項目 pass / 0 fail（contrast 4 系統 × 42 判定の最小値: PC 5.11 / SP 5.75 / PC no-JS 5.39 / SP no-JS 5.85、no-JS 系統は全 cover で data-wt-lum 属性なしを確認）**、撮影 **76 枚（68 + dark/light 8）、CATALOG-INDEX 349 → 425 エントリ（既存不変更）**。

## 3. 実測（`results/metrics.json`、調査スクリプト `../2026-09-04-site-survey/scripts/measure.mjs`）

| | 本文 | lh | h1 | h2 | h3 | ヘッダー高 | ボタン高 | 本文列幅 | 小タップ率 |
|---|---|---|---|---|---|---|---|---|---|
| 試作 03 記事 SP 390 | 17 | 1.6 | 22.53 | 18.52 | 17.27 | 61 | 48 | 358 | 0.04 |
| 試作 03 記事 PC 1280 | 17 | 1.7 | 28 | 24 | 20 | 61 | 48 | 680（`cont` 1232 は alignfull 外枠） | 0.16 |
| 試作 02 記事 SP（参考） | 15.09 | 1.8 | 22.53 | 20.35 | 18.18 | 61 | 46 | 350 | 0.72 |
| 調査 記事 中央値 SP（参考） | 16 | 1.8 | 22.2 | 20 | 18 | 64 | 40 | 351 | – |

小タップ率（`smallTapRate`）は調査スクリプトの定義（44px 未満の a/button の割合。本文中のインラインリンクを含む）。

## 4. 検証結果（`results/verify.json`、`scripts/verify.mjs`。段5（PO 反応1〜7回目 + Astra レビュー是正）まで実機実行済み）

下表は段1/2の検査項目（段4の再実行後の値。段3で共有 float の復元・著者ボックス・末尾 slot が加わったため、記事のタップ総数と no-JS の本文字数が段1時点から変わっている）。段3で追加した 14 項目（categoryTapSp / categoryTapPc / footerTapSp / footerTapPc / authorSnsTapSp / authorSnsTapPc / footerContrast / footerNoJs / loadMoreNoJs / loadMoreJs / categoryPagination / categoryHeroContrast / fixedOverlapSp / fixedOverlapPc）の結果は §2.12「段3のguardと証跡」に記載。段4で追加した 14 項目（lpTapSp / lpTapPc / lpContrast / lpFullbleedContrast / lpFormNoJs / lpAnchorNav / lpSections / lpFixedOverlapSp / lpFixedOverlapPc / lpReducedMotion / lpLcpHero / lpFooterFaceDefault / lpVisibleAnchors / lpFaceScopedTotop）の結果は §2.13「段4の検証結果」に記載。段5で追加した4項目（tableCaptionSp / tableNumFontSize / headerInnerWidth / headerCtaOffCenter）は §2.14、続く4項目（headingNumberPc / headingNumberSp / underlineGap / prTagNotStacked→後述の理由で `prTagNotStackedPc`/`prTagNotStackedSp` に分離）は §2.15、Astra レビュー是正で追加した4項目（`prTagNotStackedSp`・`tocFloatLeft`・`tableCaptionPcPosition`・`prAutoFixtures`）は §2.16 に記載。保存済みの最新 `results/verify.json` は `summary` 52 項目 **pass 52 / fail 0**、総合 `pass: true`（`prAutoFixtures` は `--wpclidir` 指定時に wp-cli で一時記事を作成し実機確認する検査で、本 README の実行では指定して実行済み）。段1/2の項目も `contrast` / `contrastGuard` / `headline` / タップ4画面に合否を持たせ、総合 `pass` は段5までの全項目の AND。

| 項目 | 結果 |
|---|---|
| JS 無効描画（SP、`motion:on,header:announce`） | `html.wt-js` なし、お知らせ帯 存在・可視、目次 表示・開・リンク 12、`.wt-reveal` 10 件すべて opacity 1、商品カード・比較表・関連カード 7 件 表示、本文 3,048 字表示。`pass: true` |
| reduced-motion（SP、`motion:on`） | `.wt-reveal` 10/10 が初期表示、ヘッダー・ボタンの transition `none`、count-up は最終値「1,284」。比較: 通常設定では読み込み直後 10/10 が非表示 → スクロールで出現 |
| コントラスト（PC、算出） | CTA ボタン 白/#c2410c **5.18:1**、本文段落内リンク（`.wp-block-post-content > p > a`、#1d4ed8/白）6.7:1、補助文字 mute 5.69:1、PR 表記 5.69:1、目次リンク 6.19:1、帯タイトル 6.7:1、ランクバッジ 5.18:1、アウトラインボタン 6.7:1、価格単位 5.69:1、リンクカードラベル 5.69:1、カード日付 5.69:1。11 項目すべて 4.5:1 以上 |
| 自動コントラスト guard（実描画、§2.9 の算式、文字は実色 #fff） | dark 画像（`dark`、文字位置の画像 L 0.025 / α .371）本文 15.98:1・h3 16.74:1 / mid（`mid`、L 0.246 / α .66）本文 7.86:1・h3 6.17:1 / light（`light`、L 0.882 / α .933）本文 9.62:1・h3 5.48:1。スクリムなしでは mid 3.55、light 1.13 で不合格。記事 hero アイキャッチ（生成写真、`mid`）h1 11.21:1（最大輝度画素 3.38、要 3:1）、パンくず「ホーム」10.39:1、カテゴリ 7.38:1、著者名 17.61:1、日付 11.95:1、更新日 10.74:1（いずれも実色 rgb(255,255,255)）。12 判定すべて合格 |
| 比較表（記事 554 の描画 HTML） | `<thead>` 無傷、`th` 4 / `scope="col"` 4、行 8 / 行見出し `tbody th[scope=row]` 8（`td[scope=row]` 0）、`data-th` 24（データセル td のみ。行見出し th の `data-th` 0）、`tfoot` 行は変換対象外（本記事に tfoot なし、変換件数 0）、`caption` あり。`pass: true` |
| 404 | 素の URL・3 変種すべて **HTTP 404**、robots meta 全件 `["max-image-preview:large","noindex"]` → noindex あり、謝意・原因・検索（ボタン付き）・人気 3 件・カテゴリ・ホーム・CV slot 3 枠・検索語提案 4 件 |
| タップ領域監査（SP） | 除外は p / li 直下の display:inline リンクだけ（記事 2・カタログ 1・404 0）。core の skip-link（1×1、フォーカス時のみ表示）は SR 用として別掲。**44px（P05）**: 記事 68/68、帯 + カルーセル + float 共有 72/72、404 45/45、カタログ 31/31。**24px（WCAG 2.5.8）**: 同じく全件。サイト名・パンくず・カテゴリターム・カードタイトルは inline-flex + 負マージンで 44px 化（行送りは据え置き） |
| 見出し 1 行 | h2 18.5px、18 字、358px、1 行（19.3 字まで） |
| 目次しきい値 | h2 5 / h3 7 → 目次 5 / 7、`scroll-margin-top` 76px、SP は JS で閉 |
| 動作（別スクリプト） | お知らせ帯: 閉 → 再読込後も非表示（localStorage キー 1 件）。ヘッダー: 下スクロールで隠れ、上で再表示、背景不透明。目次: `#h-3` 到達で「3 製品の比較表」が current。カルーセル前後ボタン 2 組。比較表 SP: 行が block（カード）。横スクロールなし |

修正した点（初回検証・Astra レビューで判明）: PR 表記の重複判定が `is-style-wt-product` に誤一致して未挿入だった（`class="wt-pr "` 判定へ）、次に読むカードが関連グリッドの列幅を継承していた、目次リンク・FAQ summary・フッターナビが 44px 未満だった。レビュー指摘: `<th` の正規表現が `<thead>` に一致し最後の列見出しに scope が付かなかった（thead 内限定・タグ境界限定・件数制限なしへ）、post meta 4 件に sanitize_callback（allowlist 外は既定値）と REST schema enum を追加、監査の除外条件を狭め 44 / 24 を分離、guard の検査を固定 alpha から実描画へ（その結果 light 画像が未達と判明 → スクリム強化）、robots meta 全件判定、JS 無効テストで帯を検査、本文リンクのセレクタを段落内に限定（記事本文に内部リンクを 1 つ追加）。再レビュー（head cc9fa8b）: post meta の空文字はサイト設定継承として保持、比較表の先頭列を `<th scope="row">` に（CSS・検査も th 前提へ）、輝度別スクリムのセレクタを登録 block style クラス `.is-style-wt-scrim` に統一、guard は文字要素ごとの実色で判定し hero 上のメタを白に上書き、既存 scope 検出を空白・大文字対応に。

## 5. 描画証跡

`results/` には既存151枚（JPEG q75、長辺 1600 以下。`CATALOG-INDEX.json` に {file, face, part, variant, dev}）を保持する。内訳: 記事全長 SP/PC 2 + 画面単位 20、ヘッダー 8（PC 4・SP 3・帯）、アイキャッチ 10、目次 9、h2 12、h3 6、囲み 16、CTA 8、比較表 2、メリデメ 2、評価バー 2、リンクカード 2、PR 2、de-text 部品 2、関連 8、共有 4、4 軸 on/off 24（depth 8・density 4・detext 8・motion 4）、コントラスト guard 6、404 6（計 151）。段3で 112 枚を追加（カテゴリ面 18 variant × SP/PC = 36、footer 27 variant × SP/PC = 54、記事末尾 11 variant × SP/PC = 22。`category-*`, `footer-*`, `tail-*`）、計 263。段4で 48 枚を追加（`lp-*` 42 + LP 面 `footer-layout` 6）、計 311。段5（PO 反応1回目、`scripts/shots-reaction1.mjs`）で記事全長 2・画面単位 20・ヘッダー PC 4・比較表 2・404 6 の既存 34 枚を差し替え、新規に幅プリセット `width-{narrow,default,wide}-{sp,pc}.jpg` 6 枚を追加し、計 317。反応2〜5回目（`scripts/shots-reaction2.mjs`）で記事全長 2・画面単位 20（SP は本文が伸びて 10→12 枚になり、うち 11・12 は新規）・h2 既存2型（icon/underline）4・h3 既存2型（dotted/num）4・PR 表記 1 の既存 32 枚を差し替え、新規に画面単位 SP 11・12 の 2 枚と h2 +4 型・h3 +2 型・囲み +5 型・CTA +4 型（各 SP/PC）計 30 枚を追加し（新規計 32）、計 349。全長画像は縮小で判読しにくいため `article-screen-NN-*.jpg` を併用する。

## 6. 手順（再現）

1. `docker cp theme/helix-wt/. agent-neo-wp:/var/www/html/wp-content/themes/helix-wt/`（helix-wt は試作 02 から有効のまま）
2. 記事: `wp post create --post_type=post --post_content='<!-- wp:pattern {"slug":"helix-wt/compare-article"} /-->' ...`、アイキャッチは `wp media import <theme>/assets/img/media-pickup-1.jpg` → `_thumbnail_id`。関連一覧用に短い架空記事 5 件 + カタログ固定ページ（`helix-wt/catalog-03`、テンプレ page-canvas）
3. 段3データ: WP-CLIで親カテゴリ `topic-index` を作成し、子カテゴリ `topic-one` / `topic-two` / `topic-three` を `--parent=<親term_id>` で作成する。各子へダミー記事を5件ずつ、親へ横断記事を2件以上 `wp post create --post_type=post --post_status=publish --post_category=<term_id> --post_content='<!-- wp:paragraph --><p>ダミー本文です。</p><!-- /wp:paragraph -->' --post_title='読みもの <連番>'` で登録し、合計14件以上にする。カテゴリ説明は `wp term update category <term_id> --description='カテゴリの説明文です。'` で設定する。固有名・実在URL・第三者ロゴは使わない。
4. 変種の確認: 既存軸は `?wt=header:cta,sp:left,eyecatch:hero,toc:float,related:rank,share:float,motion:on,depth:2,density:airy,detext:on,nf:suggest`、段3は `?wt=cat_header:hero,cat_children:steps,cat_list:featured-grid,cat_pagination:load-more,cat_ranking:sidebar,cat_minihome:on,footer_layout:columns-3,footer_above:banner-row,footer_legal:copyright-only,footer_extra:sns-sites,footer_totop:button,tail_order:cta-related-author-share,tail_share:icons-row,tail_author:avatar-bio-sns,tail_prevnext:thumb` のように付ける。サイト既定は `wp theme mod set wt_<key> <value>`、記事単位は `wp post meta set <ID> wt_eyecatch|wt_toc|wt_pr|wt_share <value>`
5. 段4のテーマ配置（リポ root から）: `docker cp docs/research/2026-09-05-design-prototype-03/theme/helix-wt/. agent-neo-wp:/var/www/html/wp-content/themes/helix-wt/`。LP ページが未作成なら `docker compose run --rm -T wpcli post create --post_type=page --post_status=publish --post_name=lp --post_title='案内ページ' --page_template=page-lp` で 1 枚だけ作成する。撮影は既存を上書きせず、`NODE_PATH=<playwright の node_modules> node scripts/shots.mjs --stage4 true --base <site> --out results`。検証は `NODE_PATH=<playwright の node_modules> node scripts/verify.mjs --base <site> --out results/verify.json`、計測は `node ../2026-09-04-site-survey/scripts/measure.mjs --url <記事 URL> --out <dir> --playwright <playwright パス>`
6. 輝度テスト画像 3 枚（`assets/img/lum-{dark,mid,light}.jpg`）は PHP GD で生成した無文字のグラデーション（手順はコンテナ内 eval、リポには成果物のみ）
7. 段5（PO 反応1回目）のテーマ配置は手順5と同じ `docker cp`。再撮影は `NODE_PATH=<playwright の node_modules> node scripts/shots-reaction1.mjs --base <site> --out results`（`--stage3`/`--stage4` と同じ merge 方式で `CATALOG-INDEX.json` を更新）、検証は手順5と同じ `scripts/verify.mjs`
8. 段5（PO 反応2〜5回目）は同じ `docker cp` で配置。新規パターン（`patterns/cta-*.php`）追加時は WP core の block pattern スキャンがディレクトリの mtime を見て結果をサイトトランジェント（`wp_theme_files_patterns-*`）にキャッシュするため、**ファイル追加だけでは反映されないことがある**（`docker cp` は個々のファイルの mtime は更新するが、ディレクトリ自体の mtime やキャッシュキーに使う `style.css` の `Version` が変わらないと古いキャッシュが残る）。`style.css` の `Version` を上げる（本 PR は 0.3.0→0.3.1）か `wp transient delete --all` を実行してから確認すること。再撮影は `NODE_PATH=<playwright の node_modules> node scripts/shots-reaction2.mjs --base <site> --out results`（同じ merge 方式）
9. `pr:auto` の重複検出フィクスチャ検査（`verify.mjs` の `prAutoFixtures`）は wp-cli で一時記事を作成・削除するため、docker compose の wpcli サービスを持つ project dir を `--wpclidir <dir>` で渡す（例: `node verify.mjs --base <site> --out results/verify.json --wpclidir /path/to/docker-compose-project`）。未指定時はこの1項目だけスキップし、集計 `pass` には影響しない。
10. PO反応6・7の追加型は `docker cp docs/research/2026-09-05-design-prototype-03/theme/helix-wt/. agent-neo-wp:/var/www/html/wp-content/themes/helix-wt/` 後、`NODE_PATH=$HOME/dev/poc-wp/node_modules node scripts/shots-reaction3.mjs --base http://localhost:8086 --out results` で追加撮影する。`CATALOG-INDEX.json` は既存ファイルを保持して未登録エントリだけ末尾へ追加する。検証は `NODE_PATH=$HOME/dev/poc-wp/node_modules node scripts/verify.mjs --base http://localhost:8086 --out results/verify.json`。

## 7. 終了時状態（意図的に残置）

- 反応 6・7 是正時（本ブランチ）: 過去の `verify.mjs` 実行が残した一時記事 8 件（`fixture-*`、ID 604–611、`prAutoFixtures` の後片付け失敗分）を **削除せず下書きへ変更**した（関連一覧の最新 6 件を占めていたため。削除は PO 判断事項として未実施）。投稿 475 / 1 はアイキャッチ未設定のままで、関連カードは既定画像フォールバックで描画される。
- テーマ `helix-wt`（試作 03 版）が有効。投稿: 記事 **554**（`/standing-desk-compare/`）、関連用 555–559、カタログ固定ページ 560（`/catalog-03/`）、添付 548–553、カテゴリ term 7（desk）。段3: カテゴリ term 8 `topic-index`（説明あり）、子 9 `topic-one` / 10 `topic-two` / 11 `topic-three`（各 5 件）、ダミー記事 561–577（17 件、親直下 2 件、アイキャッチは 548–553 を再利用、日付 2026-08-01〜17）。554–559 と 561–577 の `post_author` を 1 に設定（WP-CLI 作成時は 0 で、著者ボックスが空になる）。試作 02 の 518 / 519 / 520 / 533 と `wp_global_styles` 525 はそのまま。519 にアイキャッチ（添付 551）を付与。
- サイト名・キャッチフレーズ・ユーザー 1 の表示名と紹介文を架空値へ変更（試作 02 時の値から）。
- 撤去: `wp post delete 554 555 556 557 558 559 560 --force`、`wp post delete 548 549 550 551 552 553 --force`、`wp term delete category 7`、段3: `wp post delete $(seq 561 577) --force`、`wp term delete category 9 10 11 8`、試作 02 の README §5 の手順。

## 8. 未実装・次タスク

- 人気記事の集計方式（404 の人気・関連の「人気」は新着順で代替、#110）。
- 選択 UI（サイトエディターの variation / 記事サイドバー）。現状は theme_mod・post meta・プレビュー引数。
- 目次の float は 1200px 以上のみ（本文 680 + レール 240 + 余白）。1024–1199px では box にフォールバック。
- コントラスト guard の canvas 標本化はクライアント側。実装ではアップロード時（サーバ）に輝度を事前計算して attachment meta に持ち、`data-wt-lum` をサーバで出す（R75「輝度事前計算」）。
- PR 表記の自動挿入は投稿タイプ post 全件（比較媒体前提）。実装ではカテゴリ / 記事 meta で対象を絞る。
- 4 軸のうち depth-2 の CTA 立体化と `.is-style-wt-raised` の重複、`.wt-c-*` 色 modifier の block style 化（現状は追加 CSS class）は設計で整理。
- 44px 監査: カード全面クリックの実効領域を数える監査ロジック（`a::after` の矩形を含める）。
- PO反応6・7の追加型（比較表 / pros-cons / review-bar / ブログカード / PR / detext / contrast-guard / related）は §2.17 に実装・実機検証（verify 57/57、撮影 76 枚）を記録した。次段は PO の反応待ち。related の既定画像フォールバックは投稿カード限定で、カテゴリ面カードへの適用可否は次の反応で判断する。

## 9. 公開安全

サイト名・URL・実在の製品名・ブランド名なし（製品・価格・数値・引用は架空）。参照テーマは テーマA / テーマB 表記、第三者プラグイン名・SNS 名なし（共有は Web Share API + リンクコピー）。画像は生成画像と GD 生成のグラデーション（文字・ロゴなし）。パスはリポ相対。スクリプトの既定 URL はローカル docker のもの。

## 10. 段5 — PO 反応 8 回目（WT-EVT-0249）の是正

### PO 原文

> 「density:compact これがバクにしか見えないんだけどなに？axis-detextこれもよくわらんな。axis-motionこれはなに見た目わからん。レビューするうえで説明入れといて。後半になればなるほど何を指しているかわかんね～。」

### 原因の分析（Claude 案）

- `density` は旧撮影が記事冒頭の切り出しで、余白差が最も出る本文中盤の「見出し → 段落 → リスト → 次の見出し」を同じ範囲で比較していなかった。また、旧画像の `off/on` という命名は、実装上の `airy / normal / compact` という 3 値を表していない。旧 PR タグの縦積みも同じ画面に写り、軸の差ではなく不具合のように見えた可能性がある。
- `detext` は、現行記事の h2 が `is-style-wt-2tone`、ol が `is-style-wt-badge-list`、blockquote が `is-style-wt-quote-mark` である。CSS は h2・ol・quote について `:not([class*="is-style-wt-"])` を条件にしているため、これらの本文コンテンツには `detext:on` の追加差分が出ない。除外条件のない `.wt-toc__list>li::before` の目次丸番号バッジだけが、現行記事で実際に見える差分である。旧撮影が「変化しない本文」を説明なしに見せていたことが、軸の意味を分かりにくくした可能性が高い（Claude 案）。
- `motion` は IntersectionObserver が `.wt-reveal` に `is-in` を付け、`opacity:0 / translateY(12px)` から 375ms で表示する軸である。静止画 1 枚だけでは、薄く浮いた途中フレームなのか、表示完了後なのかを区別できず、「見た目がわからない」状態になった可能性がある（Claude 案）。

### 対応

新規スクリプト `scripts/shots-reaction4.mjs` では、既存画像・既存 `CATALOG-INDEX.json` エントリを変更せず、以下の比較を新しいファイル名で追加する設計にした。

- `density` は `airy / normal / compact` の 3 値を SP/PC それぞれで同じ `#h-4` 付近から viewport 撮影する。各ページで h2 の `margin-top`、h2 後の段落の `margin-bottom`、連続する 2 段落間の実測距離を `page.evaluate` で取得し、`results/density-measure.json` に保存する。
- `detext` は記事冒頭の `.wt-toc` を off/on で撮影する。SP では通常の box が閉じるため、番号バッジを読めるよう撮影時だけ details を開く。併せて `#h-4` 付近の本文を off/on で撮影し、h2/list/quote に差分が出ないことを比較対象として明記する。
- `motion` は同じ記事末の先頭 `.wt-rcard` が画面下から入り始める位置へ移動し、ページを毎回開き直して `f0=0ms`、`f1=200ms`、`f2=900ms` を撮影する。別途 `motion:off` を 900ms 後に撮影し、途中フレームと完了後・無効状態を切り分ける。

実機で `shots-reaction4.mjs` を実行し、18 枚の追加画像と `results/density-measure.json` を生成した。density の計測は SP/PC の両 viewport で同じ値になったため、下表には値ごとの共通値を記載する。

### density 実測値

| 値 | h2 上マージン実測 px | h2 後の段落 margin-bottom 実測 px | 連続 2 段落間の実測距離 px |
|---|---:|---:|---:|
| airy | 52 | 4 | 21.59 |
| normal | 40 | 4 | 16 |
| compact | 32 | 4 | 12 |

`h2 後の段落 margin-bottom` は `#h-4` 直後に実際に置かれた段落の computed style、`連続 2 段落間の実測距離` は記事本文内で隣接する 2 段落の矩形距離である。参考として CSS の指定は `airy` が h2 `3.25rem`・段落 `1.35rem`、`compact` が h2 `2rem`・段落 `.75rem`、`normal` は既定値であり、実測値はこの指定が反映された結果である。

### 追加画像一覧（撮影対象）

期待する追加枚数は合計 18 枚で、内訳は density 6 枚、detext-toc 4 枚、detext-body 4 枚、motion 4 枚である。

- density: `axis-density-airy-sp.jpg`、`axis-density-airy-pc.jpg`、`axis-density-normal-sp.jpg`、`axis-density-normal-pc.jpg`、`axis-density-compact-sp.jpg`、`axis-density-compact-pc.jpg`
- detext-toc: `axis-detext-off-toc-sp.jpg`、`axis-detext-off-toc-pc.jpg`、`axis-detext-on-toc-sp.jpg`、`axis-detext-on-toc-pc.jpg`
- detext-body: `axis-detext-off-body-sp.jpg`、`axis-detext-off-body-pc.jpg`、`axis-detext-on-body-sp.jpg`、`axis-detext-on-body-pc.jpg`
- motion: `axis-motion-on-f0-sp.jpg`、`axis-motion-on-f1-sp.jpg`、`axis-motion-on-f2-sp.jpg`、`axis-motion-off-f2-sp.jpg`

上記 18 ファイルを生成済みで、各ファイルは非空の JPEG として確認した。`CATALOG-INDEX.json` には既存 349 件を変更せず、新規 18 件を追記している。計測データは `results/density-measure.json` に保存した。

### 用語集

`CATALOG-GLOSSARY.json` は本ディレクトリ直下に置く **Claude 案の用語集**である。既存カタログの全 `part` / `variant` と `face` を、日本語の `label`・`desc`、型ごとの見え方、軸ごとの `changes`・`where`・`how_to_tell` に整理している。追加撮影で使う density、detext body、motion frame の値も、同じ形式で説明している。
