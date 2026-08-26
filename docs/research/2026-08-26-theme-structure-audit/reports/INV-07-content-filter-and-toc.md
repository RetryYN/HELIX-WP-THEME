# THEME-INV-07 レポート — 目次と本文フィルタ機構の方式

- 対象イシュー: `issues/THEME-INV-07-toc-and-content-filter.md`
- 状態: **①〜④ 一次完了**（本文変換の全一覧・レイヤ割り当て・目次の方式決定まで）
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用
- 一次証跡: `evidence/re-themeB-pipeline.txt`（`content_filter.php` 全文）・
  `evidence/re-themeA-render.txt`（`single.php` 全文 + `the_content` フィルタ一覧 + 広告挿入 grep）・
  `evidence/re-themeA-ads.txt`（`themeA_h2_ads_concert` 本体）・`evidence/re-themeB-boot.txt`

## 1. 本文変換の全一覧

### 1.1 テーマA — `the_content` に刺さるのは 3 本だけ

証跡（`evidence/re-themeA-render.txt`、grep 結果そのまま）:

```
include/custom-functions.php:1250  add_filter('the_content', 'wrap_iframe_in_div');
include/custom-functions.php:3433  add_filter('the_content', 'themeA_paid_content_display_switch', 9);
include/custom-functions.php:4523  add_filter('the_content', 'themeA_h2_ads_concert');
```

| # | 変換 | 優先度 | 内容 | 依存 |
|---|---|---|---|---|
| J-1 | `wrap_iframe_in_div` | 既定(10) | `<iframe>` を div でラップ（レスポンシブ対応） | 本文のみ |
| J-2 | `themeA_paid_content_display_switch` | **9** | 有料記事の本文差し替え | ログイン状態 / 購入状態 / セッション |
| J-3 | `themeA_h2_ads_concert` | 既定(10) | 最初の h2 の前に広告を注入 | カテゴリ / options / post meta |

**持っていないもの**: 目次・遅延読み込み・URL の自動カード化・空 p タグ除去。
これらは外部プラグインへ委譲している（topic-A の active 構成: `rich-table-of-content`（目次）、
`ewww-image-optimizer`（画像最適化）、`website-llms-txt`、`flexible-table-block` ほか）。

本文以外の挿入は**テンプレートとウィジェットエリア**が担う（`single.php` の
`post-top-widget` / `post-start-widget` / `post-end-widget` / `post-bottom-widget`）。
つまり テーマA は「**本文はほぼ素通し、周辺はテンプレートで組む**」方式。

### 1.2 テーマB — 優先度 12 に揃えたパイプライン

証跡（`evidence/re-themeB-pipeline.txt`）。登録は `wp_loaded`(20)、本文フィルタは全て優先度 12。

| # | 変換 | 対象フック | 条件 | 依存 |
|---|---|---|---|---|
| S-1 | oEmbed 自動埋め込み（`$wp_embed->autoembed`） | the_content / widget_text / widget_text_content / widget_block_content | 常時 | 外部 oEmbed（キャッシュあり） |
| S-2 | ショートコード展開 | widget_text | 常時 | — |
| S-3 | 目次挿入 `add_toc` | the_content ほか | `wp_head`(99) で後付け登録 | 設定 + 見出し構造 |
| S-4 | 目次ショートコード `add_toc_on_widget` | widget 系 3 種 | 常時 | 同上 |
| S-5 | 空 `<p>` 除去 `remove_empty_p` | 4 フック | `remove_delete_empp` が偽 | 本文のみ |
| S-6 | lazysizes 変換 `add_lazysizes` | 4 フック | `$lazy_type === 'lazysizes'` | 画像ファイル（アスペクト比取得） |
| S-7 | URL の自動ブログカード化 `url_to_blog_card` | the_content | `themeB_remove_url_to_card` が偽 | 投稿 DB / 外部 OGP |

さらにページ全体に対する変換が 1 本（本文フィルタではない）:

| S-8 | `rewrite_lazyload_scripts` | `ob_start()` でページ HTML 全体 | `delay_js` 有効 | リクエストメソッド / 除外ページ設定 |

**設計上の特徴が 3 つ**（`content_filter.php` 冒頭コメントに明記されている）:

```
* memo: ショートコード展開の優先度:11
*       ダイナミックブロック展開の優先度:9
*       優先度12 → ショートコード展開より後に実行するため
*       rest読み込みを考慮すると wp フックでは遅いので wp_loaded
```

1. **展開（9→11）の後に変換（12）**という順序を数値で固定している。
2. **登録タイミングをずらして文脈を分ける** — 目次と URL カード化だけ `wp_head`(99) で後付け。
   理由もコメントにある: 「SEO プラグインの meta ディスクリプション生成時に発火しないように」。
3. **REST 経路を除外** — `add_lazysizes` 冒頭で `is_rest()` を判定し、
   自前の遅延読み込みエンドポイントだけ通す。

### 1.3 AGENT NEO — 本文変換を持たない

テーマ側に `the_content` フィルタは無い。方針は「中間 JSON → 決定論レンダラ」で、
本文は生成時点で完成した形を持つ。目次は `toc:false` フラグで制御する既存方針。

---

## 2. 各変換の担当レイヤ

「生成時（中間 JSON 正本）」「レンダリング時（決定論レンダラ）」「表示時（文脈依存）」の 3 層へ割り当てる。
判定基準は **入力が同じなら出力が同じか**（＝決定論を壊さないか）。

| 変換 | 層 | 根拠 |
|---|---|---|
| S-1 oEmbed 埋め込み | **レンダリング時** | 外部依存だがキャッシュ可。解決済み HTML を JSON に焼くと更新できない |
| S-2 ショートコード展開 | **生成時（に消す）** | 中間 JSON にショートコードを残さない。生成時に意図ノードへ変換する |
| S-3/S-4 目次 | **レンダリング時** | 見出しツリーから導出可能。§3 参照 |
| S-5 空 p 除去 | **生成時** | そもそも空ノードを作らない。後処理で消すのは対症療法 |
| S-6 lazysizes | **表示時** | 表示環境（JS 有無・画像最適化プラグイン）に依存。JSON にも正本 HTML にも入れない |
| S-7 URL 自動カード化 | **生成時** | 「この URL はカードにする」は編集意図。JSON にカードノードとして持つ |
| S-8 delay_js の HTML 書き換え | **表示時** | ページ配信の最適化。コンテンツではない |
| J-1 iframe ラップ | **レンダリング時** | マークアップ規約。レンダラの責務 |
| J-2 有料記事の本文差し替え | **表示時** | 閲覧者の状態に依存。決定論の外（スコープは INV-11） |
| J-3 h2 前広告 | **レンダリング時** | ゾーン定義（INV-03）+ 見出し位置から導出。ただし広告コード自体は表示時に解決 |

**原則として言語化すると**:
- **生成時** = 編集者の意図。人が決めたことだけが入る。
- **レンダリング時** = 意図から機械的に導けるもの。同じ JSON なら常に同じ HTML。
- **表示時** = 閲覧者・環境・時点に依存するもの。ここに入るものは JSON に持たない。

テーマB がこの分離を実装で持っている点（`is_rest()` による経路分離）は
`reports/INV-15-themeB-pipeline-transfer.md` §3 に記載。

---

## 3. 目次の扱い — 決定

**結論: 目次は中間 JSON の一級要素にしない。レンダラの派生物とし、配置だけを意図ノードで持つ。**

### 3.1 3 実装の比較

| | テーマA | テーマB | AGENT NEO（現方針） |
|---|---|---|---|
| 実装 | **持たない**（RTOC プラグイン） | テーマ内蔵 | レンダラ生成 |
| 本文への痕跡 | なし（プラグインが挿入） | プレースホルダ div または自動挿入 | `toc:false` フラグ |
| 配置指定 | プラグイン設定 | ショートコード `[themeB_toc]` or 自動（最初の h2 の前） | フラグのみ |
| 広告の同梱 | — | あり（`toc_ad_position` で前後指定） | — |

### 3.2 テーマB の方式が示す解（証跡: `evidence/re-themeB-pipeline.txt`）

ショートコードは**空のプレースホルダを置くだけ**で、実体は後段が入れる:

```php
if ( false !== strpos( $content, 'class="themeB-toc-placeholder"' ) ) {
	$toc = '<div class="p-toc -called-from-sc -' . $SETTING['index_style'] . '">' . … ;
	$content = str_replace( '<div class="themeB-toc-placeholder"></div>', $toc_content, $content );
	テーマB::$added_toc = true;
} elseif ( $is_content_hook ) {
	if ( テーマB::$added_toc ) return $content;      // 二重生成防止
	…
	$tag = '/^<h2.*?>/im';
	if ( (int) get_query_var( 'page' ) > 1 ) {
		$content = $toc_content . $content;          // 2ページ目以降は先頭
	} else {
		$content = preg_replace( $tag, $toc_content . $tags[0], $content, 1 );
	}
}
```

### 3.3 採用する規則

1. **中間 JSON に目次の中身（見出しリスト）を持たない。** 見出しノードから導出する。
   見出しを直せば目次も直る、を保証する。
2. **配置は意図ノードで持つ。** `{ "type": "toc" }` を本文の任意位置に置ける。
   置かれていなければレンダラの既定配置（最初の h2 の直前）を適用する。
   → テーマB のプレースホルダ方式と同じ考え方だが、HTML 文字列ではなくツリーのノードとして持つ。
3. **既定配置の規則はレンダラの仕様として文書化**し、設定で変えない。
   （設定で変わると同じ JSON から違う HTML が出る＝決定論が壊れる）
4. **目次広告は目次ノードの属性ではなくゾーン定義（INV-03）側**に置く。
   目次の前 / 後という位置指定はゾーン `toc_before` / `toc_after` として扱う。
5. **冪等性を担保する。** レンダラは同じ入力に対し目次を 1 つだけ生成する
   （テーマB の `$added_toc` に相当する保証をレンダラ内部で持つ）。
6. **ページ送りによる配置変更は表示時**（`get_query_var('page')` 相当）。JSON にもレンダラにも入れない。

### 3.4 テーマA サイト移管時の扱い

テーマA は目次を持たず RTOC プラグインが挿入している。本文には痕跡が無いため、
**移管時に目次の配置情報は失われる**（プラグイン設定側にしかない）。
既定配置ルール（最初の h2 の前）を適用すれば実用上は再現できる見込み。
RTOC の設定（`rtoc_*` オプション）を読めば見出しレベルや表示条件は復元できる。→ INV-09 の対象に追加。

---

## 4. 未了項目

- [ ] テーマA の `wrap_iframe_in_div` / `themeA_paid_content_display_switch` の本体読み出し
      （行番号は特定済み: custom-functions.php 1250 / 3433。中身は未読）
- [ ] テーマB `url_to_blog_card` の正規表現全文と、カード化の判定条件の詳細
      （`evidence/re-themeB-pipeline.txt` に途中まで採取済み）
- [ ] RTOC プラグイン（`rich-table-of-content`）の設定キーの棚卸し（INV-09 と合流）
- [ ] `toc_before` / `toc_after` ゾーンの正式定義（INV-03 で確定）

## 5. 証跡ファイル

| 内容 | 場所 |
|---|---|
| テーマB `content_filter.php` 全文（目次・lazysizes・カード化） | `evidence/re-themeB-pipeline.txt` |
| テーマA `the_content` フィルタ 3 本の登録行 | `evidence/re-themeA-render.txt` |
| テーマA `single.php` 全文（ウィジェットによる周辺挿入） | 同上 |
| テーマA `themeA_h2_ads_concert` 本体 | `evidence/re-themeA-ads.txt` |
| テーマB `separate.php` / `gutenberg.php` | `evidence/re-themeB-boot.txt` |
