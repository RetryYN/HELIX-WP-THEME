# SP サイズ感の比較計測（2026-09-04）

PO 指摘「スマホはサイズ感が全くおかしい」の根拠づけ。390×844（DSF 2）で描画し、`getComputedStyle` / `getBoundingClientRect` の
px 値を系列ごとに取った。決定ではなく証跡。

## 系列と状態

| 系列 | 内容 | 状態 |
|---|---|---|
| テーマA（親） | 比較対象の第三者テーマ | **実運用サイト site-A 上で計測**（2026-09-04、公開ページを GET のみ。ローカル同一内容ではない） |
| テーマB（親） | 比較対象の第三者テーマ | **実運用サイト site-B 上で計測**（同上） |
| 本テーマ light | `agent-neo-theme` 親、既定 style | ローカル WP 7.1・投稿 ID 475 で計測済 |
| 本テーマ compare | 子テーマ `wt-proto` + `styles/compare.json` | 同上 |
| 参考 TT5 | WP 同梱既定テーマ（Twenty Twenty-Five） | 同上。テーマA/B 実測前の暫定基準（以後は参考） |

### テーマ判定の根拠（伏せ字）

- site-A: HTML 内のテーマ CSS/JS 読込パスが theme-a 系 slug 配下（17 参照）、`body` / `article` のクラス接頭辞も同 slug → **theme-a**。
- site-B: 同パスが theme-b 系 slug 配下（20 参照）、レイアウトクラスが `l-` / `p-` / `c-` 接頭辞の BEM → **theme-b**。
- どちらも `generator` meta は site-kit 系プラグインのもので WP 版は判別不能。実 slug・ドメイン・対応表はリポ外（`~/.config/helix-redaction/`）。

### 実運用サイト計測の限界

- 記事が異なる（ローカルは ID 475、実サイトは各サイトの最新記事 1 本）。**FV 内文字数・h1 行数・記事高さは記事内容に依存**するため参考値。
- 実サイトにはアイキャッチ・広告表記・SNS シェア帯・サイト固有のカスタマイズ（子テーマ・追加 CSS・プラグイン）が乗っており、
  親テーマ素の値ではない。ただし font-size / line-height / ヘッダー高さは概ね親テーマ既定と見なせる。
- 実サイトの本文コンテナ・ボタンはテーマ固有クラスに依存せず「20 文字超の p を最も多く含む最深祖先」「背景色付き padding≥8px の a/button」で取った。
- テーマB の記事にはボタン様要素が無く、ボタン列は空。

## 比較表（記事ページ、SP 390 幅、px）

倍率は「本テーマ light ÷ 各系列」。

| 項目 | 本テーマ light | テーマA | テーマB | 参考 TT5 | 本/A | 本/B |
|---|---|---|---|---|---|---|
| html / body font-size | 16 / 16 | 10 / 16 | 14.04 / 14.04 | 16 / 18.27 | — | — |
| 本文 p font-size / line-height | 17 / 30.6（1.8） | 14.5 / 25.2（1.74） | 15 / 27（1.8） | 18.27 / 25.6 | **1.17 / 1.21** | 1.13 / 1.13 |
| 本文 p 段落間（margin） | 24 | 33（上下 33） | 30（下） | 19.2 | 0.73 | 0.80 |
| 本文コンテナ内側幅 / 左右余白 | 326 / 32 | 360 / 15 | 359 / 16 | 330 / 30 | 0.91 | 0.91 |
| **h1（記事タイトル）font-size** | **48** | **19.6** | **19.5** | 35.33 | **2.45** | **2.46** |
| h1 line-height / 高さ（行数・文字数） | 52.8 / 316.8（6 行・36 字） | 27.4 / 54.8（2 行・31 字） | 27.3 / 81.9（3 行・35 字） | 39.75 / 158.9（4 行） | 1.93 / 5.8 | 1.93 / 3.9 |
| h1 margin-top / bottom | 32.2 / 32.2 | 12 / 0 | 0 / 0 | 0 / 0 | — | — |
| **h2 font-size** | **36** | **18.85** | **18** | 28.27 | **1.91** | **2.0** |
| h2 line-height / margin-top | 43.2 / 24 | 24.5 / 52.8 | 25.2 / 72 | 31.8 / 19.2 | 1.76 / 0.45 | 1.71 / 0.33 |
| h3 font-size | 20 | 16.68 | 16.5 | 18.27 | 1.20 | 1.21 |
| ボタン font-size / 高さ | 16 / 56.8 | 16.2 / 53.7 | （記事内に無し） | 16.14 / 54.6 | 0.99 / 1.06 | — |
| ボタン padding（上下 / 左右） | 14 / 32 | 12.75 / 31（右 49、矢印込） | — | 16 / 36 | 1.10 / 1.03 | — |
| **ヘッダー高さ** | **149（2 段折返し）** | 135（ロゴ行 + アイコンナビ行の 2 段設計） | **49（1 段）** | 65.6 | 1.10 | **3.04** |
| サイト名 font-size | 20 | 26 | 19.5 | 18.27 | 0.77 | 1.03 |
| h1 上端 y | 260 | 495（アイキャッチが先） | 109 | 126 | — | — |
| 本文先頭 p 上端 y | 752 | 643 | 668（アイキャッチ + シェア帯の後） | 424 | 1.17 | 1.13 |
| **FV（844px）内の本文文字数** | **19** | **88** | **74** | 139 | **0.22** | **0.26** |
| 下部固定バナー | cookie 同意 370（実効 FV 474） | 無し | 無し | 無し | — | — |
| 記事全体の高さ | 7588 | 32228（別記事） | 43744（別記事） | 5819 | 比較不能 | 比較不能 |

## PC 1280 幅（記事ページ、px）— SP との縮尺関係

| 項目 | 本テーマ light | テーマA | テーマB |
|---|---|---|---|
| html font-size | 16 | 10 | 16 |
| 本文 p font-size / line-height | 17 / 30.6（theme.json 固定） | 16 / 31.2 | 17 / 30.6 |
| h1 font-size（SP ÷ PC） | 48（**1.00**、固定） | 28.5（0.69） | 24（0.81） |
| h2 font-size（SP ÷ PC） | 36（1.00） | 26.4（0.71） | 23.8（0.76） |
| h3 font-size | 20 | 21.6 | 22.1 |
| 本文コンテナ内側幅 | （未計測） | 740 | 828 |
| ヘッダー高さ | （未計測） | 91 | 95 |
| 本文先頭 p 上端 y | （未計測） | 891 | 1010 |

本テーマの PC 値は `theme.json` の固定 rem から。テーマA/B は **PC で h1 24〜28.5、SP で 19.5〜19.6** と PC 側でも本テーマの半分強で、
SP では約 0.7〜0.8 倍に縮む。本テーマは SP/PC とも 48 で縮まない。

## トップページ（SP）

- 本テーマ: ヘッダー 149 + ヒーロー 702（h1 48px / 800 weight・4 行）。FV に見出し以外がほぼ入らない。
- テーマA: ヘッダー 135（ロゴ行 + アイコンナビ）+ CTA 帯 ≈55 + スライダー ≈330（スクリーンショット目視の概算）。h1 無し。
- テーマB: ヘッダー 49 + 記事カルーセル（h1 はサイト名 14px 相当）。ヒーロー見出し無し、FV の大半が記事カード。
- TT5 の "/" は投稿一覧（ヘッダー 65.6）。

代表画像（390 等倍・webp q80、**寸法確認用**。実サイトの画像はロゴ・アイキャッチ等の識別部位を塗りつぶし、本文の引用はしない）:
`results/theme-a-article-fv-390.webp`, `results/theme-b-article-fv-390.webp`, `results/ours-light-article-fv-390.webp`,
`results/ours-compare-article-fv-390.webp`, `results/ref-tt5-article-fv-390.webp`。フルページ PNG・top FV・PC FV・生 JSON（URL 含む）は scratchpad 側（リポ外）。

## 所見（本テーマが「おかしい」と感じる原因の候補）

テーマA/B 実測で更新。数値で差が大きい順。

1. **見出しが実運用テーマの約 2.5 倍（h1）/ 2 倍（h2）**
   `theme.json` は `settings.typography.fluid` 未設定で、`fontSizes` は固定 rem のみ:
   ```json
   { "slug": "xx-large", "size": "2.25rem" }, { "slug": "xxx-large", "size": "3rem" }
   ```
   `styles.elements.h1.typography.fontSize = xxx-large`（48px）、`h2 = xx-large`（36px）。
   テーマA/B は SP で **h1 19.5〜19.6px（2〜3 行・55〜82px 高）、h2 18〜18.9px**、PC でも h1 24〜28.5px。
   本テーマの h1 は同じ長さのタイトルで 6 行・317px と、**高さで 4〜6 倍**。これが縮尺違和感の主因。
2. **ファーストビューに本文が入らない**
   本文先頭 y=752（テーマA 643 / B 668。両者はアイキャッチ・シェア帯を FV に置いてなお本テーマより上）。
   FV 内本文 19 文字に対しテーマA 88 / B 74（4〜5 分の 1）。
   さらに cookie 同意バナー（`inc/assets/class-third-party-manager.php`、`position: fixed; bottom: 0`、SP で 370px）が
   FV の 44% を覆う（テーマA/B には下部固定バナー無し）。
3. **ヘッダー**
   テーマB は 1 段 49px（本テーマの 1/3）。テーマA は 135px だがロゴ行 + アイコン付きナビ行という **設計上の 2 段**で、
   本テーマの 149px は `flexWrap: wrap` による **意図しない折返し**（サイト名行の下にハンバーガー + CTA が落ちる）。
   数値は近いが性質が違う。
4. **本文は「文字が大きめ・行間は同等・段落間は狭め」**
   p は 17px（テーマA 14.5 / B 15 → 1.13〜1.17 倍）、line-height 1.8 は **テーマB と同じ、A（1.74）とも同帯域**。
   段落間 24px は A/B の 30〜33px より狭い。前版の「行送りが広い」は TT5 比の見立てで、実運用テーマ比では成立しない。
   見出し 2.5 倍・本文 1.15 倍という **本文と見出しの縮尺不一致** が「文字は普通なのに見出しが巨大」に見える原因。
5. **h2 の上マージンが小さい**（24 vs A 52.8 / B 72）。見出しが大きいのに節の区切りが詰まり、階層が読みにくい。
6. h3・ボタン・本文余白・サイト名は A/B と ±20% 以内で原因の主体ではない。本文コンテナ幅は A/B が 359〜360（左右 15〜16）で本テーマ 326（左右 32）より 1 割広い。

**compare variation は数値に影響しない**（色と h2 の左 padding 12px のみ差分）。SP 尺度の問題は親 `theme.json` 側にある。

## PO への示唆（案、決定ではない）— テーマA/B 実測に合わせて更新

- `theme.json` に `settings.typography.fluid: true` を入れ、`fontSizes` を `fluid: { min, max }` 形式にする。
  実測帯域に合わせた案: xxx-large（h1）`min 1.25rem（20px） / max 1.75rem（28px）`、xx-large（h2）`min 1.125rem（18px） / max 1.625rem（26px）`、
  large（h3）`min 1.0625rem（17px） / max 1.375rem（22px）`。SP で h1 20 / h2 18 / h3 17 となりテーマA/B と同帯域、
  PC で h1 28 / h2 26 / h3 22 でテーマA（28.5 / 26.4 / 21.6）相当。
- 本文: `core/post-content` の fontSize を SP 15px 前後（`fluid min 0.9375rem / max 1.0625rem`）に、lineHeight 1.75〜1.8 は維持、
  blockGap は spacing-30（24px）→ 30px 前後へ広げる（A 33 / B 30）。h2 の margin-top は 48〜64px 帯へ。
- 記事ヘッダー: h1 の margin を SP で 8〜12px、パンくず・メタの上下 padding を詰め、本文先頭を **y≤650**（A 643 / B 668、アイキャッチ込みで）へ。
  h1 は 2〜3 行に収まる想定（19.5〜20px、31〜36 字）。
- ヘッダー: SP で 1 段（サイト名 + ハンバーガー + 小 CTA）、高さ **48〜64px**（B 49、TT5 65.6）。2 段にするならテーマA のように設計上のナビ行として。
- cookie バナー: SP では 1 行テキスト + 横並びボタンで高さ ≤ 120px、または初回のみ折りたたみ。
- L2 要求（WT-*）に書く SP 受け入れ基準の候補（実測起点）: **h1 ≤ 22px・h2 ≤ 20px（SP）、FV 内本文 ≥ 70 文字、本文先頭 y ≤ 650、
  ヘッダー ≤ 64px（1 段）、h1 SP/PC 比 ≤ 0.85**。

## 再現手順

ローカル系列:
```
export SHOT_DIR=<scratchpad>/sp-compare OUT_DIR=$PWD/docs/research/2026-09-04-sp-size-compare/results \
       PW_NODE_PATH=$PWD/node_modules PW_EXECUTABLE=<playwright chromium headless shell>
bash docs/research/2026-09-04-sp-size-compare/scripts/run-series.sh ours-light agent-neo-themes/agent-neo-theme -
bash docs/research/2026-09-04-sp-size-compare/scripts/run-series.sh ours-compare wt-proto compare
bash docs/research/2026-09-04-sp-size-compare/scripts/run-series.sh ref-tt5 twentytwentyfive -
docker compose run --rm -T wpcli theme activate agent-neo-themes/agent-neo-theme
```
実運用サイト系列（GET のみ。URL は引数でだけ渡し、生 JSON は SHOT_DIR に、リポ側には `sanitize-live.js` で URL・テキスト・クラス名を落とした JSON と webp のみ）:
```
bash docs/research/2026-09-04-sp-size-compare/scripts/measure-live.sh theme-a https://<site-a> /<latest-post-path>/
bash docs/research/2026-09-04-sp-size-compare/scripts/measure-live.sh theme-b https://<site-b> /<latest-post-path>/
```
webp はロゴ・アイキャッチ領域を `ffmpeg drawbox` で塗りつぶした版を収載（座標は scratchpad 側のメモ）。
`measure-sp.js` はローカル・実サイト共通で、PC 1280×800 の計測（`pc` キー）も同時に取る（ours-* / ref-tt5 の既存 JSON は PC 計測前の版）。

## 環境復元

有効テーマ `agent-neo-themes/agent-neo-theme` に戻し済み。DB・リポの `themes/` `plugins/` は未変更。
`wt-proto` の user global styles（wp_global_styles 投稿）は compare 適用のまま（子テーマは非 active なので表示に影響しない）。
実運用サイトへの write は無し（GET のみ、ログイン・フォーム送信なし）。


## 画像収載について

テーマA / テーマB の実サイト由来スクリーンショット（`theme-a-article-fv-390.webp` / `theme-b-article-fv-390.webp`）は、ナビ文言・記事タイトルが残るため転載許諾の確認が済むまでリポジトリに収載しない（scratchpad に保持）。数値はすべて `results/measurements.json` で確認できる。
