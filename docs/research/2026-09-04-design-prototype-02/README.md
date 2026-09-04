# デザイン試作 02 — 新規ブロックテーマ helix-wt（一新、agent-neo 非依存）

- 実施日: 2026-09-04（PO 指示「改修ではなく一新」「調査を先に」「デザインは必ず見せる」）
- 位置づけ: PoC 証跡。`theme/helix-wt/` は試作テーマの複製で、テーマ本体（`themes/`）は未編集。要求の確定・設計の決定ではない。
- 入力: 実サイト調査（`../2026-09-04-site-survey/`、尺度案 §6）、PO 指摘（2026-09-04: CTA / ハンバーガー順、画像、見出し付きボックス、SVG アイコン、構造はテーマB・パーツはテーマA を参照軸）。
- 一覧: 要求由来のパーツと選定軸は `../2026-09-04-parts-inventory/README.md`。

## 1. 作ったもの

| 種別 | 内容 |
|---|---|
| theme.json | 色 9 スラッグ、書体 5 系統（システム和文・欧文・明朝・等幅）、fluid 段 9（xs…display）、spacing 8 段、shadow 2、custom（header 60 / radius 4-6-10 / button 46 / gutter 20）、contentSize 740 / wide 1120 |
| templates | index / archive / single / page / front-page / page-lp（ヘッダー最小）/ page-canvas / 404 |
| parts | header（SP: ロゴ｜CTA｜ハンバーガー、PC: ロゴ｜文字ナビ｜CTA、1 段 60px 固定）/ footer |
| patterns 11 | hero-split / numbers / features / cases / steps / pricing / faq / cta / article-kit / article-parts / media-top |
| block style 10 | group: note / point / warn / card / card-shadow、list: check、heading: bar / underline、button: pill、table: compare |
| CSS 部品 | 見出し付きボックス 3 型（帯 / タブ / ラベル）× 色、吹き出し 2 方向、タイムライン、評価バー、メリデメ、アイコン付きリスト・ボタン、ピックアップ（大 1 + 小 4）、新着 / 人気タブ（core/tabs）、カテゴリカード、ランキング、プロフィール、数字訴求 |
| SVG アイコン 36 | 自前作成（24 grid、stroke 2）。`assets/icons/*.svg` と CSS mask クラス（`assets/css/icons.css`、currentColor） |
| style variation 2 | mincho / rules（palette と radius の差し替えのみ）。dark は PO 決定（2026-09-03 WT-Q-LOOK-04 不採用・J-13）に反していたため 2026-09-05 に撤去 |
| 画像 15 | codex 画像生成（hero、事例 3、商品、著者、特徴 3、記事カバー 6）。架空・無文字。`assets/img/` |
| JS | IO 出現アニメ 1 種（reduced-motion で停止） |

## 2. 実測（SP 390、同一スクリプト `../2026-09-04-site-survey/scripts/measure.mjs`）

| | 本文 | lh | h1 | h2 | h3 | ヘッダー高 | ボタン高 | 本文列幅 |
|---|---|---|---|---|---|---|---|---|
| 新テーマ 記事 | 15.09 | 1.8 | 22.53 | 20.35 | 18.18 | 61 | 46 | 350 |
| 新テーマ トップ（企業） | 14 | 1.8 | 29.05 | 20.35 | 17.09 | 61 | 46 | 308 |
| テーマA 記事 | 14.5 | 1.74 | 19.6 | 18.85 | 16.68 | 135 | 45 | 360 |
| テーマB 記事 | 15 | 1.8 | 19.5 | 18 | 16.5 | 49 | 35 | 359 |
| 旧テーマ 記事 | 17 | 1.8 | 48 | 36 | 20 | 149 | 57 | 326 |
| 調査 記事 中央値 | 16 | 1.8 | 22.2 | 20 | 18 | 64 | 40 | 351 |

PC 1280 の新テーマ記事: 本文 16 / h1 28 / h2 24 / h3 20 / ヘッダー 61 / 本文列 740。全数値は `results/metrics.json`。

## 3. 描画証跡

`results/`: 企業型トップ SP / PC、メディア型トップ SP / PC、記事 SP 全長、variation 2 本（mincho / rules）の SP 初回画面（いずれもローカル docker WP 7.1、生成画像・架空文）。

## 4. 手順（再現）

1. `docker cp theme/helix-wt agent-neo-wp:/var/www/html/wp-content/themes/helix-wt` → `wp theme activate helix-wt`
2. ページ本文は `<!-- wp:pattern {"slug":"helix-wt/<slug>"} /-->` の列挙（企業型トップ: hero-split, numbers, features, cases, steps, pricing, faq, cta / 記事: article-kit, article-parts / LP: hero-split…cta / メディア型トップ: media-top、テンプレ page-canvas）
3. variation は `scripts/set-variation.php`（`wp eval-file … <slug|reset>`、試作 01 と同じもの）
4. 撮影・計測は調査スクリプト `measure.mjs` と `firstview-all.mjs`

## 5. 終了時状態と未復元項目（意図的）

- コンテナ内: テーマ `helix-wt` が有効のまま（次の試作で続けて使うため）。投稿 518（ホーム）/ 519（記事）/ 520（LP）/ 533（メディア）、カテゴリ term 5、`wp_global_styles` 投稿 525（reset 済み）を残置。撤去は `wp theme activate agent-neo-themes/agent-neo-theme`、`wp post delete 518 519 520 533 --force`、`wp term delete category 5`。
- `wt-proto`（試作 01 の子テーマ）はそのまま。

## 6. わかったこと・注意

- WP は font-size スラッグを kebab-case 化するため `2xl` / `h1` は使えない（`--wp--preset--font-size--h-1` になる）。`xxl` 等の英字スラッグにした。
- post-content を `align:full` にしないと、constrained な main の中で alignfull セクションが本文幅に閉じ込められる。
- core navigation の `overlayMenu: mobile` は PC でもアイコンボタンを描画するため CSS で非表示にした。
- メディア部品（ピックアップ・ランキング・カテゴリ・プロフィール）は HTML ブロックの手組み。実装では Query Loop / 新規ブロック（VOCAB-01 の 6 + 1 枠）へ置き換える（Issue #110 / #117 / #113）。
- 明朝 variation は描画環境に和文セリフが無く fallback。和文フォント自己ホストは Issue #123。
- PO 指摘（2026-09-04）「のっぺり感」は未解決。奥行き表現は Issue #122 で扱う。

## 7. 公開安全

サイト名・URL なし。参照テーマは テーマA / テーマB 表記。画像は生成画像（文字・ロゴ・実在ブランドなし）。統合層の `check-public-safety.sh --staged` 通過。

## 8. 2026-09-05 追記

- PO 指摘で列内カードの高さを自動統一する CSS を `assets/css/theme.css` 末尾に追加（columns 内の card / price を stretch）。
- dark variation を撤去（PO 決定 WT-Q-LOOK-04 不採用に反していた）。コンテナ内の `styles/dark.json` も削除済み。
- アイキャッチ位置 5 案・カードのメディア枠 5 案はカタログ（scratchpad、DOM 注入による見た目比較）で PO に提示。テーマ側の実装は Issue #126 / #127。
