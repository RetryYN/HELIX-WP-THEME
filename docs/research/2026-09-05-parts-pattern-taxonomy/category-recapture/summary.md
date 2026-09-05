# カテゴリ一覧ページ デザイン観察リサーチ（R17）集計（最新: codex-astra 10巡目反映・summary は gen_summary.py の全生成）

固有名・URLは書かない（mapping.jsonのみに記録）。id は observations.json 参照。
本ファイルは `python3 gen_summary.py > summary.md` で observations.json から**全文を生成**する。本台帳の集計値（件数・%・区分 n）はすべてスクリプト計算値。既存台帳からの引用値と語彙の定義上の数値は計算対象外で、その旨を各所に明記する。
### 対応履歴（過去の巡回。件数は当時の値で、現在の値は §1 を正とする）
- 10巡目: ranking の優先順（本体側 > sidebar）を定義し assert を全 entry 対象に。1col と sidebar 併存の位置は未確認と明記。訂正件数を 14 件に訂正。
- 9巡目: ranking の記録基準を統一（sidebar に popular-ranking がある entry は ranking=sidebar。none との併存を全 14 件（主集計内 12・主集計外 2）訂正）。columns_pc と sidebar の意味を §0 に定義。既存台帳との比較語を数値併記のみに変更。
- 8巡目: E12 の sidebar から重複した none を除去（sidebar=none の過大計上 1 件を是正）。
- 7巡目: title 注記の説明を G05（date+title）と矛盾しない表現に修正。
- 6巡目: card_elements の `title` は記録基準が全件で揃っていないため比率解釈対象外と明記。更新履歴を最新と過去に分離。
- 5巡目: A04・G08 を card_elements=[title] に訂正して採用、G07 を empty-list-at-observation で除外。区分別件数・変動幅を集計から生成。`line` 語彙コードの注記。
- 4巡目: 適格判定の追跡条件を §0 に明文化。A05・D07（記事カードなし）・D04（利用者投稿まとめ）を除外。『多数派』を撤回。
codex-astra 3巡目（2026-09-06）の指摘反映（当時）:
- 主集計の適格基準を「**編集記事の一覧ページ**（記事一覧）」と明文化し、境界例 D08（媒体を列挙する一覧記事）と E02（利用者投稿の一覧）を page_kind で主集計から除外・別掲
- 区分別の説明文の件数を機械集計と一致させる（A の name-desc-image、C の minihome 分母、grid 系合計の名称と範囲、E の pagination）
- 「最も定型的」等の解釈語を削り、既存台帳の引用値は出典を明示して「本台帳からは再計算不能」と注記

## 0. 主集計の適格基準

項目の意味（Astra 9巡目で明文化）: `ranking` はランキング表示の位置で、複数位置に併存する場合は一覧本体側を優先して 1 値を記録する（優先順: top=一覧上部 > inline=一覧内 > bottom=一覧下 > sidebar=補助列 > none）。したがって sidebar に popular-ranking がある entry は ranking≠none（本体側にあれば top/inline/bottom、無ければ sidebar）。全 entry を対象に assert で担保。`columns_pc` は一覧本体の列構成（1col=一覧が全幅）、`sidebar` は一覧の外にある補助ウィジェットの内容で、位置（右列か一覧の下か）は記録していない。したがって 1col と sidebar ありは併存し得る（配置位置は未確認。矛盾とは扱わない。該当 6 件）。
語彙コードの注記: cta=line 等の `line` は「メッセージングアプリ導線」を表す既存語彙コードで、第三者サービス名としての記述ではない（台帳間で語彙を揃えるため保持）。

- fetched=full（本文 HTML を取得できた）かつ page_kind なし。
- page_kind は「取得はできたが観察対象として不適格」の理由コード: `product-list`（商品カタログ・EC トップ）、`not-a-category-list`（診断ツール・トップページ等で記事一覧のカード構造がない）、`listicle-article`（複数の対象を列挙する **一覧記事**。記事本文であって記事一覧ではない）、`ugc-post-list`（利用者投稿の一覧。編集記事の一覧ではない）。
- 「記事一覧」= サイト運営者が編集した記事（コラム・ニュース・お知らせ・ブログ）が一覧カードで並ぶページ。判定の追跡条件: card_elements が空（記事カードが無い）なら not-a-category-list、利用者投稿（口コミ・コーディネート・まとめ）の一覧なら ugc-post-list、複数対象を列挙する記事本文なら listicle-article。4巡目で A05・D07（card_elements 空）・D04（利用者投稿まとめ）を除外。5巡目: 記事タイトルのテキストリンクだけの一覧は card_elements=[title] として採用（A04・G08 を訂正）、取得時に一覧が空で観察できなかったものは empty-list-at-observation として除外（G07）。主集計に card_elements 空の entry は残さない。

## 1. 件数の内訳（fetched / page_kind / 主集計対象）

observations.json 全体は **69件**。

```
total_all_entries: 69
fetched_counts_all_entries: {'full': 67, 'partial': 2}
page_kind_counts_all_entries: {'main': 55, 'not-a-category-list': 4, 'ugc-post-list': 2, 'product-list': 6, 'listicle-article': 1, 'empty-list-at-observation': 1}
main_pool_size: 54
excluded_entries:
  A03 partial not-a-category-list
  A05 full not-a-category-list
  A06 full not-a-category-list
  C02 partial None
  D04 full ugc-post-list
  D07 full not-a-category-list
  D08 full listicle-article
  E02 full ugc-post-list
  E03 full product-list
  E04 full product-list
  E05 full product-list
  E06 full product-list
  E07 full product-list
  E08 full product-list
  G07 full empty-list-at-observation
by_pattern_main: {'A': 7, 'B': 8, 'C': 8, 'D': 5, 'E': 7, 'F': 8, 'G': 11}
```

- fetched: full 67 / partial 2（partial は A03, C02）。
- 除外の重複加算なし（1 entry に理由は 1 つ。A03 は partial かつ not-a-category-list だが 1 件として数える）。
- 区分: A=比較・アフィリエイト、B=ニュース、C=企業オウンドメディア、D=ポータル、E=EC の記事一覧（コラム/特集/お知らせ）、F=個人・専門ブログ、G=官公庁・大学・団体。区分別件数は A 7, B 8, C 8, D 5, E 7, F 8, G 11（G が多いのは自治体サイトが取得しやすかった結果で、意図的な積み増しではない）。

## 2. 観察項目の型内訳（主集計 n=54、na 除外・分母併記、機械集計そのまま）

```
[header] valid_n=54 na=0 total=54
   name-count: 37 (68.5%)
   name-only: 9 (16.7%)
   name-desc: 5 (9.3%)
   name-desc-image: 3 (5.6%)
[lead] valid_n=54 na=0 total=54
   none: 42 (77.8%)
   lead-text: 7 (13.0%)
   editorial-article: 5 (9.3%)
[children_nav] valid_n=53 na=1 total=54
   chips: 21 (39.6%)
   none: 20 (37.7%)
   sidebar-tree: 8 (15.1%)
   cards: 3 (5.7%)
   image-banners: 1 (1.9%)
[list_layout] valid_n=54 na=0 total=54
   grid-3: 23 (42.6%)
   text-list: 21 (38.9%)
   featured-plus-grid: 4 (7.4%)
   grid-2: 3 (5.6%)
   thumb-list: 2 (3.7%)
   timeline: 1 (1.9%)
[columns_pc] valid_n=54 na=0 total=54
   2col-sidebar-right: 37 (68.5%)
   1col: 17 (31.5%)
[filter_sort] valid_n=54 na=0 total=54
   none: 34 (63.0%)
   tabs: 8 (14.8%)
   year-filter: 6 (11.1%)
   tag-filter: 5 (9.3%)
   sort-select: 1 (1.9%)
[pagination] valid_n=54 na=0 total=54
   numbers: 23 (42.6%)
   none: 21 (38.9%)
   load-more: 5 (9.3%)
   prev-next: 5 (9.3%)
[ranking] valid_n=54 na=0 total=54
   sidebar: 24 (44.4%)
   none: 23 (42.6%)
   top: 7 (13.0%)
[pickup] valid_n=54 na=0 total=54
   none: 28 (51.9%)
   top-featured: 19 (35.2%)
   editor-pick-box: 7 (13.0%)
[minihome] valid_n=47 na=7 total=54
   yes: 28 (59.6%)
   none: 19 (40.4%)
[cta] valid_n=54 na=0 total=54
   none: 29 (53.7%)
   lp-banner: 12 (22.2%)
   newsletter: 6 (11.1%)
   line: 4 (7.4%)
   app-download: 3 (5.6%)
[count_per_page] na=16/54
[card_elements] denom(n_main)=54
   date: 41 (75.9%)
   category-chip: 34 (63.0%)
   excerpt: 32 (59.3%)
   image: 32 (59.3%)
   author: 6 (11.1%)
   new-badge: 6 (11.1%)
   tags: 6 (11.1%)
   rank-badge: 3 (5.6%)
   title: 3 (5.6%)
   updated: 3 (5.6%)
   pr-badge: 2 (3.7%)
   view-count: 1 (1.9%)
[sidebar] denom(n_main)=54
   categories: 36 (66.7%)
   popular-ranking: 31 (57.4%)
   cta-banner: 18 (33.3%)
   archive-month: 11 (20.4%)
   none: 11 (20.4%)
   search: 10 (18.5%)
   new-posts: 8 (14.8%)
   profile: 7 (13.0%)
   tags: 7 (13.0%)
   sns-follow: 3 (5.6%)
```

card_elements の `title` は一部の entry（A04・G05・G08）でのみ明示記録され、他の entry ではタイトルの有無を記録していない（全件で記録基準が揃っていない）。したがって `title` の行は「タイトルの存在率」ではなく記録の不統一を含むため**比率の解釈対象外**とする。

minihome は none/no の表記ゆれを `none` に統一済み。sidebar は空配列を `["none"]` に統一した上で multi-select 集計（分母は n_main、合計は 100% を超える）。

list_layout の **grid 系合計**（grid-3 + grid-2 + featured-plus-grid。thumb-list は「サムネイル付き縦リスト」でグリッドではないため含めない）= 23+3+4 = 30/54(55.6%)。grid-3 単体は 23/54(42.6%) で過半数ではなく、text-list 21/54(38.9%) と並ぶ。

## 3. 区分別の型内訳（主集計、機械集計そのまま）

```
[header]
  A(n=7): {'name-desc-image': 2, 'name-count': 4, 'name-only': 1}
  B(n=8): {'name-count': 6, 'name-desc': 1, 'name-only': 1}
  C(n=8): {'name-count': 6, 'name-desc': 1, 'name-desc-image': 1}
  D(n=5): {'name-count': 4, 'name-desc': 1}
  E(n=7): {'name-only': 3, 'name-count': 3, 'name-desc': 1}
  F(n=8): {'name-count': 3, 'name-only': 4, 'name-desc': 1}
  G(n=11): {'name-count': 11}
[list_layout]
  A(n=7): {'grid-3': 3, 'text-list': 3, 'grid-2': 1}
  B(n=8): {'text-list': 3, 'featured-plus-grid': 2, 'grid-3': 2, 'thumb-list': 1}
  C(n=8): {'grid-3': 5, 'text-list': 1, 'grid-2': 2}
  D(n=5): {'grid-3': 3, 'text-list': 1, 'featured-plus-grid': 1}
  E(n=7): {'grid-3': 6, 'text-list': 1}
  F(n=8): {'timeline': 1, 'grid-3': 4, 'text-list': 2, 'featured-plus-grid': 1}
  G(n=11): {'text-list': 10, 'thumb-list': 1}
[children_nav]
  A(n=7): {'chips': 2, 'none': 3, 'sidebar-tree': 1, 'cards': 1}
  B(n=8): {'chips': 6, 'none': 2}
  C(n=8): {'chips': 2, 'none': 3, 'na': 1, 'sidebar-tree': 1, 'image-banners': 1}
  D(n=5): {'cards': 1, 'none': 2, 'chips': 2}
  E(n=7): {'none': 2, 'sidebar-tree': 3, 'chips': 2}
  F(n=8): {'sidebar-tree': 3, 'none': 3, 'chips': 1, 'cards': 1}
  G(n=11): {'chips': 6, 'none': 5}
[pagination]
  A(n=7): {'none': 4, 'numbers': 3}
  B(n=8): {'numbers': 3, 'load-more': 2, 'none': 3}
  C(n=8): {'none': 3, 'numbers': 3, 'prev-next': 1, 'load-more': 1}
  D(n=5): {'none': 2, 'numbers': 2, 'load-more': 1}
  E(n=7): {'none': 1, 'numbers': 4, 'load-more': 1, 'prev-next': 1}
  F(n=8): {'prev-next': 3, 'none': 3, 'numbers': 2}
  G(n=11): {'numbers': 6, 'none': 5}
[ranking]
  A(n=7): {'top': 3, 'none': 2, 'sidebar': 2}
  B(n=8): {'sidebar': 6, 'top': 2}
  C(n=8): {'sidebar': 5, 'none': 3}
  D(n=5): {'none': 1, 'sidebar': 2, 'top': 2}
  E(n=7): {'none': 4, 'sidebar': 3}
  F(n=8): {'none': 3, 'sidebar': 5}
  G(n=11): {'none': 10, 'sidebar': 1}
[minihome]
  A(n=7): {'yes': 4, 'na': 1, 'none': 2}
  B(n=8): {'none': 2, 'yes': 4, 'na': 2}
  C(n=8): {'yes': 6, 'na': 1, 'none': 1}
  D(n=5): {'yes': 4, 'none': 1}
  E(n=7): {'none': 4, 'yes': 3}
  F(n=8): {'none': 2, 'yes': 5, 'na': 1}
  G(n=11): {'none': 7, 'na': 2, 'yes': 2}
```

### 区分別に言えること（件数を明示し、断定を避ける。各区分 n は 5〜11 で 1 件の異同が 9〜20 ポイント動く）

- A（比較・アフィリエイト、n=7）: header は name-count 4件・name-desc-image 2件・name-only 1件。name-desc-image は他区分では 1 件で、A の 2/7 をもって「A に多い」とは言えない。ranking=top は 3/7 で全区分中最多。
- B（ニュース、n=8）: children_nav=chips 6/8。list_layout は text-list 3・featured-plus-grid 2・grid-3 2・thumb-list 1 に分散。
- C（企業オウンドメディア、n=8）: list_layout は grid-3 5＋grid-2 2=7/8。minihome=yes は na を除いた 7 件中 6 件（85.7%。na 込みの分母なら 6/8）。
- D（ポータル、n=5）: children_nav は {'cards': 1, 'none': 2, 'chips': 2}。値の種類数は他区分と同程度で「最も多様」とは言えない。
- E（EC 記事一覧、n=7）: list_layout=grid-3 6/7 で全区分中最も高い比率。children_nav は sidebar-tree 3・chips 2・none 2。pagination は numbers 4・load-more 1・prev-next 1・none 1（numbers が最多）。
- F（個人・専門ブログ、n=8）: header=name-only 4/8 で全区分中最多。pagination=prev-next 3/8、children_nav=sidebar-tree 3/8。
- G（官公庁・大学・団体、n=11）: list_layout=text-list 10/11、header=name-count 11/11、ranking=none 10/11。（観察事実のみ。装飾の多寡は評価しない）

## 4. 既存台帳との差分の見立て（仮説として明示）

既存台帳 = リポ内 `docs/research/2026-09-05-parts-pattern-taxonomy/recapture-v2/`（PR #140）のカテゴリ面集計。そこに記載の値（名前のみ 42% / 子導線 chips 29% / ミニ HOME 7〜8% / ランキング none 85%）は**引用**であり、本台帳の JSON からは再計算できない。語彙も区分構成も異なるため、以下は比較条件の違いを含む「Claude 案」。

- header: name-only 9/54(16.7%)、name-count 37/54(68.5%)。既存台帳が name-count を「名前のみ」に含めていたなら 46/54(85.2%) 相当で 42% とは乖離する。語彙定義の突合せをしない限り結論は出せない（Claude 案）。
- children_nav=chips 21/53(39.6%)（na 除外）。既存台帳の引用値は 29%（比較条件が異なるため近い/遠いの判断はしない）。
- pagination: none 21/54(38.9%)、numbers 23/54(42.6%)。区分別では G が numbers 6/11・none 5/11、A が none 4/7、D が none 2/5、E は numbers 4/7 が最多。「番号ページ送りが多数派」という既存記述との差は A・D の none の多さが一因である可能性がある（Claude 案。既存台帳の区分構成が不明で検証不能）。
- minihome=yes 28/47(59.6%)（na 7 件除外）。既存 7〜8% との差は、本回が「視覚的に区切られた子カテゴリ別セクション」まで yes と判定した語彙の緩さが原因の可能性がある（Claude 案。語彙定義の再すり合わせが必要）。
- ranking=none 23/54(42.6%)。既存 85% より低いのは、ranking=top の多い A（3/7）を含む区分構成の違いが一因の可能性（Claude 案）。

## 5. 限界

- 主集計は区分ごと n=5〜11 で代表性は小さい。E は専門小売・工芸品・食品・作業服系の D2C/中堅 EC に偏り、F はガジェット・節約・釣り・筋トレに偏る。
- 大手（ニュース・EC・求人）の多くが 403/DNS/タイムアウトで取得不能だったため中小規模へ切り替えた。区分内の「大手 vs 中小」の軸は中小に偏る。
- SP 差分は全件 na。PC 想定の HTML 読み取りのみ。
- JS 描画で実カードが取得できていない可能性、children_nav / filter_sort の語彙ゆれ（tabs の混入、image-banners の判定）が残る。
- page_kind は WebFetch 経由 1 回の判定に基づく。D08・E02 は 3 巡目で境界例として除外したが、目視再確認はしていない。
- 既存台帳との差分は区分定義・サンプル構成の違いを制御できておらず、事実の差ではなく比較条件の違いに起因する可能性が高い。

## 6. 試作既定値への持ち込み方（Claude 案、PO 確認前提）

- 観察された型から選ぶ試作候補（最多型とは限らない。全体集計であり用途別集計ではないため、特定業種での最多型の証拠には使えない）: header name-count 37/54(68.5%)、children_nav chips 21/53(39.6%)（none は 20/53(37.7%)）、list_layout grid-3 23/54(42.6%)（text-list は 21/54(38.9%)）、columns 2col-sidebar-right 37/54(68.5%)、pagination numbers 23/54(42.6%)（none は 21/54(38.9%)）。各項目の最多型は §2 の表を正とし、ここでは順位を主張しない。「一般的標準」ではなく**カテゴリ面の試作候補**としてのみ使う。
- 適用範囲: 試作 03 のカテゴリ面 variant の追加候補の選定。検証条件: 試作を PO が見て反応を返すこと（prototype reaction）。要求への昇格は別途 PO 判断。
