# THEME-INV-17 レポート — テーマA のグローバル改変の影響

- 対象イシュー: `issues/THEME-INV-17-themeA-global-side-effects.md`
- 状態: **コード解析・HTTP実挙動確認完了 / GSC・サーバー側影響・対処判断は未了**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/probe3-raw.txt` L276-L302（該当コード全文）・
  `evidence/re-themeA-boot.txt`（`functions.php` のフック一覧）

## 1. 対象コード（全文）

`functions.php` 末尾、ブロック登録の直後に 2 つのグローバル改変がある。

```php
/**
 * 記事内でページネーションを表示する際、通常ではページネーションのリンク先がページのURLになるが、
 * /pages/2/などのURLになるようにする
 */
add_filter('redirect_canonical', 'themeA_disable_redirect_canonical');
function themeA_disable_redirect_canonical($redirect_url)
{
	$redirect_url = false;
	return $redirect_url;
}

function themeA_init_session_start()
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
		session_regenerate_id();
	}
}
add_action('template_redirect', 'themeA_init_session_start');
```

## 2. ① `redirect_canonical` の無条件無効化 — 解析

### 2.1 コードから確定していること

- フィルタは `$redirect_url` を受け取るが、**引数を一切見ずに `false` を返す**。
- コメントが述べる意図（記事内ページネーションの URL 形式）に対して、
  実装は**全リクエストに適用**される。条件分岐が無い。
- WordPress の `redirect_canonical()` は `wp` フックの中で走り、
  戻り値が falsy ならリダイレクトを行わない。よって **正規化リダイレクトが全面停止**する。

### 2.2 停止する正規化（WordPress コアの `redirect_canonical` が本来行うもの）

| 正規化 | 例 | 停止後の挙動 |
|---|---|---|
| 末尾スラッシュの統一 | `/foo` → `/foo/` | 両方が 200 |
| `?p=ID` → パーマリンク | `/?p=123` → `/slug/` | `?p=123` のまま 200 |
| カテゴリ / タグの正規 URL | 旧 slug → 新 slug | 旧 slug が 200 or 404 |
| 添付ファイルページの正規化 | — | 停止 |
| ページ送りの正規化 | `/page/1/` → `/` | `/page/1/` が 200 |
| 大文字小文字・余分なスラッシュ | `/FOO//` | そのまま 200 |

### 2.3 SEO 上の含意

**site-A.example は SEO 施策サイト**（人間執筆 59 記事・繁忙期に収益が立つ実績サイト）。
同一コンテンツが複数 URL で 200 を返すことの影響:

- クロールバジェットの分散
- 内部リンクの評価が複数 URL へ割れる
- `canonical` タグが正しく出ていれば**インデックス統合はされる**（緩和される）が、
  リダイレクトによる明示的な集約は失われる

**確認が要る前提条件**: `canonical` タグを誰が出しているか。
テーマA には `_themeA_canonical_display` という post meta があり（`evidence` の post meta 27 種）、
テーマ側で canonical を扱っている形跡がある。**出力の実体は未確認**
（`reports/INV-06-structured-data-gap.md` の未了項目と共通）。

## 3. ② 全ページでの `session_start()` + `session_regenerate_id()` — 解析

### 3.1 コードから確定していること

- `template_redirect` は**フロントの全ページ描画で発火**する
  （管理画面・REST・cron では発火しない）。
- 条件は `session_status() !== PHP_SESSION_ACTIVE` のみ。
  **ページ種別・機能の有効/無効を見ていない。**
- `session_regenerate_id()` を**毎リクエストで呼ぶ**。

### 3.2 `session_regenerate_id()` を毎回呼ぶことの意味

通常この関数は**権限レベルが変わる瞬間**（ログイン直後・権限昇格時）にのみ呼ぶ。
セッション固定化攻撃への対策であり、毎リクエストで呼ぶ設計ではない。

毎回呼んだ場合に起きること:

| 影響 | 内容 |
|---|---|
| セッションの継続性 | ID が毎回変わる。旧セッションファイルは（既定では）削除されず残る |
| ディスク | `session.save_path` にセッションファイルが**リクエスト数だけ蓄積**する。GC 頼み |
| Cookie | 全レスポンスに `Set-Cookie: PHPSESSID=…` が付く |
| キャッシュ | **`Set-Cookie` を持つレスポンスはキャッシュ対象外**にするのが一般的。ホスティング の X アクセラレータや CDN がバイパスする可能性 |
| 有料記事機能 | セッションに依存している以上、ID が毎回変わると**状態が保てない**はず。実装の整合性に疑問 |

### 3.3 有料記事との関係（INV-11 と連動）

`reports/INV-11-scope-boundary.md` で確定したとおり、
**`themeA-blocks/paidpost` の公開記事での実使用は 0**。
有料記事を使っていないなら、このセッション処理は**全ページで純粋なオーバーヘッド**になる。

## 4. 実挙動の確認（2026-08-27・read-only）

- 記事URLの末尾スラッシュ有無は**両方HTTP 200**、`Location`なし、redirect 0回だった。
- front / category / search の**3ページすべて**でセッションCookie発行を確認した。
- コード解析で予測した「正規化redirectの停止」と「全ページセッション」が実環境でも成立している。

以下は再現手順と、なお未了の確認項目である。

### 4.1 正規化リダイレクトの実挙動（自サイトへの HTTP GET のみ）

```
# 末尾スラッシュ有無
curl -sI https://site-A.example/<記事slug>  | head -1
curl -sI https://site-A.example/<記事slug>/ | head -1
# ?p=ID 形式
curl -sI 'https://site-A.example/?p=<記事ID>' | head -1
# ページ送りの正規化
curl -sI https://site-A.example/page/1/ | head -1
```

判定: すべて `200` なら正規化が停止していることの実証。`301` が返れば別要因で機能している。

あわせて canonical の出力を確認:
```
curl -s https://site-A.example/<記事slug> | grep -i 'rel="canonical"'
```

### 4.2 セッションの実挙動

```
# Set-Cookie が全ページに付くか
curl -sI https://site-A.example/ | grep -i 'set-cookie'
# セッションファイルの蓄積（SSH・読み取りのみ）
php -r 'echo ini_get("session.save_path");'
ls -1 <save_path> | wc -l
```

### 4.3 インデックス面の実害（GSC）

GSC の「ページ」レポートで次を確認（CSV エクスポート車線）:
- 「重複しています。ユーザーにより選択された正規 URL がない」の件数
- 「代替ページ（適切な canonical タグあり）」の件数
- `?p=` 形式や `/page/1/` が検出 URL として現れているか

## 5. 移管方針（暫定）

| 改変 | 引き継ぐか | 理由 |
|---|---|---|
| `redirect_canonical` 無効化 | **引き継がない** | 正規化はコアに任せる。ページネーションの URL 形式が必要なら、**その条件でのみ** filter を返す実装にする |
| 全ページセッション | **引き継がない** | 有料記事をスコープ外にするなら不要（INV-11）。必要になっても「必要なページでのみ開始」「`regenerate_id` はログイン時のみ」が正しい |

**現行サイトへの対処**（テーマ更新で消えないよう子テーマ側で行う）:

```php
// themeA-child/functions.php（案・未適用）
remove_filter('redirect_canonical', 'themeA_disable_redirect_canonical');
remove_action('template_redirect', 'themeA_init_session_start');
```

**前提**: 記事内ページネーションが壊れないことの確認（§6）。
テーマA の `object/nextpage.php` が `<!--nextpage-->` によるページ送りを担っており、
`redirect_canonical` を戻すと `/pages/2/` 形式が `/2/` へリダイレクトされる可能性がある。

## 6. 未了項目

- [x] §4.1 の末尾スラッシュ正規化と §4.2 の全ページCookie発行
- [ ] §4.1 のその他URL形式、§4.2 のサーバー側ファイル数、§4.3 のGSC確認
- [ ] `object/nextpage.php` の精読 — ページネーションの URL 生成方式
- [ ] `<!--nextpage-->` を使った記事が実在するか（実在しなければ、そもそも改変の目的自体が不要）
- [ ] canonical タグの出力主体（テーマ / `automation-seo` / その他）
- [ ] `session.save_path` のファイル数

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| `redirect_canonical` 無効化・セッション開始の全文 | `evidence/probe3-raw.txt` L276-L302 |
| `functions.php` のフック登録一覧 | `evidence/re-themeA-boot.txt` |
| `paidpost` の実使用 0 の確認 | `evidence/usage-raw.txt` / `reports/INV-11-scope-boundary.md` |
| post meta `_themeA_canonical_display` の存在 | `reports/INV-08-agent-interface-gap.md` §3 |
