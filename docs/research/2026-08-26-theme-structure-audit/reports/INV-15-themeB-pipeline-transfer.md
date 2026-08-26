# THEME-INV-15 レポート — テーマB の解析・変換パイプラインの転用可否

- 対象イシュー: `issues/THEME-INV-15-themeB-pipeline-transfer.md`
- 状態: **①〜④ 判定完了 / ⑤（参照展開の中間 JSON 表現）は INV-04 と合流のうえ結論**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用（ソース全文読み出し）
- 一次証跡: `evidence/re-themeB-blocks.txt`（`Pre_Parse_Blocks` 全文）・
  `evidence/re-themeB-pipeline.txt`（`content_filter.php` 全文 + `Style.php` + `block_assets.php`）・
  `evidence/re-themeB-boot.txt`（`separate.php` / `gutenberg.php` / autoloader）

## 0. 判定サマリ

| # | 機構 | 判定 | 理由 |
|---|---|---|---|
| 1 | `Pre_Parse_Blocks` の 2 パス走査 | **転用可（中核）** | 「本文の意味構造を描画前に確定する」処理が動く形で存在。再帰展開・参照解決まで実装済み |
| 2 | `content_filter` の優先度設計 | **転用可（設計原則として）** | 3 レイヤ分離に直接写像できる。二重適用防止の実装パターンも有用 |
| 3 | `is_rest()` による保存/表示の分離 | **参考のみ（思想は採用）** | 判定条件が テーマB 固有。ただし「保存 HTML ≠ 表示 HTML」の原則は中間 JSON の前提そのもの |
| 4 | プレースホルダ方式の目次 | **転用可（表現方式として）** | 「後段で解決するノード」の中間 JSON 表現に流用できる |

---

## 1. `Pre_Parse_Blocks` の 2 パス走査 — 転用可（中核）

### 1.1 実装の実際（証跡: `evidence/re-themeB-blocks.txt`）

起動は `lib/load/separate.php` から。`init`（優先度 9）で条件を判定し、`wp_head`（優先度 0）で発火する。

```php
add_action( 'init', function() {
	if ( ! \THEMEB_Theme::is_separate_css() ) return;
	add_filter( 'should_load_separate_core_block_assets', '__return_true' );
	add_action( 'wp_head', __NAMESPACE__ . '\pre_parse_blocks', 0 );
	function pre_parse_blocks() {
		if ( \THEMEB_Theme::is_separate_css() ) {
			\THEMEB_Theme\Pre_Parse_Blocks::init();
		}
	}
}, 9 );
```

`Pre_Parse_Blocks::init()` の処理順:

```
1. add_filter('render_block', render_check)          … 描画されたブロック名を記録するフックを一時装着
2. ページ種別で入力を決める
   ├ is_single() / is_page() / (is_home() && !is_front_page())
   │    → get_post( get_queried_object_id() )->post_content
   └ is_term()
        → term_meta 'themeB_term_meta_display_parts' の参照先 post
3. parse_content( $content )
   ├ parse_blocks( do_shortcode( $content ) )        … ショートコードを先に展開してからパース
   ├ check_parsed_block() を全ブロックに適用（innerBlocks へ再帰）
   │    └ themeB/blog-parts の attrs.partsID / core/block の attrs.ref を辿り
   │      参照先 post の post_content を parse_content() で再帰処理
   └ check_content_str( $content )                   … 文字列直検査で補完
4. parse_widgets()                                    … 下記 1.2
5. remove_filter('render_block', render_check)        … フックを外す
6. ページ種別による補完（ピックアップバナー / アーカイブの tab）
```

### 1.2 ウィジェットのドライラン

ウィジェットは静的に読めないため、**実際に出力してから捨てる**。

```php
public static function parse_single_widget() {
	ob_start();
	\THEMEB_Theme::outuput_cta();
	\THEMEB_Theme::outuput_content_widget( 'single', 'top' );
	\THEMEB_Theme::outuput_content_widget( 'single', 'bottom' );
	\THEMEB_Theme::outuput_widgets( 'before_related' );
	\THEMEB_Theme::outuput_widgets( 'after_related' );
	ob_clean();          // ← 出力は捨てる。目的は render_block フックの発火だけ
}
```

対象エリアは `parse_sidebar` / `parse_front_widget` / `parse_page_widget` /
`parse_single_widget` / `parse_other_area` の 5 メソッドに分かれ、
合計で sidebar・front_top/bottom・single_top/bottom/cta・before/after_related・
footer_sp・footer_box1-3・before_footer・sp_menu_bottom・head_box を網羅する。

サイドバーだけは別プロパティ `$sidebar_blocks` に集めてから `array_merge` する。
コード中コメントに「**キャッシュもできるように別処理にしている**」とあり、
サイドバーは全ページ共通のためキャッシュ対象として切り出す設計余地を残している。

### 1.3 静的解析で取りこぼす分を文字列検査で補完

`parse_blocks()` では捕まらないショートコード記法を、生文字列の `strpos` で拾う。

```php
'[ad_tag'          → themeB/ad-tag
'[ふきだし' / '[speech_balloon' → themeB/balloon
'cap_box'          → themeB/cap-block
'[full_wide_content' → themeB/full-wide
'[カスタムバナー' / '[custom_banner' → themeB/banner-link
'<table'           → core/table
```

さらに `dynamic_sidebar` フックでレガシーウィジェットのクラス名を見て、
対応するコアブロック（`core/calendar` / `core/tag-cloud` / `core/latest-posts` /
`core/categories` / `core/archives`）へ写像する。

### 1.4 副作用として CSS をキューする

```php
public static function push_used_blocks( $block_name, &$list ) {
	if ( isset( $list[ $block_name ] ) ) return;
	$list[ $block_name ] = true;

	if ( false !== strpos( $block_name, 'core/' ) ) {
		$core_name = str_replace( 'core/', '', $block_name );
		wp_enqueue_style( "wp-block-{$core_name}" );
	}
}
```
コメントに「`parse_blocks()` だけだと separate なコア CSS がフッターで読み込まれてしまうので
ここでキューに追加」とある。**収集と副作用が同じ関数に同居している**点は転用時に分離すべき。

### 1.5 転用判定

**転用可（中核）。** 中間 JSON 抽出に必要な要素がすでに揃っている。

| 中間 JSON 抽出に要るもの | テーマB 実装の有無 |
|---|---|
| ショートコードの先行展開 | ○ `do_shortcode()` を `parse_blocks()` の前に通す |
| ブロックツリーの再帰走査 | ○ `check_parsed_block()` が innerBlocks へ再帰 |
| 参照ノードの解決 | ○ `themeB/blog-parts` / `core/block` を post 取得して再帰 |
| ページ種別ごとの入力決定 | ○ single / page / home / term を分岐 |
| 動的領域（ウィジェット）の把握 | ○ ドライラン方式 |
| 静的解析の取りこぼし補完 | ○ 文字列検査 + ウィジェットクラス名写像 |

**そのまま使えない点（転用時に変える必要がある箇所）**

1. **収集しているのが「ブロック名」だけ**。中間 JSON には attributes と innerHTML が要る。
   `check_parsed_block()` は `$block` 配列全体を受け取っているので、記録対象を
   `$block['blockName']` から `$block` 自体へ広げるだけで拡張できる。
2. **`wp_enqueue_style()` の副作用が混入**している。抽出器としては純関数であるべき。
3. **`render_block` フック依存の部分がある**。`render_check` は描画時にしか発火しないため、
   CLI / REST からの抽出には使えない。純粋な `parse_blocks()` 経路（`check_parsed_block`）だけで
   完結させる形に寄せる。
4. **ウィジェットのドライランは中間 JSON の射程外**（本文ではなくテンプレート領域）。
   ただし INV-03（広告 / CV ゾーン）で「どのゾーンに何が入っているか」を機械的に知る手段として
   そのまま使える。

---

## 2. `content_filter` の優先度設計 — 転用可（設計原則として）

### 2.1 実装の実際（証跡: `evidence/re-themeB-pipeline.txt`）

ファイル冒頭のコメントが設計意図をそのまま書いている:

```
* memo: ショートコード展開の優先度:11
*       ダイナミックブロック展開の優先度:9
*       優先度12 → ショートコード展開より後に実行するため
*       rest読み込みを考慮すると wp フックでは遅いので wp_loaded
```

登録は `wp_loaded`（優先度 20）で行い、本文系フィルタはすべて**優先度 12**で揃える。
さらに目次と URL カード化だけは `wp_head`（優先度 99）で後付け登録する。理由もコメントにある:

```php
// 本文へのフック → SEOプラグインのmetaディスクリプション生成時に発火しないように、登録を遅らせる。
add_action('wp_head', function () {
	add_filter( 'the_content', __NAMESPACE__ . '\add_toc', 12 );
	$remove_url_to_card = apply_filters( 'themeB_remove_url_to_card', テーマB::get_option( 'remove_url2card' ) );
	if ( ! $remove_url_to_card ) {
		add_filter( 'the_content', __NAMESPACE__ . '\url_to_blog_card', 12 );
	}
	…
}, 99 );
```

→ **「同じ本文を読む処理でも、目的が違えば通すフィルタを変える」**という発想。
SEO プラグインの description 生成に目次 HTML が混ざる事故を、登録タイミングで回避している。

### 2.2 中間 JSON の 3 レイヤへの写像

| テーマB のタイミング | 中間 JSON パイプラインの層 | 対応する処理の性格 |
|---|---|---|
| ショートコード展開（11）/ 動的ブロック展開（9）より前 | **生成時（JSON 正本）** | 意図語彙で持つ。参照は ID のまま |
| 優先度 12 の変換群 | **レンダリング時（決定論レンダラ）** | 参照解決・目次生成・カード化。入力が同じなら出力も同じにする |
| `wp_head`(99) で後付け登録する分 | **表示時（文脈依存フィルタ）** | 誰が読むか（SEO プラグイン / ブラウザ）で出し分ける層 |

**採用すべき原則は 3 つ**:
1. 変換の順序を**優先度という数値ではなく層として固定**する（テーマB は数値で運用しており、
   プラグインとの競合を各所のコメントで回避している。中間 JSON では層で分ける）。
2. **二重適用の防止をフラグで持つ**（テーマB は `テーマB::$added_toc`）。
   レンダラは冪等であるべきなので、同種の防止機構が要る。
3. **無効化点を `apply_filters` で明示する**（`themeB_remove_url_to_card`）。
   コメントに「プラグインなどで不具合があるページだけオフにしたりできるように」とある。

---

## 3. `is_rest()` による保存/表示の分離 — 参考のみ（思想は採用）

### 3.1 実装の実際

```php
function add_lazysizes( $content ) {
	// サーバーサイドレンダー, wp-json/wp/v2 などからはフック通さない (コンテンツ遅延読み込み時は通す)
	$is_rest = ! テーマB::is_rest( 'lazyload' ) && テーマB::is_rest();
	if ( $is_rest || テーマB::is_iframe() ) return $content;
	…
```

`wp_loaded` の登録自体にも同種の判定がある:

```php
// ajax遅延読み込み時も is_admin() true になる
if ( ! テーマB::is_rest() && is_admin() ) return;
```

`is_rest()` は引数で「どの REST 経路か」を区別できる作りになっており、
自前の遅延読み込みエンドポイント（`themeB-lazyload-contents`）だけは変換を通す。

### 3.2 判定

**思想は採用、実装は参考のみ。**

- 採用する原則: **保存されている HTML と表示される HTML は別物**であり、
  どちらを返すかは「誰が取りに来たか」で決まる。これは中間 JSON の
  「JSON が正本・HTML は派生」という前提と同型。
- 転用しない理由: `is_rest()` の中身は テーマB 固有の判定（自前エンドポイント名の照合を含む）で、
  そのまま持ち込む意味がない。中間 JSON 側では**経路ではなくレンダラの呼び出し引数**で
  明示的に決めるほうが決定論的。

**設計への反映**: レンダラは「表示用」「取得用（API）」「編集用」のどれとして呼ばれたかを
引数で受け取り、経路を推測しない。

---

## 4. プレースホルダ方式の目次 — 転用可（表現方式として）

### 4.1 実装の実際

ショートコード `[themeB_toc]` は本文に**空のプレースホルダ div を置くだけ**:

```php
// ショートコードで目次が挿入されているかどうか
if ( false !== strpos( $content, 'class="themeB-toc-placeholder"' ) ) {
	$toc = '<div class="p-toc -called-from-sc -' . $SETTING['index_style'] . '">' .
		'<span class="p-toc__ttl">' . $SETTING['toc_title'] . '</span></div>';
	if ( テーマB::is_show_toc_ad() ) { $toc_ad = \THEMEB_PARTS::toc_ad(); }
	$toc_content = 'after' === $SETTING['toc_ad_position'] ? $toc . $toc_ad : $toc_ad . $toc;
	$content = str_replace( '<div class="themeB-toc-placeholder"></div>', $toc_content, $content );
	テーマB::$added_toc = true;
}
```

プレースホルダが無い場合は**最初の h2 の直前**に挿入する:

```php
$tag = '/^<h2.*?>/im';
if ( $toc_content && preg_match( $tag, $content, $tags ) ) {
	if ( (int) get_query_var( 'page' ) > 1 ) {
		$content = $toc_content . $content;          // 2ページ目以降は先頭へ
	} else {
		$content = preg_replace( $tag, $toc_content . $tags[0], $content, 1 );
	}
	テーマB::$added_toc = true;
}
```

### 4.2 判定

**転用可。** 中間 JSON における「**後段で解決するノード**」の表現として素直に使える。

- 中間 JSON 側では `{ "type": "toc", "resolved_at": "render" }` のような
  **意図だけを持つノード**として保存し、実体（見出しツリー・目次広告）はレンダラが構築する。
  → 記事の正本に目次 HTML を焼き付けない。見出しを直せば目次も直る。
- 「明示配置がなければ最初の h2 の前」という**既定配置ルール**も、
  レンダラ側の規定として持てば決定論を壊さない。
- ページ送り（`get_query_var('page') > 1`）のような**表示文脈依存の分岐は表示層**へ寄せる
  （§3 の結論と整合）。

**注意点**: テーマB の実装は正規表現による HTML 文字列操作。中間 JSON では
ツリー操作（見出しノードの前に挿入）になるため、実装は流用せず**規則だけ**を採る。

---

## 5. ⑤ 参照展開の中間 JSON 表現（INV-04 と合流）

`themeB/blog-parts`（attrs.partsID）と `core/block`（attrs.ref）は、
`Pre_Parse_Blocks` では**実体を取得して再帰展開**している。

中間 JSON でどう持つかは 2 択で、INV-04（再利用パーツ機構の抽象化契約）と同じ論点:

| 方式 | 中間 JSON | 長所 | 短所 |
|---|---|---|---|
| 参照（ID） | `{ "type": "parts_ref", "id": 123 }` | パーツ更新が全記事へ波及。正本が 1 つ | レンダリングに DB 参照が要る＝外部状態依存 |
| 展開（実体埋め込み） | パーツの中身をその場に展開 | 決定論を保てる。単体で完結 | パーツ更新が波及しない。差分が巨大化 |

**本レポートの推奨**: 中間 JSON は**参照で持ち**、レンダラが解決する。
ただし「解決に使った版」を記録して再現性を担保する（`{ "id": 123, "resolved_rev": 45 }`）。
決定論の要件は「同じ入力 → 同じ出力」であり、参照先の版を入力の一部として固定すれば満たせる。
最終判断は INV-04 で確定する。

---

## 6. 転用する場合の最小実装範囲

1. **抽出器**（`Pre_Parse_Blocks::parse_content` 相当・副作用なし）
   - `do_shortcode` → `parse_blocks` → innerBlocks 再帰 → 参照ノードの再帰解決
   - 記録対象は blockName ではなく `$block` 全体（attrs / innerHTML / innerBlocks）
   - `render_block` フック依存を持たない（CLI / REST から呼べる）
2. **層の定義**（生成時 / レンダリング時 / 表示時）と、各層に属する変換の一覧
3. **冪等性の担保**（二重適用防止フラグに相当する仕組み）
4. **プレースホルダ・ノードの型定義**（目次を最初の実例とする）

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| `Pre_Parse_Blocks` 全文（約 290 行） | `evidence/re-themeB-blocks.txt` |
| `content_filter.php` 全文 / `Style.php` / `block_assets.php` | `evidence/re-themeB-pipeline.txt` |
| `separate.php` / `gutenberg.php` / autoloader / `load_files.php` | `evidence/re-themeB-boot.txt` |
| `register_block()` 実装 / `post-link.php` / `Theme_Data` / `rewrite_html` | `evidence/re-themeB-detail.txt` |
