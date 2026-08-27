# THEME-INV-01 レポート — ブロック語彙 3 系統の対応表（意味層）

- 対象イシュー: `issues/THEME-INV-01-block-vocabulary-map.md`
- 状態: **②（意味の突き合わせ）完了 / ④（インライン書式）判定完了 /
  ①（属性・出力マークアップの採取）と ③（意図語彙への写像の確定）は追加読み取りが要る**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用
- 一次証跡: `evidence/probe2-raw.txt`（テーマA 名前空間・テーマB `themeB/*` 全 50 名）・
  `evidence/probe3-raw.txt`（テーマA 登録全 25 種）・`evidence/theme-features-raw.txt`（block.json 名・
  ショートコード）・`evidence/usage-raw.txt`（実使用）・`evidence/re-themeB-blocks.txt`（normal/dynamic 分類）

## 1. 母集団

| 系統 | 数 | 内訳 |
|---|---|---|
| テーマA `themeA-blocks/*` | **25** | 動的 7 / 静的 18。加えて `core/list` のブロックスタイル 2 種 |
| テーマA ショートコード | 6 | `themeA_button` `themeA_fukidashi` `themeA_heading_iconbox` `themeA_profile` `themeA_simple_iconbox` `message` |
| テーマB `themeB/*` | **50** | 通常 22（`register_block`）/ 動的 10（個別ファイル）/ インライン書式ほか 18 |
| テーマB ショートコード | 20 | 日本語別名 4 を含む |
| AGENT NEO | **1 ブロック + 24 パターン** | `agent-neo/embed` のみ。装飾はコアブロック + パターンで構成 |

**合計 75 ブロック**（25 + 50）。本レポートは**意味の対応**を確定させ、
属性レベルの突き合わせ（①）は次段へ送る。

## 2. 意味対応表

凡例: **●** 専用ブロックあり ／ ○ 相当機能あり（別形態）／ — 無し

| # | 意味 | テーマA | テーマB | AGENT NEO | 実使用（テーマA / テーマB） |
|---|---|---|---|---|---|
| 1 | 汎用囲みボックス | ● `simplebox` | ● `cap-block` / `style-block` / `mini-note` | — | **697** / 29 |
| 2 | ボタン | ● `button`（SSR） | ● `button` / `inline-btn` | ○ core/buttons + `article-cta` パターン | **339** / 0 |
| 3 | 内部リンクカード | ● `blogcard`（SSR） | ● `post-link`（SSR） | ○ `blog-card-controller`（プラグイン REST） | **330** / 6 |
| 4 | 吹き出し | ● `fukidashi` + SC `[themeA_fukidashi]` | ● `balloon`（SSR） + SC `[speech_balloon]` `[ふきだし]` + **独自テーブル `themeB_balloon`** | — | **186** / 0 |
| 5 | 比較表 | ● `compare` + `comparechild` | — （`flexible-table-block` プラグインで代替） | ○ `lp-comparison` パターン | **177 / 59** / 0 |
| 6 | 定義リスト | — | ● `dl` `dt` `dd` `dl-dt` `dl-dd` | ○ core/list | 0 / **106 / 106 / 30** |
| 7 | FAQ | **—** | ● `faq` + `faq-item` | ○ `lp-faq` パターン | 0 / **56 / 6** |
| 8 | 手順・ステップ | ● `timeline` + `timelinechild` | ● `step` + `step-item` | — | 1 / 4 ・ **28 / 7** |
| 9 | 記事一覧 | ● `postlist` `postcard`（SSR） | ● `post-list`（SSR） | ○ `inc/class-related-query.php` | 38 / 0 |
| 10 | アコーディオン | ● `accordion` + `accordionchild` | ● `accordion` + `accordion-item` | — | 7 / 15 ・ 0 |
| 11 | タブ | ● `tab` + `tabchild` | ● `tab` + `tab-body` | — | 1 / 2 ・ 0 |
| 12 | 装飾見出し | ● `designtitle` | ○ `core/heading` を block.json で拡張 | ○ theme.json `styles.blocks` | 37 / — |
| 13 | 全幅セクション | ● `fullwidth` / `background` | ● `full-wide` / `bg-color` | ○ core/group alignfull | 6 / 33 ・ 0 |
| 14 | リッチメニュー | ● `richmenu` + `richmenuchild` | ● `box-menu` + `box-menu-item` | ○ `home-gateway` パターン | 5 / 16 ・ 0 |
| 15 | アイコンボックス | ● `iconbox` + SC 2 種 | ● `inline-icon` + SC `[icon]` `[アイコン]` | — | 1 / 0 |
| 16 | 区切り線 | ● `designborder` | — | ○ core/separator | 0 / — |
| 17 | コード表示 | ● `syntax-hl` | ● `code-dir` / `code-file` | — | 0 / 0 |
| 18 | スライダー | ● `slider`（SSR） | ○ `parts/top/main_visual-slider.php` | — | 0 / — |
| 19 | カテゴリ表示 | ● `category`（SSR） | ○ `themeB-term-list` REST | — | 0 / — |
| 20 | 再利用パーツ | ○ 番号スロット型 option | ● `blog-parts`（SSR）+ **CPT `blog_parts`** | — | — / 2 |
| 21 | 広告タグ | ○ ウィジェット + h2 前挿入 | ● `ad-tag`（SSR）+ **CPT `ad_tag`** | ○ `ad-zones` / `ad-tags` controller | — / 0 |
| 22 | バナーリンク | — | ● `banner-link` + SC `[custom_banner]` `[カスタムバナー]` | — | — / 0 |
| 23 | リンクリスト | — | ● `link-list` + `link-list-item` | — | — / 0 |
| 24 | レビュー・評価 | — | ● `review` + SC `[review_stars]` | — | — / 0 |
| 25 | RSS | — | ● `rss`（SSR） | — | — / 0 |
| 26 | A/B テスト | — | ● `ab-test` + `-a` + `-b` | ○ `ab-test` controller | — / 0 |
| 27 | 会員・課金制限 | ● `paidpost`（SSR・Stripe） | ● `restricted-area`（SSR）+ SC `[only_login]` `[only_logout]` | — | **0** / 0 |
| 28 | プロフィール | ○ SC `[themeA_profile]` ＋**未登録ブロック `profile`** | ○ ウィジェット `THEMEB_Prof_Widget` | ○ `author-profile` パターン | 1 / — |
| 29 | 目次 | ○ 外部プラグイン RTOC | ● SC `[themeB_toc]` + `content_filter` | ○ レンダラ生成 | — |
| 30 | PR 表記 | ○ テンプレート内（`representation-act-setting`） | ● SC `[pr_notation]` | — | — / 0 |
| 31 | AI 生成 HTML の隔離差込 | — | — | ● `agent-neo/embed`（DSD shadowroot SSR） | — |

## 3. 分類の結論

### 3.1 意味が一致するもの（両テーマに専用ブロックがある）— **11 組**
#1 囲みボックス / #2 ボタン / #3 内部リンクカード / #4 吹き出し / #8 手順 / #9 記事一覧 /
#10 アコーディオン / #11 タブ / #13 全幅 / #14 リッチメニュー / #27 会員制限

これらは**意図語彙の第一候補**。両テーマが独立に同じ概念へ到達しているため、
汎用的な意味として妥当性が高い。

### 3.2 片方にしかないもの

| テーマA のみ | テーマB のみ |
|---|---|
| #5 比較表（`compare` + `comparechild`・実使用 **177 / 59**）| #6 定義リスト（実使用 **106 / 106**）|
| #12 装飾見出し（専用ブロック）| #7 FAQ（実使用 **56**）|
| #16 区切り線 | #22 バナーリンク・#23 リンクリスト・#24 レビュー・#25 RSS・#26 A/B テスト |
| #18 スライダー・#19 カテゴリ表示 | #30 PR 表記 |

**注目**: テーマA の比較表（177 + 59）と テーマB の定義リスト（106 + 106）・FAQ（56）は
**それぞれのサイトで多用されているが、相手側に対応物が無い**。
Graphix NEO はこの両方を持つ必要がある（どちらのサイトも移管対象になりうるため）。

**テーマB に FAQ ブロックがあり テーマA に無い**点は `reports/INV-06-structured-data-gap.md` と接続する。
FAQ を意図ノードとして持てば `FAQPage` 構造化データを自動生成できる。

### 3.3 粒度がずれるもの（親子分割の有無・命名の差）

| 意味 | テーマA | テーマB | 差 |
|---|---|---|---|
| アコーディオン | `accordion` / `accordionchild` | `accordion` / `accordion-item` | **命名のみ**（child vs item） |
| タブ | `tab` / `tabchild` | `tab` / `tab-body` | **確定（re-themeB-blocks.txt L60-62）**: テーマB のブロック登録配列に子は `tab-body`（本文領域）のみで「タブ 1 枚」に相当する子名が無い。テーマA は `tabchild`=タブ 1 枚。**構造が非対称** — 中間 JSON では「タブ集合 → 各タブ（見出し＋本文）」に正規化し、両テーマへ投影時に子名を割り当てる。|
| 手順 | `timeline` / `timelinechild` | `step` / `step-item` | テーマA は「時系列」、テーマB は「手順」。**意味が微妙に違う** |
| リッチメニュー | `richmenu` / `richmenuchild` | `box-menu` / `box-menu-item` | 命名のみ |
| 定義リスト | — | `dl` / `dt` / `dd` + `dl-dt` / `dl-dd` | テーマB 側に**重複した 2 系統**がある（旧実装の残存と推定） |

**意図語彙の設計方針**: 親子分割は**保つ**（innerBlocks の構造がそのまま意味構造）。
命名は実装由来（`child` / `item` / `body`）ではなく**意味**で決める
（`reports/INV-10-shortcode-compat.md` §5 の「非技術者が扱える語彙」の要求と整合）。

### 3.4 写像不能・要判断のもの

| 対象 | 理由 | 送り先 |
|---|---|---|
| #27 会員・課金制限 | 閲覧者の状態依存で決定論の外 | INV-11（スコープ外を推奨） |
| #21 広告タグ | 3 者で機構が全く違う（ウィジェット / CPT / コントローラ） | INV-03 |
| #20 再利用パーツ | 同上 | INV-04（**参照方式で決着済み**） |
| #29 目次 | 実装位置が 3 者バラバラ | INV-07（**レンダラ導出で決着済み**） |
| #5 比較表 | テーマA 専用ブロック vs プラグイン（`flexible-table-block` 46 回） | 表全般の扱いとして別途 |
| `themeA-blocks/profile` | **登録一覧に無いブロックが本文に 1 回出現** | INV-14（廃止ブロックの残存と推定） |

## 4. ④ インライン書式の扱い — 判定

テーマB は**インライン書式を block.json で 10 種近く定義**している:
`marker` `font-size` `text-color` `bg-color` `nowrap` `inline-icon` `inline-tools`
`text-centered` `clear` `cell-bg`。テーマA には対応物が無い（CSS 変数 `--marker1/2-color` は持つ）。

**判定: インライン書式は中間 JSON の「ノード」ではなく「テキストの装飾範囲」として持つ。**

理由と方式:
- インライン書式はブロックではなく**テキストの一部区間に付く属性**。
  ブロックと同じノードとして扱うと、テキストが細切れになり再編集が壊れる。
- 中間 JSON では**テキストノード + 装飾レンジの配列**で表す:
  ```json
  { "type": "paragraph",
    "text": "この部分が重要です",
    "marks": [ { "type": "marker", "start": 2, "end": 6, "variant": 1 } ] }
  ```
- 対応させる装飾は**意味のあるものだけ**（marker / strong / link / code）。
  `text-centered` `clear` `nowrap` のような**レイアウト操作は装飾ではなく段落の属性**へ寄せる。
- `inline-tools` `cell-bg` はエディタ内部の補助と推定（未確認）。**採用しない方向**で確認する。

## 5. 未了項目（①③の完了に要る）

- [ ] **75 ブロックの属性採取**
      - テーマB: `find themes/themeB -name block.json` の全 JSON を読む（機械的に取れる）
      - テーマA: **block.json が無い**ため、実記事の `<!-- wp:themeA-blocks/… {…} -->` から帰納（INV-14）
- [ ] **save 出力マークアップの採取** — 実記事の HTML から各ブロックの出力形を確定
- [ ] `tabchild` と `tab-body` の意味差の確認
- [ ] テーマB の `dl` / `dt` / `dd` と `dl-dt` / `dl-dd` の 2 系統の関係（旧実装か）
- [ ] `inline-tools` / `cell-bg` の用途確認
- [ ] `themeA-blocks/profile`（未登録ブロック）の実体確認
- [ ] 意図語彙の命名確定（③）— 上記が揃ってから

## 6. 証跡ファイル

| 内容 | 場所 |
|---|---|
| テーマA 名前空間の全出現・テーマB `themeB/*` 全 50 名 | `evidence/probe2-raw.txt` |
| テーマA `register_block_type` 全 25 種 + `register_block_style` 2 件 | `evidence/probe3-raw.txt` |
| block.json 由来のブロック名・ショートコード一覧 | `evidence/theme-features-raw.txt` |
| テーマB の normal(22) / dynamic(10) 分類 | `evidence/re-themeB-blocks.txt` |
| 実使用回数（両サイト） | `evidence/usage-raw.txt` |
| テーマB の CPT 定義（`blog_parts` / `ad_tag` / `lp`） | `evidence/probe4-raw.txt` |
