# リバースエンジニアリング A — JIN:R 1.4.6

調査日 2026-08-26 / XServer SSH 読み取り専用 / 対象 `themes/jinr`（PHP 33,931 行・vendor 除く）

## 1. 起動シーケンス

`functions.php`（999 行）が唯一のエントリ。**クラスも名前空間もオートローダも無い**。
すべてグローバル関数 + `require` / `get_template_part` の直列実行。

```
functions.php
├── define: JINR_THEME_VERSION / JINR_PHP_INCLUDE / JINR_CORE_DIR
├── add_action(wp_enqueue_scripts) × 3   … jQuery をフッターへ再登録 / JS / CSS
├── add_action(admin_enqueue_scripts) × 3
├── add_action(wp_footer)  … style-footer.css を遅延出力
├── require_once include/customizer.php          ← カスタマイザ UI 17 ファイルを foreach で require
├── require_once include/load-customizer-value.php ← 動的 CSS 生成器（後述）
├── get_template_part include/widgets           … register_sidebar 11
├── get_template_part include/shortcode         … add_shortcode 6
├── get_template_part include/jinr-setting      … 管理画面（2,692 行）
├── get_template_part include/head/title
├── get_template_part include/font-selection
├── get_template_part include/json-ld           … 条件付き
├── get_template_part include/custom-functions  ← 本体（5,214 行）
├── add_action('init', create_block_jinr_blocks_block_init)  … ブロック 25 種を一括登録
├── add_filter('redirect_canonical', …)
├── add_action('template_redirect', jinr_init_session_start)  … PHP セッション開始
└── require theme-update-checker.php
```

**観測点**
- `get_template_part` を「ファイルを読み込む」目的で使っている（テンプレート出力用の API を初期化に流用）。
- `template_redirect` で `session_start()` 相当を呼ぶ（有料記事機能のため）。ページキャッシュと相性が悪い。
- 依存関係が暗黙。`load-customizer-value.php` は `customizer/ui/*.php` が定義するアクセサに依存するが、
  それを保証しているのは require の**行順だけ**。

## 2. 設定レイヤ — 707 個の手書きアクセサ

`include/customizer/ui/*.php` 17 ファイルが、**カスタマイザのコントロール登録と値のゲッターを同一ファイルに同居**させている。

| ファイル | `function jinr__*` の数 |
|---|---|
| button-design-setting.php | 181 |
| fukidashi-setting.php | 121 |
| main-visual-setting.php | 80 |
| spmenu-setting.php | 75 |
| site-design-setting.php | 53 |
| box-design-setting.php | 49 |
| color-setting.php | 47 |
| 他 10 ファイル | 101 |
| **合計** | **707** |

つまり設定 1 項目につき「コントロール定義 + 専用ゲッター関数」を人手で 1 対 1 に書いている。
スキーマも型定義も無く、**設定の総体を機械的に列挙する手段が存在しない**
（`jinr_*` 個別オプションキーは 1,225 種、post meta は `_jinr_*` 27 種）。

## 3. 動的 CSS 生成 — 単一 2,098 行関数

`include/load-customizer-value.php` は **関数 1 個だけ**のファイル。

```php
function jinr_customize_inline_style() { … 2,098 行 … }
add_action('wp_head',    'jinr_customize_inline_style');
add_action('admin_head', 'jinr_customize_inline_style');
```

- 冒頭で 700 近いアクセサを一度に呼び、`jinr_hex_to_rgb()` / `jinr_hex_to_hsl()` で派生色を計算
  （hue rotate +30 / +45、明度 +9 などをハードコード）。
- ブレークポイントは関数内のローカル変数（`sp: max-width 551px` / `mini_tablet: 552` / `tablet: 782` /
  `pc_view: 961` / `pc: 1340`）。**外部から参照も上書きもできない。**
- 生成した CSS 変数は 151 種。`--cv-button` `--fukidashi-*` `--compare-*` `--header-style-*` のように
  **意味的トークンではなく「部品の見た目そのもの」**が変数化されている。

### 3.1 描画パスでの DB 書き込み（要注意）
同関数の中で **`set_theme_mod()` を 5 箇所**呼び、値が未設定なら既定色を **その場で DB へ書き込む**。

```php
if (jinr__theme_color() == false) { set_theme_mod('jinr__theme_color', '#407FED'); }
```

`wp_head` は読み取り専用であるべき描画パス。ここに副作用があるため:
- 未設定サイトへの初回アクセスが DB write を誘発する（同時アクセス時の競合）。
- **読み取りだけのつもりのクロールが設定を確定させる**。移管・複製時に「いつの間にか値が入っていた」が起きる。

## 4. 描画フロー

`single.php`（220 行）は PHP と HTML を直接混在させたテンプレート。

```
get_header()
 └ [widget] post-top-widget
 <main id="mainContent" class="<?= jinr_mainContent_class_insert() ?>">
   <article id="jinrArticle">
     <header id="postHeader">     … 日付/カテゴリ/PR表記/タイトル/アイキャッチ(YouTube 差し替え可)
     <section id="postContent">
       ├ PR 表記（記事冒頭）
       ├ [widget] post-start-widget
       ├ the_content()            ← ここに後段フィルタが刺さる
       ├ object/nextpage
       ├ object/sns-share-selected
       ├ [widget] post-end-widget
       …
 get_footer()
```

- クラス名は `jinr_*_class_insert()` 系のグローバル関数が `echo` する（戻り値でなく直接出力）。
- 表示可否は `get_post_meta($post->ID, '_jinr_*_display')` と `jinr__*()` の**二重条件**。
  記事単位のメタが空文字のときだけカスタマイザ値を見る、という規約が各所にベタ書きされている。
- PR 表記（ステマ規制対応）はカテゴリ ID の除外リストを `explode(",", …)` で毎回パースして判定。

### 4.1 `the_content` に刺さるフィルタは 3 本だけ
```
wrap_iframe_in_div            （custom-functions.php:1250）
jinr_paid_content_display_switch  優先度 9（有料記事の本文差し替え）
jinr_h2_ads_concert           （h2 前広告挿入）
```
SWELL と違い**目次・遅延読み込み・ブログカード化のパイプラインは持たない**
（目次は外部プラグイン RTOC、遅延読み込みは EWWW など別プラグインに委ねている）。

## 5. h2 前広告の実装（`ad-zone.schema.json` CARRY-A2-001 の実体）

`jinr_h2_ads_concert()`（custom-functions.php 4317-4523、約 200 行）。

処理の骨格:
1. 記事メタ `_jinr_ads_display == '1'` なら中断（記事単位のオプトアウト）。
2. `is_single() && post_type === 'post'` に限定。
3. 付与カテゴリを **term_id 昇順でソートし先頭を採用**（複数カテゴリ時の決定規則がこれだけ）。
4. **4 スロット固定**のカテゴリ別上書き（`jinr_choise_category_1` 〜 `_4`）を解決する。
   親カテゴリの有無で分岐し、`get_term_children()` + `array_unshift` + `get_ancestors()` +
   `array_intersect` / `array_diff` を組み合わせて「どのスロットが勝つか」を決める。
5. 勝ったスロットの `jinr_h2_ads_code_$i` / `jinr_h2_sponsor_text_$i` / `jinr_h2_sp_display_$i` を読む。
6. 広告 HTML を組み立て、`preg_replace('/<h2.*?>/i', $ad . $tag, $content, 1)` で**最初の h2 の直前に注入**。

**実装上の危うさ**
- PHP の**可変変数**（`${'cat_array_id_0' . $num}`、`${"merge" . $num}`、`${"exist1_" . $m}`）を多用。
  静的解析が効かず、スロット数を増やす拡張はコードを書き足す以外に無い。
- 「4 スロット」は仕様ではなく**ループ上限のハードコード**（`for ($num = 1; $num <= 4; $num++)`）。
- 挿入位置は正規表現による HTML 文字列操作。h2 が無い記事には広告が出ない。
- 広告コードは `get_option()` の生 HTML をそのまま出力（エスケープなし。管理者入力前提）。

> `ad-zone.schema.json` の「JIN:R の 4 ゾーン（h2 前挿入 / 記事終 / 関連上 / カテゴリ別上書き）」は、
> 正確には **「h2 前挿入」1 ゾーン + そのカテゴリ別上書き 4 スロット**という構造。
> 記事終・関連上はこの関数ではなく `ad-finish.php` / `ad-related.php` とウィジェットエリアが担う。
> スキーマの前提とコードの実体がずれている。

## 6. ブロック実装

### 6.1 登録
`create_block_jinr_blocks_block_init()`（functions.php:950 で init にフック）が
**25 種を PHP から手書きで `register_block_type()`**。block.json は使わない。

全ブロックが**同一の editor script / editor style を共有**する:
```php
'editor_script' => 'jinr-blocks-script',   // editor/build/index.js（単一バンドル）
'editor_style'  => 'jinr-blocks-editor-style', // block.css
```
環境値は `wp_localize_script('jinr-blocks-script', 'JINR_VAR', [...])` で一括注入
（プロフィール・SNS URL・お問い合わせ URL・パーマリンク構造・記事カラム設定・サムネイル方針など）。

**含意**: ブロックはテーマ設定と密結合で、1 種だけ切り出しても動かない。
エディタ側の実装は 1 本の minified バンドルにまとまっており、ブロック単位の分離点が無い。

### 6.2 動的ブロックの中身（`jinr_blog_card_dynamic_render_callback` の例）
- 属性は `postUrl` / `postTitle` / `thumbnailUrl` / `blogcardDesign` / `blogcardType` /
  余白 4 種（PC/SP × 上下）/ `displayDeviceAttribute` / `className` / `jinrBlocksCSSAttribute`。
- **未指定属性はカスタマイザ値へフォールバック**（`?: jinr__blogcard_design()`）
  → 同じ保存内容でもサイト設定が違えば出力が変わる。決定論レンダリングに直接抵触。
- 余白などの詳細設定は**クラス名の文字列連結**で表現（`$detail_setting .= …`）。
- `jinrBlocksCSSAttribute` があると `<style jsx="true">…</style>` を**ブロック単位でインライン出力**。
- サムネイルは `-320x180` サフィックスを付けたファイルの `file_exists()` を見て差し替える
  （**レンダリング中にファイルシステムを触る**）。
- 内部リンク時は `new WP_REST_Request('GET', '/jinr/post_by_url')` → `rest_do_request()` で
  **自分自身の REST を内部ディスパッチ**して記事情報を取る。

## 7. REST — 2 本ある（前回の「0 本」を訂正）

複数行の `register_rest_route(` だったため初回の単一行 grep で漏れていた。実際は 2 本:

| 名前空間 / ルート | メソッド | permission_callback | 実装 |
|---|---|---|---|
| `jinr/post_by_url` | GET | `__return_true` | `url_to_postid()` → タイトル・サムネ・カテゴリを返す |
| `jinr/external_url` | GET | `__return_true` | 任意 URL を `file_get_contents()` して og: メタを正規表現抽出 |

加えて `rest_api_init` に 2 本（`slug_register_views_orderby` / `jinr_slug_register_views`）が刺さり、
PV 数によるオーダーバイを REST に追加している。

### 7.1 セキュリティ上の指摘（`jinr/external_url`）
- **認証なし**（`permission_callback => '__return_true'`）で、クエリ `url` を検証せず
  `file_get_contents()` に渡している。典型的な **SSRF** の形。
  内部ネットワークやクラウドのメタデータエンドポイントへサーバーを踏み台にできる形状で、
  取得結果は正規表現抽出の上でレスポンスに載る。
- `post_by_url` も未認証で、非公開記事の存在推定に使える余地がある。

> 本調査は読み取りのみで、**上記の到達性検証（実際のリクエスト送出）は行っていない**。
> 対処は WAF/プラグイン側の遮断か、テーマ側で `permission_callback` の厳格化 +
> スキーム/ホスト許可リスト + `wp_safe_remote_get()` への置換。実サイトの話なので優先度は高い。
> → THEME-INV-13 として切り出し。

## 8. データモデル

| 種別 | 実測 |
|---|---|
| options | `jinr_*` 個別キー 1,225 種（配列にまとめない） |
| theme_mod | `jinr__*` 系（アクセサ 707 個が対応） |
| post meta | `_jinr_*` 27 種（表示制御 15・YouTube 5・SEO 4・有料記事 2 ほか） |
| CPT / タクソノミ | **なし** |
| セッション | `template_redirect` で開始（有料記事） |
| 外部依存 | Stripe SDK 同梱（vendor/stripe 286 ファイル）・Swiper を CDN から直読み |

**再利用パーツに CPT を使わない**ため、パーツの実体はオプション値（番号スロット）に埋まる。
REST から一覧・取得する経路が無い。

## 9. 拡張点

- 自前 `do_action` 3 / `apply_filters` **1**。
- 上書きの唯一の現実的手段は「子テーマで同名関数を先に定義」…も不可
  （`function_exists()` ガードが無いため**再定義すると fatal**）。
- 実サイトの `jinr-child` は親 CSS を enqueue するだけの 6 ファイルで、実質カスタマイズしていない。

→ **外部エージェントから制御する経路は事実上「オプションを書き換える」しか無い。**

## 10. 総括（機構レベル）

| 観点 | 実態 |
|---|---|
| 抽象化 | ほぼ無い。グローバル関数 + 直列 require |
| 設定 | 手書きアクセサ 707・個別オプション 1,225。スキーマなし |
| CSS | 単一 2,098 行関数が `wp_head` で全生成。副作用として DB write |
| ブロック | 25 種を PHP 一括登録・単一 JS バンドル・環境値注入で強結合 |
| 決定論 | 属性未指定時にサイト設定へフォールバックするため**同一入力でも出力が変わる** |
| 拡張点 | filter 1 本。実質クローズド |
| REST | 2 本（うち 1 本は未認証 SSRF 形状） |
| 移植性 | ブロック単位の切り出しは不可能に近い。**出力マークアップの再現**が現実的な方針 |
