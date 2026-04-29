# JIN:R親テーマ実コードSEO解析

## 1. 解析対象

| 項目 | 内容 |
|---|---|
| ZIP | `jinr-20260428T161343Z-3-001.zip` |
| 展開先 | `jinr-parent/jinr/jinr` |
| ファイル規模 | 305 files / 約10MB |
| PHP | 77 files |
| 主要SEOファイル | `include/head/*`, `include/json-ld.php`, `include/jinr-setting.php`, `include/custom-functions.php` |

## 2. 結論

JIN:Rは、SEOをテーマ中核に内包している。SWELLのようにSEO SIMPLE PACK前提で分離する思想ではなく、テーマ自身が以下を直接出力・管理する。

- title
- description
- canonical
- robots noindex
- keywords
- OGP
- Twitter Card
- JSON-LD
- 著者情報/SNS sameAs
- パンくず
- SEO設定管理画面
- 記事別SEOメタのREST公開

AGENT NEOのSEO Coreは、JIN:Rの「統合SEO UX」を採用する判断でよい。ただしJIN:RはクラシックPHPテンプレート型なので、AGENT NEOではFSE/theme.json/block.json/REST/MCP/WP CLIに合わせて再設計する。

## 3. head出力構造

`header.php` でSEO関連テンプレートを明示的に読み込む。

| 読込順 | ファイル | 役割 |
|---|---|---|
| 1 | `include/head/ogp.php` | OGP type/title/url/description/image |
| 2 | `include/head/description.php` | meta description |
| 3 | `include/head/noindex.php` | robots noindex |
| 4 | `include/head/keywords.php` | meta keywords |
| 5 | `include/head/others.php` | canonical、og:site_name、fb:app_id、twitter card、favicon |
| 6 | `include/head/tags.php` | AdSense、Analytics、Search Console、head任意タグ |
| 7 | `wp_head()` | WordPress標準hook |
| 8 | `include/head/preload.php` | preload |

設計上のポイントは、`wp_head()` より前にテーマ独自のSEOタグを直接出していること。AI操作前提では、AGENT NEOもSEO出力の順序と重複検知を明示的に管理する必要がある。

## 4. title設計

`include/head/title.php` は `add_theme_support('title-tag')` を有効化し、`pre_get_document_title` でtitleを制御する。

| 条件 | titleの決定 |
|---|---|
| home | site name + separator + site description |
| front page | `_jinr_seotitle_display` があれば優先 |
| single/page | `_jinr_seotitle_display` があれば優先。なければ post title + separator + site name |
| category | 対応固定ページで作り込み済みなら、その固定ページのSEO titleを使う |
| tag/date/search/404 | テーマ側で生成 |

AGENT NEOへの取り込み:

- `seo.title` は投稿/LP/分類/検索系テンプレート単位で制御する。
- `titleにサイト名を含ませない` のようなルールを、単なるUI設定ではなく `titleTemplate` 契約にする。

## 5. description設計

`include/head/description.php` は、投稿/固定ページ/カテゴリでdescriptionを出し分ける。

優先順位:

1. `_jinr_description_display`
2. 抜粋
3. `jinr_auto_desc_func()` による本文先頭120文字
4. トップページでは `jinr__desc_text`

`jinr_auto_desc_func()` はショートコードを除去し、HTMLタグを落として120文字に丸める。

AGENT NEOへの取り込み:

- descriptionはAI生成値、手動値、自動抜粋値を分離して保持する。
- 自動生成値は `source: ai | manual | excerpt | fallback` を持たせる。

## 6. canonical設計

JIN:Rは `include/custom-functions.php` でWordPress標準の `rel_canonical` を削除し、`include/head/others.php` で独自canonicalを出力する。

| 条件 | canonical |
|---|---|
| home | site URL |
| category | category link |
| page/singular | permalink |
| page/singularで `_jinr_canonical_display` あり | カスタムcanonical優先 |
| 404 | `/404` |
| その他 | site URL |

AGENT NEOへの取り込み:

- canonicalはP0。
- 標準canonical、SEOプラグインcanonical、AGENT NEO canonicalの重複検知が必須。
- AIによるcanonical変更は危険操作としてdryRun/diffReview/rollback対象にする。

## 7. noindex設計

`include/head/noindex.php` は投稿別メタとアーカイブ一括設定を組み合わせている。

| 対象 | 制御元 |
|---|---|
| single/page | `_jinr_noindex_display` |
| category | `jinr_category_noindex`、除外ID、ページネーション |
| tag | `jinr_tag_noindex`、除外ID、ページネーション |
| home pagination | `jinr_top_next_noindex` |
| date archive | `jinr_date_archive_noindex` |
| search | `jinr_search_page_noindex` |
| attachment | `jinr_image_page_noindex` |
| author | `jinr_author_noindex` |
| 404 | 常にnoindex |

JIN:Rの強みは、低品質・重複・薄いページになりやすいWP標準アーカイブをテーマ管理画面から制御できる点。

AGENT NEOへの取り込み:

- robots設定は個別ページとテンプレート種別の2層に分ける。
- `indexabilityPolicy` として `post`, `page`, `lp`, `category`, `tag`, `author`, `date`, `search`, `attachment`, `pagination` を持つ。

## 8. OGP/Twitter Card設計

`include/head/ogp.php` はページ種別ごとにOGPを出力し、`include/head/others.php` はTwitter Cardと `fb:app_id` を出す。

| 項目 | 内容 |
|---|---|
| og:type | home/frontはwebsite、それ以外はarticle |
| og:title | SEO title優先 |
| og:description | SEO description、トップdescription、抜粋、自動description |
| og:url | home/permalink/current URL |
| og:image | トップOGP画像、アイキャッチ、noimage fallback |
| twitter:card | カスタマイザー設定 |
| twitter:site | カスタマイザー設定 |

AGENT NEOへの取り込み:

- OGPはSEO Coreに含める。
- `ogp.image` は投稿アイキャッチ、LP専用画像、サイト標準画像、fallbackを明示的に優先順位化する。
- SNSキャッシュ確認導線は管理画面に置く価値がある。

## 9. JSON-LD設計

`include/json-ld.php` は `wp_footer` でJSON-LDを出力する。カスタマイザーの `jinr__reading_jsonld` がfalseの場合に読み込まれる。

| 対象 | schema type |
|---|---|
| single | Article |
| page | WebPage |
| front/home | WebSite |
| category/tag/date | CollectionPage |
| breadcrumb | BreadcrumbList |
| author | Person |
| publisher | Organization |

著者にはプロフィール名、肩書き、プロフィールURL、SNS同一性情報が入り、publisherにはサイト名、URL、ロゴが入る。

弱点:

- `@graph` ではなく、BreadcrumbListとページEntityを別scriptで出す。
- BreadcrumbListの `itemListElement` が配列の入れ子になりやすい。
- Product、Review、Offer、FAQPageは見当たらない。
- FAQブロック連動のJSON-LDは確認できない。

AGENT NEOへの取り込み:

- JIN:Rの「テーマ標準JSON-LD」思想は採用する。
- 実装はSWELL寄りの `@graph` 方式にして、ページEntity、Breadcrumb、Organization、Person、Product、Offer、Review、FAQを一つのEntity Graphとして構築する。

## 10. 設定管理

`include/jinr-setting.php` は `JINR設定` 管理画面を追加し、SEO設定、広告、計測タグを同一管理画面で扱う。

SEO設定として確認できる主な項目:

- トップページ2ページ目以降noindex
- カテゴリ一覧noindex
- カテゴリ除外ID
- カテゴリページネーションnoindex
- タグ一覧noindex
- タグ除外ID
- タグページネーションnoindex
- 年月日アーカイブnoindex
- 画像ページnoindex
- 著者アーカイブnoindex
- 検索結果noindex
- パンくずHOME文言変更
- パンくず非表示
- トップページmeta keywords
- 見かけ上のタイトル/サブタイトル
- タイトル区切り
- titleタグにサイト名を含ませない

AGENT NEOへの取り込み:

- SEO設定は `SEO Core` 画面として独立させる。
- 人間UIだけではなくREST/MCP/WP CLIで同じ設定を操作できるようにする。

## 11. REST公開メタ

`include/custom-functions.php` でSEO関連post metaが `show_in_rest: true` で登録されている。

| meta key | 型 | 用途 |
|---|---|---|
| `_jinr_seotitle_display` | string | 記事別SEO title |
| `_jinr_description_display` | string | 記事別description |
| `_jinr_keyword_display` | string | keywords |
| `_jinr_hastag_display` | string | SNSハッシュタグ |
| `_jinr_canonical_display` | string | canonical |
| `_jinr_noindex_display` | boolean | noindex |

これはAGENT NEOにとって重要。JIN:Rは人間GUIだけでなく、WP REST API経由でSEOメタを編集できる土台を持っている。

AGENT NEOでは、これをさらに進めて `seo-meta.schema.json` と `POST /wp-json/agent-neo/v1/seo/meta` に統合する。

## 12. 計測タグ/広告タグ設計

`include/head/tags.php` は以下をheadへ直接出す。

- AdSense tag
- Analytics tag
- Search Console tag
- 任意head tag

body開始直後には `jinr_body_start_tag`、body終了側にも任意タグがある。

AGENT NEOへの取り込み:

- 計測タグを許可する設計は必要。
- ただし、任意HTMLをそのまま出す方式ではなく、連携先ごとのadapter、許可タグ、capability、監査ログを持つ。

## 13. セキュリティ/品質上の注意

JIN:Rの実装は参考にできるが、AGENT NEOではそのまま踏襲してはいけない点がある。

| 観点 | 観測 | AGENT NEOでの対策 |
|---|---|---|
| 外部URL取得 | `jinr/external_url` が公開RESTで `file_get_contents($post_url)` を実行 | SSRF対策、URL allowlist、timeout、HTTP API利用、rate limit |
| 任意タグ出力 | head/bodyタグ、広告タグを raw echo | role/capability、sanitize/allowlist、監査ログ、危険操作警告 |
| SEOメタ出力 | `get_post_meta` や `get_option` を直接echoする箇所が多い | esc_attr/esc_url/wp_ksesを徹底 |
| JSON-LD | json_encodeのみでscript出力 | wp_json_encode、schema validation、型別必須項目検証 |
| register_setting | sanitize_callbackが無い設定が多い | setting schema + sanitize_callback必須 |

## 14. AGENT NEO設計への反映

| 採用 | 内容 |
|---|---|
| 採用 | SEOをテーマ標準機能にする |
| 採用 | 記事別SEO title/description/canonical/noindexをREST操作可能にする |
| 採用 | noindexを個別ページ + アーカイブテンプレートの二層で管理する |
| 採用 | OGP/Twitter CardをSEO Coreに含める |
| 採用 | 著者/SNS/profileをPerson Entityへ反映する |
| 改良 | JSON-LDは `@graph` 方式で統合する |
| 改良 | Product/Review/Offer/FAQを追加する |
| 改良 | raw tag出力ではなくadapter/allowlist方式にする |
| 改良 | FSE/block.json/theme.json/JSON契約で再実装する |
| 不採用 | meta keywordsを主要SEO機能として扱う |

## 15. Gate判定

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | JIN:R親テーマZIPを展開し、SEO関連ファイルを確認 |
| RG1 | passed | head出力、post meta、settings、REST公開メタを抽出 |
| RG2 | passed | JIN:R統合SEO UXのAs-Is設計を復元 |
| RG3 | passed | ユーザー仮説「JIN:RのほうがSEO設計が良い」を実コードで支持 |
| R4 | passed | AGENT NEOのSEO Core方針へ反映可能な採用/改良/不採用を分類 |

