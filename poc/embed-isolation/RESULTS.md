# AGENT NEO embed isolation PoC results

- Date: 2026-06-20
- Verdict: **PASS**
- origin-parent: `http://127.0.0.1:18080`
- origin-sandbox: `http://127.0.0.1:18090`

## 実測結果

| 検証項目 | 実測値 | 判定 | 補足 |
|---|---|---|---|
| parent page load | `{"elapsed_ms": 160.13, "status": 200}` | PASS | origin-parent が通常ロードできる |
| static Shadow DOM non-inherited CSS isolation | `{"cardBackground": "rgb(232, 244, 255)", "cardBorderColor": "rgb(18, 92, 160)", "cardBorderWidth": "2px", "explicitBackground": "rgb(232, 244, 255)", "explicitColor": "rgb(16, 24, 32)", "explicitFont": "Arial, sans-serif", "hasShadowRoot": true, "hostBackground": "rgb(238, 247, 255)", "hostColor": "rgb(1, 2, 3)", "hostFont": "Arial, sans-serif", "inheritedBackground": "rgb(232, 244, 255)", "inheritedBorderColor": "rgb(18, 92, 160)", "inheritedColor": "rgb(1, 2, 3)", "inheritedFont": "Arial, sans-serif", "inlineStyleCount": 0, "lightBackground": "rgb(250, 250, 250)", "resetLinkHref": "http://127.0.0.1:18080/embed-reset.css", "resetRules": [":host { color-scheme: initial !important; forced-color-adjust: initial !important; math-depth: initial !important; position: initial !important; position-anchor: initial !important; text-size-adjust: initial !important; appearance: initial !important; font-feature-settings: initial !important; font-kerning: initial !important; font-language-override: initial !important; font-optical-sizing: initial !important; font-palette: initial !important; font-size: initial !important; font-size-adjust: initial !important; font-stretch: initial !important; font-style: initial !important; font-synthesis: initial !important; font-variant: initial !important; font-variation-settings: initial !important; font-weight: initial !important; position-area: initial !important; text-orientation: initial !important; text-rendering: initial !important; text-spacing-trim: initial !important; -webkit-font-smoothing: initial !important; -webkit-locale: initial !important; -webkit-text-orientation: initial !important; -webkit-writing-mode: initial !important; writing-mode: initial !important; zoom: initial !important; accent-color: initial !important; place-content: initial !important; place-items: initial !important; place-self: initial !important; alignment-baseline: initial !important; anchor-name: initial !important; anchor-scope: initial !important; animation-composition: initial !important; animation: initial !important; animation-trigger: initial !important; app-region: initial !important; aspect-ratio: initial !important; backdrop-filter: initial !important; backface-visibility: initial !important; background-blend-mode: initial !important; baseline-shift: initial !important; baseline-source: initial !important; block-size: initial !important; border-block: initial !important; border: initial !important; border-radius: initial !important; border-collapse: initial !important; border-end-end-radius: initial !important; border-end-start-radius: initial !important; border-inline: initial !important; border-shape: initial !important; border-start-end-radius: initial !important; border-start-start-radius: initial !important; inset: initial !important; box-decoration-break: initial !important; box-shadow: initial !important; break-after: initial !important; break-before: initial !important; break-inside: initial !important; buffered-rendering: initial !important; caption-side: initial !important; caret-animation: initial !important; caret-color: initial !important; caret-shape: initial !important; clear: initial !important; clip: initial !important; clip-path: initial !important; clip-rule: initial !important; color-interpolation: initial !important; color-interpolation-filters: initial !important; color-rendering: initial !important; columns: initial !important; column-fill: initial !important; gap: initial !important; column-rule: initial !important; column-span: initial !important; contain: initial !important; contain-intrinsic-block-size: initial !important; contain-intrinsic-size: initial !important; contain-intrinsic-inline-size: initial !important; container: initial !important; content: initial !important; content-visibility: initial !important; corner-shape: initial !important; corner-block-end-shape: initial !important; corner-block-start-shape: initial !important; counter-increment: initial !important; counter-reset: initial !important; counter-set: initial !important; cursor: initial !important; cx: initial !important; cy: initial !important; d: initial !important; dominant-baseline: initial !important; dynamic-range-limit: initial !important; empty-cells: initial !important; field-sizing: initial !important; fill: initial !important; fill-opacity: initial !important; fill-rule: initial !important; filter: initial !important; flex: initial !important; flex-flow: initial !important; float: initial !important; flood-color: initial !important; flood-opacity: initial !important; grid: initial !important; grid-area: initial !important; height: initial !important; hyphenate-character: initial !important; hyphenate-limit-chars: initial !important; hyphens: initial !important; image-orientation: initial !important; image-rendering: initial !important; initial-letter: initial !important; inline-size: initial !important; inset-block: initial !important; inset-inline: initial !important; interactivity: initial !important; interest-delay: initial !important; interpolate-size: initial !important; isolation: initial !important; letter-spacing: initial !important; lighting-color: initial !important; line-break: initial !important; list-style: initial !important; margin-block: initial !important; margin: initial !important; margin-inline: initial !important; marker: initial !important; mask: initial !important; mask-type: initial !important; math-shift: initial !important; math-style: initial !important; max-block-size: initial !important; max-height: initial !important; max-inline-size: initial !important; max-width: initial !important; min-block-size: initial !important; min-height: initial !important; min-inline-size: initial !important; min-width: initial !important; mix-blend-mode: initial !important; object-fit: initial !important; object-position: initial !important; object-view-box: initial !important; offset: initial !important; opacity: initial !important; order: initial !important; orphans: initial !important; outline: initial !important; outline-offset: initial !important; overflow-anchor: initial !important; overflow-block: initial !important; overflow-clip-margin: initial !important; overflow-inline: initial !important; overflow-wrap: initial !important; overflow: initial !important; overlay: initial !important; overscroll-behavior-block: initial !important; overscroll-behavior-inline: initial !important; overscroll-behavior: initial !important; padding-block: initial !important; padding-inline: initial !important; page: initial !important; page-orientation: initial !important; paint-order: initial !important; perspective: initial !important; perspective-origin: initial !important; pointer-events: initial !important; position-try: initial !important; position-visibility: initial !important; print-color-adjust: initial !important; quotes: initial !important; r: initial !important; reading-flow: initial !important; reading-order: initial !important; resize: initial !important; rotate: initial !important; ruby-align: initial !important; ruby-position: initial !important; rx: initial !important; ry: initial !important; scale: initial !important; scroll-behavior: initial !important; scroll-initial-target: initial !important; scroll-margin-block: initial !important; scroll-margin: initial !important; scroll-margin-inline: initial !important; scroll-marker-group: initial !important; scroll-padding-block: initial !important; scroll-padding: initial !important; scroll-padding-inline: initial !important; scroll-snap-align: initial !important; scroll-snap-stop: initial !important; scroll-snap-type: initial !important; scroll-target-group: initial !important; scroll-timeline: initial !important; scrollbar-color: initial !important; scrollbar-gutter: initial !important; scrollbar-width: initial !important; shape-image-threshold: initial !important; shape-margin: initial !important; shape-outside: initial !important; shape-rendering: initial !important; size: initial !important; speak: initial !important; stop-color: initial !important; stop-opacity: initial !important; stroke: initial !important; stroke-dasharray: initial !important; stroke-dashoffset: initial !important; stroke-linecap: initial !important; stroke-linejoin: initial !important; stroke-miterlimit: initial !important; stroke-opacity: initial !important; stroke-width: initial !important; tab-size: initial !important; table-layout: initial !important; text-align: initial !important; text-align-last: initial !important; text-anchor: initial !important; text-autospace: initial !important; text-box: initial !important; text-combine-upright: initial !important; text-decoration: initial !important; text-decoration-skip-ink: initial !important; text-emphasis: initial !important; text-emphasis-position: initial !important; text-indent: initial !important; text-justify: initial !important; text-overflow: initial !important; text-shadow: initial !important; text-transform: initial !important; text-underline-offset: initial !important; text-underline-position: initial !important; text-wrap: initial !important; timeline-scope: initial !important; timeline-trigger: initial !important; touch-action: initial !important; transform: initial !important; transform-box: initial !important; transform-origin: initial !important; transform-style: initial !important; transition: initial !important; translate: initial !important; trigger-scope: initial !important; user-select: initial !important; vector-effect: initial !important; vertical-align: initial !important; view-timeline: initial !important; view-transition-class: initial !important; view-transition-group: initial !important; view-transition-name: initial !important; view-transition-scope: initial !important; visibility: initial !important; border-spacing: initial !important; -webkit-box-align: initial !important; -webkit-box-decoration-break: initial !important; -webkit-box-direction: initial !important; -webkit-box-flex: initial !important; -webkit-box-ordinal-group: initial !important; -webkit-box-orient: initial !important; -webkit-box-pack: initial !important; -webkit-box-reflect: initial !important; -webkit-line-break: initial !important; -webkit-line-clamp: initial !important; -webkit-mask-box-image: initial !important; -webkit-rtl-ordering: initial !important; -webkit-ruby-position: initial !important; -webkit-tap-highlight-color: initial !important; -webkit-text-combine: initial !important; -webkit-text-decorations-in-effect: initial !important; -webkit-text-fill-color: initial !important; -webkit-text-security: initial !important; -webkit-text-stroke: initial !important; -webkit-user-drag: initial !important; white-space-collapse: initial !important; widows: initial !important; width: initial !important; will-change: initial !important; word-break: initial !important; word-spacing: initial !important; x: initial !important; y: initial !important; z-index: initial !important; display: block !important; box-sizing: border-box !important; padding: 12px !important; color: rgb(1, 2, 3) !important; font-family: Arial, sans-serif !important; line-height: 1.5 !important; background: rgb(238, 247, 255) !important; }", ":host *, :host ::before, :host ::after { box-sizing: border-box; }", ".shadow-card { border: 2px solid rgb(18, 92, 160); padding: 12px; background: rgb(232, 244, 255); }", "#shadow-explicit { color: rgb(16, 24, 32); font-family: Arial, sans-serif; background: rgb(232, 244, 255); border-color: rgb(18, 92, 160); }", "#shadow-inherited { color: inherit; font-family: inherit; background: rgb(232, 244, 255); border: 1px solid rgb(18, 92, 160); }", ".light-leak-target { background: rgb(0, 255, 0) !important; }"], "resetState": {"error": false, "href": "http://127.0.0.1:18080/embed-reset.css", "loaded": true}}` | PASS | 外部 same-origin reset CSS 配信により、非継承・継承の双方で親 CSS 漏洩なしを実測 |
| static Shadow DOM inherited CSS observation | `{"hostColor": "rgb(1, 2, 3)", "hostFont": "Arial, sans-serif", "inheritedColor": "rgb(1, 2, 3)", "inheritedFont": "Arial, sans-serif", "warning": null}` | PASS | 継承プロパティも外部 reset CSS の実測上は親グローバル CSS に負けなかった |
| iframe sandbox tokens | `{"formActionSandbox": "allow-scripts allow-forms", "formActionSrc": "http://127.0.0.1:18090/embed-allow-forms.html?nonce=ane-poc-nonce-20260620&sink=http%3A//127.0.0.1%3A18080/sink&probe=form-only&formPath=form-action-csp-post", "formActionTitle": "AGENT NEO interactive form-action CSP PoC", "heightAttr": "160", "heightStyle": "", "sandbox": "allow-scripts", "src": "http://127.0.0.1:18090/embed.html?nonce=ane-poc-nonce-20260620&sink=http%3A//127.0.0.1%3A18080/sink", "title": "AGENT NEO interactive embed PoC"}` | PASS | `allow-scripts` のみを許可する主変種で、親 origin 化と top navigation を禁止 |
| allow-forms variant sandbox tokens | `{"formActionSandbox": "allow-scripts allow-forms", "formActionSrc": "http://127.0.0.1:18090/embed-allow-forms.html?nonce=ane-poc-nonce-20260620&sink=http%3A//127.0.0.1%3A18080/sink&probe=form-only&formPath=form-action-csp-post", "formActionTitle": "AGENT NEO interactive form-action CSP PoC", "heightAttr": "160", "heightStyle": "", "sandbox": "allow-scripts", "src": "http://127.0.0.1:18090/embed.html?nonce=ane-poc-nonce-20260620&sink=http%3A//127.0.0.1%3A18080/sink", "title": "AGENT NEO interactive embed PoC"}` | PASS | `allow-forms` 変種でも親 origin 化と top navigation は禁止 |
| parent cannot read iframe document | `{"accessible": false, "message": "Failed to read a named property 'document' from 'Window': Blocked a frame with origin \"http://127.0.0.1:18080\" from accessing a cross-origin frame.", "name": "SecurityError"}` | PASS | opaque origin / same-origin policy により親から iframe document 参照不可 |
| egress attempts blocked | `{"channel_evidence": {"fetch": {"api_result_reference_only": {"detail": "TypeError", "status": "blocked"}, "block_reason": "CSP", "console_evidence": ["Connecting to 'http://127.0.0.1:18080/sink/fetch' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked.", "Fetch API cannot load http://127.0.0.1:18080/sink/fetch. Refused to connect because it violates the document's Content Security Policy."], "playwright_requests": [], "request_failures": [], "responses": []}, "form": {"api_result_reference_only": {"detail": "sandbox should prevent network before CSP", "status": "submitted-call-returned"}, "block_reason": "sandbox", "console_evidence": ["Blocked form submission to 'http://127.0.0.1:18080/sink/form-post' because the form's frame is sandboxed and the 'allow-forms' permission is not set."], "playwright_requests": [], "request_failures": [], "responses": []}, "img": {"api_result_reference_only": {"detail": "error", "status": "blocked"}, "block_reason": "CSP", "console_evidence": ["Loading the image 'http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781' violates the following Content Security Policy directive: \"img-src 'self'\". The action has been blocked."], "playwright_requests": ["http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"], "request_failures": [{"failure": "csp", "url": "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"}], "responses": []}, "sendBeacon": {"api_result_reference_only": {"detail": "return=true", "status": "queued-by-api"}, "block_reason": "CSP", "console_evidence": ["Connecting to 'http://127.0.0.1:18080/sink/beacon' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked."], "playwright_requests": [], "request_failures": [], "responses": []}, "xhr": {"api_result_reference_only": {"detail": "error", "status": "blocked"}, "block_reason": "CSP", "console_evidence": ["Connecting to 'http://127.0.0.1:18080/sink/xhr' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked."], "playwright_requests": ["http://127.0.0.1:18080/sink/xhr"], "request_failures": [{"failure": "csp", "url": "http://127.0.0.1:18080/sink/xhr"}], "responses": []}}, "egress_results": {"fetch": {"detail": "TypeError", "status": "blocked"}, "form": {"detail": "sandbox should prevent network before CSP", "status": "submitted-call-returned"}, "img": {"detail": "error", "status": "blocked"}, "sendBeacon": {"detail": "return=true", "status": "queued-by-api"}, "xhr": {"detail": "error", "status": "blocked"}}, "playwright_sink_requests": ["http://127.0.0.1:18080/sink/xhr", "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"], "playwright_sink_responses": [], "request_failures": [{"failure": "csp", "url": "http://127.0.0.1:18080/sink/xhr"}, {"failure": "csp", "url": "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"}], "sink_log_count": 0, "sink_paths": [], "wait": {"observed_keys": ["fetch", "form", "img", "sendBeacon", "xhr"], "settle_ms": 300, "settled": true, "sink_request_events": 2, "timeout_ms": 8000}}` | PASS | 判定は5キー揃い + sink 宛300ms静止 + sink 到達0件 + sink 宛成功レスポンス0件の実通信基準 |
| interactive form-action CSP (allow-forms variant) | `{"channel_evidence": {"api_result_reference_only": null, "block_reason": "CSP", "console_evidence": ["Framing 'http://127.0.0.1:18080/sink/form-action-csp-post' violates the following Content Security Policy directive: \"frame-src http://127.0.0.1:18090\". The request has been blocked.\n", "Sending form data to 'http://127.0.0.1:18080/sink/form-action-csp-post' violates the following Content Security Policy directive: \"form-action 'none'\". The request has been blocked.\n"], "playwright_requests": [], "request_failures": [], "responses": []}, "csp_violations": [], "egress_results": {}, "form_action_result": null, "sink_log_count": 0, "sink_paths": [], "successful_response_count": 0, "wait": {"observed_keys": [], "settle_ms": 300, "settled": false, "sink_request_events": 2, "timeout_ms": 8000}}` | PASS | `allow-forms` ありでも CSP `form-action 'none'` 証拠 + form POST の sink 着信0件 + 成功レスポンス0件を確認 |
| postMessage source + nonce validation | `{"accepted_count": 3, "accepted_heights": [180, 201], "origins": ["null", "null", "null", "null", "null", "null", "null", "null", "http://127.0.0.1:18080"], "rejected_count": 6, "rejected_reasons": ["nonce", "source", "source", "source", "source", "source"]}` | PASS | origin は null 前提で、event.source と nonce による受理/破棄を確認 |
| reference performance metrics | `{"parent": {"longTaskCount": 0, "longTaskMaxMs": 0, "longTaskObserverSupported": true, "navigationDurationMs": 148.39999961853027}, "sandbox": {"longTaskCount": 0, "longTaskMaxMs": 0, "longTaskObserverSupported": true}}` | PASS | 参考値。INP は操作入力がないため Long Task / navigation duration で代替記録 |

## 設計どおり効いた点

- Static mode: Shadow DOM 内の reset CSS は inline `<style>` ではなく親オリジン配信の `/embed-reset.css` として適用したため、親 CSP `style-src 'self'` 下でロード可能だった。
- Static mode: 外部 same-origin reset CSS 配信により、非継承・継承の双方で親 CSS 漏洩なしを実測した。
- Interactive mode: `<iframe sandbox="allow-scripts">` で `allow-same-origin` / top-navigation 系を付けない場合、親から iframe document へアクセスできなかった。
- Interactive mode 主変種: fetch / XHR / sendBeacon は `connect-src 'none'`、img beacon は `img-src 'self'`、form submit は `allow-forms` 不在の sandbox により sink 到達 0 件だった。
- Interactive form-action CSP allow-forms 変種: `sandbox="allow-scripts allow-forms"` の form submit は CSP `form-action 'none'` により sink 着信 0 件だった。
- postMessage: sandbox iframe の `event.origin` は `null` と観測されたため、ADR-026 どおり `event.source === iframe.contentWindow` + nonce で受理した。
- Static mode: 継承プロパティ（color / font-family）も今回の実測では親グローバル CSS に負けなかった。

## 効かなかった点 / 想定外

- 主変種の form submit は iframe sandbox に `allow-forms` がないため、CSP `form-action` の通電検証としては扱わない。
- INP は実ユーザー入力の計測値ではないため、本 PoC では Long Task と navigation duration の参考値に留めた。

## CARRY-EMBED 契約具体化

| Carry | PoC で具体化できた契約値 |
|---|---|
| CARRY-EMBED-001 | PoC では block.json は未作成。属性候補として `mode`, `embed_url`, `title`, `nonce/payload_id` が必要と確認。 |
| CARRY-EMBED-002 | 主変種の sandbox 属性は `allow-scripts` のみ。form-action 通電検証用に `allow-scripts allow-forms` 変種を併設。postMessage は `{ type: 'ane-embed:ready'|'ane-embed:height', nonce, height }` を `event.source` + nonce で検証。 |
| CARRY-EMBED-003 | 親 CSP は `frame-src http://127.0.0.1:18090`。sandbox-origin CSP は `default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'none'; img-src 'self'; form-action 'none'; base-uri 'none'; object-src 'none'; frame-ancestors http://127.0.0.1:18080`。allow-forms 変種が SKIP の場合は L4 実WPで form-action 通電を再検証する。 |
| CARRY-EMBED-004 | static mode の host reset は親オリジン配信 `/embed-reset.css` として ShadowRoot 内 `<link rel="stylesheet">` から適用。sanitize allowlist は本 PoC 対象外。 |
| CARRY-EMBED-005 | Abilities API / REST apply endpoint は本 PoC 対象外。 |
| CARRY-EMBED-006 | 検証パイプライン候補として本 `verify.py` の CSS 隔離・sandbox・egress・postMessage 検査を CI 化可能。 |

## 参照

- ADR-026: `docs/adr/ADR-026.md`
- Test plan: `docs/test-plan/L3-test-plan.md` §10 TC-066 / TC-067 / TC-068 / TC-079
- WHATWG HTML `<iframe sandbox>`: https://html.spec.whatwg.org/multipage/iframe-embed-object.html#attr-iframe-sandbox
- MDN CSP `style-src`: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/style-src
- W3C CSP3 directives: `connect-src`, `img-src`, `form-action`: https://www.w3.org/TR/CSP3/
- Playwright Python docs: events / frame evaluate

## Raw artifacts

```json
{
  "console": [
    {
      "text": "Failed to load resource: the server responded with a status of 404 (Not Found)",
      "type": "error"
    },
    {
      "text": "Connecting to 'http://127.0.0.1:18080/sink/fetch' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked.",
      "type": "error"
    },
    {
      "text": "Fetch API cannot load http://127.0.0.1:18080/sink/fetch. Refused to connect because it violates the document's Content Security Policy.",
      "type": "error"
    },
    {
      "text": "Connecting to 'http://127.0.0.1:18080/sink/xhr' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked.",
      "type": "error"
    },
    {
      "text": "Connecting to 'http://127.0.0.1:18080/sink/beacon' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked.",
      "type": "error"
    },
    {
      "text": "Loading the image 'http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781' violates the following Content Security Policy directive: \"img-src 'self'\". The action has been blocked.",
      "type": "error"
    },
    {
      "text": "Blocked form submission to 'http://127.0.0.1:18080/sink/form-post' because the form's frame is sandboxed and the 'allow-forms' permission is not set.",
      "type": "error"
    },
    {
      "text": "Framing 'http://127.0.0.1:18080/sink/form-action-csp-post' violates the following Content Security Policy directive: \"frame-src http://127.0.0.1:18090\". The request has been blocked.\n",
      "type": "error"
    },
    {
      "text": "Sending form data to 'http://127.0.0.1:18080/sink/form-action-csp-post' violates the following Content Security Policy directive: \"form-action 'none'\". The request has been blocked.\n",
      "type": "error"
    }
  ],
  "egress_channel_evidence": {
    "fetch": {
      "api_result_reference_only": {
        "detail": "TypeError",
        "status": "blocked"
      },
      "block_reason": "CSP",
      "console_evidence": [
        "Connecting to 'http://127.0.0.1:18080/sink/fetch' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked.",
        "Fetch API cannot load http://127.0.0.1:18080/sink/fetch. Refused to connect because it violates the document's Content Security Policy."
      ],
      "playwright_requests": [],
      "request_failures": [],
      "responses": []
    },
    "form": {
      "api_result_reference_only": {
        "detail": "sandbox should prevent network before CSP",
        "status": "submitted-call-returned"
      },
      "block_reason": "sandbox",
      "console_evidence": [
        "Blocked form submission to 'http://127.0.0.1:18080/sink/form-post' because the form's frame is sandboxed and the 'allow-forms' permission is not set."
      ],
      "playwright_requests": [],
      "request_failures": [],
      "responses": []
    },
    "img": {
      "api_result_reference_only": {
        "detail": "error",
        "status": "blocked"
      },
      "block_reason": "CSP",
      "console_evidence": [
        "Loading the image 'http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781' violates the following Content Security Policy directive: \"img-src 'self'\". The action has been blocked."
      ],
      "playwright_requests": [
        "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"
      ],
      "request_failures": [
        {
          "failure": "csp",
          "url": "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"
        }
      ],
      "responses": []
    },
    "sendBeacon": {
      "api_result_reference_only": {
        "detail": "return=true",
        "status": "queued-by-api"
      },
      "block_reason": "CSP",
      "console_evidence": [
        "Connecting to 'http://127.0.0.1:18080/sink/beacon' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked."
      ],
      "playwright_requests": [],
      "request_failures": [],
      "responses": []
    },
    "xhr": {
      "api_result_reference_only": {
        "detail": "error",
        "status": "blocked"
      },
      "block_reason": "CSP",
      "console_evidence": [
        "Connecting to 'http://127.0.0.1:18080/sink/xhr' violates the following Content Security Policy directive: \"connect-src 'none'\". The action has been blocked."
      ],
      "playwright_requests": [
        "http://127.0.0.1:18080/sink/xhr"
      ],
      "request_failures": [
        {
          "failure": "csp",
          "url": "http://127.0.0.1:18080/sink/xhr"
        }
      ],
      "responses": []
    }
  },
  "egress_results": {
    "fetch": {
      "detail": "TypeError",
      "status": "blocked"
    },
    "form": {
      "detail": "sandbox should prevent network before CSP",
      "status": "submitted-call-returned"
    },
    "img": {
      "detail": "error",
      "status": "blocked"
    },
    "sendBeacon": {
      "detail": "return=true",
      "status": "queued-by-api"
    },
    "xhr": {
      "detail": "error",
      "status": "blocked"
    }
  },
  "egress_wait": {
    "observed_keys": [
      "fetch",
      "form",
      "img",
      "sendBeacon",
      "xhr"
    ],
    "settle_ms": 300,
    "settled": true,
    "sink_request_events": 2,
    "timeout_ms": 8000
  },
  "form_action_csp_evidence": {
    "api_result_reference_only": null,
    "block_reason": "CSP",
    "console_evidence": [
      "Framing 'http://127.0.0.1:18080/sink/form-action-csp-post' violates the following Content Security Policy directive: \"frame-src http://127.0.0.1:18090\". The request has been blocked.\n",
      "Sending form data to 'http://127.0.0.1:18080/sink/form-action-csp-post' violates the following Content Security Policy directive: \"form-action 'none'\". The request has been blocked.\n"
    ],
    "playwright_requests": [],
    "request_failures": [],
    "responses": []
  },
  "form_action_csp_result": null,
  "form_action_csp_results": {},
  "form_action_csp_sink_log": [],
  "form_action_csp_violations": [],
  "form_action_csp_wait": {
    "observed_keys": [],
    "settle_ms": 300,
    "settled": false,
    "sink_request_events": 2,
    "timeout_ms": 8000
  },
  "iframe_attrs": {
    "formActionSandbox": "allow-scripts allow-forms",
    "formActionSrc": "http://127.0.0.1:18090/embed-allow-forms.html?nonce=ane-poc-nonce-20260620&sink=http%3A//127.0.0.1%3A18080/sink&probe=form-only&formPath=form-action-csp-post",
    "formActionTitle": "AGENT NEO interactive form-action CSP PoC",
    "heightAttr": "160",
    "heightStyle": "",
    "sandbox": "allow-scripts",
    "src": "http://127.0.0.1:18090/embed.html?nonce=ane-poc-nonce-20260620&sink=http%3A//127.0.0.1%3A18080/sink",
    "title": "AGENT NEO interactive embed PoC"
  },
  "message_state": {
    "accepted": [
      {
        "data": {
          "height": 160,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:ready"
        },
        "origin": "null"
      },
      {
        "data": {
          "height": 180,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:height"
        },
        "origin": "null"
      },
      {
        "data": {
          "height": 201,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:height"
        },
        "origin": "null"
      }
    ],
    "origins": [
      "null",
      "null",
      "null",
      "null",
      "null",
      "null",
      "null",
      "null",
      "http://127.0.0.1:18080"
    ],
    "rejected": [
      {
        "data": {
          "height": 777,
          "nonce": "bad-nonce",
          "type": "ane-embed:height"
        },
        "origin": "null",
        "reason": "nonce"
      },
      {
        "data": {
          "height": 160,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:ready"
        },
        "origin": "null",
        "reason": "source"
      },
      {
        "data": {
          "height": 777,
          "nonce": "bad-nonce",
          "type": "ane-embed:height"
        },
        "origin": "null",
        "reason": "source"
      },
      {
        "data": {
          "height": 180,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:height"
        },
        "origin": "null",
        "reason": "source"
      },
      {
        "data": {
          "height": 180,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:height"
        },
        "origin": "null",
        "reason": "source"
      },
      {
        "data": {
          "height": 999,
          "nonce": "ane-poc-nonce-20260620",
          "type": "ane-embed:height"
        },
        "origin": "http://127.0.0.1:18080",
        "reason": "source"
      }
    ],
    "sandboxOrigin": "http://127.0.0.1:18090"
  },
  "parent_metrics": {
    "longTaskCount": 0,
    "longTaskMaxMs": 0,
    "longTaskObserverSupported": true,
    "navigationDurationMs": 148.39999961853027
  },
  "parent_origin": "http://127.0.0.1:18080",
  "request_failures": [
    {
      "failure": "csp",
      "url": "http://127.0.0.1:18080/sink/xhr"
    },
    {
      "failure": "csp",
      "url": "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"
    }
  ],
  "route_mode": false,
  "sandbox_metrics": {
    "longTaskCount": 0,
    "longTaskMaxMs": 0,
    "longTaskObserverSupported": true
  },
  "sandbox_origin": "http://127.0.0.1:18090",
  "sink_log": [],
  "sink_request_events": [
    {
      "ts_ms": 214.55,
      "url": "http://127.0.0.1:18080/sink/xhr"
    },
    {
      "ts_ms": 258.88,
      "url": "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"
    }
  ],
  "sink_requests_seen_by_playwright": [
    "http://127.0.0.1:18080/sink/xhr",
    "http://127.0.0.1:18080/sink/img-beacon.gif?ts=1782006605781"
  ],
  "sink_responses": [],
  "static_inheritance_warning": null,
  "static_styles": {
    "cardBackground": "rgb(232, 244, 255)",
    "cardBorderColor": "rgb(18, 92, 160)",
    "cardBorderWidth": "2px",
    "explicitBackground": "rgb(232, 244, 255)",
    "explicitColor": "rgb(16, 24, 32)",
    "explicitFont": "Arial, sans-serif",
    "hasShadowRoot": true,
    "hostBackground": "rgb(238, 247, 255)",
    "hostColor": "rgb(1, 2, 3)",
    "hostFont": "Arial, sans-serif",
    "inheritedBackground": "rgb(232, 244, 255)",
    "inheritedBorderColor": "rgb(18, 92, 160)",
    "inheritedColor": "rgb(1, 2, 3)",
    "inheritedFont": "Arial, sans-serif",
    "inlineStyleCount": 0,
    "lightBackground": "rgb(250, 250, 250)",
    "resetLinkHref": "http://127.0.0.1:18080/embed-reset.css",
    "resetRules": [
      ":host { color-scheme: initial !important; forced-color-adjust: initial !important; math-depth: initial !important; position: initial !important; position-anchor: initial !important; text-size-adjust: initial !important; appearance: initial !important; font-feature-settings: initial !important; font-kerning: initial !important; font-language-override: initial !important; font-optical-sizing: initial !important; font-palette: initial !important; font-size: initial !important; font-size-adjust: initial !important; font-stretch: initial !important; font-style: initial !important; font-synthesis: initial !important; font-variant: initial !important; font-variation-settings: initial !important; font-weight: initial !important; position-area: initial !important; text-orientation: initial !important; text-rendering: initial !important; text-spacing-trim: initial !important; -webkit-font-smoothing: initial !important; -webkit-locale: initial !important; -webkit-text-orientation: initial !important; -webkit-writing-mode: initial !important; writing-mode: initial !important; zoom: initial !important; accent-color: initial !important; place-content: initial !important; place-items: initial !important; place-self: initial !important; alignment-baseline: initial !important; anchor-name: initial !important; anchor-scope: initial !important; animation-composition: initial !important; animation: initial !important; animation-trigger: initial !important; app-region: initial !important; aspect-ratio: initial !important; backdrop-filter: initial !important; backface-visibility: initial !important; background-blend-mode: initial !important; baseline-shift: initial !important; baseline-source: initial !important; block-size: initial !important; border-block: initial !important; border: initial !important; border-radius: initial !important; border-collapse: initial !important; border-end-end-radius: initial !important; border-end-start-radius: initial !important; border-inline: initial !important; border-shape: initial !important; border-start-end-radius: initial !important; border-start-start-radius: initial !important; inset: initial !important; box-decoration-break: initial !important; box-shadow: initial !important; break-after: initial !important; break-before: initial !important; break-inside: initial !important; buffered-rendering: initial !important; caption-side: initial !important; caret-animation: initial !important; caret-color: initial !important; caret-shape: initial !important; clear: initial !important; clip: initial !important; clip-path: initial !important; clip-rule: initial !important; color-interpolation: initial !important; color-interpolation-filters: initial !important; color-rendering: initial !important; columns: initial !important; column-fill: initial !important; gap: initial !important; column-rule: initial !important; column-span: initial !important; contain: initial !important; contain-intrinsic-block-size: initial !important; contain-intrinsic-size: initial !important; contain-intrinsic-inline-size: initial !important; container: initial !important; content: initial !important; content-visibility: initial !important; corner-shape: initial !important; corner-block-end-shape: initial !important; corner-block-start-shape: initial !important; counter-increment: initial !important; counter-reset: initial !important; counter-set: initial !important; cursor: initial !important; cx: initial !important; cy: initial !important; d: initial !important; dominant-baseline: initial !important; dynamic-range-limit: initial !important; empty-cells: initial !important; field-sizing: initial !important; fill: initial !important; fill-opacity: initial !important; fill-rule: initial !important; filter: initial !important; flex: initial !important; flex-flow: initial !important; float: initial !important; flood-color: initial !important; flood-opacity: initial !important; grid: initial !important; grid-area: initial !important; height: initial !important; hyphenate-character: initial !important; hyphenate-limit-chars: initial !important; hyphens: initial !important; image-orientation: initial !important; image-rendering: initial !important; initial-letter: initial !important; inline-size: initial !important; inset-block: initial !important; inset-inline: initial !important; interactivity: initial !important; interest-delay: initial !important; interpolate-size: initial !important; isolation: initial !important; letter-spacing: initial !important; lighting-color: initial !important; line-break: initial !important; list-style: initial !important; margin-block: initial !important; margin: initial !important; margin-inline: initial !important; marker: initial !important; mask: initial !important; mask-type: initial !important; math-shift: initial !important; math-style: initial !important; max-block-size: initial !important; max-height: initial !important; max-inline-size: initial !important; max-width: initial !important; min-block-size: initial !important; min-height: initial !important; min-inline-size: initial !important; min-width: initial !important; mix-blend-mode: initial !important; object-fit: initial !important; object-position: initial !important; object-view-box: initial !important; offset: initial !important; opacity: initial !important; order: initial !important; orphans: initial !important; outline: initial !important; outline-offset: initial !important; overflow-anchor: initial !important; overflow-block: initial !important; overflow-clip-margin: initial !important; overflow-inline: initial !important; overflow-wrap: initial !important; overflow: initial !important; overlay: initial !important; overscroll-behavior-block: initial !important; overscroll-behavior-inline: initial !important; overscroll-behavior: initial !important; padding-block: initial !important; padding-inline: initial !important; page: initial !important; page-orientation: initial !important; paint-order: initial !important; perspective: initial !important; perspective-origin: initial !important; pointer-events: initial !important; position-try: initial !important; position-visibility: initial !important; print-color-adjust: initial !important; quotes: initial !important; r: initial !important; reading-flow: initial !important; reading-order: initial !important; resize: initial !important; rotate: initial !important; ruby-align: initial !important; ruby-position: initial !important; rx: initial !important; ry: initial !important; scale: initial !important; scroll-behavior: initial !important; scroll-initial-target: initial !important; scroll-margin-block: initial !important; scroll-margin: initial !important; scroll-margin-inline: initial !important; scroll-marker-group: initial !important; scroll-padding-block: initial !important; scroll-padding: initial !important; scroll-padding-inline: initial !important; scroll-snap-align: initial !important; scroll-snap-stop: initial !important; scroll-snap-type: initial !important; scroll-target-group: initial !important; scroll-timeline: initial !important; scrollbar-color: initial !important; scrollbar-gutter: initial !important; scrollbar-width: initial !important; shape-image-threshold: initial !important; shape-margin: initial !important; shape-outside: initial !important; shape-rendering: initial !important; size: initial !important; speak: initial !important; stop-color: initial !important; stop-opacity: initial !important; stroke: initial !important; stroke-dasharray: initial !important; stroke-dashoffset: initial !important; stroke-linecap: initial !important; stroke-linejoin: initial !important; stroke-miterlimit: initial !important; stroke-opacity: initial !important; stroke-width: initial !important; tab-size: initial !important; table-layout: initial !important; text-align: initial !important; text-align-last: initial !important; text-anchor: initial !important; text-autospace: initial !important; text-box: initial !important; text-combine-upright: initial !important; text-decoration: initial !important; text-decoration-skip-ink: initial !important; text-emphasis: initial !important; text-emphasis-position: initial !important; text-indent: initial !important; text-justify: initial !important; text-overflow: initial !important; text-shadow: initial !important; text-transform: initial !important; text-underline-offset: initial !important; text-underline-position: initial !important; text-wrap: initial !important; timeline-scope: initial !important; timeline-trigger: initial !important; touch-action: initial !important; transform: initial !important; transform-box: initial !important; transform-origin: initial !important; transform-style: initial !important; transition: initial !important; translate: initial !important; trigger-scope: initial !important; user-select: initial !important; vector-effect: initial !important; vertical-align: initial !important; view-timeline: initial !important; view-transition-class: initial !important; view-transition-group: initial !important; view-transition-name: initial !important; view-transition-scope: initial !important; visibility: initial !important; border-spacing: initial !important; -webkit-box-align: initial !important; -webkit-box-decoration-break: initial !important; -webkit-box-direction: initial !important; -webkit-box-flex: initial !important; -webkit-box-ordinal-group: initial !important; -webkit-box-orient: initial !important; -webkit-box-pack: initial !important; -webkit-box-reflect: initial !important; -webkit-line-break: initial !important; -webkit-line-clamp: initial !important; -webkit-mask-box-image: initial !important; -webkit-rtl-ordering: initial !important; -webkit-ruby-position: initial !important; -webkit-tap-highlight-color: initial !important; -webkit-text-combine: initial !important; -webkit-text-decorations-in-effect: initial !important; -webkit-text-fill-color: initial !important; -webkit-text-security: initial !important; -webkit-text-stroke: initial !important; -webkit-user-drag: initial !important; white-space-collapse: initial !important; widows: initial !important; width: initial !important; will-change: initial !important; word-break: initial !important; word-spacing: initial !important; x: initial !important; y: initial !important; z-index: initial !important; display: block !important; box-sizing: border-box !important; padding: 12px !important; color: rgb(1, 2, 3) !important; font-family: Arial, sans-serif !important; line-height: 1.5 !important; background: rgb(238, 247, 255) !important; }",
      ":host *, :host ::before, :host ::after { box-sizing: border-box; }",
      ".shadow-card { border: 2px solid rgb(18, 92, 160); padding: 12px; background: rgb(232, 244, 255); }",
      "#shadow-explicit { color: rgb(16, 24, 32); font-family: Arial, sans-serif; background: rgb(232, 244, 255); border-color: rgb(18, 92, 160); }",
      "#shadow-inherited { color: inherit; font-family: inherit; background: rgb(232, 244, 255); border: 1px solid rgb(18, 92, 160); }",
      ".light-leak-target { background: rgb(0, 255, 0) !important; }"
    ],
    "resetState": {
      "error": false,
      "href": "http://127.0.0.1:18080/embed-reset.css",
      "loaded": true
    }
  }
}
```
