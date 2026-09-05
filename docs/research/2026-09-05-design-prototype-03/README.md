# デザイン試作 03 — 比較・アフィリエイト媒体の記事面・404（選べる型として実装）

- 実施日: 2026-09-05。計画は `PLAN.md`（段 1 = 記事面 + 404 が本書の範囲）
- 位置づけ: PoC 証跡。要求の確定・設計の決定ではない。PO 決定 2026-09-05「フロント先行」（試作 02 は殺風景・変化が少ないという PO 判定を受け、1 サイトパターン = 比較・アフィリエイト媒体に絞り、記事面と 404 を先に作る）に基づく。カテゴリ ミニ HOME・フッター・LP は再撮影の完了待ちで本タスクの範囲外。
- 方針: 見た目の差はすべて**テーマの実コード**（theme.json / block style / pattern / template part / CSS / 小さな JS）で、選択軸は body class・block style・pattern として実際に切り替えられる（DOM 注入のモックではない）。
- 入力: 型の棚卸し `../2026-09-05-parts-pattern-taxonomy/by-purpose.md` §1「比較・アフィリエイト媒体」と §2（比較媒体・全用途共通）、語彙 `../2026-09-05-parts-pattern-taxonomy/PARTS-VOCAB.md`、既定値 `../2026-09-05-cro-usability-evidence/README.md` §2 P01–P33、パーツ一覧 `../2026-09-04-parts-inventory/README.md`（#98 / #107 / #110 / #122 / #126 / #127 / #130 / #132 / #134）。
- テーマ本体（`themes/`）・`plugins/` は未編集。`theme/helix-wt/` は試作 02 の複製に上書きした試作テーマ。

## 1. 選択軸の仕組み

選択は `functions.php` の `wt_axes()` に列挙した 12 軸。解決順は **プレビュー引数 `?wt=key:value,...`（PoC 用）→ 記事の post meta `wt_<key>`（eyecatch / toc / pr / share のみ、「この記事では目次を隠す」等）→ `theme_mod` `wt_<key>`（サイト既定）→ 既定値**。結果は `body.wt-<key>-<value>` の class になり、CSS が切り替える。ヘッダーだけは `render_block_data` で template part の slug を `header-<variant>` に差し替える。実装時はプレビュー引数を管理者限定にし、サイトエディター / 記事サイドバーの選択 UI を付ける。

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
| 比較表 | `.wp-block-table.is-style-wt-compare`（PC: 先頭列 sticky + 横スクロール、SP: 行ごとのカード縦積み。render_block で `data-th` / `scope` を付与、`caption` あり、数値は `.wt-num` 右揃え、◎○△ は凡例で文字代替） | table: compare-sticky-first-col | R39–R41、P24 |
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
- CSS: `data-wt-lum` ごとにスクリム（黒の linear-gradient、to top）の不透明度を切替。下端 / 55–70% / 上端の alpha は dark .55/.40/0、mid .80/.70/.15、light .95/.93/.70、未計測（既定）.90/.84/.30。**同一オリジンでない・読めない画像は属性を付けず既定（強）に倒す**。初回計測で light 画像の h3 位置が 3.49:1 だったため、文字位置（下部 0〜60%）の最小 alpha を引き上げた（§4）。
- ゲートの検査方法（`scripts/verify.mjs` §6、実描画から算出）: 文字要素の boundingRect を取り、(a) スクリム擬似要素の `background-image` の gradient stop を解析して文字矩形の上端・下端位置の alpha を線形補間し小さい方を採る、(b) 画像を canvas に描き object-fit: cover の写像で文字矩形に当たる画素の平均輝度 L と最大輝度を測る、(c) 合成輝度 `Lc = L × (1 − α)`（黒スクリム）と白文字の比 `1.05 / (Lc + 0.05)` を本文 4.5:1・大文字（≥18.67px bold / ≥24px）3:1 で判定。gradient の補間は線形近似で、ブラウザの実際の補間（sRGB / premultiplied）と僅差があるため**概算**。最大輝度画素での比も `ratioWorstPixel` として併記。

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

## 3. 実測（`results/metrics.json`、調査スクリプト `../2026-09-04-site-survey/scripts/measure.mjs`）

| | 本文 | lh | h1 | h2 | h3 | ヘッダー高 | ボタン高 | 本文列幅 | 小タップ率 |
|---|---|---|---|---|---|---|---|---|---|
| 試作 03 記事 SP 390 | 17 | 1.6 | 22.53 | 18.52 | 17.27 | 61 | 48 | 358 | 0.04 |
| 試作 03 記事 PC 1280 | 17 | 1.7 | 28 | 24 | 20 | 61 | 48 | 680（`cont` 1232 は alignfull 外枠） | 0.16 |
| 試作 02 記事 SP（参考） | 15.09 | 1.8 | 22.53 | 20.35 | 18.18 | 61 | 46 | 350 | 0.72 |
| 調査 記事 中央値 SP（参考） | 16 | 1.8 | 22.2 | 20 | 18 | 64 | 40 | 351 | – |

小タップ率（`smallTapRate`）は調査スクリプトの定義（44px 未満の a/button の割合。本文中のインラインリンクを含む）。

## 4. 検証結果（`results/verify.json`、`scripts/verify.mjs`。Astra レビュー PR #139 の指摘 8 件を反映して再実行）

| 項目 | 結果 |
|---|---|
| JS 無効描画（SP、`motion:on,header:announce`） | `html.wt-js` なし、お知らせ帯 存在・可視、目次 表示・開・リンク 12、`.wt-reveal` 10 件すべて opacity 1、商品カード・比較表・関連カード 7 件 表示、本文 3,052 字表示。`pass: true` |
| reduced-motion（SP、`motion:on`） | `.wt-reveal` 10/10 が初期表示、ヘッダー・ボタンの transition `none`、count-up は最終値「1,284」。比較: 通常設定では読み込み直後 10/10 が非表示 → スクロールで出現 |
| コントラスト（PC、算出） | CTA ボタン 白/#c2410c **5.18:1**、本文段落内リンク（`.wp-block-post-content > p > a`、#1d4ed8/白）6.7:1、補助文字 mute 5.69:1、PR 表記 5.69:1、目次リンク 6.19:1、帯タイトル 6.7:1、ランクバッジ 5.18:1、アウトラインボタン 6.7:1、価格単位 5.69:1、リンクカードラベル 5.69:1、カード日付 5.69:1。11 項目すべて 4.5:1 以上 |
| 自動コントラスト guard（実描画、§2.9 の算式） | dark 画像（`dark`、文字位置の画像 L 0.025 / α .371）本文 15.98:1・h3 16.74:1 / mid（`mid`、L 0.246 / α .66）本文 7.86:1・h3 6.17:1 / light（`light`、L 0.882 / α .933）本文 9.62:1・h3 5.48:1。スクリムなしでは mid 3.55、light 1.13 で不合格。記事 hero アイキャッチ（生成写真、`mid`）h1 11.21:1（最大輝度画素 3.38、要 3:1）、meta 10.94:1。8 判定すべて合格 |
| 比較表（記事 554 の描画 HTML） | `<thead>` 無傷、`th` 4 / `scope="col"` 4、行見出し `td[scope=row]` 8、`data-th` 32、`caption` あり。`pass: true` |
| 404 | 素の URL・3 変種すべて **HTTP 404**、robots meta 全件 `["max-image-preview:large","noindex"]` → noindex あり、謝意・原因・検索（ボタン付き）・人気 3 件・カテゴリ・ホーム・CV slot 3 枠・検索語提案 4 件 |
| タップ領域監査（SP） | 除外は p / li 直下の display:inline リンクだけ（記事 2・カタログ 1・404 0）。core の skip-link（1×1、フォーカス時のみ表示）は SR 用として別掲。**44px（P05）**: 記事 50/50、帯 + カルーセル + float 共有 54/54、404 23/23、カタログ 13/13。**24px（WCAG 2.5.8）**: 同じく全件。サイト名・パンくず・カテゴリターム・カードタイトルは inline-flex + 負マージンで 44px 化（行送りは据え置き） |
| 見出し 1 行 | h2 18.5px、18 字、358px、1 行（19.3 字まで） |
| 目次しきい値 | h2 5 / h3 7 → 目次 5 / 7、`scroll-margin-top` 76px、SP は JS で閉 |
| 動作（別スクリプト） | お知らせ帯: 閉 → 再読込後も非表示（localStorage キー 1 件）。ヘッダー: 下スクロールで隠れ、上で再表示、背景不透明。目次: `#h-3` 到達で「3 製品の比較表」が current。カルーセル前後ボタン 2 組。比較表 SP: 行が block（カード）。横スクロールなし |

修正した点（初回検証・Astra レビューで判明）: PR 表記の重複判定が `is-style-wt-product` に誤一致して未挿入だった（`class="wt-pr "` 判定へ）、次に読むカードが関連グリッドの列幅を継承していた、目次リンク・FAQ summary・フッターナビが 44px 未満だった。レビュー指摘: `<th` の正規表現が `<thead>` に一致し最後の列見出しに scope が付かなかった（thead 内限定・タグ境界限定・件数制限なしへ）、post meta 4 件に sanitize_callback（allowlist 外は既定値）と REST schema enum を追加、監査の除外条件を狭め 44 / 24 を分離、guard の検査を固定 alpha から実描画へ（その結果 light 画像が未達と判明 → スクリム強化）、robots meta 全件判定、JS 無効テストで帯を検査、本文リンクのセレクタを段落内に限定（記事本文に内部リンクを 1 つ追加）。

## 5. 描画証跡

`results/` に 151 枚（JPEG q75、長辺 1600 以下。`CATALOG-INDEX.json` に {file, face, part, variant, dev}）。内訳: 記事全長 SP/PC 2 + 画面単位 20、ヘッダー 8（PC 4・SP 3・帯）、アイキャッチ 10、目次 9、h2 12、h3 6、囲み 16、CTA 8、比較表 2、メリデメ 2、評価バー 2、リンクカード 2、PR 2、de-text 部品 2、関連 8、共有 4、4 軸 on/off 24（depth 8・density 4・detext 8・motion 4）、コントラスト guard 6、404 6（計 151）。全長画像は縮小で判読しにくいため `article-screen-NN-*.jpg` を併用。

## 6. 手順（再現）

1. `docker cp theme/helix-wt/. agent-neo-wp:/var/www/html/wp-content/themes/helix-wt/`（helix-wt は試作 02 から有効のまま）
2. 記事: `wp post create --post_type=post --post_content='<!-- wp:pattern {"slug":"helix-wt/compare-article"} /-->' ...`、アイキャッチは `wp media import <theme>/assets/img/media-pickup-1.jpg` → `_thumbnail_id`。関連一覧用に短い架空記事 5 件 + カタログ固定ページ（`helix-wt/catalog-03`、テンプレ page-canvas）
3. 変種の確認: `?wt=header:cta,sp:left,eyecatch:hero,toc:float,related:rank,share:float,motion:on,depth:2,density:airy,detext:on,nf:suggest` のように付ける。サイト既定は `wp theme mod set wt_<key> <value>`、記事単位は `wp post meta set <ID> wt_eyecatch|wt_toc|wt_pr|wt_share <value>`
4. 撮影 `NODE_PATH=<playwright の node_modules> node scripts/shots.mjs --base <site> --out results`、検証 `node scripts/verify.mjs --base <site> --out results/verify.json`、計測 `node ../2026-09-04-site-survey/scripts/measure.mjs --url <記事 URL> --out <dir> --playwright <playwright パス>`
5. 輝度テスト画像 3 枚（`assets/img/lum-{dark,mid,light}.jpg`）は PHP GD で生成した無文字のグラデーション（手順はコンテナ内 eval、リポには成果物のみ）

## 7. 終了時状態（意図的に残置）

- テーマ `helix-wt`（試作 03 版）が有効。投稿: 記事 **554**（`/standing-desk-compare/`）、関連用 555–559、カタログ固定ページ 560（`/catalog-03/`）、添付 548–553、カテゴリ term 7（desk）。試作 02 の 518 / 519 / 520 / 533 と `wp_global_styles` 525 はそのまま。519 にアイキャッチ（添付 551）を付与。
- サイト名・キャッチフレーズ・ユーザー 1 の表示名と紹介文を架空値へ変更（試作 02 時の値から）。
- 撤去: `wp post delete 554 555 556 557 558 559 560 --force`、`wp post delete 548 549 550 551 552 553 --force`、`wp term delete category 7`、試作 02 の README §5 の手順。

## 8. 未実装・次タスク

- カテゴリ ミニ HOME、フッター変種、LP: 再撮影の完了待ち（次タスク）。
- 人気記事の集計方式（404 の人気・関連の「人気」は新着順で代替、#110）。
- 選択 UI（サイトエディターの variation / 記事サイドバー）。現状は theme_mod・post meta・プレビュー引数。
- 目次の float は 1200px 以上のみ（本文 680 + レール 240 + 余白）。1024–1199px では box にフォールバック。
- コントラスト guard の canvas 標本化はクライアント側。実装ではアップロード時（サーバ）に輝度を事前計算して attachment meta に持ち、`data-wt-lum` をサーバで出す（R75「輝度事前計算」）。
- PR 表記の自動挿入は投稿タイプ post 全件（比較媒体前提）。実装ではカテゴリ / 記事 meta で対象を絞る。
- 4 軸のうち depth-2 の CTA 立体化と `.is-style-wt-raised` の重複、`.wt-c-*` 色 modifier の block style 化（現状は追加 CSS class）は設計で整理。
- 44px 監査: カード全面クリックの実効領域を数える監査ロジック（`a::after` の矩形を含める）。

## 9. 公開安全

サイト名・URL・実在の製品名・ブランド名なし（製品・価格・数値・引用は架空）。参照テーマは テーマA / テーマB 表記、第三者プラグイン名・SNS 名なし（共有は Web Share API + リンクコピー）。画像は生成画像と GD 生成のグラデーション（文字・ロゴなし）。パスはリポ相対。スクリプトの既定 URL はローカル docker のもの。
