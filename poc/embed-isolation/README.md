# AGENT NEO embed isolation PoC

ADR-026 の interactive 埋め込みについて、WordPress なしで 2 origin + HTTP header + ブラウザ挙動を実測する PoC です。

## 構成

- origin-parent: `http://127.0.0.1:18080`
  - ホスト記事ページを模す。
  - 親 CSP は `frame-src` を origin-sandbox のみに制限する。
  - static mode として Shadow DOM + `:host { all: initial !important; }` を生成する。
  - `/sink/*` は egress 到達確認用 sink。
- origin-sandbox: `http://127.0.0.1:18090`
  - interactive embed 配信元を模す。
  - HTTP response header で strict CSP を返す。
  - `fetch` / XHR / `navigator.sendBeacon` / `new Image().src` / `form.submit()` の egress を試みる。
  - form submit は主変種（`sandbox="allow-scripts"`）では sandbox 起因、allow-forms 変種（`sandbox="allow-scripts allow-forms"`）では CSP `form-action 'none'` 起因のブロックとして分けて確認する。

指定ポートが使用中の場合、`server.py` / `verify.py` は空きポートへフォールバックし、実際の URL を出力します。Docker daemon へのアクセスは不要です。

## 起動

```bash
cd /opt/agent-neo/poc/embed-isolation
python3 server.py
```

ブラウザで表示:

```text
http://127.0.0.1:18080/
```

任意ポートで起動:

```bash
python3 server.py --parent-port 38080 --sandbox-port 38090
```

## 計測

```bash
cd /opt/agent-neo/poc/embed-isolation
python3 verify.py
```

`verify.py` はサーバを起動し、Playwright + Chromium で以下を自動判定します。
ローカル socket bind が禁止された実行環境では、同じ `http://127.0.0.1:18080` / `:18090` URL と HTTP ヘッダを Playwright route で返す fallback に切り替わります。

- 親グローバル CSS が Shadow DOM 内へ漏れないこと。
- Shadow DOM 内 CSS が light DOM へ漏れないこと。
- iframe が `sandbox="allow-scripts"` で、`allow-same-origin` / top-navigation 系を含まないこと。
- allow-forms 変種 iframe が `sandbox="allow-scripts allow-forms"` で、`allow-same-origin` / top-navigation 系を含まないこと。
- 親から `iframe.contentWindow.document` へアクセスできないこと。
- 主変種では fetch / XHR / sendBeacon / img beacon / form submit の sink 到達が 0 件であり、form submit は sandbox 起因として扱うこと。
- allow-forms 変種では form submit が CSP `form-action 'none'` 起因でブロックされ、専用 sink 着信が 0 件であること。
- postMessage が `event.source` + nonce 一致時のみ受理され、不一致 nonce と別 source は破棄されること。
- Long Task / navigation duration の参考値。

計測結果は `RESULTS.md` に上書きされます。

## 停止

手動起動した `server.py` は `Ctrl+C` で停止します。

```bash
# 別シェルから止める場合は、該当 server.py プロセスを確認して終了する
ps -ef | grep 'poc/embed-isolation/server.py'
kill <pid>
```

`verify.py` が起動したサーバは検証終了時に自動停止します。
