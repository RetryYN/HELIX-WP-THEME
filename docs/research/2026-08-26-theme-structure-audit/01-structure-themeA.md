# 構造調査 A — テーマA（site-A.example）

- 調査日: 2026-08-26 / 手段: ホスティング SSH 読み取り専用（`ssh -p 10022 <account>@<hosting-host>`）+ WP-CLI 読み取りクエリ
- 対象: `~/site-A.example/public_html/wp-content/themes/themeA`（親）/ `themeA-child`（子）
- テーマ: テーマA 1.4.6 / ベンダーA / Requires PHP 7.0 / WP core 7.0.2
- 書き込み: なし（find・grep・cat・ls・SELECT のみ）

## 1. 物理構造

| 項目 | 値 |
|---|---|
| サイズ / ファイル数 | 22MB / 679 |
| 拡張子内訳 | php 374・png 122・scss 59・webp 35・json 29・js 22・css 13 |
| ルート直下 | 古典テンプレート階層一式（`index/single/page/archive/category/search/404/attachment/comments/header/footer/sidebar/searchform`）+ `template-full-width.php` / `template-thanks-page.php` / `ad-finish.php` / `ad-related.php` |
| 主要ディレクトリ | `include/`(179、うち `customizer/` 162)・`lib/`(80)・`scss/`(63)・`object/`(22)・`vendor/`(298、うち `stripe/` 286)・`editor/build/`(3) |
| theme.json | **無し**（クラシックテーマ） |
| 子テーマ | `themeA-child` は 6 ファイル・親 style.css を enqueue するだけ（実質カスタム無し） |

## 2. パーツ機構

### 2.1 Gutenberg ブロック（25 種・名前空間 `themeA-blocks/`）
`functions.php` 内で **PHP から一括 `register_block_type`**。全ブロックが単一の
`editor_script = themeA-blocks-script`（`editor/build/index.js`）と単一 `editor_style`（`block.css`）を共有する。

- **動的（`render_callback` 有り）7 種**: postcard / postlist / paidpost / slider / button / blogcard / category
- **静的（save 出力）18 種**: designtitle / syntax-hl / simplebox / richmenu / richmenuchild / designborder / fukidashi / iconbox / fullwidth / accordion(+child) / compare(+child) / timeline(+child) / tab(+child) / background
- 加えて `register_block_style('core/list', …)` で **コアブロックのスタイル 2 種**
  （`themeA-checkmark` / `themeA-checkmark-square`）を登録
  （初回調査の「register_block_style 0」は複数行記法による grep 漏れ。訂正）
- ブロックへ渡す環境値は `wp_localize_script` の `THEMEA_VAR`（プロフィール・SNS URL・パーマリンク構造・記事カラム等）に集約 → **ブロックの描画がテーマ設定値に強結合**

### 2.2 ショートコード（6 種）
`themeA_button` / `themeA_fukidashi` / `themeA_heading_iconbox` / `themeA_profile` / `themeA_simple_iconbox` / `message`

### 2.3 ウィジェットエリア（11）
`sidebar` / `sidebar-tracking`（追尾）/ `post-top-widget` / `post-start-widget` / `post-end-widget` / `post-bottom-widget` / `relatedpost-bottom-widget` / `toppage-widget` / `footer-widget` / `hamburger-widget` ほか
→ **記事内の広告・CV 挿入位置がウィジェットエリアとして仕様化されている**

### 2.4 メニュー（3）
`glonavi` / `hamburger` / `footer-menu`

### 2.5 表示部品（`object/` 22 ファイル）
breadcrumb / cvbutton / hamburger / header-layout-left / header-parts / informationbar / logo /
main-visual（stillimage・image-slider・movie・post-slider・post-slider-themeA）/ new-post-list /
nextpage / paidpost-popup / related-post / sidepr / sns-share(+selected) / spmenu / time

## 3. 設定・データの持ち方

| 項目 | 実測 |
|---|---|
| オプションキー | **`themeA_*` 個別キーが 1,225 種**（単一配列にまとめない設計） |
| カスタマイザ | `include/customizer/` 162 ファイル（`controls/` に独自コントロール群）、`add_setting` を含むファイル 18 |
| add_theme_support | `automatic-feed-links` / `menus` / `post-thumbnails` / `title-tag` の 4 つのみ |
| CSS カスタムプロパティ | 151 種（`--cv-button` `--fukidashi-*` `--compare-*` `--header-style-*` など**部品の見た目そのものが変数化**） |
| 構造化データ | `include/json-ld.php`（344 行）。出力型は `Organization` / `Person` / `ListItem` / `ImageObject` |
| 拡張点 | 自前 `do_action` 3・`apply_filters` 1 → **外部からの介入点がほぼ無い** |
| REST | 独自ルート **2 本**（`themeA/post_by_url` / `themeA/external_url`、いずれも `permission_callback => __return_true`）+ `rest_api_init` 経由の PV orderby 拡張 2 本 |
| CPT / タクソノミ | **0**（再利用パーツは番号スロット型 shortcode + テーマオプションで保持） |
| 決済 | `vendor/stripe` 286 ファイル（`themeA-blocks/paidpost` = 有料記事機能） |

## 4. 実使用（site-A.example 公開記事 59 本 + 固定 10 本の実測）

| ブロック | 使用数 | | ブロック | 使用数 |
|---|---|---|---|---|
| core/paragraph | 6,293 | | themeA-blocks/comparechild | 177 |
| core/heading | 1,540 | | themeA-blocks/compare | 59 |
| core/list-item | 715 | | flexible-table-block/table | 46 |
| **themeA-blocks/simplebox** | **697** | | themeA-blocks/postlist | 38 |
| **themeA-blocks/button** | **339** | | themeA-blocks/designtitle | 37 |
| **themeA-blocks/blogcard** | **330** | | themeA-blocks/background | 33 |
| core/image | 324 | | richmenuchild / accordionchild | 16 / 15 |
| **themeA-blocks/fukidashi** | **186** | | 他 13 種 | 各 1〜10 |

ショートコード実使用: `[themeA_fukidashi]` 186・`[smartslider]` 1・`[themeA_profile]` 1・`[themeA_heading_iconbox]` 1・`[contact]` 1
投稿タイプ実態: post 59 / page 10 / attachment 232（**CPT 無し**）

## 5. 構造的性格（要約）

1. **設定駆動のクラシックテーマ**。1,225 個の個別オプション + 162 ファイルのカスタマイザが実質の「正本」で、テンプレートはそれを読むだけ。
2. **ブロックは 25 種あるが疎結合ではない** — 単一 editor バンドルと `THEMEA_VAR` に依存し、テーマ外へ切り出せる形になっていない。
3. **広告・CV 位置はウィジェットエリアとして明示的**（post-top/start/end/bottom・relatedpost-bottom・sidebar-tracking）。ここが agent-neo の `ad-zone.schema.json` が既に参照している 4 ゾーンの出どころ。
4. **拡張点（フック）がほぼ無い** → 外部エージェントからの制御は実質「オプション書き換え」しか経路が無い。
   REST は 2 本あるがブロック描画のための内部用途で、操作 API ではない（詳細は `10-reverse-themeA.md`）。
