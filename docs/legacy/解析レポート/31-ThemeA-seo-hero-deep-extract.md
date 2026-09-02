# テーマA 親テーマ SEO & Hero Deep Extract レポート

**生成日**: 2026-04-30
**対象**: <local-path>\Desktop\AGENT NEO\themeA-parent\themeA\themeA\
**作成範囲**: SEO meta、Hero variant、Customizer 構造

---

## 領域 A: SEO meta の保存・出力構造

### A-1. Post Meta Key 一覧（_themeA_xxx 系）

| Post Meta Key | 用途 | 保存対象 | 出力 |
|---|---|---|---|
| `_themeA_seotitle_display` | SEO用タイトル表示 | post/page | title.php, ogp.php, json-ld.php |
| `_themeA_description_display` | SEO用説明文 | post/page | description.php, ogp.php, json-ld.php |
| `_themeA_keyword_display` | SEOキーワード | post/page | keywords.php |
| `_themeA_canonical_display` | Canonical URL カスタム | post/page | others.php (rel=canonical) |
| `_themeA_noindex_display` | noindex フラグ (値=1) | post/page | noindex.php |
| `_themeA_category` | カテゴリー関連付け | page | title.php, description.php, json-ld.php |
| `_themeA_category_edit` | カテゴリー編集フラグ (値=1) | page | title.php, description.php, ogp.php, json-ld.php |
| `_themeA_thumb_youtube` | YouTube サムネイル保存 | post | post-slider.php |
| `_themeA_time_youtube` | YouTube 再生時間 | post | post-slider.php |
| `_wp_attachment_image_alt` | 画像 Alt テキスト | attachment | 複数の hero variant で読み込み |

**ソースコード参照**:
- title.php: 行 29-84
- description.php: 行 6-59
- keywords.php: 行 12-21
- noindex.php: 行 2-75
- others.php: 行 13-22
- ogp.php: 行 12-126
- post-slider.php: 行 13-14, 54

---

### A-2. Customizer Settings (theme_mod / option) 一覧

| Setting Key | Type | Default | Show in REST | 用途 |
|---|---|---|---|---|
| `themeA__desc_text` | text | '' | N/A | トップページ説明文 |
| `themeA__theme_color` | color | #407FED | Y | テーマカラー |
| `themeA__bg_color` | color | #f7faff | Y | 背景色 |
| `themeA__text_color` | color | #555555 | Y | 文字色 |
| `themeA__color_tab` | tab | 'general' | Y | カラータブ切り替え |
| `themeA__title_customize()` | function | 0/1 | N/A | タイトル SEP 非表示フラグ |
| `themeA__ogp_image_url()` | function | '' | N/A | OGP 画像 URL |
| `themeA__main_visual_type` | radio | 'type03-stillimage' | Y | Hero タイプ選択 |
| `themeA__slider_url1-6` | text | '' | Y | Post Slider 記事 URL |
| `themeA__stillimage_url` | image | '' | Y | Still Image URL |
| `themeA__imageslider_image_url1-6` | image | '' | Y | Image Slider URL |
| `themeA__movie_url()` | function | '' | N/A | 動画 URL |
| `themeA__home_column_style` | radio | 't--home-one-column' | Y | トップページレイアウト |
| `themeA__post_column_style` | radio | 't--post-one-column' | Y | 記事ページレイアウト |
| `themeA__main_style` | radio | 'd--main-style-outline' | Y | ページフレームスタイル |

**ソースコード参照**:
- color-setting.php: 行 5-100
- main-visual-setting.php: 行 24-170
- site-design-setting.php: 行 8-97

---

## 領域 B: Hero Variant 完全実装

### B-1. Hero Variant タイプ一覧

| Variant | Type Key | Default | HTML Root Class | ファイル |
|---|---|---|---|---|
| Still Image | type03-stillimage | Yes | o--themeA-stillimage | stillimage.php |
| Post Slider | type01-post-slider | No | o--themeA-slider | post-slider.php |
| Image Slider | type02-image-slider | No | o--themeA-imageslider | image-slider.php |
| Movie | type04-movie | No | o--themeA-movie | movie.php |

**ソースコード参照**:
- object/main-visual/ 全ファイル

---

### B-2. Still Image 実装

**Settings**: `themeA__main_visual_type = 'type03-stillimage'` (デフォルト)

**HTML Root**: `<section class="o--themeA-mainvisual o--themeA-stillimage t--main-width d--stillimage-style01">`

**主要子要素**:
- `.c--stillimage`: 画像ラッパー
- `.a--stillimage-overlay`: オーバーレイ
- `.c--stillimage-contents`: テキストコンテナ
- `.a--stillimage-maincopy`, `.a--stillimage-subcopy`: テキスト要素
- `.b--themeA-button`: ボタンコンテナ

**カスタマイザー連携** (20+ keys):
- `themeA__stillimage_url()`, `themeA__stillimage_url_sp()`
- `themeA__stillimage_overlay_design()` → class 出力
- `themeA__stillimage_copy_display()` → on/off
- `themeA__stillimage_maincopy()`, `themeA__stillimage_subcopy()`
- `themeA__stillimage_button_display()` → on/off
- `themeA__stillimage_button_design_select()`, `themeA__stillimage_button_link()`, `themeA__stillimage_button_text()`

**ソースコード参照**:
- stillimage.php: 行 1-38
- main-visual-setting.php: 行 565-1000 (推定)

---

### B-3. Post Slider 実装

**Settings**: `themeA__main_visual_type = 'type01-post-slider'`

**HTML Root**: `<section class="o--themeA-mainvisual o--themeA-slider d--slider-design1 [speed] [color]">`

**記事 URL 指定ロジック**:
`
Loop count=1 to 6:
  - `themeA__slider_url[N]()` から URL 取得
  - `url_to_postid()` で post_id 変換
  - YouTube: `_themeA_thumb_youtube`, `_themeA_time_youtube` meta を使用
  - 通常: `get_post_thumbnail_id()` → `wp_get_attachment_image_src()`
`

**カスタマイザー連携** (10+ keys):
- `themeA__slider_url1-6()` (text, required)
- `themeA__slider_text_color_select()`
- `themeA__slider_link_text()`
- `themeA__slider_animation_speed()`

**ソースコード参照**:
- post-slider.php: 行 1-84
- main-visual-setting.php: 行 79-255

---

### B-4. Image Slider 実装

**Settings**: `themeA__main_visual_type = 'type02-image-slider'`

**HTML Root**: `<section class="o--themeA-mainvisual o--themeA-slider o--themeA-imageslider [animation] [speed] [autoplay]">`

**アニメーション 3 パターン**:
1. Slide In: `d--imageslider-animation-slidein` → `<ul class="c--themeA-slider">`
2. Parallax: `d--imageslider-animation-parallax` → Swiper クラス + `data-swiper-parallax-x="100%"`
3. Default: その他 → Swiper standard

**Responsive Image 処理**:
- 対象拡張子: .png, .jpg, .jpeg
- Suffix: -320x180.*, -640x360.*, -1360x765.*
- srcset 自動生成 (ファイル存在確認)

**カスタマイザー連携** (15+ keys):
- `themeA__imageslider_image_url1-6()` (image)
- `themeA__imageslider_image_link1-6()` (text)
- `themeA__imageslider_animation_select()` (radio)
- `themeA__imageslider_animation_speed()`
- `themeA__imageslider_autoplay()`

**ソースコード参照**:
- image-slider.php: 行 1-168
- main-visual-setting.php: 行 256-563

---

### B-5. Movie 実装

**Settings**: `themeA__main_visual_type = 'type04-movie'`

**HTML Root**: `<section class="o--themeA-mainvisual o--themeA-movie t--main-width">`

**対応フォーマット**: .gif, .mp4, .flv, .avi, .mov, .webm

**Video タグ属性**:
- `src="[url]#t=0.001"` (開始 1ms から)
- `type="video/[拡張子]"`
- `playsinline muted [loop] [autoplay]`

**カスタマイザー連携** (15+ keys):
- `themeA__movie_url()` (text, required)
- `themeA__movie_autoplay()`, `themeA__movie_loop()` (radio)
- `themeA__movie_copy_display()` (on/off)
- `themeA__movie_maincopy()`, `themeA__movie_subcopy()`
- `themeA__movie_button_*()`

**ソースコード参照**:
- movie.php: 行 1-35

---

## 領域 C: Customizer 構造

### C-1. UI ファイル一覧 (14 個)

| ファイル | Section Key | 主管理対象 | Settings 数 |
|---|---|---|---|
| color-setting.php | themeA__color_setting_panel | カラー (50+) | 50+ |
| site-design-setting.php | themeA__site_design_section | レイアウト/フレーム | 30+ |
| site-setting.php | themeA__site_setting_section | 基本/OGP/SNS | 15+ |
| main-visual-setting.php | themeA__main_visual_section | Hero all type | 120+ |
| button-design-setting.php | themeA__button_design_panel | Button 6 type | 200+ |
| headline-design-setting.php | themeA__headline_design_section | H1-H6 design | 40+ |
| box-design-setting.php | themeA__box_design_section | Box/Card design | 80+ |
| fukidashi-setting.php | themeA__fukidashi_section | 吹き出し design | 100+ |
| animation-setting.php | themeA__animation_setting_section | Animation | 20+ |
| profile-setting.php | themeA__profile_setting_section | プロフィール | 15+ |
| sns-setting.php | themeA__sns_setting_section | SNS URL | 30+ |
| design-preset-setting.php | themeA__design_preset_section | プリセット | 5+ |
| others-setting.php | themeA__others_setting_section | その他 | 20+ |
| spmenu-setting.php | themeA__spmenu_section | SP メニュー | 150+ |

**ソースコード参照**:
- 各ファイル最初の 50 行に section / panel / setting 定義が集約

---

### C-2. Color Setting 詳細

**Section**: `themeA__color_setting_panel` (Priority: 2)

**Tab** (`themeA__color_tab`):
- 'general': テーマカラー, BG色, 文字色, ヘッダー色など
- 'gradation': グラデーション登録

**基本色 Settings** (5個):
- `themeA__theme_color` (default: #407FED)
- `themeA__bg_color` (default: #f7faff)
- `themeA__text_color` (default: #555555)
- `themeA__header_bg_color`
- `themeA__header_text_color`

**ソースコード参照**:
- color-setting.php: 行 5-100+

---

### C-3. Main Visual Setting 詳細

**Section**: `themeA__main_visual_section` (Priority: 4)

**Primary Control** (`themeA__main_visual_type`):
`
radio, default='type03-stillimage'
Choices:
  - none
  - type01-post-slider
  - type02-image-slider
  - type03-stillimage
  - type04-movie
`

**Type 別 Sub-settings**:

**Post Slider** (15+ keys):
- `themeA__mainvisual_slider_extra_settings1-2` (hidden)
- `themeA__slider_url1-6` (text)
- `themeA__slider_text_color_select`, `themeA__slider_link_text`, `themeA__slider_animation_speed`

**Image Slider** (20+ keys):
- `themeA__mainvisual_imageslider_extra_settings1-2` (hidden)
- `themeA__imageslider_image_url1-6` (image)
- `themeA__imageslider_image_link1-6` (text)
- `themeA__imageslider_animation_select`, `themeA__imageslider_animation_speed`, `themeA__imageslider_autoplay`

**Still Image** (30+ keys):
- `themeA__mainvisual_stillimage_extra_settings1` (hidden)
- `themeA__stillimage_design`, `themeA__stillimage_url`, `themeA__stillimage_url_sp`
- `themeA__stillimage_height_size*`, `themeA__stillimage_overlay_*`, `themeA__stillimage_copy_*`, `themeA__stillimage_button_*`

**Movie** (20+ keys):
- `themeA__mainvisual_movie_extra_settings*` (hidden)
- `themeA__movie_url`, `themeA__movie_autoplay`, `themeA__movie_loop`
- `themeA__movie_copy_*`, `themeA__movie_button_*`

**Total**: 120+ settings

**ソースコード参照**:
- main-visual-setting.php: 全ファイル

---

## AGENT NEO への含意 (主要発見 3 点)

### 1. SEO Meta の 2 層管理構造

**テーマA の特徴**: Post Meta (_themeA_xxx_display) と Theme Mod (themeA__xxx) を併用

- **Post Meta**: ページ/記事ごとのカスタム SEO (title, description, noindex, canonical)
- **Theme Mod**: サイト全体のデフォルト値 + 共通設定 (color, layout, hero type)

**AGENT NEO への含意**:
- Token 化する際は「サイト全体デフォルト」と「ページ個別オーバーライド」の 2 層を設計
- Meta Box / ACF を使用して post meta を管理する場合、REST API 公開設定を明示 (`'show_in_rest' => true`)
- JSON-LD は post meta と theme_mod の両方をマージして出力するロジックが必須

---

### 2. Hero Variant の 4 型統一 UI 設計

**テーマA の特徴**: Customizer の 1 つの radio control で 4 タイプ全切替、各タイプは独立した settings セット

- **type03-stillimage (デフォルト)**: シンプル、テキスト + ボタン
- **type01-post-slider**: 記事 URL を 6 個まで設定、YouTube 対応
- **type02-image-slider**: 画像 6 個、animation 3 種 (slidein, parallax, swiper)
- **type04-movie**: 動画ファイル (.mp4 等)、autoplay/loop 制御

**AGENT NEO への含意**:
- Hero section を block editor component 化する場合、variant selector を <SelectControl> で実装
- Post Slider の「URL → post_id」変換は `url_to_postid()` を使用する実装が安定的
- Image Slider の responsive handling は suffix パターン matching が効果的 (WP_CONTENT_DIR チェック)

---

### 3. Customizer の 14 個セクション + 500+ settings の大規模管理

**テーマA の特徴**: Theme Mod ベースの細粒度設計、tab/section で UI 整理

**セクション階層**:
- **トップ level**: color-setting, site-design-setting, main-visual-setting (priority 2-4)
- **デザイン詳細**: button-design, headline-design, box-design, fukidashi-setting (中規模)
- **補助機能**: animation, profile, sns, others, spmenu (小～中規模)

**各セクション内の構成パターン**:
1. Hidden heading (`themeA__xxx_extra_settings[N]`) で グループ分け
2. Control type: radio (選択肢), color (Color Control), text (text), image (media upload), range
3. Conditional display: frontend で `get_theme_mod()` で条件判定

**AGENT NEO への含意**:
- Design token export: color-setting の 50+ key を colors.* namespace に変換
- Customizer を REST API 経由で提供する場合、**settings ごとに** show_in_rest を明示設定
- 各 section の priority / panel 構造を保持して、UI ナビゲーション階層を preserve する
- 500+ settings の **一括 export / import** 機能が必須 (Theme Mod serialize/backup)

---

**ファイル完成**: <local-path>\Desktop\AGENT NEO\解析レポート\31-ThemeA-seo-hero-deep-extract.md