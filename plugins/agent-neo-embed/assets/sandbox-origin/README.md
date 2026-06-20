# Sandbox-origin HTTP response contract

`agent-neo/embed` の `mode=interactive` は Automation SEO がホストする sandbox-origin の HTML 文書を `<iframe sandbox="allow-scripts">` で読み込む。AGENT NEO 側は URL を受け取り表示する薄いレンダラであり、sandbox-origin の実ホスティングは後続ウェーブで実施する。

## Sandbox-origin document CSP

Sandbox-origin の embed 文書は、HTTP レスポンスヘッダで次の CSP を配信する。

```http
Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'none'; img-src 'self'; form-action 'none'; base-uri 'none'; object-src 'none'; frame-ancestors <host-origin>
```

- `connect-src 'none'` により `fetch` / XHR / `sendBeacon` を default deny にする。
- `img-src 'self'` により pixel beacon の外部送信を禁止する。
- `form-action 'none'` により `allow-forms` 変種でも許可外 POST を禁止する。
- `<host-origin>` は記事ページを配信する AGENT NEO 側 origin に置き換える。

## Parent page CSP

親の記事ページは sandbox-origin の iframe だけを許可する。

```http
Content-Security-Policy: frame-src <sandbox-origin>
```

親ページ全体へ `unsafe-inline` を追加しない。`srcdoc` は使用しない。

## Hosting owner

Sandbox-origin の生成・ホスティング・HTTP CSP 配信は Automation SEO 側の責務（CARRY-EMBED-005）とする。AGENT NEO 側は `embedUrl` を iframe の `src` に設定するだけで、AI 生成 HTML / JS を保持しない。
