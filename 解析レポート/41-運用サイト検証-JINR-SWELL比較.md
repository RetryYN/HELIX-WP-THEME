# 運用サイト検証レポート - JIN:R / SWELL 比較

## 1. 概要

本レポートは、公開運用中の WordPress サイト 2 件について、非破壊の外形監視レベルで実施した検証結果をまとめる。

検証は HTTP GET / HEAD、robots / sitemap、HTML head、公開アセット参照、Playwright によるトップページ表示確認のみを対象とした。ログイン、POST、フォーム送信、決済、管理画面操作、負荷試験は実施していない。

| 項目 | 内容 |
|---|---|
| 実施日 | 2026-05-01 JST |
| 検証種別 | 公開サイト非破壊検証 |
| 対象 1 | https://it-shukatu-college.com/ |
| 対象 1 テーマ | JIN:R |
| 対象 2 | https://solobiz-lab.com/ |
| 対象 2 テーマ | SWELL |
| 主な検証手段 | curl / sitemap sampling / Playwright |

## 2. 結論

両サイトともトップページ、robots.txt、sitemap.xml、サイトマップ内サンプル URL は 200 OK で到達できる。テーマ判定も想定どおりで、JIN:R 側は `/wp-content/themes/jinr/`、SWELL 側は `/wp-content/themes/swell/` を確認した。

一方で、計測系の実装には本番上の問題がある。JIN:R 側は公開 HTML に `http://localhost:8000` 参照が残っており、一般ユーザー環境では計測 JS / API が成立しない。SWELL 側は本番計測ドメイン `automation-seo.solobiz-lab.com` が 502 Bad Gateway を返しており、こちらも計測が成立していない。

優先対応は以下の順とする。

1. JIN:R の `localhost:8000` 参照を本番用エンドポイントへ差し替える、または計測スクリプトを一時停止する。
2. `automation-seo.solobiz-lab.com` の 502 を復旧し、`/static/st.js` と `/api/v1/tracking/event` が正しい Content-Type / CORS で返ることを確認する。
3. JIN:R の canonical / www 正規化、Cookie / Cache-Control、sitemap の noindex URL 混入を修正する。
4. SWELL の HTTP から HTTPS への強制リダイレクトを設定する。

## 3. 検証対象

| サイト | URL | 判定テーマ | 根拠 |
|---|---|---|---|
| IT就活大学 | https://it-shukatu-college.com/ | JIN:R | `/wp-content/themes/jinr/` を HTML 内で確認 |
| 大人のひとりビジネスラボ | https://solobiz-lab.com/ | SWELL | `/wp-content/themes/swell/` を HTML 内で確認 |

## 4. 実測サマリー

| 項目 | JIN:R | SWELL |
|---|---:|---:|
| トップページ HTTP | 200 OK | 200 OK |
| curl 取得時間 | 約 0.69 秒 | 約 0.20 秒 |
| HTML サイズ | 315,944 bytes | 111,830 bytes |
| title 文字数 | 6 | 53 |
| description 文字数 | 58 | 121 |
| img タグ数 | 193 | 40 |
| script タグ数 | 41 | 18 |
| stylesheet 数 | 10 | 18 |
| robots.txt | 200 OK | 200 OK |
| sitemap.xml | 200 OK | 200 OK |
| post sitemap URL 数 | 59 | 7 |

初回観測では、SWELL 側の HTML は JIN:R 側の約 35% 程度のサイズで、トップページ応答も軽い。JIN:R 側は画像数、script 数、HTML サイズが大きく、運用プラグインやトップページ構成の影響が大きい。

## 5. 重要 findings

### High-1: JIN:R の公開 HTML に localhost 参照が残っている

JIN:R 側トップページ HTML に以下の参照を確認した。

```html
http://localhost:8000/st.js
http://localhost:8000/api/v1/tracking/event
```

Playwright でトップページを表示した結果、`http://localhost:8000/st.js` は `net::ERR_CONNECTION_REFUSED` となった。これは一般ユーザーのブラウザ上でローカルホストへ接続しに行く状態であり、計測 JS は正常にロードされない。

影響:

- Automation SEO / 独自計測の pageview が欠落する。
- ブラウザコンソールにエラーが出る。
- 本番環境に開発環境設定が混入しているため、運用設定の信頼性が下がる。

推奨対応:

- 本番では `https://automation-seo.<domain>/static/st.js` のような HTTPS の本番 URL を出す。
- 開発 URL が未設定の場合はスクリプトを出力しない。
- `localhost`、`127.0.0.1`、`http://` を本番 HTML に出さない CI / smoke test を追加する。

### High-2: SWELL 側の計測ドメインが 502

SWELL 側トップページは以下の計測先を参照している。

```text
https://automation-seo.solobiz-lab.com/static/st.js
https://automation-seo.solobiz-lab.com/api/v1/tracking/event
```

しかし、実測では両方とも `502 Bad Gateway` を返した。`/static/st.js` は `Content-Type: text/html` の 502 レスポンスとなり、Playwright では `ERR_BLOCKED_BY_ORB` が発生した。

影響:

- 計測 JS がロードされない。
- tracking event API が受信できない。
- 本番サイト側の HTML は本番ドメインを向いているが、配信基盤が落ちている。

推奨対応:

- `automation-seo.solobiz-lab.com` の upstream / backend process / reverse proxy を復旧する。
- `/static/st.js` が `200 OK` かつ `Content-Type: application/javascript` で返ることを確認する。
- `/api/v1/tracking/event` は OPTIONS / POST の両方を検証する。

### Medium-1: JIN:R の www 正規化が不完全

`https://www.it-shukatu-college.com/` が 200 OK を返し、非 www へリダイレクトされなかった。一方、canonical は `https://it-shukatu-college.com/` である。

影響:

- www / non-www の重複 URL として扱われる可能性がある。
- 内部リンク、外部リンク、計測、Search Console 管理で URL が分散する。

推奨対応:

- `www.it-shukatu-college.com` から `it-shukatu-college.com` へ 301 リダイレクトする。
- WordPress のサイト URL、サーバー設定、CDN 設定を同じ正規 URL に揃える。

### Medium-2: SWELL の HTTP が HTTPS に強制されていない

`http://solobiz-lab.com/` が `200 OK` を返し、HTTPS へリダイレクトされなかった。

影響:

- HTTP でアクセスできる導線が残る。
- セキュリティ、SEO、計測の正規化で不利になる。

推奨対応:

- `http://solobiz-lab.com/` から `https://solobiz-lab.com/` へ 301 リダイレクトする。
- HSTS の導入も検討する。

### Medium-3: JIN:R のトップページで PHPSESSID が発行されている

JIN:R 側トップページのレスポンスに `Set-Cookie: PHPSESSID=...; path=/` を確認した。トップページ閲覧だけでセッション Cookie が発行され、`Secure`、`HttpOnly`、`SameSite` 属性も確認できなかった。

影響:

- キャッシュ効率が低下する。
- Cookie 属性不足によりセキュリティレビューで指摘対象になり得る。
- `Cache-Control: no-store, no-cache, must-revalidate` と組み合わさり、公開ページのキャッシュ戦略が弱くなる。

推奨対応:

- トップページ表示時に不要な `session_start()` が走っていないか確認する。
- 必要な Cookie であれば `Secure; HttpOnly; SameSite=Lax` などを付与する。
- 公開ページとログイン / 決済 / フォーム処理ページでキャッシュ方針を分離する。

### Medium-4: JIN:R の sitemap に noindex ページが含まれている

`https://it-shukatu-college.com/thanks-page-template/` は `meta name="robots" content="noindex"` を返すが、sitemap サンプルに含まれていた。

影響:

- noindex と sitemap inclusion が矛盾する。
- Search Console 上で除外や警告の原因になる。

推奨対応:

- noindex ページを sitemap から除外する。
- 決済完了、サンクスページ、内部テンプレート系ページは sitemap 対象外にする。

## 6. 正常確認できた項目

| 項目 | JIN:R | SWELL |
|---|---|---|
| トップページ到達 | passed | passed |
| robots.txt | passed | passed |
| sitemap.xml | passed | passed |
| sitemap child 取得 | passed | passed |
| sitemap サンプル URL 到達 | passed | passed |
| WordPress REST root | passed | passed |
| HTTPS トップページ | passed | passed |
| テーマ識別 | passed | passed |
| canonical 出力 | passed | passed |
| description 出力 | passed | passed |

## 7. サイトマップ検証

### JIN:R

| sitemap | URL 数 | ステータス |
|---|---:|---|
| `sitemap-misc.xml` | 2 | 200 OK |
| `post-sitemap.xml` | 59 | 200 OK |
| `page-sitemap.xml` | 9 | 200 OK |

サンプル確認した URL はすべて 200 OK。

```text
https://it-shukatu-college.com/
https://it-shukatu-college.com/sitemap.html
https://it-shukatu-college.com/job-hunting-measures/shukatu-cards/
https://it-shukatu-college.com/it-shukatu/what-i-want-to-do/
https://it-shukatu-college.com/it-shukatu/humanities-dangerous/
https://it-shukatu-college.com/owner/
https://it-shukatu-college.com/privacy/
https://it-shukatu-college.com/thanks-page-template/
```

### SWELL

| sitemap | URL 数 | ステータス |
|---|---:|---|
| `sitemap-misc.xml` | 2 | 200 OK |
| `category-sitemap.xml` | 3 | 200 OK |
| `post-sitemap.xml` | 7 | 200 OK |
| `archives-sitemap.xml` | 1 | 200 OK |

サンプル確認した URL はすべて 200 OK。

```text
https://solobiz-lab.com/
https://solobiz-lab.com/sitemap.html
https://solobiz-lab.com/category/client-work/web-writer/
https://solobiz-lab.com/category/client-work/
https://solobiz-lab.com/category/client-work/video-editor/
https://solobiz-lab.com/client-work/28/
https://solobiz-lab.com/client-work/39/
https://solobiz-lab.com/client-work/44/
```

## 8. セキュリティヘッダ観測

トップページの HEAD レスポンスでは、両サイトとも以下のセキュリティヘッダは確認できなかった。

```text
Strict-Transport-Security
Content-Security-Policy
X-Frame-Options
Referrer-Policy
Permissions-Policy
```

WordPress REST root では `X-Content-Type-Options: nosniff` を確認したが、トップページでは確認できていない。

推奨対応:

- `Strict-Transport-Security` を HTTPS 正規化後に導入する。
- `X-Frame-Options: SAMEORIGIN` または CSP の `frame-ancestors` を設定する。
- `Referrer-Policy: strict-origin-when-cross-origin` を設定する。
- まずは壊れにくいヘッダから段階導入し、広告 / analytics / iframe との衝突を確認する。

## 9. Playwright 観測

### JIN:R

| 項目 | 結果 |
|---|---|
| HTTP status | 200 |
| page title | IT就活大学 |
| first h1 | 空 |
| failed request | `http://localhost:8000/st.js` |
| browser error | `net::ERR_CONNECTION_REFUSED` |

補足として、Google Analytics の collect リクエストに `net::ERR_ABORTED` が出ているが、これは headless ブラウザや外部通信条件の影響を受けやすいため、今回の主指摘からは外す。

### SWELL

| 項目 | 結果 |
|---|---|
| HTTP status | 200 |
| page title | 大人のひとりビジネスラボ | 賢い大人がはじめる20代からのソロビジネスで人生を攻略するためのWebサイト |
| first h1 | 大人のひとりビジネスラボ |
| failed request | `https://automation-seo.solobiz-lab.com/static/st.js` |
| browser error | `net::ERR_BLOCKED_BY_ORB` |

`ERR_BLOCKED_BY_ORB` の直接原因は、JS として読み込むべき URL が 502 の HTML を返していることと見られる。

## 10. 推奨対応タスク

| 優先度 | 対象 | タスク | 完了条件 |
|---|---|---|---|
| P0 | JIN:R | `localhost:8000` 参照を除去 | 公開 HTML に `localhost` / `127.0.0.1` / `http://` の計測 URL が出ない |
| P0 | SWELL / 計測基盤 | `automation-seo.solobiz-lab.com` の 502 復旧 | `/static/st.js` が 200 `application/javascript`、API が OPTIONS / POST で正常 |
| P1 | JIN:R | www から non-www へ 301 | `https://www.it-shukatu-college.com/` が `https://it-shukatu-college.com/` へ 301 |
| P1 | SWELL | HTTP から HTTPS へ 301 | `http://solobiz-lab.com/` が `https://solobiz-lab.com/` へ 301 |
| P1 | JIN:R | トップページの不要 Cookie を停止 | トップ GET で `PHPSESSID` が出ない、または必要属性が付く |
| P2 | JIN:R | noindex URL を sitemap から除外 | `thanks-page-template` が sitemap に含まれない |
| P2 | 両サイト | 基本セキュリティヘッダ導入 | HSTS / Referrer-Policy / frame 対策の段階導入 |
| P2 | 両サイト | smoke test 自動化 | 公開 HTML、計測 JS、sitemap、canonical、redirect を定期検証 |

## 11. 再検証コマンド例

```powershell
curl.exe -I -L https://it-shukatu-college.com/
curl.exe -I -L https://www.it-shukatu-college.com/
curl.exe -L https://it-shukatu-college.com/ | Select-String -Pattern 'localhost:8000|http://localhost|127.0.0.1'
curl.exe -I -L https://it-shukatu-college.com/sitemap.xml
curl.exe -sS -L https://it-shukatu-college.com/thanks-page-template/ | Select-String -Pattern 'noindex|canonical'
```

```powershell
curl.exe -I -L https://solobiz-lab.com/
curl.exe -I -L http://solobiz-lab.com/
curl.exe -I -L https://automation-seo.solobiz-lab.com/static/st.js
curl.exe -I -L https://automation-seo.solobiz-lab.com/api/v1/tracking/event
curl.exe -I -L https://solobiz-lab.com/sitemap.xml
```

## 12. HELIX 適用結果

| 項目 | 結果 |
|---|---|
| size | S |
| phase | L6 相当の運用外形検証 |
| skill | verification / documentation |
| 本番非破壊検証 | passed |
| テーマ識別 | passed |
| robots / sitemap | passed |
| サンプル URL 到達 | passed |
| Playwright 表示確認 | passed_with_findings |
| 本番変更 | not_performed |
| フォーム / 決済 / ログイン検証 | blocked: 明示許可なし |
| overall | passed_with_high_priority_findings |

## 13. 残リスク

- 計測 API の POST は実施していないため、復旧後に別途送信テストが必要。
- 管理画面、ログイン、フォーム、決済導線は未検証。
- Lighthouse / Core Web Vitals は今回の検証範囲外。ページ重量差は HTML 外形値であり、実ユーザー体験の最終判定ではない。
- セキュリティヘッダは広告、Analytics、外部 iframe と衝突する可能性があるため、段階導入とブラウザ確認が必要。

## 14. 追加探索結果

追加で、WordPress の公開エンドポイント、REST namespace、サンプルページのSEO構造、フォーム/アセット参照を確認した。引き続き GET / HEAD / OPTIONS の範囲に限定し、ログイン、POST、フォーム送信、設定変更は実施していない。

### 14.1 新規 findings

#### Medium-5: REST users で投稿者 slug が公開されている

両サイトで `wp-json/wp/v2/users?per_page=10` が 200 OK を返し、投稿者 slug が取得できた。slug はメールアドレス由来に見える文字列で、アカウント列挙とプライバシー観点の指摘対象になる。

影響:

- ログインユーザー名推測の材料になる。
- メールアドレス由来の slug の場合、個人情報に近い識別子が露出する。
- 攻撃者に WordPress アカウント存在情報を与える。

推奨対応:

- REST users の匿名アクセスを制限する。
- author slug を業務用の匿名表示名へ変更する。
- author archive も含め、ログインID / メール由来文字列が公開URLに出ないようにする。

#### Medium-6: `readme.html` / `license.txt` が公開されている

両サイトで以下が 200 OK を返した。

```text
/readme.html
/license.txt
```

WordPress では珍しくないが、ハードニング観点では不要な標準ファイル公開は減らす方がよい。

推奨対応:

- Webサーバーまたはセキュリティプラグインで `readme.html` を 404 / 403 にする。
- `license.txt` も必要がなければ公開対象から外す。

#### Medium-7: `xmlrpc.php` が有効

両サイトで `xmlrpc.php` は GET に対して 405 を返し、本文に XML-RPC は POST のみ受け付ける旨が出た。これは XML-RPC エンドポイント自体が残っている状態を示す。

影響:

- WordPress では XML-RPC 経由の認証攻撃、pingback 悪用、不要な攻撃面として扱われることが多い。

推奨対応:

- Jetpack、外部投稿アプリ、XML-RPC 依存連携がなければ無効化する。
- 依存がある場合はWAF / rate limit / IP制限を検討する。

#### Medium-8: JIN:R はサンプルページ全体で JSON-LD が出ていない

JIN:R 側の確認サンプルでは、トップ、記事、固定ページのすべてで `application/ld+json` が 0 件だった。

確認サンプル:

```text
/
/job-hunting-measures/shukatu-cards/
/it-shukatu/what-i-want-to-do/
/owner/
/privacy/
```

SWELL 側はトップ、カテゴリ、記事で 1-2 件の JSON-LD を確認した。

推奨対応:

- JIN:R 側で WebSite / Organization / BreadcrumbList / Article の出力状態を確認する。
- SEOプラグインまたはテーマ設定で JSON-LD が無効化されていないか確認する。

#### Medium-9: JIN:R トップページに H1 がない

Playwright / HTML parsing の双方で、JIN:R トップページの H1 は 0 件だった。記事・固定ページでは H1 が確認できるため、トップページ固有の構成問題と見られる。

推奨対応:

- トップページにサイト主題を表す H1 を1つ追加する。
- 視覚デザイン上不要な場合でも、アクセシビリティとSEOのために構造上の H1 を持たせる。

#### Low-1: SWELL のカテゴリページで meta description が空

SWELL 側カテゴリページでは `description` が空だった。

確認サンプル:

```text
/category/client-work/web-writer/
/category/client-work/
```

推奨対応:

- 主要カテゴリにカテゴリ説明文を設定する。
- description テンプレートがカテゴリページにも適用されるか確認する。

#### Low-2: 画像 alt 空が多い

サンプルページで alt のない画像が複数見つかった。

| サイト | ページ例 | img | alt 空 |
|---|---:|---:|---:|
| JIN:R | top | 93 | 25 |
| JIN:R | `/job-hunting-measures/shukatu-cards/` | 98 | 38 |
| JIN:R | `/it-shukatu/what-i-want-to-do/` | 92 | 26 |
| SWELL | top | 40 | 19 |
| SWELL | `/category/client-work/` | 28 | 14 |
| SWELL | 記事ページサンプル | 1 | 0 |

装飾画像であれば空 alt は許容されるが、記事カード、サムネイル、説明画像で空の場合は改善対象。

### 14.2 REST / API 観測

| 項目 | JIN:R | SWELL |
|---|---:|---:|
| REST namespace 数 | 11 | 9 |
| REST route 数 | 228 | 251 |
| `aseo/v1` | あり | あり |
| `contact-form-7/v1/contact-forms` | 403 | 403 |
| `google-site-kit/v1/.../connection` | 401 | 401 |
| `aseo/v1/status` | 401 | 401 |
| `aseo/v1/compatibility` | 401 | 401 |
| `aseo/v1/block-catalog` | 401 | 401 |

`aseo/v1` は AI write / media update / setup 系の route 名が REST index に出ている。ただし、GET確認では主要読み取り系は 401、書き込み系は GET では 404 となり、少なくとも匿名 GET で内容取得できる状態ではなかった。

確認できた write-like route 名:

```text
/aseo/v1/update-apply
/aseo/v1/setup/install-plugin
/aseo/v1/setup/run
/aseo/v1/ai/write
/aseo/v1/ai/bulk
/aseo/v1/ai/block-edit
/aseo/v1/media/upload
/aseo/v1/media/update
/aseo/v1/media/delete
/aseo/v1/media/bulk-upload
```

注意:

- route 名の公開自体は WordPress REST では一般的だが、攻撃面の説明情報になる。
- 権限検証、nonce / Application Password / capability check、監査ログ、dry-run / rollback は必須。
- POST は未実施のため、書き込み系の最終的な認可検証は未完了。

### 14.3 公開ファイル / エンドポイント

| パス | JIN:R | SWELL | 評価 |
|---|---:|---:|---|
| `/wp-login.php` | 200 | 200 | 通常。ただしログイン保護は別途必要 |
| `/xmlrpc.php` | 405 | 405 | エンドポイント有効。不要なら無効化 |
| `/readme.html` | 200 | 200 | ハードニング対象 |
| `/license.txt` | 200 | 200 | ハードニング対象 |
| `/.well-known/security.txt` | 404 | 404 | 任意。設置すると脆弱性連絡窓口を明示できる |
| `/wp-json/wp/v2/settings` | 401 | 401 | OK |
| `/wp-content/debug.log` | 404 | 404 | OK |

### 14.4 アセット / キャッシュ観測

両サイトともトップページと主要CSSで Brotli 圧縮を確認した。テーマCSSはどちらも `Cache-Control: max-age=604800` が返っており、静的アセットのキャッシュは効いている。

| 対象 | Content-Encoding | Cache-Control |
|---|---|---|
| JIN:R top | br | `no-store, no-cache, must-revalidate` + `s-maxage=10` |
| SWELL top | br | 明示的な cache-control なし |
| JIN:R theme CSS | br | `max-age=604800` |
| SWELL main CSS | br | `max-age=604800` |

評価:

- 静的アセットは概ね正常。
- JIN:R のトップページは Cookie / no-store と組み合わさってキャッシュ効率が悪い。
- SWELL はトップページに `Vary: User-Agent` があるため、キャッシュキー分散には注意が必要。

### 14.5 追加推奨タスク

| 優先度 | 対象 | タスク | 完了条件 |
|---|---|---|---|
| P1 | 両サイト | REST users の匿名公開を制限 | `wp-json/wp/v2/users` が匿名で一覧を返さない |
| P1 | 両サイト | author slug を匿名化 | メール由来・ログインID由来の slug が公開URL/RESTに出ない |
| P1 | 両サイト | XML-RPC 無効化または制限 | 依存がなければ `/xmlrpc.php` を 403/404 |
| P2 | 両サイト | `readme.html` / `license.txt` 非公開化 | 不要な標準ファイルが 404/403 |
| P2 | JIN:R | JSON-LD 出力を復旧 | トップ/記事で WebSite / Article / BreadcrumbList 等を確認 |
| P2 | JIN:R | トップ H1 を追加 | トップページ H1 が1件 |
| P3 | SWELL | カテゴリ description 設定 | 主要カテゴリで description が空でない |
| P3 | 両サイト | 画像 alt 棚卸し | 重要画像の alt 空を解消 |
