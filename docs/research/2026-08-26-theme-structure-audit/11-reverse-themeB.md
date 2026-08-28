# リバースエンジニアリング B — テーマB

調査日 2026-08-26 / ホスティング SSH 読み取り専用 / 対象 `themes/themeB`（PHP 45,622 行）

## 1. 起動シーケンス

`functions.php` は**薄い**。実体は 1 クラス + 順序付き require。

```php
require lib/check_environment.php     // 動作環境チェック（先頭）
define T_DIRE / S_DIRE / T_DIRE_URI / S_DIRE_URI
require lib/autoloader.php            // spl_autoload_register
require lib/polyfill/php_functions.php

class THEMEB_Theme extends \THEMEB_Theme\Theme_Data {
    use Utility\Get, Utility\Attrs, Utility\Balloon, Utility\Parts, Utility\Status, Utility\Others;
    public function __construct() {
        self::data_init();                 // ① 設定を DB から読み、静的プロパティへ確定
        require theme_setup / define_const / load_files / customizer / post_type / taxonomy
              / custom_menu / widget / tiny_mce / output / gutenberg / post_meta / term_meta
              / shortcode / pluggable / pluggable_parts / overwrite / content_filter
              / rest_api / hooks / rewrite_html;
        if ( current_user_can('manage_options') ) require activate / update / updated_action;
        if ( is_admin() ) { require notice; new Meta_User(); new Admin_Menu(); }
    }
}
new THEMEB_Theme();
```

**観測点**
- **設定の確定（`data_init`）が最初**。以降のすべてのモジュールが確定済み設定を前提にできる。
  テーマA が「描画中にアクセサを呼び、必要なら DB へ書く」のと対照的。
- 管理者判定・管理画面判定で**読み込む範囲を絞る**（更新チェッカやメニュー生成はフロントに載らない）。
- オートローダは `THEMEB_Theme` / `THEMEB_` / `ベンダーB_` を名前で判定して `classes/` へ解決。
  `ベンダーB_` が残っているのは旧ブランド（`themeB/` ブロック名前空間と同じ由来）。

## 2. 設定レイヤ — 4 グループ + 独自テーブル

`classes/Theme_Data.php`:

```php
const DB_NAMES = [
  'customize' => 'themeB_customizer',   // カスタマイザ由来
  'options'   => 'themeB_options',     // 設定画面由来（既定 540 キー）
  'editors'   => 'themeB_editors',     // エディタ設定
  'others'    => 'themeB_others',
];
const DB_TABLES = [ 'balloon' => 'themeB_balloon' ];   // ← 独自テーブル
```

- 設定は**単一配列 4 本**に集約。既定値は `classes/Data/Default_Settings.php`（667 行）が持ち、
  実値とマージして `THEMEB_Theme::$setting` / `$customize` / `$options` / `$editors` に載る。
  → **設定の総体が 1 ファイルで列挙できる**（テーマA には無い性質）。
- ふきだし（balloon）だけ **専用の DB テーブル**を持つ。`Utility/Balloon.php` に
  「プレフィックスなし旧テーブル → プレフィックスあり新テーブル」の移行判定があり、
  テーマがスキーママイグレーションを抱えている。
- post meta / term meta は `lib/post_meta/`（5 ファイル）・`lib/term_meta.php` に分離。
  タームメタ `themeB_term_meta_display_parts` はタームへ「表示するブログパーツ」を紐づける。

## 3. CSS 生成 — アキュムレータ + モジュール分離

`classes/Style.php`（657 行）は**文字列アキュムレータ**。

```php
public static $root_styles = ['all'=>'','pc'=>'','sp'=>'','tab'=>'','mobile'=>''];
public static $styles      = [ … 同じ 5 バケット … ];
public static $modules     = [];   // 別ファイルへ分離した CSS
public static $ex_css      = '';   // 直書き

Style::add_root( $name, $val, $media_query );          // CSS 変数
Style::add( $selectors, $properties, $media_query, $branch ); // 通常宣言
Style::add_post_style( $selectors, $properties, $mq, $branch ); // 本文用。front/editor でセレクタを自動切替
Style::add_module( $filename );
Style::generate_css() / get_front_css() / get_editor_css() / get_nocache_css()
```

- **メディアクエリがバケットとして一級**。呼び出し側は `'sp'` を渡すだけで、
  最終出力時にまとめて `@media` に包まれる。テーマA がブレークポイントを 1 関数内の
  ローカル変数に埋めているのと対照的。
- `$branch`（`'editor'` / `'front'` / `'both'`）で**同じ宣言をエディタとフロントへ出し分ける**。
  `add_post_style()` はセレクタ側も自動で `.post_content` ↔ `.mce-content-body, .editor-styles-wrapper` に切替。
  → **エディタとフロントの見た目一致がアーキテクチャで担保されている。**
- `classes/Style/` 配下に 11 の生成器（Body / Color / Header / Footer / Post / Post_List / Top / Page /
  Widget / Editor / Others）。責務がファイル分割されている。
- `get_nocache_css()` / `get_nocache_modules()` があり、キャッシュ有無で経路が分かれる。

## 4. CSS 分離の中核 — 2 パス・ドライラン描画

`THEMEB_Theme::is_separate_css()` が真のとき、**「使われているブロックだけ CSS を読む」**ために
`wp_head` の優先度 0 で `Pre_Parse_Blocks::init()` が走る（`lib/load/separate.php`）。

```
wp_head(0)
└ Pre_Parse_Blocks::init()
   ├ add_filter('render_block', render_check)      … 描画されたブロック名を記録
   ├ 本文: parse_blocks( do_shortcode( $post->post_content ) ) を再帰走査
   │   └ themeB/blog-parts / core/block は参照先 post を取得して**中身も再帰**
   ├ ターム: term_meta themeB_term_meta_display_parts の参照先を走査
   ├ ウィジェット: ob_start() → 各ウィジェットエリアを実際に出力 → ob_clean() で捨てる
   │   （サイドバー・front_top/bottom・single_top/bottom/cta・before/after_related・
   │     footer_box1-3・sp_menu_bottom・head_box を総当たり）
   ├ 文字列直検査: [ad_tag / [ふきだし / [speech_balloon / cap_box / [full_wide_content /
   │               [カスタムバナー / <table を含むかで used_blocks を補完
   └ ページ種別で補完（トップのピックアップバナー、アーカイブの tab など）
```

- 結果は静的プロパティ `THEMEB_Theme::$used_blocks` に溜まり、以後の CSS 出力判断に使われる。
- **コアブロックは `wp_enqueue_style("wp-block-{$name}")` をこの場で呼ぶ**。
  `parse_blocks()` だけだとコア CSS がフッターに落ちるため、と実装コメントに明記。
- サイドバーだけ別プロパティ（`$sidebar_blocks`）に集めてからマージ。コメントに
  「キャッシュもできるように別処理にしている」とあり、キャッシュ前提の設計余地を残している。

**この仕組みは「本文の意味構造を描画前にサーバー側で把握している」ということ**で、
中間 JSON パイプラインの発想と近い。既に `do_shortcode` → `parse_blocks` → 再帰展開まで通っている。

## 5. ブロック実装

### 5.1 登録
```php
register_themeB_blocks()  // init
├ register_normal_blocks()   … 22 種を THEMEB_Theme::register_block() で
└ register_dynamic_blocks()  … 10 種は lib/gutenberg/block/{name}.php を require
```

`THEMEB_Theme::register_block()`（`Utility/Others.php`）:
- `index.asset.php`（wp-scripts 生成）から依存とバージョンを読み、`themeB/{name}` ハンドルで JS 登録。
  依存に `themeB_blocks`（共通バンドル）を必ず足す。
- `index.css` があればエディタスタイルとして登録。
- 最後に **`register_block_type_from_metadata()`** で `block.json` から登録。
  → 属性・supports・スタイルの定義は **block.json が正本**。PHP は配線だけ。

### 5.2 動的ブロックの中身（`post-link.php` の例）
```php
\THEMEB_Theme::register_block('post-link', ['render_callback' => __NAMESPACE__ . '\cb']);
function cb( $attrs ) { … }
```
- **属性のバージョン移行がコード内に明示**されている:
  `linkData`（v2 以降: `id` / `url` / `kind` / `type`）があればそれを使い、
  無ければ v1 の `postId` / `externalUrl` にフォールバック。
  → 保存済みコンテンツの後方互換をブロック側で吸収する設計。
- 表示上書きは `$card_args` という**連想配列 1 個に集約**してからカード生成関数へ渡す。
  テーマA のようにクラス名文字列を継ぎ足す方式ではない。

### 5.3 コアブロックへの干渉
`lib/gutenberg.php` が WordPress コアの挙動を上書きする:
- `remove_filter('render_block', 'wp_render_layout_support_flag')` した上で自前ラッパを追加し、
  `core/columns` / `core/column` に `wp-container-*` を付けない。`core/group` は layout 設定がある時だけ通し、
  flex なら `is-stack` / `is-row` クラスを注入。
- `wp_theme_json_data_default` フィルタで `core/image` の lightbox を無効化
  （**theme.json ファイルを持たないのに theme.json データ層へ介入している**）。
- ウィジェット内 `core/group` の `<h2>` を `<div class="c-widget__title">` へ正規表現置換。

## 6. 本文パイプライン（`lib/content_filter.php`）

`wp_loaded`（優先度 20）で組み立て、フックは **優先度 12**（ショートコード展開 11・
動的ブロック展開 9 より後）に揃えられている。

| 変換 | 対象フック | 条件 |
|---|---|---|
| oEmbed 自動埋め込み | the_content / widget_text / widget_text_content / widget_block_content | 常時 |
| ショートコード展開（カスタム HTML ウィジェット） | widget_text | 常時 |
| 目次挿入 `add_toc` | the_content ほか | `wp_head`(99) で後付け登録 |
| 空 `<p>` 除去 | 4 フック | `remove_delete_empp` が偽 |
| lazysizes 変換 | 4 フック | `$lazy_type === 'lazysizes'` |
| URL の自動ブログカード化 | the_content | `apply_filters('themeB_remove_url_to_card', …)` で無効化可 |

**目次の実装**が示す設計:
- ショートコード `[themeB_toc]` は本文に `<div class="themeB-toc-placeholder"></div>` を置くだけ。
  後段の `add_toc()` がそれを実体（`p-toc` + 目次広告）へ置換する。**プレースホルダ方式**。
- プレースホルダが無ければ `preg_match('/^<h2.*?>/im')` で**最初の h2 の前**へ挿入。
  ページ 2 以降（`get_query_var('page') > 1`）はコンテンツ先頭へ。
- 二重生成防止に `テーマB::$added_toc` フラグ。
- 目次広告の前後配置は `toc_ad_position` 設定で切替。

**lazysizes 変換**は `<iframe>` / `<img>` / `<video>` を正規表現でコールバック置換し、
`src`→`data-src`、`srcset`→`data-srcset`、`<noscript>` 退避、`lazyload` クラス付与、
アスペクト比の補完（`set_aspectratio()`）まで行う。YouTube 埋め込みだけは
`loading="lazy"` へ切り替える例外処理付き（エラー 153 回避のコメントあり）。

**REST 経路の除外が明示**されている: `テーマB::is_rest()` / `is_iframe()` を見て、
サーバーサイドレンダーや `wp-json/wp/v2` 経由では変換を通さない。
→ **「保存されている HTML」と「表示される HTML」を意図的に分けている。**

## 7. 出力全体の書き換え（`lib/rewrite_html.php`）

`delay_js` 設定が有効なとき、`wp` フックで **`ob_start()` を掛けてページ HTML 全体をバッファし**、
`rewrite_lazyload_scripts()` で script タグを遅延読み込みへ書き換える。
- GET 以外は素通し、非 HTML（`<?xml` 等）も素通し。
- `delay_js_prevent_pages` で除外ページを設定可能。

## 8. REST

`lib/rest_api/` に 14 ルート。**すべて `wp/v2` 名前空間へ相乗り**:
`/themeB-block-settings` `/themeB-term-list` `/themeB-balloon`(+copy/recover/sort)
`/themeB-ct-ad-data` `/themeB-ct-btn-data` `/themeB-ct-pv` `/themeB-reset-ad-data`
`/themeB-reset-cache` `/themeB-reset-settings` `/themeB-do-update-action` `/themeB-lazyload-contents`

- 用途は**管理系が中心**（設定取得・キャッシュ削除・ふきだし CRUD・広告クリック計測データ）。
- `themeB-lazyload-contents` はコンテンツ遅延読み込み用で、`is_rest('lazyload')` の分岐と対になっている。
- 自前名前空間（`themeB/v1`）を切らずコアの `wp/v2` に足しているため、**コアや他プラグインとの
  ルート衝突リスクを構造的に抱える**。

## 9. 拡張点

- 自前 `apply_filters` **79** / `do_action` 5。
- `lib/pluggable.php` / `lib/pluggable_parts.php` — **子テーマから関数を差し替える前提**の
  `function_exists()` ガード付き定義群（テーマA にはこのガードが無い）。
- `lib/overwrite.php` — 設定の上書き処理を 1 箇所に集約。

→ **外部から介入する正規の口が用意されている。**

## 10. 総括（機構レベル）

| 観点 | 実態 |
|---|---|
| 抽象化 | クラス + trait + 名前空間 + オートローダ。責務がファイル分割されている |
| 設定 | 単一配列 4 グループ + 既定値 1 ファイル（540 キー）+ 独自テーブル 1 |
| CSS | アキュムレータ + メディアクエリのバケット + front/editor 出し分け + モジュール分離 |
| 描画前解析 | **2 パスのドライラン**で使用ブロックを収集（`Pre_Parse_Blocks`） |
| ブロック | block.json が正本。共通バンドル + ブロック別 JS。属性のバージョン移行を実装 |
| 本文変換 | 優先度 12 に揃えたパイプライン。REST 経路は意図的に除外 |
| コア干渉 | render_block / theme_json / layout support flag を上書き |
| 拡張点 | filter 79 + pluggable 関数群 |
| 移植性 | **block.json + render_callback の単位で切り出せる**。契約化しやすい |
