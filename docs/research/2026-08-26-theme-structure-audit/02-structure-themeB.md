# 構造調査 B — テーマB（site-B.example）

- 調査日: 2026-08-26 / 手段: ホスティング SSH 読み取り専用 + WP-CLI 読み取りクエリ
- 対象: `~/site-B.example/public_html/wp-content/themes/themeB`
- テーマ: テーマB / 子テーマ **無し**（親を直接運用）
- 書き込み: なし

## 1. 物理構造

| 項目 | 値 |
|---|---|
| サイズ / ファイル数 | 19MB / 747 |
| 拡張子内訳 | php 331・css 137・js 100・json 67・scss 35・po/mo 各 14・svg 12・フォント各種 |
| ルート直下 | テンプレート階層 + `single-lp.php` / `archive-term.php` / `author.php` + `build/` `src/` `classes/` `lib/` `parts/` `assets/` `languages/` |
| 主要ディレクトリ | `build/`(268: css 96・gutenberg 137・js 23・icons 10)・`lib/`(187: customizer 34・gutenberg 19・update 69・hooks 5・rest_api 1・post_meta 5)・`classes/`(61)・`parts/`(54)・`src/`(92: gutenberg 71) |
| theme.json | **無し**（クラシックテーマ + Gutenberg ブロック実装） |
| ビルド | `sass-builder.js` + `src/` → `build/` の自前ビルドパイプラインを同梱 |

## 2. パーツ機構

### 2.1 Gutenberg ブロック（`themeB/` 名前空間・block.json 実装 50 種）
`src/gutenberg`(71) → `build/gutenberg`(137)。**block.json による正式登録**で、フォーマット系（インライン）とブロック系が同居。

- 構造系: `columns` `column` `dl` `dt` `dd` `dl-dt` `dl-dd` `tab` `tab-body` `accordion(+item)` `step(+item)` `faq(+item)` `list` `link-list(+item)` `box-menu(+item)`
- 表現系: `balloon` `cap-block` `button` `inline-btn` `banner-link` `review` `mini-note` `style-block` `full-wide`
- 動的・機能系: `post-link` `post-list` `blog-parts` `ad-tag` `rss` `restricted-area` `ab-test(+a/+b)`
- インライン書式: `marker` `font-size` `text-color` `bg-color` `nowrap` `inline-icon` `inline-tools` `text-centered` `clear`
- コア拡張: `core/heading` `core/paragraph` に block.json で属性追加

### 2.2 ショートコード（20 種・**日本語エイリアス有り**）
`ad` `ad_tag` `blog_parts` `custom_banner` `full_wide_content` `html` `icon` `only_login` `only_logout`
`pcbr` `spbr` `post_link` `post_list` `pr_notation` `review_stars` `speech_balloon` `themeB_toc`
+ 日本語別名 `ふきだし` `アイコン` `カスタムバナー` `ブログパーツ`

### 2.3 カスタム投稿タイプ（3・**すべて `show_in_rest: true`**）
| CPT | 用途 | public | supports |
|---|---|---|---|
| `lp` | LP 専用（`single-lp.php`） | true（検索除外） | title/editor/thumbnail/author/revisions/custom-fields |
| `blog_parts` | **再利用パーツ正本** | false | title/editor |
| `ad_tag` | 広告タグ正本 | false | title/editor |

いずれも `capability_type` を専用化し、`remove_*` オプションで無効化可能。

### 2.4 ウィジェットエリア（24）
`sidebar-1` `sidebar_top` `sidebar_sp` `fix_sidebar` / `single_top` `single_bottom` `single_cta`
`before_related` `after_related` / `page_top` `page_bottom` / `front_top` `front_bottom`
`head_box` `footer_box1-3` `footer_sp` `sp_menu_bottom` `before_footer` ほか

### 2.5 メニュー（6）
`header_menu` `sp_head_menu` `nav_sp_menu` `footer_menu` `fix_bottom_menu` `pickup_banner`

### 2.6 テンプレートパーツ（`parts/` 54 ファイル）
`header/`(7) `footer/`(6) `single/`(10) `post_list/`(16) `top/`(7) `archive/`(1) + breadcrumb / profile_box / sidebar_content / icon_list / page_head / home_content / top_title_area

## 3. 設定・データの持ち方

| 項目 | 実測 |
|---|---|
| オプション | **単一配列 `themeB_options` 方式**（`classes/Data/Default_Settings.php` に既定 540 キー）+ `themeB_block_settings` `themeB_version` 等の少数 |
| カスタマイザ | `lib/customizer/`(34) + `classes/Customizer/`(10・独自 Control 7 種) |
| 管理画面 | `classes/THEMEB_THEME/Menu/` にタブ 8 枚（Colors / Btn / Balloon / Border / Iconbox / Marker / Custom / Others）= **テーマ独自の設定 UI** |
| add_theme_support | 10（`align-wide` `editor-color-palette` `custom-line-height` `custom-units` `responsive-embeds` `html5` `widgets` ほか） |
| CSS カスタムプロパティ | 155 種。`classes/Style/`(11 ファイル: Body/Color/Header/Footer/Post/Post_List/Top/Page/Widget/Editor/Others)で **設定値から動的に CSS を生成** |
| 構造化データ | `classes/Json_Ld.php`（472 行）。`Article` `WebSite` `WebPage` `CollectionPage` `BreadcrumbList` `SearchAction` `Organization` `Person` `ImageObject` `ListItem` |
| 拡張点 | 自前 `apply_filters` **79 種**・`do_action` 5 → **外部拡張を前提にした設計** |
| REST | `wp/v2` 名前空間に **14 ルートを間借り**（`/themeB-block-settings` `/themeB-term-list` `/themeB-balloon`(+copy/recover/sort) `/themeB-ct-ad-data` `/themeB-ct-btn-data` `/themeB-ct-pv` `/themeB-reset-*` `/themeB-do-update-action` `/themeB-lazyload-contents`） |
| ブロックパターン | `lib/gutenberg/block_pattern/` に `themeB-pattern/*` を登録（カテゴリ登録あり） |
| 目次 | **テーマ内蔵**（`themeB_toc` ショートコード + `lib/content_filter.php` で自動挿入） |
| その他 | `Pre_Parse_Blocks.php`（ブロック事前解析）・`lib/post_meta/`(5)・`lib/update/`(69) |

## 4. 実使用（site-B.example 公開記事 7 本の実測）

| ブロック | 使用数 |
|---|---|
| core/paragraph | 1,032 |
| core/heading | 287 |
| core/list-item | 261 |
| **themeB/dt / themeB/dd** | 各 106 |
| themeB/list | 67 |
| **themeB/faq-item** | 56 |
| themeB/dl | 30 |
| themeB/cap-block | 29 |
| **themeB/step-item** | 28 |
| themeB/step | 7 |
| themeB/post-link / themeB/faq | 各 6 |
| core/table | 3 |
| themeB/blog-parts | 2 |

投稿タイプ実態: post 16（公開 7）/ page 0 / attachment 9。ショートコード実使用 **0**
（= 装飾はすべてブロック経由。CPT `lp` / `blog_parts` / `ad_tag` の実データも現状 0）

## 5. 構造的性格（要約）

1. **ブロック・ファーストのクラシックテーマ**。block.json 50 種を自前ビルドで供給し、コア（heading/paragraph）も拡張する。
2. **再利用パーツと広告タグを CPT で正本化**（`blog_parts` / `ad_tag`、いずれも `show_in_rest`）→ **REST から機械操作できる**。テーマA の番号スロット shortcode と対照的。
3. **設定は単一配列 + 動的 CSS 生成**。トークンは PHP のスタイル生成器が持ち、静的ファイルではない。
4. **拡張点（filter 79）と REST 14 本**を持ち、外部からの介入余地が テーマA より桁違いに大きい。
5. ただし **REST を `wp/v2` 名前空間に相乗り**させており、名前空間設計としては借用（自前 `themeB/v1` を切っていない）。
