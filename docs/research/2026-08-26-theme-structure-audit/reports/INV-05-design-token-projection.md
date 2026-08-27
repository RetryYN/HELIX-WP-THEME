# THEME-INV-05 レポート — デザイントークンの正本と投影方式

- 対象イシュー: `issues/THEME-INV-05-design-token-projection.md`
- 状態: **仕分け方法を確定 / 採取済みの部分集合（テーマA 50 of 151・テーマB 29 of 155）に適用。
  全量の仕分けは追加採取が要る**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用
- 一次証跡: `evidence/probe6-raw.txt`（CSS カスタムプロパティ抽出・総数と先頭 50/29 件）・
  `evidence/re-themeB-pipeline.txt`（`Style` クラス）・`evidence/re-themeA-boot.txt`（CSS 生成関数）

## 1. 総数と保持方式

| テーマ | 変数の総数 | 保持方式 | 生成のタイミング |
|---|---|---|---|
| テーマA | **151** | カスタマイザ値（`themeA_*` 個別 option 1,225 / theme_mod） | `wp_head` で単一 2,098 行関数が全生成 |
| テーマB | **155** | 単一配列 `themeB_options`（既定 540 キー） | `classes/Style/`（11 生成器）が動的生成・`$modules` 分離あり |
| AGENT NEO | palette 8 + fontSizes 6 + spacingScale + custom 2 | `theme.json` v3 + `styles/{light,dark}.json` | **静的宣言**（生成しない） |

**採取の限界**: `evidence/probe6-raw.txt` は総数（151 / 155）とアルファベット順の先頭のみを保存している
（テーマA は 50 件、テーマB は `*.php` 由来の 29 件）。全量の仕分けには再採取が要る（§5）。

## 2. 仕分けの方法（確定）

各変数を 3 分類する。分類基準は**「Graphix NEO の設計語彙として意味を持つか」**。

| 分類 | 定義 | 中間 JSON / theme.json での扱い |
|---|---|---|
| **A. 意味的トークン** | 色・余白・タイポ・角丸・影など、**部品に依らない設計の語彙** | `theme.json` の `settings` へ宣言 |
| **B. 部品固有スタイル** | 特定の部品（ふきだし・比較表・CV ボタン等）**専用**の見た目 | レンダラ側 or `styles.blocks` の責務。トークンにしない |
| **C. 状態・分岐フラグ** | レイアウト種別やデザインプリセットの**切り替えスイッチ**（値ではなく状態） | **トークンではない**。設定値として扱う |

**C を独立させたのが本レポートの判断。** 従来「意味的 / 部品固有」の 2 分類で考えていたが、
テーマA の実測には `--header-style-default` / `--header-style-slope` / `--header-style-triangle` /
`--header-layout1` / `--header-layout2` / `--flat-design` のように、
**値ではなく「どのデザインを選んだか」を CSS 変数で表現しているもの**が相当数ある。
これらは色や寸法ではなく**分岐条件**であり、トークンとして持つと設計が歪む。

## 3. 採取済み部分集合への適用

### 3.1 テーマA（先頭 50 件 / 151 件中）

| 分類 | 件数 | 例 |
|---|---|---|
| **A. 意味的トークン** | **0** | — |
| **B. 部品固有スタイル** | 40 | `--ads-contents` `--ads-tabs` `--ads-text` `--aff-tabs` `--blogcard-external` `--blogcard-mysite` `--blogcard-style1/2` `--compare-*`（13 件）`--cv-button` `--cv-button-icon` `--cv-button-image` `--fukidashi-chat` `--fukidashi-img` `--fukidashi-innervoice` `--fukidashi-interview` `--heading-box5` `--heading-box6` `--h2rich-center` `--ham-follow-label` `--archive-subtitle` `--customizer-icontitle` ほか |
| **C. 状態・分岐フラグ** | 8 | `--header-style-default` `--header-style-slope` `--header-style-triangle` `--header-style-border` `--header-layout1` `--header-layout2` `--header-tracking-on` `--flat-design` `--display-none-title` `--check-using-article` `--compare-button-display` `--compare-button-disappear` |
| A. 余白・レイアウト | +2 | `--bottom-margin-s-pc` `--bottom-margin-xs-pc` — **確定（probe6-raw.txt）**: 変数一覧上でレイアウト系（`--article-style*` `--header-layout*`）と同帯に並ぶ汎用の下余白（S/XS ブレークポイント×PC）。特定部品専用ではなく**カテゴリ A（余白）**に確定。|

**先頭 50 件に意味的トークンが 1 つも無い**。アルファベット順の a〜h の範囲であり、
色（`--text-color` 等）が後半にある可能性はあるが、
命名規則からして **テーマA の変数体系は「部品の見た目を変数化したもの」**と判断してよい。

裏付け: 動的 CSS 生成関数は、テーマカラーから
`themeA_hex_to_hsl()` で **hue +30 / +45、明度 +9 を関数内でハードコード計算**しており
（`evidence/re-themeA-boot.txt` L270-272）、派生色を**トークンとして持っていない**。
派生規則がコードに埋まっている＝トークン体系が存在しない証拠。

### 3.2 テーマB（`*.php` 由来 29 件 / 155 件中）

| 分類 | 件数 | 例 |
|---|---|---|
| **A. 意味的トークン** | 3〜5 | `--the-color`（現在の文脈色）`--the-fz`（フォントサイズ）`--the-btn-radius`（角丸）ほか |
| **B. 部品固有スタイル** | 20 | `--capbox-color` `--capbox-color--bg` `--cell-icon-color` `--tbody-th-color--bg` `--tbody-th-color--txt` `--thead-color--bg` `--thead-color--txt` `--the-btn-bg` `--the-btn-color` `--the-btn-color2` `--the-cell-bg` `--the-icon-svg` `--the-solid-shadow` ほか |
| **C. 状態・分岐フラグ** | 0 | — |
| **D. レイアウト寸法** | 6 | `--block_max_width` `--clmn-w--pc/tab/mobile` `--the-box-width--pc/tab/mb` `--swl-lp_content_width` `--table-width` `--swl-cell1-width` |

**テーマB には D（レイアウト寸法）という第 4 の類型がある。** これは A に含めてよい
（`theme.json` の `settings.layout.contentSize` / `wideSize` に対応する）。

また **`--the-*` プレフィックスが「現在のスコープにおける値」を表す**設計になっている
（`--the-color` / `--the-fz` / `--the-btn-*`）。これは CSS のカスケードを使った
**文脈依存トークン**で、`theme.json` の宣言的モデルとは考え方が違う。

`--swl-fb` / `--swl-fb_pc` / `--swl-fb_tab` は テーマB 固有のプレフィックス付き。
命名に一貫性が無い（`--swl-` 付きと無しが混在）のは歴史的経緯と見られる。

## 4. theme.json で表現できるか — 判定

| 分類 | theme.json での表現 | 判定 |
|---|---|---|
| A. 意味的トークン | `settings.color.palette` / `typography.fontSizes` / `spacing.spacingSizes` / `border` | **可** |
| D. レイアウト寸法 | `settings.layout.contentSize` / `wideSize` / `custom` | **可** |
| B. 部品固有スタイル | `styles.blocks.<block>` でブロック単位に書ける | **条件付きで可**。ただし**部品がブロックとして存在する場合のみ**。テーマA の `--compare-*` のように独自ブロック前提のものは、Graphix NEO に対応ブロックが無ければ写せない |
| C. 状態・分岐フラグ | **不可** | theme.json は値の宣言であって分岐を持たない。**設定値（`config/*.json`）として扱う**べき |

### 4.1 不足カテゴリの洗い出し（暫定）

`theme.json` v3 の `settings` で**表現しにくい**もの:

| 内容 | 実測での該当 | 対応案 |
|---|---|---|
| 影（box-shadow）のスケール | `--the-solid-shadow` | `settings.shadow.presets`（WP 6.3+）で表現可 |
| SVG アイコン | `--the-icon-svg` | トークンではない。アセットとして持つ |
| デザインプリセットの選択 | `--header-style-*` `--flat-design` | **`config/*.json` の設定値**へ。theme.json では持たない |
| 文脈依存の値（`--the-*`） | テーマB 全般 | `theme.json` の `styles.blocks` + CSS カスケードで再現。**宣言では表せない部分が残る** |

## 5. 未了項目（追加採取が要る）

- [ ] **テーマA 151 件の全量取得**（現在 50 件）— `grep -rhoE '\-\-[a-zA-Z0-9_-]+\s*:' | sort -u` の全出力
- [ ] **テーマB 155 件の全量取得**（現在 PHP 由来 29 件）— `*.css` / `*.scss` を含めた全出力
- [ ] AGENT NEO の `styles/light.json` / `dark.json` の中身確認（トークンの上書き範囲）
- [ ] 「JSON デザイントークン → theme.json 投影」の既存実証（HELIX Neo）との接続点の確認
- [x] 判定保留 2 件（`--bottom-margin-s-pc` / `--bottom-margin-xs-pc`）→ カテゴリ A（余白）に確定（probe6-raw.txt）

## 6. 現時点の結論

1. **テーマA のトークン体系は移植対象にならない。** 151 件はほぼ B（部品固有）と C（分岐フラグ）で、
   意味的トークンが存在しない。派生色の規則もコードに埋まっている。
   → **参照のみ**（`reports/INV-12-asset-reuse-ledger.md` #2 の判定材料）
2. **テーマB は A + D が一定量あり、`theme.json` へ写せる部分がある。**
   ただし `--the-*` の文脈依存モデルは宣言的モデルと思想が違い、そのままは写らない。
3. **トークン正本は JSON 一方向投影にできる。** ただし条件が 2 つ:
   - **C（分岐フラグ）をトークンから分離**し、設定値として別に持つこと
   - **B（部品固有）はブロック / レンダラの責務**とし、トークン体系に混ぜないこと
   この 2 つを守れば、`theme.json` + `config/*.json` の宣言から CSS を一方向生成できる。
   逆に混ぜると テーマA と同じ「2,098 行の生成関数」に向かう。

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| CSS カスタムプロパティの総数と部分リスト | `evidence/probe6-raw.txt` |
| テーマA の派生色ハードコード（hue +30/+45・明度 +9） | `evidence/re-themeA-boot.txt` L270-272 |
| テーマB の `Style` クラス（バケット・`$modules`・front/editor 出し分け） | `evidence/re-themeB-pipeline.txt` |
| AGENT NEO の theme.json 構成 | `03-structure-agent-neo.md` §1 |
