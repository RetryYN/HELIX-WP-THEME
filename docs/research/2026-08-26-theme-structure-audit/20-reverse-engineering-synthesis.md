# リバースエンジニアリング統合レポート — テーマA / テーマB の機構と Graphix NEO への含意

- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用（サーバーへの書き込みなし）
- 対象: `themes/themeA`（PHP 33,931 行）/ `themes/themeB`（PHP 45,622 行）/
  本リポの `agent-neo-theme` + `agent-neo-core` / `agent-neo-embed`
- 位置づけ: 個別調査 17 本（`reports/`）と機構解析 3 本（`10` `11` `12`）の**統合**。
  ここだけ読めば設計判断に必要な結論が揃うようにしてある。

---

## 第 1 部 — 三者の機構

### 1.1 一行で言うと

| テーマ | 性格 |
|---|---|
| **テーマA** | **設定駆動の閉じたテーマ。** グローバル関数の直列実行で、契約もスキーマも拡張点も持たない。設定 1,225 キーと手書きアクセサ 707 個が実質の正本 |
| **テーマB** | **契約を持つ開いたテーマ。** クラス + trait + オートローダ。block.json・filter 79 本・pluggable 関数群・REST 14 本を備え、外部からの介入を前提に設計されている |
| **AGENT NEO** | **宣言と検証のテーマ。** 設定は JSON、起動状態は `health()` で自己申告。3 者で唯一「自分を機械可読に説明する」が、運用面の面積は空 |

### 1.2 起動 — 依存関係をどう保証しているか

| | テーマA | テーマB | AGENT NEO |
|---|---|---|---|
| 構成単位 | グローバル関数のみ | クラス + trait + 名前空間 | final クラス + module |
| 読み込み | `require` / `get_template_part` の直列 | オートローダ + **設定確定 → 21 モジュール** | 明示 require + `register()` |
| 依存の保証 | **行順のみ（暗黙）** | 明示（`data_init()` が先） | config 検証 → module 登録 |
| 条件分岐 | ほぼ無し | 管理者 / 管理画面で読み込み範囲を絞る | — |
| 自己診断 | 無し | `check_environment.php`（環境要件のみ） | **`health()` が steps と modules を返す** |

**テーマA の要点**: `get_template_part()` を「ファイルを読み込む」目的に流用している。
`load-customizer-value.php` は `customizer/ui/*.php` が定義するアクセサに依存するが、
それを保証しているのは **require の行順だけ**。静的解析で依存グラフを引けない。

**テーマB の要点**: **設定の確定が最初**（`self::data_init()`）。
以降のすべてのモジュールが確定済み設定を前提にできる。
テーマA が「描画中にアクセサを呼び、必要なら DB へ書く」のと対照的。

### 1.3 設定 — 目録を作れるか

| | テーマA | テーマB | AGENT NEO |
|---|---|---|---|
| 格納 | 個別 option **1,225** + theme_mod | 配列 **4 グループ** + 独自テーブル `themeB_balloon` | `theme.json` + `config/*.json` 7 本 |
| 既定値 | **707 アクセサ関数に散在**（17 ファイル） | `Default_Settings.php` **1 ファイル 540 キー** | JSON 宣言 |
| 検証 | 無し | 無し（既定値とのマージのみ） | **fail-fast schema 検証** |
| **目録化** | **不可** | **可** | **可** |

アクセサ 707 の分布（`reports/INV-09-settings-authority.md` §1.1）は、
**約 530（75%）が「見た目」**（ボタン 181・吹き出し 121・メインビジュアル 80・SP メニュー 75 …）。
**サイトの意味に属する設定はごく少数**で、移管必須はおよそ 60〜80 キー（全体の 5〜7%）と見積もった。

### 1.4 CSS 生成 — 生成器の構造

| | テーマA | テーマB |
|---|---|---|
| 実装 | **単一 2,098 行関数**を `wp_head` / `admin_head` へ | `Style` アキュムレータ + 生成器 11 ファイル |
| メディアクエリ | 関数内ローカル変数（5 段） | **バケットとして一級**（all/pc/sp/tab/mobile） |
| エディタ対応 | 同じ関数を `admin_head` にも刺す | `$branch` で front/editor を出し分け、**セレクタも自動切替** |
| 分離 | 無し（全部インライン） | `$modules` として別ファイル化・キャッシュ経路あり |
| **副作用** | **`set_theme_mod()` で描画中に DB へ書く（5 箇所）** | 無し |
| 変数の性格 | 部品固有（151） | 部品固有 + 文脈依存（155） |

`Style::add_post_style()` は**セレクタ側も自動で切り替える**
（`.post_content …` ↔ `.mce-content-body …, .editor-styles-wrapper …`）。
**エディタとフロントの見た目一致がアーキテクチャで担保されている。**

### 1.5 本文パイプライン — 保存 HTML と表示 HTML

| | テーマA | テーマB |
|---|---|---|
| `the_content` フィルタ | **3 本** | **7 種以上**（優先度 12 に統一） |
| 目次 | 持たない（RTOC プラグイン） | 内蔵。**プレースホルダ置換方式** |
| 遅延読み込み | 持たない（EWWW 等） | lazysizes 変換を自前実装 |
| URL 自動カード化 | 持たない | 内蔵（`themeB_remove_url_to_card` で無効化可） |
| REST 経路 | 区別しない | **`is_rest()` で意図的に除外** |

テーマB の `content_filter.php` は冒頭コメントで設計意図を明示している:

```
* memo: ショートコード展開の優先度:11 / ダイナミックブロック展開の優先度:9
*       優先度12 → ショートコード展開より後に実行するため
*       rest読み込みを考慮すると wp フックでは遅いので wp_loaded
```

さらに目次と URL カード化だけ `wp_head`(99) で**後付け登録**する。理由もコメントにある —
「SEO プラグインの meta ディスクリプション生成時に発火しないように」。
**同じ本文を読む処理でも、目的が違えば通すフィルタを変える**という発想。

### 1.6 使用要素の把握 — テーマB の 2 パス・ドライラン

`Pre_Parse_Blocks` が `wp_head`(0) で走り、CSS を「使われているブロックの分だけ」読むために
**本文とウィジェットを描画前に走査**する。

```
1. add_filter('render_block', render_check)          … 一時装着
2. 本文: parse_blocks( do_shortcode( $content ) ) を再帰走査
       └ themeB/blog-parts(attrs.partsID) / core/block(attrs.ref) は参照先 post を取得して再帰
3. ターム: term_meta 'themeB_term_meta_display_parts' の参照先を走査
4. ウィジェット: ob_start() → 実際に出力 → ob_clean() で捨てる（14 エリアを総当たり）
5. 文字列直検査: [ad_tag / [ふきだし / cap_box / [full_wide_content / <table …
6. remove_filter
```

**これは「本文の意味構造を描画前にサーバー側で確定する」処理**であり、
中間 JSON パイプラインと同型。`reports/INV-15-themeB-pipeline-transfer.md` で
**転用可（中核）**と判定した。

### 1.7 ブロックの契約

| | テーマA | テーマB |
|---|---|---|
| 定義の正本 | **PHP の `register_block_type()` 呼び出し** | **block.json**（`register_block_type_from_metadata`） |
| 数 | 25（動的 7 / 静的 18）+ `core/list` スタイル 2 | 50（通常 22 / 動的 10 / インライン書式ほか 18） |
| エディタ JS | 全 25 種が**単一 minified バンドル** | 共通バンドル + **ブロック別 `index.js`**（`index.asset.php` で依存解決） |
| 環境値 | `wp_localize_script` の `THEMEA_VAR` に一括注入 | ブロック属性 + 設定 API |
| 属性の後方互換 | 記述なし | **v1/v2 の分岐をコードに明示**（`linkData` ↔ `postId`） |
| 未指定属性 | **サイト設定へフォールバック** | block.json の既定値 |
| 切り出し | 実質不可 | **ブロック単位で可能** |

### 1.8 介入点

| | テーマA | テーマB | AGENT NEO |
|---|---|---|---|
| 自前 `apply_filters` | **1** | **79** | （プラグイン側） |
| 自前 `do_action` | 3 | 5 | — |
| pluggable | **無し**（`function_exists` ガードも無く再定義は fatal） | `pluggable.php` / `pluggable_parts.php` | — |
| REST | 2（内部用途・未認証） | 14（`wp/v2` 相乗り・管理系） | **34 コントローラ + MCP + CLI** |

---

## 第 2 部 — 解析で出た所見

構造の一覧では出てこない、**実装を読んで初めて分かったこと**。

### R-01 未認証 REST 2 本（セキュリティ・最優先）

`themeA/post_by_url` と `themeA/external_url`。**両方 `permission_callback => '__return_true'`**。
後者は `get_param('url')` を検証せず `file_get_contents()` に渡す **SSRF の構造**で、
`wp_safe_remote_get()`（`wp_http_validate_url()` を通す）を使っていない。
取得結果は正規表現抽出のうえレスポンスに載る。

**対処の設計を変える発見**: `post_by_url` は**ブログカード（実使用 330）が
`rest_do_request()` で内部ディスパッチ**している。`rest_endpoints` フィルタでルートを消すと
描画が壊れるが、`rest_do_request()` は HTTP を経由しないため、
**サーバ層で `/wp-json/themeA/external_url` を遮断すれば内部呼び出しは通る**。

topic-A にはセキュリティ系プラグインが入っていない（site-B には `cloudsecure-wp-security`）。
→ `reports/INV-13-themeA-rest-endpoints.md`

### R-02 描画パスの DB 書き込み

`wp_head` の CSS 生成関数が `set_theme_mod()` を 5 回呼び、未設定なら既定色を DB へ書く。
**閲覧やクロールが設定を確定させる。** 複製・移管で「触っていないのに値が入っている」が起きる。

ただし該当 5 キー（`theme_color` / `header_bg_color` / `header_menu_color` / `text_color` /
`bg_color`）はすべて「見た目」に属し、**移管必須集合に入らない**ため実害は限定的。
→ `reports/INV-16-themeA-render-side-effects.md` / `reports/INV-09-settings-authority.md` §4.1

### R-03 正規化リダイレクトの全面停止

```php
add_filter('redirect_canonical', 'themeA_disable_redirect_canonical');
function themeA_disable_redirect_canonical($redirect_url) { $redirect_url = false; return $redirect_url; }
```

コメントの意図は「記事内ページネーションの URL 形式」だが、**引数を見ず常に `false`**。
末尾スラッシュ・`?p=ID`・ページ送り・大文字小文字の正規化がすべて停止する。
**topic-A は SEO 施策サイト**であり、重複 URL とクロールバジェットに直結する。
→ `reports/INV-17-themeA-global-side-effects.md`

### R-04 全ページでのセッション再生成

`template_redirect` で `session_start()` + **`session_regenerate_id()` を毎リクエスト**。
通常は権限昇格時にのみ呼ぶ関数。全レスポンスに `Set-Cookie` が付き、
**ページキャッシュ・CDN と衝突**する。
そして有料記事（`themeA-blocks/paidpost`）の**公開記事での実使用は 0** なので、
**全ページで純粋なオーバーヘッド**になっている。
→ `reports/INV-17` / `reports/INV-11-scope-boundary.md`

### R-05 決定論との衝突

テーマA の render_callback は**未指定属性をカスタマイザ値へフォールバック**する。

```php
$blogcardDesign = ! empty($block_attr['blogcardDesign']) ? $block_attr['blogcardDesign']
                                                        : themeA__blogcard_design();
```

**同じ保存内容でも、サイト設定が違えば出力が変わる。**
動的 7 種のうち **6 種は正規化（実効値の解決と固定）で決定論レンダラに載る**が、
`paidpost` のみ閲覧者状態依存で載らない。
→ `reports/INV-02-dynamic-render-semantics.md`

### R-06 広告ゾーンの実体とスキーマのズレ

`ad-zone.schema.json` は「テーマA の 4 ゾーン（h2 前挿入 / 記事終 / 関連上 / カテゴリ別上書き）」と
書くが、実装では:

- **`category_override` はゾーンではなく上書き規則**（`before_h2` の広告コードを
  カテゴリ 4 スロットで差し替える仕組み）
- 記事終・関連上は同関数ではなく `ad-finish.php` / `ad-related.php` とウィジェットが担う
- 実測を意味で正規化すると **23 ゾーン**あり、うち 20 がスキーマの語彙に無い

実装は PHP の**可変変数**（`${'cat_array_id_0'.$num}` `${"merge".$num}` `${"exist1_".$m}`）を多用し、
スロット数 4 は**ループ上限のハードコード**。広告コードは `get_option()` の生 HTML を
**エスケープなしで出力**している。
→ `reports/INV-03-ad-cv-zones.md`

### R-07 属性の実体は CSS クラス名

テーマA の「詳細設定」（余白・表示デバイス）は、**数値やトークンではなくクラス名の文字列**。

```php
$detail_setting .= $topMarginPc !== "auto" ? $topMarginPc . " " : "";
```

中間 JSON へ写すには**クラス名から意味への逆変換**が要る。
加えて `themeABlocksCSSAttribute` があると `<style jsx="true">` を**ブロック単位でインライン出力**する。
→ `reports/INV-14-themeA-attribute-induction.md`

### R-08 テーマB には転用できる前例がある

`Pre_Parse_Blocks` の 2 パス走査・`content_filter` の優先度設計・
`is_rest()` による保存/表示の分離・プレースホルダ方式の目次。
このうち **2 つを転用可**、1 つを設計原則として採用、1 つを思想のみ採用と判定した。
→ `reports/INV-15-themeB-pipeline-transfer.md`

---

## 第 3 部 — 初回調査からの訂正

RE パスで初回の構造調査を 4 件訂正した。原因はいずれも grep の取りこぼしと数え違い。

| 項目 | 初回 | 訂正後 | 原因 |
|---|---|---|---|
| テーマA の独自 REST | 0 本 | **2 本** | 複数行 `register_rest_route(` を単一行前提の grep が拾えなかった |
| テーマA の `register_block_style` | 0 | **2 件**（`core/list`） | 同上 |
| テーマA の動的ブロック | 9 種以上 | **7 種** | 登録コード全文を数え直し |
| `themeA-blocks/paidpost` の実使用 | 本文中 16 回 | **公開記事で 0 回** | 16 は**テーマソース内の文字列出現数**で、本文の使用回数ではなかった |

**加えて未解決の不整合が 1 件**: `evidence/usage-raw.txt` に `themeA-blocks/profile` が 1 回出現するが、
このブロック名は登録一覧 25 種に**存在しない**（廃止ブロックの残存と推定。INV-14 の抽出で確定する）。

> **教訓**: 複数行に分かれた PHP の関数呼び出しは、単一行 grep では捕まらない。
> 「0 件」という結果が出たときは、記法のバリエーションを疑ってから結論を書く。

---

## 第 4 部 — Graphix NEO への設計含意

### 4.1 移植の原則

1. **テーマA は実装を移植する対象ではない。**
   契約が無く、環境結合が強く、拡張点も無い（filter 1 本、`function_exists` ガードすら無い）。
   取るべきは**出力マークアップと意味構造**であって、コードではない。
2. **テーマB は契約と変換パイプラインの前例として読む。**
   block.json・`Pre_Parse_Blocks`・`content_filter` の優先度設計・`is_rest()` の分離。
3. **AGENT NEO が唯一持つのは「自分を説明する機構」。**
   health / config 検証 / boundary guard / section-registry / JSON Schema。
   機能面積では劣るが、**この性質は捨てずに拡張する**。

### 4.2 消化で確定した設計判断

| 論点 | 決定 | 出典 |
|---|---|---|
| 再利用パーツ | **参照（ID）で持ち、解決に使った版と digest を記録**。展開しない | INV-04 |
| 目次 | **中間 JSON の一級要素にしない。** 配置だけ意図ノード、実体はレンダラ導出。既定は最初の h2 直前 | INV-07 |
| 本文変換の層 | **生成時 / レンダリング時 / 表示時**の 3 層。判定基準は「入力が同じなら出力が同じか」 | INV-07 |
| ショートコード | テーマ語彙は**意図ノードへ展開**、プラグイン語彙は**不透明ノードで原文保持** | INV-10 |
| インライン書式 | **ノードではなくテキストの装飾レンジ**（marks 配列）として持つ | INV-01 |
| REST 名前空間 | **コアに相乗りしない。** 自前名前空間を切る（`agent-neo/v1` を継承せず新規） | INV-08 |
| 広告ゾーン | `creative_ref` は**参照**。`overrides` は **first-match-wins の配列** | INV-03 |
| 課金・会員 | **スコープ外 + プラグイン委譲**（両サイトとも公開面で実使用 0） | INV-11 |
| デザイントークン | **A 意味的 / B 部品固有 / C 状態フラグ / D レイアウト寸法**の 4 分類。C を混ぜない | INV-05 |
| 設定の移管 | **①サイト固有のみ移管**（60〜80 キー）。②見た目は作り直す | INV-09 |
| 構造化データ | `CollectionPage` / `SearchAction` を追加。FAQ/HowTo/ItemList は**意図ノードから自動生成** | INV-06 |
| グローバル改変 | `redirect_canonical` 無効化・全ページセッションは**引き継がない** | INV-17 |

### 4.3 Graphix NEO が持つ必要のある語彙（実使用の裏取り）

**両テーマに専用ブロックがある 11 組**が意図語彙の第一候補
（囲みボックス・ボタン・リンクカード・吹き出し・手順・記事一覧・アコーディオン・タブ・
全幅・リッチメニュー・会員制限）。

加えて**片方にしかないが多用されているもの**を両方持つ必要がある:

| 語彙 | 出所 | 実使用 |
|---|---|---|
| 比較表 | テーマA のみ | **177 + 59** |
| 定義リスト | テーマB のみ | **106 + 106 + 30** |
| FAQ | テーマB のみ | **56 + 6** |

FAQ を意図ノードとして持てば `FAQPage` 構造化データを自動生成できる（INV-06 と接続）。

### 4.4 中間 JSON 抽出器の最小実装範囲

`Pre_Parse_Blocks` から転用しつつ、4 点を変える（INV-15 §1.5）:

1. 記録対象を blockName から **`$block` 全体**（attrs / innerHTML / innerBlocks）へ
2. **`wp_enqueue_style()` の副作用を排除**（抽出器は純関数に）
3. **`render_block` フック依存をやめる** — 純粋な `parse_blocks()` 経路で完結させ、
   CLI / REST から呼べるようにする
4. ウィジェットのドライランは本文の射程外だが、**INV-03（ゾーンの実配置把握）にそのまま使える**

加えて**参照解決に深さ上限と訪問済み集合**を入れる（テーマB の実装には無く、循環参照で無限ループ）。

---

## 第 5 部 — 残作業と証跡

### 5.1 実行可能な状態にしてあるもの

| スクリプト | 内容 |
|---|---|
| `create-issues.sh` | ラベル 25 種 + イシュー 17 本を起票。重複スキップ・`DRY_RUN=1` 対応 |
| `extract-themeA-attrs.sh` | 読み取り専用の属性抽出。**1 回で INV-14 / 01（属性層）/ 02 / 10 / 11 の未了 5 件が閉じる** |

### 5.2 PO 承認が要る項目

| 項目 | イシュー |
|---|---|
| 未認証 REST の到達性確認（自サイトへの HTTP GET）と対処案の選択 | INV-13 |
| 正規化リダイレクト停止の実挙動確認 | INV-17 |
| 実ページの JSON-LD 採取 | INV-06 |
| 本番 DB の SELECT（`theme_mods_themeA` / `sidebars_widgets` / option 一覧） | INV-16 / 03 / 09 |
| `ad-zone.schema.json` の改訂 | INV-03 |
| 資産再利用可否台帳の GRAPHIX-NEO への反映（cross-repo） | INV-12 |
| ベンダー（ベンダーA）への報告要否 | INV-13 |

### 5.3 証跡

| 種別 | 場所 | 数 |
|---|---|---|
| サーバー調査の生出力 | `evidence/` | 17 ファイル |
| 個別調査レポート | `reports/` | 17 本 |
| イシュー草案 | `issues/` | 17 本 |
| 機構解析 | `10-reverse-themeA.md` / `11-reverse-themeB.md` / `12-mechanism-comparison.md` | 3 本 |
| 構造調査 | `01`〜`04` | 4 本 |
| 消化状況 | `PROGRESS.md` | — |

**すべて読み取り専用の実測に基づく。** 推測は「仮説」「推定」と明記し、
未読部分は各レポートの「未了項目」として区別してある。
