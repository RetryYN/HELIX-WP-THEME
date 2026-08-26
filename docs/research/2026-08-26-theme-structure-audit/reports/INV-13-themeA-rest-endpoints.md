# THEME-INV-13 レポート — テーマA の未認証 REST エンドポイント

- 対象イシュー: `issues/THEME-INV-13-themeA-rest-ssrf.md`
- 状態: **コード解析パート完了 / 到達性検証は未実施（PO 承認待ち）**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用（`sed -n` によるソース読み出しのみ）
- 対象サイト: site-A.example（テーマA 1.4.6・WP 7.0.2）

> ## ⚠ 証拠ギャップ（2026-08-26 検証で判明・要是正）
> 本レポートの目玉である REST 2 本の登録・`__return_true`・`file_get_contents()` は、
> **本レポート本文への手写しのみで、生キャプチャファイル（`evidence/re-themeA-rest.txt`）が未生成**。
> `evidence/theme-features-raw.txt` の テーマA `register_rest_route` 欄は単一行 grep のため**空**で、
> ここには テーマA の REST が写っていない（テーマB 分のみ採取済み）。
> 独立裏取りがあるのは `post_by_url` の内部ディスパッチ（`evidence/re-themeA-ads.txt` の
> `new WP_REST_Request('GET','/themeA/post_by_url')` → `rest_do_request`）**のみ**。
> `external_url` の存在・両者の permission_callback・SSRF 構造は現状**未採取**。
> **是正**: Bash/SSH 復旧後に PROGRESS.md 手順 3（`re-themeA-rest.txt` の生採取。読み取り専用）を
> 最優先で実行し、本文の引用が実ソースと一致することを確定させる。到達性の HTTP 実証は別途 PO 承認。

## 1. 結論

テーマA は独自 REST を **2 本**登録している。両方とも `permission_callback` が `__return_true`
（＝**認証・権限チェックなし**）。うち `themeA/external_url` は、クエリで受け取った URL を
検証せずに `file_get_contents()` へ渡しており、**SSRF の構造**を持つ。

| # | ルート | メソッド | permission_callback | リスク |
|---|---|---|---|---|
| 1 | `themeA/post_by_url` | GET (`WP_REST_Server::READABLE`) | `__return_true` | 情報露出（低〜中） |
| 2 | `themeA/external_url` | GET (`WP_REST_Server::READABLE`) | `__return_true` | **SSRF（高）** |

前回の構造調査で「テーマA の独自 REST は 0 本」と報告した点の訂正。
`register_rest_route(` が複数行に分かれて書かれており、単一行を前提にした grep が拾えていなかった。

## 2. 証跡（ソース）

`include/custom-functions.php`。行番号は 2026-08-26 時点の実ファイル。

### 2.1 `themeA/external_url`（3775-3800 付近）

```php
function get_ogp_from_url_endpoint()
{
	register_rest_route(
		'themeA',
		'/external_url',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'callback'            => 'get_ogp_from_url',
		)
	);
}
add_action('rest_api_init', 'get_ogp_from_url_endpoint');


function get_ogp_from_url($data)
{
	$post_url = $data->get_param('url');
	$html     = file_get_contents($post_url);
	preg_match_all("<meta property=\"og:([^\"]+)\" content=\"([^\"]+)\">", $html, $ogp);
	for ($i = 0; $i < count($ogp[1]); $i++) {
		$result[$ogp[1][$i]] = $ogp[2][$i];
	}
	…
```

**問題点（コードから読み取れる事実）**
1. `permission_callback => '__return_true'` — 未認証・未ログインで到達可能な設計。
2. `$data->get_param('url')` を**スキーム検証・ホスト許可リスト・プライベート IP 除外なし**で使用。
3. 取得に `file_get_contents()` を使用 — WordPress の `wp_safe_remote_get()`（内部宛リクエストを
   遮断する `wp_http_validate_url()` を通す）ではない。
4. 取得結果を正規表現で抽出してレスポンスに載せるため、**取得内容が外部へ返る**。
5. `allow_url_fopen` が有効な環境では `http(s)://` 以外のラッパも解釈されうる
   （PHP 設定依存。実環境の `allow_url_fopen` / `allow_url_include` は未確認）。

### 2.2 `themeA/post_by_url`（3670-3700 付近）

```php
function get_post_from_url_endpoint()
{
	register_rest_route(
		'themeA',
		'/post_by_url',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'callback'            => 'get_post_from_url',
		)
	);
}
add_action('rest_api_init', 'get_post_from_url_endpoint');

function get_post_from_url($data)
{
	global $post;
	$param                = $data->get_param('url');
	$post_id              = url_to_postid(untrailingslashit($param));
	$post                 = get_post($post_id);
	$categories           = get_the_category($post_id);
	$title                = get_the_title($post_id);
	$thumbnail_id         = get_post_thumbnail_id($post_id);
	$thumbnail_alt        = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
	$image                = wp_get_attachment_image_src($thumbnail_id, 'medium_size');
	$thumbnail_url        = is_array($image) ? $image[0] : themeA_noimage_url('medium');
	$image_square         = wp_get_attachment_image_src($thumbnail_id, 'thumbnail_size');
	$thumbnail_square_url = is_array($image_square) ? $image_square[0] : themeA_noimage_url('thumbnail');
	return $return_array;
}
```

**問題点**
- 未認証。`url_to_postid()` の結果に対し `get_post()` を行い、**投稿ステータスを確認していない**。
  下書き・非公開・予約投稿のタイトルやサムネイルが返る可能性がある（要検証）。
- `global $post` を書き換えている（REST 応答中のグローバル汚染）。

### 2.3 その他の `rest_api_init` フック

同ファイルに 2 本。ルート追加ではなく既存 REST の拡張。

```
custom-functions.php:3582  add_action('rest_api_init', 'slug_register_views_orderby');
custom-functions.php:3616  add_action('rest_api_init', 'themeA_slug_register_views');
```

PV 数（ビュー数）によるオーダーバイを REST に追加するもの。今回の指摘対象外。

## 3. 呼び出し元 — 遮断した場合の影響範囲

`themeA/post_by_url` は **ブロック描画のために内部から呼ばれている**。
`themeA_blog_card_dynamic_render_callback()`（内部リンクのブログカード）が、
自分自身の REST を内部ディスパッチして記事情報を取得する:

```php
$request = new WP_REST_Request('GET', '/themeA/post_by_url');
$request->set_query_params(array('url' => $block_attr['postUrl']));
$response = rest_do_request($request);
$server   = rest_get_server();
$data     = $server->response_to_data($response, false);
```

**重要**: `rest_do_request()` は**内部ディスパッチ**であり HTTP を経由しない。
したがって「HTTP 経由の外部アクセスだけを遮断する」対処（WAF / `rest_endpoints` ではなく
Web サーバ層での URL 遮断）を選べば、**ブログカードの描画は壊れない**見込み。
一方 `rest_endpoints` フィルタでルート自体を除去すると内部ディスパッチも失敗するため、
ブログカード（実使用 330 回）に影響が出る。ここが対処案の選択を分ける。

`themeA/external_url` の呼び出し元は、外部リンクのブログカード作成時に
**エディタ側（`editor/build/index.js`）から fetch される**と推定される。
ソースが minified のため未確定 — 要確認（§6 の未了項目）。

## 4. 現状の防御層

| サイト | セキュリティ系プラグイン | 備考 |
|---|---|---|
| site-A.example（テーマA） | **なし** | `audit-topic-A.json` の active 11 件に該当なし |
| site-B.example（テーマB） | `cloudsecure-wp-security` | テーマA サイトには入っていない |

→ **当該ルートは現状、テーマ以外に遮断層が無い。**

## 5. 対処案（比較）

| 案 | 効果 | テーマ更新耐性 | ブログカードへの影響 | 手間 |
|---|---|---|---|---|
| A. WAF / サーバ層で `/wp-json/themeA/external_url` を遮断 | HTTP 経由のみ遮断 | ○（テーマ外） | **なし**（内部ディスパッチは通る） | 小 |
| B. 子テーマから `rest_endpoints` フィルタでルート除去 | 内部含め完全遮断 | ○（子テーマ） | **あり**（`post_by_url` を消すと壊れる。`external_url` のみ除去なら影響は外部カード作成時のみ） | 小 |
| C. 子テーマで `rest_pre_dispatch` に検証を挟む（許可リスト + `wp_safe_remote_get` 相当へ） | 機能を残して危険性を下げる | ○ | なし | 中 |
| D. テーマ本体を改変 | 完全 | **×**（更新で消える） | なし | 小 |

**推奨は A + B の併用**（`external_url` のみ `rest_endpoints` で除去し、加えてサーバ層でも遮断）。
`post_by_url` はブログカードが依存しているため、除去ではなく **ステータス確認の追加（C 相当）**が筋。

## 6. 未了項目（PO 承認が要るもの / 次の作業）

- [ ] **到達性の確認** — 自サイトに対して `/wp-json/themeA/external_url?url=…` を実際に叩く。
      外部への write ではないが、実サイトへのリクエスト送出につき PO 承認が要る。
- [ ] `post_by_url` が下書き・非公開記事を返すかの確認（同上）
- [ ] サーバーの `allow_url_fopen` / `open_basedir` / 外向き通信の可否（`php -i` 読み取りで確認可）
- [ ] `editor/build/index.js` 内の `external_url` 呼び出し箇所の特定（minified の解析）
- [ ] ベンダー（ベンダーA）への報告要否 — PO 判断

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| REST ルート登録・コールバック本体 | 本レポート §2 に全文転記 |
| ブロック描画からの内部ディスパッチ | `evidence/re-themeA-ads.txt`（`themeA_blog_card_dynamic_render_callback`） |
| プラグイン構成 | `<local-poc-evidence>/audit-topic-A.json`（2026-08 時点の監査） |

> 注: §2 のソースは調査セッション中に SSH 経由で読み出したものを転記した。
> 生キャプチャファイルとしての再取得は未実施（`evidence/re-themeA-rest.txt` として別途採取する）。
