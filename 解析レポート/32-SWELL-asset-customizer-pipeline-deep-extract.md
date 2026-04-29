# SWELL アセット読み込み + カスタマイザーパイプライン + ad_tag CPT 深堀り

> 解析日: 2026-04-30 / 対象: SWELL Theme v2.16.0 親テーマ
> 解析コンテキスト: PM Opus 観点で Codex 解析の情報密度を上げる目的の追加抽出

## 領域 A: コンディショナルアセット読み込みの判定ロジック

### 読み込み判定フロー

| ファイル | Hook | 判定キー | 条件 | 動作 |
|---------|------|---------|------|------|
| `lib/load/front.php` L12-21 | `wp_enqueue_scripts` Pri:8 | なし（無条件） | - | フロント JS/CSS 全体を `load_plugins()`, `load_front_styles()`, `load_front_scripts()` で初期化 |
| `lib/load/front.php` L18-20 | `wp_enqueue_scripts` Pri:8 | `load_style_async` | `SWELL::get_option()` で確認 | `style_loader_tag` フィルターで対象 handle を `media="print"` + `onload` に置換 |
| `lib/load/front.php` L174-183 | 内部（`load_separated_styles()`) | `is_separate_css()` | 設定値確認 + ウィジェット iframe で `$used_blocks` をリセット | 使用ブロックのみ CSS を enqueue（skip されたブロックは読まない） |
| `lib/load/separate.php` L8-25 | `init` Pri:9 | `is_separate_css()` | - | Pre_Parse_Blocks::init() を `wp_head` Pri:0 で追加 |
| `lib/load/block_assets.php` L9-66 | `enqueue_block_editor_assets` Pri:20 | なし（エディター環境） | - | エディター用スタイル・スクリプト・翻訳ファイルを常時読み込み |
| `lib/load/admin.php` L10-136 | `admin_enqueue_scripts` | `$hook_suffix` 値 | 投稿タイプ別・ページタイプ別 | UI に応じた特定 handle のみ enqueue |
| `lib/load/admin.php` L39-46 | `admin_enqueue_scripts` | `is_customize_preview()` | - | カスタマイザー画面で `swell_admin/customizer` + `swell_admin/widgets` を読み込み |
| `lib/load/admin.php` L127-135 | `admin_enqueue_scripts` | `$post_type === 'ad_tag'` | - | 広告タグ管理画面用 CSS + JS を個別読み込み |

### Pre_Parse_Blocks ブロック検知メカニズム

| メソッド | 呼び出しポイント | 検知内容 | リスト管理 |
|---------|----------------|--------|----------|
| `init()` L16-51 | `wp_head` Pri:0 | 1. メインコンテンツ parse + render_block フック監視 / 2. ウィジェット出力内容チェック / 3. ページタイプ別強制セット | `SWELL::$used_blocks` 静的配列 |
| `parse_content()` L56-70 | メインコンテンツ・パーツコンテンツ | `do_shortcode()` → `parse_blocks()` で全ブロック抽出 + 文字列検索（旧ショートコード） | 同上 |
| `render_check()` L75-81 | `render_block` Pri:10 | blockName を抽出して `push_used_blocks()` で記録 | 同上 |
| `check_parsed_block()` L100-125 | 再帰処理 innerBlocks | ネストされたブロック + blog_parts・sync パターン展開時の子ブロック | 同上 |
| `check_content_str()` L289-327 | `render_check` 後に呼び出し | 旧ショートコード `[ad_tag`, `[ふきだし`, `[speech_balloon` 等を文字列検索 | 同上 |
| `parse_widgets()` L157-185 | 全ウィジェット領域 | `dynamic_sidebar`, `widget_text` フィルター + ページ種別ごと widget 出力 (`ob_start/clean`) | `$sidebar_blocks` + マージ |
| `check_dynamic_sidebar()` L343-379 | `dynamic_sidebar` フック | ウィジェットクラスから `core/calendar`, `widget/rss` 等を自動検知 | `SWELL::$used_blocks` に直接追加 |

### キャッシュ機構
- サイドバー用 separate list → コメント行で草案あり（L193-198）
- 本体は transient キャッシュ未使用（毎回 parse）
- ただし Style.php 内で `swell_parts_style_common` キャッシュ（30 日）

---

## 領域 B: カスタマイザー → CSS 変数生成パイプライン

### 設定値 → CSS 変数マッピング表

| カスタマイザー設定 | Type | Default_Settings.php | CSS 変数名 | 参照元 (Style クラス) | 出力先 |
|------------------|------|---------------------|-----------|-------------------|--------|
| `color_main` | color | `#04384c` | `--color_main` | Style/Color::common() L236 | `:root{}` |
| `color_text` | color | `#333` | `--color_text` | 同上 | `:root{}` |
| `color_link` | color | `#1176d4` | `--color_link` | 同上 + Style/Post.php L452 | `:root{}` |
| `color_deep01` ~ `04` | color | `#e44141` 等 | `--color_deep01~04` | Style.php L382-385 | `:root{}` |
| `color_pale01` ~ `04` | color | `#fff2f0` 等 | `--color_pale01~04` | Style.php L386-389 | `:root{}` |
| `color_mark_blue` ~ `orange` | color | `#b7e3ff` 等 | `--color_mark_*` | Style/Post::marker() L396 | `:root{}` |
| `marker_type` | select | `thin` | - | Style/Post::marker(val) | post selector `.mark_*` |
| `body_font_family` | select | `yugo` | `--swl-font_family`, `--swl-font_weight` | Style/Body::font() L15-53 | `:root{}` |
| `post_font_size_pc` `post_font_size_sp` | text | `16px` / `4vw` | `--swl-fz--content` | Style/Body::font() L18-19 | `:root{}` (with @media) |
| `container_size` | number | 1200 | `--container_size` | Style/Body::content_size() L62 | `:root{}` |
| `article_size` | number | 900 | `--article_size` | 同上 | `:root{}` + @media |
| `logo_size_pc` | number | 40 | - | Style/Header::logo() | `.l-logo img{width:var()}` |
| `fix_header_opacity` | number | 1 | - | Style.php L249 | `.l-fixHeader::before{opacity:val}` |
| `color_info_bg` | color | `#ff4133` | - | Style/Header::info_bar() | `.p-infoBar{background:val}` |
| `body_bg` `body_bg_sp` | image | '' | - | Style/Body::bg() L73-92 | `#body_wrap {background:url(...)}` |
| `mv_slide_effect` 等 | select | varies | - | global_vars_on_front() L305-310 | JS 変数 `swellVars.mvSlideEffect` |

### CSS 生成キャッシュ戦略

| キャッシュ名 | 対象範囲 | Transient 名 | 有効期限 | Reset Trigger |
|------------|---------|------------|--------|----------------|
| 共通スタイル | 全ページ（フロント共通） | `swell_parts_style_common` | 30 日 | キャッシュクリア / カスタマイザー変更時に admin_toolbar で DELETE |
| ページタイプ別 | トップ・投稿・固定ページ等 | `swell_parts_style_{page_type}` | 30 日 | 同上 |
| インライン CSS | メインスタイル + モジュール CSS | ファイルから読み込み | キャッシュなし | 常に最新 |
| キャッシュ判定 | `cache_style` 設定 && `!is_customize_preview()` | - | - | Default_Settings.php L50 |

### キャッシュ無効化フロー
- カスタマイザー preview では `cache_style` 無視（L483）→ 常時再生成
- 投稿メタ `swell_meta_no_mb` で個別 CSS override 可能（Style.php L538）

---

## 領域 C: 広告タグ CPT (ad_tag) 管理フロー

### 投稿タイプ登録と権限設定

| 登録項目 | 値 | 説明 |
|---------|---|------|
| 投稿タイプ名 | `ad_tag` | lib/post_type.php L76-96 |
| 公開設定 | `public: false` | 管理画面でのみ管理 |
| REST API | `show_in_rest: false` | エディター非対応 |
| サポート | `title` のみ | メタボックスで全情報管理 |
| 権限構造 | 複数形 `ad_tags` | post_type.php L89 |
| 権限割当 | Admin/Editor: 全権限 | post_type.php L161 |
|  | Author: 自分の投稿のみ編集可 | post_type.php L173 |

### Ad_tag メタボックス構造（meta_ad.php）

| メタキー | 型 | 保存形式 | 管理画面 UI | フロント使用 |
|---------|---|--------|----------|-----------|
| `ad_type` | str | `normal` / `text` / `affiliate` / `amazon` / `ranking` | ラジオボタン + プレビュー | shortcode render で分岐 |
| `ad_img` | code | HTML/スクリプト（inline script/img 等） | textarea 10 行 | `.p-adBox__img {}` に `$ad_img` をそのまま出力 |
| `ad_border` | str | `off` / `on` | ラジオボタン | CSS class `.p-adBox.-border-{val}` |
| `ad_rank` | str | `rank1` / `rank2` / `rank3` / `rank0` | ラジオボタン | ranking タイプのみ見出し表示 |
| `ad_name` | html | テキスト（wp_kses_post） | input type=text | `.p-adBox__name` に表示 |
| `ad_price` | str | テキスト | input type=text | `.p-adBox__price` に表示 |
| `ad_desc` | html | テキスト（wp_kses_post） | textarea 5 行 | `.p-adBox__desc` に表示 |
| `ad_star` | str | `0.5` ~ `5` (0.5 刻み) | select dropdown | `SWELL_PARTS::review_stars()` で★表示 |
| `ad_btn1_url` | url | URL（urlsanitize） | input type=text | `.p-adBox__btn.-btn1 href` |
| `ad_btn1_text` | str | テキスト（default: '詳しくみる'） | input type=text | `.p-adBox__btn.-btn1` テキスト |
| `ad_btn2_url` | url | URL（urlsanitize） | input type=text | `.p-adBox__btn.-btn2 href` |
| `ad_btn2_text` | str | テキスト（default: '購入する'） | input type=text | `.p-adBox__btn.-btn2` テキスト |

**メタ保存処理** (meta_ad.php L187-237)
- hook: `save_post` で nonce チェック
- `SWELL::save_post_metas()` で一括保存
- サニタイズ: `str` / `code` / `html` / `url` タイプ別

### フロント側レンダリング（shortcode.php L151-269）

| ad_type | レンダリングロジック | HTML 構造 | 出力条件 |
|---------|-----------------|---------|--------|
| text | 広告コードそのまま出力 | `<span class="p-adBox -text" data-ad="text">$ad_img</span>` | ブロック呼び出し不可（ショートコードのみ） |
| normal | 画像 + ボタン型（バナー） | `<div class="p-adBox -normal -border-*"><img>...</div>` | デフォルト形式 |
| affiliate | 詳細情報型（商品名、説明、ボタン） | `<div class="-affiliate"><name><desc><btns></div>` | 商品レビューサイト向け |
| amazon | Amazon affiliate 最適型 | 名前・価格・説明・ボタン 2 個 | affiliate と同構造 |
| ranking | ランキング型（順位ラベル付き） | `<div class="-ranking"><title rank="1">...</title><footer><btns></footer></div>` | `ad_rank` を head 見出しに使用 |

### ショートコード呼び出し
- shortcode: `[ad_tag id="123" class="custom-class"]`
- block: `<ad-tag adID="123" className="custom-class" />`（block.php L9-16）
- block render: ショートコードに再委譲（L13）

### 計測連携ポイント

| 計測対象 | フック / 実装場所 | データ点 | 備考 |
|--------|---------------|--------|------|
| 広告ブロック検知 | Pre_Parse_Blocks::check_content_str() L291-295 | `loos/ad-tag` フラグ | CSS separate 判定用 |
| クリック計測 ID | メタキーに未実装 | - | meta_ad.php にコメント行なし（将来拡張対応可） |
| CTR 計測フラグ | lib/gutenberg/block/ad-tag.php L11 | `set_use( 'count_CTR' )` コメント化中 | 実装予定 |
| 計測スクリプト連携 | - | - | Google Analytics / アフィリエイト計測 JS は ad_img に含める |

---

## 主要発見（AGENT NEO への含意）

### 1. アセット条件付き読み込みは 2 層構造
- 第 1 層: hook 基準（環境・ページタイプ）
- 第 2 層: ブロック検知基準（used_blocks → separate CSS）

**含意**: AGENT NEO が類似テーマを構築する際、この 2 層フィルターパターンを採用すれば、カスタム CPT・カスタムブロック毎の条件付きロード機構が実現でき、SWELL と同等のパフォーマンス最適化が可能。`asset-policy.schema.json` でこの 2 層を明示し、AGENT NEO ブロックは block.json の `assets` 宣言で第 2 層自動連携を実現できる。

### 2. CSS 変数生成パイプラインは厳密な「層別出力」
- `:root` レベル（全メディア共通）
- `@media (min-width)` / `@media (max-width)` で pc/sp 分岐
- transient キャッシュで 30 日固定

**含意**: AGENT NEO の動的 CSS 生成も同じ 3 段階（`カスタマイザー → CSS 変数 → selector`）を採用すべき。AGENT NEO では FSE の theme.json が CSS 変数定義の正本になるため、`design-tokens.json` を theme.json に変換するビルダーを設計すれば、AI 操作 → JSON 更新 → CSS 自動再生成という流れが綺麗に組める。キャッシュは 30 日 transient + customizer preview で無効化のパターンを踏襲。

### 3. ad_tag CPT は「メタボックス駆動型」で、テンプレート化が限定的
- `ad_type` 選択時に render 分岐（5 パターン）
- ショートコード + ブロック で同一データ参照
- 計測 ID はメタキーに未実装（将来拡張可能）

**含意**: AGENT NEO 個人版の広告タグ CPT は、SWELL ad_tag をベースにしつつ以下を追加すべき:
- `cta_id` / `tracking_id` メタキー必須化（クリック計測のため）
- `affiliate_network` メタ（Amazon / 楽天 / ASP 種別）
- `pr_disclosure` メタ（景表法対応の PR 表記自動付与フラグ）
- `placement_rules` メタ（記事内の自動差し込み位置・条件）
- show_in_rest = true（agent-api 経由で AI が広告を CRUD 可能に）

### 4. 補足: SWELL の弱点として AGENT NEO が改善すべき点
- 計測 ID がメタに未実装 → AGENT NEO は最初から組み込む
- ad_tag が REST 非公開 → AGENT NEO は AI 操作前提で REST 公開
- shortcode + block の二重実装 → AGENT NEO は block.json 単一ソース
- transient のサイドバー separate キャッシュは草案のみ → AGENT NEO は完成形で実装

---

**レポート作成**: 2026-04-30 / Explore subagent 抽出 / PM Opus 検証
**分析対象**: SWELL Theme v2.16.0 親テーマ
**分析ファイル行数集計**: 1,658 行（lib/load, classes/Style*, Post_Meta/meta_ad）
