# THEME-INV-17: テーマA のグローバル改変（正規化リダイレクト無効化・全ページセッション）の影響を確定する

labels: investigation, seo, performance, priority:high
depends: なし

> **状態: 実挙動確認済み / 対処は PO 承認待ち**（2026-08-27）／レポート: `../reports/INV-17-themeA-global-side-effects.md`
> コード解析完了。**停止する正規化 6 種**（末尾スラッシュ・`?p=ID`・ページ送り・
> 大文字小文字・カテゴリ正規 URL・添付ページ）を列挙。
> `session_regenerate_id()` の毎回実行が**セッション継続性・ディスク・キャッシュ**に与える影響を整理。
> **有料記事の実使用 0（INV-11）なので、このセッション処理は全ページで純粋なオーバーヘッド**。
> 検証コマンドと子テーマでの対処案（`remove_filter` / `remove_action`）を用意。
> read-only GETにより、末尾スラッシュ有無がともに200でredirectなし、front/category/searchの
> 3種すべてでセッションCookieが発行されることを確認した。
> **残**: GSCによる実害確認、`object/nextpage.php`の精読、対処方針のPO判断。

## 背景（コード実測 — `10-reverse-themeA.md` §9.5 / 証跡 `evidence/probe3-raw.txt` L254-L302）

`functions.php` 末尾に、サイト全体へ効く改変が 2 つ入っている。

### ① `redirect_canonical` の無条件無効化

```php
add_filter('redirect_canonical', 'themeA_disable_redirect_canonical');
function themeA_disable_redirect_canonical($redirect_url)
{
	$redirect_url = false;
	return $redirect_url;
}
```

コメントの意図は「記事内ページネーションのリンク先を `/pages/2/` にするため」だが、
実装は引数を見ずに**常に `false`**。WordPress の正規化リダイレクトが**全面停止**する。

想定される影響:
- 末尾スラッシュ有無・大文字小文字違い・`?p=ID` 形式などが**すべて 200 を返す**
- 同一コンテンツが複数 URL で到達可能 → 重複コンテンツ・クロールバジェットの浪費
- `canonical` タグが正しく出ていれば実害は緩和されるが、リダイレクトによる集約は失われる

**site-A.example は SEO 施策サイト**であり、この挙動は成果に直結しうる。

### ② 全ページでの `session_start()` + `session_regenerate_id()`

```php
add_action('template_redirect', 'themeA_init_session_start');
function themeA_init_session_start()
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
		session_regenerate_id();
	}
}
```

- フロントの**全ページ描画で発火**（有料記事を使わないページでも）
- `session_regenerate_id()` を毎リクエストで実行 — 通常は権限昇格時のみ呼ぶもの
- 全レスポンスに `Set-Cookie: PHPSESSID` が付く → **ページキャッシュ / CDN と相性が悪い**

## 調査項目

1. **正規化リダイレクトの実挙動確認**（読み取りのみ・自サイト）
   - 末尾スラッシュ有無 / `?p=ID` / 大文字小文字違いで実際に 200 が返るか
   - `canonical` タグが正しく出ているか（プラグイン由来かテーマ由来か）
2. **重複 URL の実害の見積もり** — GSC の「重複」「代替ページ」レポートで
   実際にインデックスの問題が出ているかを確認（GSC は CSV エクスポート車線）
3. **セッションの実害の確認**
   - サーバー側のセッションファイル蓄積（`session.save_path` のファイル数）
   - `Set-Cookie` が全ページに付いているか（レスポンスヘッダ確認）
   - X アクセラレータ / サーバーキャッシュがセッション Cookie でバイパスされていないか
4. **有料記事機能の実使用確認**（INV-11 と合流）— 使っていないならセッション自体が不要
5. **移管方針の決定** — 両方とも Graphix NEO へ引き継がない前提でよいか、
   引き継がない場合に壊れるもの（記事内ページネーションの URL 形式）の代替

## 完了条件

- [ ] 正規化リダイレクト無効化の実挙動が証跡付きで確認されている
- [ ] インデックス面の実害の有無が GSC データで判定されている
- [ ] セッションのキャッシュへの影響が確認されている
- [ ] 両改変について「引き継がない / 代替する / 引き継ぐ」の方針が決まっている

## 注記

これは テーマA テーマの実装であり、修正するとテーマ更新で戻る。
対処するなら子テーマ側で `remove_filter('redirect_canonical', 'themeA_disable_redirect_canonical')` /
`remove_action('template_redirect', 'themeA_init_session_start')` を行う形になるが、
記事内ページネーションが壊れないかの確認が前提（調査項目 5）。
