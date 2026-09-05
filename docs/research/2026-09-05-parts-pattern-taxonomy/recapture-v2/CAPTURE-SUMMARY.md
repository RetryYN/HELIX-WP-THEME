# 再取得の実施記録（footer・記事末尾・カテゴリ面）

- 対象: 実サイト調査の ID 一覧（278 件。URL 一覧は非公開・リポ外）+ ローカル試作 4 面。1 面以上取得できたのは 268 サイト。手順: `scripts/recapture.mjs`（末尾までスクロール → footer / 記事末尾 / カテゴリ面 hero・mid・foot を取得。SP 390px・PC 1440px、高さ上限 1400px）。
- 画像はリポに置かない（取得成功 1,947 枚 + ブラウザ再起動時の再試行で残った重複 5 枚 = 1,952 ファイル・151MB、リポ外の作業領域。コーディング対象は index 上の 1,947 枚）。台帳に残すのはコーディング結果（`coded/`）と集計のみ。

## 取得数（ok）

| 面 | 領域 | SP | PC |
|---|---|---|---|
| top | foot | 267 | 254 |
| article | tail | 74 | 78 |
| article | foot | 74 | 78 |
| cat | hero | 190 | 184 |
| cat | mid | 190 | 184 |
| cat | foot | 190 | 184 |

## 取得できなかった理由（件数 = サイト×面×端末の行数。`no_cat_link` はサイト×端末）

| 理由 | 件数 |
|---|---|
| no_cat_link | 172 |
| http | 35 |
| timeout:article-sp | 18 |
| timeout:article-pc | 10 |
| page.screenshot: Timeout 30000ms exceeded. | 8 |
| page.waitForTimeout: Target page, context or browser has been closed | 4 |
| timeout:cat-pc | 4 |
| timeout:top-pc | 4 |
| page.goto: net::ERR_NAME_NOT_RESOLVED at <url> | 3 |
| timeout:top-sp | 2 |
| page.goto: net::ERR_HTTP2_PROTOCOL_ERROR at <url> | 2 |
| page.evaluate: Execution context was destroyed, most likely because of a navigation | 1 |
| page.goto: Target page, context or browser has been closed | 1 |
| timeout:cat-sp | 1 |
| page.goto: Timeout 20000ms exceeded. | 1 |
| page.screenshot: Protocol error (Page.captureScreenshot): Unable to capture screenshot | 1 |

- `no_cat_link` はトップからカテゴリ / アーカイブ導線を発見できなかったサイト（ヒューリスティック一致なし）。サイト単位では 91 件。`http` はサイト側の bot 壁・UA 制限が主。
- カテゴリ面として取得したページの一部は、実際には単一記事・サービス紹介ページだった（コーディングでは該当 PART を `na` または `other:article` として扱った）。
