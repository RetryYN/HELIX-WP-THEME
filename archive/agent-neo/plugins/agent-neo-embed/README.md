# AGENT NEO Embed Plugin

`agent-neo/embed` は ADR-026 と `poc/embed-isolation/RESULTS.md` で all-green になった隔離契約を、実 WordPress でロード可能な Gutenberg ブロックプラグインへ移したものです。

## Scope

- `mode=static`: サーバー側で `staticHtml` を `wp_kses` でサニタイズし、Declarative Shadow DOM（`<template shadowrootmode="open">`）として SSR 出力する。Shadow root には same-origin の `assets/embed-reset.css` を `<link rel="stylesheet">` として含める。`view.js` は DSD 未対応ブラウザ向けの補完だけを行う。
- `mode=interactive`: `embedUrl` の origin が `agent_neo_embed_allowed_sandbox_origins` allowlist と一致する場合だけ `<iframe sandbox="allow-scripts">` の `src` に設定する。不一致・空・不正 URL は iframe を出さず blocked fallback を返す。`allow-same-origin` と `allow-top-navigation*` は付けない。
- postMessage: `{ type: 'ane-embed:ready'|'ane-embed:height', nonce, height }` だけを対象にし、`event.source === iframe.contentWindow` と `payloadId` 一致で検証する。opaque origin のため `event.origin` 検証には依存しない。
- REQ-NF-025: 本プラグインはレンダリングと隔離だけを担当する。AI 生成、mode 判定、variant 生成、統計判定、CV 監査、リスクスコア計算、モデル呼び出しは実装しない。

## PoC mapping

| PoC contract | Product location |
|---|---|
| CARRY-EMBED-001: `mode`, `embed_url`, `title`, `nonce/payload_id` | `src/embed/block.json` attributes (`mode`, `embedUrl`, `title`, `payloadId`, `staticHtml`, `align`) |
| CARRY-EMBED-002: `sandbox="allow-scripts"` and source + nonce postMessage validation | `src/embed/render.php` iframe output / `src/embed/view.js` message handler |
| CARRY-EMBED-003: sandbox-origin CSP and parent `frame-src` requirement | `agent_neo_embed_allowed_sandbox_origins()` / `assets/sandbox-origin/README.md` |
| CARRY-EMBED-004: external same-origin reset CSS for Shadow DOM | `assets/embed-reset.css` / `src/embed/render.php` / `src/embed/view.js` |
| CARRY-EMBED-005: Automation SEO owns sandbox-origin hosting | `assets/sandbox-origin/README.md` |
| CARRY-EMBED-006: CI pipeline candidate | Not implemented in this wave |

## Not Done

- 実 WordPress Gutenberg レンダリング検証。
- sandbox-origin の実ホスティングと HTTP CSP 配信。
- `wp_kses` allowlist の精緻化。
- PoC `verify.py` の Playwright CI 化（T-029）。
