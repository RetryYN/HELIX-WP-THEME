# THEME-INV-03 レポート — 広告 / CV ゾーン仕様の横断確定

- 対象イシュー: `issues/THEME-INV-03-ad-cv-zones.md`
- 状態: **①（正規化）完了 / ②（スキーマ差分の提案）完了 / ③（条件表示の実装確認）一部 /
  ④（本番のウィジェット実配置）は未了**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/theme-features-raw.txt`（両テーマの `register_sidebar` / sidebar id）・
  `evidence/re-themeA-render.txt`（`single.php` のウィジェット呼び出し位置）・
  `evidence/re-themeA-ads.txt`（`themeA_h2_ads_concert` 本体）・
  `evidence/re-themeB-blocks.txt`（`Pre_Parse_Blocks` のウィジェットエリア総当たり）・
  `evidence/probe5-raw.txt`（テーマB `parts/` 構成）

## 1. ① ゾーンの正規化

**位置の意味**で正規化し、3 系統の名前を対応させる。

### 1.1 記事本文まわり

| 正規化 ID | 意味 | テーマA | テーマB | AGENT NEO |
|---|---|---|---|---|
| `article_before` | 記事の前（ヘッダより上） | `post-top-widget` | `single_top` | — |
| `article_head` | タイトル直下・本文の直前 | `post-start-widget` | — | `post-header` パート |
| `content_before_h2` | **本文中・最初の h2 の直前** | `themeA_h2_ads_concert`（フィルタ） | `toc_ad`（目次と連動） | **`ad-zone.schema.json` の `before_h2`** |
| `content_toc_before` / `content_toc_after` | 目次の前 / 後 | — | `toc_ad_position` 設定で前後指定 | — |
| `article_foot` | 本文の直後 | `post-end-widget` | `single_bottom` | `post-footer` パート |
| `article_cta` | 記事末の CTA 枠 | `ad-finish.php`（テンプレート） | **`single_cta`** | `article-cta` パターン |
| `related_before` | 関連記事の前 | — | `before_related` | — |
| `related_after` | 関連記事の後 | `relatedpost-bottom-widget` | `after_related` | — |
| `article_after` | 記事全体の後 | `post-bottom-widget` | — | — |
| （関連記事内） | 関連記事枠そのもの | `ad-related.php` | `parts/single/related_post_list.php` | `class-related-query.php` |

### 1.2 サイドバー

| 正規化 ID | 意味 | テーマA | テーマB |
|---|---|---|---|
| `sidebar_main` | サイドバー本体 | `sidebar` | `sidebar-1` |
| `sidebar_top` | サイドバー上部固定 | — | `sidebar_top` |
| `sidebar_sticky` | **追尾（スクロール追従）** | **`sidebar-tracking`** | **`fix_sidebar`** |
| `sidebar_sp` | スマホ時のサイドバー | — | `sidebar_sp` |

### 1.3 サイト共通

| 正規化 ID | 意味 | テーマA | テーマB |
|---|---|---|---|
| `header_box` | ヘッダ直下 | （`object/informationbar.php`） | `head_box` |
| `footer_main` | フッター | `footer-widget` | `footer_box1` / `footer_box2` / `footer_box3` |
| `footer_sp` | スマホ時フッター | — | `footer_sp` |
| `footer_before` | フッター直前 | — | `before_footer` |
| `nav_drawer` | ハンバーガー / SP メニュー内 | `hamburger-widget` | `sp_menu_bottom` |
| `bottom_fixed` | **画面下部固定** | （`object/cvbutton.php`・SP CV ボタン） | **`fix_bottom_menu`**（メニュー） |
| `front_top` / `front_bottom` | トップページ上部 / 下部 | `toppage-widget` | `front_top` / `front_bottom` |
| `page_top` / `page_bottom` | 固定ページ上部 / 下部 | — | `page_top` / `page_bottom` |
| `archive_head` | アーカイブ見出し部 | （`archive-subtitle` CSS 変数あり） | `parts/archive/term_head.php` |

### 1.4 正規化の結果

**正規化ゾーン 23 種**（記事まわり 10・サイドバー 4・サイト共通 9）。
内訳は テーマA 11 / テーマB 24 の和集合から意味単位で統合したもの。

**両方にあるゾーン（＝汎用性が高い）**: `article_before` / `article_foot` / `related_after` /
`sidebar_main` / **`sidebar_sticky`** / `footer_main` / `nav_drawer` / `front_top`

**テーマB のみ**: `sidebar_top` / `sidebar_sp` / `footer_sp` / `footer_before` /
`page_top` / `page_bottom` / `related_before` / `article_cta`（専用ウィジェット）/ `head_box`

**テーマA のみ**: `article_head`（`post-start-widget`）/ `article_after`（`post-bottom-widget`）

## 2. ② `ad-zone.schema.json` との差分

### 2.1 スキーマの現状

```json
"description": "広告ゾーン定義スキーマ。CARRY-A2-001: テーマA の4ゾーン
                (h2前挿入/記事終/関連上/カテゴリ別上書き)に対応する静的管理。REQ-NF-025厳守。",
"required": ["zone_id", "zone_name", "position"],
"additionalProperties": false,
"properties": {
  "zone_id": { "type": "string",
    "description": "ゾーン識別子（slug 形式）。例: before_h2, after_article, above_related, category_override",
    "pattern": "^[a-z0-9_-]+$" },
  ...
```

### 2.2 実測との差分（3 点）

**差分 1 — 「4 ゾーン」の粒度が間違っている**

スキーマは `before_h2` / `after_article` / `above_related` / `category_override` を
**同列の 4 ゾーン**として例示している。しかし実装（`evidence/re-themeA-ads.txt`）を読むと:

- `category_override` は**ゾーンではなく、ゾーンに対する「上書き規則」**。
  `themeA_h2_ads_concert()` の中で、`before_h2` ゾーンの広告コードを
  カテゴリ 4 スロット（`themeA_choise_category_1`〜`_4`）で差し替える仕組み。
- `after_article` / `above_related` は同関数ではなく
  `ad-finish.php` / `ad-related.php` とウィジェットエリアが担う。

→ **`zone_id` の語彙に `category_override` を並べるのは誤り。**
   上書きは別のプロパティ（`overrides`）としてモデル化すべき。

**差分 2 — 正規化 23 ゾーンのうち 20 が語彙に無い**

スキーマの例示は 3 ゾーン（+1 誤り）だけ。実測では最低でも次が要る:

`sidebar_sticky`（両テーマにあり CV の主力面）/ `article_cta` / `bottom_fixed` /
`related_before` / `article_head` / `footer_*` / `nav_drawer` / `front_*` / `page_*` /
`content_toc_before` / `content_toc_after`

**差分 3 — 条件表示のモデルが無い**

実装が持っている条件が、スキーマに表現されていない:

| 条件 | テーマA の実装 | テーマB の実装 |
|---|---|---|
| 記事単位のオプトアウト | post meta `_themeA_ads_display == '1'` で中断 | （未確認） |
| 投稿タイプ限定 | `is_single() && post_type === 'post'` | — |
| カテゴリ別の差し替え | 4 スロット（親子カテゴリ解決つき） | — |
| デバイス別表示 | `themeA_h2_sp_display` / `--*-sp-display` | — |
| ログイン状態 | — | `[only_login]` / `[only_logout]` |

### 2.3 拡張提案

```jsonc
{
  "zone_id": "sidebar_sticky",           // 正規化 23 ゾーンの語彙から
  "zone_name": "追尾サイドバー",
  "position": { "anchor": "sidebar", "placement": "sticky" },
  "conditions": {                         // ★ 追加
    "post_types": ["post"],
    "devices": ["pc", "sp"],
    "exclude_by_meta": "_ads_display",
    "auth": "any"
  },
  "overrides": [                          // ★ 追加（category_override をここへ）
    { "match": { "taxonomy": "category", "terms": [12], "include_children": true },
      "creative_ref": "ad_tag:34" }
  ],
  "creative_ref": "ad_tag:12",            // ★ 実体は参照（INV-04 の決着と整合）
  "fallback": "none"
}
```

**設計上の要点 3 つ**

1. **`creative_ref` は参照にする。** テーマA は `get_option('themeA_h2_ads_code')` の
   **生 HTML をそのまま出力**している（エスケープなし）。広告コードを設定値に直接埋めると
   スキーマ検証も差し替え管理もできない。テーマB の CPT `ad_tag` 方式（`show_in_rest`）に寄せ、
   ゾーン定義は**参照だけ**を持つ（`reports/INV-04-reusable-parts-mechanism.md` の決着と同型）。
2. **`overrides` は配列にして評価順を明示する。** テーマA の 4 スロットは
   `array_intersect` / `array_diff` と可変変数で「どれが勝つか」を決めており、
   **規則が読めない**。配列の先頭一致（first-match-wins）にすれば決定論になる。
3. **`conditions` に `auth` を持たせない選択もある。** ログイン状態依存は
   決定論レンダラの外（`reports/INV-07-content-filter-and-toc.md` §2 の表示時レイヤ）。
   ゾーン定義には持たせず、表示層で解決するほうが一貫する。

## 3. ③ 条件表示の実装実態（一部確認）

`themeA_h2_ads_concert()` から確認できた条件（`evidence/re-themeA-ads.txt`）:

```php
$post_ads_display_settings = get_post_meta(get_the_ID(), '_themeA_ads_display', true);
if ($post_ads_display_settings == '1') { return $the_content; }      // 記事単位オプトアウト

$post_type = get_post_type();
if (is_single() && $post_type == 'post') { …                          // 投稿限定

// カテゴリーが複数設定されている場合、カテゴリーIDの小さい方を読み込むようにする
$ids = array_column($categories, 'term_id');
array_multisort($ids, SORT_ASC, $categories);
$cat_current = $categories[0];                                        // 複数カテゴリの決定規則

if ($themeA_h2_sp_display == '1') { … }                                 // デバイス別
```

**「複数カテゴリなら term_id が小さい方」**という決定規則は、
正規化スキーマでも明示する必要がある（暗黙にすると移管で挙動が変わる）。

**未確認**: テーマB 側の条件表示（ウィジェットの表示条件プラグインを使っている可能性）。

## 4. ④ 本番の実配置（追試・非ブロッキング）

「どのゾーンに実際に何が入っているか」の本番実測は保留中。これが分かると
**第一級ゾーン（実際に使われている）と後回しゾーン**を証跡で切り分けられる。
ただしゾーン仕様（§1-3）は 3 テーマ横断で確定済であり、占有状況は
**優先度付けの精緻化**にすぎず INV-03 の要求出力には影響しない（実測は本番 read-only の `option get ... | wc -c` 有無確認のみ・値は記録しない）。

取得方法（シェル復帰後）:

```
# ウィジェットの配置状況（読み取りのみ）
wp option get sidebars_widgets --format=json --path=~/site-A.example/public_html
wp option get sidebars_widgets --format=json --path=~/site-B.example/public_html

# 広告コードが入っているかの有無だけ確認（値は出さない）
wp option get themeA_h2_ads_code --path=~/site-A.example/public_html | wc -c
```

> `themeA_h2_ads_code` は広告タグ（アフィリエイト ID を含みうる）。
> **値は取得・記録せず、長さや有無だけ**を見る。

## 5. 未了項目

- [ ] ④ 本番のウィジェット実配置と広告コードの有無
- [ ] テーマB 側の条件表示の実装確認
- [ ] `ad-finish.php`（215 行）/ `ad-related.php`（233 行）の精読 —
      `after_article` / `above_related` ゾーンの実装詳細
- [ ] `object/cvbutton.php`（183 行）の精読 — `bottom_fixed`（SP CV ボタン）の条件と設定
- [ ] `ad-zone.schema.json` の改訂案の PO 承認（cross-repo のため反映は承認後）

## 6. 証跡ファイル

| 内容 | 場所 |
|---|---|
| 両テーマの `register_sidebar` / sidebar id 一覧 | `evidence/theme-features-raw.txt` |
| `single.php` のウィジェット呼び出し位置 | `evidence/re-themeA-render.txt` |
| `themeA_h2_ads_concert` 本体（条件・カテゴリ解決・挿入） | `evidence/re-themeA-ads.txt` |
| テーマB のウィジェットエリア総当たり（`Pre_Parse_Blocks`） | `evidence/re-themeB-blocks.txt` |
| テーマB `parts/` 54 ファイルの構成 | `evidence/probe5-raw.txt` |
| `ad-zone.schema.json` の現行記述 | `03-structure-agent-neo.md` §3 |

## 2026-08-27 実測の反映

`evidence/option-and-sidebars-raw.txt` で本番の `sidebars_widgets` を read-only 実測した。

| 項目 | 実測結果 |
|---|---|
| 登録サイドバー | 12 |
| 実配置の領域 | 5 領域 |
| 実配置の内訳 | `toppage` 1 / `post-top` 1 / `post-end` 3 / `sidebar` 11 / `sidebar-tracking` 1 |
| 空の領域 | 6 領域 |

よって §4 ④ の「本番実配置は保留中」という採取前の状態は解消され、
正しい結論は **5 領域・sidebar 11・sidebar-tracking 1** である。
ウィジェット総数の 3 分の 2 が `sidebar` に集中しており、sidebar 系は第一級ゾーンから外せない。
これは 08-27 要約で一時的に「4 領域、sidebar 3+」とされた内容の訂正でもある。
広告ゾーン語彙・条件表示のスキーマ改訂案を採用するかどうかは PO 判断に残る。
