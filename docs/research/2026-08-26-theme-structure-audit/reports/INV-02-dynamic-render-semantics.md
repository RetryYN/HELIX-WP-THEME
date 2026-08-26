# THEME-INV-02 レポート — 動的ブロックの意味論と再現性

- 対象イシュー: `issues/THEME-INV-02-dynamic-render-semantics.md`
- 状態: **①（依存の分類）JIN:R 完了・SWELL は一覧まで / ②③ 一次判定 / ④ PO 上申の材料を作成**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用
- 一次証跡: `evidence/probe3-raw.txt`（JIN:R の `register_block_type` 全 25 件・全文）・
  `evidence/re-jinr-ads.txt`（`jinr_blog_card_dynamic_render_callback` 本体）・
  `evidence/re-swell-blocks.txt`（SWELL の normal/dynamic 分類）・
  `evidence/re-swell-detail.txt`（`post-link.php` 全文）

## 1. 訂正 — JIN:R の動的ブロックは「9 種以上」ではなく **7 種**

`10-reverse-jinr.md` §6.1 および `12-mechanism-comparison.md` で「9 種以上が SSR」と記述したが、
`evidence/probe3-raw.txt` の登録コード全文を数え直した結果、**`render_callback` を持つのは 7 種**。
静的（save 出力）が 18 種。合計 25 種で登録総数と一致する。

## 2. JIN:R — 25 ブロックの静的 / 動的の全分類

証跡: `evidence/probe3-raw.txt`（`create_block_jinr_blocks_block_init()` の全文）

### 2.1 動的（`render_callback` あり）7 種

| ブロック | コールバック | 実使用（`usage-raw.txt`） |
|---|---|---|
| `jinr-blocks/postcard` | `jinr_post_card_dynamic_render_callback` | 0 |
| `jinr-blocks/postlist` | `jinr_post_list_dynamic_render_callback` | 38 |
| `jinr-blocks/paidpost` | `jinr_paidpost_dynamic_render_callback` | 0 |
| `jinr-blocks/slider` | `jinr_slider_dynamic_render_callback` | 0 |
| `jinr-blocks/button` | `jinr_button_dynamic_render_callback` | **339** |
| `jinr-blocks/blogcard` | `jinr_blog_card_dynamic_render_callback` | **330** |
| `jinr-blocks/category` | `jinr_category_dynamic_render_callback` | 0 |

> **【2026-08-26 訂正】** 「実使用」列は公開コンテンツの使用回数（`evidence/usage-raw.txt`）に統一。
> 旧版は `paidpost=16 / postcard=1 / slider=1 / category=21` としていたが、これらは
> `usage-raw.txt` の「block usage (post+page, published)」一覧に**出現しない = 実使用 0**。
> 旧 `paidpost=16` はテーマソース内の文字列出現数（`probe2-raw.txt`）の混入で、
> INV-11・00-REPORT §3-4 の訂正と整合しなかった。`postlist=38 / button=339 / blogcard=330` は
> `usage-raw.txt` と一致するため据え置き。

### 2.2 静的（save 出力）18 種

`designtitle` `syntax-hl` `simplebox` `richmenu` `richmenuchild` `designborder` `fukidashi`
`iconbox` `fullwidth` `accordion` `accordionchild` `compare` `comparechild` `tab` `tabchild`
`timeline` `timelinechild` `background`

### 2.3 追加発見 — コアブロックのスタイル拡張 2 件

同じ init 関数の末尾で、`core/list` にブロックスタイルを 2 つ登録している。

```php
register_block_style( 'core/list', array(
	'name' => 'jinr-checkmark',        'label' => __('チェック１'),
	'style_handle' => 'jinr-checkmark',
) );
register_block_style( 'core/list', array(
	'name' => 'jinr-checkmark-square', 'label' => __('チェック２'),
	'style_handle' => 'jinr-checkmark-square',
) );
```

初回調査で「JIN:R の `register_block_style` は 0」と報告したのは grep の取りこぼし（複数行記法）。
**`core/list` に `is-style-jinr-checkmark` / `is-style-jinr-checkmark-square` が付いた記事がある**
可能性があり、移植時にコアブロックの className として現れる。→ INV-01 の対応表に追加する。

## 3. ① 依存要因の分類

### 3.1 `jinr-blocks/blogcard`（実使用 330・全文を読了）

証跡: `evidence/re-jinr-ads.txt`

| 依存 | 内容 | 決定論への影響 |
|---|---|---|
| **サイト設定** | `blogcardDesign` / `blogcardTitle` / `blogcardLabel` が未指定なら `jinr__blogcard_design()` / `jinr__blogcard_title()` へフォールバック | **致命的**。同じ保存内容でも設定変更で出力が変わる |
| **投稿 DB** | 内部リンク時は `url_to_postid()` → タイトル・サムネイル・カテゴリを取得 | 参照先の更新で出力が変わる（意図的な挙動） |
| **ファイルシステム** | `-320x180` サフィックスのサムネイルを `file_exists()` で確認して差し替え | 環境依存。サムネイル再生成の有無で変わる |
| **自サイト REST** | `rest_do_request('/jinr/post_by_url')` で内部ディスパッチ | ルート除去で壊れる（INV-13 §3） |
| **外部 HTTP** | 外部リンク時は OGP 取得（`jinr/external_url` 系） | 外部サイトの状態に依存 |

さらに `jinrBlocksCSSAttribute` が指定されていると
`<style jsx="true">…</style>` を**ブロック単位でインライン出力**する。

### 3.2 依存の型（7 種の分類・blogcard 以外は登録情報からの推定を含む）

| ブロック | サイト設定 | 投稿 DB | ユーザー状態 | 外部 | 判定 |
|---|---|---|---|---|---|
| `blogcard` | ● 確認済 | ● 確認済 | — | ● 確認済 | **要正規化** |
| `postlist` | ▲ 推定 | ● ほぼ確実 | — | — | **要正規化** |
| `postcard` | ▲ 推定 | ● ほぼ確実 | — | — | 要正規化 |
| `category` | ▲ 推定 | ● ほぼ確実（ターム取得） | — | — | 要正規化 |
| `button` | ● 推定（CV ボタン設定 `jinr__spcv_*` 群あり） | — | — | — | **要正規化** |
| `slider` | ▲ 推定 | ● 推定 | — | — | 要正規化 |
| `paidpost` | ▲ | ▲ | **● ログイン / 購入状態 / セッション** | ● Stripe | **決定論の外** |

● = コード確認済み／▲ = 未読（コールバック本体の精読が未了）

### 3.3 SWELL の動的ブロック 10 種

証跡: `evidence/re-swell-blocks.txt`

```
ab-test / ad-tag / balloon / blog-parts / link-list / post-list / post-link / restricted-area / review / rss
```

`post-link.php` の全文を読了（`evidence/re-swell-detail.txt`）。JIN:R と決定的に違う点:

```php
if ( ! empty( $linkData ) ) {
	// v2以降は、linkDataを使う
	$link_id = $linkData['id'] ?? 0;   $url = $linkData['url'] ?? '';
	$kind    = $linkData['kind'] ?? ''; $type = $linkData['type'] ?? '';
} else {
	// v1
	$link_id = $attrs['postId'] ?? 0;  $url = $attrs['externalUrl'] ?? '';
	$kind    = $link_id ? 'post-type' : '';
	$type    = '';  // 投稿タイプは判断できない
}
```

- **属性のバージョン移行がコードに明示**されている（v1 → v2）。
- **未指定属性のフォールバック先は block.json の既定値**であって、サイト設定ではない。
  → **保存内容だけで出力が決まる**（`ad-tag` / `restricted-area` 等の例外を除く）。
- 表示上書きは `$card_args` という連想配列 1 個に集約してからカードジェネレータへ渡す。

**ユーザー状態に依存するもの**: `restricted-area`（ログイン状態）、`ab-test`（振り分け）、
`ad-tag`（広告データ）、`rss`（外部フィード）。それ以外は投稿 DB 依存に留まる。

## 4. ② 決定論レンダラでの再現可否

| 依存の型 | 再現可否 | 必要な措置 |
|---|---|---|
| **サイト設定フォールバック** | ○（条件付き） | **移行時に実効値へ解決して固定する**。中間 JSON には解決済みの値を入れ、以後フォールバックしない |
| **投稿 DB 参照** | ○ | 参照 ID を中間 JSON に持つ。加えて「解決に使った版」を記録して再現性を担保（INV-15 §5） |
| **ファイルシステム参照** | ○ | サムネイル URL を解決済みで持つ。`file_exists()` 分岐はレンダラから排除 |
| **自サイト REST 内部ディスパッチ** | ○ | 関数呼び出しに置換（REST を経由する理由がない） |
| **外部 HTTP（OGP）** | △ | 取得結果をキャッシュし、**取得時刻つきで中間 JSON に保存**。レンダリング時に外部へ出ない |
| **ユーザー状態 / セッション** | **×** | 決定論の外。表示時レイヤへ（INV-07 §2 の 3 層分類と整合） |

**結論**: 7 種のうち **6 種は正規化で決定論レンダラに載せられる**。
`paidpost` のみユーザー状態依存で載らない（→ ④ へ）。

## 5. ③ 投稿 DB 参照型は「参照 ID を持つ」で足りるか

**足りる。ただし 2 つ条件がある。**

1. **参照の解決結果を記録する** — タイトルやサムネイルは参照先の更新で変わる。
   「変わってよい」のが仕様（内部リンクのタイトル追従は望ましい挙動）なので、
   中間 JSON は ID を持ち、**レンダリング結果のスナップショットを別に残す**（差分検知用）。
2. **参照先が消えた場合の規約を決める** — JIN:R の実装は
   `url_to_postid()` が 0 を返した場合の分岐が曖昧（`$post_ID` が 0 のまま処理継続）。
   中間 JSON 側では `missing` 状態を明示するノードにする。

`blogcard` は URL（`postUrl`）で参照している点にも注意。
**パーマリンク構造を変えると参照が切れる**。中間 JSON では ID 参照へ正規化すべき
（`url_to_postid()` の結果を移行時に確定させる）。

## 6. ④ ユーザー状態依存機能のスコープ判断（PO 上申材料）

| 機能 | テーマ | 実使用 | 判断材料 |
|---|---|---|---|
| `jinr-blocks/paidpost`（有料記事） | JIN:R | **0**（公開記事での使用。16 はテーマソース内の文字列数） | Stripe SDK 同梱（vendor 286 ファイル）。`template_redirect` で `session_start()`。`_jinr_paidpost` / `_jinr_paidpost_product_id` post meta あり。**実運用しているかは未確認**（INV-11 で確認） |
| `loos/restricted-area` | SWELL | **0** | 機構はあるが未使用 |
| `[only_login]` / `[only_logout]` | SWELL | **0** | 同上 |

**上申**: SWELL 側は実データ 0 なのでスコープ外にしても失うものが無い。
JIN:R の `paidpost` は 16 参照あり、実運用なら**移管時に機能が消える**。
INV-11 で「公開記事での実使用か、設定由来の参照か」を確定してから判断する。

## 7. 未了項目

- [ ] 残り 6 コールバックの本体精読（`postcard` / `postlist` / `paidpost` / `slider` / `button` / `category`）
      — 特に `button`（実使用 339）の設定フォールバック範囲
- [ ] SWELL 動的ブロック 10 種のうち `post-link` 以外 9 種のコールバック精読
- [ ] `jinrBlocksCSSAttribute`（ブロック単位インライン CSS）の実使用有無（INV-14 と合流）
- [ ] `is-style-jinr-checkmark` 系クラスの実使用回数（INV-01 / INV-14 と合流）

## 8. 証跡ファイル

| 内容 | 場所 |
|---|---|
| JIN:R `register_block_type` 全 25 件 + `register_block_style` 2 件の全文 | `evidence/probe3-raw.txt` |
| `jinr_blog_card_dynamic_render_callback` 本体 | `evidence/re-jinr-ads.txt` |
| SWELL の normal(22) / dynamic(10) 分類 | `evidence/re-swell-blocks.txt` |
| SWELL `post-link.php` 全文（v1/v2 移行） | `evidence/re-swell-detail.txt` |
| ブロック実使用回数 | `evidence/usage-raw.txt` |
