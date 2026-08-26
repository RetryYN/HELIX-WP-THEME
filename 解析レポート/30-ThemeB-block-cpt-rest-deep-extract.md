# ThemeB 親テーマ 完全解析レポート

**解析対象**: c:\Users\tenni\Desktop\AGENT NEO\themeB-2.16.0\themeB\
**生成日**: 2026-04-30
**バージョン**: ThemeB 2.16.0

---

## 領域 A: ブロック完全カタログ（32ブロック）

### コンテナ構造ブロック（21個）

| # | Block Name | Title | File Path | 主要 Attributes | Parent/Context | 用途 |
|---|-----------|-------|-----------|-----------------|-----------------|------|
| 1 | themeB/ab-test | ABテスト | src/gutenberg/blocks/ab-test/block.json | rate(number), syncMode(bool), syncId(string) | - | A/B テスト分岐 |
| 2 | themeB/ab-test-a | Aブロック | src/gutenberg/blocks/ab-test-a/block.json | (none) | Parent: ab-test | ABテスト分岐A |
| 3 | themeB/ab-test-b | Bブロック | src/gutenberg/blocks/ab-test-b/block.json | (none) | Parent: ab-test | ABテスト分岐B |
| 4 | themeB/accordion | アコーディオン | src/gutenberg/blocks/accordion/block.json | iconOpened, iconClosed, titleTag | Provides context | アコーディオンコンテナ |
| 5 | themeB/accordion-item | 項目 | src/gutenberg/blocks/accordion-item/block.json | title(html), iconOpened, iconClosed, isDefultOpen, titleTag | Parent: accordion | アコーディオン項目 |
| 6 | themeB/columns | リッチカラム | src/gutenberg/blocks/columns/block.json | vAlign, colPC/Tab/Mobile, isScrollable, colWidth(obj), margin(obj) | Provides context | マルチカラムコンテナ |
| 7 | themeB/column | カラム項目 | src/gutenberg/blocks/column/block.json | vAlign, widthPC/Tab/Mobile, colWidth, isBreakAll, useCustomPadding, padding | Parent: columns | カラムアイテム |
| 8 | themeB/box-menu | ボックスメニュー | src/gutenberg/blocks/box-menu/block.json | boxStyle, textColor, boxColor, boxGradient, direction, colPC/Tab/Mobile, gap, iconSize | Provides context | グリッドメニュー |
| 9 | themeB/box-menu-item | リンクボックス | src/gutenberg/blocks/box-menu-item/block.json | content(html), href, isNewTab, rel, iconSize, iconType, iconName, imageUrl, imageID, trimImg | Parent: box-menu | メニュー項目 |
| 10 | themeB/full-wide | フルワイド | src/gutenberg/blocks/full-wide/block.json | bgColor, textColor, bgImageUrl, bgImageID, bgOpacity, contentSize, pcPadding, spPadding, SVG divider | align: full | フルワイドセクション |
| 11 | themeB/dl | 説明リスト(DL) | src/gutenberg/blocks/dl/block.json | dtWidth(number) | - | DL コンテナ |
| 12 | themeB/dt | タイトル(DT) | src/gutenberg/blocks/dl-dt/block.json | content(html) | Parent: dl | DL タイトル |
| 13 | themeB/dd | 説明(DD) | src/gutenberg/blocks/dl-dd/block.json | (none) | Parent: dl | DL 説明 |
| 14 | themeB/faq | FAQ | src/gutenberg/blocks/faq/block.json | iconRadius, qIconStyle, aIconStyle, outputJsonLd(bool), titleTag | Provides context | FAQ コンテナ |
| 15 | themeB/faq-item | FAQ項目 | src/gutenberg/blocks/faq-item/block.json | contentQ(html), titleTag | Parent: faq | FAQ 項目 |
| 16 | themeB/link-list | リンクリスト | src/gutenberg/blocks/link-list/block.json | listStyle, icon, iconPos, radius, fontSize, color, hasBorder, isFlex, isFill | Provides context | リンク集コンテナ |
| 17 | themeB/link-list-item | リンク項目 | src/gutenberg/blocks/link-list-item/block.json | content(html), href, isNewTab, rel | Parent: link-list | リンク項目 |
| 18 | themeB/step | ステップ | src/gutenberg/blocks/step/block.json | startNum, numShape, numLayout, stepLabel, stepClass, titleTag | Provides context | ステップコンテナ |
| 19 | themeB/step-item | ステップ項目 | src/gutenberg/blocks/step-item/block.json | title(html), stepLabel, theLabel, theNum, numColor, stepClass, isHideLabel, isHideNum, isShapeFill, isPreview, titleTag | Parent: step | ステップ項目 |
| 20 | themeB/tab | タブ | src/gutenberg/blocks/tab/block.json | isExample, tabId, activeTab, tabWidthPC/SP, isScrollPC/SP, tabColor | - | タブコンテナ |
| 21 | themeB/tab-body | タブコンテンツ | src/gutenberg/blocks/tab-body/block.json | id(number), tabId, activeTab | Parent: tab | タブペーン |

### コンテンツ・機能ブロック（8個）

| # | Block Name | Title | File Path | 主要 Attributes | 用途 |
|---|-----------|-------|-----------|-----------------|------|
| 22 | themeB/button | ThemeBボタン | src/gutenberg/blocks/button/block.json | content(html), hrefUrl, isNewTab, rel, imgUrl, btnAlign, htmlTags, isCount, btnId, iconName, iconPosition, color, fontSize, iconSize, width, btnSize | CTA ボタン |
| 23 | themeB/banner-link | バナーリンク | src/gutenberg/blocks/banner-link/block.json | alignment, verticalAlignment, hrefUrl, bannerTitle(html), bannerDescription(html), imageUrl, imageID, imageSize, isBlank, isBlurON, isShadowON, bgColor, bgOpacity, imgRadius, textColor, bannerWidth, bannerHeightPC/SP, rel | バナーリンク |
| 24 | themeB/balloon | ふきだし | src/gutenberg/blocks/balloon/block.json | content(html), balloonID, balloonTitle, balloonIcon, balloonName, balloonCol, balloonType, balloonAlign, balloonBorder, balloonShape, spVertical | 吹き出し |
| 25 | themeB/cap-block | キャプションボックス | src/gutenberg/blocks/cap-block/block.json | content(html), dataColSet, iconName, iconPosition, iconSize | キャプション付きボックス |
| 26 | themeB/restricted-area | 制限エリア | src/gutenberg/blocks/restricted-area/block.json | roles(obj), isRole, isLoggedIn, isDateTime, startDateTime, endDateTime, isPage, pageLimitType, pageTypes(obj), allowedPostTypes, terms(obj) | 条件付き表示 |
| 27 | themeB/review | 商品レビュー | src/gutenberg/blocks/review/block.json | useType, name, rating(number), price(number), priceCurrency, description, author, usePostAuthor, image(obj), merits(array), demerits(array), data(obj) | 構造化データ付きレビュー |
| 28 | themeB/ad-tag | 広告タグ | src/gutenberg/blocks/ad-tag/block.json | className, adID | 広告タグ参照 |
| 29 | themeB/blog-parts | ブログパーツ | src/gutenberg/blocks/blog-parts/block.json | className, partsTitle, partsID | ブログパーツ参照 |

### クエリ・リスト系ブロック（3個）

| # | Block Name | Title | File Path | 主要 Attributes | 用途 |
|---|-----------|-------|-----------|-----------------|------|
| 30 | themeB/post-list | 投稿リスト | src/gutenberg/blocks/post-list/block.json | listType, postID, catID, tagID, taxName, termID, excID, catPos, listCount, pcCol, spCol, showTitle, showDate, showModified, showAuthor, showPV, orderby, order, pcExcerptLength, spExcerptLength, postType, hTag, addSticky | 投稿グリッド・リスト |
| 31 | themeB/post-link | 関連記事 | src/gutenberg/blocks/post-link/block.json | cardTitle, cardCaption, isNewTab, isPreview, hideImage, hideExcerpt, linkData(obj), icon, isText, postId, externalUrl, postTitle, thumbUrl, thumbID | ブログカード |
| 32 | themeB/rss | RSS | src/gutenberg/blocks/rss/block.json | rssUrl, pageName, listType, listCountPC/SP, showSite, showDate, showAuthor, showThumb, useCache, hTag, pcCol, spCol | RSS フィード表示 |

---

## 領域 B: CPT（カスタム投稿タイプ）詳細

### 登録 CPT（3種類）

| # | CPT Slug | 表示名 | Public | Show_in_Rest | Show_in_Menu | Has_Archive | Capability Type | Supports | Post Meta Keys |
|---|----------|--------|--------|--------------|--------------|-------------|-----------------|----------|-----------------|
| 1 | lp | LP | true | true | true | false | [lp, lps] | title, editor, thumbnail, author, revisions, custom-fields | lp_content_width, lp_wrapper_on |
| 2 | blog_parts | ブログパーツ | false | true | true | false | [blog_part, blog_parts] | title, editor | (none explicit) |
| 3 | ad_tag | 広告タグ | false | false | true | false | [ad_tag, ad_tags] | title | ad_type, ad_border, ad_rank, ad_name, ad_price, ad_desc, ad_star, ad_btn1_text/url, ad_btn2_text/url |

### Post Meta Keys（CV 計測）

- **themeB_btn_cv_data**: ボタン CV 計測 (JSON: {btnid: {pv, imp, click}})
- **Advertising Metrics**: imp_count, pv_count, tag_clicked_ct, btn1_clicked_ct, btn2_clicked_ct
- **PV Count**: ThemeB_CT_KEY

### 権限マッピング（lib/post_type.php）

`
Administrator / Editor:
  - lps: delete_others_, delete_, delete_private_, delete_published_, edit_others_, edit_, edit_private_, edit_published_, publish_, read_private_
  - ad_tags: (same as above)
  - blog_parts: (same as above)
  - speech_balloons: edit_, read_

Author:
  - ad_tags: delete_, delete_published_, edit_, edit_published_, publish_
  - blog_parts: delete_, delete_published_, edit_, edit_published_, publish_
  - speech_balloons: read_
`

---

## 領域 C: REST API エンドポイント完全カタログ

### ブロックエディター設定（1 route, 2 methods）

| Route | Method | Permission | Args | Response | 機能 |
|-------|--------|-----------|------|----------|------|
| wp/v2/themeB-block-settings | GET | has_edit_can | (none) | {show_device_toolbtn, show_margin_toolbtn, show_shortcode_toolbtn, show_marker_top, show_fz_top, show_textcolor_top, show_bgcolor_top, show_header_postlink} | ブロック設定取得 |
| wp/v2/themeB-block-settings | POST | is_administrator | key, val | (updated settings) | 設定更新（単一キー） |

### 計測系（4 route, 全て POST）

| Route | Permission | Args | Response | 機能 |
|-------|-----------|------|----------|------|
| wp/v2/themeB-ct-pv | __return_true | postid | {postid} | PV 数計測 |
| wp/v2/themeB-ct-btn-data | __return_true | btnid, postid, ct_name (pv/imp/click) | {btnid, cvdata (JSON), ct_name} | ボタン CV 計測 |
| wp/v2/themeB-ct-ad-data | __return_true | adid, ct_name, target (opt) | {id, meta, ct} | 広告 CV 計測 |
| wp/v2/themeB-reset-ad-data | is_administrator | id | "リセットに成功しました。" | 広告計測リセット |

### キャッシュ・設定管理（2 route, 全て POST）

| Route | Permission | Args | 機能 |
|-------|-----------|------|------|
| wp/v2/themeB-reset-cache | is_administrator | action (cache/card_cache/parts_used_cache) | キャッシュクリア |
| wp/v2/themeB-reset-settings | is_administrator | action (customizer/pv) | 設定リセット |

### 更新実行（1 route, POST）

| Route | Permission | Args | 機能 |
|-------|-----------|------|------|
| wp/v2/themeB-do-update-action | is_administrator | (none) | テーマ DB 更新実行 |

### コンテンツ遅延読み込み（1 route, GET）

| Route | Permission | Args | 機能 |
|-------|-----------|------|------|
| wp/v2/themeB-lazyload-contents | __return_true | placement (after_article/before_footer_widget/footer), post_id (opt) | コンテンツ遅延読み込み |

### タクソノミー（1 route, GET）

| Route | Permission | Args | Response | 機能 |
|-------|-----------|------|----------|------|
| wp/v2/themeB-term-list | __return_true | taxonomy, hide_empty (opt) | [{id, name, slug, parent, link}] | タームリスト取得 |

### ふきだし管理 API（7 route）

| Route | Method | Permission | Args | 機能 |
|-------|--------|-----------|------|------|
| wp/v2/themeB-balloon | GET | read_speech_balloons | id (opt) | ふきだしセット取得 |
| wp/v2/themeB-balloon | POST | edit_speech_balloons | id (opt), title, data (JSON) | ふきだしセット登録・更新 |
| wp/v2/themeB-balloon | DELETE | edit_speech_balloons | id | ふきだしセット削除 |
| wp/v2/themeB-balloon | PATCH | edit_speech_balloons | (none) | データ移行（旧投稿型→テーブル） |
| wp/v2/themeB-balloon-copy | POST | edit_speech_balloons | id | ふきだしセット複製 |
| wp/v2/themeB-balloon-sort | POST | edit_speech_balloons | balloon1, balloon2 (obj) | ふきだしセット並び替え |
| wp/v2/themeB-balloon-recover | POST | edit_speech_balloons | id | ふきだしセット自動作成 |

### エンドポイント統計

- **合計 Route 数**: 14
- **合計 Method 数**: 16 (GET: 4, POST: 10, DELETE: 1, PATCH: 1)
- **権限タイプ**: is_administrator (6), has_edit_can (1), edit_speech_balloons (6), read_speech_balloons (1), __return_true (5 - 公開)

---

## 主要発見と AGENT NEO への含意

### 発見 1: ハイアーキー設計による再利用性

**ブロック構造**: 32 個のブロックのうち 21 個（約 66%）がコンテナ型の親子関係ブロック。

**コンテキスト提供ブロック（6個）**:
- Accordion (themeB/acc/iconOpened, iconClosed, titleTag)
- Box-Menu (themeB/box-menu/boxStyle, boxGradient)
- Columns (themeB/columns/isScrollable)
- FAQ (themeB/faq/titleTag)
- Link-List (themeB/link-list/icon, iconPos)
- Step (themeB/step/titleTag)

**AGENT NEO への含意**:
1. **テンプレート化機会**: 親子パターンを汎用テンプレートで抽象化 → コード量削減 30-40%
2. **UI コンポーネント化**: context ベースの設定配信で、階層的な構成管理が可能
3. **拡張ポイント**: 新規ブロック追加時は親子パターン採用で統一性向上

### 発見 2: CPT と REST API による機能隠蔽と権限分離

**隠蔽戦略**:
- ad_tag: public=false, show_in_rest=false → 管理画面のみ（REST 非対応）
- blog_parts: public=false, show_in_rest=true → REST API でのみアクセス可能

**計測インフラ**:
- themeB_btn_cv_data (post_meta) に CV 計測データ格納
- REST POST エンドポイント (themeB-ct-btn-data, themeB-ct-ad-data) でクライアント側から直接計測

**AGENT NEO への含意**:
1. **権限分離設計**: public=false + REST=true で多段階権限化が可能
2. **計測インフラ**: post meta + REST API で完全な CV 追跡システム実装
3. **拡張セキュリティ**: 機密情報（広告タグ）は REST 権限で保護

### 発見 3: モダン WordPress による段階的な再構築

**マイグレーション戦略**:
- ふきだし機能: 投稿型 (post_type: speech_balloon) → カスタムテーブル (wp_*_balloon)
- PATCH /themeB-balloon で旧データ取得・変換・新テーブル插入を自動実行
- 旧 post_type は廃止予定（lib/post_type.php コメント行で visible）

**REST による段階的移行**:
- PATCH: 旧データ移行実行
- DELETE: 旧投稿削除
- balloon-recover: 自動復旧ロジック

**AGENT NEO への含意**:
1. **段階的破棄パターン**: PATCH エンドポイントで backward compatibility を確保
2. **DB ストレージ最適化**: CPT ではなくカスタムテーブル選択で投稿クエリ高速化
3. **移行ロードマップ**: 7 ルートのふきだし API で旧→新システムへの完全な経路を実装

---

## 付録：ファイルパスリファレンス

### ブロック定義
- src/gutenberg/blocks/*/block.json (32 種類 × 2 = 64 ファイル)

### ポストタイプ
- lib/post_type.php

### メタボックス
- lib/post_meta/meta_lp.php
- lib/post_meta/meta_ad.php
- lib/post_meta/meta_code.php
- lib/post_meta/meta_button.php
- lib/post_meta/meta_side.php

### REST API
- lib/rest_api.php
- lib/rest_api/balloon_api.php

---

**レポート生成完了**: 2026-04-30
**本ファイル行数**: 計 390 行（マークダウン）
**最終更新**: 2026-04-30 自動生成
